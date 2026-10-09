<?php

namespace Database\Seeders;

use App\Models\{Alumno, Asistencia, Clase, Grupo, Profesor};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Amplía el escenario financiero sin alterar sus importes. Solo base descartable. */
class ReportesAsistenciaEscenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDatabaseName() !== 'wings_testing_codex' || !Alumno::exists() || Asistencia::exists()) {
            throw new RuntimeException('Requiere escenario financiero sin asistencias en wings_testing_codex.');
        }
        $reloj = Carbon::getTestNow();
        try {
            $profesores = [];
            foreach (Grupo::orderBy('id')->get() as $i => $grupo) {
                $base = Profesor::firstOrFail();
                $profesores[$grupo->id] = $i === 0 ? $base : Profesor::create([
                    'deporte_id' => $grupo->deporte_id, 'nombre' => 'Docente '.($i + 1), 'apellido' => 'Prueba',
                    'dni' => (string) (31000001 + $i), 'fecha_nacimiento' => '1980-01-01',
                    'direccion' => 'Domicilio ficticio', 'localidad' => 'Prueba',
                    'valor_hora' => 12000 + $i * 1000, 'porcentaje_comision' => 20, 'activo' => true,
                ]);
            }
            foreach (range(4, 10) as $mes) {
                $periodo = sprintf('2026-%02d', $mes);
                Carbon::setTestNow($periodo.'-09 10:00:00');
                foreach (Grupo::with('alumnos')->orderBy('id')->get() as $indice => $grupo) {
                    $dias = $mes === 10 ? [1, 3, 5, 8] : [1, 4, 8, 11, 15, 18, 22, 25];
                    foreach ($dias as $n => $dia) {
                        $clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => sprintf('%s-%02d', $periodo, $dia),
                            'hora_inicio' => '18:00:00', 'hora_fin' => '19:00:00',
                            'validada_para_liquidacion' => false, 'cancelada' => false]);
                        $clase->profesores()->attach($profesores[$grupo->id]->id);
                        foreach ($grupo->alumnos->values() as $i => $alumno) {
                            // Registro real de presente/ausente; mejora gradual a lo largo del semestre.
                            $presente = (($n + $i + $indice) % 10) >= (10 - $mes);
                            if ($mes === 10 && $i === 5) $presente = false;
                            Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => $presente]);
                        }
                    }
                    $cancelada = Clase::create(['grupo_id' => $grupo->id, 'fecha' => $periodo.'-02',
                        'hora_inicio' => '18:00:00', 'hora_fin' => '19:00:00', 'cancelada' => true]);
                    // Un registro en una clase cancelada no debe entrar en ningún indicador.
                    Asistencia::create(['clase_id' => $cancelada->id, 'alumno_id' => $grupo->alumnos->first()->id, 'presente' => true]);
                }
            }
            Carbon::setTestNow('2026-10-09 10:00:00');
            foreach (Alumno::orderByDesc('id')->take(2)->get() as $alumno) $alumno->update(['activo' => false]);
        } finally {
            Carbon::setTestNow($reloj);
        }
    }
}
