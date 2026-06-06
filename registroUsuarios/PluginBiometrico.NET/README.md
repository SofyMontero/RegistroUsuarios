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

**Pendiente (Sprint 3+):** integración con lector Digital Persona físico.

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

## Publicar ejecutable

```powershell
dotnet publish src\PluginBiometrico.App -c Release -r win-x64 --self-contained -o publish
```

El ejecutable quedará en `publish\PluginBiometrico.exe`.

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
