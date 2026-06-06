using System.Drawing;
using System.IO;
using System.Windows;
using PluginBiometrico.App.Ventanas;
using PluginBiometrico.Core.Interfaces;
using Application = System.Windows.Application;

namespace PluginBiometrico.App.Tray;

/// <summary>
/// Icono en la bandeja del sistema. Reemplaza TrayClass.java.
/// </summary>
public sealed class TrayApplication : IDisposable
{
    private readonly IAlmacenConfiguracion _almacen;
    private System.Windows.Forms.NotifyIcon? _iconoBandeja;

    public TrayApplication(IAlmacenConfiguracion almacen)
    {
        _almacen = almacen;
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
        menu.Items.Add(new System.Windows.Forms.ToolStripSeparator());
        menu.Items.Add("Cerrar", null, (_, _) => CerrarAplicacion());

        _iconoBandeja.ContextMenuStrip = menu;
        _iconoBandeja.DoubleClick += (_, _) => AbrirConfiguracion();
    }

    private void AbrirConfiguracion()
    {
        var ventana = new VentanaConfiguracion(_almacen);
        ventana.ShowDialog();
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
        if (_iconoBandeja is null)
        {
            return;
        }

        _iconoBandeja.Visible = false;
        _iconoBandeja.Dispose();
        _iconoBandeja = null;
    }
}
