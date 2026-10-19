<?php

namespace Tests\Feature;

use App\Models\{Clase, Deporte, Grupo, Liquidacion, LiquidacionDetalle, Nivel, Profesor, User};
use App\Services\LiquidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaseProfesoraObligatoriaT66Test extends TestCase
{
    use RefreshDatabase;

    protected Grupo $grupo;
    protected Profesor $profesora;
    protected Profesor $suplente;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(12, 0));
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($this->admin);
        $deporte = Deporte::create(['nombre' => 'Patín T66', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial T66']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $base = ['deporte_id' => $deporte->id, 'apellido' => 'Prueba T66', 'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true];
        $this->profesora = Profesor::create($base + ['nombre' => 'Lucía', 'dni' => '49006601']);
        $this->suplente = Profesor::create($base + ['nombre' => 'María', 'dni' => '49006602']);
    }

    protected function datos(): array
    {
        return ['grupo_id' => $this->grupo->id, 'fecha' => '2026-10-20', 'hora_inicio' => '17:00',
            'hora_fin' => '18:00', 'profesores' => [$this->profesora->id]];
    }

    protected function clase(string $fecha = '2026-10-20'): Clase
    {
        $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => $fecha,
            'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'validada_para_liquidacion' => true]);
        $clase->profesores()->attach($this->profesora);
        return $clase;
    }

    public function test_alta_unica_rechaza_ausencia_vacio_null_y_formato_invalido(): void
    {
        foreach ([null, [], 'no-es-lista'] as $valor) {
            $this->postJson('/clases', array_replace($this->datos(), ['profesores' => $valor]))
                ->assertUnprocessable()->assertJsonValidationErrors('profesores');
            $this->assertDatabaseCount('clases', 0);
            $this->assertDatabaseCount('clase_profesor', 0);
        }
        $datos = $this->datos();
        unset($datos['profesores']);
        $this->postJson('/clases', $datos)->assertUnprocessable()->assertJsonValidationErrors('profesores');
        $this->assertDatabaseCount('clases', 0);
    }

    public function test_serie_sin_profesora_no_crea_ninguna_clase(): void
    {
        $this->postJson('/clases', $this->serie([]))->assertUnprocessable()->assertJsonValidationErrors('profesores');
        $this->assertDatabaseCount('clases', 0);
        $this->assertDatabaseCount('clase_profesor', 0);
    }

    protected function serie(array $ids): array
    {
        return ['tipo_creacion' => 'recurrente', 'grupo_id' => $this->grupo->id, 'fecha_desde' => '2026-10-20',
            'fecha_hasta' => '2026-10-27', 'dias_semana' => [2], 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'profesores' => $ids];
    }

    public function test_una_profesora_valida_permite_alta_unica_y_serie_completa(): void
    {
        $this->post('/clases', array_replace($this->datos(), ['fecha' => '2026-10-21']))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->post('/clases', $this->serie([$this->profesora->id]))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseCount('clases', 3);
        $this->assertDatabaseCount('clase_profesor', 3);
        $this->assertSame(0, Clase::doesntHave('profesores')->count());
    }

    public function test_profesora_inactiva_ajena_o_inexistente_no_cuenta_en_ninguna_entrada(): void
    {
        $otra = Deporte::create(['nombre' => 'Otro T66', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->suplente->update(['deporte_id' => $otra->id]);
        $clase = $this->clase();
        $this->profesora->update(['activo' => false]);
        foreach ([$this->profesora->id, $this->suplente->id, 999999] as $id) {
            $datos = array_replace($this->datos(), ['profesores' => [$id]]);
            $this->postJson('/clases', $datos)->assertUnprocessable();
            $this->postJson('/clases', $this->serie([$id]))->assertUnprocessable();
            $this->putJson('/clases/'.$clase->id, $datos)->assertUnprocessable();
            $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$id]])->assertUnprocessable();
            $this->assertSame([$this->profesora->id], $clase->profesores()->pluck('profesores.id')->all());
            $this->assertDatabaseCount('clases', 1);
        }
    }

    public function test_edicion_y_reasignacion_no_permiten_quitar_todas_ni_escribir_parcialmente(): void
    {
        $clase = $this->clase();
        $antes = $clase->fresh()->getAttributes();
        $this->putJson('/clases/'.$clase->id, array_replace($this->datos(), ['hora_inicio' => '16:00', 'profesores' => []]))
            ->assertUnprocessable()->assertJsonValidationErrors('profesores');
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => []])->assertUnprocessable()->assertJsonValidationErrors('profesores');
        $this->assertEquals($antes, $clase->fresh()->getAttributes());
        $this->assertSame([$this->profesora->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_reemplazar_por_una_valida_y_guardar_sin_cambios_sigue_permitido(): void
    {
        $clase = $this->clase();
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$this->suplente->id]])->assertOk()->assertJsonPath('success', true);
        $this->put('/clases/'.$clase->id, array_replace($this->datos(), ['profesores' => [$this->suplente->id]]))->assertSessionHasNoErrors();
        $this->assertSame([$this->suplente->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_omitir_profesoras_en_edicion_y_reasignacion_tambien_se_rechaza(): void
    {
        $clase = $this->clase();
        $datos = $this->datos();
        unset($datos['profesores']);
        $this->putJson('/clases/'.$clase->id, $datos)->assertUnprocessable()->assertJsonValidationErrors('profesores');
        $this->patchJson('/clases/'.$clase->id.'/profesores', [])->assertUnprocessable()->assertJsonValidationErrors('profesores');
        $this->assertSame([$this->profesora->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_primera_asignacion_a_pasada_historica_sin_profesora_no_exige_motivo(): void
    {
        $clase = $this->clase('2026-09-10');
        $clase->profesores()->detach();
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$this->profesora->id]])->assertOk();
        $this->assertNull($clase->fresh()->motivo_cambio_profesor);
        $this->assertSame([$this->profesora->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_serie_con_solape_posterior_revierte_todas_las_clases(): void
    {
        $this->clase('2026-10-27');
        $this->post('/clases', $this->serie([$this->profesora->id]))->assertSessionHas('error');
        $this->assertDatabaseCount('clases', 1);
        $this->assertDatabaseCount('clase_profesor', 1);
    }

    public function test_cambio_pasado_exige_motivo_y_lo_guarda(): void
    {
        $clase = $this->clase('2026-09-10');
        $url = '/clases/'.$clase->id.'/profesores';
        $this->patchJson($url, ['profesores' => [$this->suplente->id]])->assertUnprocessable();
        $this->patchJson($url, ['profesores' => [$this->suplente->id], 'motivo' => 'Suplencia real'])->assertOk();
        $this->assertSame('Suplencia real', $clase->fresh()->motivo_cambio_profesor);
    }

    public function test_cancelada_no_admite_reasignacion_ni_edicion_que_cambie_profesoras(): void
    {
        $clase = $this->clase();
        $clase->update(['cancelada' => true]);
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$this->suplente->id]])->assertUnprocessable();
        $this->putJson('/clases/'.$clase->id, array_replace($this->datos(), ['profesores' => [$this->suplente->id]]))->assertUnprocessable();
        $this->assertTrue($clase->fresh()->cancelada);
        $this->assertSame([$this->profesora->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_liquidacion_cerrada_bloquea_y_cancelarla_permite_conservar_historial(): void
    {
        $clase = $this->clase('2026-09-10');
        $servicio = app(LiquidacionService::class);
        $liq = $servicio->generarLiquidacionMensual($this->profesora->id, 9, 2026);
        $servicio->cerrarLiquidacion($liq->id);
        $antes = $liq->fresh()->getAttributes();
        $detalles = $liq->detalles()->get()->toArray();
        $datos = ['profesores' => [$this->suplente->id], 'motivo' => 'Suplencia real', 'confirmar' => true];
        $this->patchJson('/clases/'.$clase->id.'/profesores', $datos)->assertUnprocessable()->assertJsonValidationErrors('profesores');
        $this->assertEquals($antes, $liq->fresh()->getAttributes());
        $this->assertEquals($detalles, $liq->detalles()->get()->toArray());
        $this->assertSame([$this->profesora->id], $clase->profesores()->pluck('profesores.id')->all());
        $servicio->cancelarLiquidacion($liq->id, 'Revisar profesoras', $this->admin->id);
        $this->patchJson('/clases/'.$clase->id.'/profesores', $datos)->assertOk();
        $this->assertSame([$this->suplente->id], $clase->profesores()->pluck('profesores.id')->all());
        $this->assertEquals($detalles, $liq->detalles()->get()->toArray());
    }

    public function test_pagada_no_permite_forzar_el_cambio_con_confirmacion(): void
    {
        $clase = $this->clase('2026-09-10');
        $servicio = app(LiquidacionService::class);
        $liq = $servicio->generarLiquidacionMensual($this->profesora->id, 9, 2026);
        $servicio->cerrarLiquidacion($liq->id);
        // Fixture PAGADA; no ejecuta ni afirma un pago de sueldo real.
        $liq->fresh()->update(['estado_pago' => Liquidacion::ESTADO_PAGO_PAGADA]);
        $antes = $liq->fresh()->getAttributes();
        $detalles = $liq->detalles()->get()->toArray();
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$this->suplente->id], 'motivo' => 'Suplencia', 'confirmar' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('profesores');
        $this->assertEquals($antes, $liq->fresh()->getAttributes());
        $this->assertEquals($detalles, $liq->detalles()->get()->toArray());
        $this->assertSame([$this->profesora->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_comision_cerrada_bloquea_aunque_no_haya_detalle_directo_de_clase(): void
    {
        $clase = $this->clase('2026-09-10');
        $liq = Liquidacion::create(['profesor_id' => $this->profesora->id, 'mes' => 9, 'anio' => 2026,
            'tipo' => 'COMISION', 'estado' => 'CERRADA', 'estado_pago' => 'PENDIENTE', 'total_calculado' => 1000]);
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$this->suplente->id], 'motivo' => 'Suplencia'])
            ->assertUnprocessable();
        $this->assertSame([$this->profesora->id], $clase->profesores()->pluck('profesores.id')->all());
        $this->assertSame('1000.00', $liq->fresh()->total_calculado);
    }

    public function test_permisos_conservan_operativo_hoy_y_profesor_sin_reasignar(): void
    {
        $clase = $this->clase();
        $this->actingAs(User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]));
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$this->suplente->id]])->assertOk();
        $this->postJson('/clases', $this->datos())->assertForbidden();
        $clase->update(['fecha' => '2026-09-10']);
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$this->profesora->id], 'motivo' => 'Suplencia'])->assertForbidden();
        $this->actingAs(User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]));
        $this->patchJson('/clases/'.$clase->id.'/profesores', ['profesores' => [$this->profesora->id]])->assertForbidden();
    }

    public function test_formularios_indican_minimo_y_error_conserva_los_datos(): void
    {
        $this->from('/clases/create')->post('/clases', array_replace($this->datos(), ['profesores' => []]))
            ->assertSessionHasErrors('profesores')->assertSessionHasInput('grupo_id', $this->grupo->id);
        $this->get('/clases/create')->assertOk()->assertSee('Seleccioná al menos una profesora activa del deporte.')
            ->assertDontSee('(opcional)')->assertSee('value="17:00"', false);
        $clase = $this->clase();
        $this->get('/clases/'.$clase->id.'/edit')->assertOk()->assertSee('Seleccioná al menos una profesora activa del deporte.')->assertDontSee('(opcional)');
        $this->get('/clases/'.$clase->id)->assertOk()->assertSee('Seleccioná al menos una profesora activa del deporte.');
    }
}
