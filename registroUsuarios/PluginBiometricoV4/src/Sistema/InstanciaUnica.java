/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Sistema;

import java.io.File;
import java.io.IOException;
import java.io.RandomAccessFile;
import java.nio.channels.FileChannel;
import java.nio.channels.FileLock;
import java.nio.channels.OverlappingFileLockException;

/**
 * Guard de instancia única equivalente al Mutex "Global\PluginBiometrico.Singleton"
 * del plugin .NET, implementado con un bloqueo de archivo (sin dependencias nuevas).
 *
 * @author Mauricio Herrera
 */
public class InstanciaUnica {

    private static final String ARCHIVO_LOCK = "plugin.lock";

    private static RandomAccessFile archivo;
    private static FileChannel canal;
    private static FileLock candado;

    private InstanciaUnica() {
    }

    /**
     * @param carpetaConfiguracion carpeta donde se guarda el candado (misma
     * carpeta que config.json y plugin.log).
     * @return true si esta es la instancia principal (se obtuvo el candado).
     */
    public static synchronized boolean adquirir(File carpetaConfiguracion) {
        try {
            if (!carpetaConfiguracion.exists()) {
                carpetaConfiguracion.mkdirs();
            }
            archivo = new RandomAccessFile(new File(carpetaConfiguracion, ARCHIVO_LOCK), "rw");
            canal = archivo.getChannel();
            candado = canal.tryLock();
            return candado != null;
        } catch (IOException | OverlappingFileLockException ex) {
            return false;
        }
    }

    public static synchronized void liberar() {
        try {
            if (candado != null) {
                candado.release();
            }
            if (canal != null) {
                canal.close();
            }
            if (archivo != null) {
                archivo.close();
            }
        } catch (IOException ex) {
            // el proceso está terminando, no hay nada más que hacer
        }
    }
}
