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

class CajaAperturaSaldoInicialP2Test extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private TipoCaja $efectivo;
    private Subrubro $subrubroIngreso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->efectivo = TipoCaja::firstOrCreate(
            ['nombre' => 'Efectivo'],
            ['activo' => true, 'permite_descubierto' => false]
        );

        $rubro = Rubro::firstOrCreate(
            ['nombre' => 'Cuotas'],
            ['tipo' => 'INGRESO']
        );

        $this->subrubroIngreso = Subrubro::firstOrCreate(
            ['nombre' => 'Cobro Cuota'],
            [
                'rubro_id' => $rubro->id,
                'permitido_para' => 'OPERATIVO',
                'afecta_caja' => true,
                'activo' => true,
            ]
        );
    }

    public function test_vista_index_ofrece_formulario_de_apertura_cuando_no_hay_caja_hoy(): void
    {
        $res = $this->actingAs($this->operativo)->get(route('web.caja.index'));

        $res->assertOk();
        $res->assertSee('Apertura de caja');
        $res->assertSee('Saldo inicial');
        $res->assertSee('Abrir');
    }

    public function test_operativo_puede_abrir_caja_con_saldo_inicial(): void
    {
        $res = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'saldo_inicial' => '5000.50',
        ]);

        $caja = CajaOperativa::where('usuario_operativo_id', $this->operativo->id)
            ->where('estado', 'ABIERTA')
            ->first();

        $this->assertNotNull($caja);
        $this->assertEquals(5000.50, (float) $caja->saldo_inicial);
        $res->assertRedirect(route('web.caja.resumen', $caja->id));
    }

    public function test_apertura_de_caja_valida_saldo_inicial_numerico_y_no_negativo(): void
    {
        $res = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'saldo_inicial' => '-500',
        ]);

        $res->assertSessionHasErrors(['saldo_inicial']);
        $this->assertDatabaseMissing('cajas_operativas', [
            'usuario_operativo_id' => $this->operativo->id,
        ]);
    }

    public function test_caja_resumen_y_detalle_muestran_saldo_inicial_y_arqueo_efectivo(): void
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at' => now(),
            'estado' => 'ABIERTA',
            'saldo_inicial' => 4500,
        ]);

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => now()->toDateString(),
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->subrubroIngreso->id,
            'monto' => 15000,
            'usuario_id' => $this->operativo->id,
            'estado' => 'ACTIVO',
        ]);

        $resResumen = $this->actingAs($this->operativo)->get(route('web.caja.resumen', $caja->id));
        $resResumen->assertOk();
        $resResumen->assertSee('4.500'); // Saldo inicial
        $resResumen->assertSee('19.500'); // Total efectivo esperado

        $resDetalle = $this->actingAs($this->operativo)->get(route('web.caja.detalle', $caja->id));
        $resDetalle->assertOk();
        $resDetalle->assertSee('4.500');
    }

    public function test_cierre_de_caja_guarda_arqueo_efectivo_y_calcula_diferencia(): void
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at' => now(),
            'estado' => 'ABIERTA',
            'saldo_inicial' => 5000,
        ]);

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => now()->toDateString(),
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->subrubroIngreso->id,
            'monto' => 10000,
            'usuario_id' => $this->operativo->id,
            'estado' => 'ACTIVO',
        ]);

        // Esperado: 5000 + 10000 = 15000. Operativo cuenta 14500 (faltante de $500)
        $res = $this->actingAs($this->operativo)->post(route('web.caja.cerrar', $caja->id), [
            'saldo_cierre_efectivo' => '14500',
        ]);

        $res->assertRedirect(route('web.caja.index'));

        $caja->refresh();
        $this->assertEquals('CERRADA', $caja->estado);
        $this->assertEquals(14500, (float) $caja->saldo_cierre_efectivo);
        $this->assertEquals(-500, (float) $caja->diferencia_cierre);
    }
}
