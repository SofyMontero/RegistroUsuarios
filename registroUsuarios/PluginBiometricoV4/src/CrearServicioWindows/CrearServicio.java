/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package CrearServicioWindows;

import Windows.CMD;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.List;

/**
 *
 * @author Mauricio Herrera
 */
public class CrearServicio {

    private static final String CLAVE_RUN = "HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Run";

    public static String addServicesOnWindows(String nameService, String SO, String rutaFile) {
        List<String> comando = new ArrayList<>();
        comando.add("REG");
        comando.add("ADD");
        comando.add("XP".equals(SO) ? "HKEY_CURRENT_USER\\Software\\" + nameService : CLAVE_RUN);
        comando.add("/v");
        comando.add(nameService);
        comando.add("/t");
        comando.add("REG_SZ");
        comando.add("/d");
        comando.add(rutaFile);
        comando.add("/f");
        return ejecutar(comando);
    }

    public static String removeServicesOnWindows(String nameService, String SO) {
        List<String> comando = new ArrayList<>();
        comando.add("REG");
        comando.add("DELETE");
        comando.add("XP".equals(SO) ? "HKEY_CURRENT_USER\\Software\\Pepsi" : CLAVE_RUN);
        comando.add("/v");
        comando.add(nameService);
        comando.add("/f");
        return ejecutar(comando);
    }

    /**
     * Consulta el estado real del registro en vez de confiar en una bandera
     * guardada aparte (usado por Sistema.GestorInicioAutomatico).
     */
    public static boolean existeEnRegistro(String nameService) {
        List<String> comando = new ArrayList<>();
        comando.add("REG");
        comando.add("QUERY");
        comando.add(CLAVE_RUN);
        comando.add("/v");
        comando.add(nameService);
        try {
            ProcessBuilder pb = new ProcessBuilder(comando);
            pb.redirectErrorStream(true);
            Process proceso = pb.start();
            try (BufferedReader read = new BufferedReader(new InputStreamReader(proceso.getInputStream(), CMD.Detectar_Windows()))) {
                while (read.readLine() != null) {
                    // solo interesa el código de salida
                }
            }
            return proceso.waitFor() == 0;
        } catch (IOException | InterruptedException ex) {
            return false;
        }
    }

    private static String ejecutar(List<String> comando) {
        String response = "";
        try {
            ProcessBuilder pb = new ProcessBuilder(comando);
            pb.redirectErrorStream(true);
            Process proceso = pb.start();
            try (BufferedReader read = new BufferedReader(new InputStreamReader(proceso.getInputStream(), CMD.Detectar_Windows()))) {
                String linea;
                while ((linea = read.readLine()) != null) {
                    response += linea + "\n";
                }
            }
            proceso.waitFor();
        } catch (IOException | InterruptedException ex) {
            response = ex.getLocalizedMessage();
        }
        return response;
    }
}
