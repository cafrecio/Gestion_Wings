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
use App\Models\User;
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A54 y A55: cuánto debe un alumno se calcula en un solo lugar.
 *
 * Cada pantalla lo sacaba por su cuenta y decían cosas distintas de la misma persona.
 * El selector de cobro sumaba `saldo_pendiente` de todas las cuotas PENDIENTE —contando
 * como deudor a quien tiene una cuota en cero— y no miraba la inscripción, que sí mira
 * Cobranza. Es la tercera vez que el mismo problema de raíz produce defectos distintos,
 * así que acá se fija lo que importa: **las tres pantallas dicen el mismo número**.
 */
class SaldoUnicoPorAlumnoTest extends TestCase
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
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, \App\Models\TipoCaja::firstOrFail()->id);
        $this->deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
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

    /** A54: una cuota PENDIENTE con saldo cero no es deuda y no cuenta como deudor. */
    public function test_la_cuota_pendiente_sin_saldo_no_cuenta_como_deuda(): void
    {
        $alumno = $this->crearAlumno('Pérez', 'Ana');
        // El caso que reportó Gemini: cuota en $0 que quedó PENDIENTE. El selector la
        // contaba como deuda porque miraba el estado y no el saldo.
        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 0,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $saldo = app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$alumno]))[$alumno->id];

        $this->assertSame(0.0, $saldo['total']);
        $this->assertSame(0, $saldo['cuotas_impagas']);

        // El modelo y el servicio tienen que decir lo mismo de la misma cuota.
        $this->assertSame(
            0.0,
            (float) DeudaCuota::where('alumno_id', $alumno->id)->sole()->saldo_pendiente,
            'El saldo del modelo y el del servicio tienen que coincidir.'
        );

        // El selector lo sigue ofreciendo, porque se le puede cobrar el mes en curso por
        // adelantado, pero no lo cuenta como deudor: eso es lo que decía mal (A54).
        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar-cuota'))
            ->assertOk()
            ->assertSee('Pérez, Ana')
            ->assertSee('Sin deudas pendientes')
            ->assertDontSee('1 alumno con deuda pendiente', false);
    }

    /** A55: la inscripción impaga es parte de lo que la persona debe. */
    public function test_el_saldo_incluye_la_inscripcion_impaga(): void
    {
        $alumno = $this->crearAlumno('Gaitán', 'Lucía');
        $this->crearInscripcion($alumno, 5000);

        $saldo = app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$alumno]))[$alumno->id];

        $this->assertSame(5000.0, $saldo['inscripcion']);
        $this->assertSame(0.0, $saldo['cuotas']);
        $this->assertSame(5000.0, $saldo['total']);

        // Debe plata, así que el selector de cobro tiene que ofrecerlo.
        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar-cuota'))
            ->assertOk()
            ->assertSee('Gaitán, Lucía');
    }

    /** Las tres pantallas miran al mismo alumno y dicen el mismo número. */
    public function test_cobranza_la_ficha_y_el_selector_dicen_el_mismo_saldo(): void
    {
        $alumno = $this->crearAlumno('Gómez', 'Luz');
        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 48000,
            'monto_pagado' => 0,
            'monto_condonado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
        $this->crearInscripcion($alumno, 5000);

        $servicio = app(CobranzaEstadoService::class);

        // 48.000 de la cuota + 5.000 de inscripción.
        $this->assertSame(53000.0, $servicio->saldoDeAlumnos(collect([$alumno]))[$alumno->id]['total']);

        $fila = $servicio->listadoCobranza()->firstWhere('id', $alumno->id);
        $this->assertSame(53000.0, (float) $fila->deuda_total);

        // El selector de cobro muestra el total, inscripción incluida.
        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar-cuota'))
            ->assertOk()
            ->assertSee('53.000');

        // La ficha muestra las dos partes, cada una con el mismo criterio.
        $this->actingAs($this->operativo)
            ->get(route('web.alumnos.show', $alumno->id))
            ->assertOk()
            ->assertSee('48.000')
            ->assertSee('5.000');
    }

    /** Un cobro parcial baja el saldo en los tres lugares a la vez. */
    public function test_un_pago_parcial_baja_el_saldo_en_todas_las_pantallas(): void
    {
        $alumno = $this->crearAlumno('Cuello', 'Renata');
        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 48000,
            'monto_pagado' => 18000,
            'monto_condonado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $servicio = app(CobranzaEstadoService::class);
        $this->assertSame(30000.0, $servicio->saldoDeAlumnos(collect([$alumno]))[$alumno->id]['total']);
        $this->assertSame(30000.0, (float) $servicio->listadoCobranza()->firstWhere('id', $alumno->id)->deuda_total);

        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar-cuota'))
            ->assertOk()
            ->assertSee('30.000');
    }

    private function crearAlumno(string $apellido, string $nombre): Alumno
    {
        $alumno = Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => (string) random_int(30000000, 39999999),
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

    private function crearInscripcion(Alumno $alumno, float $monto): CargoAlumno
    {
        return CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'dni' => $alumno->dni,
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'inscripcion:dni:' . $alumno->dni,
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => $monto,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => '2026-09-01'],
            'estado' => 'VIGENTE',
        ]);
    }
}
