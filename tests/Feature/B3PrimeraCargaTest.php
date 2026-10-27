<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, Asistencia, Clase, Deporte, DeudaCuota, ExcepcionCobranza, Grupo, GrupoPlan, Nivel, PrimeraCarga, User};
use App\Services\ExcepcionCobranzaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Adapta la prueba roja de Claude del27/10 y conserva el recorrido por ruta. */
class B3PrimeraCargaTest extends TestCase
{
    use RefreshDatabase;

    protected User $operativa;
    protected Grupo $grupo;
    protected GrupoPlan $plan;
    protected Alumno $alumno;
    protected Clase $clase;
    private int $dni = 33888700;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-27 18:00:00'));
        Notification::fake();
        $this->withoutVite();
        $this->operativa = User::factory()->create(['name' => 'Operativa B3 PC', 'rol' => 'OPERATIVO', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín PC', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'PC'])->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 28000, 'activo' => true]);
        $this->alumno = $this->crearAlumno('Carga', '2023-03-10');
        PrimeraCarga::whereKey(1)->update(['estado' => 'TERMINADA', 'detalle' => ['alumnos' => [$this->alumno->id]]]);
        $this->clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => '2026-10-27', 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
    }

    protected function crearAlumno(string $nombre, string $alta): Alumno
    {
        $a = Alumno::create(['nombre' => $nombre, 'apellido' => 'Prueba PC', 'dni' => (string) ++$this->dni,
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'fecha_alta' => $alta,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'fecha_desde' => $alta, 'activo' => true]);
        foreach ([25, 26] as $dia) {
            $previa = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => "2026-10-$dia", 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
            Asistencia::create(['clase_id' => $previa->id, 'alumno_id' => $a->id, 'presente' => true]);
        }
        return $a;
    }

    protected function condicion(Alumno $a): array
    {
        return app(ExcepcionCobranzaService::class)->condicionesAsistencia(collect([$a]), $this->clase)[$a->id];
    }

    protected function marcar(Alumno $a, ?string $motivo = null)
    {
        return $this->actingAs($this->operativa)->postJson(route('web.clases.asistencias', $this->clase->id),
            ['items' => [['alumno_id' => $a->id, 'presente' => true, 'motivo_excepcion' => $motivo]]]);
    }

    public function test_alumno_de_la_primera_carga_al_dia_entra_a_su_tercera_clase_sin_justificar(): void
    {
        $this->assertDatabaseCount('pagos', 0);
        $this->marcar($this->alumno)->assertOk();
        $this->assertDatabaseHas('asistencias', ['clase_id' => $this->clase->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);
        $this->assertDatabaseCount('excepciones_cobranza', 0);
        $this->assertFalse($this->condicion($this->alumno)['nuevo']);
    }

    public function test_deudor_de_primera_carga_pide_motivo_de_deuda_y_guarda_con_el(): void
    {
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-09', 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $respuesta = $this->marcar($this->alumno)->assertUnprocessable()->assertJsonPath('tipos', ['ASISTENCIA_DEUDOR']);
        $this->assertStringContainsString('deuda vencida', $respuesta->json('message'));
        $this->assertStringNotContainsString('Todavía no registró', $respuesta->json('message'));
        $this->assertDatabaseMissing('asistencias', ['clase_id' => $this->clase->id]);
        $this->marcar($this->alumno, 'Acuerdo de deuda con el dueño.')->assertOk();
        $e = ExcepcionCobranza::sole();
        $this->assertSame('ASISTENCIA_DEUDOR', $e->tipo);
        $this->assertSame('Acuerdo de deuda con el dueño.', $e->motivo);
        $this->assertSame($this->operativa->id, $e->autor_id);
        Notification::assertNothingSent();
    }

    public function test_moroso_de_primera_carga_entra_sin_justificar(): void
    {
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10', 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $this->assertSame('MOROSO', $this->condicion($this->alumno)['estado']);
        $this->marcar($this->alumno)->assertOk();
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_en_plazo_de_primera_carga_entra_sin_justificar(): void
    {
        $this->alumno->update(['fecha_alta' => '2026-10-26']);
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10', 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $this->assertSame('EN_PLAZO', $this->condicion($this->alumno)['estado']);
        $this->marcar($this->alumno)->assertOk();
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_alta_posterior_sin_pago_sigue_nueva_y_necesita_motivo_desde_la_tercera(): void
    {
        $posterior = $this->crearAlumno('Posterior', '2026-10-24');
        $this->assertTrue($this->condicion($posterior)['nuevo']);
        $this->marcar($posterior)->assertUnprocessable()->assertJsonPath('tipos', ['ASISTENCIA_NUEVO']);
        $this->marcar($posterior, 'Se registra el primer pago al salir.')->assertOk();
        $this->assertDatabaseHas('excepciones_cobranza', ['alumno_id' => $posterior->id, 'tipo' => 'ASISTENCIA_NUEVO']);
    }

    public function test_pendiente_o_sin_detalle_valido_no_falla_ni_exime_al_alumno(): void
    {
        foreach ([['PENDIENTE', ['alumnos' => [$this->alumno->id]]], ['PENDIENTE', null], ['TERMINADA', null],
            ['TERMINADA', []], ['TERMINADA', ['alumnos' => []]], ['TERMINADA', ['alumnos' => 'invalido']]] as [$estado, $detalle]) {
            PrimeraCarga::whereKey(1)->update(compact('estado', 'detalle'));
            $condicion = $this->condicion($this->alumno);
            $this->assertTrue($condicion['nuevo']);
            $this->assertTrue($condicion['requiere_motivo']);
            $this->assertSame('ASISTENCIA_NUEVO', $condicion['tipo']);
        }
    }

    public function test_la_lista_no_muestra_nuevo_al_cargado_y_si_al_posterior(): void
    {
        $posterior = $this->crearAlumno('Posterior', '2026-10-24');
        $respuesta = $this->actingAs($this->operativa)->get(route('web.clases.show', $this->clase->id))->assertOk();
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>'.$respuesta->getContent());
        $xpath = new \DOMXPath($doc);
        $cargado = $xpath->query('//*[@id="card-'.$this->alumno->id.'"]//*[@data-condicion-cobranza]')->item(0)->textContent;
        $alta = $xpath->query('//*[@id="card-'.$posterior->id.'"]//*[@data-condicion-cobranza]')->item(0)->textContent;
        $this->assertStringNotContainsString('Nuevo', $cargado);
        $this->assertStringContainsString('Nuevo', $alta);
        $this->assertDatabaseCount('pagos', 0);
    }
}
