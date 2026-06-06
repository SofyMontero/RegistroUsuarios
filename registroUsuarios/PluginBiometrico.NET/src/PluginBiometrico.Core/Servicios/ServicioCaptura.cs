using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Core.Servicios;

/// <summary>
/// Orquesta el enrollment de huella y envía progreso al servidor PHP.
/// Reemplaza la lógica de CapturarHuella.java.
/// </summary>
public sealed class ServicioCaptura
{
    private readonly ILectorHuellas _lector;
    private readonly IClienteApiBiometrica _api;
    private readonly ConfiguracionLocal _config;
    private readonly IRegistroEventos _registro;
    private readonly IPresentadorCaptura? _presentador;
    private readonly IEmisorEventosLocal? _eventosLocal;
    private readonly Action<string, string, string, object?>? _depuracion;

    private readonly SemaphoreSlim _bloqueo = new(1, 1);
    private TaskCompletionSource<bool>? _finalizacion;

    public ServicioCaptura(
        ILectorHuellas lector,
        IClienteApiBiometrica api,
        ConfiguracionLocal config,
        IRegistroEventos registro,
        IPresentadorCaptura? presentador = null,
        IEmisorEventosLocal? eventosLocal = null,
        Action<string, string, string, object?>? depuracion = null)
    {
        _lector = lector;
        _api = api;
        _config = config;
        _registro = registro;
        _presentador = presentador;
        _eventosLocal = eventosLocal;
        _depuracion = depuracion;

        _lector.MuestraProcesada += OnMuestraProcesada;
        _lector.MensajeEstado += OnMensajeEstado;
    }

    public async Task EjecutarAsync(CancellationToken cancellationToken)
    {
        if (!await _bloqueo.WaitAsync(0, cancellationToken))
        {
            _registro.Advertencia("Ya hay una captura en curso.");
            return;
        }

        try
        {
            // #region agent log
            _depuracion?.Invoke("S3-H1", "ServicioCaptura.EjecutarAsync", "Iniciando captura", new
            {
                sdkDisponible = _lector.SdkDisponible
            });
            // #endregion

            if (!_lector.SdkDisponible)
            {
                _registro.Error("SDK Digital Persona no disponible. Revise Librerias/README.md");
                return;
            }

            _finalizacion = new TaskCompletionSource<bool>(TaskCreationOptions.RunContinuationsAsynchronously);

            if (_presentador is not null)
            {
                await _presentador.AbrirAsync();
            }

            _lector.IniciarCaptura();

            using var registro = cancellationToken.Register(() => _finalizacion.TrySetCanceled());
            await _finalizacion.Task;
        }
        catch (OperationCanceledException)
        {
            _registro.Info("Captura cancelada.");
        }
        finally
        {
            _lector.DetenerCaptura();

            if (_presentador is not null)
            {
                await _presentador.CerrarAsync();
            }

            _bloqueo.Release();
        }
    }

    private void OnMensajeEstado(object? sender, string mensaje)
    {
        _registro.Info(mensaje);
        _presentador?.Actualizar(mensaje, string.Empty);
    }

    private async void OnMuestraProcesada(object? sender, EventoMuestraHuella evento)
    {
        try
        {
            _presentador?.Actualizar(evento.Mensaje, evento.EstadoPlantilla);

            var imagenBase64 = evento.ImagenJpeg is null
                ? string.Empty
                : Convert.ToBase64String(evento.ImagenJpeg);

            switch (evento.Estado)
            {
                case EstadoEnrollment.EnProgreso:
                    await _api.ActualizarHuellaAsync(new ActualizarHuellaRequest
                    {
                        SerialPc = _config.IdUnicoPc,
                        ImagenHuellaBase64 = imagenBase64,
                        Mensaje = evento.Mensaje,
                        EstadoPlantilla = evento.EstadoPlantilla,
                        Opcion = "actualizar"
                    }, CancellationToken.None);

                    // #region agent log
                    _depuracion?.Invoke("S3-H2", "ServicioCaptura.OnMuestraProcesada", "Progreso enviado PUT", new
                    {
                        evento.EstadoPlantilla
                    });
                    // #endregion

                    _eventosLocal?.Emitir("captura_progreso", new
                    {
                        evento.Mensaje,
                        evento.EstadoPlantilla,
                        imagenHuella = imagenBase64
                    });
                    break;

                case EstadoEnrollment.PlantillaLista:
                    var plantillaBase64 = evento.PlantillaSerializada is null
                        ? string.Empty
                        : Convert.ToBase64String(evento.PlantillaSerializada);

                    await _api.GuardarHuellaAsync(new GuardarHuellaRequest
                    {
                        SerialPc = _config.IdUnicoPc,
                        HuellaBase64 = plantillaBase64,
                        ImagenHuellaBase64 = imagenBase64,
                        Mensaje = evento.Mensaje,
                        EstadoPlantilla = evento.EstadoPlantilla
                    }, CancellationToken.None);

                    // #region agent log
                    _depuracion?.Invoke("S3-H3", "ServicioCaptura.OnMuestraProcesada", "Plantilla guardada POST", new
                    {
                        longitudPlantilla = plantillaBase64.Length
                    });
                    // #endregion

                    _registro.Info("Plantilla biométrica guardada correctamente.");

                    _eventosLocal?.Emitir("captura_completada", new
                    {
                        evento.Mensaje,
                        evento.EstadoPlantilla,
                        imagenHuella = imagenBase64
                    });

                    _finalizacion?.TrySetResult(true);
                    break;

                case EstadoEnrollment.Fallido:
                    _registro.Advertencia("La plantilla no pudo crearse. Reiniciando captura.");
                    _lector.IniciarCaptura();
                    break;
            }
        }
        catch (Exception ex)
        {
            // #region agent log
            _depuracion?.Invoke("S3-H4", "ServicioCaptura.OnMuestraProcesada", "Error procesando muestra", new
            {
                error = ex.Message
            });
            // #endregion

            _registro.Error("Error enviando datos de huella al servidor.", ex);
            _finalizacion?.TrySetException(ex);
        }
    }
}
