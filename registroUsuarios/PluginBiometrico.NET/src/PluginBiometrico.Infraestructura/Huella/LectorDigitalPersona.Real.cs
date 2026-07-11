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
    private bool _escuchaActiva;
    private string? _lectorActivoSerial;
    private string? _lectorUsbSerial;

    /// <summary>Recuerda el U.are.U entre sesiones (cada captura crea un lector nuevo).</summary>
    private static string? UltimoLectorUsbConocido;

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
        try
        {
            _capturador?.StopCapture();
        }
        catch
        {
            // ignorar
        }

        _capturador = new Capture();
        _capturador.EventHandler = this;
        _capturaActiva = true;
        _escuchaActiva = false;
        _lectorUsbSerial = UltimoLectorUsbConocido;

        // Igual que el plugin Java: startCapture() de inmediato (el lector ya puede estar conectado).
        ActivarEscuchaLector("Iniciando captura");
    }

    private void RegistrarLectorUsb(string readerSerialNumber)
    {
        if (EsSensorIntegradoWbf(readerSerialNumber))
        {
            return;
        }

        UltimoLectorUsbConocido = readerSerialNumber.Trim();
        _lectorUsbSerial = UltimoLectorUsbConocido;
    }

    partial void DetenerCapturaReal()
    {
        _capturaActiva = false;
        _escuchaActiva = false;

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

    private void ActivarEscuchaLector(string motivo)
    {
        if (_capturador is null || !_capturaActiva || _escuchaActiva)
        {
            return;
        }

        try
        {
            _capturador.StartCapture();
            _escuchaActiva = true;
            NotificarMensaje("Utilizando el lector de huella dactilar");
            NotificarMensaje($"{motivo}. Presione el dedo 4 segundos y retírelo despacio.");
        }
        catch (Exception ex)
        {
            NotificarMensaje($"No se pudo activar el lector: {ex.Message}");
        }
    }

    private static readonly string GuidSensorIntegradoWbf = "00000000-0000-0000-0000-000000000000";

    /// <summary>
    /// El SDK reporta el sensor WBF del portátil con GUID vacío.
    /// U.are.U 4500 usa un GUID real (ej. 39cae277-7977-7348-bcac-...).
    /// </summary>
    private static bool EsSensorIntegradoWbf(string readerSerialNumber)
    {
        if (string.IsNullOrWhiteSpace(readerSerialNumber))
        {
            return true;
        }

        return readerSerialNumber.Trim()
            .Equals(GuidSensorIntegradoWbf, StringComparison.OrdinalIgnoreCase);
    }

    /// <summary>
    /// OnComplete a veces llega con serial vacío aunque el dedo fue en el U.are.U.
    /// </summary>
    private string ResolverSerialLector(string readerSerialNumber)
    {
        if (!string.IsNullOrWhiteSpace(readerSerialNumber)
            && !EsSensorIntegradoWbf(readerSerialNumber))
        {
            return readerSerialNumber.Trim();
        }

        if (!string.IsNullOrWhiteSpace(_lectorUsbSerial))
        {
            return _lectorUsbSerial;
        }

        if (!string.IsNullOrWhiteSpace(UltimoLectorUsbConocido))
        {
            return UltimoLectorUsbConocido;
        }

        return readerSerialNumber?.Trim() ?? string.Empty;
    }

    private bool DebeIgnorarEvento(string readerSerialNumber)
    {
        var serial = ResolverSerialLector(readerSerialNumber);
        return EsSensorIntegradoWbf(serial);
    }

    partial void LiberarRecursosReal()
    {
        _capturaActiva = false;
        _escuchaActiva = false;
        _capturador = null;
        _enrollment = null;
        _lectorActivoSerial = null;
    }

    public void OnComplete(object capture, string readerSerialNumber, Sample sample)
    {
        var serial = ResolverSerialLector(readerSerialNumber);

        if (EsSensorIntegradoWbf(serial))
        {
            NotificarMensaje("Muestra descartada (sensor integrado). Use el U.are.U USB.");
            return;
        }

        RegistrarLectorUsb(serial);
        _lectorActivoSerial = serial;
        NotificarMensaje("Huella dactilar capturada.");

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
        if (DebeIgnorarEvento(readerSerialNumber))
        {
            return;
        }

        RegistrarLectorUsb(ResolverSerialLector(readerSerialNumber));
        NotificarMensaje("Dedo colocado sobre el lector U.are.U.");
    }

    public void OnFingerGone(object capture, string readerSerialNumber)
    {
        if (DebeIgnorarEvento(readerSerialNumber))
        {
            return;
        }

        NotificarMensaje("Dedo retirado. Si no apareció 'Huella capturada', presione 4 segundos la próxima vez.");
    }

    public void OnReaderConnect(object capture, string readerSerialNumber)
    {
        if (EsSensorIntegradoWbf(readerSerialNumber))
        {
            return;
        }

        RegistrarLectorUsb(readerSerialNumber);
        _lectorActivoSerial = readerSerialNumber;
        NotificarMensaje($"U.are.U listo ({readerSerialNumber}).");
    }

    public void OnReaderDisconnect(object capture, string readerSerialNumber)
    {
        if (EsSensorIntegradoWbf(readerSerialNumber))
        {
            NotificarMensaje("Sensor integrado desactivado.");
            return;
        }

        NotificarMensaje("U.are.U desconectado. Verifique el cable USB.");
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
                    _escuchaActiva = false;
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
