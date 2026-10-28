<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AyudaManualT12T13Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operativo;
    protected User $profesor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'rol'    => 'ADMIN',
            'activo' => true,
        ]);

        $this->operativo = User::factory()->create([
            'rol'    => 'OPERATIVO',
            'activo' => true,
        ]);

        $this->profesor = User::factory()->create([
            'rol'    => 'PROFESOR',
            'activo' => true,
        ]);
    }

    public function test_invitado_sin_sesion_es_redirigido_a_login(): void
    {
        $this->get(route('web.ayuda.index'))->assertRedirect(route('login'));
        $this->get(route('web.ayuda.show', 'cobrar-cuota'))->assertRedirect(route('login'));
    }

    public function test_admin_ve_todas_las_secciones_en_el_indice_de_ayuda(): void
    {
        $response = $this->actingAs($this->admin)->get(route('web.ayuda.index'));

        $response->assertOk();
        $response->assertSee('Ayuda');
        $response->assertSee('Cobrar una cuota');
        $response->assertSee('Corregir un movimiento mal cargado en una caja ya validada');
        $response->assertSee('Tomar asistencia en una clase');
    }

    public function test_operativo_ve_solo_sus_guias_en_el_indice(): void
    {
        $response = $this->actingAs($this->operativo)->get(route('web.ayuda.index'));

        $response->assertOk();
        $response->assertSee('Cobrar una cuota');
        $response->assertDontSee('Corregir un movimiento mal cargado en una caja ya validada');
        $response->assertDontSee('Tomar asistencia en una clase');
    }

    public function test_profesor_ve_solo_sus_guias_en_el_indice(): void
    {
        $response = $this->actingAs($this->profesor)->get(route('web.ayuda.index'));

        $response->assertOk();
        $response->assertSee('Tomar asistencia en una clase');
        $response->assertDontSee('Cobrar una cuota');
        $response->assertDontSee('Corregir un movimiento mal cargado en una caja ya validada');
    }

    public function test_admin_puede_abrir_cualquier_pagina_del_manual(): void
    {
        $this->actingAs($this->admin)->get(route('web.ayuda.show', 'cobrar-cuota'))->assertOk()->assertSee('Cobrar una cuota');
        $this->actingAs($this->admin)->get(route('web.ayuda.show', 'corregir-movimiento-caja-validada'))->assertOk()->assertSee('Corregir un movimiento mal cargado en una caja ya validada');
        $this->actingAs($this->admin)->get(route('web.ayuda.show', 'tomar-asistencia'))->assertOk()->assertSee('Tomar asistencia en una clase');
    }

    public function test_operativo_puede_ver_guia_operativa_pero_recibe_403_al_forzar_guia_de_admin_o_profesor(): void
    {
        $this->actingAs($this->operativo)->get(route('web.ayuda.show', 'cobrar-cuota'))->assertOk();

        // Intento forzar página de admin -> 403
        $this->actingAs($this->operativo)->get(route('web.ayuda.show', 'corregir-movimiento-caja-validada'))->assertForbidden();

        // Intento forzar página de profesor -> 403
        $this->actingAs($this->operativo)->get(route('web.ayuda.show', 'tomar-asistencia'))->assertForbidden();
    }

    public function test_profesor_puede_ver_su_guia_pero_recibe_403_al_forzar_guia_de_admin_u_operativo(): void
    {
        $this->actingAs($this->profesor)->get(route('web.ayuda.show', 'tomar-asistencia'))->assertOk();

        // Intento forzar página de operativo -> 403
        $this->actingAs($this->profesor)->get(route('web.ayuda.show', 'cobrar-cuota'))->assertForbidden();

        // Intento forzar página de admin -> 403
        $this->actingAs($this->profesor)->get(route('web.ayuda.show', 'corregir-movimiento-caja-validada'))->assertForbidden();
    }

    public function test_pagina_inexistente_devuelve_404(): void
    {
        $this->actingAs($this->admin)->get(route('web.ayuda.show', 'slug-que-no-existe'))->assertNotFound();
    }

    public function test_menu_lateral_muestra_acceso_a_ayuda_para_cada_rol(): void
    {
        // Admin
        $respAdmin = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $respAdmin->assertOk();
        $respAdmin->assertSee(route('web.ayuda.index'));

        // Operativo
        $respOp = $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'));
        $respOp->assertOk();
        $respOp->assertSee(route('web.ayuda.index'));

        // Profesor
        $respProf = $this->actingAs($this->profesor)->get(route('web.clases.index'));
        $respProf->assertOk();
        $respProf->assertSee(route('web.ayuda.index'));
    }

    public function test_boton_de_ayuda_aparece_en_pantallas_de_muestra_y_no_en_otras(): void
    {
        // En cashflow (admin) debe aparecer el botón con el slug de corrección de movimiento
        $respCashflow = $this->actingAs($this->admin)->get(route('web.cashflow.index'));
        $respCashflow->assertOk();
        $respCashflow->assertSee(route('web.ayuda.show', 'corregir-movimiento-caja-validada'));

        // En cobrar (alumno ficticio) debe aparecer el botón con el slug cobrar-cuota
        $tipoCaja = \App\Models\TipoCaja::firstOrCreate(['nombre' => 'Caja General'], ['activo' => true]);
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, $tipoCaja->id);

        $deporte = \App\Models\Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = \App\Models\Nivel::create(['nombre' => 'Inicial']);
        $grupo = \App\Models\Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $alumno = Alumno::create([
            'nombre'           => 'Juana',
            'apellido'         => 'Perez',
            'dni'              => '45123987',
            'fecha_nacimiento' => '2015-05-10',
            'celular'          => '1111111111',
            'deporte_id'       => $deporte->id,
            'grupo_id'         => $grupo->id,
            'fecha_alta'       => '2026-08-01',
            'activo'           => true,
        ]);
        $plan = \App\Models\GrupoPlan::create([
            'grupo_id'          => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual'    => 25000,
            'activo'            => true,
        ]);
        \App\Models\AlumnoPlan::create([
            'alumno_id'   => $alumno->id,
            'plan_id'     => $plan->id,
            'fecha_desde' => '2026-08-01',
            'activo'      => true,
        ]);

        $respCobrar = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $alumno->id));
        $respCobrar->assertOk();
        $respCobrar->assertSee(route('web.ayuda.show', 'cobrar-cuota'));

        // En alumnos/index no debe aparecer el botón de ayuda en la topbar en la Etapa 1
        $respAlumnos = $this->actingAs($this->admin)->get(route('web.alumnos.index'));
        $respAlumnos->assertOk();
        $respAlumnos->assertDontSee('title="Ayuda sobre esta pantalla"');
    }

    public function test_pagina_de_ayuda_tiene_botones_volver_e_imprimir(): void
    {
        $response = $this->actingAs($this->admin)->get(route('web.ayuda.show', 'cobrar-cuota'));
        $response->assertOk();
        $response->assertSee('Volver');
        $response->assertSee('Imprimir');
        $response->assertSee('data-accion="imprimir"', false);
    }
}
