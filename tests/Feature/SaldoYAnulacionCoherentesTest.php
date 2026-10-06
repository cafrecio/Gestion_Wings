<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CargoAlumno;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que Codex devolvió al verificar A55, A13 y B1.
 *
 * 1) La inscripción es **una por persona**, y la persona puede estar anotada en dos
 *    deportes, o sea en dos registros. El cálculo se la imputaba al registro que figura en
 *    el cargo y la ficha la buscaba por DNI, así que el selector del segundo deporte decía
 *    $0 y su ficha $5.000. Los dos tienen que decir lo mismo, sin cobrarla dos veces.
 *
 * 2) Al anular un cobro, el historial de la ficha mostraba el mes del pago en vez de los
 *    meses cobrados: se borran las imputaciones y quedaba el mes de `fecha_pago`. El dato
 *    correcto está guardado en el detalle de anulación, que es lo que ya usa el recibo.
 */
class SaldoYAnulacionCoherentesTest extends TestCase
{
    use RefreshDatabase;

    private const PRECIO = 48000.0;
    private const INSCRIPCION = 5000.0;

    private User $admin;
    private Alumno $patin;
    private Alumno $futbol;
    private TipoCaja $tipoCaja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-06 10:00:00');

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->tipoCaja = TipoCaja::first() ?? TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);

        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $deportePatin = Deporte::firstOrCreate(
            ['nombre' => 'Patín'],
            ['tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]
        );
        $deporteFutbol = Deporte::firstOrCreate(
            ['nombre' => 'Fútbol'],
            ['tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION, 'activo' => true]
        );

        // La misma persona, un DNI, dos registros: uno por deporte.
        $this->patin = $this->crearRegistro('33444555', $deportePatin, $nivel);
        $this->futbol = $this->crearRegistro('33444555', $deporteFutbol, $nivel);

        // Una sola inscripción, atada al primer registro.
        CargoAlumno::create([
            'alumno_id' => $this->patin->id,
            'dni' => '33444555',
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'inscripcion:dni:33444555',
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => self::INSCRIPCION,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => '2026-10-01'],
            'estado' => 'VIGENTE',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** A55: la inscripción se cuenta una sola vez, pero las dos pantallas coinciden. */
    public function test_la_inscripcion_de_quien_hace_dos_deportes_figura_en_un_solo_registro(): void
    {
        $saldos = app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$this->patin, $this->futbol]));

        $this->assertSame(self::INSCRIPCION, $saldos[$this->patin->id]['inscripcion']);
        $this->assertSame(0.0, $saldos[$this->futbol->id]['inscripcion'], 'No se cobra dos veces.');

        // La ficha del registro que la tiene, la muestra.
        $this->actingAs($this->admin)
            ->get(route('web.alumnos.show', $this->patin->id))
            ->assertOk()
            ->assertSee('Inscripción al club');
    }

    /**
     * Y la ficha del otro deporte no puede decir que debe $5.000 cuando el selector dice $0:
     * tiene que decir dónde está esa inscripción.
     */
    public function test_la_ficha_del_otro_deporte_no_duplica_el_saldo_y_dice_donde_esta(): void
    {
        $respuesta = $this->actingAs($this->admin)
            ->get(route('web.alumnos.show', $this->futbol->id))
            ->assertOk();

        $respuesta->assertSee('Patín', false);
        $respuesta->assertDontSee('Saldo pendiente: $5.000,00', false);
    }

    /** A13/B1: el historial anulado tiene que decir qué meses cubría ese cobro. */
    public function test_el_historial_de_un_cobro_anulado_muestra_los_meses_cobrados(): void
    {
        // Se cobra agosto en octubre: el mes del pago y el mes cobrado son distintos.
        DeudaCuota::create([
            'alumno_id' => $this->patin->id,
            'periodo' => '2026-08',
            'monto_original' => self::PRECIO,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->admin)->post(route('web.caja.pagar', $this->patin->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-08'],
            'montos_cuota' => ['2026-08' => self::PRECIO],
            'monto_entregado' => self::PRECIO + self::INSCRIPCION,
            'fecha_pago' => '2026-10-06',
            'confirmar_fecha_vieja' => 1,
        ])->assertSessionHasNoErrors();

        $pago = Pago::where('alumno_id', $this->patin->id)->sole();

        $this->actingAs($this->admin)
            ->post(route('web.pagos.anular', $pago->id), ['motivo' => 'Se equivocó de medio de pago'])
            ->assertSessionHas('success');

        // El detalle de la anulación es lo que conserva qué meses cubría el cobro.
        $this->assertSame(
            ['2026-08'],
            array_column($pago->fresh()->detalle_anulacion['periodos'] ?? [], 'periodo'),
            'Sin ese detalle, el historial no tiene de dónde sacar el mes cobrado.'
        );

        $html = $this->actingAs($this->admin)
            ->get(route('web.alumnos.show', $this->patin->id))
            ->assertOk()
            ->getContent();

        // Se mira el historial, no la pantalla entera: octubre aparece en otros bloques.
        $desde = strpos($html, 'Historial de pagos');
        $hasta = strpos($html, 'Asistencias', $desde);
        $historial = substr($html, $desde, $hasta - $desde);
        $this->assertStringContainsString('Ago 2026', $historial, 'Tiene que decir el mes cobrado.');
        $this->assertStringNotContainsString('Oct 2026', $historial, 'No el mes en que se pagó.');
    }

    private function crearRegistro(string $dni, Deporte $deporte, Nivel $nivel): Alumno
    {
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => self::PRECIO,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'apellido' => 'Morales',
            'nombre' => 'Sofía',
            'dni' => $dni,
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0000',
            'fecha_alta' => '2026-10-01',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-10-01',
            'activo' => true,
        ]);

        return $alumno;
    }
}
