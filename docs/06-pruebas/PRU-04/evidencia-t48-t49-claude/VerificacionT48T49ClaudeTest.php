<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\SubrubroSueldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Controles propios de Claude al verificar T48/T49 (e4c2424), 17/10/2026. */
class VerificacionT48T49ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-17 10:00:00'));
        $rubro = Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO', 'observacion' => 'Pagos al personal']);
        $rubro->es_reservado_sistema = true;
        $rubro->save();
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true, 'es_superadmin' => false]);
    }

    /** Lo que ve el dueño: el sueldo ya pagado aparece en Cashflow con el nombre nuevo. */
    public function test_cashflow_muestra_el_sueldo_viejo_con_el_nombre_nuevo(): void
    {
        $operativa = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $subrubro = app(SubrubroSueldoService::class)->paraUsuarioOperativo($operativa);
        $tipoCaja = TipoCaja::create(['nombre' => 'Transferencia', 'activo' => true]);
        CashflowMovimiento::create([
            'fecha' => '2026-10-15', 'subrubro_id' => $subrubro->id, 'tipo_caja_id' => $tipoCaja->id,
            'monto' => 150000, 'observaciones' => 'Sueldo octubre', 'usuario_admin_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->put(route('web.usuarios.update', $operativa->id), [
            'name' => 'Sandra Gomez', 'email' => $operativa->email, 'rol' => User::ROL_OPERATIVO,
        ])->assertSessionHas('success');

        $this->actingAs($this->admin)
            ->get(route('web.cashflow.index', ['periodo' => 'mes', 'anio' => 2026, 'mes' => 10]))
            ->assertOk()
            ->assertSee('Op-Sandra Gomez')
            ->assertDontSee('Op-Sandra Vidal');
    }

    /** Nombre y rol cambiados a la vez, sin caja: deja de ser operativa y su subrubro conserva el nombre viejo. */
    public function test_cambiar_nombre_y_rol_juntos_no_renombra_el_subrubro(): void
    {
        $operativa = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $subrubro = app(SubrubroSueldoService::class)->paraUsuarioOperativo($operativa);

        $this->actingAs($this->admin)->put(route('web.usuarios.update', $operativa->id), [
            'name' => 'Sandra Gomez', 'email' => $operativa->email, 'rol' => User::ROL_ADMIN,
        ])->assertSessionHas('success');

        $this->assertSame(User::ROL_ADMIN, $operativa->fresh()->rol);
        $this->assertSame($subrubro->id, $operativa->fresh()->subrubro_id);
        $this->assertSame('Op-Sandra Vidal', $subrubro->fresh()->nombre);
    }

    /** El choque se detecta aunque difiera solo en mayúsculas (la base compara sin distinguirlas). */
    public function test_choque_que_difiere_solo_en_mayusculas_no_guarda_nada(): void
    {
        $sandra = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        app(SubrubroSueldoService::class)->paraUsuarioOperativo($sandra);
        $pablo = User::factory()->create(['name' => 'Pablo Ledesma', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $subPablo = app(SubrubroSueldoService::class)->paraUsuarioOperativo($pablo);

        $this->actingAs($this->admin)->put(route('web.usuarios.update', $pablo->id), [
            'name' => 'sandra vidal', 'email' => $pablo->email, 'rol' => User::ROL_OPERATIVO,
        ])->assertSessionHas('error');

        $this->assertSame('Pablo Ledesma', $pablo->fresh()->name);
        $this->assertSame('Op-Pablo Ledesma', $subPablo->fresh()->nombre);
    }

    /** Reactivar a una operativa inactiva no lo frena la regla: solo aplica al desactivar. */
    public function test_reactivar_no_se_frena(): void
    {
        $operativa = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO]);
        $operativa->activo = false;
        $operativa->save();

        $this->actingAs($this->admin)
            ->patchJson(route('web.usuarios.toggle-activo', $operativa->id))
            ->assertOk()
            ->assertJson(['activo' => true]);
    }
}
