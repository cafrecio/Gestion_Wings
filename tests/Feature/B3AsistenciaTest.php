<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, Asistencia, Clase, Deporte, DeudaCuota, ExcepcionCobranza, Grupo, GrupoPlan, Nivel, Pago, Profesor, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class B3AsistenciaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private User $profesora;
    private Grupo $grupo;
    private GrupoPlan $plan;
    private Clase $clase;
    private int $dni = 33888000;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-27 18:00:00'));
        Notification::fake();
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín B3', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'B3'])->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 28000, 'activo' => true]);
        $prof = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Profesora', 'apellido' => 'B3', 'dni' => '30999001',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true]);
        $this->profesora = User::factory()->create(['rol' => 'PROFESOR', 'profesor_id' => $prof->id, 'activo' => true]);
        $this->clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => '2026-10-27', 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
        $this->clase->profesores()->attach($prof->id);
    }

    private function alumno(bool $historia = false, ?string $deuda = null, string $alta = '2026-01-01'): Alumno
    {
        $a = Alumno::create(['nombre' => 'Alumno', 'apellido' => 'B3 '.++$this->dni, 'dni' => (string) $this->dni,
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'fecha_alta' => $alta,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'fecha_desde' => $alta, 'activo' => true]);
        if ($historia) $this->pago($a);
        if ($deuda) DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $deuda, 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        return $a;
    }

    private function pago(Alumno $a, float $cuota = 28000): Pago
    {
        return Pago::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'mes' => 1, 'anio' => 2026,
            'monto_base' => 28000, 'porcentaje_aplicado' => 100, 'monto_final' => 28000, 'monto_cuota' => $cuota,
            'fecha_pago' => '2026-01-01', 'estado' => 'COMPLETADO']);
    }

    private function previas(Alumno $a, int $cantidad): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => '2026-10-'.(27 - $i), 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
            Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $a->id, 'presente' => true]);
        }
    }

    private function marcar(Alumno $a, ?string $motivo = null, bool $presente = true, ?User $usuario = null)
    {
        return $this->actingAs($usuario ?? $this->operativa)->postJson(route('web.clases.asistencias', $this->clase->id),
            ['items' => [['alumno_id' => $a->id, 'presente' => $presente, 'motivo_excepcion' => $motivo]]]);
    }

    public function test_nuevo_tiene_dos_clases_de_margen_aunque_su_estado_sea_deudor(): void
    {
        foreach ([0, 1] as $previas) {
            $a = $this->alumno(false, '2026-09');
            $this->previas($a, $previas);
            $this->marcar($a)->assertOk()->assertJsonPath('success', true);
        }
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_tercera_clase_nuevo_sin_motivo_rechaza_sin_guardar(): void
    {
        $a = $this->alumno();
        $this->previas($a, 2);
        $this->marcar($a)->assertUnprocessable()->assertJsonPath('requiere_motivo', true);
        $this->assertDatabaseMissing('asistencias', ['clase_id' => $this->clase->id, 'alumno_id' => $a->id]);
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_tercera_con_motivo_guarda_autor_alumno_registro_y_no_notifica_individualmente(): void
    {
        $a = $this->alumno();
        $this->previas($a, 2);
        $this->marcar($a, 'El pago se registra al salir.')->assertOk();
        $asistencia = Asistencia::where('clase_id', $this->clase->id)->where('alumno_id', $a->id)->firstOrFail();
        $e = ExcepcionCobranza::sole();
        $this->assertSame('ASISTENCIA_NUEVO', $e->tipo);
        $this->assertSame('El pago se registra al salir.', $e->motivo);
        $this->assertSame($this->operativa->id, $e->autor_id);
        $this->assertSame($a->id, $e->alumno_id);
        $this->assertSame('ASISTENCIA', $e->registro_tipo);
        $this->assertSame($asistencia->id, $e->registro_id);
        $this->assertSame($this->clase->id, $e->contexto['clase_id']);
        Notification::assertNothingSent();
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('El pago se registra al salir.');
    }

    public function test_deudor_con_historia_necesita_motivo_y_permite_con_el(): void
    {
        $a = $this->alumno(true, '2026-09');
        $this->marcar($a)->assertUnprocessable();
        $this->marcar($a, 'Acuerdo de pago con el dueño.')->assertOk();
        $this->assertDatabaseHas('excepciones_cobranza', ['tipo' => 'ASISTENCIA_DEUDOR', 'alumno_id' => $a->id]);
    }

    public function test_moroso_con_historia_entra_sin_justificar_y_se_ve_su_condicion(): void
    {
        $a = $this->alumno(true, '2026-10');
        $this->marcar($a)->assertOk();
        $this->assertDatabaseCount('excepciones_cobranza', 0);
        $this->actingAs($this->profesora)->get(route('web.clases.show', $this->clase->id))->assertOk()->assertSee('Moroso');
    }

    public function test_un_pago_de_cuota_parcial_termina_la_condicion_de_nuevo(): void
    {
        $a = $this->alumno();
        $this->previas($a, 2);
        $this->pago($a, 1);
        $this->marcar($a)->assertOk();
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_pagar_solo_inscripcion_no_elude_el_control_de_primera_cuota(): void
    {
        $a = $this->alumno();
        $this->previas($a, 2);
        $this->pago($a, 0);
        $this->marcar($a)->assertUnprocessable();
    }

    public function test_rechazo_de_segundo_alumno_revierte_toda_la_lista(): void
    {
        $libre = $this->alumno(true);
        $deudor = $this->alumno(true, '2026-09');
        $this->actingAs($this->operativa)->postJson(route('web.clases.asistencias', $this->clase->id), ['items' => [
            ['alumno_id' => $libre->id, 'presente' => true], ['alumno_id' => $deudor->id, 'presente' => true],
        ]])->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 0);
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_motivo_blanco_y_mayor_de_500_rechazados(): void
    {
        $a = $this->alumno(true, '2026-09');
        foreach (['   ', str_repeat('a', 501)] as $motivo) $this->marcar($a, $motivo)->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_reenviar_lista_guardada_no_duplica_ni_reescribe_el_motivo(): void
    {
        $a = $this->alumno(true, '2026-09');
        $this->marcar($a, 'Primera decisión.')->assertOk();
        $this->marcar($a)->assertOk();
        $this->assertDatabaseCount('excepciones_cobranza', 1);
        $this->assertSame('Primera decisión.', ExcepcionCobranza::sole()->motivo);
    }

    public function test_ausente_no_necesita_motivo_y_ausente_a_presente_si(): void
    {
        $a = $this->alumno(true, '2026-09');
        $this->marcar($a, null, false)->assertOk();
        $this->marcar($a)->assertUnprocessable();
        $this->assertFalse(Asistencia::sole()->presente);
    }

    public function test_exceso_de_plan_solo_conserva_la_anotacion_recupera(): void
    {
        $a = $this->alumno(true);
        $this->previas($a, 1);
        $this->actingAs($this->operativa)->postJson(route('web.clases.asistencias', $this->clase->id), ['items' => [
            ['alumno_id' => $a->id, 'presente' => true, 'motivo_exceso' => 'RECUPERA'],
        ]])->assertOk();
        $this->assertDatabaseHas('asistencia_excesos', ['alumno_id' => $a->id, 'motivo' => 'RECUPERA']);
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_lista_muestra_condicion_de_cada_alumno_sin_contador_de_nuevo(): void
    {
        $nuevo = $this->alumno();
        $this->previas($nuevo, 2);
        $this->alumno(true, '2026-09');
        $this->alumno(true, '2026-10');
        $this->alumno(true, '2026-10', '2026-10-26');
        $this->alumno(true);
        $this->actingAs($this->profesora)->get(route('web.clases.show', $this->clase->id))
            ->assertOk()->assertSee('Nuevo')->assertSee('Deudor')->assertSee('Moroso')->assertSee('En plazo')->assertSee('Al día')
            ->assertDontSee('asistencias_nuevo')->assertDontSee('tercera clase')->assertDontSee('3ª');
    }

    public function test_admin_operativa_y_profesora_pueden_justificar_sin_cambiar_permisos(): void
    {
        foreach ([$this->admin, $this->operativa, $this->profesora] as $user) {
            $a = $this->alumno(true, '2026-09');
            $this->marcar($a, null, true, $user)->assertUnprocessable();
            $this->marcar($a, 'Acuerdo autorizado.', true, $user)->assertOk();
            $this->assertDatabaseHas('excepciones_cobranza', ['autor_id' => $user->id, 'alumno_id' => $a->id]);
        }
    }

    public function test_un_motivo_no_habilita_clases_canceladas_ni_futuras(): void
    {
        $a = $this->alumno(true, '2026-09');
        $this->clase->update(['cancelada' => true]);
        $this->marcar($a, 'Con motivo.')->assertUnprocessable();
        $this->clase->update(['cancelada' => false, 'fecha' => '2026-10-28']);
        $this->marcar($a, 'Con motivo.')->assertUnprocessable();
        $this->assertDatabaseCount('asistencias', 0);
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_volver_a_admitir_despues_de_ausente_conserva_ambas_decisiones(): void
    {
        $a = $this->alumno(true, '2026-09');
        $this->marcar($a, 'Primer acuerdo.')->assertOk();
        $this->marcar($a, null, false)->assertOk();
        $this->marcar($a)->assertUnprocessable();
        $this->marcar($a, 'Segundo acuerdo.', true, $this->profesora)->assertOk();
        $filas = ExcepcionCobranza::orderBy('id')->get();
        $this->assertCount(2, $filas);
        $this->assertSame(['Primer acuerdo.', 'Segundo acuerdo.'], $filas->pluck('motivo')->all());
        $this->assertSame([$this->operativa->id, $this->profesora->id], $filas->pluck('autor_id')->all());
        $this->assertSame($filas[0]->registro_id, $filas[1]->registro_id);
        $this->marcar($a)->assertOk();
        $this->assertDatabaseCount('excepciones_cobranza', 2);
    }

    public function test_fallo_posterior_revierte_asistencia_y_excepcion_juntas(): void
    {
        $a = $this->alumno(true, '2026-09');
        $this->partialMock(\App\Services\ClaseService::class, function ($mock) {
            $mock->shouldReceive('verificarDisponibilidadAlumno')->andReturn(['puede_asistir' => true, 'razon' => null]);
            $mock->shouldReceive('contarAsistenciasSemana')->andReturnUsing(function () {
                $this->assertDatabaseCount('excepciones_cobranza', 1);
                throw new \RuntimeException('Fallo posterior a la decisión B3');
            });
        });
        $this->withoutExceptionHandling();
        try {
            $this->marcar($a, 'Admisión que fallará después.');
            $this->fail('Debía fallar después de registrar la excepción.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo posterior a la decisión B3', $error->getMessage());
        }
        $this->assertDatabaseCount('asistencias', 0);
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_una_marca_presente_sin_cambios_no_se_justifica_retroactivamente(): void
    {
        $a = $this->alumno(true, '2026-09');
        Asistencia::create(['clase_id' => $this->clase->id, 'alumno_id' => $a->id, 'presente' => true]);
        $this->marcar($a)->assertOk();
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_los_dos_tipos_de_asistencia_van_juntos_al_resumen_diario(): void
    {
        $nuevo = $this->alumno();
        $this->previas($nuevo, 2);
        $deudor = $this->alumno(true, '2026-09');
        $this->marcar($nuevo, 'Se registra el primer pago al salir.')->assertOk();
        $this->marcar($deudor, 'Acuerdo de pago.')->assertOk();
        Notification::assertNothingSent();
        $this->assertTrue(app(\App\Services\AvisoAdminService::class)->resumenDiario());
        Notification::assertSentTo($this->admin, \App\Notifications\AvisoOperativo::class, function ($aviso) {
            $texto = $aviso->toArray($this->admin)['datos']['Excepciones justificadas (últimas 24 horas)'];
            return str_contains($texto, 'Alumno nuevo sin primer pago: 1') && str_contains($texto, 'Asistencia de alumno deudor: 1');
        });
        Notification::assertSentToTimes($this->admin, \App\Notifications\AvisoOperativo::class, 1);
        Notification::assertSentOnDemandTimes(\App\Notifications\AvisoOperativo::class, 1);
    }

    public function test_ausencias_y_clases_posteriores_no_consumen_el_margen_del_nuevo(): void
    {
        $a = $this->alumno();
        $this->previas($a, 1);
        foreach ([['2026-10-24', false], ['2026-10-28', true]] as [$fecha, $presente]) {
            $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => $fecha, 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
            Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $a->id, 'presente' => $presente]);
        }
        $this->marcar($a)->assertOk();
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }
}
