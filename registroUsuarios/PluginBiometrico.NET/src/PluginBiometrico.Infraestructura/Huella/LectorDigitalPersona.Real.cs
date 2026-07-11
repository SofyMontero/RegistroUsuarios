#if TIENE_SDK_DPFP
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;
using DPFP;
using DPFP.Capture;
using DPFP.Processing;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>Implementación real con SDK Digital Persona One Touch (.NET).</summary>
public sealed partial class LectorDigitalPersona : DPFP.Capture.EventHandler
{
    private enum ModoOperacion
    {
        Captura,
        Verificacion
    }

    private Capture? _capturador;
    private Enrollment? _enrollment;
    private ModoOperacion _modo = ModoOperacion.Captura;
    private byte[]? _ultimaImagenJpeg;
    private bool _capturaActiva;
    private string? _lectorActivoSerial;

    partial void EstablecerModoCaptura()
    {
        _modo = ModoOperacion.Captura;
        _enrollment = new Enrollment();
    }

    partial void EstablecerModoVerificacion()
    {
        _modo = ModoOperacion.Verificacion;
        _enrollment = null;
    }

    partial void IniciarCapturaReal()
    {
        _capturador ??= new Capture();

        if (_capturador is null)
        {
            NotificarMensaje("No se pudo iniciar el lector de huellas.");
            return;
        }

        _capturador.EventHandler = this;
        _capturaActiva = true;
        ReiniciarCapturaEnLector("Iniciando captura");
    }

    partial void DetenerCapturaReal()
    {
        _capturaActiva = false;

        if (_capturador is not null)
        {
            try
            {
                _capturador.StopCapture();
            }
            catch
            {
                // ignorar si ya estaba detenido
            }
        }
    }

    private void ReiniciarCapturaEnLector(string motivo)
    {
        if (_capturador is null || !_capturaActiva)
        {
            return;
        }

        try
        {
            try
            {
                _capturador.StopCapture();
            }
            catch
            {
                // puede no estar activa aún
            }

            _capturador.StartCapture();
            NotificarMensaje("Utilizando el lector de huella dactilar");
            NotificarMensaje($"{motivo}. Coloque el dedo firmemente 2-3 segundos.");
        }
        catch (Exception ex)
        {
            NotificarMensaje($"No se pudo activar el lector: {ex.Message}");
        }
    }

    private static bool EsLectorDigitalPersona(string readerSerialNumber) =>
        readerSerialNumber.Contains("05BA", StringComparison.OrdinalIgnoreCase)
        || readerSerialNumber.Contains("Digital Persona", StringComparison.OrdinalIgnoreCase)
        || readerSerialNumber.Contains("U.are.U", StringComparison.OrdinalIgnoreCase);

    partial void LiberarRecursosReal()
    {
        _capturaActiva = false;
        _capturador = null;
        _enrollment = null;
        _lectorActivoSerial = null;
    }

    public void OnComplete(object capture, string readerSerialNumber, Sample sample)
    {
        _lectorActivoSerial = readerSerialNumber;

        if (!EsLectorDigitalPersona(readerSerialNumber))
        {
            NotificarMensaje(
                "La muestra llegó desde un lector incompatible (probablemente WBF del portátil). " +
                "Desactive 'ELAN WBF Fingerprint Sensor' en Administrador de dispositivos y use solo U.are.U 4500.");
            return;
        }

        if (_modo == ModoOperacion.Verificacion)
        {
            ProcesarMuestraVerificacion(sample);
        }
        else
        {
            ProcesarMuestraCaptura(sample);
        }
    }

    public void OnFingerTouch(object capture, string readerSerialNumber)
    {
        if (!EsLectorDigitalPersona(readerSerialNumber))
        {
            NotificarMensaje("Use el lector U.are.U 4500 (USB), no el sensor integrado del portátil.");
            return;
        }

        NotificarMensaje("Dedo colocado sobre el lector.");
    }

    public void OnFingerGone(object capture, string readerSerialNumber) =>
        NotificarMensaje("Dedo retirado del lector.");

    public void OnReaderConnect(object capture, string readerSerialNumber)
    {
        _lectorActivoSerial = readerSerialNumber;

        if (EsLectorDigitalPersona(readerSerialNumber))
        {
            NotificarMensaje($"U.are.U conectado ({readerSerialNumber}).");
            if (_capturaActiva)
            {
                ReiniciarCapturaEnLector("Lector USB listo");
            }
            return;
        }

        NotificarMensaje(
            $"Sensor detectado: {readerSerialNumber}. Si no es U.are.U 4500, desactívelo en Administrador de dispositivos.");
    }

