<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Writer;

// 1. Cargar el padrón base de 90 filas
$reader = new Reader();
$ssBase = $reader->load('docs/06-pruebas/PRU-03/PADRON-PRU-03.xlsx');
$sh = $ssBase->getSheetByName('Alumnos');

// Verificar última fila con datos
$lastRow = 91; // Encabezado en 1 + 90 filas = 91

// Definir los 10 alumnos nuevos inventados cumpliendo los requisitos:
// - dos al día:
//   1. Julieta Morales (DNI 58100911, Patín Principiantes, 5 años, nac 12/03/2021, ing 10/03/2026) -> Al día. Tutor: Morales Esteban 1145129901
//   2. Facundo Rios (DNI 57200912, Fútbol Principiantes, 6 años, nac 05/08/2020, ing 15/03/2026) -> Al día. Tutor: Rios Mariana 1145129902
// - uno con un mes:
//   3. Renata Cabrera (DNI 55100913, Patín Intermedias, 8 años, nac 22/11/2017, ing 10/04/2025) -> Deuda 102026: 43000. Tutor: Cabrera Jorge 1145129903
// - uno con tres meses:
//   4. Joaquin Vega (DNI 51200914, Fútbol Avanzadas, 12 años, nac 14/06/2014, ing 05/03/2024) -> Deuda 082026: 48000, 092026: 48000, 102026: 48000. Tutor: Vega Andrea 1145129904
// - uno con un mes de 2025:
//   5. Alma Navarro (DNI 53100915, Patín Intermedias, 10 años, nac 30/01/2016, ing 10/08/2024) -> Deuda 112025: 25000, 102026: 33000. Tutor: Navarro Claudia 1145129905
// - uno con pago parcial:
//   6. Bautista Ferrari (DNI 54200916, Fútbol Principiantes, 9 años, nac 18/09/2017, ing 12/03/2025) -> Deuda 102026: 15000 (remanente de cuota 35000). Tutor: Ferrari Martin 1145129906
// - dos hermanos:
//   7. Martina Castro (DNI 56100917, Patín Principiantes, 7 años, nac 09/04/2019, ing 15/03/2026) -> Debe inscrip: Sí. Deuda: No. Tutor: Castro Gabriel 1145129907
//   8. Mateo Castro (DNI 54200918, Fútbol Principiantes, 9 años, nac 11/10/2016, ing 15/03/2026) -> Debe inscrip: Sí. Deuda: 102026: 28000. Tutor: Castro Gabriel 1145129907
// - un mayor de edad:
//   9. Tomas Gimenez (DNI 45100919, Fútbol Avanzadas, 20 años, nac 15/02/2006, ing 10/03/2023) -> Al día. Sin tutor.
// - uno que debe inscripción:
//   10. Paulina Silva (DNI 57100920, Patín Principiantes, 6 años, nac 20/07/2020, ing 01/09/2026) -> Debe inscrip: Sí. Deuda 102026: 30000. Tutor: Silva Veronica 1145129908

