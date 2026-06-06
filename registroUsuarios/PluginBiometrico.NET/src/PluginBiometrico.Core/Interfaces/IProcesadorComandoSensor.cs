namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Ejecuta la acción cuando el servidor pide capturar o leer.
/// Sprint 2: solo registra el evento. Sprint 3+: integra el lector físico.
/// </summary>
public interface IProcesadorComandoSensor
{
    Task ProcesarCapturaAsync(CancellationToken cancellationToken);

    Task ProcesarLecturaAsync(CancellationToken cancellationToken);
}
