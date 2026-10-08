<?php

/**
 * Tablero unico de tareas de Wings.
 *
 * El estado de cada tarea vive en un solo archivo, docs/00-estado/tareas.json. La pantalla
 * que abre Carlos (TABLERO.html) y el bloque de arriba de QUIEN-HACE-QUE.md se generan desde
 * ahi; nadie los escribe a mano.
 *
 * Existe porque el 06/10/2026 el estado se llevaba a mano en tres documentos, la hoja de
 * quien hace que estaba medio dia atrasada y 27 de 72 defectos no tenian dueño ni paso
 * siguiente. Eramos cuatro trabajando sobre lo mismo sin poder seguirlo.
 */

const TABLERO_RAIZ = __DIR__ . '/../..';
const TABLERO_DATOS = 'docs/00-estado/tareas.json';
const TABLERO_HTML = 'docs/00-estado/TABLERO.html';
const TABLERO_HOJA = 'docs/00-estado/QUIEN-HACE-QUE.md';
const TABLERO_DEFECTOS = 'docs/06-pruebas/PRU-02/DEFECTOS.md';

const TABLERO_MARCA_INICIO = '<!-- TABLERO:INICIO — generado por scripts/tablero, no editar a mano -->';
const TABLERO_MARCA_FIN = '<!-- TABLERO:FIN -->';

/** Desde este dia un cierre exige saber quien lo hizo y quien lo verifico. */
const TABLERO_CORTE = '2026-10-07';

const TABLERO_ESTADOS = [
    'sin_empezar' => 'Sin empezar',
    'en_curso' => 'En curso',
    'a_verificar' => 'Hecho, espera verificación',
    'devuelto' => 'Devuelto',
    'cerrado' => 'Cerrado',
];

const TABLERO_AGENTES = ['Claude', 'Codex', 'Gemini'];
const TABLERO_GRAVEDADES = ['Frena', 'Molesta', 'Falta', ''];
const TABLERO_CAMPOS = ['id', 'titulo', 'gravedad', 'estado', 'tiene', 'hizo', 'verifica', 'paso', 'ref', 'fecha'];

function tablero_ruta(string $relativa): string
{
    return TABLERO_RAIZ . '/' . $relativa;
}

/** @return list<array<string,string>> */
function tablero_leer(): array
{
    $tareas = json_decode(file_get_contents(tablero_ruta(TABLERO_DATOS)), true, 512, JSON_THROW_ON_ERROR);

    return array_map(fn (array $t) => tablero_normalizar($t), $tareas);
}

/** @return array<string,string> */
function tablero_normalizar(array $t): array
{
    $limpia = [];
    foreach (TABLERO_CAMPOS as $campo) {
        $limpia[$campo] = trim((string) ($t[$campo] ?? ''));
    }
    if ($limpia['tiene'] === '') {
        $limpia['tiene'] = 'nadie';
    }

    return $limpia;
}

/**
 * Una tarea por linea: tres agentes editan este archivo y asi Git mezcla los cambios
 * de tareas distintas sin conflicto.
 */
