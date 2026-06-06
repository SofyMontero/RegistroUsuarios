using System.Text.Json.Serialization;

namespace PluginBiometrico.Core.Modelos;

/// <summary>
/// PUT a UsuarioRestApi.php para progreso de captura o resultado de verificación.
/// </summary>
public sealed class ActualizarHuellaRequest
{
    [JsonPropertyName("serial")]
    public string SerialPc { get; set; } = string.Empty;

    [JsonPropertyName("imageHuella")]
    public string ImagenHuellaBase64 { get; set; } = string.Empty;

    [JsonPropertyName("texto")]
    public string Mensaje { get; set; } = string.Empty;

    [JsonPropertyName("statusPlantilla")]
    public string EstadoPlantilla { get; set; } = string.Empty;

    [JsonPropertyName("option")]
    public string? Opcion { get; set; }

    [JsonPropertyName("documento")]
    public string Documento { get; set; } = string.Empty;

    [JsonPropertyName("nombre")]
    public string Nombre { get; set; } = string.Empty;

    [JsonPropertyName("dedo")]
    public string Dedo { get; set; } = string.Empty;

    [JsonPropertyName("foto_usu")]
    public string FotoUsuario { get; set; } = string.Empty;
}
