using DPFP;
using DPFP.Capture;

namespace PluginBiometricoV4.Biometric;

/// <summary>
/// Sesión de captura DPFP (equivalente a listeners sobre <c>DPFPCapture</c> en Java).
/// </summary>
public sealed class FingerprintReaderSession : DPFP.Capture.EventHandler, IDisposable
{
    private readonly Capture _capture;
    private readonly SynchronizationContext? _sync;

    public FingerprintReaderSession()
    {
        _sync = SynchronizationContext.Current;
        _capture = new Capture { EventHandler = this };
    }

    public event Action<string>? Status;
    public event Action<Sample>? SampleAcquired;

    public void Start() => _capture.StartCapture();

    public void Stop() => _capture.StopCapture();

    public void OnComplete(object capture, string readerSerialNumber, Sample sample) =>
        Post(() => SampleAcquired?.Invoke(sample));

    public void OnFingerGone(object capture, string readerSerialNumber) =>
        Post(() => Status?.Invoke("Dedo retirado del lector."));

    public void OnFingerTouch(object capture, string readerSerialNumber) =>
        Post(() => Status?.Invoke("Dedo colocado sobre el lector."));

    public void OnReaderConnect(object capture, string readerSerialNumber) =>
        Post(() => Status?.Invoke("Sensor conectado."));

    public void OnReaderDisconnect(object capture, string readerSerialNumber) =>
        Post(() => Status?.Invoke("Sensor desconectado."));

    public void OnSampleQuality(object capture, string readerSerialNumber, CaptureFeedback feedback) =>
        Post(() => Status?.Invoke("Calidad de muestra insuficiente; vuelva a colocar el dedo."));

    private void Post(Action action)
    {
        if (_sync is not null)
            _sync.Post(_ => action(), null);
        else
            action();
    }

    public void Dispose()
    {
        try
        {
            _capture.StopCapture();
        }
        catch
        {
            // ignorar si ya estaba detenido
        }
    }
}
