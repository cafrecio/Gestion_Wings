<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, MovimientoOperativo, Rubro, Subrubro, TipoCaja, User};
use App\Services\{CajaService, CashflowSaldoService, ReporteAlumnosService, ReporteMensualService, ReporteSueldosService};
use App\Support\ClasificacionSubrubros;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Illuminate\Support\Str;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/** CSH-018: dos movimientos independientes; nunca un gasto ni un ingreso del club. */
class TraspasosEntreMediosT61Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TipoCaja $efectivo;
    private TipoCaja $banco;
    private Subrubro $salida;
    private Subrubro $entrada;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || !in_array(config('database.connections.mysql.database'),
            ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T61 solo permite bases descartables de pruebas.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 12:00:00');
        Notification::fake();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2026-09-01']);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($this->admin);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->banco = TipoCaja::where('nombre', 'Banco Nación')->firstOrFail();
        $this->efectivo->update(['saldo_inicial' => '600000.00']);
        // El capital de partida existe desde septiembre; no inventarlo al comparar ambos cierres.
        DB::table('reporte_eventos')->where('tipo', 'CAJA_INICIAL')->where('origen_id', $this->efectivo->id)
            ->update(['fecha' => '2026-09-01']);
        $this->salida = Subrubro::where('nombre', 'Salida a otro medio')->firstOrFail();
        $this->entrada = Subrubro::where('nombre', 'Entrada desde otro medio')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cargar(Subrubro $sub, TipoCaja $medio, string $monto = '300000.00', string $fecha = '2026-10-20'): CashflowMovimiento
    {
        $this->post('/cashflow/movimiento', ['subrubro_id' => $sub->id, 'tipo_caja_id' => $medio->id,
            'monto' => $monto, 'fecha' => $fecha, 'confirmar_fecha_vieja' => 1, 'observaciones' => 'Traspaso ficticio T61'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        return CashflowMovimiento::latest('id')->firstOrFail();
    }

    private function reporte(string $mes = '2026-10'): array
    {
        return app(ReporteMensualService::class)->obtener($mes);
    }

    private function indicadores(array $reporte): array
    {
        return \Illuminate\Support\Arr::only($reporte, ['ingresos', 'egresos', 'resultado', 'resultado_global',
            'sin_clasificar', 'aportes', 'retiros', 'disponible', 'deuda', 'por_pagar', 'gastos_club', 'avisos']);
    }

    public function test_catalogo_nuevo_tiene_dos_conceptos_admin_activos_y_la_etiqueta_en_ambos_tipos(): void
    {
        foreach ([$this->salida, $this->entrada] as $sub) {
            $this->assertSame('TRASPASO', $sub->clasificacion_resultado);
            $this->assertSame('ADMIN', $sub->permitido_para);
            $this->assertTrue((bool) $sub->afecta_caja);
            $this->assertTrue((bool) $sub->activo);
            $this->get(route('web.subrubros.edit', [$sub->rubro_id, $sub->id]))->assertOk()
                ->assertSee('Traspaso entre medios (no cuenta en el resultado)');
        }
        $this->assertSame('EGRESO', $this->salida->rubro->tipo);
        $this->assertSame('INGRESO', $this->entrada->rubro->tipo);
        $this->assertNotSame($this->salida->rubro->nombre, $this->entrada->rubro->nombre);
    }

    public function test_csh018_carga_300mil_efectivo_a_banco_sin_alterar_el_resultado_del_club(): void
    {
        // Una base con ingreso/gasto y aporte/retiro evita aprobar por comparar solo ceros.
        $this->cargar(Subrubro::where('nombre', 'Intereses Banco')->firstOrFail(), $this->banco, '12000.00');
        $this->cargar(Subrubro::where('nombre', 'Luz')->firstOrFail(), $this->banco, '5000.00');
        $this->cargar(Subrubro::where('nombre', 'Patines')->firstOrFail(), $this->efectivo, '8000.00');
        $this->cargar(Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail(), $this->efectivo, '2000.00');
        $antes = $this->reporte();
        $this->get('/cashflow?periodo=mes&anio=2026&mes=10')->assertOk()
            ->assertViewHas('totalIngresos', fn ($m) => (float) $m === 20000.0)
            ->assertViewHas('totalEgresos', fn ($m) => (float) $m === 7000.0);
        $salida = $this->cargar($this->salida, $this->efectivo);
        $entrada = $this->cargar($this->entrada, $this->banco);
        $despues = $this->reporte();
        $this->assertSame('-300000.00', $salida->monto);
        $this->assertSame('300000.00', $entrada->monto);
        foreach ([$salida, $entrada] as $m) {
            $this->assertSame('TRASPASO', $m->reporte_clasificacion);
            $this->assertNull($m->referencia_tipo);
        }
        $this->assertSame(1200000, $despues['ingresos']);
        $this->assertSame(500000, $despues['egresos']);
        $this->assertSame(700000, $despues['resultado']);
        $this->assertSame(800000, $despues['aportes']);
        $this->assertSame(200000, $despues['retiros']);
        $this->assertSame(0, $despues['sin_clasificar']);
        $this->assertSame($this->indicadores($antes), $this->indicadores($despues));
        $a = collect($antes['cajas'])->keyBy('id');
        $d = collect($despues['cajas'])->keyBy('id');
        $this->assertSame($a[$this->efectivo->id]['total'] - 30000000, $d[$this->efectivo->id]['total']);
        $this->assertSame($a[$this->banco->id]['total'] + 30000000, $d[$this->banco->id]['total']);
        $cashflow = $this->get('/cashflow?periodo=mes&anio=2026&mes=10')->assertOk()->assertSee('Traspaso');
        $cashflow->assertViewHas('movimientos', fn ($ms) => $ms->where('origenCorreccion', 'Traspaso')->count() === 2);
        $cashflow->assertViewHas('saldosPorMedio', $despues['cajas']);
        // Carlos: Cashflow muestra lo que entró y salió, aunque no sea del club.
        $cashflow->assertViewHas('totalIngresos', fn ($m) => (float) $m === 320000.0)
            ->assertViewHas('totalEgresos', fn ($m) => (float) $m === 307000.0);
        // También concilia el saldo del libro Cashflow, sin depender de la vista.
        $libro = collect(app(CashflowSaldoService::class)->obtenerSaldosPorTipoCaja()['por_tipo_caja'])->keyBy('tipo_caja_id');
        $this->assertEquals($d[$this->efectivo->id]['total'] / 100, $libro[$this->efectivo->id]['saldo']);
        $this->assertEquals($d[$this->banco->id]['total'] / 100, $libro[$this->banco->id]['saldo']);
        // CSH-018.d: T13 hará la pantalla; T61 entrega el texto listo con los dos pasos.
        $rutaAyuda = base_path('docs/06-pruebas/PRU-04/IMPLEMENTACION-T61.md');
        $this->assertFileExists($rutaAyuda);
        $this->assertSame(1, preg_match('/^## Para la ayuda\R(.*?)(?=^## |\z)/ms', file_get_contents($rutaAyuda), $seccion));
        foreach (['Salida a otro medio', 'Entrada desde otro medio', 'Efectivo', 'Banco', '$300.000', 'No es una venta'] as $texto) {
            $this->assertStringContainsString($texto, $seccion[1]);
        }
        $this->assertMatchesRegularExpression('/^1\. /m', $seccion[1]);
        $this->assertMatchesRegularExpression('/^2\. /m', $seccion[1]);
    }

    public function test_inicio_y_reportes_conservan_indicadores_y_sueldos_y_alumnos_no_cambian(): void
    {
        $f = new \Tests\Support\ImportesT24('apertura');
        $this->cargar(Subrubro::where('nombre', 'Intereses Banco')->firstOrFail(), $this->banco, '12000.00');
        $antes = $this->indicadores($this->reporte());
        $sueldos = app(ReporteSueldosService::class)->obtener('2026-10');
        $alumnos = app(ReporteAlumnosService::class)->obtener('2026-10');
        $this->assertSame(1, $alumnos['activos']);
        $this->assertNotEmpty($sueldos['profesores']);
        $this->cargar($this->salida, $this->efectivo);
        $this->cargar($this->entrada, $this->banco);
        foreach (['/admin/dashboard', '/reportes?mes=2026-10'] as $ruta) {
            $this->get($ruta)->assertOk()->assertViewHas('reporte', fn ($r) => $this->indicadores($r) === $antes);
        }
        $this->get('/reportes/sueldos?mes=2026-10')->assertOk()->assertViewHas('reporte', $sueldos);
        $this->get('/reportes/alumnos?mes=2026-10')->assertOk()->assertViewHas('reporte', $alumnos);
        $this->get('/reportes?mes=2026-10')->assertDontSee('Transferencias internas');
    }

    public function test_salida_y_entrada_de_meses_distintos_no_alteran_negocio_pero_si_global_de_cada_mes(): void
    {
        $this->cargar($this->salida, $this->efectivo, '300000.00', '2026-09-30');
        $septiembre = $this->reporte('2026-09');
        $this->assertSame(-30000000, $septiembre['resultado_global']);
        $this->assertSame(0, $septiembre['resultado']);
        $this->cargar($this->entrada, $this->banco, '300000.00', '2026-10-01');
        $octubre = $this->reporte();
        $this->assertSame(30000000, $octubre['resultado_global']);
        $this->assertSame(0, $octubre['resultado']);
        $this->assertSame(0, $octubre['sin_clasificar']);
        $sept = collect($septiembre['cajas'])->keyBy('id');
        $oct = collect($octubre['cajas'])->keyBy('id');
        $this->assertSame($sept[$this->efectivo->id]['total'], $oct[$this->efectivo->id]['total']);
        $this->assertSame($sept[$this->banco->id]['total'] + 30000000, $oct[$this->banco->id]['total']);
    }

    public function test_no_obliga_a_emparejar_importes_fechas_ni_registros(): void
    {
        $this->cargar($this->salida, $this->efectivo, '300000.00', '2026-10-19');
        $this->assertSame(0, $this->reporte()['resultado']);
        $this->cargar($this->entrada, $this->banco, '290000.00');
        $this->assertSame(2, CashflowMovimiento::count());
        $this->assertSame(-1000000, $this->reporte()['resultado_global']);
        $this->assertSame(0, $this->reporte()['resultado']);
    }

    public function test_operativa_no_ve_conceptos_ni_puede_cargarlos_por_envio_directo(): void
    {
        $op = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        app(CajaService::class)->configurarMostrador($this->efectivo->id, $this->admin->id);
        CajaDeclarada::crear($op->id, $this->efectivo->id);
        $this->actingAs($op)->get('/caja/movimiento')->assertOk()
            ->assertDontSee('Salida a otro medio')->assertDontSee('Entrada desde otro medio');
        foreach ([$this->salida, $this->entrada] as $sub) {
            $datos = ['subrubro_id' => $sub->id, 'tipo_caja_id' => $this->efectivo->id,
                'monto' => '300000.00', 'fecha' => '2026-10-20', 'observaciones' => 'No permitido T61'];
            $this->post('/caja/movimiento', $datos)->assertSessionHas('error', 'El subrubro seleccionado no está permitido para usuarios operativos.');
            $this->post('/cashflow/movimiento', $datos)->assertForbidden();
        }
        $this->get('/cashflow/movimiento')->assertForbidden();
        $this->assertSame(0, MovimientoOperativo::count());
        $this->assertSame(0, CashflowMovimiento::count());
    }

    public function test_formulario_acepta_traspaso_en_ambos_tipos_y_rechaza_clasificaciones_ajenas(): void
    {
        foreach ([$this->salida, $this->entrada] as $sub) {
            $ruta = route('web.subrubros.update', [$sub->rubro_id, $sub->id]);
            $datos = ['nombre' => $sub->nombre, 'permitido_para' => 'ADMIN', 'afecta_caja' => 1];
            foreach (['OTRA', 'traspaso', $sub->rubro->tipo === 'INGRESO' ? 'RETIRO' : 'APORTE'] as $ajena) {
                $this->put($ruta, $datos + ['clasificacion_resultado' => $ajena])->assertSessionHasErrors('clasificacion_resultado');
                $this->assertSame('TRASPASO', $sub->fresh()->clasificacion_resultado);
            }
            $this->put($ruta, $datos + ['clasificacion_resultado' => 'TRASPASO'])->assertSessionHasNoErrors();
        }
        $rubro = $this->salida->rubro;
        $this->put(route('web.rubros.update', $rubro->id), ['nombre' => $rubro->nombre, 'tipo' => 'INGRESO'])->assertSessionHas('success');
        $this->assertSame('TRASPASO', $this->salida->fresh()->clasificacion_resultado);
    }

    public function test_migracion_existente_dos_veces_no_duplica_y_no_pisa_clasificaciones(): void
    {
        DB::table('subrubros')->whereIn('id', [$this->salida->id, $this->entrada->id])->delete();
        DB::table('rubros')->whereIn('nombre', array_column(ClasificacionSubrubros::TRASPASOS, 'rubro'))->delete();
        $this->assertSame(12, Rubro::count());
        $this->assertSame(19, Subrubro::count());
        $luz = Subrubro::where('nombre', 'Luz')->firstOrFail();
        $luz->update(['clasificacion_resultado' => 'RETIRO']);
        $migracion = require database_path('migrations/2026_10_20_120000_agregar_traspasos_entre_medios.php');
        $migracion->up();
        $salida = Subrubro::where('nombre', 'Salida a otro medio')->sole();
        $salida->update(['clasificacion_resultado' => 'RETIRO']);
        $foto = DB::table('subrubros')->orderBy('id')->get()->toJson();
        $migracion->up();
        $this->assertSame(14, Rubro::count());
        $this->assertSame(21, Subrubro::count());
        $this->assertSame($foto, DB::table('subrubros')->orderBy('id')->get()->toJson());
        $this->assertSame('RETIRO', $luz->fresh()->clasificacion_resultado);
        $this->assertSame('RETIRO', $salida->fresh()->clasificacion_resultado);
    }

    public function test_migracion_completa_vacio_compatible_sin_reclasificar_fotos_historicas(): void
    {
        $original = $this->cargar($this->salida, $this->efectivo);
        DB::table('subrubros')->where('id', $this->salida->id)->update(['clasificacion_resultado' => null]);
        $migracion = require database_path('migrations/2026_10_20_120000_agregar_traspasos_entre_medios.php');
        $migracion->up();
        $this->assertSame('TRASPASO', $this->salida->fresh()->clasificacion_resultado);
        $this->assertSame('TRASPASO', $original->fresh()->reporte_clasificacion);
        $this->assertSame(21, Subrubro::count());
    }

    public function test_editar_y_anular_conserva_clasificacion_fecha_y_saldo_del_traspaso(): void
    {
        $mov = $this->cargar($this->salida, $this->efectivo);
        $ruta = '/cashflow/movimientos/'.$mov->id;
        $this->putJson($ruta, ['clave' => (string) Str::uuid(), 'version' => 0, 'fecha' => '2026-10-20',
            'subrubro_id' => $this->salida->id, 'tipo_caja_id' => $this->banco->id, 'monto' => '310000.00',
            'observaciones' => 'Corregido ficticio T61'])->assertRedirect();
        $this->assertSame('TRASPASO', $mov->fresh()->reporte_clasificacion);
        $this->assertSame('-310000.00', $mov->fresh()->monto);
        $this->assertSame($this->banco->id, $mov->fresh()->tipo_caja_id);
        $this->deleteJson($ruta, ['clave' => (string) Str::uuid(), 'version' => 1])->assertRedirect();
        $contra = CashflowMovimiento::where('contra_origen_id', $mov->id)->sole();
        $this->assertSame('310000.00', $contra->monto);
        $this->assertSame('TRASPASO', $contra->reporte_clasificacion);
        $this->assertSame('EGRESO', $contra->reporte_tipo);
        $this->assertSame($mov->fecha->toDateString(), $contra->fecha->toDateString());
        $this->assertNotNull($mov->fresh()->anulado_at);
        $this->assertEquals(0, CashflowMovimiento::sum('monto'));
        $this->assertSame(0, $this->reporte()['resultado']);
        $this->assertSame(0, $this->reporte()['sin_clasificar']);
        $this->assertSame(0, $this->reporte()['resultado_global']);
    }

    public function test_cambio_del_catalogo_no_reescribe_traspaso_ni_su_correccion_o_anulacion(): void
    {
        $mov = $this->cargar($this->entrada, $this->banco);
        $this->entrada->update(['clasificacion_resultado' => 'NEGOCIO']);
        $ruta = '/cashflow/movimientos/'.$mov->id;
        $this->putJson($ruta, ['clave' => (string) Str::uuid(), 'version' => 0, 'fecha' => '2026-10-20',
            'subrubro_id' => $this->entrada->id, 'tipo_caja_id' => $this->banco->id, 'monto' => '320000.00',
            'observaciones' => 'Conservar foto T61'])->assertRedirect();
        $this->assertSame('TRASPASO', $mov->fresh()->reporte_clasificacion);
        $this->assertSame(0, $this->reporte()['ingresos']);
        $this->deleteJson($ruta, ['clave' => (string) Str::uuid(), 'version' => 1])->assertRedirect();
        $contra = CashflowMovimiento::where('contra_origen_id', $mov->id)->sole();
        $this->assertSame('TRASPASO', $contra->reporte_clasificacion);
        $this->assertSame('-320000.00', $contra->monto);
        $this->assertSame(0, $this->reporte()['resultado']);
    }

    public function test_saldos_cashflow_son_actuales_aunque_filtre_otro_mes_y_distinguen_pendientes(): void
    {
        $op = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $caja = CajaDeclarada::crear($op->id, $this->efectivo->id);
        MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'fecha' => today(),
            'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->value('id'), 'tipo_caja_id' => $this->efectivo->id,
            'monto' => '5000.00', 'usuario_id' => $op->id, 'estado' => 'ACTIVO']);
        $this->cargar($this->salida, $this->efectivo);
        $saldos = $this->reporte()['cajas'];
        $this->assertSame(-500000, collect($saldos)->keyBy('id')[$this->efectivo->id]['pendiente']);
        $this->get('/cashflow?periodo=mes&anio=2026&mes=9')->assertOk()
            ->assertViewHas('saldosPorMedio', $saldos)->assertSee('En cajas sin validar:')
            ->assertSee('No dependen del período del listado.');
    }
}
