using DPFP;
using DPFP.Processing;
using DPFP.Verification;
using PluginBiometricoV4.Api;
using PluginBiometricoV4.Biometric;
using PluginBiometricoV4.Config;

namespace PluginBiometricoV4.UI;

/// <summary>Equivalente a <c>LecturaHuella.java</c> (1:N contra API + PUT verificar).</summary>
public sealed class LecturaHuellaForm : Form
{
    /// <summary>Mismo tope máximo que <c>UsuarioRestApi.php</c> (menos peticiones HTTP con muchos usuarios).</summary>
    private const int PageSize = 500;

    private readonly LocalConfigStore _store;
    private readonly UsuarioRestApiClient _api;
    private FingerprintReaderSession? _reader;
    private readonly Verification _verificator = new();
    private readonly TextBox _log;
    private readonly Button _btnClose;
    private int _busy;

    public LecturaHuellaForm(LocalConfigStore store, UsuarioRestApiClient api)
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
        Text = "Lectura";

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
            AppendLine("Sensor en modo lectura.");
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
        _ = IdentifyAsync(sample);
    }

    private async Task IdentifyAsync(Sample sample)
    {
        try
        {
            var rest = _store.Get(LocalConfigStore.KeyUrlRestApi);
            var serial = _store.Get(LocalConfigStore.KeyUniqueId);
            if (string.IsNullOrWhiteSpace(rest) || string.IsNullOrWhiteSpace(serial))
            {
                AppendLine("Falta urlRestApi o uniqueId.");
                return;
            }

            var features = DpfpHelper.ExtractFeatures(sample, DataPurpose.Verification);
            if (features is null)
                return;

            var img = DpfpHelper.SampleToJpegBase64(sample);
            string mensaje = "El usuario no existe";
            string documento = "----";
            string nombre = "------";
            string dedo = "";
            string? fotoUsu = "";

            var offset = 0;
            var total = 0;
            var found = false;

            while (true)
            {
                var batch = await _api.GetHuellasAsync(rest, serial, offset, PageSize).ConfigureAwait(true);
                if (batch.Count == 0)
                    break;

                total = (int)Math.Max(batch[0].Count, total);

                foreach (var row in batch)
                {
                    if (string.IsNullOrEmpty(row.Huella))
                        continue;
                    try
                    {
                        var raw = Convert.FromBase64String(row.Huella);
                        using var ms = new MemoryStream(raw);
                        var template = new Template(ms);
                        var result = new Verification.Result();
                        _verificator.Verify(features, template, ref result);
                        if (result.Verified)
                        {
                            mensaje = "Usuario Verificado";
                            documento = row.Documento;
                            nombre = row.NombreCompleto;
                            dedo = row.NombreDedo;
                            fotoUsu = row.FotoUsu ?? "";
                            found = true;
                            break;
                        }
                    }
                    catch
                    {
                        // plantilla corrupta / base64 inválido
                    }
                }

                if (found)
                    break;

                offset += batch.Count;
                if (total > 0 && offset >= total)
                    break;
                if (batch.Count < PageSize)
                    break;
            }

            await _api.PutActualizarHuellaAsync(rest, new FingerTempPayload
            {
                Serial = serial,
                ImageHuella = img,
                Texto = "Huella dactilar capturada.",
                StatusPlantilla = mensaje,
                Option = "verificar",
                Documento = documento,
                Nombre = nombre,
                Dedo = dedo,
                FotoUsu = fotoUsu ?? "",
            }).ConfigureAwait(true);

            AppendLine(found ? $"Verificado: {nombre}" : mensaje);
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