function tablero_texto_datos(array $tareas): string
{
    usort($tareas, fn ($a, $b) => strnatcmp($a['id'], $b['id']));
    $lineas = array_map(
        fn ($t) => '  ' . json_encode(tablero_normalizar($t), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $tareas
    );

    return "[\n" . implode(",\n", $lineas) . "\n]\n";
}

/** @return list<string> errores; vacio si esta todo bien */
function tablero_validar(array $tareas): array
{
    $errores = [];
    $vistos = [];
    $duenios = [...TABLERO_AGENTES, 'Carlos', 'nadie'];

    foreach ($tareas as $t) {
        $id = $t['id'];
        if (! preg_match('/^[A-Z]+\d+$/', $id)) {
            $errores[] = "'{$id}': el identificador tiene que ser letras y numero, como A13 o T2.";
            continue;
        }
        if (isset($vistos[$id])) {
            $errores[] = "{$id}: esta dos veces.";
        }
        $vistos[$id] = true;

        if ($t['titulo'] === '') {
            $errores[] = "{$id}: falta el titulo.";
        }
        if (! isset(TABLERO_ESTADOS[$t['estado']])) {
            $errores[] = "{$id}: estado '{$t['estado']}' no existe. Son: " . implode(', ', array_keys(TABLERO_ESTADOS)) . '.';
            continue;
        }
        if (! in_array($t['gravedad'], TABLERO_GRAVEDADES, true)) {
            $errores[] = "{$id}: gravedad '{$t['gravedad']}' no existe. Son: Frena, Molesta, Falta o vacia.";
        }
        if (! in_array($t['tiene'], $duenios, true)) {
            $errores[] = "{$id}: tiene='{$t['tiene']}' no existe. Son: " . implode(', ', $duenios) . '.';
        }
        // Lo visual que Carlos aprueba no pasa por otro agente (decision suya, 08/10/2026):
        // por eso puede figurar como quien verifica. Nunca como quien lo hizo.
        foreach (['hizo' => TABLERO_AGENTES, 'verifica' => [...TABLERO_AGENTES, 'Carlos']] as $campo => $validos) {
            if ($t[$campo] !== '' && ! in_array($t[$campo], $validos, true)) {
                $errores[] = "{$id}: {$campo}='{$t[$campo]}' no existe. Son: " . implode(', ', $validos) . '.';
            }
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $t['fecha'])) {
            $errores[] = "{$id}: la fecha va como 2026-10-06.";
        }

        if ($t['hizo'] !== '' && $t['hizo'] === $t['verifica']) {
            $errores[] = "{$id}: lo hizo y lo verifica {$t['hizo']}. Nadie cierra lo suyo.";
        }

        switch ($t['estado']) {
            case 'en_curso':
            case 'devuelto':
                if ($t['tiene'] === 'nadie') {
                    $errores[] = "{$id}: esta " . mb_strtolower(TABLERO_ESTADOS[$t['estado']]) . ' y no lo tiene nadie.';
                }
                if ($t['paso'] === '') {
                    $errores[] = "{$id}: falta el proximo paso.";
                }
                break;
            case 'a_verificar':
                if ($t['hizo'] === '') {
                    $errores[] = "{$id}: espera verificacion y no dice quien lo hizo.";
                }
                break;
            case 'cerrado':
                if ($t['tiene'] !== 'nadie') {
                    $errores[] = "{$id}: esta cerrado y todavia lo tiene {$t['tiene']}.";
                }
                if ($t['fecha'] >= TABLERO_CORTE && ($t['hizo'] === '' || $t['verifica'] === '')) {
                    $errores[] = "{$id}: para cerrar hay que decir quien lo hizo y quien lo verifico.";
                }
                break;
        }
    }

    return $errores;
}

function tablero_es_defecto(array $t): bool
{
    return (bool) preg_match('/^[AB]\d+$/', $t['id']);
}

/** @return array{cerrados:int,total:int,frenan:list<string>} solo defectos, que es el avance que se informa */
function tablero_avance(array $tareas): array
{
    $defectos = array_filter($tareas, 'tablero_es_defecto');
    $abiertos = array_filter($defectos, fn ($t) => $t['estado'] !== 'cerrado');

    return [
        'cerrados' => count($defectos) - count($abiertos),
        'total' => count($defectos),
        'frenan' => array_values(array_map(
            fn ($t) => $t['id'],
            array_filter($abiertos, fn ($t) => $t['gravedad'] === 'Frena')
        )),
    ];
}

function tablero_ultimo_cambio(array $tareas): string
{
    $fecha = max(array_column($tareas, 'fecha') ?: ['2026-10-06']);

    return substr($fecha, 8, 2) . '/' . substr($fecha, 5, 2) . '/' . substr($fecha, 0, 4);
}

/**
 * Los grupos en el orden en que Carlos los necesita leer: primero lo que depende de el,
 * despues lo que nadie esta mirando, despues cada agente.
 *
 * @return array<string,list<array<string,string>>>
 */
