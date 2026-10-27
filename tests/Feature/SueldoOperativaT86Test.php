<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, Deporte, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\{ReporteMensualService, SubrubroSueldoService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Tests\TestCase;

class SueldoOperativaT86Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TipoCaja $medio;

    protected function beforeRefreshingDatabase(): void
    {
        // Suite permanente: cada agente usa su base; nuestras corridas fijan Codex en el bootstrap.
        $permitidas = ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'];
        if (!app()->environment('testing') || !in_array(config('database.connections.mysql.database'), $permitidas, true)
            || !in_array(DB::connection()->getDatabaseName(), $permitidas, true)) {
            throw new \RuntimeException('T86: solo una base de pruebas descartable.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-27 12:00:00');
        $this->withoutVite();
        Notification::fake();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->medio = TipoCaja::where('activo', true)->firstOrFail();
        DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2026-10-01']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function operativa(string $nombre = 'Operativa T86'): User
    {
        $u = User::factory()->create(['name' => $nombre, 'rol' => 'OPERATIVO', 'activo' => true]);
        app(SubrubroSueldoService::class)->paraUsuarioOperativo($u);
        return $u->fresh();
    }

    private function profesora(): Profesor
    {
        $p = Profesor::create(['deporte_id' => Deporte::firstOrFail()->id, 'nombre' => 'Docente', 'apellido' => 'T86', 'dni' => '31860000',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 10000, 'activo' => true]);
        app(SubrubroSueldoService::class)->paraProfesor($p);
        return $p->fresh();
    }

    private function datos(int $subrubro): array
    {
        return ['subrubro_id' => $subrubro, 'tipo_caja_id' => $this->medio->id, 'monto' => '48000.75',
            'fecha' => '2026-10-27', 'observaciones' => 'Sueldo sintetico T86'];
    }

    private function migrar(): void
    {
        (require database_path('migrations/2026_10_27_120000_habilitar_sueldos_operativas_en_cashflow.php'))->up();
    }

    public function test_el_sueldo_nuevo_es_admin_no_reservado_y_no_afecta_caja_operativa(): void
    {
        $u = $this->operativa();
        $s = $u->subrubro;
        $this->assertFalse($s->es_reservado_sistema);
        $this->assertSame('ADMIN', $s->permitido_para);
        $this->assertFalse($s->afecta_caja);
        $this->assertSame('NEGOCIO', $s->clasificacion_resultado);
        $this->assertSame('Sueldos', $s->rubro->nombre);
    }

    public function test_nuevo_cashflow_ofrece_operativas_y_oculta_docentes(): void
    {
        $a = $this->operativa('Primera T86'); $b = $this->operativa('Segunda T86'); $p = $this->profesora();
        $response = $this->actingAs($this->admin)->get(route('web.cashflow.movimiento'))->assertOk();
        $response->assertViewHas('rubros', function ($rubros) use ($a, $b, $p) {
            $sueldos = $rubros->firstWhere('nombre', 'Sueldos');
            return $sueldos && $sueldos->subrubros->pluck('id')->sort()->values()->all() === collect([$a->subrubro_id, $b->subrubro_id])->sort()->values()->all()
                && !$sueldos->subrubros->contains('id', $p->subrubro_id);
        });
        $response->assertSee('Op-Primera T86')->assertSee('Op-Segunda T86')->assertDontSee($p->subrubro->nombre);
    }

    public function test_el_duenio_paga_y_el_egreso_coincide_en_cashflow_reportes_e_inicio(): void
    {
        $u = $this->operativa();
        $this->actingAs($this->admin)->post(route('web.cashflow.movimiento.store'), $this->datos($u->subrubro_id))
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $m = CashflowMovimiento::where('subrubro_id', $u->subrubro_id)->sole();
        $this->assertSame('-48000.75', (string) $m->monto);
        $this->assertSame('EGRESO', $m->reporte_tipo);
        $this->assertSame('NEGOCIO', $m->reporte_clasificacion);
        $this->assertNull($m->reporte_deporte_id);
        $this->assertNull($m->referencia_tipo);
        $this->actingAs($this->admin)->get(route('web.cashflow.index'))->assertOk()->assertSee($u->subrubro->nombre);
        foreach (['web.reportes.index', 'admin.dashboard'] as $ruta) {
            $this->get(route($ruta))->assertOk()->assertViewHas('reporte', fn ($r) => $r['egresos'] === 4800075
                && $r['gastos_club'] === 4800075 && $r['resultado'] === -4800075 && $r['disponible'] === -4800075);
        }
        $r = app(ReporteMensualService::class)->obtener('2026-10');
        $this->assertSame('Op-Operativa T86', $r['filas'][0]['concepto']);
        $this->assertTrue($r['filas'][0]['confirmado']);
        $this->assertSame(-4800075, $r['filas'][0]['centavos']);
    }

    public function test_sueldo_docente_sigue_reservado_y_post_forzado_no_escribe(): void
    {
        $p = $this->profesora();
        $this->assertTrue($p->subrubro->es_reservado_sistema);
        $this->actingAs($this->admin)->post(route('web.cashflow.movimiento.store'), $this->datos($p->subrubro_id))->assertSessionHas('error');
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_renombrar_conserva_vinculo_movimiento_y_condiciones_del_sueldo(): void
    {
        $u = $this->operativa();
        $this->actingAs($this->admin)->post(route('web.cashflow.movimiento.store'), $this->datos($u->subrubro_id))->assertSessionHas('success');
        $antes = DB::table('cashflow_movimientos')->where('subrubro_id', $u->subrubro_id)->first();
        $this->put(route('web.usuarios.update', $u->id), ['name' => 'Renombrada T86', 'email' => $u->email, 'rol' => 'OPERATIVO'])->assertSessionHas('success');
        $this->assertSame($u->subrubro_id, $u->fresh()->subrubro_id);
        $this->assertSame('Op-Renombrada T86', $u->subrubro->fresh()->nombre);
        $this->assertFalse($u->subrubro->fresh()->es_reservado_sistema);
        $this->assertEquals($antes, DB::table('cashflow_movimientos')->where('id', $antes->id)->first());
    }

    public function test_migracion_identifica_por_fk_y_rol_sin_inferir_prefijos(): void
    {
        $u = $this->operativa(); $s = $u->subrubro; $s->update(['nombre' => 'Nombre historico sin prefijo', 'es_reservado_sistema' => true]);
        $suelto = Subrubro::create(['nombre' => 'Op-Sin usuario', 'rubro_id' => $s->rubro_id, 'permitido_para' => 'ADMIN', 'es_reservado_sistema' => true]);
        $admin = $this->operativa('Ex operativa T86'); $admin->rol = 'ADMIN'; $admin->save(); $admin->subrubro->update(['es_reservado_sistema' => true]);
        $p = $this->profesora();
        $this->migrar();
        $this->assertFalse($s->fresh()->es_reservado_sistema);
        $this->assertTrue($suelto->fresh()->es_reservado_sistema);
        $this->assertTrue($admin->subrubro->fresh()->es_reservado_sistema);
        $this->assertTrue($p->subrubro->fresh()->es_reservado_sistema);
    }

    public function test_migracion_no_desmarca_un_subrubro_vinculado_tambien_a_docente(): void
    {
        $p = $this->profesora(); $u = $this->operativa(); $u->subrubro_id = $p->subrubro_id; $u->save();
        $antes = DB::table('subrubros')->where('id', $p->subrubro_id)->first();
        $this->migrar();
        $this->assertEquals($antes, DB::table('subrubros')->where('id', $p->subrubro_id)->first());
    }

    public function test_migracion_es_idempotente_y_solo_cambia_la_reserva(): void
    {
        $u = $this->operativa(); $u->subrubro->update(['es_reservado_sistema' => true]);
        $antes = (array) DB::table('subrubros')->where('id', $u->subrubro_id)->first();
        $this->migrar();
        $esperado = $antes; $esperado['es_reservado_sistema'] = 0;
        $despues = (array) DB::table('subrubros')->where('id', $u->subrubro_id)->first();
        $this->assertSame($esperado, $despues);
        $this->migrar();
        $this->assertSame($despues, (array) DB::table('subrubros')->where('id', $u->subrubro_id)->first());
    }

    public function test_admin_sin_subrubro_al_pasar_a_operativo_recibe_un_sueldo_habilitado(): void
    {
        $u = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($this->admin)->put(route('web.usuarios.update', $u->id), ['name' => $u->name, 'email' => $u->email, 'rol' => 'OPERATIVO'])->assertSessionHas('success');
        $this->assertNotNull($u->fresh()->subrubro_id);
        $this->assertFalse($u->fresh()->subrubro->es_reservado_sistema);
    }

    public function test_operativo_y_profesor_no_pueden_pagar_sueldos_por_cashflow(): void
    {
        $u = $this->operativa();
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $actor = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $this->actingAs($actor)->post(route('web.cashflow.movimiento.store'), $this->datos($u->subrubro_id))->assertForbidden();
        }
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }
}
