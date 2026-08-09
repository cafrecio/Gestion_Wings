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

class EditarMovimientoSubrubroTest extends TestCase
{
    use RefreshDatabase;

    public function test_edicion_rechaza_subrubros_reservados_admin_o_que_no_afectan_caja(): void
    {
        $operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);
        $tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $rubro = Rubro::create([
            'nombre' => 'Gastos',
            'tipo' => 'EGRESO',
            'observacion' => '',
        ]);
        $subrubroValido = $this->crearSubrubro($rubro, 'Librería');
        $destinosInvalidos = [
            $this->crearSubrubro($rubro, 'Sistema', reservado: true),
            $this->crearSubrubro($rubro, 'Servicios', permitidoPara: User::ROL_ADMIN),
            $this->crearSubrubro($rubro, 'Sin caja', afectaCaja: false),
        ];

        foreach ($destinosInvalidos as $indice => $destino) {
            $movimiento = MovimientoOperativo::create([
                'caja_operativa_id' => $caja->id,
                'fecha' => today(),
                'tipo_caja_id' => $tipoCaja->id,
                'subrubro_id' => $subrubroValido->id,
                'monto' => 1000 + $indice,
                'observaciones' => 'Original',
                'usuario_id' => $operativo->id,
                'estado' => MovimientoOperativo::ESTADO_ACTIVO,
            ]);

            $this->actingAs($operativo)
                ->from(route('web.caja.detalle', $caja->id))
                ->put(route('web.caja.movimientos.update', [
                    'cajaId' => $caja->id,
                    'movId' => $movimiento->id,
                ]), [
                    'tipo_caja_id' => $tipoCaja->id,
                    'subrubro_id' => $destino->id,
                    'monto' => 2000,
                    'fecha' => today()->toDateString(),
                    'observaciones' => 'Alterado',
                ])
                ->assertRedirect(route('web.caja.detalle', $caja->id))
                ->assertSessionHas('error');

            $this->assertDatabaseHas('movimientos_operativos', [
                'id' => $movimiento->id,
                'subrubro_id' => $subrubroValido->id,
                'monto' => (string) (1000 + $indice) . '.00',
                'observaciones' => 'Original',
            ]);
        }
    }

    private function crearSubrubro(
        Rubro $rubro,
        string $nombre,
        string $permitidoPara = User::ROL_OPERATIVO,
        bool $afectaCaja = true,
        bool $reservado = false
    ): Subrubro {
        return Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => $nombre,
            'permitido_para' => $permitidoPara,
            'afecta_caja' => $afectaCaja,
            'es_reservado_sistema' => $reservado,
        ]);
    }
}
