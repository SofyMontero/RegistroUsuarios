#if TIENE_SDK_DPFP_ACTIVEX
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.Runtime.InteropServices;
using DPFPEngXLib;
using DPFPDevXLib;
using DPFPShrXLib;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>Captura mediante el SDK ActiveX de DigitalPersona (proceso x86).</summary>
public sealed partial class LectorDigitalPersona
{
    private DPFPCaptureClass? _capturador;
    private DPFPEnrollmentClass? _enrollment;
    private DPFPFeatureExtractionClass? _extractor;
    private DPFPSampleConversionClass? _convertidor;
    private bool _modoVerificacion;
    private byte[]? _ultimaImagenJpeg;

    partial void EstablecerModoCaptura()
    {
        _modoVerificacion = false;
        _enrollment = null;
    }

    partial void EstablecerModoVerificacion()
    {
        _modoVerificacion = true;
        _enrollment = null;
    }

    partial void IniciarCapturaReal()
    {
        DetenerCapturaReal();

        try
        {
            _capturador = new DPFPCaptureClass();
            // Los objetos de procesamiento COM se crean en OnComplete.
            // El SDK entrega ese callback desde otro apartment y no permite
            // utilizar allí objetos creados en el hilo que inició la captura.
            _extractor = null;
            _convertidor = null;

            _capturador.OnReaderConnect += OnReaderConnect;
            _capturador.OnReaderDisconnect += OnReaderDisconnect;
            _capturador.OnFingerTouch += OnFingerTouch;
            _capturador.OnFingerGone += OnFingerGone;
            _capturador.OnSampleQuality += OnSampleQuality;
            _capturador.OnComplete += OnComplete;
            _capturador.StartCapture();

            NotificarMensaje(_modoVerificacion
                ? "ActiveX: lector en modo lectura."
                : "ActiveX: lector en modo captura.");

            if (!_modoVerificacion)
            {
                _ultimaImagenJpeg = CrearIndicadorJpeg(false);
                NotificarMuestra(new EventoMuestraHuella
                {
                    Mensaje = "Esperando captura de huella.",
                    EstadoPlantilla = "Muestras Restantes: 4",
                    ImagenJpeg = _ultimaImagenJpeg,
                    Estado = EstadoEnrollment.EnProgreso
                });
            }
        }
        catch (COMException ex)
        {
            NotificarMensaje($"DigitalPersona ActiveX no está registrado: {ex.Message}");
        }
        catch (Exception ex)
        {
            NotificarMensaje($"No se pudo iniciar DigitalPersona ActiveX: {ex.Message}");
        }
    }

    partial void DetenerCapturaReal()
    {
        if (_capturador is null)
        {
            return;
        }

        try
        {
            _capturador.OnReaderConnect -= OnReaderConnect;
            _capturador.OnReaderDisconnect -= OnReaderDisconnect;
            _capturador.OnFingerTouch -= OnFingerTouch;
            _capturador.OnFingerGone -= OnFingerGone;
            _capturador.OnSampleQuality -= OnSampleQuality;
            _capturador.OnComplete -= OnComplete;
        }
        catch
        {
            // El control COM puede estar a medias de soltar el lector.
        }

        try
        {
            _capturador.StopCapture();
        }
        catch
        {
            // El control COM puede estar detenido o desconectado.
        }
    }

    partial void LiberarRecursosReal()
    {
        DetenerCapturaReal();
        LiberarCom(_capturador);
        LiberarCom(_enrollment);
        LiberarCom(_extractor);
        LiberarCom(_convertidor);
        _capturador = null;
        _enrollment = null;
        _extractor = null;
        _convertidor = null;
    }

    private void OnReaderConnect(string serial) =>
        NotificarMensaje("Sensor activado o conectado.");

    private void OnReaderDisconnect(string serial) =>
        NotificarMensaje("Sensor desactivado o no conectado.");

    private void OnFingerTouch(string serial) =>
        NotificarMensaje("Dedo colocado sobre el lector.");

    private void OnFingerGone(string serial) =>
        NotificarMensaje("Dedo retirado del lector.");

    private void OnSampleQuality(string serial, DPFPDevXLib.DPFPCaptureFeedbackEnum feedback) =>
        NotificarMensaje(feedback == DPFPDevXLib.DPFPCaptureFeedbackEnum.CaptureFeedbackGood
            ? "Calidad de muestra correcta."
            : $"Calidad de muestra: {feedback}");

    private void OnComplete(string serial, object sample)
    {
        NotificarMensaje("Huella dactilar capturada.");

        try
        {
            _extractor ??= new DPFPFeatureExtractionClass();
            _convertidor ??= new DPFPSampleConversionClass();

            if (_modoVerificacion)
            {
                ProcesarVerificacion(sample);
            }
            else
            {
                _enrollment ??= new DPFPEnrollmentClass();
                ProcesarEnrollment(sample);
            }
        }
        catch (Exception ex)
        {
            NotificarMensaje($"No se pudo preparar el procesamiento ActiveX: {ex.Message}");
        }
    }

