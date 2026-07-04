namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>Comprueba en tiempo de ejecución que las DLL del SDK estén junto al .exe.</summary>
internal static class SdkDigitalPersona
{
    private static readonly string[] DllsRequeridas =
    [
        "DPFPShrNET.dll",
        "DPFPDevNET.dll",
        "DPFPEngNET.dll"
    ];

    public static bool EstaDisponible()
    {
#if TIENE_SDK_DPFP
        var dir = AppContext.BaseDirectory;
        foreach (var dll in DllsRequeridas)
        {
            if (!File.Exists(Path.Combine(dir, dll)))
            {
                return false;
            }
        }

        return true;
#else
        return false;
#endif
    }

    public static string ObtenerMensajeNoDisponible()
    {
#if TIENE_SDK_DPFP
        var dir = AppContext.BaseDirectory;
        var faltantes = DllsRequeridas
            .Where(dll => !File.Exists(Path.Combine(dir, dll)))
            .ToList();

        if (faltantes.Count == 0)
        {
            return "SDK Digital Persona no disponible.";
        }

        return
            "Faltan DLL del SDK junto al ejecutable (" +
            string.Join(", ", faltantes) +
            "). Ejecute publish.ps1 y reinicie el plugin.";
#else
        return
            "SDK Digital Persona no incluido en este ejecutable. " +
            "Ejecute publish.ps1 desde PluginBiometrico.NET y reinicie el plugin.";
#endif
    }
}
