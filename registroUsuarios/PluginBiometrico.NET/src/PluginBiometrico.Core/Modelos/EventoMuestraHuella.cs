namespace PluginBiometrico.Core.Modelos;

/// <summary>
/// Resultado de cada muestra capturada por el lector.
/// </summary>
public sealed class EventoMuestraHuella
{
    public string Mensaje { get; init; } = string.Empty;

    public string EstadoPlantilla { get; init; } = string.Empty;

    public byte[]? ImagenJpeg { get; init; }

    public byte[]? PlantillaSerializada { get; init; }

    public EstadoEnrollment Estado { get; init; } = EstadoEnrollment.EnProgreso;
}
