<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\ReporteMensualService;
use App\Services\TransferenciaCashflowService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Control de Claude sobre T97, sobre main con T106. Afirma lo que tendría que pasar: una falla es un hallazgo.
 * Se corre por ruta y no queda en la suite.
 */
class ControlT97ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private TipoCaja $efectivo;
    private TipoCaja $banco;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-15 10:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->banco = TipoCaja::where('nombre', 'Banco Galicia')->firstOrFail();
        $this->efectivo->update(['saldo_inicial' => 600000]);
        $this->banco->update(['saldo_inicial' => 100000]);
        $this->actingAs($this->admin);
    }

    private function transferir(string $monto, string $fecha, ?string $observaciones = 'Depósito'): int
    {
        return app(TransferenciaCashflowService::class)->registrar($this->admin->id, ['clave' => (string) Str::uuid(),
            'salida_id' => $this->efectivo->id, 'destino_id' => $this->banco->id, 'monto' => $monto, 'fecha' => $fecha, 'observaciones' => $observaciones]);
    }

    private function patas(int $id)
    {
        return CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)->where('referencia_id', $id)->orderBy('id')->get();
    }

    private function anular(int $movimientoId, string $motivo = 'Cuenta de destino equivocada')
    {
        return $this->post(route('web.cashflow.transferencia.anular.store', $movimientoId), ['motivo' => $motivo]);
    }

    private function saldos(): array
    {
        $s = collect(app(\App\Services\CashflowSaldoService::class)->obtenerSaldosPorTipoCaja()['por_tipo_caja'])->keyBy('tipo_caja_id');

        return [(float) $s[$this->efectivo->id]['saldo'], (float) $s[$this->banco->id]['saldo']];
    }

    private function totales(array $filtros = [])
    {
        return $this->get('/cashflow?'.http_build_query(['periodo' => 'mes', 'fecha' => '2026-11-10'] + $filtros))->assertOk();
    }

    public function test_de_punta_a_punta_con_centavos_y_con_t106(): void
    {
        $id = $this->transferir('300000.75', '2026-11-10');
        $this->assertSame([299999.25, 400000.75], $this->saldos());
        $this->totales()->assertViewHas('totalIngresos', fn ($n) => (float) $n === 0.0)->assertViewHas('totalEgresos', fn ($n) => (float) $n === 0.0);

        $this->anular($this->patas($id)[1]->id)->assertSessionHas('success');

        $this->assertSame([600000.0, 100000.0], $this->saldos());
        foreach ([[], ['tipo_caja_id' => $this->efectivo->id], ['tipo_caja_id' => $this->banco->id]] as $filtro) {
            $this->totales($filtro)->assertViewHas('totalIngresos', fn ($n) => (float) $n === 0.0)->assertViewHas('totalEgresos', fn ($n) => (float) $n === 0.0)
                ->assertViewHas('movimientos', fn ($m) => $m->count() === ($filtro ? 2 : 4));
        }
        $this->get('/caja/historial?desde=2026-11-01&hasta=2026-11-30')->assertOk()
            ->assertViewHas('totalIngresos', fn ($n) => (float) $n === 0.0)->assertViewHas('totalEgresos', fn ($n) => (float) $n === 0.0);
        $this->assertSame(70000000, app(ReporteMensualService::class)->obtener('2026-11')['disponible']);
    }

    public function test_las_cuatro_filas_abren_y_solo_dejan_ver(): void
    {
        $id = $this->transferir('1000', '2026-11-10');
        $this->anular($this->patas($id)[0]->id)->assertSessionHas('success');

        $filas = CashflowMovimiento::whereIn('referencia_tipo', [CashflowMovimiento::REF_TRANSFERENCIA, CashflowMovimiento::REF_ANULACION_TRANSFERENCIA])->get();
        $this->assertCount(4, $filas);
        $foto = $filas->toJson();
        foreach ($filas as $fila) {
            $this->get(route('web.cashflow.movimientos.show', $fila->id))->assertOk();
            $this->assertGreaterThanOrEqual(403, $this->get(route('web.cashflow.movimientos.editar', $fila->id))->getStatusCode());
            $this->assertGreaterThanOrEqual(403, $this->get(route('web.cashflow.movimientos.eliminar', $fila->id))->getStatusCode());
        }
        $this->totales()->assertViewHas('movimientos', fn ($m) => $m->every(fn ($f) => array_column($f->accionesCorreccion, 'nombre') === ['Ver']));
        $this->assertSame($foto, CashflowMovimiento::whereIn('id', $filas->pluck('id'))->get()->toJson());
    }

    public function test_sin_observaciones_se_puede_anular(): void
    {
        $id = $this->transferir('1000', '2026-11-10', null);

        $this->get(route('web.cashflow.transferencia.anular', $this->patas($id)[0]->id))->assertOk();
        $this->anular($this->patas($id)[0]->id)->assertSessionHas('success');
    }

    public function test_si_la_plata_ya_se_gasto_la_anulacion_deja_negativo_sin_bloquear(): void
    {
        $id = $this->transferir('300000', '2026-11-10');
        CashflowMovimiento::create(['fecha' => '2026-11-12', 'subrubro_id' => Subrubro::where('nombre', 'Luz')->value('id'), 'tipo_caja_id' => $this->banco->id,
            'monto' => -350000, 'observaciones' => 'Gasto', 'usuario_admin_id' => $this->admin->id]);

        $this->anular($this->patas($id)[0]->id)->assertSessionHas('success');

        $this->assertSame([600000.0, -250000.0], $this->saldos());
    }

    public function test_anular_el_mes_anterior_no_cambia_el_mes_actual(): void
    {
        $antes = app(ReporteMensualService::class)->obtener('2026-11');
        $id = $this->transferir('300000', '2026-10-20');
        $this->anular($this->patas($id)[1]->id)->assertSessionHas('success');

        $despues = app(ReporteMensualService::class)->obtener('2026-11');
        foreach (['ingresos', 'egresos', 'resultado', 'aportes', 'retiros', 'disponible', 'cajas'] as $campo) {
            $this->assertEquals($antes[$campo], $despues[$campo], $campo);
        }
        $this->assertSame('2026-10-20', CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_ANULACION_TRANSFERENCIA)->first()->fecha->toDateString());
    }

    public function test_despues_de_anular_se_carga_otra_con_los_datos_correctos(): void
    {
        $id = $this->transferir('300000', '2026-11-10');
        $this->anular($this->patas($id)[0]->id)->assertSessionHas('success');
        $otra = $this->transferir('30000', '2026-11-10');

        $this->assertNotSame($id, $otra);
        $this->assertSame([570000.0, 130000.0], $this->saldos());
        $this->totales()->assertViewHas('movimientos', fn ($m) => $m->filter(fn ($f) => array_column($f->accionesCorreccion, 'nombre') === ['Ver', 'Anular'])->count() === 2);
    }

    public function test_la_operativa_no_llega_ni_ve_nada_de_esto(): void
    {
        $id = $this->transferir('300000', '2026-11-10', 'Depósito secreto');
        $pata = $this->patas($id)[0];

        $this->actingAs($this->operativa);
        $this->get(route('web.cashflow.transferencia.anular', $pata->id))->assertForbidden();
        $this->anular($pata->id)->assertForbidden();
        $this->get('/caja/historial?desde=2026-11-01&hasta=2026-11-30')->assertOk()->assertDontSee('Depósito secreto');
        $this->assertSame(0, DB::table('cashflow_transferencias')->whereNotNull('anulada_at')->count());
    }

    public function test_el_formulario_viejo_de_una_ya_anulada_no_hace_nada(): void
    {
        $id = $this->transferir('300000', '2026-11-10');
        $patas = $this->patas($id);
        $this->anular($patas[0]->id)->assertSessionHas('success');
        $primera = DB::table('cashflow_transferencias')->where('id', $id)->first();

        $this->get(route('web.cashflow.transferencia.anular', $patas[1]->id))->assertRedirect()->assertSessionHas('error', 'La transferencia ya está anulada.');
        $this->anular($patas[1]->id, 'Otro motivo')->assertSessionHasErrors(['motivo' => 'La transferencia ya está anulada.']);

        $this->assertSame($primera->motivo_anulacion, DB::table('cashflow_transferencias')->where('id', $id)->value('motivo_anulacion'));
        $this->assertSame(2, CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_ANULACION_TRANSFERENCIA)->count());
    }
}
