<?php

namespace Tests\Support;

use App\Models\{CashflowMovimiento, TipoCaja, User};
use App\Services\{ReporteMensualService, TransferenciaCashflowService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Support\Facades\{DB, Notification};
use Illuminate\Support\Str;

trait TransferenciasT97
{
    protected User $admin;
    protected TipoCaja $salida;
    protected TipoCaja $destino;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || !in_array(config('database.connections.mysql.database'),
            ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T97 solo permite bases descartables de pruebas.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-15 12:00:00');
        Notification::fake();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Laura Gómez', 'rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($this->admin);
        $this->salida = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->destino = TipoCaja::where('nombre', 'Banco Nación')->firstOrFail();
        $this->salida->update(['saldo_inicial' => '600000.00']);
        $this->destino->update(['saldo_inicial' => '100000.00']);
        DB::table('reporte_eventos')->where('tipo', 'CAJA_INICIAL')
            ->whereIn('origen_id', [$this->salida->id, $this->destino->id])->update(['fecha' => '2026-09-01']);
        DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2026-09-01']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function transferir(string $fecha = '2026-11-15', string $monto = '300000.00')
    {
        $id = app(TransferenciaCashflowService::class)->registrar($this->admin->id, [
            'clave' => (string) Str::uuid(), 'salida_id' => $this->salida->id, 'destino_id' => $this->destino->id,
            'fecha' => $fecha, 'monto' => $monto, 'observaciones' => 'Depósito del efectivo en el banco',
        ]);
        return CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)
            ->where('referencia_id', $id)->orderBy('id')->get();
    }

    protected function fotoT97(): array
    {
        return collect(['cashflow_transferencias', 'cashflow_movimientos', 'reporte_eventos'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->orderBy('id')->get()->toJson()])->all();
    }

    protected function assertCuentasT97(int $salida = 60000000, int $destino = 10000000): void
    {
        $saldos = collect(app(ReporteMensualService::class)->saldosPorCaja('2026-11-15'))->keyBy('id');
        $this->assertSame($salida, $saldos[$this->salida->id]['total']);
        $this->assertSame($destino, $saldos[$this->destino->id]['total']);
        $this->assertSame($salida + $destino, array_sum(array_column($saldos->all(), 'total')));
    }

    protected function rutaT97(int $id): string
    {
        return route('web.cashflow.transferencia.anular.store', $id);
    }
}
