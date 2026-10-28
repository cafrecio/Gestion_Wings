<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CargoAlumno, Deporte, DeudaCuota, DeudaPerdida, Grupo, GrupoPlan, Nivel, User};
use App\Services\{CobranzaEstadoService, DeudaInactivosService, ReporteAlumnosService, ReporteMensualService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('lento')]
class InactivosT70T71Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $this->withoutVite();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín T70', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial T70']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
    }

    public function test_fecha_e_historia_se_conservan_al_reactivar_sin_duplicar_el_mes(): void
    {
        $a = $this->alumno();
        foreach ([false, true, false, true] as $activo) {
            $this->actingAs($this->admin)->patchJson(route('web.alumnos.toggle-activo', $a->id))
                ->assertOk()->assertJson(['activo' => $activo]);
            $this->assertSame($activo ? null : '2026-10-28', $a->fresh()->inactivo_desde?->toDateString());
        }
        $r = app(ReporteAlumnosService::class)->obtener('2026-10');
        $this->assertSame(1, $r['pases_inactivo_mes']);
        $this->assertSame(0, $r['altas_mes']);
        $this->assertSame(4, DB::table('alumno_estado_historial')->count());
        $this->assertSame(1, DeudaCuota::where('alumno_id', $a->id)->where('periodo', '2026-10')->count());
    }

    public function test_inactivos_anteriores_sin_fecha_no_se_inventan_y_altas_respeta_mes_y_deporte(): void
    {
        $viejo = $this->alumno(['activo' => false]);
        $this->alumno(['fecha_alta' => '2026-10-03']);
        $this->alumno(['fecha_alta' => '2026-11-01']);
        $this->assertNull($viejo->fresh()->inactivo_desde);
        $s = app(ReporteAlumnosService::class);
        $this->assertSame(1, $s->obtener('2026-10')['altas_mes']);
        $this->assertSame(0, $s->obtener('2026-10')['pases_inactivo_mes']);
        $this->assertSame(1, $s->obtener('2026-10', $this->grupo->deporte_id)['altas_mes']);
        $this->assertSame(0, $s->obtener('2026-10', 999999)['altas_mes']);
    }

    public function test_pase_de_noviembre_no_borra_la_cantidad_de_octubre(): void
    {
        $a = $this->alumno();
        $a->update(['activo' => false]);
        $this->travelTo(Carbon::parse('2026-11-03'));
        $a->update(['activo' => true]);
        $a->update(['activo' => false]);
        $s = app(ReporteAlumnosService::class);
        $this->assertSame(1, $s->obtener('2026-10')['pases_inactivo_mes']);
        $this->assertSame(1, $s->obtener('2026-11')['pases_inactivo_mes']);
    }

    public function test_alu017_b_d_deuda_aparte_no_aumenta_ningun_total_existente(): void
    {
        $a = $this->alumno();
        $this->cuota($a, '2026-09', 10000);
        $this->cuota($a, '2026-10', 20000);
        $s = app(CobranzaEstadoService::class);
        $this->assertSame(30000.0, $s->resumenDashboard()['total_adeudado']);
        $this->actingAs($this->admin)->patchJson(route('web.alumnos.toggle-activo', $a->id))->assertOk();
        $this->assertSame(0.0, $s->resumenDashboard()['total_adeudado']);
        $this->assertCount(0, $s->listadoCobranza('TODOS'));
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-10')['deuda']['total']);
        $r = app(DeudaInactivosService::class)->obtener();
        $this->assertSame(3000000, $r['total_centavos']);
        $this->assertSame('2026-10-28 12:00:00', $r['filas'][0]['inactivo_desde']);
        foreach (['web.cobranza.index', 'web.reportes.index'] as $ruta) {
            $this->get(route($ruta))->assertOk()->assertSee('Deuda de alumnos inactivos')
                ->assertViewHas('deudaInactivos', fn ($d) => $d['total_centavos'] === 3000000);
        }
    }

    public function test_perdida_es_aparte_del_perdon_con_rastro_y_sin_movimiento_de_dinero(): void
    {
        $a = $this->alumno();
        $cuota = $this->cuota($a, '2026-09', 10000);
        $cuota->forceFill(['monto_pagado' => 3000])->save();
        $a->update(['activo' => false]);
        $antes = DB::table('cashflow_movimientos')->count();
        $reporteAntes = app(ReporteMensualService::class)->obtener('2026-10');
        $pagosAntes = DB::table('pagos')->count();
        $cajaAntes = DB::table('movimientos_operativos')->count();
        $this->actingAs($this->admin)->postJson(route('web.inactivos.perder', $a->id), ['centavos' => 700000])->assertOk();
        $this->assertSame(0.0, $cuota->fresh()->saldo_pendiente);
        $this->assertSame(0.0, (float) $cuota->fresh()->monto_condonado);
        $this->assertSame(7000.0, (float) $cuota->fresh()->monto_perdido);
        $p = DeudaPerdida::sole();
        $this->assertSame($this->admin->id, $p->usuario_id);
        $this->assertSame('2026-10-28', $p->created_at->toDateString());
        $this->assertSame(7000.0, (float) $p->importe);
        $this->assertCount(1, $p->detalle);
        $this->assertSame(0, app(DeudaInactivosService::class)->obtener()['total_centavos']);
        $this->assertSame($antes, DB::table('cashflow_movimientos')->count());
        $this->assertSame($pagosAntes, DB::table('pagos')->count());
        $this->assertSame($cajaAntes, DB::table('movimientos_operativos')->count());
        $this->assertSame($reporteAntes, app(ReporteMensualService::class)->obtener('2026-10'));
        $this->get(route('web.alumnos.show', $a->id))->assertOk()->assertSee('Deuda dada por perdida')->assertSee('$7,000.00');
    }

    public function test_operativo_y_profesor_no_pueden_perder_aunque_fuercen_la_ruta(): void
    {
        $a = $this->alumno(['activo' => false]);
        $cuota = $this->cuota($a, '2026-09', 10000);
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $u = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $this->actingAs($u)->postJson(route('web.inactivos.perder', $a->id), ['centavos' => 1000000])->assertForbidden();
        }
        $this->assertSame(0, DeudaPerdida::count());
        $this->assertSame(10000.0, $cuota->fresh()->saldo_pendiente);
    }

    public function test_saldo_desactualizado_activo_y_repeticion_rechazan_sin_escritura(): void
    {
        $a = $this->alumno();
        $this->cuota($a, '2026-09', 10000);
        $this->actingAs($this->admin)->postJson(route('web.inactivos.perder', $a->id), ['centavos' => 1000000])->assertUnprocessable();
        $a->update(['activo' => false]);
        $this->postJson(route('web.inactivos.perder', $a->id), ['centavos' => 900000])->assertUnprocessable();
        $this->assertSame(0, DeudaPerdida::count());
        $this->postJson(route('web.inactivos.perder', $a->id), ['centavos' => 1000000])->assertOk();
        $this->postJson(route('web.inactivos.perder', $a->id), ['centavos' => 1000000])->assertUnprocessable();
        $this->assertSame(1, DeudaPerdida::count());
    }

    public function test_reingreso_no_restituye_perdida_ni_la_confunde_con_condonacion(): void
    {
        $a = $this->alumno();
        $cuota = $this->cuota($a, '2026-09', 10000);
        $a->update(['activo' => false]);
        app(DeudaInactivosService::class)->perder($a->id, 1000000, $this->admin);
        $this->actingAs($this->admin)->patchJson(route('web.alumnos.toggle-activo', $a->id))->assertOk();
        $this->assertSame(0.0, $cuota->fresh()->saldo_pendiente);
        $this->assertNull($a->fresh()->inactivo_desde);
        $this->assertSame(4000.0, app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado']);
        $this->assertSame(400000, app(ReporteMensualService::class)->obtener('2026-10')['deuda']['total']);
        $this->assertSame(1, DeudaPerdida::count());
    }

    public function test_inscripcion_compartida_con_activo_no_se_duplica_ni_se_pierde(): void
    {
        $a = $this->alumno(['activo' => false]);
        $this->cargo($a, 'INSCRIPCION', 5000);
        $otroDeporte = Deporte::create(['nombre' => 'Fútbol T71', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $activo = $this->alumno(['dni' => $a->dni, 'deporte_id' => $otroDeporte->id]);
        $this->assertSame(5000.0, app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado']);
        $this->assertSame(0, app(DeudaInactivosService::class)->obtener()['total_centavos']);
        $activo->update(['activo' => false]);
        $r = app(DeudaInactivosService::class)->obtener();
        $this->assertSame(500000, $r['total_centavos']);
        $this->assertCount(1, $r['filas']);
    }

    public function test_perdida_cubre_inscripcion_y_recargo_y_excluye_mes_futuro(): void
    {
        $a = $this->alumno(['activo' => false]);
        $cuota = $this->cuota($a, '2026-09', 10000);
        $futura = $this->cuota($a, '2026-11', 20000);
        $inscripcion = $this->cargo($a, 'INSCRIPCION', 5000);
        $punitorio = $this->cargo($a, 'PUNITORIO', 1000, $cuota->id);
        app(DeudaInactivosService::class)->perder($a->id, 1600000, $this->admin);
        $this->assertSame(0.0, $inscripcion->fresh()->saldo_pendiente);
        $this->assertSame(0.0, $punitorio->fresh()->saldo_pendiente);
        $this->assertSame(20000.0, $futura->fresh()->saldo_pendiente);
        $a->update(['activo' => true]);
        $this->assertSame(0.0, app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado']);
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-10')['deuda']['total']);
    }

    public function test_alu017_y_alu025_ficha_busqueda_clases_mes_siguiente_y_reingreso(): void
    {
        $a = $this->alumno();
        $this->cuota($a, '2026-09', 10000);
        $this->cuota($a, '2026-10', 10000);
        $pasada = \App\Models\Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => '2026-10-20', 'hora_inicio' => '17:00', 'hora_fin' => '18:00']);
        $futura = \App\Models\Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => '2026-11-04', 'hora_inicio' => '17:00', 'hora_fin' => '18:00']);
        \App\Models\Asistencia::create(['clase_id' => $pasada->id, 'alumno_id' => $a->id, 'presente' => true]);
        $this->actingAs($this->admin)->patchJson(route('web.alumnos.toggle-activo', $a->id))->assertOk()->assertJson(['activo' => false]);
        $this->get(route('web.alumnos.show', $a->id))->assertOk()->assertSee('$20,000.00');
        $this->get(route('web.caja.cobrar-cuota', ['search' => $a->dni]))->assertOk()
            ->assertViewHas('alumnos', fn ($alumnos) => !$alumnos->contains('id', $a->id));
        $this->get(route('web.clases.show', $futura->id))->assertOk()
            ->assertViewHas('alumnos', fn ($alumnos) => !$alumnos->contains('id', $a->id));
        $this->get(route('web.clases.show', $pasada->id))->assertOk()
            ->assertViewHas('asistenciasMap', fn ($marcas) => $marcas->get($a->id)?->presente);
        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('reporte', fn ($r) => $r['deuda']['total'] === 0);
        $this->travelTo(Carbon::parse('2026-11-01 09:00:00'));
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $this->assertDatabaseMissing('deuda_cuotas', ['alumno_id' => $a->id, 'periodo' => '2026-11']);
        $this->assertSame(0, \App\Models\AlumnoRevisionCobranza::where('alumno_id', $a->id)->where('periodo_objetivo', '2026-11')->count());
        $this->patchJson(route('web.alumnos.toggle-activo', $a->id))->assertOk()->assertJson(['activo' => true]);
        $this->assertSame(30000.0, app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado']);
        $this->get(route('web.clases.show', $futura->id))->assertOk()->assertViewHas('alumnos', fn ($alumnos) => $alumnos->contains('id', $a->id));
    }

    public function test_fallo_del_registro_de_auditoria_revierte_toda_la_perdida(): void
    {
        $a = $this->alumno(['activo' => false]);
        $cuota = $this->cuota($a, '2026-09', 10000);
        $falso = new User;
        $falso->forceFill(['id' => 9999999, 'rol' => 'ADMIN', 'activo' => true]);
        try {
            app(DeudaInactivosService::class)->perder($a->id, 1000000, $falso);
            $this->fail('La FK del autor inexistente debe rechazar la auditoría.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame('23000', $e->errorInfo[0]);
        }
        $this->assertSame(10000.0, $cuota->fresh()->saldo_pendiente);
        $this->assertSame(0, DeudaPerdida::count());
    }

    public function test_perdida_no_borra_condonacion_previa_de_cargos_ni_reescribe_cortes_anteriores(): void
    {
        $a = $this->alumno(['activo' => false]);
        $cargo = $this->cargo($a, 'INSCRIPCION', 5000);
        $cargo->forceFill(['monto_condonado' => 1000])->save();
        $this->travelTo(Carbon::parse('2026-11-03'));
        app(DeudaInactivosService::class)->perder($a->id, 400000, $this->admin);
        $a->update(['activo' => true]);
        $this->assertSame(1000.0, (float) $cargo->fresh()->monto_condonado);
        $this->assertSame(4000.0, (float) $cargo->fresh()->monto_perdido);
        $this->assertSame(400000, app(ReporteMensualService::class)->obtener('2026-10')['deuda']['total']);
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-11')['deuda']['total']);
    }

    public function test_reporte_muestra_cantidades_y_la_ficha_distingue_la_perdida(): void
    {
        $this->admin->name = 'Dueño de prueba';
        $this->admin->save();
        $ana = $this->alumno(['apellido' => 'Gómez', 'nombre' => 'Ana', 'fecha_alta' => '2026-10-03']);
        $luz = $this->alumno(['apellido' => 'López', 'nombre' => 'Luz']);
        $vieja = $this->alumno(['apellido' => 'Ruiz', 'nombre' => 'Eva', 'activo' => false]);
        $this->cuota($ana, '2026-10', 24000);
        $this->cuota($luz, '2026-09', 18000);
        $this->cuota($vieja, '2026-09', 7500);
        $ana->update(['activo' => false]);
        $luz->update(['activo' => false]);
        app(DeudaInactivosService::class)->perder($luz->id, 1800000, $this->admin);
        $this->actingAs($this->admin);
        if (getenv('WINGS_CAPTURAS') === '1') $this->withVite();
        $reporte = $this->get(route('web.reportes.alumnos'))->assertOk()
            ->assertSee('Altas del mes')->assertSee('Pases a Inactivo del mes')
            ->assertViewHas('reporte', fn ($r) => $r['altas_mes'] === 1 && $r['pases_inactivo_mes'] === 2);
        $this->assertContains('2026-03', $reporte->viewData('meses'));
        $ficha = $this->get(route('web.alumnos.show', $luz->id))->assertOk()->assertSee('Deuda dada por perdida')->assertSee('Dueño de prueba');
        $this->assertStringNotContainsString('López, Luz', $reporte->getContent());
        if (getenv('WINGS_CAPTURAS') !== '1') return;
        $paginas = ['01-reporte-alumnos' => $reporte->getContent(), '04-ficha-perdida' => $ficha->getContent(),
            '02-cobranza' => $this->get(route('web.cobranza.index'))->assertOk()->getContent(),
            '03-reportes' => $this->get(route('web.reportes.index'))->assertOk()->getContent()];
        $operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true, 'name' => 'Mostrador de prueba']);
        $paginas['05-cobranza-operativo'] = $this->actingAs($operativo)->get(route('web.cobranza.index'))->assertOk()->getContent();
        \Illuminate\Support\Facades\Auth::logout();
        $paginas['00-login'] = $this->get(route('login'))->assertOk()->getContent();
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t70-t71');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        foreach ($paginas as $nombre => $html) {
            $html = preg_replace('~https?://[^/"\s]+/(build/|img/|favicon|apple-touch|site.webmanifest)~', '../../../../public/$1', $html);
            $html = preg_replace('~(<meta name="csrf-token" content=")[^"]*~', '$1', $html);
            $html = preg_replace('~(<input[^>]*name="_token"[^>]*value=")[^"]*~', '$1', $html);
            $html = preg_replace('/[\t ]+$/m', '', $html);
            file_put_contents($dir.'/'.$nombre.'.html', rtrim($html).PHP_EOL);
        }
    }

    public function test_reingreso_no_regenera_la_cuota_del_mes_que_se_dio_por_perdida(): void
    {
        $a = $this->alumno();
        $cuota = $this->cuota($a, '2026-10', 10000);
        $a->update(['activo' => false]);
        app(DeudaInactivosService::class)->perder($a->id, 1000000, $this->admin);
        $this->actingAs($this->admin)->patchJson(route('web.alumnos.toggle-activo', $a->id))->assertOk()->assertJson(['activo' => true]);
        $this->assertSame(1, DeudaCuota::where('alumno_id', $a->id)->count());
        $this->assertSame(0.0, $cuota->fresh()->saldo_pendiente);
        $this->assertSame(0.0, app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado']);
    }

    private function alumno(array $datos = []): Alumno
    {
        $a = Alumno::create($datos + ['nombre' => 'Ana', 'apellido' => 'Prueba', 'dni' => (string) (70000000 + Alumno::count()),
            'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000',
            'fecha_alta' => '2026-03-01', 'activo' => true, 'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
        return $a;
    }

    private function cuota(Alumno $a, string $periodo, float $importe): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => $importe, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    }

    private function cargo(Alumno $a, string $tipo, float $importe, ?int $cuota = null): CargoAlumno
    {
        return CargoAlumno::create(['alumno_id' => $a->id, 'dni' => $a->dni, 'tipo' => $tipo, 'estado' => 'VIGENTE',
            'clave_origen' => $tipo.':'.$a->id, 'monto_original' => $importe, 'deuda_cuota_id' => $cuota,
            'subrubro_id' => DB::table('subrubros')->value('id'), 'calculo' => []]);
    }
}
