<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\MovimientoOperativo;
use App\Models\MovimientoOperativoCorreccion;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\CashflowIntegracionCajaService;
use App\Services\ReporteMensualService;
use App\Support\ClasificacionSubrubros;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T116, opción A de Carlos, 01/11/2026: «Retiro de dueños» va en Ambos. El dueño se lleva plata
 * del cajón, lo carga en el turno, y la operativa no cierra con un faltante que no es suyo.
 */
class RetiroEnTurnoT116Test extends TestCase
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
        $this->efectivo->update(['saldo_inicial' => 10000]);
        $this->caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => '10000.00']);
    }

    private function agregar(User $quien, string $subrubro, string $monto, string $detalle)
    {
        return $this->actingAs($quien)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => Subrubro::where('nombre', $subrubro)->firstOrFail()->id,
            'monto' => $monto,
            'fecha' => now()->toDateString(),
            'observaciones' => $detalle,
        ]);
    }

    private function retiroDelDuenio(string $monto = '50000.00'): MovimientoOperativo
    {
        $this->agregar($this->operativa, 'Inscripciones', '90000.00', 'Cobros del viernes')->assertSessionHas('success');
        $this->agregar($this->duenio, 'Retiro de dueños', $monto, 'Se llevó del cajón')->assertSessionHas('success', 'Movimiento registrado.');

        return MovimientoOperativo::where('observaciones', 'Se llevó del cajón')->sole();
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

    private function cerrarYValidar(string $contado): void
    {
        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $this->caja->id), [
            'efectivo_contado' => $contado,
            'cambio_retenido' => $contado,
        ])->assertSessionHas('success', 'Caja cerrada.');

        foreach ($this->caja->movimientos()->get() as $movimiento) {
            app(CajaService::class)->verificarMovimiento($this->caja->id, $movimiento->id, $this->duenio->id, true, $movimiento->huellaVerificacion());
        }
        $this->actingAs($this->duenio)->post(route('web.cajas.validar', $this->caja->id))->assertSessionHas('success');
    }

    public function test_el_viernes_del_cajon_cierra_sin_faltante_y_la_plata_da_al_peso(): void
    {
        $retiro = $this->retiroDelDuenio();

        $this->assertSame($this->duenio->id, $retiro->usuario_id);
        $this->assertEquals(50000, $this->esperado());
        $this->assertEquals(10000, $this->saldoEfectivo(), 'La cuenta baja recién al validar');

        $this->cerrarYValidar('50000.00');

        $this->assertEquals(0, (float) $this->caja->fresh()->diferencia_efectivo);
        $this->assertSame('VALIDADA', $this->caja->fresh()->estado);
        $this->assertEquals(50000, $this->saldoEfectivo(), 'Saldo $10.000 + cobros $90.000 − retiro $50.000, una sola vez');

        $asiento = CashflowMovimiento::where('subrubro_id', $retiro->subrubro_id)->sole();
        $this->assertEquals(-50000, (float) $asiento->monto);
        $this->assertSame($this->duenio->id, $asiento->usuario_admin_id);

        $reporte = app(ReporteMensualService::class)->obtener(now()->format('Y-m'));
        $this->assertEquals(5000000, $reporte['retiros']);
        $this->assertEquals(9000000, $reporte['resultado'], 'El retiro no es gasto del club');
    }

    public function test_la_operativa_ve_el_retiro_a_nombre_del_duenio(): void
    {
        $this->retiroDelDuenio();

        $this->actingAs($this->operativa)->get(route('web.movimientos.index'))->assertOk()
            ->assertSee('Se llevó del cajón')->assertSee($this->duenio->name);
        $this->actingAs($this->operativa)->get(route('web.caja.detalle', $this->caja->id))->assertOk()
            ->assertSee('Se llevó del cajón');
    }

    public function test_la_operativa_puede_cargar_un_retiro_y_queda_a_su_nombre(): void
    {
        $this->agregar($this->operativa, 'Inscripciones', '90000.00', 'Cobros del viernes')->assertSessionHas('success');

        $this->actingAs($this->operativa)->post(route('web.caja.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail()->id,
            'monto' => '30000.00',
            'observaciones' => 'Se lo llevó el dueño',
        ])->assertSessionHas('success', 'Movimiento registrado.');

        $retiro = MovimientoOperativo::where('observaciones', 'Se lo llevó el dueño')->sole();
        $this->assertSame($this->operativa->id, $retiro->usuario_id);
        $this->assertEquals(70000, $this->esperado());

        $this->cerrarYValidar('70000.00');

        $this->assertSame($this->operativa->id, CashflowMovimiento::where('subrubro_id', $retiro->subrubro_id)->sole()->usuario_admin_id);
    }

    public function test_un_retiro_no_puede_superar_lo_que_hay_en_el_cajon(): void
    {
        $this->agregar($this->duenio, 'Retiro de dueños', '10000.01', 'Más de lo que hay')
            ->assertSessionHas('error', 'El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.');

        $this->assertSame(0, MovimientoOperativo::where('caja_operativa_id', $this->caja->id)->count());
        $this->assertEquals(10000, $this->esperado());
    }

    public function test_la_operativa_puede_corregir_el_retiro_del_duenio_y_queda_registrado(): void
    {
        $retiro = $this->retiroDelDuenio();

        $this->actingAs($this->operativa)->put(route('web.caja.movimientos.update', [$this->caja->id, $retiro->id]), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $retiro->subrubro_id,
            'monto' => '20000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Se llevó del cajón',
        ])->assertSessionHas('success', 'Movimiento actualizado.');

        $retiro->refresh();
        $this->assertSame($this->duenio->id, $retiro->usuario_id, 'El autor no cambia');
        $this->assertEquals(80000, $this->esperado());
        $this->assertSame('EDITAR', MovimientoOperativoCorreccion::where('movimiento_id', $retiro->id)->sole()->accion);

        $this->actingAs($this->operativa)->delete(route('web.caja.movimientos.destroy', [$this->caja->id, $retiro->id]))
            ->assertSessionHas('success', 'Movimiento eliminado.');

        $correccion = MovimientoOperativoCorreccion::where('movimiento_id', $retiro->id)->where('accion', 'ELIMINAR')->sole();
        $this->assertSame($this->operativa->id, $correccion->usuario_id);
        $this->actingAs($this->duenio)->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('Historia de correcciones');
    }

    public function test_quien_no_es_titular_no_puede_cargarlo_en_ese_turno(): void
    {
        $otra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $profesora = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);

        $this->agregar($otra, 'Retiro de dueños', '100.00', 'Ajena')->assertForbidden();
        $this->agregar($profesora, 'Retiro de dueños', '100.00', 'Ajena')->assertForbidden();

        $this->assertSame(0, MovimientoOperativo::where('caja_operativa_id', $this->caja->id)->count());
    }

    public function test_lo_que_sigue_siendo_solo_del_duenio_no_cambia(): void
    {
        foreach (['San Carlos', 'Luz', 'Pago al organizador'] as $nombre) {
            $this->assertSame('ADMIN', Subrubro::where('nombre', $nombre)->value('permitido_para'), $nombre);
            $this->agregar($this->duenio, $nombre, '100.00', "Intento $nombre")
                ->assertSessionHas('error', 'El subrubro seleccionado no está permitido para usuarios operativos.');
        }
    }

    public function test_el_retiro_de_un_banco_sigue_entrando_por_cashflow_y_no_toca_el_turno(): void
    {
        $galicia = TipoCaja::where('nombre', 'Banco Galicia')->firstOrFail();

        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $galicia->id,
            'subrubro_id' => Subrubro::where('nombre', 'Retiro de dueños')->firstOrFail()->id,
            'monto' => '300000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Retiro del mes',
        ])->assertSessionHas('success');

        $this->assertEquals(10000, $this->esperado());
        $this->assertSame(0, MovimientoOperativo::where('caja_operativa_id', $this->caja->id)->count());
        $this->actingAs($this->operativa)->get(route('web.movimientos.index'))->assertOk()->assertDontSee('Retiro del mes');
    }

    public function test_el_subrubro_llega_en_ambos_por_migracion_y_por_catalogo(): void
    {
        $retiro = fn () => Subrubro::where('nombre', 'Retiro de dueños')->sole()->permitido_para;
        $migracion = require database_path('migrations/2026_11_01_150000_retiro_de_duenos_en_ambos.php');
        $luz = Subrubro::where('nombre', 'Luz')->sole()->updated_at;

        $this->assertSame('OPERATIVO', $retiro());

        Subrubro::where('nombre', 'Retiro de dueños')->update(['permitido_para' => 'ADMIN']);
        $migracion->up();
        $this->assertSame('OPERATIVO', $retiro());
        $this->assertEquals($luz, Subrubro::where('nombre', 'Luz')->sole()->updated_at, 'No toca a los demás');

        $migracion->down();
        $this->assertSame('ADMIN', $retiro());

        $this->seed(CatalogosSeeder::class);
        $this->assertSame('OPERATIVO', $retiro());

        DB::table('subrubros')->whereIn('nombre', ['Retiro de dueños', 'Pago al organizador'])->delete();
        ClasificacionSubrubros::aplicarCatalogo();
        $this->assertSame('OPERATIVO', $retiro());
        $this->assertSame('ADMIN', Subrubro::where('nombre', 'Pago al organizador')->sole()->permitido_para);
    }
}
