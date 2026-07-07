# Publica PluginBiometrico para entrega al cliente (Windows x64).
#
# Uso:
#   .\publish.ps1              # Autocontenido (~146 MB, no requiere .NET en la PC)
#   .\publish.ps1 -Ligero      # Solo exe + DLL (~1 MB, requiere .NET 8 Desktop Runtime)
#   .\publish.ps1 -Zip         # Autocontenido + ZIP en dist-cliente/
#   .\publish.ps1 -Ligero -Zip # Ligero + ZIP

param(
    [switch]$Ligero,
    [switch]$Zip
)

$ErrorActionPreference = "Stop"
$raiz = $PSScriptRoot
$salida = Join-Path $raiz "publish"
$proyecto = Join-Path $raiz "src\PluginBiometrico.App\PluginBiometrico.App.csproj"
$distCliente = Join-Path $raiz "dist-cliente"

if (Test-Path $salida) {
    Remove-Item $salida -Recurse -Force
}

$modo = if ($Ligero) { "LIGERO" } else { "AUTOCONTENIDO" }
Write-Host "Publicando modo $modo..." -ForegroundColor Cyan

if ($Ligero) {
    dotnet publish $proyecto `
        -c Release `
        -r win-x64 `
        --self-contained false `
        -p:DebugType=none `
        -o $salida
}
else {
    dotnet publish $proyecto `
        -c Release `
        -r win-x64 `
        --self-contained true `
        -p:PublishReadyToRun=true `
        -p:DebugType=none `
        -o $salida
}

$librerias = Join-Path $raiz "Librerias"
if (Test-Path (Join-Path $librerias "DPFPEngNET.dll")) {
    Write-Host "Copiando DLL del SDK Digital Persona (Librerias/)..." -ForegroundColor Yellow
    Copy-Item (Join-Path $librerias "DPFP*.dll") $salida -Force -ErrorAction SilentlyContinue
}
else {
    $sdkNuget = Join-Path $env:USERPROFILE ".nuget\packages\digitalpersona.net\1.4.0\lib\net46"
    if (Test-Path $sdkNuget) {
        Write-Host "Copiando DLL del SDK Digital Persona (NuGet)..." -ForegroundColor Yellow
        Copy-Item (Join-Path $sdkNuget "DPFP*.dll") $salida -Force
    }
}

$version = "1.0.0"
try {
    $xml = [xml](Get-Content $proyecto)
    $versionNode = $xml.Project.PropertyGroup.Version | Select-Object -First 1
    if ($versionNode) { $version = $versionNode }
} catch { }

$leeme = @"
PLUGIN BIOMÉTRICO — INSTALACIÓN RÁPIDA
======================================

Versión: $version
Modo: $modo

REQUISITOS
----------
- Windows 10 o superior (64 bits)
- Lector Digital Persona U.are.U con drivers instalados
- Acceso de red al servidor donde corre la aplicación web PHP

PASO 1 — INSTALAR
-----------------
1. Copie TODA esta carpeta a la PC del operador (ejemplo: C:\PluginBiometrico\).
2. No separe PluginBiometrico.exe de los demás archivos.
3. Ejecute PluginBiometrico.exe.

PASO 2 — CONFIGURAR
-------------------
En la ventana de configuración:
- URL base (opcional): use "Autocompletar" para generar las URLs del servidor.
- ID único PC: use "Generar" o el mismo token que usa la web para esta estación.
- Pulse "Probar conexión" antes de guardar.
- Pulse "Guardar".

La configuración queda en:
  %LocalAppData%\PluginBiometrico\config.json

PASO 3 — LECTOR (si no responde)
--------------------------------
El ejecutable incluye las DLL del SDK Digital Persona (.NET).
Si el lector no responde:
- Verifique drivers U.are.U (NO use driver WBF / Windows Hello).
- En Administrador de dispositivos el lector debe aparecer como "U.are.U 4500" sin "WBF".
- Reinicie el plugin tras conectar el lector USB.

PASO 4 — INICIO AUTOMÁTICO
--------------------------
Clic derecho en el icono de la bandeja → "Crear inicio automático".

SOPORTE
-------
Bandeja del sistema → "Ver log" (%LocalAppData%\PluginBiometrico\plugin.log)
"@

Set-Content -Path (Join-Path $salida "LEEME.txt") -Value $leeme -Encoding UTF8

$archivos = (Get-ChildItem $salida -Recurse -File).Count
$tamanoMb = [math]::Round((Get-ChildItem $salida -Recurse -File | Measure-Object Length -Sum).Sum / 1MB, 1)
$countDpfp = (Get-ChildItem $salida -Filter "DPFP*.dll" -File -ErrorAction SilentlyContinue).Count

Write-Host ""
Write-Host "Listo. Ejecutable:" -ForegroundColor Green
Write-Host "  $salida\PluginBiometrico.exe"
Write-Host "  $archivos archivos, $tamanoMb MB" -ForegroundColor Gray
Write-Host "  DLL SDK (DPFP*): $countDpfp" -ForegroundColor $(if ($countDpfp -ge 3) { "Gray" } else { "Red" })
Write-Host "  LEEME.txt incluido para el operador" -ForegroundColor Gray
Write-Host "  Inicio rapido: .\iniciar-plugin.ps1" -ForegroundColor Gray
Write-Host ""

if ($countDpfp -lt 3) {
    Write-Host "ADVERTENCIA: Faltan DLL del SDK. La captura NO funcionara." -ForegroundColor Red
    Write-Host "  Copie DPFPEngNET.dll a Librerias/ o restaure paquetes NuGet y vuelva a publicar." -ForegroundColor Yellow
}

if ($Ligero) {
    Write-Host "Modo ligero: instale .NET 8 Desktop Runtime si la PC no lo tiene:" -ForegroundColor Yellow
    Write-Host "  https://dotnet.microsoft.com/download/dotnet/8.0" -ForegroundColor Gray
}
else {
    Write-Host "Distribuya la carpeta 'publish' completa (no borre DLL sueltas)." -ForegroundColor Gray
}

if ($Zip) {
    New-Item -ItemType Directory -Path $distCliente -Force | Out-Null
    $sufijo = if ($Ligero) { "ligero" } else { "win-x64" }
    $nombreZip = "PluginBiometrico-v$version-$sufijo.zip"
    $rutaZip = Join-Path $distCliente $nombreZip

    if (Test-Path $rutaZip) {
        Remove-Item $rutaZip -Force
    }

    Write-Host ""
    Write-Host "Creando paquete ZIP para el cliente..." -ForegroundColor Cyan
    Compress-Archive -Path (Join-Path $salida "*") -DestinationPath $rutaZip -CompressionLevel Optimal

    $zipMb = [math]::Round((Get-Item $rutaZip).Length / 1MB, 1)
    Write-Host "Paquete listo:" -ForegroundColor Green
    Write-Host "  $rutaZip ($zipMb MB)" -ForegroundColor Gray
}

Write-Host ""
