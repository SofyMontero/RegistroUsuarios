/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Ayudantes;

import java.net.InetAddress;
import java.net.UnknownHostException;
import java.util.UUID;

/**
 * Genera un identificador único legible para esta estación de trabajo,
 * replicando PluginBiometrico.App.Ayudantes.GeneradorIdEstacion del plugin
 * .NET.
 *
 * @author Mauricio Herrera
 */
public class GeneradorIdEstacion {

    private GeneradorIdEstacion() {
    }

    public static String generar() {
        String nombre = sanitizar(obtenerNombreMaquina());
        if (nombre.isEmpty()) {
            nombre = "PC";
        }
        String sufijo = UUID.randomUUID().toString().replace("-", "").substring(0, 6).toUpperCase();
        return nombre + "-" + sufijo;
    }

    private static String obtenerNombreMaquina() {
        String nombre = System.getenv("COMPUTERNAME");
        if (nombre == null || nombre.trim().isEmpty()) {
            try {
                nombre = InetAddress.getLocalHost().getHostName();
            } catch (UnknownHostException ex) {
                nombre = "";
            }
        }
        return nombre == null ? "" : nombre;
    }

    private static String sanitizar(String texto) {
        StringBuilder resultado = new StringBuilder(texto.length());
        for (char c : texto.trim().toCharArray()) {
            if (Character.isLetterOrDigit(c) || c == '-') {
                resultado.append(c);
            } else if (Character.isWhitespace(c) || c == '_') {
                resultado.append('-');
            }
        }
        return resultado.toString().replaceAll("^-+|-+$", "");
    }
}
