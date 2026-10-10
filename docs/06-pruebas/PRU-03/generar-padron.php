<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Writer;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$reader = new Reader();
$ss = $reader->load('docs/06-pruebas/PRU-03/plantilla-original.xlsx');
$alumnosSheet = $ss->getSheetByName('Alumnos');

// Limpiar filas de plantilla si tienen datos de ejemplo (la plantilla descargada tiene 201 filas vacías con validación)
// Construiremos 90 filas exactamente.

// Grupos y planes disponibles en catálogo:
// Patín:
// - Principiantes: "Patín / Principiantes / 1 clases" (30000), "Patín / Principiantes / 2 clases" (40000)
// - Intermedias: "Patín / Intermedias / 1 clases" (33000), "Patín / Intermedias / 2 clases" (43000)
// - Avanzadas: "Patín / Avanzadas / 1 clases" (35000), "Patín / Avanzadas / 2 clases" (45000)
// - Federadas: "Patín / Federadas / 1 clases" (40000), "Patín / Federadas / 2 clases" (50000)
// Fútbol:
// - Principiantes: "Fútbol / Principiantes / 1 clases" (28000), "Fútbol / Principiantes / 2 clases" (35000)
// - Avanzadas: "Fútbol / Avanzadas / 1 clases" (38000), "Fútbol / Avanzadas / 2 clases" (48000)

// Familias con hermanos (6 familias):
// Familia 1: Gomez (Sofia en Patin Princ, Lucas en Futbol Princ) -> Tutor: Gomez Marcelo 1145123401
// Familia 2: Rodriguez (Mia en Patin Interm, Joaquin en Futbol Princ, Mateo en Futbol Avanz) -> Tutor: Rodriguez Laura 1145123402
// Familia 3: Fernandez (Emma en Patin Avanz, Benjamin en Futbol Avanz) -> Tutor: Fernandez Carlos 1145123403
// Familia 4: Lopez (Martina en Patin Princ, Valentina en Patin Princ) -> Tutor: Lopez Mariana 1145123404
// Familia 5: Diaz (Catalina en Patin Federadas, Thiago en Futbol Avanz) -> Tutor: Diaz Roberto 1145123405
// Familia 6: Martinez (Delfina en Patin Interm, Bautista en Futbol Princ) -> Tutor: Martinez Patricia 1145123406

// Alumna en dos deportes:
// Camila Benitez (DNI 54100200) -> Fila en Patín Avanzadas Y Fila en Fútbol Principiantes!

$filas = [];

// Helper para fecha en formato DD/MM/AAAA
function fFecha($d, $m, $y) {
    return sprintf('%02d/%02d/%04d', $d, $m, $y);
}

// Generador de datos
$rowsData = [];

// Definimos la estructura de las 90 filas:
// 60 Patín:
// - Principiantes: 22 nenas (edades 5 a 9 años, nacimiento 2017 a 2021)
// - Intermedias: 18 nenas (edades 9 a 13 años, nacimiento 2013 a 2017)
// - Avanzadas: 12 nenas/chicas (edades 13 a 17 años, más 2 mayores de 18: nacimientos 2007-2013, y 2 de 2005-2006)
// - Federadas: 8 nenas/chicas (edades 14 a 17 años, más 2 mayores de 18: nacimientos 2005-2012)
// Total Patín = 22 + 18 + 12 + 8 = 60

// 30 Fútbol:
// - Principiantes: 18 nenes (edades 5 a 11 años, nacimientos 2015 a 2021)
// - Avanzadas: 12 nenes/chicos (edades 12 a 17 años, más 2 mayores de 18: nacimientos 2005 a 2014)
// Total Fútbol = 18 + 12 = 30
// Total General = 90 filas.

$paisesNombresMujer = [
    'Emma', 'Mia', 'Sofia', 'Martina', 'Valentina', 'Catalina', 'Delfina', 'Julieta', 'Zoe', 'Camila',
    'Lucia', 'Helena', 'Olivia', 'Guadalupe', 'Victoria', 'Renata', 'Emilia', 'Alma', 'Juana', 'Paulina',
    'Bianca', 'Pilar', 'Clara', 'Morena', 'Abril', 'Milagros', 'Micaela', 'Agustina', 'Lola', 'Josefina',
    'Malena', 'Constanza', 'Paula', 'Candela', 'Berenice', 'Florencia', 'Rocio', 'Sol', 'Carla', 'Melina'
];

$paisesNombresVaron = [
    'Liam', 'Mateo', 'Benicio', 'Joaquin', 'Bautista', 'Thiago', 'Lucas', 'Felipe', 'Santino', 'Benjamin',
    'Agustin', 'Lautaro', 'Facundo', 'Ignacio', 'Bruno', 'Tomas', 'Manuel', 'Santiago', 'Nicolas', 'Gael',
    'Valentin', 'Leon', 'Simon', 'Franco', 'Julian', 'Maximo', 'Tobias', 'Dante', 'Lorenzo', 'Ramiro'
];

$apellidos = [
    'Gonzalez', 'Rodriguez', 'Gomez', 'Fernandez', 'Lopez', 'Diaz', 'Martinez', 'Perez', 'Garcia', 'Sanchez',
    'Romero', 'Sosa', 'Alvarez', 'Torres', 'Ruiz', 'Ramirez', 'Flores', 'Acosta', 'Benitez', 'Medina',
    'Herrera', 'Suarez', 'Castro', 'Gimenez', 'Gutierrez', 'Pereyra', 'Ramos', 'Nuñez', 'Iglesias', 'Rossi',
    'Silva', 'Molina', 'Ortiz', 'Morales', 'Rios', 'Ferrari', 'Navarro', 'Cabrera', 'Ledesma', 'Vega'
];

// Asignación estructurada y reproducible de las 90 filas
require_once __DIR__ . '/definir-padron-datos.php';

echo "Total filas construidas: " . count($padronFilas) . PHP_EOL;

// Escribir en la hoja Alumnos
$rowNum = 2;
foreach ($padronFilas as $f) {
    for ($col = 1; $col <= count($f); $col++) {
        $val = $f[$col - 1];
        if ($val !== null && $val !== '') {
            $alumnosSheet->setCellValue([$col, $rowNum], $val);
        }
    }
    $rowNum++;
}

// Guardar PADRON-PRU-03.xlsx
$writer = new Writer($ss);
$padronPath = 'docs/06-pruebas/PRU-03/PADRON-PRU-03.xlsx';
$writer->save($padronPath);
echo "Padrón guardado exitosamente en: $padronPath" . PHP_EOL;
