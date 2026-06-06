using PluginBiometrico.Core.Interfaces;

namespace PluginBiometrico.Infraestructura.Logging;

/// <summary>
/// Escribe eventos en un archivo de texto legible para humanos.
/// Rota automáticamente cuando supera 1 MB (mantiene 3 respaldos).
/// </summary>
public sealed class RegistroArchivo : IRegistroEventos
{
    private const long MaximoBytes = 1_000_000;
    private const int MaximoRespaldos = 3;

    private readonly string _rutaLog;
    private readonly object _bloqueo = new();

    public RegistroArchivo()
    {
        var carpeta = ObtenerCarpetaLog();
        Directory.CreateDirectory(carpeta);
        _rutaLog = Path.Combine(carpeta, "plugin.log");
    }

    public static string ObtenerCarpetaLog() =>
        Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "PluginBiometrico");

    public static string ObtenerRutaLog() =>
        Path.Combine(ObtenerCarpetaLog(), "plugin.log");

    public void Info(string mensaje) => Escribir("INFO", mensaje);

    public void Advertencia(string mensaje) => Escribir("WARN", mensaje);

    public void Error(string mensaje, Exception? excepcion = null)
    {
        var detalle = excepcion is null ? mensaje : $"{mensaje} | {excepcion.Message}";
        Escribir("ERROR", detalle);
    }

    private void Escribir(string nivel, string mensaje)
    {
        var linea = $"{DateTime.Now:yyyy-MM-dd HH:mm:ss} [{nivel}] {mensaje}";

        lock (_bloqueo)
        {
            RotarSiEsNecesario();
            File.AppendAllText(_rutaLog, linea + Environment.NewLine);
        }
    }

    private void RotarSiEsNecesario()
    {
        if (!File.Exists(_rutaLog))
        {
            return;
        }

        var info = new FileInfo(_rutaLog);
        if (info.Length < MaximoBytes)
        {
            return;
        }

        for (var i = MaximoRespaldos - 1; i >= 1; i--)
        {
            var origen = $"{_rutaLog}.{i}";
            var destino = $"{_rutaLog}.{i + 1}";

            if (File.Exists(destino))
            {
                File.Delete(destino);
            }

            if (File.Exists(origen))
            {
                File.Move(origen, destino);
            }
        }

        if (File.Exists($"{_rutaLog}.1"))
        {
            File.Delete($"{_rutaLog}.1");
        }

        File.Move(_rutaLog, $"{_rutaLog}.1");
    }
}
