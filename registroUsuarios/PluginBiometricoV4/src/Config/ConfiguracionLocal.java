/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Config;

/**
 *
 * @author Mauricio Herrera
 */
public class ConfiguracionLocal {

    private String idUnicoPc;
    private String urlHabilitarSensor;
    private String urlApiRest;
    private String navegador;
    private boolean autoInicioConfigurado;
    private int puertoWebSocketLocal;
    private boolean habilitarWebSocketLocal;
    private boolean modoComunicacionRapida;

    public ConfiguracionLocal() {
    }

    public static ConfiguracionLocal porDefecto() {
        ConfiguracionLocal cfg = new ConfiguracionLocal();
        cfg.idUnicoPc = "";
        cfg.urlHabilitarSensor = "";
        cfg.urlApiRest = "";
        cfg.navegador = "Chrome";
        cfg.autoInicioConfigurado = false;
        cfg.puertoWebSocketLocal = 17890;
        cfg.habilitarWebSocketLocal = true;
        cfg.modoComunicacionRapida = true;
        return cfg;
    }

    public String getIdUnicoPc() {
        return idUnicoPc;
    }

    public void setIdUnicoPc(String idUnicoPc) {
        this.idUnicoPc = idUnicoPc;
    }

    public String getUrlHabilitarSensor() {
        return urlHabilitarSensor;
    }

    public void setUrlHabilitarSensor(String urlHabilitarSensor) {
        this.urlHabilitarSensor = urlHabilitarSensor;
    }

    public String getUrlApiRest() {
        return urlApiRest;
    }

    public void setUrlApiRest(String urlApiRest) {
        this.urlApiRest = urlApiRest;
    }

    public String getNavegador() {
        return navegador;
    }

    public void setNavegador(String navegador) {
        this.navegador = navegador;
    }

    public boolean isAutoInicioConfigurado() {
        return autoInicioConfigurado;
    }

    public void setAutoInicioConfigurado(boolean autoInicioConfigurado) {
        this.autoInicioConfigurado = autoInicioConfigurado;
    }

    public int getPuertoWebSocketLocal() {
        return puertoWebSocketLocal;
    }

    public void setPuertoWebSocketLocal(int puertoWebSocketLocal) {
        this.puertoWebSocketLocal = puertoWebSocketLocal;
    }

    public boolean isHabilitarWebSocketLocal() {
        return habilitarWebSocketLocal;
    }

    public void setHabilitarWebSocketLocal(boolean habilitarWebSocketLocal) {
        this.habilitarWebSocketLocal = habilitarWebSocketLocal;
    }

    public boolean isModoComunicacionRapida() {
        return modoComunicacionRapida;
    }

    public void setModoComunicacionRapida(boolean modoComunicacionRapida) {
        this.modoComunicacionRapida = modoComunicacionRapida;
    }

    public boolean esValida() {
        return idUnicoPc != null && !idUnicoPc.isEmpty()
                && urlHabilitarSensor != null && !urlHabilitarSensor.isEmpty()
                && urlApiRest != null && !urlApiRest.isEmpty();
    }
}
