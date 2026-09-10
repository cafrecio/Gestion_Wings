<?php

namespace App\Console\Commands;

use App\Services\CargaSaldoInicialPadronService;
use Illuminate\Console\Command;

class ImportarSaldoInicialPadronCommand extends Command
{
    protected $signature = 'wings:importar-padron
        {archivo : Ruta al .xlsx exportado con wings:exportar-padron y completado por el club}
        {--corte= : Mes que se cierra, en formato YYYY-MM. Por defecto, el mes en curso}
        {--solo-validar : Recorre el archivo completo sin escribir nada}';

    protected $description = 'Carga el saldo inicial de todo el padrón y cierra el mes de corte';

    public function handle(CargaSaldoInicialPadronService $servicio): int
    {
        $archivo = (string) $this->argument('archivo');
        $corte = (string) ($this->option('corte') ?: now()->format('Y-m'));

        if ($this->option('solo-validar')) {
            $resultado = $servicio->validar($archivo, $corte);
            if ($resultado['errores'] !== []) {
                return $this->mostrarErrores($resultado['errores']);
            }
            $this->info(sprintf(
                'Validación aprobada: %d deuda(s) y %d cierre(s) de %s listos para escribir.',
                count($resultado['deudas']),
                count($resultado['cierres']),
                $corte
            ));
            return self::SUCCESS;
        }

        $resultado = $servicio->importar($archivo, $corte);
        if ($resultado['errores'] !== []) {
            return $this->mostrarErrores($resultado['errores']);
        }

        $this->info("Carga completada sobre el corte {$corte}:");
        $this->line("- {$resultado['deudas']} deuda(s) pendientes creadas.");
        $this->line("- {$resultado['cierres']} alumno(s) quedaron con saldo inicial cero y {$corte} cerrado.");
        $this->line('No se registró ningún pago: lo cobrado antes de Wings queda fuera de esta contabilidad.');

        return self::SUCCESS;
    }

    /** @param array<int, array{fila: int, mensaje: string}> $errores */
    private function mostrarErrores(array $errores): int
    {
        $this->error('La carga fue rechazada. No se escribió nada:');
        foreach ($errores as $error) {
            $ubicacion = $error['fila'] > 0 ? "Fila {$error['fila']}: " : '';
            $this->line("- {$ubicacion}{$error['mensaje']}");
        }
        return self::FAILURE;
    }
}
