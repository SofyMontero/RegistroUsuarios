using System.Windows;
using PluginBiometrico.App.Sistema;
using PluginBiometrico.App.Tray;
using PluginBiometrico.App.Ventanas;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Infraestructura.Config;
using PluginBiometrico.Infraestructura.Logging;

namespace PluginBiometrico.App;

/// <summary>
/// Punto de entrada del plugin. Reemplaza Start.java.
/// </summary>
public partial class App : System.Windows.Application
{
    private InstanciaUnica? _instanciaUnica;
    private TrayApplication? _tray;

    protected override void OnStartup(StartupEventArgs e)
    {
        base.OnStartup(e);

        _instanciaUnica = new InstanciaUnica();
        if (!_instanciaUnica.EsInstanciaPrincipal)
        {
            // #region agent log
            AgenteDiagnostico.Registrar("S5-H4", "App.OnStartup", "Segunda instancia bloqueada", null, "sprint5");
            // #endregion

            System.Windows.MessageBox.Show(
                "El Plugin Biométrico ya está en ejecución.\nRevise el icono en la bandeja del sistema.",
                "Sensor Biométrico",
                MessageBoxButton.OK,
                MessageBoxImage.Information);

            Shutdown();
            return;
        }

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
        _instanciaUnica?.Dispose();
        base.OnExit(e);
    }
}
