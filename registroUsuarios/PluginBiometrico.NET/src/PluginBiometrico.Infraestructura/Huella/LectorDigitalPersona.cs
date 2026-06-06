using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>
/// Lector biométrico Digital Persona One Touch (.NET).
/// Compila con stub si no hay SDK; usa hardware real cuando existen las DLL en Librerias/.
/// </summary>
public sealed partial class LectorDigitalPersona : ILectorHuellas
{
    public bool SdkDisponible => SdkEstaDisponible();

    public event EventHandler<EventoMuestraHuella>? MuestraProcesada;

    public event EventHandler<EventoVerificacionHuella>? VerificacionCapturada;

    public event EventHandler<string>? MensajeEstado;

    public void IniciarCaptura()
    {
        if (!SdkDisponible)
        {
            MensajeEstado?.Invoke(this,
                "SDK no encontrado. Copie las DLL de Digital Persona One Touch a la carpeta Librerias/.");
            return;
        }

        EstablecerModoCaptura();
        IniciarCapturaReal();
    }

    public void IniciarVerificacion()
    {
        if (!SdkDisponible)
        {
            MensajeEstado?.Invoke(this,
                "SDK no encontrado. Copie las DLL de Digital Persona One Touch a la carpeta Librerias/.");
            return;
        }

        EstablecerModoVerificacion();
        IniciarCapturaReal();
    }

    public void DetenerCaptura()
    {
        if (SdkDisponible)
        {
            DetenerCapturaReal();
        }
    }

    public void Dispose()
    {
        DetenerCaptura();
        LiberarRecursosReal();
    }

    private void NotificarMuestra(EventoMuestraHuella evento) =>
        MuestraProcesada?.Invoke(this, evento);

    private void NotificarMensaje(string mensaje) =>
        MensajeEstado?.Invoke(this, mensaje);

    private void NotificarVerificacion(EventoVerificacionHuella evento) =>
        VerificacionCapturada?.Invoke(this, evento);

    private static partial bool SdkEstaDisponible();
    partial void EstablecerModoCaptura();
    partial void EstablecerModoVerificacion();
    partial void IniciarCapturaReal();
    partial void DetenerCapturaReal();
    partial void LiberarRecursosReal();
}
