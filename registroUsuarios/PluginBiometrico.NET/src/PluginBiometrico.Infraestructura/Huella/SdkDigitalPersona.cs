namespace PluginBiometrico.Infraestructura.Huella;

/// <summary>Comprueba las DLL y el registro COM del SDK ActiveX de DigitalPersona.</summary>
internal static class SdkDigitalPersona
{
    private static readonly string[] DllsRequeridas =
    [
        "DPFPShrXLib.dll",
        "DPFPDevXLib.dll",
        "DPFPEngXLib.dll"
    ];

    public static bool EstaDisponible()
    {
#if TIENE_SDK_DPFP_ACTIVEX
        var dir = AppContext.BaseDirectory;
        if (DllsRequeridas.Any(dll => !File.Exists(Path.Combine(dir, dll))))
        {
            return false;
        }

        return Type.GetTypeFromProgID("DPFPDevX.DPFPCapture") is not null
            || Type.GetTypeFromProgID("DPFPDevX.DPFPCapture.1") is not null;
#else
        return false;
#endif
    }

    public static string ObtenerMensajeNoDisponible()
    {
#if TIENE_SDK_DPFP_ACTIVEX
        var dir = AppContext.BaseDirectory;
        var faltantes = DllsRequeridas
            .Where(dll => !File.Exists(Path.Combine(dir, dll)))
            .ToList();

        if (faltantes.Count == 0)
        {
            return
                "DigitalPersona ActiveX no está registrado para aplicaciones x86. " +
                "Ejecute el registrador ActiveX como administrador.";
        }

        return
            "Faltan DLL ActiveX junto al ejecutable (" +
            string.Join(", ", faltantes) +
            "). Compile nuevamente el plugin.";
#else
        return "SDK DigitalPersona ActiveX no incluido en este ejecutable.";
#endif
    }
}
