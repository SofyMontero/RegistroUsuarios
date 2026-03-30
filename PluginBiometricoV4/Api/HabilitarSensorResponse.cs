using System.Text.Json.Serialization;

namespace PluginBiometricoV4.Api;

/// <summary>
/// Respuesta JSON de <c>Model/HabilitarSensor.php</c> (fecha_creacion en segundos Unix, opc: capturar | leer | reintentar | stop).
/// </summary>
public sealed class HabilitarSensorResponse
{
    [JsonPropertyName("fecha_creacion")]
    public long FechaCreacion { get; init; }

    [JsonPropertyName("opc")]
    public string Opc { get; init; } = "reintentar";
}
