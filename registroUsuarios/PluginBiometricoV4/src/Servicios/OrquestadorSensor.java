/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Servicios;

import Config.AlmacenConfiguracionJson;
import Config.ConfiguracionLocal;
import Logging.RegistroArchivo;
import UI.HabilitarLector;
import WebSocket.ServidorWebSocketLocal;
import java.util.Date;
import java.util.HashMap;
import java.util.Map;

/**
 * Bucle de fondo que reemplaza el TimerTask de UI.Start: consulta
 * HabilitarSensor.php, despacha capturar/leer/stop y emite los eventos por
 * WebSocket. Replica el "modo rápido" del OrquestadorSensor del plugin .NET:
 * espera 300 ms cuando el servidor responde "reintentar" (nada que hacer) y
 * modoComunicacionRapida está activo, o 1000 ms en cualquier otro caso
 * (incluyendo errores).
 *
 * @author Mauricio Herrera
 */
public class OrquestadorSensor {

    private static final long ESPERA_NORMAL_MS = 1000L;
    private static final long ESPERA_RAPIDA_MS = 300L;

    private final ServidorWebSocketLocal servidorWs;
    private volatile boolean activo;
    private Thread hilo;
    private long ultimaFechaConocida = new Date().getTime() / 1000;

    public OrquestadorSensor(ServidorWebSocketLocal servidorWs) {
        this.servidorWs = servidorWs;
    }

    public void iniciar() {
        if (activo) {
            return;
        }
        activo = true;
        hilo = new Thread(this::ejecutar, "orquestador-sensor");
        hilo.setDaemon(true);
        hilo.start();
    }

    public void detener() {
        activo = false;
        if (hilo != null) {
            hilo.interrupt();
        }
    }

    private void ejecutar() {
        while (activo) {
            long esperaMs = ESPERA_NORMAL_MS;
            try {
                ConfiguracionLocal cfg = AlmacenConfiguracionJson.cargarOCrearPorDefecto();
                HabilitarLector lector = new HabilitarLector();
                ultimaFechaConocida = lector.sendGet(ultimaFechaConocida, cfg.getIdUnicoPc());

                Map<String, Object> datos = new HashMap<>();
                datos.put("operacion", lector.getUltimaOperacion());
                datos.put("fechaCreacion", ultimaFechaConocida);
                datos.put("documento", lector.getUltimoDocumento());
                emitir("comando", datos);

                if (cfg.isModoComunicacionRapida() && "reintentar".equals(lector.getUltimaOperacion())) {
                    esperaMs = ESPERA_RAPIDA_MS;
                }
            } catch (Exception ex) {
                String mensaje = ex.getMessage() == null ? "" : ex.getMessage();
                if (mensaje.contains("504")) {
                    RegistroArchivo.error("El servidor respondió 504 (tiempo de espera agotado). Revise HabilitarSensor.php", ex);
                } else {
                    RegistroArchivo.error("Error consultando el sensor, se reintentará en 1 segundo", ex);
                }
                emitir("error", mensaje);
                esperaMs = ESPERA_NORMAL_MS;
            }
            try {
                Thread.sleep(esperaMs);
            } catch (InterruptedException ex) {
                Thread.currentThread().interrupt();
                activo = false;
            }
        }
    }

    private void emitir(String tipo, Object datos) {
        if (servidorWs != null) {
            servidorWs.emitir(tipo, datos);
        }
    }
}
