using System.Text.Json;
using System.Text.Json.Serialization;

namespace PluginBiometricoV4.Api;

/// <summary>
/// Cliente para <c>Model/UsuarioRestApi.php</c> (GET lista, POST plantilla, PUT actualización / verificación).
/// </summary>
public sealed class UsuarioRestApiClient : IDisposable
{
    private static readonly JsonSerializerOptions JsonRead = new()
    {
        PropertyNameCaseInsensitive = true,
    };

    private static readonly JsonSerializerOptions JsonWrite = new()
    {
        PropertyNamingPolicy = null,
        DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull,
    };

    private readonly HttpClient _http;
    private readonly bool _disposeHttp;

    public UsuarioRestApiClient(HttpClient? http = null)
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
        _http.Timeout = TimeSpan.FromMinutes(3);
    }

    /// <param name="desde">OFFSET (primera fila = 0).</param>
    /// <param name="hasta">Tamaño de página (LIMIT), máx. 500 en el servidor.</param>
    public async Task<IReadOnlyList<HuellaListaItem>> GetHuellasAsync(
        string restApiBaseUrl,
        string token,
        int desde,
        int hasta,
        CancellationToken cancellationToken = default)
    {
        if (string.IsNullOrWhiteSpace(restApiBaseUrl) || string.IsNullOrWhiteSpace(token))
            return Array.Empty<HuellaListaItem>();

        var sep = restApiBaseUrl.Contains('?', StringComparison.Ordinal) ? '&' : '?';
        var url =
            $"{restApiBaseUrl.Trim()}{sep}token={Uri.EscapeDataString(token)}&desde={desde}&hasta={hasta}&_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";

        using var response = await _http.GetAsync(url, cancellationToken).ConfigureAwait(false);
        if (!response.IsSuccessStatusCode)
            return Array.Empty<HuellaListaItem>();

        await using var stream = await response.Content.ReadAsStreamAsync(cancellationToken).ConfigureAwait(false);
        try
        {
            var list = await JsonSerializer.DeserializeAsync<List<HuellaListaItem>>(stream, JsonRead, cancellationToken)
                .ConfigureAwait(false);
            return list ?? (IReadOnlyList<HuellaListaItem>)Array.Empty<HuellaListaItem>();
        }
        catch (JsonException)
        {
            return Array.Empty<HuellaListaItem>();
        }
    }

    public async Task<bool> PostAsociarHuellaAsync(string restApiBaseUrl, FingerTempPayload body, CancellationToken cancellationToken = default)
    {
        var url = AppendCacheBust(restApiBaseUrl);
        var json = JsonSerializer.Serialize(body, JsonWrite);
        using var content = new StringContent(json, System.Text.Encoding.UTF8, "application/json");
        using var request = new HttpRequestMessage(HttpMethod.Post, url) { Content = content };
        request.Headers.TryAddWithoutValidation("Accept", "*/*");

        using var response = await _http.SendAsync(request, cancellationToken).ConfigureAwait(false);
        return response.IsSuccessStatusCode;
    }

    public async Task<bool> PutActualizarHuellaAsync(string restApiBaseUrl, FingerTempPayload body, CancellationToken cancellationToken = default)
    {
        var url = AppendCacheBust(restApiBaseUrl);
        var json = JsonSerializer.Serialize(body, JsonWrite);
        using var content = new StringContent(json, System.Text.Encoding.UTF8, "application/json");
        using var request = new HttpRequestMessage(HttpMethod.Put, url) { Content = content };
        request.Headers.TryAddWithoutValidation("Accept", "*/*");

        using var response = await _http.SendAsync(request, cancellationToken).ConfigureAwait(false);
        return response.IsSuccessStatusCode;
    }

    public void Dispose()
    {
        if (_disposeHttp)
            _http.Dispose();
    }

    private static string AppendCacheBust(string restApiBaseUrl)
    {
        var b = restApiBaseUrl.Trim();
        var sep = b.Contains('?', StringComparison.Ordinal) ? '&' : '?';
        return $"{b}{sep}_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";
    }
}
