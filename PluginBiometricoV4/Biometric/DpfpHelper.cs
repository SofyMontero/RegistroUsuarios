using System.Drawing;
using System.Drawing.Imaging;
using DPFP;
using DPFP.Capture;
using DPFP.Processing;

namespace PluginBiometricoV4.Biometric;

internal static class DpfpHelper
{
    public static FeatureSet? ExtractFeatures(Sample sample, DataPurpose purpose)
    {
        var extractor = new FeatureExtraction();
        CaptureFeedback feedback = CaptureFeedback.None;
        var features = new FeatureSet();
        extractor.CreateFeatureSet(sample, purpose, ref feedback, ref features);
        return feedback == CaptureFeedback.Good ? features : null;
    }

    /// <summary>Imagen JPG en Base64, similar al flujo Java (bitmap redimensionado).</summary>
    public static string SampleToJpegBase64(Sample sample, int width = 450, int height = 500)
    {
        var convertor = new SampleConversion();
        Bitmap? bmp = null;
        try
        {
            convertor.ConvertToPicture(sample, ref bmp);
            if (bmp is null)
                return "";

            using var resized = new Bitmap(width, height, PixelFormat.Format24bppRgb);
            using (var g = Graphics.FromImage(resized))
            {
                g.DrawImage(bmp, 0, 0, width, height);
            }

            using var ms = new MemoryStream();
            resized.Save(ms, ImageFormat.Jpeg);
            return Convert.ToBase64String(ms.ToArray());
        }
        finally
        {
            bmp?.Dispose();
        }
    }
}
