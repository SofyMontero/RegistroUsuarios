#if !TIENE_SDK_DPFP
namespace PluginBiometrico.Infraestructura.Huella;

public sealed partial class MatcherDigitalPersona
{
    private partial bool CoincideConPlantillaReal(object caracteristicasBiometricas, byte[] plantillaReferencia) => false;
}
#endif
