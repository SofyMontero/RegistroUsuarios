using System.IO;
using System.Text.Json;

namespace PluginBiometrico.App.Ayudantes;

/// <summary>
/// Lee appsettings.json para pre-llenar la ventana de configuración la primera vez.
/// </summary>
public static class CargadorValoresPorDefecto
{
    public static (string UrlSensor, string UrlApi, string Navegador) Cargar()
    {
        var ruta = Path.Combine(AppContext.BaseDirectory, "appsettings.json");

        if (!File.Exists(ruta))
        {
            return (string.Empty, string.Empty, "Chrome");
        }

        try
        {
            using var stream = File.OpenRead(ruta);
            using var document = JsonDocument.Parse(stream);

            if (!document.RootElement.TryGetProperty("ValoresPorDefecto", out var valores))
            {
                return (string.Empty, string.Empty, "Chrome");
            }

            var urlSensor = valores.TryGetProperty("UrlHabilitarSensor", out var s) ? s.GetString() ?? "" : "";
            var urlApi = valores.TryGetProperty("UrlApiRest", out var a) ? a.GetString() ?? "" : "";
            var navegador = valores.TryGetProperty("Navegador", out var n) ? n.GetString() ?? "Chrome" : "Chrome";

            return (urlSensor, urlApi, navegador);
        }
        catch (JsonException)
        {
            return (string.Empty, string.Empty, "Chrome");
        }
    }
}
