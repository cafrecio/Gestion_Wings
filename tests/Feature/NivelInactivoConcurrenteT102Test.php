<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T102: inactivar un nivel y asignarle un grupo se esperan entre sí. Si no, un grupo activo
 * podría quedar en un nivel recién inactivado. Otra conexión MariaDB tiene tomado el nivel:
 * cada pedido tiene que quedarse esperando y no escribir nada.
 */
#[\PHPUnit\Framework\Attributes\Group('lento')]
class NivelInactivoConcurrenteT102Test extends TestCase
{
    use DatabaseMigrations;

    private Deporte $patin;
    private Nivel $escuelita;
    private Nivel $competencia;
    private Grupo $grupo;
    private GrupoPlan $plan;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->beforeApplicationDestroyed(function () {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]));
        $this->patin = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $this->escuelita = Nivel::create(['nombre' => 'Escuelita']);
        $this->competencia = Nivel::create(['nombre' => 'Competencia']);
        $this->grupo = Grupo::create(['deporte_id' => $this->patin->id, 'nivel_id' => $this->escuelita->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
    }

    /** Corre el pedido con el nivel tomado por otra conexión y exige que espere sin escribir. */
    private function assertEsperaAlNivel(Nivel $nivel, callable $pedido): void
    {
        config(['database.connections.t102_otra' => config('database.connections.'.DB::getDefaultConnection())]);
        $otra = DB::connection('t102_otra');
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        $antes = [Grupo::orderBy('id')->get()->toArray(), Nivel::orderBy('id')->get()->toArray()];
        $this->withoutExceptionHandling();

        try {
            $otra->beginTransaction();
            $otra->table('niveles')->where('id', $nivel->id)->lockForUpdate()->first();
            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

            try {
                $pedido();
                $this->fail('El pedido siguió sin esperar al nivel tomado por la otra conexión.');
            } catch (DeadlockException|QueryException $e) {
                $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
                // Sin candado, la escritura también esperaría, pero después de leer datos viejos.
                $sql = $e instanceof QueryException ? $e : $e->getPrevious();
                $this->assertStringContainsString('for update', strtolower($sql->getSql()));
                $this->assertStringContainsString('niveles', strtolower($sql->getSql()));
            }
        } finally {
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
            while ($otra->transactionLevel() > 0) {
                $otra->rollBack();
            }
            DB::purge('t102_otra');
        }

        $this->assertSame($antes, [Grupo::orderBy('id')->get()->toArray(), Nivel::orderBy('id')->get()->toArray()]);
    }

    public function test_inactivar_espera_a_quien_le_esta_asignando_un_grupo(): void
    {
        $this->assertEsperaAlNivel($this->competencia, fn () =>
            $this->patch(route('web.niveles.toggle-activo', $this->competencia->id)));
    }

    public function test_crear_un_grupo_espera_a_quien_esta_inactivando_el_nivel(): void
    {
        $this->assertEsperaAlNivel($this->competencia, fn () =>
            $this->post(route('web.grupos.store'), [
                'deporte_id' => $this->patin->id, 'nivel_id' => $this->competencia->id,
                'planes' => [['clases_por_semana' => 1, 'precio_mensual' => 20000]],
            ]));
    }

    public function test_cambiar_de_nivel_espera_a_quien_esta_inactivando_el_destino(): void
    {
        $this->assertEsperaAlNivel($this->competencia, fn () =>
            $this->put(route('web.grupos.update', $this->grupo->id), [
                'nivel_id' => $this->competencia->id,
                'planes' => [['id' => $this->plan->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000]],
            ]));
    }

    public function test_activar_un_grupo_espera_a_quien_esta_inactivando_su_nivel(): void
    {
        $this->grupo->update(['activo' => false]);

        $this->assertEsperaAlNivel($this->escuelita, fn () =>
            $this->patch(route('web.grupos.toggle-activo', $this->grupo->id)));
    }
}
