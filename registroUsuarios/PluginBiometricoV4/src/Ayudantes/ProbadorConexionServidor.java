/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Ayudantes;

import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import com.google.gson.JsonSyntaxException;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;

/**
 * Verifica que el servidor PHP responda antes de guardar la configuración,
 * replicando PluginBiometrico.App.Ayudantes.ProbadorConexionServidor del
 * plugin .NET (primero prueba HabilitarSensor.php con ping=1; si falla,
 * intenta UsuarioRestApi.php como respaldo).
 *
 * @author Mauricio Herrera
 */
public class ProbadorConexionServidor {

    public static final class Resultado {

        public final boolean exito;
        public final String mensaje;

        public Resultado(boolean exito, String mensaje) {
            this.exito = exito;
            this.mensaje = mensaje;
        }
    }

    private ProbadorConexionServidor() {
    }

    public static Resultado probar(String urlHabilitarSensor, String urlApiRest, String idUnicoPc) {
        if (!ConstructorUrlsServidor.esUrlValida(urlHabilitarSensor)) {
            return new Resultado(false, "La URL del sensor no es válida. Use http:// o https://");
        }
        if (!ConstructorUrlsServidor.esUrlValida(urlApiRest)) {
            return new Resultado(false, "La URL de la API REST no es válida. Use http:// o https://");
        }
        if (idUnicoPc == null || idUnicoPc.trim().isEmpty()) {
            return new Resultado(false, "Indique el ID único de esta PC (token).");
        }

        String token;
        try {
            token = URLEncoder.encode(idUnicoPc.trim(), "UTF-8");
        } catch (UnsupportedEncodingException ex) {
            token = idUnicoPc.trim();
        }

        String urlSensor = urlHabilitarSensor.trim() + "?timestamp=0&token=" + token + "&ping=1&_=" + System.currentTimeMillis();
        Resultado sensor = probarSensor(urlSensor);
        if (sensor.exito) {
            return sensor;
        }

        String urlApi = urlApiRest.trim() + "?desde=0&hasta=0&token=" + token;
        Resultado api = probarApiRest(urlApi);
        if (api.exito) {
            return new Resultado(true, "Conexión con el servidor correcta (API REST). "
                    + "Actualice HabilitarSensor.php en el servidor para habilitar la prueba directa del sensor.");
        }

        return new Resultado(false, sensor.mensaje != null ? sensor.mensaje : api.mensaje);
    }

    private static Resultado probarSensor(String url) {
        try {
            HttpURLConnection con = abrirConexion(url);
            int codigo = con.getResponseCode();
            String cuerpo = leerCuerpo(con, codigo);
            con.disconnect();
            if (codigo < 200 || codigo >= 300) {
                return new Resultado(false, "HabilitarSensor.php respondió con error HTTP " + codigo + ".");
            }
            JsonObject json = new JsonParser().parse(cuerpo).getAsJsonObject();
            String operacion = json.has("opc") && !json.get("opc").isJsonNull() ? json.get("opc").getAsString() : "reintentar";
            return new Resultado(true, "Conexión correcta. El sensor responde (operación: " + operacion + ").");
        } catch (JsonSyntaxException | IllegalStateException ex) {
            return new Resultado(false, "HabilitarSensor.php respondió, pero no devolvió JSON válido.");
        } catch (IOException ex) {
            return new Resultado(false, "No se pudo conectar a HabilitarSensor.php: " + ex.getMessage());
        }
    }

    private static Resultado probarApiRest(String url) {
        try {
            HttpURLConnection con = abrirConexion(url);
            int codigo = con.getResponseCode();
            String cuerpo = leerCuerpo(con, codigo);
            con.disconnect();
            if (codigo < 200 || codigo >= 300) {
                return new Resultado(false, "UsuarioRestApi.php respondió con error HTTP " + codigo + ".");
            }
            if (!cuerpo.trim().startsWith("[")) {
                return new Resultado(false, "UsuarioRestApi.php respondió, pero el JSON no es un arreglo válido.");
            }
            return new Resultado(true, "Conexión correcta vía API REST.");
        } catch (IOException ex) {
            return new Resultado(false, "No se pudo conectar: " + ex.getMessage());
        }
    }

    private static HttpURLConnection abrirConexion(String url) throws IOException {
        HttpURLConnection con = (HttpURLConnection) new URL(url).openConnection();
        con.setConnectTimeout(12000);
        con.setReadTimeout(12000);
        con.setRequestMethod("GET");
        con.setRequestProperty("User-Agent", "PluginBiometricoV4");
        con.setRequestProperty("Accept-Charset", "UTF-8");
        return con;
    }

    private static String leerCuerpo(HttpURLConnection con, int codigo) throws IOException {
        InputStream flujo = (codigo >= 200 && codigo < 300) ? con.getInputStream() : con.getErrorStream();
        if (flujo == null) {
            return "";
        }
        StringBuilder sb = new StringBuilder();
        try (BufferedReader in = new BufferedReader(new InputStreamReader(flujo))) {
            String linea;
            while ((linea = in.readLine()) != null) {
                sb.append(linea);
            }
        }
        return sb.toString();
    }
}
