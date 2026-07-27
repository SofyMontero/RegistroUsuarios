/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package UI;

import Config.AlmacenConfiguracionJson;
import Config.ConfiguracionLocal;
import Helper.Utils;
import Sistema.GestorInicioAutomatico;
import ds.desktop.notify.DesktopNotify;
import java.awt.AWTException;
import java.awt.Image;
import java.awt.Menu;
import java.awt.MenuItem;
import java.awt.PopupMenu;
import java.awt.SystemTray;
import java.awt.TrayIcon;
import java.awt.event.ActionEvent;
import java.io.IOException;
import javax.swing.ImageIcon;

/**
 *
 * @author Mauricio Herrera
 */
public class TrayClass {

    private static final String NOMBRE_SERVICIO = "PluginBiometricoV3.exe";
    private static final String[] NAVEGADORES = {"Chrome", "Mozilla", "Edge", "Explorer"};

    static TrayIcon trayIcon;

    public static void show() throws IOException {

        if (trayIcon == null) {
            if (!SystemTray.isSupported()) {
                System.exit(0);
            }
            trayIcon = new TrayIcon(createIcon("/Imagenes/tryicon.png", "Icon"));
            trayIcon.setToolTip("Sensor Biometrico");
            final SystemTray tray = SystemTray.getSystemTray();
            final PopupMenu menu = new PopupMenu();
            addMenuBrowser(menu);
            menu.addSeparator();
            addMenuConfig(menu);
            addMenuUpdateConfig(menu);
            MenuItem addRegistro = new MenuItem("Crear Inicio Automatico");
            MenuItem removeRegistro = new MenuItem("Eliminar Inicio Automatico");
            MenuItem close = new MenuItem("Cerrar");

            addRegistro.addActionListener((e) -> {
                boolean activado = GestorInicioAutomatico.activarConLanzadorActual(NOMBRE_SERVICIO);
                if (activado) {
                    DesktopNotify.showDesktopMessage("Aviso..!", "La aplicación ahora iniciara con el sistema operativo",
                            DesktopNotify.SUCCESS, 4000L);
                } else {
                    DesktopNotify.showDesktopMessage("Aviso..!", "No fue posible configurar el inicio automático",
                            DesktopNotify.ERROR, 3000L);
                }
            });

            removeRegistro.addActionListener((e) -> {
                boolean eliminado = GestorInicioAutomatico.desactivar(NOMBRE_SERVICIO);
                if (eliminado) {
                    DesktopNotify.showDesktopMessage("Aviso..!", "La aplicación ya no iniciará con el sistema operativo",
                            DesktopNotify.SUCCESS, 4000L);
                } else {
                    DesktopNotify.showDesktopMessage("Aviso..!", "No hay registro de auto inicio..!",
                            DesktopNotify.INFORMATION, 3000L);
                }
            });
            close.addActionListener((e) -> {
                System.exit(0);
            });
            menu.addSeparator();
            menu.add(addRegistro);
            menu.add(removeRegistro);
            menu.addSeparator();
            menu.add(close);
            trayIcon.setPopupMenu(menu);
            try {
                tray.add(trayIcon);
            } catch (AWTException e) {
                System.out.println("Error " + e);
            }
        }
    }

    private static Image createIcon(String imagen, String icon) {
        Image imageIcon = new javax.swing.ImageIcon(TrayClass.class.getResource(imagen)).getImage();
        return (new ImageIcon(imageIcon, icon)).getImage();
    }

    private static void addMenuBrowser(PopupMenu menu) {
        ConfiguracionLocal cfg = AlmacenConfiguracionJson.cargarOCrearPorDefecto();
        Menu browserMenu = new Menu("Navegador");
        for (String nombre : NAVEGADORES) {
            String etiqueta = nombre.equals(cfg.getNavegador()) ? "✓ " + nombre : nombre;
            MenuItem item = new MenuItem(etiqueta);
            item.addActionListener((ActionEvent e) -> {
                ConfiguracionLocal actual = AlmacenConfiguracionJson.cargarOCrearPorDefecto();
                actual.setNavegador(nombre);
                AlmacenConfiguracionJson.guardar(actual);
                Utils.restartApplication();
            });
            browserMenu.add(item);
        }
        menu.add(browserMenu);
    }

    private static void addMenuUpdateConfig(PopupMenu menu) {
        Menu updateMenu = new Menu("Actualizar Configuración");
        for (String nombre : NAVEGADORES) {
            MenuItem item = new MenuItem(nombre);
            item.addActionListener((ActionEvent e) -> updateConfig(nombre));
            updateMenu.add(item);
        }
        menu.add(updateMenu);
    }

    private static void addMenuConfig(PopupMenu menu) {
        MenuItem config = new MenuItem("Nueva Configuración");
        menu.add(config);
        config.addActionListener((e) -> {
            ConfigForm cf = new ConfigForm();
            cf.setLocationRelativeTo(null);
            cf.setVisible(true);
        });
    }

    private static void updateConfig(String browser) {
        ConfigForm cf = new ConfigForm();
        cf.setLocationRelativeTo(null);
        cf.setVisible(true);
        cf.cboBrowser.setSelectedItem(browser);
    }

}
