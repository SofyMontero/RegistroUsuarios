using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Cliente HTTP hacia HabilitarSensor.php y UsuarioRestApi.php.
/// Reemplaza finger_temp.java y HabilitarLector.java.
/// </summary>
public interface IClienteApiBiometrica
{
    Task<ComandoSensor> EsperarComandoAsync(long ultimaFechaUnix, CancellationToken cancellationToken);

    Task GuardarHuellaAsync(GuardarHuellaRequest datos, CancellationToken cancellationToken);

    Task ActualizarHuellaAsync(ActualizarHuellaRequest datos, CancellationToken cancellationToken);

    Task<IReadOnlyList<PlantillaUsuario>> ObtenerPlantillasAsync(int desde, int hasta, CancellationToken cancellationToken);
}
