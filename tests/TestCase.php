<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Los tests históricos ejercitan un club ya operativo. P1 restaura explícitamente
        // PENDIENTE en su fixture y prueba el recorrido completo, también con altas directas.
        if (\Illuminate\Support\Facades\Schema::hasTable('primera_carga')) {
            \Illuminate\Support\Facades\DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
        }
    }
}
