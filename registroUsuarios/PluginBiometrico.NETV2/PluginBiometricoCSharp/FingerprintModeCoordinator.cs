using System;
using System.Drawing;
using System.Windows.Forms;

namespace PluginBiometricoCSharp
{
    public class FingerprintModeCoordinator
    {
        private FingerprintCaptureForm _captureForm;
        private FingerprintReadForm _readForm;
        private string _activeMode;

        public void ActivateCapture()
        {
            if (_activeMode == "capturar" && IsOpen(_captureForm))
            {
                BringToFront(_captureForm);
                return;
            }

            CloseReadForm();
            _captureForm = EnsureCaptureForm();
            ShowAtBottomRight(_captureForm);
            _captureForm.StartCapture();
            _activeMode = "capturar";
        }

        public void ActivateRead()
        {
            if (_activeMode == "leer" && IsOpen(_readForm))
            {
                BringToFront(_readForm);
                return;
            }

            CloseCaptureForm();
            _readForm = EnsureReadForm();
            ShowAtBottomRight(_readForm);
            _readForm.StartRead();
            _activeMode = "leer";
        }

        private FingerprintCaptureForm EnsureCaptureForm()
        {
            if (!IsOpen(_captureForm))
            {
                _captureForm = new FingerprintCaptureForm();
                _captureForm.FormClosed += (s, e) =>
                {
                    if (ReferenceEquals(_captureForm, s))
                    {
                        _captureForm = null;
                        if (_activeMode == "capturar") _activeMode = null;
                    }
                };
            }

            return _captureForm;
        }

        private FingerprintReadForm EnsureReadForm()
        {
            if (!IsOpen(_readForm))
            {
                _readForm = new FingerprintReadForm();
                _readForm.FormClosed += (s, e) =>
                {
                    if (ReferenceEquals(_readForm, s))
                    {
                        _readForm = null;
                        if (_activeMode == "leer") _activeMode = null;
                    }
                };
            }

            return _readForm;
        }

        private void CloseCaptureForm()
        {
            if (IsOpen(_captureForm))
            {
                _captureForm.Close();
            }

            _captureForm = null;
        }

        private void CloseReadForm()
        {
            if (IsOpen(_readForm))
            {
                _readForm.Close();
            }

            _readForm = null;
        }

        private static bool IsOpen(Form form)
        {
            return form != null && !form.IsDisposed && form.Created;
        }

        private static void ShowAtBottomRight(Form form)
        {
            form.StartPosition = FormStartPosition.Manual;
            form.FormBorderStyle = FormBorderStyle.FixedToolWindow;
            form.TopMost = true;

            var workingArea = Screen.PrimaryScreen.WorkingArea;
            form.Location = new Point(
                workingArea.Right - form.Width - 8,
                workingArea.Bottom - form.Height - 8);

            if (!form.Visible)
            {
                form.Show();
            }

            BringToFront(form);
        }

        private static void BringToFront(Form form)
        {
            if (form == null || form.IsDisposed)
            {
                return;
            }

            form.Show();
            form.WindowState = FormWindowState.Normal;
            form.Activate();
        }
    }
}
