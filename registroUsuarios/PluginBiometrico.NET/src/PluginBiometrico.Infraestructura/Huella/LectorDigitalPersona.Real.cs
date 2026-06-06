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
public sealed partial class LectorDigitalPersona : Capture.EventHandler
{
    private Capture? _capturador;
    private Enrollment? _enrollment;
    private byte[]? _ultimaImagenJpeg;

    private static partial bool SdkEstaDisponible() => true;

    partial void IniciarCapturaReal()
    {
        _enrollment = new Enrollment();
        _capturador = new Capture();

        if (_capturador is null)
        {
            NotificarMensaje("No se pudo iniciar el lector de huellas.");
            return;
        }

        _capturador.EventHandler = this;
        _capturador.StartCapture();
        NotificarMensaje("Utilizando el lector de huella dactilar");
    }

    partial void DetenerCapturaReal()
    {
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

    partial void LiberarRecursosReal()
    {
        _capturador = null;
        _enrollment = null;
    }

    public void OnComplete(object capture, string readerSerialNumber, Sample sample) =>
        ProcesarMuestra(sample);

    public void OnFingerTouch(object capture, string readerSerialNumber) =>
        NotificarMensaje("Dedo colocado sobre el lector.");

    public void OnFingerGone(object capture, string readerSerialNumber) =>
        NotificarMensaje("Dedo retirado del lector.");

    public void OnReaderConnect(object capture, string readerSerialNumber) =>
        NotificarMensaje("Sensor activado o conectado.");

    public void OnReaderDisconnect(object capture, string readerSerialNumber) =>
        NotificarMensaje("Sensor desactivado o no conectado.");

    public void OnSampleQuality(object capture, string readerSerialNumber, CaptureFeedback feedback) { }

    public void OnFeatureSet(object capture, string readerSerialNumber, FeatureSet featureSet) { }

    private void ProcesarMuestra(Sample sample)
    {
        if (_enrollment is null)
        {
            return;
        }

        NotificarMensaje("Huella dactilar capturada.");

        var features = ExtraerCaracteristicas(sample, DataPurpose.Enrollment);
        if (features is null)
        {
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
                    var plantilla = _enrollment.Template.Serialize();
                    NotificarMuestra(new EventoMuestraHuella
                    {
                        Mensaje = "La plantilla ha sido creada ya puede identificarla",
                        EstadoPlantilla = estadoPlantilla,
                        ImagenJpeg = _ultimaImagenJpeg,
                        PlantillaSerializada = plantilla,
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

    private static FeatureSet? ExtraerCaracteristicas(Sample sample, DataPurpose proposito)
    {
        var extractor = new FeatureExtraction();
        try
        {
            return extractor.CreateFeatureSet(sample, proposito);
        }
        catch
        {
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
        using var stream = new MemoryStream();
        bitmap.Save(stream, ImageFormat.Jpeg);
        return stream.ToArray();
    }
}
#endif
