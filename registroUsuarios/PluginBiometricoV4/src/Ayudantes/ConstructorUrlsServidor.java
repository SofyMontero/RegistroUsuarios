/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Ayudantes;

import java.net.URI;
import java.net.URISyntaxException;

/**
 * Construye las URLs del plugin a partir de la URL base del servidor web,
 * replicando PluginBiometrico.App.Ayudantes.ConstructorUrlsServidor del
 * plugin .NET.
 *
 * @author Mauricio Herrera
 */
public class ConstructorUrlsServidor {

    private ConstructorUrlsServidor() {
    }

    public static String[] desdeUrlBase(String urlBase) {
        String baseNorm = urlBase.trim();
        while (baseNorm.endsWith("/")) {
            baseNorm = baseNorm.substring(0, baseNorm.length() - 1);
        }
        if (baseNorm.toLowerCase().endsWith("/model")) {
            baseNorm = baseNorm.substring(0, baseNorm.length() - "/model".length());
            while (baseNorm.endsWith("/")) {
                baseNorm = baseNorm.substring(0, baseNorm.length() - 1);
            }
        }
        if (baseNorm.toLowerCase().endsWith(".php")) {
            int indice = baseNorm.lastIndexOf('/');
            if (indice > 0) {
                baseNorm = baseNorm.substring(0, indice);
            }
        }
        return new String[]{
            baseNorm + "/Model/HabilitarSensor.php",
            baseNorm + "/Model/UsuarioRestApi.php"
        };
    }

    public static boolean esUrlValida(String url) {
        if (url == null) {
            return false;
        }
        try {
            URI uri = new URI(url.trim());
            String esquema = uri.getScheme();
            return uri.isAbsolute() && ("http".equalsIgnoreCase(esquema) || "https".equalsIgnoreCase(esquema));
        } catch (URISyntaxException ex) {
            return false;
        }
    }
}
