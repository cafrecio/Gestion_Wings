<?php

namespace Tests\VerificacionCodex;

use App\Models\{Alumno, AlumnoPlan, AlumnoRevisionCobranza, Asistencia, Clase, Deporte, DeudaCuota, GeneracionCuotas, Grupo, GrupoPlan, Nivel};
use App\Notifications\AvisoOperativo;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Tests\TestCase;

/** Expectativas independientes. No adapta el esperado a un contador incorrecto. */
class VerificacionT92CodexTest extends TestCase
{
    use RefreshDatabase;

    private Grupo $grupo;
    private GrupoPlan $plan;
    private int $dni = 20920000;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'wings_testing_codex'
            || DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('T92: base efectiva incorrecta antes de migrar.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 06:00:00');
        Notification::fake();
        GeneracionCuotas::query()->delete();
        $deporte = Deporte::create(['nombre' => 'Verificacion T92', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'T92'])->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function alumno(bool $asistencia = true, bool $plan = true): Alumno
    {
        $a = Alumno::create(['nombre' => 'Sintetico', 'apellido' => 'T92', 'dni' => (string) $this->dni++,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111', 'deporte_id' => $this->grupo->deporte_id,
            'grupo_id' => $this->grupo->id, 'fecha_alta' => '2026-06-01', 'activo' => true]);
        if ($plan) AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'fecha_desde' => '2026-06-01', 'activo' => true]);
        if ($asistencia) {
            $c = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => '2026-10-15', 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
            Asistencia::create(['clase_id' => $c->id, 'alumno_id' => $a->id, 'presente' => true]);
        }
        return $a;
    }

    private function fila(): array
    {
        return (array) DB::table('generaciones_cuotas')->where('periodo', '2026-11')->first();
    }

    private function guardar(string $caso, array $datos): void
    {
        file_put_contents(__DIR__.'/'.$caso.'.json', json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
    }

    public function test_repeticion_conserva_contadores_iniciales_y_actualiza_solo_updated_at(): void
    {
        $a = $this->alumno();
        $b = $this->alumno();
        $this->alumno(false, false);
        $preexistente = $this->alumno();
        app(PagoCuotaService::class)->crearDeudaSiNoExiste($preexistente->id, '2026-11', 30000);
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();
        $primera = $this->fila();
        $this->assertSame([2, 0, 1, 1], array_map(fn ($k) => (int) $primera[$k], ['creadas', 'a_revision', 'ya_existian', 'sin_plan']));
        Carbon::setTestNow('2026-11-01 08:30:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $segunda = $this->fila();
        $this->assertSame('2026-11-01 08:30:00', $segunda['updated_at']);
        $primera['updated_at'] = $segunda['updated_at'];
        $this->assertSame($primera, $segunda, 'Una repeticion sin novedades deja todos los campos intactos salvo updated_at.');
    }

    public function test_tres_corridas_suman_una_cuota_nueva_y_mantienen_terminada_at(): void
    {
        $this->alumno(); $this->alumno();
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();
        $filas = [$this->fila()];
        Carbon::setTestNow('2026-11-02 09:00:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $filas[] = $this->fila();
        $nuevo = $this->alumno();
        Carbon::setTestNow('2026-11-03 10:15:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $filas[] = $this->fila();
        $this->guardar('tres-corridas-cuotas', ['constancias' => $filas, 'deudas' => DB::table('deuda_cuotas')->orderBy('id')->get()]);
        $this->assertSame([2, 2, 3], array_map(fn ($r) => (int) $r['creadas'], $filas));
        $this->assertSame(['2026-11-01 06:00:00'], array_values(array_unique(array_column($filas, 'terminada_at'))));
        $this->assertSame(1, GeneracionCuotas::where('periodo', '2026-11')->count());
        $this->assertSame(3, DeudaCuota::where('periodo', '2026-11')->count());
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $nuevo->id, 'periodo' => '2026-11', 'monto_original' => 30000]);
        Notification::assertNothingSent();
    }

    public function test_repetir_no_suma_otra_vez_la_misma_revision(): void
    {
        $a = $this->alumno(false);
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();
        $primera = $this->fila();
        Carbon::setTestNow('2026-11-01 08:00:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $segunda = $this->fila();
        $revisiones = DB::table('alumnos_revision_cobranza')->where('periodo_objetivo', '2026-11')->get();
        $this->guardar('revision-repetida', compact('primera', 'segunda', 'revisiones'));
        $this->assertCount(1, $revisiones);
        $this->assertSame($a->id, (int) $revisiones[0]->alumno_id);
        $this->assertSame(1, (int) $segunda['a_revision'], 'La misma revision pendiente no es un nuevo envio.');
    }

    public function test_tercera_corrida_suma_solo_la_revision_nueva(): void
    {
        $this->alumno(false);
        $filas = [];
        foreach (['2026-11-01 06:00:00', '2026-11-01 08:00:00', '2026-11-02 09:00:00'] as $i => $fecha) {
            if ($i === 2) $this->alumno(false);
            Carbon::setTestNow($fecha);
            $this->artisan('cobranza:generar-deudas')->assertSuccessful();
            $filas[] = $this->fila();
        }
        $revisiones = DB::table('alumnos_revision_cobranza')->where('periodo_objetivo', '2026-11')->orderBy('id')->get();
        $this->guardar('revision-nueva', ['constancias' => $filas, 'revisiones' => $revisiones]);
        $this->assertCount(2, $revisiones);
        $this->assertSame([1, 1, 2], array_map(fn ($r) => (int) $r['a_revision'], $filas));
    }

    public function test_corrida_interrumpida_no_deja_constancia_y_reintenta_sin_duplicar(): void
    {
        $a = $this->alumno(); $b = $this->alumno();
        GeneracionCuotas::create(['periodo' => '2026-10', 'terminada_at' => '2026-10-01 06:00:00']);
        $real = app(PagoCuotaService::class); $llamadas = 0;
        $this->partialMock(PagoCuotaService::class, function ($mock) use ($real, &$llamadas) {
            $mock->shouldReceive('crearDeudaSiNoExiste')->andReturnUsing(function (...$args) use ($real, &$llamadas) {
                if (++$llamadas === 2) throw new \RuntimeException('Corte deliberado T92 despues de la primera cuota');
                return $real->crearDeudaSiNoExiste(...$args);
            });
        });
        $this->artisan('cobranza:generar-deudas --si-falta')->assertFailed();
        $this->assertSame(1, DeudaCuota::where('periodo', '2026-11')->count());
        $this->assertFalse(GeneracionCuotas::where('periodo', '2026-11')->exists());
        $this->app->forgetInstance(PagoCuotaService::class);
        $this->app->offsetUnset(PagoCuotaService::class);
        Carbon::setTestNow('2026-11-02 06:00:00');
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();
        foreach ([$a, $b] as $alumno) $this->assertSame(1, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-11')->count());
        $this->assertSame(1, (int) $this->fila()['creadas']);
        $this->assertSame(1, (int) $this->fila()['ya_existian']);
        $this->guardar('corrida-interrumpida', ['constancia' => $this->fila(), 'deudas' => DB::table('deuda_cuotas')->orderBy('id')->get()]);
    }

    public function test_aviso_de_atraso_refleja_la_corrida_y_no_se_repite(): void
    {
        $this->alumno(); $this->alumno(); $this->alumno(false);
        GeneracionCuotas::create(['periodo' => '2026-10', 'terminada_at' => '2026-10-01 06:00:00']);
        Carbon::setTestNow('2026-11-03 06:00:00');
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();
        $avisos = [];
        Notification::assertSentOnDemand(AvisoOperativo::class, function ($aviso) use (&$avisos) {
            $leer = fn () => [$this->titulo, $this->datos];
            [$titulo, $datos] = \Closure::bind($leer, $aviso, AvisoOperativo::class)();
            $texto = $aviso->toTelegram(new \Illuminate\Notifications\AnonymousNotifiable());
            $this->assertStringContainsString('Cuotas creadas: 2', $texto);
            $this->assertStringContainsString('Enviados a revisión: 1', $texto);
            $avisos[] = compact('titulo', 'datos', 'texto');
            return [$titulo, $datos] === ['las cuotas del mes se generaron con atraso', ['Mes' => '11/2026', 'Generadas el' => '03/11/2026', 'Cuotas creadas' => '2', 'Enviados a revisión' => '1']];
        });
        $this->guardar('aviso-atraso', ['avisos' => $avisos, 'constancia' => $this->fila()]);
        Notification::fake();
        Carbon::setTestNow('2026-11-04 06:00:00');
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_indice_unico_rechaza_dos_constancias_del_mismo_mes(): void
    {
        GeneracionCuotas::create(['periodo' => '2026-11', 'terminada_at' => now()]);
        try {
            GeneracionCuotas::create(['periodo' => '2026-11', 'terminada_at' => now()]);
            $this->fail('La base acepto dos constancias del mismo mes.');
        } catch (QueryException $e) {
            $this->assertSame('23000', (string) $e->getCode());
            $this->assertSame(1062, $e->errorInfo[1]);
        }
        $this->assertSame(1, GeneracionCuotas::where('periodo', '2026-11')->count());
    }

    public function test_mariadb_on_update_se_activa_y_repetir_el_valor_lo_evita(): void
    {
        $columnas = DB::select("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_DEFAULT, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND LOWER(EXTRA) LIKE '%on update%' ORDER BY TABLE_NAME,COLUMN_NAME");
        $this->guardar('columnas-on-update', ['base' => DB::selectOne('SELECT DATABASE() AS nombre, VERSION() AS motor'), 'columnas' => $columnas]);
        $this->assertContains('generaciones_cuotas.terminada_at', array_map(fn ($c) => $c->TABLE_NAME.'.'.$c->COLUMN_NAME, $columnas));
        $g = GeneracionCuotas::create(['periodo' => '2026-11', 'terminada_at' => '2024-01-01 06:00:00']);
        DB::table('generaciones_cuotas')->where('id', $g->id)->update(['creadas' => 1]);
        $cambio = $this->fila();
        $this->assertNotSame('2024-01-01 06:00:00', $cambio['terminada_at']);
        DB::table('generaciones_cuotas')->where('id', $g->id)->update(['creadas' => 2, 'terminada_at' => $cambio['terminada_at']]);
        $this->assertSame($cambio['terminada_at'], $this->fila()['terminada_at']);
    }
}
