<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\ReglaPrimerPago;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobrarPrimeraCuotaWebTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private TipoCaja $tipoCaja;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-15 10:00:00');

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);
        $this->tipoCaja = TipoCaja::create(['nombre' => 'Caja General', 'activo' => true]);
        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 28000,
            'activo' => true,
        ]);
        $this->alumno = Alumno::create([
            'nombre' => 'Primera',
            'apellido' => 'Cuota',
            'dni' => '30123456',
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-08-01',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-08-01',
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_la_pantalla_ofrece_el_periodo_vigente_sin_crear_la_deuda(): void
    {
        $this->assertDatabaseCount('deuda_cuotas', 0);

        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar-cuota'))
            ->assertOk()
            ->assertSee('Cuota, Primera');

        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $this->alumno->id))
            ->assertOk()
            ->assertSee('Agosto 2026');

        $this->assertDatabaseCount('deuda_cuotas', 0);
    }

    public function test_el_cobro_web_autocrea_y_paga_la_primera_deuda(): void
    {
        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => ['2026-08'],
                'montos_cuota' => ['2026-08' => 28000],
                'fecha_pago' => '2026-08-15',
            ])
            ->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('success');

        $deuda = DeudaCuota::where('alumno_id', $this->alumno->id)
            ->where('periodo', '2026-08')
            ->firstOrFail();

        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deuda->estado);
        $this->assertSame(28000.0, (float) $deuda->monto_original);
        $this->assertSame(28000.0, (float) $deuda->monto_pagado);
        $this->assertDatabaseHas('pagos', [
            'alumno_id' => $this->alumno->id,
            'monto_final' => '28000.00',
        ]);
        $this->assertDatabaseHas('pago_deuda_cuota', [
            'deuda_cuota_id' => $deuda->id,
            'monto_aplicado' => '28000.00',
        ]);
        $this->assertDatabaseHas('movimientos_operativos', [
            'alumno_id' => $this->alumno->id,
            'monto' => '28000.00',
        ]);
        $this->assertDatabaseCount('cajas_operativas', 1);
        $this->assertSame(
            CobranzaEstadoService::ESTADO_AL_DIA,
            app(CobranzaEstadoService::class)->estadoAlumno($this->alumno->id)['estado']
        );
    }

    public function test_la_primera_cuota_autocreada_con_descuento_deja_al_alumno_al_dia(): void
    {
        Carbon::setTestNow('2026-08-20 10:00:00');
        $this->alumno->update(['fecha_alta' => '2026-08-20']);
        ReglaPrimerPago::create([
            'nombre' => 'Segunda quincena',
            'dia_desde' => 16,
            'dia_hasta' => 23,
            'porcentaje' => 70,
            'activo' => true,
        ]);

        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => ['2026-08'],
                'montos_cuota' => ['2026-08' => 28000],
                'fecha_pago' => '2026-08-20',
            ])
            ->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-08',
            'monto_original' => '19600.00',
            'monto_pagado' => '19600.00',
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
        $this->assertSame(
            CobranzaEstadoService::ESTADO_AL_DIA,
            app(CobranzaEstadoService::class)->estadoAlumno($this->alumno->id)['estado']
        );
    }
}
