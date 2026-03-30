using System.Text.Json.Serialization;

namespace PluginBiometricoV4.Api;

public sealed class HuellaListaItem
{
    [JsonPropertyName("count")]
    public double Count { get; set; }

    [JsonPropertyName("documento")]
    public string Documento { get; set; } = "";

    [JsonPropertyName("nombre_completo")]
    public string NombreCompleto { get; set; } = "";

    [JsonPropertyName("nombre_dedo")]
    public string NombreDedo { get; set; } = "";

    [JsonPropertyName("huella")]
    public string Huella { get; set; } = "";

    [JsonPropertyName("imgHuella")]
    public string? ImgHuella { get; set; }

    [JsonPropertyName("foto_usu")]
    public string? FotoUsu { get; set; }
}
