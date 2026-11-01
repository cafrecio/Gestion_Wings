<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\AlumnoRevisionCobranza;
use App\Models\Asistencia;
use App\Models\CajaOperativa;
use App\Models\CargoAlumno;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T100 (Carlos, 01/11/2026): cancelar un adelanto deja todo como si ese cobro no hubiera existido.
 * Reemplaza CJ-26, por la que la cuota quedaba por el importe cobrado: $300.000 por un cero de más
 * dejaban $270.000 de deuda después de cobrar bien los $30.000.
 */
class AdelantoCanceladoT100Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private Alumno $alumno;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 10:00:00');

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Cuota Mensual', 'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true, 'es_reservado_sistema' => true]);

        $this->efectivo = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->caja = \Tests\Support\CajaDeclarada::crear($this->operativo->id, $this->efectivo->id);

        $deporte = Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Clara', 'apellido' => 'Iglesias', 'dni' => '40111222',
            'fecha_nacimiento' => '2012-05-10', 'celular' => '1144445555', 'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id, 'fecha_alta' => '2026-01-10', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-01-10', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10', 'monto_original' => 30000,
            'monto_pagado' => 30000, 'estado' => DeudaCuota::ESTADO_PAGADA]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cobrarAdelanto(User $quien, float $monto, array $periodos = ['2026-11'], array $extra = []): Pago
    {
        $this->actingAs($quien)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => $periodos,
            'montos_cuota' => array_fill_keys($periodos, $monto),
            'fecha_pago' => now()->toDateString(),
            'confirmar_pago_adelantado' => 1,
        ] + $extra)->assertSessionHas('success');

        return Pago::latest('id')->firstOrFail();
    }

    private function cancelar(Pago $pago): void
    {
        $movimiento = MovimientoOperativo::where('pago_id', $pago->id)->firstOrFail();
        $this->actingAs($this->operativo)
            ->post(route('web.caja.movimientos.cancelar.store', [$this->caja->id, $movimiento->id]), ['motivo' => 'Un cero de más'])
            ->assertSessionHas('success');
    }

    private function anular(Pago $pago): void
    {
        $this->actingAs($this->admin)->post(route('web.pagos.anular', $pago->id), ['motivo' => 'Un cero de más en el importe'])
            ->assertSessionHas('success');
    }

    private function noviembre(): ?DeudaCuota
    {
        return DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->first();
    }

    public function test_el_adelanto_recuerda_el_cobro_del_que_nacio(): void
    {
        $pago = $this->cobrarAdelanto($this->operativo, 300000);

        $this->assertSame($pago->id, $this->noviembre()->pago_origen_id);
    }

    public function test_cancelar_y_cobrar_bien_no_deja_saldo_fantasma(): void
    {
        $this->cancelar($this->cobrarAdelanto($this->operativo, 300000));
        $this->assertNull($this->noviembre());

        // El cobro correcto no pide motivo de parcial: es un adelanto nuevo.
        $this->cobrarAdelanto($this->operativo, 30000);

        $noviembre = $this->noviembre();
        $this->assertSame('30000.00', $noviembre->monto_original);
        $this->assertSame('30000.00', $noviembre->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $noviembre->estado);
    }

    public function test_cancelar_baja_el_efectivo_esperado_y_conserva_el_detalle(): void
    {
        $esperado = fn () => (float) app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado'];
        $antes = $esperado();
        $pago = $this->cobrarAdelanto($this->operativo, 300000);
        $this->assertSame($antes + 300000, $esperado());

        $this->cancelar($pago);

        $this->assertSame($antes, $esperado());
        $pago->refresh();
        $this->assertSame('ANULADO', $pago->estado);
        $this->assertSame([['periodo' => '2026-11', 'monto_aplicado' => '300000.00']], $pago->detalle_anulacion['periodos']);
        $this->actingAs($this->admin)->get(route('web.alumnos.show', $this->alumno->id))->assertOk();
    }

    public function test_el_duenio_anula_su_adelanto_y_la_cuota_tampoco_queda(): void
    {
        $pago = $this->cobrarAdelanto($this->admin, 300000);

        $this->anular($pago);

        $this->assertNull($this->noviembre());
    }

    public function test_al_llegar_el_mes_con_asistencia_se_genera_a_precio_del_plan(): void
    {
        $this->cancelar($this->cobrarAdelanto($this->operativo, 300000));
        $clase = Clase::create(['grupo_id' => $this->alumno->grupo_id, 'fecha' => '2026-10-20',
            'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false]);
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);

        Carbon::setTestNow('2026-11-01 06:00:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();

        $noviembre = $this->noviembre();
        $this->assertSame('30000.00', $noviembre->monto_original);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $noviembre->estado);
    }

    public function test_al_llegar_el_mes_sin_asistencia_va_a_revision_y_no_debe_nada(): void
    {
        $this->cancelar($this->cobrarAdelanto($this->operativo, 300000));

        Carbon::setTestNow('2026-11-01 06:00:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();

        $this->assertNull($this->noviembre());
        $this->assertSame(1, AlumnoRevisionCobranza::where('alumno_id', $this->alumno->id)->where('periodo_objetivo', '2026-11')->count());
    }

    public function test_con_el_mes_ya_empezado_no_se_borra_y_vale_el_precio_del_plan(): void
    {
        $pago = $this->cobrarAdelanto($this->operativo, 300000);
        $movimiento = MovimientoOperativo::where('pago_id', $pago->id)->firstOrFail();

        Carbon::setTestNow('2026-11-03 10:00:00');
        app(PagoCuotaService::class)->cancelarCobroOperativo($movimiento->id, 'Un cero de más', $this->operativo->id);

        $noviembre = $this->noviembre();
        $this->assertSame('30000.00', $noviembre->monto_original);
        $this->assertSame('0.00', $noviembre->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $noviembre->estado);
        $this->assertNull($noviembre->pago_origen_id);
    }

    public function test_cancelar_un_cobro_de_varios_meses_solo_borra_los_que_nacieron_de_el(): void
    {
        DeudaCuota::where('periodo', '2026-10')->update(['monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);

        $pago = $this->cobrarAdelanto($this->operativo, 30000, ['2026-10', '2026-11']);
        $this->cancelar($pago);

        $octubre = DeudaCuota::where('periodo', '2026-10')->sole();
        $this->assertSame('30000.00', $octubre->monto_original);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $octubre->estado);
        $this->assertNull($this->noviembre());
    }

    public function test_una_cuota_que_ya_existia_sigue_igual(): void
    {
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-11', 'monto_original' => 25000,
            'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);

        $this->cancelar($this->cobrarAdelanto($this->operativo, 25000));

        $noviembre = $this->noviembre();
        $this->assertSame('25000.00', $noviembre->monto_original);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $noviembre->estado);
    }

    public function test_no_se_deshace_mientras_otro_cobro_la_sostenga(): void
    {
        $primero = $this->cobrarAdelanto($this->admin, 30000);
        $this->noviembre()->update(['monto_original' => 50000, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
        $segundo = $this->cobrarAdelanto($this->admin, 20000, ['2026-11'], ['motivo' => 'Completa el importe pactado']);

        $this->anular($primero);
        $this->assertSame('20000.00', $this->noviembre()->monto_pagado);

        $this->anular($segundo);
        $this->assertNull($this->noviembre());
    }

    public function test_con_un_recargo_atado_no_se_borra(): void
    {
        $pago = $this->cobrarAdelanto($this->admin, 300000);
        CargoAlumno::create(['alumno_id' => $this->alumno->id, 'deuda_cuota_id' => $this->noviembre()->id, 'tipo' => 'PUNITORIO',
            'clave_origen' => 'punitorio:prueba-t100', 'monto_original' => 100, 'estado' => 'VIGENTE', 'calculo' => [],
            'subrubro_id' => Subrubro::where('nombre', 'Cuota Mensual')->value('id')]);

        $this->anular($pago);

        $this->assertSame('30000.00', $this->noviembre()->monto_original);
    }

    public function test_con_una_parte_dada_por_perdida_no_se_toca(): void
    {
        $pago = $this->cobrarAdelanto($this->admin, 300000);
        $this->noviembre()->forceFill(['monto_perdido' => 100])->save();

        $this->anular($pago);

        $this->assertNotNull($this->noviembre());
        $this->assertSame('300000.00', $this->noviembre()->monto_original);
    }
}
