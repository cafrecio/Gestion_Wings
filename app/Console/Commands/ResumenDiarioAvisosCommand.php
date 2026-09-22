<?php

namespace App\Console\Commands;

use App\Services\AvisoAdminService;
use Illuminate\Console\Command;

class ResumenDiarioAvisosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avisos:resumen-diario';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía al administrador el resumen diario de pendientes operativos (cajas, revisiones y liquidaciones)';

    /**
     * Execute the console command.
     */
    public function handle(AvisoAdminService $avisoService): int
    {
        $enviado = $avisoService->resumenDiario();

        if ($enviado) {
            $this->info('Resumen diario de avisos enviado correctamente.');
        } else {
            $this->info('Sin pendientes: no se envió ningún aviso.');
        }

        return Command::SUCCESS;
    }
}
