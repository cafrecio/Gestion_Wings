$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path
Set-Location -LiteralPath $repo
$salida = Join-Path $PSScriptRoot 'evidencia-seguridad-2026-10-23'
New-Item -ItemType Directory -Force -Path $salida | Out-Null
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'mysql'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3306'
$env:DB_DATABASE = 'wings_testing_codex'
$env:DB_USERNAME = 'root'
$env:DB_PASSWORD = ''
$env:DB_URL = ''
$env:APP_CONFIG_CACHE = Join-Path $salida 'config-no-existe.php'
if (Test-Path -LiteralPath $env:APP_CONFIG_CACHE) { throw 'Cache inesperada: no se ejecuta.' }
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'array'
$env:MAIL_MAILER = 'array'
$env:QUEUE_CONNECTION = 'sync'
& C:/xampp/php/php.exe (Join-Path $PSScriptRoot 'comprobar-base.php') | Tee-Object -FilePath (Join-Path $salida 'base-efectiva.json')
if ($LASTEXITCODE -ne 0) { throw 'Preflight rechazado.' }
$inicio = (Get-Date).ToString('o')
& C:/xampp/php/php.exe vendor/phpunit/phpunit/phpunit '--filter=AccesoSinPermisoTest|SeguridadRutasProfesorTest|CobroReciboAccesoTest|CajaVerificacionMovimientosT36Test|CajaRechazoMotivoT37Test|ReciboErrorSanitizadoTest' '--colors=never' ('--log-junit=' + (Join-Path $salida 'junit.xml')) 2>&1 | Tee-Object -FilePath (Join-Path $salida 'salida.txt')
$codigo = $LASTEXITCODE
@{inicio=$inicio;fin=(Get-Date).ToString('o');salida=$codigo;base='wings_testing_codex';commit=(& git rev-parse HEAD)} | ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $salida 'ejecucion.json')
exit $codigo
