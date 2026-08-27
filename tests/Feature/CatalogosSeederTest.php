<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogosSeederTest extends TestCase
{
    use RefreshDatabase;

    private const TABLAS = [
        'deportes',
        'niveles',
        'rubros',
        'subrubros',
        'tipos_caja',
        'reglas_primer_pago',
    ];

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_database_seeder_deja_solo_los_catalogos_base(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('deportes', 2);
        $this->assertDatabaseCount('niveles', 3);
        $this->assertDatabaseCount('rubros', 8);
        $this->assertDatabaseCount('subrubros', 15);
        $this->assertDatabaseCount('tipos_caja', 5);
        $this->assertDatabaseCount('reglas_primer_pago', 3);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 0);

        foreach (['Principiantes', 'Intermedias', 'Avanzadas'] as $nivel) {
            $this->assertDatabaseHas('niveles', ['nombre' => $nivel]);
        }

        $this->assertDatabaseHas('subrubros', [
            'nombre' => 'Cuota Mensual',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);
        $this->assertDatabaseHas('rubros', [
            'nombre' => 'Sueldos',
            'tipo' => 'EGRESO',
        ]);
        $this->assertFalse(
            DB::table('subrubros')->where('nombre', 'like', 'Sueldo - %')->exists()
        );
    }

    public function test_es_idempotente_y_la_segunda_ejecucion_no_modifica_filas(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $primeraEjecucion = $this->snapshot();

        Carbon::setTestNow('2026-08-26 13:00:00');
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($primeraEjecucion, $this->snapshot());
    }

    private function snapshot(): array
    {
        $snapshot = [];

        foreach (self::TABLAS as $tabla) {
            $snapshot[$tabla] = DB::table($tabla)
                ->orderBy('id')
                ->get()
                ->map(fn (object $fila): array => (array) $fila)
                ->all();
        }

        return $snapshot;
    }
}
