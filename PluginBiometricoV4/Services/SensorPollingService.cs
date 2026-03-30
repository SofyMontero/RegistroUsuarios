using PluginBiometricoV4.Api;
using PluginBiometricoV4.Config;

namespace PluginBiometricoV4.Services;

/// <summary>
/// Equivalente al <c>Timer</c> + <c>HabilitarLector.sendGet</c> del plugin Java.
/// </summary>
public sealed class SensorPollingService : IDisposable
{
    private readonly LocalConfigStore _store;
    private readonly HabilitarSensorClient _client;
    private CancellationTokenSource? _cts;
    private Task? _loopTask;
    private long _timestamp;
    private int _pollInFlight;

    public SensorPollingService(LocalConfigStore store, HabilitarSensorClient? client = null)
    {
        _store = store;
        _client = client ?? new HabilitarSensorClient();
    }

    /// <summary>
    /// Se dispara en hilo de fondo cuando <c>opc</c> es <c>capturar</c> o <c>leer</c> (como en Java).
    /// El suscriptor debe invocar <c>BeginInvoke</c> si actualiza UI.
    /// </summary>
    public event EventHandler<HabilitarSensorResponse>? CommandReceived;

    public void Start()
    {
        Stop();
        _cts = new CancellationTokenSource();
        _timestamp = DateTimeOffset.UtcNow.ToUnixTimeSeconds();
        _loopTask = Task.Run(() => RunLoopAsync(_cts.Token));
    }

    public void Stop()
    {
        _cts?.Cancel();
        try
        {
            _loopTask?.GetAwaiter().GetResult();
        }
        catch (OperationCanceledException)
        {
            // esperado
        }
        _loopTask = null;
        _cts?.Dispose();
        _cts = null;
    }

    private async Task RunLoopAsync(CancellationToken ct)
    {
        while (!ct.IsCancellationRequested)
        {
            var url = _store.Get(LocalConfigStore.KeyUrlHabSensor);
            var token = _store.Get(LocalConfigStore.KeyUniqueId);
            if (string.IsNullOrWhiteSpace(url) || string.IsNullOrWhiteSpace(token))
            {
                await Task.Delay(1000, ct).ConfigureAwait(false);
                continue;
            }

            if (Interlocked.CompareExchange(ref _pollInFlight, 1, 0) != 0)
            {
                await Task.Delay(200, ct).ConfigureAwait(false);
                continue;
            }

            try
            {
                var resp = await _client.PollAsync(url, _timestamp, token, ct).ConfigureAwait(false);
                if (resp is not null)
                {
                    _timestamp = resp.FechaCreacion;
                    if (resp.Opc is "capturar" or "leer")
                        CommandReceived?.Invoke(this, resp);
                }
            }
            catch (OperationCanceledException) when (ct.IsCancellationRequested)
            {
                break;
            }
            finally
            {
                Interlocked.Exchange(ref _pollInFlight, 0);
            }

            try
            {
                await Task.Delay(1000, ct).ConfigureAwait(false);
            }
            catch (OperationCanceledException)
            {
                break;
            }
        }
    }

    public void Dispose()
    {
        Stop();
        _client.Dispose();
    }
}
