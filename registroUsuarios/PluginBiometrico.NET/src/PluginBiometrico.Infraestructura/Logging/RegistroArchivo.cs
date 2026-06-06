using PluginBiometrico.Core.Interfaces;

namespace PluginBiometrico.Infraestructura.Logging;

/// <summary>
/// Escribe eventos en un archivo de texto legible para humanos.
/// Ubicación: %LocalAppData%\PluginBiometrico\plugin.log
/// </summary>
public sealed class RegistroArchivo : IRegistroEventos
{
    private readonly string _rutaLog;
    private readonly object _bloqueo = new();

    public RegistroArchivo()
    {
        var carpeta = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "PluginBiometrico");

        Directory.CreateDirectory(carpeta);
        _rutaLog = Path.Combine(carpeta, "plugin.log");
    }

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
            File.AppendAllText(_rutaLog, linea + Environment.NewLine);
        }
    }
}
