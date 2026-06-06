using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.App.Sistema;

/// <summary>Coordina el registro de Windows con la configuración local.</summary>
public sealed class GestorInicioAutomatico
{
    private readonly IAlmacenConfiguracion _almacen;

    public GestorInicioAutomatico(IAlmacenConfiguracion almacen)
    {
        _almacen = almacen;
    }

    public ResultadoInicioAutomatico Activar()
    {
        var resultado = InicioAutomaticoWindows.Activar();
        if (resultado.Exito)
        {
            ActualizarBanderaConfig(true);
        }

        return resultado;
    }

    public ResultadoInicioAutomatico Desactivar()
    {
        var resultado = InicioAutomaticoWindows.Desactivar();
        if (resultado.Exito)
        {
            ActualizarBanderaConfig(false);
        }

        return resultado;
    }

    public void SincronizarBanderaConRegistro()
    {
        var config = _almacen.Cargar();
        if (config is null)
        {
            return;
        }

        var enRegistro = InicioAutomaticoWindows.EstaConfigurado();
        if (config.AutoInicioConfigurado != enRegistro)
        {
            config.AutoInicioConfigurado = enRegistro;
            _almacen.Guardar(config);
        }
    }

    private void ActualizarBanderaConfig(bool activo)
    {
        var config = _almacen.Cargar();
        if (config is null)
        {
            return;
        }

        config.AutoInicioConfigurado = activo;
        _almacen.Guardar(config);
    }
}
