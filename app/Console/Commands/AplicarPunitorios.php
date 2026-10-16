<?php

namespace App\Console\Commands;

use App\Services\PunitorioService;
use Illuminate\Console\Command;

class AplicarPunitorios extends Command
{
    protected $signature = 'cobranza:aplicar-punitorios';
    protected $description = 'Aplica una sola vez el recargo a cuotas impagas del mes actual de Wings';

    public function handle(PunitorioService $service): int
    {
        $this->info('Recargos aplicados: '.$service->aplicar());
        return self::SUCCESS;
    }
}
