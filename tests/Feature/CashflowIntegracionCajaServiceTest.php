<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowIntegracionCajaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_caja_validada_refleja_solo_movimientos_activos(): void
    {
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'apertura_at' => now(),
            'cierre_at' => now(),
            'estado' => CajaOperativa::ESTADO_CERRADA,
        ]);
        $tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $rubro = Rubro::create([
            'nombre' => 'Ingresos',
            'tipo' => 'INGRESO',
            'observacion' => '',
        ]);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuotas',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
        ]);

        $this->crearMovimiento($caja, $tipoCaja, $subrubro, $operativo, 1000, MovimientoOperativo::ESTADO_ACTIVO);
        $this->crearMovimiento($caja, $tipoCaja, $subrubro, $operativo, 9000, MovimientoOperativo::ESTADO_CANCELADO);

        $service = app(CajaService::class);
        $service->validarCaja($caja->id, $admin->id);
        $service->validarCaja($caja->id, $admin->id);

        $this->assertDatabaseCount('cashflow_movimientos', 1);
        $this->assertDatabaseHas('cashflow_movimientos', [
            'referencia_tipo' => CashflowMovimiento::REF_CAJA,
            'referencia_id' => $caja->id,
            'monto' => '1000.00',
        ]);
        $this->assertDatabaseMissing('cashflow_movimientos', ['monto' => '9000.00']);
    }

    private function crearMovimiento(
        CajaOperativa $caja,
        TipoCaja $tipoCaja,
        Subrubro $subrubro,
        User $operativo,
        float $monto,
        string $estado
    ): void {
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today(),
            'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $subrubro->id,
            'monto' => $monto,
            'usuario_id' => $operativo->id,
            'estado' => $estado,
        ]);
    }
}
