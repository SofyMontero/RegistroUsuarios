# WebSocket local — Plugin Biométrico (Sprint 6)

El plugin expone un servidor WebSocket en la PC del operador para que la página web reciba eventos **al instante**, sin depender del long-poll de `httpush.php` (que espera 1 segundo entre consultas).

## Conexión

```
ws://127.0.0.1:17890/eventos
```

El puerto por defecto es `17890`. Se configura en `config.json`:

```json
{
  "puertoWebSocketLocal": 17890,
  "habilitarWebSocketLocal": true,
  "modoComunicacionRapida": true
}
```

## Formato de mensajes

Cada mensaje es JSON:

```json
{
  "tipo": "verificacion",
  "datos": {
    "encontrado": true,
    "documento": "1234567890",
    "nombre": "Juan Pérez",
    "imagenHuella": "base64..."
  },
  "timestamp": 1717680000123
}
```

## Tipos de evento

| `tipo` | Cuándo ocurre |
|--------|----------------|
| `servidor_iniciado` | El plugin arrancó el WebSocket |
| `cliente_conectado` | La web se conectó |
| `comando` | Llegó `capturar`, `leer` o `stop` desde PHP |
| `captura_iniciada` | Comenzó enrollment |
| `captura_progreso` | Nueva muestra capturada (PUT enviado) |
| `captura_completada` | Plantilla final guardada (POST enviado) |
| `lectura_iniciada` | Modo verificación activo |
| `verificacion` | Resultado de identificación |
| `error` | Fallo de red o hardware |
| `stop` | Comando stop recibido |

## Integración en la web

Incluya `js/plugin-ws.js` y conecte al iniciar la página:

```html
<script src="js/plugin-ws.js"></script>
<script>
PluginBiometricoWs.conectar(17890, function (evento) {
    if (evento.tipo === 'verificacion' && evento.datos) {
        $('#documento').text(evento.datos.documento || '');
        $('#nombre').text(evento.datos.nombre || '');
        if (evento.datos.imagenHuella) {
            $('#huella').attr('src', 'data:image/png;base64,' + evento.datos.imagenHuella);
        }
    }
    if (evento.tipo === 'captura_progreso' && evento.datos) {
        $('#status').text(evento.datos.estadoPlantilla || '');
    }
});
</script>
```

`httpush.php` sigue funcionando como respaldo si el WebSocket no está disponible.

## Verificación 1:1 (Sprint 6)

Si `huellas_temp.documento` tiene valor al activar modo `leer`, el plugin solo descarga las huellas de ese usuario (más rápido que 1:N).

Para activar 1:1 desde PHP, inserte el documento al crear el registro temporal:

```sql
INSERT INTO huellas_temp (pc_serial, opc, documento, texto, statusPlantilla)
VALUES ('TOKEN-PC', 'leer', '1234567890', 'Verificando usuario...', 'Esperando lectura');
```

`HabilitarSensor.php` devuelve el campo `documento` en su JSON de respuesta.
