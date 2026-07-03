<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generar deudas mensuales el 1ro de cada mes a las 6:00 AM (hora Argentina)
Schedule::command('cobranza:generar-deudas')->monthlyOn(1, '06:00');
