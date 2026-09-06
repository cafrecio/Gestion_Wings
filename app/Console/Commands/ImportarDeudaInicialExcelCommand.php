<?php

namespace App\Console\Commands;

use App\Services\CargaDeudaInicialExcelService;
use Illuminate\Console\Command;

class ImportarDeudaInicialExcelCommand extends Command
{
    protected $signature = 'wings:importar-deuda-inicial
        {archivo : Ruta al archivo .xlsx}
        {--solo-validar : Recorre el archivo completo sin crear deudas}
        {--revertir : Retira únicamente las deudas pendientes e intactas de este mismo Excel}';

    protected $description = 'Importa la deuda inicial desde el Excel entregado por el club';

    public function handle(CargaDeudaInicialExcelService $servicio): int
    {
        $archivo = (string) $this->argument('archivo');
        if ($this->option('solo-validar')) {
            $resultado = $servicio->validar($archivo);
            return $this->mostrarResultadoValidacion($resultado['errores'], count($resultado['items']));
        }

        $resultado = $this->option('revertir')
            ? $servicio->revertir($archivo)
            : $servicio->importar($archivo);
        if ($resultado['errores'] !== []) {
            $this->mostrarErrores($resultado['errores']);
            return self::FAILURE;
        }

        $accion = $this->option('revertir') ? 'retiradas' : 'creadas';
        $this->info("Carga completada: {$resultado['cantidad']} deuda(s) {$accion}.");
        return self::SUCCESS;
    }

    /** @param array<int, array{fila: int, mensaje: string}> $errores */
    private function mostrarResultadoValidacion(array $errores, int $cantidad): int
    {
        if ($errores !== []) {
            $this->mostrarErrores($errores);
            return self::FAILURE;
        }
        $this->info("Validación aprobada: {$cantidad} deuda(s) listas para importar.");
        return self::SUCCESS;
    }

    /** @param array<int, array{fila: int, mensaje: string}> $errores */
    private function mostrarErrores(array $errores): void
    {
        $this->error('La carga fue rechazada. No se escribió ninguna deuda:');
        foreach ($errores as $error) {
            $ubicacion = $error['fila'] > 0 ? "Fila {$error['fila']}: " : '';
            $this->line("- {$ubicacion}{$error['mensaje']}");
        }
    }
}
