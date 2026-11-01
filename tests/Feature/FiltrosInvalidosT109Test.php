<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\Profesor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T109: escribir basura en la dirección no da error del servidor: el filtro se ignora.
 * Un recibo que no existe muestra la pantalla común, no texto técnico.
 */
class FiltrosInvalidosT109Test extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $operativa;
    private User $profesora;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->duenio = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->profesora = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);
    }

    private function assertAbre(User $quien, string $ruta, array $parametros): void
    {
        $url = route($ruta).'?'.http_build_query($parametros);
        $codigo = $this->actingAs($quien)->get($url)->getStatusCode();
        $this->assertLessThan(500, $codigo, "{$quien->rol} {$url} dio {$codigo}");
    }

    public function test_fechas_invalidas_en_historial_no_dan_error(): void
    {
        foreach (['desde', 'hasta'] as $filtro) {
            foreach (['abc', '9999-99-99', "1' OR '1'='1", '0', '-1', '2026-02-31', ['x']] as $valor) {
                $this->assertAbre($this->duenio, 'web.caja.historial', [$filtro => $valor]);
                $this->assertAbre($this->operativa, 'web.caja.historial', [$filtro => $valor]);
            }
        }
    }

    public function test_una_lista_en_vez_de_un_texto_no_da_error(): void
    {
        $casos = [
            ['web.caja.historial', 'search'], ['web.movimientos.index', 'desde'], ['web.movimientos.index', 'hasta'],
            ['web.cobranza.index', 'estado'], ['web.clases.index', 'fecha'], ['web.alumnos.index', 'search'],
            ['web.alumnos.autocomplete', 'q'], ['web.caja.cobrar-cuota', 'search'],
        ];
        foreach ($casos as [$ruta, $filtro]) {
            $this->assertAbre($this->duenio, $ruta, [$filtro => ['x']]);
            $this->assertAbre($this->operativa, $ruta, [$filtro => ['x']]);
        }
        $this->assertAbre($this->duenio, 'web.profesores.index', ['search' => ['x']]);
    }

    public function test_un_mes_invalido_en_caja_muestra_el_mes_actual(): void
    {
        foreach (['abc', "1' OR '1'='1", '0', '2026-13', '-1', ['x']] as $valor) {
            $url = route('web.caja.index').'?'.http_build_query(['mes' => $valor]);
            $this->actingAs($this->duenio)->get($url)->assertOk()->assertViewHas('mes', now()->format('Y-m'));
        }
    }

    public function test_un_mes_valido_en_caja_se_sigue_respetando(): void
    {
        $viejo = CajaOperativa::create(['usuario_operativo_id' => $this->operativa->id, 'apertura_at' => '2026-03-10 09:00:00', 'estado' => 'CERRADA']);

        $this->actingAs($this->duenio)->get(route('web.caja.index', ['mes' => '2026-03']))->assertOk()
            ->assertViewHas('mes', '2026-03')
            ->assertViewHas('cajas', fn ($cajas) => $cajas->pluck('id')->all() === [$viejo->id]);
    }

    public function test_las_fechas_validas_siguen_filtrando(): void
    {
        $this->actingAs($this->duenio)->get(route('web.caja.historial', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
            ->assertOk()->assertSee('value="2026-03-01"', false)->assertSee('value="2026-03-31"', false);
    }

    public function test_el_campo_del_filtro_ignorado_queda_vacio(): void
    {
        $this->actingAs($this->duenio)->get(route('web.caja.historial', ['desde' => 'abc']))
            ->assertOk()->assertDontSee('value="abc"', false);
    }

    public function test_un_texto_valido_en_los_filtros_no_se_toca(): void
    {
        $this->actingAs($this->duenio)->get(route('web.alumnos.index', ['search' => 'Clara']))
            ->assertOk()->assertSee('value="Clara"', false);
    }

    public function test_recibo_de_cuota_inexistente_muestra_la_pantalla_comun(): void
    {
        foreach ([$this->duenio, $this->operativa] as $quien) {
            $this->actingAs($quien)->get(route('web.recibos.cuota', 99999))
                ->assertNotFound()->assertSee('Página no disponible')->assertDontSee('Pago no encontrado');
        }
    }

    public function test_la_profesora_recibe_lo_mismo_exista_o_no_el_recibo(): void
    {
        $this->actingAs($this->profesora)->get(route('web.recibos.cuota', 99999))->assertForbidden();
    }

    public function test_recibo_de_liquidacion_inexistente_muestra_la_pantalla_comun(): void
    {
        $this->actingAs($this->duenio)->get(route('web.recibos.liquidacion', 99999))
            ->assertNotFound()->assertSee('Página no disponible')->assertDontSee('Liquidación no encontrada');

    }

    public function test_recibo_de_liquidacion_sin_pagar_muestra_la_pantalla_comun(): void
    {
        $deporte = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $profesor = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Ana', 'apellido' => 'Paz', 'dni' => '20123456', 'fecha_nacimiento' => '1985-05-15', 'direccion' => 'Calle 1', 'localidad' => 'Rosario', 'telefono' => '1122334455', 'valor_hora' => 10000, 'activo' => true]);
        $sinPagar = Liquidacion::create(['profesor_id' => $profesor->id, 'mes' => 9, 'anio' => 2026, 'tipo' => Liquidacion::TIPO_HORA, 'total_calculado' => 50000, 'estado' => Liquidacion::ESTADO_CERRADA]);

        $this->actingAs($this->duenio)->get(route('web.recibos.liquidacion', $sinPagar->id))
            ->assertNotFound()->assertSee('Página no disponible');
    }

    public function test_recibo_con_texto_en_la_direccion_muestra_la_pantalla_comun(): void
    {
        $this->actingAs($this->duenio)->get('/recibos/cuota/abc')->assertNotFound()->assertSee('Página no disponible');
        $this->actingAs($this->duenio)->get('/recibos/liquidacion/abc')->assertNotFound()->assertSee('Página no disponible');
    }
}
