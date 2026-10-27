<?php

require __DIR__.'/../evidencia-verificacion-t92-codex/bootstrap.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') throw new RuntimeException('Base efectiva distinta.');
$fixture = json_decode(file_get_contents(__DIR__.'/fixture-navegador.json'), true);
$etapa = $argv[1] ?? 'final';
$asistencias = App\Models\Asistencia::where('clase_id', $fixture['clase_id'])->orderBy('alumno_id')->get();
$excepciones = App\Models\ExcepcionCobranza::where('registro_tipo', 'ASISTENCIA')->orderBy('id')->get();
if ($etapa === 'sin-motivo') {
    if ($asistencias->isNotEmpty() || $excepciones->isNotEmpty()) throw new RuntimeException('Guardó sin motivo.');
} else {
    if ($asistencias->count() !== 6 || $asistencias->where('presente', true)->count() !== 4 || $excepciones->count() !== 2) throw new RuntimeException('Cantidades inesperadas.');
    foreach (['Nuevo', 'Deudor', 'Moroso', 'Margen'] as $nombre) {
        if (!$asistencias->firstWhere('alumno_id', $fixture['alumnos'][$nombre])?->presente) throw new RuntimeException('Falta presente '.$nombre);
    }
    if ($excepciones->pluck('tipo')->sort()->values()->all() !== ['ASISTENCIA_DEUDOR', 'ASISTENCIA_NUEVO']) throw new RuntimeException('Tipos incorrectos.');
    foreach ($excepciones as $e) {
        if (!$e->motivo || (int) $e->autor_id !== 3 || (int) $e->contexto['clase_id'] !== $fixture['clase_id']) throw new RuntimeException('Registro incompleto.');
        if (!$asistencias->firstWhere('id', $e->registro_id)?->presente) throw new RuntimeException('Origen incorrecto.');
    }
}
$datos = ['base' => 'wings_testing_codex', 'etapa' => $etapa, 'clase_id' => $fixture['clase_id'],
    'asistencias' => $asistencias->toArray(), 'excepciones_asistencia' => $excepciones->toArray(),
    'excepciones_totales' => App\Models\ExcepcionCobranza::count()];
file_put_contents(__DIR__.'/recorrido-'.$etapa.'.json', json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
echo 'PASS '.$etapa.PHP_EOL;
