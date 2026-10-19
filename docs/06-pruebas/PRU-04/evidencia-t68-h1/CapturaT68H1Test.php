<?php

namespace Tests\Feature;

use App\Models\Profesor;

// Reutiliza la regresion permanente y guarda exclusivamente respuestas reales de Laravel.
class CapturaT68H1Test extends AlumnoSinPlanT56T68Test
{
    protected function setUp(): void
    {
        if (getenv('DB_DATABASE') !== 'wings_testing_codex') {
            throw new \RuntimeException('La captura exige wings_testing_codex antes de iniciar Laravel.');
        }
        parent::setUp();
    }

    public function test_captura_correccion_h1(): void
    {
        $this->test_ficha_profesor_muestra_importe_vigente_tras_ajuste_t68_h1();
        $profesor = Profesor::where('dni', '30111222')->firstOrFail();
        $this->guardar('profesor-ajuste', $this->get(route('web.profesores.show', $profesor->id))
            ->assertOk()->getContent());
        auth()->logout();
        $this->guardar('login-control', $this->get('/login')->assertOk()->getContent());
    }

    private function guardar(string $nombre, string $html): void
    {
        $html = preg_replace_callback('~(?:https?://[^/"\s]+)?/build/([^"\s]+)~',
            fn ($m) => 'file:///'.str_replace('\\', '/', public_path('build/'.$m[1])), $html);
        $html = preg_replace_callback('~https?://[^/"\s]+/img/([^"\s]+)~',
            fn ($m) => 'file:///'.str_replace('\\', '/', public_path('img/'.$m[1])), $html);
        $html = preg_replace('/(name="_token"[^>]*value=")[^"]*/', '$1TOKEN_SINTETICO_OMITIDO', $html);
        $html = preg_replace('/(name="csrf-token"[^>]*content=")[^"]*/', '$1TOKEN_SINTETICO_OMITIDO', $html);
        file_put_contents(__DIR__.'/'.$nombre.'.html', $html);
    }
}
