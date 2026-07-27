/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package WebSocket;

import Logging.RegistroArchivo;
import com.google.gson.Gson;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.net.ServerSocket;
import java.net.Socket;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.Base64;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.ConcurrentHashMap;

/**
 * Servidor WebSocket local mínimo (RFC 6455), sin dependencias externas, que
 * replica el rol de PluginBiometrico.Infraestructura.WebSocket.ServidorWebSocketLocal
 * del plugin .NET: retransmite en tiempo real a la web los mismos eventos que
 * hoy se conocen por polling, en ws://127.0.0.1:{puerto}/eventos.
 *
 * @author Mauricio Herrera
 */
public class ServidorWebSocketLocal {

    private static final String GUID_WEBSOCKET = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
    private static final String RUTA_EVENTOS = "/eventos";

    private static volatile ServidorWebSocketLocal instanciaActiva;

    private final int puerto;
    private final Gson gson = new Gson();
    private final Set<Socket> clientes = ConcurrentHashMap.newKeySet();

    private ServerSocket servidor;
    private volatile boolean activo;

    public ServidorWebSocketLocal(int puerto) {
        this.puerto = puerto;
    }

    /**
     * Instancia en ejecución, para que CapturarHuella/LecturaHuella (creados
     * por los singletons GetCapturarHuella/GetLecturaHuella, sin acceso al
     * hilo que arrancó el servidor) puedan emitir eventos sin necesitar una
     * referencia inyectada.
     */
    public static ServidorWebSocketLocal obtenerInstanciaActiva() {
        return instanciaActiva;
    }

    public void iniciar() {
        if (activo) {
            return;
        }
        try {
            servidor = new ServerSocket();
            servidor.bind(new InetSocketAddress("127.0.0.1", puerto));
            activo = true;
            instanciaActiva = this;
            Thread hilo = new Thread(this::aceptarConexiones, "ws-biometrico-accept");
            hilo.setDaemon(true);
            hilo.start();
            RegistroArchivo.info("WebSocket local activo en ws://127.0.0.1:" + puerto + RUTA_EVENTOS);
            emitir("servidor_iniciado", null);
        } catch (IOException ex) {
            RegistroArchivo.error("No se pudo iniciar el WebSocket local en el puerto " + puerto, ex);
        }
    }

    public void detener() {
        if (!activo) {
            return;
        }
        activo = false;
        if (instanciaActiva == this) {
            instanciaActiva = null;
        }
        emitir("stop", null);
        for (Socket cliente : clientes) {
            cerrarSilenciosamente(cliente);
        }
        clientes.clear();
        try {
            if (servidor != null) {
                servidor.close();
            }
        } catch (IOException ex) {
            // se está cerrando, no hay nada más que hacer
        }
    }

    /**
     * Envía {tipo, datos, timestamp} a todos los clientes conectados. No-op
     * si no hay clientes, igual que en el plugin .NET.
     */
    public void emitir(String tipo, Object datos) {
        if (clientes.isEmpty()) {
            return;
        }
        Map<String, Object> evento = new HashMap<>();
        evento.put("tipo", tipo);
        evento.put("datos", datos);
        evento.put("timestamp", System.currentTimeMillis());
        byte[] frame = construirFrameTexto(gson.toJson(evento).getBytes(StandardCharsets.UTF_8));
        Iterator<Socket> it = clientes.iterator();
        while (it.hasNext()) {
            Socket cliente = it.next();
            try {
                OutputStream salida = cliente.getOutputStream();
                synchronized (cliente) {
                    salida.write(frame);
                    salida.flush();
                }
            } catch (IOException ex) {
                it.remove();
                cerrarSilenciosamente(cliente);
            }
        }
    }

    private void aceptarConexiones() {
        while (activo) {
            try {
                Socket socket = servidor.accept();
                Thread manejador = new Thread(() -> manejarConexion(socket), "ws-biometrico-cliente");
                manejador.setDaemon(true);
                manejador.start();
            } catch (IOException ex) {
                if (activo) {
                    RegistroArchivo.error("Error aceptando conexión WebSocket", ex);
                }
            }
        }
    }

