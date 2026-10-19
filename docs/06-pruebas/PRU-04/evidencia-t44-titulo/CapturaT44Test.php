<?php
namespace Tests\CapturasT44Titulo;

use App\Models\{Alumno, AlumnoPlan, GrupoPlan, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, URL, Auth};

require_once dirname(__DIR__, 4).'/tests/Feature/PrecioPlanVigenciaT44Test.php';

/** Instrumental fuera de la suite permanente: respuestas de las rutas reales. */
class CapturaT44Test extends \Tests\Feature\PrecioPlanVigenciaT44Test
{
    public function createApplication()
    {
        $app = parent::createApplication();
        if (!$app->environment('testing') || $app['db']->connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('Instrumental exclusivo de testing/wings_testing_codex.');
        }
        return $app;
    }

    public function test_capturas_reales_t44(): void
    {
        $this->assertSame('wings_testing_codex', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
        Carbon::setTestNow('2026-10-20 12:00:00');
        URL::forceRootUrl('http://127.0.0.1:8744');
        $plan = GrupoPlan::whereHas('grupo.nivel', fn ($q) => $q->where('nombre', 'Inicial T44'))->where('clases_por_semana', 2)->sole();
        $alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Vigencia', 'dni' => '44999999',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1144000000', 'fecha_alta' => '2026-09-01',
            'deporte_id' => $plan->grupo->deporte_id, 'grupo_id' => $plan->grupo_id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        $plan->update(['precio_mensual' => 45000]);
        $bajo = GrupoPlan::create(['grupo_id' => $plan->grupo_id, 'clases_por_semana' => 1, 'precio_mensual' => 20000, 'activo' => true]);
        GrupoPlan::create(['grupo_id' => $plan->grupo_id, 'clases_por_semana' => 3, 'precio_mensual' => 60000, 'activo' => true]);
        $admin = User::where('rol', User::ROL_ADMIN)->latest('id')->firstOrFail();
        $admin->update(['name' => 'Administración T44']);
        $casos = [
            'planes-editar' => route('web.grupos.edit', $plan->grupo_id),
            'planes-nuevo' => route('web.grupos.create'),
            'login' => route('login'),
        ];
        $directorio = base_path('docs/06-pruebas/PRU-04/evidencia-t44-titulo/html');
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        $evidencia = [];
        $programado = false;
        foreach ($casos as $slug => $url) {
            if (str_contains($slug, 'programada') && !$programado) {
                app(\App\Services\CambioPlanService::class)->cambiar($alumno, $bajo);
                $programado = true;
            }
            if ($slug === 'cobrar-parcial') {
                app(\App\Services\PagoCuotaService::class)->crearDeudaSiNoExiste($alumno->id, '2026-10', 40000)
                    ->update(['monto_pagado' => 10000]);
            }
            $this->flushSession();
            Auth::forgetGuards();
            if ($slug !== 'login') $this->actingAs($admin);
            $respuesta = $this->get($url)->assertOk();
            $html = str_replace("\r\n", "\n", $respuesta->getContent());
            file_put_contents($directorio.'/'.$slug.'.html', $html);
            $evidencia[] = ['slug' => $slug, 'url' => $url, 'estado' => $respuesta->status(), 'sha256' => hash('sha256', $html), 'normalizacion' => 'CRLF a LF'];
        }
        file_put_contents(dirname($directorio).'/respuestas.json', json_encode($evidencia, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}
