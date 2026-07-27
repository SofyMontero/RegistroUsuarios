#if !TIENE_SDK_DPFP_ACTIVEX
namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>Implementación stub cuando el SDK no está instalado.</summary>
public sealed partial class LectorDigitalPersona
{
    partial void EstablecerModoCaptura() { }

    partial void EstablecerModoVerificacion() { }

    partial void IniciarCapturaReal() { }

    partial void DetenerCapturaReal() { }

    partial void LiberarRecursosReal() { }
}
#endif
