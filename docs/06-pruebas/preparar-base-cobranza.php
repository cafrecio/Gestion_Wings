<?php

/**
 * SCRIPT DE UN SOLO USO — preparar la base de prueba de cobranza.
 *
 * No es parte del sistema ni del importador de deuda. Resuelve algo que Wings
 * no va a resolver solo: dejar la base de `wings_test` con alumnos repartidos
 * en los estados de cobranza reales para poder probarlos.
 *
 * Hace dos cosas:
 *
 *  1. Crea un PAGO DE APERTURA en cero para los alumnos que ya venian del club.
 *     Wings decide "deudor" preguntando "¿pago alguna vez aca?", y sin este
 *     asiento los 60 figuran deudores aunque no deban un peso. El pago es de
 *     $0 a proposito: no es plata cobrada, es la marca de que el alumno tiene
 *     historia. Queda dicho en observaciones. No pasa por caja ni por cashflow
 *     — `pagos` no tiene relacion con caja; es el movimiento el que apunta al
 *     pago, y estos no tienen movimiento.
 *
 *  2. Genera el Excel de deuda para cargar con el importador de Codex, que no
 *     se toca: sigue siendo la herramienta que va a usar el cliente.
 *
 * Uso: php artisan tinker --execute="require 'docs/06-pruebas/preparar-base-cobranza.php';"
 */

use App\Models\Pago;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$destino = 'C:/xampp/htdocs/gestion-wings/docs/06-pruebas/';

// ── Los alumnos, con su plan y su precio ─────────────────────────────────────

