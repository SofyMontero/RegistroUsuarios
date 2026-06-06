using System.Text.Json.Serialization;

namespace PluginBiometrico.Core.Modelos;

/// <summary>
/// Respuesta de HabilitarSensor.php cuando hay un cambio en huellas_temp.
/// </summary>
public sealed class ComandoSensor
{
    [JsonPropertyName("fecha_creacion")]
    public long FechaCreacion { get; set; }

    /// <summary>Valores: capturar, leer, reintentar, stop.</summary>
    [JsonPropertyName("opc")]
    public string Operacion { get; set; } = "reintentar";
}
