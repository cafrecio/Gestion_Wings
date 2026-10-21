<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, MovimientoOperativo, Rubro, Subrubro, TipoCaja, User};
use App\Services\ReporteMensualService;
use App\Support\ClasificacionSubrubros;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verificación independiente de T61 (Claude CAB, 20/10). Fuera de tests/ a propósito.
 *   DB_DATABASE=wings_testing_claude php artisan test docs/06-pruebas/PRU-04/evidencia-verificacion-t61-claude/VerificacionT61ClaudeTest.php
 */
class VerificacionT61ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private TipoCaja $banco;
    private Rubro $gastos;

    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
            $this->markTestSkipped('Solo wings_testing_claude.');
        }
        Carbon::setTestNow('2026-10-20 12:00:00');
        Notification::fake();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2026-09-01']);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->banco = TipoCaja::where('nombre', 'Banco Nación')->firstOrFail();
        $this->gastos = Rubro::where('nombre', 'Gastos Operativos')->where('tipo', 'EGRESO')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function reporte(): array
    {
        return app(ReporteMensualService::class)->obtener('2026-10');
    }

    public function test_camino_feliz_300mil_un_envio_dos_patas_y_el_resultado_no_cambia(): void
    {
        $antes = $this->reporte();
        $clave = (string) Str::uuid();
        $datos = ['clave' => $clave, 'salida_id' => $this->efectivo->id, 'destino_id' => $this->banco->id,
            'monto' => '300,000.00', 'fecha' => '2026-10-20'];

        $this->actingAs($this->admin)->post(route('web.cashflow.transferencia.store'), $datos)
            ->assertRedirect(route('web.cashflow.index'))->assertSessionHas('success');
        // Doble envío: no duplica.
        $this->actingAs($this->admin)->post(route('web.cashflow.transferencia.store'), $datos)->assertRedirect(route('web.cashflow.index'));

        $movs = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)->orderBy('monto')->get();
        $this->assertCount(2, $movs);
        $this->assertSame(['-300000.00', '300000.00'], $movs->pluck('monto')->map(fn ($m) => (string) $m)->all());
        $this->assertSame([$this->efectivo->id, $this->banco->id], $movs->pluck('tipo_caja_id')->map(fn ($i) => (int) $i)->all());
        $this->assertSame(['TRASPASO', 'TRASPASO'], $movs->pluck('reporte_clasificacion')->all());
        $this->assertFalse($movs->contains(fn ($m) => $m->puedeCorregirse()));

        $despues = $this->reporte();
        foreach (['ingresos', 'egresos', 'resultado', 'gastos_club', 'aportes', 'retiros', 'disponible'] as $k) {
            $this->assertSame($antes[$k], $despues[$k], $k);
        }
        $saldo = fn ($r, $id) => collect($r['cajas'])->firstWhere('id', $id)['total'];
        $this->assertSame(-30000000, $saldo($despues, $this->efectivo->id) - $saldo($antes, $this->efectivo->id));
        $this->assertSame(30000000, $saldo($despues, $this->banco->id) - $saldo($antes, $this->banco->id));
    }

    public function test_hallazgo_el_duenio_puede_crear_un_subrubro_comun_clasificado_traspaso(): void
    {
        $this->actingAs($this->admin)->post(route('web.subrubros.store', $this->gastos->id), [
            'nombre' => 'Compra escondida', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => 1,
            'clasificacion_resultado' => ClasificacionSubrubros::TRASPASO,
        ]);

        $sub = Subrubro::where('nombre', 'Compra escondida')->first();
        // Se registra lo que pasa hoy; si el formulario lo rechaza, este control queda en rojo y el hallazgo no existe.
        $this->assertNotNull($sub, 'El formulario rechazó TRASPASO en un subrubro común');
        $this->assertSame('TRASPASO', $sub->clasificacion_resultado);
        $this->assertFalse((bool) $sub->es_reservado_sistema);
    }

    public function test_hallazgo_con_ese_subrubro_la_operativa_carga_un_gasto_que_no_cuenta_en_el_resultado(): void
    {
        $sub = Subrubro::create(['rubro_id' => $this->gastos->id, 'nombre' => 'Compra escondida', 'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true, 'activo' => true, 'clasificacion_resultado' => ClasificacionSubrubros::TRASPASO]);
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, $this->efectivo->id);
        $antes = $this->reporte();

        $this->actingAs($this->operativo)->post(route('web.caja.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => $sub->id, 'monto' => '50,000.00',
        ])->assertSessionHasNoErrors();

        $mov = MovimientoOperativo::where('subrubro_id', $sub->id)->first();
        $this->assertNotNull($mov, 'La caja rechazó el subrubro TRASPASO');
        $despues = $this->reporte();
        // Un egreso real de $50.000 que no aparece en gastos ni baja el resultado: una sola pata.
        $this->assertSame($antes['egresos'], $despues['egresos']);
        $this->assertSame($antes['resultado'], $despues['resultado']);
    }

    public function test_hallazgo_corregir_un_gasto_directo_pasandolo_a_ese_subrubro(): void
    {
        $sub = Subrubro::create(['rubro_id' => $this->gastos->id, 'nombre' => 'Compra escondida', 'permitido_para' => 'ADMIN',
            'afecta_caja' => true, 'activo' => true, 'clasificacion_resultado' => ClasificacionSubrubros::TRASPASO]);
        $limpieza = Subrubro::where('nombre', 'Limpieza')->firstOrFail();
        $this->actingAs($this->admin)->post('/cashflow/movimiento', ['subrubro_id' => $limpieza->id, 'tipo_caja_id' => $this->efectivo->id,
            'monto' => '40,000.00', 'fecha' => '2026-10-20', 'observaciones' => 'Gasto real'])->assertSessionHas('success');
        $mov = CashflowMovimiento::latest('id')->firstOrFail();
        $conGasto = $this->reporte();

        $respuesta = $this->actingAs($this->admin)->put(route('web.cashflow.movimientos.update', $mov->id), [
            'clave' => (string) Str::uuid(), 'version' => $mov->version_correccion, 'motivo' => 'Prueba de verificación T61',
            'fecha' => '2026-10-20', 'tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => $sub->id,
            'monto' => '40,000.00', 'observaciones' => 'Gasto real',
        ]);

        $mov->refresh();
        $resultado = ['redireccion' => $respuesta->headers->get('Location'), 'errores' => session('errors')?->all(),
            'subrubro_final' => $mov->subrubro->nombre, 'clasificacion_final' => $mov->reporte_clasificacion,
            'egresos_antes' => $conGasto['egresos'], 'egresos_despues' => $this->reporte()['egresos']];
        file_put_contents(__DIR__.'/correccion-a-traspaso.json', json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
        // Solo registra qué pasa: la conclusión va en el informe.
        $this->assertTrue(true);
    }
}
