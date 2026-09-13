<?php
namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, Clase, Deporte, Grupo, Nivel, Profesor, User, Liquidacion, LiquidacionDetalle};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditarClaseTest extends TestCase
{
    use RefreshDatabase;
    private Grupo $grupo;
    private Profesor $profesor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 13)->setTime(12, 0));
        $this->actingAs(User::factory()->create(['rol' => 'ADMIN', 'activo' => true]));
        $deporte = Deporte::create(['nombre' => 'FIN10', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'FIN10']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->profesor = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Prueba', 'apellido' => 'FIN10',
            'dni' => '30999111', 'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true]);
    }

    private function clase(string $fecha = '2026-09-13', string $inicio = '10:00', string $fin = '11:00'): Clase
    {
        $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => $fecha, 'hora_inicio' => $inicio, 'hora_fin' => $fin]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    private function editar(Clase $clase, array $datos = [])
    {
        return $this->from('/clases/'.$clase->id.'/edit')->put('/clases/'.$clase->id, array_replace([
            'fecha' => $clase->fecha->format('Y-m-d'), 'hora_inicio' => $clase->hora_inicio->format('H:i'),
            'hora_fin' => $clase->hora_fin->format('H:i'), 'profesores' => [$this->profesor->id],
        ], $datos));
    }

    public function test_fecha_pasada_no_se_mueve(): void
    {
        $clase = $this->clase('2026-09-12');
        $this->editar($clase, ['fecha' => '2026-09-13'])->assertSessionHasErrors('fecha');
        $this->assertSame('2026-09-12', $clase->fresh()->fecha->format('Y-m-d'));
    }

    public function test_futura_no_se_mueve_al_pasado(): void
    {
        $clase = $this->clase('2026-09-14');
        $this->editar($clase, ['fecha' => '2026-09-12'])->assertSessionHasErrors('fecha');
        $this->assertSame('2026-09-14', $clase->fresh()->fecha->format('Y-m-d'));
    }

    public function test_horario_pasado_exige_y_guarda_motivo_sin_asistencia(): void
    {
        $clase = $this->clase('2026-09-12');
        $datos = ['hora_inicio' => '12:00', 'hora_fin' => '13:00'];
        $this->editar($clase, $datos)->assertSessionHasErrors('motivo');
        $this->assertSame('10:00', $clase->fresh()->hora_inicio->format('H:i'));
        $this->editar($clase, $datos + ['motivo' => 'Corrección real'])->assertSessionHasNoErrors();
        $this->assertSame('Corrección real', $clase->fresh()->motivo_cambio_horario);
        $this->assertSame('12:00', $clase->fresh()->hora_inicio->format('H:i'));
    }

    public function test_profesor_existente_bloquea_solape_y_revierte_fecha_y_lista(): void
    {
        $clase = $this->clase();
        $this->clase('2026-09-14', '12:00', '13:00');
        $antes = $clase->fresh()->getAttributes();
        $this->editar($clase, ['fecha' => '2026-09-14', 'hora_inicio' => '12:00', 'hora_fin' => '13:00'])
            ->assertSessionHasErrors('profesores');
        $this->assertEquals($antes, $clase->fresh()->getAttributes());
        $this->assertSame([$this->profesor->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_presentes_se_validan_hoy_y_pasado_con_rollback_completo(): void
    {
        $alumno = Alumno::create(['nombre' => 'Prueba', 'apellido' => 'FIN10', 'dni' => '30999222',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111', 'fecha_alta' => '2026-01-01',
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        foreach (['2026-09-12', '2026-09-13'] as $fecha) {
            $clase = $this->clase($fecha);
            $otra = $this->clase($fecha, '12:00', '13:00');
            $otra->profesores()->detach();
            foreach ([$clase, $otra] as $c) {
                Asistencia::create(['clase_id' => $c->id, 'alumno_id' => $alumno->id, 'presente' => true]);
            }
            $antes = $clase->fresh()->getAttributes();
            $this->editar($clase, ['hora_inicio' => '12:00', 'hora_fin' => '13:00', 'motivo' => 'Corrección', 'profesores' => []])
                ->assertSessionHasErrors('hora_inicio');
            $this->assertEquals($antes, $clase->fresh()->getAttributes());
            $this->assertSame([$this->profesor->id], $clase->profesores()->pluck('profesores.id')->all());
        }
    }

    public function test_liquidacion_cerrada_bloquea_abierta_permite(): void
    {
        $clase = $this->clase('2026-09-12');
        $clase->update(['validada_para_liquidacion' => true]);
        $liq = app(\App\Services\LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $datos = ['hora_inicio' => '12:00', 'hora_fin' => '13:00', 'motivo' => 'Corrección'];
        $this->editar($clase, $datos)->assertSessionHasNoErrors();
        $liq->update(['estado' => Liquidacion::ESTADO_CERRADA]);
        $this->editar($clase->fresh(), ['hora_inicio' => '14:00', 'hora_fin' => '15:00', 'motivo' => 'Otra'])
            ->assertSessionHasErrors('hora_inicio');
        $this->assertSame('12:00', $clase->fresh()->hora_inicio->format('H:i'));
        $this->editar($clase->fresh())->assertSessionHasNoErrors();
    }

    public function test_pasada_ignora_profesores_y_guardar_sin_cambios_no_pide_motivo(): void
    {
        $clase = $this->clase('2026-09-12');
        $this->editar($clase, ['profesores' => [999999]])->assertSessionHasNoErrors();
        $this->assertSame([$this->profesor->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_hoy_permite_contiguas_sin_autosolape_y_quitar_todos(): void
    {
        $clase = $this->clase();
        $this->clase('2026-09-13', '12:00', '13:00');
        $this->editar($clase)->assertSessionHasNoErrors();
        $this->editar($clase, ['hora_inicio' => '11:00', 'hora_fin' => '12:00'])->assertSessionHasNoErrors();
        $this->editar($clase->fresh(), ['profesores' => []])->assertSessionHasNoErrors();
        $this->assertCount(0, $clase->profesores()->get());
    }

    public function test_profesor_agregado_se_valida_sin_mover_horario(): void
    {
        $clase = $this->clase();
        $clase->profesores()->detach();
        $this->clase();
        $this->editar($clase)->assertSessionHasErrors('profesores');
        $this->assertCount(0, $clase->profesores()->get());
    }

    public function test_hoy_permite_fecha_futura_y_nuevo_profesor_sin_motivo(): void
    {
        $clase = $this->clase();
        $clase->profesores()->detach();
        $this->editar($clase, ['fecha' => '2026-09-14', 'hora_inicio' => '15:00', 'hora_fin' => '16:00'])
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-09-14', $clase->fresh()->fecha->format('Y-m-d'));
        $this->assertSame('15:00', $clase->fresh()->hora_inicio->format('H:i'));
        $this->assertSame([$this->profesor->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_presente_propio_no_bloquea_y_ausente_en_otra_no_cuenta(): void
    {
        $alumno = Alumno::create(['nombre' => 'Prueba', 'apellido' => 'FIN10', 'dni' => '30999333',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111', 'fecha_alta' => '2026-01-01',
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        $clase = $this->clase();
        $otra = $this->clase('2026-09-13', '12:00', '13:00');
        $otra->profesores()->detach();
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => true]);
        Asistencia::create(['clase_id' => $otra->id, 'alumno_id' => $alumno->id, 'presente' => false]);
        $this->editar($clase, ['hora_inicio' => '12:00', 'hora_fin' => '13:00'])->assertSessionHasNoErrors();
        $this->assertSame('12:00', $clase->fresh()->hora_inicio->format('H:i'));
    }

    public function test_pantalla_pasada_muestra_motivo_y_controles_protegidos(): void
    {
        $clase = $this->clase('2026-09-12');
        $this->get('/clases/'.$clase->id.'/edit')->assertOk()->assertSee('name="motivo"', false)
            ->assertSee('readonly', false)->assertSee('disabled', false);
        $hoy = $this->clase();
        $this->get('/clases/'.$hoy->id.'/edit')->assertOk()->assertDontSee('name="motivo"', false);
    }
}
