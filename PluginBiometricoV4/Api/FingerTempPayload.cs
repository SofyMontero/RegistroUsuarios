using System.Text.Json.Serialization;

namespace PluginBiometricoV4.Api;

/// <summary>
/// Cuerpo JSON compatible con Gson de <c>finger_temp.java</c> y <c>UsuarioRestApi.php</c>.
/// </summary>
public sealed class FingerTempPayload
{
    [JsonPropertyName("serial")]
    public string Serial { get; set; } = "";

    [JsonPropertyName("huella")]
    public string? Huella { get; set; }

    [JsonPropertyName("imageHuella")]
    public string ImageHuella { get; set; } = "";

    [JsonPropertyName("texto")]
    public string Texto { get; set; } = "";

    [JsonPropertyName("statusPlantilla")]
    public string StatusPlantilla { get; set; } = "";

    [JsonPropertyName("documento")]
    public string Documento { get; set; } = "";

    [JsonPropertyName("nombre")]
    public string Nombre { get; set; } = "";

    [JsonPropertyName("dedo")]
    public string Dedo { get; set; } = "";

    [JsonPropertyName("option")]
    public string? Option { get; set; }

    [JsonPropertyName("foto_usu")]
    public string FotoUsu { get; set; } = "";
}
