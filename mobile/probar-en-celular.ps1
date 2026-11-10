<#
  Levanta todo lo necesario para probar la app en el celular con Expo Go:
  la API de Laravel en la red local (con una base SQLite de prueba) y Expo con el QR.

  Uso (o doble clic en probar-en-celular.cmd):
    .\probar-en-celular.ps1               # API local + Expo para el celular
    .\probar-en-celular.ps1 -Reiniciar    # igual, pero deja los datos de prueba como nuevos
    .\probar-en-celular.ps1 -Web          # abre la app en el navegador de la PC
    .\probar-en-celular.ps1 -Produccion   # usa la API publicada en Render (no levanta Laravel)

  Requisitos: Laragon (PHP), Node 22.13 o superior, y en el celular la app Expo Go
  conectado al mismo Wi-Fi que la PC.
#>
param(
    [switch]$Reiniciar,
    [switch]$Web,
    [switch]$Produccion
)

$ErrorActionPreference = 'Stop'
$mobile = $PSScriptRoot
$raiz = Split-Path $mobile -Parent
$puertoApi = 8000
$puertoExpo = 8081
$urlProduccion = 'https://educontrol-5btq.onrender.com/api/v1'

function Paso($texto) { Write-Host "`n==> $texto" -ForegroundColor Cyan }
function Falla($texto) { Write-Host "`nERROR: $texto" -ForegroundColor Red; exit 1 }

# Libera un puerto si lo ocupa un servidor viejo de PHP o de Node (por ejemplo, de una prueba anterior).
function Liberar-Puerto($puerto) {
    $conexiones = Get-NetTCPConnection -LocalPort $puerto -State Listen -ErrorAction SilentlyContinue
    foreach ($c in $conexiones) {
        $proceso = Get-Process -Id $c.OwningProcess -ErrorAction SilentlyContinue
        if (-not $proceso) { continue }
        if ($proceso.ProcessName -in @('php', 'node')) {
            Write-Host "    Cierro un $($proceso.ProcessName) viejo que usaba el puerto $puerto (PID $($proceso.Id))."
            Stop-Process -Id $proceso.Id -Force
        } else {
            Falla "El puerto $puerto lo usa '$($proceso.ProcessName)'. Cerralo y volvé a intentar."
        }
    }
}

# --- IP de la PC en la red (la del adaptador que tiene puerta de enlace, normalmente el Wi-Fi) ---
Paso 'Buscando la IP de la PC en la red'
$ip = (Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' } |
    Select-Object -First 1).IPv4Address.IPAddress
if (-not $ip) { Falla 'No encontré una conexión de red activa. Conectate al Wi-Fi y volvé a intentar.' }
$red = (Get-NetConnectionProfile | Select-Object -First 1)
Write-Host "    IP: $ip  (red '$($red.Name)', perfil $($red.NetworkCategory))"

# --- Dependencias de la app ---
if (-not (Test-Path (Join-Path $mobile 'node_modules'))) {
    Paso 'Instalando las dependencias de la app (solo la primera vez)'
    Push-Location $mobile; npm install; Pop-Location
}

