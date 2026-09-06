<?php

/**
 * Genera los dos Excel de deuda inicial para la prueba funcional.
 *
 *  - DEUDA-INICIAL-PRUEBA-V1.xlsx    : el caso valido, 48 alumnos con deuda.
 *  - DEUDA-INICIAL-RECHAZOS-V1.xlsx  : ocho filas que el importador debe rechazar.
 *
 * Los montos salen del precio real del plan activo de cada alumno, leido de la
 * base. La reparticion esta fijada por DNI+deporte, no por posicion: si el
 * seeder cambia el orden, el archivo sale igual.
 *
 * Uso: php artisan tinker --execute="require 'C:/tmp/generar-deuda-inicial.php';"
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;

$destino = 'C:/xampp/htdocs/gestion-wings/docs/06-pruebas/';

// ── Los alumnos, con su plan y su precio ─────────────────────────────────────

$alumnos = DB::table('alumnos as a')
    ->join('deportes as d', 'd.id', '=', 'a.deporte_id')
    ->join('alumno_planes as ap', function ($j) {
        $j->on('ap.alumno_id', '=', 'a.id')->where('ap.activo', 1);
    })
    ->join('grupo_planes as gp', 'gp.id', '=', 'ap.plan_id')
    ->orderBy('d.nombre')->orderBy('a.fecha_alta')->orderBy('a.id')
    ->selectRaw('a.id, a.dni, a.nombre, a.apellido, d.nombre deporte,
                 gp.precio_mensual precio, a.fecha_alta')
    ->get();

if (count($alumnos) !== 60) {
    throw new RuntimeException('Se esperaban 60 alumnos con plan activo, hay ' . count($alumnos));
}

$clave = fn($dni, $deporte) => $dni . '|' . $deporte;

// ── Reparticion, por DNI+deporte ─────────────────────────────────────────────
// Cada grupo dice que demuestra. El detalle esta en DISENO-DEUDA-INICIAL-V1.md.

// Deuda vieja de 2025: prueban que el FIFO cruza de anio. Monto menor al plan
// actual a proposito: la cuota de 2025 se genero con el precio de 2025.
$deudaVieja = [
    '41738592|Patín'  => [['24000', '032025'], ['24000', '042025']], // Arias, alta 01/2025
    '39862471|Patín'  => [['34000', '042025'], ['34000', '052025']], // Cabrera, alta 02/2025
    '41627593|Fútbol' => [['22000', '062025']],                      // Acosta, alta 05/2025
    '39815267|Fútbol' => [['38000', '082025'], ['38000', '092025']], // Barrios, alta 07/2025
];

// Se condonan durante la prueba: la deuda condonada deja de contar como impaga.
$paraCondonar = [
    '42691735|Patín'  => [['28000', '052025']], // Dominguez, alta 03/2025
    '42509736|Fútbol' => [['24000', '112025']], // Cejas, alta 09/2025
];

// Sin deuda: control. Se les cobra septiembre y tienen que quedar AL_DIA.
$sinDeuda = [
    '40518376|Patín', '33456789|Patín', '43827019|Patín', '39281654|Patín',
    '42159308|Patín', '40926713|Patín', '43529704|Patín', '40738195|Patín',
    '35678901|Fútbol', '40468125|Fútbol', '43175682|Fútbol', '39724619|Fútbol',
];

// Tres meses (072026+082026+092026): acumulacion y orden con tres periodos.
$tresMeses = [
    '43385126|Patín', '42370481|Patín', '39647280|Patín', '41863957|Patín',
    '39726845|Patín', '32123456|Patín', '40274518|Patín', '42916483|Patín',
];

// Dos meses (082026+092026): FIFO, tiene que imputar agosto antes que septiembre.
$dosMeses = [
    '43185072|Patín', '39481562|Patín', '41957362|Patín', '36789012|Patín',
    '39562841|Patín', '37890123|Patín', '43029618|Patín', '40389617|Patín',
    '42391857|Fútbol', '40537268|Fútbol', '42865109|Fútbol', '39916473|Fútbol',
    '43370526|Fútbol', '38901234|Fútbol',
];

// El resto debe solo septiembre. Es el caso que pidio Carlos: recien empieza el mes.

// ── Armado de las filas ──────────────────────────────────────────────────────

$filas   = [];
$resumen = [];
$usados  = [];

foreach ($alumnos as $a) {
    $k      = $clave($a->dni, $a->deporte);
    $precio = (string) (int) $a->precio;
    $usados[$k] = true;

    if (isset($deudaVieja[$k])) {
        $grupo  = 'deuda vieja 2025';
        $cuotas = $deudaVieja[$k];
    } elseif (isset($paraCondonar[$k])) {
        $grupo  = 'para condonar';
        $cuotas = $paraCondonar[$k];
    } elseif (in_array($k, $sinDeuda, true)) {
        $resumen[] = [$a->dni, $a->deporte, $a->apellido . ', ' . $a->nombre, 'sin deuda', '-'];
        continue;
    } elseif (in_array($k, $tresMeses, true)) {
        $grupo  = 'tres meses';
        $cuotas = [[$precio, '072026'], [$precio, '082026'], [$precio, '092026']];
    } elseif (in_array($k, $dosMeses, true)) {
        $grupo  = 'dos meses';
        $cuotas = [[$precio, '082026'], [$precio, '092026']];
    } else {
        $grupo  = 'solo septiembre';
        $cuotas = [[$precio, '092026']];
    }

    // Ninguna cuota puede ser anterior al alta del alumno.
    $alta = substr($a->fecha_alta, 0, 7);
    foreach ($cuotas as [$monto, $periodo]) {
        $periodoIso = substr($periodo, 2, 4) . '-' . substr($periodo, 0, 2);
        if ($periodoIso < $alta) {
            throw new RuntimeException(
                "El periodo {$periodo} es anterior al alta ({$a->fecha_alta}) de {$a->apellido} [{$k}]"
            );
        }
    }

    $fila = [$a->dni, $a->deporte];
    foreach ($cuotas as [$monto, $periodo]) {
        $fila[] = $monto;
        $fila[] = $periodo;
    }
    $filas[] = $fila;

    $resumen[] = [
        $a->dni, $a->deporte, $a->apellido . ', ' . $a->nombre, $grupo,
        implode(' ', array_map(fn($c) => $c[1] . ':$' . $c[0], $cuotas)),
    ];
}

// Las claves declaradas tienen que existir todas en la base.
foreach (array_merge(array_keys($deudaVieja), array_keys($paraCondonar), $sinDeuda, $tresMeses, $dosMeses) as $k) {
    if (! isset($usados[$k])) {
        throw new RuntimeException("La clave {$k} no corresponde a ningun alumno con plan activo.");
    }
}

// ── Escritura del Excel valido ───────────────────────────────────────────────

$maxCuotas = max(array_map(fn($f) => (count($f) - 2) / 2, $filas));

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

// El periodo es texto de seis digitos: sin esto Excel lo convierte en numero.
$columnaTexto = function ($hoja, array $columnas, int $ultimaFila) {
    foreach ($columnas as $c) {
        $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
        $hoja->getStyle("{$letra}2:{$letra}{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('@');
    }
};

$columnasPeriodo = [];
for ($c = 4; $c <= 2 + $maxCuotas * 2; $c += 2) {
    $columnasPeriodo[] = $c;
}
$columnaTexto($hoja, $columnasPeriodo, count($filas) + 1);

(new Xlsx($libro))->save($destino . 'DEUDA-INICIAL-PRUEBA-V1.xlsx');

// ── Los ocho rechazos ────────────────────────────────────────────────────────
// Cada fila rompe una regla distinta. El importador valida el archivo completo
// antes de escribir, asi que tiene que informarlas todas de una y no cargar nada.

$primero = $alumnos->first();
$segundo = $alumnos->skip(1)->first();

$rechazos = [
    ['99999999', 'Patín', '30000', '092026', '', '', '', ''],                 // 1 DNI inexistente
    [$primero->dni, 'Hockey', '30000', '092026', '', '', '', ''],             // 2 deporte inexistente
    [$segundo->dni, $segundo->deporte, '0', '092026', '', '', '', ''],        // 3 monto cero
    [$segundo->dni, $segundo->deporte, '-5000', '092026', '', '', '', ''],    // 4 monto negativo
    [$primero->dni, $primero->deporte, '30000', '132026', '', '', '', ''],    // 5 mes 13
    [$primero->dni, $primero->deporte, '30000', '122024', '', '', '', ''],    // 6 anterior a 2025
    [$primero->dni, $primero->deporte, '30000', '', '', '', '', ''],          // 7 par incompleto
    [$segundo->dni, $segundo->deporte, '30000', '092026', '', '', '', ''],    // 8 DNI+deporte repetido
];

$libro2 = new Spreadsheet();
$hoja2  = $libro2->getActiveSheet();
$hoja2->setTitle('rechazos');
$hoja2->fromArray(['DNI', 'deporte', 'monto', 'mmYYYY', 'monto', 'mmYYYY', 'monto', 'mmYYYY'], null, 'A1');
$hoja2->fromArray($rechazos, null, 'A2', true);
$columnaTexto($hoja2, [4, 6, 8], count($rechazos) + 1);
(new Xlsx($libro2))->save($destino . 'DEUDA-INICIAL-RECHAZOS-V1.xlsx');

// ── Resumen para revisar a ojo ───────────────────────────────────────────────

$conteo = array_count_values(array_column($resumen, 3));
$texto  = "DNI|deporte|alumno|grupo|cuotas\n";
foreach ($resumen as $r) {
    $texto .= implode('|', $r) . "\n";
}
file_put_contents('C:/tmp/reparticion-deuda.txt', $texto);

$totalCuotas = array_sum(array_map(fn($f) => (count($f) - 2) / 2, $filas));
$totalPesos  = 0;
foreach ($filas as $f) {
    for ($i = 2; $i < count($f); $i += 2) {
        $totalPesos += (int) $f[$i];
    }
}

echo "Alumnos leidos: " . count($alumnos) . PHP_EOL;
echo "Filas en el Excel valido: " . count($filas) . PHP_EOL;
echo "Cuotas a crear: {$totalCuotas}" . PHP_EOL;
echo "Total en pesos: $" . number_format($totalPesos, 0, ',', '.') . PHP_EOL;
foreach ($conteo as $grupo => $n) {
    echo "  {$grupo}: {$n}" . PHP_EOL;
}
echo "Filas de rechazo: " . count($rechazos) . PHP_EOL;
