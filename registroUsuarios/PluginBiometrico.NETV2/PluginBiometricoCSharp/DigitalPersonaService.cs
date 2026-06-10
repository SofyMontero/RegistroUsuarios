using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;
using System.Runtime.InteropServices;
using System.Threading.Tasks;
using DPFPEngXLib;
using DPFPDevXLib;
using DPFPShrXLib;
using Newtonsoft.Json.Linq;

namespace PluginBiometricoCSharp
{
    public class DigitalPersonaService : IDisposable
    {
        private DPFPCaptureClass _capture;
        private DPFPEnrollmentClass _enrollment;
        private DPFPFeatureExtractionClass _featureExtraction;
        private DPFPVerificationClass _verification;
        private DPFPSampleConversionClass _sampleConversion;
        private FingerprintRestClient _restClient;
        private bool _verificationMode;

        public event Action<string> StatusChanged;

        public bool StartEnrollment()
        {
            return Start(false);
        }

        public bool StartVerification()
        {
            return Start(true);
        }

        public void Stop()
        {
            try
            {
                if (_capture != null)
                {
                    _capture.StopCapture();
                }
            }
            catch (Exception ex)
            {
                Notify("Error deteniendo lector: " + ex.Message);
            }
        }

        private bool Start(bool verificationMode)
        {
            try
            {
                Stop();
                _verificationMode = verificationMode;
                _restClient = new FingerprintRestClient();
                _capture = new DPFPCaptureClass();
                _enrollment = new DPFPEnrollmentClass();
                _featureExtraction = new DPFPFeatureExtractionClass();
                _verification = new DPFPVerificationClass();
                _sampleConversion = new DPFPSampleConversionClass();

                _capture.OnReaderConnect += Capture_OnReaderConnect;
                _capture.OnReaderDisconnect += Capture_OnReaderDisconnect;
                _capture.OnFingerTouch += Capture_OnFingerTouch;
                _capture.OnFingerGone += Capture_OnFingerGone;
                _capture.OnSampleQuality += Capture_OnSampleQuality;
                _capture.OnComplete += Capture_OnComplete;
                _capture.StartCapture();

                Notify(verificationMode ? "Utilizando el lector en modo lectura." : "Utilizando el lector en modo captura.");
                return true;
            }
            catch (COMException ex)
            {
                Notify("DigitalPersona no esta registrado o no esta disponible: " + ex.Message);
                return false;
            }
            catch (Exception ex)
            {
                Notify("No se pudo iniciar DigitalPersona: " + ex.Message);
                return false;
            }
        }

        private void Capture_OnReaderConnect(string readerSerNum)
        {
            Notify("Sensor activado o conectado.");
        }

        private void Capture_OnReaderDisconnect(string readerSerNum)
        {
            Notify("Sensor desactivado o no conectado.");
        }

        private void Capture_OnFingerTouch(string readerSerNum)
        {
            Notify("Dedo colocado sobre el lector.");
        }

        private void Capture_OnFingerGone(string readerSerNum)
        {
            Notify("Dedo retirado del lector.");
        }

        private void Capture_OnSampleQuality(string readerSerNum, DPFPDevXLib.DPFPCaptureFeedbackEnum captureFeedback)
        {
            Notify(captureFeedback == DPFPDevXLib.DPFPCaptureFeedbackEnum.CaptureFeedbackGood
                ? "Calidad de muestra correcta."
                : "Calidad de muestra: " + captureFeedback);
        }

        private async void Capture_OnComplete(string readerSerNum, object pSample)
        {
            if (_verificationMode)
            {
                await ProcessVerificationAsync(pSample);
            }
            else
            {
                await ProcessEnrollmentAsync(pSample);
            }
        }

        private async Task ProcessEnrollmentAsync(object sample)
        {
            Notify("Huella dactilar capturada.");

            var feedback = _featureExtraction.CreateFeatureSet(sample, DPFPDataPurposeEnum.DataPurposeEnrollment);
            if (feedback != DPFPEngXLib.DPFPCaptureFeedbackEnum.CaptureFeedbackGood)
            {
                Notify("No se pudieron extraer caracteristicas: " + feedback);
                return;
            }

            _enrollment.AddFeatures(_featureExtraction.FeatureSet);
            var imageBase64 = ConvertSampleToJpegBase64(sample);
            var status = "Muestras Restantes: " + _enrollment.FeaturesNeeded;

            await UpdateStatusAsync("actualizar", "Huella dactilar capturada.", status, imageBase64, null, null, null);

            if (_enrollment.TemplateStatus == DPFPTemplateStatusEnum.TemplateStatusTemplateReady)
            {
                var template = (IDPFPTemplate)_enrollment.Template;
                var templateBase64 = ToBase64(template.Serialize());

                await _restClient.AsociarHuellaAsync(new FingerprintPayload
                {
                    Serial = Program.UniqueId,
                    Huella = templateBase64,
                    ImageHuella = imageBase64,
                    Texto = "La plantilla ha sido creada ya puede identificarla",
                    StatusPlantilla = status
                });

                Notify("Plantilla creada y enviada al servidor.");
                Stop();
            }
            else if (_enrollment.TemplateStatus == DPFPTemplateStatusEnum.TemplateStatusCreationFailed)
            {
                _enrollment.Clear();
                Notify("La plantilla no pudo ser creada. Intente de nuevo.");
            }
        }

