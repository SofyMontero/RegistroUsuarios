using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Core.Servicios;

/// <summary>
/// Identifica usuario por huella comparando contra plantillas del servidor.
/// Reemplaza LecturaHuella.java + identificarHuella().
/// Sprint 6: verificación 1:1 cuando el comando incluye documento.
/// </summary>
public sealed class ServicioVerificacion
{
    private readonly ILectorHuellas _lector;
    private readonly IClienteApiBiometrica _api;
    private readonly IMatcherHuellas _matcher;
    private readonly ConfiguracionLocal _config;
    private readonly IRegistroEventos _registro;
    private readonly IPresentadorCaptura? _presentador;
    private readonly string? _documentoObjetivo;
    private readonly IEmisorEventosLocal? _eventosLocal;
    private readonly Action<string, string, string, object?>? _depuracion;

    private readonly SemaphoreSlim _bloqueo = new(1, 1);

    public ServicioVerificacion(
        ILectorHuellas lector,
        IClienteApiBiometrica api,
        IMatcherHuellas matcher,
        ConfiguracionLocal config,
        IRegistroEventos registro,
        IPresentadorCaptura? presentador = null,
        string? documentoObjetivo = null,
        IEmisorEventosLocal? eventosLocal = null,
        Action<string, string, string, object?>? depuracion = null)
    {
        _lector = lector;
        _api = api;
        _matcher = matcher;
        _config = config;
        _registro = registro;
        _presentador = presentador;
        _documentoObjetivo = documentoObjetivo;
        _eventosLocal = eventosLocal;
        _depuracion = depuracion;

        _lector.VerificacionCapturada += OnVerificacionCapturada;
        _lector.MensajeEstado += OnMensajeEstado;
    }

