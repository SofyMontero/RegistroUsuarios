using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;
using PluginBiometrico.Core.Servicios;
using PluginBiometrico.Infraestructura.Huella;

namespace PluginBiometrico.Infraestructura.Servicios;

/// <summary>
/// Ejecuta captura o lectura cuando el servidor lo solicita.
/// Sprint 3: captura. Sprint 4: verificación/lectura. Sprint 6: eventos WebSocket.
/// </summary>
public sealed class ProcesadorComandoSensor : IProcesadorComandoSensor
{
    private readonly IClienteApiBiometrica _api;
    private readonly ConfiguracionLocal _config;
    private readonly IRegistroEventos _registro;
    private readonly IPresentadorCaptura? _presentador;
    private readonly IEmisorEventosLocal? _eventosLocal;
    private readonly Action<string>? _notificarBandeja;
    private readonly Action<string, string, string, object?>? _depuracion;

    public ProcesadorComandoSensor(
        IClienteApiBiometrica api,
        ConfiguracionLocal config,
        IRegistroEventos registro,
        IPresentadorCaptura? presentador = null,
        IEmisorEventosLocal? eventosLocal = null,
        Action<string>? notificarBandeja = null,
        Action<string, string, string, object?>? depuracion = null)
    {
        _api = api;
        _config = config;
        _registro = registro;
        _presentador = presentador;
        _eventosLocal = eventosLocal;
        _notificarBandeja = notificarBandeja;
        _depuracion = depuracion;
    }

    public async Task ProcesarCapturaAsync(ComandoSensor comando, CancellationToken cancellationToken)
    {
        _notificarBandeja?.Invoke("Modo captura activado.");
        _eventosLocal?.Emitir("captura_iniciada", new { comando.Operacion });

        using var lector = FabricaLectorHuellas.Crear();
        var servicio = new ServicioCaptura(
            lector,
            _api,
            _config,
            _registro,
            _presentador,
            _eventosLocal,
            _depuracion);

        await servicio.EjecutarAsync(cancellationToken);
    }

    public async Task ProcesarLecturaAsync(ComandoSensor comando, CancellationToken cancellationToken)
    {
        _notificarBandeja?.Invoke("Modo lectura activado.");
        _eventosLocal?.Emitir("lectura_iniciada", new
        {
            comando.Operacion,
            comando.Documento,
            modo = string.IsNullOrWhiteSpace(comando.Documento) ? "1:N" : "1:1"
        });

        using var lector = FabricaLectorHuellas.Crear();
        var matcher = FabricaMatcherHuellas.Crear();
        var servicio = new ServicioVerificacion(
            lector,
            _api,
            matcher,
            _config,
            _registro,
            _presentador,
            comando.Documento,
            _eventosLocal,
            _depuracion);

        await servicio.EjecutarAsync(cancellationToken);
    }
}
