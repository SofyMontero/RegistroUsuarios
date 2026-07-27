/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package UI;

import Config.AlmacenConfiguracionJson;
import FIngerUtils.CapturarHuella;
import FIngerUtils.GetCapturarHuella;
import FIngerUtils.GetLecturaHuella;
import FIngerUtils.LecturaHuella;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import java.awt.AWTException;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;

/**
 *
 * @author Mauricio Herrera
 */
public class HabilitarLector {

    private static final String USER_AGENT = "Mozilla/5.0";

    private static String SERVER_PATH;

    private String ultimaOperacion = "reintentar";
    private String ultimoDocumento = "";

    public HabilitarLector() {
        SERVER_PATH = AlmacenConfiguracionJson.cargarOCrearPorDefecto().getUrlHabilitarSensor();
    }

    public String getUltimaOperacion() {
        return ultimaOperacion;
    }

    public String getUltimoDocumento() {
        return ultimoDocumento;
    }

    public long sendGet(long d, String srn) throws IOException, AWTException {
        long timestamp = d;
        StringBuilder stringBuilder = new StringBuilder(SERVER_PATH);
        stringBuilder.append("?timestamp=");
        stringBuilder.append(URLEncoder.encode("" + d, "UTF-8"));
        stringBuilder.append("&token=").append(srn);
        stringBuilder.append("&_=").append(System.currentTimeMillis());

        URL obj = new URL(stringBuilder.toString());

        HttpURLConnection con = (HttpURLConnection) obj.openConnection();
        con.setRequestMethod("GET");
        con.setRequestProperty("User-Agent", USER_AGENT);
        con.setRequestProperty("Accept-Charset", "UTF-8");
        int responseCode = con.getResponseCode();

        StringBuilder respuesta = new StringBuilder();
        try (BufferedReader in = new BufferedReader(new InputStreamReader(con.getInputStream()))) {
            String line;
            while ((line = in.readLine()) != null) {
                respuesta.append(line);
            }
        }
        con.disconnect();

        if (responseCode == 200) {
            JsonParser parser = new JsonParser();
            JsonObject objJson = parser.parse(respuesta.toString()).getAsJsonObject();
            timestamp = objJson.get("fecha_creacion").getAsLong();
            ultimaOperacion = objJson.get("opc").getAsString();
            ultimoDocumento = objJson.has("documento") && !objJson.get("documento").isJsonNull()
                    ? objJson.get("documento").getAsString() : "";

            switch (ultimaOperacion) {
                case "capturar":
                    detenerLecturaSiActiva();
                    if (GetCapturarHuella.ch == null) {
                        CapturarHuella ch = GetCapturarHuella.getCapturarHuella();
                        ch.Iniciar();
                        ch.start();
                    }
                    break;
                case "leer":
                    detenerCapturaSiActiva();
                    if (GetLecturaHuella.lh == null) {
                        LecturaHuella lh = GetLecturaHuella.getLecturarHuella();
                        lh.setDocumentoObjetivo(ultimoDocumento);
                        lh.Iniciar();
                        lh.start();
                    } else {
                        GetLecturaHuella.lh.setDocumentoObjetivo(ultimoDocumento);
                    }
                    break;
                case "stop":
                    detenerCapturaSiActiva();
                    detenerLecturaSiActiva();
                    break;
                default:
                    break;
            }
        }
        return timestamp;

    }

    /**
     * GetCapturarHuella.ch / GetLecturaHuella.lh son la única fuente de
     * verdad sobre si hay una sesión activa (CapturarHuella.guardarHuella()
     * puede disponer la ventana de captura de forma independiente al
     * terminar el enrollment). Antes se pedía la ventana en cada ciclo de
     * poll solo para cerrarla de inmediato, lo que reiniciaba la sesión del
     * lector físico constantemente en vez de dejarlo escuchando en espera
     * del dedo.
     */
    private void detenerLecturaSiActiva() throws AWTException {
        if (GetLecturaHuella.lh != null) {
            GetLecturaHuella.lh.stop();
            GetLecturaHuella.lh.dispose();
            GetLecturaHuella.setLecturarHuella();
        }
    }

    private void detenerCapturaSiActiva() throws AWTException {
        if (GetCapturarHuella.ch != null) {
            GetCapturarHuella.ch.stop();
            GetCapturarHuella.setCapturarHuella();
        }
    }

}
