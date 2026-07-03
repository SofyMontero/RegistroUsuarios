using System.Text;

namespace PluginBiometrico.App.Ayudantes;

/// <summary>
/// Genera un identificador único legible para esta estación de trabajo.
/// </summary>
public static class GeneradorIdEstacion
{
    public static string Generar()
    {
        var nombre = Sanitizar(Environment.MachineName);
        if (string.IsNullOrWhiteSpace(nombre))
        {
            nombre = "PC";
        }

        var sufijo = Guid.NewGuid().ToString("N")[..6].ToUpperInvariant();
        return $"{nombre}-{sufijo}";
    }

    private static string Sanitizar(string texto)
    {
        var resultado = new StringBuilder(texto.Length);
        foreach (var c in texto.Trim())
        {
            if (char.IsLetterOrDigit(c) || c == '-')
            {
                resultado.Append(c);
            }
            else if (char.IsWhiteSpace(c) || c == '_')
            {
                resultado.Append('-');
            }
        }

        return resultado.ToString().Trim('-');
    }
}
