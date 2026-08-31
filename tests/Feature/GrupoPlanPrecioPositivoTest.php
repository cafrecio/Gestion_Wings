<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoPlanPrecioPositivoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);
        $deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Avanzadas']);
        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 42000,
            'activo' => true,
        ]);
    }

    public function test_rechaza_precio_cero_con_mensaje_y_sin_escribir(): void
    {
        $this->actualizarConPrecio(0)
            ->assertSessionHasErrors([
                'planes.0.precio_mensual' => 'El precio debe ser mayor a cero.',
            ]);

        $this->assertPlanSinCambios();
    }

    public function test_rechaza_precio_negativo_armado_a_mano_y_sin_escribir(): void
    {
        $this->actualizarConPrecio(-5000)
            ->assertSessionHasErrors([
                'planes.0.precio_mensual' => 'El precio debe ser mayor a cero.',
            ]);

        $this->assertPlanSinCambios();
    }

    private function actualizarConPrecio(int $precio)
    {
        return $this->actingAs($this->admin)
            ->put(route('web.grupos.update', $this->grupo->id), [
                'planes' => [[
                    'id' => $this->plan->id,
                    'clases_por_semana' => 2,
                    'precio_mensual' => $precio,
                ]],
            ]);
    }

    private function assertPlanSinCambios(): void
    {
        $this->assertDatabaseCount('grupo_planes', 1);
        $this->assertDatabaseHas('grupo_planes', [
            'id' => $this->plan->id,
            'clases_por_semana' => 2,
            'precio_mensual' => '42000.00',
        ]);
    }
}
