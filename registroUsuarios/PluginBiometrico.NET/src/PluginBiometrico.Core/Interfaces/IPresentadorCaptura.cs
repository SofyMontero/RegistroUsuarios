namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Muestra la ventana de estado durante la captura (UI mínima).
/// </summary>
public interface IPresentadorCaptura
{
    Task AbrirAsync();

    void Actualizar(string mensaje, string estadoPlantilla);

    Task CerrarAsync();
}
