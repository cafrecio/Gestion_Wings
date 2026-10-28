<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, AlumnoRevisionCobranza, CargoAlumno, Deporte, DeudaCuota, DeudaPerdida, Grupo, GrupoPlan, Nivel, TipoCaja, User};
use App\Services\{DeudaInactivosService, PagoCuotaService, ReporteAlumnosService, RevisionCobranzaService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Ataques de Claude a T70/T71 (verificación, fuera de la suite permanente). */
class VerificacionT70T71ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $this->withoutVite();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín T70', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial T70']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
    }

    /** Ataque 1: lo perdido no se puede cobrar, ni con el alumno de vuelta y forzando el período. */
    public function test_cuota_perdida_parcial_no_admite_cobro_al_volver(): void
    {
        $a = $this->alumno();
        $cuota = $this->cuota($a, '2026-09', 10000);
        $cuota->update(['monto_pagado' => 4000]);
        $a->update(['activo' => false]);
        app(DeudaInactivosService::class)->perder($a->id, 600000, $this->admin);
        $this->assertSame(6000.0, (float) $cuota->fresh()->monto_perdido);
        $a->update(['activo' => true]);

        $medio = TipoCaja::where('activo', true)->first();
        $cobro = false;
        try {
            app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $a->id, 'tipo_caja_id' => $medio->id,
                'usuario_admin_id' => $this->admin->id, 'monto_entregado' => 100, 'items' => [['periodo' => '2026-09', 'monto' => 100]]]);
            $cobro = true;
        } catch (\Throwable $e) {
            $this->assertMatchesRegularExpression('/saldo|pagada/i', $e->getMessage());
        }
        $this->assertFalse($cobro, 'Cobró una cuota dada por perdida.');
        $this->assertSame(4000.0, (float) $cuota->fresh()->monto_pagado);
    }

    /** Ataque 2: la baja que sale de la cola de Revisión también deja fecha e historial. */
    public function test_baja_desde_revision_deja_fecha_e_historial(): void
    {
        $a = $this->alumno();
        $rev = AlumnoRevisionCobranza::create(['alumno_id' => $a->id, 'periodo_objetivo' => '2026-11',
            'motivo' => 'Sin asistencias el mes anterior', 'estado_revision' => AlumnoRevisionCobranza::ESTADO_PENDIENTE]);
        app(RevisionCobranzaService::class)->resolver($rev->id, AlumnoRevisionCobranza::RESOLUCION_INACTIVO, 'se fue', $this->admin->id);
        $this->assertFalse($a->fresh()->activo);
        $this->assertNotNull($a->fresh()->inactivo_desde);
        $this->assertSame(1, DB::table('alumno_estado_historial')->where('alumno_id', $a->id)->where('activo', false)->count());
        $this->assertSame(1, app(ReporteAlumnosService::class)->obtener('2026-10')['pases_inactivo_mes']);
    }

    /** Ataque 3: dar por perdida dos veces seguidas con el mismo importe: la segunda no escribe nada. */
    public function test_doble_envio_no_pierde_dos_veces(): void
    {
        $a = $this->alumno();
        $this->cuota($a, '2026-09', 10000);
        $a->update(['activo' => false]);
        app(DeudaInactivosService::class)->perder($a->id, 1000000, $this->admin);
        $this->actingAs($this->admin)->postJson(route('web.inactivos.perder', $a->id), ['centavos' => 1000000])->assertStatus(422);
        $this->assertSame(1, DeudaPerdida::count());
        $this->assertSame(10000.0, (float) DeudaCuota::where('alumno_id', $a->id)->sum('monto_perdido'));
    }

    private function alumno(): Alumno
    {
        $a = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'dni' => (string) (70000000 + Alumno::count()),
            'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000',
            'fecha_alta' => '2026-03-01', 'activo' => true, 'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
        return $a;
    }

    private function cuota(Alumno $a, string $periodo, float $importe): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => $importe, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    }
}
