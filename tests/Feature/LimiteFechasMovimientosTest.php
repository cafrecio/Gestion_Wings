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
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FIN-09: Límites de fechas al cargar movimientos y cobros.
 * - Posterior a hoy: rechazada.
 * - Mes en curso: se guarda sin aviso.
 * - Mes anterior o más viejo: confirmación antes de guardar.
 * - El movimiento conserva su fecha real y entra en la caja abierta actual.
 * - Las cajas cerradas o validadas no se tocan.
 */
class LimiteFechasMovimientosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $tipoCaja;
    private Subrubro $subrubroIngreso;
    private CajaOperativa $cajaAbierta;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-19 10:00:00');

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);

        $rubro = Rubro::create(['nombre' => 'General', 'tipo' => 'INGRESO', 'observacion' => '']);
        $this->subrubroIngreso = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Venta de Materiales',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => false,
            'activo' => true,
        ]);

        $this->cajaAbierta = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_fecha_manana_es_rechazada_en_alta_y_edicion_de_caja(): void
    {
        $fechaFutura = '2026-09-20';

        // 1. Alta en caja abierta (editarStore)
        $this->actingAs($this->operativo)
            ->post(route('web.caja.editar.store', $this->cajaAbierta->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 1500,
                'fecha' => $fechaFutura,
                'observaciones' => 'Prueba futura',
            ])
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseMissing('movimientos_operativos', [
            'monto' => 1500,
            'fecha' => $fechaFutura,
        ]);

        // 2. Edición de movimiento existente (updateMovimiento)
        $mov = MovimientoOperativo::create([
            'caja_operativa_id' => $this->cajaAbierta->id,
            'tipo_caja_id' => $this->tipoCaja->id,
            'subrubro_id' => $this->subrubroIngreso->id,
            'monto' => 2000,
            'fecha' => '2026-09-19',
            'usuario_id' => $this->operativo->id,
            'observaciones' => 'Original',
            'estado' => 'ACTIVO',
        ]);

        $this->actingAs($this->operativo)
            ->put(route('web.caja.movimientos.update', [$this->cajaAbierta->id, $mov->id]), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 2000,
                'fecha' => $fechaFutura,
                'observaciones' => 'Intento editar a futuro',
            ])
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseHas('movimientos_operativos', [
            'id' => $mov->id,
            'fecha' => '2026-09-19',
        ]);
    }

    public function test_fecha_manana_es_rechazada_en_cashflow(): void
    {
        $fechaFutura = '2026-09-20';

        $this->actingAs($this->admin)
            ->post(route('web.cashflow.movimiento.store'), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 3000,
                'fecha' => $fechaFutura,
                'observaciones' => 'Cashflow futuro',
            ])
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseMissing('cashflow_movimientos', [
            'monto' => 3000,
            'fecha' => $fechaFutura,
        ]);
    }

    public function test_fecha_de_hoy_y_primer_dia_del_mes_se_cargan_sin_aviso(): void
    {
        // Fecha de hoy: 2026-09-19
        $this->actingAs($this->operativo)
            ->post(route('web.caja.editar.store', $this->cajaAbierta->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 1000,
                'fecha' => '2026-09-19',
                'observaciones' => 'De hoy',
            ])
            ->assertRedirect(route('web.caja.resumen', $this->cajaAbierta->id))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('aviso_fecha_vieja');

        $this->assertDatabaseHas('movimientos_operativos', [
            'monto' => 1000,
            'fecha' => '2026-09-19',
        ]);

        // Primer día del mes en curso: 2026-09-01
        $this->actingAs($this->operativo)
            ->post(route('web.caja.editar.store', $this->cajaAbierta->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 1200,
                'fecha' => '2026-09-01',
                'observaciones' => 'Primer día del mes',
            ])
            ->assertRedirect(route('web.caja.resumen', $this->cajaAbierta->id))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('aviso_fecha_vieja');

        $this->assertDatabaseHas('movimientos_operativos', [
            'monto' => 1200,
            'fecha' => '2026-09-01',
        ]);
    }

    public function test_ultimo_dia_del_mes_anterior_pide_confirmacion_y_segundo_envio_guarda(): void
    {
        $ultimoDiaMesAnterior = '2026-08-31';

        // 1er envío sin confirmación: no guarda y vuelve con aviso
        $response = $this->actingAs($this->operativo)
            ->from(route('web.caja.editar', $this->cajaAbierta->id))
            ->post(route('web.caja.editar.store', $this->cajaAbierta->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 5000,
                'fecha' => $ultimoDiaMesAnterior,
                'observaciones' => 'Cobro tardío MP',
            ]);

        $response->assertRedirect(route('web.caja.editar', $this->cajaAbierta->id));
        $response->assertSessionHas('aviso_fecha_vieja');

        $this->assertDatabaseMissing('movimientos_operativos', [
            'monto' => 5000,
            'fecha' => $ultimoDiaMesAnterior,
        ]);

        // 2do envío con confirmar_fecha_vieja = 1: guarda exitosamente
        $this->actingAs($this->operativo)
            ->post(route('web.caja.editar.store', $this->cajaAbierta->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 5000,
                'fecha' => $ultimoDiaMesAnterior,
                'observaciones' => 'Cobro tardío MP',
                'confirmar_fecha_vieja' => '1',
            ])
            ->assertRedirect(route('web.caja.resumen', $this->cajaAbierta->id))
            ->assertSessionMissing('aviso_fecha_vieja');

        $this->assertDatabaseHas('movimientos_operativos', [
            'caja_operativa_id' => $this->cajaAbierta->id,
            'monto' => 5000,
            'fecha' => $ultimoDiaMesAnterior,
        ]);
    }

    public function test_fecha_tres_meses_atras_guarda_en_caja_abierta_sin_tocar_caja_cerrada(): void
    {
        $fechaVieja = '2026-06-15';

        // Crear una caja ya VALIDADA en junio con un movimiento previo
        $cajaCerradaVieja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at' => Carbon::parse('2026-06-10 09:00:00'),
            'cierre_at' => Carbon::parse('2026-06-10 18:00:00'),
            'estado' => CajaOperativa::ESTADO_VALIDADA,
        ]);
        MovimientoOperativo::create([
            'caja_operativa_id' => $cajaCerradaVieja->id,
            'tipo_caja_id' => $this->tipoCaja->id,
            'subrubro_id' => $this->subrubroIngreso->id,
            'monto' => 10000,
            'fecha' => '2026-06-10',
            'usuario_id' => $this->operativo->id,
            'estado' => 'ACTIVO',
        ]);

        $conteoAntes = $cajaCerradaVieja->movimientos()->count();
        $totalAntes = $cajaCerradaVieja->movimientos()->sum('monto');

        // Confirmar y guardar movimiento con fecha 2026-06-15 en la caja de hoy
        $this->actingAs($this->operativo)
            ->post(route('web.caja.editar.store', $this->cajaAbierta->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 4500,
                'fecha' => $fechaVieja,
                'observaciones' => 'Gasto de junio cargado hoy',
                'confirmar_fecha_vieja' => '1',
            ])
            ->assertRedirect(route('web.caja.resumen', $this->cajaAbierta->id));

        // Verificación 1: el movimiento quedó en la caja abierta de hoy con su fecha real
        $this->assertDatabaseHas('movimientos_operativos', [
            'caja_operativa_id' => $this->cajaAbierta->id,
            'monto' => 4500,
            'fecha' => $fechaVieja,
        ]);

        // Verificación 2: la caja cerrada de junio NO cambió en nada
        $this->assertEquals($conteoAntes, $cajaCerradaVieja->movimientos()->count());
        $this->assertEquals($totalAntes, $cajaCerradaVieja->movimientos()->sum('monto'));
    }

    public function test_editar_movimiento_con_fecha_vieja_pide_confirmacion(): void
    {
        $mov = MovimientoOperativo::create([
            'caja_operativa_id' => $this->cajaAbierta->id,
            'tipo_caja_id' => $this->tipoCaja->id,
            'subrubro_id' => $this->subrubroIngreso->id,
            'monto' => 2000,
            'fecha' => '2026-09-10',
            'usuario_id' => $this->operativo->id,
            'estado' => 'ACTIVO',
        ]);

        $fechaVieja = '2026-07-20';

        // 1er intento sin confirmación: vuelve con aviso
        $this->actingAs($this->operativo)
            ->from(route('web.caja.movimientos.editar', [$this->cajaAbierta->id, $mov->id]))
            ->put(route('web.caja.movimientos.update', [$this->cajaAbierta->id, $mov->id]), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 2000,
                'fecha' => $fechaVieja,
                'observaciones' => 'Corrección a julio',
            ])
            ->assertRedirect(route('web.caja.movimientos.editar', [$this->cajaAbierta->id, $mov->id]))
            ->assertSessionHas('aviso_fecha_vieja');

        $this->assertDatabaseHas('movimientos_operativos', [
            'id' => $mov->id,
            'fecha' => '2026-09-10',
        ]);

        // 2do intento confirmado
        $this->actingAs($this->operativo)
            ->put(route('web.caja.movimientos.update', [$this->cajaAbierta->id, $mov->id]), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 2000,
                'fecha' => $fechaVieja,
                'observaciones' => 'Corrección a julio',
                'confirmar_fecha_vieja' => '1',
            ])
            ->assertRedirect(route('web.caja.detalle', $this->cajaAbierta->id));

        $this->assertDatabaseHas('movimientos_operativos', [
            'id' => $mov->id,
            'fecha' => $fechaVieja,
        ]);
    }

    public function test_cashflow_store_con_fecha_vieja_pide_confirmacion_y_luego_guarda(): void
    {
        $fechaVieja = '2026-08-15';

        // 1er intento: aviso
        $this->actingAs($this->admin)
            ->from(route('web.cashflow.movimiento'))
            ->post(route('web.cashflow.movimiento.store'), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 7000,
                'fecha' => $fechaVieja,
                'observaciones' => 'Cashflow agosto',
            ])
            ->assertRedirect(route('web.cashflow.movimiento'))
            ->assertSessionHas('aviso_fecha_vieja');

        $this->assertDatabaseMissing('cashflow_movimientos', [
            'monto' => 7000,
            'fecha' => $fechaVieja,
        ]);

        // 2do intento: confirmado
        $this->actingAs($this->admin)
            ->post(route('web.cashflow.movimiento.store'), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubroIngreso->id,
                'monto' => 7000,
                'fecha' => $fechaVieja,
                'observaciones' => 'Cashflow agosto',
                'confirmar_fecha_vieja' => '1',
            ])
            ->assertRedirect(route('web.cashflow.index'));

        $this->assertDatabaseHas('cashflow_movimientos', [
            'monto' => 7000,
            'fecha' => $fechaVieja,
        ]);
    }

    public function test_cobro_cuota_fecha_pago_mes_anterior_requiere_confirmacion_409(): void
    {
        $rubroCuota = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create([
            'rubro_id' => $rubroCuota->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);

        $deporte = Deporte::create([
            'nombre' => 'Patin',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['deporte_id' => $deporte->id, 'nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'dia_semana' => 1,
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
        ]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'nombre' => 'Plan Mensual',
            'precio_mensual' => 25000,
            'clases_por_semana' => 1,
            'activo' => true,
        ]);
        $alumno = Alumno::create([
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'apellido' => 'Gomez',
            'nombre' => 'Lucia',
            'dni' => '40111222',
            'celular' => '1144445555',
            'fecha_nacimiento' => '2010-01-01',
            'fecha_alta' => '2026-01-10',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-08-01',
            'activo' => true,
        ]);
        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-08',
            'monto_original' => 25000,
            'monto_pagado' => 0,
            'saldo_pendiente' => 25000,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $fechaPagoVieja = '2026-08-31';

        // 1. Envío sin confirmar: 409 con requiere_confirmacion_fecha_vieja
        $res = $this->actingAs($this->operativo)
            ->postJson(route('web.caja.pagar', $alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => ['2026-08'],
                'montos_cuota' => ['2026-08' => 25000],
                'fecha_pago' => $fechaPagoVieja,
            ]);

        $res->assertStatus(409)
            ->assertJson([
                'success' => false,
                'requiere_confirmacion_fecha_vieja' => true,
            ]);

        $this->assertDatabaseMissing('pagos', [
            'alumno_id' => $alumno->id,
        ]);

        // 2. Envío con confirmar_fecha_vieja = 1: se cobra con fecha real
        $res2 = $this->actingAs($this->operativo)
            ->postJson(route('web.caja.pagar', $alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => ['2026-08'],
                'montos_cuota' => ['2026-08' => 25000],
                'fecha_pago' => $fechaPagoVieja,
                'confirmar_fecha_vieja' => true,
            ]);

        $res2->assertRedirect(route('web.caja.index'));

        $this->assertDatabaseHas('pagos', [
            'alumno_id' => $alumno->id,
            'monto_final' => 25000,
            'fecha_pago' => $fechaPagoVieja,
        ]);

        $this->assertDatabaseHas('movimientos_operativos', [
            'caja_operativa_id' => $this->cajaAbierta->id,
            'fecha' => $fechaPagoVieja,
            'monto' => 25000,
        ]);
    }
}
