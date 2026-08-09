<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelarMovimientoScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_puede_cancelar_un_movimiento_que_no_pertenece_a_la_caja_indicada(): void
    {
        $operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);
        $otroOperativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);
        $cajaPropia = CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);
        $cajaAjena = CajaOperativa::create([
            'usuario_operativo_id' => $otroOperativo->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);
        $tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Ingresos', 'tipo' => 'INGRESO', 'observacion' => '']);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Prueba',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
        ]);
        $movimientoAjeno = MovimientoOperativo::create([
            'caja_operativa_id' => $cajaAjena->id,
            'fecha' => today(),
            'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $subrubro->id,
            'monto' => 1000,
            'usuario_id' => $otroOperativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        $this->actingAs($operativo)
            ->post(route('web.caja.movimientos.cancelar.store', [
                'cajaId' => $cajaPropia->id,
                'movId' => $movimientoAjeno->id,
            ]), ['motivo' => 'Intento fuera de scope'])
            ->assertNotFound();

        $this->assertDatabaseHas('movimientos_operativos', [
            'id' => $movimientoAjeno->id,
            'caja_operativa_id' => $cajaAjena->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);
    }
}
