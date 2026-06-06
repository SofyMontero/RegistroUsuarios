namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Registro legible para operadores y soporte técnico.
/// </summary>
public interface IRegistroEventos
{
    void Info(string mensaje);

    void Advertencia(string mensaje);

    void Error(string mensaje, Exception? excepcion = null);
}
