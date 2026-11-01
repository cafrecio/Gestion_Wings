<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Support\ClasificacionSubrubros;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MedioPagoCajonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $tipoCajaMostrador;
    private TipoCaja $tipoCajaOtro;
    private CajaService $cajaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-18 10:00:00'));

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->tipoCajaMostrador = TipoCaja::create([
            'nombre' => 'Efectivo Mostrador',
            'abreviatura' => 'EFT',
            'saldo_inicial' => 1000,
            'activo' => true,
        ]);

        $this->tipoCajaOtro = TipoCaja::create([
            'nombre' => 'Efectivo Alternativo',
            'abreviatura' => 'EFA',
            'saldo_inicial' => 500,
            'activo' => true,
        ]);

        $this->cajaService = app(CajaService::class);
        $this->cajaService->configurarMostrador($this->tipoCajaMostrador->id, $this->admin->id);
    }

    public function test_no_se_puede_desactivar_el_medio_de_pago_del_mostrador_via_toggle_web(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('web.tipos-caja.toggle-activo', $this->tipoCajaMostrador->id));

        $response->assertSessionHas('error', 'No se puede desactivar porque es el medio de pago del cajón de mostrador.');
        $this->assertTrue($this->tipoCajaMostrador->fresh()->activo);
    }

    public function test_no_se_puede_desactivar_el_medio_de_pago_del_mostrador_via_toggle_json(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson(route('web.tipos-caja.toggle-activo', $this->tipoCajaMostrador->id));

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'No se puede desactivar porque es el medio de pago del cajón de mostrador.',
            ]);
        $this->assertTrue($this->tipoCajaMostrador->fresh()->activo);
    }

    public function test_no_se_puede_desactivar_el_medio_del_mostrador_via_update(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('web.tipos-caja.update', $this->tipoCajaMostrador->id), [
                'nombre' => 'Efectivo Mostrador',
                'abreviatura' => 'EFT',
                'saldo_inicial' => 1000,
                'activo' => 0,
            ]);

        $response->assertSessionHasErrors(['activo']);
        $this->assertTrue($this->tipoCajaMostrador->fresh()->activo);
    }

    public function test_se_puede_desactivar_otro_medio_de_pago(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('web.tipos-caja.toggle-activo', $this->tipoCajaOtro->id));

        $response->assertSessionHas('success', 'Tipo de caja desactivado.');
        $this->assertFalse($this->tipoCajaOtro->fresh()->activo);
    }

    public function test_la_cuenta_configurada_no_se_puede_cambiar_sin_caja_abierta(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(CajaService::EFECTIVO_YA_CONFIGURADO);

        $this->cajaService->configurarMostrador($this->tipoCajaOtro->id, $this->admin->id);
    }

    public function test_la_cuenta_configurada_no_se_puede_cambiar_con_caja_abierta(): void
    {
        $this->abrirCaja();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(CajaService::EFECTIVO_YA_CONFIGURADO);

        $this->cajaService->configurarMostrador($this->tipoCajaOtro->id, $this->admin->id);
    }

    public function test_pedido_directo_no_cambia_la_cuenta_ni_el_saldo_ya_configurados(): void
    {
        $efectivo = $this->efectivoSinConfigurar(2000);
        $this->cajaService->configurarEfectivo($this->admin->id, '2000');

        $this->actingAs($this->admin)->post(route('web.caja.configuracion.store'), [
            'tipo_caja_id' => $this->tipoCajaOtro->id,
            'saldo_inicial' => '999',
        ])->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('error', CajaService::EFECTIVO_YA_CONFIGURADO);

        $this->assertSame($efectivo->id, $this->cajaService->tipoCajaMostradorId());
        $this->assertSame('2000.00', $efectivo->fresh()->saldo_inicial);
    }

    public function test_pantalla_ya_configurada_vuelve_a_caja(): void
    {
        $this->actingAs($this->admin)->get(route('web.caja.configuracion'))
            ->assertRedirect(route('web.caja.index'));
    }

    public function test_caja_configurada_solo_ofrece_abrir(): void
    {
        $response = $this->actingAs($this->admin)->get(route('web.caja.index'));

        $response->assertOk();
        $response->assertSee('Cajón del mostrador: abrile la caja a un operativo');
        $response->assertSee('href="'.route('web.caja.apertura').'"', false);
        $response->assertDontSee(route('web.caja.configuracion'), false);
        $response->assertDontSee('Configurar');
    }

    public function test_caja_sin_configurar_solo_ofrece_configurar(): void
    {
        $this->efectivoSinConfigurar();

        $response = $this->actingAs($this->admin)->get(route('web.caja.index'));

        $response->assertOk();
        $response->assertSee('Falta cargar el saldo de Efectivo');
        $response->assertSee('href="'.route('web.caja.configuracion').'"', false);
        $response->assertDontSee('href="'.route('web.caja.apertura').'"', false);
    }

    public function test_pantalla_muestra_efectivo_fijo_y_el_saldo_vacio(): void
    {
        $this->efectivoSinConfigurar(2000);

        $response = $this->actingAs($this->admin)->get(route('web.caja.configuracion'));

        $response->assertOk();
        $response->assertSee('value="Efectivo" readonly', false);
        $response->assertDontSee('name="tipo_caja_id"', false);
        $response->assertSee('name="saldo_inicial"', false);
        $response->assertDontSee('2,000.00');
        $response->assertSee('Guardar');
    }

    public function test_guardar_configura_efectivo_con_su_saldo_y_deja_autor_y_fecha(): void
    {
        $efectivo = $this->efectivoSinConfigurar();

        $this->actingAs($this->admin)->post(route('web.caja.configuracion.store'), [
            'saldo_inicial' => '2,310,000.50',
            'tipo_caja_id' => $this->tipoCajaOtro->id,
        ])->assertRedirect(route('web.caja.index'))->assertSessionHas('success', 'Efectivo configurado.');

        $this->assertSame($efectivo->id, $this->cajaService->tipoCajaMostradorId());
        $this->assertSame('2310000.50', $efectivo->fresh()->saldo_inicial);
        $this->assertSame('500.00', $this->tipoCajaOtro->fresh()->saldo_inicial);
        $mostrador = DB::table('caja_mostrador')->where('id', 1)->first();
        $this->assertEquals($this->admin->id, $mostrador->configurado_por_id);
        $this->assertEquals('2026-10-18 10:00:00', $mostrador->configurado_at);
    }

    public function test_saldo_en_cero_se_acepta_si_se_escribe(): void
    {
        $efectivo = $this->efectivoSinConfigurar(2000);

        $this->actingAs($this->admin)->post(route('web.caja.configuracion.store'), ['saldo_inicial' => '0'])
            ->assertSessionHasNoErrors();

        $this->assertSame('0.00', $efectivo->fresh()->saldo_inicial);
        $this->assertSame($efectivo->id, $this->cajaService->tipoCajaMostradorId());
    }

    #[DataProvider('saldosInvalidos')]
    public function test_saldo_invalido_no_configura_nada(array $datos): void
    {
        $efectivo = $this->efectivoSinConfigurar(2000);

        $this->actingAs($this->admin)->post(route('web.caja.configuracion.store'), $datos)
            ->assertSessionHasErrors('saldo_inicial');

        $this->assertNull($this->cajaService->tipoCajaMostradorId());
        $this->assertSame('2000.00', $efectivo->fresh()->saldo_inicial);
    }

    public static function saldosInvalidos(): array
    {
        return [
            'sin saldo' => [[]],
            'vacio' => [['saldo_inicial' => '']],
            'negativo' => [['saldo_inicial' => '-5']],
            'tres decimales' => [['saldo_inicial' => '48.505']],
            'letras' => [['saldo_inicial' => 'mucho']],
        ];
    }

    public function test_efectivo_con_movimientos_no_cambia_su_saldo_pero_se_configura(): void
    {
        $efectivo = $this->efectivoSinConfigurar(2000);
        $this->movimientoEn($efectivo);

        $response = $this->actingAs($this->admin)->get(route('web.caja.configuracion'));
        $response->assertOk();
        $response->assertSee('Efectivo ya tiene movimientos');
        $response->assertDontSee('name="saldo_inicial"', false);
        $response->assertSee('value="2,000.00"', false);

        $this->actingAs($this->admin)->post(route('web.caja.configuracion.store'), ['saldo_inicial' => '999'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2000.00', $efectivo->fresh()->saldo_inicial);
        $this->assertSame($efectivo->id, $this->cajaService->tipoCajaMostradorId());
    }

    public function test_sin_cuenta_efectivo_la_crea_al_configurar(): void
    {
        DB::table('caja_mostrador')->where('id', 1)->update(['tipo_caja_id' => null]);
        TipoCaja::where('nombre', 'Efectivo')->delete();

        $this->actingAs($this->admin)->post(route('web.caja.configuracion.store'), ['saldo_inicial' => '100'])
            ->assertSessionHasNoErrors();

        $efectivo = TipoCaja::where('nombre', 'Efectivo')->sole();
        $this->assertSame('100.00', $efectivo->saldo_inicial);
        $this->assertSame($efectivo->id, $this->cajaService->tipoCajaMostradorId());
    }

    public function test_abrir_sin_configurar_avisa_y_la_operativa_no_ve_configurar(): void
    {
        $this->efectivoSinConfigurar();

        $response = $this->actingAs($this->operativo)->get(route('web.caja.apertura'));
        $response->assertOk();
        $response->assertSee('El dueño tiene que configurar Efectivo antes de abrir la caja.');
        $response->assertDontSee('Configurar');
        $response->assertDontSee(route('web.caja.configuracion'), false);

        $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'efectivo_inicial' => 0, 'confirmacion' => 1,
        ])->assertSessionHasErrors(['efectivo_inicial' => 'El dueño tiene que configurar Efectivo antes de abrir la caja.']);
        $this->assertDatabaseCount('cajas_operativas', 0);
    }

    public function test_tipos_de_caja_no_cambia_nombre_ni_saldo_de_la_cuenta_configurada(): void
    {
        $edicion = $this->actingAs($this->admin)->get(route('web.tipos-caja.edit', $this->tipoCajaMostrador->id));
        $edicion->assertOk();
        $edicion->assertSee('El nombre de esta cuenta no se puede cambiar.');
        $edicion->assertSee('Se ajusta con un movimiento, no editando el saldo inicial.');
        $edicion->assertDontSee('name="nombre"', false);
        $edicion->assertDontSee('name="saldo_inicial"', false);

        $this->actingAs($this->admin)->put(route('web.tipos-caja.update', $this->tipoCajaMostrador->id), [
            'nombre' => 'Otro nombre', 'abreviatura' => 'OTR', 'saldo_inicial' => 5,
        ])->assertSessionHasNoErrors();

        $cuenta = $this->tipoCajaMostrador->fresh();
        $this->assertSame('Efectivo Mostrador', $cuenta->nombre);
        $this->assertSame('1000.00', $cuenta->saldo_inicial);
        $this->assertSame('OTR', $cuenta->abreviatura);
    }

    public function test_efectivo_sin_configurar_tampoco_se_renombra_ni_se_desactiva(): void
    {
        $efectivo = $this->efectivoSinConfigurar();

        $this->actingAs($this->admin)->put(route('web.tipos-caja.update', $efectivo->id), [
            'nombre' => 'Billetes', 'abreviatura' => 'EFT', 'saldo_inicial' => 5, 'activo' => 0,
        ])->assertSessionHasErrors('activo');
        $this->actingAs($this->admin)->put(route('web.tipos-caja.update', $efectivo->id), [
            'nombre' => 'Billetes', 'abreviatura' => 'EFT', 'saldo_inicial' => 5,
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->patch(route('web.tipos-caja.toggle-activo', $efectivo->id))
            ->assertSessionHas('error', 'No se puede desactivar porque es el medio de pago del cajón de mostrador.');

        $efectivo = $efectivo->fresh();
        $this->assertSame('Efectivo', $efectivo->nombre);
        $this->assertTrue($efectivo->activo);
    }

    public function test_las_demas_cuentas_se_corrigen_hasta_su_primer_movimiento(): void
    {
        $datos = ['nombre' => 'Otro nombre', 'abreviatura' => 'OTR', 'saldo_inicial' => 700];

        $this->actingAs($this->admin)->put(route('web.tipos-caja.update', $this->tipoCajaOtro->id), $datos)
            ->assertSessionHasNoErrors();
        $this->assertSame('700.00', $this->tipoCajaOtro->fresh()->saldo_inicial);
        $this->assertSame('Otro nombre', $this->tipoCajaOtro->fresh()->nombre);

        $this->movimientoEn($this->tipoCajaOtro);
        $this->actingAs($this->admin)->put(route('web.tipos-caja.update', $this->tipoCajaOtro->id), [...$datos, 'saldo_inicial' => 800])
            ->assertSessionHasNoErrors();
        $this->assertSame('700.00', $this->tipoCajaOtro->fresh()->saldo_inicial);
    }

    #[DataProvider('ajustesDeSaldo')]
    public function test_ajuste_de_saldo_mueve_la_cuenta_fuera_del_resultado(string $subrubro, string $tipo, string $clasificacion, string $monto): void
    {
        ClasificacionSubrubros::aplicarAjustes();
        $concepto = Subrubro::where('nombre', $subrubro)->sole();
        $this->assertSame($tipo, $concepto->rubro->tipo);
        $this->assertSame('ADMIN', $concepto->permitido_para);

        $this->actingAs($this->admin)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $this->tipoCajaMostrador->id,
            'subrubro_id' => $concepto->id,
            'monto' => '250.00',
            'fecha' => '2026-10-18',
            'observaciones' => 'Conteo de la caja fuerte',
        ])->assertRedirect(route('web.cashflow.index'));

        $movimiento = CashflowMovimiento::sole();
        $this->assertSame($clasificacion, $movimiento->reporte_clasificacion);
        $this->assertSame($monto, (string) $movimiento->monto);
    }

    public static function ajustesDeSaldo(): array
    {
        return [
            'entrada' => ['Ajuste de saldo: entrada', 'INGRESO', ClasificacionSubrubros::APORTE, '250.00'],
            'salida' => ['Ajuste de saldo: salida', 'EGRESO', ClasificacionSubrubros::RETIRO, '-250.00'],
        ];
    }

    public function test_operativa_no_puede_usar_el_ajuste_de_saldo(): void
    {
        $this->abrirCaja();

        $this->actingAs($this->operativo)->post('/caja/movimiento', [
            'tipo_caja_id' => $this->tipoCajaMostrador->id,
            'subrubro_id' => Subrubro::where('nombre', 'Ajuste de saldo: salida')->sole()->id,
            'monto' => '10',
        ]);

        $this->assertDatabaseCount('movimientos_operativos', 0);
    }

    public function test_operativo_no_ve_configurar_en_caja(): void
    {
        $response = $this->actingAs($this->operativo)->get(route('web.caja.index'));

        $response->assertOk();
        $response->assertDontSee('Configurar');
        $response->assertDontSee(route('web.caja.configuracion'), false);
    }

    public function test_operativo_no_puede_desactivar_tipo_caja(): void
    {
        $response = $this->actingAs($this->operativo)
            ->patch(route('web.tipos-caja.toggle-activo', $this->tipoCajaOtro->id));

        $response->assertForbidden();
        $this->assertTrue($this->tipoCajaOtro->fresh()->activo);
    }

    public function test_operativo_no_puede_ver_configuracion_del_cajon(): void
    {
        $this->efectivoSinConfigurar();

        $response = $this->actingAs($this->operativo)
            ->get(route('web.caja.configuracion'));

        $response->assertForbidden();
    }

    public function test_operativo_no_puede_configurar_el_cajon(): void
    {
        $efectivo = $this->efectivoSinConfigurar(2000);

        $response = $this->actingAs($this->operativo)
            ->post(route('web.caja.configuracion.store'), [
                'saldo_inicial' => '5',
            ]);

        $response->assertForbidden();
        $this->assertNull($this->cajaService->tipoCajaMostradorId());
        $this->assertSame('2000.00', $efectivo->fresh()->saldo_inicial);
    }

    private function abrirCaja(): void
    {
        CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'tipo_caja_efectivo_id' => $this->tipoCajaMostrador->id,
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 1000,
            'apertura_at' => now(),
        ]);
    }

    private function efectivoSinConfigurar(float $saldo = 0): TipoCaja
    {
        DB::table('caja_mostrador')->where('id', 1)->update(['tipo_caja_id' => null, 'configurado_por_id' => null, 'configurado_at' => null]);

        return TipoCaja::updateOrCreate(['nombre' => 'Efectivo'], ['abreviatura' => 'EFT', 'saldo_inicial' => $saldo, 'activo' => true]);
    }

    private function movimientoEn(TipoCaja $cuenta): void
    {
        CashflowMovimiento::create([
            'fecha' => '2026-10-18',
            'subrubro_id' => Subrubro::where('nombre', 'Ajuste de saldo: entrada')->sole()->id,
            'tipo_caja_id' => $cuenta->id,
            'monto' => 10,
            'usuario_admin_id' => $this->admin->id,
        ]);
    }
}
