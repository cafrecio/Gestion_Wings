<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generar deudas mensuales el 1ro de cada mes a las 6:00 AM (hora Argentina)
Schedule::command('cobranza:generar-deudas')->monthlyOn(1, '06:00');

// Resumen semanal de los avisos de la politica de seguridad de contenido (CSP).
//
// Entra por el scheduler, que ya corre cada minuto en el servidor, en vez de
// pedir un cron nuevo: asi se despliega y anda, sin un paso manual que alguien
// tenga que acordarse de hacer. Un cron que hay que instalar a mano es un cron
// que en algun servidor no esta.
//
// Se invoca el script de shell en lugar de reescribirlo como comando de Artisan
// para no duplicar en PHP el envio de Telegram: el robot, el chat y los
// secretos ya viven en monitoreo-common.sh y en /etc/wings-monitor.
//
// Semanal y no diario porque el resumen solo informa lo que no se aviso antes:
// mientras no aparezca una violacion nueva, no manda nada.
Schedule::exec('bash ' . base_path('scripts/servidor/resumen-csp.sh'))
    ->weeklyOn(1, '07:00')
    ->onOneServer()
    ->withoutOverlapping();
