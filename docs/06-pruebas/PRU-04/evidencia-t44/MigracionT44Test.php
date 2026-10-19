<?php
namespace Tests\MigracionT44;

use App\Models\{Deporte, Grupo, GrupoPlan, Nivel};
use Illuminate\Support\Facades\{Artisan, DB, Schema};
use Tests\TestCase;

class MigracionT44Test extends TestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        if (!$app->environment('testing') || $app['db']->connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('Instrumental exclusivo de testing/wings_testing_codex.');
        }
        return $app;
    }

    public function test_migracion_con_planes_preexistentes(): void
    {
        $this->assertSame('wings_testing_codex', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
        Artisan::call('migrate:fresh', ['--force' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín migración T44', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Migración T44']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 40000.50, 'activo' => true]);
        $migracion = require base_path('database/migrations/2026_10_18_230000_create_grupo_plan_precios.php');
        $migracion->down();
        $this->assertFalse(Schema::hasTable('grupo_plan_precios'));
        $this->assertSame('40000.50', $plan->fresh()->precio_mensual);
        $migracion->up();
        $fila = DB::table('grupo_plan_precios')->where('grupo_plan_id', $plan->id)->sole();
        $this->assertNull($fila->vigente_desde);
        $this->assertSame('40000.50', $fila->precio_mensual);
        $this->assertSame(40000.50, $plan->precioParaPeriodo('2000-01', null));
        $this->assertSame(40000.50, $plan->precioParaPeriodo('2027-01', null));
        file_put_contents(__DIR__.'/migracion.json', json_encode(['base' => DB::connection()->getDatabaseName(), 'precio_antes' => $plan->fresh()->precio_mensual, 'fecha_inicial' => $fila->vigente_desde, 'precio_2000_01' => $plan->precioParaPeriodo('2000-01', null), 'precio_2027_01' => $plan->precioParaPeriodo('2027-01', null), 'rollback_y_up' => true], JSON_PRETTY_PRINT));
    }
}
