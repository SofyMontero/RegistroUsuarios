# Plugin Biométrico .NET

Agente de escritorio para Windows que conecta el lector de huellas Digital Persona con la aplicación web PHP de registro de usuarios.

## Sprint 1 — Completado

- [x] Estructura de solución (.NET 8)
- [x] Configuración local en JSON (`config.json`)
- [x] Ventana de configuración inicial
- [x] Icono en bandeja del sistema (Configurar / Cerrar)
- [x] Documentación del contrato API

## Sprint 2 — Completado

- [x] DTOs alineados con `UsuarioRestApi.php` y `HabilitarSensor.php`
- [x] `ClienteApiBiometrica` (GET comando, POST/PUT huella, GET plantillas)
- [x] `OrquestadorSensor` — bucle de escucha en segundo plano
- [x] Log legible en `%LocalAppData%\PluginBiometrico\plugin.log`
- [x] Notificación en bandeja al recibir comando `capturar` / `leer`

## Sprint 3 — Completado

- [x] `ILectorHuellas` + `LectorDigitalPersona` (SDK One Touch .NET)
- [x] `ServicioCaptura` — enrollment multi-muestra + PUT progreso + POST plantilla
- [x] `VentanaEstadoCaptura` — ventana pequeña esquina inferior derecha
- [x] Detección automática del SDK en `Librerias/`

**Requisito hardware:** copiar DLL del SDK a `PluginBiometrico.NET/Librerias/` (ver `Librerias/README.md`).

## Sprint 4 — Completado

- [x] `ServicioVerificacion` — identificación 1:N contra plantillas del servidor
- [x] `MatcherDigitalPersona` — comparación con `DPFPVerNET`
- [x] Modo lectura en `LectorDigitalPersona` (`IniciarVerificacion`)
- [x] PUT `option=verificar` compatible con `UsuarioRestApi.php`
- [x] Ventana de estado con título "Sensor en modo lectura"

## Sprint 6 — Completado

- [x] Servidor WebSocket local (`ws://127.0.0.1:17890/eventos`) — eventos instantáneos a la web
- [x] Cliente JS `js/plugin-ws.js` para integrar en la página
- [x] Modo comunicación rápida — sin espera de 1 s entre consultas `reintentar`
- [x] Verificación 1:1 por `documento` en `HabilitarSensor.php` + `UsuarioRestApi.php`
- [x] Documentación `docs/websocket-api.md`

## Sprint 5 — Completado

- [x] Inicio automático con Windows (menú bandeja, registro Run)
- [x] Una sola instancia del plugin (mutex global)
- [x] Log rotativo (`plugin.log` → `.1`, `.2`, `.3` al superar 1 MB)
- [x] Script `publish.ps1` — ejecutable autocontenido win-x64
- [x] Guía de instalación `docs/INSTALACION.md`
- [x] Versión de aplicación y manifiesto Windows

## Requisitos

