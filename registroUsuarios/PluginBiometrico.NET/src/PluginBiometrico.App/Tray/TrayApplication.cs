using System.Drawing;
using System.IO;
using System.Windows;
using PluginBiometrico.App.Servicios;
using PluginBiometrico.App.Ventanas;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Infraestructura.Logging;
using Application = System.Windows.Application;

namespace PluginBiometrico.App.Tray;

/// <summary>
/// Icono en la bandeja del sistema. Reemplaza TrayClass.java.
/// </summary>
public sealed class TrayApplication : IDisposable
{
    private readonly IAlmacenConfiguracion _almacen;
    private readonly IRegistroEventos _registro;
    private readonly ServicioSensorEnSegundoPlano _servicioSensor;
    private System.Windows.Forms.NotifyIcon? _iconoBandeja;

    public TrayApplication(IAlmacenConfiguracion almacen)
    {
        _almacen = almacen;
        _registro = new RegistroArchivo();
        _servicioSensor = new ServicioSensorEnSegundoPlano(
            _almacen,
            _registro,
            MostrarNotificacion,
            Application.Current.Dispatcher);
    }

    public void Iniciar()
    {
        if (!System.Windows.Forms.SystemInformation.UserInteractive)
        {
            return;
        }

        _iconoBandeja = new System.Windows.Forms.NotifyIcon
        {
            Icon = CargarIcono(),
            Visible = true,
            Text = "Sensor Biométrico"
        };

        var menu = new System.Windows.Forms.ContextMenuStrip();
        menu.Items.Add("Configurar", null, (_, _) => AbrirConfiguracion());
        menu.Items.Add("Ver log", null, (_, _) => AbrirLog());
        menu.Items.Add(new System.Windows.Forms.ToolStripSeparator());
        menu.Items.Add("Cerrar", null, (_, _) => CerrarAplicacion());

        _iconoBandeja.ContextMenuStrip = menu;
        _iconoBandeja.DoubleClick += (_, _) => AbrirConfiguracion();

        _servicioSensor.Iniciar();
    }

    private void MostrarNotificacion(string mensaje)
    {
        _iconoBandeja?.ShowBalloonTip(4000, "Sensor Biométrico", mensaje, System.Windows.Forms.ToolTipIcon.Info);
    }

    private void AbrirConfiguracion()
    {
        var ventana = new VentanaConfiguracion(_almacen);
        if (ventana.ShowDialog() == true)
        {
            _servicioSensor.Detener();
            _servicioSensor.Iniciar();
        }
    }

    private void AbrirLog()
    {
        var ruta = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "PluginBiometrico",
            "plugin.log");

        if (File.Exists(ruta))
        {
            System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
            {
                FileName = ruta,
                UseShellExecute = true
            });
        }
        else
        {
            MostrarNotificacion("Aún no hay eventos en el log.");
        }
    }

    private void CerrarAplicacion()
    {
        Dispose();
        Application.Current.Shutdown();
    }

    private static Icon CargarIcono()
    {
        var rutaIcono = Path.Combine(AppContext.BaseDirectory, "Recursos", "tryicon.png");

        if (File.Exists(rutaIcono))
        {
            using var bitmap = new Bitmap(rutaIcono);
            return Icon.FromHandle(bitmap.GetHicon());
        }

        return SystemIcons.Application;
    }

    public void Dispose()
    {
        _servicioSensor.Dispose();

        if (_iconoBandeja is null)
        {
            return;
        }

        _iconoBandeja.Visible = false;
        _iconoBandeja.Dispose();
        _iconoBandeja = null;
    }
}
