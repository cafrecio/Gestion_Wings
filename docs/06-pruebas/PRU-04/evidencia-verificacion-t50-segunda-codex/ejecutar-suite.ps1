$ErrorActionPreference='Stop'
$env:DB_DATABASE='wings_testing_codex'
$env:DB_CONNECTION='mysql'
$env:DB_HOST='127.0.0.1'
$env:DB_USERNAME='root'
$env:DB_PASSWORD=''
$env:APP_ENV='testing'
$env:APP_BASE_PATH=(Get-Location).Path
$otros=@(Get-CimInstance Win32_Process -Filter "name='php.exe'" | Where-Object CommandLine -match 'artisan test|phpunit')
if($otros.Count){throw 'Otra suite activa. No se inicia.'}
$fuentes=@(Get-ChildItem app,resources,routes,database,tests,config,bootstrap -Recurse -File | Where-Object {$_.FullName -notmatch '[\\/]bootstrap[\\/]cache[\\/]'} | ForEach-Object {@{path=$_.FullName.Substring((Get-Location).Path.Length+1);sha256=(Get-FileHash -LiteralPath $_.FullName).Hash}})
$fuentes|ConvertTo-Json -Depth 4|Set-Content (Join-Path $PSScriptRoot 'fuentes-antes-suite.json') -Encoding utf8
$monitor=Start-Process -FilePath (Get-Process -Id $PID).Path -ArgumentList @('-NoProfile','-File',('"'+(Join-Path $PSScriptRoot 'monitor-suite.ps1')+'"'),'-Launcher',$PID,'-Destino',('"'+(Join-Path $PSScriptRoot 'control-procesos-suite.json')+'"')) -WindowStyle Hidden -PassThru
@{inicio=(Get-Date).ToString('o');launcher=$PID;monitor=$monitor.Id;commit=(git rev-parse HEAD);raiz=(Get-Location).Path;base=$env:DB_DATABASE;otros_procesos=$otros.Count}|ConvertTo-Json|Set-Content (Join-Path $PSScriptRoot 'contexto-suite.json') -Encoding utf8
php artisan test --colors=never *> (Join-Path $PSScriptRoot 'suite-completa.txt')
$salida=$LASTEXITCODE
$cambios=@($fuentes|Where-Object {!(Test-Path -LiteralPath $_.path) -or (Get-FileHash -LiteralPath $_.path).Hash -ne $_.sha256})
@{fin=(Get-Date).ToString('o');salida=$salida;fuentes=$fuentes.Count;cambios=$cambios}|ConvertTo-Json -Depth 4|Set-Content (Join-Path $PSScriptRoot 'resultado-suite.json') -Encoding utf8
Get-Content (Join-Path $PSScriptRoot 'suite-completa.txt') -Tail 6
exit $salida
