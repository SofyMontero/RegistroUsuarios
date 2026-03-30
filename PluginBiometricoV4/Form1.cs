using System.ComponentModel;
using PluginBiometricoV4.Api;
using PluginBiometricoV4.Config;
using PluginBiometricoV4.Services;
using PluginBiometricoV4.UI;

namespace PluginBiometricoV4;

public partial class Form1 : Form
{
    private readonly LocalConfigStore _store = new();
    private SensorPollingService? _polling;
    private UsuarioRestApiClient? _rest;
    private CapturaHuellaForm? _captura;
    private LecturaHuellaForm? _lectura;

    private NotifyIcon _tray = null!;
    private ContextMenuStrip _trayMenu = null!;
    private ToolStripMenuItem _browserMenu = null!;
    private ToolStripMenuItem _miStartup = null!;
    private Icon? _trayIcon;
    private bool _allowClose;
    private bool _trayDisposed;
    private bool _firstShown = true;
    private readonly EventHandler _trayDoubleClickHandler;

    public Form1()
    {
        _trayDoubleClickHandler = (_, _) => ShowMainWindow();
        InitializeComponent();
        SetupTrayIcon();
    }

    protected override void OnLoad(EventArgs e)
    {
        base.OnLoad(e);
        _rest = new UsuarioRestApiClient();
        _polling = new SensorPollingService(_store);
        _polling.CommandReceived += OnSensorCommand;
        _polling.Start();
    }

    protected override void OnShown(EventArgs e)
    {
        base.OnShown(e);
        if (!_firstShown)
            return;
        _firstShown = false;
        BeginInvoke(static (Form f) =>
        {
            f.Hide();
            f.ShowInTaskbar = false;
        }, this);
    }

    protected override void OnFormClosing(FormClosingEventArgs e)
    {
        if (!_allowClose && e.CloseReason == CloseReason.UserClosing)
        {
            e.Cancel = true;
            Hide();
            ShowInTaskbar = false;
            if (!_trayDisposed)
            {
                _tray.ShowBalloonTip(
                    2500,
                    "Plugin biométrico",
                    "Sigue activo en la bandeja (icono junto al reloj).",
                    ToolTipIcon.Info);
            }

            return;
        }

        DisposeTrayResources();

        _captura?.Close();
        _lectura?.Close();
        _captura = null;
        _lectura = null;

        if (_polling is not null)
        {
            _polling.CommandReceived -= OnSensorCommand;
            _polling.Dispose();
            _polling = null;
        }

        _rest?.Dispose();
        _rest = null;

        base.OnFormClosing(e);
    }

    private void SetupTrayIcon()
    {
        _trayMenu = new ContextMenuStrip();
        _trayMenu.Opening += TrayMenu_Opening;

        _trayMenu.Items.Add("Mostrar ventana", null, (_, _) => ShowMainWindow());
        _trayMenu.Items.Add("Configuración…", null, (_, _) => EditConfigurationFromTray());
        _trayMenu.Items.Add(new ToolStripSeparator());

        _browserMenu = new ToolStripMenuItem("Navegador predeterminado");
        foreach (var name in new[] { "Chrome", "Edge", "Mozilla", "Explorer" })
        {
            var item = new ToolStripMenuItem(name) { Tag = name };
            item.Click += BrowserMenuItem_Click;
            _browserMenu.DropDownItems.Add(item);
        }

        _trayMenu.Items.Add(_browserMenu);
        _trayMenu.Items.Add(new ToolStripSeparator());

        _miStartup = new ToolStripMenuItem("Iniciar con Windows");
        _miStartup.Click += (_, _) => ToggleStartupFromTray();
        _trayMenu.Items.Add(_miStartup);

        _trayMenu.Items.Add(new ToolStripSeparator());
        _trayMenu.Items.Add("Salir", null, (_, _) => ExitFromTray());

        _tray = new NotifyIcon
        {
            ContextMenuStrip = _trayMenu,
            Text = "Sensor biométrico",
            Visible = true,
        };
        TrySetTrayApplicationIcon();
        _tray.DoubleClick += _trayDoubleClickHandler;
    }

    private void TrayMenu_Opening(object? sender, CancelEventArgs e)
    {
        var current = _store.Get(LocalConfigStore.KeyBrowser) ?? "Chrome";
        foreach (ToolStripMenuItem item in _browserMenu.DropDownItems)
        {
            if (item.Tag is string tag)
                item.Checked = tag.Equals(current, StringComparison.OrdinalIgnoreCase);
        }

        _miStartup.Checked = WindowsStartup.IsEnabled();
    }

    private void BrowserMenuItem_Click(object? sender, EventArgs e)
    {
        if (sender is ToolStripMenuItem { Tag: string name })
            _store.Set(LocalConfigStore.KeyBrowser, name);
    }

    private void ToggleStartupFromTray()
    {
        try
        {
            WindowsStartup.SetEnabled(!WindowsStartup.IsEnabled(), Application.ExecutablePath);
        }
        catch (Exception ex)
        {
            MessageBox.Show(
                this,
                ex.Message,
                Text,
                MessageBoxButtons.OK,
                MessageBoxIcon.Warning);
        }
    }

    private void EditConfigurationFromTray()
    {
        using var cfg = new ConfigurationForm();
        cfg.ShowDialog(this);
    }

    private void ShowMainWindow()
    {
        Show();
        WindowState = FormWindowState.Normal;
        ShowInTaskbar = true;
        Activate();
    }

    private void ExitFromTray()
    {
        _allowClose = true;
        Close();
    }

    private void TrySetTrayApplicationIcon()
    {
        try
        {
            var extracted = Icon.ExtractAssociatedIcon(Application.ExecutablePath);
            if (extracted is not null)
            {
                _trayIcon = new Icon(extracted, 16, 16);
                extracted.Dispose();
                _tray.Icon = _trayIcon;
            }
            else
            {
                _tray.Icon = SystemIcons.Application;
            }
        }
        catch
        {
            _tray.Icon = SystemIcons.Application;
        }
    }

    private void DisposeTrayResources()
    {
        if (_trayDisposed)
            return;
        _trayDisposed = true;
        _tray.Visible = false;
        _tray.DoubleClick -= _trayDoubleClickHandler;
        _tray.ContextMenuStrip = null;
        _tray.Icon = null;
        _tray.Dispose();
        _trayMenu.Dispose();
        _trayIcon?.Dispose();
        _trayIcon = null;
    }

    private void OnSensorCommand(object? sender, HabilitarSensorResponse resp)
    {
        if (IsDisposed)
            return;
        BeginInvoke(() => HandleSensorCommand(resp));
    }

    private void HandleSensorCommand(HabilitarSensorResponse resp)
    {
        if (_rest is null)
            return;

        lblEstado.Text = $"Última orden: {resp.Opc}\r\nfecha_creacion (Unix s): {resp.FechaCreacion}";

        switch (resp.Opc)
        {
            case "capturar":
                _lectura?.Close();
                _lectura = null;
                _captura?.Close();
                _captura = new CapturaHuellaForm(_store, _rest);
                _captura.FormClosed += (_, _) => _captura = null;
                _captura.Show(this);
                break;
            case "leer":
                _captura?.Close();
                _captura = null;
                _lectura?.Close();
                _lectura = new LecturaHuellaForm(_store, _rest);
                _lectura.FormClosed += (_, _) => _lectura = null;
                _lectura.Show(this);
                break;
        }
    }
}
