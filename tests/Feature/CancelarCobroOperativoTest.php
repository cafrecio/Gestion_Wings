<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\PagoDeudaCuota;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\PagoCuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelarCobroOperativoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelacion_elimina_imputaciones_y_restablece_el_invariante_de_la_deuda(): void
    {
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);
        $tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);
        $deporte = Deporte::create([
            'nombre' => 'Vóley',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Cancelación']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $alumno = Alumno::create([
            'nombre' => 'Cobro',
            'apellido' => 'Cancelado',
            'dni' => '40111222',
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => today(),
            'activo' => true,
        ]);
        $deuda = DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => now()->format('Y-m'),
            'monto_original' => 10000,
            'monto_pagado' => 10000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
        $pago = Pago::create([
            'alumno_id' => $alumno->id,
            'mes' => now()->month,
            'anio' => now()->year,
            'monto_base' => 10000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 10000,
            'fecha_pago' => today(),
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);
        PagoDeudaCuota::create([
            'pago_id' => $pago->id,
            'deuda_cuota_id' => $deuda->id,
            'monto_aplicado' => 10000,
        ]);
        $movimiento = MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today(),
            'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $subrubro->id,
            'monto' => 10000,
            'usuario_id' => $operativo->id,
            'alumno_id' => $alumno->id,
            'pago_id' => $pago->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        app(PagoCuotaService::class)->cancelarCobroOperativo(
            $movimiento->id,
            'Pago duplicado',
            $operativo->id
        );

        $this->assertDatabaseHas('pagos', [
            'id' => $pago->id,
            'estado' => Pago::ESTADO_ANULADO,
        ]);
        $this->assertDatabaseHas('deuda_cuotas', [
            'id' => $deuda->id,
            'monto_pagado' => '0.00',
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
        $this->assertDatabaseMissing('pago_deuda_cuota', ['pago_id' => $pago->id]);
        $this->assertSame(
            0.0,
            (float) PagoDeudaCuota::where('deuda_cuota_id', $deuda->id)->sum('monto_aplicado')
        );
    }
}
