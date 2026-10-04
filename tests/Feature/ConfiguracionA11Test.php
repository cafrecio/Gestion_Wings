<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracionA11Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]));
    }

    public function test_configuracion_explica_los_valores_y_los_agrupa_por_trabajo_del_club(): void
    {
        $this->get(route('web.configuraciones.index'))
            ->assertOk()
            ->assertSee('La plata')
            ->assertSee('La cobranza')
            ->assertSee('Los avisos')
            ->assertSee('Inscripción al club')
            ->assertSee('Días de gracia para pagar')
            ->assertSee('Hasta este día del mes, el que no pagó todavía no es moroso.')
            ->assertSee('Correo para los avisos del club')
            ->assertSee('Telegram para los avisos del club');
    }

    public function test_inscripcion_rechaza_importes_invalidos_sin_cambiar_el_precio_vigente(): void
    {
        foreach ([-100, 'texto', 0, '', '10.123'] as $valor) {
            $this->patchJson(route('web.configuraciones.update', 'inscripcion_importe'), ['valor' => $valor])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('valor');
            $this->assertSame('5000.00', Configuracion::where('clave', 'inscripcion_importe')->firstOrFail()->valor);
        }
        $this->patchJson(route('web.configuraciones.update', 'inscripcion_importe'), ['valor' => -100])
            ->assertJsonPath('errors.valor.0', 'El importe de la inscripción debe ser mayor que cero.');
    }

    public function test_dias_de_gracia_rechazan_valores_fuera_del_mes_y_no_se_guardan(): void
    {
        foreach ([0, 29, 31, 'texto', '2.5'] as $valor) {
            $this->patchJson(route('web.configuraciones.update', 'dias_gracia_cobranza'), ['valor' => $valor])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('valor');
            $this->assertSame('10', Configuracion::where('clave', 'dias_gracia_cobranza')->firstOrFail()->valor);
        }
    }

    public function test_un_correo_invalido_no_reemplaza_el_destino_de_los_avisos(): void
    {
        Configuracion::set('avisos_email', 'admin@club.test');
        $this->patchJson(route('web.configuraciones.update', 'avisos_email'), ['valor' => 'esto no es un correo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valor')
            ->assertJsonPath('errors.valor.0', 'Ingresá un correo válido, por ejemplo administracion@club.com.');
        $this->assertSame('admin@club.test', Configuracion::get('avisos_email'));
    }

    public function test_destinos_vacios_conservan_el_comportamiento_del_club_y_no_se_rechazan(): void
    {
        foreach (['avisos_email', 'avisos_telegram_chat_id'] as $clave) {
            Configuracion::set($clave, '12345');
            $this->patchJson(route('web.configuraciones.update', $clave), ['valor' => ''])
                ->assertOk()
                ->assertJsonPath('valor', '');
            $this->assertSame('', Configuracion::get($clave));
        }
    }

    public function test_los_valores_validos_se_guardan_y_el_servidor_confirma_el_resultado(): void
    {
        foreach ([
            ['dias_gracia_cobranza', 1, '1'],
            ['dias_gracia_cobranza', 28, '28'],
            ['inscripcion_importe', '7250.50', '7250.50'],
            ['avisos_email', 'admin@club.test', 'admin@club.test'],
            ['avisos_telegram_chat_id', '-1001234567890', '-1001234567890'],
        ] as [$clave, $valor, $guardado]) {
            $this->patchJson(route('web.configuraciones.update', $clave), ['valor' => $valor])
                ->assertOk()
                ->assertJsonPath('ok', true)
                ->assertJsonPath('valor', $guardado);
            $this->assertSame($guardado, Configuracion::where('clave', $clave)->firstOrFail()->valor);
        }
    }

    public function test_el_dia_fijo_de_generacion_no_se_presenta_editable_y_rechaza_escrituras(): void
    {
        $this->get(route('web.configuraciones.index'))
            ->assertOk()
            ->assertSee('1 · Fijo')
            ->assertDontSee('id="cfg-dia_generacion_deuda"', false);

        $this->patchJson(route('web.configuraciones.update', 'dia_generacion_deuda'), ['valor' => 20])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valor');
        $this->assertSame('1', Configuracion::get('dia_generacion_deuda').'');
    }

    public function test_un_error_sin_javascript_se_ve_arriba_y_junto_al_campo_con_el_valor_ingresado(): void
    {
        $this->from(route('web.configuraciones.index'))
            ->patch(route('web.configuraciones.update', 'dias_gracia_cobranza'), ['valor' => 0])
            ->assertRedirect(route('web.configuraciones.index'))
            ->assertSessionHasErrors('valor');

        $this->get(route('web.configuraciones.index'))
            ->assertOk()
            ->assertSee('No se guardó')
            ->assertSee('id="configuracion-error-resumen"', false)
            ->assertSee('id="error-dias_gracia_cobranza"', false)
            ->assertSee('value="0"', false);
        $this->assertSame(10, Configuracion::get('dias_gracia_cobranza'));
    }
}
