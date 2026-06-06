using System.IO;
using Microsoft.Win32;

namespace PluginBiometrico.App.Sistema;

/// <summary>
/// Configura el inicio automático con Windows (registro Run).
/// Reemplaza CrearServicio.java del plugin Java.
/// </summary>
public static class InicioAutomaticoWindows
{
    private const string ClaveRun = @"Software\Microsoft\Windows\CurrentVersion\Run";
    private const string NombreAplicacion = "PluginBiometrico";

    public static bool EstaConfigurado()
    {
        using var clave = Registry.CurrentUser.OpenSubKey(ClaveRun, writable: false);
        var valor = clave?.GetValue(NombreAplicacion) as string;
        return !string.IsNullOrWhiteSpace(valor);
    }

    public static ResultadoInicioAutomatico Activar()
    {
        try
        {
            var rutaEjecutable = ObtenerRutaEjecutable();

            using var clave = Registry.CurrentUser.OpenSubKey(ClaveRun, writable: true);
            if (clave is null)
            {
                return new ResultadoInicioAutomatico(false, "No se pudo abrir el registro de Windows.");
            }

            clave.SetValue(NombreAplicacion, $"\"{rutaEjecutable}\"");

            // #region agent log
            Infraestructura.Logging.AgenteDiagnostico.Registrar(
                "S5-H1", "InicioAutomaticoWindows.Activar", "Auto-inicio activado",
                new { rutaEjecutable }, "sprint5");
            // #endregion

            return new ResultadoInicioAutomatico(
                true,
                "La aplicación ahora iniciará con el sistema operativo.");
        }
        catch (Exception ex)
        {
            return new ResultadoInicioAutomatico(false, ex.Message);
        }
    }

    public static ResultadoInicioAutomatico Desactivar()
    {
        try
        {
            using var clave = Registry.CurrentUser.OpenSubKey(ClaveRun, writable: true);
            if (clave is null)
            {
                return new ResultadoInicioAutomatico(false, "No se pudo abrir el registro de Windows.");
            }

            if (clave.GetValue(NombreAplicacion) is null)
            {
                return new ResultadoInicioAutomatico(
                    false,
                    "No hay registro de auto inicio.");
            }

            clave.DeleteValue(NombreAplicacion, throwOnMissingValue: false);

            // #region agent log
            Infraestructura.Logging.AgenteDiagnostico.Registrar(
                "S5-H2", "InicioAutomaticoWindows.Desactivar", "Auto-inicio desactivado", null, "sprint5");
            // #endregion

            return new ResultadoInicioAutomatico(
                true,
                "La aplicación ya no iniciará con el sistema operativo.");
        }
        catch (Exception ex)
        {
            return new ResultadoInicioAutomatico(false, ex.Message);
        }
    }

    private static string ObtenerRutaEjecutable()
    {
        var ruta = Environment.ProcessPath;
        if (!string.IsNullOrWhiteSpace(ruta) && File.Exists(ruta))
        {
            return Path.GetFullPath(ruta);
        }

        return Path.GetFullPath(Path.Combine(AppContext.BaseDirectory, "PluginBiometrico.exe"));
    }
}

public readonly record struct ResultadoInicioAutomatico(bool Exito, string Mensaje);