    public void OnReaderDisconnect(object capture, string readerSerialNumber)
    {
        if (EsLectorDigitalPersona(readerSerialNumber))
        {
            NotificarMensaje("U.are.U desconectado. Verifique el cable USB.");
            return;
        }

        NotificarMensaje("Sensor desactivado o no conectado.");
    }

    public void OnSampleQuality(object capture, string readerSerialNumber, CaptureFeedback feedback)
    {
        if (feedback is CaptureFeedback.Good or CaptureFeedback.None)
        {
            return;
        }

        NotificarMensaje($"Calidad insuficiente ({feedback}). Limpie el lector y presione el dedo de nuevo 2-3 s.");
    }

    public void OnFeatureSet(object capture, string readerSerialNumber, FeatureSet featureSet) { }

    private void ProcesarMuestraCaptura(Sample sample)
    {
        if (_enrollment is null)
        {
            return;
        }

        NotificarMensaje("Huella dactilar capturada.");

        var features = ExtraerCaracteristicas(sample, DataPurpose.Enrollment, out var feedbackCalidad);
        if (features is null)
        {
            var detalle = feedbackCalidad is CaptureFeedback.Good or CaptureFeedback.None
                ? "No se pudieron extraer características"
                : $"Calidad: {feedbackCalidad}";
            NotificarMensaje($"Muestra rechazada ({detalle}). Intente de nuevo con el dedo seco y centrado.");
            return;
        }

        try
        {
            _enrollment.AddFeatures(features);
            _ultimaImagenJpeg = ConvertirMuestraAJpeg(sample);
            var estadoPlantilla = $"Muestras Restantes: {_enrollment.FeaturesNeeded}";

            NotificarMuestra(new EventoMuestraHuella
            {
                Mensaje = "Huella dactilar capturada.",
                EstadoPlantilla = estadoPlantilla,
                ImagenJpeg = _ultimaImagenJpeg,
                Estado = EstadoEnrollment.EnProgreso
            });

            switch (_enrollment.TemplateStatus)
            {
                case Enrollment.Status.Ready:
                    DetenerCapturaReal();
                    byte[]? plantillaBytes = null;
                    plantillaBytes = _enrollment.Template.Serialize(ref plantillaBytes);
                    NotificarMuestra(new EventoMuestraHuella
                    {
                        Mensaje = "La plantilla ha sido creada ya puede identificarla",
                        EstadoPlantilla = estadoPlantilla,
                        ImagenJpeg = _ultimaImagenJpeg,
                        PlantillaSerializada = plantillaBytes,
                        Estado = EstadoEnrollment.PlantillaLista
                    });
                    break;

                case Enrollment.Status.Failed:
                    _enrollment.Clear();
                    DetenerCapturaReal();
                    NotificarMuestra(new EventoMuestraHuella
                    {
                        Mensaje = "La plantilla no pudo ser creada",
                        EstadoPlantilla = estadoPlantilla,
                        ImagenJpeg = _ultimaImagenJpeg,
                        Estado = EstadoEnrollment.Fallido
                    });
                    break;
            }
        }
        catch (Exception ex)
        {
            NotificarMensaje($"Error procesando huella: {ex.Message}");
        }
    }

    private void ProcesarMuestraVerificacion(Sample sample)
    {
        NotificarMensaje("Huella dactilar capturada.");

        var features = ExtraerCaracteristicas(sample, DataPurpose.Verification, out _);
        if (features is null)
        {
            NotificarMensaje("Muestra no válida para verificación. Intente de nuevo.");
            return;
        }

        _ultimaImagenJpeg = ConvertirMuestraAJpeg(sample);

        NotificarVerificacion(new EventoVerificacionHuella
        {
            Mensaje = "Huella dactilar capturada.",
            ImagenJpeg = _ultimaImagenJpeg,
            CaracteristicasBiometricas = features
        });
    }

    private static FeatureSet? ExtraerCaracteristicas(
        Sample sample,
        DataPurpose proposito,
        out CaptureFeedback feedback)
    {
        feedback = CaptureFeedback.Good;
        var extractor = new FeatureExtraction();
        try
        {
            FeatureSet features = new();
            extractor.CreateFeatureSet(sample, proposito, ref feedback, ref features);

            if (feedback is CaptureFeedback.Good or CaptureFeedback.None)
            {
                return features;
            }

            return null;
        }
        catch
        {
            feedback = CaptureFeedback.None;
            return null;
        }
    }

    private static byte[]? ConvertirMuestraAJpeg(Sample sample)
    {
        var convertidor = new SampleConversion();
        Bitmap? bitmap = null;
        convertidor.ConvertToPicture(sample, ref bitmap);

        if (bitmap is null)
        {
            return null;
        }

        using (bitmap)
        {
            using var stream = new MemoryStream();
            bitmap.Save(stream, ImageFormat.Jpeg);
            return stream.ToArray();
        }
    }
}
#endif
