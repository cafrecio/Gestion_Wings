<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\MovimientoOperativo;
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

        $this->post(route('web.tipos-caja.store'), [
            'nombre' => 'Banco',
            'abreviatura' => 'BCO',
            'saldo_inicial' => 200000,
        ])->assertRedirect(route('web.tipos-caja.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tipos_caja', [
            'nombre' => 'Banco',
            'saldo_inicial' => '200000.00',
        ]);

        $this->actingAs($admin)->post(route('web.tipos-caja.store'), [
            'nombre' => 'Billetera',
            'abreviatura' => 'BIL',
            'saldo_inicial' => -1,
        ])->assertSessionHasErrors('saldo_inicial');

        $this->assertDatabaseMissing('tipos_caja', ['nombre' => 'Billetera']);
    }

    public function test_nombre_duplicado_por_mayusculas_se_rechaza_sin_crear_otro_tipo(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $tipoCaja = TipoCaja::create(['nombre' => 'Banco', 'abreviatura' => 'BCO']);

        // ASCII intencional: la equivalencia de acentos se verifica en MariaDB (B2),
        // no en SQLite, cuya comparacion no reproduce utf8mb4_unicode_ci.
        $this->actingAs($admin)->post(route('web.tipos-caja.store'), [
            'nombre' => 'BANCO',
            'abreviatura' => 'OTRO',
            'saldo_inicial' => 100,
        ])->assertSessionHasErrors('nombre');

        $this->assertDatabaseCount('tipos_caja', 1);
        $this->assertDatabaseHas('tipos_caja', ['id' => $tipoCaja->id, 'nombre' => 'Banco']);
        $this->getJson(route('web.tipos-caja.check-disponible', ['nombre' => 'BANCO']))
            ->assertOk()->assertJson(['disponible' => false]);
        $this->getJson(route('web.tipos-caja.check-disponible', [
            'nombre' => 'BANCO', 'tipo_caja_id' => $tipoCaja->id,
        ]))->assertOk()->assertJson(['disponible' => true]);
    }

    public function test_saldo_inicial_se_corrige_por_web_solo_mientras_no_haya_movimientos(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($admin)->post(route('web.tipos-caja.store'), [
            'nombre' => 'Banco', 'abreviatura' => 'BCO', 'saldo_inicial' => 200000,
        ])->assertRedirect(route('web.tipos-caja.index'))->assertSessionHasNoErrors();
        $tipoCaja = TipoCaja::where('nombre', 'Banco')->sole();

        $this->get(route('web.tipos-caja.edit', $tipoCaja->id))
            ->assertOk()->assertSee('name="saldo_inicial"', false);
        $this->put(route('web.tipos-caja.update', $tipoCaja->id), [
            'nombre' => 'BANCO', 'abreviatura' => 'BCO', 'saldo_inicial' => 250000,
        ])->assertRedirect(route('web.tipos-caja.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tipos_caja', [
            'id' => $tipoCaja->id, 'saldo_inicial' => '250000.00',
        ]);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
        $this->assertDatabaseCount('movimientos_operativos', 0);

        $this->put(route('web.tipos-caja.update', $tipoCaja->id), [
            'nombre' => 'BANCO', 'abreviatura' => 'BCO', 'saldo_inicial' => -1,
        ])->assertSessionHasErrors('saldo_inicial');
        $this->assertDatabaseHas('tipos_caja', [
            'id' => $tipoCaja->id, 'saldo_inicial' => '250000.00',
        ]);
    }

    public function test_un_movimiento_cashflow_impide_reescribir_el_saldo_inicial(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($admin)->post(route('web.tipos-caja.store'), [
            'nombre' => 'Banco', 'abreviatura' => 'BCO', 'saldo_inicial' => 200000,
        ])->assertRedirect(route('web.tipos-caja.index'))->assertSessionHasNoErrors();
        $tipoCaja = TipoCaja::where('nombre', 'Banco')->sole();
        [, $subrubro] = $this->crearSubrubroEgreso();

        $this->post(route('web.cashflow.movimiento.store'), [
            'fecha' => today()->toDateString(), 'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $subrubro->id, 'monto' => 50000,
            'observaciones' => 'Movimiento de prueba',
        ])->assertRedirect(route('web.cashflow.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cashflow_movimientos', [
            'tipo_caja_id' => $tipoCaja->id, 'monto' => '-50000.00',
        ]);
        $this->assertDatabaseCount('movimientos_operativos', 0);

        $this->comprobarSaldoIgnorado($tipoCaja);
        $this->assertDatabaseCount('cashflow_movimientos', 1);
        $this->assertDatabaseCount('movimientos_operativos', 0);
    }

    public function test_un_movimiento_operativo_incluso_cancelado_bloquea_el_saldo_inicial(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($admin);
        $tipoCaja = TipoCaja::create([
            'nombre' => 'Banco', 'abreviatura' => 'BCO', 'saldo_inicial' => 200000,
        ]);
        [, $subrubro] = $this->crearSubrubroEgreso();
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $admin->id, 'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);
        $movimiento = MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id, 'usuario_id' => $admin->id,
            'fecha' => today(), 'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $subrubro->id, 'monto' => 100,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        $this->comprobarSaldoIgnorado($tipoCaja);
        $movimiento->update([
            'estado' => MovimientoOperativo::ESTADO_CANCELADO,
            'motivo_cancelacion' => 'Cancelacion de prueba',
        ]);
        $this->comprobarSaldoIgnorado($tipoCaja);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
        $this->assertDatabaseCount('movimientos_operativos', 1);
    }

    private function comprobarSaldoIgnorado(TipoCaja $tipoCaja): void
    {
        // Debe ignorarlo, no validarlo: probar valores validos, invalidos y omision.
        foreach ([['saldo_inicial' => 999999], ['saldo_inicial' => -1],
            ['saldo_inicial' => 'no es un monto'], ['saldo_inicial' => null], []] as $saldo) {
            $this->put(route('web.tipos-caja.update', $tipoCaja->id), array_merge([
                'nombre' => 'Banco', 'abreviatura' => 'BCO',
                'descripcion' => 'Descripcion editable',
            ], $saldo))->assertRedirect(route('web.tipos-caja.index'))->assertSessionHasNoErrors();
            $this->assertDatabaseHas('tipos_caja', [
                'id' => $tipoCaja->id, 'saldo_inicial' => '200000.00',
                'descripcion' => 'Descripcion editable',
            ]);
        }

        $this->withSession(['_old_input' => ['saldo_inicial' => 999999]])
            ->get(route('web.tipos-caja.edit', $tipoCaja->id))
            ->assertOk()->assertDontSee('name="saldo_inicial"', false)
            ->assertSee('200.000')->assertDontSee('999.999')
            ->assertSee('Se ajusta con un movimiento, no editando el saldo inicial.');
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
        // Ya no hace falta emular YEAR(): la suite corre sobre MariaDB, que la
        // trae de fabrica. Este parche existia solo porque SQLite no la tiene.

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