        private async Task ProcessVerificationAsync(object sample)
        {
            Notify("Huella dactilar capturada.");

            var feedback = _featureExtraction.CreateFeatureSet(sample, DPFPDataPurposeEnum.DataPurposeVerification);
            if (feedback != DPFPEngXLib.DPFPCaptureFeedbackEnum.CaptureFeedbackGood)
            {
                Notify("No se pudieron extraer caracteristicas: " + feedback);
                return;
            }

            var imageBase64 = ConvertSampleToJpegBase64(sample);
            var message = "El usuario no existe";
            string documento = "----";
            string nombre = "------";
            string dedo = null;

            try
            {
                var desde = 0;
                var hasta = 200;
                var iterations = 1;

                for (var i = 0; i < iterations; i++)
                {
                    var json = await _restClient.ListaHuellasAsync(Program.UniqueId, desde, hasta);
                    var list = JArray.Parse(json);

                    foreach (var item in list)
                    {
                        var countToken = item["count"];
                        if (countToken != null)
                        {
                            iterations = Math.Max(1, (int)Math.Ceiling(countToken.Value<double>() / 10d));
                        }

                        var huella = item.Value<string>("huella");
                        if (string.IsNullOrWhiteSpace(huella))
                        {
                            continue;
                        }

                        var template = new DPFPTemplateClass();
                        template.Deserialize(Convert.FromBase64String(huella));
                        var result = (IDPFPVerificationResult)_verification.Verify(_featureExtraction.FeatureSet, template);

                        if (result.Verified)
                        {
                            message = "Usuario Verificado";
                            documento = item.Value<string>("documento");
                            nombre = item.Value<string>("nombre_completo");
                            dedo = item.Value<string>("nombre_dedo");
                            break;
                        }
                    }

                    if (message == "Usuario Verificado")
                    {
                        break;
                    }

                    desde += 10;
                    hasta += 10;
                }
            }
            catch (Exception ex)
            {
                Notify("Error verificando huella: " + ex.Message);
            }

            await UpdateStatusAsync("verificar", "Huella dactilar capturada.", message, imageBase64, documento, nombre, dedo);
            Notify(message);
        }

        private async Task UpdateStatusAsync(string option, string texto, string status, string imageBase64, string documento, string nombre, string dedo)
        {
            await _restClient.ActualizarHuellaAsync(new FingerprintPayload
            {
                Serial = Program.UniqueId,
                ImageHuella = imageBase64,
                Texto = texto,
                StatusPlantilla = status,
                Documento = documento,
                Nombre = nombre,
                Dedo = dedo,
                Option = option
            });
        }

        private string ConvertSampleToJpegBase64(object sample)
        {
            try
            {
                var picture = _sampleConversion.ConvertToPicture(sample);
                var handle = Convert.ToInt32(picture.GetType().InvokeMember(
                    "Handle",
                    System.Reflection.BindingFlags.GetProperty,
                    null,
                    picture,
                    null));
                var image = Image.FromHbitmap(new IntPtr(handle));

                using (var bitmap = new Bitmap(450, 500))
                using (var graphics = Graphics.FromImage(bitmap))
                using (var stream = new MemoryStream())
                {
                    graphics.DrawImage(image, 0, 0, 450, 500);
                    bitmap.Save(stream, ImageFormat.Jpeg);
                    return Convert.ToBase64String(stream.ToArray());
                }
            }
            catch (Exception ex)
            {
                Notify("No se pudo convertir imagen de huella: " + ex.Message);
                return null;
            }
        }

        private static string ToBase64(object rawData)
        {
            if (rawData is byte[] bytes)
            {
                return Convert.ToBase64String(bytes);
            }

            if (rawData is Array array)
            {
                var buffer = new byte[array.Length];
                for (var i = 0; i < array.Length; i++)
                {
                    buffer[i] = Convert.ToByte(array.GetValue(i));
                }

                return Convert.ToBase64String(buffer);
            }

            throw new InvalidOperationException("Formato de template no soportado.");
        }

        private void Notify(string message)
        {
            StatusChanged?.Invoke(message);
        }

        public void Dispose()
        {
            Stop();
        }
    }
}
