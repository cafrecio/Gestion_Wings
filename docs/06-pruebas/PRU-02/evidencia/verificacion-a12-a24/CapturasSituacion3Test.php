<?php
namespace Tests\Evidencia;
use App\Models\TipoCaja; use App\Models\User; use Carbon\Carbon; use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class CapturasSituacion3Test extends TestCase {
    use RefreshDatabase;
    public function test_guarda(): void {
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->seed(CatalogosSeeder::class);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $sandra = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $marcos = User::factory()->create(['name' => 'Marcos Peña', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->actingAs($admin)->post('/caja/configuracion', ['tipo_caja_id' => TipoCaja::first()->id]);
        $this->actingAs($marcos)->post('/caja/apertura', ['efectivo_inicial' => '10.000,00', 'confirmacion' => '1', 'motivo_apertura' => 'x']);
        @mkdir(public_path('_cap-a24'), 0777, true);
        file_put_contents(public_path('_cap-a24/inicio.html'), $this->actingAs($sandra)->get(route('web.operativo.dashboard'))->assertOk()->getContent());
        $r = $this->actingAs($sandra)->followingRedirects()->get(route('web.caja.cobrar-cuota'));
        file_put_contents(public_path('_cap-a24/cobrar.html'), $r->getContent());
        Carbon::setTestNow();
    }
}
