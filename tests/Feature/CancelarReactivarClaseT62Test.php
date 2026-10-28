<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T62 / DU-30 / DU-31 / CLA-012 / CLA-015:
 * Cancelar y reactivar una clase: aviso por lista tomada, choque y motivo conservado.
 */
class CancelarReactivarClaseT62Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesorUser;
    private Profesor $profesor;
    private Grupo $grupo;
    private Deporte $deporte;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->profesorUser = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);

        $this->deporte = Deporte::create([
            'nombre' => 'Patín Artístico',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Iniciación']);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'nombre' => 'Patín Escuela',
            'activo' => true,
        ]);
        $this->profesor = Profesor::create([
            'deporte_id' => $this->deporte->id,
            'nombre' => 'Lucía',
            'apellido' => 'Sosa',
            'dni' => '33111222',
            'fecha_nacimiento' => '1990-04-15',
            'direccion' => 'Pellegrini 1234',
            'localidad' => 'Rosario',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'valor_hora' => 15000,
            'activo' => true,
        ]);
    }

    private function crearClase(string $fecha, string $inicio = '17:00', string $fin = '18:00', ?int $serieId = null): Clase
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => $fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'cancelada' => false,
            'serie_id' => $serieId,
        ]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    private function crearAlumno(string $nombre = 'Camila'): Alumno
    {
        return Alumno::create([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'nombre' => $nombre,
            'apellido' => 'Gómez',
            'dni' => (string) rand(30000000, 45000000),
            'email' => 'alumno' . rand(100, 999) . '@test.com',
            'celular' => '3415551122',
            'fecha_nacimiento' => '2012-05-10',
            'fecha_alta' => '2026-01-01',
            'activo' => true,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CLA-012.a: Cancelar con lista tomada avisa y pide confirmación
    // ──────────────────────────────────────────────────────────────────────────

    public function test_cancelar_clase_sin_asistencia_no_pide_confirmacion(): void
    {
        $clase = $this->crearClase('2026-10-28');

        $res = $this->actingAs($this->operativo)->patchJson(route('web.clases.toggle-cancelada', $clase->id), [
            'motivo_cancelacion' => 'Pista mojada',
        ]);

        $res->assertOk()->assertJson([
            'success' => true,
            'message' => 'Clase cancelada.',
            'cancelada' => true,
        ]);

        $this->assertTrue((bool) $clase->fresh()->cancelada);
        $this->assertSame('Pista mojada', $clase->fresh()->motivo_cancelacion);
    }

    public function test_cancelar_clase_con_asistencia_avisa_y_exige_confirmacion(): void
    {
        $clase = $this->crearClase('2026-10-28');
        $alumno = $this->crearAlumno('Martina');
        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);

        // Sin confirmar: responde HTTP 409 Conflict
        $res = $this->actingAs($this->operativo)->patchJson(route('web.clases.toggle-cancelada', $clase->id), [
            'motivo_cancelacion' => 'Lluvia torrencial',
        ]);

        $res->assertStatus(409)->assertJson([
            'success' => false,
            'requiere_confirmacion' => true,
            'con_lista' => 1,
        ]);
        $this->assertStringContainsString('ya tiene la lista tomada', $res->json('message'));
        $this->assertFalse((bool) $clase->fresh()->cancelada);

        // Con confirmar: true -> cancela con éxito
        $resConfirmado = $this->actingAs($this->operativo)->patchJson(route('web.clases.toggle-cancelada', $clase->id), [
            'motivo_cancelacion' => 'Lluvia torrencial',
            'confirmar' => true,
        ]);

        $resConfirmado->assertOk()->assertJson([
            'success' => true,
            'message' => 'Clase cancelada.',
            'cancelada' => true,
        ]);
        $this->assertTrue((bool) $clase->fresh()->cancelada);
        $this->assertSame('Lluvia torrencial', $clase->fresh()->motivo_cancelacion);
    }

    public function test_cancelar_serie_con_asistencias_informa_cuantas_tienen_lista_y_pide_confirmacion(): void
    {
        Carbon::setTestNow('2026-10-28 10:00:00');
        $serieId = 55;

        $clase1 = $this->crearClase('2026-10-28', '17:00', '18:00', $serieId);
        $clase2 = $this->crearClase('2026-11-04', '17:00', '18:00', $serieId);
        $clase3 = $this->crearClase('2026-11-11', '17:00', '18:00', $serieId);

        $alumno = $this->crearAlumno();
        // Clase 1 tiene asistencia registrada
        Asistencia::create([
            'clase_id' => $clase1->id,
            'alumno_id' => $alumno->id,
            'presente' => false, // ausente también cuenta como lista tomada
        ]);

        // Intento de cancelar serie sin confirmar
        $res = $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase1->id), [
            'motivo_cancelacion' => 'Refacción de pista',
            'cancelar_serie' => true,
        ]);

        $res->assertStatus(409)->assertJson([
            'success' => false,
            'requiere_confirmacion' => true,
            'con_lista' => 1,
        ]);
        $this->assertStringContainsString('1 clase de la serie ya tiene la lista tomada', $res->json('message'));
        $this->assertFalse((bool) $clase1->fresh()->cancelada);
        $this->assertFalse((bool) $clase2->fresh()->cancelada);

        // Con confirmar: true
        $resConfirmado = $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase1->id), [
            'motivo_cancelacion' => 'Refacción de pista',
            'cancelar_serie' => true,
            'confirmar' => true,
        ]);

        $resConfirmado->assertOk();
        $this->assertTrue((bool) $clase1->fresh()->cancelada);
        $this->assertTrue((bool) $clase2->fresh()->cancelada);
        $this->assertTrue((bool) $clase3->fresh()->cancelada);

        Carbon::setTestNow();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CLA-012.b: Al confirmar, las marcas quedan guardadas y no suma en liquidación/comisión
    // ──────────────────────────────────────────────────────────────────────────

    public function test_marcas_de_asistencia_quedan_guardadas_y_clase_no_cuenta_en_liquidacion(): void
    {
        $clase = $this->crearClase('2026-08-10', '17:00', '18:00');
        $alumno = $this->crearAlumno('Luciana');
        $asistencia = Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);

        // Cancelar con confirmación
        $this->actingAs($this->operativo)->patchJson(route('web.clases.toggle-cancelada', $clase->id), [
            'motivo_cancelacion' => 'Cancelación justificada',
            'confirmar' => true,
        ])->assertOk();

        // 1. Las marcas de asistencia siguen existiendo en la base de datos
        $this->assertDatabaseHas('asistencias', [
            'id' => $asistencia->id,
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);

        // 2. Liquidación: LiquidacionService no incluye clases canceladas
        $liqService = app(LiquidacionService::class);
        $liq = $liqService->generarLiquidacionMensual($this->profesor->id, 8, 2026);

        // No hay detalles de clases liquidables porque la única clase del mes está cancelada
        $this->assertSame(0, $liq->detalles->count());
        $this->assertEquals(0, $liq->total_calculado);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CLA-015.b: Choque de horario al reactivar con otra clase activa
    // ──────────────────────────────────────────────────────────────────────────

    public function test_reactivar_falla_si_la_profesora_ya_tiene_otra_clase_activa_que_se_superpone(): void
    {
        // Clase 1 cancelada (17:00 a 18:00)
        $clase1 = $this->crearClase('2026-10-28', '17:00', '18:00');
        $clase1->update(['cancelada' => true, 'motivo_cancelacion' => 'Lluvia']);

        // Mientras estuvo cancelada, se le asignó otra clase activa a la misma profesora en el mismo horario
        $otroNivel = Nivel::create(['nombre' => 'Competición']);
        $otroGrupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $otroNivel->id,
            'nombre' => 'Patín Competición',
            'activo' => true,
        ]);
        $clase2 = Clase::create([
            'grupo_id' => $otroGrupo->id,
            'fecha' => '2026-10-28',
            'hora_inicio' => '17:30',
            'hora_fin' => '18:30',
            'cancelada' => false,
        ]);
        $clase2->profesores()->attach($this->profesor->id);

        // El admin intenta reactivar la clase 1
        $res = $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase1->id));

        $res->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('No se puede reactivar', $res->json('message'));
        $this->assertStringContainsString('Lucía Sosa', $res->json('message'));
        $this->assertStringContainsString('Competición', $res->json('message'));
        $this->assertTrue((bool) $clase1->fresh()->cancelada);
    }

    public function test_reactivar_permite_si_la_otra_clase_superpuesta_esta_cancelada(): void
    {
        // Clase 1 cancelada
        $clase1 = $this->crearClase('2026-10-28', '17:00', '18:00');
        $clase1->update(['cancelada' => true, 'motivo_cancelacion' => 'Motivo A']);

        // Otra clase en el mismo horario pero TAMBIÉN cancelada
        $clase2 = $this->crearClase('2026-10-28', '17:00', '18:00');
        $clase2->update(['cancelada' => true, 'motivo_cancelacion' => 'Motivo B']);

        // Reactivar clase 1 debe permitirse porque clase 2 no está activa
        $res = $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase1->id));

        $res->assertOk()->assertJson(['success' => true, 'message' => 'Clase reactivada.']);
        $this->assertFalse((bool) $clase1->fresh()->cancelada);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CLA-015.c: Conservar que estuvo cancelada y su motivo
    // ──────────────────────────────────────────────────────────────────────────

    public function test_al_reactivar_se_conserva_el_motivo_en_la_base_y_se_muestra_en_la_ficha(): void
    {
        $clase = $this->crearClase('2026-10-28', '17:00', '18:00');
        $clase->update(['cancelada' => true, 'motivo_cancelacion' => 'Feriado imprevisto']);

        // Reactivar
        $this->actingAs($this->admin)->patch(route('web.clases.toggle-cancelada', $clase->id))
            ->assertRedirect();

        $clase->refresh();
        $this->assertFalse((bool) $clase->cancelada);
        // Motivo conservado en base de datos: no es null
        $this->assertSame('Feriado imprevisto', $clase->motivo_cancelacion);

        // La vista show muestra el rastro de que estuvo cancelada
        $vistaRes = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $vistaRes->assertOk();
        $vistaRes->assertSee('Estuvo cancelada: Feriado imprevisto');
        $vistaRes->assertSee('Activa');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CLA-015.a y d: Vuelve a programada, permite asistencia y cuenta en listado y «Sin asistencia»
    // ──────────────────────────────────────────────────────────────────────────

    public function test_reactivada_permite_guardar_asistencias(): void
    {
        Carbon::setTestNow('2026-10-28 17:30:00');
        $clase = $this->crearClase('2026-10-28', '17:00', '18:00');
        $alumno = $this->crearAlumno('Sofía');

        $clase->update(['cancelada' => true, 'motivo_cancelacion' => 'Lluvia']);

        // Cuando está cancelada, guardar asistencia es rechazado
        $this->actingAs($this->operativo)->postJson(route('web.clases.asistencias', $clase->id), [
            'items' => [['alumno_id' => $alumno->id, 'presente' => 1]],
        ])->assertStatus(422);

        // Reactivar
        $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase->id))->assertOk();

        // Ahora reactivada, permite tomar y guardar asistencia
        $resAsistencia = $this->actingAs($this->operativo)->postJson(route('web.clases.asistencias', $clase->id), [
            'items' => [['alumno_id' => $alumno->id, 'presente' => 1]],
        ]);

        $resAsistencia->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('asistencias', [
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => 1,
        ]);

        Carbon::setTestNow();
    }

    public function test_reactivada_pasada_vuelve_a_contar_en_sin_asistencia(): void
    {
        Carbon::setTestNow('2026-10-28 12:00:00');

        // Clase de ayer sin asistencias y no validada
        $claseAyer = $this->crearClase('2026-10-27', '17:00', '18:00');
        $claseAyer->update(['cancelada' => true, 'motivo_cancelacion' => 'Paro']);

        // Con cancelada=true, no aparece en filtro finalizada (sin lista tomada)
        $resCancelada = $this->actingAs($this->admin)->get(route('web.clases.index', ['estado' => 'finalizada']));
        $resCancelada->assertOk();
        $this->assertFalse(collect($resCancelada->viewData('clasesFiltradas')->items())->pluck('id')->contains($claseAyer->id));

        // Reactivar
        $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $claseAyer->id))->assertOk();

        // Con cancelada=false, vuelve a aparecer en «Sin asistencia / Sin resolver»
        $resReactivada = $this->actingAs($this->admin)->get(route('web.clases.index', ['estado' => 'finalizada']));
        $resReactivada->assertOk();
        $this->assertTrue(collect($resReactivada->viewData('clasesFiltradas')->items())->pluck('id')->contains($claseAyer->id));

        Carbon::setTestNow();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Permisos de roles
    // ──────────────────────────────────────────────────────────────────────────

    public function test_operativo_no_puede_reactivar_y_profesor_no_puede_cancelar_ni_reactivar(): void
    {
        $clase = $this->crearClase('2026-10-28', '17:00', '18:00');
        $clase->update(['cancelada' => true, 'motivo_cancelacion' => 'Cierre']);

        // Operativo no puede reactivar (solo admin)
        $this->actingAs($this->operativo)->patchJson(route('web.clases.toggle-cancelada', $clase->id))
            ->assertStatus(403);

        // Profesor no puede reactivar
        $this->actingAs($this->profesorUser)->patchJson(route('web.clases.toggle-cancelada', $clase->id))
            ->assertStatus(403);

        $clase->update(['cancelada' => false]);

        // Profesor no puede cancelar
        $this->actingAs($this->profesorUser)->patchJson(route('web.clases.toggle-cancelada', $clase->id), [
            'motivo_cancelacion' => 'Falta profesor',
        ])->assertStatus(403);
    }
}
