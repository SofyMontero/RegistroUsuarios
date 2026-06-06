using System.Net;
using System.Net.WebSockets;
using System.Text;
using System.Text.Json;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.Infraestructura.EventosLocales;

/// <summary>
/// Servidor WebSocket en localhost para que la web reciba eventos sin esperar httpush.php.
/// Sprint 6 — reemplaza el polling de 1 s del navegador cuando la página usa plugin-ws.js.
/// </summary>
public sealed class ServidorWebSocketLocal : IEmisorEventosLocal
{
    private static readonly JsonSerializerOptions OpcionesJson = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase
    };

    private readonly int _puerto;
    private readonly Action<string, string, string, object?>? _depuracion;
    private readonly object _bloqueoClientes = new();
    private readonly List<WebSocket> _clientes = new();

    private HttpListener? _listener;
    private CancellationTokenSource? _cts;
    private Task? _tareaAceptar;

    public ServidorWebSocketLocal(int puerto, Action<string, string, string, object?>? depuracion = null)
    {
        _puerto = puerto;
        _depuracion = depuracion;
    }

    public int Puerto => _puerto;

    public bool EstaActivo => _listener?.IsListening == true;

    public async Task IniciarAsync(CancellationToken cancellationToken)
    {
        if (_listener is not null)
        {
            return;
        }

        _listener = new HttpListener();
        _listener.Prefixes.Add($"http://127.0.0.1:{_puerto}/");
        _listener.Start();

        _cts = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);

        // #region agent log
        _depuracion?.Invoke("S6-H1", "ServidorWebSocketLocal.IniciarAsync", "WebSocket local activo", new
        {
            puerto = _puerto,
            url = $"ws://127.0.0.1:{_puerto}/eventos"
        });
        // #endregion

        _tareaAceptar = Task.Run(() => AceptarConexionesAsync(_cts.Token), CancellationToken.None);

        Emitir("servidor_iniciado", new { puerto = _puerto });

        await Task.CompletedTask;
    }

    public void Detener()
    {
        _cts?.Cancel();

        lock (_bloqueoClientes)
        {
            foreach (var cliente in _clientes)
            {
                try
                {
                    cliente.Abort();
                    cliente.Dispose();
                }
                catch
                {
                    // ignorar al cerrar
                }
            }

            _clientes.Clear();
        }

        if (_listener is not null)
        {
            _listener.Stop();
            _listener.Close();
            _listener = null;
        }

        _cts?.Dispose();
        _cts = null;
        _tareaAceptar = null;
    }

    public void Emitir(string tipo, object? datos = null)
    {
        lock (_bloqueoClientes)
        {
            if (_clientes.Count == 0)
            {
                return;
            }
        }

        var evento = new EventoPluginLocal
        {
            Tipo = tipo,
            Datos = datos,
            Timestamp = DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()
        };

        var json = JsonSerializer.Serialize(evento, OpcionesJson);
        var bytes = Encoding.UTF8.GetBytes(json);

        List<WebSocket> copia;
        lock (_bloqueoClientes)
        {
            copia = _clientes.ToList();
        }

        foreach (var cliente in copia)
        {
            if (cliente.State != WebSocketState.Open)
            {
                continue;
            }

            _ = EnviarAsync(cliente, bytes);
        }
    }

    public void Dispose() => Detener();

    private async Task AceptarConexionesAsync(CancellationToken cancellationToken)
    {
        while (!cancellationToken.IsCancellationRequested && _listener is not null)
        {
            HttpListenerContext? contexto = null;

            try
            {
                contexto = await _listener.GetContextAsync();
            }
            catch (HttpListenerException) when (cancellationToken.IsCancellationRequested)
            {
                break;
            }
            catch (ObjectDisposedException)
            {
                break;
            }

            if (contexto is null)
            {
                continue;
            }

            _ = Task.Run(async () =>
            {
                try
                {
                    await AtenderSolicitudAsync(contexto, cancellationToken);
                }
                catch (Exception ex)
                {
                    // #region agent log
                    _depuracion?.Invoke("S6-H5", "ServidorWebSocketLocal.AceptarConexionesAsync", "Error en conexión WS", new
                    {
                        error = ex.Message
                    });
                    // #endregion
                }
            }, CancellationToken.None);
        }
    }

    private async Task AtenderSolicitudAsync(HttpListenerContext contexto, CancellationToken cancellationToken)
    {
        var ruta = contexto.Request.Url?.AbsolutePath ?? "/";

        if (!contexto.Request.IsWebSocketRequest || !ruta.Equals("/eventos", StringComparison.OrdinalIgnoreCase))
        {
            contexto.Response.StatusCode = (int)HttpStatusCode.NotFound;
            contexto.Response.Close();
            return;
        }

        var wsContext = await contexto.AcceptWebSocketAsync(subProtocol: null);
        var socket = wsContext.WebSocket;

        lock (_bloqueoClientes)
        {
            _clientes.Add(socket);
        }

        // #region agent log
        _depuracion?.Invoke("S6-H2", "ServidorWebSocketLocal.AtenderSolicitudAsync", "Cliente WebSocket conectado", new
        {
            clientes = _clientes.Count
        });
        // #endregion

        Emitir("cliente_conectado", new { clientes = _clientes.Count });

        await MantenerConexionAsync(socket, cancellationToken);

        lock (_bloqueoClientes)
        {
            _clientes.Remove(socket);
        }

        socket.Dispose();
    }

    private static async Task MantenerConexionAsync(WebSocket socket, CancellationToken cancellationToken)
    {
        var buffer = new byte[1024];

        while (socket.State == WebSocketState.Open && !cancellationToken.IsCancellationRequested)
        {
            WebSocketReceiveResult resultado;

            try
            {
                resultado = await socket.ReceiveAsync(buffer, cancellationToken);
            }
            catch (WebSocketException)
            {
                break;
            }

            if (resultado.MessageType == WebSocketMessageType.Close)
            {
                break;
            }
        }

        if (socket.State == WebSocketState.Open || socket.State == WebSocketState.CloseReceived)
        {
            try
            {
                await socket.CloseAsync(WebSocketCloseStatus.NormalClosure, "Cierre", CancellationToken.None);
            }
            catch
            {
                // ignorar
            }
        }
    }

    private static async Task EnviarAsync(WebSocket socket, byte[] bytes)
    {
        try
        {
            await socket.SendAsync(bytes, WebSocketMessageType.Text, endOfMessage: true, CancellationToken.None);
        }
        catch
        {
            // cliente desconectado
        }
    }
}
