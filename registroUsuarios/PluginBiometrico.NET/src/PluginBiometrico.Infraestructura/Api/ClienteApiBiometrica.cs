using System.Text;
using System.Text.Json;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Infraestructura.Api;

/// <summary>
/// Implementación HTTP compatible con el plugin Java original.
/// </summary>
public sealed class ClienteApiBiometrica : IClienteApiBiometrica
{
    private static readonly JsonSerializerOptions OpcionesJson = new()
    {
        PropertyNameCaseInsensitive = true
    };

    private readonly HttpClient _http;
    private readonly ConfiguracionLocal _config;
    private readonly Action<string, string, string, object?>? _depuracion;

    public ClienteApiBiometrica(
        HttpClient http,
        ConfiguracionLocal config,
        Action<string, string, string, object?>? depuracion = null)
    {
        _http = http;
        _config = config;
        _depuracion = depuracion;
    }

    public async Task<ComandoSensor> EsperarComandoAsync(long ultimaFechaUnix, CancellationToken cancellationToken)
    {
        var url = ConstruirUrlHabilitarSensor(ultimaFechaUnix);

        // #region agent log
        _depuracion?.Invoke("H2", "ClienteApiBiometrica.EsperarComandoAsync", "Consultando servidor", new
        {
            urlBase = _config.UrlHabilitarSensor,
            ultimaFechaUnix
        });
        // #endregion

        using var request = new HttpRequestMessage(HttpMethod.Get, url);
        request.Headers.UserAgent.ParseAdd("Mozilla/5.0");
        request.Headers.AcceptCharset.ParseAdd("UTF-8");

        using var response = await _http.SendAsync(request, cancellationToken);
        var cuerpo = await response.Content.ReadAsStringAsync(cancellationToken);

        // #region agent log
        _depuracion?.Invoke("H3", "ClienteApiBiometrica.EsperarComandoAsync", "Respuesta HTTP", new
        {
            status = (int)response.StatusCode,
            longitudCuerpo = cuerpo.Length
        });
        // #endregion

        response.EnsureSuccessStatusCode();

        var comando = JsonSerializer.Deserialize<ComandoSensor>(cuerpo, OpcionesJson)
                      ?? new ComandoSensor();

        // #region agent log
        _depuracion?.Invoke("H4", "ClienteApiBiometrica.EsperarComandoAsync", "JSON deserializado", new
        {
            comando.Operacion,
            comando.FechaCreacion
        });
        // #endregion

        return comando;
    }

    public async Task GuardarHuellaAsync(GuardarHuellaRequest datos, CancellationToken cancellationToken)
    {
        var url = ConstruirUrlConCacheBuster(_config.UrlApiRest);
        var json = JsonSerializer.Serialize(datos, OpcionesJson);

        using var contenido = new StringContent(json, Encoding.UTF8, "application/json");
        using var request = new HttpRequestMessage(HttpMethod.Post, url) { Content = contenido };
        request.Headers.UserAgent.ParseAdd("Mozilla/5.0");

        using var response = await _http.SendAsync(request, cancellationToken);
        response.EnsureSuccessStatusCode();
    }

    public async Task ActualizarHuellaAsync(ActualizarHuellaRequest datos, CancellationToken cancellationToken)
    {
        var url = ConstruirUrlConCacheBuster(_config.UrlApiRest);
        var json = JsonSerializer.Serialize(datos, OpcionesJson);

        using var contenido = new StringContent(json, Encoding.UTF8, "application/json");
        using var request = new HttpRequestMessage(HttpMethod.Put, url) { Content = contenido };
        request.Headers.UserAgent.ParseAdd("Mozilla/5.0");

        using var response = await _http.SendAsync(request, cancellationToken);
        response.EnsureSuccessStatusCode();
    }

    public async Task<IReadOnlyList<PlantillaUsuario>> ObtenerPlantillasAsync(
        int desde,
        int hasta,
        CancellationToken cancellationToken)
    {
        var url = $"{_config.UrlApiRest}" +
                  $"?token={Uri.EscapeDataString(_config.IdUnicoPc)}" +
                  $"&desde={desde}&hasta={hasta}" +
                  $"&_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";

        using var request = new HttpRequestMessage(HttpMethod.Get, url);
        request.Headers.UserAgent.ParseAdd("Mozilla/5.0");
        request.Headers.AcceptCharset.ParseAdd("UTF-8");

        using var response = await _http.SendAsync(request, cancellationToken);
        var cuerpo = await response.Content.ReadAsStringAsync(cancellationToken);
        response.EnsureSuccessStatusCode();

        return JsonSerializer.Deserialize<List<PlantillaUsuario>>(cuerpo, OpcionesJson) ?? new List<PlantillaUsuario>();
    }

    public async Task<IReadOnlyList<PlantillaUsuario>> ObtenerPlantillasPorDocumentoAsync(
        string documento,
        CancellationToken cancellationToken)
    {
        var url = $"{_config.UrlApiRest}" +
                  $"?token={Uri.EscapeDataString(_config.IdUnicoPc)}" +
                  $"&documento={Uri.EscapeDataString(documento)}" +
                  $"&_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";

        // #region agent log
        _depuracion?.Invoke("S6-H4", "ClienteApiBiometrica.ObtenerPlantillasPorDocumentoAsync", "Consulta 1:1", new
        {
            documento
        });
        // #endregion

        using var request = new HttpRequestMessage(HttpMethod.Get, url);
        request.Headers.UserAgent.ParseAdd("Mozilla/5.0");
        request.Headers.AcceptCharset.ParseAdd("UTF-8");

        using var response = await _http.SendAsync(request, cancellationToken);
        var cuerpo = await response.Content.ReadAsStringAsync(cancellationToken);
        response.EnsureSuccessStatusCode();

        return JsonSerializer.Deserialize<List<PlantillaUsuario>>(cuerpo, OpcionesJson) ?? new List<PlantillaUsuario>();
    }

    private string ConstruirUrlHabilitarSensor(long ultimaFechaUnix)
    {
        return $"{_config.UrlHabilitarSensor}" +
               $"?timestamp={Uri.EscapeDataString(ultimaFechaUnix.ToString())}" +
               $"&token={Uri.EscapeDataString(_config.IdUnicoPc)}" +
               $"&_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";
    }

    private static string ConstruirUrlConCacheBuster(string urlBase)
    {
        var separador = urlBase.Contains('?') ? '&' : '?';
        return $"{urlBase}{separador}_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";
    }
}
