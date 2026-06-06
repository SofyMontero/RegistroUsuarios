namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Compara características capturadas contra una plantilla almacenada.
/// La implementación real usa DPFP.Verification.
/// </summary>
public interface IMatcherHuellas
{
    bool SdkDisponible { get; }

    bool CoincideConPlantilla(object caracteristicasBiometricas, byte[] plantillaReferencia);
}
