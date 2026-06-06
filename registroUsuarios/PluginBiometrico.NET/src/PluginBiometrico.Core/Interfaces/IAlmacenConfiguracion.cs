using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Guarda y lee la configuración local del plugin.
/// Reemplaza la clase Java DB.Conexion.
/// </summary>
public interface IAlmacenConfiguracion
{
    /// <summary>Devuelve true si ya existe un archivo de configuración válido.</summary>
    bool ExisteConfiguracion();

    /// <summary>Lee la configuración guardada. Devuelve null si no existe o está vacía.</summary>
    ConfiguracionLocal? Cargar();

    /// <summary>Guarda la configuración en disco.</summary>
    void Guardar(ConfiguracionLocal configuracion);

    /// <summary>Ruta completa del archivo config.json (útil para soporte técnico).</summary>
    string ObtenerRutaArchivo();
}
