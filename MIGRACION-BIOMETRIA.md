# Plan dual — Biometría U.are.U 4500

Opción 3: **plugin .NET operativo ahora** + **React + HID WebSDK** como camino definitivo.

## Pista A — Plugin .NET (inmediato)

| Paso | Acción |
|------|--------|
| 1 | Conectar lector U.are.U 4500 USB |
| 2 | `cd registroUsuarios\PluginBiometrico.NET` |
| 3 | `.\publish.ps1` → verificar **DLL SDK (DPFP*): 8** |
| 4 | `.\iniciar-plugin.ps1` (solo carpeta `publish\`) |
| 5 | Bandeja → Configurar → mismo token que la web |
| 6 | Log debe decir: `SDK Digital Persona activo` |
| 7 | Web → Asociar huella → ventana negra |

**No usar** `dist-cliente\` (sin DLL del SDK).

## Pista B — React + WebSDK (PoC → producción)

| Fase | Entregable | Estado |
|------|------------|--------|
| B0 | Lite Client + WebSdk en `public/websdk/` | Manual |
| B1 | `registroUsuarios-web/` PoC captura PNG | **Hecho** |
| B2 | Enrollment directo → `EnrollWebSdk.php` | **PoC listo** |
| B3 | VerifyPage + agente matching local | Pendiente |
| B4 | Reemplazar `Home.php` / `verificar.php` | Pendiente |

```powershell
cd registroUsuarios-web
npm install
npm run dev
```

## Token único (ambas pistas)

```
Navegador: localStorage.srnPc  =  URL ?token=
Plugin:    config.json idUnicoPc
```

Deben ser **idénticos**.

## Arquitectura objetivo

```
React (captura WebSDK) ──HTTPS──► API PHP ──► MySQL
        │                              ▲
        └── ws://localhost (futuro agente matching)
Plugin .NET (legacy) ──polling──► HabilitarSensor.php (deprecar)
```

## Archivos clave creados/actualizados

- `PluginBiometrico.NET/iniciar-plugin.ps1`
- `PluginBiometrico.NET/publish.ps1` (validación DLL)
- `registroUsuarios-web/` (PoC React)
- `registroUsuarios/inc/token_sesion.php`
- `registroUsuarios/Model/EnrollWebSdk.php` (enrollment React sin huellas_temp)

## Orden recomendado esta semana

1. **Hoy:** Lite Client + WebSdk + `npm run dev` + probar captura React  
2. **Hoy:** `iniciar-plugin.ps1` + probar captura plugin .NET  
3. **Mañana:** Subir PHP (token + ActivarSensorAdd) a Hostinger  
4. **Semana:** Enrollment React → guardar en BD sin `huellas_temp`