$nuevos10 = [
    ['58100911', 'Morales', 'Julieta', '12/03/2021', '10/03/2026', '1145129901', 'esteban.morales@testmail.com', 'Esteban Morales', '1145129901', 'Patín', 'Principiantes', 'Patín / Principiantes / 1 clases', 'No', 'No', []],
    ['57200912', 'Rios', 'Facundo', '05/08/2020', '15/03/2026', '1145129902', 'mariana.rios@testmail.com', 'Mariana Rios', '1145129902', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'No', 'No', []],
    ['55100913', 'Cabrera', 'Renata', '22/11/2017', '10/04/2025', '1145129903', 'jorge.cabrera@testmail.com', 'Jorge Cabrera', '1145129903', 'Patín', 'Intermedias', 'Patín / Intermedias / 2 clases', 'No', 'Sí', [['102026', 43000]]],
    ['51200914', 'Vega', 'Joaquin', '14/06/2014', '05/03/2024', '1145129904', 'andrea.vega@testmail.com', 'Andrea Vega', '1145129904', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'Sí', [['082026', 48000], ['092026', 48000], ['102026', 48000]]],
    ['53100915', 'Navarro', 'Alma', '30/01/2016', '10/08/2024', '1145129905', 'claudia.navarro@testmail.com', 'Claudia Navarro', '1145129905', 'Patín', 'Intermedias', 'Patín / Intermedias / 1 clases', 'No', 'Sí', [['112025', 25000], ['102026', 33000]]],
    ['54200916', 'Ferrari', 'Bautista', '18/09/2017', '12/03/2025', '1145129906', 'martin.ferrari@testmail.com', 'Martin Ferrari', '1145129906', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 2 clases', 'No', 'Sí', [['102026', 15000]]],
    ['56100917', 'Castro', 'Martina', '09/04/2019', '15/03/2026', '1145129907', 'gabriel.castro@testmail.com', 'Gabriel Castro', '1145129907', 'Patín', 'Principiantes', 'Patín / Principiantes / 1 clases', 'Sí', 'No', []],
    ['54200918', 'Castro', 'Mateo', '11/10/2016', '15/03/2026', '1145129907', 'gabriel.castro@testmail.com', 'Gabriel Castro', '1145129907', 'Fútbol', 'Principiantes', 'Fútbol / Principiantes / 1 clases', 'Sí', 'Sí', [['102026', 28000]]],
    ['45100919', 'Gimenez', 'Tomas', '15/02/2006', '10/03/2023', '1145129909', 'tomas.g@testmail.com', '', '', 'Fútbol', 'Avanzadas', 'Fútbol / Avanzadas / 2 clases', 'No', 'No', []],
    ['57100920', 'Silva', 'Paulina', '20/07/2020', '01/09/2026', '1145129908', 'veronica.silva@testmail.com', 'Veronica Silva', '1145129908', 'Patín', 'Principiantes', 'Patín / Principiantes / 1 clases', 'Sí', 'Sí', [['102026', 30000]]]
];

// Insertar las 10 filas nuevas en el Excel
$currRow = 92;
foreach ($nuevos10 as $item) {
    for ($c = 0; $c <= 13; $c++) {
        $val = $item[$c];
        if ($val !== '') {
            $sh->setCellValue([$c + 1, $currRow], $val);
        }
    }
    // Pares de periodos
    $idx = 15;
    foreach ($item[14] as $par) {
        $sh->setCellValue([$idx, $currRow], $par[0]);
        $sh->setCellValue([$idx + 1, $currRow], $par[1]);
        $idx += 2;
    }
    $currRow++;
}

// Guardar padrón v2 (100 alumnos limpios)
$writer = new Writer($ssBase);
$v2Path = 'docs/06-pruebas/PRU-03/PADRON-PRU-03-v2.xlsx';
$writer->save($v2Path);
echo "Padrón final de 100 alumnos guardado en: $v2Path" . PHP_EOL;

// 2. Ahora generar la versión con los 12 ERRORES (incluyendo la FILA REPETIDA)
// Tomamos el padrón de 100 y le introducimos los 12 errores para la prueba B:
// 1. Fecha texto raro en fila 4 (D4)
// 2. Menor sin tutor en fila 9 (H9 e I9 vacíos)
// 3. Plan ajeno al grupo en fila 13 (L13 plan de fútbol para patín)
// 4. "Tiene deuda: Sí" sin meses en fila 16 (N16 Sí, O16 y P16 vacíos)
// 5. Mismo mes dos veces en fila 22 (Q22 y O22 con 102026)
// 6. Monto con signo $ en fila 27 (P27 = "$22.000")
// 7. Monto cero en fila 30 (P30 = 0)
// 8. Celular vacío en fila 35 (F35 = "")
// 9. Deporte con minúsculas y sin acento en fila 40 (J40 = "patin")
// 10. Mes inexistente en fila 45 (O45 = "132026")
// 11. Fecha de ingreso futura en fila 50 (E50 = "15/12/2026")
// 12. ERROR 12: FILA REPETIDA (Copiar la fila 95 idéntica en la fila 102: mismo DNI y deporte que fila 95!)

$ssErr = $reader->load($v2Path);
$shErr = $ssErr->getSheetByName('Alumnos');

$shErr->setCellValue('D4', '15 de mayo del 15');
$shErr->setCellValue('H9', '');
$shErr->setCellValue('I9', '');
$shErr->setCellValue('L13', 'Fútbol / Principiantes / 1 clases');
$shErr->setCellValue('N16', 'Sí');
$shErr->setCellValue('O16', '');
$shErr->setCellValue('P16', '');
$shErr->setCellValue('Q22', '102026');
$shErr->setCellValue('R22', 50000);
$shErr->setCellValue('P27', '$22.000');
$shErr->setCellValue('P30', 0);
$shErr->setCellValue('F35', '');
$shErr->setCellValue('J40', 'patin');
$shErr->setCellValue('N45', 'Sí');
$shErr->setCellValue('O45', '132026');
$shErr->setCellValue('P45', 30000);
$shErr->setCellValue('E50', '15/12/2026');

// Fila 102: Duplicado exacto de fila 95 (Bautista Ferrari, Fútbol Principiantes, DNI 54200916)
for ($col = 1; $col <= 38; $col++) {
    $shErr->setCellValue([$col, 102], $shErr->getCell([$col, 97])->getValue());
}

$writerErr = new Writer($ssErr);
$errPath = 'docs/06-pruebas/PRU-03/PADRON-PRU-03-v2-con-12-errores.xlsx';
$writerErr->save($errPath);
echo "Padrón con 12 errores guardado en: $errPath" . PHP_EOL;
