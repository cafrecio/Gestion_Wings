<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapturaT21Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $userProfesor;
    private Profesor $profesorLucia;
    private Profesor $profesorMariela;
    private Grupo $grupoPatin;
    private Grupo $grupoFutbol;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-12 10:00:00'));

        $this->admin = User::factory()->create([
            'rol' => 'ADMIN',
            'activo' => true,
            'name' => 'Carlos Administrador',
        ]);

        $this->operativo = User::factory()->create([
            'rol' => 'OPERATIVO',
            'activo' => true,
            'name' => 'Sandra Mostrador',
        ]);

        $deportePatin = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);

        $deporteFutbol = Deporte::create([
            'nombre' => 'Fútbol',
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);

        $nivelPrincipiantes = Nivel::create(['nombre' => 'Principiantes']);
        $nivelIntermedio = Nivel::create(['nombre' => 'Intermedias']);

        $this->grupoPatin = Grupo::create([
            'deporte_id' => $deportePatin->id,
            'nivel_id' => $nivelPrincipiantes->id,
            'activo' => true,
        ]);

        $this->grupoFutbol = Grupo::create([
            'deporte_id' => $deporteFutbol->id,
            'nivel_id' => $nivelIntermedio->id,
            'activo' => true,
        ]);

        $this->profesorLucia = Profesor::create([
            'deporte_id' => $deportePatin->id,
            'nombre' => 'Lucía',
            'apellido' => 'Gaitán',
            'dni' => '35111222',
            'fecha_nacimiento' => '1995-05-10',
            'direccion' => 'Calle 1',
            'localidad' => 'Ciudad',
            'valor_hora' => 2000,
            'activo' => true,
        ]);

        $this->profesorMariela = Profesor::create([
            'deporte_id' => $deporteFutbol->id,
            'nombre' => 'Mariela',
            'apellido' => 'Salinas',
            'dni' => '35333444',
            'fecha_nacimiento' => '1996-06-12',
            'direccion' => 'Calle 2',
            'localidad' => 'Ciudad',
            'valor_hora' => 2000,
            'activo' => true,
        ]);

        $this->userProfesor = User::factory()->create([
            'rol' => 'PROFESOR',
            'activo' => true,
            'name' => 'Lucía Gaitán',
            'profesor_id' => $this->profesorLucia->id,
        ]);
    }

    private function guardarHtml(string $nombre, string $html): void
    {
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t21');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $buildPath = str_replace('\\', '/', public_path('build'));
        $htmlAjustado = str_replace(['src="/build/', 'href="/build/'], [
            'src="' . $buildPath . '/',
            'href="' . $buildPath . '/',
        ], $html);

        file_put_contents("{$dir}/{$nombre}.html", $htmlAjustado);

        // Generar también el marco de 360px
        $marcoHtml = <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Wings · {$nombre} (360px)</title>
</head>
<body style="margin:0;background:#888;display:flex;justify-content:center;padding:24px 0;">
<iframe src="{$nombre}.html" style="width:360px;height:1150px;border:0;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.35);border-radius:16px;"></iframe>
</body>
</html>
HTML;
        file_put_contents("{$dir}/{$nombre}-marco-360.html", $marcoHtml);
    }

    public function test_generar_htmls_para_capturas(): void
    {
        // ── 1. Escenario Parcial: 1 activa (16:00) y 1 cancelada (17:00) ─────
        $cActiva = Clase::create([
            'grupo_id' => $this->grupoFutbol->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => false,
        ]);
        $cActiva->profesores()->attach($this->profesorMariela->id);

        $cCancelada = Clase::create([
            'grupo_id' => $this->grupoPatin->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '17:00',
            'hora_fin' => '18:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado 12 de octubre: el club no abre',
        ]);
        $cCancelada->profesores()->attach($this->profesorLucia->id);

        // Clase futura para el listado inferior
        $cFutura = Clase::create([
            'grupo_id' => $this->grupoPatin->id,
            'fecha' => '2026-10-14',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => false,
        ]);
        $cFutura->profesores()->attach($this->profesorLucia->id);

        // Admin parcial
        $htmlAdminParcial = $this->actingAs($this->admin)->get(route('web.clases.index'))->getContent();
        $this->guardarHtml('admin-parcial', $htmlAdminParcial);

        // Operativo parcial
        $htmlOpParcial = $this->actingAs($this->operativo)->get(route('web.clases.index'))->getContent();
        $this->guardarHtml('operativo-parcial', $htmlOpParcial);

        // Profesora parcial
        $htmlProfParcial = $this->actingAs($this->userProfesor)->get(route('web.clases.index'))->getContent();
        $this->guardarHtml('profesora-parcial', $htmlProfParcial);

        // ── 2. Escenario Todas Canceladas ────────────────────────────────────
        $cActiva->update([
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado 12 de octubre: el club no abre',
        ]);

        // Admin todas canceladas
        $htmlAdminTodas = $this->actingAs($this->admin)->get(route('web.clases.index'))->getContent();
        $this->guardarHtml('admin-todas-canceladas', $htmlAdminTodas);

        // Operativo todas canceladas
        $htmlOpTodas = $this->actingAs($this->operativo)->get(route('web.clases.index'))->getContent();
        $this->guardarHtml('operativo-todas-canceladas', $htmlOpTodas);

        // Profesora todas canceladas
        $htmlProfTodas = $this->actingAs($this->userProfesor)->get(route('web.clases.index'))->getContent();
        $this->guardarHtml('profesora-todas-canceladas', $htmlProfTodas);

        // ── 3. Control: Login ───────────────────────────────────────────────
        auth()->logout();
        $htmlLogin = $this->get('/login')->getContent();
        $this->guardarHtml('login-control', $htmlLogin);

        $this->assertTrue(true);
    }
}
