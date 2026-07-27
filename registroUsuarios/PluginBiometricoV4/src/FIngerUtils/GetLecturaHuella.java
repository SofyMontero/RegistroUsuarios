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
public class GetLecturaHuella {

    public static LecturaHuella lh;
    public static int BARRA_DE_ESTADO = 40;

    public static LecturaHuella getLecturarHuella() throws AWTException {
        if (lh == null) {
            try {
                lh = new LecturaHuella();
                int sizeX = lh.getWidth() + 4;
                int sizeY = lh.getHeight();
                int maxSizeX = (int) Toolkit.getDefaultToolkit().getScreenSize().getWidth();
                int maxSizeY = (int) Toolkit.getDefaultToolkit().getScreenSize().getHeight();
                lh.setLocation(maxSizeX - sizeX, maxSizeY - sizeY - BARRA_DE_ESTADO);
                lh.setAlwaysOnTop(true);
                lh.requestFocus();
                lh.setVisible(true);
            } catch (HeadlessException | SecurityException e) {
                System.out.println("Error " + e.getMessage());
            }
        } else if (!lh.isVisible()) {
            lh.setVisible(true);
        }
        return lh;
    }

    public static void setLecturarHuella() {
        lh = null;
    }

}
