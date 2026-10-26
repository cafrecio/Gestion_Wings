<?php

namespace Tests\Feature;

use App\Models\{Clase, Deporte, Grupo, Nivel};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRU-04 · La rutina encuentra la clase del día por grupo y hora (Claude, 26/10/2026).
 * Misma consulta que scripts/pruebas/rutina/rutina.php. Fuera de tests/: no cambia el contador.
 *   DB_DATABASE=wings_testing_claude php artisan test docs/06-pruebas/PRU-04/prueba-tecnica/RutinaClasePorGrupoTest.php
 */
class RutinaClasePorGrupoTest extends TestCase
{
    use RefreshDatabase;

    private function buscar(string $grupo, string $hora)
    {
        [$dep, $niv] = array_map('trim', explode('—', $grupo, 2));
        return Clase::whereDate('fecha', today())->whereTime('hora_inicio', $hora.':00')->where('cancelada', false)
            ->whereHas('grupo', fn ($g) => $g->whereHas('deporte', fn ($d) => $d->where('nombre', $dep))
                ->whereHas('nivel', fn ($n) => $n->where('nombre', $niv)))->get();
    }

    public function test_encuentra_solo_la_clase_de_hoy_de_ese_grupo_y_hora(): void
    {
        Carbon::setTestNow('2026-10-26 15:00:00');
        $patin = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $prin = Nivel::create(['nombre' => 'Principiantes']);
        $gp = Grupo::create(['deporte_id' => $patin->id, 'nivel_id' => $prin->id, 'activo' => true]);
        $gf = Grupo::create(['deporte_id' => $futbol->id, 'nivel_id' => $prin->id, 'activo' => true]);
        $base = ['hora_inicio' => '16:00', 'hora_fin' => '17:00', 'cancelada' => false, 'validada_para_liquidacion' => false];
        $hoy = Clase::create($base + ['grupo_id' => $gp->id, 'fecha' => '2026-10-26']);
        Clase::create($base + ['grupo_id' => $gf->id, 'fecha' => '2026-10-26']);          // mismo horario, otro deporte
        Clase::create($base + ['grupo_id' => $gp->id, 'fecha' => '2026-10-19']);          // misma clase, otra semana
        Clase::create(['hora_inicio' => '17:00', 'hora_fin' => '18:00'] + $base + ['grupo_id' => $gp->id, 'fecha' => '2026-10-26']);

        $r = $this->buscar('Patín — Principiantes', '16:00');
        $this->assertSame([$hoy->id], $r->pluck('id')->all());
        $this->assertCount(0, $this->buscar('Patín — Avanzadas', '16:00'));
        Carbon::setTestNow();
    }
}
