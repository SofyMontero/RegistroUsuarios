/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Helper;

import java.io.File;
import java.io.IOException;

/**
 *
 * @author Mauricio Herrera
 */
public class Utils {

    public static String complement = "\\dist";

    public Utils() {
    }

    public static void restartApplication() {
        try {
            String current = new java.io.File(".").getCanonicalPath() + complement;
            String nameapp = "PluginBiometricoV3.exe";
            File archivo = new File(current + "\\" + nameapp);
            if (!archivo.exists()) {
                nameapp = "PluginBiometricoV3.jar";
            }
            new ProcessBuilder("cmd", "/c start /min " + current + "\\" + nameapp + " ^& exit").start();
            System.exit(0);
        } catch (IOException ex) {
            System.out.println("Error reiniciando " + ex);
        }
    }
}
