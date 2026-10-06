<?php

/**
 * Tablero unico de tareas. Uso, desde la raiz del repositorio:
 *
 *   php scripts/tablero/tablero.php ver [Codex|Gemini|Claude|Carlos]
 *   php scripts/tablero/tablero.php cambiar A13 estado=cerrado verifica=Codex paso=""
 *   php scripts/tablero/tablero.php nueva T9 "Titulo de la tarea" tiene=Gemini estado=en_curso paso="Que sigue"
 *   php scripts/tablero/tablero.php validar
 *   php scripts/tablero/tablero.php generar
 *   php scripts/tablero/tablero.php traer      (transicion: trae cierres anotados en DEFECTOS.md)
 *
 * Estados: sin_empezar, en_curso, a_verificar, devuelto, cerrado.
 * Campos: titulo, gravedad, estado, tiene, hizo, verifica, paso, ref.
 * "tiene" es quien tiene la pelota ahora: Carlos, Claude, Codex, Gemini o nadie.
 *
 * cambiar y nueva validan antes de escribir y regeneran TABLERO.html y QUIEN-HACE-QUE.md.
 */

require __DIR__ . '/lib.php';

$args = array_slice($argv, 1);
$orden = array_shift($args) ?? 'ver';

/** @return array<string,string> */
function pares(array $args): array
{
    $pares = [];
    foreach ($args as $arg) {
        if (! str_contains($arg, '=')) {
            salir("No entiendo '{$arg}'. Los cambios van como campo=valor.");
        }
        [$campo, $valor] = explode('=', $arg, 2);
        if (! in_array($campo, TABLERO_CAMPOS, true) || in_array($campo, ['id', 'fecha'], true)) {
            salir("El campo '{$campo}' no se puede cambiar. Son: titulo, gravedad, estado, tiene, hizo, verifica, paso, ref.");
        }
        $pares[$campo] = $valor;
    }

    return $pares;
}

function salir(string $mensaje): never
{
    fwrite(STDERR, $mensaje . "\n");
    exit(1);
}

function guardar_si_vale(array $tareas): void
{
    $errores = tablero_validar($tareas);
    if ($errores) {
        salir("No se guardo nada:\n  " . implode("\n  ", $errores));
    }
    tablero_guardar_y_generar($tareas);
}

function resumen(array $tareas): string
{
    $av = tablero_avance($tareas);

    return "{$av['cerrados']} defectos cerrados de {$av['total']}. Frenan: "
        . ($av['frenan'] ? implode(', ', $av['frenan']) : 'ninguno') . '.';
}

