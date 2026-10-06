<?php

namespace Tests\Feature;

require_once getcwd().'/tests/Feature/SaldoYAnulacionCoherentesTest.php';

/** Evidencia documental, fuera de la suite permanente. Ejecutar sobre a55-inscripcion. */
class GenerarCapturasA55Test extends SaldoYAnulacionCoherentesTest
{
    public function test_exporta_respuestas_reales_con_redaccion_aprobada(): void
    {
        $this->assertSame('wings_testing_codex', \Illuminate\Support\Facades\DB::connection()->getDatabaseName());
        $this->test_el_historial_de_un_cobro_anulado_muestra_los_meses_cobrados();
        $campos = [];
        foreach (['admin', 'patin', 'futbol'] as $campo) {
            $campos[$campo] = (new \ReflectionProperty(SaldoYAnulacionCoherentesTest::class, $campo))->getValue($this);
        }
        foreach (['patin', 'futbol'] as $deporte) {
            $html = $this->actingAs($campos['admin'])->get(route('web.alumnos.show', $campos[$deporte]->id))->assertOk()->getContent();
            if ($deporte === 'futbol') {
                $this->assertStringContainsString('La inscripción se cobra una sola vez, aunque el alumno practique varios deportes.', $html);
                $this->assertStringContainsString('Podés consultar el estado de ese cargo en su ficha de Patín.', $html);
            }
            file_put_contents(base_path('docs/06-pruebas/PRU-02/capturas-a55/ficha-'.$deporte.'.html'), $html);
        }
        $this->app['auth']->guard()->logout();
        $html = $this->get('/login')->assertOk()->getContent();
        file_put_contents(base_path('docs/06-pruebas/PRU-02/capturas-a55/login.html'), $html);
    }
}
