<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$reader = new Xlsx();
$ss = $reader->load('docs/06-pruebas/PRU-03/plantilla-original.xlsx');

echo "Hojas: " . implode(', ', $ss->getSheetNames()) . PHP_EOL;

foreach ($ss->getSheetNames() as $name) {
    $sheet = $ss->getSheetByName($name);
    echo "--- Hoja: $name (filas: {$sheet->getHighestRow()}, cols: {$sheet->getHighestColumn()}) ---" . PHP_EOL;
}

$alumnos = $ss->getSheetByName('Alumnos');
echo PHP_EOL . "Encabezados en Alumnos:" . PHP_EOL;
for ($col = 1; $col <= 40; $col++) {
    $val = $alumnos->getCell([$col, 1])->getValue();
    if ($val !== null && $val !== '') {
        $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        echo "Col $col ($coord): $val" . PHP_EOL;
    }
}

$catalogos = $ss->getSheetByName('Catálogos');
echo PHP_EOL . "Contenido en Catálogos (primeras 20 filas):" . PHP_EOL;
for ($r = 1; $r <= 20; $r++) {
    $rowVals = [];
    for ($c = 1; $c <= 10; $c++) {
        $v = $catalogos->getCell([$c, $r])->getValue();
        if ($v !== null && $v !== '') $rowVals[] = "$c: $v";
    }
    if (!empty($rowVals)) echo "Fila $r: " . implode(' | ', $rowVals) . PHP_EOL;
}

$guia = $ss->getSheetByName('Guía');
echo PHP_EOL . "Contenido en Guía (primeras 20 filas):" . PHP_EOL;
for ($r = 1; $r <= 25; $r++) {
    $rowVals = [];
    for ($c = 1; $c <= 6; $c++) {
        $v = $guia->getCell([$c, $r])->getValue();
        if ($v !== null && $v !== '') $rowVals[] = $v;
    }
    if (!empty($rowVals)) echo "Fila $r: " . implode(' | ', $rowVals) . PHP_EOL;
}
