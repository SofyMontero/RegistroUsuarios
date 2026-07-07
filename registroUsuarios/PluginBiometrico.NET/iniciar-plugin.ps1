# Inicia PluginBiometrico desde publish/ (única carpeta válida para captura).
# Uso: .\iniciar-plugin.ps1

$ErrorActionPreference = "Stop"
$raiz = $PSScriptRoot
$publish = Join-Path $raiz "publish"
$exe = Join-Path $publish "PluginBiometrico.exe"

if (-not (Test-Path $exe)) {
    Write-Host "No existe publish\PluginBiometrico.exe. Ejecute primero:" -ForegroundColor Red
    Write-Host "  .\publish.ps1" -ForegroundColor Yellow
    exit 1
}

$dpfp = @(Get-ChildItem $publish -Filter "DPFP*.dll" -ErrorAction SilentlyContinue)
if ($dpfp.Count -lt 3) {
    Write-Host "Faltan DLL del SDK (DPFP*.dll) en publish\. Ejecute:" -ForegroundColor Red
    Write-Host "  .\publish.ps1" -ForegroundColor Yellow
    exit 1
}

Get-Process -Name "PluginBiometrico" -ErrorAction SilentlyContinue | ForEach-Object {
    Write-Host "Cerrando instancia anterior (PID $($_.Id))..." -ForegroundColor Yellow
    Stop-Process -Id $_.Id -Force
    Start-Sleep -Seconds 1
}

Write-Host "Iniciando plugin desde publish ($($dpfp.Count) DLL SDK)..." -ForegroundColor Green
Start-Process -FilePath $exe -WorkingDirectory $publish
Write-Host "Listo. Configure el token en bandeja -> Configurar. Log: %LocalAppData%\PluginBiometrico\plugin.log"
