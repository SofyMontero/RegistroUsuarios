namespace PluginBiometrico.Core.Modelos;

/// <summary>Resultado de identificar una huella contra la base de datos.</summary>
public sealed class ResultadoVerificacion
{
    public bool Encontrado { get; init; }

    public string Mensaje { get; init; } = "El usuario no existe";

    public string Documento { get; init; } = "----";

    public string Nombre { get; init; } = "------";

    public string Dedo { get; init; } = string.Empty;
}
