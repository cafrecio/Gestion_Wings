<?php

namespace Tests\Feature;

/** Respuestas reales de Laravel. Ejecutar aparte, antes de la suite, en la base Codex. */
class CapturasT66Test extends ClaseProfesoraObligatoriaT66Test
{
    public function createApplication()
    {
        $app = parent::createApplication();
        if (!$app->environment('testing') || $app['db']->connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('Capturas exclusivas de testing/wings_testing_codex.');
        }
        return $app;
    }

    public function test_exportar_capturas_t66(): void
    {
        $this->assertSame('wings_testing_codex', \DB::connection()->getDatabaseName());
        $dir = __DIR__.'/html';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $guardar = function (string $slug, $respuesta) use ($dir) {
            $respuesta->assertOk();
            $html = preg_replace('#https?://[^/]+/(build/)#', '/$1', $respuesta->getContent());
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $html = preg_replace('#/build/assets/app-[^"\s]+\.css#', '/build/'.$manifest['resources/css/app.css']['file'], $html);
            file_put_contents($dir.'/'.$slug.'.html', $html);
        };
        $this->from('/clases/create')->post('/clases', array_replace($this->datos(), ['profesores' => []]))->assertSessionHasErrors('profesores');
        $guardar('nueva-error', $this->get('/clases/create')->assertSee('Seleccioná al menos una profesora activa del deporte de la clase.'));
        $clase = $this->clase();
        $this->from('/clases/'.$clase->id.'/edit')->put('/clases/'.$clase->id, array_replace($this->datos(), ['profesores' => []]))->assertSessionHasErrors('profesores');
        $guardar('editar-error', $this->get('/clases/'.$clase->id.'/edit')->assertSee('Seleccioná al menos una profesora activa del deporte de la clase.'));
        $guardar('cambiar-profesoras', $this->get('/clases/'.$clase->id));
        \Auth::forgetGuards();
        $guardar('login-control', $this->get('/login'));
        file_put_contents(__DIR__.'/respuestas.json', json_encode(['metodo' => 'GET Laravel autenticado después de POST/PUT inválidos reales; ficha de clase real para abrir panel', 'casos' => ['nueva-error', 'editar-error', 'cambiar-profesoras', 'login-control']], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }
}
