<?php

use App\Models\{Alumno, AlumnoPlan, Asistencia, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Liquidacion, LiquidacionDetalle, Nivel, Profesor, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/** Verificación independiente: no corrige producto ni modifica las pruebas originales. */
class T52ReverificacionTest extends Tests\TestCase
{
    use RefreshDatabase;

    private Grupo $a;
    private Grupo $b;
    private GrupoPlan $planA;
    private GrupoPlan $planB;
    private Profesor $profesor;
    private User $admin;
    private User $op;
    private int $dni = 59000000;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') {
            throw new RuntimeException('Solo wings_testing_codex descartable.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-20 12:00:00'));
        $dep = Deporte::create(['nombre' => 'Prueba T52', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->a = Grupo::create(['deporte_id' => $dep->id, 'nivel_id' => Nivel::create(['nombre' => 'A'])->id, 'activo' => true]);
        $this->b = Grupo::create(['deporte_id' => $dep->id, 'nivel_id' => Nivel::create(['nombre' => 'B'])->id, 'activo' => true]);
        $this->planA = GrupoPlan::create(['grupo_id' => $this->a->id, 'clases_por_semana' => 2, 'precio_mensual' => 12000, 'activo' => true]);
        $this->planB = GrupoPlan::create(['grupo_id' => $this->b->id, 'clases_por_semana' => 2, 'precio_mensual' => 12000, 'activo' => true]);
        $this->profesor = Profesor::create(['deporte_id' => $dep->id, 'nombre' => 'Docente', 'apellido' => 'Prueba', 'dni' => '29000001',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Ficticia', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true]);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->op = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
    }

    private function alumno(?Grupo $grupo = null, string $alta = '2026-09-01', bool $activo = true): Alumno
    {
        $grupo ??= $this->a;
        $alumno = Alumno::create(['nombre' => 'Ficticio', 'apellido' => 'T52', 'dni' => (string) ++$this->dni,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111', 'grupo_id' => $grupo->id,
            'deporte_id' => $grupo->deporte_id, 'fecha_alta' => $alta, 'activo' => $activo]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $grupo->id === $this->a->id ? $this->planA->id : $this->planB->id,
            'fecha_desde' => $alta, 'activo' => true]);
        return $alumno;
    }

    private function clase(?Grupo $grupo = null, string $fecha = '2026-10-10'): Clase
    {
        $clase = Clase::create(['grupo_id' => ($grupo ?? $this->a)->id, 'fecha' => $fecha, 'hora_inicio' => '10:00', 'hora_fin' => '11:00',
            'cancelada' => false, 'validada_para_liquidacion' => false]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    private function observar(string $caso, array $datos): void
    {
        file_put_contents(__DIR__.'/observaciones.jsonl', json_encode(['caso' => $caso] + $datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
    }

    public function test_inactivo_sin_marca_no_se_puede_cargar_por_post(): void
    {
        $alumno = $this->alumno(activo: false);
        $clase = $this->clase();
        $vista = $this->actingAs($this->op)->get('/clases/'.$clase->id)->assertOk();
        $this->assertNotContains($alumno->id, $vista->viewData('alumnos')->pluck('id'));
        $r = $this->postJson('/clases/'.$clase->id.'/asistencias', ['items' => [['alumno_id' => $alumno->id, 'presente' => 1]]]);
        $this->observar('inactivo_sin_marca', ['http' => $r->status(), 'asistencias' => Asistencia::where('clase_id', $clase->id)->get()->toArray()]);
        $r->assertStatus(422);
        $this->assertDatabaseMissing('asistencias', ['clase_id' => $clase->id, 'alumno_id' => $alumno->id]);
    }

    public function test_cambio_en_el_dia_del_alta_no_se_retrotrae_al_corregir_ingreso_por_http(): void
    {
        $alumno = $this->alumno(alta: '2026-10-20');
        $datos = $alumno->only(['nombre', 'apellido', 'dni', 'fecha_nacimiento', 'celular', 'deporte_id']);
        $datos['fecha_nacimiento'] = '2000-01-01';
        $datos += ['grupo_id' => $this->b->id, 'fecha_alta' => '2026-10-20', 'plan_id' => $this->planB->id];
        $this->actingAs($this->admin)->put('/alumnos/'.$alumno->id, $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('2026-10-20', ($alumno->fresh()->grupo_desde ?? $alumno->fresh()->fecha_alta)->toDateString());
        $datos['fecha_alta'] = '2026-09-01';
        $datos['motivo_fecha_alta'] = 'Corrección administrativa de fecha de ingreso';
        $this->put('/alumnos/'.$alumno->id, $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
        $clase = $this->clase($this->b);
        $vista = $this->get('/clases/'.$clase->id)->assertOk();
        $this->observar('corregir_alta_tras_cambio_mismo_dia', ['grupo_desde' => ($alumno->fresh()->grupo_desde ?? $alumno->fresh()->fecha_alta)->toDateString(), 'aparece_clase_previa' => $vista->viewData('alumnos')->contains('id', $alumno->id)]);
        $this->assertSame('2026-10-20', ($alumno->fresh()->grupo_desde ?? $alumno->fresh()->fecha_alta)->toDateString());
        $this->assertNotContains($alumno->id, $vista->viewData('alumnos')->pluck('id'));
    }

    public function test_consultas_de_show_no_crecen_por_alumno(): void
    {
        $this->alumno();
        $clase = $this->clase();
        $this->actingAs($this->op)->get('/clases/'.$clase->id)->assertOk();
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->get('/clases/'.$clase->id)->assertOk(); $una = DB::getQueryLog();
        DB::disableQueryLog();
        for ($i=0; $i<11; $i++) $this->alumno();
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->get('/clases/'.$clase->id)->assertOk(); $doce = DB::getQueryLog(); DB::disableQueryLog();
        $this->observar('consultas_lista', ['uno' => count($una), 'doce' => count($doce), 'sql_uno' => array_column($una, 'query'), 'sql_doce' => array_column($doce, 'query')]);
        $this->assertSame(count($una), count($doce));
    }

    public function test_reactivacion_conserva_fecha_y_misma_lista_para_tres_roles(): void
    {
        $alumno = $this->alumno(activo: false);
        $clase = $this->clase();
        $this->actingAs($this->admin)->patchJson('/alumnos/'.$alumno->id.'/toggle-activo')->assertOk();
        $this->assertTrue($alumno->fresh()->activo);
        $this->assertSame('2026-09-01', ($alumno->fresh()->grupo_desde ?? $alumno->fresh()->fecha_alta)->toDateString());
        $prof = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true, 'profesor_id' => $this->profesor->id]);
        foreach ([$this->admin, $this->op, $prof] as $user) {
            $r = $this->actingAs($user)->get('/clases/'.$clase->id)->assertOk();
            $this->assertSame([$alumno->id], $r->viewData('alumnos')->pluck('id')->all());
        }
        $this->assertNull($alumno->fresh()->grupo_desde);
        $this->observar('reactivacion', ['grupo_desde' => null, 'fecha_efectiva' => $alumno->fresh()->fecha_alta->toDateString(), 'tres_roles_misma_lista' => true]);
    }

    public function test_grupo_inmediato_con_mes_pagado_y_plan_diferido(): void
    {
        $alumno = $this->alumno();
        DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 12000, 'monto_pagado' => 12000, 'estado' => 'PAGADA']);
        $datos = $alumno->only(['nombre', 'apellido', 'dni', 'celular', 'deporte_id']);
        $datos += ['fecha_nacimiento' => '2000-01-01', 'fecha_alta' => '2026-09-01', 'grupo_id' => $this->b->id, 'plan_id' => $this->planB->id];
        $this->actingAs($this->admin)->put('/alumnos/'.$alumno->id, $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($this->b->id, $alumno->fresh()->grupo_id);
        $this->assertSame('2026-10-20', ($alumno->fresh()->grupo_desde ?? $alumno->fresh()->fecha_alta)->toDateString());
        $this->assertSame('2026-11-01', $alumno->fresh()->planProgramado->fecha_desde->toDateString());
        $anterior = $this->clase($this->b); $hoy = $this->clase($this->b, '2026-10-20');
        $this->assertNotContains($alumno->id, $this->get('/clases/'.$anterior->id)->viewData('alumnos')->pluck('id'));
        $this->assertContains($alumno->id, $this->get('/clases/'.$hoy->id)->viewData('alumnos')->pluck('id'));
    }

    public function test_cerradas_y_pagadas_hora_y_comision_preservan_filas_e_importes(): void
    {
        $alumno = $this->alumno();
        foreach (['HORA', 'COMISION'] as $tipo) foreach (['PENDIENTE', 'PAGADA'] as $pago) {
            $this->a->deporte->update(['tipo_liquidacion' => $tipo]);
            $this->profesor->update(['tipo_liquidacion' => $tipo, 'porcentaje_comision' => $tipo === 'COMISION' ? 20 : null]);
            $clase = $this->clase();
            $marca = Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => 1]);
            $liq = Liquidacion::create(['profesor_id' => $this->profesor->id, 'mes' => 10, 'anio' => 2026, 'tipo' => $tipo, 'estado' => 'ABIERTA', 'estado_pago' => $pago, 'total_calculado' => 1000]);
            if ($tipo === 'HORA') LiquidacionDetalle::create(['liquidacion_id' => $liq->id, 'tipo_referencia' => 'clase', 'referencia_id' => $clase->id, 'monto' => 1000, 'minutos' => 60]);
            $liq->update(['estado' => 'CERRADA']);
            $antes = [$liq->fresh()->getAttributes(), $liq->detalles()->get()->toArray(), $marca->fresh()->getAttributes()];
            foreach ([$this->admin, $this->op] as $user) $this->actingAs($user)->postJson('/clases/'.$clase->id.'/asistencias', ['items' => [['alumno_id' => $alumno->id, 'presente' => 0]], 'motivo' => 'Corrección ficticia'])->assertStatus(422);
            $this->assertSame($antes, [$liq->fresh()->getAttributes(), $liq->detalles()->get()->toArray(), $marca->fresh()->getAttributes()]);
            $this->observar('liquidacion_protegida', ['tipo' => $tipo, 'pago' => $pago, 'sin_cambios' => true]);
            // Fixture siguiente: otro profesor evita el UNIQUE del período, no reabre la liquidación.
            $this->profesor = $this->profesor->replicate(); $this->profesor->dni = (string) ++$this->dni; $this->profesor->save();
        }
    }

    public function test_migracion_historica_y_cambio_previo_con_plan_diferido(): void
    {
        $normal = $this->alumno();
        $sinPlan = $this->alumno(alta: '2026-09-05'); $sinPlan->planes()->delete();
        $planAnterior = $this->alumno(alta: '2026-09-10');
        $planAnterior->planes()->update(['fecha_desde' => '2026-08-01']);
        $cambio = $this->alumno();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-05 12:00:00'));
        $cambio->update(['grupo_id' => $this->b->id]);
        $cambio->planes()->update(['activo' => null, 'fecha_hasta' => '2026-10-31']);
        AlumnoPlan::create(['alumno_id' => $cambio->id, 'plan_id' => $this->planB->id, 'fecha_desde' => '2026-11-01', 'activo' => true]);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-20 12:00:00'));
        $m = require base_path('database/migrations/2026_10_20_150000_add_grupo_desde_to_alumnos.php');
        $m->down(); $m->up();
        $this->assertSame('2026-09-01', ($normal->fresh()->grupo_desde ?? $normal->fresh()->fecha_alta)->toDateString());
        $this->assertSame('2026-09-05', ($sinPlan->fresh()->grupo_desde ?? $sinPlan->fresh()->fecha_alta)->toDateString());
        $this->assertSame('2026-09-10', ($planAnterior->fresh()->grupo_desde ?? $planAnterior->fresh()->fecha_alta)->toDateString());
        $clase = $this->clase($this->b);
        $vista = $this->actingAs($this->op)->get('/clases/'.$clase->id)->assertOk();
        $this->observar('migracion_cambio_previo_diferido', ['cambio_real' => '2026-10-05', 'primer_plan_nuevo' => '2026-11-01',
            'grupo_desde_migrado' => ($cambio->fresh()->grupo_desde ?? $cambio->fresh()->fecha_alta)->toDateString(), 'aparece_clase_10_10' => $vista->viewData('alumnos')->contains('id', $cambio->id)]);
        // Constata la aproximación escrita, sin confundirla con la fecha histórica real.
        $this->assertSame('2026-10-20', ($cambio->fresh()->grupo_desde ?? $cambio->fresh()->fecha_alta)->toDateString());
        $this->assertNotContains($cambio->id, $vista->viewData('alumnos')->pluck('id'));
    }

    public function test_migracion_con_ingreso_futuro_no_lo_adelanta(): void
    {
        $alumno = $this->alumno(alta: '2026-11-01');
        $m = require base_path('database/migrations/2026_10_20_150000_add_grupo_desde_to_alumnos.php');
        $m->down(); $m->up();
        $clase = $this->clase(fecha: '2026-10-20');
        $vista = $this->actingAs($this->op)->get('/clases/'.$clase->id)->assertOk();
        $this->assertNull($alumno->fresh()->grupo_desde);
        $this->observar('migracion_ingreso_futuro', ['fecha_alta' => '2026-11-01', 'grupo_desde' => null, 'fecha_efectiva' => $alumno->fresh()->fecha_alta->toDateString(), 'aparece_hoy' => $vista->viewData('alumnos')->contains('id', $alumno->id)]);
        $this->assertGreaterThanOrEqual('2026-11-01', ($alumno->fresh()->grupo_desde ?? $alumno->fresh()->fecha_alta)->toDateString());
    }

    public function test_ingreso_futuro_cambiado_de_grupo_no_asiste_antes_del_ingreso_por_http(): void
    {
        $alumno = $this->alumno(alta: '2026-11-01');
        $datos = $alumno->only(['nombre', 'apellido', 'dni', 'celular', 'deporte_id']);
        $datos += ['fecha_nacimiento' => '2000-01-01', 'fecha_alta' => '2026-11-01',
            'grupo_id' => $this->b->id, 'plan_id' => $this->planB->id];
        $this->actingAs($this->admin)->put('/alumnos/'.$alumno->id, $datos)
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $clase = $this->clase($this->b, '2026-10-20');
        $vista = $this->get('/clases/'.$clase->id)->assertOk();
        $guardar = $this->postJson('/clases/'.$clase->id.'/asistencias', [
            'items' => [['alumno_id' => $alumno->id, 'presente' => 1]],
        ]);
        $this->observar('ingreso_futuro_cambio_grupo_http', [
            'ingreso' => $alumno->fresh()->fecha_alta->toDateString(),
            'grupo_desde' => $alumno->fresh()->grupo_desde?->toDateString(),
            'aparece_antes_ingreso' => $vista->viewData('alumnos')->contains('id', $alumno->id),
            'http_guardado' => $guardar->status(),
            'marca_guardada' => Asistencia::where('clase_id', $clase->id)->where('alumno_id', $alumno->id)->exists(),
        ]);
        $this->assertNotContains($alumno->id, $vista->viewData('alumnos')->pluck('id'));
        $guardar->assertStatus(422);
        $this->assertDatabaseMissing('asistencias', ['clase_id' => $clase->id, 'alumno_id' => $alumno->id]);
    }
}
