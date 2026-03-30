using System.Text.Json;

namespace PluginBiometricoV4.Api;

/// <summary>
/// GET al endpoint de habilitación (mismo contrato que <c>HabilitarLector.java</c>).
/// </summary>
public sealed class HabilitarSensorClient : IDisposable
{
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNameCaseInsensitive = true,
    };

    private readonly HttpClient _http;
    private readonly bool _disposeHttp;

    public HabilitarSensorClient(HttpClient? http = null)
    {
        if (http is not null)
        {
            _http = http;
            _disposeHttp = false;
            return;
        }

        _http = new HttpClient();
        _disposeHttp = true;
        _http.DefaultRequestHeaders.UserAgent.ParseAdd("Mozilla/5.0");
        // PHP puede mantener la conexión ~150s en el bucle de long-poll (usleep + límite 1500).
        _http.Timeout = TimeSpan.FromSeconds(180);
    }

    /// <summary>
    /// Una petición de long-poll. Devuelve null si hay error de red o JSON inválido.
    /// </summary>
    public async Task<HabilitarSensorResponse?> PollAsync(
        string habilitarSensorUrl,
        long timestampSeconds,
        string token,
        CancellationToken cancellationToken = default)
    {
        if (string.IsNullOrWhiteSpace(habilitarSensorUrl) || string.IsNullOrWhiteSpace(token))
            return null;

        var uri = BuildRequestUri(habilitarSensorUrl, timestampSeconds, token);

        using var response = await _http.GetAsync(uri, cancellationToken).ConfigureAwait(false);
        if (!response.IsSuccessStatusCode)
            return null;

        await using var stream = await response.Content.ReadAsStreamAsync(cancellationToken).ConfigureAwait(false);
        try
        {
            return await JsonSerializer.DeserializeAsync<HabilitarSensorResponse>(stream, JsonOptions, cancellationToken)
                .ConfigureAwait(false);
        }
        catch (JsonException)
        {
            return null;
        }
    }

    private static Uri BuildRequestUri(string baseUrl, long timestampSeconds, string token)
    {
        var sep = baseUrl.Contains('?', StringComparison.Ordinal) ? '&' : '?';
        var ts = Uri.EscapeDataString(timestampSeconds.ToString());
        var tk = Uri.EscapeDataString(token);
        var cacheBust = DateTimeOffset.UtcNow.ToUnixTimeMilliseconds();
        var s = $"{baseUrl.Trim()}{sep}timestamp={ts}&token={tk}&_={cacheBust}";
        return new Uri(s, UriKind.Absolute);
    }

    public void Dispose()
    {
        if (_disposeHttp)
            _http.Dispose();
    }
}
