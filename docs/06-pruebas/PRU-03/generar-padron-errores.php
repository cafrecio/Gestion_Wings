<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Writer;

$reader = new Reader();
$ss = $reader->load('docs/06-pruebas/PRU-03/PADRON-PRU-03.xlsx');
$sheet = $ss->getSheetByName('Alumnos');

// Introduciremos 11 errores humanos típicos en 11 filas distintas:
// Error 1: Fila 4 (Mia Rodriguez) -> Fecha escrita como texto raro: Fecha nacimiento "15 de mayo del 15"
// Error 2: Fila 9 (Martina Lopez, 6 años) -> Menor sin tutor: Vaciar Tutor y Teléfono tutor (cols H e I)
// Error 3: Fila 13 (Catalina Diaz, Patín / Federadas) -> Plan que no es de ese grupo: Poner plan de fútbol "Fútbol / Principiantes / 1 clases"
// Error 4: Fila 16 (Delfina Martinez) -> "Tiene deuda: Sí" sin ningún mes especificado (cols O y P vacías)
// Error 5: Fila 22 (Berenice Rossi) -> El mismo mes dos veces: Período 1 = 102026, Período 2 = 102026
// Error 6: Fila 27 (Julieta Alvarez) -> Monto con signo $: Monto 1 = "$22.000"
// Error 7: Fila 30 (Felipe Sosa) -> Monto cero: Monto 1 = "0"
// Error 8: Fila 35 (Liam Pereyra) -> Celular vacío (col F vacía)
// Error 9: Fila 40 (Victoria Acosta) -> Deporte escrito a mano con minúsculas y sin acento: "patin"
// Error 10: Fila 45 (Alma Herrera) -> Mes inexistente: Período 1 = "132026", Monto 1 = "30000", Tiene deuda: "Sí"
// Error 11: Fila 50 (Pilar Ramos) -> Fecha de ingreso futura: Fecha ingreso = "15/12/2026"

$sheet->setCellValue('D4', '15 de mayo del 15'); // Error 1: Fecha texto raro

$sheet->setCellValue('H9', '');                 // Error 2: Menor sin tutor
$sheet->setCellValue('I9', '');

$sheet->setCellValue('L13', 'Fútbol / Principiantes / 1 clases'); // Error 3: Plan de otro grupo/deporte

$sheet->setCellValue('N16', 'Sí');               // Error 4: Tiene deuda Sí sin meses
$sheet->setCellValue('O16', '');
$sheet->setCellValue('P16', '');

$sheet->setCellValue('Q22', '102026');           // Error 5: Mismo mes dos veces
$sheet->setCellValue('R22', 50000);

$sheet->setCellValue('P27', '$22.000');          // Error 6: Monto con signo $

$sheet->setCellValue('P30', 0);                  // Error 7: Monto cero

$sheet->setCellValue('F35', '');                 // Error 8: Celular vacío

$sheet->setCellValue('J40', 'patin');            // Error 9: Deporte con minúsculas y sin acento

$sheet->setCellValue('N45', 'Sí');               // Error 10: Mes inexistente 132026
$sheet->setCellValue('O45', '132026');
$sheet->setCellValue('P45', 30000);

$sheet->setCellValue('E50', '15/12/2026');       // Error 11: Fecha de ingreso futura

$writer = new Writer($ss);
$errorPath = 'docs/06-pruebas/PRU-03/PADRON-PRU-03-con-errores.xlsx';
$writer->save($errorPath);
echo "Padrón con 11 errores guardado en: $errorPath" . PHP_EOL;
