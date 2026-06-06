using PluginBiometrico.Core.Interfaces;

namespace PluginBiometrico.Infraestructura.Huella;

public static class FabricaMatcherHuellas
{
    public static IMatcherHuellas Crear() => new MatcherDigitalPersona();
}
