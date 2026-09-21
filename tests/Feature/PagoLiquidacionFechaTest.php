<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\AvisoAdminService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * FIN-09 alcanza también al pago de una liquidación (Carlos, 21/09/2026).
 *
 * La revisión cruzada encontró que era la única vía de la pantalla que aceptaba
 * cualquier fecha: el ADMIN podía registrar el egreso de un sueldo con fecha futura,
 * o de un mes ya cerrado sin enterarse de que cambiaba ese resultado. Misma regla que
 * caja, cobro y Cashflow: nada futuro, el mes en curso directo, y lo anterior pide
 * confirmar y avisa por mail y Telegram.
 */
class PagoLiquidacionFechaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TipoCaja $tipoCaja;
    private Liquidacion $liquidacion;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-21 10:00:00');

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->tipoCaja = TipoCaja::create([
            'nombre' => 'Banco',
            'abreviatura' => 'BCO',
            'saldo_inicial' => 500000,
            'permite_descubierto' => false,
            'activo' => true,
        ]);
        $rubro = Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO', 'observacion' => '']);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Sueldo de prueba',
            'permitido_para' => User::ROL_ADMIN,
            'afecta_caja' => false,
            'es_reservado_sistema' => false,
            'activo' => true,
        ]);
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
        $this->liquidacion = Liquidacion::create([
            'profesor_id' => $profesor->id,
            'mes' => 8,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 100000,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_fecha_futura_se_rechaza(): void
    {
        $this->pagar('2026-09-22')->assertSessionHasErrors('fecha_pago');

        $this->assertNoPagada();
    }

    public function test_fecha_del_mes_en_curso_paga_sin_preguntar_ni_avisar(): void
    {
        $this->sinAvisos();

        $this->pagar('2026-09-01')->assertRedirect(route('web.liquidaciones.show', $this->liquidacion->id));

        $this->assertPagadaCon('2026-09-01');
    }

    public function test_fecha_de_un_mes_anterior_sin_confirmar_no_paga_y_pregunta(): void
    {
        $this->sinAvisos();

        $this->pagar('2026-08-31')->assertSessionHas('aviso_fecha_vieja');

        $this->assertNoPagada();
        $this->actingAs($this->admin)
            ->get(route('web.liquidaciones.show', $this->liquidacion->id))
            ->assertSee('name="confirmar_fecha_vieja"', false);
    }

    public function test_fecha_de_un_mes_anterior_confirmada_paga_con_su_fecha_y_avisa(): void
    {
        $avisos = Mockery::mock(AvisoAdminService::class);
        $avisos->shouldReceive('fechaVieja')->once()->withArgs(
            fn (string $que, string $fecha) => str_contains($que, 'liquidación') && $fecha === '2026-08-31'
        );
        $this->app->instance(AvisoAdminService::class, $avisos);

        $this->pagar('2026-08-31', confirmar: true)
            ->assertRedirect(route('web.liquidaciones.show', $this->liquidacion->id));

        $this->assertPagadaCon('2026-08-31');
    }

    private function pagar(string $fecha, bool $confirmar = false)
    {
        return $this->actingAs($this->admin)->post(route('web.liquidaciones.pagar', $this->liquidacion->id), array_filter([
            'fecha_pago' => $fecha,
            'tipo_caja_id' => $this->tipoCaja->id,
            'confirmar_fecha_vieja' => $confirmar ? '1' : null,
        ]));
    }

    private function sinAvisos(): void
    {
        $avisos = Mockery::mock(AvisoAdminService::class);
        $avisos->shouldNotReceive('fechaVieja');
        $this->app->instance(AvisoAdminService::class, $avisos);
    }

    private function assertNoPagada(): void
    {
        $this->assertSame(Liquidacion::ESTADO_PAGO_PENDIENTE, $this->liquidacion->fresh()->estado_pago);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    private function assertPagadaCon(string $fecha): void
    {
        $this->assertSame(Liquidacion::ESTADO_PAGO_PAGADA, $this->liquidacion->fresh()->estado_pago);
        $this->assertDatabaseHas('cashflow_movimientos', [
            'tipo_caja_id' => $this->tipoCaja->id,
            'monto' => '-100000.00',
            'fecha' => $fecha,
        ]);
    }
}
