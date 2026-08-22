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
    private CancellationTokenSource? _cancelacionOperacion;
    private Task? _operacionActiva;
    private string? _tipoOperacionActiva;
    private string? _ultimaOperacionQueUsoLector;

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
                        if (await CambiarOperacionAsync(
                            "capturar",
                            comando.FechaCreacion,
                            token => _procesador.ProcesarCapturaAsync(comando, token),
                            cancellationToken))
                        {
                            _registro.Info("Comando recibido: CAPTURAR huella.");
                        }
                        else
                        {
                            await Task.Delay(300, cancellationToken);
                        }
                        break;

                    case "leer":
                        if (await CambiarOperacionAsync(
                            "leer",
                            comando.FechaCreacion,
                            token => _procesador.ProcesarLecturaAsync(comando, token),
                            cancellationToken))
                        {
                            _registro.Info("Comando recibido: LEER huella.");
                        }
                        else
                        {
                            await Task.Delay(300, cancellationToken);
                        }
                        break;

                    case "stop":
                        _registro.Info("Comando recibido: STOP.");
                        CancelarOperacionActiva();
                        _eventosLocal?.Emitir("stop", null);
                        break;

                    default:
                        // reintentar u otros — sin acción
                        break;
                }

                if (_config.ModoComunicacionRapida && comando.Operacion == "reintentar")
                {
                    // #region agent log
                    _depuracion?.Invoke("S6-H3", "OrquestadorSensor.EjecutarAsync", "Modo rápido: espera corta", null);
                    // #endregion

                    await Task.Delay(300, cancellationToken);
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

        CancelarOperacionActiva();
        if (_operacionActiva is not null)
        {
            try
            {
                await _operacionActiva;
            }
            catch (OperationCanceledException)
            {
                // Cierre normal del servicio.
            }
            catch (Exception ex)
            {
                _registro.Error("La operación biométrica terminó con error.", ex);
            }
        }

        _registro.Info("Orquestador del sensor detenido.");
    }

    private async Task<bool> CambiarOperacionAsync(
        string tipo,
        long fechaComando,
        Func<CancellationToken, Task> iniciar,
        CancellationToken cancellationToken)
    {
        // Los PUT de progreso también modifican fecha_creacion en huellas_temp.
        // Mientras el enrolamiento/lectura siga activo, ese cambio no representa
        // una orden nueva: reiniciarlo aquí perdería las muestras ya acumuladas.
        if (_tipoOperacionActiva == tipo
            && _operacionActiva is { IsCompleted: false })
        {
            return false;
        }

        var operacionAnterior = _tipoOperacionActiva ?? _ultimaOperacionQueUsoLector;

        CancelarOperacionActiva();
        if (_operacionActiva is not null)
        {
            await _operacionActiva;
        }

        // Tras enrolar, el ActiveX tarda en soltar el U.are.U. Si arrancamos
        // lectura enseguida el ingreso no reconoce al usuario hasta reiniciar.
        if (operacionAnterior is "capturar" or "leer")
        {
            await Task.Delay(500, cancellationToken);
        }

        _cancelacionOperacion?.Dispose();
        _cancelacionOperacion = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
        _tipoOperacionActiva = tipo;
        _operacionActiva = EjecutarOperacionAsync(tipo, iniciar, _cancelacionOperacion.Token);
        return true;
    }

    private async Task EjecutarOperacionAsync(
        string tipo,
        Func<CancellationToken, Task> iniciar,
        CancellationToken cancellationToken)
    {
        try
        {
            await iniciar(cancellationToken);
        }
        catch (OperationCanceledException) when (cancellationToken.IsCancellationRequested)
        {
            _registro.Info($"Operación {tipo} cancelada para cambiar de modo.");
        }
        catch (Exception ex)
        {
            _registro.Error($"Error durante la operación biométrica {tipo}.", ex);
            _eventosLocal?.Emitir("error", new { mensaje = ex.Message });
        }
        finally
        {
            if (tipo is "capturar" or "leer")
            {
                _ultimaOperacionQueUsoLector = tipo;
            }
        }
    }

    private void CancelarOperacionActiva()
    {
        if (_cancelacionOperacion is not null && !_cancelacionOperacion.IsCancellationRequested)
        {
            _cancelacionOperacion.Cancel();
        }

        _tipoOperacionActiva = null;
    }
}
