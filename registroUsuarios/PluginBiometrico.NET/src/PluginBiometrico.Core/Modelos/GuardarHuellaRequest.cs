using System.Text.Json.Serialization;

namespace PluginBiometrico.Core.Modelos;

/// <summary>
/// POST a UsuarioRestApi.php cuando la plantilla biométrica está lista.
/// </summary>
public sealed class GuardarHuellaRequest
{
    [JsonPropertyName("serial")]
    public string SerialPc { get; set; } = string.Empty;

    [JsonPropertyName("huella")]
    public string HuellaBase64 { get; set; } = string.Empty;

    [JsonPropertyName("imageHuella")]
    public string ImagenHuellaBase64 { get; set; } = string.Empty;

    [JsonPropertyName("texto")]
    public string Mensaje { get; set; } = string.Empty;

    [JsonPropertyName("statusPlantilla")]
    public string EstadoPlantilla { get; set; } = string.Empty;

    [JsonPropertyName("foto_usu")]
    public string FotoUsuario { get; set; } = string.Empty;
}
