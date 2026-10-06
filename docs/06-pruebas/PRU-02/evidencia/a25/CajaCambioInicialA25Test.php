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

/**
 * A25: preparación roja aislada de la suite compartida mientras se implementa.
 * Mover a tests/Feature cuando el recorrido completo esté listo para entregar.
 * Solo ejecutable con DB_DATABASE=wings_testing_codex; nunca con la base del club.
 */
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
        $this->assertSame('wings_testing_codex', config('database.connections.mysql.database'));
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
}
