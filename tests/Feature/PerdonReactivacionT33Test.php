<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CargoAlumno, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, Subrubro, TipoCaja, User};
use App\Services\{CobranzaEstadoService, InscripcionService, PagoCuotaService, ReporteMensualService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

class PerdonReactivacionT33Test extends TestCase
{
    use RefreshDatabase { refreshDatabase as private refrescarBase; }

    private User $admin;
    private Alumno $alumno;
    private CargoAlumno $cargo;
    private DeudaCuota $cuota;
    private const MOTIVO = 'Excepción autorizada por el dueño para esta prueba.';

    // Se valida ANTES de RefreshDatabase, que puede borrar tablas.
    public function refreshDatabase(): void
    {
        if (!in_array(DB::connection()->getDatabaseName(), ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T33 requiere una base descartable de pruebas.');
        }
        $this->refrescarBase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 12:00:00');
        Storage::fake();
        $this->seed(CatalogosSeeder::class);
        \App\Models\ReglaPrimerPago::query()->delete();
        DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2026-09-01']);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Prueba', 'apellido' => 'Perdón T33', 'dni' => '33999001',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'fecha_alta' => '2026-09-01',
            'deporte_id' => $grupo->deporte_id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        app(InscripcionService::class)->sincronizar($this->alumno, $this->admin->id);
        $this->cargo = app(InscripcionService::class)->cargo($this->alumno->dni);
        $this->cuota = DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10',
            'monto_original' => 48000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function accion(string $ruta, int $id, string $motivo = self::MOTIVO)
    {
        return $this->from(route('web.alumnos.show', $this->alumno->id))->post(route($ruta, $id), ['motivo' => $motivo]);
    }

    private function perdonarCargo(): void
    {
        $this->accion('web.inscripciones.condonar', $this->cargo->id)->assertSessionHas('success');
    }

    private function reactivarCargo(): void
    {
        $this->accion('web.inscripciones.reactivar', $this->cargo->id)->assertSessionHas('success');
    }

    private function cobrar(float $importe, array $items = []): Pago
    {
        return app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['motivo' => 'Parcial autorizado para la prueba de regresion', 'alumno_id' => $this->alumno->id,
            'usuario_admin_id' => $this->admin->id, 'tipo_caja_id' => TipoCaja::first()->id,
            'items' => $items, 'monto_entregado' => $importe, 'fecha_pago' => today()->toDateString()])['pago'];
    }

    private function dinero(): array
    {
        return collect(['pagos', 'pago_deuda_cuota', 'pago_cargo_alumno', 'cajas_operativas', 'movimientos_operativos', 'cashflow_movimientos'])
            ->mapWithKeys(fn ($tabla) => [$tabla => DB::table($tabla)->orderBy('id')->get()->toJson()])->all();
    }

    public function test_admin_ve_condonar_inscripcion_y_reutiliza_el_modal(): void
    {
        $this->get(route('web.alumnos.show', $this->alumno->id))->assertOk()
            ->assertSee('data-abrir-condonar="'.route('web.inscripciones.condonar', $this->cargo->id).'"', false)
            ->assertSee('id="modal-condonar"', false)->assertSee('minlength="10"', false)->assertSee('maxlength="500"', false);
    }

    public function test_condonar_inscripcion_guarda_solo_saldo_y_evento_con_autor_fecha_motivo_importe(): void
    {
        $antes = $this->dinero();
        $this->perdonarCargo();
        $this->assertSame('5000.00', $this->cargo->fresh()->monto_original);
        $this->assertSame('5000.00', $this->cargo->fresh()->monto_condonado);
        $this->assertSame(0.0, $this->cargo->fresh()->saldo_pendiente);
        $evento = DB::table('cargo_alumno_eventos')->where('accion', 'CONDONAR')->first();
        $this->assertSame($this->admin->id, $evento->usuario_id);
        $this->assertSame(self::MOTIVO, $evento->motivo);
        $this->assertSame('2026-10-13 12:00:00', $evento->created_at);
        $this->assertEquals(5000, json_decode($evento->detalle, true)['importe']);
        $this->assertSame($antes, $this->dinero());
        $this->get(route('web.alumnos.show', $this->alumno->id))->assertSee('Saldo pendiente: $0.00')
            ->assertSee('data-abrir-reactivar="'.route('web.inscripciones.reactivar', $this->cargo->id).'"', false);
    }

    public function test_inscripcion_parcial_perdona_resto_y_anular_pago_deja_debiendo_el_parcial(): void
    {
        $pago = $this->cobrar(2000);
        $dinero = $this->dinero();
        $this->perdonarCargo();
        $this->assertSame(2000.0, $this->cargo->fresh()->monto_cobrado);
        $this->assertSame('3000.00', $this->cargo->fresh()->monto_condonado);
        $this->assertSame(0.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame($dinero, $this->dinero());
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Pago cargado por error en prueba', $this->admin->id);
        $this->assertSame(2000.0, $this->cargo->fresh()->saldo_pendiente);
        $this->reactivarCargo();
        $this->assertSame(5000.0, $this->cargo->fresh()->saldo_pendiente);
    }

    public function test_reactivar_inscripcion_conserva_parcial_original_y_ambos_eventos(): void
    {
        $this->cobrar(2000);
        $this->perdonarCargo();
        $antes = $this->dinero();
        $this->reactivarCargo();
        $this->assertSame('0.00', $this->cargo->fresh()->monto_condonado);
        $this->assertSame(3000.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame($antes, $this->dinero());
        $this->assertSame(['CREAR', 'CONDONAR', 'REACTIVAR'], DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $this->cargo->id)->orderBy('id')->pluck('accion')->all());
        $e = DB::table('cargo_alumno_eventos')->where('accion', 'REACTIVAR')->first();
        $this->assertSame($this->admin->id, $e->usuario_id);
        $this->assertSame(self::MOTIVO, $e->motivo);
        $this->assertSame('2026-10-13 12:00:00', $e->created_at);
        $this->assertEquals(3000, json_decode($e->detalle, true)['importe']);
        $this->cobrar(3000);
        $this->assertSame(0.0, $this->cargo->fresh()->saldo_pendiente);
    }

    public function test_reactivar_cuota_conserva_parciales_imputaciones_y_historial(): void
    {
        $this->cobrar(15000, [['periodo' => '2026-10', 'monto' => 10000]]);
        $this->accion('web.deudas.condonar', $this->cuota->id)->assertSessionHas('success');
        $historia = $this->cuota->fresh()->observaciones;
        $dinero = $this->dinero();
        Carbon::setTestNow('2026-10-14 11:00:00');
        $this->accion('web.deudas.reactivar', $this->cuota->id)->assertSessionHas('success');
        $q = $this->cuota->fresh();
        $this->assertSame('PENDIENTE', $q->estado);
        $this->assertSame('48000.00', $q->monto_original);
        $this->assertSame('10000.00', $q->monto_pagado);
        $this->assertSame(38000.0, $q->saldo_pendiente);
        $this->assertStringStartsWith($historia."\n", $q->observaciones);
        $this->assertStringContainsString('[2026-10-14 11:00:00] REACTIVADA por admin ID:'.$this->admin->id, $q->observaciones);
        $this->assertSame($dinero, $this->dinero());
        $this->cobrar(38000, [['periodo' => '2026-10', 'monto' => 38000]]);
        $this->assertSame('PAGADA', $this->cuota->fresh()->estado);
    }

    public function test_ficha_lista_cuotas_condonadas_y_reactivar_solo_para_admin(): void
    {
        $this->accion('web.deudas.condonar', $this->cuota->id)->assertSessionHas('success');
        $this->get(route('web.alumnos.show', $this->alumno->id))->assertOk()->assertSee('Cuotas condonadas')
            ->assertSee('data-abrir-reactivar="'.route('web.deudas.reactivar', $this->cuota->id).'"', false);
        $this->actingAs(User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]));
        $this->get(route('web.alumnos.show', $this->alumno->id))->assertOk()->assertSee('Cuotas condonadas')
            ->assertDontSee('data-abrir-reactivar', false)->assertDontSee('data-abrir-condonar', false);
    }

    public function test_motivos_invalidos_rechazados_en_las_tres_acciones_sin_escribir(): void
    {
        $this->perdonarCargo();
        $this->accion('web.deudas.condonar', $this->cuota->id)->assertSessionHas('success');
        foreach (['abc', str_repeat('x', 501), str_repeat(' ', 15)] as $motivo) {
            foreach (['web.inscripciones.condonar' => $this->cargo->id, 'web.inscripciones.reactivar' => $this->cargo->id,
                'web.deudas.reactivar' => $this->cuota->id] as $ruta => $id) {
                $antes = DB::table('cargo_alumno_eventos')->get()->toJson();
                $this->accion($ruta, $id, $motivo)->assertSessionHas('error');
                $this->assertSame($antes, DB::table('cargo_alumno_eventos')->get()->toJson());
                $this->assertSame('CONDONADA', $this->cuota->fresh()->estado);
                $this->assertSame('5000.00', $this->cargo->fresh()->monto_condonado);
            }
        }
    }

    public function test_motivos_limite_diez_y_quinientos_caracteres_se_aceptan(): void
    {
        $this->accion('web.inscripciones.condonar', $this->cargo->id, str_repeat('á', 10))->assertSessionHas('success');
        $this->accion('web.inscripciones.reactivar', $this->cargo->id, str_repeat('á', 500))->assertSessionHas('success');
        $this->accion('web.deudas.condonar', $this->cuota->id, str_repeat('á', 10))->assertSessionHas('success');
        $this->accion('web.deudas.reactivar', $this->cuota->id, str_repeat('á', 500))->assertSessionHas('success');
    }

    public function test_operativo_y_profesor_rechazados_por_post_directo(): void
    {
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol, 'activo' => true]));
            foreach (['web.inscripciones.condonar' => $this->cargo->id, 'web.inscripciones.reactivar' => $this->cargo->id,
                'web.deudas.reactivar' => $this->cuota->id] as $ruta => $id) $this->accion($ruta, $id)->assertForbidden();
            if ($rol === 'PROFESOR') $this->get(route('web.alumnos.show', $this->alumno->id))->assertForbidden();
        }
        $this->assertSame('0.00', $this->cargo->fresh()->monto_condonado);
        $this->assertSame('PENDIENTE', $this->cuota->fresh()->estado);
        $this->assertDatabaseCount('cargo_alumno_eventos', 1);
    }

    public function test_inscripcion_ya_perdonada_no_admite_segundo_perdon(): void
    {
        $this->perdonarCargo();
        $this->accion('web.inscripciones.condonar', $this->cargo->id)->assertSessionHas('error');
        $this->assertDatabaseCount('cargo_alumno_eventos', 2);
        $this->assertSame('5000.00', $this->cargo->fresh()->monto_condonado);
    }

    public function test_inscripcion_pagada_entera_no_admite_perdon(): void
    {
        $this->cobrar(5000);
        $antes = $this->dinero();
        $this->accion('web.inscripciones.condonar', $this->cargo->id)->assertSessionHas('error');
        $this->assertDatabaseCount('cargo_alumno_eventos', 1);
        $this->assertSame($antes, $this->dinero());
    }

    public function test_inscripcion_anulada_rechaza_condonar_y_reactivar(): void
    {
        $this->cargo->update(['estado' => 'ANULADO']);
        foreach (['web.inscripciones.condonar', 'web.inscripciones.reactivar'] as $ruta) $this->accion($ruta, $this->cargo->id)->assertSessionHas('error');
        $this->assertDatabaseCount('cargo_alumno_eventos', 1);
        $this->assertSame('0.00', $this->cargo->fresh()->monto_condonado);
    }

    public function test_cuota_no_condonada_no_admite_reactivacion(): void
    {
        foreach (['PENDIENTE', 'PAGADA', 'AJUSTADA'] as $estado) {
            $this->cuota->update(['estado' => $estado]);
            $antes = $this->cuota->fresh()->getAttributes();
            $this->accion('web.deudas.reactivar', $this->cuota->id)->assertSessionHas('error');
            $this->assertSame($antes, $this->cuota->fresh()->getAttributes());
        }
    }

    public function test_inscripcion_sin_perdon_no_admite_reactivacion(): void
    {
        $this->accion('web.inscripciones.reactivar', $this->cargo->id)->assertSessionHas('error');
        $this->assertDatabaseCount('cargo_alumno_eventos', 1);
    }

    public function test_perdonada_no_se_cobra_sin_crear_pago_movimiento_o_recibo(): void
    {
        $this->cuota->update(['estado' => 'CONDONADA']);
        $this->perdonarCargo();
        $antes = $this->dinero();
        foreach ([[], [['periodo' => '2026-10', 'monto' => 48000]]] as $items) {
            try { $this->cobrar(1000, $items); $this->fail('Aceptó cobrar una deuda perdonada'); }
            catch (\RuntimeException $e) { $this->assertStringContainsString('importe entregado', $e->getMessage()); }
            catch (\Exception $e) { $this->assertStringContainsString('condonada', $e->getMessage()); }
        }
        $this->assertSame($antes, $this->dinero());
        $this->assertSame([], Storage::allFiles());
    }

    public function test_tres_pantallas_y_buscador_coinciden_antes_perdon_despues_y_reactivacion(): void
    {
        foreach ([53000, 0, 53000] as $i => $esperado) {
            if ($i === 1) { $this->perdonarCargo(); $this->accion('web.deudas.condonar', $this->cuota->id)->assertSessionHas('success'); }
            if ($i === 2) { $this->reactivarCargo(); $this->accion('web.deudas.reactivar', $this->cuota->id)->assertSessionHas('success'); }
            $c = $this->get(route('web.cobranza.index'))->assertOk()->viewData('resumen');
            $inicio = $this->get(route('admin.dashboard'))->assertOk()->viewData('reporte');
            $reporte = $this->get(route('web.reportes.index', ['mes' => '2026-10']))->assertOk()->viewData('reporte');
            $this->assertEquals($esperado, $c['total_adeudado']);
            $this->assertSame($esperado * 100, $inicio['deuda']['total']);
            $this->assertSame($inicio['deuda']['total'], $reporte['deuda']['total']);
            $b = $this->get(route('web.caja.cobrar-cuota'))->assertOk();
            $this->assertSame($esperado > 0 ? 1 : 0, $b->viewData('alumnos')->total());
        }
    }

    public function test_cuota_futura_reactivada_no_suma_hasta_el_primer_dia_del_mes(): void
    {
        $this->perdonarCargo();
        $this->cuota->delete();
        $futura = DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-11',
            'monto_original' => 42000, 'monto_pagado' => 0, 'estado' => 'CONDONADA']);
        $this->accion('web.deudas.reactivar', $futura->id)->assertSessionHas('success');
        $this->assertEquals(0, app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado']);
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-10')['deuda']['total']);
        $this->get(route('web.caja.cobrar-cuota'))->assertOk()->assertViewHas('alumnos', fn ($a) => $a->total() === 0);
        Carbon::setTestNow('2026-11-01 12:00:00');
        $this->assertEquals(42000, app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado']);
        $this->assertSame(4200000, app(ReporteMensualService::class)->obtener('2026-11')['deuda']['total']);
    }

    public function test_alumno_de_baja_admite_ambas_decisiones_sin_reactivar_al_alumno(): void
    {
        $this->alumno->update(['activo' => false]);
        $this->perdonarCargo();
        $this->accion('web.deudas.condonar', $this->cuota->id)->assertSessionHas('success');
        $this->reactivarCargo();
        $this->accion('web.deudas.reactivar', $this->cuota->id)->assertSessionHas('success');
        $this->assertFalse($this->alumno->fresh()->activo);
        $this->assertSame(48000.0, $this->cuota->fresh()->saldo_pendiente);
        $this->assertSame(5000.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertEquals(0, app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado']);
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-10')['deuda']['total']);
    }

    public function test_dos_deportes_un_cargo_solo_ficha_duena_ofrece_decidir(): void
    {
        $otroDeporte = Deporte::whereKeyNot($this->alumno->deporte_id)->firstOrFail();
        $otro = $this->alumno->replicate();
        $otro->deporte_id = $otroDeporte->id;
        $otro->save();
        $this->perdonarCargo();
        $this->get(route('web.alumnos.show', $otro->id))->assertOk()->assertSee('La inscripción se cobra una sola vez')
            ->assertDontSee('data-abrir-reactivar="'.route('web.inscripciones.reactivar', $this->cargo->id).'"', false);
        $this->get(route('web.alumnos.show', $this->alumno->id))->assertSee('Saldo pendiente: $0.00');
        $this->reactivarCargo();
        $this->assertDatabaseCount('cargos_alumno', 1);
        $this->get(route('web.alumnos.show', $this->alumno->id))->assertSee('Saldo pendiente: $5,000.00');
        $this->get(route('web.alumnos.show', $otro->id))->assertSee('La inscripción se cobra una sola vez');
    }

    public function test_mes_anterior_conserva_cuota_historica_pero_usa_perdon_actual_de_inscripcion(): void
    {
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->cargo->created_at = now(); $this->cargo->save();
        $this->cuota->delete();
        $this->cuota = DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-09',
            'monto_original' => 48000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        Carbon::setTestNow('2026-10-13 12:00:00');
        $s = app(ReporteMensualService::class);
        $this->assertSame(5300000, $s->obtener('2026-09')['deuda']['total']);
        $this->perdonarCargo();
        $this->accion('web.deudas.condonar', $this->cuota->id)->assertSessionHas('success');
        $this->assertSame(4800000, $s->obtener('2026-09')['deuda']['total']);
        $this->assertSame(0, $s->obtener('2026-10')['deuda']['total']);
        $this->reactivarCargo();
        $this->accion('web.deudas.reactivar', $this->cuota->id)->assertSessionHas('success');
        $this->assertSame(5300000, $s->obtener('2026-09')['deuda']['total']);
        $eventos = DB::table('reporte_eventos')->where('deuda_cuota_id', $this->cuota->id)->orderBy('id')->get();
        $this->assertSame(['2026-09-20', '2026-10-13', '2026-10-13'], $eventos->pluck('fecha')->all());
        $this->assertSame([4800000, -4800000, 4800000], $eventos->pluck('delta_centavos')->all());
    }

    public function test_evidencia_ficha_real_opcional_sin_sumar_una_prueba_omitida(): void
    {
        $this->assertTrue(true);
        if (getenv('WINGS_CAPTURAS_T33') !== '1') return;
        $directorio = base_path('docs/06-pruebas/PRU-04/evidencia-t33');
        if (!is_dir($directorio)) mkdir($directorio, 0775, true);
        $guardar = function ($nombre, $ruta) use ($directorio) {
            $html = $this->get($ruta)->assertOk()->getContent();
            $html = preg_replace('~(?:https?://[^/\"]+)?/build/~', './build/', $html);
            file_put_contents($directorio.'/'.$nombre.'.html', $html);
        };
        $guardar('inscripcion-pendiente', route('web.alumnos.show', $this->alumno->id));
        $this->perdonarCargo();
        $guardar('inscripcion-condonada', route('web.alumnos.show', $this->alumno->id));
        $this->accion('web.deudas.condonar', $this->cuota->id)->assertSessionHas('success');
        $guardar('cuota-condonada', route('web.alumnos.show', $this->alumno->id));
        auth()->logout();
        $guardar('login', route('login'));
    }
}
