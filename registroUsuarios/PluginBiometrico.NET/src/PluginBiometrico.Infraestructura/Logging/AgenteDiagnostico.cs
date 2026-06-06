using System.Text.Json;

namespace PluginBiometrico.Infraestructura.Logging;

/// <summary>
/// Registro NDJSON para depuración de agente (Sprint 2).
/// </summary>
public static class AgenteDiagnostico
{
    private const string SessionId = "b6010c";
    private static readonly object Bloqueo = new();

    public static void Registrar(string hypothesisId, string location, string message, object? data = null, string runId = "sprint2")
    {
        var ruta = ResolverRutaLog();

        var entrada = new
        {
            sessionId = SessionId,
            hypothesisId,
            location,
            message,
            data,
            timestamp = DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(),
            runId
        };

        var linea = JsonSerializer.Serialize(entrada);

        lock (Bloqueo)
        {
            File.AppendAllText(ruta, linea + Environment.NewLine);
        }
    }

    private static string ResolverRutaLog()
    {
        var directorio = AppContext.BaseDirectory;

        for (var i = 0; i < 12; i++)
        {
            var candidato = Path.Combine(directorio, "debug-b6010c.log");
            if (Directory.Exists(Path.Combine(directorio, "registroUsuarios")))
            {
                return candidato;
            }

            var padre = Directory.GetParent(directorio)?.FullName;
            if (padre is null || padre == directorio)
            {
                break;
            }

            directorio = padre;
        }

        var carpetaLocal = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "PluginBiometrico");

        Directory.CreateDirectory(carpetaLocal);
        return Path.Combine(carpetaLocal, "debug-b6010c.log");
    }
}
