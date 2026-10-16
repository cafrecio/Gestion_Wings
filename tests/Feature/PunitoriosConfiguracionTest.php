<?php

namespace Tests\Feature;

use App\Models\{Alumno, CargoAlumno, Configuracion, Deporte, DeudaCuota, Grupo, Nivel, Rubro, Subrubro, TipoCaja, User};
use App\Services\PunitorioService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PunitoriosConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-11 09:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_pantalla_guarda_ambas_claves_y_cambia_el_motor_con_12_coma_5(): void
    {
        $this->get(route('web.configuraciones.index'))->assertOk()
            ->assertSee('cfg-mora_dia_desde')->assertSee('cfg-mora_porcentaje');
        $deporte = Deporte::firstOrFail();
        $nivel = Nivel::firstOrFail();
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $alumno = Alumno::create(['nombre' => 'Prueba', 'apellido' => 'Configuración', 'dni' => '48000001',
            'fecha_nacimiento' => '2005-01-01', 'fecha_alta' => '2025-01-01', 'celular' => '11-4000-0000',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 10000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $this->patchJson(route('web.configuraciones.update', 'mora_porcentaje'), ['valor' => '12,5'])
            ->assertOk()->assertJsonPath('valor', '12.50');
        $this->assertSame('12.50', Configuracion::get('mora_porcentaje'));
        $this->patchJson(route('web.configuraciones.update', 'mora_dia_desde'), ['valor' => 15])->assertOk();
        $this->assertSame(0, app(PunitorioService::class)->aplicar());
        $this->patchJson(route('web.configuraciones.update', 'mora_dia_desde'), ['valor' => 10])->assertOk();
        $this->assertSame(1, app(PunitorioService::class)->aplicar());
        $this->assertSame('1250.00', CargoAlumno::where('tipo', 'PUNITORIO')->sole()->monto_original);
    }

    public function test_porcentajes_invalidos_y_dias_invalidos_se_rechazan_en_castellano_sin_guardar(): void
    {
        foreach (['-1', '100.01', '12,555', '1.2.3', '1e1', [], 'texto'] as $valor) {
            $this->patchJson(route('web.configuraciones.update', 'mora_porcentaje'), compact('valor'))
                ->assertUnprocessable()->assertJsonValidationErrors('valor');
            $this->assertSame('0', Configuracion::get('mora_porcentaje'));
        }
        foreach ([0, 29, '2.5', 'texto'] as $valor) {
            $respuesta = $this->patchJson(route('web.configuraciones.update', 'mora_dia_desde'), compact('valor'))
                ->assertUnprocessable()->assertJsonValidationErrors('valor');
            $this->assertStringContainsString('del 1 al 28', $respuesta->json('errors.valor.0'));
            $this->assertSame(10, Configuracion::get('mora_dia_desde'));
        }
        foreach (['0', '100', '12.50'] as $valor) {
            $this->patchJson(route('web.configuraciones.update', 'mora_porcentaje'), compact('valor'))->assertOk();
        }
    }

    public function test_seed_repetido_no_reinicia_configuracion_y_conserva_catalogo_unico(): void
    {
        Configuracion::set('mora_porcentaje', '12.50');
        Configuracion::set('mora_dia_desde', 15);
        $this->seed(CatalogosSeeder::class);
        $this->assertSame('12.50', Configuracion::get('mora_porcentaje'));
        $this->assertSame(15, Configuracion::get('mora_dia_desde'));
        $this->assertSame(1, Rubro::where('nombre', 'Punitorios')->count());
        $this->assertSame(1, Subrubro::where('nombre', 'Punitorio Cuota')->count());
    }

    public function test_operativo_no_puede_cambiar_configuracion_de_mora(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]));
        $this->patchJson(route('web.configuraciones.update', 'mora_porcentaje'), ['valor' => 5])->assertForbidden();
        $this->patchJson(route('web.configuraciones.update', 'mora_dia_desde'), ['valor' => 15])->assertForbidden();
        $this->assertSame('0', Configuracion::get('mora_porcentaje'));
        $this->assertSame(10, Configuracion::get('mora_dia_desde'));
    }

    public function test_rubro_reservado_no_admite_subrubro_edicion_borrado_ni_desactivacion(): void
    {
        $sub = Subrubro::where('nombre', 'Punitorio Cuota')->sole();
        $rubro = Rubro::findOrFail($sub->rubro_id);
        $antesSub = $sub->getAttributes();
        $antesRubro = $rubro->getAttributes();
        $parametros = ['rubroId' => $rubro->id, 'id' => $sub->id];
        $this->post(route('web.subrubros.store', $rubro->id), ['nombre' => 'Otro recargo', 'permitido_para' => 'OPERATIVO'])
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'administrado por el sistema'));
        $this->delete(route('web.rubros.destroy', $rubro->id))->assertSessionHas('error');
        $this->put(route('web.subrubros.update', $parametros), ['nombre' => 'Alterado', 'permitido_para' => 'ADMIN'])->assertSessionHas('error');
        $this->patch(route('web.subrubros.toggle-activo', $parametros))->assertSessionHas('error');
        $this->assertSame($antesSub, $sub->fresh()->getAttributes());
        $this->assertSame($antesRubro, $rubro->fresh()->getAttributes());
        $this->assertDatabaseMissing('subrubros', ['nombre' => 'Otro recargo']);
    }

    public function test_recargo_no_aparece_en_movimientos_manuales_ni_se_admite_por_post_directo(): void
    {
        $sub = Subrubro::where('nombre', 'Punitorio Cuota')->sole();
        $tipo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->get(route('web.cashflow.movimiento'))->assertOk()->assertDontSee('Punitorio Cuota');
        $operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        \Tests\Support\CajaDeclarada::crear($operativo->id, $tipo->id);
        $this->actingAs($operativo)->get(route('web.caja.movimiento'))->assertOk()->assertDontSee('Punitorio Cuota');
        $this->post(route('web.caja.movimiento.store'), ['tipo_caja_id' => $tipo->id,
            'subrubro_id' => $sub->id, 'monto' => 500, 'fecha' => '2026-10-11'])->assertSessionHas('error');
        $this->assertDatabaseMissing('movimientos_operativos', ['subrubro_id' => $sub->id]);
    }
}