if ($Produccion) {
    $urlApi = $urlProduccion
    Paso "Usando la API de producción: $urlApi"
    Write-Host '    Si Render estaba dormido, la primera carga tarda hasta un minuto.'
} else {
    # --- PHP: el del PATH o el de Laragon ---
    $php = (Get-Command php -ErrorAction SilentlyContinue).Source
    if (-not $php) {
        $php = Get-ChildItem 'C:\laragon\bin\php\php-*\php.exe' -ErrorAction SilentlyContinue |
            Sort-Object FullName | Select-Object -Last 1 -ExpandProperty FullName
    }
    if (-not $php) { Falla 'No encontré PHP. Instalá Laragon o agregá PHP al PATH.' }
    if (-not (Test-Path (Join-Path $raiz 'vendor'))) { Falla 'Faltan las dependencias de PHP: corré "composer install" en la carpeta del proyecto.' }
    if (-not (Test-Path (Join-Path $raiz '.env'))) { Falla 'Falta el archivo .env del proyecto (copiá .env.example y corré "php artisan key:generate").' }

    # --- Base de prueba SQLite: no toca la base MySQL de Laragon ---
    $base = Join-Path $raiz 'database\demo-mobile.sqlite'
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $base
    Push-Location $raiz
    try {
        if ($Reiniciar -or -not (Test-Path $base)) {
            Paso 'Creando la base de prueba con los usuarios y datos de siempre'
            New-Item -ItemType File -Path $base -Force | Out-Null
            & $php artisan migrate:fresh --seed --force
        } else {
            Paso 'Usando la base de prueba existente (con -Reiniciar la dejás como nueva)'
            & $php artisan migrate --force
        }
        if ($LASTEXITCODE -ne 0) { Falla 'No se pudo preparar la base de prueba.' }
    } finally { Pop-Location }

    # --- API de Laravel en otra ventana, escuchando en la red ---
    Paso "Levantando la API en http://${ip}:$puertoApi (ventana aparte)"
    Liberar-Puerto $puertoApi
    $comandoApi = "`$host.UI.RawUI.WindowTitle = 'EduControl API (no cerrar)'; " +
        "`$env:DB_CONNECTION = 'sqlite'; `$env:DB_DATABASE = '$base'; `$env:APP_DEBUG = 'false'; `$env:PHP_CLI_SERVER_WORKERS = '4'; " +
        "& '$php' artisan serve --host=0.0.0.0 --port=$puertoApi"
    Start-Process powershell -WorkingDirectory $raiz -ArgumentList '-NoExit', '-NoProfile', '-Command', $comandoApi | Out-Null

    $lista = $false
    for ($i = 0; $i -lt 30 -and -not $lista; $i++) {
        Start-Sleep -Seconds 1
        try { $lista = (Invoke-WebRequest "http://${ip}:$puertoApi/up" -UseBasicParsing -TimeoutSec 3).StatusCode -eq 200 } catch {}
    }
    if (-not $lista) { Falla "La API no respondió en http://${ip}:$puertoApi/up. Mirá la ventana 'EduControl API'." }
    Write-Host '    La API responde.'
    $urlApi = "http://${ip}:$puertoApi/api/v1"
}

# --- Expo ---
# Expo Go en iPhone pide que la PC tenga sesión con la misma cuenta que el celular.
# En lugar de "npx expo login" se puede guardar un token de acceso de expo.dev en mobile\.expo-token
# (Git lo ignora: nunca se sube al repositorio).
$archivoToken = Join-Path $mobile '.expo-token'
if (Test-Path $archivoToken) {
    $env:EXPO_TOKEN = (Get-Content $archivoToken -Raw).Trim()
    Write-Host "`n    Uso el token de Expo guardado en mobile\.expo-token."
}
Liberar-Puerto $puertoExpo
$env:EXPO_PUBLIC_API_URL = $urlApi
Remove-Item Env:CI -ErrorAction SilentlyContinue

Write-Host ''
Write-Host '------------------------------------------------------------------' -ForegroundColor Green
if ($Web) {
    Write-Host ' Se abre la app en el navegador: http://localhost:8081' -ForegroundColor Green
    Write-Host ' (F12 y el icono de celular para verla en tamaño de teléfono)'
} else {
    Write-Host ' En el celular (mismo Wi-Fi que la PC):' -ForegroundColor Green
    Write-Host '   1. Abrí Expo Go.'
    Write-Host '   2. Android: "Scan QR code". iPhone: escaneá con la cámara.'
    Write-Host "   3. Si el QR no anda: en Expo Go, 'Enter URL manually' -> exp://${ip}:$puertoExpo"
}
Write-Host ' Usuarios (contraseña "password"): tutor@educontrol.test, docente@educontrol.test'
Write-Host " API: $urlApi"
Write-Host ' Para terminar: Ctrl+C acá y cerrá la ventana "EduControl API".'
Write-Host '------------------------------------------------------------------' -ForegroundColor Green
Write-Host ''

Push-Location $mobile
try {
    # --clear: la URL de la API se graba en el bundle, así que se limpia la caché para que tome la actual.
    if ($Web) { npx expo start --web --clear --port $puertoExpo }
    else { npx expo start --lan --clear --port $puertoExpo }
} finally { Pop-Location }
