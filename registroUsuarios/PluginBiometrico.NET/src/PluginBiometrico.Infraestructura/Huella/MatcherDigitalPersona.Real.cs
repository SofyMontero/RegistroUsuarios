#if TIENE_SDK_DPFP
using DPFP;
using DPFP.Verification;

namespace PluginBiometrico.Infraestructura.Huella;

public sealed partial class MatcherDigitalPersona
{
    private partial bool CoincideConPlantillaReal(object caracteristicasBiometricas, byte[] plantillaReferencia)
    {
        if (caracteristicasBiometricas is not FeatureSet caracteristicas)
        {
            return false;
        }

        var referencia = new Template();
        referencia.DeSerialize(plantillaReferencia);

        var verificador = new Verification();
        var resultado = new Verification.Result();
        verificador.Verify(caracteristicas, referencia, ref resultado);
        return resultado.Verified;
    }
}
#endif
