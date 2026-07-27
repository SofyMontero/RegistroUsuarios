/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Logging;

import Config.AlmacenConfiguracionJson;
import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
import java.nio.file.Files;
import java.nio.file.StandardCopyOption;
import java.text.SimpleDateFormat;
import java.util.Date;

/**
 * Log legible en %LocalAppData%\PluginBiometrico\plugin.log con rotación,
 * replicando el esquema de PluginBiometrico.Infraestructura.Logging.RegistroArchivo
 * del plugin .NET (máx. 1 MB, hasta 3 respaldos .1/.2/.3).
 *
 * @author Mauricio Herrera
 */
public class RegistroArchivo {

    private static final String ARCHIVO_LOG = "plugin.log";
    private static final long MAXIMO_BYTES = 1_000_000L;
    private static final int MAXIMO_RESPALDOS = 3;

    private RegistroArchivo() {
    }

    public static synchronized void info(String mensaje) {
        escribir("INFO", mensaje);
    }

    public static synchronized void warn(String mensaje) {
        escribir("WARN", mensaje);
    }

    public static synchronized void error(String mensaje) {
        escribir("ERROR", mensaje);
    }

    public static synchronized void error(String mensaje, Throwable ex) {
        escribir("ERROR", mensaje + " - " + ex.getMessage());
    }

    private static void escribir(String nivel, String mensaje) {
        try {
            File carpeta = AlmacenConfiguracionJson.carpetaConfiguracion();
            if (!carpeta.exists()) {
                carpeta.mkdirs();
            }
            File archivo = new File(carpeta, ARCHIVO_LOG);
            rotarSiEsNecesario(archivo);
            String linea = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(new Date())
                    + " [" + nivel + "] " + mensaje;
            try (PrintWriter escritor = new PrintWriter(new FileWriter(archivo, true))) {
                escritor.println(linea);
            }
        } catch (IOException ex) {
            System.out.println("Error escribiendo plugin.log " + ex.getMessage());
        }
    }

    private static void rotarSiEsNecesario(File archivo) throws IOException {
        if (!archivo.exists() || archivo.length() < MAXIMO_BYTES) {
            return;
        }
        File carpeta = archivo.getParentFile();
        for (int i = MAXIMO_RESPALDOS - 1; i >= 1; i--) {
            File origen = new File(carpeta, ARCHIVO_LOG + "." + i);
            File destino = new File(carpeta, ARCHIVO_LOG + "." + (i + 1));
            if (origen.exists()) {
                Files.move(origen.toPath(), destino.toPath(), StandardCopyOption.REPLACE_EXISTING);
            }
        }
        File primerRespaldo = new File(carpeta, ARCHIVO_LOG + ".1");
        Files.move(archivo.toPath(), primerRespaldo.toPath(), StandardCopyOption.REPLACE_EXISTING);
    }
}
