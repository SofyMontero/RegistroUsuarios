using System.Text.Json.Serialization;

namespace PluginBiometrico.Core.Modelos;

/// <summary>
/// Cada registro devuelto por GET UsuarioRestApi.php?token=&desde=&hasta=
/// </summary>
public sealed class PlantillaUsuario
{
    [JsonPropertyName("count")]
    public int TotalUsuarios { get; set; }

    [JsonPropertyName("documento")]
    public string Documento { get; set; } = string.Empty;

    [JsonPropertyName("nombre_completo")]
    public string NombreCompleto { get; set; } = string.Empty;

    [JsonPropertyName("nombre_dedo")]
    public string NombreDedo { get; set; } = string.Empty;

    [JsonPropertyName("huella")]
    public string HuellaBase64 { get; set; } = string.Empty;

    [JsonPropertyName("imgHuella")]
    public string ImagenHuellaBase64 { get; set; } = string.Empty;

    [JsonPropertyName("foto_usu")]
    public string FotoUsuario { get; set; } = string.Empty;
}
