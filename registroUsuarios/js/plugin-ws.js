/**
 * Cliente WebSocket para el Plugin Biométrico .NET (Sprint 6).
 * Recibe eventos en tiempo real sin esperar httpush.php cada 1 segundo.
 *
 * Uso:
 *   PluginBiometricoWs.conectar(17890, function (evento) {
 *     if (evento.tipo === 'verificacion') { ... }
 *   });
 */
var PluginBiometricoWs = (function () {
    var socket = null;
    var reconectarMs = 2000;

    function conectar(puerto, onEvento, onError) {
        puerto = puerto || 17890;
        var url = 'ws://127.0.0.1:' + puerto + '/eventos';

        try {
            socket = new WebSocket(url);
        } catch (e) {
            if (onError) {
                onError(e);
            }
            return;
        }

        socket.onmessage = function (msg) {
            try {
                var evento = JSON.parse(msg.data);
                if (onEvento) {
                    onEvento(evento);
                }
            } catch (e) {
                if (onError) {
                    onError(e);
                }
            }
        };

        socket.onclose = function () {
            setTimeout(function () {
                conectar(puerto, onEvento, onError);
            }, reconectarMs);
        };

        socket.onerror = function (err) {
            if (onError) {
                onError(err);
            }
        };
    }

    function cerrar() {
        if (socket) {
            socket.close();
            socket = null;
        }
    }

    return {
        conectar: conectar,
        cerrar: cerrar
    };
})();
