<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Control de Claude sobre T94: casos que la entrega de Gemini no cubre. Afirma lo que tendría que pasar:
 * una falla es un hallazgo. Se corre por ruta y no queda en la suite.
 */
class ControlT94ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $uno;
    private GrupoPlan $dos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($this->admin);
        $this->deporte = Deporte::create(['nombre' => 'Control', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Control']);
        $this->grupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->uno = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000, 'activo' => true]);
        $this->dos = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
    }

    private function alumnoQueUso(GrupoPlan $plan): Alumno
    {
        $alumno = Alumno::create(['nombre' => 'Uso', 'apellido' => 'Anterior', 'dni' => (string) random_int(30000000, 39999999),
            'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => '2026-01-10', 'celular' => '1144445555',
            'deporte_id' => $this->deporte->id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-01-10', 'fecha_hasta' => '2026-02-28', 'activo' => null]);

        return $alumno;
    }

    private function fila(GrupoPlan $plan, ?int $clases = null, ?int $precio = null): array
    {
        return ['id' => $plan->id, 'clases_por_semana' => $clases ?? $plan->clases_por_semana, 'precio_mensual' => $precio ?? (int) $plan->precio_mensual];
    }

    private function guardar(array $planes)
    {
        return $this->from(route('web.grupos.edit', $this->grupo->id))
            ->put(route('web.grupos.update', $this->grupo->id), ['planes' => $planes]);
    }

    /** «Editar una fila y cambiarle la frecuencia a una que ya existe no puede dar error del servidor.» */
    public function test_intercambiar_dos_frecuencias_no_da_error_del_servidor(): void
    {
        $this->assertLessThan(500, $this->guardar([$this->fila($this->uno, 2), $this->fila($this->dos, 1)])->getStatusCode());
    }

    /** Un formulario abierto antes de desactivar no tiene que revivir la frecuencia sin que nadie lo pida. */
    public function test_la_pantalla_vieja_no_revive_una_frecuencia_desactivada(): void
    {
        $this->alumnoQueUso($this->dos);
        $this->delete(route('web.grupos.plans.destroy', $this->dos))->assertSessionHas('success');
        $this->assertFalse($this->dos->fresh()->activo);

        $this->guardar([$this->fila($this->uno), $this->fila($this->dos)]);

        $this->assertFalse($this->dos->fresh()->activo, 'La pantalla vieja reactivó la frecuencia');
    }

    /** Quitar la fila y volver a cargar la misma frecuencia no la desactiva: el aviso no puede decir que sí. */
    public function test_sacar_y_volver_a_cargar_la_misma_frecuencia_no_avisa_que_se_desactivo(): void
    {
        $this->alumnoQueUso($this->dos);

        $respuesta = $this->guardar([$this->fila($this->uno), ['clases_por_semana' => 2, 'precio_mensual' => 35000]]);

        $this->assertTrue($this->dos->fresh()->activo);
        $respuesta->assertSessionHas('success', 'Grupo actualizado correctamente.');
    }

    /** Cambiar una fila a la frecuencia de una desactivada no puede mezclar la historia de las dos. */
    public function test_cambiar_a_una_frecuencia_desactivada_no_mueve_la_historia_ajena(): void
    {
        $alumno = $this->alumnoQueUso($this->dos);
        $tres = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 3, 'precio_mensual' => 40000, 'activo' => true]);
        $this->delete(route('web.grupos.plans.destroy', $this->dos))->assertSessionHas('success');

        $this->assertLessThan(500, $this->guardar([$this->fila($this->uno), $this->fila($tres, 2)])->getStatusCode());

        $this->assertSame($this->dos->id, AlumnoPlan::where('alumno_id', $alumno->id)->value('plan_id'), 'Se reescribió de qué plan fue un alumno');
    }

    /** El cobro no puede pasar a un alumno a una frecuencia desactivada. */
    public function test_el_cobro_no_acepta_una_frecuencia_desactivada(): void
    {
        $this->alumnoQueUso($this->dos);
        $this->delete(route('web.grupos.plans.destroy', $this->dos))->assertSessionHas('success');
        $efectivo = \App\Models\TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $alumno = Alumno::create(['nombre' => 'Actual', 'apellido' => 'Uno', 'dni' => '31000111', 'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-01-10', 'celular' => '1144445555', 'deporte_id' => $this->deporte->id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $this->uno->id, 'fecha_desde' => '2026-01-10', 'activo' => true]);

        $this->post(route('web.caja.pagar', $alumno->id), ['tipo_caja_id' => $efectivo->id, 'nuevo_plan_id' => $this->dos->id,
            'periodos' => [now()->format('Y-m')], 'fecha_pago' => now()->toDateString()])->assertSessionHasErrors('nuevo_plan_id');

        $this->assertSame($this->uno->id, $alumno->planes()->where('activo', true)->value('plan_id'));
    }

    /** La frecuencia desactivada tampoco se ofrece en las pantallas de alta y de cobro. */
    public function test_no_se_ofrece_en_el_alta_de_alumno(): void
    {
        $this->alumnoQueUso($this->dos);
        $this->delete(route('web.grupos.plans.destroy', $this->dos))->assertSessionHas('success');

        $datos = $this->get(route('web.alumnos.create'))->assertOk()->viewData('grupoPlanesJson');

        $this->assertSame([$this->uno->id], collect($datos[$this->grupo->id])->pluck('id')->all());
    }

    /** Eliminar por pedido directo una frecuencia ya desactivada no puede fallar ni decir algo falso. */
    public function test_eliminar_una_ya_desactivada_no_da_error_del_servidor(): void
    {
        $this->alumnoQueUso($this->dos);
        $this->delete(route('web.grupos.plans.destroy', $this->dos))->assertSessionHas('success');

        $this->assertLessThan(500, $this->delete(route('web.grupos.plans.destroy', $this->dos))->getStatusCode());
        $this->assertFalse($this->dos->fresh()->activo);
    }

    /** Un plan con precio cambiado conserva su precio del pasado al reactivarse: vale para deudas viejas. */
    public function test_reactivar_conserva_el_precio_de_los_meses_anteriores(): void
    {
        $this->alumnoQueUso($this->dos);
        $this->delete(route('web.grupos.plans.destroy', $this->dos))->assertSessionHas('success');

        $this->post(route('web.grupos.plans.store', $this->grupo->id), ['clases_por_semana' => 2, 'precio_mensual' => 35000])->assertSessionHas('success');

        $this->assertSame($this->dos->id, GrupoPlan::where('grupo_id', $this->grupo->id)->where('clases_por_semana', 2)->sole()->id);
        $this->assertSame(30000.0, $this->dos->fresh()->precioParaPeriodo('2026-01', null));
        $this->assertSame(1, DB::table('grupo_planes')->where('grupo_id', $this->grupo->id)->where('clases_por_semana', 2)->count());
    }
}