function tablero_grupos(array $tareas): array
{
    usort($tareas, function ($a, $b) {
        $peso = ['Frena' => 0, 'Molesta' => 1, 'Falta' => 2, '' => 1];

        return [$peso[$a['gravedad']] ?? 1, 0] <=> [$peso[$b['gravedad']] ?? 1, 0] ?: strnatcmp($a['id'], $b['id']);
    });

    $abiertas = array_filter($tareas, fn ($t) => $t['estado'] !== 'cerrado');
    $de = fn (string $quien) => array_values(array_filter(
        $abiertas,
        fn ($t) => $t['tiene'] === $quien && $t['estado'] !== 'sin_empezar'
    ));

    $grupos = [
        'carlos' => array_values(array_filter($abiertas, fn ($t) => $t['tiene'] === 'Carlos')),
        'sin_verificador' => array_values(array_filter(
            $abiertas,
            fn ($t) => $t['estado'] === 'a_verificar' && $t['tiene'] === 'nadie'
        )),
    ];
    foreach (TABLERO_AGENTES as $agente) {
        $grupos[$agente] = $de($agente);
    }
    $grupos['sin_empezar'] = array_values(array_filter(
        $abiertas,
        fn ($t) => $t['estado'] === 'sin_empezar' && $t['tiene'] !== 'Carlos'
    ));
    $grupos['cerradas'] = array_values(array_filter($tareas, fn ($t) => $t['estado'] === 'cerrado'));

    return $grupos;
}

