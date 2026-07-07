# Registro Usuarios — Frontend React (PoC)

Captura de huella con **U.are.U 4500** vía **HID WebSDK** (`@digitalpersona/devices`).

## Requisitos en la PC operador

1. Windows 10/11 x64  
2. Lector **U.are.U 4500** (driver HID, **no** WBF)  
3. [HID Lite Client](https://digitalpersona.hidglobal.com/lite-client/) instalado  
4. Archivo `websdk.client.ui.min.js` en `public/websdk/` (ver README de esa carpeta)

## Desarrollo

```powershell
cd registroUsuarios-web
npm install
npm run dev
```

Abrir http://localhost:5173 — el token se genera en la URL y en `localStorage` (`srnPc`).

## Variables de entorno

Cree `.env.local`:

```
VITE_API_BASE=https://registrousuarios.edmaramericas.com
```

## En paralelo: plugin .NET (legacy)

Mientras se migra la UI, el plugin clásico sigue funcionando:

```powershell
cd ..\registroUsuarios\PluginBiometrico.NET
.\publish.ps1
.\iniciar-plugin.ps1
```

Use el **mismo token** en Configurar → ID único PC.

## Próximos pasos (Fase 2)

- [x] API `EnrollWebSdk.php` — guardar sin `huellas_temp`
- [ ] Pantalla de verificación (ingreso 1:N)  
- [ ] Agente local para matching (reemplazo del polling)  
- [ ] Deploy estático en Hostinger

> **Nota:** el stub en `public/websdk/websdk.client.ui.min.js` solo permite compilar.
> En la PC operador reemplácelo por el archivo real del Lite Client.
