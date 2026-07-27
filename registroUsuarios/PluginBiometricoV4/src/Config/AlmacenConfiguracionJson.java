/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Config;

import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

/**
 * Reemplaza la antigua capa de configuración en SQLite (DB.Conexion, cuyo
 * fuente ya no existe en el proyecto) por un archivo JSON, en la misma ruta
 * que usa el plugin PluginBiometrico.NET para permitir migrar de un plugin al
 * otro sin reconfigurar la estación.
 *
 * @author Mauricio Herrera
 */
public class AlmacenConfiguracionJson {

    private static final String CARPETA = "PluginBiometrico";
    private static final String ARCHIVO_CONFIG = "config.json";
    private static final Gson GSON = new GsonBuilder().setPrettyPrinting().create();

    private static final String[] RUTAS_BD_LEGADAS = {
        "src/DB/Config.db",
        "DB/Config.db",
        "Config.db"
    };

    private AlmacenConfiguracionJson() {
    }

    public static File carpetaConfiguracion() {
        String base = System.getenv("LOCALAPPDATA");
        if (base == null || base.isEmpty()) {
            base = System.getProperty("user.home");
        }
        return new File(base, CARPETA);
    }

    public static File archivoConfiguracion() {
        return new File(carpetaConfiguracion(), ARCHIVO_CONFIG);
    }

    /**
     * @return la configuración guardada, o null si nunca se ha configurado
     * esta estación (ni en JSON ni en la base de datos legada).
     */
    public static ConfiguracionLocal cargar() {
        File archivo = archivoConfiguracion();
        if (archivo.exists()) {
            try (FileReader lector = new FileReader(archivo)) {
                ConfiguracionLocal cfg = GSON.fromJson(lector, ConfiguracionLocal.class);
                if (cfg != null) {
                    return cfg;
                }
            } catch (IOException ex) {
                System.out.println("Error leyendo config.json " + ex.getMessage());
            }
        }
        ConfiguracionLocal migrada = migrarDesdeSqliteSiExiste();
        if (migrada != null) {
            guardar(migrada);
            return migrada;
        }
        return null;
    }

    public static ConfiguracionLocal cargarOCrearPorDefecto() {
        ConfiguracionLocal cfg = cargar();
        return cfg != null ? cfg : ConfiguracionLocal.porDefecto();
    }

    public static boolean guardar(ConfiguracionLocal cfg) {
        try {
            File carpeta = carpetaConfiguracion();
            if (!carpeta.exists()) {
                carpeta.mkdirs();
            }
            try (FileWriter escritor = new FileWriter(archivoConfiguracion())) {
                GSON.toJson(cfg, escritor);
            }
            return true;
        } catch (IOException ex) {
            System.out.println("Error guardando config.json " + ex.getMessage());
            return false;
        }
    }

    /**
     * Migra una sola vez: si tiene éxito, renombra la base legada (a
     * .migrado) para no volver a resucitar una config.json que el usuario
     * borró intencionalmente para reconfigurar la estación desde cero.
     */
    private static ConfiguracionLocal migrarDesdeSqliteSiExiste() {
        for (String ruta : RUTAS_BD_LEGADAS) {
            File bd = new File(ruta);
            if (bd.exists()) {
                ConfiguracionLocal cfg = leerConfigDesdeSqlite(bd);
                if (cfg != null) {
                    marcarBaseDatosLegadaComoMigrada(bd);
                    return cfg;
                }
            }
        }
        return null;
    }

    private static void marcarBaseDatosLegadaComoMigrada(File bd) {
        File absoluta = bd.getAbsoluteFile();
        File destino = new File(absoluta.getParentFile(), absoluta.getName() + ".migrado");
        if (destino.exists()) {
            destino.delete();
        }
        absoluta.renameTo(destino);
    }

    private static ConfiguracionLocal leerConfigDesdeSqlite(File bd) {
        try {
            Class.forName("org.sqlite.JDBC");
        } catch (ClassNotFoundException ex) {
            // el driver moderno se autorregistra vía META-INF/services; se ignora si falla
        }
        String url = "jdbc:sqlite:" + bd.getAbsolutePath();
        try (Connection con = DriverManager.getConnection(url); Statement st = con.createStatement(); ResultSet rs = st.executeQuery("select * from CONFIG where estado = 'activo'")) {
            if (rs.next()) {
                ConfiguracionLocal cfg = ConfiguracionLocal.porDefecto();
                cfg.setIdUnicoPc(rs.getString("uniqueId"));
                cfg.setUrlHabilitarSensor(rs.getString("urlHabSensor"));
                cfg.setUrlApiRest(rs.getString("urlRestApi"));
                String navegador = rs.getString("browser");
                if (navegador != null && !navegador.isEmpty()) {
                    cfg.setNavegador(navegador);
                }
                return cfg;
            }
        } catch (SQLException ex) {
            System.out.println("Error migrando configuración legada " + ex.getMessage());
        }
        return null;
    }
}
