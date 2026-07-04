# SDK Digital Persona One Touch (.NET)

Para que el plugin capture huellas reales, el proyecto usa el paquete NuGet **DigitalPersona.NET** (se descarga al compilar).

## Si compiló antes sin SDK

Los **drivers del lector** y el **SDK .NET** son cosas distintas:

| Componente | Qué instala | Para qué sirve |
|------------|-------------|----------------|
| Drivers U.are.U | Administrador de dispositivos | Windows reconoce el lector USB |
| SDK .NET (`DPFP*NET.dll`) | NuGet al compilar | El plugin captura la huella |

Tras instalar drivers, ejecute:

```powershell
cd registroUsuarios\PluginBiometrico.NET
.\publish.ps1
```

## DLL del SDK (referencia)

| Archivo | Descripción |
|---------|-------------|
| `DPFPShrNET.dll` | Tipos compartidos |
| `DPFPDevNET.dll` | Dispositivo / captura |
| `DPFPEngNET.dll` | Enrollment y extracción |
| `DPFPVerNET.dll` | Verificación 1:1 |

Opcional: copie manualmente a esta carpeta si tiene el instalador oficial en:

```
C:\Program Files\DigitalPersona\One Touch SDK\.NET\Bin\
```

## Driver correcto

En **Administrador de dispositivos**, el lector debe ser **U.are.U 4500** sin **WBF** (Windows Hello). El driver WBF no funciona con este SDK.
