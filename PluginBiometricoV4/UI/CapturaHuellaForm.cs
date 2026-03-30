using DPFP;
using DPFP.Processing;
using PluginBiometricoV4.Api;
using PluginBiometricoV4.Biometric;
using PluginBiometricoV4.Config;

namespace PluginBiometricoV4.UI;

/// <summary>Equivalente a <c>CapturarHuella.java</c> (enrolamiento + POST plantilla).</summary>
public sealed class CapturaHuellaForm : Form
{
    private readonly LocalConfigStore _store;
    private readonly UsuarioRestApiClient _api;
    private FingerprintReaderSession? _reader;
    private readonly Enrollment _enroller = new();
    private readonly TextBox _log;
    private readonly Button _btnClose;
    private int _busy;

    public CapturaHuellaForm(LocalConfigStore store, UsuarioRestApiClient api)
    {
        _store = store;
        _api = api;

        FormBorderStyle = FormBorderStyle.None;
        Size = new Size(280, 130);
        StartPosition = FormStartPosition.Manual;
        var wa = Screen.PrimaryScreen?.WorkingArea ?? new Rectangle(0, 0, 800, 600);
        Location = new Point(wa.Right - Width - 12, wa.Bottom - Height - 8);
        TopMost = true;
        BackColor = Color.FromArgb(34, 41, 50);
        ForeColor = Color.White;

        _btnClose = new Button
        {
            Text = "X",
            ForeColor = Color.White,
            BackColor = Color.FromArgb(34, 41, 50),
            FlatStyle = FlatStyle.Flat,
            Size = new Size(28, 24),
            Anchor = AnchorStyles.Top | AnchorStyles.Right,
            Location = new Point(Width - 36, 4),
        };
        _btnClose.FlatAppearance.BorderSize = 0;
        _btnClose.Click += (_, _) => Close();

        _log = new TextBox
        {
            Multiline = true,
            ReadOnly = true,
            ScrollBars = ScrollBars.Vertical,
            BorderStyle = BorderStyle.FixedSingle,
            BackColor = Color.FromArgb(34, 41, 50),
            ForeColor = Color.White,
            Font = new Font("Segoe UI", 8.5f),
            Location = new Point(8, 32),
            Size = new Size(Width - 16, Height - 40),
            Anchor = AnchorStyles.Top | AnchorStyles.Bottom | AnchorStyles.Left | AnchorStyles.Right,
        };

        Controls.Add(_log);
        Controls.Add(_btnClose);
        Text = "Captura";

        Load += OnLoad;
        FormClosed += OnFormClosed;
    }

    private void OnLoad(object? sender, EventArgs e)
    {
        try
        {
            _reader = new FingerprintReaderSession();
            _reader.Status += AppendLine;
            _reader.SampleAcquired += OnSample;
            _reader.Start();
            AppendLine("Sensor en modo captura. Coloque el dedo varias veces.");
        }
        catch (Exception ex)
        {
            AppendLine("No se pudo iniciar el lector: " + ex.Message);
        }
    }

    private void OnFormClosed(object? sender, FormClosedEventArgs e)
    {
        if (_reader is not null)
        {
            _reader.SampleAcquired -= OnSample;
            _reader.Status -= AppendLine;
            _reader.Dispose();
            _reader = null;
        }
    }

    private void AppendLine(string msg)
    {
        if (IsDisposed)
            return;
        if (_log.TextLength > 4000)
            _log.Clear();
        _log.AppendText(msg + Environment.NewLine);
    }

    private void OnSample(Sample sample)
    {
        if (Interlocked.CompareExchange(ref _busy, 1, 0) != 0)
            return;
        _ = ProcessSampleAsync(sample);
    }

    private async Task ProcessSampleAsync(Sample sample)
    {
        try
        {
            var rest = _store.Get(LocalConfigStore.KeyUrlRestApi);
            var serial = _store.Get(LocalConfigStore.KeyUniqueId);
            if (string.IsNullOrWhiteSpace(rest) || string.IsNullOrWhiteSpace(serial))
            {
                AppendLine("Falta urlRestApi o uniqueId en configuración.");
                return;
            }

            var features = DpfpHelper.ExtractFeatures(sample, DataPurpose.Enrollment);
            if (features is null)
                return;

            try
            {
                _enroller.AddFeatures(features);
            }
            catch
            {
                AppendLine("Muestra rechazada; repita.");
                return;
            }

            var img = DpfpHelper.SampleToJpegBase64(sample);
            AppendLine("Huella capturada.");
            var status = $"Muestras restantes: {_enroller.FeaturesNeeded}";

            await _api.PutActualizarHuellaAsync(rest, new FingerTempPayload
            {
                Serial = serial,
                ImageHuella = img,
                Texto = "Huella dactilar capturada.",
                StatusPlantilla = status,
                Option = "actualizar",
                FotoUsu = "",
            }).ConfigureAwait(true);

            switch (_enroller.TemplateStatus)
            {
                case Enrollment.Status.Ready:
                    _reader?.Stop();
                    byte[]? bytes = null;
                    _enroller.Template.Serialize(ref bytes);
                    var b64 = Convert.ToBase64String(bytes ?? Array.Empty<byte>());
                    await _api.PostAsociarHuellaAsync(rest, new FingerTempPayload
                    {
                        Serial = serial,
                        Huella = b64,
                        ImageHuella = img,
                        Texto = "La plantilla ha sido creada ya puede identificarla",
                        StatusPlantilla = status,
                        FotoUsu = "",
                    }).ConfigureAwait(true);
                    AppendLine("Plantilla enviada al servidor.");
                    BeginInvoke(Close);
                    break;

                case Enrollment.Status.Failed:
                    _enroller.Clear();
                    _reader?.Stop();
                    AppendLine("Falló la plantilla; reintentando captura.");
                    _reader?.Start();
                    break;
            }
        }
        catch (Exception ex)
        {
            AppendLine("Error: " + ex.Message);
        }
        finally
        {
            Interlocked.Exchange(ref _busy, 0);
        }
    }
}
