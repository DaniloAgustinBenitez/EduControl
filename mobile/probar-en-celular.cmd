@echo off
rem Doble clic para probar la app en el celular con Expo Go (ver probar-en-celular.ps1).
rem Se le pueden pasar las mismas opciones: -Reiniciar, -Web o -Produccion.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0probar-en-celular.ps1" %*
pause
