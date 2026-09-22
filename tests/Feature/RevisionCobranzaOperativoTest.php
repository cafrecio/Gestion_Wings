<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\AlumnoRevisionCobranza;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FIN-08: la revision de cobranza es trabajo del mostrador.
 *
 * Decision de Carlos (17/09/2026): decidir si a un alumno que no vino se le
 * genera la cuota del mes es 100% tarea del OPERATIVO. La ruta y el menu
 * estaban solo para el ADMIN, y una prueba anterior lo exigia ("la revision es
 * del admin"). Abrirla era seguro recien desde el 19/09: hasta entonces marcar
 * "Inactivo" condonaba el mes anterior, y condonar es solo del ADMIN.
 */
class RevisionCobranzaOperativoTest extends TestCase
{
    use RefreshDatabase;

    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 30000,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Renata',
            'apellido' => 'Cuello',
            'dni' => '44238951',
            'fecha_nacimiento' => '2011-04-02',
            'celular' => '1155667788',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-03-01',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-03-01',
            'activo' => true,
        ]);
    }

    public function test_el_operativo_entra_a_la_revision(): void
    {
        $this->revision();

        $this->actingAs($this->usuario(User::ROL_OPERATIVO))
            ->get(route('web.revision-cobranza.index'))
            ->assertOk()
            ->assertSee('Cuello');
    }

    public function test_el_operativo_resuelve_continua_y_se_genera_la_cuota(): void
    {
        $revision = $this->revision();

        $this->actingAs($this->usuario(User::ROL_OPERATIVO))
            ->post(route('web.revision-cobranza.resolver', $revision->id), [
                'resolucion' => AlumnoRevisionCobranza::RESOLUCION_CONTINUA,
                'nota_resolucion' => 'Vuelve la semana que viene',
            ])
            ->assertRedirect(route('web.revision-cobranza.index'))
            ->assertSessionHas('success');

        $this->assertSame(AlumnoRevisionCobranza::ESTADO_RESUELTO, $revision->fresh()->estado_revision);
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 30000,
        ]);
    }

    /** Inactivo da de baja pero no le da al operativo un camino para condonar. */
    public function test_el_operativo_resuelve_inactivo_sin_condonar_nada(): void
    {
        $deudaVieja = DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-08',
            'monto_original' => 30000,
            'monto_pagado' => 10000,
            'saldo_pendiente' => 20000,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
            'observaciones' => 'Pago parcial en mostrador',
        ]);
        $revision = $this->revision();

        // Nota corta: la pantalla muestra el error arriba, y tiene que estar en
        // castellano (la app corre con APP_LOCALE=en y sin traducciones).
        $this->actingAs($this->usuario(User::ROL_OPERATIVO))
            ->post(route('web.revision-cobranza.resolver', $revision->id), [
                'resolucion' => AlumnoRevisionCobranza::RESOLUCION_INACTIVO,
                'nota_resolucion' => 'ok',
            ])
            ->assertSessionHasErrors(['nota_resolucion' => 'La nota debe tener al menos 5 caracteres.']);
        $this->assertTrue((bool) $this->alumno->fresh()->activo);

        $this->actingAs($this->usuario(User::ROL_OPERATIVO))
            ->post(route('web.revision-cobranza.resolver', $revision->id), [
                'resolucion' => AlumnoRevisionCobranza::RESOLUCION_INACTIVO,
                'nota_resolucion' => 'Avisó que deja el club',
            ])
            ->assertSessionHas('success');

        $this->assertFalse((bool) $this->alumno->fresh()->activo);
        $deudaVieja->refresh();
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $deudaVieja->estado);
        $this->assertEquals(20000, $deudaVieja->saldo_pendiente);
        $this->assertSame('Pago parcial en mostrador', $deudaVieja->observaciones);
        $this->assertSame(0, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-09')->count());
    }

    public function test_el_profesor_no_entra_ni_resuelve(): void
    {
        $revision = $this->revision();
        $profesor = $this->usuario(User::ROL_PROFESOR);

        $this->assertNotSame(200, $this->actingAs($profesor)->get(route('web.revision-cobranza.index'))->getStatusCode());

        $this->actingAs($profesor)->post(route('web.revision-cobranza.resolver', $revision->id), [
            'resolucion' => AlumnoRevisionCobranza::RESOLUCION_INACTIVO,
            'nota_resolucion' => 'No deberia poder',
        ]);

        $this->assertSame(AlumnoRevisionCobranza::ESTADO_PENDIENTE, $revision->fresh()->estado_revision);
        $this->assertTrue((bool) $this->alumno->fresh()->activo);
    }

    /** Un permiso sin link es una puerta sin picaporte. */
    public function test_el_menu_del_operativo_tiene_el_link_a_revision(): void
    {
        $this->actingAs($this->usuario(User::ROL_OPERATIVO))
            ->get(route('web.alumnos.index'))
            ->assertOk()
            ->assertSee(route('web.revision-cobranza.index'), false);
    }

    public function test_el_menu_del_profesor_no_tiene_revision(): void
    {
        $this->actingAs($this->usuario(User::ROL_PROFESOR))
            ->get(route('web.clases.index'))
            ->assertDontSee(route('web.revision-cobranza.index'), false);
    }

    private function revision(): AlumnoRevisionCobranza
    {
        return AlumnoRevisionCobranza::create([
            'alumno_id' => $this->alumno->id,
            'periodo_objetivo' => '2026-09',
            'motivo' => 'Sin asistencias el mes anterior',
            'estado_revision' => AlumnoRevisionCobranza::ESTADO_PENDIENTE,
        ]);
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol' => $rol, 'activo' => true]);
    }
}
