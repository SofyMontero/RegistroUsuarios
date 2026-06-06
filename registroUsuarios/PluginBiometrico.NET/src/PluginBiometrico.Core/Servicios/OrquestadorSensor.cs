using PluginBiometrico.Core.Interfaces;

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
    private readonly Action<string, string, string, object?>? _depuracion;

    private long _ultimaFechaConocida;

    public OrquestadorSensor(
        IClienteApiBiometrica api,
        IProcesadorComandoSensor procesador,
        IRegistroEventos registro,
        Action<string, string, string, object?>? depuracion = null)
    {
        _api = api;
        _procesador = procesador;
        _registro = registro;
        _depuracion = depuracion;
    }

    public async Task EjecutarAsync(CancellationToken cancellationToken)
    {
        // #region agent log
        _depuracion?.Invoke("H1", "OrquestadorSensor.EjecutarAsync", "Bucle iniciado", new { ultimaFecha = _ultimaFechaConocida });
        // #endregion

        _registro.Info("Orquestador del sensor iniciado. Esperando comandos del servidor...");

        while (!cancellationToken.IsCancellationRequested)
        {
            try
            {
                var comando = await _api.EsperarComandoAsync(_ultimaFechaConocida, cancellationToken);
                _ultimaFechaConocida = comando.FechaCreacion;

                // #region agent log
                _depuracion?.Invoke("H5", "OrquestadorSensor.EjecutarAsync", "Comando recibido", new
                {
                    comando.Operacion,
                    comando.FechaCreacion
                });
                // #endregion

                switch (comando.Operacion)
                {
                    case "capturar":
                        _registro.Info("Comando recibido: CAPTURAR huella.");
                        await _procesador.ProcesarCapturaAsync(cancellationToken);
                        break;

                    case "leer":
                        _registro.Info("Comando recibido: LEER huella.");
                        await _procesador.ProcesarLecturaAsync(cancellationToken);
                        break;

                    case "stop":
                        _registro.Info("Comando recibido: STOP.");
                        break;

                    default:
                        // reintentar u otros — sin acción
                        break;
                }
            }
            catch (OperationCanceledException) when (cancellationToken.IsCancellationRequested)
            {
                break;
            }
            catch (Exception ex)
            {
                // #region agent log
                _depuracion?.Invoke("H3", "OrquestadorSensor.EjecutarAsync", "Error en ciclo", new { error = ex.Message });
                // #endregion

                _registro.Error("Error consultando el servidor. Se reintentará en 1 segundo.", ex);
            }

            await Task.Delay(1000, cancellationToken);
        }

        _registro.Info("Orquestador del sensor detenido.");
    }
}
