<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * En el Historial de caja, los límites son del operativo y no del dueño.
 *
 * Carlos, 11/10/2026: «El dueño debe ver todo, los límites no son para él». El
 * operativo ve 90 días, los rubros que tiene permitidos y solo lo que sigue en pie.
 * El dueño ve cualquier fecha, todos los rubros y también lo cancelado.
 *
 * Antes la pantalla aplicaba los tres límites a todos, y contaba como un ingreso
 * más el asiento en contra con que se anula un cobro del dueño.
 */
class HistorialDuenioSinLimitesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $sandra;
    private User $pablo;
    private TipoCaja $efectivo;
    private Subrubro $cuota;
    private Subrubro $soloDelDuenio;
    private CajaOperativa $cajaDePablo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->sandra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Sandra']);
        $this->pablo  = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Pablo']);

        $this->efectivo = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $ingresos = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        $egresos  = Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO', 'observacion' => '']);

        $this->cuota = Subrubro::create([
            'rubro_id' => $ingresos->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
        ]);
        $this->soloDelDuenio = Subrubro::create([
            'rubro_id' => $egresos->id,
            'nombre' => 'Sueldo profesora',
            'permitido_para' => User::ROL_ADMIN,
            'afecta_caja' => true,
        ]);

        $this->cajaDePablo = CajaOperativa::create([
            'usuario_operativo_id' => $this->pablo->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);
    }

    public function test_el_duenio_ve_mas_alla_de_90_dias_y_el_operativo_no(): void
    {
        $this->movimiento($this->cuota, 31500, 'cobro de hace cinco meses', ['fecha' => today()->subDays(150)]);
        $desde = today()->subDays(200)->toDateString();

        $this->assertContains('cobro de hace cinco meses', $this->observaciones($this->admin, ['desde' => $desde]));
        $this->assertNotContains('cobro de hace cinco meses', $this->observaciones($this->sandra, ['desde' => $desde]));

        // Sin fecha pedida, el dueño arranca en los últimos 90 días.
        $this->assertNotContains('cobro de hace cinco meses', $this->observaciones($this->admin));
    }

    public function test_el_duenio_ve_los_rubros_que_son_solo_suyos_y_el_operativo_no(): void
    {
        $this->movimiento($this->soloDelDuenio, 90000, 'sueldo de septiembre');

        $respuesta = $this->actingAs($this->admin)->get(route('web.caja.historial'))->assertOk();
        $this->assertContains('sueldo de septiembre', $respuesta->viewData('filas')->pluck('obs')->all());
        $this->assertContains('Sueldo profesora', $respuesta->viewData('subrubros')->pluck('nombre')->all());

        $respuesta = $this->actingAs($this->sandra)->get(route('web.caja.historial'))->assertOk();
        $this->assertNotContains('sueldo de septiembre', $respuesta->viewData('filas')->pluck('obs')->all());
        $this->assertNotContains('Sueldo profesora', $respuesta->viewData('subrubros')->pluck('nombre')->all());
    }

    public function test_el_duenio_ve_lo_cancelado_marcado_y_sin_sumarlo(): void
    {
        $this->movimiento($this->cuota, 30000, 'cobro bien hecho');
        $this->movimiento($this->cuota, 20000, 'cobro al alumno equivocado', ['estado' => MovimientoOperativo::ESTADO_CANCELADO]);

        $respuesta = $this->actingAs($this->admin)->get(route('web.caja.historial'))->assertOk();
        $respuesta->assertSee('Cancelado');
        $cancelada = $respuesta->viewData('filas')->firstWhere('obs', 'cobro al alumno equivocado');
        $this->assertNotNull($cancelada, 'El dueño tiene que ver el movimiento cancelado.');
        $this->assertTrue($cancelada->cancelado);
        $this->assertEquals(30000.0, $respuesta->viewData('totalIngresos'));

        $respuesta = $this->actingAs($this->sandra)->get(route('web.caja.historial'))->assertOk();
        $respuesta->assertDontSee('Cancelado');
        $this->assertNotContains('cobro al alumno equivocado', $respuesta->viewData('filas')->pluck('obs')->all());
        $this->assertEquals(30000.0, $respuesta->viewData('totalIngresos'));
    }

    public function test_la_anulacion_de_un_cobro_del_duenio_resta_en_vez_de_sumar(): void
    {
        foreach ([48000, -48000] as $importe) {
            CashflowMovimiento::create([
                'fecha' => today(),
                'subrubro_id' => $this->cuota->id,
                'tipo_caja_id' => $this->efectivo->id,
                'monto' => $importe,
                'observaciones' => $importe > 0 ? 'cobro del dueño' : 'Anulación del cobro',
                'usuario_admin_id' => $this->admin->id,
                'referencia_tipo' => CashflowMovimiento::REF_PAGO_CUOTA,
                'referencia_id' => 1,
            ]);
        }

        foreach ([$this->admin, $this->sandra] as $usuario) {
            $respuesta = $this->actingAs($usuario)->get(route('web.caja.historial'))->assertOk();
            $this->assertCount(2, $respuesta->viewData('filas'));
            $this->assertEquals(0.0, $respuesta->viewData('totalIngresos'));
        }
    }

    private function movimiento(Subrubro $subrubro, float $monto, string $observaciones, array $extra = []): MovimientoOperativo
    {
        return MovimientoOperativo::create($extra + [
            'caja_operativa_id' => $this->cajaDePablo->id,
            'fecha' => today(),
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubro->id,
            'monto' => $monto,
            'observaciones' => $observaciones,
            'usuario_id' => $this->pablo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);
    }

    private function observaciones(User $usuario, array $filtros = []): array
    {
        return $this->actingAs($usuario)->get(route('web.caja.historial', $filtros))
            ->assertOk()
            ->viewData('filas')->pluck('obs')->all();
    }
}
