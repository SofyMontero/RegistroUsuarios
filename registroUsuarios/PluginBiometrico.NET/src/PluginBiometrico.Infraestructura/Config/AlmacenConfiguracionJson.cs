using System.Text.Json;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Infraestructura.Config;

/// <summary>
/// Guarda la configuración en un archivo JSON legible por humanos.
/// Ubicación: %LocalAppData%\PluginBiometrico\config.json
/// </summary>
public sealed class AlmacenConfiguracionJson : IAlmacenConfiguracion
{
    private static readonly JsonSerializerOptions OpcionesJson = new()
    {
        WriteIndented = true,
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase
    };

    private readonly string _rutaArchivo;

    public AlmacenConfiguracionJson()
    {
        var carpeta = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "PluginBiometrico");

        Directory.CreateDirectory(carpeta);
        _rutaArchivo = Path.Combine(carpeta, "config.json");
    }

    public string ObtenerRutaArchivo() => _rutaArchivo;

    public bool ExisteConfiguracion()
    {
        var config = Cargar();
        return config is not null && EsConfiguracionValida(config);
    }

    public ConfiguracionLocal? Cargar()
    {
        if (!File.Exists(_rutaArchivo))
        {
            return null;
        }

        try
        {
            var json = File.ReadAllText(_rutaArchivo);
            return JsonSerializer.Deserialize<ConfiguracionLocal>(json, OpcionesJson);
        }
        catch (JsonException)
        {
            return null;
        }
    }

    public void Guardar(ConfiguracionLocal configuracion)
    {
        if (!EsConfiguracionValida(configuracion))
        {
            throw new InvalidOperationException(
                "La configuración está incompleta. Revise Id único, URL del sensor y URL de la API.");
        }

        var json = JsonSerializer.Serialize(configuracion, OpcionesJson);
        File.WriteAllText(_rutaArchivo, json);
    }

    private static bool EsConfiguracionValida(ConfiguracionLocal config)
    {
        return !string.IsNullOrWhiteSpace(config.IdUnicoPc)
            && !string.IsNullOrWhiteSpace(config.UrlHabilitarSensor)
            && !string.IsNullOrWhiteSpace(config.UrlApiRest);
    }
}
