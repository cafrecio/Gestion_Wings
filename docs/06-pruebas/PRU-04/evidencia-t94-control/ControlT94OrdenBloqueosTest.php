<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Tests\TestCase;

/**
 * Segundo control de Claude sobre T94. `update` toma grupo y después plan; `destroyPlan`, plan y después grupo.
 * La otra conexión hace lo de `update` mientras el dueño toca Eliminar en la ficha.
 * Afirma lo que tendría que pasar: una falla es un hallazgo. Se corre por ruta y no queda en la suite.
 */
class ControlT94OrdenBloqueosTest extends TestCase
{
    public function test_eliminar_desde_la_ficha_mientras_otro_edita_el_grupo_no_da_error_del_servidor(): void
    {
        $this->artisan('migrate:fresh');
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Control', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Control']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000, 'activo' => true]);
        $dos = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);

        $c = config('database.connections.mysql');
        $codigo = sprintf(
            '$p=new PDO("mysql:host=%s;port=%s;dbname=%s",%s,%s);$p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);'
            .'$p->beginTransaction();$p->query("SELECT id FROM grupos WHERE id=%d FOR UPDATE")->fetchAll();'
            .'sleep(3);$p->query("SELECT id FROM grupo_planes WHERE id=%d FOR UPDATE")->fetchAll();$p->commit();',
            $c['host'], $c['port'], $c['database'], var_export($c['username'], true), var_export((string) $c['password'], true), $grupo->id, $dos->id
        );
        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes);
        sleep(1);

        $respuesta = $this->actingAs($admin)->from(route('web.grupos.show', $grupo->id))->delete(route('web.grupos.plans.destroy', $dos->id));

        while (proc_get_status($proceso)['running']) {
            usleep(100000);
        }
        proc_close($proceso);

        $this->assertLessThan(500, $respuesta->getStatusCode(), 'Error del servidor por el orden de los bloqueos');
    }
}
