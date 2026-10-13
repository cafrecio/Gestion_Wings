<?php

namespace Tests\Feature;

use App\Models\{Alumno, CargoAlumno, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Subrubro, TipoCaja, User};
use App\Services\{CobranzaEstadoService, InscripcionService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verificación de Claude sobre T31 (Gemini). Vive fuera de tests/: no es parte de la suite.
 *
 *   DB_DATABASE=wings_testing_claude php vendor/bin/phpunit --configuration phpunit.xml \
 *     docs/06-pruebas/PRU-04/evidencia-t31/VerificacionT31ClaudeTest.php
 *
 * No repite las pruebas del autor: usa los ejemplos que dio Carlos y busca dónde dos
 * pantallas pueden contar distinto.
 */
class VerificacionT31ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private Deporte $patin;
    private Deporte $futbol;
    private Grupo $grupoPatin;
    private Grupo $grupoFutbol;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        Configuracion::set('dias_gracia_cobranza', 10);
        $this->patin = Deporte::where('nombre', 'Patín')->firstOrFail();
        $this->futbol = Deporte::where('nombre', 'Fútbol')->firstOrFail();
        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);
        $this->grupoPatin = Grupo::create(['deporte_id' => $this->patin->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->grupoFutbol = Grupo::create(['deporte_id' => $this->futbol->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        foreach ([$this->grupoPatin, $this->grupoFutbol] as $g) {
            GrupoPlan::create(['grupo_id' => $g->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function alumno(string $dni, string $ingreso, ?Deporte $deporte = null): Alumno
    {
        $deporte ??= $this->patin;

        return Alumno::create(['nombre' => 'Ana', 'apellido' => 'Prueba'.$dni, 'dni' => $dni, 'fecha_nacimiento' => '2010-01-01',
            'fecha_alta' => $ingreso, 'celular' => '1140000000', 'deporte_id' => $deporte->id,
            'grupo_id' => ($deporte->id === $this->patin->id ? $this->grupoPatin : $this->grupoFutbol)->id, 'activo' => true]);
    }

    private function cuota(Alumno $a, string $periodo, float $monto = 30000): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => $monto,
            'monto_pagado' => 0, 'monto_condonado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }

    private function inscripcion(Alumno $a, float $monto = 5000): CargoAlumno
    {
        return CargoAlumno::create(['alumno_id' => $a->id, 'tipo' => 'INSCRIPCION', 'dni' => InscripcionService::dni($a->dni),
            'clave_origen' => 'inscripcion:dni:'.InscripcionService::dni($a->dni),
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => $monto, 'monto_condonado' => 0, 'calculo' => ['fecha_ingreso' => (string) $a->fecha_alta], 'estado' => 'VIGENTE']);
    }

    private function estadoEl(Alumno $a, string $dia): string
    {
        Carbon::setTestNow($dia.' 10:00:00');

        return app(CobranzaEstadoService::class)->estadoAlumno($a->id)['estado'];
    }

    /** Carlos, 13/10: «se anota un lunes… no va a ser moroso hasta el lunes siguiente». */
    public function test_el_ejemplo_de_carlos_ingresa_un_lunes_y_es_moroso_el_lunes_siguiente(): void
    {
        // Lunes 12/10/2026, ya pasado el día de gracia (10). Debe la cuota del mes y la inscripción.
        $a = $this->alumno('40000001', '2026-10-12');
        $this->cuota($a, '2026-10');
        $this->inscripcion($a);

        foreach (['2026-10-12', '2026-10-15', '2026-10-18'] as $dia) {
            $this->assertSame('EN_PLAZO', $this->estadoEl($a, $dia), $dia);
        }
        $this->assertSame('MOROSO', $this->estadoEl($a, '2026-10-19'), 'El lunes siguiente.');
        $this->assertSame('DEUDOR', $this->estadoEl($a, '2026-11-01'), 'Cuando cierra el mes de ingreso.');
    }

    public function test_si_la_gracia_del_mes_vence_mas_tarde_vale_el_plazo_mas_largo(): void
    {
        $a = $this->alumno('40000002', '2026-10-03');
        $this->cuota($a, '2026-10');
        $this->inscripcion($a);

        $this->assertSame('EN_PLAZO', $this->estadoEl($a, '2026-10-10'), 'Hasta el 10, como todos.');
        $this->assertSame('MOROSO', $this->estadoEl($a, '2026-10-11'));
    }

    public function test_el_plazo_que_cruza_el_fin_de_mes_se_respeta_y_despues_es_deudor(): void
    {
        $a = $this->alumno('40000003', '2026-09-28');
        $this->cuota($a, '2026-09');
        $this->inscripcion($a);

        $this->assertSame('EN_PLAZO', $this->estadoEl($a, '2026-10-01'), 'Septiembre cerró, pero sigue en su plazo.');
        $this->assertSame('EN_PLAZO', $this->estadoEl($a, '2026-10-04'));
        $this->assertSame('DEUDOR', $this->estadoEl($a, '2026-10-05'), 'Vencido el plazo con el mes de ingreso cerrado.');
    }

    public function test_el_plazo_del_alta_no_tapa_la_cuota_del_mes_siguiente(): void
    {
        $a = $this->alumno('40000004', '2026-09-28');
        $this->cuota($a, '2026-09');
        $this->cuota($a, '2026-10');

        $this->assertSame('EN_PLAZO', $this->estadoEl($a, '2026-10-03'));
        // El 11 octubre ya es morosa por la regla general, y septiembre es deudora desde el 5.
        $this->assertSame('DEUDOR', $this->estadoEl($a, '2026-10-11'));
    }

    public function test_quien_debe_solo_la_inscripcion_nunca_esta_al_dia(): void
    {
        $viejo = $this->alumno('40000005', '2025-03-15');
        $this->inscripcion($viejo);
        $this->assertSame('DEUDOR', $this->estadoEl($viejo, '2026-10-13'));

        $pagada = $this->alumno('40000006', '2025-03-15');
        $this->inscripcion($pagada)->update(['monto_condonado' => 5000]);
        $this->assertSame('AL_DIA', $this->estadoEl($pagada, '2026-10-13'), 'Perdonada entera: no debe nada.');
    }

    public function test_persona_en_dos_deportes_la_inscripcion_afecta_a_un_solo_registro(): void
    {
        $enPatin = $this->alumno('40000007', '2025-03-15', $this->patin);
        $enFutbol = $this->alumno('40000007', '2025-03-15', $this->futbol);
        $this->inscripcion($enPatin);

        $this->assertSame('DEUDOR', $this->estadoEl($enPatin, '2026-10-13'));
        $this->assertSame('AL_DIA', $this->estadoEl($enFutbol, '2026-10-13'));

        $estados = app(CobranzaEstadoService::class)->estadosParaAlumnos(Alumno::whereKey($enFutbol->id)->get());
        $this->assertSame('AL_DIA', $estados[$enFutbol->id], 'Mirado solo, el otro registro no hereda la inscripción.');

        // Si el registro que la tiene se da de baja, pasa al que sigue activo.
        $enPatin->update(['activo' => false]);
        $this->assertSame('DEUDOR', $this->estadoEl($enFutbol, '2026-10-13'));
    }

    public function test_sin_la_clave_de_configuracion_avisa_en_vez_de_asumir(): void
    {
        Configuracion::where('clave', 'dias_plazo_alta')->delete();
        $a = $this->alumno('40000008', '2026-10-12');

        $this->expectException(\LogicException::class);
        $this->estadoEl($a, '2026-10-13');
    }

    /** El objetivo de T31: que todas las pantallas cuenten a los mismos. */
    public function test_cobranza_el_buscador_de_cobrar_y_el_inicio_de_la_operativa_cuentan_a_los_mismos(): void
    {
        Carbon::setTestNow('2026-10-13 10:00:00');
        $deudorDeCuota = $this->alumno('40000010', '2026-01-10');
        $this->cuota($deudorDeCuota, '2026-09');
        $soloInscripcion = $this->alumno('40000011', '2025-03-15');
        $this->inscripcion($soloInscripcion);
        $adelantadoAnulado = $this->alumno('40000012', '2026-01-10');
        $this->cuota($adelantadoAnulado, '2026-11'); // mes que no empezó: no es deuda
        $this->alumno('40000013', '2026-01-10'); // al día

        $servicio = app(CobranzaEstadoService::class);
        $enCobranza = $servicio->listadoCobranza()->count();
        $enBuscador = count(array_filter($servicio->saldoDeAlumnos(Alumno::where('activo', true)->get(['id', 'dni'])), fn ($s) => $s['total'] > 0));
        $this->assertSame(2, $enCobranza);
        $this->assertSame(2, $enBuscador);

        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $tipo = TipoCaja::firstOrFail();
        \Illuminate\Support\Facades\DB::table('caja_mostrador')->updateOrInsert(['id' => 1], ['tipo_caja_id' => $tipo->id]);
        $contar = fn () => $this->actingAs($operativo)->get(route('web.operativo.dashboard'))->assertOk()->viewData('alumnosConDeuda');

        // Con los cuatro juntos da 2 por casualidad: le falta uno y le sobra otro. Se miran por separado.
        $this->assertSame(2, $contar());

        // Sin el del mes que no empezó tendría que seguir dando 2 (la de cuota y el de inscripción).
        DeudaCuota::where('alumno_id', $adelantadoAnulado->id)->delete();
        $this->assertSame(2, $enCobranza = $servicio->listadoCobranza()->count());
        $this->assertSame($enCobranza, $contar(),
            'El Inicio de la operativa no cuenta a quien debe solo la inscripción.');
    }

    public function test_el_inicio_de_la_operativa_no_cuenta_un_mes_que_todavia_no_empezo(): void
    {
        Carbon::setTestNow('2026-10-13 10:00:00');
        $adelantadoAnulado = $this->alumno('40000020', '2026-01-10');
        $this->cuota($adelantadoAnulado, '2026-11');

        $servicio = app(CobranzaEstadoService::class);
        $this->assertSame(0, $servicio->listadoCobranza()->count());

        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        \Illuminate\Support\Facades\DB::table('caja_mostrador')->updateOrInsert(['id' => 1], ['tipo_caja_id' => TipoCaja::firstOrFail()->id]);
        $this->assertSame(0, $this->actingAs($operativo)->get(route('web.operativo.dashboard'))->assertOk()->viewData('alumnosConDeuda'),
            'El Inicio de la operativa cuenta como deuda un mes que todavía no empezó.');
    }
}
