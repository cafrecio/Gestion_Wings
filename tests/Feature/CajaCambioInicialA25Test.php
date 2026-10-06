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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** A25: reglas de apertura, conteo, permisos y conservación de la entrega. */
class CajaCambioInicialA25Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private TipoCaja $transferencia;
    private Subrubro $ingreso;
    private Subrubro $egreso;
    private CajaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(realpath(dirname(__DIR__, 2)), realpath(base_path()));
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::create(['nombre' => 'Billetes A25', 'activo' => true]);
        $this->transferencia = TipoCaja::create(['nombre' => 'Banco A25', 'activo' => true]);
        $rubroIngreso = Rubro::create(['nombre' => 'Ingresos A25', 'tipo' => 'INGRESO']);
        $rubroEgreso = Rubro::create(['nombre' => 'Gastos A25', 'tipo' => 'EGRESO']);
        $this->ingreso = Subrubro::create([
            'rubro_id' => $rubroIngreso->id, 'nombre' => 'Ingreso A25',
            'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true,
        ]);
        $this->egreso = Subrubro::create([
            'rubro_id' => $rubroEgreso->id, 'nombre' => 'Gasto A25',
            'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true,
        ]);
        $this->service = app(CajaService::class);
    }

    private function abrir(float $recibido = 10000, ?User $operativo = null, array $extra = []): CajaOperativa
    {
        $this->service->configurarMostrador($this->efectivo->id, $this->admin->id);
        $propuesta = $this->service->propuestaApertura();
        return $this->service->abrirCajaOperativa(($operativo ?? $this->operativo)->id, [
            'efectivo_inicial' => $recibido,
            'confirmacion' => true,
            'caja_origen_id' => $propuesta['caja_origen_id'],
            ...$extra,
        ]);
    }

    private function movimiento(CajaOperativa $caja, float $monto, Subrubro $subrubro, ?TipoCaja $medio = null, string $estado = 'ACTIVO'): void
    {
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id, 'fecha' => today(),
            'tipo_caja_id' => ($medio ?? $this->efectivo)->id,
            'subrubro_id' => $subrubro->id, 'monto' => $monto,
            'usuario_id' => $caja->usuario_operativo_id, 'estado' => $estado,
        ]);
    }

    public function test_no_se_abre_automaticamente_sin_declarar_el_efectivo(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('abrir');
        $this->service->abrirCajaSiNoExiste($this->operativo->id);
    }

    public function test_primera_apertura_conserva_importe_sin_crear_ingresos(): void
    {
        $caja = $this->abrir();
        $this->assertSame('10000.00', $caja->efectivo_inicial);
        $this->assertSame($this->efectivo->id, $caja->tipo_caja_efectivo_id);
        $this->assertNull($caja->caja_origen_id);
        $this->assertDatabaseCount('movimientos_operativos', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_esperado_excluye_transferencias_cancelados_y_cobros_admin(): void
    {
        $caja = $this->abrir();
        $this->movimiento($caja, 30000, $this->ingreso);
        $this->movimiento($caja, 5000, $this->egreso);
        $this->movimiento($caja, 90000, $this->ingreso, $this->transferencia);
        $this->movimiento($caja, 7000, $this->ingreso, estado: 'CANCELADO');
        CashflowMovimiento::create([
            'fecha' => today(), 'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->ingreso->id, 'monto' => 20000,
            'usuario_admin_id' => $this->admin->id,
        ]);
        $this->assertSame('35000.00', $this->service->arqueoCaja($caja->id)['efectivo_esperado']);
    }

    public function test_cierra_con_faltante_y_separa_el_cambio_del_retiro(): void
    {
        $caja = $this->abrir();
        $this->movimiento($caja, 30000, $this->ingreso);
        $this->movimiento($caja, 5000, $this->egreso);
        $cerrada = $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 34000, 'cambio_retenido' => 10000,
        ]);
        $this->assertSame('CERRADA', $cerrada->estado);
        $this->assertSame('35000.00', $cerrada->efectivo_esperado);
        $this->assertSame('34000.00', $cerrada->efectivo_contado);
        $this->assertSame('-1000.00', $cerrada->diferencia_efectivo);
        $this->assertSame('10000.00', $cerrada->cambio_retenido);
        $this->assertSame('24000.00', $cerrada->efectivo_retirado);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_puede_dejar_mas_cambio_que_en_la_apertura(): void
    {
        $caja = $this->abrir();
        $this->movimiento($caja, 25000, $this->ingreso);
        $cerrada = $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 35000, 'cambio_retenido' => 15000,
        ]);
        $this->assertSame('15000.00', $cerrada->cambio_retenido);
        $this->assertSame('20000.00', $cerrada->efectivo_retirado);
        $this->assertSame('0.00', $cerrada->diferencia_efectivo);
    }

    public function test_siguiente_operativa_hereda_ultimo_cierre_del_club_sin_esperar_validacion(): void
    {
        $caja = $this->abrir();
        $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 10000, 'cambio_retenido' => 10000,
        ]);
        $otra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $segunda = $this->abrir(10000, $otra);
        $this->assertSame($caja->id, $segunda->caja_origen_id);
        $this->assertSame('10000.00', $segunda->efectivo_heredado);
        $this->assertSame('10000.00', $segunda->efectivo_inicial);
    }

    public function test_corregir_lo_heredado_requiere_motivo(): void
    {
        $caja = $this->abrir();
        $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 10000, 'cambio_retenido' => 10000,
        ]);
        $this->expectException(ValidationException::class);
        $this->abrir(8000);
    }

    public function test_corregir_lo_heredado_con_motivo_conserva_ambos_importes(): void
    {
        $caja = $this->abrir();
        $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 10000, 'cambio_retenido' => 10000,
        ]);
        $segunda = $this->abrir(8000, extra: ['motivo_apertura' => 'Recibí menos efectivo']);
        $this->assertSame('10000.00', $segunda->efectivo_heredado);
        $this->assertSame('8000.00', $segunda->efectivo_inicial);
        $this->assertSame('Recibí menos efectivo', $segunda->motivo_apertura);
    }

    public function test_otro_operativo_no_puede_abrir_un_turno_simultaneo(): void
    {
        $this->abrir();
        $otra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->expectException(ValidationException::class);
        $this->abrir(10000, $otra);
    }

    public function test_admin_no_valida_una_caja_abierta_sin_conteo(): void
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at' => now(), 'estado' => 'ABIERTA',
        ]);
        $this->expectException(\Exception::class);
        $this->service->validarCaja($caja->id, $this->admin->id);
    }

    public function test_cerrar_sin_conteo_no_cambia_el_estado(): void
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at' => now(), 'estado' => 'ABIERTA',
        ]);
        $this->expectException(ValidationException::class);
        $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id);
    }

    public function test_no_puede_dejar_mas_cambio_que_efectivo_contado(): void
    {
        $caja = $this->abrir();
        $this->expectException(ValidationException::class);
        $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 8000, 'cambio_retenido' => 10000,
        ]);
    }

    public function test_corregir_rechazada_conserva_la_entrega_y_el_turno_siguiente(): void
    {
        $lunes = $this->abrir();
        $this->movimiento($lunes, 25000, $this->ingreso);
        $this->service->cerrarCajaOperativa($lunes->id, $this->operativo->id, false, [
            'efectivo_contado' => 35000, 'cambio_retenido' => 10000,
        ]);
        $cierreOriginal = $lunes->fresh()->cierre_at->toISOString();
        $martes = $this->abrir();
        $this->service->rechazarCaja($lunes->id, $this->admin->id, 'Corregir importe del movimiento');
        $movimiento = $lunes->movimientos()->firstOrFail();
        $this->service->actualizarMovimientoEnCaja($lunes->id, $movimiento->id, [
            'tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => $this->ingreso->id,
            'monto' => 20000, 'fecha' => today()->toDateString(), 'observaciones' => 'Importe corregido',
        ]);
        $this->assertSame('5000.00', $this->service->arqueoCaja($lunes->id)['diferencia_efectivo']);
        $this->actingAs($this->admin)->get(route('web.caja.resumen', $lunes->id))
            ->assertOk()->assertViewHas('efectivoEsperado', '30000.00')
            ->assertViewHas('diferenciaEfectivo', '5000.00');
        $corregida = $this->service->cerrarCajaOperativa($lunes->id, $this->operativo->id, false, [
            'efectivo_contado' => 35000, 'cambio_retenido' => 10000,
        ]);
        $this->assertSame('35000.00', $corregida->efectivo_contado);
        $this->assertSame('10000.00', $corregida->cambio_retenido);
        $this->assertSame('25000.00', $corregida->efectivo_retirado);
        $this->assertSame('30000.00', $corregida->efectivo_esperado);
        $this->assertSame('5000.00', $corregida->diferencia_efectivo);
        $this->assertSame($cierreOriginal, $corregida->cierre_at->toISOString());
        $this->assertSame('10000.00', $martes->fresh()->efectivo_heredado);
        $this->assertSame('10000.00', $martes->fresh()->efectivo_inicial);
    }

    public function test_rechazada_no_permite_reescribir_el_conteo_fisico(): void
    {
        $caja = $this->abrir();
        $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 10000, 'cambio_retenido' => 10000,
        ]);
        $this->service->rechazarCaja($caja->id, $this->admin->id, 'Revisar');
        $this->expectException(ValidationException::class);
        $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 8000, 'cambio_retenido' => 8000,
        ]);
    }

    public function test_rechazar_no_cierra_una_caja_sin_conteo(): void
    {
        $caja = $this->abrir();
        $this->expectException(\Exception::class);
        $this->service->rechazarCaja($caja->id, $this->admin->id, 'Revisar');
    }

    public function test_admin_abre_para_la_operativa_sin_crearse_caja_propia(): void
    {
        $this->service->configurarMostrador($this->efectivo->id, $this->admin->id);
        $this->actingAs($this->admin)->post(route('web.caja.abrir'), [
            'operativo_id' => $this->operativo->id, 'efectivo_inicial' => '10.000', 'confirmacion' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $caja = CajaOperativa::sole();
        $this->assertSame($this->operativo->id, $caja->usuario_operativo_id);
        $this->assertSame($this->admin->id, $caja->usuario_apertura_id);
        $this->assertSame('10000.00', $caja->efectivo_inicial);
    }

    public function test_formulario_no_permite_inicial_negativo_ni_sin_confirmacion(): void
    {
        $this->service->configurarMostrador($this->efectivo->id, $this->admin->id);
        $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'efectivo_inicial' => -1, 'confirmacion' => 1,
        ])->assertSessionHasErrors('efectivo_inicial');
        $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'efectivo_inicial' => 10000,
        ])->assertSessionHasErrors('confirmacion');
        $this->assertDatabaseCount('cajas_operativas', 0);
    }

    public function test_cierre_web_normaliza_miles_y_admin_puede_contar_turno_ajeno(): void
    {
        $caja = $this->abrir();
        $this->actingAs($this->admin)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '34.000', 'cambio_retenido' => '10.000',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('34000.00', $caja->fresh()->efectivo_contado);
        $this->assertSame('24000.00', $caja->fresh()->efectivo_retirado);
        $this->assertSame($this->admin->id, $caja->fresh()->usuario_cierre_id);
    }

    public function test_operativo_no_configura_y_profesor_no_accede_al_arqueo(): void
    {
        $this->actingAs($this->operativo)->get(route('web.caja.configuracion'))->assertForbidden();
        $this->actingAs($this->operativo)->post(route('web.caja.configuracion.store'), [
            'tipo_caja_id' => $this->efectivo->id,
        ])->assertForbidden();
        $profesor = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);
        $this->actingAs($profesor)->get(route('web.caja.apertura'))->assertForbidden();
        $this->actingAs($profesor)->post(route('web.caja.abrir'), ['efectivo_inicial' => 0, 'confirmacion' => 1])->assertForbidden();
    }

    public function test_segundo_operativo_no_cierra_turno_ajeno_por_url(): void
    {
        $caja = $this->abrir();
        $otra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->actingAs($otra)->get(route('web.caja.cierre', $caja->id))->assertForbidden();
        $this->actingAs($otra)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => 10000, 'cambio_retenido' => 10000,
        ])->assertForbidden();
        $this->assertSame('ABIERTA', $caja->fresh()->estado);
    }

    public function test_pantalla_vieja_no_abre_con_cierre_origen_desactualizado(): void
    {
        $caja = $this->abrir();
        $this->service->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 10000, 'cambio_retenido' => 10000,
        ]);
        $this->expectException(ValidationException::class);
        $this->service->abrirCajaOperativa($this->operativo->id, [
            'efectivo_inicial' => 10000, 'confirmacion' => true, 'caja_origen_id' => null,
        ]);
    }

    public function test_caja_historica_no_inventa_inicial_ni_diferencia(): void
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id, 'apertura_at' => now(), 'estado' => 'CERRADA',
        ]);
        $cerrada = $this->service->cerrarCajaOperativa($caja->id, $this->admin->id, true, [
            'efectivo_contado' => 10000, 'cambio_retenido' => 10000,
        ]);
        $this->assertNull($cerrada->efectivo_inicial);
        $this->assertNull($cerrada->efectivo_esperado);
        $this->assertNull($cerrada->diferencia_efectivo);
        $this->assertSame('10000.00', $cerrada->cambio_retenido);
    }
}
