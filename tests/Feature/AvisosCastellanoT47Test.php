<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\TipoCaja;
use App\Models\User;
use App\Support\AvisoError;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * T47 (DU-02, ACC-014): lo que ve la persona cuando usa mal una dirección de acción.
 *
 * Antes: abrir a mano la dirección de una acción mostraba la página técnica en inglés
 * («405 Method Not Allowed»), y condonar o reactivar algo que no existe decía
 * «No query results for model [App\Models\CargoAlumno]».
 */
class AvisosCastellanoT47Test extends TestCase
{
    use RefreshDatabase;

    private const MOTIVO = 'Motivo suficientemente largo para pasar la validación';

    private User $admin;
    private User $operativo;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
    }

    public function test_abrir_a_mano_la_direccion_de_una_accion_muestra_la_pagina_en_castellano(): void
    {
        $direcciones = ['/cajas/1/validar', '/pagos/1/anular', '/deudas/1/condonar', '/deudas/1/reactivar',
            '/inscripciones/1/condonar', '/inscripciones/1/reactivar'];

        foreach ([[$this->admin, route('admin.dashboard')], [$this->operativo, route('web.operativo.dashboard')]] as [$usuario, $inicio]) {
            foreach ($direcciones as $direccion) {
                $this->actingAs($usuario)->get($direccion)
                    ->assertStatus(405)
                    ->assertSee('Esta dirección no se abre a mano')
                    ->assertSee('No se hizo ningún cambio')
                    ->assertSee('href="' . $inicio . '"', false)
                    ->assertDontSee('Method Not Allowed')
                    ->assertDontSee('MethodNotAllowed');
            }
        }
    }

    public function test_enviar_a_una_direccion_que_solo_se_abre_tambien_avisa_en_castellano(): void
    {
        $this->actingAs($this->operativo)->post('/cobranza')
            ->assertStatus(405)
            ->assertSee('Esta dirección no se abre a mano')
            ->assertDontSee('Method Not Allowed');

        // Una dirección que no existe con ningún método sigue siendo «no disponible».
        $this->actingAs($this->operativo)->get('/cajas/1/inventada-t47')->assertNotFound()->assertSee('Página no disponible');
    }

    public function test_la_accion_no_se_ejecuta_y_la_caja_queda_como_estaba(): void
    {
        $caja = \Tests\Support\CajaDeclarada::crear($this->operativo->id, TipoCaja::firstOrFail()->id);
        $antes = $caja->fresh()->getAttributes();

        $this->actingAs($this->admin)->get("/cajas/{$caja->id}/validar")->assertStatus(405);

        $this->assertSame($antes, CajaOperativa::findOrFail($caja->id)->getAttributes());
    }

    public function test_un_pedido_json_con_el_metodo_equivocado_sigue_recibiendo_json(): void
    {
        $this->actingAs($this->admin)->getJson('/cajas/1/validar')
            ->assertStatus(405)
            ->assertHeader('content-type', 'application/json');
    }

    public function test_condonar_o_reactivar_algo_que_no_existe_avisa_en_castellano(): void
    {
        $rutas = ['web.deudas.condonar', 'web.deudas.reactivar', 'web.inscripciones.condonar',
            'web.inscripciones.reactivar', 'web.punitorios.condonar', 'web.punitorios.reactivar'];

        foreach ($rutas as $ruta) {
            $respuesta = $this->actingAs($this->admin)->from('/alumnos')
                ->post(route($ruta, 999999), ['motivo' => self::MOTIVO]);

            $respuesta->assertRedirect('/alumnos')->assertSessionHas('error', AvisoError::REGISTRO_INEXISTENTE);
            $this->assertStringNotContainsString('No query results', session('error'), $ruta);
            $this->assertStringNotContainsString('App\\Models', session('error'), $ruta);
        }
    }

    public function test_un_fallo_de_base_no_muestra_la_consulta_y_queda_en_el_log(): void
    {
        Log::spy();
        $fallo = new QueryException('mysql', 'select * from cajas_operativas where secreto = ?', [1], new \PDOException('SQLSTATE[42S22]'));

        $this->assertSame(AvisoError::FALLO_TECNICO, AvisoError::texto($fallo));
        Log::shouldHaveReceived('error')->once();
    }

    public function test_los_avisos_del_negocio_se_muestran_como_estaban(): void
    {
        $this->assertSame('Tenés una caja abierta de un día anterior.', AvisoError::texto(new \Exception('Tenés una caja abierta de un día anterior.')));
        $this->assertSame('Medio no válido.', AvisoError::texto(new \DomainException('Medio no válido.')));
    }
}
