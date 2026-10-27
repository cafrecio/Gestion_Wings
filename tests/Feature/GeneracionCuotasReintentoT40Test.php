<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\GeneracionCuotas;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use App\Notifications\AvisoOperativo;
use App\Services\AvisoAdminService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * T40 (DU-52, caso CBZ-020): si el proceso de cuotas del día 1 no corre, se
 * reintenta solo los días siguientes, genera lo que falta sin duplicar y avisa.
 */
class GeneracionCuotasReintentoT40Test extends TestCase
{
    use RefreshDatabase;

    private Grupo $grupo;
    private GrupoPlan $plan;
    private Deporte $deporte;
    private int $siguienteDni = 20000000;

    protected function setUp(): void
    {
        parent::setUp();

        // La migración deja anotado el mes real de la corrida: cada prueba
        // arma su propio historial.
        GeneracionCuotas::query()->delete();

        User::create([
            'name' => 'Dueño',
            'email' => 'duenio-t40@wings.test',
            'password' => bcrypt('secreto-de-prueba'),
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->deporte = Deporte::create([
            'nombre' => 'Vóley T40',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => Nivel::create(['nombre' => 'T40'])->id,
            'activo' => true,
        ]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 30000,
            'activo' => true,
        ]);

        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_dia_1_genera_y_deja_constancia_sin_avisar(): void
    {
        $alumno = $this->alumnoQueVinoEnOctubre();
        Carbon::setTestNow('2026-11-01 06:00:00');

        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();

        $this->assertSame(1, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-11')->count());
        $generacion = GeneracionCuotas::where('periodo', '2026-11')->firstOrFail();
        $this->assertSame(1, $generacion->creadas);
        Notification::assertNothingSent();
    }

    public function test_si_el_dia_1_no_corrio_lo_recupera_el_dia_3_y_avisa_al_duenio(): void
    {
        $alumno = $this->alumnoQueVinoEnOctubre();
        $this->mesGenerado('2026-10');
        Carbon::setTestNow('2026-11-03 06:00:00');

        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();

        $this->assertSame(1, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-11')->count());
        $this->assertTrue(GeneracionCuotas::where('periodo', '2026-11')->exists());
        $this->assertAviso('las cuotas del mes se generaron con atraso', ['Mes' => '11/2026', 'Generadas el' => '03/11/2026']);
    }

    public function test_con_el_mes_ya_generado_los_dias_siguientes_no_hacen_nada(): void
    {
        $alumno = $this->alumnoQueVinoEnOctubre();
        $this->mesGenerado('2026-10');
        $this->mesGenerado('2026-11');
        Carbon::setTestNow('2026-11-02 06:00:00');

        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();

        // Ni siquiera al alumno elegible: el mes ya se atendió el día 1.
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->count());
        Notification::assertNothingSent();
    }

    public function test_sin_el_mes_anterior_generado_no_genera_a_mitad_de_mes(): void
    {
        // Servidor o base nuevos: no hay prueba de que el sistema venía andando,
        // así que no se generan cuotas del mes en curso a destiempo.
        $alumno = $this->alumnoQueVinoEnOctubre();
        Carbon::setTestNow('2026-11-15 06:00:00');

        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();

        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->count());
        $this->assertFalse(GeneracionCuotas::where('periodo', '2026-11')->exists());
        Notification::assertNothingSent();
    }

    public function test_una_corrida_cortada_se_completa_al_dia_siguiente_sin_duplicar(): void
    {
        $primero = $this->alumnoQueVinoEnOctubre();
        $segundo = $this->alumnoQueVinoEnOctubre();
        $this->mesGenerado('2026-10');
        Carbon::setTestNow('2026-11-01 06:00:00');

        // El día 1 la base se cae después de crear la primera cuota.
        $real = app(PagoCuotaService::class);
        $llamadas = 0;
        $this->partialMock(PagoCuotaService::class, function ($mock) use ($real, &$llamadas) {
            $mock->shouldReceive('crearDeudaSiNoExiste')->andReturnUsing(
                function (...$argumentos) use ($real, &$llamadas) {
                    if (++$llamadas === 2) {
                        throw new \RuntimeException('Se perdió la conexión con la base');
                    }
                    return $real->crearDeudaSiNoExiste(...$argumentos);
                }
            );
        });

        $this->artisan('cobranza:generar-deudas --si-falta')->assertFailed();

        $this->assertSame(1, DeudaCuota::where('periodo', '2026-11')->count());
        $this->assertFalse(GeneracionCuotas::where('periodo', '2026-11')->exists());
        $this->assertAviso('no se pudieron generar las cuotas del mes', ['Mes' => '11/2026']);

        // Al día siguiente la base volvió.
        $this->app->forgetInstance(PagoCuotaService::class);
        $this->app->offsetUnset(PagoCuotaService::class);
        Carbon::setTestNow('2026-11-02 06:00:00');

        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();

        $this->assertSame(1, DeudaCuota::where('alumno_id', $primero->id)->where('periodo', '2026-11')->count());
        $this->assertSame(1, DeudaCuota::where('alumno_id', $segundo->id)->where('periodo', '2026-11')->count());
        $this->assertSame(1, GeneracionCuotas::where('periodo', '2026-11')->firstOrFail()->ya_existian);
        $this->assertAviso('las cuotas del mes se generaron con atraso', ['Generadas el' => '02/11/2026']);
    }

    public function test_otra_corrida_del_mismo_mes_no_pisa_la_constancia_de_la_primera(): void
    {
        // PRU-04 día 23: la automática creó 100 y la corrida a mano posterior dejó la constancia en 0 creadas.
        $this->alumnoQueVinoEnOctubre();
        $this->alumnoQueVinoEnOctubre();
        Carbon::setTestNow('2026-11-01 06:00:00');
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();

        Carbon::setTestNow('2026-11-01 06:02:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();

        $generacion = GeneracionCuotas::where('periodo', '2026-11')->firstOrFail();
        $this->assertSame(2, $generacion->creadas);
        $this->assertSame(0, $generacion->ya_existian);
        $this->assertSame('2026-11-01 06:00:00', $generacion->terminada_at->format('Y-m-d H:i:s'));

        // Si la corrida posterior sí crea algo (un alumno que faltaba), se suma.
        $this->alumnoQueVinoEnOctubre();
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $this->assertSame(3, $generacion->fresh()->creadas);
        $this->assertSame(1, GeneracionCuotas::count());
    }

    public function test_otra_corrida_no_vuelve_a_contar_a_quien_ya_estaba_en_revision(): void
    {
        // Devolución de Codex sobre T92: con un alumno ya en Revisión, la constancia daba 1, 2, 4.
        $sinVenir = $this->alumnoQueVinoEnOctubre();
        \App\Models\Asistencia::where('alumno_id', $sinVenir->id)->delete();
        Carbon::setTestNow('2026-11-01 06:00:00');
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();
        $generacion = GeneracionCuotas::where('periodo', '2026-11')->firstOrFail();
        $this->assertSame(1, $generacion->a_revision);

        Carbon::setTestNow('2026-11-01 08:00:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $this->assertSame(1, $generacion->fresh()->a_revision);

        // Otro que tampoco vino: ese sí es un envío nuevo.
        $otro = $this->alumnoQueVinoEnOctubre();
        \App\Models\Asistencia::where('alumno_id', $otro->id)->delete();
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $this->assertSame(2, $generacion->fresh()->a_revision);
        $this->assertSame(2, \App\Models\AlumnoRevisionCobranza::where('periodo_objetivo', '2026-11')->count());
    }

    public function test_si_falta_no_se_combina_con_un_periodo(): void
    {
        Carbon::setTestNow('2026-11-01 06:00:00');

        $this->artisan('cobranza:generar-deudas --si-falta --periodo=2026-10')->assertFailed();

        $this->assertSame(0, DeudaCuota::count());
        $this->assertSame(0, GeneracionCuotas::count());
    }

    public function test_la_corrida_manual_de_un_tecnico_tambien_deja_constancia(): void
    {
        $this->alumnoQueVinoEnOctubre();
        Carbon::setTestNow('2026-11-04 10:00:00');

        $this->artisan('cobranza:generar-deudas')->assertSuccessful();

        // Sin la constancia, la corrida de las 6 del día siguiente generaría de nuevo y avisaría.
        $this->assertTrue(GeneracionCuotas::where('periodo', '2026-11')->exists());
        Notification::assertNothingSent();
    }

    public function test_el_resumen_diario_muestra_las_cuotas_sin_generar_hasta_que_se_generan(): void
    {
        $this->mesGenerado('2026-10');
        Carbon::setTestNow('2026-11-02 08:00:00');

        $this->assertTrue(app(AvisoAdminService::class)->resumenDiario());
        $this->assertAviso('resumen diario de pendientes', ['Cuotas del mes sin generar' => null]);

        $this->mesGenerado('2026-11');
        Notification::fake();

        // Sin ningún otro pendiente, el resumen ya no tiene nada que contar.
        $this->assertFalse(app(AvisoAdminService::class)->resumenDiario());
        Notification::assertNothingSent();
    }

    public function test_el_programador_lo_corre_todos_los_dias_a_las_6(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($evento) => str_contains((string) $evento->command, 'cobranza:generar-deudas'));

        $this->assertCount(1, $eventos);
        $evento = $eventos->first();
        $this->assertStringContainsString('--si-falta', $evento->command);
        $this->assertSame('0 6 * * *', $evento->expression);
        $this->assertTrue($evento->withoutOverlapping);
        $this->assertTrue($evento->onOneServer);
    }

    private function alumnoQueVinoEnOctubre(): Alumno
    {
        $alumno = Alumno::create([
            'nombre' => 'Alumno',
            'apellido' => 'T40',
            'dni' => (string) $this->siguienteDni++,
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => '2026-06-01',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-06-01',
            'activo' => true,
        ]);
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-'.str_pad((string) (10 + $alumno->id % 15), 2, '0', STR_PAD_LEFT),
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => true]);

        return $alumno;
    }

    private function mesGenerado(string $periodo): void
    {
        GeneracionCuotas::create(['periodo' => $periodo, 'terminada_at' => $periodo.'-01 06:00:00']);
    }

    /**
     * @param array<string,?string> $datos clave => valor esperado (null: solo que exista)
     */
    private function assertAviso(string $titulo, array $datos): void
    {
        Notification::assertSentOnDemand(AvisoOperativo::class, function (AvisoOperativo $aviso) use ($titulo, $datos) {
            $leer = fn () => [$this->titulo, $this->datos];
            [$tituloReal, $datosReales] = \Closure::bind($leer, $aviso, AvisoOperativo::class)();
            if ($tituloReal !== $titulo) {
                return false;
            }
            foreach ($datos as $clave => $valor) {
                if (!array_key_exists($clave, $datosReales)) {
                    return false;
                }
                if ($valor !== null && $datosReales[$clave] !== $valor) {
                    return false;
                }
            }
            return true;
        });
    }
}
