using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Ejecuta la acción cuando el servidor pide capturar o leer.
/// Sprint 2: solo registra el evento. Sprint 3+: integra el lector físico.
/// </summary>
public interface IProcesadorComandoSensor
{
    Task ProcesarCapturaAsync(ComandoSensor comando, CancellationToken cancellationToken);

    Task ProcesarLecturaAsync(ComandoSensor comando, CancellationToken cancellationToken);
}
