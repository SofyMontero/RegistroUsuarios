using PluginBiometrico.Core.Interfaces;

namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>Comparador de huellas Digital Persona One Touch (.NET).</summary>
public sealed partial class MatcherDigitalPersona : IMatcherHuellas
{
    public bool SdkDisponible => SdkMatcherDisponible();

    public bool CoincideConPlantilla(object caracteristicasBiometricas, byte[] plantillaReferencia)
    {
        if (!SdkDisponible)
        {
            return false;
        }

        return CoincideConPlantillaReal(caracteristicasBiometricas, plantillaReferencia);
    }

    private static partial bool SdkMatcherDisponible(); // stub o real
    private partial bool CoincideConPlantillaReal(object caracteristicasBiometricas, byte[] plantillaReferencia);
}
