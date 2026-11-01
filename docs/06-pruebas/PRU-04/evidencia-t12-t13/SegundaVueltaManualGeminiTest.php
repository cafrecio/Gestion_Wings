<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\CobroCuota;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\CashflowIntegracionCajaService;
use App\Services\ReporteMensualService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * Segunda vuelta de control de manual T12 y T13 — Gemini CAB, 01/11/2026.
 * Vive en evidencia a propósito para no afectar tests/ ni DocumentacionNoMienteTest.
 */
class SegundaVueltaManualGeminiTest extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $operativa;
    private TipoCaja $efectivo;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);

        $this->duenio = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => '10000.00']);
    }

    private function saldoEfectivo(): float
    {
        $saldos = app(CashflowIntegracionCajaService::class)->saldoAcumuladoHastaFecha(now()->toDateString());
        return (float) collect($saldos['por_tipo_caja'])->firstWhere('tipo_caja_id', $this->efectivo->id)['saldo'];
    }

    private function esperado(): float
    {
        return (float) app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado'];
    }

    /**
     * 1. Un retiro no entra al turno, ni por el dueño ni por la operativa.
     */
    public function test_un_retiro_no_entra_al_turno_ni_por_duenio_ni_por_operativa(): void
    {
        $subrubroRetiro = Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail();

        // Intento por el dueño con "Agregar" en la tarjeta del turno
        $resDuenio = $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubroRetiro->id,
            'monto' => '2500.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Retiro dueño con Agregar',
        ]);
        $resDuenio->assertSessionHas('error', 'El subrubro seleccionado no está permitido para usuarios operativos.');

        // Intento por la operativa desde "Nuevo movimiento" (/caja/movimiento)
        $resOperativa = $this->actingAs($this->operativa)->post(route('web.caja.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubroRetiro->id,
            'monto' => '2500.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Retiro intentado por operativa',
        ]);
        $resOperativa->assertSessionHas('error', 'El subrubro seleccionado no está permitido para usuarios operativos.');

        // Ningún movimiento se registró en la caja
        $this->assertSame(0, MovimientoOperativo::where('caja_operativa_id', $this->caja->id)->count());
        $this->assertEquals(10000.0, $this->esperado());
    }

    /**
     * 2 y 3. Por Cashflow baja Efectivo una sola vez; si sale en turno hay faltante,
     * si sale después del cierre no hay faltante. Validar no vuelve a descontar.
     */
    public function test_retiro_en_cashflow_baja_efectivo_una_vez_y_validar_no_duplica_descuento(): void
    {
        $this->efectivo->update(['saldo_inicial' => 15000]);
        $saldoInicial = $this->saldoEfectivo();

        // Dueño retira $3000 por Cashflow
        $subrubroRetiro = Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail();
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubroRetiro->id,
            'monto' => '3000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Retiro del cajón en turno',
        ])->assertSessionHas('success');

        // Efectivo en Cashflow bajó exactamente $3000
        $this->assertEquals($saldoInicial - 3000, $this->saldoEfectivo());
        // El esperado del turno sigue siendo $10000 (no se entera del retiro externo)
        $this->assertEquals(10000, $this->esperado());

        // Operativa cuenta el cajón real ($7000): cierra con faltante de -$3000
        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $this->caja->id), [
            'efectivo_contado' => '7000.00',
            'cambio_retenido' => '7000.00',
        ])->assertSessionHas('success', 'Caja cerrada.');
        $this->assertEquals(-3000, (float) $this->caja->fresh()->diferencia_efectivo);

        // Dueño valida la caja con el faltante
        $this->actingAs($this->duenio)->post(route('web.cajas.validar', $this->caja->id))
            ->assertSessionHas('success');
        $this->assertSame('VALIDADA', $this->caja->fresh()->estado);

        // El saldo en Efectivo NO vuelve a bajar
        $this->assertEquals($saldoInicial - 3000, $this->saldoEfectivo());

        // Reporte mensual computa retiro pero no altera el resultado operativo
        $reporte = app(ReporteMensualService::class)->obtener(now()->format('Y-m'));
        $this->assertEquals(0, $reporte['resultado']);
        $this->assertEquals(300000, $reporte['retiros']); // En centavos ($3000)
    }

    /**
     * 4. Efectivo puede quedar en negativo hasta que se valida, y el saldo se acomoda al validar.
     */
    public function test_efectivo_queda_en_negativo_y_se_acomoda_al_validar_cobros_del_turno(): void
    {
        // Cuenta Efectivo arranca en $0
        $this->efectivo->update(['saldo_inicial' => 0, 'permite_descubierto' => false]);
        $this->assertEquals(0, $this->saldoEfectivo());

        // Retiro del dueño de $4000 en Cashflow deja Efectivo en -$4000
        $subrubroRetiro = Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail();
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubroRetiro->id,
            'monto' => '4000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Retiro previo a validación',
        ])->assertSessionHas('success');
        $this->assertEquals(-4000, $this->saldoEfectivo());

        // En el turno de caja hubo un cobro en efectivo de $10000
        $subCobro = Subrubro::where('nombre', 'Cuota Mensual')->firstOrFail();
        $movCobro = MovimientoOperativo::create([
            'caja_operativa_id' => $this->caja->id,
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subCobro->id,
            'usuario_id' => $this->operativa->id,
            'monto' => '10000.00',
            'fecha' => now()->toDateString(),
            'reporte_tipo' => 'INGRESO',
            'reporte_clasificacion' => 'OPERATIVO',
            'afecta_caja' => true,
            'observaciones' => 'Cobro cuota en efectivo',
        ]);

        // Cierre de caja: inicial 10000 + cobro 10000 - retiro 4000 = 16000 contado
        // Esperado del turno es 10000 inicial + 10000 cobro = 20000. Faltante: -$4000.
        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $this->caja->id), [
            'efectivo_contado' => '16000.00',
            'cambio_retenido' => '10000.00',
        ])->assertSessionHas('success');

        // Dueño verifica el movimiento de cobro y valida la caja
        $movCobro = $movCobro->fresh();
        app(CajaService::class)->verificarMovimiento(
            $this->caja->id,
            $movCobro->id,
            $this->duenio->id,
            true,
            $movCobro->huellaVerificacion()
        );

        $this->actingAs($this->duenio)->post(route('web.cajas.validar', $this->caja->id))
            ->assertSessionHas('success');

        // Al validar, los cobros del turno entran a Cashflow (+10000)
        // Saldo final Efectivo: -4000 + 10000 = +6000. ¡El saldo se acomoda al validar!
        $this->assertEquals(6000, $this->saldoEfectivo());
    }

    /**
     * 5. Un aporte entra con Agregar solo si su subrubro está en Ambos y en Afecta caja.
     */
    public function test_aporte_entra_con_agregar_solo_si_esta_en_ambos_y_afecta_caja(): void
    {
        $rubroAportes = Rubro::create(['nombre' => 'Aportes Socios', 'tipo' => 'INGRESO']);

        $aporteSoloAdmin = Subrubro::create([
            'rubro_id' => $rubroAportes->id,
            'nombre' => 'Aporte Personal Solo Admin',
            'permitido_para' => 'ADMIN',
            'afecta_caja' => true,
            'clasificacion_resultado' => 'APORTE',
            'activo' => true,
        ]);

        $aporteAmbosSinAfecta = Subrubro::create([
            'rubro_id' => $rubroAportes->id,
            'nombre' => 'Aporte Ambos Sin Afecta',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => false,
            'clasificacion_resultado' => 'APORTE',
            'activo' => true,
        ]);

        $aporteAmbosAfecta = Subrubro::create([
            'rubro_id' => $rubroAportes->id,
            'nombre' => 'Aporte Ambos Afecta Caja',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'clasificacion_resultado' => 'APORTE',
            'activo' => true,
        ]);

        // 1. Aporte Solo Admin: rechazado por validarSubrubroManual
        $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $aporteSoloAdmin->id,
            'monto' => '5000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Intento aporte solo admin',
        ])->assertSessionHas('error', 'El subrubro seleccionado no está permitido para usuarios operativos.');

        // 2. Aporte Ambos sin afecta_caja: rechazado
        $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $aporteAmbosSinAfecta->id,
            'monto' => '5000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Intento aporte sin afecta caja',
        ])->assertSessionHas('error', 'El subrubro seleccionado no afecta la caja operativa.');

        // 3. Aporte Ambos con afecta_caja: ingresa con éxito
        $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $aporteAmbosAfecta->id,
            'monto' => '5000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Aporte capital en efectivo',
        ])->assertSessionHas('success', 'Movimiento registrado.');

        // Esperado subió de $10000 a $15000
        $this->assertEquals(15000, $this->esperado());

        // El movimiento queda registrado a nombre del dueño
        $mov = MovimientoOperativo::where('observaciones', 'Aporte capital en efectivo')->sole();
        $this->assertSame($this->duenio->id, $mov->usuario_id);
    }

    /**
     * 6. Agregar le ofrece al dueño subrubros que después rechaza, con el texto exacto.
     */
    public function test_agregar_ofrece_subrubros_al_duenio_que_luego_rechaza_con_mensaje_exacto(): void
    {
        // En la pantalla Agregar del turno (/caja/{id}/editar)
        $subrubrosOfrecidos = $this->actingAs($this->duenio)->get(route('web.caja.editar', $this->caja->id))
            ->assertOk()
            ->viewData('rubros')
            ->flatMap->subrubros
            ->pluck('nombre')
            ->all();

        // Ofrece 'Retiro de dueños'
        $this->assertContains('Retiro de dueños', $subrubrosOfrecidos);

        // Al intentar guardarlo, rechaza con el texto exacto reportado en el manual:
        $subrubroRetiro = Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail();
        $res = $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubroRetiro->id,
            'monto' => '1000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Prueba texto exacto',
        ]);

        $res->assertSessionHas('error', 'El subrubro seleccionado no está permitido para usuarios operativos.');
    }
}
