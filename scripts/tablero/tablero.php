<?php

/**
 * Tablero unico de tareas. Uso, desde la raiz del repositorio:
 *
 *   php scripts/tablero/tablero.php ver [Codex|Gemini|Claude|Carlos]
 *   php scripts/tablero/tablero.php cambiar A13 estado=cerrado verifica=Codex paso=""
 *   php scripts/tablero/tablero.php nueva T9 "Titulo de la tarea" tiene=Gemini estado=en_curso paso="Que sigue"
 *   php scripts/tablero/tablero.php validar
 *   php scripts/tablero/tablero.php generar
 *
 * Estados: sin_empezar, en_curso, a_verificar, devuelto, cerrado.
 * Campos: titulo, gravedad, prioridad, estado, tiene, hizo, verifica, paso, ref.
 * prioridad = cuando se hace, lo decide Carlos: ahora, antes-1-11, produccion, despues o vacia (sin decidir).
 * "tiene" es quien tiene la pelota ahora: Carlos, Claude, Codex, Gemini o nadie.
 *
 * cambiar y nueva validan antes de escribir y regeneran TABLERO.html, QUIEN-HACE-QUE.md y el estado
 * de cada defecto en DEFECTOS.md y DEFECTOS.html (desde el 16/10, T3).
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
        if (! in_array($campo, TABLERO_CAMPOS, true) || in_array($campo, ['id', 'fecha', 'cerrado_el'], true)) {
            salir("El campo '{$campo}' no se puede cambiar. Son: titulo, gravedad, prioridad, estado, tiene, hizo, verifica, paso, ref.");
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
            if (in_array($clave, ['sin_empezar', 'cerradas', 'mejoras_futuras', 'otros_proyectos'], true)) {
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
            echo "\nSin empezar: " . count($g['sin_empezar']) . '. Cerradas: ' . count($g['cerradas'])
                . '. Mejoras futuras, aparte: ' . count($g['mejoras_futuras'])
                . '. De otros proyectos: ' . count($g['otros_proyectos']) . ".\n";
        }
        break;

    case 'cambiar':
        $id = array_shift($args) ?? salir('Falta el identificador de la tarea.');
        $cambios = pares($args);
        if (! $cambios) {
            salir('No dijiste que cambiar.');
        }
        $tareas = tablero_cambiar(tablero_leer(), $id, $cambios, date('Y-m-d'))
            ?? salir("No existe la tarea {$id}. Para crearla: nueva {$id} \"Titulo\".");
        guardar_si_vale($tareas);
        echo "{$id} actualizada. " . resumen(tablero_leer()) . "\n";
        break;

    case 'nueva':
        $id = array_shift($args) ?? salir('Falta el identificador.');
        $titulo = array_shift($args) ?? salir('Falta el titulo.');
        $tareas = tablero_leer();
        $nueva = array_merge(
            ['id' => $id, 'titulo' => $titulo, 'estado' => 'sin_empezar', 'tiene' => 'nadie', 'fecha' => date('Y-m-d')],
            pares($args)
        );
        if ($nueva['estado'] === 'cerrado') {
            $nueva['cerrado_el'] = date('Y-m-d');
        }
        $tareas[] = $nueva;
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

    default:
        salir("Orden desconocida: {$orden}. Ordenes: ver, cambiar, nueva, validar, generar.");
}
