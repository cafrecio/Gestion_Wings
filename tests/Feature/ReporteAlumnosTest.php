<?php

namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, Clase, PrimeraCarga, User};
use App\Services\ReporteAlumnosService;
use Carbon\Carbon;
use Database\Seeders\{ReportesAsistenciaEscenarioSeeder, ReportesEscenarioSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReporteAlumnosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReportesEscenarioSeeder::class);
        $this->seed(ReportesAsistenciaEscenarioSeeder::class);
        Carbon::setTestNow('2026-10-09 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_repeticiones_cuentan_como_asistencias_y_no_como_alumnos_unicos(): void
    {
        $r = app(ReporteAlumnosService::class)->obtener('2026-10');
        $this->assertSame(22, $r['activos']);
        $this->assertSame(2, $r['inactivos']);
        $this->assertSame(80, $r['presentes']);
        $this->assertSame(16, $r['ausentes']);
        $this->assertSame(20, $r['alumnos_asistieron']);
        $this->assertSame(83.3, $r['porcentaje_presencia']);
        $this->assertSame(17, $r['clases']);
        $this->assertSame(1, $r['clases_sin_registros']);
        $this->assertCount(3, $r['sin_presentes_actuales']);
        $this->assertSame(22, array_sum(array_column($r['por_nivel'], 'cantidad')));
        $this->assertSame(80, array_sum(array_column($r['grupos'], 'presentes')));
    }

    public function test_canceladas_y_futuras_no_sumaron_asistencias(): void
    {
        $s = app(ReporteAlumnosService::class);
        $antes = $s->obtener('2026-10');
        $alumno = Alumno::firstOrFail();
        foreach ([['2026-10-30', false], ['2026-10-02', true]] as [$fecha, $cancelada]) {
            $clase = Clase::create(['grupo_id'=>$alumno->grupo_id, 'fecha'=>$fecha, 'hora_inicio'=>'12:00:00', 'hora_fin'=>'13:00:00', 'cancelada'=>$cancelada]);
            Asistencia::create(['clase_id'=>$clase->id,'alumno_id'=>$alumno->id,'presente'=>true]);
        }
        $this->assertSame($antes, $s->obtener('2026-10'));
    }

    public function test_baja_actual_no_reescribe_asistencias_del_mes_anterior(): void
    {
        $s = app(ReporteAlumnosService::class);
        $antes = $s->obtener('2026-09');
        Alumno::firstOrFail()->update(['activo'=>false]);
        $despues = $s->obtener('2026-09');
        $this->assertSame($antes['presentes'], $despues['presentes']);
        $this->assertSame($antes['ausentes'], $despues['ausentes']);
        $this->assertSame('2026-10-09', $despues['fecha_matricula']);
        $this->assertSame(21, $despues['activos']);
    }

    public function test_deporte_de_la_clase_define_la_asistencia_aunque_cambie_la_ficha(): void
    {
        $s = app(ReporteAlumnosService::class);
        $alumno = Alumno::firstOrFail();
        $deporte = $alumno->deporte_id;
        $otro = Alumno::where('deporte_id','!=',$deporte)->firstOrFail();
        $antes = $s->obtener('2026-09', $deporte);
        $alumno->update(['deporte_id'=>$otro->deporte_id,'grupo_id'=>$otro->grupo_id]);
        $r = $s->obtener('2026-09', $deporte);
        $this->assertSame($antes['presentes'], $r['presentes']);
        $this->assertSame($antes['activos'] - 1, $r['activos']);
        $this->assertSame(0, $s->obtener('2026-02', $deporte)['clases']);
        $this->assertNull($s->obtener('2026-02', $deporte)['porcentaje_presencia']);
    }

    public function test_huecos_de_registro_no_se_dibujan_como_cero_y_mes_cerrado_elegido_participa(): void
    {
        $s = app(ReporteAlumnosService::class);
        $this->assertCount(6, $s->evolucion('2026-10'));
        $this->assertSame('2026-09', collect($s->evolucion('2026-09'))->last()['mes']);
        Clase::create(['grupo_id'=>Alumno::first()->grupo_id,'fecha'=>'2026-08-02','hora_inicio'=>'12:00:00','hora_fin'=>'13:00:00','cancelada'=>false,'validada_para_liquidacion'=>true]);
        $agosto = collect($s->evolucion('2026-10'))->firstWhere('mes','2026-08');
        $this->assertNull($agosto['presentes']);
        $this->assertNull($agosto['ausentes']);
        $this->assertSame(1, $agosto['sin_registros']);
        $this->assertGreaterThan(0, $agosto['registrados']);
        $this->assertGreaterThan(0, $agosto['ausentes_registrados']);
    }

    public function test_consultas_no_generan_deudas_revision_o_mensajes(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();
        $tablas = ['deuda_cuotas','alumnos_revision_cobranza','liquidaciones','asistencias','clases'];
        $antes = array_map(fn ($t) => DB::table($t)->orderBy('id')->get()->toJson(), $tablas);
        app(ReporteAlumnosService::class)->obtener('2026-10');
        app(ReporteAlumnosService::class)->evolucion('2026-10');
        $despues = array_map(fn ($t) => DB::table($t)->orderBy('id')->get()->toJson(), $tablas);
        $this->assertSame($antes, $despues);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_ruta_de_alumnos_conserva_mes_y_deporte_entre_reportes(): void
    {
        $this->actingAs(User::where('rol', 'ADMIN')->firstOrFail());
        $contexto = ['mes'=>'2026-09', 'deporte_id'=>Alumno::firstOrFail()->deporte_id];
        $r = $this->get(route('web.reportes.alumnos', $contexto))->assertOk()->assertViewIs('reportes.alumnos');
        $this->assertSame('2026-09', $r->viewData('reporte')['mes']);
        $this->assertSame($contexto['deporte_id'], $r->viewData('reporte')['deporte_id']);
        $r->assertSee(route('web.reportes.index', $contexto));
        $r->assertSee(route('web.reportes.alumnos'), false)->assertSee('Activos hoy')->assertSee('Septiembre 2026');
        $this->get(route('web.reportes.index', $contexto))->assertOk()->assertSee(route('web.reportes.alumnos', $contexto));
    }

    public function test_alumnos_rechaza_filtros_invalidos_y_permite_todos_los_deportes(): void
    {
        $this->actingAs(User::where('rol', 'ADMIN')->firstOrFail());
        foreach (['', '2026-13', '2026-11', '1899-12', '2026-09-01'] as $mes) {
            $this->getJson(route('web.reportes.alumnos', ['mes'=>$mes]))->assertUnprocessable()->assertJsonValidationErrors('mes');
        }
        foreach (['abc', '999999'] as $id) {
            $this->getJson(route('web.reportes.alumnos', ['deporte_id'=>$id]))->assertUnprocessable()->assertJsonValidationErrors('deporte_id');
        }
        $r = $this->get(route('web.reportes.alumnos', ['deporte_id'=>'']))->assertOk();
        $this->assertNull($r->viewData('reporte')['deporte_id']);
    }

    public function test_alumnos_solo_admin_y_primera_carga_conserva_el_bloqueo(): void
    {
        $this->get(route('web.reportes.alumnos'))->assertRedirect(route('login'));
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $this->actingAs(User::factory()->create(['rol'=>$rol, 'activo'=>true]))->get(route('web.reportes.alumnos'))->assertForbidden();
        }
        $this->actingAs(User::where('rol', 'ADMIN')->firstOrFail());
        PrimeraCarga::query()->update(['estado'=>'PENDIENTE']);
        $this->get(route('web.reportes.alumnos'))->assertRedirect(route('web.primera-carga.index'));
    }

    public function test_mes_sin_clases_sigue_seleccionado_y_no_inventa_matricula_historica(): void
    {
        $this->actingAs(User::where('rol', 'ADMIN')->firstOrFail());
        $r = $this->get(route('web.reportes.alumnos', ['mes'=>'2026-02']))->assertOk()->assertSee('Febrero 2026')->assertSee('Matrícula actual');
        $this->assertContains('2026-02', $r->viewData('meses'));
        $this->assertSame(0, $r->viewData('reporte')['clases']);
        $this->assertNull($r->viewData('reporte')['porcentaje_presencia']);
        $this->assertSame(22, $r->viewData('reporte')['activos']);
    }
}
