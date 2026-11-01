$ErrorActionPreference='Stop'
$e='C:\xampp\htdocs\gestion-wings\docs\06-pruebas\PRU-04\evidencia-control-t102-codex'
Set-Location 'C:\xampp\htdocs\gestion-wings\storage\app\control-gw-261101\app'
$previas=@(Get-CimInstance Win32_Process -Filter "name='php.exe'" | Where-Object {$_.CommandLine -notmatch 'artisan serve| -S 127\.0\.0\.1:8111'})
if($previas.Count){throw 'Esperar a las pruebas activas'}
$env:DB_DATABASE='wings_testing_codex'
$filtro='test_el_adelanto_recuerda_el_cobro_del_que_nacio|test_editando_el_grupo_con_frecuencia_usada_antes|test_intercambiar_dos_frecuencias_no_da_error_del_servidor'
php artisan test tests/Feature/AdelantoCanceladoT100Test.php tests/Feature/ControlT94CarreraTest.php tests/Feature/ControlT94ClaudeTest.php --filter $filtro --no-ansi *> "$e\repro-orden-con-carrera.txt"
$con=$LASTEXITCODE
php artisan test tests/Feature/AdelantoCanceladoT100Test.php tests/Feature/ControlT94ClaudeTest.php --filter $filtro --no-ansi *> "$e\repro-orden-sin-carrera.txt"
$sin=$LASTEXITCODE
@{con_carrera=$con;sin_carrera=$sin;filtro=$filtro;base='wings_testing_codex';producto_modificado=$false} | ConvertTo-Json | Set-Content -LiteralPath "$e\repro-orden-resultado.json" -Encoding utf8
Write-Output "Con carrera: $con; sin carrera: $sin"
