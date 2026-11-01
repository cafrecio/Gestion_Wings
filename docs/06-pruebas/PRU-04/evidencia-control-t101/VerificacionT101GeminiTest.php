<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\MovimientoOperativo;
use App\Models\MovimientoOperativoCorreccion;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\CashflowIntegracionCajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

class VerificacionT101GeminiTest extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $operativa;
    private User $otraOperativa;
    private User $profesora;
    private TipoCaja $efectivo;
    private Subrubro $gastoOperativo;
    private Subrubro $gastoAdmin;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duenio = User::factory()->create([
            'name' => 'Carlos Dueño',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->operativa = User::factory()->create([
            'name' => 'Sandra Operativa',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->otraOperativa = User::factory()->create([
            'name' => 'Marta OtraOperativa',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->profesora = User::factory()->create([
            'name' => 'Lucía Profesora',
            'rol' => User::ROL_PROFESOR,
            'activo' => true,
        ]);

        $this->efectivo = TipoCaja::create([
            'nombre' => 'Efectivo',
            'activo' => true,
        ]);

        $rubro = Rubro::create([
            'nombre' => 'Gastos Generales T101',
            'tipo' => 'EGRESO',
        ]);

        $this->gastoOperativo = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Insumos T101',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'clasificacion_resultado' => 'NEGOCIO',
            'activo' => true,
        ]);

        $this->gastoAdmin = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Mantenimiento Admin T101',
            'permitido_para' => 'ADMIN',
            'afecta_caja' => true,
            'clasificacion_resultado' => 'NEGOCIO',
            'activo' => true,
        ]);

        $this->caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => '10000.00']);
    }

    /**
     * Caso 1: Dueño agrega un gasto en el turno de la operativa.
     * Resultado: Figura a su nombre en Movimientos, también para la operativa; el efectivo esperado baja por el importe.
     */
    public function test_caso_1_duenio_agrega_gasto_en_turno_de_operativa(): void
    {
        $arqueoAntes = app(CajaService::class)->arqueoCaja($this->caja->id);
        $esperadoAntes = (float) $arqueoAntes['efectivo_esperado'];

        $response = $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '3500.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Gasto de Carlos en turno de Sandra',
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('web.caja.resumen', $this->caja->id));

        $movimiento = MovimientoOperativo::where('observaciones', 'Gasto de Carlos en turno de Sandra')->sole();
        $this->assertSame($this->duenio->id, $movimiento->usuario_id);
        $this->assertSame($this->caja->id, $movimiento->caja_operativa_id);

        // Pantalla de Movimientos vista por la operativa
        $resOperativa = $this->actingAs($this->operativa)->get(route('web.movimientos.index'));
        $resOperativa->assertOk();
        $resOperativa->assertSee('Gasto de Carlos en turno de Sandra');
        $resOperativa->assertSee('Carlos Dueño');

        // Pantalla de Movimientos vista por el dueño
        $resDuenio = $this->actingAs($this->duenio)->get(route('web.movimientos.index'));
        $resDuenio->assertOk();
        $resDuenio->assertSee('Gasto de Carlos en turno de Sandra');
        $resDuenio->assertSee('Carlos Dueño');

        // Efectivo esperado de la caja baja por el importe
        $arqueoDespues = app(CajaService::class)->arqueoCaja($this->caja->id);
        $esperadoDespues = (float) $arqueoDespues['efectivo_esperado'];
        $this->assertSame($esperadoAntes - 3500.00, $esperadoDespues);
    }

    /**
     * Caso 2: Operativa agrega en su propio turno, por Agregar y por movimiento de caja.
     * Resultado: Sigue a su nombre.
     */
    public function test_caso_2_operativa_agrega_en_su_propio_turno(): void
    {
        // Vía Agregar (editarStore)
        $this->actingAs($this->operativa)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '1200.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Lapiceras Sandra',
        ])->assertSessionHasNoErrors();

        $mov1 = MovimientoOperativo::where('observaciones', 'Lapiceras Sandra')->sole();
        $this->assertSame($this->operativa->id, $mov1->usuario_id);

        // Vía Movimiento de caja (/caja/movimiento)
        $this->actingAs($this->operativa)->post('/caja/movimiento', [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '800.00',
            'observaciones' => 'Café Sandra',
        ])->assertSessionHas('success');

        $mov2 = MovimientoOperativo::where('observaciones', 'Café Sandra')->sole();
        $this->assertSame($this->operativa->id, $mov2->usuario_id);

        // Comprobar en pantalla de movimientos
        $res = $this->actingAs($this->operativa)->get(route('web.movimientos.index'));
        $res->assertOk();
        $res->assertSee('Lapiceras Sandra');
        $res->assertSee('Café Sandra');
        $res->assertSee('Sandra Operativa');
    }

    /**
     * Caso 3: Otra operativa o la profesora, y un pedido directo con otro usuario_id.
     * Resultado: Rechazado, o el autor no cambia.
     */
    public function test_caso_3_otra_operativa_o_profesora_y_spoofing_de_usuario_id(): void
    {
        // Otra operativa no puede agregar en el turno de Sandra
        $this->actingAs($this->otraOperativa)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '500.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Intento otra operativa',
        ])->assertForbidden();

        // Profesora no puede agregar en la caja
        $this->actingAs($this->profesora)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '500.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Intento profesora',
        ])->assertForbidden();

        // Pedido directo de la operativa intentando enviar usuario_id ajeno
        $this->actingAs($this->operativa)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '250.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Spoofing operativa',
            'usuario_id' => $this->duenio->id,
        ])->assertSessionHasNoErrors();

        $movSpoof = MovimientoOperativo::where('observaciones', 'Spoofing operativa')->sole();
        $this->assertSame($this->operativa->id, $movSpoof->usuario_id, 'El autor debe ser la operativa autenticada y no el usuario_id del request');

        // Pedido directo del dueño intentando enviar usuario_id de la operativa
        $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '300.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Spoofing duenio',
            'usuario_id' => $this->operativa->id,
        ])->assertSessionHasNoErrors();

        $movSpoofDuenio = MovimientoOperativo::where('observaciones', 'Spoofing duenio')->sole();
        $this->assertSame($this->duenio->id, $movSpoofDuenio->usuario_id, 'El autor debe ser el dueño autenticado');
    }

    /**
     * Caso 4: Validar esa caja.
     * Resultado: En Cashflow, «Registró» dice quién lo cargó (CashflowIntegracionCajaService:81) y el saldo da al peso.
     */
    public function test_caso_4_validar_caja_y_reflejar_en_cashflow_con_autor_y_saldo(): void
    {
        // 1. Dueño agrega gasto de $3.000
        $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '3000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Gasto Duenio para Cashflow',
        ]);

        // 2. Operativa agrega gasto de $1.000
        $this->actingAs($this->operativa)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '1000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Gasto Operativa para Cashflow',
        ]);

        $movDuenio = MovimientoOperativo::where('observaciones', 'Gasto Duenio para Cashflow')->sole();
        $movOperativa = MovimientoOperativo::where('observaciones', 'Gasto Operativa para Cashflow')->sole();

        // 3. Cierre y validación de la caja
        // Inicial: 10.000. Egresos: 3.000 + 1.000 = 4.000. Esperado: 6.000.
        $this->caja->update([
            'efectivo_declarado' => '6000.00',
            'estado' => 'VALIDADA',
            'usuario_admin_validacion_id' => $this->duenio->id,
            'validacion_at' => now(),
        ]);

        // Reflejar en Cashflow
        app(CashflowIntegracionCajaService::class)->reflejarCajaEnCashflow($this->caja->id, $this->duenio->id);

        // Verificar asientos en cashflow_movimientos
        $asientoDuenio = CashflowMovimiento::where('movimiento_operativo_id', $movDuenio->id)->sole();
        $this->assertSame($this->duenio->id, $asientoDuenio->usuario_admin_id);
        $this->assertSame('-3000.00', (string) $asientoDuenio->monto);

        $asientoOperativa = CashflowMovimiento::where('movimiento_operativo_id', $movOperativa->id)->sole();
        $this->assertSame($this->operativa->id, $asientoOperativa->usuario_admin_id);
        $this->assertSame('-1000.00', (string) $asientoOperativa->monto);

        // Verificar vista de Cashflow
        $res = $this->actingAs($this->duenio)->get(route('web.cashflow.index'));
        $res->assertOk();
        $res->assertSee('Gasto Duenio para Cashflow');
        $res->assertSee('Carlos Dueño');
        $res->assertSee('Gasto Operativa para Cashflow');
        $res->assertSee('Sandra Operativa');

        // Saldo da al peso: suma de movimientos de efectivo es -4.000,00
        $saldoAcum = app(CashflowIntegracionCajaService::class)->saldoAcumuladoHastaFecha(today()->toDateString());
        $saldoEfectivo = collect($saldoAcum['por_tipo_caja'])->firstWhere('tipo_caja_id', $this->efectivo->id)['saldo'];
        $this->assertEquals(-4000.00, $saldoEfectivo);
    }

    /**
     * Caso 5: Editar, cancelar o corregir ese gasto en la caja; recibo y resumen de cierre.
     * Resultado: Funciona igual; decí qué pasa con el autor.
     */
    public function test_caso_5_editar_corregir_eliminar_y_resumen(): void
    {
        // 1. Dueño agrega gasto de $2.000
        $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '2000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Gasto original Carlos',
        ]);
        $mov = MovimientoOperativo::where('observaciones', 'Gasto original Carlos')->sole();
        $this->assertSame($this->duenio->id, $mov->usuario_id);

        // 2. Dueño edita el gasto (cambia monto a 2.500 y obs a "Gasto editado Carlos")
        $this->actingAs($this->duenio)->put(route('web.caja.movimientos.update', [$this->caja->id, $mov->id]), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '2500.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Gasto editado Carlos',
        ])->assertRedirect(route('web.caja.detalle', $this->caja->id));

        $mov->refresh();
        $this->assertSame('2500.00', (string) $mov->monto);
        // El autor original sigue siendo el dueño
        $this->assertSame($this->duenio->id, $mov->usuario_id);

        // La corrección registra quién editó
        $corr = MovimientoOperativoCorreccion::where('movimiento_id', $mov->id)->sole();
        $this->assertSame('EDITAR', $corr->accion);
        $this->assertSame($this->duenio->id, $corr->usuario_id);
        $this->assertSame('Carlos Dueño', $corr->autor);

        // 3. Operativa edita el gasto de Carlos en su caja (cambia monto a 2.300 y obs a "Gasto ajustado por Sandra")
        $this->actingAs($this->operativa)->put(route('web.caja.movimientos.update', [$this->caja->id, $mov->id]), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoOperativo->id,
            'monto' => '2300.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Gasto ajustado por Sandra',
        ])->assertRedirect(route('web.caja.detalle', $this->caja->id));

        $mov->refresh();
        $this->assertSame('2300.00', (string) $mov->monto);
        // El autor original NO cambia (sigue siendo Carlos)
        $this->assertSame($this->duenio->id, $mov->usuario_id);

        // Segunda corrección registra que Sandra fue quien editó
        $corr2 = MovimientoOperativoCorreccion::where('movimiento_id', $mov->id)->latest('id')->first();
        $this->assertSame('EDITAR', $corr2->accion);
        $this->assertSame($this->operativa->id, $corr2->usuario_id);
        $this->assertSame('Sandra Operativa', $corr2->autor);

        // 4. Cancelación no permitida en gastos manuales (formulario da 403, POST da error)
        $this->actingAs($this->duenio)->get(route('web.caja.movimientos.cancelar', [$this->caja->id, $mov->id]))
            ->assertForbidden();

        $this->actingAs($this->duenio)->from(route('web.caja.detalle', $this->caja->id))
            ->post(route('web.caja.movimientos.cancelar.store', [$this->caja->id, $mov->id]), [
                'motivo' => 'Intento cancelar gasto',
            ])->assertRedirect(route('web.caja.detalle', $this->caja->id))
            ->assertSessionHas('error', 'Este cobro no tiene registro de pago vinculado. No se puede cancelar automáticamente.');

        // 5. Resumen de caja (web.caja.resumen)
        $resumen = $this->actingAs($this->duenio)->get(route('web.caja.resumen', $this->caja->id));
        $resumen->assertOk();
        $resumen->assertSee('2,300.00');

        // 6. Eliminar movimiento manual
        $this->actingAs($this->duenio)->delete(route('web.caja.movimientos.destroy', [$this->caja->id, $mov->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('movimientos_operativos', ['id' => $mov->id]);
        $corr3 = MovimientoOperativoCorreccion::where('movimiento_id', $mov->id)->latest('id')->first();
        $this->assertSame('ELIMINAR', $corr3->accion);
        $this->assertSame('Carlos Dueño', $corr3->autor);
    }
}
