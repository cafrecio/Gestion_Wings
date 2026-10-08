<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, Rubro, Subrubro, TipoCaja, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowPeriodosA10Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TipoCaja $caja;
    private Subrubro $ingreso;
    private Subrubro $egreso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-08 12:00:00'));
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($this->admin);
        $this->caja = TipoCaja::create(['nombre' => 'Banco de prueba', 'activo' => true, 'saldo_inicial' => 500]);
        foreach (['INGRESO' => 'ingreso', 'EGRESO' => 'egreso'] as $tipo => $propiedad) {
            $rubro = Rubro::create(['nombre' => $tipo.' de prueba', 'tipo' => $tipo]);
            $this->{$propiedad} = Subrubro::create([
                'rubro_id' => $rubro->id, 'nombre' => $tipo.' de prueba',
                'permitido_para' => User::ROL_ADMIN, 'afecta_caja' => false,
                'es_reservado_sistema' => false, 'activo' => true,
            ]);
        }
    }

    private function movimiento(string $fecha, float $monto, ?TipoCaja $caja = null): CashflowMovimiento
    {
        return CashflowMovimiento::create([
            'fecha' => $fecha, 'monto' => $monto, 'tipo_caja_id' => ($caja ?? $this->caja)->id,
            'subrubro_id' => ($monto < 0 ? $this->egreso : $this->ingreso)->id,
            'usuario_admin_id' => $this->admin->id, 'observaciones' => 'Prueba de intervalo',
        ]);
    }

    public function test_limpiar_escribe_un_enlace_valido_y_conserva_el_periodo_en_los_cuatro_modos(): void
    {
        foreach (['dia', 'semana', 'mes', 'anio'] as $modo) {
            $r = $this->get(route('web.cashflow.index', [
                'periodo' => $modo, 'fecha' => '2024-02-29', 'anio' => 2024, 'mes' => 2,
                'tipo_caja_id' => $this->caja->id, 'tipo' => 'INGRESO',
            ]))->assertOk();
            $dom = new \DOMDocument();
            $dom->loadHTML($r->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $href = null;
            foreach ($dom->getElementsByTagName('a') as $enlace) {
                if (trim($enlace->textContent) === 'Limpiar') {
                    $href = $enlace->getAttribute('href');
                    break;
                }
            }
            $this->assertNotNull($href);
            // DOM decodifica una vez, igual que Chrome. Request::create decodifica
            // otra vez y ocultaría un href con &amp;amp; si solo lo navegáramos.
            parse_str(parse_url($href, PHP_URL_QUERY), $parametros);
            $claves = array_keys($parametros);
            sort($claves);
            $this->assertSame(['anio', 'fecha', 'mes', 'periodo'], $claves);
            $this->assertSame('2024-02-29', $parametros['fecha']);
            $this->assertSame($modo, $parametros['periodo']);
            $limpio = $this->get($href)->assertOk();
            $this->assertSame($modo, $limpio->viewData('modo'));
            $this->assertSame('2024-02-29', $limpio->viewData('fechaReferencia')->toDateString());
            $this->assertNull($limpio->viewData('tipoCajaId'));
            $this->assertNull($limpio->viewData('tipo'));
        }
    }

    public function test_dia_excluye_ambos_dias_vecinos_y_no_suma_saldo_inicial(): void
    {
        $this->movimiento('2026-10-07', 1000);
        $entrada = $this->movimiento('2026-10-08', 100);
        $salida = $this->movimiento('2026-10-08', -25);
        $this->movimiento('2026-10-09', 2000);
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'dia', 'fecha' => '2026-10-08']))->assertOk();
        $this->assertSame([$entrada->id, $salida->id], $r->viewData('movimientos')->pluck('id')->sort()->values()->all());
        $this->assertEquals(100, $r->viewData('totalIngresos'));
        $this->assertEquals(25, $r->viewData('totalEgresos'));
        $r->assertSee('8 de octubre de 2026')->assertSeeInOrder(['$75', 'resultado del período'])->assertDontSee('$575');
    }

    public function test_semana_incluye_lunes_y_domingo_aunque_cruce_de_mes(): void
    {
        $this->movimiento('2026-09-27', 1000);
        $lunes = $this->movimiento('2026-09-28', 100);
        $domingo = $this->movimiento('2026-10-04', -25);
        $this->movimiento('2026-10-05', 2000);
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'semana', 'fecha' => '2026-10-01']))
            ->assertOk()->assertSee('Del 28/09/2026 al 04/10/2026');
        $this->assertSame([$lunes->id, $domingo->id], $r->viewData('movimientos')->pluck('id')->sort()->values()->all());
        $this->assertEquals(100, $r->viewData('totalIngresos'));
        $this->assertEquals(25, $r->viewData('totalEgresos'));
    }

    public function test_semana_cruza_de_anio_sin_recortarse_al_anio_del_selector(): void
    {
        $this->movimiento('2026-12-27', 1000);
        $this->movimiento('2026-12-28', 120);
        $this->movimiento('2027-01-03', -20);
        $this->movimiento('2027-01-04', 2000);
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'semana', 'fecha' => '2027-01-01', 'anio' => 2027]))
            ->assertOk()->assertSee('Del 28/12/2026 al 03/01/2027');
        $this->assertSame(2, $r->viewData('movimientos')->total());
        $this->assertEquals(120, $r->viewData('totalIngresos'));
        $this->assertEquals(20, $r->viewData('totalEgresos'));
    }

    public function test_mes_bisiesto_incluye_febrero_29_y_excluye_marzo_1(): void
    {
        $this->movimiento('2024-01-31', 1000);
        $this->movimiento('2024-02-01', 100);
        $this->movimiento('2024-02-29', -15);
        $this->movimiento('2024-03-01', 2000);
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'mes', 'anio' => 2024, 'mes' => 2]))
            ->assertOk()->assertSee('Febrero 2024');
        $this->assertSame(2, $r->viewData('movimientos')->total());
        $this->assertEquals(100, $r->viewData('totalIngresos'));
        $this->assertEquals(15, $r->viewData('totalEgresos'));
    }

    public function test_enlaces_anteriores_de_anio_y_mes_conservan_su_intervalo(): void
    {
        $this->movimiento('2025-12-31', 1000);
        $this->movimiento('2026-01-01', 100);
        $this->movimiento('2026-10-08', 30);
        $this->movimiento('2026-12-31', -20);
        $this->movimiento('2027-01-01', 2000);
        $r = $this->get(route('web.cashflow.index', ['anio' => 2026]))->assertOk()->assertSee('Año 2026 completo');
        $this->assertSame('anio', $r->viewData('modo'));
        $this->assertSame(3, $r->viewData('movimientos')->total());
        $this->assertEquals(130, $r->viewData('totalIngresos'));
        $this->assertEquals(20, $r->viewData('totalEgresos'));
        $r = $this->get(route('web.cashflow.index', ['anio' => 2026, 'mes' => 10]))->assertOk();
        $this->assertSame('mes', $r->viewData('modo'));
        $this->assertSame(1, $r->viewData('movimientos')->total());
        $this->get(route('web.cashflow.index'))->assertOk()->assertViewHas('modo', 'anio');
    }

    public function test_caja_filtra_filas_y_totales_mientras_tipo_filtra_solo_filas(): void
    {
        $otra = TipoCaja::create(['nombre' => 'Otra caja', 'activo' => true]);
        $this->movimiento('2026-10-08', 100);
        $salida = $this->movimiento('2026-10-08', -25);
        $this->movimiento('2026-10-08', 3000, $otra);
        $this->movimiento('2026-10-09', 5000);
        $r = $this->get(route('web.cashflow.index', [
            'periodo' => 'dia', 'fecha' => '2026-10-08', 'tipo_caja_id' => $this->caja->id, 'tipo' => 'EGRESO',
        ]))->assertOk();
        $this->assertSame([$salida->id], $r->viewData('movimientos')->pluck('id')->all());
        $this->assertEquals(100, $r->viewData('totalIngresos'));
        $this->assertEquals(25, $r->viewData('totalEgresos'));
        $r->assertSee('Limpiar');
    }

    public function test_cambio_de_mes_a_dia_conserva_fecha_y_limita_el_dia_al_mes_elegido(): void
    {
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'mes', 'anio' => 2024, 'mes' => 2, 'fecha' => '2026-01-31']))->assertOk();
        $this->assertSame('2024-02-29', $r->viewData('fechaReferencia')->toDateString());
        $r->assertSee('name="fecha" value="2024-02-29"', false);
        $this->get(route('web.cashflow.index', ['periodo' => 'dia', 'fecha' => '2024-02-29']))
            ->assertOk()->assertSee('29 de febrero de 2024');
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'mes', 'fecha' => '2024-02-29']))->assertOk();
        $this->assertSame(2024, $r->viewData('anio'));
        $this->assertSame(2, $r->viewData('mes'));
    }

    public function test_rechaza_periodo_y_fechas_invalidos_sin_error_500(): void
    {
        foreach ([['periodo' => 'otro'], ['fecha' => '2026-02-30'], ['anio' => 2101], ['mes' => 13]] as $datos) {
            $campo = array_key_first($datos);
            $this->from(route('web.cashflow.index'))->get(route('web.cashflow.index', $datos))
                ->assertRedirect(route('web.cashflow.index'))->assertSessionHasErrors($campo);
        }
    }

    public function test_paginacion_conserva_el_periodo_y_sus_totales(): void
    {
        for ($i = 0; $i < 31; $i++) $this->movimiento('2026-10-08', 10);
        $this->movimiento('2026-10-12', 5000);
        $datos = ['periodo' => 'semana', 'fecha' => '2026-10-08', 'tipo_caja_id' => $this->caja->id];
        $r = $this->get(route('web.cashflow.index', $datos))->assertOk();
        $this->assertSame(31, $r->viewData('movimientos')->total());
        $this->assertEquals(310, $r->viewData('totalIngresos'));
        $siguiente = $r->viewData('movimientos')->nextPageUrl();
        $this->assertStringContainsString('periodo=semana', $siguiente);
        $this->assertStringContainsString('fecha=2026-10-08', $siguiente);
        $r = $this->get($siguiente)->assertOk();
        $this->assertCount(1, $r->viewData('movimientos')->items());
        $this->assertEquals(310, $r->viewData('totalIngresos'));
    }
}
