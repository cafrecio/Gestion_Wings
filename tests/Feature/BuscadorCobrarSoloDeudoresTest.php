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
 * T29: sin buscar, el buscador de Cobrar lista a los que deben, y son los mismos que
 * cuenta el encabezado.
 *
 * Salió del día 1 de la prueba por días: después de la primera carga el encabezado
 * anunciaba 40 alumnos con deuda y la lista traía los 100, porque además sumaba a todo
 * alumno sin la cuota del mes generada. A quien no debe se llega buscándolo.
 */
class BuscadorCobrarSoloDeudoresTest extends TestCase
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

    public function test_sin_buscar_la_lista_trae_solo_a_los_que_deben_y_coincide_con_el_encabezado(): void
    {
        // Como después de una primera carga: unos deben y otros no tienen ninguna cuota cargada.
        $this->deuda($this->alumno('Deudora', 'Uno'), '2026-09');
        $this->deuda($this->alumno('Deudora', 'Dos'), '2026-10');
        $this->alumno('Aldia', 'SinCuotas');
        $this->deuda($this->alumno('Aldia', 'Pagada'), '2026-10', pagado: 48000, estado: DeudaCuota::ESTADO_PAGADA);

        $respuesta = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();

        $respuesta->assertSee('Deudora, Uno')->assertSee('Deudora, Dos');
        $respuesta->assertDontSee('Aldia, SinCuotas')->assertDontSee('Aldia, Pagada');

        $this->assertSame(2, $respuesta->viewData('conDeuda'));
        $this->assertSame(2, $respuesta->viewData('alumnos')->total(), 'La lista y el número del encabezado tienen que ser los mismos alumnos.');
        $respuesta->assertSee('alumnos con deuda pendiente');
    }

    public function test_a_quien_no_debe_se_llega_buscandolo_y_se_le_puede_cobrar(): void
    {
        $alDia = $this->alumno('Aldia', 'SinCuotas');
        $this->deuda($this->alumno('Deudora', 'Uno'), '2026-09');

        $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))
            ->assertOk()
            ->assertDontSee('Aldia, SinCuotas')
            ->assertSee('buscalo por nombre o DNI');

        foreach (['Aldia', $alDia->dni] as $busqueda) {
            $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => $busqueda]))
                ->assertOk()
                ->assertSee('Aldia, SinCuotas')
                ->assertSee(route('web.caja.cobrar', $alDia->id));
        }

        // Y la pantalla de cobro lo sigue atendiendo: le ofrece el mes en curso.
        $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $alDia->id))
            ->assertOk()
            ->assertSee('Octubre 2026');
    }

    public function test_quien_debe_solo_la_inscripcion_aparece_sin_buscar(): void
    {
        $alumno = $this->alumno('Inscripta', 'Nueva');
        CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'dni' => $alumno->dni,
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'inscripcion:dni:' . $alumno->dni,
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => 5000,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => '2026-09-01'],
            'estado' => 'VIGENTE',
        ]);

        $respuesta = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();

        $respuesta->assertSee('Inscripta, Nueva');
        $this->assertSame(1, $respuesta->viewData('conDeuda'));
    }

    public function test_un_mes_que_todavia_no_empezo_no_hace_aparecer_al_alumno(): void
    {
        // Lo que queda cuando se anula un cobro adelantado: noviembre guardado y pendiente.
        $this->deuda($this->alumno('Adelantada', 'Anulada'), '2026-11');

        $respuesta = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();

        $respuesta->assertDontSee('Adelantada, Anulada');
        $this->assertSame(0, $respuesta->viewData('conDeuda'));
    }

    public function test_un_alumno_inactivo_con_deuda_no_aparece_ni_se_cuenta(): void
    {
        $inactiva = $this->alumno('Inactiva', 'Deudora');
        $this->deuda($inactiva, '2026-09');
        $inactiva->update(['activo' => false]);

        $respuesta = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();

        $respuesta->assertDontSee('Inactiva, Deudora');
        $this->assertSame(0, $respuesta->viewData('conDeuda'));
        $this->assertSame(0, $respuesta->viewData('alumnos')->total());
    }

    public function test_cuando_nadie_debe_la_pantalla_lo_dice_y_explica_como_cobrar_igual(): void
    {
        $this->alumno('Aldia', 'SinCuotas');

        $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))
            ->assertOk()
            ->assertSee('Sin deudas pendientes')
            ->assertSee('Todos los alumnos están al día')
            ->assertSee('buscá al alumno por nombre o DNI');

        $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'Nadie con este nombre']))
            ->assertOk()
            ->assertSee('Sin resultados');
    }

    public function test_la_lista_de_deudores_pagina_sin_perder_ni_repetir_a_nadie(): void
    {
        foreach (range(1, 14) as $n) {
            $this->deuda($this->alumno('Deudora', sprintf('Numero%02d', $n)), '2026-09');
        }
        $this->alumno('Aldia', 'SinCuotas');

        $primera = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $segunda = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['page' => 2]))->assertOk();

        $this->assertSame(14, $primera->viewData('conDeuda'));
        $this->assertSame(14, $primera->viewData('alumnos')->total());
        $vistos = $primera->viewData('alumnos')->pluck('id')->merge($segunda->viewData('alumnos')->pluck('id'));
        $this->assertCount(14, $vistos->unique());
    }

    private function alumno(string $apellido, string $nombre): Alumno
    {
        $alumno = Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => (string) (30000000 + Alumno::count() + 1),
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0000',
            'fecha_alta' => '2026-09-01',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-09-01',
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
}