    private void manejarConexion(Socket socket) {
        try {
            InputStream entrada = socket.getInputStream();
            if (!realizarHandshake(entrada, socket.getOutputStream())) {
                cerrarSilenciosamente(socket);
                return;
            }
            clientes.add(socket);
            emitir("cliente_conectado", "clientes=" + clientes.size());
            while (activo) {
                int primerByte = entrada.read();
                if (primerByte == -1) {
                    break;
                }
                int segundoByte = leerByte(entrada);
                int opcode = primerByte & 0x0F;
                boolean enmascarado = (segundoByte & 0x80) != 0;
                long longitud = segundoByte & 0x7F;
                if (longitud == 126) {
                    longitud = (leerByte(entrada) << 8) | leerByte(entrada);
                } else if (longitud == 127) {
                    longitud = 0;
                    for (int i = 0; i < 8; i++) {
                        longitud = (longitud << 8) | leerByte(entrada);
                    }
                }
                byte[] mascara = new byte[4];
                if (enmascarado) {
                    leerCompleto(entrada, mascara, 4);
                }
                byte[] datos = new byte[(int) longitud];
                leerCompleto(entrada, datos, datos.length);
                if (opcode == 0x8) { // close
                    break;
                }
            }
        } catch (IOException ex) {
            // conexión cerrada por el cliente, nada más que hacer
        } finally {
            clientes.remove(socket);
            cerrarSilenciosamente(socket);
        }
    }

    private int leerByte(InputStream entrada) throws IOException {
        int b = entrada.read();
        if (b == -1) {
            throw new IOException("Conexión cerrada leyendo frame WebSocket");
        }
        return b;
    }

    private void leerCompleto(InputStream entrada, byte[] destino, int longitud) throws IOException {
        int leidos = 0;
        while (leidos < longitud) {
            int n = entrada.read(destino, leidos, longitud - leidos);
            if (n == -1) {
                throw new IOException("Conexión cerrada leyendo frame WebSocket");
            }
            leidos += n;
        }
    }

    private boolean realizarHandshake(InputStream entrada, OutputStream salida) throws IOException {
        StringBuilder solicitud = new StringBuilder();
        int b;
        int saltosDeLinea = 0;
        while ((b = entrada.read()) != -1) {
            solicitud.append((char) b);
            if (b == '\n') {
                saltosDeLinea++;
            } else if (b != '\r') {
                saltosDeLinea = 0;
            }
            if (saltosDeLinea == 2) {
                break;
            }
        }
        String peticion = solicitud.toString();
        String claveCliente = extraerCabecera(peticion, "Sec-WebSocket-Key");
        if (!peticion.contains(RUTA_EVENTOS) || claveCliente == null) {
            escribirRespuesta404(salida);
            return false;
        }
        String aceptacion = calcularAceptacion(claveCliente);
        String respuesta = "HTTP/1.1 101 Switching Protocols\r\n"
                + "Upgrade: websocket\r\n"
                + "Connection: Upgrade\r\n"
                + "Sec-WebSocket-Accept: " + aceptacion + "\r\n\r\n";
        salida.write(respuesta.getBytes(StandardCharsets.UTF_8));
        salida.flush();
        return true;
    }

    private void escribirRespuesta404(OutputStream salida) throws IOException {
        salida.write("HTTP/1.1 404 Not Found\r\nConnection: close\r\n\r\n".getBytes(StandardCharsets.UTF_8));
        salida.flush();
    }

    private String extraerCabecera(String peticion, String nombre) {
        for (String linea : peticion.split("\r\n")) {
            int separador = linea.indexOf(':');
            if (separador > 0 && linea.substring(0, separador).trim().equalsIgnoreCase(nombre)) {
                return linea.substring(separador + 1).trim();
            }
        }
        return null;
    }

    private String calcularAceptacion(String claveCliente) throws IOException {
        try {
            MessageDigest sha1 = MessageDigest.getInstance("SHA-1");
            byte[] hash = sha1.digest((claveCliente + GUID_WEBSOCKET).getBytes(StandardCharsets.UTF_8));
            return Base64.getEncoder().encodeToString(hash);
        } catch (NoSuchAlgorithmException ex) {
            throw new IOException("SHA-1 no disponible", ex);
        }
    }

    private byte[] construirFrameTexto(byte[] payload) {
        int longitudCabecera;
        if (payload.length <= 125) {
            longitudCabecera = 2;
        } else if (payload.length <= 65535) {
            longitudCabecera = 4;
        } else {
            longitudCabecera = 10;
        }
        byte[] frame = new byte[longitudCabecera + payload.length];
        frame[0] = (byte) 0x81; // FIN + opcode texto
        if (payload.length <= 125) {
            frame[1] = (byte) payload.length;
        } else if (payload.length <= 65535) {
            frame[1] = 126;
            frame[2] = (byte) ((payload.length >> 8) & 0xFF);
            frame[3] = (byte) (payload.length & 0xFF);
        } else {
            frame[1] = 127;
            long longitud = payload.length;
            for (int i = 0; i < 8; i++) {
                frame[2 + i] = (byte) ((longitud >> (8 * (7 - i))) & 0xFF);
            }
        }
        System.arraycopy(payload, 0, frame, longitudCabecera, payload.length);
        return frame;
    }

    private void cerrarSilenciosamente(Socket socket) {
        try {
            socket.close();
        } catch (IOException ex) {
            // ya se está cerrando
        }
    }
}
