using PluginBiometricoV4.Config;

namespace PluginBiometricoV4;

/// <summary>
/// Primer arranque: mismas URLs y token que el formulario Java (ConfigForm).
/// </summary>
public class ConfigurationForm : Form
{
    private readonly LocalConfigStore _store = new();
    private readonly TextBox _urlHabSensor = new() { PlaceholderText = "URL HabilitarSensor (GET long-polling)" };
    private readonly TextBox _urlRestApi = new() { PlaceholderText = "URL UsuarioRestApi (GET/POST/PUT)" };
    private readonly TextBox _uniqueId = new() { PlaceholderText = "Token / serial del PC (pc_serial)" };
    private readonly ComboBox _browser = new() { DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly Button _save = new() { Text = "Guardar y continuar" };

    public ConfigurationForm()
    {
        Text = "Configuración | Plugin biométrico (C#)";
        StartPosition = FormStartPosition.CenterScreen;
        FormBorderStyle = FormBorderStyle.FixedDialog;
        MaximizeBox = false;
        MinimizeBox = false;
        ClientSize = new Size(520, 220);
        Padding = new Padding(12);

        _browser.Items.AddRange(new object[] { "Chrome", "Edge", "Mozilla", "Explorer" });
        _browser.SelectedIndex = 0;

        _save.Click += OnSave;

        var table = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 6,
        };
        table.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        table.RowStyles.Add(new RowStyle(SizeType.Absolute, 28));
        table.RowStyles.Add(new RowStyle(SizeType.Absolute, 28));
        table.RowStyles.Add(new RowStyle(SizeType.Absolute, 28));
        table.RowStyles.Add(new RowStyle(SizeType.Absolute, 28));
        table.RowStyles.Add(new RowStyle(SizeType.Absolute, 36));
        table.Controls.Add(new Label { Text = "Rutas del servidor (Model PHP)", AutoSize = true }, 0, 0);
        table.Controls.Add(_urlHabSensor, 0, 1);
        table.Controls.Add(_urlRestApi, 0, 2);
        table.Controls.Add(_uniqueId, 0, 3);
        table.Controls.Add(_browser, 0, 4);
        table.Controls.Add(_save, 0, 5);

        Controls.Add(table);

        LoadExisting();
    }

    private void LoadExisting()
    {
        _store.EnsureCreated();
        _urlHabSensor.Text = _store.Get(LocalConfigStore.KeyUrlHabSensor) ?? "";
        _urlRestApi.Text = _store.Get(LocalConfigStore.KeyUrlRestApi) ?? "";
        _uniqueId.Text = _store.Get(LocalConfigStore.KeyUniqueId) ?? "";
        var b = _store.Get(LocalConfigStore.KeyBrowser);
        if (!string.IsNullOrEmpty(b))
        {
            var i = _browser.Items.IndexOf(b);
            if (i >= 0) _browser.SelectedIndex = i;
        }
    }

    private void OnSave(object? sender, EventArgs e)
    {
        if (string.IsNullOrWhiteSpace(_urlHabSensor.Text)
            || string.IsNullOrWhiteSpace(_urlRestApi.Text)
            || string.IsNullOrWhiteSpace(_uniqueId.Text))
        {
            MessageBox.Show(this, "Complete URL de habilitación, API REST y el token del equipo.", Text,
                MessageBoxButtons.OK, MessageBoxIcon.Warning);
            return;
        }

        _store.Set(LocalConfigStore.KeyUrlHabSensor, _urlHabSensor.Text.Trim());
        _store.Set(LocalConfigStore.KeyUrlRestApi, _urlRestApi.Text.Trim());
        _store.Set(LocalConfigStore.KeyUniqueId, _uniqueId.Text.Trim());
        _store.Set(LocalConfigStore.KeyBrowser, _browser.SelectedItem?.ToString() ?? "Chrome");

        DialogResult = DialogResult.OK;
        Close();
    }
}