function tablero_e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function tablero_html(array $tareas): string
{
    $g = tablero_grupos($tareas);
    $av = tablero_avance($tareas);
    $porciento = $av['total'] ? round($av['cerrados'] * 100 / $av['total']) : 0;
    $frenan = $av['frenan'] ? implode(', ', $av['frenan']) : 'ninguno';
    $cambio = tablero_ultimo_cambio($tareas);

    $fila = function (array $t): string {
        $grav = $t['gravedad'] !== '' ? '<span class="g g-' . strtolower($t['gravedad']) . '">' . $t['gravedad'] . '</span>' : '';
        $estado = '<span class="e e-' . $t['estado'] . '">' . tablero_e(TABLERO_ESTADOS[$t['estado']]) . '</span>';
        $quien = [];
        if ($t['hizo'] !== '') {
            $quien[] = 'lo hizo ' . $t['hizo'];
        }
        if ($t['verifica'] !== '') {
            $quien[] = ($t['estado'] === 'cerrado' ? 'verificó ' : 'verifica ') . $t['verifica'];
        }
        if ($t['estado'] === 'sin_empezar' && $t['tiene'] !== 'nadie') {
            $quien[] = 'asignado a ' . $t['tiene'];
        }
        $pie = $quien ? '<span class="q">' . tablero_e(implode(' · ', $quien)) . '</span>' : '';
        $paso = $t['paso'] !== '' ? '<p class="paso">' . tablero_e($t['paso']) . '</p>' : '';

        return '<li><div class="cab"><b class="id">' . tablero_e($t['id']) . '</b>'
            . '<span class="tit">' . tablero_e($t['titulo']) . '</span></div>'
            . '<div class="meta">' . $grav . $estado . $pie . '</div>' . $paso . '</li>';
    };

    $bloque = function (string $titulo, array $lista, string $vacio, string $clase = '') use ($fila): string {
        $cuerpo = $lista
            ? '<ul>' . implode("\n", array_map($fila, $lista)) . '</ul>'
            : '<p class="vacio">' . tablero_e($vacio) . '</p>';

        return '<section class="' . $clase . '"><h2>' . tablero_e($titulo)
            . ' <span class="n">' . count($lista) . '</span></h2>' . $cuerpo . '</section>';
    };

    $plegado = function (string $titulo, array $lista) use ($fila): string {
        return '<details><summary>' . tablero_e($titulo) . ' <span class="n">' . count($lista) . '</span></summary><ul>'
            . implode("\n", array_map($fila, $lista)) . '</ul></details>';
    };

    $secciones = $bloque('Esperan por vos', $g['carlos'], 'Nada espera por vos.', 'vos')
        . $bloque('Hecho y sin nadie que lo verifique', $g['sin_verificador'], 'Todo lo hecho tiene quien lo verifique.', 'alerta');
    foreach (TABLERO_AGENTES as $agente) {
        $secciones .= $bloque($agente, $g[$agente], 'No tiene nada en este momento.');
    }
    $secciones .= $plegado('Sin empezar', $g['sin_empezar']) . $plegado('Cerradas', $g['cerradas']);

    return <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Wings · Tablero</title>
<!-- Generado por scripts/tablero desde docs/00-estado/tareas.json. No editar a mano. -->
<style>
  :root { --fondo:#f6f5f1; --carta:#fff; --texto:#1d1d1b; --suave:#6b6a65; --borde:#e2e0d8; --ok:#2f7d4f; --frena:#b3261e; --molesta:#9a6700; --falta:#5b5fc7; --vos:#fff4d6; --alerta:#fdeceb; }
  @media (prefers-color-scheme: dark) { :root { --fondo:#18181a; --carta:#222225; --texto:#ececea; --suave:#a09f9a; --borde:#35353a; --ok:#6fcf97; --frena:#ff8a80; --molesta:#f2c94c; --falta:#a5a8ff; --vos:#3a3115; --alerta:#3d1f1d; } }
  * { box-sizing:border-box; }
  body { margin:0; background:var(--fondo); color:var(--texto); font:16px/1.45 system-ui, "Segoe UI", sans-serif; }
  main { max-width:860px; margin:0 auto; padding:20px 16px 60px; }
  h1 { font-size:1.5rem; margin:0 0 4px; }
  .sub { color:var(--suave); margin:0 0 14px; font-size:.9rem; }
  .avance { background:var(--carta); border:1px solid var(--borde); border-radius:12px; padding:14px 16px; margin-bottom:18px; }
  .avance b { font-size:1.6rem; }
  .barra { height:10px; background:var(--borde); border-radius:6px; overflow:hidden; margin:8px 0; }
  .barra i { display:block; height:100%; width:{$porciento}%; background:var(--ok); }
  section, details { background:var(--carta); border:1px solid var(--borde); border-radius:12px; padding:12px 16px; margin-bottom:14px; }
  section.vos { background:var(--vos); }
  section.alerta { background:var(--alerta); }
  h2, summary { font-size:1.1rem; margin:0 0 8px; font-weight:700; }
  summary { cursor:pointer; margin:0; }
  details[open] summary { margin-bottom:8px; }
  .n { color:var(--suave); font-weight:400; font-size:.9rem; }
  ul { list-style:none; margin:0; padding:0; }
  li { padding:9px 0; border-top:1px solid var(--borde); }
  li:first-child { border-top:0; }
  .cab { display:flex; gap:8px; align-items:baseline; }
  .id { min-width:2.6em; }
  .meta { display:flex; flex-wrap:wrap; gap:6px; align-items:center; margin:4px 0 0 calc(2.6em + 8px); font-size:.8rem; }
  .g, .e { border:1px solid currentColor; border-radius:999px; padding:0 8px; white-space:nowrap; }
  .g-frena { color:var(--frena); font-weight:700; } .g-molesta { color:var(--molesta); } .g-falta { color:var(--falta); }
  .e { color:var(--suave); } .e-cerrado { color:var(--ok); } .e-devuelto { color:var(--frena); }
  .q { color:var(--suave); }
  .paso { margin:4px 0 0 calc(2.6em + 8px); font-size:.92rem; }
  .vacio { color:var(--suave); margin:0; }
</style>
</head>
<body>
<main>
  <h1>Wings · Tablero</h1>
  <p class="sub">Último cambio: {$cambio}. Esta pantalla se arma sola; el estado se cambia en un solo lugar.</p>
  <div class="avance">
    <b>{$av['cerrados']}</b> defectos cerrados de {$av['total']}
    <div class="barra"><i></i></div>
    Frenan: <b style="font-size:1rem">{$frenan}</b>
  </div>
  {$secciones}
</main>
</body>
</html>

HTML;
}

/** El bloque de arriba de QUIEN-HACE-QUE.md, que es lo que leen los agentes al arrancar. */
function tablero_bloque_hoja(array $tareas): string
{
    $g = tablero_grupos($tareas);
    $av = tablero_avance($tareas);
    $celda = fn (string $s) => str_replace('|', '/', $s);

    $tabla = function (array $lista, string $vacio) use ($celda): string {
        if (! $lista) {
            return $vacio . "\n";
        }
        $filas = array_map(function ($t) use ($celda) {
            $quien = trim(($t['hizo'] !== '' ? 'hizo ' . $t['hizo'] : '')
                . ($t['verifica'] !== '' ? ', verifica ' . $t['verifica'] : ''), ', ');

            return '| **' . $t['id'] . '** ' . $celda($t['titulo']) . ' | ' . TABLERO_ESTADOS[$t['estado']]
                . ($quien !== '' ? ' (' . $quien . ')' : '') . ' | ' . $celda($t['paso']) . ' |';
        }, $lista);

        return "| Tarea | Estado | Próximo paso |\n|---|---|---|\n" . implode("\n", $filas) . "\n";
    };

    $md = TABLERO_MARCA_INICIO . "\n\n"
        . 'Último cambio: ' . tablero_ultimo_cambio($tareas) . '. Avance: **' . $av['cerrados'] . ' defectos cerrados de '
        . $av['total'] . '**. Frenan: ' . ($av['frenan'] ? implode(', ', $av['frenan']) : 'ninguno') . ".\n\n"
        . "## Esperan por Carlos\n\n" . $tabla($g['carlos'], 'Nada.') . "\n"
        . "## Hecho y sin nadie que lo verifique\n\n" . $tabla($g['sin_verificador'], 'Nada.') . "\n";
    foreach (TABLERO_AGENTES as $agente) {
        $md .= "## {$agente}\n\n" . $tabla($g[$agente], 'Nada en este momento.') . "\n";
    }
    $sinEmpezar = array_map(fn ($t) => $t['id'], $g['sin_empezar']);
    $md .= '## Sin empezar (' . count($sinEmpezar) . ")\n\n" . ($sinEmpezar ? implode(', ', $sinEmpezar) : 'Nada') . ".\n\n"
        . TABLERO_MARCA_FIN;

    return $md;
}

/** Devuelve la hoja completa con el bloque generado reemplazado; lo escrito a mano se conserva. */
function tablero_hoja(array $tareas, string $hojaActual): string
{
    $patron = '/' . preg_quote(TABLERO_MARCA_INICIO, '/') . '.*?' . preg_quote(TABLERO_MARCA_FIN, '/') . '/su';
    if (! preg_match($patron, $hojaActual)) {
        throw new RuntimeException(TABLERO_HOJA . ' no tiene las marcas del tablero.');
    }

    return preg_replace_callback($patron, fn () => tablero_bloque_hoja($tareas), $hojaActual);
}

/** Escribe los datos y todo lo que se arma desde ellos. */
function tablero_guardar_y_generar(array $tareas): void
{
    $tareas = array_map('tablero_normalizar', $tareas);
    file_put_contents(tablero_ruta(TABLERO_DATOS), tablero_texto_datos($tareas));
    file_put_contents(tablero_ruta(TABLERO_HTML), tablero_html($tareas));
    $hoja = file_get_contents(tablero_ruta(TABLERO_HOJA));
    file_put_contents(tablero_ruta(TABLERO_HOJA), tablero_hoja($tareas, $hoja));
}

/**
 * Lee los titulos de DEFECTOS.md. Sirve para el arranque y, mientras dure la transicion,
 * para traer los cierres que los agentes sigan anotando alla.
 *
 * @return array<string,array<string,string>>
 */
function tablero_desde_defectos(): array
{
    preg_match_all('/^### ([AB]\d+)\.\s*(.*)$/mu', file_get_contents(tablero_ruta(TABLERO_DEFECTOS)), $m, PREG_SET_ORDER);

    $tareas = [];
    foreach ($m as [, $id, $resto]) {
        $partes = array_map('trim', explode(' · ', $resto));
        $t = ['id' => $id, 'titulo' => array_shift($partes), 'gravedad' => '', 'estado' => 'sin_empezar',
            'tiene' => 'nadie', 'hizo' => '', 'verifica' => '', 'paso' => '', 'ref' => '', 'fecha' => '2026-09-23'];
        if ($partes && in_array($partes[0], ['Frena', 'Molesta', 'Falta'], true)) {
            $t['gravedad'] = array_shift($partes);
        }
        $cola = implode(' · ', $partes);
        if (preg_match('/(\d{2})\/(\d{2})/', $cola, $f)) {
            $t['fecha'] = "2026-{$f[2]}-{$f[1]}";
        }
        if (mb_stripos($cola, 'CERRADO') !== false) {
            $t['estado'] = 'cerrado';
            if (preg_match('/verificado (?:por )?(Claude|Codex|Gemini)/u', $cola, $v)) {
                $t['verifica'] = $v[1];
            }
        } elseif (preg_match('/HECHO \((Claude|Codex|Gemini)\)/u', $cola, $h)) {
            $t['estado'] = 'a_verificar';
            $t['hizo'] = $h[1];
        }
        $tareas[$id] = $t;
    }

    return $tareas;
}