    private void ProcesarEnrollment(object sample)
    {
        if (_extractor is null || _enrollment is null)
        {
            return;
        }

        try
        {
            var feedback = _extractor.CreateFeatureSet(
                sample,
                DPFPDataPurposeEnum.DataPurposeEnrollment);

            if (feedback != DPFPEngXLib.DPFPCaptureFeedbackEnum.CaptureFeedbackGood)
            {
                NotificarMensaje($"No se pudieron extraer características: {feedback}");
                return;
            }

            _enrollment.AddFeatures(_extractor.FeatureSet);
            _ultimaImagenJpeg = CrearIndicadorJpeg(true);
            var estado = $"Muestras Restantes: {_enrollment.FeaturesNeeded}";

            NotificarMuestra(new EventoMuestraHuella
            {
                Mensaje = "Huella dactilar capturada.",
                EstadoPlantilla = estado,
                ImagenJpeg = _ultimaImagenJpeg,
                Estado = EstadoEnrollment.EnProgreso
            });

            if (_enrollment.TemplateStatus == DPFPTemplateStatusEnum.TemplateStatusTemplateReady)
            {
                var plantilla = (IDPFPTemplate)_enrollment.Template;
                var bytes = ConvertirABytes(plantilla.Serialize());
                DetenerCapturaReal();

                NotificarMuestra(new EventoMuestraHuella
                {
                    Mensaje = "La plantilla ha sido creada ya puede identificarla",
                    EstadoPlantilla = estado,
                    ImagenJpeg = _ultimaImagenJpeg,
                    PlantillaSerializada = bytes,
                    Estado = EstadoEnrollment.PlantillaLista
                });
            }
            else if (_enrollment.TemplateStatus == DPFPTemplateStatusEnum.TemplateStatusCreationFailed)
            {
                _enrollment.Clear();
                NotificarMuestra(new EventoMuestraHuella
                {
                    Mensaje = "La plantilla no pudo ser creada",
                    EstadoPlantilla = estado,
                    ImagenJpeg = _ultimaImagenJpeg,
                    Estado = EstadoEnrollment.Fallido
                });
            }
        }
        catch (Exception ex)
        {
            NotificarMensaje($"Error procesando huella ActiveX: {ex.Message}");
        }
    }

    private void ProcesarVerificacion(object sample)
    {
        if (_extractor is null)
        {
            return;
        }

        try
        {
            var feedback = _extractor.CreateFeatureSet(
                sample,
                DPFPDataPurposeEnum.DataPurposeVerification);

            if (feedback != DPFPEngXLib.DPFPCaptureFeedbackEnum.CaptureFeedbackGood)
            {
                NotificarMensaje($"Muestra no válida para verificación: {feedback}");
                return;
            }

            _ultimaImagenJpeg = CrearIndicadorJpeg(true);
            NotificarVerificacion(new EventoVerificacionHuella
            {
                Mensaje = "Huella dactilar capturada.",
                ImagenJpeg = _ultimaImagenJpeg,
                CaracteristicasBiometricas = new CaracteristicasActiveX(_extractor.FeatureSet)
            });
        }
        catch (Exception ex)
        {
            NotificarMensaje($"Error preparando verificación ActiveX: {ex.Message}");
        }
    }

    private byte[]? ConvertirMuestraAJpeg(object sample)
    {
        if (_convertidor is null)
        {
            return null;
        }

        var picture = _convertidor.ConvertToPicture(sample);
        using var original = ConversorImagenActiveX.Convertir(picture);
        using var stream = new MemoryStream();
        // La web histórica espera JPEG. Conservamos dimensiones y proporción
        // nativas y usamos calidad alta para no destruir las crestas.
        var codecJpeg = ImageCodecInfo.GetImageEncoders()
            .First(codec => codec.FormatID == ImageFormat.Jpeg.Guid);
        using var parametros = new EncoderParameters(1);
        parametros.Param[0] = new EncoderParameter(Encoder.Quality, 95L);
        original.Save(stream, codecJpeg, parametros);
        return stream.ToArray();
    }

    private static byte[] CrearIndicadorJpeg(bool capturada)
    {
        const int lado = 320;
        using var imagen = new Bitmap(lado, lado);
        using var grafico = Graphics.FromImage(imagen);
        grafico.SmoothingMode = SmoothingMode.AntiAlias;
        grafico.Clear(Color.White);

        var color = capturada
            ? Color.FromArgb(22, 163, 154)
            : Color.FromArgb(220, 53, 69);

        using var fondo = new SolidBrush(color);
        grafico.FillEllipse(fondo, 30, 30, 260, 260);

        using var trazo = new Pen(Color.White, 28)
        {
            StartCap = LineCap.Round,
            EndCap = LineCap.Round,
            LineJoin = LineJoin.Round
        };

        if (capturada)
        {
            grafico.DrawLines(trazo,
            new Point[]
            {
                new Point(92, 164),
                new Point(140, 212),
                new Point(232, 112)
            });
        }
        else
        {
            grafico.DrawLine(trazo, 105, 105, 215, 215);
            grafico.DrawLine(trazo, 215, 105, 105, 215);
        }

        using var stream = new MemoryStream();
        imagen.Save(stream, ImageFormat.Jpeg);
        return stream.ToArray();
    }

    private static byte[] ConvertirABytes(object datos)
    {
        if (datos is byte[] bytes)
        {
            return bytes;
        }

        if (datos is Array array)
        {
            var resultado = new byte[array.Length];
            for (var i = 0; i < array.Length; i++)
            {
                resultado[i] = Convert.ToByte(array.GetValue(i));
            }

            return resultado;
        }

        throw new InvalidOperationException("Formato de plantilla ActiveX no soportado.");
    }

    private static void LiberarCom(object? instancia)
    {
        if (instancia is not null && Marshal.IsComObject(instancia))
        {
            Marshal.FinalReleaseComObject(instancia);
        }
    }

    private sealed class ConversorImagenActiveX : System.Windows.Forms.AxHost
    {
        private ConversorImagenActiveX() : base(string.Empty)
        {
        }

        public static Image Convertir(object picture) =>
            GetPictureFromIPictureDisp(picture);
    }
}

internal sealed class CaracteristicasActiveX(object valor)
{
    public object Valor { get; } = valor;
}
#endif
