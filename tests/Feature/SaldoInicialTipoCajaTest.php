<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CashflowIntegracionCajaService;
use App\Services\CashflowSaldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SaldoInicialTipoCajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_alta_guarda_saldo_inicial_y_rechaza_valores_negativos(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $this->actingAs($admin)->get(route('web.tipos-caja.create'))
            ->assertOk()
            ->assertSee('name="saldo_inicial"', false);

        TipoCaja::create([
            'nombre' => 'Banco',
            'abreviatura' => 'BCO',
            'saldo_inicial' => 200000,
        ]);

        $this->assertDatabaseHas('tipos_caja', [
            'nombre' => 'Banco',
            'saldo_inicial' => '200000.00',
        ]);

        $this->actingAs($admin)->post(route('web.tipos-caja.store'), [
            'abreviatura' => 'BIL',
            'saldo_inicial' => -1,
        ])->assertSessionHasErrors('saldo_inicial');

        $this->assertDatabaseMissing('tipos_caja', ['nombre' => 'Billetera']);
    }

    public function test_cashflow_muestra_saldo_inicial_separado_y_lo_incluye_en_el_balance(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $tipoCaja = TipoCaja::create([
            'nombre' => 'Banco',
            'abreviatura' => 'BCO',
            'saldo_inicial' => 200000,
            'activo' => true,
        ]);
        DB::connection()->getPdo()->sqliteCreateFunction(
            'YEAR',
            fn(?string $fecha) => $fecha ? (int) substr($fecha, 0, 4) : null
        );

        $sinMovimientos = $this->actingAs($admin)->get(route('web.cashflow.index', [
            'tipo_caja_id' => $tipoCaja->id,
        ]));

        $sinMovimientos->assertOk()
            ->assertSeeInOrder(['$200.000', 'saldo inicial', '$200.000', 'balance']);

        [, $subrubro] = $this->crearSubrubroEgreso();
        CashflowMovimiento::create([
            'fecha' => today(),
            'subrubro_id' => $subrubro->id,
            'tipo_caja_id' => $tipoCaja->id,
            'monto' => -50000,
            'observaciones' => 'Ajuste de prueba',
            'usuario_admin_id' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('web.cashflow.index', [
            'tipo_caja_id' => $tipoCaja->id,
        ]))->assertOk()
            ->assertSeeInOrder(['$50.000', 'egresos', '$200.000', 'saldo inicial', '$150.000', 'balance']);
    }

    public function test_saldo_inicial_permite_pagar_liquidacion_sin_movimientos_previos(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $tipoCaja = TipoCaja::create([
            'nombre' => 'Banco',
            'abreviatura' => 'BCO',
            'saldo_inicial' => 500000,
            'permite_descubierto' => false,
            'activo' => true,
        ]);
        [, $subrubro] = $this->crearSubrubroEgreso();
        $deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'dni' => '30111222',
            'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Calle 1',
            'localidad' => 'Buenos Aires',
            'valor_hora' => 10000,
            'subrubro_id' => $subrubro->id,
            'activo' => true,
        ]);
        $liquidacion = Liquidacion::create([
            'profesor_id' => $profesor->id,
            'mes' => now()->month,
            'anio' => now()->year,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 100000,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        $this->actingAs($admin)->get(route('web.liquidaciones.show', $liquidacion->id))
            ->assertOk()
            ->assertSee('$500.000');

        $this->actingAs($admin)->post(route('web.liquidaciones.pagar', $liquidacion->id), [
            'fecha_pago' => today()->toDateString(),
            'tipo_caja_id' => $tipoCaja->id,
        ])->assertRedirect(route('web.liquidaciones.show', $liquidacion->id));

        $this->assertDatabaseHas('liquidaciones', [
            'id' => $liquidacion->id,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PAGADA,
        ]);
        $this->assertDatabaseHas('cashflow_movimientos', [
            'tipo_caja_id' => $tipoCaja->id,
            'monto' => '-100000.00',
        ]);
    }

    public function test_servicios_de_saldo_incluyen_tipos_sin_movimientos(): void
    {
        $tipoCaja = TipoCaja::create([
            'nombre' => 'Banco',
            'saldo_inicial' => 200000,
            'activo' => true,
        ]);
        $tipoCajaExistente = TipoCaja::create([
            'nombre' => 'Efectivo',
            'activo' => true,
        ]);

        $aFecha = app(CashflowIntegracionCajaService::class)
            ->saldoAcumuladoHastaFecha(today()->toDateString());
        $actuales = app(CashflowSaldoService::class)->obtenerSaldosPorTipoCaja();

        $saldosAFecha = collect($aFecha['por_tipo_caja'])->keyBy('tipo_caja_id');
        $saldosActuales = collect($actuales['por_tipo_caja'])->keyBy('tipo_caja_id');

        $this->assertSame(200000.0, $saldosAFecha[$tipoCaja->id]['saldo']);
        $this->assertSame(0.0, $saldosAFecha[$tipoCajaExistente->id]['saldo']);
        $this->assertSame(200000.0, $aFecha['totales']['saldo_total']);
        $this->assertSame(200000.0, $saldosActuales[$tipoCaja->id]['saldo']);
        $this->assertSame(0.0, $saldosActuales[$tipoCajaExistente->id]['saldo']);
        $this->assertSame(200000.0, $actuales['totales']['saldo_inicial']);
        $this->assertSame(200000.0, $actuales['totales']['saldo']);
    }

    private function crearSubrubroEgreso(): array
    {
        $rubro = Rubro::create([
            'nombre' => 'Sueldos',
            'tipo' => 'EGRESO',
            'observacion' => '',
        ]);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Sueldo de prueba',
            'permitido_para' => User::ROL_ADMIN,
            'afecta_caja' => false,
            'es_reservado_sistema' => false,
            'activo' => true,
        ]);

        return [$rubro, $subrubro];
    }
}
