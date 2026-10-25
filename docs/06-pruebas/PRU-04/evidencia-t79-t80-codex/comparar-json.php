<?php
$directorio = __DIR__;
$diferencias = [];
$comparar = function ($a, $b, string $ruta = '') use (&$comparar, &$diferencias): void {
    if (is_array($a) && is_array($b)) {
        if (array_keys($a) !== array_keys($b)) { $diferencias[] = ['ruta' => $ruta, 'claves_distintas' => true]; return; }
        foreach ($a as $clave => $valor) $comparar($valor, $b[$clave], $ruta.'/'.$clave);
    } elseif ($a !== $b) $diferencias[] = ['ruta' => $ruta, 'antes' => $a, 'ahora' => $b];
};
$leerReporte = fn ($nombre) => json_decode(is_file($directorio.'/'.$nombre)
    ? file_get_contents($directorio.'/'.$nombre) : gzdecode(file_get_contents($directorio.'/'.$nombre.'.gz')), true, flags: JSON_THROW_ON_ERROR);
$antes = $leerReporte('reportes-antes.json');
$ahora = $leerReporte('reportes-ahora.json');
$comparar($antes, $ahora);
$invalidas = array_values(array_filter($diferencias, fn ($d) => !str_ends_with($d['ruta'], '/id') || ($d['antes'] ?? 0) < 7000 || ($d['ahora'] ?? 0) < 7000));
$resultado = ['reportes' => count($antes['meses']), 'cortes_saldos' => count($antes['saldos']),
    'evoluciones' => 2, 'diferencias_solo_id_filas_agregadas' => count($invalidas) === 0,
    'diferencias_id' => $diferencias, 'otras_diferencias' => $invalidas,
    'cobranza_identica_bytes' => hash_file('sha256', $directorio.'/cobranza-antes.json') === hash_file('sha256', $directorio.'/cobranza-ahora.json')];
file_put_contents($directorio.'/comparacion-json.json', json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
echo json_encode(array_diff_key($resultado, array_flip(['diferencias_id', 'otras_diferencias']))).PHP_EOL;
if ($invalidas || !$resultado['cobranza_identica_bytes']) exit(1);
