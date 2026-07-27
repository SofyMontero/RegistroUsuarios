#if TIENE_SDK_DPFP_ACTIVEX
using DPFPEngXLib;
using DPFPShrXLib;

namespace PluginBiometrico.Infraestructura.Huella;

public sealed partial class MatcherDigitalPersona
{
    private partial bool CoincideConPlantillaReal(object caracteristicasBiometricas, byte[] plantillaReferencia)
    {
        if (caracteristicasBiometricas is not CaracteristicasActiveX caracteristicas)
        {
            return false;
        }

        var plantilla = new DPFPTemplateClass();
        plantilla.Deserialize(plantillaReferencia);
        var verificador = new DPFPVerificationClass();
        var resultado = (IDPFPVerificationResult)verificador.Verify(caracteristicas.Valor, plantilla);
        return resultado.Verified;
    }
}
#endif
