<?php
/**
 * Corre los números de línea de las fichas de fuentes del manual cuando el código se movió (T12/T13).
 * Claude CAB, 01/11/2026.
 *
 * Solo toca una cita si la fila cita un solo archivo y línea, y su texto entre `` o «» aparece
 * exactamente una vez en ese archivo, en otra línea. Lo demás no lo adivina: lo lista para mirarlo a mano.
 *
 * Uso:  php scripts/manual/actualizar-citas-fichas.php [--aplicar]
 */
$raiz = str_replace('\\', '/', dirname(__DIR__, 2));
chdir($raiz);
$aplicar = in_array('--aplicar', $argv, true);

function normal(string $s): string
{
    $s = str_replace(['\\|', '&#124;', "\\'", '\\"'], ['|', '|', "'", '"'], $s);
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function literalMasLargo(string $t): string
{
    $partes = preg_split('/\s*(?:…|\.\.\.|:[a-z_]+|<[^>]+>|\{[^}]*\}|\$\w+|\b\d+\b)\s*/u', normal($t));
    usort($partes, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
    return preg_replace('/^[.,:;`«»\s]+|[.,:;`«»\s]+$/u', '', $partes[0] ?? '');
}

$cita = '#((?:app|resources|routes|lang|config|database)/[\w./\-]+\.(?:php|js|css)):(\d+)(-\d+)?#u';
$corridas = $bien = 0;
$aMano = [];
foreach (glob('docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/*.md') as $ficha) {
    $lineas = file($ficha, FILE_IGNORE_NEW_LINES);
    $cambio = false;
    $colArchivo = $colTexto = null;
    foreach ($lineas as $i => $fila) {
        if (!str_starts_with($fila, '|')) {
            $colArchivo = $colTexto = null;
            continue;
        }
        $celdas = array_map('trim', preg_split('/(?<!\\\\)\|/u', trim($fila, " |")));
        if ($colArchivo === null) {
            foreach ($celdas as $k => $c) {
                if (preg_match('/Archivo|Dónde está/u', $c)) $colArchivo = $k;
                if (preg_match('/Condici[oó]n|^Rechazo|^Mensaje/u', $c)) $colTexto = $k;
            }
            continue;
        }
        if ($colTexto === null || preg_match('/^-+$/', $celdas[0] ?? '') || !isset($celdas[$colArchivo], $celdas[$colTexto])) continue;
        if (preg_match_all($cita, $celdas[$colArchivo], $m, PREG_SET_ORDER) !== 1) continue;
        [$entera, $archivo, $numero] = $m[0];
        if (!is_file($archivo) || preg_match('/,\s*\d+/', $celdas[$colArchivo])) continue;
        preg_match_all('/`([^`]+)`|«([^»]+)»/u', $celdas[$colTexto], $tx);
        $textos = array_values(array_filter(array_merge($tx[1], $tx[2])));
        $literal = $textos ? literalMasLargo($textos[0]) : '';
        if (mb_strlen($literal) < 8) continue;
        $codigo = array_map(fn ($l) => normal($l), file($archivo, FILE_IGNORE_NEW_LINES));
        $hallados = array_keys(array_filter($codigo, fn ($l) => mb_stripos($l, $literal) !== false));
        $citada = (int) $numero;
        $cerca = array_filter($hallados, fn ($h) => abs($h + 1 - $citada) <= 2);
        if ($cerca) {
            $bien++;
        } elseif (count($hallados) === 1) {
            $corridas++;
            $nueva = $hallados[0] + 1;
            $lineas[$i] = str_replace("$archivo:$numero", "$archivo:$nueva", $fila);
            $cambio = true;
        } else {
            $aMano[] = basename($ficha).':'.($i + 1).' | '.count($hallados).' apariciones | '.$entera.' | '.mb_substr($literal, 0, 90);
        }
    }
    if ($cambio && $aplicar) {
        file_put_contents($ficha, implode("\n", $lineas)."\n");
    }
}
echo "En su línea: $bien. Corridas ".($aplicar ? 'corregidas' : 'a corregir').": $corridas. A mano: ".count($aMano)."\n";
foreach ($aMano as $a) echo "  $a\n";
