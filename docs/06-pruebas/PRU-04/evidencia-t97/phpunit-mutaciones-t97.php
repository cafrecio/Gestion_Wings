<?php

// Un proceso PHP representa todo el lote: el chequeo compartido de suites ve el
// nombre phpunit durante los intervalos entre contrapruebas, no una ventana libre.
$raiz = dirname(__DIR__, 4);
require $raiz.'/vendor/autoload.php';
$python = getenv('WINGS_PYTHON_T97');
if (!$python || !is_file($python)) throw new RuntimeException('Indicá WINGS_PYTHON_T97 con el Python instalado.');
if (!in_array('--solo-mutaciones', $argv, true)) {
$dirigidas = new Symfony\Component\Process\Process([PHP_BINARY, 'artisan', 'test',
    '--filter=AnulacionTransferencias|TraspasosEntreMediosT61|CashflowCorrecciones.*T27'], $raiz,
    ['DB_DATABASE' => 'wings_testing_codex', 'WINGS_EVIDENCIA_T97' => '1']);
$dirigidas->setTimeout(null);
$registro = fopen(__DIR__.'/dirigidas.txt', 'wb');
$codigo = $dirigidas->run(function ($tipo, $salida) use ($registro) {
    fwrite($registro, $salida); fwrite($tipo === Symfony\Component\Process\Process::ERR ? STDERR : STDOUT, $salida);
});
fclose($registro);
if ($codigo !== 0) exit($codigo); // No atribuir a la mutación un rojo que ya estaba en la base.
} elseif (!preg_match('/Tests:\s+\d+ passed/', file_get_contents(__DIR__.'/dirigidas.txt'))) {
    throw new RuntimeException('Falta una corrida dirigida verde antes de reanudar las contrapruebas.');
}
$proceso = new Symfony\Component\Process\Process([$python, __DIR__.'/probar-mutaciones.py'], $raiz,
    ['WINGS_T97_BATCH_PID' => (string) getmypid()]);
$proceso->setTimeout(null);
exit($proceso->run(function ($tipo, $salida) {
    fwrite($tipo === Symfony\Component\Process\Process::ERR ? STDERR : STDOUT, $salida);
}));
