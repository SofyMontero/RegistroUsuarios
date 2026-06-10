using System;
using System.Drawing;
using System.Windows.Forms;

namespace PluginBiometricoCSharp
{
    public class ConfigForm : Form
    {
        private readonly string _path;
        private TextBox txtUrlHab;
        private TextBox txtRestApi;
        private TextBox txtUniqueId;
        private ComboBox cboBrowser;
        private Button btnSave;

        public ConfigForm(string path)
        {
            _path = path;
            Text = "Configuracion";
            Icon = AppIcons.MainIcon;
            AppTheme.ApplyForm(this, new Size(620, 430));

            Controls.Add(AppTheme.CreateHeaderPanel(ClientSize.Width, "Plugin Biometrico", "Configuracion del sensor y conexion al servidor"));

            var left = 34;
            var top = 112;
            var width = 552;

            Controls.Add(AppTheme.CreateLabel("Url Habilitar Sensor", left, top, width));
            txtUrlHab = AppTheme.CreateTextBox(left, top + 22, width);

            Controls.Add(AppTheme.CreateLabel("Url Rest Api", left, top + 64, width));
            txtRestApi = AppTheme.CreateTextBox(left, top + 86, width);

            Controls.Add(AppTheme.CreateLabel("Token", left, top + 128, width));
            txtUniqueId = AppTheme.CreateTextBox(left, top + 150, width);

            Controls.Add(AppTheme.CreateLabel("Navegador", left, top + 192, width));
            cboBrowser = AppTheme.CreateComboBox(left, top + 214, 220);
            cboBrowser.Items.AddRange(new object[] { "Seleccione", "Chrome", "Mozilla", "Edge", "Explorer" });
            cboBrowser.SelectedItem = "Seleccione";

            LoadExistingValues();

            btnSave = AppTheme.CreatePrimaryButton("Guardar", left, top + 266, 132);
            btnSave.Click += BtnSave_Click;

            Controls.Add(txtUrlHab);
            Controls.Add(txtRestApi);
            Controls.Add(txtUniqueId);
            Controls.Add(cboBrowser);
            Controls.Add(btnSave);
        }

        private void LoadExistingValues()
        {
            txtUrlHab.Text = Utils.GetConfigValue("urlHabSensor");
            txtRestApi.Text = Utils.GetConfigValue("urlRestApi");
            txtUniqueId.Text = Utils.GetConfigValue("uniqueId");

            var browser = Utils.GetConfigValue("browser");
            if (!string.IsNullOrWhiteSpace(browser) && cboBrowser.Items.Contains(browser))
            {
                cboBrowser.SelectedItem = browser;
            }
        }

        private bool ValidateFields()
        {
            var valid = true;
            valid &= ValidateTextBox(txtUrlHab);
            valid &= ValidateTextBox(txtRestApi);
            valid &= ValidateTextBox(txtUniqueId);

            if ((string)cboBrowser.SelectedItem == "Seleccione")
            {
                cboBrowser.BackColor = Color.FromArgb(80, 34, 42);
                valid = false;
            }
            else
            {
                cboBrowser.BackColor = AppTheme.SurfaceSoft;
            }

            return valid;
        }

        private static bool ValidateTextBox(TextBox textBox)
        {
            if (string.IsNullOrWhiteSpace(textBox.Text))
            {
                textBox.BackColor = Color.FromArgb(80, 34, 42);
                return false;
            }

            textBox.BackColor = AppTheme.SurfaceSoft;
            return true;
        }

        private void BtnSave_Click(object sender, EventArgs e)
        {
            if (!ValidateFields())
            {
                MessageBox.Show("Los campos marcados son obligatorios.", "Configuracion");
                return;
            }

            Utils.SetConfigValue("urlHabSensor", txtUrlHab.Text.Trim());
            Utils.SetConfigValue("urlRestApi", txtRestApi.Text.Trim());
            Utils.SetConfigValue("uniqueId", txtUniqueId.Text.Trim());
            Utils.SetConfigValue("browser", cboBrowser.SelectedItem.ToString());
            MessageBox.Show("Configuracion guardada.", "Configuracion");
            Close();
            Utils.RestartApplication();
        }
    }
}
