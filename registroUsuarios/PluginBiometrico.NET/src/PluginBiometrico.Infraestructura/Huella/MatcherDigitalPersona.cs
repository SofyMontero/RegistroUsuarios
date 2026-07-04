using PluginBiometrico.Core.Interfaces;

namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>Comparador de huellas Digital Persona One Touch (.NET).</summary>
public sealed partial class MatcherDigitalPersona : IMatcherHuellas
{
    public bool SdkDisponible => SdkDigitalPersona.EstaDisponible();

    public bool CoincideConPlantilla(object caracteristicasBiometricas, byte[] plantillaReferencia)
    {
        if (!SdkDisponible)
        {
            return false;
        }

        return CoincideConPlantillaReal(caracteristicasBiometricas, plantillaReferencia);
    }

    private partial bool CoincideConPlantillaReal(object caracteristicasBiometricas, byte[] plantillaReferencia);
}
