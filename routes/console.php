<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generar las cuotas del mes. Corre todos los días a las 6:00 (hora Argentina):
// el día 1 genera; los días siguientes solo actúa si el día 1 no corrió, y
// entonces genera lo que falta y avisa al dueño (T40, DU-52).
Schedule::command('cobranza:generar-deudas --si-falta')->dailyAt('06:00')->withoutOverlapping()->onOneServer();

// B11: consulta la fecha de Wings; recupera un día omitido dentro del mismo mes.
Schedule::command('cobranza:aplicar-punitorios')->dailyAt('06:15')->withoutOverlapping()->onOneServer();

// Resumen diario de pendientes para el ADMIN a las 08:00 (hora Argentina, ENT-06)
Schedule::command('avisos:resumen-diario')->dailyAt('08:00');

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
