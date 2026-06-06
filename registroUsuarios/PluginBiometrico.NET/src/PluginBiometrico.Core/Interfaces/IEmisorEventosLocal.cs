namespace PluginBiometrico.Core.Interfaces;

/// <summary>
/// Emite eventos en tiempo real hacia la web vía WebSocket local (Sprint 6).
/// </summary>
public interface IEmisorEventosLocal : IDisposable
{
    int Puerto { get; }

    bool EstaActivo { get; }

    Task IniciarAsync(CancellationToken cancellationToken);

    void Detener();

    void Emitir(string tipo, object? datos = null);
}
