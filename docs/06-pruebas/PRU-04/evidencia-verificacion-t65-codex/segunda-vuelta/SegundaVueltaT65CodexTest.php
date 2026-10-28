<?php
namespace Tests\VerificacionSegundaT65;

use App\Models\{CargoAlumno, Deporte, DeudaCuota, ExcepcionCobranza, Grupo, Pago, Subrubro};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\EscenarioT65;
use Tests\TestCase;

class SegundaVueltaT65CodexTest extends TestCase
{
    use RefreshDatabase, EscenarioT65;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('wings_testing_codex', DB::connection()->getDatabaseName());
        $this->travelTo(Carbon::parse('2026-10-27 18:00:00'));
        $this->escenarioT65();
    }

    public function test_baja_conserva_historia_de_septiembre_y_retira_octubre(): void
    {
        foreach (['reporte_cobertura', 'reporte_analitico_cobertura'] as $tabla) {
            DB::table($tabla)->where('id', 1)->update(['desde' => '2026-09-01 00:00:00']);
        }
        $this->alumnoT65->update(['fecha_alta' => '2026-09-01']);
        $this->travelTo(Carbon::parse('2026-09-15 12:00:00'));
        $septiembre = DeudaCuota::create(['alumno_id' => $this->alumnoT65->id, 'periodo' => '2026-09',
            'monto_original' => 15000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $previas = DB::table('reporte_analitico_historial')->where('tipo', 'CUOTA')->where('persona_id', $this->alumnoT65->id)->get();
        $this->travelTo(Carbon::parse('2026-10-27 18:00:00'));
        $this->actingAs($this->duenioT65);
        $evidencia = [];
        foreach (['antes', 'despues'] as $momento) {
            foreach (['2026-09' => 1500000, '2026-10' => $momento === 'antes' ? 1500000 : 0] as $mes => $esperado) {
                $r = $this->get(route('web.reportes.sueldos', ['mes' => $mes]))->assertOk();
                $valor = $r->viewData('reporte')['sin_asistencia'];
                $this->assertSame($esperado, $valor, $momento.' '.$mes);
                $evidencia[$momento]['sueldos'][$mes] = $valor;
            }
            foreach (['web.reportes.index', 'admin.dashboard'] as $ruta) {
                $r = $this->get(route($ruta, ['mes' => '2026-10']))->assertOk();
                $valor = $r->viewData('reporte')['deuda']['total'];
                if ($momento === 'antes') $this->assertGreaterThan(0, $valor);
                else $this->assertSame(0, $valor);
                $evidencia[$momento][$ruta] = $valor;
            }
            if ($momento === 'antes') $this->deleteJson(route('web.alumnos.destroy', $this->alumnoT65))->assertOk();
        }
        foreach ($previas as $fila) $this->assertSame((array) $fila, (array) DB::table('reporte_analitico_historial')->where('id', $fila->id)->first());
        $this->assertDatabaseHas('reporte_analitico_historial', ['tipo' => 'CUOTA', 'origen_id' => $septiembre->id, 'vigente' => false]);
        $evidencia['filas'] = DB::table('reporte_analitico_historial')->where('tipo', 'CUOTA')->where('persona_id', $this->alumnoT65->id)->orderBy('id')->get()->all();
        file_put_contents(__DIR__.'/h2-gets-e-historia.json', json_encode($evidencia, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    public function test_excepciones_de_todos_los_tipos_rechazan_y_no_se_borran(): void
    {
        $respuestas = [];
        foreach (['ASISTENCIA_NUEVO', 'ASISTENCIA_DEUDOR', 'COBRO_PARCIAL', 'COBRO_DEUDA_ANTERIOR'] as $i => $tipo) {
            $a = $this->nuevoAlumnoT65((string) (33992001 + $i));
            $e = ExcepcionCobranza::create(['alumno_id' => $a->id, 'autor_id' => $this->duenioT65->id,
                'tipo' => $tipo, 'motivo' => 'Defensa sin origen segunda T65', 'registro_tipo' => str_starts_with($tipo, 'COBRO') ? 'PAGO' : 'ASISTENCIA',
                'registro_id' => 999990 + $i, 'ocurrida_at' => now(), 'contexto' => []]);
            $fila = $e->fresh()->getAttributes();
            $r = $this->actingAs($this->duenioT65)->deleteJson(route('web.alumnos.destroy', $a))->assertStatus(422);
            $this->assertStringContainsString('excepciones de cobranza', $r->json('message'));
            $this->assertStringContainsString('inactivo', $r->json('message'));
            $this->assertSame($fila, $e->fresh()->getAttributes());
            $this->assertDatabaseHas('alumnos', ['id' => $a->id]);
            $respuestas[$tipo] = $r->json();
        }
        file_put_contents(__DIR__.'/h3-respuestas.json', json_encode($respuestas, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    public function test_cargos_no_inscripcion_se_borran_y_cobrados_o_condonados_bloquean(): void
    {
        $d = Deporte::create(['nombre' => 'Otro deporte segunda T65', 'activo' => true]);
        $g = Grupo::create(['deporte_id' => $d->id, 'nivel_id' => $this->grupoT65->nivel_id, 'activo' => true]);
        $otro = $this->nuevoAlumnoT65($this->alumnoT65->dni, $g);
        $inscripcion = CargoAlumno::where('tipo', 'INSCRIPCION')->where('dni', $otro->dni)->sole();
        $cargos = [];
        foreach (['PUNITORIO'] as $tipo) $cargos[] = CargoAlumno::create([
            'alumno_id' => $this->alumnoT65->id, 'dni' => $otro->dni, 'deuda_cuota_id' => $this->alumnoT65->deudaCuotas()->sole()->id,
            'tipo' => $tipo, 'clave_origen' => 'segunda-'.$tipo, 'subrubro_id' => Subrubro::firstOrFail()->id,
            'monto_original' => 1500, 'monto_condonado' => 0, 'estado' => 'VIGENTE', 'calculo' => []]);
        $url = route('web.alumnos.destroy', $this->alumnoT65);
        $this->actingAs($this->duenioT65);
        $cargos[0]->update(['monto_condonado' => 100]);
        $this->deleteJson($url)->assertStatus(422);
        $this->assertSame('100.00', $cargos[0]->fresh()->monto_condonado);
        $cargos[0]->update(['monto_condonado' => 0]);
        $pago = Pago::create(['alumno_id' => $otro->id, 'mes' => 10, 'anio' => 2026, 'monto_base' => 100,
            'porcentaje_aplicado' => 100, 'monto_final' => 100, 'monto_cuota' => 0, 'fecha_pago' => today(), 'estado' => 'COMPLETADO']);
        $pago->cargos()->attach($cargos[0]->id, ['monto_aplicado' => 100]);
        $this->assertSame(0, $this->alumnoT65->pagos()->count());
        $r = $this->deleteJson($url)->assertStatus(422);
        $this->assertStringContainsString('cargos con cobro o condonados', $r->json('message'));
        $this->assertDatabaseHas('pago_cargo_alumno', ['cargo_alumno_id' => $cargos[0]->id, 'pago_id' => $pago->id]);
        // Retirar sólo el vínculo sintético para probar la rama sin cobro; no simula un flujo del club.
        $pago->cargos()->detach();
        $this->deleteJson($url)->assertOk();
        foreach ($cargos as $cargo) $this->assertDatabaseMissing('cargos_alumno', ['id' => $cargo->id]);
        $this->assertSame([$inscripcion->id], CargoAlumno::where('dni', $otro->dni)->pluck('id')->all());
        $this->assertSame($otro->id, $inscripcion->fresh()->alumno_id);
    }
}
