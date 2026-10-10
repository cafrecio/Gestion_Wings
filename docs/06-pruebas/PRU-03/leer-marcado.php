<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$reader = new Xlsx();
$ssErr = $reader->load('docs/06-pruebas/PRU-03/PADRON-PRU-03-con-errores.xlsx');
$shErr = $ssErr->getSheetByName('Alumnos');

echo "En PADRON-PRU-03-con-errores.xlsx:" . PHP_EOL;
echo "Fila 40 (J40 Deporte): '" . $shErr->getCell('J40')->getValue() . "' | Grupo (K40): '" . $shErr->getCell('K40')->getValue() . "' | Plan (L40): '" . $shErr->getCell('L40')->getValue() . "'" . PHP_EOL;
echo "Fila 50 (E50 Ingreso): '" . $shErr->getCell('E50')->getValue() . "' | Nacimiento (D50): '" . $shErr->getCell('D50')->getValue() . "'" . PHP_EOL;

$ssMarc = $reader->load('docs/06-pruebas/PRU-03/PADRON-PRU-03-marcado-errores.xlsx');
$shMarc = $ssMarc->getSheetByName('Alumnos');
echo PHP_EOL . "En PADRON-PRU-03-marcado-errores.xlsx:" . PHP_EOL;
echo "Fila 40 AM: '" . $shMarc->getCell('AM40')->getValue() . "'" . PHP_EOL;
echo "Fila 50 AM: '" . $shMarc->getCell('AM50')->getValue() . "'" . PHP_EOL;