switch ($orden) {
    case 'ver':
        $tareas = tablero_leer();
        $g = tablero_grupos($tareas);
        $titulos = ['carlos' => 'ESPERAN POR CARLOS', 'sin_verificador' => 'HECHO Y SIN NADIE QUE LO VERIFIQUE'];
        $pedido = $args[0] ?? null;
        echo resumen($tareas) . "\n";
        foreach ($g as $clave => $lista) {
            if (in_array($clave, ['sin_empezar', 'cerradas'], true)) {
                continue;
            }
            if ($pedido !== null && strcasecmp($pedido, $clave) !== 0) {
                continue;
            }
            echo "\n" . ($titulos[$clave] ?? mb_strtoupper($clave)) . ' (' . count($lista) . ")\n";
            foreach ($lista as $t) {
                echo "  {$t['id']}  [" . TABLERO_ESTADOS[$t['estado']] . "]  {$t['titulo']}\n";
                if ($t['paso'] !== '') {
                    echo "        -> {$t['paso']}\n";
                }
            }
        }
        if ($pedido === null) {
            echo "\nSin empezar: " . count($g['sin_empezar']) . '. Cerradas: ' . count($g['cerradas']) . ".\n";
        }
        break;

    case 'cambiar':
        $id = array_shift($args) ?? salir('Falta el identificador de la tarea.');
        $cambios = pares($args);
        if (! $cambios) {
            salir('No dijiste que cambiar.');
        }
        $tareas = tablero_leer();
        $hallada = false;
        foreach ($tareas as &$t) {
            if ($t['id'] !== $id) {
                continue;
            }
            $hallada = true;
            $t = array_merge($t, $cambios, ['fecha' => date('Y-m-d')]);
            // Al cerrar, la pelota no la tiene nadie y no queda paso pendiente.
            if (($cambios['estado'] ?? '') === 'cerrado') {
                $t['tiene'] = $cambios['tiene'] ?? 'nadie';
                $t['paso'] = $cambios['paso'] ?? '';
            }
        }
        unset($t);
        if (! $hallada) {
            salir("No existe la tarea {$id}. Para crearla: nueva {$id} \"Titulo\".");
        }
        guardar_si_vale($tareas);
        echo "{$id} actualizada. " . resumen(tablero_leer()) . "\n";
        break;

    case 'nueva':
        $id = array_shift($args) ?? salir('Falta el identificador.');
        $titulo = array_shift($args) ?? salir('Falta el titulo.');
        $tareas = tablero_leer();
        $tareas[] = array_merge(
            ['id' => $id, 'titulo' => $titulo, 'estado' => 'sin_empezar', 'tiene' => 'nadie', 'fecha' => date('Y-m-d')],
            pares($args)
        );
        guardar_si_vale(array_map('tablero_normalizar', $tareas));
        echo "{$id} creada.\n";
        break;

    case 'validar':
        $errores = tablero_validar(tablero_leer());
        if ($errores) {
            salir(implode("\n", $errores));
        }
        echo 'Tablero valido. ' . resumen(tablero_leer()) . "\n";
        break;

    case 'generar':
        guardar_si_vale(tablero_leer());
        echo 'Generado. ' . resumen(tablero_leer()) . "\n";
        break;

    case 'importar':
        if (file_exists(tablero_ruta(TABLERO_DATOS))) {
            salir('tareas.json ya existe. Importar es solo para el arranque.');
        }
        file_put_contents(tablero_ruta(TABLERO_DATOS), tablero_texto_datos(array_values(tablero_desde_defectos())));
        echo 'Importadas ' . count(tablero_desde_defectos()) . " tareas desde DEFECTOS.md.\n";
        break;

    case 'traer':
        // Solo avanza: nunca reabre algo que el tablero ya tiene cerrado.
        $alla = tablero_desde_defectos();
        $tareas = tablero_leer();
        $traidas = [];
        foreach ($tareas as &$t) {
            $d = $alla[$t['id']] ?? null;
            if ($d === null || $t['estado'] === 'cerrado') {
                continue;
            }
            if ($d['estado'] === 'cerrado') {
                $t = array_merge($t, ['estado' => 'cerrado', 'tiene' => 'nadie', 'paso' => '', 'fecha' => $d['fecha'],
                    'verifica' => $d['verifica'] ?: $t['verifica']]);
                $traidas[] = $t['id'] . ' cerrado';
            } elseif ($d['estado'] === 'a_verificar' && $t['estado'] === 'sin_empezar') {
                $t = array_merge($t, ['estado' => 'a_verificar', 'hizo' => $d['hizo'], 'fecha' => $d['fecha']]);
                $traidas[] = $t['id'] . ' hecho';
            }
        }
        unset($t);
        foreach (array_diff_key($alla, array_column($tareas, null, 'id')) as $nueva) {
            $tareas[] = tablero_normalizar($nueva);
            $traidas[] = $nueva['id'] . ' nueva';
        }
        guardar_si_vale($tareas);
        echo ($traidas ? 'Traido: ' . implode(', ', $traidas) : 'Nada nuevo en DEFECTOS.md') . ".\n";
        break;

    default:
        salir("Orden desconocida: {$orden}. Ordenes: ver, cambiar, nueva, validar, generar, traer.");
}
