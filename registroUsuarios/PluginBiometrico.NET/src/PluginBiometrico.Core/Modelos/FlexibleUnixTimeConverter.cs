using System.Text.Json;
using System.Text.Json.Serialization;

namespace PluginBiometrico.Core.Modelos;

/// <summary>
/// Acepta enteros, cadenas numéricas, null o false en campos de timestamp del JSON legacy de PHP.
/// </summary>
public sealed class FlexibleUnixTimeConverter : JsonConverter<long>
{
    public override long Read(ref Utf8JsonReader reader, Type typeToConvert, JsonSerializerOptions options)
    {
        return reader.TokenType switch
        {
            JsonTokenType.Number => reader.GetInt64(),
            JsonTokenType.String => long.TryParse(reader.GetString(), out var n) ? n : 0,
            JsonTokenType.Null or JsonTokenType.False => 0,
            _ => throw new JsonException($"No se pudo leer timestamp desde {reader.TokenType}.")
        };
    }

    public override void Write(Utf8JsonWriter writer, long value, JsonSerializerOptions options)
    {
        writer.WriteNumberValue(value);
    }
}
