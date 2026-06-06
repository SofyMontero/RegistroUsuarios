# Publica PluginBiometrico como ejecutable autocontenido para Windows x64.
# Uso: .\publish.ps1

$ErrorActionPreference = "Stop"
$raiz = $PSScriptRoot
$salida = Join-Path $raiz "publish"

Write-Host "Publicando Plugin Biometrico..." -ForegroundColor Cyan

dotnet publish (Join-Path $raiz "src\PluginBiometrico.App\PluginBiometrico.App.csproj") `
    -c Release `
    -r win-x64 `
    --self-contained true `
    -p:PublishReadyToRun=true `
    -o $salida

$librerias = Join-Path $raiz "Librerias"
if (Test-Path (Join-Path $librerias "DPFPEngNET.dll")) {
    Write-Host "Copiando DLL del SDK Digital Persona..." -ForegroundColor Yellow
    Copy-Item (Join-Path $librerias "DPFP*.dll") $salida -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "Listo. Ejecutable:" -ForegroundColor Green
Write-Host "  $salida\PluginBiometrico.exe"
Write-Host ""
Write-Host "Distribuya la carpeta 'publish' completa al operador." -ForegroundColor Gray