    public async Task EjecutarAsync(CancellationToken cancellationToken)
    {
        if (!await _bloqueo.WaitAsync(0, cancellationToken))
        {
            _registro.Advertencia("Ya hay una lectura en curso.");
            return;
        }

        try
        {
            // #region agent log
            _depuracion?.Invoke("S4-H1", "ServicioVerificacion.EjecutarAsync", "Iniciando lectura", new
            {
                sdkLector = _lector.SdkDisponible,
                sdkMatcher = _matcher.SdkDisponible,
                documentoObjetivo = _documentoObjetivo,
                modo = string.IsNullOrWhiteSpace(_documentoObjetivo) ? "1:N" : "1:1"
            });
            // #endregion

            if (!_lector.SdkDisponible || !_matcher.SdkDisponible)
            {
                _registro.Error("SDK Digital Persona no disponible para verificación.");
                return;
            }

            if (_presentador is not null)
            {
                var titulo = string.IsNullOrWhiteSpace(_documentoObjetivo)
                    ? "Sensor en modo lectura."
                    : $"Verificación 1:1 ({_documentoObjetivo})";

                await _presentador.AbrirAsync(titulo);
            }

            _lector.IniciarVerificacion();

            await Task.Delay(Timeout.Infinite, cancellationToken);
        }
        catch (OperationCanceledException)
        {
            _registro.Info("Lectura cancelada.");
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

    private async void OnVerificacionCapturada(object? sender, EventoVerificacionHuella evento)
    {
        try
        {
            _presentador?.Actualizar(evento.Mensaje, "Identificando...");

            var resultado = await IdentificarAsync(evento.CaracteristicasBiometricas, cancellationToken: CancellationToken.None);

            // #region agent log
            _depuracion?.Invoke("S4-H2", "ServicioVerificacion.OnVerificacionCapturada", "Resultado identificación", new
            {
                resultado.Encontrado,
                resultado.Mensaje,
                modo = string.IsNullOrWhiteSpace(_documentoObjetivo) ? "1:N" : "1:1"
            });
            // #endregion

            var imagenBase64 = evento.ImagenJpeg is null
                ? string.Empty
                : Convert.ToBase64String(evento.ImagenJpeg);

            await _api.ActualizarHuellaAsync(new ActualizarHuellaRequest
            {
                SerialPc = _config.IdUnicoPc,
                ImagenHuellaBase64 = imagenBase64,
                Mensaje = evento.Mensaje,
                EstadoPlantilla = resultado.Mensaje,
                Opcion = "verificar",
                Documento = resultado.Documento,
                Nombre = resultado.Nombre,
                Dedo = resultado.Dedo
            }, CancellationToken.None);

            // #region agent log
            _depuracion?.Invoke("S4-H3", "ServicioVerificacion.OnVerificacionCapturada", "PUT verificación enviado", new
            {
                resultado.Documento,
                resultado.Nombre
            });
            // #endregion

            _eventosLocal?.Emitir("verificacion", new
            {
                resultado.Encontrado,
                resultado.Mensaje,
                resultado.Documento,
                resultado.Nombre,
                resultado.Dedo,
                imagenHuella = imagenBase64
            });

            _presentador?.Actualizar(evento.Mensaje, resultado.Mensaje);
            _registro.Info(resultado.Encontrado
                ? $"Usuario verificado: {resultado.Nombre}"
                : "No existe un usuario asociado a esta huella.");
        }
        catch (Exception ex)
        {
            // #region agent log
            _depuracion?.Invoke("S4-H4", "ServicioVerificacion.OnVerificacionCapturada", "Error en verificación", new
            {
                error = ex.Message
            });
            // #endregion

            _registro.Error("Error identificando huella.", ex);
            _eventosLocal?.Emitir("error", new { mensaje = ex.Message });
        }
    }

    private async Task<ResultadoVerificacion> IdentificarAsync(
        object? caracteristicas,
        CancellationToken cancellationToken)
    {
        if (caracteristicas is null)
        {
            return new ResultadoVerificacion();
        }

        if (!string.IsNullOrWhiteSpace(_documentoObjetivo))
        {
            return await IdentificarUnoAUnoAsync(caracteristicas, _documentoObjetivo, cancellationToken);
        }

        return await IdentificarUnoANAsync(caracteristicas, cancellationToken);
    }

    private async Task<ResultadoVerificacion> IdentificarUnoAUnoAsync(
        object caracteristicas,
        string documento,
        CancellationToken cancellationToken)
    {
        var plantillas = await _api.ObtenerPlantillasPorDocumentoAsync(documento, cancellationToken);

        // #region agent log
        _depuracion?.Invoke("S6-H4", "ServicioVerificacion.IdentificarUnoAUnoAsync", "Plantillas 1:1", new
        {
            documento,
            cantidad = plantillas.Count
        });
        // #endregion

        foreach (var plantilla in plantillas)
        {
            if (string.IsNullOrWhiteSpace(plantilla.HuellaBase64))
            {
                continue;
            }

            var bytes = Convert.FromBase64String(plantilla.HuellaBase64);

            if (_matcher.CoincideConPlantilla(caracteristicas, bytes))
            {
                return new ResultadoVerificacion
                {
                    Encontrado = true,
                    Mensaje = "Usuario Verificado",
                    Documento = plantilla.Documento,
                    Nombre = plantilla.NombreCompleto,
                    Dedo = plantilla.NombreDedo
                };
            }
        }

        return new ResultadoVerificacion();
    }

    private async Task<ResultadoVerificacion> IdentificarUnoANAsync(
        object caracteristicas,
        CancellationToken cancellationToken)
    {
        var desde = 0;
        var hasta = 200;
        var iteraciones = 3;

        for (var i = 0; i < iteraciones && !cancellationToken.IsCancellationRequested; i++)
        {
            var plantillas = await _api.ObtenerPlantillasAsync(desde, hasta, cancellationToken);

            // #region agent log
            _depuracion?.Invoke("S4-H5", "ServicioVerificacion.IdentificarAsync", "Lote de plantillas", new
            {
                desde,
                hasta,
                cantidad = plantillas.Count
            });
            // #endregion

            if (plantillas.Count == 0)
            {
                break;
            }

            iteraciones = (int)Math.Round(plantillas[0].TotalUsuarios / 10.0);
            if (iteraciones < 1)
            {
                iteraciones = 1;
            }

            foreach (var plantilla in plantillas)
            {
                if (string.IsNullOrWhiteSpace(plantilla.HuellaBase64))
                {
                    continue;
                }

                var bytes = Convert.FromBase64String(plantilla.HuellaBase64);

                if (_matcher.CoincideConPlantilla(caracteristicas, bytes))
                {
                    return new ResultadoVerificacion
                    {
                        Encontrado = true,
                        Mensaje = "Usuario Verificado",
                        Documento = plantilla.Documento,
                        Nombre = plantilla.NombreCompleto,
                        Dedo = plantilla.NombreDedo
                    };
                }
            }

            desde += 10;
            hasta += 10;
        }

        return new ResultadoVerificacion();
    }
}
