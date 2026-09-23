<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Mueve el día del servidor de prueba, que es como avanza la novela de PRU-02.
 *
 * Escribe `FECHA_SIMULADA` en el `.env` y limpia la configuración cacheada, para
 * no depender de que alguien edite el archivo a mano en el servidor y se olvide
 * del segundo paso. En producción no hace nada: ahí la fecha es la real.
 */
class FechaSimuladaCommand extends Command
{
    protected $signature = 'wings:fecha-simulada
        {fecha? : Día a simular (2026-10-15) u hora exacta (2026-10-15 09:30). Sin argumento, muestra la vigente}
        {--real : Vuelve a la fecha real del sistema}';

    protected $description = 'Fija el día que Wings cree que es, para la prueba grande';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('En producción Wings usa siempre la fecha real. No se cambió nada.');

            return self::FAILURE;
        }

        $archivo = base_path('.env');
        if (!is_writable($archivo)) {
            $this->error("No se puede escribir {$archivo}.");

            return self::FAILURE;
        }

        if ($this->option('real')) {
            $this->escribir($archivo, '');
            $this->info('Wings vuelve a la fecha real: ' . Carbon::now()->format('d/m/Y H:i'));

            return self::SUCCESS;
        }

        $fecha = $this->argument('fecha');
        if ($fecha === null) {
            $vigente = config('app.fecha_simulada');
            $this->line($vigente
                ? "Fecha simulada vigente: {$vigente}"
                : 'Sin fecha simulada: Wings usa la fecha real, ' . Carbon::now()->format('d/m/Y H:i'));

            return self::SUCCESS;
        }

        try {
            $parseada = Carbon::parse($fecha);
        } catch (\Throwable) {
            $this->error("'{$fecha}' no es una fecha válida. Va como 2026-10-15 o 2026-10-15 09:30.");

            return self::FAILURE;
        }

        $this->escribir($archivo, $parseada->toDateTimeString());
        $this->info('Wings ahora cree que es ' . $parseada->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY, HH:mm'));
        $this->line('Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.');

        return self::SUCCESS;
    }

    private function escribir(string $archivo, string $valor): void
    {
        $contenido = file_get_contents($archivo);
        $linea = $valor === '' ? '' : 'FECHA_SIMULADA="' . $valor . '"';

        if (preg_match('/^FECHA_SIMULADA=.*$/m', $contenido)) {
            $contenido = preg_replace('/^FECHA_SIMULADA=.*$/m', $linea, $contenido);
            $contenido = preg_replace("/\n{3,}/", "\n\n", $contenido);
        } elseif ($linea !== '') {
            $contenido = rtrim($contenido) . "\n" . $linea . "\n";
        }

        file_put_contents($archivo, $contenido);
        $this->call('config:clear');
    }
}
