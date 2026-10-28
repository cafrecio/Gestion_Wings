<?php

namespace Tests\Feature;

use App\Models\{Deporte, Profesor, User};
use App\Services\DeporteLiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

/** DDL real sin transacciones de RefreshDatabase: simula el orden de un respaldo antiguo. */
class VigenciaDeporteEsquemaAnteriorT45Test extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        // Mismo aislamiento que T27: no depende del rollback de migraciones antiguas.
        $this->beforeApplicationDestroyed(function () {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        });
    }

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || !in_array(config('database.connections.mysql.database'),
            ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T45 solo permite bases descartables de pruebas.');
        }
    }

    public function test_migracion_analitica_con_profesores_previos_no_depende_de_la_tabla_futura(): void
    {
        $this->assertContains(DB::connection()->getDatabaseName(), ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini']);
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        Schema::drop('deporte_liquidacion_vigencias');
        Schema::drop('reporte_analitico_historial');
        Schema::drop('reporte_analitico_cobertura');
        $d = Deporte::create(['nombre' => 'Deporte previo T45', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $p = Profesor::create(['deporte_id' => $d->id, 'nombre' => 'Ana', 'apellido' => 'Prueba', 'dni' => '70000001',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba',
            'valor_hora' => 5000, 'porcentaje_comision' => 20, 'activo' => true]);
        (require database_path('migrations/2026_10_09_180000_create_historial_analitico_reportes.php'))->up();
        $fila = DB::table('reporte_analitico_historial')->where('tipo', 'TARIFA')->first();
        $this->assertSame('HORA', json_decode($fila->datos, true)['tipo']);
        $this->assertFalse(Schema::hasTable('deporte_liquidacion_vigencias'));
        (require database_path('migrations/2026_10_28_000002_create_deporte_liquidacion_vigencias_table.php'))->up();
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        app(DeporteLiquidacionService::class)->actualizar($d->id, $d->nombre, 'COMISION', $admin);
        $this->travelTo(Carbon::parse('2026-11-12'));
        $p->update(['valor_hora' => 5001]);
        $ultima = DB::table('reporte_analitico_historial')->where('tipo', 'TARIFA')->orderByDesc('id')->first();
        $this->assertSame('COMISION', json_decode($ultima->datos, true)['tipo']);
    }
}
