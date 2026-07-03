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

        try
        {
            using var http = new HttpClient { Timeout = TimeSpan.FromSeconds(12) };
            var url = $"{config.UrlHabilitarSensor.Trim()}" +
                      $"?timestamp=0" +
                      $"&token={Uri.EscapeDataString(config.IdUnicoPc.Trim())}" +
                      $"&_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";

            using var request = new HttpRequestMessage(HttpMethod.Get, url);
            request.Headers.UserAgent.ParseAdd("PluginBiometrico/1.0");
            request.Headers.AcceptCharset.ParseAdd("UTF-8");

            using var response = await http.SendAsync(request);
            var cuerpo = await response.Content.ReadAsStringAsync();

            if (!response.IsSuccessStatusCode)
            {
                return (false, $"El servidor respondió con error HTTP {(int)response.StatusCode}.");
            }

            var comando = JsonSerializer.Deserialize<ComandoSensor>(cuerpo, OpcionesJson);
            if (comando is null)
            {
                return (false, "El servidor respondió, pero el JSON no es válido. Revise HabilitarSensor.php.");
            }

            return (true, $"Conexión correcta. El sensor responde (operación: {comando.Operacion ?? "reintentar"}).");
        }
        catch (TaskCanceledException)
        {
            return (false, "Tiempo de espera agotado. Verifique la URL y que Apache/PHP estén activos.");
        }
        catch (HttpRequestException ex)
        {
            return (false, $"No se pudo conectar: {ex.Message}");
        }
        catch (JsonException)
        {
            return (false, "El servidor respondió, pero no devolvió JSON válido.");
        }
    }
}
