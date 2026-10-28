<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CajaOperativa, DeudaCuota, Deporte, Grupo, GrupoPlan, Nivel, Pago, TipoCaja, User};
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CobroInactivoT25VerificacionGeminiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private GrupoPlan $plan;
    private Grupo $grupo;
    private TipoCaja $efectivo;
    private CajaOperativa $cajaOperativa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        $this->withoutVite();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->cajaOperativa = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativa->id,
            'usuario_apertura_id' => $this->operativa->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
            'tipo_caja_efectivo_id' => $this->efectivo->id,
            'efectivo_inicial' => 0,
        ]);

        $deporte = Deporte::create(['nombre' => 'Patín T25 Gem', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel T25 Gem']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
    }

    public function test_operativa_puede_cobrar_con_no_y_queda_inactivo(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        // 1. Sin reactivar: pide reactivación con 409
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 10000],
        ])->assertStatus(409)->assertJson(['requiere_reactivacion' => true, 'permite_no' => true]);

        // 2. Con No (reactivar = 0)
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 10000],
            'reactivar' => 0,
        ])->assertRedirect(route('web.caja.index'));

        $this->assertFalse($alumno->fresh()->activo);
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->count());
        $pago = Pago::where('alumno_id', $alumno->id)->latest('id')->first();
        $this->assertNotNull($pago);
        $this->assertDatabaseHas('movimientos_operativos', ['pago_id' => $pago->id]);
    }

    public function test_operativa_puede_cobrar_con_si_y_reactiva_con_cuota_reingreso(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 10000],
            'reactivar' => 1,
        ])->assertRedirect(route('web.caja.index'));

        $this->assertTrue($alumno->fresh()->activo);
        $octubre = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->sole();
        $this->assertNotNull($octubre->fecha_reingreso);
    }

    public function test_combinacion_con_fecha_de_mes_cerrado(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        // Intento pagar con fecha de septiembre (mes cerrado)
        $payload = [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 10000],
            'fecha_pago' => '2026-09-25',
        ];

        // 1. Primero salta fecha vieja (409)
        $res1 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res1->assertStatus(409)->assertJson(['requiere_confirmacion_fecha_vieja' => true]);

        // 2. Con confirmar_fecha_vieja, salta reactivacion (409)
        $payload['confirmar_fecha_vieja'] = 1;
        $res2 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res2->assertStatus(409)->assertJson(['requiere_reactivacion' => true, 'permite_no' => true]);

        // 3. Con reactivar = 0 y confirmar_fecha_vieja = 1, cobra y queda inactivo
        $payload['reactivar'] = 0;
        $res3 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res3->assertRedirect(route('web.caja.index'));

        $this->assertFalse($alumno->fresh()->activo);
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->count());
    }

    public function test_combinacion_con_mes_adelantado(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        // Intento pagar noviembre adelantado (2026-11)
        $payload = [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-09', '2026-11'],
            'montos_cuota' => ['2026-09' => 10000, '2026-11' => 10000],
        ];

        // 1. Primero salta confirmar_pago_adelantado (409)
        $res1 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res1->assertStatus(409)->assertJson(['requiere_confirmacion_adelantado' => true]);

        // 2. Con confirmar_pago_adelantado = 1, salta reactivacion pero permite_no = false!
        $payload['confirmar_pago_adelantado'] = 1;
        $res2 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res2->assertStatus(409)->assertJson(['requiere_reactivacion' => true, 'permite_no' => false]);

        // 3. Si se intenta forzar reactivar = 0, se rechaza con 422
        $payload['reactivar'] = 0;
        $res3 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res3->assertStatus(422);

        // 4. Con reactivar = 1, cobra y reactiva
        $payload['reactivar'] = 1;
        $res4 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res4->assertRedirect(route('web.caja.index'));

        $this->assertTrue($alumno->fresh()->activo);
        $this->assertTrue(DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-11')->exists());
    }

    public function test_combinacion_con_cobro_parcial_b3(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        // Cobro parcial: debe 10.000 de septiembre pero se cobran 6.000
        $payload = [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 6000],
        ];

        // 1. Primero salta reactivación (409)
        $res1 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res1->assertStatus(409)->assertJson(['requiere_reactivacion' => true, 'permite_no' => true]);

        // 2. Con reactivar = 0, salta B3 (requiere_confirmacion / requiere_motivo)
        $payload['reactivar'] = 0;
        $res2 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res2->assertStatus(409)->assertJson(['requiere_confirmacion' => true, 'requiere_motivo' => true]);

        // 3. Con reactivar = 0, confirmar_deuda_anterior = 1 y motivo
        $payload['confirmar_deuda_anterior'] = 1;
        $payload['motivo'] = 'Pago parcial acordado para cancelar deuda vieja';
        $res3 = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $payload);
        $res3->assertRedirect(route('web.caja.index'));

        $this->assertFalse($alumno->fresh()->activo);
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->count());
        $this->assertDatabaseHas('excepciones_cobranza', [
            'alumno_id' => $alumno->id,
            'motivo' => 'Pago parcial acordado para cancelar deuda vieja',
        ]);
    }

    public function test_despues_de_no_el_generador_del_dia_1_no_crea_cuota(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        // Cobra con No
        $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 10000],
            'reactivar' => 0,
        ])->assertRedirect(route('web.caja.index'));

        $this->assertFalse($alumno->fresh()->activo);

        // Viajar al 1 de noviembre (2026-11-01)
        $this->travelTo(Carbon::parse('2026-11-01 06:00:00'));

        $this->artisan('cobranza:generar-deudas')->assertSuccessful();

        // No debe tener cuota de noviembre
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-11')->count());
        $this->assertFalse($alumno->fresh()->activo);
    }

    public function test_recibo_de_pago_se_genera_bien_tras_cobro_con_no(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        $res = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 10000],
            'reactivar' => 0,
        ])->assertRedirect(route('web.caja.index'));

        $pago = Pago::where('alumno_id', $alumno->id)->latest('id')->firstOrFail();
        $this->assertNotNull($pago);

        // Recibo route
        $this->actingAs($this->admin)->get(route('web.recibos.cuota', $pago->id))->assertOk();
    }

    private function inactivoQueDebeSeptiembre(): Alumno
    {
        $alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Inactivo', 'dni' => '25000002',
            'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => '2026-03-01',
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $this->plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
        $this->cuota($alumno, '2026-08', 10000);
        $this->cuota($alumno, '2026-09', 10000);
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $alumno->id, 'tipo_caja_id' => $this->efectivo->id,
            'items' => [['periodo' => '2026-08', 'monto' => 10000]], 'usuario_admin_id' => $this->admin->id, 'monto_entregado' => 10000]);
        $alumno->update(['activo' => false]);
        return $alumno->fresh();
    }

    private function cuota(Alumno $alumno, string $periodo, float $monto): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => $periodo,
            'monto_original' => $monto, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }
}
