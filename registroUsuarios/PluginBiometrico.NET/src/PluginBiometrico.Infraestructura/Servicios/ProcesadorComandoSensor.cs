using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;
using PluginBiometrico.Core.Servicios;
using PluginBiometrico.Infraestructura.Huella;

namespace PluginBiometrico.Infraestructura.Servicios;

/// <summary>
/// Ejecuta captura o lectura cuando el servidor lo solicita.
/// Sprint 3: captura. Sprint 4: verificación/lectura.
/// </summary>
public sealed class ProcesadorComandoSensor : IProcesadorComandoSensor
{
    private readonly IClienteApiBiometrica _api;
    private readonly ConfiguracionLocal _config;
    private readonly IRegistroEventos _registro;
    private readonly IPresentadorCaptura? _presentador;
    private readonly Action<string>? _notificarBandeja;
    private readonly Action<string, string, string, object?>? _depuracion;

    public ProcesadorComandoSensor(
        IClienteApiBiometrica api,
        ConfiguracionLocal config,
        IRegistroEventos registro,
        IPresentadorCaptura? presentador = null,
        Action<string>? notificarBandeja = null,
        Action<string, string, string, object?>? depuracion = null)
    {
        _api = api;
        _config = config;
        _registro = registro;
        _presentador = presentador;
        _notificarBandeja = notificarBandeja;
        _depuracion = depuracion;
    }

    public async Task ProcesarCapturaAsync(CancellationToken cancellationToken)
    {
        _notificarBandeja?.Invoke("Modo captura activado.");

        using var lector = FabricaLectorHuellas.Crear();
        var servicio = new ServicioCaptura(lector, _api, _config, _registro, _presentador, _depuracion);

        await servicio.EjecutarAsync(cancellationToken);
    }

    public async Task ProcesarLecturaAsync(CancellationToken cancellationToken)
    {
        _notificarBandeja?.Invoke("Modo lectura activado.");

        using var lector = FabricaLectorHuellas.Crear();
        var matcher = FabricaMatcherHuellas.Crear();
        var servicio = new ServicioVerificacion(
            lector,
            _api,
            matcher,
            _config,
            _registro,
            _presentador,
            _depuracion);

        await servicio.EjecutarAsync(cancellationToken);
    }
}
