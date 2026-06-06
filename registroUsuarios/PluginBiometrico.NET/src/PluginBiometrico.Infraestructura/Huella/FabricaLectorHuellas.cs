using PluginBiometrico.Core.Interfaces;

namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>Crea el lector biométrico (una instancia por sesión de captura).</summary>
public static class FabricaLectorHuellas
{
    public static ILectorHuellas Crear() => new LectorDigitalPersona();
}
