/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package UI;

import Config.AlmacenConfiguracionJson;
import Config.ConfiguracionLocal;
import Servicios.OrquestadorSensor;
import Sistema.GestorInicioAutomatico;
import Sistema.InstanciaUnica;
import WebSocket.ServidorWebSocketLocal;
import com.jtattoo.plaf.acryl.AcrylLookAndFeel;
import java.io.IOException;
import java.util.Properties;
import javax.swing.UIManager;
import javax.swing.UnsupportedLookAndFeelException;

/**
 *
 * @author Mauricio Herrera
 */
public class Start {

    private static final String NOMBRE_SERVICIO = "PluginBiometricoV3.exe";

    public static void main(String[] args) throws IOException, InterruptedException {

        try {
            Properties props = new Properties();
            props.put("logoString", "M-Systems");
            AcrylLookAndFeel.setCurrentTheme(props);
            UIManager.setLookAndFeel("com.jtattoo.plaf.acryl.AcrylLookAndFeel");
        } catch (ClassNotFoundException | InstantiationException | IllegalAccessException | UnsupportedLookAndFeelException ex) {
            System.out.println("error confiig form");
        }

        if (!InstanciaUnica.adquirir(AlmacenConfiguracionJson.carpetaConfiguracion())) {
            System.out.println("Ya existe una instancia del plugin en ejecución, saliendo.");
            return;
        }
        Runtime.getRuntime().addShutdownHook(new Thread(InstanciaUnica::liberar));

        ConfiguracionLocal cfg = AlmacenConfiguracionJson.cargar();
        if (cfg == null || !cfg.esValida()) {
            ConfigForm cf = new ConfigForm();
            cf.setLocationRelativeTo(null);
            cf.setVisible(true);
            return;
        }

        GestorInicioAutomatico.sincronizarBanderaConRegistro(NOMBRE_SERVICIO);
        if (!GestorInicioAutomatico.estaConfigurado(NOMBRE_SERVICIO)) {
            GestorInicioAutomatico.activarConLanzadorActual(NOMBRE_SERVICIO);
        }

        ServidorWebSocketLocal servidorWs = null;
        if (cfg.isHabilitarWebSocketLocal()) {
            servidorWs = new ServidorWebSocketLocal(cfg.getPuertoWebSocketLocal());
            servidorWs.iniciar();
        }

        new OrquestadorSensor(servidorWs).iniciar();

        TrayClass.show();

    }
}
