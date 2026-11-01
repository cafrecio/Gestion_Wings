<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
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
 * Hallazgo de Gemini del 01/11/2026: qué puede hacer hoy el dueño con la plata del cajón.
 * Vive en evidencia a propósito: retrata lo que Wings hace hoy, no lo que debería hacer.
 * Uso: DB_DATABASE=wings_testing_claude php artisan test docs/06-pruebas/PRU-04/evidencia-t12-t13/RetiroDuenoCajonRealidadTest.php
 */
class RetiroDuenoCajonRealidadTest extends TestCase
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

    private function agregar(string $subrubro, string $observaciones)
    {
        return $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => Subrubro::where('nombre', $subrubro)->firstOrFail()->id,
            'monto' => '2000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => $observaciones,
        ]);
    }

    private function esperado(): float
    {
        return (float) app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado'];
    }

    private function saldoEfectivo(): float
    {
        $saldos = app(CashflowIntegracionCajaService::class)->saldoAcumuladoHastaFecha(now()->toDateString());

        return collect($saldos['por_tipo_caja'])->firstWhere('tipo_caja_id', $this->efectivo->id)['saldo'];
    }

    public function test_la_tarjeta_del_turno_le_ofrece_al_duenio_lo_que_despues_rechaza(): void
    {
        $ofrecidos = $this->actingAs($this->duenio)->get(route('web.caja.editar', $this->caja->id))
            ->assertOk()->viewData('rubros')->flatMap->subrubros->pluck('nombre')->all();

        foreach (['Retiro de dueños', 'San Carlos', 'Luz'] as $nombre) {
            $this->assertContains($nombre, $ofrecidos);
            $this->agregar($nombre, "Intento $nombre")
                ->assertSessionHas('error', 'El subrubro seleccionado no está permitido para usuarios operativos.');
        }

        $this->assertSame(0, MovimientoOperativo::where('caja_operativa_id', $this->caja->id)->count());
        $this->assertEquals(10000, $this->esperado());
    }

    public function test_un_aporte_solo_entra_al_turno_si_el_subrubro_es_de_la_operativa(): void
    {
        $rubro = Rubro::create(['nombre' => 'Aportes prueba', 'tipo' => 'INGRESO']);
        foreach (['ADMIN' => 'Aporte solo dueño', 'OPERATIVO' => 'Aporte de ambos'] as $permitido => $nombre) {
            Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => $nombre, 'permitido_para' => $permitido,
                'afecta_caja' => true, 'clasificacion_resultado' => 'APORTE', 'activo' => true]);
        }

        $this->agregar('Aporte solo dueño', 'No entra')
            ->assertSessionHas('error', 'El subrubro seleccionado no está permitido para usuarios operativos.');
        $this->assertEquals(10000, $this->esperado());

        $this->agregar('Aporte de ambos', 'Entra')->assertSessionHas('success', 'Movimiento registrado.');
        $this->assertEquals(12000, $this->esperado());
    }

    public function test_el_retiro_por_cashflow_baja_la_cuenta_y_el_turno_cierra_con_faltante(): void
    {
        $this->efectivo->update(['saldo_inicial' => 10000]);
        $antes = $this->saldoEfectivo();

        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail()->id,
            'monto' => '2000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Se llevó del cajón',
        ])->assertSessionHas('success');

        $this->assertEquals($antes - 2000, $this->saldoEfectivo());
        $this->assertEquals(10000, $this->esperado(), 'El esperado del turno no se entera');

        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $this->caja->id), [
            'efectivo_contado' => '8000.00',
            'cambio_retenido' => '8000.00',
        ])->assertSessionHas('success', 'Caja cerrada.');
        $this->assertEquals(-2000, (float) $this->caja->fresh()->diferencia_efectivo);

        $this->actingAs($this->duenio)->post(route('web.cajas.validar', $this->caja->id))
            ->assertSessionHas('success');

        $this->assertSame('VALIDADA', $this->caja->fresh()->estado);
        $this->assertEquals($antes - 2000, $this->saldoEfectivo(), 'El faltante no vuelve a bajar la cuenta');

        $reporte = app(ReporteMensualService::class)->obtener(now()->format('Y-m'));
        $this->assertEquals(0, $reporte['resultado']);
        $this->assertEquals(200000, $reporte['retiros']);
    }

    /** Alternativa sin código para que decida Carlos: el subrubro en «Ambos». No es lo que trae Wings de fábrica. */
    public function test_con_retiro_en_ambos_entra_al_turno_y_la_operativa_lo_ve(): void
    {
        Subrubro::where('nombre', 'Retiro de dueños')->update(['permitido_para' => 'OPERATIVO']);

        $this->agregar('Retiro de dueños', 'Se llevó del cajón')->assertSessionHas('success', 'Movimiento registrado.');
        $this->assertEquals(8000, $this->esperado());
        $this->assertSame($this->duenio->id, MovimientoOperativo::where('observaciones', 'Se llevó del cajón')->sole()->usuario_id);

        $this->actingAs($this->operativa)->get(route('web.movimientos.index'))->assertOk()->assertSee('Se llevó del cajón');
        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $this->caja->id), [
            'efectivo_contado' => '8000.00',
            'cambio_retenido' => '8000.00',
        ])->assertSessionHas('success', 'Caja cerrada.');
        $this->assertEquals(0, (float) $this->caja->fresh()->diferencia_efectivo);

        $this->efectivo->update(['saldo_inicial' => 10000]);
        $movimiento = MovimientoOperativo::where('observaciones', 'Se llevó del cajón')->sole();
        app(CajaService::class)->verificarMovimiento($this->caja->id, $movimiento->id, $this->duenio->id, true, $movimiento->huellaVerificacion());
        $this->actingAs($this->duenio)->post(route('web.cajas.validar', $this->caja->id))->assertSessionHas('success');

        $this->assertEquals(8000, $this->saldoEfectivo());
        $reporte = app(ReporteMensualService::class)->obtener(now()->format('Y-m'));
        $this->assertEquals(0, $reporte['resultado']);
        $this->assertEquals(200000, $reporte['retiros']);

        // También la operativa puede cargarlo: ese es el costo de esta alternativa.
        $this->assertContains('Retiro de dueños', $this->actingAs($this->operativa)->get(route('web.caja.editar', $this->crearOtraCaja()->id))
            ->assertOk()->viewData('rubros')->flatMap->subrubros->pluck('nombre')->all());
    }

    private function crearOtraCaja(): CajaOperativa
    {
        $caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $caja->update(['efectivo_inicial' => '8000.00']);

        return $caja;
    }

    public function test_si_saca_la_plata_despues_del_cierre_no_hay_faltante(): void
    {
        $this->efectivo->update(['saldo_inicial' => 10000]);
        $antes = $this->saldoEfectivo();

        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $this->caja->id), [
            'efectivo_contado' => '10000.00',
            'cambio_retenido' => '8000.00',
        ])->assertSessionHas('success', 'Caja cerrada.');
        $this->assertEquals(0, (float) $this->caja->fresh()->diferencia_efectivo);

        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail()->id,
            'monto' => '2000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Se llevó lo que entregó la operativa',
        ])->assertSessionHas('success');

        $this->actingAs($this->duenio)->post(route('web.cajas.validar', $this->caja->id))->assertSessionHas('success');
        $this->assertEquals($antes - 2000, $this->saldoEfectivo());
    }

    public function test_el_retiro_por_cashflow_entra_aunque_la_cuenta_quede_en_negativo(): void
    {
        $this->efectivo->update(['saldo_inicial' => 0, 'permite_descubierto' => false]);

        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail()->id,
            'monto' => '2000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'El cajón tiene plata pero la cuenta todavía no',
        ])->assertSessionHas('success', 'Movimiento directo registrado en cashflow.');

        $this->assertEquals(-2000, $this->saldoEfectivo());
    }
}
