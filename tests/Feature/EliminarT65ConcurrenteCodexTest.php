<?php

namespace Tests\Feature;

use App\Http\Controllers\{AlumnoWebController, ClaseWebController};
use App\Models\{Alumno, CargoAlumno, Clase, Subrubro, TipoCaja};
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Notification, Storage};
use Tests\Support\EscenarioT65;
use Tests\TestCase;

/** Filas confirmadas, dos sesiones MariaDB y servicios/controladores reales. */
#[\PHPUnit\Framework\Attributes\Group('lento')]
class EliminarT65ConcurrenteCodexTest extends TestCase
{
    use EscenarioT65;

    private string $principal;
    private TipoCaja $medioT65;
    private ?int $subCuotaCreado = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->principal = config('database.default');
        $this->assertMatchesRegularExpression('/^wings_testing_(?:codex|claude|gemini)$/', DB::connection()->getDatabaseName());
        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh')->assertExitCode(0);
            RefreshDatabaseState::$migrated = true;
        }
        $this->travelTo(Carbon::parse('2026-10-27 18:00:00'));
        Storage::fake(); Notification::fake();
        $this->escenarioT65();
        if (!Subrubro::where('nombre', 'Cuota Mensual')->exists()) {
            $this->subCuotaCreado = Subrubro::create(['nombre' => 'Cuota Mensual',
                'rubro_id' => Subrubro::firstOrFail()->rubro_id, 'permitido_para' => 'OPERATIVO',
                'afecta_caja' => true, 'activo' => true, 'es_reservado_sistema' => true])->id;
        }
        $this->medioT65 = TipoCaja::create(['nombre' => 'Transferencia verificación T65', 'activo' => true]);
        config(['database.connections.t65_otra' => config('database.connections.'.$this->principal)]);
        $otra = DB::connection('t65_otra');
        $otra->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $this->assertNotSame(DB::selectOne('SELECT CONNECTION_ID() id')->id, $otra->selectOne('SELECT CONNECTION_ID() id')->id);
    }

    private function borrar(string $tipo): mixed
    {
        $this->actingAs($this->duenioT65);
        $request = Request::create('/t65-verificacion', 'DELETE');
        $request->headers->set('Accept', 'application/json');
        return $tipo === 'clase'
            ? app(ClaseWebController::class)->destroy($request, $this->claseT65->id)
            : app(AlumnoWebController::class)->destroy($request, $this->alumnoT65->id);
    }

    private function operacion(string $flujo): void
    {
        if (str_starts_with($flujo, 'asistencia')) {
            $this->actingAs($this->operativaT65);
            $request = Request::create('/t65-verificacion', 'POST', ['items' => [[
                'alumno_id' => $this->alumnoT65->id, 'presente' => true]]]);
            $request->headers->set('Accept', 'application/json');
            $r = app(ClaseWebController::class)->storeAsistencias($request, $this->claseT65->id);
            $this->assertSame(200, $r->getStatusCode());
            return;
        }
        // La inscripción se cobra por el mismo servicio: primero se imputa a ese cargo.
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin([
            'alumno_id' => $this->alumnoT65->id, 'usuario_admin_id' => $this->duenioT65->id,
            'tipo_caja_id' => $this->medioT65->id, 'fecha_pago' => '2026-10-27',
            'items' => [['periodo' => '2026-10', 'monto' => $flujo === 'inscripcion' ? 0 : 15000]],
            'monto_entregado' => $flujo === 'inscripcion' ? 5000 : 20000,
            'motivo' => 'Verificación de bloqueo concurrente T65.',
        ]);
    }

    private function esperarSinEscribir(callable $accion, string $tabla): void
    {
        $otra = DB::connection('t65_otra');
        DB::setDefaultConnection('t65_otra');
        $otra->flushQueryLog(); $otra->enableQueryLog();
        try {
            $accion();
            $this->fail('La segunda sesión no esperó la fila protegida.');
        } catch (\Illuminate\Database\DeadlockException|QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
            $sql = $e instanceof QueryException ? $e : $e->getPrevious();
            $this->assertInstanceOf(QueryException::class, $sql);
            $this->assertStringContainsString('`'.$tabla.'`', $sql->getSql());
            $this->assertStringContainsString('for update', strtolower($sql->getSql()));
        } finally {
            $queries = $otra->getQueryLog(); $otra->disableQueryLog();
            DB::setDefaultConnection($this->principal);
        }
        $this->assertSame([], array_values(array_filter($queries,
            fn ($q) => preg_match('/^\s*(insert|update|delete)\b/i', $q['query']))));
    }

    public function test_borrado_retiene_alumno_y_clase_frente_a_cuota_inscripcion_y_asistencia(): void
    {
        foreach (['cuota', 'inscripcion', 'asistencia_alumno', 'asistencia_clase'] as $flujo) {
            DB::beginTransaction();
            try {
                $tipo = $flujo === 'asistencia_clase' ? 'clase' : 'alumno';
                // Retener exactamente la fila que relee destroy(), antes de sus deletes.
                if ($tipo === 'clase') Clase::whereKey($this->claseT65->id)->lockForUpdate()->firstOrFail();
                else Alumno::whereKey($this->alumnoT65->id)->lockForUpdate()->firstOrFail();
                $this->esperarSinEscribir(fn () => $this->operacion($flujo), $tipo === 'clase' ? 'clases' : 'alumnos');
                $this->assertSame(200, $this->borrar($tipo)->getStatusCode());
            } finally { DB::rollBack(); }
            $this->assertDatabaseHas('alumnos', ['id' => $this->alumnoT65->id]);
            $this->assertDatabaseHas('clases', ['id' => $this->claseT65->id]);
            $this->assertDatabaseCount('pagos', 0);
            $this->assertDatabaseCount('asistencias', 0);
        }
    }

    public function test_cobro_y_asistencia_retienen_la_misma_fila_antes_del_borrado(): void
    {
        foreach (['cuota', 'inscripcion', 'asistencia_alumno', 'asistencia_clase'] as $flujo) {
            DB::beginTransaction();
            try {
                $this->operacion($flujo);
                $tipo = $flujo === 'asistencia_clase' ? 'clase' : 'alumno';
                $this->esperarSinEscribir(fn () => $this->borrar($tipo), $tipo === 'clase' ? 'clases' : 'alumnos');
                $this->assertDatabaseHas('alumnos', ['id' => $this->alumnoT65->id]);
                $this->assertDatabaseHas('clases', ['id' => $this->claseT65->id]);
            } finally { DB::rollBack(); }
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->principal)) {
            DB::setDefaultConnection($this->principal);
            $otra = DB::connection('t65_otra');
            while ($otra->transactionLevel()) $otra->rollBack();
            while (DB::transactionLevel()) DB::rollBack();
            DB::purge('t65_otra');
            if (isset($this->alumnoT65)) {
                $cargos = CargoAlumno::where('alumno_id', $this->alumnoT65->id)->pluck('id');
                DB::table('cargo_alumno_eventos')->whereIn('cargo_alumno_id', $cargos)->delete();
                DB::table('cargos_alumno')->whereIn('id', $cargos)->delete();
                DB::table('deuda_cuotas')->where('alumno_id', $this->alumnoT65->id)->delete();
                DB::table('reporte_analitico_historial')->where('persona_id', $this->alumnoT65->id)->where('tipo', 'CUOTA')->delete();
                DB::table('alumnos')->where('id', $this->alumnoT65->id)->delete();
                DB::table('inscripcion_personas')->where('dni', $this->alumnoT65->dni)->delete();
                $this->claseT65->delete(); $this->planT65->delete(); $this->grupoT65->delete();
                DB::table('niveles')->where('id', $this->grupoT65->nivel_id)->delete();
                DB::table('deportes')->where('id', $this->grupoT65->deporte_id)->delete();
                if (isset($this->medioT65)) $this->medioT65->delete();
                if ($this->subCuotaCreado) Subrubro::whereKey($this->subCuotaCreado)->delete();
                $this->duenioT65->delete(); $this->operativaT65->delete();
            }
        }
        parent::tearDown();
    }
}
