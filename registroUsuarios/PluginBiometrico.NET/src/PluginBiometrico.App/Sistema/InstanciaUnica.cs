namespace PluginBiometrico.App.Sistema;

/// <summary>Evita que se abran dos copias del plugin al mismo tiempo.</summary>
public sealed class InstanciaUnica : IDisposable
{
    private const string NombreMutex = "Global\\PluginBiometrico.Singleton";
    private readonly Mutex? _mutex;
    private readonly bool _esPropietario;

    public InstanciaUnica()
    {
        _mutex = new Mutex(initiallyOwned: true, name: NombreMutex, createdNew: out _esPropietario);
    }

    public bool EsInstanciaPrincipal => _esPropietario;

    public void Dispose()
    {
        if (_esPropietario && _mutex is not null)
        {
            _mutex.ReleaseMutex();
        }

        _mutex?.Dispose();
    }
}
