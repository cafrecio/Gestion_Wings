<?php

namespace Tests\Verificacion;

require_once dirname(__DIR__, 5).'/tests/Feature/ProgramarClasesA15A16Test.php';

use App\Models\{Clase, Grupo, Profesor};
use Illuminate\Support\Facades\{Auth, DB};
use Tests\Feature\ProgramarClasesA15A16Test;

/** Solo captura: usar --filter=test_preparar_capturas_de_la_implementacion. */
class A15A16CapturasTest extends ProgramarClasesA15A16Test
{
    public function test_preparar_capturas_de_la_implementacion(): void
    {
        $this->test_a16_seis_cargas_guardan_las_76_clases_y_seis_series();
        $grupo = Grupo::whereHas('deporte', fn ($q) => $q->where('nombre', 'Patín'))
            ->whereHas('nivel', fn ($q) => $q->where('nombre', 'Intermedias'))->sole();
        $profesor = Profesor::where('apellido', 'Salinas')->sole();
        $entrada = ['tipo_creacion' => 'recurrente', 'grupo_id' => $grupo->id, 'profesores' => [$profesor->id],
            'fecha_desde' => '2026-09-24', 'fecha_hasta' => '2026-10-31', 'dias_semana' => [1, 5],
            'horarios' => [1 => ['hora_inicio' => '17:00', 'hora_fin' => '18:00'], 5 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00']]];
        Auth::user()->update(['name' => 'Verificación Codex', 'email' => 'clases.verificacion@example.test']);
        $recurrente = $this->withSession(['_old_input' => $entrada])->get(route('web.clases.create'))->assertOk();
        file_put_contents(__DIR__.'/final-recurrente.html', $recurrente->getContent());

        $unica = ['tipo_creacion' => 'unica', 'grupo_id' => $grupo->id, 'profesores' => [$profesor->id],
            'fecha' => '2026-09-24', 'hora_inicio' => '17:30', 'hora_fin' => '18:30'];
        $this->post(route('web.clases.store'), $unica)->assertSessionHas('aviso_cancha');
        $this->assertDatabaseCount('clases', 76);
        $firma = session('aviso_cancha.firma');
        $aviso = $this->get(route('web.clases.create'))->assertOk()->assertSee('2 bloques de alquiler');
        file_put_contents(__DIR__.'/final-aviso.html', $aviso->getContent());
        $this->post(route('web.clases.store'), $unica + ['confirmar_cancha' => $firma])->assertSessionHas('success');
        $this->assertDatabaseCount('clases', 77);
        $filas = Clase::with('grupo.deporte', 'grupo.nivel')->orderBy('grupo_id')->orderBy('fecha')->get();
        file_put_contents(__DIR__.'/resultado-76-clases.json', json_encode($filas->filter(fn ($c) => $c->serie_id !== null)
            ->map(fn ($c) => ['grupo' => $c->grupo->nombre_completo, 'serie_id' => $c->serie_id,
                'fecha' => $c->fecha->toDateString(), 'inicio' => $c->hora_inicio->format('H:i'), 'fin' => $c->hora_fin->format('H:i')])->values()->all(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if (getenv('WINGS_VERIFICACION_HTTP') === '1') {
            $this->assertSame('wings_testing_codex', DB::connection()->getDatabaseName());
            DB::commit();
        }
    }
}
