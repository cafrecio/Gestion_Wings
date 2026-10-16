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
const TABLERO_DEFECTOS_HTML = 'docs/06-pruebas/PRU-02/DEFECTOS.html';

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
// Prioridad que le da Carlos a una tarea que todavia no se empieza. Vacia = sin marcar.
const TABLERO_PRIORIDADES = ['alta' => 'Prioritaria', '' => '', 'baja' => 'Muy a futuro'];
// fecha = ultimo cambio; cerrado_el = dia en que se cerro. No son lo mismo: cambiar el paso de algo
// cerrado no puede mover su fecha de cierre (lo encontro Codex al verificar T3, 16/10/2026).
const TABLERO_CAMPOS = ['id', 'titulo', 'gravedad', 'prioridad', 'estado', 'tiene', 'hizo', 'verifica', 'paso', 'ref', 'fecha', 'cerrado_el'];

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
        if ($t['estado'] === 'cerrado' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $t['cerrado_el'])) {
            $errores[] = "{$id}: esta cerrada y no dice cuando se cerro (cerrado_el).";
        }
        if ($t['estado'] !== 'cerrado' && $t['cerrado_el'] !== '') {
            $errores[] = "{$id}: no esta cerrada y tiene fecha de cierre.";
        }
        if (! isset(TABLERO_PRIORIDADES[$t['prioridad']])) {
            $errores[] = "{$id}: prioridad '{$t['prioridad']}' no existe. Son: alta, baja o vacia.";
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

/** Mejora futura: anotada para no perderla, sin intención de empezarla ahora. */
/** De otro proyecto (CAB01…): importante, pero fuera de lo pendiente de Wings. Carlos, 13/10/2026. */
function tablero_es_de_otro_proyecto(array $t): bool
{
    return (bool) preg_match('/^CAB\d+$/', $t['id']);
}

function tablero_es_mejora_futura(array $t): bool
{
    return (bool) preg_match('/^MF\d+$/', $t['id']);
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
    // Las mejoras futuras (MF) no son trabajo de ahora: van aparte y no cuentan como
    // «sin empezar». Carlos, 13/10/2026: «ensucian la situación del proyecto».
    $mejoras = array_values(array_filter($abiertas, 'tablero_es_mejora_futura'));
    // En el orden que les dio Carlos: prioritarias, sin marcar, muy a futuro.
    $orden = ['alta' => 0, '' => 1, 'baja' => 2];
    usort($mejoras, fn ($a, $b) => $orden[$a['prioridad']] <=> $orden[$b['prioridad']] ?: strnatcmp($a['id'], $b['id']));
    // Lo que es de otro proyecto (CAB) se anota para no perderlo, pero no es pendiente de Wings.
    $otros = array_values(array_filter($abiertas, 'tablero_es_de_otro_proyecto'));
    $abiertas = array_filter($abiertas, fn ($t) => ! tablero_es_mejora_futura($t) && ! tablero_es_de_otro_proyecto($t));
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
    $grupos['mejoras_futuras'] = $mejoras;
    $grupos['otros_proyectos'] = $otros;

    return $grupos;
}

function tablero_e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

/** Dias sin moverse a partir de los cuales una tarea empezada figura como atrasada. */
const TABLERO_DIAS_ATRASO = 3;

/** Lo primero que dice el paso, para leerlo de un vistazo; el resto queda en «Detalle». */
function tablero_resumen(string $paso): string
{
    $corto = preg_split('/(?<=\.)\s/u', $paso, 2)[0];

    return mb_strlen($corto) > 150 ? mb_substr($corto, 0, 147) . '…' : $corto;
}

/** Lo que Carlos tiene que hacer con una tarea que espera por el, en pocas palabras. */
function tablero_accion_de_carlos(array $t): string
{
    return match ($t['estado']) {
        'a_verificar' => $t['verifica'] === '' || $t['verifica'] === 'Carlos' ? 'Revisar y dar el OK' : 'Dar el OK final',
        'sin_empezar' => 'Decidir quién la hace',
        'devuelto' => 'Volvió: decidir cómo sigue',
        default => 'Te toca el próximo paso',
    };
}

/**
 * El tablero que abre Carlos. Tiene que entenderse con solo mirarlo (Carlos, 13/10/2026):
 * que le toca, que esta en marcha, que esta atrasado y cuanto esta hecho. El texto largo
 * queda plegado en «Detalle»; arriba van numeros, colores y una accion por tarjeta.
 */
function tablero_html(array $tareas): string
{
    $g = tablero_grupos($tareas);
    $cambio = tablero_ultimo_cambio($tareas);
    // Fecha del ultimo cambio, no la de hoy: lo generado depende solo de tareas.json. Al abrir, el script recalcula.
    $hoy = max(array_column($tareas, 'fecha') ?: ['2026-10-06']);
    $dias = fn (array $t): int => max(0, (int) floor((strtotime($hoy) - strtotime($t['fecha'])) / 86400));
    $empezada = fn (array $t): bool => in_array($t['estado'], ['en_curso', 'a_verificar', 'devuelto'], true);
    $atrasada = fn (array $t): bool => $empezada($t) && $dias($t) >= TABLERO_DIAS_ATRASO;

    $deAgentes = array_merge(...array_values(array_map(fn ($a) => $g[$a], TABLERO_AGENTES)));
    $enMarcha = array_merge($deAgentes, $g['sin_verificador']);
    $atrasadas = array_values(array_filter(array_merge($g['carlos'], $enMarcha), $atrasada));
    $total = count($g['carlos']) + count($enMarcha) + count($g['sin_empezar']) + count($g['cerradas']);
    $pc = fn (int $n): string => $total ? rtrim(rtrim(number_format($n * 100 / $total, 2, '.', ''), '0'), '.') : '0';

    $tarjeta = function (array $t, bool $deCarlos = false) use ($dias, $atrasada, $empezada): string {
        $clases = 't e-' . $t['estado'] . ($atrasada($t) ? ' atrasada' : '');
        $n = $dias($t);
        $edad = $n === 0 ? 'hoy' : ($n === 1 ? 'ayer' : "hace {$n} días");
        $chips = '<span class="chip estado">' . tablero_e(TABLERO_ESTADOS[$t['estado']]) . '</span>';
        if ($t['gravedad'] === 'Frena') {
            $chips .= '<span class="chip frena">Frena</span>';
        }
        if ($t['prioridad'] !== '') {
            $chips .= '<span class="chip p-' . $t['prioridad'] . '">' . TABLERO_PRIORIDADES[$t['prioridad']] . '</span>';
        }
        $chips .= '<span class="chip edad" data-edad>' . ($atrasada($t) ? 'Atrasada · ' : '') . $edad . '</span>';

        $camino = [];
        if ($t['hizo'] !== '') {
            $camino[] = 'hizo ' . $t['hizo'];
        }
        if ($t['verifica'] !== '') {
            $camino[] = 'verifica ' . $t['verifica'];
        }
        $quien = $camino ? '<div class="quien">' . tablero_e(implode(' → ', $camino)) . '</div>' : '';

        $accion = $deCarlos ? '<div class="accion">' . tablero_e(tablero_accion_de_carlos($t)) . '</div>' : '';
        $resumen = $t['paso'] !== '' ? tablero_resumen($t['paso']) : '';
        $corto = $resumen !== '' ? '<p class="corto">' . tablero_e($resumen) . '</p>' : '';
        $detalle = $t['paso'] !== '' && $resumen !== $t['paso']
            ? '<details><summary>Detalle</summary><p>' . tablero_e($t['paso']) . '</p></details>' : '';

        return '<article class="' . $clases . '" data-fecha="' . $t['fecha'] . '" data-empezada="' . ($empezada($t) ? 1 : 0) . '">'
            . '<div class="cab"><b class="id">' . tablero_e($t['id']) . '</b><span class="tit">' . tablero_e($t['titulo']) . '</span></div>'
            . $accion . '<div class="chips">' . $chips . '</div>' . $quien . $corto . $detalle . '</article>';
    };

    $renglon = function (array $t): string {
        $chip = $t['prioridad'] !== '' ? '<span class="chip p-' . $t['prioridad'] . '">' . TABLERO_PRIORIDADES[$t['prioridad']] . '</span>' : '';
        $de = $t['estado'] !== 'cerrado' && $t['tiene'] !== 'nadie' ? '<span class="chip de">' . tablero_e($t['tiene']) . '</span>' : '';
        $paso = $t['paso'] !== '' ? ' title="' . tablero_e($t['paso']) . '"' : '';

        return '<li' . $paso . '><b class="id">' . tablero_e($t['id']) . '</b><span class="tit">' . tablero_e($t['titulo']) . '</span>' . $chip . $de . '</li>';
    };

    $vos = $g['carlos']
        ? implode("\n", array_map(fn ($t) => $tarjeta($t, true), $g['carlos']))
        : '<p class="vacio">Nada espera por vos.</p>';

    $carriles = '';
    foreach (TABLERO_AGENTES as $agente) {
        $lista = $g[$agente];
        $carriles .= '<div class="carril"><h3>' . $agente . ' <span class="n">' . count($lista) . '</span></h3>'
            . ($lista ? implode("\n", array_map($tarjeta, $lista)) : '<p class="libre">Libre</p>') . '</div>';
    }
    $huerfanas = $g['sin_verificador']
        ? '<div class="huerfanas"><h3>Hecho y sin nadie que lo verifique <span class="n">' . count($g['sin_verificador']) . '</span></h3>'
            . implode("\n", array_map($tarjeta, $g['sin_verificador'])) . '</div>'
        : '';

    $orden = ['alta' => 0, '' => 1, 'baja' => 2];
    $sinEmpezar = $g['sin_empezar'];
    usort($sinEmpezar, fn ($a, $b) => $orden[$a['prioridad']] <=> $orden[$b['prioridad']] ?: strnatcmp($a['id'], $b['id']));
    $cerradas = $g['cerradas'];
    usort($cerradas, fn ($a, $b) => strcmp($b['fecha'], $a['fecha']) ?: strnatcmp($a['id'], $b['id']));
    $plegado = fn (string $titulo, array $lista, string $clase): string => '<details class="lista ' . $clase . '"><summary><i class="punto"></i>'
        . tablero_e($titulo) . ' <span class="n">' . count($lista) . '</span></summary><ul>' . implode("\n", array_map($renglon, $lista)) . '</ul></details>';

    $nVos = count($g['carlos']);
    $nMarcha = count($enMarcha);
    $nAtraso = count($atrasadas);
    $nHecho = count($g['cerradas']);
    $nResto = count($g['sin_empezar']);
    $limite = TABLERO_DIAS_ATRASO;
    $barra = '<i class="s-hecho" style="width:' . $pc($nHecho) . '%"></i><i class="s-marcha" style="width:' . $pc($nMarcha) . '%"></i>'
        . '<i class="s-vos" style="width:' . $pc($nVos) . '%"></i><i class="s-resto" style="width:' . $pc($nResto) . '%"></i>';
    $listas = $plegado('Sin empezar', $sinEmpezar, 'l-resto') . $plegado('Hecho', $cerradas, 'l-hecho')
        . $plegado('Mejoras futuras, no se empiezan ahora', $g['mejoras_futuras'], 'l-futuro')
        . $plegado('De otros proyectos, no es pendiente de Wings', $g['otros_proyectos'], 'l-futuro');

    $css = <<<'CSS'
  :root { --fondo:#f3f4f6; --carta:#fff; --texto:#111827; --suave:#6b7280; --borde:#e5e7eb;
    --vos:#f59e0b; --vos-f:#fffbeb; --marcha:#2563eb; --marcha-f:#eff6ff; --verif:#7c3aed; --atraso:#dc2626; --atraso-f:#fef2f2;
    --hecho:#16a34a; --hecho-f:#f0fdf4; --resto:#9ca3af; }
  @media (prefers-color-scheme: dark) { :root { --fondo:#111113; --carta:#1c1c20; --texto:#ececea; --suave:#a1a1aa; --borde:#2e2e33;
    --vos-f:#2e2410; --marcha-f:#131d33; --atraso-f:#331616; --hecho-f:#12261a; } }
  * { box-sizing:border-box; }
  body { margin:0; background:var(--fondo); color:var(--texto); font:16px/1.4 system-ui, "Segoe UI", sans-serif; }
  main { max-width:none; margin:0; padding:18px 24px 60px; }
  header { display:flex; align-items:baseline; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
  h1 { font-size:1.4rem; margin:0; }
  .sub { color:var(--suave); font-size:.85rem; }
  .fichas { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:12px; }
  .ficha { display:block; text-decoration:none; color:inherit; background:var(--carta); border:1px solid var(--borde); border-top:6px solid var(--c); border-radius:14px; padding:12px 16px; }
  .ficha b { display:block; font-size:2.6rem; line-height:1; color:var(--c); }
  .ficha span { font-weight:700; }
  .ficha small { display:block; color:var(--suave); }
  .f-vos { --c:var(--vos); } .f-marcha { --c:var(--marcha); } .f-atraso { --c:var(--atraso); } .f-hecho { --c:var(--hecho); }
  .f-atraso.cero { --c:var(--resto); }
  .barra { display:flex; height:16px; border-radius:8px; overflow:hidden; background:var(--borde); }
  .barra i { display:block; height:100%; }
  .s-hecho { background:var(--hecho); } .s-marcha { background:var(--marcha); } .s-vos { background:var(--vos); } .s-resto { background:var(--resto); }
  .leyenda { display:flex; flex-wrap:wrap; gap:4px 16px; font-size:.82rem; color:var(--suave); margin:6px 0 20px; }
  .leyenda i, .punto { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }
  h2 { font-size:1.05rem; text-transform:uppercase; letter-spacing:.06em; margin:22px 0 10px; display:flex; align-items:center; gap:8px; }
  h2 i { width:14px; height:14px; border-radius:4px; }
  h3 { font-size:1rem; margin:0 0 8px; }
  .n { color:var(--suave); font-weight:400; }
  .grilla { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:12px; }
  .carriles { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; align-items:start; }
  .carril, .huerfanas { background:var(--marcha-f); border:1px solid var(--borde); border-radius:14px; padding:12px; }
  .huerfanas { background:var(--atraso-f); margin-top:12px; }
  .carril .t, .huerfanas .t { margin-bottom:10px; } .carril .t:last-child, .huerfanas .t:last-child { margin-bottom:0; }
  .libre { margin:0; padding:18px 0; text-align:center; color:var(--suave); font-weight:700; border:2px dashed var(--borde); border-radius:10px; }
  .t { background:var(--carta); border:1px solid var(--borde); border-left:6px solid var(--c, var(--resto)); border-radius:12px; padding:10px 12px; }
  .e-en_curso { --c:var(--marcha); } .e-a_verificar { --c:var(--verif); } .e-devuelto { --c:var(--atraso); } .e-sin_empezar { --c:var(--resto); }
  #vos .t { --c:var(--vos); background:var(--vos-f); }
  .t.atrasada { --c:var(--atraso) !important; background:var(--atraso-f) !important; }
  .cab { display:flex; gap:8px; align-items:baseline; }
  .id { font-size:.95rem; white-space:nowrap; }
  .tit { font-weight:600; }
  .accion { margin:6px 0 2px; font-weight:800; font-size:1.05rem; }
  .accion::before { content:"→ "; color:var(--vos); }
  .chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; font-size:.76rem; }
  .chip { border-radius:999px; padding:1px 9px; background:var(--borde); white-space:nowrap; font-weight:600; }
  .chip.estado { background:var(--c, var(--resto)); color:#fff; }
  .chip.frena, .t.atrasada .chip.edad { background:var(--atraso); color:#fff; }
  .chip.p-alta { background:#be123c; color:#fff; } .chip.p-baja { color:var(--suave); } .chip.de { background:var(--marcha-f); }
  .quien { color:var(--suave); font-size:.8rem; margin-top:4px; }
  .corto { margin:6px 0 0; font-size:.88rem; }
  .t details { margin-top:4px; font-size:.85rem; } .t summary { cursor:pointer; color:var(--suave); } .t details p { margin:4px 0 0; }
  .vacio { color:var(--suave); margin:0; }
  .lista { background:var(--carta); border:1px solid var(--borde); border-radius:12px; padding:10px 14px; margin-bottom:10px; }
  .lista > summary { cursor:pointer; font-weight:700; }
  .lista ul { list-style:none; margin:8px 0 0; padding:0; }
  .lista li { display:flex; gap:8px; align-items:baseline; padding:5px 0; border-top:1px solid var(--borde); font-size:.92rem; }
  .lista li .id { min-width:3.2em; } .lista li .tit { font-weight:400; flex:1; } .lista li .chip { font-size:.72rem; }
  .l-resto .punto { background:var(--resto); } .l-hecho .punto { background:var(--hecho); } .l-futuro .punto { background:var(--borde); border:1px solid var(--resto); }
  @media (max-width:760px) { .fichas { grid-template-columns:repeat(2, 1fr); } .carriles { grid-template-columns:1fr; } .ficha b { font-size:2.1rem; } }
CSS;

    // La antiguedad se recalcula al abrir: la pagina se genera cuando cambia una tarea, no cada dia.
    $js = <<<'JS'
  (function () {
    var limite = Number(document.body.dataset.limite), hoy = new Date(); hoy.setHours(0, 0, 0, 0); var atrasadas = 0;
    document.querySelectorAll('article.t').forEach(function (t) {
      var p = t.dataset.fecha.split('-'), n = Math.max(0, Math.round((hoy - new Date(p[0], p[1] - 1, p[2])) / 86400000));
      var tarde = t.dataset.empezada === '1' && n >= limite;
      t.classList.toggle('atrasada', tarde); if (tarde) { atrasadas++; }
      t.querySelector('[data-edad]').textContent = (tarde ? 'Atrasada · ' : '') + (n === 0 ? 'hoy' : n === 1 ? 'ayer' : 'hace ' + n + ' días');
    });
    var f = document.querySelector('.f-atraso'); f.querySelector('b').textContent = atrasadas; f.classList.toggle('cero', atrasadas === 0);
  })();
JS;

    $cero = $nAtraso === 0 ? ' cero' : '';

    return <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Wings · Tablero</title>
<!-- Generado por scripts/tablero desde docs/00-estado/tareas.json. No editar a mano. -->
<style>
{$css}
</style>
</head>
<body data-limite="{$limite}">
<main>
  <header><h1>Wings · Tablero</h1><span class="sub">Último cambio: {$cambio}</span></header>

  <div class="fichas">
    <a class="ficha f-vos" href="#vos"><b>{$nVos}</b><span>Te toca a vos</span><small>esperan una decisión o tu OK</small></a>
    <a class="ficha f-marcha" href="#marcha"><b>{$nMarcha}</b><span>En marcha</span><small>las tienen los agentes</small></a>
    <a class="ficha f-atraso{$cero}" href="#marcha"><b>{$nAtraso}</b><span>Atrasadas</span><small>{$limite} días o más sin moverse</small></a>
    <a class="ficha f-hecho" href="#listas"><b>{$nHecho}</b><span>Hechas</span><small>de {$total} tareas de Wings</small></a>
  </div>
  <div class="barra">{$barra}</div>
  <div class="leyenda">
    <span><i class="s-hecho"></i>Hecho {$nHecho}</span><span><i class="s-marcha"></i>En marcha {$nMarcha}</span>
    <span><i class="s-vos"></i>Te toca a vos {$nVos}</span><span><i class="s-resto"></i>Sin empezar {$nResto}</span>
  </div>

  <section id="vos">
    <h2><i class="s-vos"></i>Te toca a vos <span class="n">{$nVos}</span></h2>
    <div class="grilla">{$vos}</div>
  </section>

  <section id="marcha">
    <h2><i class="s-marcha"></i>En marcha <span class="n">{$nMarcha}</span></h2>
    <div class="carriles">{$carriles}</div>
    {$huerfanas}
  </section>

  <section id="listas">
    <h2><i class="s-resto"></i>Lo demás</h2>
    {$listas}
  </section>
</main>
<script>
{$js}
</script>
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
    $mejoras = array_map(fn ($t) => $t['id'] . ($t['prioridad'] !== '' ? ' (' . mb_strtolower(TABLERO_PRIORIDADES[$t['prioridad']]) . ')' : ''), $g['mejoras_futuras']);
    $md .= '## Sin empezar (' . count($sinEmpezar) . ")\n\n" . ($sinEmpezar ? implode(', ', $sinEmpezar) : 'Nada') . ".\n\n"
        . '## Mejoras futuras, no se empiezan ahora (' . count($mejoras) . ")\n\n" . ($mejoras ? implode(', ', $mejoras) : 'Nada') . ".\n\n"
        . '## De otros proyectos, no es pendiente de Wings (' . count($g['otros_proyectos']) . ")\n\n"
        . ($g['otros_proyectos'] ? implode(', ', array_map(fn ($t) => $t['id'] . ' ' . $t['titulo'], $g['otros_proyectos'])) : 'Nada') . ".\n\n"
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
/**
 * Aplica un cambio a una tarea. Devuelve null si no existe. Al cerrar, guarda el dia del cierre
 * y la pelota no la tiene nadie; al reabrir, borra la fecha de cierre. Cambiar otro dato de algo
 * cerrado no toca su fecha de cierre.
 */
function tablero_cambiar(array $tareas, string $id, array $cambios, string $hoy): ?array
{
    $hallada = false;
    foreach ($tareas as &$t) {
        if ($t['id'] !== $id) {
            continue;
        }
        $hallada = true;
        $antes = $t['estado'];
        $t = array_merge($t, $cambios, ['fecha' => $hoy]);
        if ($t['estado'] === 'cerrado' && $antes !== 'cerrado') {
            $t['cerrado_el'] = $hoy;
            $t['tiene'] = $cambios['tiene'] ?? 'nadie';
            $t['paso'] = $cambios['paso'] ?? '';
        } elseif ($t['estado'] !== 'cerrado') {
            $t['cerrado_el'] = '';
        }
    }
    unset($t);

    return $hallada ? $tareas : null;
}
/** Como figura el estado de un defecto en DEFECTOS.md y DEFECTOS.html. Sale del tablero, nunca a mano. */
function tablero_estado_defecto(array $t): string
{
    $fecha = substr($t['cerrado_el'], 8, 2) . '/' . substr($t['cerrado_el'], 5, 2);
    if ($t['estado'] === 'cerrado') {
        return 'CERRADO ' . $fecha . ($t['verifica'] !== '' ? ', verificó ' . $t['verifica'] : '');
    }

    return TABLERO_ESTADOS[$t['estado']] . ($t['tiene'] !== 'nadie' ? ', la tiene ' . $t['tiene'] : '');
}

/** El avance de los defectos, en una frase. */
function tablero_frase_avance(array $tareas): array
{
    $av = tablero_avance($tareas);
    $fecha = tablero_ultimo_cambio($tareas);
    $abiertos = $av['total'] - $av['cerrados'];

    return [$fecha, $av['cerrados'], $av['total'], $abiertos, count($av['frenan'])];
}

/**
 * DEFECTOS.md con el estado tomado del tablero: el encabezado de cada defecto y el avance.
 * El texto de cada defecto (que pasa, por que importa, donde) se sigue escribiendo a mano.
 */
function tablero_defectos_md(array $tareas, string $actual): string
{
    $porId = array_column(array_filter($tareas, 'tablero_es_defecto'), null, 'id');
    $md = preg_replace_callback('/^### ([AB]\d+)\.\s*(.*)$/mu', function ($m) use ($porId) {
        $t = $porId[$m[1]] ?? null;
        if ($t === null) {
            return $m[0];
        }
        // Un cerrado que ya dice CERRADO conserva su encabezado: guarda matices que el tablero no tiene
        // (aprobo Carlos, verificaron dos, cerrado por decision). Lo abierto se reescribe siempre.
        if ($t['estado'] === 'cerrado' && str_contains(mb_strtoupper($m[2]), 'CERRADO')) {
            return $m[0];
        }
        $titulo = trim(explode(' · ', $m[2])[0]);

        return '### ' . $m[1] . '. ' . $titulo . ($t['gravedad'] !== '' ? ' · ' . $t['gravedad'] : '') . ' · ' . tablero_estado_defecto($t);
    }, $actual);

    [$fecha, $cerrados, $total, $abiertos, $frenan] = tablero_frase_avance($tareas);
    $bloque = TABLERO_MARCA_INICIO . "\n> **Avance al {$fecha}: {$cerrados} cerrados de {$total}.** Quedan {$abiertos} abiertos, de los cuales **{$frenan} frenan**.\n"
        . "> El estado de cada defecto sale del tablero (`docs/00-estado/tareas.json`) y se cambia con `scripts/tablero`; no se edita a mano.\n"
        . TABLERO_MARCA_FIN;
    $patron = '/' . preg_quote(TABLERO_MARCA_INICIO, '/') . '.*?' . preg_quote(TABLERO_MARCA_FIN, '/') . '/su';
    if (! preg_match($patron, $md)) {
        throw new RuntimeException(TABLERO_DEFECTOS . ' no tiene las marcas del tablero.');
    }

    return preg_replace_callback($patron, fn () => $bloque, $md);
}

/** DEFECTOS.html con el estado tomado del tablero: tilde, sello y etiqueta de cada defecto, y el avance. */
function tablero_defectos_html(array $tareas, string $actual): string
{
    $porId = array_column(array_filter($tareas, 'tablero_es_defecto'), null, 'id');
    $lineas = explode("\n", $actual);
    foreach ($lineas as &$l) {
        if (! preg_match('/<input id="([AB]\d+)" type="checkbox"( checked)?>/', $l, $m) || ! isset($porId[$m[1]])) {
            continue;
        }
        $t = $porId[$m[1]];
        $cerrado = $t['estado'] === 'cerrado';
        if ($cerrado && ($m[2] ?? '') === ' checked' && str_contains($l, '<span class="tag done">')) {
            continue; // ya figura cerrado: conserva su sello escrito
        }
        $l = str_replace($m[0], '<input id="' . $m[1] . '" type="checkbox"' . ($cerrado ? ' checked' : '') . '>', $l);
        $l = preg_replace('#\s*<span class="sello">.*?</span>#u', '', $l, 1);
        $sello = ' <span class="sello">' . tablero_e(tablero_estado_defecto($t)) . '</span>';
        $l = preg_replace('#(<span class="id">' . $m[1] . '</span>.*?)(<span class="detail">|</label>)#u', '$1' . $sello . '$2', $l, 1);
        $etiqueta = $cerrado ? '<span class="tag done">Cerrado</span>' : '<span class="tag alta">' . tablero_e(TABLERO_ESTADOS[$t['estado']]) . '</span>';
        $l = preg_replace('#<span class="tag [a-z]+">[^<]*</span></div>\s*$#u', $etiqueta . '</div>', $l, 1);
    }
    unset($l);

    [$fecha, $cerrados, $total, $abiertos, $frenan] = tablero_frase_avance($tareas);
    $html = implode("\n", $lineas);
    $html = preg_replace(
        '#<b>Avance al [^<]*</b>.*?<b>\d+ frenan</b>\.#u',
        "<b>Avance al {$fecha}: {$cerrados} cerrados de {$total}.</b> Quedan {$abiertos} abiertos; <b>{$frenan} frenan</b>.",
        $html,
        1,
        $hechos
    );
    if ($hechos !== 1) {
        throw new RuntimeException(TABLERO_DEFECTOS_HTML . ' no tiene la línea de avance.');
    }

    return $html;
}

/**
 * Todo lo que se genera desde tareas.json: ruta => contenido. Lo usa el guardado y la prueba
 * que comprueba que ninguno de estos archivos se edito a mano.
 *
 * @return array<string,string>
 */
function tablero_generados(array $tareas): array
{
    $tareas = array_map('tablero_normalizar', $tareas);
    $leer = fn (string $ruta) => file_get_contents(tablero_ruta($ruta));

    return [
        TABLERO_DATOS => tablero_texto_datos($tareas),
        TABLERO_HTML => tablero_html($tareas),
        TABLERO_HOJA => tablero_hoja($tareas, $leer(TABLERO_HOJA)),
        TABLERO_DEFECTOS => tablero_defectos_md($tareas, $leer(TABLERO_DEFECTOS)),
        TABLERO_DEFECTOS_HTML => tablero_defectos_html($tareas, $leer(TABLERO_DEFECTOS_HTML)),
    ];
}

function tablero_guardar_y_generar(array $tareas): void
{
    foreach (tablero_generados($tareas) as $ruta => $contenido) {
        file_put_contents(tablero_ruta($ruta), $contenido);
    }
}

/**
 * Lee los titulos de DEFECTOS.md. Sirvio para el arranque (importar). Desde el 16/10 (T3)
 * el estado de esos titulos sale del tablero, no al reves.
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
