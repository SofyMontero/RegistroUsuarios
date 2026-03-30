using Microsoft.Win32;

namespace PluginBiometricoV4.Config;

/// <summary>
/// Inicio automático ligero (HKCU Run), alternativa al servicio Windows del plugin Java.
/// </summary>
public static class WindowsStartup
{
    private const string RunSubKey = @"Software\Microsoft\Windows\CurrentVersion\Run";
    private const string ValueName = "PluginBiometricoV4";

    public static bool IsEnabled()
    {
        using var key = Registry.CurrentUser.OpenSubKey(RunSubKey, false);
        var v = key?.GetValue(ValueName) as string;
        return !string.IsNullOrWhiteSpace(v);
    }

    public static void SetEnabled(bool enabled, string executablePath)
    {
        using var key = Registry.CurrentUser.OpenSubKey(RunSubKey, true)
                        ?? throw new InvalidOperationException("No se pudo abrir el registro de inicio.");

        if (enabled)
        {
            // Siempre entrecomillar: sin comillas, "C:\Users\Sofia Montero\...\app.exe" se parte en el espacio
            // y Windows intenta abrir "C:\Users\Sofia" → diálogo "abrir con" para un archivo "Sofia".
            var quoted = $"\"{executablePath.Trim()}\"";
            key.SetValue(ValueName, quoted);
        }
        else
        {
            try
            {
                key.DeleteValue(ValueName, false);
            }
            catch (ArgumentException)
            {
                // ya no existía
            }
        }
    }
}
