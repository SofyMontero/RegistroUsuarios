using System.Net.Http;
using System.Text.Json;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.App.Ayudantes;

/// <summary>
/// Verifica que el servidor PHP responda antes de guardar la configuración.
/// </summary>
public static class ProbadorConexionServidor
{
    private static readonly JsonSerializerOptions OpcionesJson = new()
    {
        PropertyNameCaseInsensitive = true
    };

    public static async Task<(bool Exito, string Mensaje)> ProbarAsync(ConfiguracionLocal config)
    {
        if (!ConstructorUrlsServidor.EsUrlValida(config.UrlHabilitarSensor))
        {
            return (false, "La URL del sensor no es válida. Use http:// o https://");
        }

        if (!ConstructorUrlsServidor.EsUrlValida(config.UrlApiRest))
        {
            return (false, "La URL de la API REST no es válida. Use http:// o https://");
        }

        if (string.IsNullOrWhiteSpace(config.IdUnicoPc))
        {
            return (false, "Indique el ID único de esta PC (token).");
        }

        using var http = new HttpClient { Timeout = TimeSpan.FromSeconds(12) };
        var token = Uri.EscapeDataString(config.IdUnicoPc.Trim());

        var urlSensor = $"{config.UrlHabilitarSensor.Trim()}" +
                        $"?timestamp=0&token={token}&ping=1" +
                        $"&_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";

        var (exitoSensor, mensajeSensor) = await ProbarSensorAsync(http, urlSensor);
        if (exitoSensor)
        {
            return (true, mensajeSensor ?? "Conexión correcta con el sensor.");
        }

        var urlApi = $"{config.UrlApiRest.Trim()}?desde=0&hasta=0&token={token}";
        var (exitoApi, mensajeApi) = await ProbarApiRestAsync(http, urlApi);
        if (exitoApi)
        {
            return (true,
                "Conexión con el servidor de producción correcta (API REST). " +
                "Actualice HabilitarSensor.php en el servidor para habilitar la prueba directa del sensor.");
        }

        return (false, mensajeSensor ?? mensajeApi ?? "No se pudo conectar al servidor.");
    }

    private static async Task<(bool Exito, string? Mensaje)> ProbarSensorAsync(HttpClient http, string url)
    {
        try
        {
            using var request = CrearPeticionGet(url);
            using var response = await http.SendAsync(request);
            var cuerpo = await response.Content.ReadAsStringAsync();

            if (!response.IsSuccessStatusCode)
            {
                return (false, $"HabilitarSensor.php respondió con error HTTP {(int)response.StatusCode}.");
            }

            var comando = JsonSerializer.Deserialize<ComandoSensor>(cuerpo, OpcionesJson);
            if (comando is null)
            {
                return (false, "HabilitarSensor.php respondió, pero el JSON no es válido.");
            }

            return (true, $"Conexión correcta. El sensor responde (operación: {comando.Operacion ?? "reintentar"}).");
        }
        catch (TaskCanceledException)
        {
            return (false, null);
        }
        catch (HttpRequestException ex)
        {
            return (false, $"No se pudo conectar a HabilitarSensor.php: {ex.Message}");
        }
        catch (JsonException)
        {
            return (false, "HabilitarSensor.php respondió, pero no devolvió JSON válido.");
        }
    }

    private static async Task<(bool Exito, string? Mensaje)> ProbarApiRestAsync(HttpClient http, string url)
    {
        try
        {
            using var request = CrearPeticionGet(url);
            using var response = await http.SendAsync(request);
            var cuerpo = await response.Content.ReadAsStringAsync();

            if (!response.IsSuccessStatusCode)
            {
                return (false, $"UsuarioRestApi.php respondió con error HTTP {(int)response.StatusCode}.");
            }

            using var doc = JsonDocument.Parse(cuerpo);
            if (doc.RootElement.ValueKind != JsonValueKind.Array)
            {
                return (false, "UsuarioRestApi.php respondió, pero el JSON no es un arreglo válido.");
            }

            return (true, "Conexión correcta vía API REST.");
        }
        catch (TaskCanceledException)
        {
            return (false, "Tiempo de espera agotado. Verifique la URL de producción y que el servidor responda.");
        }
        catch (HttpRequestException ex)
        {
            return (false, $"No se pudo conectar: {ex.Message}");
        }
        catch (JsonException)
        {
            return (false, "UsuarioRestApi.php respondió, pero no devolvió JSON válido.");
        }
    }

    private static HttpRequestMessage CrearPeticionGet(string url)
    {
        var request = new HttpRequestMessage(HttpMethod.Get, url);
        request.Headers.UserAgent.ParseAdd("PluginBiometrico/1.0");
        request.Headers.AcceptCharset.ParseAdd("UTF-8");
        return request;
    }
}
