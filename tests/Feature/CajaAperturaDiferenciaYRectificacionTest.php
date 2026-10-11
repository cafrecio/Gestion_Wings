<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CajaAperturaDiferenciaYRectificacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private Subrubro $subrubroIngreso;
    private CajaService $cajaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-11 09:00:00'));

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->efectivo = TipoCaja::create([
            'nombre' => 'Efectivo Mostrador T23',
            'activo' => true,
        ]);

        $rubro = Rubro::create([
            'nombre' => 'Ingresos Varios T23',
            'tipo' => 'INGRESO',
        ]);

        $this->subrubroIngreso = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cobro Mostrador T23',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'activo' => true,
        ]);

        $this->cajaService = app(CajaService::class);
        $this->cajaService->configurarMostrador($this->efectivo->id, $this->admin->id);
    }

    private function crearCierreConCambio(float $cambioRetenido): CajaOperativa
    {
        $caja = $this->cajaService->abrirCajaOperativa($this->operativo->id, [
            'efectivo_inicial' => $cambioRetenido,
            'confirmacion' => 1,
            'caja_origen_id' => null,
        ], $this->admin->id);

        return $this->cajaService->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => $cambioRetenido,
            'cambio_retenido' => $cambioRetenido,
        ]);
    }

    public function test_aviso_por_diferencia_mayor_al_50_por_ciento_hacia_arriba(): void
    {
        $cierre = $this->crearCierreConCambio(10000.00);

        // Intento de abrir con 16.000 (+60% de diferencia) sin confirmar la diferencia grande
        $response = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'caja_origen_id' => $cierre->id,
            'efectivo_inicial' => '16000.00',
            'motivo_apertura' => 'Conté más billetes',
            'confirmacion' => 1,
        ]);

        $response->assertSessionHasErrors('confirmar_diferencia_grande');
        $this->assertDatabaseMissing('cajas_operativas', [
            'estado' => 'ABIERTA',
        ]);

        // Ahora confirmando la diferencia grande explícitamente
        $responseOk = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'caja_origen_id' => $cierre->id,
            'efectivo_inicial' => '16000.00',
            'motivo_apertura' => 'Conté más billetes',
            'confirmar_diferencia_grande' => 1,
            'confirmacion' => 1,
        ]);

        $responseOk->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cajas_operativas', [
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 16000.00,
            'efectivo_heredado' => 10000.00,
        ]);
    }

    public function test_aviso_por_diferencia_mayor_al_50_por_ciento_hacia_abajo(): void
    {
        $cierre = $this->crearCierreConCambio(10000.00);

        // Intento de abrir con 4.000 (-60% de diferencia) sin confirmar la diferencia grande
        $response = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'caja_origen_id' => $cierre->id,
            'efectivo_inicial' => '4000.00',
            'motivo_apertura' => 'Había menos cambio del esperado',
            'confirmacion' => 1,
        ]);

        $response->assertSessionHasErrors('confirmar_diferencia_grande');
        $this->assertDatabaseMissing('cajas_operativas', [
            'estado' => 'ABIERTA',
        ]);

        // Con confirmación de diferencia grande
        $responseOk = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'caja_origen_id' => $cierre->id,
            'efectivo_inicial' => '4000.00',
            'motivo_apertura' => 'Había menos cambio del esperado',
            'confirmar_diferencia_grande' => 1,
            'confirmacion' => 1,
        ]);

        $responseOk->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cajas_operativas', [
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 4000.00,
        ]);
    }

    public function test_sin_aviso_cuando_la_diferencia_no_supera_el_50_por_ciento(): void
    {
        $cierre = $this->crearCierreConCambio(10000.00);

        // Exactamente 50% hacia arriba: 15.000 (diff = 5.000 == 50% de 10.000)
        // No debe pedir confirmar_diferencia_grande; solo motivo_apertura
        $response = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'caja_origen_id' => $cierre->id,
            'efectivo_inicial' => '15000.00',
            'motivo_apertura' => 'Cambio extra dejado',
            'confirmacion' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cajas_operativas', [
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 15000.00,
        ]);

        // Cerrar y probar 50% hacia abajo: 5.000 (diff = 5.000 == 50% de 10.000)
        $caja = CajaOperativa::where('estado', 'ABIERTA')->first();
        $this->cajaService->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 10000.00,
            'cambio_retenido' => 10000.00,
        ]);

        $cierre2 = CajaOperativa::where('estado', 'CERRADA')->orderByDesc('id')->first();
        $responseAbajo = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'caja_origen_id' => $cierre2->id,
            'efectivo_inicial' => '5000.00',
            'motivo_apertura' => 'Retiro previo justificado',
            'confirmacion' => 1,
        ]);

        $responseAbajo->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cajas_operativas', [
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 5000.00,
        ]);
    }

    public function test_sin_aviso_del_50_por_ciento_en_primera_apertura_o_cuando_cierre_dejo_cero(): void
    {
        // 1. Primera apertura (sin cierre anterior): cualquier monto abre sin aviso del 50%
        $responsePrimera = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'caja_origen_id' => null,
            'efectivo_inicial' => '50000.00',
            'confirmacion' => 1,
        ]);

        $responsePrimera->assertSessionHasNoErrors();
        $caja1 = CajaOperativa::where('estado', 'ABIERTA')->first();
        $this->assertNotNull($caja1);

        // Cerrar dejando $0 en cambio retenido
        $this->cajaService->cerrarCajaOperativa($caja1->id, $this->operativo->id, false, [
            'efectivo_contado' => 50000.00,
            'cambio_retenido' => 0.00,
        ]);

        // 2. Apertura posterior cuando el cierre anterior dejó $0:
        // No aplica el aviso del 50%, solo el motivo habitual por diferir de 0
        $cierreCero = CajaOperativa::where('estado', 'CERRADA')->orderByDesc('id')->first();
        $responseCero = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'caja_origen_id' => $cierreCero->id,
            'efectivo_inicial' => '10000.00',
            'motivo_apertura' => 'Aporte inicial de cambio',
            'confirmacion' => 1,
        ]);

        $responseCero->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cajas_operativas', [
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 10000.00,
            'efectivo_heredado' => 0.00,
        ]);
    }

    public function test_rectificacion_con_caja_abierta_por_operativo_y_por_admin(): void
    {
        $caja = $this->cajaService->abrirCajaOperativa($this->operativo->id, [
            'efectivo_inicial' => 100000.00,
            'confirmacion' => 1,
            'caja_origen_id' => null,
        ], $this->operativo->id);

        // Operativo rectifica su propia caja abierta
        $responseOperativo = $this->actingAs($this->operativo)->post(route('web.caja.rectificar.guardar', $caja->id), [
            'efectivo_inicial' => '10000.00',
            'motivo_rectificacion' => 'Se tipeó un cero de más al abrir',
        ]);

        $responseOperativo->assertRedirect(route('web.caja.resumen', $caja->id));

        $caja->refresh();
        $this->assertEquals('10000.00', $caja->efectivo_inicial);
        $this->assertEquals('100000.00', $caja->efectivo_inicial_anterior);
        $this->assertEquals('Se tipeó un cero de más al abrir', $caja->motivo_rectificacion);
        $this->assertNotNull($caja->rectificado_at);
        $this->assertEquals($this->operativo->id, $caja->usuario_rectificacion_id);

        // Admin también puede rectificar la caja abierta
        $responseAdmin = $this->actingAs($this->admin)->post(route('web.caja.rectificar.guardar', $caja->id), [
            'efectivo_inicial' => '10500.00',
            'motivo_rectificacion' => 'Ajuste de 500 pesos verificado por tesorería',
        ]);

        $responseAdmin->assertRedirect(route('web.caja.resumen', $caja->id));

        $caja->refresh();
        $this->assertEquals('10500.00', $caja->efectivo_inicial);
        $this->assertEquals('10000.00', $caja->efectivo_inicial_anterior);
        $this->assertEquals($this->admin->id, $caja->usuario_rectificacion_id);
    }

    public function test_rectificacion_exige_motivo_obligatorio(): void
    {
        $caja = $this->cajaService->abrirCajaOperativa($this->operativo->id, [
            'efectivo_inicial' => 10000.00,
            'confirmacion' => 1,
            'caja_origen_id' => null,
        ], $this->operativo->id);

        $response = $this->actingAs($this->operativo)->post(route('web.caja.rectificar.guardar', $caja->id), [
            'efectivo_inicial' => '12000.00',
            'motivo_rectificacion' => '',
        ]);

        $response->assertSessionHasErrors('motivo_rectificacion');

        $caja->refresh();
        $this->assertEquals('10000.00', $caja->efectivo_inicial);
        $this->assertNull($caja->rectificado_at);
    }

    public function test_rechazo_de_rectificacion_cuando_caja_esta_cerrada_o_validada(): void
    {
        $caja = $this->cajaService->abrirCajaOperativa($this->operativo->id, [
            'efectivo_inicial' => 10000.00,
            'confirmacion' => 1,
            'caja_origen_id' => null,
        ], $this->operativo->id);

        $this->cajaService->cerrarCajaOperativa($caja->id, $this->operativo->id, false, [
            'efectivo_contado' => 10000.00,
            'cambio_retenido' => 10000.00,
        ]);

        $caja->refresh();
        $this->assertEquals(CajaOperativa::ESTADO_CERRADA, $caja->estado);

        // Intento vía web redirige con error
        $responseWeb = $this->actingAs($this->operativo)->post(route('web.caja.rectificar.guardar', $caja->id), [
            'efectivo_inicial' => '12000.00',
            'motivo_rectificacion' => 'Intento posterior a cierre',
        ]);
        $responseWeb->assertSessionHas('error');

        // Intento directo vía servicio lanza ValidationException
        $this->expectException(ValidationException::class);
        $this->cajaService->rectificarApertura($caja->id, [
            'efectivo_inicial' => 12000.00,
            'motivo_rectificacion' => 'Intento posterior a cierre',
        ], $this->operativo->id);
    }

    public function test_efectivo_esperado_se_recalcula_al_rectificar(): void
    {
        $caja = $this->cajaService->abrirCajaOperativa($this->operativo->id, [
            'efectivo_inicial' => 10000.00,
            'confirmacion' => 1,
            'caja_origen_id' => null,
        ], $this->operativo->id);

        // Registrar un cobro/ingreso de efectivo por 3.000
        $this->cajaService->registrarMovimientoOperativoInterno([
            'usuario_operativo_id' => $this->operativo->id,
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->subrubroIngreso->id,
            'monto' => 3000.00,
        ]);

        // Antes de rectificar: esperado = 10.000 + 3.000 = 13.000
        $arqueoAntes = $this->cajaService->arqueoCaja($caja->id);
        $this->assertEquals('13000.00', $arqueoAntes['efectivo_esperado']);

        // Rectificar apertura: cambiar inicial de 10.000 a 8.000
        $this->actingAs($this->operativo)->post(route('web.caja.rectificar.guardar', $caja->id), [
            'efectivo_inicial' => '8000.00',
            'motivo_rectificacion' => 'Conteo inicial real fue 8000',
        ]);

        $caja->refresh();
        $this->assertEquals('8000.00', $caja->efectivo_inicial);

        // Después de rectificar: esperado recalculado = 8.000 + 3.000 = 11.000
        $this->assertEquals('11000.00', $caja->efectivo_esperado);
        $arqueoDespues = $this->cajaService->arqueoCaja($caja->id);
        $this->assertEquals('11000.00', $arqueoDespues['efectivo_esperado']);
    }
}
