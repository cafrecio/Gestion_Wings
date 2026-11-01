<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Control de Claude sobre T94: carrera real con dos conexiones. Otra conexión asigna un alumno con el bloqueo
 * del plan tomado, como hacen el alta y el cambio de plan, mientras el dueño saca esa frecuencia.
 * Afirma lo que tendría que pasar: una falla es un hallazgo. Se corre por ruta y no queda en la suite.
 */
class ControlT94CarreraTest extends TestCase
{
    private User $admin;
    private Grupo $grupo;
    private GrupoPlan $uno;
    private GrupoPlan $dos;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();
        // Sin transacción: la otra conexión tiene que ver los datos. El bajar migraciones de DatabaseMigrations falla en esta base.
        $this->artisan('migrate:fresh');
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Control', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Control']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->uno = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000, 'activo' => true]);
        $this->dos = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->alumno = $this->crearAlumno('31000222');
    }

    private function crearAlumno(string $dni): Alumno
    {
        return Alumno::create(['nombre' => 'Carrera', 'apellido' => $dni, 'dni' => $dni, 'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-01-10', 'celular' => '1144445555', 'deporte_id' => $this->grupo->deporte_id,
            'grupo_id' => $this->grupo->id, 'activo' => true]);
    }

    /** Segunda conexión: toma el plan, asigna al alumno y confirma recién a los 4 segundos. */
    private function asignarDesdeOtraConexion(): mixed
    {
        $c = config('database.connections.mysql');
        $codigo = sprintf(
            '$p=new PDO("mysql:host=%s;port=%s;dbname=%s",%s,%s);$p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);'
            .'$p->beginTransaction();$p->query("SELECT id FROM grupo_planes WHERE id=%d FOR UPDATE")->fetchAll();'
            .'$p->exec("INSERT INTO alumno_planes (alumno_id,plan_id,fecha_desde,activo,created_at,updated_at) VALUES (%d,%d,CURDATE(),1,NOW(),NOW())");'
            .'sleep(4);$p->commit();',
            $c['host'], $c['port'], $c['database'], var_export($c['username'], true), var_export((string) $c['password'], true),
            $this->dos->id, $this->alumno->id, $this->dos->id
        );
        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [], $pipes);
        sleep(1);

        return $proceso;
    }

    private function esperar(mixed $proceso): void
    {
        while (proc_get_status($proceso)['running']) {
            usleep(100000);
        }
        proc_close($proceso);
    }

    private function sinAlumnoEnFrecuenciaSacada(): void
    {
        $plan = GrupoPlan::find($this->dos->id);
        $asignado = AlumnoPlan::where('alumno_id', $this->alumno->id)->where('plan_id', $this->dos->id)->where('activo', true)->exists();
        $this->assertFalse($asignado && (!$plan || !$plan->activo), 'Quedó un alumno con una frecuencia desactivada');
    }

    private function guardarSinDos()
    {
        return $this->actingAs($this->admin)->from(route('web.grupos.edit', $this->grupo->id))
            ->put(route('web.grupos.update', $this->grupo->id), ['planes' => [
                ['id' => $this->uno->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000],
            ]]);
    }

    public function test_editando_el_grupo_con_frecuencia_usada_antes(): void
    {
        $otro = $this->crearAlumno('31000333');
        AlumnoPlan::create(['alumno_id' => $otro->id, 'plan_id' => $this->dos->id, 'fecha_desde' => '2026-01-10', 'fecha_hasta' => '2026-02-28', 'activo' => null]);

        $proceso = $this->asignarDesdeOtraConexion();
        $respuesta = $this->guardarSinDos();
        $this->esperar($proceso);

        $this->assertLessThan(500, $respuesta->getStatusCode());
        $this->sinAlumnoEnFrecuenciaSacada();
    }

    public function test_editando_el_grupo_con_frecuencia_nunca_usada(): void
    {
        $proceso = $this->asignarDesdeOtraConexion();
        $respuesta = $this->guardarSinDos();
        $this->esperar($proceso);

        $this->assertLessThan(500, $respuesta->getStatusCode(), 'Error del servidor: la relación no deja borrar la frecuencia recién asignada');
        $this->sinAlumnoEnFrecuenciaSacada();
    }

    public function test_desde_la_ficha(): void
    {
        $otro = $this->crearAlumno('31000444');
        AlumnoPlan::create(['alumno_id' => $otro->id, 'plan_id' => $this->dos->id, 'fecha_desde' => '2026-01-10', 'fecha_hasta' => '2026-02-28', 'activo' => null]);

        $proceso = $this->asignarDesdeOtraConexion();
        $respuesta = $this->actingAs($this->admin)->from(route('web.grupos.show', $this->grupo->id))
            ->delete(route('web.grupos.plans.destroy', $this->dos->id));
        $this->esperar($proceso);

        $this->assertLessThan(500, $respuesta->getStatusCode());
        $this->sinAlumnoEnFrecuenciaSacada();
    }
}
