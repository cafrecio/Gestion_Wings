<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerarOchoSituacionesTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'America/Argentina/Buenos_Aires';

    private User $admin;
    private User $sandra;
    private User $marcos;
    private TipoCaja $tipoEfectivo;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Carlos Admin', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->sandra = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->marcos = User::factory()->create(['name' => 'Marcos Peña', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $this->tipoEfectivo = TipoCaja::first();
        $this->actingAs($this->admin)->post('/caja/configuracion', ['tipo_caja_id' => $this->tipoEfectivo->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function abrirCaja(User $operativo, string $fechaHora, string $monto = '10.000,00'): CajaOperativa
    {
        $dt = Carbon::parse($fechaHora, self::TZ);
        Carbon::setTestNow($dt);

        $this->actingAs($operativo)->post('/caja/apertura', [
            'efectivo_inicial' => $monto,
            'confirmacion' => '1',
            'caja_origen_id' => null,
            'motivo_apertura' => 'Test apertura',
        ]);

        return CajaOperativa::where('usuario_operativo_id', $operativo->id)
            ->where('estado', 'ABIERTA')
            ->latest('id')
            ->firstOrFail();
    }

    private function cobrarManual(User $operativo, float $monto): void
    {
        $sub = Subrubro::where('permitido_para', 'OPERATIVO')
            ->where('es_reservado_sistema', false)
            ->firstOrFail();

        $this->actingAs($operativo)->post('/caja/movimiento', [
            'tipo' => 'INGRESO',
            'subrubro_id' => $sub->id,
            'monto' => number_format($monto, 2, ',', '.'),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'concepto' => 'Cobro prueba',
        ]);
    }

    public function test_exportar_ocho_situaciones(): void
    {
        $destDir = base_path('docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/html-ocho');
        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        // 1. Recién llega, nadie abrió
        Carbon::setTestNow('2026-10-08 10:00:00');
        $r1 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        file_put_contents("{$destDir}/situacion-1.html", $r1->getContent());
        CajaOperativa::query()->delete();

        // 2. Turno propio abierto hoy con cobro
        $this->abrirCaja($this->sandra, '2026-10-08 10:00:00');
        $this->cobrarManual($this->sandra, 25000);
        $r2 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        file_put_contents("{$destDir}/situacion-2.html", $r2->getContent());
        CajaOperativa::query()->delete();

        // 3. El turno lo abrió Marcos; entra Sandra
        $this->abrirCaja($this->marcos, '2026-10-08 10:00:00');
        $this->cobrarManual($this->marcos, 18000);
        Carbon::setTestNow('2026-10-08 11:30:00');
        $r3 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        file_put_contents("{$destDir}/situacion-3.html", $r3->getContent());
        CajaOperativa::query()->delete();

        // 4. Sandra cerró su turno hoy
        Carbon::setTestNow('2026-10-08 10:00:00');
        $caja4 = $this->abrirCaja($this->sandra, '2026-10-08 10:00:00');
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $caja4->id), [
            'efectivo_contado' => '10.000,00',
            'cambio_retenido' => '10.000,00',
        ]);
        $r4 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        file_put_contents("{$destDir}/situacion-4.html", $r4->getContent());
        CajaOperativa::query()->delete();

        // 5. Sandra tiene una caja rechazada
        Carbon::setTestNow('2026-10-08 10:00:00');
        $caja5 = $this->abrirCaja($this->sandra, '2026-10-08 10:00:00');
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $caja5->id), [
            'efectivo_contado' => '10.000,00',
            'cambio_retenido' => '10.000,00',
        ]);
        $this->actingAs($this->admin)->post(route('web.cajas.rechazar', $caja5->id), [
            'motivo' => 'Revisar arqueo',
            'motivo_rechazo' => 'Revisar arqueo',
        ]);
        $r5 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        file_put_contents("{$destDir}/situacion-5.html", $r5->getContent());
        CajaOperativa::query()->delete();

        // 6. Marcos dejó abierta ayer; Sandra entra hoy
        $this->abrirCaja($this->marcos, '2026-10-07 18:30:00');
        Carbon::setTestNow('2026-10-08 10:00:00');
        $r6 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        file_put_contents("{$destDir}/situacion-6.html", $r6->getContent());
        CajaOperativa::query()->delete();

        // 7. Sandra dejó abierta ayer; entra hoy
        $this->abrirCaja($this->sandra, '2026-10-07 18:30:00');
        Carbon::setTestNow('2026-10-08 10:00:00');
        $r7 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        file_put_contents("{$destDir}/situacion-7.html", $r7->getContent());
        CajaOperativa::query()->delete();

        // 8. Turno propio abierto a las 22:30
        $this->abrirCaja($this->sandra, '2026-10-08 22:30:00');
        $this->cobrarManual($this->sandra, 9000);
        $r8 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        file_put_contents("{$destDir}/situacion-8.html", $r8->getContent());

        $this->assertTrue(true);
    }
}
