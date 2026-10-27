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

    public function test_la_observacion_de_un_rubro_reservado_no_se_edita(): void
    {
        $sueldos = $this->rubroSueldos();

        $this->actingAs($this->admin())
            ->put(route('web.rubros.update', $sueldos->id), [
                'nombre'      => 'Sueldos',
                'tipo'        => 'EGRESO',
                'observacion' => 'Docentes y administración',
            ])
            ->assertSessionHas('error');

        $this->assertSame('Pagos al personal', $sueldos->fresh()->observacion);
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

    public function test_un_rubro_reservado_vacio_no_acepta_subrubros_a_mano(): void
    {
        $sueldos = $this->rubroSueldos();

        $this->assertCount(0, $sueldos->subrubros, 'El caso es justamente el rubro vacío.');

        $this->actingAs($this->admin())
            ->post(route('web.subrubros.store', $sueldos->id), [
                'nombre'         => 'Honorarios',
                'permitido_para' => 'ADMIN',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('subrubros', ['nombre' => 'Honorarios']);
    }

    /** Control: en un rubro común el admin sigue pudiendo crear los suyos. */
    public function test_un_rubro_comun_sigue_aceptando_subrubros(): void
    {
        $rubro = Rubro::create([
            'nombre'      => 'Honorarios',
            'tipo'        => 'EGRESO',
            'observacion' => null,
        ]);

        $this->actingAs($this->admin())
            ->post(route('web.subrubros.store', $rubro->id), [
                'nombre'         => 'Contador',
                'permitido_para' => 'ADMIN',
                'clasificacion_resultado' => 'NEGOCIO',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subrubros', ['nombre' => 'Contador', 'rubro_id' => $rubro->id]);
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
        // T86, Carlos 27/10: operativas por Cashflow; docentes por Liquidaciones.
        $this->assertFalse($usuario->subrubro->es_reservado_sistema);
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
        $this->assertSame('Op-Nombre Cambiado', Subrubro::findOrFail($subrubroOriginal)->nombre);
        $this->assertSame(1, Subrubro::where('nombre', 'like', 'Op-%')->count());
    }

    public function test_renombrar_operativo_renombra_su_subrubro_y_preserva_movimientos_imputados(): void
    {
        $this->rubroSueldos();
        $admin = $this->admin();

        $operativo = User::factory()->create([
            'name'   => 'Sandra Vidal',
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $subrubro = app(\App\Services\SubrubroSueldoService::class)->paraUsuarioOperativo($operativo);
        $this->assertNotNull($subrubro);
        $this->assertSame('Op-Sandra Vidal', $subrubro->nombre);

        $tipoCaja = \App\Models\TipoCaja::create([
            'nombre' => 'Transferencia Bancaria',
            'activo' => true,
        ]);

        $movimiento = \App\Models\CashflowMovimiento::create([
            'fecha'            => '2026-10-15',
            'subrubro_id'      => $subrubro->id,
            'tipo_caja_id'     => $tipoCaja->id,
            'monto'            => 150000,
            'observaciones'    => 'Sueldo octubre Sandra',
            'usuario_admin_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('web.usuarios.update', $operativo->id), [
                'name'  => 'Sandra Gomez',
                'email' => $operativo->email,
                'rol'   => User::ROL_OPERATIVO,
            ])
            ->assertSessionHas('success');

        $this->assertSame($subrubro->id, $operativo->fresh()->subrubro_id);
        $this->assertSame('Op-Sandra Gomez', $subrubro->fresh()->nombre);
        $this->assertSame(1, Subrubro::where('nombre', 'like', 'Op-%')->count());

        $movimientoFresco = $movimiento->fresh();
        $this->assertSame($subrubro->id, $movimientoFresco->subrubro_id);
        $this->assertSame('150000.00', (string) $movimientoFresco->monto);
        $this->assertSame('Sueldo octubre Sandra', $movimientoFresco->observaciones);
    }

    public function test_editar_operativo_sin_cambiar_el_nombre_no_toca_el_subrubro(): void
    {
        $this->rubroSueldos();

        $operativo = User::factory()->create([
            'name'   => 'Sandra Vidal',
            'email'  => 'sandra@wings.test',
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $subrubro = app(\App\Services\SubrubroSueldoService::class)->paraUsuarioOperativo($operativo);
        $fechaOriginal = now()->subDays(3)->startOfSecond();
        \Illuminate\Support\Facades\DB::table('subrubros')
            ->where('id', $subrubro->id)
            ->update(['updated_at' => $fechaOriginal]);

        $this->actingAs($this->admin())
            ->put(route('web.usuarios.update', $operativo->id), [
                'name'  => 'Sandra Vidal',
                'email' => 'sandra.nuevo@wings.test',
                'rol'   => User::ROL_OPERATIVO,
            ])
            ->assertSessionHas('success');

        $this->assertSame('sandra.nuevo@wings.test', $operativo->fresh()->email);
        $this->assertSame($subrubro->id, $operativo->fresh()->subrubro_id);
        $this->assertSame('Op-Sandra Vidal', $subrubro->fresh()->nombre);
        $this->assertTrue($subrubro->fresh()->updated_at->equalTo($fechaOriginal));
        $this->assertSame(1, Subrubro::where('nombre', 'like', 'Op-%')->count());
    }

    public function test_renombrar_operativo_con_choque_de_subrubro_no_guarda_nada(): void
    {
        $this->rubroSueldos();
        $servicio = app(\App\Services\SubrubroSueldoService::class);

        $sandra = User::factory()->create([
            'name'   => 'Sandra Vidal',
            'email'  => 'sandra@wings.test',
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);
        $subrubroSandra = $servicio->paraUsuarioOperativo($sandra);

        $pablo = User::factory()->create([
            'name'   => 'Pablo Ledesma',
            'email'  => 'pablo@wings.test',
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
        ]);
        $subrubroPablo = $servicio->paraUsuarioOperativo($pablo);

        $this->actingAs($this->admin())
            ->from(route('web.usuarios.edit', $pablo->id))
            ->put(route('web.usuarios.update', $pablo->id), [
                'name'  => 'Sandra Vidal',
                'email' => 'pablo.modificado@wings.test',
                'rol'   => User::ROL_OPERATIVO,
            ])
            ->assertRedirect(route('web.usuarios.edit', $pablo->id))
            ->assertSessionHas('error');

        $pabloFresco = $pablo->fresh();
        $this->assertSame('Pablo Ledesma', $pabloFresco->name);
        $this->assertSame('pablo@wings.test', $pabloFresco->email);
        $this->assertSame($subrubroPablo->id, $pabloFresco->subrubro_id);
        $this->assertSame('Op-Pablo Ledesma', $subrubroPablo->fresh()->nombre);
        $this->assertSame('Op-Sandra Vidal', $subrubroSandra->fresh()->nombre);
        $this->assertSame(2, Subrubro::where('nombre', 'like', 'Op-%')->count());
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function test_catalogos_del_sistema_rechazan_toda_edicion_incluso_espacios(): void
    {
        foreach (['Cuotas' => 'INGRESO', 'Sueldos' => 'EGRESO'] as $nombre => $tipo) {
            $rubro = Rubro::create(['nombre' => $nombre, 'tipo' => $tipo, 'observacion' => 'Original']);
            $rubro->es_reservado_sistema = true;
            $rubro->save();
            // La proteccion del padre alcanza incluso a un hijo sin marca propia.
            $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => $nombre.' prueba',
                'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true]);
            $original = $rubro->fresh()->getAttributes();
            $originalSub = $sub->fresh()->getAttributes();
            foreach ([User::ROL_ADMIN, User::ROL_OPERATIVO, User::ROL_PROFESOR] as $rol) {
                $this->actingAs(User::factory()->create(['rol' => $rol, 'activo' => true]));
                // ADMIN ve el rechazo de catálogo; otros roles no entran al dominio.
                $status = $rol === User::ROL_ADMIN ? 302 : 403;
                $this->get(route('web.rubros.edit', $rubro->id))->assertStatus($status);
                $this->put(route('web.rubros.update', $rubro->id), [
                    'nombre' => $nombre.' ', 'tipo' => $tipo, 'observacion' => 'Modificada',
                ])->assertStatus($status);
                $this->delete(route('web.rubros.destroy', $rubro->id))->assertStatus($status);
                $this->get(route('web.subrubros.create', $rubro->id))->assertStatus($status);
                $this->get(route('web.subrubros.edit', [$rubro->id, $sub->id]))->assertStatus($status);
                $this->put(route('web.subrubros.update', [$rubro->id, $sub->id]), [
                    'nombre' => $sub->nombre.' ', 'permitido_para' => 'ADMIN', 'afecta_caja' => false,
                ])->assertStatus($status);
                $this->patch(route('web.subrubros.toggle-activo', [$rubro->id, $sub->id]))->assertStatus($status);
                $this->assertSame($original, $rubro->fresh()->getAttributes());
                $this->assertSame($originalSub, $sub->fresh()->getAttributes());
            }
            $this->actingAs($this->admin())->get(route('web.rubros.index'))
                ->assertOk()
                ->assertDontSee(route('web.rubros.edit', $rubro->id), false)
                ->assertDontSee(route('web.subrubros.edit', [$rubro->id, $sub->id]), false);
        }
    }

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
