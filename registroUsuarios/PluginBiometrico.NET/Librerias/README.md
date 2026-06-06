# SDK Digital Persona One Touch (.NET)

Para que el plugin capture huellas reales, copie aquí las DLL del **Digital Persona One Touch SDK** para .NET.

## DLL requeridas

| Archivo | Descripción |
|---------|-------------|
| `DPFPShrNET.dll` | Tipos compartidos |
| `DPFPDevNET.dll` | Dispositivo / captura |
| `DPFPEngNET.dll` | Enrollment y extracción |

Requerida para Sprint 4 (verificación):

| `DPFPVerNET.dll` | Verificación 1:1 contra plantillas |

## Dónde encontrarlas

Tras instalar el SDK, suelen estar en:

```
C:\Program Files\DigitalPersona\One Touch SDK\.NET\Bin\
```

o en la carpeta `Bin` del instalador del SDK.

## Después de copiar

Recompile el proyecto:

```powershell
dotnet build registroUsuarios\PluginBiometrico.NET
```

Si las DLL están presentes, el proyecto detecta `TIENE_SDK_DPFP` automáticamente y habilita el lector real.

## Compatibilidad con el plugin Java

Use el **mismo SDK One Touch** (no DPUruNet) para que las plantillas guardadas sean compatibles con las del plugin Java (`PluginBiometricoV4`).
