<?php

namespace Tests\Feature;

use App\Models\{Asistencia, CargoAlumno, Clase, Configuracion, Deporte, ExcepcionCobranza, Grupo, Liquidacion, LiquidacionDetalle, MovimientoOperativo, Pago, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\{HistorialAnaliticoReportesService, InscripcionService, PagoCuotaService, PunitorioService};
use Carbon\{Carbon, CarbonImmutable};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\EscenarioT65;
use Tests\TestCase;

/** Verificación independiente: los defectos permanecen rojos hasta el arreglo de Gemini. */
class VerificacionEliminarT65CodexTest extends TestCase
{
    use RefreshDatabase, EscenarioT65;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-27 18:00:00'));
        $this->escenarioT65();
    }

    public function test_recargo_automatico_impago_no_produce_500_al_eliminar_alumno(): void
    {
        Configuracion::updateOrCreate(['clave' => 'mora_porcentaje'], ['valor' => '10', 'tipo' => 'string']);
        Configuracion::updateOrCreate(['clave' => 'mora_dia_desde'], ['valor' => '10', 'tipo' => 'integer']);
        if (!Subrubro::where('nombre', 'Punitorio Cuota')->exists()) {
            Subrubro::create(['rubro_id' => Subrubro::firstOrFail()->rubro_id, 'nombre' => 'Punitorio Cuota',
                'activo' => true, 'afecta_caja' => true, 'permitido_para' => 'OPERATIVO', 'es_reservado_sistema' => true]);
        }
        $this->assertSame(1, app(PunitorioService::class)->aplicar());
        $this->assertTrue($this->alumnoT65->esEliminable());
        $this->assertDatabaseHas('cargos_alumno', ['alumno_id' => $this->alumnoT65->id, 'tipo' => 'PUNITORIO']);
        $this->assertDatabaseCount('pagos', 0);
        $respuesta = $this->actingAs($this->duenioT65)->deleteJson(route('web.alumnos.destroy', $this->alumnoT65));
        // Incluso si falla, comprobar antes que el rollback no haya borrado planes o deuda.
        if ($respuesta->status() >= 500) {
            $this->assertDatabaseHas('alumno_planes', ['alumno_id' => $this->alumnoT65->id]);
            $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $this->alumnoT65->id]);
        }
        $respuesta->assertOk();
        $this->assertDatabaseMissing('alumnos', ['id' => $this->alumnoT65->id]);
    }

    public function test_el_borrado_retira_la_cuota_del_reporte_actual_de_sueldos(): void
    {
        $url = route('web.reportes.sueldos', ['mes' => '2026-10']);
        $antes = $this->actingAs($this->duenioT65)->get($url)->assertOk();
        $this->assertSame(1500000, $antes->viewData('reporte')['sin_asistencia']);
        $cuotaId = $this->alumnoT65->deudaCuotas()->sole()->id;
        $this->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))->assertOk();
        $this->assertDatabaseMissing('deuda_cuotas', ['id' => $cuotaId]);
        $despues = $this->get($url)->assertOk();
        $this->assertSame(0, $despues->viewData('reporte')['sin_asistencia'],
            'Una cuota eliminada sigue inflando el importe de cuotas sin asistencia en Sueldos.');
        $this->assertSame([], app(HistorialAnaliticoReportesService::class)->obtener('CUOTA', CarbonImmutable::now(), $cuotaId)['filas']);
    }

    public function test_excepcion_sin_origen_debe_rechazar_con_explicacion_y_no_con_500(): void
    {
        // Estado permitido por el esquema, NO producido por los flujos actuales conocidos.
        ExcepcionCobranza::create(['alumno_id' => $this->alumnoT65->id, 'autor_id' => $this->duenioT65->id,
            'tipo' => 'ASISTENCIA_NUEVO', 'motivo' => 'Historia de una admisión anterior',
            'registro_tipo' => 'ASISTENCIA', 'registro_id' => 999999, 'ocurrida_at' => now(), 'contexto' => []]);
        $this->actingAs($this->duenioT65)->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))->assertStatus(422);
        $this->assertDatabaseHas('alumnos', ['id' => $this->alumnoT65->id]);
    }

    public function test_excepcion_real_de_asistencia_frena_antes_de_la_fk_y_conserva_su_origen(): void
    {
        Asistencia::create(['clase_id' => $this->claseT65->id, 'alumno_id' => $this->alumnoT65->id, 'presente' => true]);
        // Tercera admisión del nuevo requiere motivo; la excepción nace de la ruta real.
        foreach ([25, 26] as $dia) {
            $c = Clase::create(['grupo_id' => $this->grupoT65->id, 'fecha' => "2026-10-$dia", 'hora_inicio' => '10:00', 'hora_fin' => '11:00']);
            Asistencia::create(['clase_id' => $c->id, 'alumno_id' => $this->alumnoT65->id, 'presente' => true]);
        }
        Asistencia::where('clase_id', $this->claseT65->id)->delete();
        $this->actingAs($this->operativaT65)->postJson(route('web.clases.asistencias', $this->claseT65), ['items' => [[
            'alumno_id' => $this->alumnoT65->id, 'presente' => true, 'motivo_excepcion' => 'El dueño permite esta admisión.']]])->assertOk();
        $e = ExcepcionCobranza::sole();
        $this->actingAs($this->duenioT65)->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseHas('asistencias', ['id' => $e->registro_id]);
        $this->assertDatabaseHas('excepciones_cobranza', ['id' => $e->id]);
    }

    public function test_borrar_el_ancla_de_inscripcion_compartida_preserva_un_solo_cargo_y_saldo(): void
    {
        $sport = Deporte::create(['nombre' => 'Segundo deporte T65', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $sport->id, 'nivel_id' => $this->grupoT65->nivel_id, 'activo' => true]);
        $otro = $this->nuevoAlumnoT65($this->alumnoT65->dni, $grupo);
        $cargo = app(InscripcionService::class)->cargo($otro->dni);
        $atributos = $cargo->getAttributes();
        $eventos = DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $cargo->id)->get()->toJson();
        $antes = $this->actingAs($this->duenioT65)->get(route('web.cobranza.index', ['estado' => 'TODOS']))->assertOk();
        $saldo = $antes->viewData('inscripciones')[$otro->dni];
        $this->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))->assertOk();
        $cargo->refresh();
        $atributos['alumno_id'] = $otro->id;
        $this->assertSame($atributos, $cargo->getAttributes());
        $this->assertSame($eventos, DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $cargo->id)->get()->toJson());
        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->where('dni', $otro->dni)->count());
        app(InscripcionService::class)->sincronizar($otro, $this->duenioT65->id);
        $this->assertSame(1, CargoAlumno::where('dni', $otro->dni)->count());
        $despues = $this->get(route('web.cobranza.index', ['estado' => 'TODOS']))->assertOk();
        $this->assertSame($saldo, $despues->viewData('inscripciones')[$otro->dni]);
    }

    public function test_cuota_vencida_sin_cobros_no_impide_el_borrado(): void
    {
        $this->alumnoT65->deudaCuotas()->sole()->update(['periodo' => '2026-09']);
        $this->actingAs($this->duenioT65)->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))->assertOk();
        $this->assertDatabaseMissing('alumnos', ['id' => $this->alumnoT65->id]);
    }

    public function test_inscripcion_pagada_desde_otro_deporte_no_se_borra_ni_duplica(): void
    {
        $sport = Deporte::create(['nombre' => 'Pagador segundo deporte', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $sport->id, 'nivel_id' => $this->grupoT65->nivel_id, 'activo' => true]);
        $otro = $this->nuevoAlumnoT65($this->alumnoT65->dni, $grupo);
        $cargo = app(InscripcionService::class)->cargo($otro->dni);
        $p = Pago::create(['alumno_id' => $otro->id, 'mes' => 10, 'anio' => 2026, 'monto_base' => 5000, 'porcentaje_aplicado' => 100,
            'monto_final' => 5000, 'monto_cuota' => 0, 'fecha_pago' => today(), 'medio_pago' => 'EFECTIVO', 'estado' => 'ANULADO']);
        $p->cargos()->attach($cargo->id, ['monto_aplicado' => 5000]);
        $this->actingAs($this->duenioT65)->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))->assertStatus(422);
        $this->assertDatabaseHas('cargos_alumno', ['id' => $cargo->id, 'alumno_id' => $this->alumnoT65->id]);
        $this->assertDatabaseHas('pago_cargo_alumno', ['pago_id' => $p->id, 'cargo_alumno_id' => $cargo->id]);
    }

    public function test_serie_es_atomica_para_liquidacion_abierta_cerrada_y_validacion_manual(): void
    {
        $prof = Profesor::create(['nombre' => 'Docente', 'apellido' => 'T65', 'dni' => '33991099', 'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Calle de prueba 1', 'localidad' => 'Prueba',
            'deporte_id' => $this->grupoT65->deporte_id, 'tipo_liquidacion' => 'HORA', 'valor_hora' => 1000, 'activo' => true]);
        $this->claseT65->update(['serie_id' => 'serie-verificacion-t65']);
        $bloqueada = $this->claseT65->replicate(); $bloqueada->fecha = '2026-10-28'; $bloqueada->save();
        $this->claseT65->profesores()->attach($prof->id); $bloqueada->profesores()->attach($prof->id);
        $liq = Liquidacion::create(['profesor_id' => $prof->id, 'tipo' => 'HORA', 'mes' => 10, 'anio' => 2026,
            'total_calculado' => 1000, 'total_final' => 1000, 'estado' => 'ABIERTA', 'creada_por' => $this->duenioT65->id]);
        $d = LiquidacionDetalle::create(['liquidacion_id' => $liq->id, 'tipo_referencia' => 'clase',
            'referencia_id' => $bloqueada->id, 'monto' => 1000, 'descripcion' => 'Caso sintético T65']);
        foreach (['ABIERTA', 'CERRADA', 'MANUAL'] as $estado) {
            if ($estado === 'MANUAL') { DB::table('liquidacion_detalles')->where('id', $d->id)->delete(); $bloqueada->update(['validada_para_liquidacion' => true]); }
            else $liq->update(['estado' => $estado]);
            $r = $this->actingAs($this->duenioT65)->deleteJson(route('web.clases.destroy', $this->claseT65), ['eliminar_serie' => 1]);
            $r->assertStatus(422); $this->assertStringContainsString('28/10/2026', $r->json('message'));
            $this->assertDatabaseHas('clases', ['id' => $this->claseT65->id]);
            $this->assertDatabaseHas('clases', ['id' => $bloqueada->id]);
            $this->assertDatabaseCount('clase_profesor', 2);
        }
    }

    public function test_listados_filtros_cobranza_reportes_inicio_y_dni_tras_eliminar(): void
    {
        $this->claseT65->update(['fecha' => '2026-10-26']);
        $this->actingAs($this->duenioT65);
        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('reporte', fn ($r) => $r['deuda']['total'] > 0);
        $this->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))->assertOk();
        $this->deleteJson(route('web.clases.destroy', $this->claseT65))->assertOk();
        $this->get(route('web.cobranza.index', ['estado' => 'TODOS', 'deporte_id' => $this->grupoT65->deporte_id]))
            ->assertOk()->assertViewHas('alumnos', fn ($a) => $a->isEmpty());
        $this->get(route('web.alumnos.index', ['busqueda' => $this->alumnoT65->dni]))->assertOk()
            ->assertDontSee(route('web.alumnos.show', $this->alumnoT65));
        $this->get(route('web.clases.index', ['estado' => 'finalizada', 'grupo_id' => $this->grupoT65->id]))
            ->assertOk()->assertViewHas('clasesFiltradas', fn ($c) => $c->isEmpty())->assertViewHas('badgeClasesPendientes', 0);
        foreach (['admin.dashboard', 'web.reportes.index'] as $ruta) {
            $this->get(route($ruta))->assertOk()->assertViewHas('reporte', fn ($r) => $r['deuda']['total'] === 0);
        }
        $this->get(route('web.reportes.alumnos'))->assertOk()->assertViewHas('reporte', fn ($r) => $r['activos'] === 0 && $r['clases'] === 0);
        $nuevo = $this->nuevoAlumnoT65($this->alumnoT65->dni);
        $this->assertNotSame($this->alumnoT65->id, $nuevo->id);
    }

    public function test_paneles_inline_y_roles_sin_botones_no_autorizados(): void
    {
        $prof = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]);
        foreach ([$this->operativaT65, $prof] as $u) {
            foreach (['alumnos' => $this->alumnoT65, 'clases' => $this->claseT65] as $tipo => $modelo) {
                $this->actingAs($u)->deleteJson(route("web.$tipo.destroy", $modelo))->assertForbidden();
                $vista = $this->get(route("web.$tipo.show", $modelo));
                if ($vista->status() === 200) $vista->assertDontSee('id="btn-abrir-eliminar-', false);
                else $vista->assertForbidden();
            }
        }
        foreach (['alumnos' => $this->alumnoT65, 'clases' => $this->claseT65] as $tipo => $modelo) {
            $html = $this->actingAs($this->duenioT65)->get(route("web.$tipo.show", $modelo))->assertOk()->getContent();
            $doc = new \DOMDocument(); @$doc->loadHTML('<?xml encoding="UTF-8">'.$html); $xpath = new \DOMXPath($doc);
            $this->assertSame(1, $xpath->query('//*[starts-with(@id,"btn-abrir-eliminar-")]')->length);
            foreach ($xpath->query('//form[.//input[@name="_method" and @value="DELETE"]]') as $form) {
                $this->assertFalse($form->hasAttribute('data-confirmar-envio'));
                foreach ($xpath->query('.//*[@data-confirmar or @onclick or @onsubmit]', $form) as $invalid) $this->fail($doc->saveHTML($invalid));
            }
        }
        $this->assertDatabaseHas('alumnos', ['id' => $this->alumnoT65->id]);
        $this->assertDatabaseHas('clases', ['id' => $this->claseT65->id]);
    }

    public function test_movimiento_con_nombre_libre_sin_vinculo_no_se_borra_con_el_alumno(): void
    {
        $medio = TipoCaja::create(['nombre' => 'Efectivo prueba T65', 'activo' => true]);
        $caja = \Tests\Support\CajaDeclarada::crear($this->operativaT65->id, $medio->id);
        $rubro = Rubro::create(['nombre' => 'Otros ingresos T65', 'tipo' => 'INGRESO']);
        $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Ingreso genérico T65', 'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true, 'activo' => true, 'es_reservado_sistema' => false]);
        $obs = 'Ingreso genérico - '.$this->alumnoT65->nombre.' '.$this->alumnoT65->apellido;
        $this->actingAs($this->operativaT65)->post(route('web.caja.editar.store', $caja), [
            'tipo_caja_id' => $medio->id, 'subrubro_id' => $sub->id, 'monto' => 1000,
            'fecha' => '2026-10-27', 'observaciones' => $obs])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $m = MovimientoOperativo::where('observaciones', $obs)->sole();
        $this->assertNull($m->alumno_id);
        $this->actingAs($this->duenioT65)->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))->assertOk();
        $this->assertDatabaseHas('movimientos_operativos', ['id' => $m->id, 'observaciones' => $obs, 'monto' => 1000, 'alumno_id' => null]);
    }

    public function test_cla_021_d_no_admite_cambiar_el_grupo_inyectado_en_la_edicion(): void
    {
        $nivel = \App\Models\Nivel::create(['nombre' => 'Otro nivel T65', 'activo' => true]);
        $otroGrupo = Grupo::create(['deporte_id' => $this->grupoT65->deporte_id, 'nivel_id' => $nivel->id, 'activo' => false]);
        $this->claseT65->update(['fecha' => '2026-10-26']);
        $this->actingAs($this->duenioT65)->put(route('web.clases.update', $this->claseT65), [
            'grupo_id' => $otroGrupo->id, 'fecha' => '2026-10-26', 'hora_inicio' => '10:00', 'hora_fin' => '11:00'])->assertRedirect();
        $this->assertSame($this->grupoT65->id, $this->claseT65->fresh()->grupo_id);
    }
}
