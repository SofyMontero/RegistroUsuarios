using System.Windows;
using PluginBiometrico.App.Tray;
using PluginBiometrico.App.Ventanas;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Infraestructura.Config;

namespace PluginBiometrico.App;

/// <summary>
/// Punto de entrada del plugin. Reemplaza Start.java.
/// </summary>
public partial class App : System.Windows.Application
{
    private TrayApplication? _tray;

    protected override void OnStartup(StartupEventArgs e)
    {
        base.OnStartup(e);

        IAlmacenConfiguracion almacen = new AlmacenConfiguracionJson();

        if (!almacen.ExisteConfiguracion())
        {
            var ventanaInicial = new VentanaConfiguracion(almacen, permitirCancelar: false);
            var guardoConfig = ventanaInicial.ShowDialog() == true;

            if (!guardoConfig || !almacen.ExisteConfiguracion())
            {
                Shutdown();
                return;
            }
        }

        _tray = new TrayApplication(almacen);
        _tray.Iniciar();
    }

    protected override void OnExit(ExitEventArgs e)
    {
        _tray?.Dispose();
        base.OnExit(e);
    }
}
