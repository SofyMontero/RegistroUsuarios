namespace PluginBiometrico.App.Ayudantes;

/// <summary>
/// Construye las URLs del plugin a partir de la URL base del servidor web.
/// </summary>
public static class ConstructorUrlsServidor
{
    public static (string UrlSensor, string UrlApi) DesdeUrlBase(string urlBase)
    {
        var baseNorm = urlBase.Trim().TrimEnd('/');

        if (baseNorm.EndsWith("/Model", StringComparison.OrdinalIgnoreCase))
        {
            baseNorm = baseNorm[..^6].TrimEnd('/');
        }

        if (baseNorm.EndsWith(".php", StringComparison.OrdinalIgnoreCase))
        {
            var indice = baseNorm.LastIndexOf('/');
            baseNorm = indice > 0 ? baseNorm[..indice] : baseNorm;
        }

        return (
            $"{baseNorm}/Model/HabilitarSensor.php",
            $"{baseNorm}/Model/UsuarioRestApi.php");
    }

    public static bool EsUrlValida(string url)
    {
        return Uri.TryCreate(url.Trim(), UriKind.Absolute, out var uri)
            && (uri.Scheme == Uri.UriSchemeHttp || uri.Scheme == Uri.UriSchemeHttps);
    }
}