$alumnos = DB::table('alumnos as a')
    ->join('deportes as d', 'd.id', '=', 'a.deporte_id')
    ->join('alumno_planes as ap', function ($j) {
        $j->on('ap.alumno_id', '=', 'a.id')->where('ap.activo', 1);
    })
    ->join('grupo_planes as gp', 'gp.id', '=', 'ap.plan_id')
    ->orderBy('a.fecha_alta')->orderBy('a.id')
    ->selectRaw('a.id, a.dni, a.nombre, a.apellido, d.nombre deporte,
                 gp.precio_mensual precio, a.fecha_alta')
    ->get();

if (count($alumnos) !== 60) {
    throw new RuntimeException('Se esperaban 60 alumnos con plan activo, hay ' . count($alumnos));
}

// ── El reparto ───────────────────────────────────────────────────────────────
//
// `pago_hasta` es el ultimo mes que el alumno tenia cancelado cuando el club
// entro a Wings. De ahi sale el pago de apertura. `debe` son los meses que
// quedaron impagos, que van al Excel.
//
// El unico sin pago de apertura es el alumno nuevo: entro y todavia no pago.
// En un club de verdad eso dura poco — si no paga no entrena — por eso es uno
// solo y no un grupo.

$reparto = [
    // 12 AL DIA: pagaron septiembre, no deben nada.
    'al_dia' => ['cantidad' => 12, 'pago_hasta' => [9, 2026], 'debe' => []],

    // 12 EN PLAZO: pagaron agosto, deben septiembre. El dia 11 pasan a morosos.
    'en_plazo' => ['cantidad' => 12, 'pago_hasta' => [8, 2026], 'debe' => ['092026']],

    // 21 DEUDOR de dos meses: el atraso comun.
    'dos_meses' => ['cantidad' => 21, 'pago_hasta' => [7, 2026], 'debe' => ['082026', '092026']],

    // 8 DEUDOR de tres meses: los que ya dejaron de venir y el sistema todavia
    // no lo sabe. Son los que la pantalla de Revision tendria que levantar.
    'tres_meses' => ['cantidad' => 8, 'pago_hasta' => [6, 2026], 'debe' => ['072026', '082026', '092026']],

    // 4 DEUDOR con deuda de 2025: para que el FIFO tenga que cruzar de anio.
    // El monto de 2025 es menor al plan de hoy: la cuota vieja se genero con el
    // precio viejo, y asi se comprueba que el importador no recalcula.
    'deuda_2025' => ['cantidad' => 4, 'pago_hasta' => null, 'debe' => 'VIEJA'],

    // 2 para condonar durante la prueba.
    'condonar' => ['cantidad' => 2, 'pago_hasta' => null, 'debe' => 'VIEJA'],

    // 1 ALUMNO NUEVO: sin pago de apertura, debe su primera cuota.
    'nuevo' => ['cantidad' => 1, 'pago_hasta' => null, 'debe' => ['092026']],
];

// ── Asignacion ───────────────────────────────────────────────────────────────
//
// Los grupos que necesitan alta vieja se sirven primero, desde los alumnos mas
// antiguos. El alumno nuevo sale del ultimo en darse de alta.

$porFecha = $alumnos->values();
$nuevo    = $porFecha->last();
$resto    = $porFecha->slice(0, count($porFecha) - 1)->values();

$asignados = [];
$i = 0;

// Deuda de 2025 y condonar: los seis mas antiguos, que son los unicos que
// pueden deber un mes de 2025 sin que sea anterior a su alta.
foreach (['deuda_2025' => 4, 'condonar' => 2] as $grupo => $n) {
    for ($k = 0; $k < $n; $k++) {
        $asignados[$grupo][] = $resto[$i++];
    }
}
foreach (['tres_meses' => 8, 'dos_meses' => 21, 'en_plazo' => 12, 'al_dia' => 12] as $grupo => $n) {
    for ($k = 0; $k < $n; $k++) {
        $asignados[$grupo][] = $resto[$i++];
    }
}
$asignados['nuevo'][] = $nuevo;

if ($i !== count($resto)) {
    throw new RuntimeException("Quedaron alumnos sin asignar: {$i} de " . count($resto));
}

// ── Las deudas de 2025, una por alumno, posteriores a su alta ────────────────

$deudasViejas = function ($alumno, bool $paraCondonar): array {
    $alta = \Carbon\Carbon::parse($alumno->fecha_alta);
    $base = $alta->copy()->addMonths(2);           // dos meses despues del alta
    $meses = $paraCondonar ? 1 : 2;

    $cuotas = [];
    $montoViejo = (string) (int) round($alumno->precio * 0.8);   // el precio de 2025
    for ($m = 0; $m < $meses; $m++) {
        $p = $base->copy()->addMonths($m);
        $cuotas[] = [$montoViejo, $p->format('mY')];
    }
    // Ademas el mes corriente, para que se vea que sigue debiendo hoy.
    $cuotas[] = [(string) (int) $alumno->precio, '092026'];

    return $cuotas;
};

// ── Armado de filas del Excel y de los pagos de apertura ────────────────────

$filas         = [];
$pagosApertura = [];
$resumen       = [];

foreach ($reparto as $grupo => $config) {
    foreach ($asignados[$grupo] as $a) {
        $precio = (string) (int) $a->precio;

        if ($config['debe'] === 'VIEJA') {
            $cuotas = $deudasViejas($a, $grupo === 'condonar');
        } else {
            $cuotas = array_map(fn($p) => [$precio, $p], $config['debe']);
        }

        // Pago de apertura: el ultimo mes que tenia cancelado.
        if ($config['pago_hasta']) {
            [$mes, $anio] = $config['pago_hasta'];
        } elseif ($grupo === 'nuevo') {
            $mes = null;
        } else {
            // Deuda vieja: cancelado hasta el mes anterior a la primera impaga.
            $primera = \Carbon\Carbon::createFromFormat('mY d', $cuotas[0][1] . ' 01')->subMonth();
            $mes  = (int) $primera->format('n');
            $anio = (int) $primera->format('Y');
        }

        if ($mes !== null) {
            $fechaPago = \Carbon\Carbon::create($anio, $mes, 5);
            $alta      = \Carbon\Carbon::parse($a->fecha_alta);
            if ($fechaPago->lt($alta)) {
                $fechaPago = $alta->copy();
            }
            $pagosApertura[] = [
                'alumno_id'           => $a->id,
                'plan_id'             => null,
                'regla_primer_pago_id' => null,
                'mes'                 => $mes,
                'anio'                => $anio,
                'monto_base'          => 0,
                'porcentaje_aplicado' => 0,
                'monto_final'         => 0,
                'fecha_pago'          => $fechaPago->toDateString(),
                'observaciones'       => 'Apertura de carga inicial. El alumno ya venia del club y estaba al dia hasta '
                    . str_pad((string) $mes, 2, '0', STR_PAD_LEFT) . '/' . $anio
                    . '. NO representa dinero cobrado por Wings: no tiene movimiento de caja.',
                'estado'              => Pago::ESTADO_COMPLETADO,
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        // Ninguna cuota puede ser anterior al alta.
        $altaYm = substr($a->fecha_alta, 0, 7);
        foreach ($cuotas as [$monto, $periodo]) {
            $ym = substr($periodo, 2, 4) . '-' . substr($periodo, 0, 2);
            if ($ym < $altaYm) {
                throw new RuntimeException("Periodo {$periodo} anterior al alta de {$a->apellido} ({$a->fecha_alta})");
            }
        }

        if ($cuotas) {
            $fila = [$a->dni, $a->deporte];
            foreach ($cuotas as [$monto, $periodo]) {
                $fila[] = $monto;
                $fila[] = $periodo;
            }
            $filas[] = $fila;
        }

        $resumen[] = [
            $a->dni, $a->deporte, $a->apellido . ', ' . $a->nombre, $grupo,
            $mes === null ? 'sin apertura' : 'apertura ' . $mes . '/' . $anio,
            $cuotas ? implode(' ', array_map(fn($c) => $c[1] . ':$' . $c[0], $cuotas)) : 'sin deuda',
        ];
    }
}

// ── Escritura ────────────────────────────────────────────────────────────────

DB::table('pagos')->whereIn('alumno_id', array_column($pagosApertura, 'alumno_id'))
    ->where('monto_final', 0)->delete();          // idempotente: no duplica aperturas
DB::table('pagos')->insert($pagosApertura);

$maxCuotas  = max(array_map(fn($f) => (count($f) - 2) / 2, $filas));
$encabezado = ['DNI', 'deporte'];
for ($i = 0; $i < $maxCuotas; $i++) {
    $encabezado[] = 'monto';
    $encabezado[] = 'mmYYYY';
}

$libro = new Spreadsheet();
$hoja  = $libro->getActiveSheet();
$hoja->setTitle('deuda');
$hoja->fromArray($encabezado, null, 'A1');
$hoja->fromArray($filas, null, 'A2', true);

for ($c = 4; $c <= 2 + $maxCuotas * 2; $c += 2) {
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
    $hoja->getStyle("{$letra}2:{$letra}" . (count($filas) + 1))
        ->getNumberFormat()->setFormatCode('@');
}

(new Xlsx($libro))->save($destino . 'DEUDA-INICIAL-PRUEBA-V2.xlsx');

$texto = "DNI|deporte|alumno|grupo|apertura|deuda\n";
foreach ($resumen as $r) {
    $texto .= implode('|', $r) . "\n";
}
file_put_contents('C:/tmp/reparto-v2.txt', $texto);

echo 'Pagos de apertura creados: ' . count($pagosApertura) . PHP_EOL;
echo 'Filas con deuda en el Excel: ' . count($filas) . PHP_EOL;
echo 'Cuotas a crear: ' . array_sum(array_map(fn($f) => (count($f) - 2) / 2, $filas)) . PHP_EOL;
foreach (array_count_values(array_column($resumen, 3)) as $g => $n) {
    echo "  {$g}: {$n}" . PHP_EOL;
}
