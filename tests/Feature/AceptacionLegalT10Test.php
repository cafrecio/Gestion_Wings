<?php

namespace Tests\Feature;

use App\Models\AceptacionLegal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AceptacionLegalT10Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['legal.exigir' => true]);
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol' => $rol, 'activo' => true]);
    }

    public function test_cada_rol_tiene_que_aceptar_antes_de_entrar(): void
    {
        $destinos = [User::ROL_ADMIN => '/admin/dashboard', User::ROL_OPERATIVO => '/operativo', User::ROL_PROFESOR => '/clases'];

        foreach ($destinos as $rol => $destino) {
            $this->actingAs($this->usuario($rol))->get($destino)->assertRedirect(route('web.legal.aceptar'));
        }
    }

    public function test_al_aceptar_queda_la_constancia_y_entra(): void
    {
        $admin = $this->usuario(User::ROL_ADMIN);

        $this->actingAs($admin)->post(route('web.legal.guardar'), ['acepto' => '1'])->assertRedirect(route('login'));

        $constancia = AceptacionLegal::sole();
        $this->assertSame($admin->id, $constancia->user_id);
        $this->assertSame($admin->email, $constancia->email);
        $this->assertSame(User::ROL_ADMIN, $constancia->rol);
        $this->assertSame(config('legal.version'), $constancia->version);
        $this->assertSame(64, strlen($constancia->hash_contenido));
        $this->assertNotNull($constancia->ip);

        $this->actingAs($admin)->get(route('web.alumnos.index'))->assertOk();
    }

    public function test_sin_tildar_no_se_acepta(): void
    {
        $admin = $this->usuario(User::ROL_ADMIN);

        $this->actingAs($admin)->from(route('web.legal.aceptar'))->post(route('web.legal.guardar'), [])
            ->assertRedirect(route('web.legal.aceptar'))->assertSessionHasErrors('acepto');

        $this->assertSame(0, AceptacionLegal::count());
    }

    public function test_una_version_nueva_pide_aceptar_de_nuevo_y_conserva_la_anterior(): void
    {
        $admin = $this->usuario(User::ROL_ADMIN);
        $this->actingAs($admin)->post(route('web.legal.guardar'), ['acepto' => '1']);

        config(['legal.version' => '2099-01-01']);
        $this->actingAs($admin)->get(route('web.alumnos.index'))->assertRedirect(route('web.legal.aceptar'));

        $this->actingAs($admin)->post(route('web.legal.guardar'), ['acepto' => '1']);
        $this->assertSame(2, AceptacionLegal::where('user_id', $admin->id)->count());
    }

    public function test_aceptar_dos_veces_no_duplica_la_constancia(): void
    {
        $admin = $this->usuario(User::ROL_ADMIN);

        $this->actingAs($admin)->post(route('web.legal.guardar'), ['acepto' => '1']);
        $this->actingAs($admin)->post(route('web.legal.guardar'), ['acepto' => '1']);

        $this->assertSame(1, AceptacionLegal::count());
    }

    public function test_la_pantalla_muestra_los_textos_y_el_dueno_acepta_por_el_club(): void
    {
        $this->actingAs($this->usuario(User::ROL_ADMIN))->get(route('web.legal.aceptar'))
            ->assertOk()
            ->assertSee('Términos y condiciones de Wings')
            ->assertSee('Política de privacidad de Wings')
            ->assertSee('el club es el responsable de esa base de datos', false)
            ->assertSee('política de privacidad, en nombre del club.');

        $this->actingAs($this->usuario(User::ROL_PROFESOR))->get(route('web.legal.aceptar'))
            ->assertOk()
            ->assertDontSee('política de privacidad, en nombre del club.');
    }

    public function test_el_aviso_a_las_familias_no_se_le_muestra_al_profesor(): void
    {
        $this->actingAs($this->usuario(User::ROL_OPERATIVO))->get(route('web.legal.index'))->assertSee('Aviso a las familias');
        $this->actingAs($this->usuario(User::ROL_PROFESOR))->get(route('web.legal.index'))->assertDontSee('Aviso a las familias');
    }

    public function test_sin_sesion_no_se_ve_ni_se_acepta(): void
    {
        $this->get(route('web.legal.aceptar'))->assertRedirect(route('login'));
        $this->post(route('web.legal.guardar'), ['acepto' => '1'])->assertRedirect(route('login'));
        $this->assertSame(0, AceptacionLegal::count());
    }
}
