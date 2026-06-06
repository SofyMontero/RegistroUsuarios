namespace PluginBiometrico.Core.Modelos;

/// <summary>
/// Configuración guardada en la PC del operador.
/// Equivale a la tabla CONFIG del plugin Java (Config.db).
/// </summary>
public sealed class ConfiguracionLocal
{
    /// <summary>Token único de esta PC. Se envía al backend como "serial" o "token".</summary>
    public string IdUnicoPc { get; set; } = string.Empty;

    /// <summary>URL de HabilitarSensor.php — el plugin consulta aquí si debe capturar o leer.</summary>
    public string UrlHabilitarSensor { get; set; } = string.Empty;

    /// <summary>URL de UsuarioRestApi.php — aquí se envían y consultan las huellas.</summary>
    public string UrlApiRest { get; set; } = string.Empty;

    /// <summary>Navegador asociado a esta estación (Chrome, Mozilla, Edge, Explorer).</summary>
    public string Navegador { get; set; } = "Chrome";

    /// <summary>Indica si ya se configuró el inicio automático con Windows.</summary>
    public bool AutoInicioConfigurado { get; set; }
}