- Windows 10 o superior
- [.NET 8 SDK](https://dotnet.microsoft.com/download/dotnet/8.0) para compilar
- Lector Digital Persona U.are.U (Sprint 3)

## Compilar y ejecutar

```powershell
cd registroUsuarios\PluginBiometrico.NET
dotnet build
dotnet run --project src\PluginBiometrico.App
```

## Publicar ejecutable (producción)

```powershell
cd registroUsuarios\PluginBiometrico.NET
.\publish.ps1              # ~146 MB, sin instalar .NET en la PC del operador
.\publish.ps1 -Ligero      # ~1 MB, requiere .NET 8 Desktop Runtime
```

**¿Por qué tantas DLL en `publish`?** En modo autocontenido, .NET empaqueta el runtime completo (WPF + WinForms + red). Son necesarias; no las elimine a mano. El script ya excluye idiomas extra (solo `es`) y reduce ~15 MB.

En modo **ligero** solo hay ~8 archivos: el `.exe`, 3 DLL del proyecto, `System.Drawing.Common.dll`, `appsettings.json` y `Recursos\`.

Manual para operadores: [docs/INSTALACION.md](docs/INSTALACION.md)

## Configuración

Al primer arranque se abre la ventana de configuración. Los datos se guardan en:

```
%LocalAppData%\PluginBiometrico\config.json
```

Ejemplo:

```json
{
  "idUnicoPc": "MI-TOKEN-PC",
  "urlHabilitarSensor": "http://localhost/registroUsuarios/Model/HabilitarSensor.php",
  "urlApiRest": "http://localhost/registroUsuarios/Model/UsuarioRestApi.php",
  "navegador": "Chrome",
  "autoInicioConfigurado": false
}
```

El `idUnicoPc` debe coincidir con el parámetro `token` que usa la aplicación web.

## Probar Sprint 6 (WebSocket + modo rápido)

1. Ejecute el plugin y confirme en `plugin.log`: `WebSocket local activo en ws://127.0.0.1:17890/eventos`.
2. Abra la consola del navegador en la página de registro e incluya `js/plugin-ws.js`.
3. Conecte: `PluginBiometricoWs.conectar(17890, e => console.log(e))`.
4. Active el sensor desde la web — debe aparecer evento `comando` sin esperar 1 s.
5. Revise `debug-b6010c.log` (entradas `S6-H1` a `S6-H5`).

**Verificación 1:1:** inserte en `huellas_temp` un registro con `opc=leer` y `documento` del usuario; el plugin solo consultará sus huellas.

## Probar Sprint 4 (verificación / lectura)

1. Copie también `DPFPVerNET.dll` en `Librerias/` (además de las 3 DLL del Sprint 3).
2. Ejecute el plugin y desde la web active el modo **leer** (opción que inserta `opc=leer` en `huellas_temp`).
3. Coloque el dedo de un usuario ya registrado en el lector.
4. La web debe mostrar nombre y documento vía `httpush.php`.
5. Revise `plugin.log` y `debug-b6010c.log` (entradas `S4-H1` a `S4-H5`).

## Probar Sprint 2 (comunicación con PHP)

1. Asegúrate de que Apache/PHP y MySQL estén corriendo con la app `registroUsuarios`.
2. Configura el plugin con las URLs correctas y un `idUnicoPc` que coincida con el `token` de la web.
3. Ejecuta el plugin — quedará en la bandeja escuchando comandos.
4. Desde la web, activa el sensor (botón que llama `ActivarSensorAdd.php`).
5. Deberías ver:
   - Notificación en bandeja: *"Modo captura activado..."*
   - Entrada en `%LocalAppData%\PluginBiometrico\plugin.log`
   - Entrada en `debug-b6010c.log` (raíz del repo) con `opc: capturar`

Menú bandeja → **Ver log** abre el archivo de eventos.

## Estructura del proyecto

```
src/
├── PluginBiometrico.App/
│   ├── Servicios/ServicioSensorEnSegundoPlano.cs
│   └── Tray/TrayApplication.cs
├── PluginBiometrico.Core/
│   ├── Modelos/          ComandoSensor, GuardarHuellaRequest, etc.
│   ├── Interfaces/       IClienteApiBiometrica, IProcesadorComandoSensor
│   └── Servicios/        OrquestadorSensor.cs
└── PluginBiometrico.Infraestructura/
    ├── Api/ClienteApiBiometrica.cs
    ├── Config/AlmacenConfiguracionJson.cs
    └── Logging/RegistroArchivo.cs
docs/
└── contrato-api.md
```

## Relación con el plugin Java

| Java (PluginBiometricoV4) | .NET |
|-----------------------------|------|
| `Start.java` | `App.xaml.cs` |
| `ConfigForm.java` | `VentanaConfiguracion` |
| `TrayClass.java` | `TrayApplication` |
| `DB/Conexion.java` | `AlmacenConfiguracionJson` |
