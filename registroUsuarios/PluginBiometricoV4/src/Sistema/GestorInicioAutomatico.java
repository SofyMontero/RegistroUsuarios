/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Sistema;

import Config.AlmacenConfiguracionJson;
import Config.ConfiguracionLocal;
import CrearServicioWindows.CrearServicio;
import java.io.File;
import java.net.URISyntaxException;

/**
 * Envuelve CrearServicio (REG ADD/DELETE sobre
 * HKCU\Software\Microsoft\Windows\CurrentVersion\Run, igual que el plugin
 * .NET) y mantiene sincronizada la bandera autoInicioConfigurado del JSON con
 * el estado real del registro.
 *
 * @author Mauricio Herrera
 */
public class GestorInicioAutomatico {

    private GestorInicioAutomatico() {
    }

    public static boolean activar(String nombreServicio, String rutaEjecutable) {
        String respuesta = CrearServicio.addServicesOnWindows(nombreServicio, "", rutaEjecutable);
        boolean exito = !respuesta.trim().isEmpty() || CrearServicio.existeEnRegistro(nombreServicio);
        actualizarBanderaConfig(exito);
        return exito;
    }

    public static boolean desactivar(String nombreServicio) {
        CrearServicio.removeServicesOnWindows(nombreServicio, "");
        boolean sigueRegistrado = CrearServicio.existeEnRegistro(nombreServicio);
        actualizarBanderaConfig(sigueRegistrado);
        return !sigueRegistrado;
    }

    public static boolean estaConfigurado(String nombreServicio) {
        return CrearServicio.existeEnRegistro(nombreServicio);
    }

    /**
     * Registra el auto-inicio usando el .jar realmente en ejecución (vía
     * javaw, sin ventana de consola), en vez de asumir un .exe con un nombre
     * fijo que este proyecto no genera.
     */
    public static boolean activarConLanzadorActual(String nombreServicio) {
        String comando = comandoLanzamientoActual();
        return comando != null && activar(nombreServicio, comando);
    }

    private static String comandoLanzamientoActual() {
        try {
            File javaw = new File(System.getProperty("java.home"), "bin" + File.separator + "javaw.exe");
            File origen = new File(GestorInicioAutomatico.class.getProtectionDomain().getCodeSource().getLocation().toURI());
            return "\"" + javaw.getAbsolutePath() + "\" -jar \"" + origen.getAbsolutePath() + "\"";
        } catch (URISyntaxException | SecurityException ex) {
            return null;
        }
    }

    /**
     * Reconcilia ConfiguracionLocal.autoInicioConfigurado con el estado real
     * del registro al arrancar (por si el usuario lo quitó manualmente).
     */
    public static void sincronizarBanderaConRegistro(String nombreServicio) {
        boolean estadoReal = CrearServicio.existeEnRegistro(nombreServicio);
        actualizarBanderaConfig(estadoReal);
    }

    private static void actualizarBanderaConfig(boolean valor) {
        ConfiguracionLocal cfg = AlmacenConfiguracionJson.cargar();
        if (cfg != null && cfg.isAutoInicioConfigurado() != valor) {
            cfg.setAutoInicioConfigurado(valor);
            AlmacenConfiguracionJson.guardar(cfg);
        }
    }
}
