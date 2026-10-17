<?php

namespace Tests\Feature;

use App\Http\Controllers\UsuarioWebController;
use App\Models\CajaOperativa;
use App\Models\Rubro;
use App\Models\User;
use App\Services\SubrubroSueldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioActivoWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $usuario = User::factory()->create([
            'email' => 'inactivo@example.com',
            'password' => 'password',
            'activo' => false,
        ]);

        $this->from(route('login'))
            ->post('/login', [
                'email' => $usuario->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sesion_emitida_se_cierra_si_el_usuario_fue_desactivado(): void
    {
        $usuario = User::factory()->create([
            'rol' => User::ROL_PROFESOR,
            'activo' => false,
        ]);

        $this->actingAs($usuario)
            ->get(route('web.clases.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_no_se_puede_desactivar_operativa_con_caja_abierta(): void
    {
        $admin = $this->admin();
        $operativa = User::factory()->create([
            'name'   => 'Sandra Vidal',
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        CajaOperativa::create([
            'usuario_operativo_id' => $operativa->id,
            'apertura_at'          => now(),
            'estado'               => CajaOperativa::ESTADO_ABIERTA,
        ]);

        // Vía redirección
        $this->actingAs($admin)
            ->from(route('web.usuarios.index'))
            ->patch(route('web.usuarios.toggle-activo', $operativa->id))
            ->assertRedirect(route('web.usuarios.index'))
            ->assertSessionHas('error', UsuarioWebController::AVISO_CAJA_ABIERTA);

        $this->assertTrue($operativa->fresh()->activo);

        // Vía JSON
        $this->actingAs($admin)
            ->patchJson(route('web.usuarios.toggle-activo', $operativa->id))
            ->assertStatus(422)
            ->assertJson([
                'error' => UsuarioWebController::AVISO_CAJA_ABIERTA,
            ]);

        $this->assertTrue($operativa->fresh()->activo);
    }

    public function test_se_puede_desactivar_operativa_sin_caja_abierta(): void
    {
        $admin = $this->admin();
        $operativa = User::factory()->create([
            'name'   => 'Sandra Vidal',
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        CajaOperativa::create([
            'usuario_operativo_id' => $operativa->id,
            'apertura_at'          => now()->subHours(4),
            'cierre_at'            => now()->subHour(),
            'estado'               => CajaOperativa::ESTADO_CERRADA,
        ]);

        // Vía redirección
        $this->actingAs($admin)
            ->from(route('web.usuarios.index'))
            ->patch(route('web.usuarios.toggle-activo', $operativa->id))
            ->assertRedirect(route('web.usuarios.index'))
            ->assertSessionMissing('error');

        $this->assertFalse($operativa->fresh()->activo);

        // Reactivar y probar vía JSON
        $operativa = $operativa->fresh();
        $operativa->activo = true;
        $operativa->save();

        $this->actingAs($admin)
            ->patchJson(route('web.usuarios.toggle-activo', $operativa->id))
            ->assertOk()
            ->assertJson(['activo' => false]);

        $this->assertFalse($operativa->fresh()->activo);
    }

    public function test_no_se_puede_cambiar_el_rol_de_operativa_con_caja_abierta(): void
    {
        $admin = $this->admin();
        $operativa = User::factory()->create([
            'name'   => 'Sandra Vidal',
            'email'  => 'sandra@wings.test',
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        CajaOperativa::create([
            'usuario_operativo_id' => $operativa->id,
            'apertura_at'          => now(),
            'estado'               => CajaOperativa::ESTADO_ABIERTA,
        ]);

        $this->actingAs($admin)
            ->from(route('web.usuarios.edit', $operativa->id))
            ->put(route('web.usuarios.update', $operativa->id), [
                'name'  => 'Sandra Admin',
                'email' => 'sandra.admin@wings.test',
                'rol'   => User::ROL_ADMIN,
            ])
            ->assertRedirect(route('web.usuarios.edit', $operativa->id))
            ->assertSessionHas('error', UsuarioWebController::AVISO_CAJA_ABIERTA);

        $fresca = $operativa->fresh();
        $this->assertSame(User::ROL_OPERATIVO, $fresca->rol);
        $this->assertSame('Sandra Vidal', $fresca->name);
        $this->assertSame('sandra@wings.test', $fresca->email);
    }

    public function test_se_puede_editar_el_nombre_con_caja_abierta(): void
    {
        $rubro = Rubro::create([
            'nombre'      => 'Sueldos',
            'tipo'        => 'EGRESO',
            'observacion' => 'Pagos al personal',
        ]);
        $rubro->es_reservado_sistema = true;
        $rubro->save();

        $admin = $this->admin();
        $operativa = User::factory()->create([
            'name'   => 'Sandra Vidal',
            'email'  => 'sandra@wings.test',
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $subrubro = app(SubrubroSueldoService::class)->paraUsuarioOperativo($operativa);

        CajaOperativa::create([
            'usuario_operativo_id' => $operativa->id,
            'apertura_at'          => now(),
            'estado'               => CajaOperativa::ESTADO_ABIERTA,
        ]);

        $this->actingAs($admin)
            ->put(route('web.usuarios.update', $operativa->id), [
                'name'                  => 'Sandra Vidal Renombrada',
                'email'                 => 'sandra.nueva@wings.test',
                'rol'                   => User::ROL_OPERATIVO,
                'password'              => 'nueva-clave-larga-12',
                'password_confirmation' => 'nueva-clave-larga-12',
            ])
            ->assertSessionHas('success');

        $fresca = $operativa->fresh();
        $this->assertSame('Sandra Vidal Renombrada', $fresca->name);
        $this->assertSame('sandra.nueva@wings.test', $fresca->email);
        $this->assertTrue(Hash::check('nueva-clave-larga-12', $fresca->password));
        $this->assertSame($subrubro->id, $fresca->subrubro_id);
        $this->assertSame('Op-Sandra Vidal Renombrada', $subrubro->fresh()->nombre);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'rol'           => User::ROL_ADMIN,
            'activo'        => true,
            'es_superadmin' => false,
        ]);
    }
}
