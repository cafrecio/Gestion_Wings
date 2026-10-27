<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, Deporte, Profesor, Subrubro, TipoCaja, User};
use App\Services\{CashflowCorreccionService, CashflowService, ReporteSueldosService, SubrubroSueldoService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReporteSueldosOperativasT86Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operativa;
    protected TipoCaja $medio;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-27 12:00:00');
        $this->withoutVite();
        Notification::fake();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativa = $this->crearOperativa('Operativa del reporte');
        $this->medio = TipoCaja::where('activo', true)->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function crearOperativa(string $nombre): User
    {
        $u = User::factory()->create(['name' => $nombre, 'rol' => 'OPERATIVO', 'activo' => true]);
        app(SubrubroSueldoService::class)->paraUsuarioOperativo($u);
        return $u->fresh();
    }

    protected function pago(User $u, string $monto = '48000.75', string $fecha = '2026-10-27'): CashflowMovimiento
    {
        return app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $this->admin->id,
            'subrubro_id' => $u->subrubro_id, 'tipo_caja_id' => $this->medio->id,
            'monto' => $monto, 'fecha' => $fecha, 'observaciones' => 'Sueldo sintetico reporte T86']);
    }

    protected function pagos(string $mes = '2026-10', ?int $deporte = null): array
    {
        $r = app(ReporteSueldosService::class)->obtener($mes, $deporte);
        $this->assertArrayHasKey('pagos_operativas', $r);
        return $r['pagos_operativas'];
    }

    public function test_la_ruta_de_sueldos_muestra_el_pago_y_la_operativa(): void
    {
        $this->pago($this->operativa);
        $this->actingAs($this->admin)->get(route('web.reportes.sueldos'))->assertOk()
            ->assertSee('Pagos de operativas')->assertSee('Operativa del reporte')->assertSee('$48,000.75')
            ->assertViewHas('reporte', fn ($r) => $r['pagos_operativas']['total_centavos'] === 4800075 && $r['costo'] === 0);
    }

    public function test_selecciona_el_mes_y_no_incluye_fechas_futuras(): void
    {
        $this->pago($this->operativa, '100', '2026-09-30');
        $this->pago($this->operativa, '200', '2026-10-01');
        $this->pago($this->operativa, '300', '2026-10-27');
        $this->pago($this->operativa, '400', '2026-10-28');
        $this->pago($this->operativa, '500', '2026-11-01');
        $this->assertSame(50000, $this->pagos()['total_centavos']);
        $this->assertSame(10000, $this->pagos('2026-09')['total_centavos']);
    }

    public function test_agrupa_varios_pagos_y_excluye_sueldos_docentes_y_gastos_ajenos(): void
    {
        $otra = $this->crearOperativa('Segunda operativa');
        $this->pago($this->operativa); $this->pago($this->operativa, '10000.25'); $this->pago($otra, '5000');
        $p = Profesor::create(['deporte_id' => Deporte::firstOrFail()->id, 'nombre' => 'Docente', 'apellido' => 'Reporte', 'dni' => '31869999',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 10000, 'activo' => true]);
        $sub = app(SubrubroSueldoService::class)->paraProfesor($p);
        app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $this->admin->id,
            'subrubro_id' => $sub->id, 'tipo_caja_id' => $this->medio->id, 'monto' => 99999, 'fecha' => '2026-10-27',
            'referencia_tipo' => CashflowMovimiento::REF_LIQUIDACION, 'referencia_id' => 999]);
        $gasto = Subrubro::where('es_reservado_sistema', false)
            ->whereHas('rubro', fn ($q) => $q->where('tipo', 'EGRESO')->where('nombre', '!=', 'Sueldos'))->firstOrFail();
        app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $this->admin->id,
            'subrubro_id' => $gasto->id, 'tipo_caja_id' => $this->medio->id, 'monto' => 7000, 'fecha' => '2026-10-27']);
        $r = $this->pagos();
        $this->assertSame(6300100, $r['total_centavos']);
        $this->assertCount(2, $r['filas']);
        $fila = collect($r['filas'])->firstWhere('subrubro_id', $this->operativa->subrubro_id);
        $this->assertSame(5800100, $fila['centavos']);
        $this->assertSame(2, $fila['movimientos']);
    }

    public function test_anular_el_pago_resta_el_contraasiento_sin_contarlo_dos_veces(): void
    {
        $p = $this->pago($this->operativa);
        $this->assertSame(4800075, $this->pagos()['total_centavos']);
        app(CashflowCorreccionService::class)->eliminar($p->id, $this->admin->id,
            ['clave' => (string) Str::uuid(), 'version' => 0, 'motivo' => 'Anulacion sintetica para el reporte']);
        $this->assertSame(0, $this->pagos()['total_centavos']);
        $this->assertSame([], $this->pagos()['filas']);
    }

    public function test_el_pago_historico_no_desaparece_al_cambiar_rol_o_desactivar(): void
    {
        $this->pago($this->operativa);
        $antes = $this->pagos();
        $this->operativa->rol = 'ADMIN'; $this->operativa->activo = false; $this->operativa->save();
        $this->assertSame($antes, $this->pagos());
    }

    public function test_el_filtro_de_deporte_no_reparte_el_gasto_del_club(): void
    {
        $this->pago($this->operativa);
        $deporte = Deporte::firstOrFail()->id;
        $this->assertSame($this->pagos(), $this->pagos('2026-10', $deporte));
        $r = app(ReporteSueldosService::class)->obtener('2026-10', $deporte);
        $this->assertSame(0, $r['costo']);
        $this->assertSame([], $r['profesores']);
    }

    public function test_sin_pagos_muestra_cero_y_el_estado_vacio(): void
    {
        $this->assertSame(['total_centavos' => 0, 'filas' => []], $this->pagos());
        $this->actingAs($this->admin)->get(route('web.reportes.sueldos'))->assertOk()->assertSee('Sin pagos de operativas en este mes');
    }

    public function test_identifica_la_operativa_por_fk_y_escapa_su_nombre(): void
    {
        $this->operativa->name = '<img src=x onerror=alert(1)>'; $this->operativa->save();
        $this->operativa->subrubro->update(['nombre' => 'Nombre historico sin prefijo']);
        $this->pago($this->operativa);
        $huerfano = Subrubro::create(['rubro_id' => $this->operativa->subrubro->rubro_id, 'nombre' => 'Op-Sin vinculo',
            'permitido_para' => 'ADMIN', 'es_reservado_sistema' => false, 'clasificacion_resultado' => 'NEGOCIO']);
        CashflowMovimiento::create(['subrubro_id' => $huerfano->id, 'tipo_caja_id' => $this->medio->id, 'monto' => -90000,
            'fecha' => '2026-10-27', 'usuario_admin_id' => $this->admin->id]);
        $this->assertSame(4800075, $this->pagos()['total_centavos']);
        $this->actingAs($this->admin)->get(route('web.reportes.sueldos'))->assertOk()
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
    }
}
