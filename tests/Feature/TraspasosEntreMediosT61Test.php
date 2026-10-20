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

/** CSH-018: transferencia atómica, catálogo protegido y resultado económico conservado. */
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

    private function datos(array $cambios = []): array
    {
        return array_replace(['clave' => (string) Str::uuid(), 'salida_id' => $this->efectivo->id,
            'destino_id' => $this->banco->id, 'monto' => '300000.00', 'fecha' => '2026-10-20',
            'observaciones' => 'Transferencia ficticia T61'], $cambios);
    }

    private function transferir(array $cambios = []): void
    {
        $this->post('/cashflow/transferencia', $this->datos($cambios))
            ->assertRedirect('/cashflow')->assertSessionHasNoErrors()->assertSessionHas('success');
    }

    public function test_catalogo_protegido_rechaza_edicion_renombre_baja_y_subrubros_adicionales(): void
    {
        foreach ([$this->salida, $this->entrada] as $sub) {
            $this->assertTrue($sub->es_reservado_sistema);
            $this->assertTrue($sub->rubro->es_reservado_sistema);
            $this->assertSame('TRASPASO', $sub->clasificacion_resultado);
            $antes = $sub->getAttributes();
            $rubroAntes = $sub->rubro->getAttributes();
            $ruta = route('web.subrubros.update', [$sub->rubro_id, $sub->id]);
            $this->get(route('web.subrubros.edit', [$sub->rubro_id, $sub->id]))->assertSessionHas('error');
            $this->put($ruta, ['nombre' => 'Renombrado', 'clasificacion_resultado' => 'NEGOCIO',
                'permitido_para' => 'OPERATIVO'])->assertSessionHas('error');
            $this->put(route('web.rubros.update', $sub->rubro_id), ['nombre' => 'Renombrado', 'tipo' => 'EGRESO'])
                ->assertSessionHas('error');
            $this->delete(route('web.rubros.destroy', $sub->rubro_id))->assertSessionHas('error');
            $this->post(route('web.subrubros.store', $sub->rubro_id), ['nombre' => 'Adicional'])
                ->assertSessionHas('error');
            $this->assertSame($antes, $sub->fresh()->getAttributes());
            $this->assertSame($rubroAntes, $sub->rubro->fresh()->getAttributes());
        }
    }

    public function test_transferencia_mueve_300mil_y_conserva_todos_los_indicadores_del_club(): void
    {
        $this->cargar(Subrubro::where('nombre', 'Intereses Banco')->firstOrFail(), $this->banco, '12000.00');
        $this->cargar(Subrubro::where('nombre', 'Luz')->firstOrFail(), $this->banco, '5000.00');
        $this->cargar(Subrubro::where('nombre', 'Patines')->firstOrFail(), $this->efectivo, '8000.00');
        $this->cargar(Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail(), $this->efectivo, '2000.00');
        $antes = $this->reporte();
        $this->transferir();
        $despues = $this->reporte();
        $this->assertSame($this->indicadores($antes), $this->indicadores($despues));
        $this->assertSame(700000, $despues['resultado']);
        $a = collect($antes['cajas'])->keyBy('id');
        $d = collect($despues['cajas'])->keyBy('id');
        $this->assertSame($a[$this->efectivo->id]['total'] - 30000000, $d[$this->efectivo->id]['total']);
        $this->assertSame($a[$this->banco->id]['total'] + 30000000, $d[$this->banco->id]['total']);
        $movs = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)->orderBy('id')->get();
        $this->assertCount(2, $movs);
        $this->assertSame(['-300000.00', '300000.00'], $movs->pluck('monto')->all());
        $this->assertSame(1, $movs->pluck('referencia_id')->unique()->count());
        $this->assertSame(['TRASPASO', 'TRASPASO'], $movs->pluck('reporte_clasificacion')->all());
        $this->get('/cashflow?periodo=mes&anio=2026&mes=10')->assertOk()
            ->assertViewHas('totalIngresos', fn ($m) => (float) $m === 320000.0)
            ->assertViewHas('totalEgresos', fn ($m) => (float) $m === 307000.0);
        $libro = collect(app(CashflowSaldoService::class)->obtenerSaldosPorTipoCaja()['por_tipo_caja'])->keyBy('tipo_caja_id');
        $this->assertEquals($d[$this->efectivo->id]['total'] / 100, $libro[$this->efectivo->id]['saldo']);
        $this->assertEquals($d[$this->banco->id]['total'] / 100, $libro[$this->banco->id]['saldo']);
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/reportes?mes=2026-10')->assertOk();
    }

    public function test_fallo_al_guardar_entrada_revierte_salida_y_cabecera(): void
    {
        $evento = 'eloquent.creating: '.CashflowMovimiento::class;
        $listener = function ($mov) {
            if ($mov->referencia_tipo === CashflowMovimiento::REF_TRANSFERENCIA && (float) $mov->monto > 0) {
                throw new \RuntimeException('Fallo simulado de la entrada');
            }
        };
        \Illuminate\Support\Facades\Event::listen($evento, $listener);
        try {
            app(\App\Services\TransferenciaCashflowService::class)->registrar($this->admin->id, $this->datos());
            $this->fail('Debió fallar la segunda escritura.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo simulado de la entrada', $e->getMessage());
        }
        $this->assertDatabaseCount('cashflow_transferencias', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_reenvio_de_la_misma_operacion_no_duplica_y_rechaza_datos_distintos(): void
    {
        $datos = $this->datos();
        $this->post('/cashflow/transferencia', $datos)->assertSessionHas('success');
        $this->post('/cashflow/transferencia', $datos)->assertSessionHas('success');
        $this->assertDatabaseCount('cashflow_transferencias', 1);
        $this->assertDatabaseCount('cashflow_movimientos', 2);
        $this->postJson('/cashflow/transferencia', array_replace($datos, ['monto' => '300001.00']))
            ->assertUnprocessable()->assertJsonValidationErrors('clave');
        $this->assertDatabaseCount('cashflow_movimientos', 2);
    }

    public function test_rechaza_misma_cuenta_inactiva_fecha_futura_e_importes_invalidos_sin_escrituras(): void
    {
        foreach ([['destino_id' => $this->efectivo->id], ['destino_id' => 999999],
            ['monto' => '0'], ['monto' => '-1'], ['monto' => '1.001'], ['monto' => '10000000000.00'],
            ['fecha' => '2026-10-21']] as $cambio) {
            $this->postJson('/cashflow/transferencia', $this->datos($cambio))->assertUnprocessable();
        }
        $this->banco->update(['activo' => false]);
        $this->postJson('/cashflow/transferencia', $this->datos())->assertUnprocessable();
        $this->assertDatabaseCount('cashflow_transferencias', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_operativa_y_profesora_no_pueden_abrir_ni_registrar_transferencias(): void
    {
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $this->actingAs($usuario)->get('/cashflow/transferencia')->assertForbidden();
            $this->post('/cashflow/transferencia', $this->datos())->assertForbidden();
            try {
                app(\App\Services\TransferenciaCashflowService::class)->registrar($usuario->id, $this->datos());
                $this->fail('El servicio también debe controlar el rol.');
            } catch (\Illuminate\Auth\Access\AuthorizationException) {}
        }
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_no_permite_carga_individual_ni_edicion_o_anulacion_de_una_sola_pata(): void
    {
        foreach ([$this->salida, $this->entrada] as $sub) {
            $this->post('/cashflow/movimiento', ['subrubro_id' => $sub->id, 'tipo_caja_id' => $this->efectivo->id,
                'monto' => '100.00', 'fecha' => '2026-10-20', 'observaciones' => 'Pedido directo'])
                ->assertSessionHas('error');
        }
        $this->transferir();
        foreach (CashflowMovimiento::all() as $mov) {
            $this->assertFalse($mov->puedeCorregirse());
            $this->putJson('/cashflow/movimientos/'.$mov->id, ['clave' => (string) Str::uuid(), 'version' => 0,
                'subrubro_id' => $mov->subrubro_id, 'tipo_caja_id' => $this->efectivo->id,
                'monto' => '1.00', 'fecha' => '2026-10-20', 'observaciones' => 'No romper par'])->assertForbidden();
            $this->deleteJson('/cashflow/movimientos/'.$mov->id, ['clave' => (string) Str::uuid(), 'version' => 0])->assertForbidden();
        }
        $this->assertSame(['-300000.00', '300000.00'], CashflowMovimiento::orderBy('id')->pluck('monto')->all());
    }

    public function test_proteccion_del_catalogo_existente_no_inventa_vinculos_historicos(): void
    {
        $historico = CashflowMovimiento::create(['fecha' => '2026-10-19', 'subrubro_id' => $this->salida->id,
            'tipo_caja_id' => $this->efectivo->id, 'monto' => '-123.45', 'usuario_admin_id' => $this->admin->id]);
        $foto = $historico->fresh()->getAttributes();
        DB::table('subrubros')->whereIn('id', [$this->salida->id, $this->entrada->id])->update(['es_reservado_sistema' => false]);
        DB::table('rubros')->whereIn('id', [$this->salida->rubro_id, $this->entrada->rubro_id])->update(['es_reservado_sistema' => false]);
        ClasificacionSubrubros::reservarTraspasos();
        ClasificacionSubrubros::reservarTraspasos();
        $this->assertTrue($this->salida->fresh()->es_reservado_sistema);
        $this->assertTrue($this->entrada->rubro->fresh()->es_reservado_sistema);
        $this->assertSame($foto, $historico->fresh()->getAttributes());
        $this->assertDatabaseCount('cashflow_transferencias', 0);
        $this->assertDatabaseCount('subrubros', 21);
    }

    public function test_seeder_idempotente_preserva_clasificaciones_y_snapshots(): void
    {
        $this->transferir();
        $foto = CashflowMovimiento::orderBy('id')->get()->toJson();
        $luz = Subrubro::where('nombre', 'Luz')->firstOrFail();
        $luz->update(['clasificacion_resultado' => 'RETIRO']);
        ClasificacionSubrubros::aplicarTraspasos();
        ClasificacionSubrubros::aplicarTraspasos();
        $this->assertDatabaseCount('rubros', 14);
        $this->assertDatabaseCount('subrubros', 21);
        $this->assertSame('RETIRO', $luz->fresh()->clasificacion_resultado);
        $this->assertSame($foto, CashflowMovimiento::orderBy('id')->get()->toJson());
    }

    public function test_formulario_pide_dos_cuentas_un_importe_y_una_fecha_sin_rubros_editables(): void
    {
        $this->get('/cashflow/transferencia')->assertOk()->assertSee('Cuenta de salida')
            ->assertSee('Cuenta de destino')->assertSee('Importe')->assertSee('Fecha')
            ->assertDontSee('name="subrubro_id"', false)->assertDontSee('name="rubro_id"', false);
        $this->get('/cashflow/movimiento')->assertOk()->assertDontSee('Salida a otro medio')->assertDontSee('Entrada desde otro medio');
        $this->get('/cashflow')->assertOk()->assertSee(route('web.cashflow.transferencia'), false);
    }

    public function test_transferencia_fin_de_anio_con_centavos_conserva_resultado_y_fecha_de_ambas_patas(): void
    {
        Carbon::setTestNow('2027-01-01 12:00:00');
        $this->transferir(['monto' => '1233545.67', 'fecha' => '2026-12-31', 'observaciones' => null]);
        foreach (CashflowMovimiento::all() as $mov) {
            $this->assertSame('2026-12-31', $mov->fecha->toDateString());
        }
        $this->assertEquals(0, CashflowMovimiento::sum('monto'));
        $this->assertSame(0, $this->reporte('2026-12')['resultado']);
        $this->assertSame(0, $this->reporte('2026-12')['resultado_global']);
        $this->assertSame(0, $this->reporte('2027-01')['resultado']);
    }

    public function test_saldos_actuales_independientes_del_filtro_y_cajas_pendientes(): void
    {
        $op = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $caja = CajaDeclarada::crear($op->id, $this->efectivo->id);
        MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'fecha' => today(),
            'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->value('id'), 'tipo_caja_id' => $this->efectivo->id,
            'monto' => '5000.00', 'usuario_id' => $op->id, 'estado' => 'ACTIVO']);
        $this->transferir();
        $saldos = $this->reporte()['cajas'];
        $this->assertSame(-500000, collect($saldos)->keyBy('id')[$this->efectivo->id]['pendiente']);
        $this->get('/cashflow?periodo=mes&anio=2026&mes=9')->assertOk()
            ->assertViewHas('saldosPorMedio', $saldos)->assertSee('En cajas sin validar:');
    }
}
