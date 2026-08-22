/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package FIngerUtils;

import java.awt.AWTException;
import java.awt.HeadlessException;
import java.awt.Toolkit;

/**
 *
 * @author Mauricio Herrera
 */
public class GetCapturarHuella {

    public static CapturarHuella ch;
    public static int BARRA_DE_ESTADO = 40;

    public static CapturarHuella getCapturarHuella() throws AWTException {
        if (ch == null) {
            try {
                ch = new CapturarHuella();
                int sizeX = ch.getWidth() + 4;
                int sizeY = ch.getHeight();
                int maxSizeX = (int) Toolkit.getDefaultToolkit().getScreenSize().getWidth();
                int maxSizeY = (int) Toolkit.getDefaultToolkit().getScreenSize().getHeight();
                ch.setLocation(maxSizeX - sizeX, maxSizeY - sizeY - BARRA_DE_ESTADO);
                ch.setAlwaysOnTop(true);
                ch.requestFocus();
                ch.setVisible(true);
            } catch (HeadlessException | SecurityException e) {
                System.out.println("Error " + e.getMessage());
            }
        } else if (!ch.isVisible()) {
            ch.setVisible(true);
        }
        return ch;
    }

    public static void setCapturarHuella() {
        if (ch != null) {
            ch.dispose();
            ch = null;
        }
    }

}
