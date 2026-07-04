using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Core.Servicios;

/// <summary>
/// Bucle principal del plugin. Consulta el servidor y delega captura/lectura.
/// Reemplaza Start.java + HabilitarLector.java.
/// </summary>
public sealed class OrquestadorSensor
{
    private readonly IClienteApiBiometrica _api;
    private readonly IProcesadorComandoSensor _procesador;
    private readonly IRegistroEventos _registro;
    private readonly ConfiguracionLocal _config;
    private readonly IEmisorEventosLocal? _eventosLocal;
    private readonly Action<string, string, string, object?>? _depuracion;

    private long _ultimaFechaConocida;

    public OrquestadorSensor(
        IClienteApiBiometrica api,
        IProcesadorComandoSensor procesador,
        IRegistroEventos registro,
        ConfiguracionLocal config,
        IEmisorEventosLocal? eventosLocal = null,
        Action<string, string, string, object?>? depuracion = null)
    {
        _api = api;
        _procesador = procesador;
        _registro = registro;
        _config = config;
        _eventosLocal = eventosLocal;
        _depuracion = depuracion;
    }

    public async Task EjecutarAsync(CancellationToken cancellationToken)
    {
        // #region agent log
        _depuracion?.Invoke("H1", "OrquestadorSensor.EjecutarAsync", "Bucle iniciado", new
        {
            ultimaFecha = _ultimaFechaConocida,
            modoRapido = _config.ModoComunicacionRapida
        });
        // #endregion

        _registro.Info("Orquestador del sensor iniciado. Esperando comandos del servidor...");

        while (!cancellationToken.IsCancellationRequested)
        {
            var huboError = false;

            try
            {
                var comando = await _api.EsperarComandoAsync(_ultimaFechaConocida, cancellationToken);
                _ultimaFechaConocida = comando.FechaCreacion;

                // #region agent log
                _depuracion?.Invoke("H5", "OrquestadorSensor.EjecutarAsync", "Comando recibido", new
                {
                    comando.Operacion,
                    comando.FechaCreacion,
                    comando.Documento
                });
                // #endregion

                _eventosLocal?.Emitir("comando", new
                {
                    comando.Operacion,
                    comando.FechaCreacion,
                    comando.Documento
                });

                switch (comando.Operacion)
                {
                    case "capturar":
                        _registro.Info("Comando recibido: CAPTURAR huella.");
                        await _procesador.ProcesarCapturaAsync(comando, cancellationToken);
                        break;

                    case "leer":
                        _registro.Info("Comando recibido: LEER huella.");
                        await _procesador.ProcesarLecturaAsync(comando, cancellationToken);
                        break;

                    case "stop":
                        _registro.Info("Comando recibido: STOP.");
                        _eventosLocal?.Emitir("stop", null);
                        break;

                    default:
                        // reintentar u otros — sin acción
                        break;
                }

                if (_config.ModoComunicacionRapida && comando.Operacion == "reintentar")
                {
                    // #region agent log
                    _depuracion?.Invoke("S6-H3", "OrquestadorSensor.EjecutarAsync", "Modo rápido: sin espera", null);
                    // #endregion

                    continue;
                }
            }
            catch (OperationCanceledException) when (cancellationToken.IsCancellationRequested)
            {
                break;
            }
            catch (Exception ex)
            {
                huboError = true;

                // #region agent log
                _depuracion?.Invoke("H3", "OrquestadorSensor.EjecutarAsync", "Error en ciclo", new { error = ex.Message });
                // #endregion

                _registro.Error(
                    ex.Message.Contains("504", StringComparison.Ordinal)
                        ? "El servidor tardó demasiado (504). Suba HabilitarSensor.php actualizado a producción."
                        : "Error consultando el servidor. Se reintentará en 1 segundo.",
                    ex);
                _eventosLocal?.Emitir("error", new { mensaje = ex.Message });
            }

            if (huboError || !_config.ModoComunicacionRapida)
            {
                await Task.Delay(1000, cancellationToken);
            }
        }

        _registro.Info("Orquestador del sensor detenido.");
    }
}
