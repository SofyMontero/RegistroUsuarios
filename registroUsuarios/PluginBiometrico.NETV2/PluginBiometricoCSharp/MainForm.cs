using System;
using System.Drawing;
using System.Windows.Forms;

namespace PluginBiometricoCSharp
{
    public class MainForm : Form
    {
        private readonly string[] _browsers = { "Chrome", "Mozilla", "Edge", "Explorer" };
        private readonly FingerprintModeCoordinator _coordinator;
        private readonly PollingService _polling;
        private Timer _pollTimer;
        private NotifyIcon _tray;
        private ContextMenuStrip _menu;

        public MainForm()
        {
            _coordinator = new FingerprintModeCoordinator();
            _polling = new PollingService(_coordinator);

            ShowInTaskbar = false;
            WindowState = FormWindowState.Minimized;
            Opacity = 0;
            CreateHandle();

            _tray = new NotifyIcon
            {
                Icon = AppIcons.MainIcon,
                Text = "Plugin Biometrico",
                Visible = true
            };

            BuildMenu();
            StartPolling();
        }

        protected override void SetVisibleCore(bool value)
        {
            base.SetVisibleCore(false);
        }

        private void BuildMenu()
        {
            _menu?.Dispose();
            _menu = new ContextMenuStrip();

            _menu.Items.Add("Estado", null, (s, e) => ShowStatus());
            _menu.Items.Add("Nueva Configuracion", null, (s, e) => ShowConfig());
            _menu.Items.Add("Probar Captura", null, (s, e) => _coordinator.ActivateCapture());
            _menu.Items.Add("Probar Lectura", null, (s, e) => _coordinator.ActivateRead());
            _menu.Items.Add(CreateBrowserMenu());
            _menu.Items.Add(new ToolStripSeparator());
            _menu.Items.Add(CreateStartupMenuItem());
            _menu.Items.Add(new ToolStripSeparator());
            _menu.Items.Add("Cerrar", null, (s, e) => Application.Exit());

            _tray.ContextMenuStrip = _menu;
        }

        private void StartPolling()
        {
            _pollTimer = new Timer { Interval = 1000 };
            _pollTimer.Tick += async (s, e) => await _polling.CheckAsync();
            _pollTimer.Start();
        }

        private ToolStripMenuItem CreateBrowserMenu()
        {
            var current = Utils.GetConfigValue("browser");
            var browserMenu = new ToolStripMenuItem("Navegador");

            foreach (var browser in _browsers)
            {
                var item = new ToolStripMenuItem(browser)
                {
                    Checked = string.Equals(current, browser, StringComparison.OrdinalIgnoreCase)
                };

                item.Click += (s, e) =>
                {
                    Utils.SetConfigValue("browser", browser);
                    BuildMenu();
                    RestartWithNotice("Navegador actualizado. La aplicacion se reiniciara.");
                };

                browserMenu.DropDownItems.Add(item);
            }

            return browserMenu;
        }

        private ToolStripMenuItem CreateStartupMenuItem()
        {
            var enabled = Utils.IsStartupEnabled();
            var item = new ToolStripMenuItem(enabled ? "Eliminar Inicio Automatico" : "Crear Inicio Automatico");
            item.Click += (s, e) =>
            {
                try
                {
                    if (Utils.IsStartupEnabled())
                    {
                        Utils.DisableStartup();
                        ShowBalloon("Inicio automatico eliminado.");
                    }
                    else
                    {
                        Utils.EnableStartup();
                        ShowBalloon("La aplicacion iniciara con Windows.");
                    }

                    BuildMenu();
                }
                catch (Exception ex)
                {
                    MessageBox.Show("No se pudo actualizar el inicio automatico: " + ex.Message);
                }
            };

            return item;
        }

        private void ShowConfig()
        {
            using (var form = new ConfigForm(Utils.AppConfigPath))
            {
                form.StartPosition = FormStartPosition.CenterScreen;
                form.ShowDialog(this);
            }

            BuildMenu();
        }

        private void ShowStatus()
        {
            var message =
                "Plugin Biometrico activo." + Environment.NewLine +
                "Token: " + EmptyText(Utils.GetConfigValue("uniqueId")) + Environment.NewLine +
                "Url Habilitar Sensor: " + EmptyText(Utils.GetConfigValue("urlHabSensor")) + Environment.NewLine +
                "Url Rest Api: " + EmptyText(Utils.GetConfigValue("urlRestApi")) + Environment.NewLine +
                "Navegador: " + EmptyText(Utils.GetConfigValue("browser")) + Environment.NewLine +
                "Inicio automatico: " + (Utils.IsStartupEnabled() ? "Si" : "No");

            MessageBox.Show(message, "Estado");
        }

        private static string EmptyText(string value)
        {
            return string.IsNullOrWhiteSpace(value) ? "(sin configurar)" : value;
        }

        private void RestartWithNotice(string message)
        {
            ShowBalloon(message);
            var timer = new Timer { Interval = 1500 };
            timer.Tick += (s, e) =>
            {
                timer.Stop();
                timer.Dispose();
                Utils.RestartApplication();
            };
            timer.Start();
        }

        private void ShowBalloon(string message)
        {
            _tray.BalloonTipTitle = "Aviso";
            _tray.BalloonTipText = message;
            _tray.BalloonTipIcon = ToolTipIcon.Info;
            _tray.ShowBalloonTip(3000);
        }

        protected override void OnFormClosed(FormClosedEventArgs e)
        {
            _tray.Visible = false;
            _tray.Dispose();
            _menu?.Dispose();
            _pollTimer?.Stop();
            _pollTimer?.Dispose();
            base.OnFormClosed(e);
        }
    }
}
