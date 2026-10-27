<?php

namespace Tests\Feature;

// Verificacion de B3 (Claude, 27/10/2026). Un alumno de la primera carga, al dia y sin cobros
// registrados en Wings (pago antes de que Wings existiera), toma su tercera clase.
// Contrato §4, enmienda del 06/09: los alumnos de la primera carga no son nuevos.
// Se corre por ruta: DB_DATABASE=wings_testing_claude php artisan test <este archivo>

use App\Models\{Alumno, AlumnoPlan, Asistencia, Clase, Deporte, Grupo, GrupoPlan, Nivel, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Tests\TestCase;

class VerificacionB3PrimeraCargaClaudeTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumno_de_la_primera_carga_al_dia_entra_a_su_tercera_clase_sin_justificar(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 18:00:00'));
        Notification::fake();
        $operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín PC', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'PC'])->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 28000, 'activo' => true]);
        $alumno = Alumno::create(['nombre' => 'Lucía', 'apellido' => 'Gimenez', 'dni' => '45100339', 'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id, 'fecha_alta' => '2023-03-10', 'fecha_nacimiento' => '2012-01-01', 'celular' => '1100000000', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2023-03-10', 'activo' => true]);
        // Igual que en el sitio de prueba: la primera carga terminada lista al alumno.
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA',
            'detalle' => json_encode(['alumnos' => [$alumno->id]])]);
        foreach ([20, 13] as $dia) {
            $previa = Clase::create(['grupo_id' => $grupo->id, 'fecha' => "2026-10-$dia", 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
            Asistencia::create(['clase_id' => $previa->id, 'alumno_id' => $alumno->id, 'presente' => true]);
        }
        $clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => '2026-10-27', 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);

        $respuesta = $this->actingAs($operativa)->postJson(route('web.clases.asistencias', $clase->id),
            ['items' => [['alumno_id' => $alumno->id, 'presente' => true]]]);

        $respuesta->assertOk();
        $this->assertDatabaseHas('asistencias', ['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => true]);
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }
}
