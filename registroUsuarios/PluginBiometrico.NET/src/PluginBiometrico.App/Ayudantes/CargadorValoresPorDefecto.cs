using System.IO;
using System.Text.Json;

namespace PluginBiometrico.App.Ayudantes;

/// <summary>
/// Lee appsettings.json para pre-llenar la ventana de configuración la primera vez.
/// </summary>
public static class CargadorValoresPorDefecto
{
    public static (string UrlBase, string UrlSensor, string UrlApi, string Navegador) Cargar()
    {
        const string valorPorDefecto = "Chrome";
        var ruta = Path.Combine(AppContext.BaseDirectory, "appsettings.json");

        if (!File.Exists(ruta))
        {
            return (string.Empty, string.Empty, string.Empty, valorPorDefecto);
        }

        try
        {
            using var stream = File.OpenRead(ruta);
            using var document = JsonDocument.Parse(stream);

            if (!document.RootElement.TryGetProperty("ValoresPorDefecto", out var valores))
            {
                return (string.Empty, string.Empty, string.Empty, valorPorDefecto);
            }

            var urlBase = valores.TryGetProperty("UrlBase", out var b) ? b.GetString() ?? "" : "";
            var urlSensor = valores.TryGetProperty("UrlHabilitarSensor", out var s) ? s.GetString() ?? "" : "";
            var urlApi = valores.TryGetProperty("UrlApiRest", out var a) ? a.GetString() ?? "" : "";
            var navegador = valores.TryGetProperty("Navegador", out var n) ? n.GetString() ?? valorPorDefecto : valorPorDefecto;

            if (string.IsNullOrWhiteSpace(urlBase)
                && ConstructorUrlsServidor.EsUrlValida(urlSensor))
            {
                var indice = urlSensor.IndexOf("/Model/", StringComparison.OrdinalIgnoreCase);
                if (indice > 0)
                {
                    urlBase = urlSensor[..indice];
                }
            }

            return (urlBase, urlSensor, urlApi, navegador);
        }
        catch (JsonException)
        {
            return (string.Empty, string.Empty, string.Empty, valorPorDefecto);
        }
    }
}
