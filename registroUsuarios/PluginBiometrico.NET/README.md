# Plugin Biométrico .NET

Agente de escritorio para Windows que conecta el lector de huellas Digital Persona con la aplicación web PHP de registro de usuarios.

## Sprint 1 — Estado actual

- [x] Estructura de solución (.NET 8)
- [x] Configuración local en JSON (`config.json`)
- [x] Ventana de configuración inicial
- [x] Icono en bandeja del sistema (Configurar / Cerrar)
- [x] Documentación del contrato API

**Pendiente (Sprint 2+):** comunicación HTTP con PHP, captura de huella, verificación.

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

## Estructura del proyecto

```
src/
├── PluginBiometrico.App/           Programa principal + bandeja + ventanas
├── PluginBiometrico.Core/          Modelos e interfaces (sin dependencias externas)
└── PluginBiometrico.Infraestructura/  Guardado de configuración en JSON
docs/
└── contrato-api.md                 Protocolo con el backend PHP
```

## Relación con el plugin Java

| Java (PluginBiometricoV4) | .NET |
|-----------------------------|------|
| `Start.java` | `App.xaml.cs` |
| `ConfigForm.java` | `VentanaConfiguracion` |
| `TrayClass.java` | `TrayApplication` |
| `DB/Conexion.java` | `AlmacenConfiguracionJson` |
