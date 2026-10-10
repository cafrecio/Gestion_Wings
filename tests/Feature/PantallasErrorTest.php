<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PantallasErrorTest extends TestCase
{
    use RefreshDatabase;

    private const TITULOS = [
        404 => 'Página no disponible',
        429 => 'Esperá un momento',
        500 => 'Algo falló',
        503 => 'Estamos actualizando Wings',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        foreach ([404, 429, 500, 503] as $status) {
            Route::middleware('web')->get('/__test/error/'.$status, function () use ($status) {
                if ($status === 500) {
                    throw new \RuntimeException('FICTICIO-T15: detalle privado del fallo');
                }
                abort($status, '', ['Retry-After' => '60']);
            });
        }
    }

    private function perfil(?string $rol): string
    {
        Auth::forgetGuards();
        $this->app['session']->flush();
        if ($rol !== null) {
            $this->actingAs(User::factory()->create(['rol' => $rol, 'activo' => true]));
        }
        return match ($rol) {
            'ADMIN' => route('admin.dashboard'),
            'OPERATIVO' => route('web.operativo.dashboard'),
            'PROFESOR' => route('web.clases.index'),
            default => route('login'),
        };
    }

    public function test_las_cuatro_pantallas_en_castellano_para_cada_perfil(): void
    {
        foreach (['ADMIN', 'OPERATIVO', 'PROFESOR', null] as $rol) {
            $inicio = $this->perfil($rol);
            foreach (self::TITULOS as $status => $titulo) {
                $response = $this->get('/__test/error/'.$status);
                $response->assertStatus($status)->assertSee($titulo)->assertSee('Volver')
                    ->assertDontSee('FICTICIO-T15')->assertDontSee('Server Error')->assertDontSee('NOT FOUND');
                $destino = in_array($status, [500, 503], true) ? route('login') : $inicio;
                $response->assertSee('href="'.$destino.'"', false);
                $this->assertMatchesRegularExpression('/<h1\b[^>]*>\s*'.preg_quote($titulo, '/').'\s*<\/h1>/u', $response->getContent());
            }
        }
    }

    public function test_una_direccion_inventada_conserva_el_inicio_del_rol(): void
    {
        foreach (['ADMIN', 'OPERATIVO', 'PROFESOR', null] as $rol) {
            $inicio = $this->perfil($rol);
            $this->get('/direccion-inventada-t15')->assertNotFound()
                ->assertSee(self::TITULOS[404])->assertSee('href="'.$inicio.'"', false);
        }
    }

    public function test_registro_inexistente_conserva_permisos_y_login(): void
    {
        foreach (['ADMIN', 'OPERATIVO'] as $rol) {
            $this->perfil($rol);
            $this->get('/alumnos/999999')->assertNotFound()->assertSee(self::TITULOS[404]);
        }
        $this->perfil('PROFESOR');
        $this->get('/alumnos/999999')->assertForbidden()->assertSee('Sin permiso');
        $this->perfil(null);
        $this->get('/alumnos/999999')->assertRedirect(route('login'));
    }

    public function test_volver_desde_un_error_grave_recupera_el_inicio_de_cada_rol(): void
    {
        foreach (['ADMIN', 'OPERATIVO', 'PROFESOR'] as $rol) {
            $inicio = $this->perfil($rol);
            foreach ([500, 503] as $status) {
                $this->get('/__test/error/'.$status)->assertStatus($status)
                    ->assertSee('href="'.route('login').'"', false);
                $this->get(route('login'))->assertRedirect($inicio);
            }
        }
        $this->perfil(null);
        $this->get(route('login'))->assertOk()->assertSee('Ingresar');
    }

    public function test_las_respuestas_json_no_reciben_html_para_ningun_perfil(): void
    {
        foreach (['ADMIN', 'OPERATIVO', 'PROFESOR', null] as $rol) {
            $this->perfil($rol);
            foreach (self::TITULOS as $status => $titulo) {
                $this->getJson('/__test/error/'.$status)->assertStatus($status)
                    ->assertHeader('Content-Type', 'application/json')->assertDontSee('<!DOCTYPE html>', false);
            }
        }
    }

    public function test_api_sigue_en_json_incluso_si_se_pide_html(): void
    {
        foreach (self::TITULOS as $status => $titulo) {
            Route::get('/api/__test/error/'.$status, fn () => abort($status));
            $this->get('/api/__test/error/'.$status, ['Accept' => 'text/html'])
                ->assertStatus($status)->assertHeader('Content-Type', 'application/json');
        }
        $this->get('/api/alumnos', ['Accept' => 'text/html'])->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_500_y_503_no_consultan_base_autenticacion_ni_sesion(): void
    {
        $consultas = [];
        DB::listen(function ($query) use (&$consultas) { $consultas[] = $query->sql; });
        Auth::shouldReceive('check')->never();
        Auth::shouldReceive('user')->never();
        foreach ([500, 503] as $status) {
            $request = Request::create('/error-sin-base');
            $request->setUserResolver(fn () => throw new \LogicException('No consultar usuario'));
            $this->app->instance('request', $request);
            $response = $this->app->make(ExceptionHandler::class)->render($request, new HttpException($status));
            $this->assertSame($status, $response->getStatusCode());
            $this->assertStringContainsString(self::TITULOS[$status], $response->getContent());
            $this->assertFalse($request->hasSession());
        }
        $this->assertSame([], $consultas);
    }

    public function test_render_directo_de_mantenimiento_no_consulta_sesion_ni_base(): void
    {
        $consultas = [];
        DB::listen(function ($query) use (&$consultas) { $consultas[] = $query->sql; });
        Auth::shouldReceive('check')->never();
        Auth::shouldReceive('user')->never();
        $this->assertStringContainsString(self::TITULOS[503], view('errors.503')->render());
        $this->assertSame([], $consultas);
    }

    public function test_conserva_retry_after_en_429_y_503(): void
    {
        foreach ([429, 503] as $status) {
            $this->get('/__test/error/'.$status)->assertStatus($status)->assertHeader('Retry-After', '60');
        }
    }

    public function test_error_grave_no_filtra_detalles_aun_con_debug_activo(): void
    {
        config(['app.debug' => true]);
        $this->get('/__test/error/500')->assertStatus(500)->assertSee(self::TITULOS[500])
            ->assertDontSee('FICTICIO-T15')->assertDontSee('RuntimeException');
        $this->get('/direccion-inventada-t15')->assertNotFound()->assertSee(self::TITULOS[404]);
    }

    public function test_conserva_respuestas_de_validacion_y_sesion_expirada(): void
    {
        Route::middleware('web')->get('/__test/validacion', function () {
            throw ValidationException::withMessages(['dato' => 'Dato requerido']);
        });
        Route::middleware('web')->get('/__test/expirada', fn () => abort(419));
        $this->getJson('/__test/validacion')->assertUnprocessable()->assertJsonValidationErrors('dato');
        $this->get('/__test/expirada')->assertRedirect(route('login'))->assertSessionHas('error');
        $this->getJson('/__test/expirada')->assertStatus(419)->assertHeader('Content-Type', 'application/json');
    }

    public function test_inicio_coincide_con_el_aviso_de_primera_carga(): void
    {
        $this->perfil('ADMIN');
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'PENDIENTE']);
        $this->get(route('admin.dashboard'))->assertRedirect(route('web.primera-carga.index'));
        $this->get(route('web.primera-carga.index'))->assertOk()
            ->assertSee('Inicio, Alumnos y Reportes')->assertSee('Inicio')->assertDontSee('Dashboard');
    }

    public function test_fallos_500_se_registran_y_los_otros_http_no_llenan_el_log(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);
        $this->assertTrue($handler->shouldReport(new \RuntimeException('FICTICIO-T15 interno')));
        $this->assertTrue($handler->shouldReport(new HttpException(500, 'FICTICIO-T15 HTTP')));
        foreach ([403, 404, 419, 429, 503] as $status) {
            $this->assertFalse($handler->shouldReport(new HttpException($status)));
        }
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('error')->twice();
        $this->app->instance(\Psr\Log\LoggerInterface::class, $logger);
        $handler->report(new \RuntimeException('FICTICIO-T15 interno'));
        $handler->report(new HttpException(500, 'FICTICIO-T15 HTTP'));
    }
}
