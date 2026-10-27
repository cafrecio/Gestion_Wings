<?php

namespace Tests\VerificacionCodex;

// Copia byte por byte: mantiene __DIR__ y sus archivos de salida en la evidencia propia.
require_once __DIR__.'/SeguridadDiasPruebaFuente.php';

/** Ejecuta el método técnico original sin cambiarlo; sustituye solo su guardia Claude por Codex. */
class SeguridadDia23T90CodexTest extends \Tests\Feature\SeguridadDiasPruebaTest
{
    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex'
            || \Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('Adaptación del día23: solo wings_testing_codex antes de migrar.');
        }
    }
}
