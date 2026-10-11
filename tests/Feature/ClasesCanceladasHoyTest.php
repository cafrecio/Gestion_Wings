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

class ClasesCanceladasHoyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $userProfesor;
    private Profesor $profesor;
    private Profesor $otroProfesor;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-12 10:00:00'));

        $this->admin = User::factory()->create([
            'rol' => 'ADMIN',
            'activo' => true,
        ]);

        $this->operativo = User::factory()->create([
            'rol' => 'OPERATIVO',
            'activo' => true,
        ]);

        $deporte = Deporte::create([
            'nombre' => 'Patin',
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);

        $nivel = Nivel::create([
            'nombre' => 'Principiantes',
        ]);

        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Lucía',
            'apellido' => 'Gaitán',
            'dni' => '35111222',
            'fecha_nacimiento' => '1995-05-10',
            'direccion' => 'Calle 1',
            'localidad' => 'Ciudad',
            'valor_hora' => 2000,
            'activo' => true,
        ]);

        $this->otroProfesor = Profesor::create([
            'deporte_id' => $deporte->id,
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
            'profesor_id' => $this->profesor->id,
        ]);
    }

    public function test_admin_y_operativo_ven_clase_cancelada_de_hoy_en_bloque_hoy(): void
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado 12 de octubre: el club no abre',
        ]);
        $clase->profesores()->attach($this->profesor->id);

        // 1. Admin
        $resAdmin = $this->actingAs($this->admin)->get(route('web.clases.index'));
        $resAdmin->assertOk();
        $resAdmin->assertSee('Feriado 12 de octubre: el club no abre');
        $resAdmin->assertSee('Cancelada');
        $resAdmin->assertSee('16:00');

        // 2. Operativo
        $resOp = $this->actingAs($this->operativo)->get(route('web.clases.index'));
        $resOp->assertOk();
        $resOp->assertSee('Feriado 12 de octubre: el club no abre');
        $resOp->assertSee('Cancelada');
        $resOp->assertSee('16:00');
    }

    public function test_profesora_ve_su_clase_cancelada_en_tus_clases_y_otra_en_otras_clases(): void
    {
        // Clase de Lucía hoy, cancelada
        $claseLucia = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado 12 de octubre: el club no abre',
        ]);
        $claseLucia->profesores()->attach($this->profesor->id);

        // Clase de Mariela hoy, cancelada
        $claseMariela = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '17:00',
            'hora_fin' => '18:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado 12 de octubre: el club no abre',
        ]);
        $claseMariela->profesores()->attach($this->otroProfesor->id);

        $res = $this->actingAs($this->userProfesor)->get(route('web.clases.index'));
        $res->assertOk();

        // En "misClasesHoy" está la de Lucía
        $misClasesHoy = $res->viewData('misClasesHoy');
        $this->assertTrue($misClasesHoy->contains('id', $claseLucia->id));
        $this->assertFalse($misClasesHoy->contains('id', $claseMariela->id));

        // En "otrasClasesHoy" está la de Mariela
        $otrasClasesHoy = $res->viewData('otrasClasesHoy');
        $this->assertTrue($otrasClasesHoy->contains('id', $claseMariela->id));
        $this->assertFalse($otrasClasesHoy->contains('id', $claseLucia->id));

        // Ambas son visibles en el HTML
        $res->assertSee('Feriado 12 de octubre: el club no abre');
        $res->assertSee('Otras clases de hoy (1)');
    }

    public function test_clase_cancelada_de_hoy_aparece_al_final_despues_de_las_clases_en_pie(): void
    {
        // Clase en pie a las 17:00
        $claseEnPie = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '17:00',
            'hora_fin' => '18:00',
            'cancelada' => false,
        ]);
        $claseEnPie->profesores()->attach($this->profesor->id);

        // Clase cancelada a las 16:00 (hora antes que la que sigue en pie)
        $claseCancelada = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Profesor enfermo',
        ]);
        $claseCancelada->profesores()->attach($this->profesor->id);

        $res = $this->actingAs($this->admin)->get(route('web.clases.index'));
        $res->assertOk();

        $clasesHoy = $res->viewData('clasesHoy');
        $this->assertCount(2, $clasesHoy);
        // La primera debe ser la que sigue en pie (17:00)
        $this->assertEquals($claseEnPie->id, $clasesHoy[0]->id);
        // La segunda debe ser la cancelada (16:00) al final
        $this->assertEquals($claseCancelada->id, $clasesHoy[1]->id);
    }

    public function test_encabezado_de_hoy_no_cuenta_las_canceladas_como_clases_a_dar(): void
    {
        // 1 activa y 2 canceladas
        $c1 = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => false,
        ]);
        $c1->profesores()->attach($this->profesor->id);

        $c2 = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '17:00',
            'hora_fin' => '18:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Lluvia',
        ]);
        $c2->profesores()->attach($this->profesor->id);

        $c3 = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '18:00',
            'hora_fin' => '19:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Lluvia',
        ]);
        $c3->profesores()->attach($this->profesor->id);

        $res = $this->actingAs($this->admin)->get(route('web.clases.index'));
        $res->assertOk();
        // El encabezado dice "· 1 clase(s)" (solo la activa), no 3
        $res->assertSee('<strong>1</strong> clase(s)', false);
    }

    public function test_si_todas_las_de_hoy_estan_canceladas_se_ven_y_no_muestra_cartel_vacio(): void
    {
        $c = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado nacional',
        ]);
        $c->profesores()->attach($this->profesor->id);

        $res = $this->actingAs($this->admin)->get(route('web.clases.index'));
        $res->assertOk();

        // Encabezado muestra 0 clase(s)
        $res->assertSee('<strong>0</strong> clase(s)', false);
        // No muestra el cartel como si el día estuviese vacío
        $res->assertDontSee('No hay clases programadas para hoy');
        // Se ve la cancelada con su motivo
        $res->assertSee('Feriado nacional');
        $res->assertSee('Cancelada');
    }

    public function test_filtro_cancelada_encuentra_la_clase_cancelada_de_hoy(): void
    {
        $c = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado 12 de octubre',
        ]);
        $c->profesores()->attach($this->profesor->id);

        // 1. Filtro Cancelada solo
        $res1 = $this->actingAs($this->admin)->get(route('web.clases.index', ['estado' => 'cancelada']));
        $res1->assertOk();
        $clases1 = $res1->viewData('clasesFiltradas');
        $this->assertTrue($clases1->contains('id', $c->id));

        // 2. Filtro Cancelada + Fecha de hoy
        $res2 = $this->actingAs($this->admin)->get(route('web.clases.index', [
            'estado' => 'cancelada',
            'fecha' => '2026-10-12',
        ]));
        $res2->assertOk();
        $clases2 = $res2->viewData('clasesFiltradas');
        $this->assertTrue($clases2->contains('id', $c->id));

        // 3. Profesora con filtro Cancelada + su ID
        $res3 = $this->actingAs($this->userProfesor)->get(route('web.clases.index', [
            'estado' => 'cancelada',
            'profesor_id' => $this->profesor->id,
            'fecha' => '2026-10-12',
        ]));
        $res3->assertOk();
        $clases3 = $res3->viewData('clasesFiltradas');
        $this->assertTrue($clases3->contains('id', $c->id));
    }

    public function test_sin_filtros_no_repite_clases_de_hoy_en_listado_inferior(): void
    {
        $cHoy = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado',
        ]);
        $cHoy->profesores()->attach($this->profesor->id);

        $cFutura = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-15',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => false,
        ]);
        $cFutura->profesores()->attach($this->profesor->id);

        $res = $this->actingAs($this->admin)->get(route('web.clases.index'));
        $res->assertOk();

        $clasesFiltradas = $res->viewData('clasesFiltradas');
        // La futura sí está
        $this->assertTrue($clasesFiltradas->contains('id', $cFutura->id));
        // La de hoy NO se repite abajo cuando no hay filtros
        $this->assertFalse($clasesFiltradas->contains('id', $cHoy->id));
    }

    public function test_asistencia_sigue_bloqueada_en_clase_cancelada_de_hoy(): void
    {
        $c = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado',
        ]);
        $c->profesores()->attach($this->profesor->id);

        // Intento de guardar asistencia por AJAX / JSON
        $resJson = $this->actingAs($this->operativo)
            ->postJson(route('web.clases.asistencias', $c->id), [
                'asistencias' => [],
            ]);

        $resJson->assertStatus(422);
        $resJson->assertJson([
            'success' => false,
            'message' => 'La clase está cancelada. No se pueden registrar asistencias.',
        ]);

        // Ficha muestra mensaje de bloqueo y botón deshabilitado
        $resShow = $this->actingAs($this->operativo)->get(route('web.clases.show', $c->id));
        $resShow->assertOk();
        $resShow->assertSee('Esta clase está cancelada.');
    }

    public function test_contador_sin_asistencia_no_cuenta_clases_canceladas_de_hoy(): void
    {
        // Clase de hoy cancelada
        $cHoy = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-10-12',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'cancelada' => true,
            'motivo_cancelacion' => 'Feriado',
        ]);
        $cHoy->profesores()->attach($this->profesor->id);

        $res = $this->actingAs($this->admin)->get(route('web.clases.index'));
        $res->assertOk();

        // El badge compartido debe ser 0
        $this->assertEquals(0, $res->viewData('badgeClasesPendientes'));
    }
}
