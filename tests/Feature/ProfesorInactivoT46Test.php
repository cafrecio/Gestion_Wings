<?php

namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, Clase, Deporte, Grupo, GrupoPlan, Liquidacion, Nivel, Pago, Profesor, User};
use App\Services\{LiquidacionService, ProfesorEstadoService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, DB};
use Tests\TestCase;

class ProfesorInactivoT46Test extends TestCase
{
    use RefreshDatabase;
    private User $admin;
    private User $usuario;
    private Profesor $profesora;
    private Profesor $suplente;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true, 'name' => 'Dueño de prueba']);
        $d = Deporte::create(['nombre' => 'Patín de prueba', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $n = Nivel::create(['nombre' => 'Inicial de prueba']);
        $this->grupo = Grupo::create(['deporte_id' => $d->id, 'nivel_id' => $n->id, 'activo' => true]);
        $this->profesora = $this->docente('Ana', '70000001');
        $this->suplente = $this->docente('Luz', '70000002');
        $this->usuario = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true,
            'profesor_id' => $this->profesora->id, 'email' => 'profesora-t46@example.test', 'password' => 'ClaveT46segura!', 'remember_token' => 'token-anterior']);
    }

    private function docente(string $nombre, string $dni): Profesor
    {
        return Profesor::create(['deporte_id' => $this->grupo->deporte_id, 'nombre' => $nombre, 'apellido' => 'Prueba', 'dni' => $dni,
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 5000,
            'porcentaje_comision' => 20, 'activo' => true]);
    }

    private function clase(string $fecha = '2026-10-29', ?Profesor $profesora = null, array $extra = []): Clase
    {
        $c = Clase::create($extra + ['grupo_id' => $this->grupo->id, 'fecha' => $fecha,
            'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'cancelada' => false, 'validada_para_liquidacion' => true]);
        $c->profesores()->attach(($profesora ?? $this->profesora)->id);
        return $c;
    }

    private function baja(?bool $activo = false)
    {
        return $this->actingAs($this->admin)->patchJson(route('web.profesores.toggle-activo', $this->profesora->id), ['activo' => $activo]);
    }

    public function test_avisa_cantidad_y_bloquea_futuras_y_en_curso_sin_tocar_usuario(): void
    {
        $this->clase();
        $this->clase('2026-10-28', extra: ['hora_inicio' => '11:30', 'hora_fin' => '12:30']);
        $this->clase('2026-10-28'); // Ya terminó.
        $this->clase('2026-10-30', extra: ['cancelada' => true]);
        $this->baja()->assertUnprocessable()->assertJsonFragment(['error' => 'Tiene 2 clases futuras o en curso. Reasignalas antes de desactivar a la profesora.']);
        $this->assertTrue($this->profesora->fresh()->activo);
        $this->assertTrue($this->usuario->fresh()->activo);
        $this->get(route('web.profesores.index'))->assertOk()->assertSee('Tiene 2')->assertSee('Reasignar');
    }

    public function test_reasignar_por_operativa_permite_baja_y_preserva_la_clase(): void
    {
        $c = $this->clase();
        $op = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->actingAs($op)->patchJson(route('web.clases.profesores', $c->id), ['profesores' => [$this->suplente->id]])->assertOk();
        $this->baja()->assertOk()->assertJson(['activo' => false]);
        $this->assertSame([$this->suplente->id], $c->profesores()->pluck('profesores.id')->all());
        $this->assertFalse($c->fresh()->cancelada);
        $this->assertFalse($this->profesora->fresh()->activo);
        $this->assertFalse($this->usuario->fresh()->activo);
    }

    public function test_reasignacion_respeta_choque_y_no_habilita_baja(): void
    {
        $c = $this->clase(); $this->clase(profesora: $this->suplente);
        $this->actingAs($this->admin)->patchJson(route('web.clases.profesores', $c->id), ['profesores' => [$this->suplente->id]])->assertUnprocessable();
        $this->baja()->assertUnprocessable();
        $this->assertSame([$this->profesora->id], $c->profesores()->pluck('profesores.id')->all());
    }

    public function test_cancelada_no_choca_y_reasignar_quita_toda_clase_futura_activa(): void
    {
        $c = $this->clase(); $this->clase(profesora: $this->suplente, extra: ['cancelada' => true]);
        $this->actingAs($this->admin)->patchJson(route('web.clases.profesores', $c->id), ['profesores' => [$this->suplente->id]])->assertOk();
        $this->baja()->assertOk();
        $this->assertFalse($this->profesora->clases()->noTerminadas()->where('cancelada', false)->exists());
    }

    public function test_inactiva_con_dictadas_aparece_y_liquida_horas_y_desaparece_sin_pendientes(): void
    {
        $this->clase('2026-10-10');
        $this->baja()->assertOk();
        $this->get(route('web.liquidaciones.create'))->assertOk()->assertSee('Inactiva, con clases dictadas pendientes')
            ->assertViewHas('profesores', fn ($p) => $p->contains('id', $this->profesora->id));
        $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesora->id, 'mes' => 10, 'anio' => 2026])->assertSessionHasNoErrors()->assertSessionHas('success');
        $l = Liquidacion::sole();
        $this->assertSame('5000.00', $l->total_calculado);
        $this->assertSame('HORA', $l->tipo);
        $this->get(route('web.liquidaciones.create'))->assertOk()->assertViewHas('profesores', fn ($p) => !$p->contains('id', $this->profesora->id));
        $l->update(['estado' => 'CERRADA']);
        $this->get(route('web.liquidaciones.create'))->assertOk()->assertViewHas('profesores', fn ($p) => !$p->contains('id', $this->profesora->id));
    }

    public function test_inactiva_sin_pendientes_no_aparece_ni_genera_forzando_ruta(): void
    {
        $this->clase('2026-10-10', extra: ['validada_para_liquidacion' => false]);
        $this->baja()->assertOk();
        $this->get(route('web.liquidaciones.create'))->assertOk()->assertViewHas('profesores', fn ($p) => !$p->contains('id', $this->profesora->id));
        $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesora->id, 'mes' => 10, 'anio' => 2026])->assertSessionHas('error');
        $this->assertDatabaseCount('liquidaciones', 0);
    }

    public function test_inactiva_liquida_comision_por_lo_dictado_con_porcentaje_conservado(): void
    {
        Deporte::findOrFail($this->grupo->deporte_id)->update(['tipo_liquidacion' => 'COMISION']);
        $c = $this->clase('2026-10-10');
        $plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 10000, 'activo' => true]);
        $a = Alumno::create(['nombre' => 'Eva', 'apellido' => 'Prueba', 'dni' => '70000003', 'fecha_nacimiento' => '2010-01-01',
            'celular' => '1140000000', 'grupo_id' => $this->grupo->id, 'deporte_id' => $this->grupo->deporte_id, 'activo' => true]);
        Asistencia::create(['clase_id' => $c->id, 'alumno_id' => $a->id, 'presente' => true]);
        Pago::create(['alumno_id' => $a->id, 'plan_id' => $plan->id, 'mes' => 10, 'anio' => 2026, 'monto_base' => 10000,
            'monto_final' => 10000, 'monto_cuota' => 10000, 'porcentaje_aplicado' => 100, 'fecha_pago' => '2026-10-10', 'estado' => 'COMPLETADO']);
        $this->baja()->assertOk();
        $l = app(LiquidacionService::class)->generarLiquidacionMensual($this->profesora->id, 10, 2026);
        $this->assertSame('COMISION', $l->tipo); $this->assertSame('2000.00', $l->total_calculado);
        $this->assertSame('20.00', $l->porcentaje_comision_aplicado);
    }

    public function test_baja_borra_sesiones_y_token_y_corta_sesion_abierta_y_nuevo_login(): void
    {
        DB::table('sessions')->insert(['id' => 'sesion-t46', 'user_id' => $this->usuario->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->usuario)->get(route('web.clases.index'))->assertOk();
        app(ProfesorEstadoService::class)->cambiar($this->profesora->id, $this->admin, false);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-t46']);
        $this->assertNotSame('token-anterior', $this->usuario->fresh()->getRememberToken());
        $this->get(route('web.clases.index'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login'), ['email' => $this->usuario->email, 'password' => 'ClaveT46segura!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_reactivar_profesora_conserva_usuario_inactivo_hasta_activacion_explicita(): void
    {
        $this->baja()->assertOk();
        $this->baja(true)->assertOk()->assertJson(['activo' => true]);
        $this->assertTrue($this->profesora->fresh()->activo);
        $this->assertFalse($this->usuario->fresh()->activo);
        $this->patchJson(route('web.usuarios.toggle-activo', $this->usuario->id))->assertOk()->assertJson(['activo' => true]);
        $this->assertTrue($this->usuario->fresh()->puedeIngresar());
    }

    public function test_activar_usuario_solo_no_permite_entrar_con_profesora_inactiva(): void
    {
        $this->baja()->assertOk();
        $this->patchJson(route('web.usuarios.toggle-activo', $this->usuario->id))->assertOk();
        Auth::logout();
        $this->post(route('login'), ['email' => $this->usuario->email, 'password' => 'ClaveT46segura!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_operativo_y_profesor_no_desactivan_ni_liquidan_y_profesor_no_reasigna(): void
    {
        $c = $this->clase();
        foreach ([User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]), $this->usuario] as $u) {
            $this->actingAs($u)->patchJson(route('web.profesores.toggle-activo', $this->profesora->id), ['activo' => false])->assertForbidden();
            $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesora->id, 'mes' => 10, 'anio' => 2026])->assertForbidden();
        }
        $this->patchJson(route('web.clases.profesores', $c->id), ['profesores' => [$this->suplente->id]])->assertForbidden();
        $this->assertTrue($this->profesora->fresh()->activo);
    }

    public function test_inactiva_no_se_ofrece_ni_se_asigna_en_alta_o_reasignacion_forzada(): void
    {
        $this->baja()->assertOk(); $c = $this->clase(profesora: $this->suplente);
        $this->get(route('web.clases.create'))->assertOk()->assertViewHas('profesores', fn ($p) => !$p->contains('id', $this->profesora->id));
        $this->get(route('web.clases.show', $c->id))->assertOk()->assertViewHas('profesoresDisponibles', fn ($p) => !$p->contains('id', $this->profesora->id));
        $this->patchJson(route('web.clases.profesores', $c->id), ['profesores' => [$this->profesora->id]])->assertUnprocessable();
        $this->postJson(route('web.clases.store'), ['tipo_creacion' => 'unica', 'grupo_id' => $this->grupo->id, 'fecha' => '2026-11-02',
            'hora_inicio' => '15:00', 'hora_fin' => '16:00', 'profesores' => [$this->profesora->id]])->assertUnprocessable();
        $this->assertFalse($this->profesora->clases()->exists());
    }

    public function test_cancelada_futura_no_bloquea_baja_pero_no_se_reactiva_con_inactiva(): void
    {
        $c = $this->clase(extra: ['cancelada' => true]);
        $this->baja()->assertOk();
        $this->patchJson(route('web.clases.toggle-cancelada', $c->id))->assertUnprocessable()->assertJsonFragment(['success' => false]);
        $this->assertTrue($c->fresh()->cancelada);
    }

    public function test_reenvio_explicito_no_reactiva_por_error_y_baja_protegida_es_atomica(): void
    {
        $this->baja()->assertOk(); $this->baja()->assertOk()->assertJson(['activo' => false]);
        $this->assertFalse($this->profesora->fresh()->activo);
        $this->baja(true)->assertOk();
        $this->usuario->refresh()->forceFill(['es_superadmin' => true, 'activo' => true])->save();
        $this->baja()->assertUnprocessable();
        $this->assertTrue($this->profesora->fresh()->activo); $this->assertTrue($this->usuario->fresh()->activo);
    }

    public function test_pantallas_reales_avisan_reasignacion_y_liquidacion_inactiva(): void
    {
        $c = $this->clase(); $this->clase('2026-10-10');
        $this->actingAs($this->admin);
        if (getenv('WINGS_CAPTURAS') === '1') $this->withVite();
        $antes = $this->get(route('web.profesores.index'))->assertOk()->assertSee('Reasignar');
        $this->patchJson(route('web.clases.profesores', $c->id), ['profesores' => [$this->suplente->id]])->assertOk();
        $this->baja()->assertOk();
        $despues = $this->get(route('web.profesores.index'))->assertOk()->assertSee('El acceso del usuario se habilita por separado');
        $liquidacion = $this->get(route('web.liquidaciones.create'))->assertOk()->assertSee('Inactiva, con clases dictadas pendientes');
        if (getenv('WINGS_CAPTURAS') !== '1') return;
        Auth::logout();
        $paginas = ['00-login' => $this->get(route('login'))->assertOk()->getContent(), '01-profesora-futuras' => $antes->getContent(),
            '02-profesora-inactiva' => $despues->getContent(), '03-liquidacion-inactiva' => $liquidacion->getContent()];
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t46');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        foreach ($paginas as $nombre => $html) {
            $html = preg_replace('~https?://[^/"\s]+/(build/|img/|favicon|apple-touch|site.webmanifest)~', '../../../../public/$1', $html);
            $html = preg_replace('~(<meta name="csrf-token" content=")[^"]*~', '$1', $html);
            $html = preg_replace('~(<input[^>]*name="_token"[^>]*value=")[^"]*~', '$1', $html);
            file_put_contents($dir.'/'.$nombre.'.html', rtrim(preg_replace('/[\t ]+$/m', '', $html)).PHP_EOL);
        }
    }
}
