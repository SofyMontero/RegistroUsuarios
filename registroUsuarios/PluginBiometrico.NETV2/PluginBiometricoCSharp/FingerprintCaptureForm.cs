using System;
using System.Drawing;
using System.Windows.Forms;

namespace PluginBiometricoCSharp
{
    public class FingerprintCaptureForm : Form
    {
        private TextBox txtStatus;
        private DigitalPersonaService _service;

        public FingerprintCaptureForm()
        {
            Text = "Capturar Huella";
            Icon = AppIcons.MainIcon;
            AppTheme.ApplyForm(this, new Size(300, 150));
            StartPosition = FormStartPosition.Manual;

            var icon = new PictureBox
            {
                Left = 16,
                Top = 16,
                Width = 42,
                Height = 42,
                Image = AppIcons.MainIcon.ToBitmap(),
                SizeMode = PictureBoxSizeMode.StretchImage
            };
            var title = new Label
            {
                Left = 70,
                Top = 16,
                Width = 210,
                Height = 24,
                Text = "Capturar Huella",
                Font = AppTheme.TitleFont,
                ForeColor = AppTheme.Text,
                BackColor = Color.Transparent
            };
            var hint = new Label
            {
                Left = 72,
                Top = 44,
                Width = 210,
                Height = 18,
                Text = "Coloque el dedo en el lector",
                Font = AppTheme.LabelFont,
                ForeColor = AppTheme.MutedText,
                BackColor = Color.Transparent
            };

            txtStatus = AppTheme.CreateStatusBox(16, 76, 268, 48);
            Controls.Add(icon);
            Controls.Add(title);
            Controls.Add(hint);
            Controls.Add(txtStatus);
        }

        public async void StartCapture()
        {
            if (_service == null)
            {
                _service = new DigitalPersonaService();
                _service.StatusChanged += AppendStatus;
            }

            var res = _service.StartEnrollment();
            AppendStatus(res ? "Sensor iniciado en modo captura." : "No se pudo iniciar captura.");

            try
            {
                var client = new FingerprintRestClient();
                await client.ActualizarHuellaAsync(new FingerprintPayload
                {
                    Serial = Program.UniqueId,
                    Texto = txtStatus.Text,
                    StatusPlantilla = "Esperando muestras",
                    Option = "actualizar"
                });
            }
            catch (Exception ex)
            {
                AppendStatus("REST: " + ex.Message);
            }
        }

        private void AppendStatus(string message)
        {
            if (InvokeRequired)
            {
                BeginInvoke(new Action<string>(AppendStatus), message);
                return;
            }

            if (txtStatus.Lines.Length > 4)
            {
                txtStatus.Clear();
            }

            txtStatus.AppendText(message + Environment.NewLine);
        }

        protected override void OnFormClosed(FormClosedEventArgs e)
        {
            _service?.Dispose();
            base.OnFormClosed(e);
        }
    }
}
