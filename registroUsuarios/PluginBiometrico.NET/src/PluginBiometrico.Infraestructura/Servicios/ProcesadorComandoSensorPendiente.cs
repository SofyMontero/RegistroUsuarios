using PluginBiometrico.Core.Interfaces;

namespace PluginBiometrico.Infraestructura.Servicios;

/// <summary>
/// Placeholder del Sprint 2: confirma que el comando llegó.
/// El Sprint 3 conectará aquí el lector Digital Persona.
/// </summary>
public sealed class ProcesadorComandoSensorPendiente : IProcesadorComandoSensor
{
    private readonly IRegistroEventos _registro;
    private readonly Action<string>? _notificarBandeja;

    public ProcesadorComandoSensorPendiente(
        IRegistroEventos registro,
        Action<string>? notificarBandeja = null)
    {
        _registro = registro;
        _notificarBandeja = notificarBandeja;
    }

    public Task ProcesarCapturaAsync(CancellationToken cancellationToken)
    {
        const string mensaje = "Modo captura activado (lector pendiente — Sprint 3).";
        _registro.Info(mensaje);
        _notificarBandeja?.Invoke(mensaje);
        return Task.CompletedTask;
    }

    public Task ProcesarLecturaAsync(CancellationToken cancellationToken)
    {
        const string mensaje = "Modo lectura activado (lector pendiente — Sprint 3).";
        _registro.Info(mensaje);
        _notificarBandeja?.Invoke(mensaje);
        return Task.CompletedTask;
    }
}
