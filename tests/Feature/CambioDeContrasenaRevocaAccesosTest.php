<?php

namespace Tests\Feature;

use App\Http\Controllers\UsuarioWebController;
use App\Models\Rubro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SEG-02 y SEG-03.
 *
 * Una contraseña se cambia justamente cuando se filtro o la tiene quien ya no deberia.
 * Antes, cambiarla no echaba a nadie: la sesion abierta seguia viva —estan en la tabla
 * `sessions`, porque `SESSION_DRIVER=database`— y si la persona habia tildado
 * "Recordarme", su cookie lo volvia a autenticar durante cinco años con una contraseña
 * que ya no existe.
 *
 * Desactivar la cuenta si lo echaba en el request siguiente, y bajarle el rol tambien.
 * El agujero era especifico del cambio de contraseña.
 */
class CambioDeContrasenaRevocaAccesosTest extends TestCase
{
    use RefreshDatabase;

    // ── SEG-02 · revocar lo anterior ─────────────────────────────────────

    public function test_cambiar_la_contrasena_cierra_las_sesiones_abiertas(): void
    {
        $otro = $this->usuario(User::ROL_OPERATIVO);
        $this->sesionAbiertaDe($otro, 'sesion-del-navegador-de-el');

        $this->actingAs($this->admin())
            ->put(route('web.usuarios.update', $otro->id), $this->datosDe($otro, 'contrasena-nueva-1'))
            ->assertSessionHas('success');

        $this->assertSame(
            0,
            DB::table('sessions')->where('user_id', $otro->id)->count(),
            'La sesión anterior sigue viva: quien tenía la contraseña filtrada sigue adentro.'
        );
    }

    public function test_cambiar_la_contrasena_invalida_la_cookie_de_recordarme(): void
    {
        $otro = $this->usuario(User::ROL_OPERATIVO);
        $otro->setRememberToken('token-de-la-cookie-vieja');
        $otro->save();

        $this->actingAs($this->admin())
            ->put(route('web.usuarios.update', $otro->id), $this->datosDe($otro, 'contrasena-nueva-1'))
            ->assertSessionHas('success');

        $this->assertNotSame(
            'token-de-la-cookie-vieja',
            $otro->fresh()->getRememberToken(),
            'La cookie de "Recordarme" sigue sirviendo: vale cinco años y sobrevive al cambio.'
        );
    }

    /** Cambiar otro dato del usuario no tiene por que echarlo de su sesion. */
    public function test_editar_sin_tocar_la_contrasena_no_cierra_la_sesion(): void
    {
        $otro = $this->usuario(User::ROL_OPERATIVO);
        $this->sesionAbiertaDe($otro, 'sesion-que-debe-seguir-viva');

        $datos = $this->datosDe($otro, null);
        $datos['name'] = 'Nombre Cambiado';

        $this->actingAs($this->admin())
            ->put(route('web.usuarios.update', $otro->id), $datos)
            ->assertSessionHas('success');

        $this->assertSame(
            1,
            DB::table('sessions')->where('user_id', $otro->id)->count(),
            'Se lo echó por un cambio de nombre.'
        );
    }

    /** Seria absurdo que cambiarse la propia clave lo dejara afuera del sistema. */
    public function test_cambiarse_la_propia_contrasena_no_lo_echa_a_uno_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('web.usuarios.update', $admin->id), $this->datosDe($admin, 'contrasena-nueva-1'))
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($admin->fresh());
    }

    // ── SEG-03 · un solo minimo para todos los caminos ───────────────────

    public function test_el_alta_web_exige_el_mismo_minimo_que_la_consola(): void
    {
        $corta = Str::repeat('a', UsuarioWebController::MINIMO_CONTRASENA - 1);

        $this->actingAs($this->admin())
            ->post(route('web.usuarios.store'), [
                'name'                  => 'Nuevo Usuario',
                'email'                 => 'nuevo@wings.test',
                'password'              => $corta,
                'password_confirmation' => $corta,
                'rol'                   => User::ROL_OPERATIVO,
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@wings.test']);
    }

    public function test_el_cambio_de_contrasena_exige_el_mismo_minimo(): void
    {
        $otro = $this->usuario(User::ROL_OPERATIVO);
        $corta = Str::repeat('a', UsuarioWebController::MINIMO_CONTRASENA - 1);

        $this->actingAs($this->admin())
            ->put(route('web.usuarios.update', $otro->id), $this->datosDe($otro, $corta))
            ->assertSessionHasErrors('password');
    }

    /**
     * Las dos pruebas de arriba estan escritas contra la constante, asi que verifican
     * coherencia y no el valor: con el minimo en 4 tambien pasarian. Este fija el piso.
     */
    public function test_el_minimo_no_baja_de_doce(): void
    {
        $this->assertGreaterThanOrEqual(
            12,
            UsuarioWebController::MINIMO_CONTRASENA,
            'Se bajo el minimo de contraseña. Era 12, el valor que ya exigia la consola. '
            . 'Si la decision es bajarlo, cambiar tambien este numero y dejar dicho por que.'
        );
    }

    /**
     * El minimo vive en un solo lugar. Si alguien lo cambia en el controlador y se
     * olvida de la consola, esta prueba avisa: era exactamente el estado anterior,
     * 8 por pantalla y 12 por consola.
     */
    public function test_la_consola_usa_la_misma_constante_que_la_pantalla(): void
    {
        $comando = file_get_contents(dirname(__DIR__, 2) . '/app/Console/Commands/CrearAdminCommand.php');

        $this->assertStringContainsString(
            'UsuarioWebController::MINIMO_CONTRASENA',
            $comando,
            'La consola volvió a tener su propio número. Tiene que salir de la misma constante.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/mb_strlen\(\$password\) < \d+/',
            $comando,
            'Quedó un número escrito a mano en la consola.'
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function admin(): User
    {
        return User::factory()->create([
            'rol'           => User::ROL_ADMIN,
            'activo'        => true,
            'es_superadmin' => false,
        ]);
    }

    /**
     * Guardar un OPERATIVO dispara la creacion de su subrubro de sueldo, que necesita
     * el rubro reservado. Sin el, el update muere antes de llegar a lo que se prueba.
     */
    private function usuario(string $rol): User
    {
        if ($rol === User::ROL_OPERATIVO) {
            $rubro = Rubro::firstOrCreate(
                ['nombre' => 'Sueldos'],
                ['tipo' => 'EGRESO', 'observacion' => 'Pagos al personal']
            );
            $rubro->es_reservado_sistema = true;
            $rubro->save();
        }

        return User::factory()->create(['rol' => $rol, 'activo' => true, 'es_superadmin' => false]);
    }

    /** Una fila en `sessions` es lo que mantiene a alguien adentro. */
    private function sesionAbiertaDe(User $usuario, string $id): void
    {
        DB::table('sessions')->insert([
            'id'            => $id,
            'user_id'       => $usuario->id,
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'navegador de prueba',
            'payload'       => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);
    }

    /** @return array<string,mixed> */
    private function datosDe(User $usuario, ?string $password): array
    {
        $datos = [
            'name'  => $usuario->name,
            'email' => $usuario->email,
            'rol'   => $usuario->rol,
        ];

        if ($password !== null) {
            $datos['password']              = $password;
            $datos['password_confirmation'] = $password;
        }

        return $datos;
    }
}
