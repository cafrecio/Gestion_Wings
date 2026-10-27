<?php

namespace Tests\Feature;

use App\Models\{Alumno, DeudaCuota, PrimeraCarga, Profesor, User};

/** HTML real de la aplicación, para renderizarlo con Chrome (§1 de AGENTS). */
class CapturaB3PrimeraCargaTest extends B3PrimeraCargaTest
{
    public function test_capturar_lista_de_primera_carga_corregida(): void
    {
        if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex'
            || \Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('Captura exclusiva de testing local/wings_testing_codex.');
        }
        $this->withVite();
        $this->alumno->update(['nombre' => 'Inicial al día']);
        $importados = [$this->alumno->id];
        foreach ([['Inicial deudor', '2023-03-10', '2026-09'], ['Inicial moroso', '2023-03-10', '2026-10'],
            ['Inicial en plazo', '2026-10-26', '2026-10']] as [$nombre, $alta, $periodo]) {
            $a = $this->crearAlumno($nombre, $alta);
            $importados[] = $a->id;
            DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        }
        PrimeraCarga::whereKey(1)->update(['detalle' => ['alumnos' => $importados]]);
        $posterior = $this->crearAlumno('Alta posterior', '2026-10-24');
        DeudaCuota::create(['alumno_id' => $posterior->id, 'periodo' => '2026-10', 'monto_original' => 7000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $prof = Profesor::create(['nombre' => 'Profesora', 'apellido' => 'Prueba PC', 'dni' => '30999883', 'fecha_nacimiento' => '1980-01-01',
            'deporte_id' => $this->grupo->deporte_id, 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true]);
        $this->clase->profesores()->attach($prof->id);
        $user = User::factory()->create(['name' => 'Profesora de prueba PC', 'rol' => 'PROFESOR', 'profesor_id' => $prof->id, 'activo' => true]);
        $html = $this->actingAs($user)->get(route('web.clases.show', $this->clase->id))->assertOk()->getContent();
        file_put_contents(__DIR__.'/lista-real.html', $html);
        $this->app['auth']->guard()->logout();
        file_put_contents(__DIR__.'/login-real.html', $this->get(route('login'))->assertOk()->getContent());
        $condiciones = app(\App\Services\ExcepcionCobranzaService::class)->condicionesAsistencia(Alumno::all(), $this->clase);
        foreach ($importados as $id) $this->assertFalse($condiciones[$id]['nuevo']);
        $this->assertTrue($condiciones[$posterior->id]['nuevo']);
        file_put_contents(__DIR__.'/datos-captura.json', json_encode(['base' => 'wings_testing_codex', 'primera_carga' => $importados,
            'posterior' => $posterior->id, 'condiciones' => $condiciones], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
    }
}
