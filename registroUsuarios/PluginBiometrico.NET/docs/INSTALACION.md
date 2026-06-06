# Instalación — Plugin Biométrico .NET

Guía rápida para operadores (1 página).

## Requisitos

- Windows 10 o superior (64 bits)
- Lector Digital Persona U.are.U con drivers instalados
- Acceso a la aplicación web PHP de registro de usuarios
- Conexión de red hacia el servidor donde corre PHP

## Paso 1 — Instalar el plugin

1. Copie la carpeta `publish` completa a la PC del operador (ejemplo: `C:\PluginBiometrico\`).
2. No separe el `.exe` de sus archivos: debe quedar junto a `Recursos\`, DLL del SDK, etc.
3. Ejecute `PluginBiometrico.exe` una vez.

## Paso 2 — Configuración inicial

Al primer arranque aparece una ventana con 4 campos:

| Campo | Qué poner |
|-------|-----------|
| URL habilitar sensor | URL de `HabilitarSensor.php` en su servidor |
| URL API REST | URL de `UsuarioRestApi.php` |
| ID único PC | El mismo `token` que usa la web para esta estación |
| Navegador | Chrome, Edge, etc. (referencia local) |

Pulse **Guardar**. La configuración queda en:

`%LocalAppData%\PluginBiometrico\config.json`

## Paso 3 — SDK del lector (si aplica)

Si el lector no responde, copie las DLL del **Digital Persona One Touch SDK** junto al `.exe`:

- `DPFPShrNET.dll`
- `DPFPDevNET.dll`
- `DPFPEngNET.dll`
- `DPFPVerNET.dll` (para verificación)

Vea `Librerias/README.md` en el proyecto fuente.

## Paso 4 — Inicio automático (recomendado)

1. Clic derecho en el icono de la bandeja → **Crear inicio automático**.
2. El plugin arrancará solo al encender Windows.

Para quitarlo: **Eliminar inicio automático**.

## Uso diario

1. El icono del sensor queda en la bandeja (esquina inferior derecha).
2. Abra la web de registro de usuarios con el `token` correcto.
3. Pulse activar sensor en la web; el plugin captura o lee según el modo.
4. Si hay problemas: bandeja → **Ver log** (`plugin.log`).

## Solución rápida

| Problema | Qué revisar |
|----------|-------------|
| No aparece icono en bandeja | ¿Ya hay otra copia abierta? Cierre duplicados |
| Web no recibe huella | URLs en config, Apache/PHP activo, mismo `token` |
| Lector no responde | Drivers Digital Persona + DLL del SDK |
| Error de conexión | Firewall, URL incorrecta, servidor caído |

## Archivos de soporte

| Archivo | Ubicación |
|---------|-----------|
| Configuración | `%LocalAppData%\PluginBiometrico\config.json` |
| Log de eventos | `%LocalAppData%\PluginBiometrico\plugin.log` |
