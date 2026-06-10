@echo off
setlocal

set "BASE=%~dp0lib"
set "REGSVR=%SystemRoot%\SysWOW64\regsvr32.exe"

if not exist "%REGSVR%" set "REGSVR=%SystemRoot%\System32\regsvr32.exe"

echo Registrando ActiveX DigitalPersona desde:
echo %BASE%
echo.

"%REGSVR%" "%BASE%\DPFPShrX.dll"
"%REGSVR%" "%BASE%\DPFPEngX.dll"
"%REGSVR%" "%BASE%\DPFPDevX.dll"
"%REGSVR%" "%BASE%\DPFPCtlX.dll"

echo.
echo Proceso terminado. Si hubo errores, ejecute este archivo como Administrador.
pause
