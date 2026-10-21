$ErrorActionPreference='Stop'
$env:DB_DATABASE='wings_testing_codex'
$env:DB_CONNECTION='mysql'
$env:DB_HOST='127.0.0.1'
$env:DB_USERNAME='root'
$env:DB_PASSWORD=''
$env:APP_ENV='testing'
$env:APP_BASE_PATH=(Get-Location).Path
$carpeta=$PSScriptRoot
$otros=@(Get-CimInstance Win32_Process -Filter "name='php.exe'" | Where-Object CommandLine -match 'artisan test|phpunit')
if($otros.Count){throw 'Otra suite activa. No se inicia.'}
$fuentes=@(Get-ChildItem app,resources,routes,database,tests,config,bootstrap -Recurse -File | Where-Object {$_.FullName -notmatch '[\\/]bootstrap[\\/]cache[\\/]'} | ForEach-Object { @{path=$_.FullName.Substring((Get-Location).Path.Length+1);sha256=(Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash} })
$fuentes|ConvertTo-Json -Depth 4|Set-Content -LiteralPath (Join-Path $carpeta 'fuentes-antes-suite.json') -Encoding utf8
@{launcher=$PID;inicio=(Get-Date).ToString('o');base=$env:DB_DATABASE;raiz=(Get-Location).Path;commit=(git rev-parse HEAD);otros_procesos=$otros.Count}|ConvertTo-Json|Set-Content -LiteralPath (Join-Path $carpeta 'contexto-suite.json') -Encoding utf8
php artisan test --colors=never *> (Join-Path $carpeta 'suite-completa.txt')
$salida=$LASTEXITCODE
$cambios=@($fuentes|Where-Object {!(Test-Path -LiteralPath $_.path) -or (Get-FileHash -LiteralPath $_.path -Algorithm SHA256).Hash -ne $_.sha256})
@{fin=(Get-Date).ToString('o');salida=$salida;fuentes=$fuentes.Count;cambios=$cambios}|ConvertTo-Json -Depth 4|Set-Content -LiteralPath (Join-Path $carpeta 'resultado-suite.json') -Encoding utf8
Get-Content -LiteralPath (Join-Path $carpeta 'suite-completa.txt') -Tail 6
exit $salida