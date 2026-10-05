<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A13 y B1: el dueño no tiene caja.
 *
 * Cuando el admin cobraba una cuota, el sistema le abría una caja a su nombre y después
 * tenía que cerrarla y validarse a sí mismo. La pantalla mandaba a todos por el camino del
 * mostrador sin mirar el rol, aunque el camino directo a cashflow ya existía y lo usaba
 * solo la API, que está apagada. Decisión de Carlos del 23/09: el admin no tiene caja.
 *
 * Se agrega lo que faltaba para que eso no deje un agujero: un cobro sin caja también se
 * puede anular, porque anular vivía solo dentro de la caja del mostrador.
 */
class AdminCobraSinCajaTest extends TestCase
{
    use RefreshDatabase;

    private const PRECIO = 48000.0;

    private User $admin;
    private User $operativo;
    private Alumno $alumno;
    private TipoCaja $tipoCaja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-05 10:00:00');

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->tipoCaja = TipoCaja::first() ?? TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);

        $deporte = Deporte::first() ?? Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => self::PRECIO,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'apellido' => 'Dueño',
            'nombre' => 'Cobra',
            'dni' => '31222333',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0000',
            'fecha_alta' => '2026-09-01',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-09-01',
            'activo' => true,
        ]);

        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => self::PRECIO,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_admin_cobra_y_no_se_le_abre_ninguna_caja(): void
    {
        $this->cobrar($this->admin)->assertSessionHas('success');

        $this->assertDatabaseCount('cajas_operativas', 0);
        $this->assertSame(0, MovimientoOperativo::count());

        // La plata entra igual: va derecho al cashflow, con el medio elegido.
        $movimiento = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)->sole();
        $this->assertEquals(self::PRECIO, $movimiento->monto);
        $this->assertSame($this->tipoCaja->id, $movimiento->tipo_caja_id);
        $this->assertSame($this->admin->id, $movimiento->usuario_admin_id);

        $deuda = DeudaCuota::where('alumno_id', $this->alumno->id)->sole();
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deuda->estado);
    }

    public function test_el_operativo_sigue_cobrando_por_su_caja(): void
    {
        $this->cobrar($this->operativo)->assertSessionHas('success');

        $caja = CajaOperativa::sole();
        $this->assertSame($this->operativo->id, $caja->usuario_operativo_id);
        $this->assertSame(1, MovimientoOperativo::where('caja_operativa_id', $caja->id)->count());
    }

    /** El recibo tiene que salir igual, con el medio con el que cobró el dueño. */
    public function test_el_cobro_del_dueno_emite_recibo_con_su_medio(): void
    {
        $this->cobrar($this->admin)->assertSessionHas('success');
        $pago = Pago::sole();

        $this->actingAs($this->admin)
            ->get(route('web.recibos.cuota', $pago->id).'?inline=1')
            ->assertOk();
    }

    public function test_el_dueno_anula_su_cobro_y_la_deuda_vuelve(): void
    {
        $this->cobrar($this->admin)->assertSessionHas('success');
        $pago = Pago::sole();

        $this->actingAs($this->admin)
            ->post(route('web.pagos.anular', $pago->id), ['motivo' => 'Se equivocó de medio de pago'])
            ->assertSessionHas('success');

        $this->assertSame('ANULADO', $pago->fresh()->estado);

        $deuda = DeudaCuota::where('alumno_id', $this->alumno->id)->sole();
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $deuda->estado);
        $this->assertEquals(0, $deuda->monto_pagado);

        // La plata se va del cashflow: no queda un ingreso que no existió.
        $this->assertSame(
            0.0,
            (float) CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)
                ->where('referencia_id', $pago->id)
                ->sum('monto'),
            'Anular tiene que dejar el cashflow en cero para ese cobro.'
        );

        // Y queda escrito qué se anuló y por qué, como en el cobro del mostrador.
        $detalle = $pago->fresh()->detalle_anulacion;
        $this->assertSame('Se equivocó de medio de pago', $detalle['motivo']);
        $this->assertSame(['2026-10'], array_column($detalle['periodos'], 'periodo'));
    }

    public function test_el_mismo_cobro_no_se_anula_dos_veces(): void
    {
        $this->cobrar($this->admin);
        $pago = Pago::sole();
        $datos = ['motivo' => 'Primera anulación, la buena'];

        $this->actingAs($this->admin)->post(route('web.pagos.anular', $pago->id), $datos)->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('web.pagos.anular', $pago->id), $datos)->assertSessionHas('error');

        // El cobro y su contraasiento: la segunda anulación no agrega un tercero.
        $this->assertSame(2, CashflowMovimiento::where('referencia_id', $pago->id)->count());
        $this->assertSame(0.0, (float) CashflowMovimiento::where('referencia_id', $pago->id)->sum('monto'));
    }

    /** Anular un cobro es del dueño: el mostrador anula lo suyo dentro de su caja. */
    public function test_el_operativo_no_anula_el_cobro_del_dueno(): void
    {
        $this->cobrar($this->admin);
        $pago = Pago::sole();

        $this->actingAs($this->operativo)
            ->post(route('web.pagos.anular', $pago->id), ['motivo' => 'No deberia poder']);

        $this->assertSame('COMPLETADO', $pago->fresh()->estado);
    }

    public function test_anular_sin_motivo_no_hace_nada(): void
    {
        $this->cobrar($this->admin);
        $pago = Pago::sole();

        $this->actingAs($this->admin)
            ->post(route('web.pagos.anular', $pago->id), ['motivo' => ''])
            ->assertSessionHasErrors('motivo');

        $this->assertSame('COMPLETADO', $pago->fresh()->estado);
    }

    /** El botón para anular vive en el historial de la ficha, y solo lo ve el dueño. */
    public function test_el_boton_de_anular_esta_en_la_ficha_y_es_solo_del_dueno(): void
    {
        $this->cobrar($this->admin);
        $pago = Pago::sole();

        $this->actingAs($this->admin)
            ->get(route('web.alumnos.show', $this->alumno->id))
            ->assertOk()
            ->assertSee(route('web.pagos.anular', $pago->id), false);

        $this->actingAs($this->operativo)
            ->get(route('web.alumnos.show', $this->alumno->id))
            ->assertOk()
            ->assertDontSee(route('web.pagos.anular', $pago->id), false);
    }

    private function cobrar(User $usuario)
    {
        return $this->actingAs($usuario)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => self::PRECIO],
            'fecha_pago' => '2026-10-05',
        ]);
    }
}
