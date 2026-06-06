# Publica PluginBiometrico para Windows x64.
#
# Uso:
#   .\publish.ps1           # Autocontenido (~146 MB, no requiere .NET en la PC)
#   .\publish.ps1 -Ligero   # Solo 5 DLL + exe (~1 MB, requiere .NET 8 Runtime)

param(
    [switch]$Ligero
)

$ErrorActionPreference = "Stop"
$raiz = $PSScriptRoot
$salida = Join-Path $raiz "publish"
$proyecto = Join-Path $raiz "src\PluginBiometrico.App\PluginBiometrico.App.csproj"

if (Test-Path $salida) {
    Remove-Item $salida -Recurse -Force
}

if ($Ligero) {
    Write-Host "Publicando modo LIGERO (requiere .NET 8 Desktop Runtime)..." -ForegroundColor Cyan

    dotnet publish $proyecto `
        -c Release `
        -r win-x64 `
        --self-contained false `
        -p:DebugType=none `
        -o $salida
}
else {
    Write-Host "Publicando modo AUTOCONTENIDO (sin instalar .NET)..." -ForegroundColor Cyan

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
    Write-Host "Copiando DLL del SDK Digital Persona..." -ForegroundColor Yellow
    Copy-Item (Join-Path $librerias "DPFP*.dll") $salida -Force -ErrorAction SilentlyContinue
}

$archivos = (Get-ChildItem $salida -Recurse -File).Count
$tamanoMb = [math]::Round((Get-ChildItem $salida -Recurse -File | Measure-Object Length -Sum).Sum / 1MB, 1)

Write-Host ""
Write-Host "Listo. Ejecutable:" -ForegroundColor Green
Write-Host "  $salida\PluginBiometrico.exe"
Write-Host "  $archivos archivos, $tamanoMb MB" -ForegroundColor Gray
Write-Host ""

if ($Ligero) {
    Write-Host "Modo ligero: instale .NET 8 Desktop Runtime si la PC no lo tiene:" -ForegroundColor Yellow
    Write-Host "  https://dotnet.microsoft.com/download/dotnet/8.0" -ForegroundColor Gray
}
else {
    Write-Host "Distribuya la carpeta 'publish' completa (no borre DLL sueltas)." -ForegroundColor Gray
}
