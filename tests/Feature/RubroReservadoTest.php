<?php

namespace Tests\Feature;

use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El alta de profesores y la de usuarios operativos buscan el rubro
 * "Sueldos" por nombre exacto. Renombrarlo rompía las dos en silencio.
 * Estas pruebas fijan que un rubro reservado no se renombra, no cambia de
 * tipo y no se borra — y que el operativo recibe su subrubro de sueldo.
 */
class RubroReservadoTest extends TestCase
{
    use RefreshDatabase;

    // ── El rubro reservado no se toca ────────────────────────────────────

    public function test_no_se_puede_renombrar_un_rubro_reservado(): void
    {
        $sueldos = $this->rubroSueldos();

        $this->actingAs($this->admin())
            ->put(route('web.rubros.update', $sueldos->id), [
                'nombre'      => 'Sueldos y honorarios',
                'tipo'        => 'EGRESO',
                'observacion' => 'Pagos al personal',
            ])
            ->assertSessionHas('error');

        $this->assertSame('Sueldos', $sueldos->fresh()->nombre);
    }

    public function test_no_se_puede_cambiar_el_tipo_de_un_rubro_reservado(): void
    {
        $sueldos = $this->rubroSueldos();

        $this->actingAs($this->admin())
            ->put(route('web.rubros.update', $sueldos->id), [
                'nombre'      => 'Sueldos',
                'tipo'        => 'INGRESO',
                'observacion' => 'Pagos al personal',
            ])
            ->assertSessionHas('error');

        $this->assertSame('EGRESO', $sueldos->fresh()->tipo);
    }

    public function test_no_se_puede_eliminar_un_rubro_reservado(): void
    {
        $sueldos = $this->rubroSueldos();

        $this->actingAs($this->admin())
            ->delete(route('web.rubros.destroy', $sueldos->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('rubros', ['id' => $sueldos->id]);
    }

    public function test_la_observacion_de_un_rubro_reservado_si_se_edita(): void
    {
        $sueldos = $this->rubroSueldos();

        $this->actingAs($this->admin())
            ->put(route('web.rubros.update', $sueldos->id), [
                'nombre'      => 'Sueldos',
                'tipo'        => 'EGRESO',
                'observacion' => 'Docentes y administración',
            ])
            ->assertSessionHas('success');

        $this->assertSame('Docentes y administración', $sueldos->fresh()->observacion);
    }

    /** Control: sin este caso, un `abort` general también pasaría los de arriba. */
    public function test_un_rubro_comun_se_sigue_renombrando(): void
    {
        $rubro = Rubro::create([
            'nombre'      => 'Servicios',
            'tipo'        => 'EGRESO',
            'observacion' => null,
        ]);

        $this->actingAs($this->admin())
            ->put(route('web.rubros.update', $rubro->id), [
                'nombre'      => 'Servicios varios',
                'tipo'        => 'INGRESO',
                'observacion' => null,
            ])
            ->assertSessionHas('success');

        $this->assertSame('Servicios varios', $rubro->fresh()->nombre);
        $this->assertSame('INGRESO', $rubro->fresh()->tipo);
    }

    // ── El operativo recibe su subrubro de sueldo ────────────────────────

    public function test_el_alta_de_un_operativo_le_crea_su_subrubro_de_sueldo(): void
    {
        $sueldos = $this->rubroSueldos();

        $this->actingAs($this->admin())
            ->post(route('web.usuarios.store'), [
                'name'                  => 'Valentina Rios',
                'email'                 => 'valentina@wings.test',
                'password'              => 'contrasena-larga-1',
                'password_confirmation' => 'contrasena-larga-1',
                'rol'                   => User::ROL_OPERATIVO,
            ])
            ->assertSessionHas('success');

        $usuario = User::where('email', 'valentina@wings.test')->firstOrFail();

        $this->assertNotNull($usuario->subrubro_id, 'El operativo quedó sin subrubro de sueldo.');
        $this->assertSame('Op-Valentina Rios', $usuario->subrubro->nombre);
        $this->assertSame($sueldos->id, $usuario->subrubro->rubro_id);
        $this->assertSame('ADMIN', $usuario->subrubro->permitido_para);
        $this->assertFalse($usuario->subrubro->afecta_caja, 'El sueldo no se paga por caja operativa.');
        $this->assertTrue($usuario->subrubro->es_reservado_sistema);
    }

    public function test_el_alta_de_un_admin_no_crea_subrubro(): void
    {
        $this->rubroSueldos();

        $this->actingAs($this->admin())
            ->post(route('web.usuarios.store'), [
                'name'                  => 'Otro Admin',
                'email'                 => 'otro@wings.test',
                'password'              => 'contrasena-larga-1',
                'password_confirmation' => 'contrasena-larga-1',
                'rol'                   => User::ROL_ADMIN,
            ])
            ->assertSessionHas('success');

        $this->assertNull(User::where('email', 'otro@wings.test')->firstOrFail()->subrubro_id);
        $this->assertSame(0, Subrubro::where('nombre', 'like', 'Op-%')->count());
    }

    public function test_editar_un_operativo_no_le_duplica_el_subrubro(): void
    {
        $this->rubroSueldos();

        $operativo = User::factory()->create([
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        app(\App\Services\SubrubroSueldoService::class)->paraUsuarioOperativo($operativo);
        $subrubroOriginal = $operativo->fresh()->subrubro_id;

        $this->actingAs($this->admin())
            ->put(route('web.usuarios.update', $operativo->id), [
                'name'  => 'Nombre Cambiado',
                'email' => $operativo->email,
                'rol'   => User::ROL_OPERATIVO,
            ])
            ->assertSessionHas('success');

        $this->assertSame($subrubroOriginal, $operativo->fresh()->subrubro_id);
        $this->assertSame(1, Subrubro::where('nombre', 'like', 'Op-%')->count());
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function admin(): User
    {
        return User::factory()->create([
            'rol'           => User::ROL_ADMIN,
            'activo'        => true,
            'es_superadmin' => false,
        ]);
    }

    /** `es_reservado_sistema` está fuera de $fillable: se asigna directo. */
    private function rubroSueldos(): Rubro
    {
        $rubro = Rubro::create([
            'nombre'      => 'Sueldos',
            'tipo'        => 'EGRESO',
            'observacion' => 'Pagos al personal',
        ]);

        $rubro->es_reservado_sistema = true;
        $rubro->save();

        return $rubro;
    }
}
