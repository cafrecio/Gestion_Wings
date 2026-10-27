<?php

namespace Tests\VerificacionCodex;

use App\Models\{Alumno, AlumnoPlan, AlumnoRevisionCobranza, Deporte, GeneracionCuotas, Grupo, GrupoPlan, Nivel, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RevisionResueltaT92Test extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex'
            || DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('Solo wings_testing_codex antes de migrar.');
        }
    }

    public static function resoluciones(): array
    {
        return array_map(fn ($r) => [$r], ['CONTINUA', 'INACTIVO', 'REACTIVADO_AUTO']);
    }

    #[DataProvider('resoluciones')]
    public function test_repetir_respeta_toda_la_fila_resuelta(string $resolucion): void
    {
        Carbon::setTestNow('2026-11-01 06:00:00');
        Notification::fake();
        GeneracionCuotas::query()->delete();
        $d = Deporte::create(['nombre' => 'T92 resuelta', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $g = Grupo::create(['deporte_id' => $d->id, 'nivel_id' => Nivel::create(['nombre' => 'T92'])->id, 'activo' => true]);
        $p = GrupoPlan::create(['grupo_id' => $g->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $a = Alumno::create(['nombre' => 'Sintetico', 'apellido' => 'Resuelto', 'dni' => '20929999', 'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111', 'deporte_id' => $d->id, 'grupo_id' => $g->id, 'fecha_alta' => '2026-06-01', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $p->id, 'fecha_desde' => '2026-06-01', 'activo' => true]);
        $this->artisan('cobranza:generar-deudas --si-falta')->assertSuccessful();
        $rev = AlumnoRevisionCobranza::where('alumno_id', $a->id)->firstOrFail();
        // Fixture deliberado: alumno sigue elegible para el bucle y sin deuda del mes,
        // para probar firstOrCreate incluso con una resolucion historica existente.
        $rev->update(['estado_revision' => 'RESUELTO', 'resolucion' => $resolucion, 'nota_resolucion' => 'Nota que debe conservarse',
            'usuario_resolucion_id' => User::factory()->create(['rol' => 'ADMIN'])->id, 'resuelto_at' => '2026-11-01 07:00:00']);
        $antes = (array) DB::table('alumnos_revision_cobranza')->where('id', $rev->id)->first();
        Carbon::setTestNow('2026-11-02 09:00:00');
        $this->artisan('cobranza:generar-deudas')->expectsOutputToContain('ya estaba en revisión.')->assertSuccessful();
        $despues = (array) DB::table('alumnos_revision_cobranza')->where('id', $rev->id)->first();
        $this->assertSame($antes, $despues);
        $this->assertSame(1, AlumnoRevisionCobranza::where('alumno_id', $a->id)->where('periodo_objetivo', '2026-11')->count());
        $constancia = GeneracionCuotas::where('periodo', '2026-11')->firstOrFail();
        $this->assertSame(1, (int) $constancia->a_revision);
        $this->assertSame('2026-11-01 06:00:00', $constancia->terminada_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, $a->deudaCuotas()->count());
        file_put_contents(__DIR__.'/resuelta-'.$resolucion.'.json', json_encode(compact('antes', 'despues', 'constancia'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
