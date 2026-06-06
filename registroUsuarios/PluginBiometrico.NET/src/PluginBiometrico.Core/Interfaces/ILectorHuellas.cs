using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Abstracción del lector Digital Persona U.are.U.
/// </summary>
public interface ILectorHuellas : IDisposable
{
    bool SdkDisponible { get; }

    event EventHandler<EventoMuestraHuella>? MuestraProcesada;

    event EventHandler<string>? MensajeEstado;

    void IniciarCaptura();

    void DetenerCaptura();
}
