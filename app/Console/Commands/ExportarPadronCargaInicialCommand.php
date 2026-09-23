<?php

namespace App\Console\Commands;

use App\Models\Alumno;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta el padron completo para que el club declare el saldo inicial de cada alumno.
 *
 * Sale un alumno por fila, incluidos los que no deben nada. Esa es la diferencia con la
 * carga vieja: antes el que no figuraba se asumia sin deuda, y el olvido era invisible.
 * Aca cada alumno tiene que tener un SI o un NO escrito.
 */
class ExportarPadronCargaInicialCommand extends Command
{
    protected $signature = 'wings:exportar-padron
        {archivo : Ruta del .xlsx a generar}
        {--pares=6 : Cuantos pares periodo/monto dejar disponibles por alumno}';

    protected $description = 'Exporta el padron de alumnos para declarar el saldo inicial';

    public function handle(): int
    {
        $pares = max(1, (int) $this->option('pares'));
        $archivo = (string) $this->argument('archivo');

        $alumnos = Alumno::with('deporte')
            ->where('activo', true)
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        if ($alumnos->isEmpty()) {
            $this->error('No hay alumnos activos para exportar.');
            return self::FAILURE;
        }

        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Padron');

        $encabezado = ['DNI', 'Alumno', 'Deporte', 'DEBE'];
        for ($i = 1; $i <= $pares; $i++) {
            $encabezado[] = "Periodo {$i}";
            $encabezado[] = "Monto {$i}";
        }
        $hoja->fromArray($encabezado, null, 'A1');

        $fila = 2;
        foreach ($alumnos as $alumno) {
            // El DNI va como texto: hay documentos con cero adelante y Excel se los come.
            $hoja->setCellValueExplicit(
                "A{$fila}",
                (string) $alumno->dni,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $hoja->setCellValue("B{$fila}", trim("{$alumno->apellido}, {$alumno->nombre}"));
            $hoja->setCellValue("C{$fila}", $alumno->deporte->nombre ?? '');
            $fila++;
        }

        // Los períodos en texto: si no, Excel convierte 092026 en el número 92026.
        // Los montos quedan como número: en Excel argentino 52.000 se lee 52000.
        $ultimaFila = max(2, $fila - 1);
        for ($i = 1; $i <= $pares; $i++) {
            $columna = Coordinate::stringFromColumnIndex(3 + 2 * $i);
            $hoja->getStyle("{$columna}2:{$columna}{$ultimaFila}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_TEXT);
        }

        $this->escribirInstrucciones($libro, $pares);

        $directorio = dirname($archivo);
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            $this->error("No se pudo crear el directorio {$directorio}.");
            return self::FAILURE;
        }

        (new Xlsx($libro))->save($archivo);

        $this->info("Padron exportado: {$alumnos->count()} alumno(s) en {$archivo}.");
        $this->line('Completar DEBE con SI o NO en cada fila. Si dice SI, cargar los pares periodo (mmYYYY) y monto.');
        $this->line('La hoja Instrucciones explica como se completa; el padron esta en la hoja Padron.');

        return self::SUCCESS;
    }

    /**
     * Hoja de instrucciones dentro del mismo archivo.
     *
     * Va adentro y no en un documento aparte porque el Excel viaja solo: se manda por
     * correo o por WhatsApp, y quien lo completa no abre el repositorio. Queda primera
     * y seleccionada para que se lea al abrir el archivo.
     */
    private function escribirInstrucciones(Spreadsheet $libro, int $pares): void
    {
        $ultimoPar = 3 + 2 * $pares;
        $columnaPeriodo = Coordinate::stringFromColumnIndex($ultimoPar - 1);
        $columnaMonto = Coordinate::stringFromColumnIndex($ultimoPar);

        $lineas = [
            ['Como completar el padron', true],
            ['', false],
            ['Este archivo declara cuanto debe cada alumno el dia que el club arranca con Wings.', false],
            ['El padron esta en la hoja Padron, con un alumno por fila. No borres ni agregues filas,', false],
            ['ni cambies el DNI, el nombre o el deporte: esas columnas las escribio el sistema.', false],
            ['', false],
            ['Paso 1: la columna DEBE', true],
            ['', false],
            ['Todas las filas tienen que decir SI o NO. Ninguna puede quedar vacia.', false],
            ['Dejarla vacia no significa "no debe": el archivo entero se rechaza.', false],
            ['  SI = el alumno debe cuotas. Hay que cargar cuales.', false],
            ['  NO = el alumno esta al dia. Se dejan vacias las columnas de periodo y monto.', false],
            ['', false],
            ['Paso 2: los meses que debe', true],
            ['', false],
            ['Cada mes adeudado usa un par de columnas: Periodo y Monto.', false],
            ['El periodo se escribe con dos digitos de mes y cuatro de anio, todo junto y sin barras.', false],
            ['', false],
            ['  092026 = septiembre de 2026', false],
            ['  102026 = octubre de 2026', false],
            ['  012027 = enero de 2027', false],
            ['', false],
            ['Ejemplo de un alumno que debe septiembre y octubre:', false],
            ['', false],
            ['  DEBE: SI | Periodo 1: 092026 | Monto 1: 52000 | Periodo 2: 102026 | Monto 2: 52000', false],
            ['', false],
            ['Ejemplo de un alumno al dia:', false],
            ['', false],
            ['  DEBE: NO | el resto de la fila vacio', false],
            ['', false],
            ['Paso 3: los montos', true],
            ['', false],
            ['Se aceptan escritos como numero o como texto en formato argentino:', false],
            ['  52000        52.000        1.052.000        52.000,50', false],
            ['', false],
            ['Se rechazan, porque son ambiguos y el sistema no adivina:', false],
            ['  52,000 (coma de miles)    52.5 (punto decimal)    $52000 (con signo)', false],
            ['  cero, negativos y cualquier cosa con letras', false],
            ['', false],
            ['Lo que no hay que hacer', true],
            ['', false],
            ['  No dejar DEBE en blanco.', false],
            ['  No poner SI sin cargar ningun mes.', false],
            ['  No poner NO y dejar montos cargados igual.', false],
            ['  No repetir el mismo periodo dos veces en la misma fila.', false],
            ['  No agregar filas de alumnos que no esten en el padron.', false],
            ['  No borrar la fila del encabezado de la hoja Padron.', false],
            ['', false],
            ["Si un alumno debe mas de {$pares} meses", true],
            ['', false],
            ["La hoja trae {$pares} pares de columnas. Si no alcanzan, se pueden agregar al final,", false],
            ['siempre de a dos y con el encabezado escrito igual que los anteriores:', false],
            ["  despues de {$columnaPeriodo}1 y {$columnaMonto}1 van Periodo ".($pares + 1)." y Monto ".($pares + 1).'.', false],
            ['Agregar una sola de las dos columnas hace que se rechace todo el archivo.', false],
            ['', false],
            ['En esas columnas nuevas Excel se come el cero de adelante y deja 92026 en vez de', false],
            ['092026. No es problema: el sistema lo entiende igual. Lo que si se rechaza es', false],
            ['escribir el mes con barra, como 9/2026, porque Excel lo convierte en una fecha.', false],
            ['', false],
            ['Cuando esta listo', true],
            ['', false],
            ['Se guarda el archivo y se devuelve al administrador.', false],
            ['El sistema lo revisa entero antes de escribir nada: si una sola fila esta mal,', false],
            ['no carga ninguna e informa todos los errores juntos, con el numero de fila.', false],
        ];

        $hoja = $libro->createSheet(0);
        $hoja->setTitle('Instrucciones');
        $hoja->getColumnDimension('A')->setWidth(100);

        foreach ($lineas as $indice => [$texto, $titulo]) {
            $celda = 'A'.($indice + 1);
            $hoja->setCellValue($celda, $texto);
            if ($titulo) {
                $hoja->getStyle($celda)->getFont()->setBold(true);
            }
        }

        $libro->setActiveSheetIndexByName('Instrucciones');
    }
}
