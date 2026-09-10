<?php

namespace App\Console\Commands;

use App\Models\Alumno;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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

        $hoja = (new Spreadsheet())->getActiveSheet();
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

        $directorio = dirname($archivo);
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            $this->error("No se pudo crear el directorio {$directorio}.");
            return self::FAILURE;
        }

        (new Xlsx($hoja->getParent()))->save($archivo);

        $this->info("Padron exportado: {$alumnos->count()} alumno(s) en {$archivo}.");
        $this->line('Completar DEBE con SI o NO en cada fila. Si dice SI, cargar los pares periodo (mmYYYY) y monto.');

        return self::SUCCESS;
    }
}
