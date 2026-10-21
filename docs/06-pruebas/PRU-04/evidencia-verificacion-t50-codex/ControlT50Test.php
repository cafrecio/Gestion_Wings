<?php

namespace VerificacionT50Codex;

use App\Models\{Alumno, AlumnoPlan, CargoAlumno, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, PrimeraCarga, User};
use App\Services\{CobranzaEstadoService, PagoCuotaService, PrimeraCargaExcelService};
use App\Support\{FechaIngresoRegla, RelojSimulado};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Mail, Notification};
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** Controles independientes; fuera de tests/ por pedido de Carlos. Solo datos ficticios. */
class ControlT50Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $op;
    private Grupo $grupo;
    private GrupoPlan $plan;
    private array $temporales = [];
    private int $dni = 99005000;

    protected function setUp(): void
    {
        if (getenv('DB_DATABASE') !== 'wings_testing_codex') {
            throw new \RuntimeException('Base propia obligatoria antes de RefreshDatabase.');
        }
        parent::setUp();
        $this->assertSame('wings_testing_codex', DB::connection()->getDatabaseName());
        Carbon::setTestNow('2026-10-20 10:00:00');
        Notification::fake();
        Mail::fake();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->op = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => Deporte::firstOrFail()->id, 'nivel_id' => Nivel::firstOrFail()->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporales as $file) {
            if (is_file($file)) unlink($file);
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function observar(string $caso, array $datos): void
    {
        file_put_contents(__DIR__.'/controles.jsonl', json_encode(['caso' => $caso, 'datos' => $datos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND);
    }

    private function datos(string $fecha, array $extra = []): array
    {
        return array_replace(['dni' => (string) ++$this->dni, 'nombre' => 'Prueba', 'apellido' => 'Independiente T50',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000050', 'email' => 't50@wings.test',
            'fecha_alta' => $fecha, 'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'plan_id' => $this->plan->id], $extra);
    }

    private function conteos(): array
    {
        return ['alumnos' => Alumno::count(), 'alumno_planes' => AlumnoPlan::count(),
            'deuda_cuotas' => DeudaCuota::count(), 'cargos' => CargoAlumno::count(),
            'inscripcion_personas' => DB::table('inscripcion_personas')->count()];
    }

    private function fixtureAlumno(string $fecha): Alumno
    {
        // Modelo directo solo para representar registros históricos, no para el alta a verificar.
        $a = Alumno::create($this->datos($fecha));
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'fecha_desde' => $fecha, 'activo' => true]);
        return $a;
    }

    public function test_01_limites_buenos_aires_medianoche_fin_anio_y_reloj_simulado(): void
    {
        $casos = [['2026-11-01T02:59:59Z', '2026-11-30'], ['2026-11-01T03:00:00Z', '2026-12-31'],
            ['2027-01-01T02:59:59Z', '2027-01-31'], ['2027-01-01T03:00:00Z', '2027-02-28']];
        foreach ($casos as [$instante, $limite]) {
            Carbon::setTestNow(Carbon::parse($instante)->setTimezone('America/Argentina/Buenos_Aires'));
            $actual = FechaIngresoRegla::limiteMaximo()->toDateString();
            $this->observar('limite_zona', compact('instante', 'actual', 'limite'));
            $this->assertSame($limite, $actual);
            $this->assertTrue(FechaIngresoRegla::esValida($limite));
            $this->assertFalse(FechaIngresoRegla::esValida(Carbon::parse($limite)->addDay()));
        }
        RelojSimulado::aplicar('2026-12-15 15:00:00', 'testing');
        $this->assertSame('2027-01-31', FechaIngresoRegla::limiteMaximo()->toDateString());
    }

    public function test_02_alta_fuera_limite_no_escribe_alumno_cuota_cargo_ni_persona(): void
    {
        foreach ([$this->admin, $this->op] as $usuario) {
            $antes = $this->conteos();
            $r = $this->actingAs($usuario)->postJson(route('web.alumnos.store'), $this->datos('2026-12-01'));
            $this->observar('alta_invalida', ['rol' => $usuario->rol, 'status' => $r->status(), 'antes' => $antes, 'despues' => $this->conteos(), 'errores' => $r->json('errors')]);
            $r->assertStatus(422)->assertJsonPath('errors.fecha_alta.0', 'La fecha de ingreso no puede ser posterior al 30/11/2026.');
            $this->assertSame($antes, $this->conteos());
        }
    }

    public function test_03_alta_limite_exacto_guarda_fecha_cuota_e_inscripcion(): void
    {
        $data = $this->datos('2026-11-30');
        $this->post(route('web.alumnos.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $a = Alumno::where('dni', $data['dni'])->firstOrFail();
        $this->assertSame('2026-11-30', $a->fecha_alta->toDateString());
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $a->id, 'periodo' => '2026-11']);
        $this->assertTrue(CargoAlumno::where('alumno_id', $a->id)->exists());
        $this->observar('alta_limite', ['alumno' => $a->only('id', 'fecha_alta'), 'deudas' => DeudaCuota::where('alumno_id', $a->id)->get(['periodo', 'monto_original'])->toArray()]);
    }

    public function test_04_edicion_equivalente_no_bloquea_fecha_historica_fuera_rango(): void
    {
        $a = $this->fixtureAlumno('2027-05-10');
        foreach (['2027-05-10 00:00:00', '2027-05-10T00:00:00-03:00'] as $fecha) {
            $r = $this->putJson(route('web.alumnos.update', $a), $this->datos($fecha, ['dni' => $a->dni, 'celular' => '1199990050']));
            $errores = str_contains((string) $r->headers->get('Content-Type'), 'application/json') ? $r->json('errors') : null;
            $this->observar('edicion_equivalente', ['fecha' => $fecha, 'status' => $r->status(), 'errores' => $errores]);
            $r->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('2027-05-10', $a->fresh()->fecha_alta->toDateString());
            $this->assertSame('1199990050', $a->fresh()->celular);
        }
    }

    public function test_05_limite_antes_del_motivo_y_rollback_de_edicion(): void
    {
        $a = $this->fixtureAlumno('2026-10-20');
        $antes = $this->conteos();
        $r = $this->putJson(route('web.alumnos.update', $a), $this->datos('2026-12-01', ['dni' => $a->dni]));
        $r->assertStatus(422)->assertJsonValidationErrors('fecha_alta')->assertJsonMissingValidationErrors('motivo_fecha_alta');
        $this->assertSame('2026-10-20', $a->fresh()->fecha_alta->toDateString());
        $this->assertSame($antes, $this->conteos());
        $r = $this->putJson(route('web.alumnos.update', $a), $this->datos('2026-11-30', ['dni' => $a->dni]));
        $r->assertStatus(422)->assertJsonValidationErrors('motivo_fecha_alta');
        $this->assertSame($antes, $this->conteos());
        $this->putJson(route('web.alumnos.update', $a), $this->datos('2026-11-30', ['dni' => $a->dni, 'motivo_fecha_alta' => 'Corrección ficticia T50']))->assertRedirect();
        $this->assertSame('2026-11-30', $a->fresh()->fecha_alta->toDateString());
        $this->observar('orden_validacion', ['fuera_limite' => 'fecha_alta', 'dentro_sin_motivo' => 'motivo_fecha_alta', 'final' => $a->fresh()->fecha_alta->toDateString()]);
    }

    public function test_06_fecha_mal_formada_al_editar_devuelve_validacion_y_no_500(): void
    {
        $a = $this->fixtureAlumno('2026-10-20');
        $r = $this->putJson(route('web.alumnos.update', $a), $this->datos('no-es-fecha', ['dni' => $a->dni]));
        $respuesta = array_intersect_key($r->json(), array_flip(['message', 'exception', 'file', 'line']));
        $this->observar('edicion_fecha_mal_formada', ['status' => $r->status(), 'respuesta' => $respuesta, 'fecha_bd' => $a->fresh()->fecha_alta->toDateString()]);
        $this->assertSame('2026-10-20', $a->fresh()->fecha_alta->toDateString());
        $r->assertStatus(422)->assertJsonValidationErrors('fecha_alta');
    }

    public function test_07_dos_previews_http_y_servicio_rechazan_en_castellano(): void
    {
        foreach (['web.alumnos.cuota-alta-preview', 'web.alumnos.inscripcion-preview'] as $ruta) {
            $r = $this->getJson(route($ruta, ['fecha_alta' => '2026-12-01', 'plan_id' => $this->plan->id, 'dni' => '99005099']));
            $r->assertStatus(422)->assertJsonPath('errors.fecha_alta.0', 'La fecha de ingreso no puede ser posterior al 30/11/2026.');
        }
        try { app(PagoCuotaService::class)->previsualizarCuotaAlta('2026-12-01', $this->plan->id); $this->fail('El servicio aceptó una fecha fuera del límite.'); }
        catch (ValidationException $e) { $this->assertSame('La fecha de ingreso no puede ser posterior al 30/11/2026.', $e->errors()['fecha_alta'][0]); }
    }

    private function excel(string $fecha, bool $serial = false): string
    {
        $s = app(PrimeraCargaExcelService::class);
        $libro = $s->plantilla();
        $cat = $s->catalogos()[0];
        $h = $libro->getSheetByName('Alumnos');
        $h->fromArray([(string) ++$this->dni, 'Excel ficticio', 'Control T50', '01/01/2000', $fecha,
            '1100000050', 'excel@wings.test', null, null, $cat['deporte'], $cat['grupo'], $cat['plan'], 'No', 'No'], null, 'A2');
        if ($serial) $h->setCellValue('E2', Date::PHPToExcel(Carbon::createFromFormat('!d/m/Y', $fecha)));
        $path = __DIR__.'/temporal-'.bin2hex(random_bytes(4)).'.xlsx';
        $this->temporales[] = $path;
        (new Xlsx($libro))->save($path);
        $libro->disconnectWorksheets();
        return $path;
    }

    public function test_08_excel_texto_y_serial_aceptan_limite_y_marcan_exceso(): void
    {
        foreach ([false, true] as $serial) foreach (['30/11/2026', '01/12/2026'] as $fecha) {
            $resultado = app(PrimeraCargaExcelService::class)->revisar($this->excel($fecha, $serial));
            $this->observar('excel_revision', compact('serial', 'fecha', 'resultado'));
            if ($fecha === '30/11/2026') { $this->assertSame([], $resultado['errores']); $this->assertCount(1, $resultado['filas']); }
            else { $error = collect($resultado['errores'])->firstWhere('celda', 'E2'); $this->assertNotNull($error); $this->assertSame('La fecha de ingreso no puede ser posterior al 30/11/2026.', $error['esperado']); $this->assertCount(0, $resultado['filas']); }
        }
    }

    public function test_09_cargar_excel_sin_revision_no_escribe_fila_fuera_limite(): void
    {
        PrimeraCarga::whereKey(1)->update(['estado' => 'PENDIENTE', 'detalle' => null]);
        foreach ([false, true] as $serial) {
            $antes = $this->conteos();
            $r = app(PrimeraCargaExcelService::class)->cargar($this->excel('01/12/2026', $serial), $this->admin->id);
            $this->observar('excel_carga_directa', ['serial' => $serial, 'errores' => $r['errores'], 'antes' => $antes, 'despues' => $this->conteos()]);
            $this->assertNotEmpty($r['errores']); $this->assertSame($antes, $this->conteos());
            $this->assertSame('PENDIENTE', PrimeraCarga::findOrFail(1)->estado);
        }
    }

    public function test_10_cargar_revalida_fecha_aunque_resumen_revisado_antes_fuera_valido(): void
    {
        PrimeraCarga::whereKey(1)->update(['estado' => 'PENDIENTE', 'detalle' => null]);
        $file = $this->excel('01/12/2026');
        Carbon::setTestNow('2026-11-20 10:00:00');
        $service = app(PrimeraCargaExcelService::class);
        $revisado = $service->revisar($file);
        $this->assertSame([], $revisado['errores']);
        Carbon::setTestNow('2026-10-20 10:00:00');
        $antes = $this->conteos();
        $r = $service->cargar($file, $this->admin->id, $revisado['resumen']);
        $this->assertNotEmpty($r['errores']); $this->assertSame($antes, $this->conteos());
        $this->observar('excel_estado_viejo', ['revisado' => $revisado['resumen'], 'rechazo_actual' => $r['errores']]);
    }

    public function test_11_alta_futura_no_es_deuda_hasta_que_empiece_mes_en_las_tres_pantallas(): void
    {
        $data = $this->datos('2026-11-01');
        $this->post(route('web.alumnos.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $a = Alumno::where('dni', $data['dni'])->firstOrFail();
        $cobranza = app(CobranzaEstadoService::class);
        $estado = $cobranza->estadoAlumno($a->id)['estado'];
        $fila = $cobranza->listadoCobranza('TODOS')->firstWhere('id', $a->id);
        $this->assertNotNull($fila);
        \Tests\Support\CajaDeclarada::crear($this->op->id, \App\Models\TipoCaja::firstOrFail()->id);
        $this->actingAs($this->op);
        $cobrar = $this->get(route('web.caja.cobrar-cuota'))->assertOk();
        $inicio = $this->get(route('web.operativo.dashboard'))->assertOk();
        $pantallaCobranza = $this->get(route('web.cobranza.index', ['estado' => 'TODOS']))->assertOk();
        $this->observar('futuro_antes_mes', ['estado' => $estado, 'saldo' => $fila->deuda_total,
            'cuotas' => $cobranza->saldoDeAlumnos(collect([$a]))[$a->id],
            'conDeuda_cobrar' => $cobrar->viewData('conDeuda'), 'alumnos_cobrar' => $cobrar->viewData('alumnos')->pluck('id')->all(),
            'vencidos' => $inicio->viewData('vencidos'), 'en_plazo' => $inicio->viewData('enPlazo'),
            'estado_cobranza_ui' => $pantallaCobranza->viewData('alumnos')->firstWhere('id', $a->id)?->estado_cobranza]);
        $this->assertSame('AL_DIA', $estado);
        $this->assertEquals(0, $fila->deuda_total);
        $cobrar->assertViewHas('conDeuda', 0)->assertViewHas('alumnos', fn ($filas) => $filas->total() === 0);
        $inicio->assertViewHas('vencidos', 0)->assertViewHas('enPlazo', 0);
        $pantallaCobranza->assertSee('Independiente T50')->assertSee('Al día');
    }

    public function test_12_t52_h3_h5_exigen_ingreso_aunque_grupo_desde_sea_anterior(): void
    {
        $a = $this->fixtureAlumno('2026-11-01');
        $this->assertFalse(Alumno::enGrupoElDia($this->grupo->id, '2026-10-31')->whereKey($a->id)->exists());
        $this->assertTrue(Alumno::enGrupoElDia($this->grupo->id, '2026-11-01')->whereKey($a->id)->exists());
        $otro = Grupo::create(['deporte_id' => $this->grupo->deporte_id, 'nivel_id' => Nivel::where('id', '<>', $this->grupo->nivel_id)->firstOrFail()->id, 'activo' => true]);
        $a->update(['grupo_id' => $otro->id]);
        $this->assertSame('2026-10-20', $a->fresh()->grupo_desde->toDateString());
        $this->assertFalse(Alumno::enGrupoElDia($otro->id, '2026-10-31')->whereKey($a->id)->exists());
        $this->assertTrue(Alumno::enGrupoElDia($otro->id, '2026-11-01')->whereKey($a->id)->exists());
        $clase = Clase::create(['grupo_id' => $otro->id, 'fecha' => '2026-10-31', 'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'cancelada' => false]);
        $this->get(route('web.clases.show', $clase))->assertOk()->assertDontSee('Independiente T50');
        $this->observar('t52_h3_h5', ['ingreso' => $a->fresh()->fecha_alta->toDateString(), 'grupo_desde' => $a->fresh()->grupo_desde->toDateString(), 'fecha_clase_excluida' => '2026-10-31']);
    }

    public function test_13_reactivacion_no_reescribe_fecha_ingreso_ni_carga_mes_anterior(): void
    {
        $a = $this->fixtureAlumno('2026-11-01');
        $a->update(['activo' => false]);
        $this->patchJson(route('web.alumnos.toggle-activo', $a))->assertOk();
        $this->assertTrue($a->fresh()->activo);
        $this->assertSame('2026-11-01', $a->fresh()->fecha_alta->toDateString());
        $this->assertDatabaseMissing('deuda_cuotas', ['alumno_id' => $a->id, 'periodo' => '2026-10']);
    }

    public function test_14_api_apagada_no_expone_puerta_sin_regla(): void
    {
        $antes = $this->conteos();
        $legacy = collect(\Illuminate\Support\Facades\Route::getRoutes())->filter(fn ($r) =>
            str_starts_with($r->getActionName(), 'App\\Http\\Controllers\\AlumnoController@')
            || str_starts_with($r->getActionName(), 'App\\Http\\Controllers\\Admin\\AlumnoController@'));
        $this->assertCount(0, $legacy);
        $r = $this->postJson('/api/alumnos', $this->datos('2027-05-10'));
        // La ruta GET de reserva de Wings produce 405 para este POST desconocido; no es una API activa.
        $r->assertStatus(405);
        $this->assertSame($antes, $this->conteos());
        $this->observar('api', ['post_alumnos' => $r->status(), 'rutas_controladores_legacy' => $legacy->count()]);
    }

    public function test_15_servidor_en_noviembre_admite_diciembre_segun_su_reloj(): void
    {
        RelojSimulado::aplicar('2026-11-01 10:00:00', 'testing');
        $this->assertSame('2026-12-31', FechaIngresoRegla::limiteMaximo()->toDateString());
        $data = $this->datos('2026-12-01');
        $this->post(route('web.alumnos.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $a = Alumno::where('dni', $data['dni'])->firstOrFail();
        $this->assertSame('2026-12-01', $a->fecha_alta->toDateString());
        $this->observar('reloj_servidor_noviembre', ['hoy' => now()->toDateString(), 'limite' => FechaIngresoRegla::limiteMaximo()->toDateString(), 'fecha_aceptada_bd' => $a->fecha_alta->toDateString()]);
    }

    public function test_16_cuota_futura_empieza_a_contar_al_comenzar_el_mes(): void
    {
        $data = $this->datos('2026-11-01');
        $this->post(route('web.alumnos.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $a = Alumno::where('dni', $data['dni'])->firstOrFail();
        Carbon::setTestNow('2026-11-01 10:00:00');
        $saldo = app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$a]))[$a->id];
        $this->assertEquals(30000, $saldo['cuotas']);
        $this->assertGreaterThan(0, $saldo['total']);
        $this->observar('mes_iniciado', ['fecha' => now()->toDateString(), 'saldo' => $saldo]);
    }
}
