<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Rules\MedioDePagoActivo;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/** T119: tres controles que estaban solo en la pantalla: la serie, el medio inactivo y el deporte (CLA-025, COB-026, ALU-019). */
class ControlesDelServidorT119Test extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $operativa;
    private TipoCaja $efectivo;
    private TipoCaja $inactivo;
    private TipoCaja $activo;
    private Alumno $alumno;
    private Clase $clase;
    private CajaOperativa $caja;
    private int $luz;
    private int $insumos;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 18:00:00');
        $this->seed(CatalogosSeeder::class);
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
        $this->duenio = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Federadas'])->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Zoe', 'apellido' => 'Flores', 'dni' => '50000001', 'fecha_nacimiento' => '2012-01-01', 'celular' => '1111111111',
            'nombre_tutor' => 'Tutora', 'telefono_tutor' => '1111111111', 'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha_alta' => '2026-09-01', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10', 'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->efectivo->update(['saldo_inicial' => 500000]);
        $this->activo = TipoCaja::create(['nombre' => 'Banco de prueba', 'abreviatura' => 'BDP', 'activo' => true, 'saldo_inicial' => 500000]);
        $this->inactivo = TipoCaja::create(['nombre' => 'Caja vieja', 'abreviatura' => 'VJA', 'activo' => false, 'saldo_inicial' => 500000]);
        app(\App\Services\CajaService::class)->configurarMostrador($this->efectivo->id, $this->duenio->id);
        $this->caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => '10000.00', 'efectivo_esperado' => '10000.00']);
        $this->clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => '2026-10-20', 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false, 'serie_id' => (string) Str::uuid()]);
        $this->luz = Subrubro::where('nombre', 'Luz')->value('id');
        $this->insumos = Subrubro::where('permitido_para', 'OPERATIVO')->where('afecta_caja', true)->whereHas('rubro', fn ($q) => $q->where('tipo', 'EGRESO'))->value('id');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function hermana(string $fecha): Clase
    {
        $c = $this->clase->replicate();
        $c->fecha = $fecha;
        $c->save();

        return $c;
    }

    public function test_la_operativa_no_cancela_una_serie_y_no_cancela_tampoco_la_clase_pedida(): void
    {
        $otra = $this->hermana('2026-10-27');

        $this->actingAs($this->operativa)->patchJson(route('web.clases.toggle-cancelada', $this->clase->id), ['motivo_cancelacion' => 'Mariela no puede', 'cancelar_serie' => true])
            ->assertStatus(403)->assertJsonPath('message', 'La serie la cancela el dueño. No se canceló ninguna clase.');
        $this->actingAs($this->operativa)->patch(route('web.clases.toggle-cancelada', $this->clase->id), ['motivo_cancelacion' => 'Mariela no puede', 'cancelar_serie' => 1])
            ->assertSessionHas('error', 'La serie la cancela el dueño. No se canceló ninguna clase.');

        $this->assertFalse((bool) $this->clase->fresh()->cancelada);
        $this->assertFalse((bool) $otra->fresh()->cancelada);
    }

    public function test_la_operativa_sigue_cancelando_una_clase_y_el_duenio_la_serie(): void
    {
        $otra = $this->hermana('2026-10-27');

        $this->actingAs($this->operativa)->patchJson(route('web.clases.toggle-cancelada', $this->clase->id), ['motivo_cancelacion' => 'Mariela no puede', 'cancelar_serie' => false])->assertOk();
        $this->assertTrue((bool) $this->clase->fresh()->cancelada);
        $this->assertFalse((bool) $otra->fresh()->cancelada);

        $tercera = $this->hermana('2026-11-03');
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $otra->id), ['motivo_cancelacion' => 'Se terminó el curso', 'cancelar_serie' => true])->assertOk();
        $this->assertTrue((bool) $otra->fresh()->cancelada);
        $this->assertTrue((bool) $tercera->fresh()->cancelada);
    }

    public function test_no_se_cobra_por_un_medio_inactivo_y_por_uno_activo_si(): void
    {
        $datos = ['periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 30000], 'fecha_pago' => '2026-10-15'];

        foreach ([$this->operativa, $this->duenio] as $quien) {
            $this->actingAs($quien)->post(route('web.caja.pagar', $this->alumno->id), $datos + ['tipo_caja_id' => $this->inactivo->id])
                ->assertSessionHasErrors(['tipo_caja_id' => MedioDePagoActivo::MENSAJE]);
        }
        $this->assertSame(0, Pago::count());
        $this->assertSame('PENDIENTE', DeudaCuota::where('alumno_id', $this->alumno->id)->value('estado'));

        $this->actingAs($this->operativa)->post(route('web.caja.pagar', $this->alumno->id), $datos + ['tipo_caja_id' => $this->efectivo->id])->assertSessionHasNoErrors();
        $this->assertSame(1, Pago::count());
    }

    public function test_medio_inexistente_dice_lo_de_siempre(): void
    {
        $this->actingAs($this->operativa)->post(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => 999999, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 30000], 'fecha_pago' => '2026-10-15'])
            ->assertSessionHasErrors(['tipo_caja_id' => 'El valor elegido para medio de pago no existe.']);
    }

    public function test_caja_cashflow_y_transferencias_rechazan_un_medio_inactivo(): void
    {
        $mov = ['subrubro_id' => $this->insumos, 'monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Forzado', 'tipo_caja_id' => $this->inactivo->id];
        $antes = [MovimientoOperativo::count(), CashflowMovimiento::count()];

        $this->actingAs($this->operativa)->post(route('web.caja.movimiento.store'), $mov)->assertSessionHasErrors(['tipo_caja_id' => MedioDePagoActivo::MENSAJE]);
        $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), $mov)->assertSessionHasErrors(['tipo_caja_id' => MedioDePagoActivo::MENSAJE]);
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => $this->luz] + $mov)->assertSessionHasErrors(['tipo_caja_id' => MedioDePagoActivo::MENSAJE]);
        $transferencia = ['monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Forzado'];
        foreach (['salida_id' => ['salida_id' => $this->inactivo->id, 'destino_id' => $this->activo->id], 'destino_id' => ['salida_id' => $this->activo->id, 'destino_id' => $this->inactivo->id]] as $campo => $par) {
            $this->actingAs($this->duenio)->post(route('web.cashflow.transferencia.store'), $par + $transferencia + ['clave' => (string) Str::uuid()])
                ->assertSessionHasErrors([$campo => MedioDePagoActivo::MENSAJE]);
        }

        $this->assertSame($antes, [MovimientoOperativo::count(), CashflowMovimiento::count()]);
        $this->assertSame(0, DB::table('cashflow_transferencias')->count());

        $this->actingAs($this->duenio)->post(route('web.cashflow.transferencia.store'), ['salida_id' => $this->activo->id, 'destino_id' => $this->efectivo->id, 'clave' => (string) Str::uuid()] + $transferencia)
            ->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('cashflow_transferencias')->count(), 'Entre medios activos sigue funcionando');
    }

    public function test_no_se_paga_una_liquidacion_por_un_medio_inactivo(): void
    {
        $sub = Subrubro::where('nombre', 'Luz')->firstOrFail();
        $prof = Profesor::create(['deporte_id' => $this->alumno->deporte_id, 'nombre' => 'Lucía', 'apellido' => 'Gaitán', 'dni' => '30000001', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'x', 'localidad' => 'x', 'telefono' => '1', 'valor_hora' => 1000, 'activo' => true, 'subrubro_id' => $sub->id]);
        $this->clase->update(['fecha' => '2026-10-10']);
        $this->clase->profesores()->attach($prof->id);
        DB::table('asistencias')->insert(['clase_id' => $this->clase->id, 'alumno_id' => $this->alumno->id, 'presente' => true, 'created_at' => now(), 'updated_at' => now()]);
        Carbon::setTestNow('2026-11-02 10:00:00');
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.store'), ['profesor_id' => $prof->id, 'mes' => 10, 'anio' => 2026]);
        $l = Liquidacion::where('profesor_id', $prof->id)->firstOrFail();
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.cerrar', $l->id));

        $this->actingAs($this->duenio)->post(route('web.liquidaciones.pagar', $l->id), ['fecha_pago' => '2026-11-02', 'tipo_caja_id' => $this->inactivo->id])
            ->assertSessionHasErrors(['tipo_caja_id' => MedioDePagoActivo::MENSAJE]);
        $this->assertSame('PENDIENTE', $l->fresh()->estado_pago);

        $this->actingAs($this->duenio)->post(route('web.liquidaciones.pagar', $l->id), ['fecha_pago' => '2026-11-02', 'tipo_caja_id' => $this->activo->id])->assertSessionHasNoErrors();
        $this->assertSame('PAGADA', $l->fresh()->estado_pago);
    }

    /** Se desactivó después de cargarlo: corregir el importe no obliga a cambiar el medio, y no se puede pasar a otro inactivo. */
    public function test_al_corregir_se_conserva_el_medio_que_ya_tenia_aunque_se_haya_desactivado(): void
    {
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => $this->luz, 'tipo_caja_id' => $this->activo->id, 'monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Luz'])->assertSessionHasNoErrors();
        $cf = CashflowMovimiento::orderByDesc('id')->firstOrFail();
        $this->activo->update(['activo' => false]);

        $editar = ['clave' => (string) Str::uuid(), 'version' => $cf->version_correccion, 'monto' => '150', 'fecha' => '2026-10-15', 'subrubro_id' => $this->luz, 'observaciones' => 'Luz', 'motivo' => 'Corrección'];
        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $cf->id), ['tipo_caja_id' => $this->inactivo->id] + $editar)
            ->assertSessionHasErrors(['tipo_caja_id' => MedioDePagoActivo::MENSAJE]);
        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $cf->id), ['tipo_caja_id' => $this->activo->id] + $editar)->assertSessionHasNoErrors();
        $this->assertEquals(-150, (float) $cf->fresh()->monto);

        $this->actingAs($this->operativa)->post(route('web.caja.movimiento.store'), ['subrubro_id' => $this->insumos, 'tipo_caja_id' => $this->efectivo->id, 'monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Limpieza'])->assertSessionHasNoErrors();
        $mo = MovimientoOperativo::orderByDesc('id')->firstOrFail();
        $mo->update(['tipo_caja_id' => $this->activo->id]);
        $datos = ['subrubro_id' => $this->insumos, 'monto' => '80', 'fecha' => '2026-10-15', 'observaciones' => 'Limpieza'];
        $this->actingAs($this->operativa)->put(route('web.caja.movimientos.update', [$this->caja->id, $mo->id]), ['tipo_caja_id' => $this->inactivo->id] + $datos)
            ->assertSessionHasErrors(['tipo_caja_id' => MedioDePagoActivo::MENSAJE]);
        $this->actingAs($this->operativa)->put(route('web.caja.movimientos.update', [$this->caja->id, $mo->id]), ['tipo_caja_id' => $this->activo->id] + $datos)->assertSessionHasNoErrors();
        $this->assertEquals(80, (float) $mo->fresh()->monto);
    }

    public function test_el_deporte_de_un_alumno_no_se_cambia_ni_por_envio_directo(): void
    {
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $g = Grupo::create(['deporte_id' => $futbol->id, 'nivel_id' => Nivel::create(['nombre' => 'Otro'])->id, 'activo' => true]);
        $p = GrupoPlan::create(['grupo_id' => $g->id, 'clases_por_semana' => 1, 'precio_mensual' => 1000, 'activo' => true]);
        $base = $this->alumno->only(['nombre', 'apellido', 'dni', 'celular', 'nombre_tutor', 'telefono_tutor']) + ['fecha_alta' => '2026-09-01', 'fecha_nacimiento' => '2012-01-01'];
        $antes = $this->alumno->deporte_id;

        foreach ([$this->operativa, $this->duenio] as $quien) {
            $this->actingAs($quien)->put(route('web.alumnos.update', $this->alumno->id), $base + ['deporte_id' => $futbol->id, 'grupo_id' => $g->id, 'plan_id' => $p->id])
                ->assertSessionHasErrors(['deporte_id' => 'No se puede cambiar el deporte de un alumno: cargá otra ficha para el otro deporte.']);
        }
        $this->assertSame($antes, $this->alumno->fresh()->deporte_id);
        $this->assertSame(1, AlumnoPlan::where('alumno_id', $this->alumno->id)->count());

        $this->actingAs($this->operativa)->put(route('web.alumnos.update', $this->alumno->id), array_merge($base, ['nombre' => 'Zoé', 'deporte_id' => $antes, 'grupo_id' => $this->alumno->grupo_id]))->assertSessionHasNoErrors();
        $this->assertSame('Zoé', $this->alumno->fresh()->nombre, 'Editar sin cambiar el deporte sigue funcionando');
    }
}
