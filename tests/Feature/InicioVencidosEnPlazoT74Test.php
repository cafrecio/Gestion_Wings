<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T74 (DU-60, decisión de Carlos 16/10): el Inicio de la operativa muestra los vencidos
 * —el mismo número que Cobranza— y, aparte, los que deben pero están en plazo. Las dos
 * cifras suman lo que anuncia el buscador de Cobrar.
 *
 * Antes el Inicio mostraba solo los vencidos bajo «Con deuda», y el buscador de Cobrar
 * anunciaba más alumnos con deuda: la operativa veía dos números distintos para lo mismo.
 */
class InicioVencidosEnPlazoT74Test extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogosSeeder::class);
        Configuracion::set('dias_gracia_cobranza', 10);
        Configuracion::set('inscripcion_importe', '5000');
        Carbon::setTestNow('2026-10-05 10:00:00');

        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, TipoCaja::firstOrFail()->id);
        $this->deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 48000,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_inicio_separa_vencidos_de_en_plazo_y_suman_lo_del_buscador_de_cobrar(): void
    {
        $this->padronMezclado();

        $inicio = $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertOk();

        $this->assertSame(2, $inicio->viewData('vencidos'));
        $this->assertSame(2, $inicio->viewData('enPlazo'));
        $this->assertSame($this->cobranza(), $inicio->viewData('vencidos'), 'Vencidos tiene que ser lo mismo que Cobranza.');
        $this->assertSame($this->cobranza('EN_PLAZO'), $inicio->viewData('enPlazo'));
        $this->assertSame(
            $this->buscadorDeCobrar(),
            $inicio->viewData('vencidos') + $inicio->viewData('enPlazo'),
            'Vencidos más en plazo tiene que dar lo que anuncia el buscador de Cobrar.'
        );
    }

    public function test_cuando_pasa_el_plazo_el_alumno_cambia_de_tarjeta_y_la_suma_no_cambia(): void
    {
        $this->padronMezclado();

        Carbon::setTestNow('2026-10-15 10:00:00');
        // La caja del 5 quedaría vieja y Cobrar pediría cerrarla: se abre la del día.
        \App\Models\CajaOperativa::query()->delete();
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, TipoCaja::firstOrFail()->id);
        $inicio = $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertOk();

        // La cuota de octubre venció el 10: los dos que estaban en plazo pasan a vencidos.
        $this->assertSame(4, $inicio->viewData('vencidos'));
        $this->assertSame(0, $inicio->viewData('enPlazo'));
        $this->assertSame($this->cobranza(), $inicio->viewData('vencidos'));
        $this->assertSame($this->buscadorDeCobrar(), $inicio->viewData('vencidos') + $inicio->viewData('enPlazo'));
    }

    public function test_quien_debe_solo_la_inscripcion_cuenta_una_vez_y_la_suma_sigue_cerrando(): void
    {
        $alumno = $this->alumno('Inscripta', 'Nueva', '2026-10-01');
        $this->inscripcion($alumno);

        $inicio = $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertOk();

        $this->assertSame(1, $inicio->viewData('vencidos') + $inicio->viewData('enPlazo'));
        $this->assertSame($this->cobranza(), $inicio->viewData('vencidos'));
        $this->assertSame($this->cobranza('EN_PLAZO'), $inicio->viewData('enPlazo'));
        $this->assertSame($this->buscadorDeCobrar(), 1);
    }

    public function test_la_pantalla_muestra_las_dos_tarjetas_y_cada_una_lleva_a_su_lista(): void
    {
        $this->padronMezclado();

        $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))
            ->assertOk()
            ->assertSee('Vencidos')
            ->assertSee('En plazo')
            ->assertSee('Posibles inactivos')
            ->assertDontSee('Con deuda')
            ->assertSee('href="' . route('web.cobranza.index') . '"', false)
            ->assertSee('href="' . route('web.cobranza.index', ['estado' => 'EN_PLAZO']) . '"', false);
    }

    public function test_cobranza_no_cambia_sigue_mostrando_solo_los_vencidos(): void
    {
        $this->padronMezclado();

        $estados = $this->actingAs($this->operativo)->get(route('web.cobranza.index'))->assertOk()
            ->viewData('alumnos')->pluck('estado_cobranza')->unique()->sort()->values()->all();

        $this->assertNotContains('EN_PLAZO', $estados);
        $this->assertNotContains('AL_DIA', $estados);
        $this->assertSame(2, $this->cobranza());
    }

    /**
     * Dos vencidos (septiembre impago), dos en plazo (octubre impago, día 5 con 10 de
     * gracia), uno al día, uno sin cuotas, uno con un mes que todavía no empezó y una
     * inactiva con deuda, que no cuenta en ningún lado.
     */
    private function padronMezclado(): void
    {
        $this->deuda($this->alumno('Vencida', 'Uno'), '2026-09');
        $doble = $this->alumno('Vencida', 'Dos');
        $this->deuda($doble, '2026-09');
        $this->deuda($doble, '2026-10');
        $this->deuda($this->alumno('Plazo', 'Uno'), '2026-10');
        $this->deuda($this->alumno('Plazo', 'Dos'), '2026-10', pagado: 20000);
        $this->deuda($this->alumno('Aldia', 'Pagada'), '2026-10', pagado: 48000, estado: DeudaCuota::ESTADO_PAGADA);
        $this->alumno('Aldia', 'SinCuotas');
        $this->deuda($this->alumno('Adelantada', 'Noviembre'), '2026-11');
        $inactiva = $this->alumno('Inactiva', 'Deudora');
        $this->deuda($inactiva, '2026-09');
        $inactiva->update(['activo' => false]);
    }

    private function cobranza(?string $estado = null): int
    {
        $parametros = $estado ? ['estado' => $estado] : [];

        return $this->actingAs($this->operativo)->get(route('web.cobranza.index', $parametros))
            ->assertOk()->viewData('alumnos')->count();
    }

    private function buscadorDeCobrar(): int
    {
        return $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))
            ->assertOk()->viewData('conDeuda');
    }

    private function alumno(string $apellido, string $nombre, string $alta = '2026-09-01'): Alumno
    {
        $alumno = Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => (string) (30000000 + Alumno::count() + 1),
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0000',
            'fecha_alta' => $alta,
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => $alta,
            'activo' => true,
        ]);

        return $alumno;
    }

    private function deuda(Alumno $alumno, string $periodo, float $pagado = 0, string $estado = DeudaCuota::ESTADO_PENDIENTE): DeudaCuota
    {
        return DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodo,
            'monto_original' => 48000,
            'monto_pagado' => $pagado,
            'monto_condonado' => 0,
            'estado' => $estado,
        ]);
    }

    private function inscripcion(Alumno $alumno): void
    {
        CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'dni' => $alumno->dni,
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'inscripcion:dni:' . $alumno->dni,
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => 5000,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => $alumno->fecha_alta],
            'estado' => 'VIGENTE',
        ]);
    }
}
