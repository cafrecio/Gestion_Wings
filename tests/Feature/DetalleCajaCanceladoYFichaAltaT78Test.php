<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use App\Support\FormatoImporte;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T78 — El detalle de caja muestra el motivo del cobro cancelado,
 * y la ficha del alumno nuevo muestra su vencimiento y total adeudado.
 */
class DetalleCajaCanceladoYFichaAltaT78Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogosSeeder::class);
        Configuracion::set('dias_gracia_cobranza', 10);
        Configuracion::set('dias_plazo_alta', 7);
        Configuracion::set('inscripcion_importe', '5000');
        Carbon::setTestNow('2026-10-13 18:15:00');

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $this->deporte = Deporte::where('nombre', 'Fútbol')->firstOrFail();
        $nivel = Nivel::where('nombre', 'Principiantes')->firstOrFail();
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 40000,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_detalle_de_caja_muestra_el_motivo_de_un_cobro_cancelado_para_operativa_y_dueno(): void
    {
        $tipoMp = TipoCaja::where('nombre', 'Mercado Pago')->firstOrFail();
        $subrubroCuota = Subrubro::where('nombre', 'Cuota Mensual')->firstOrFail();
        $caja = CajaDeclarada::crear($this->operativo->id, $tipoMp->id);

        $alumno = $this->crearAlumno('Paz', 'Bruno', '2026-03-01');
        $deuda = DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 40000,
            'monto_pagado' => 0,
            'monto_condonado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $motivo = 'Medio equivocado: cobró MP, era Efectivo';
        $pago = Pago::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'mes' => 10,
            'anio' => 2026,
            'monto_base' => 40000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 40000,
            'monto_cuota' => 40000,
            'fecha_pago' => '2026-10-16',
            'estado' => Pago::ESTADO_ANULADO,
        ]);
        $pago->deudasCuota()->attach($deuda->id, ['monto_aplicado' => 40000]);

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'usuario_id' => $this->operativo->id,
            'fecha' => '2026-10-16',
            'subrubro_id' => $subrubroCuota->id,
            'tipo_caja_id' => $tipoMp->id,
            'alumno_id' => $alumno->id,
            'pago_id' => $pago->id,
            'monto' => 40000,
            'observaciones' => 'Cuota Fútbol 2026-10',
            'estado' => 'CANCELADO',
            'cancelado_por_id' => $this->operativo->id,
            'cancelado_at' => now(),
            'motivo_cancelacion' => $motivo,
        ]);

        // 1. La operativa mira su caja abierta.
        $this->actingAs($this->operativo)
            ->get(route('web.caja.detalle', $caja->id))
            ->assertOk()
            ->assertSee('Cancelado: ' . $motivo);

        // 2. Caja cerrada: tanto la operativa como el dueño ven "Cancelado" junto al motivo.
        $caja->update([
            'estado' => CajaOperativa::ESTADO_CERRADA,
            'cierre_at' => now(),
            'efectivo_contado' => 0,
        ]);

        $this->actingAs($this->operativo)
            ->get(route('web.caja.detalle', $caja->id))
            ->assertOk()
            ->assertSee('Cancelado: ' . $motivo);

        $this->actingAs($this->admin)
            ->get(route('web.caja.detalle', $caja->id))
            ->assertOk()
            ->assertSee('Cancelado: ' . $motivo);
    }

    public function test_la_ficha_de_un_alumno_con_alta_reciente_muestra_vencimiento_y_total_igual_a_cobranza(): void
    {
        $mora = $this->crearAlumno('Benítez', 'Mora', '2026-10-13');

        DeudaCuota::create([
            'alumno_id' => $mora->id,
            'periodo' => '2026-10',
            'monto_original' => 40000,
            'monto_pagado' => 0,
            'monto_condonado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        CargoAlumno::create([
            'alumno_id' => $mora->id,
            'dni' => $mora->dni,
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'inscripcion:dni:' . $mora->dni,
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => 5000,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => '2026-10-13'],
            'estado' => 'VIGENTE',
        ]);

        $cobranzaService = app(CobranzaEstadoService::class);
        $filaCobranza = $cobranzaService->listadoCobranza('TODOS')->firstWhere('id', $mora->id);
        $this->assertNotNull($filaCobranza);
        $this->assertSame(45000.0, (float) $filaCobranza->deuda_total);

        $totalFormateado = FormatoImporte::pesos((float) $filaCobranza->deuda_total);

        // Alta 13/10 + 7 días de plazo de alta = vence el 20/10/2026; total = $45,000.00.
        $this->actingAs($this->operativo)
            ->get(route('web.alumnos.show', $mora->id))
            ->assertOk()
            ->assertSee('En plazo')
            ->assertSee('6 días de gracia')
            ->assertSee('Vence 20/10/2026')
            ->assertSee('Total adeudado:')
            ->assertSee($totalFormateado);

        $this->actingAs($this->admin)
            ->get(route('web.alumnos.show', $mora->id))
            ->assertOk()
            ->assertSee('En plazo')
            ->assertSee('Vence 20/10/2026')
            ->assertSee('Total adeudado:')
            ->assertSee($totalFormateado);
    }

    public function test_un_alumno_al_dia_no_muestra_vencimiento_ni_total(): void
    {
        $alumnoAlDia = $this->crearAlumno('Gómez', 'Lucas', '2026-03-01');

        DeudaCuota::create([
            'alumno_id' => $alumnoAlDia->id,
            'periodo' => '2026-10',
            'monto_original' => 40000,
            'monto_pagado' => 40000,
            'monto_condonado' => 0,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        $this->actingAs($this->operativo)
            ->get(route('web.alumnos.show', $alumnoAlDia->id))
            ->assertOk()
            ->assertSee('Al día')
            ->assertDontSee('Vence ')
            ->assertDontSee('Total adeudado:');
    }

    private function crearAlumno(string $apellido, string $nombre, string $fechaAlta): Alumno
    {
        $alumno = Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => (string) random_int(30000000, 39999999),
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_nacimiento' => '2012-05-10',
            'celular' => '11-4000-0000',
            'fecha_alta' => $fechaAlta,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => $fechaAlta,
            'activo' => true,
        ]);

        return $alumno;
    }
}
