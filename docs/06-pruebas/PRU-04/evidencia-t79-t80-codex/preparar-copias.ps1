$ErrorActionPreference = 'Stop'
# Copias aisladas: no se reemplaza ningun archivo de T79/T80 ni se cambia de checkout.
$raizVerificacion = (Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path
$copiasVerificacion = Join-Path $raizVerificacion 'storage/app/verificacion-t79-t80-codex'
New-Item -ItemType Directory -Force -Path $copiasVerificacion | Out-Null
$utf8Verificacion = [System.Text.UTF8Encoding]::new($false)
foreach ($servicioVerificacion in @('ReporteMensualService', 'CobranzaEstadoService')) {
    $anteriorVerificacion = (& git -C $raizVerificacion show "514aefbb^:app/Services/$servicioVerificacion.php") -join "`n"
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo leer la version anterior.' }
    $anteriorVerificacion = $anteriorVerificacion.Replace("class $servicioVerificacion", "class ${servicioVerificacion}AntesT79")
    [IO.File]::WriteAllText((Join-Path $copiasVerificacion "${servicioVerificacion}AntesT79.php"), $anteriorVerificacion + "`n", $utf8Verificacion)
}
foreach ($nombreVerificacion in @('comparar-t79.php', 'comparar-cobranza-t79.php', 'volumen-generar.php')) {
    $fuenteVerificacion = [IO.File]::ReadAllText((Join-Path $raizVerificacion "docs/06-pruebas/PRU-04/prueba-tecnica/$nombreVerificacion"))
    $fuenteVerificacion = $fuenteVerificacion.Replace('wings_testing_claude', 'wings_testing_codex')
    if ($nombreVerificacion -ne 'volumen-generar.php') {
        $claseVerificacion = if ($nombreVerificacion -eq 'comparar-t79.php') { 'ReporteMensualService' } else { 'CobranzaEstadoService' }
        $seleccionVerificacion = @'
if (getenv('T79_VERSION') === 'anterior') {
    require __DIR__.'/_CLASE_AntesT79.php';
    $s = app(App\Services\_CLASE_AntesT79::class);
} else {
    $s = app(App\Services\_CLASE_::class);
}
'@
        $seleccionVerificacion = $seleccionVerificacion.Replace('_CLASE_', $claseVerificacion)
        $fuenteVerificacion = $fuenteVerificacion.Replace("`$s = app(App\Services\$claseVerificacion" + '::class);', $seleccionVerificacion)
    }
    [IO.File]::WriteAllText((Join-Path $copiasVerificacion $nombreVerificacion), $fuenteVerificacion, $utf8Verificacion)
}
$preparadorVerificacion = [IO.File]::ReadAllText((Join-Path $raizVerificacion 'docs/06-pruebas/PRU-04/evidencia-t80-claude/preparar-local.php'))
$preparadorVerificacion = $preparadorVerificacion.Replace('wings_testing_claude', 'wings_testing_codex')
$preparadorVerificacion = $preparadorVerificacion.Replace('dirname(__DIR__, 4)', "'" + $raizVerificacion.Replace('\', '/') + "'")
$preparadorVerificacion = $preparadorVerificacion.Replace('app/t80-local.json', 'app/verificacion-t79-t80-codex/credenciales-locales.json')
[IO.File]::WriteAllText((Join-Path $copiasVerificacion 'preparar-local.php'), $preparadorVerificacion, $utf8Verificacion)
Write-Output $copiasVerificacion
