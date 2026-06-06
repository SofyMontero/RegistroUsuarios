using System.Net.Http;
using System.Windows.Threading;
using PluginBiometrico.App.Presentadores;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Servicios;
using PluginBiometrico.Infraestructura.Api;
using PluginBiometrico.Infraestructura.Logging;
using PluginBiometrico.Infraestructura.Servicios;

namespace PluginBiometrico.App.Servicios;

/// <summary>
/// Arranca y detiene el bucle que escucha comandos del servidor PHP.
/// </summary>
public sealed class ServicioSensorEnSegundoPlano : IDisposable
{
    private readonly IAlmacenConfiguracion _almacen;
    private readonly IRegistroEventos _registro;
    private readonly Action<string>? _notificarBandeja;
    private readonly Dispatcher? _dispatcherUi;

    private CancellationTokenSource? _cts;
    private Task? _tarea;

    public ServicioSensorEnSegundoPlano(
        IAlmacenConfiguracion almacen,
        IRegistroEventos registro,
        Action<string>? notificarBandeja = null,
        Dispatcher? dispatcherUi = null)
    {
        _almacen = almacen;
        _registro = registro;
        _notificarBandeja = notificarBandeja;
        _dispatcherUi = dispatcherUi;
    }

    public void Iniciar()
    {
        if (_tarea is not null)
        {
            return;
        }

        var config = _almacen.Cargar();
        if (config is null)
        {
            _registro.Advertencia("No hay configuración. El sensor no se iniciará.");
            return;
        }

        // #region agent log
        AgenteDiagnostico.Registrar("H1", "ServicioSensorEnSegundoPlano.Iniciar", "Servicio arrancando", new
        {
            tieneUrlSensor = !string.IsNullOrWhiteSpace(config.UrlHabilitarSensor),
            tieneUrlApi = !string.IsNullOrWhiteSpace(config.UrlApiRest)
        }, "sprint3");
        // #endregion

        _cts = new CancellationTokenSource();

        var http = new HttpClient
        {
            Timeout = TimeSpan.FromMinutes(3)
        };

        Action<string, string, string, object?> depuracion =
            (h, l, m, d) => AgenteDiagnostico.Registrar(h, l, m, d, "sprint3");

        var api = new ClienteApiBiometrica(http, config, depuracion);

        IPresentadorCaptura? presentador = _dispatcherUi is not null
            ? new PresentadorCapturaVentana(_dispatcherUi)
            : null;

        IProcesadorComandoSensor procesador = new ProcesadorComandoSensor(
            api,
            config,
            _registro,
            presentador,
            _notificarBandeja,
            depuracion);

        var orquestador = new OrquestadorSensor(api, procesador, _registro, depuracion);

        _tarea = Task.Run(() => orquestador.EjecutarAsync(_cts.Token));
        _registro.Info("Servicio de escucha del sensor iniciado en segundo plano.");
    }

    public void Detener()
    {
        _cts?.Cancel();

        try
        {
            _tarea?.Wait(TimeSpan.FromSeconds(5));
        }
        catch (AggregateException)
        {
            // cancelación esperada
        }

        _cts?.Dispose();
        _cts = null;
        _tarea = null;
    }

    public void Dispose() => Detener();
}
