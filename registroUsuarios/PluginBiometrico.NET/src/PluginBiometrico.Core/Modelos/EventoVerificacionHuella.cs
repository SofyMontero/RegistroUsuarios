namespace PluginBiometrico.Core.Modelos;

/// <summary>
/// Muestra capturada en modo lectura/verificación.
/// CaracteristicasBiometricas es opaco (FeatureSet del SDK) — solo lo usa Infraestructura.
/// </summary>
public sealed class EventoVerificacionHuella
{
    public string Mensaje { get; init; } = string.Empty;

    public byte[]? ImagenJpeg { get; init; }

    public object? CaracteristicasBiometricas { get; init; }
}
