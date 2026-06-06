namespace PluginBiometrico.Core.Modelos;

/// <summary>Mensaje JSON enviado por WebSocket a la página web.</summary>
public sealed class EventoPluginLocal
{
    public string Tipo { get; set; } = string.Empty;

    public object? Datos { get; set; }

    public long Timestamp { get; set; }
}
