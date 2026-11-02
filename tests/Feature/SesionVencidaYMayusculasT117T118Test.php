<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoRevisionCobranza;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SesionVencidaYMayusculasT117T118Test extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    // T117: Sesión vencida y CSRF
    // ─────────────────────────────────────────────────────────────

    public function test_peticion_web_formulario_con_sesion_vencida_redirige_a_login_con_flash(): void
    {
        Route::middleware('web')->post('/__test/formulario_vencido', fn () => abort(419));

        $res = $this->post('/__test/formulario_vencido', ['campo' => 'valor']);
        $res->assertRedirect(route('login'));
        $res->assertSessionHas('error', 'Tu sesión expiró. Iniciá sesión nuevamente.');

        $loginRes = $this->get(route('login'));
        $loginRes->assertOk();
        $loginRes->assertSee('Tu sesión expiró. Iniciá sesión nuevamente.');
    }

    public function test_peticion_ajax_con_sesion_vencida_devuelve_419_json_con_aviso_y_redirect(): void
    {
        Route::middleware('web')->post('/__test/ajax_vencido', fn () => abort(419));

        $res = $this->postJson('/__test/ajax_vencido', ['items' => []]);
        $res->assertStatus(419);
        $res->assertJson([
            'message'  => 'Tu sesión expiró. Iniciá sesión nuevamente.',
            'redirect' => route('login'),
        ]);
        $res->assertSessionHas('error', 'Tu sesión expiró. Iniciá sesión nuevamente.');

        // Cuando el cliente JS navega a /login tras recibir 419, ve el mensaje en pantalla
        $loginRes = $this->get(route('login'));
        $loginRes->assertOk();
        $loginRes->assertSee('Tu sesión expiró. Iniciá sesión nuevamente.');
    }

    public function test_peticion_api_con_sesion_vencida_no_redirige_a_login(): void
    {
        Route::middleware('api')->post('/api/__test/api_vencido', fn () => abort(419));

        $res = $this->postJson('/api/__test/api_vencido');
        $res->assertStatus(419);
        $res->assertHeader('Content-Type', 'application/json');
        // No debe redirigir a login ni contaminar sesión web
        $this->assertFalse($res->isRedirect());
    }

    public function test_despues_de_iniciar_sesion_cada_rol_llega_a_su_inicio(): void
    {
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $profesor = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]);

        // ADMIN -> / (adminDashboard)
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');

        // OPERATIVO -> /operativo/dashboard
        $this->post('/login', ['email' => $operativo->email, 'password' => 'password'])
            ->assertRedirect(route('web.operativo.dashboard'));
        $this->post('/logout');

        // PROFESOR -> /clases
        $this->post('/login', ['email' => $profesor->email, 'password' => 'password'])
            ->assertRedirect(route('web.clases.index'));
    }

    // ─────────────────────────────────────────────────────────────
    // T118: Mayúsculas y recortes con acentos
    // ─────────────────────────────────────────────────────────────

    public function test_revision_muestra_reactivado_automaticamente_en_mayusculas_con_tilde(): void
    {
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $deporte = Deporte::firstOrCreate(['nombre' => 'Patín'], ['nombre' => 'Patín', 'activo' => true]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial'], ['nombre' => 'Inicial']);
        $grupo = Grupo::firstOrCreate([
            'deporte_id' => $deporte->id,
            'nivel_id'   => $nivel->id,
        ], [
            'activo'     => true,
        ]);

        $alumno = Alumno::create([
            'nombre'           => 'Martín',
            'apellido'         => 'Gómez',
            'dni'              => '38123456',
            'fecha_nacimiento' => '2010-01-01',
            'celular'          => '1122334455',
            'deporte_id'       => $deporte->id,
            'grupo_id'         => $grupo->id,
            'fecha_alta'       => '2026-01-01',
            'activo'           => true,
        ]);

        AlumnoRevisionCobranza::create([
            'alumno_id'             => $alumno->id,
            'motivo'                => 'Mora detectada',
            'periodo_objetivo'      => '2026-11',
            'estado_revision'       => 'RESUELTO',
            'resolucion'            => 'REACTIVADO_AUTO',
            'nota_resolucion'       => 'Reactivado automático por cobro.',
            'usuario_resolucion_id' => $admin->id,
            'fecha_resolucion'      => now(),
        ]);

        $this->actingAs($admin);
        $res = $this->get(route('web.revision-cobranza.index', ['estado' => 'RESUELTO']));
        $res->assertOk();

        // Debe verse con tilde en la A
        $res->assertSee('REACTIVADO AUTOMÁTICAMENTE');
        $res->assertDontSee('REACTIVADO AUTOMáTICAMENTE');
    }

    public function test_caja_detalle_abrevia_futbol_como_fut(): void
    {
        $operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['nombre' => 'Efectivo', 'abreviatura' => 'EFE', 'activo' => true]);
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'tipo_caja_id'         => $tipoCaja->id,
            'estado'               => 'ABIERTA',
            'apertura_at'          => now(),
            'saldo_inicial'        => 1000,
        ]);

        $deporte = Deporte::firstOrCreate(['nombre' => 'Fútbol'], ['nombre' => 'Fútbol', 'activo' => true]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial'], ['nombre' => 'Inicial']);
        $grupo = Grupo::firstOrCreate([
            'deporte_id' => $deporte->id,
            'nivel_id'   => $nivel->id,
        ], [
            'activo'     => true,
        ]);

        $alumno = Alumno::create([
            'nombre'           => 'Martín',
            'apellido'         => 'Gómez',
            'dni'              => '39123456',
            'fecha_nacimiento' => '2010-01-01',
            'celular'          => '1122334455',
            'deporte_id'       => $deporte->id,
            'grupo_id'         => $grupo->id,
            'fecha_alta'       => '2026-01-01',
            'activo'           => true,
        ]);

        $rubro = Rubro::firstOrCreate(['nombre' => 'Cuotas'], ['nombre' => 'Cuotas', 'tipo' => 'INGRESO']);
        $subrubro = Subrubro::firstOrCreate(['nombre' => 'Cuota social'], [
            'rubro_id'       => $rubro->id,
            'nombre'         => 'Cuota social',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja'    => true,
            'activo'         => true,
        ]);

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'subrubro_id'       => $subrubro->id,
            'tipo_caja_id'      => $tipoCaja->id,
            'usuario_id'        => $operativo->id,
            'alumno_id'         => $alumno->id,
            'monto'             => 5000,
            'fecha'             => now(),
            'estado'            => 'ACTIVO',
        ]);

        $this->actingAs($operativo);
        $res = $this->get(route('web.caja.detalle', $caja->id));
        $res->assertOk();

        // Debe abreviar Fútbol a 3 letras: FÚT (no Fú)
        $res->assertSee('· FÚT');
        $res->assertDontSee('· Fú ·');
    }

    public function test_movimientos_recorta_observaciones_con_acentos_sin_romper_caracteres(): void
    {
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['nombre' => 'Efectivo', 'abreviatura' => 'EFE', 'activo' => true]);
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $admin->id,
            'tipo_caja_id'         => $tipoCaja->id,
            'estado'               => 'ABIERTA',
            'apertura_at'          => now(),
            'saldo_inicial'        => 1000,
        ]);

        $rubro = Rubro::firstOrCreate(['nombre' => 'Varios'], ['nombre' => 'Varios', 'tipo' => 'INGRESO']);
        $subrubro = Subrubro::firstOrCreate(['nombre' => 'General'], [
            'rubro_id'       => $rubro->id,
            'nombre'         => 'General',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja'    => true,
            'activo'         => true,
        ]);

        // Texto con 35 caracteres ASCII y el carácter 36 es 'á' (2 bytes UTF-8)
        // Con substr($str, 0, 36) rompería el byte de la 'á'. Con mb_substr no se rompe.
        $obs = '12345678901234567890123456789012345ábcdefghij';

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'subrubro_id'       => $subrubro->id,
            'tipo_caja_id'      => $tipoCaja->id,
            'usuario_id'        => $admin->id,
            'monto'             => 1000,
            'fecha'             => now(),
            'observaciones'     => $obs,
            'estado'            => 'ACTIVO',
        ]);

        $this->actingAs($admin);
        $res = $this->get(route('web.movimientos.index'));
        $res->assertOk();

        // Verificar que el corte tiene 36 caracteres exactos más '…'
        $esperado = mb_substr($obs, 0, 36, 'UTF-8') . '…';
        $res->assertSee($esperado);

        // La respuesta completa debe ser UTF-8 válida sin secuencias de bytes quebradas
        $this->assertTrue(mb_check_encoding($res->getContent(), 'UTF-8'));
    }

    public function test_movimientos_observacion_de_36_caracteres_con_acentos_se_ve_entera_sin_puntos_suspensivos(): void
    {
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['nombre' => 'Efectivo', 'abreviatura' => 'EFE', 'activo' => true]);
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $admin->id,
            'tipo_caja_id'         => $tipoCaja->id,
            'estado'               => 'ABIERTA',
            'apertura_at'          => now(),
            'saldo_inicial'        => 1000,
        ]);

        $rubro = Rubro::firstOrCreate(['nombre' => 'Varios'], ['nombre' => 'Varios', 'tipo' => 'INGRESO']);
        $subrubro = Subrubro::firstOrCreate(['nombre' => 'General'], [
            'rubro_id'       => $rubro->id,
            'nombre'         => 'General',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja'    => true,
            'activo'         => true,
        ]);

        // Exactamente 36 caracteres UTF-8 con acentos multibyte ('í' y 'ó')
        // mb_strlen = 36, pero strlen = 38 bytes. Con strlen > 36 se truncaría erróneamente con '…'.
        $obs36 = 'Cuota social patín y natación 2026!!';
        $this->assertSame(36, mb_strlen($obs36, 'UTF-8'));
        $this->assertSame(38, strlen($obs36));

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'subrubro_id'       => $subrubro->id,
            'tipo_caja_id'      => $tipoCaja->id,
            'usuario_id'        => $admin->id,
            'monto'             => 1000,
            'fecha'             => now(),
            'observaciones'     => $obs36,
            'estado'            => 'ACTIVO',
        ]);

        $this->actingAs($admin);
        $res = $this->get(route('web.movimientos.index'));
        $res->assertOk();

        // Debe verse la observación completa y NO truncada con '…'
        $res->assertSee($obs36);
        $res->assertDontSee($obs36 . '…');
        $res->assertDontSee(mb_substr($obs36, 0, 36, 'UTF-8') . '…');
    }

    public function test_tipo_caja_alta_guarda_abreviatura_con_acento_en_mayuscula(): void
    {
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($admin);

        $res = $this->post(route('web.tipos-caja.store'), [
            'nombre'         => 'Caja Fútbol',
            'abreviatura'    => 'fút',
            'saldo_inicial'  => '1000',
            'descripcion'    => 'Caja de prueba para alta',
        ]);

        $res->assertRedirect(route('web.tipos-caja.index'));

        // Con mb_strtoupper guarda 'FÚT'; si se usara strtoupper quedaría 'FúT'
        $tipo = TipoCaja::where('nombre', 'Caja Fútbol')->firstOrFail();
        $this->assertSame('FÚT', $tipo->abreviatura);
        $this->assertNotSame('FúT', $tipo->abreviatura);
    }

    public function test_tipo_caja_edicion_guarda_abreviatura_con_acento_en_mayuscula(): void
    {
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $tipoCaja = TipoCaja::create([
            'nombre'              => 'Caja Pádel',
            'abreviatura'         => 'PAD',
            'saldo_inicial'       => 500,
            'permite_descubierto' => false,
        ]);

        $this->actingAs($admin);

        $res = $this->put(route('web.tipos-caja.update', $tipoCaja->id), [
            'nombre'         => 'Caja Pádel',
            'abreviatura'    => 'pád',
            'saldo_inicial'  => '500',
            'descripcion'    => 'Actualizada con acento',
        ]);

        $res->assertRedirect(route('web.tipos-caja.index'));

        // Con mb_strtoupper guarda 'PÁD'; si se usara strtoupper quedaría 'PáD'
        $tipoActualizado = TipoCaja::findOrFail($tipoCaja->id);
        $this->assertSame('PÁD', $tipoActualizado->abreviatura);
        $this->assertNotSame('PáD', $tipoActualizado->abreviatura);
    }
}
