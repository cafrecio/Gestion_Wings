<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, MovimientoOperativo, MovimientoOperativoCorreccion, Rubro, Subrubro, TipoCaja, User};
use App\Services\{CajaCorreccionService, CajaService, ReporteMensualService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CajaCorreccionesT51Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private CajaOperativa $caja;
    private MovimientoOperativo $movimiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true, 'name' => 'Dueño de prueba']);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true, 'name' => 'Operativa de prueba']);
        $medio = TipoCaja::create(['nombre' => 'Efectivo de prueba', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Gastos de prueba', 'tipo' => 'EGRESO']);
        $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Limpieza de prueba',
            'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true, 'clasificacion' => 'NEGOCIO']);
        $this->caja = CajaOperativa::create(['usuario_operativo_id' => $this->operativa->id, 'estado' => 'ABIERTA',
            'apertura_at' => now(), 'tipo_caja_efectivo_id' => $medio->id, 'efectivo_inicial' => 10000]);
        $this->movimiento = MovimientoOperativo::create(['caja_operativa_id' => $this->caja->id,
            'usuario_id' => $this->operativa->id, 'fecha' => today(), 'tipo_caja_id' => $medio->id,
            'subrubro_id' => $sub->id, 'monto' => 1000, 'observaciones' => 'Escobas', 'estado' => 'ACTIVO']);
    }

    private function datos(int $monto = 2500): array
    {
        return ['tipo_caja_id' => $this->movimiento->tipo_caja_id, 'subrubro_id' => $this->movimiento->subrubro_id,
            'monto' => $monto, 'fecha' => today()->toDateString(), 'observaciones' => 'Escobas y detergente'];
    }

    private function url(string $accion): string
    {
        return route('web.caja.movimientos.'.$accion, [$this->caja->id, $this->movimiento->id]);
    }

    public function test_editar_guarda_antes_autor_fecha_y_recalcula_arqueo_y_gasto_pendiente(): void
    {
        $this->actingAs($this->operativa)->put($this->url('update'), $this->datos())->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('2500.00', $this->movimiento->fresh()->monto);
        $this->assertSame('7500.00', app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado']);
        $fila = collect(app(ReporteMensualService::class)->obtener(today()->format('Y-m'))['filas'])->first();
        $this->assertSame(-250000, $fila['centavos']);
        $this->assertFalse($fila['confirmado']);
        $h = MovimientoOperativoCorreccion::sole();
        $this->assertSame('1000.00', $h->antes['monto']);
        $this->assertSame('2500.00', $h->despues['monto']);
        $this->assertSame($this->operativa->id, $h->usuario_id);
        $this->assertSame('Escobas', $h->antes['observaciones']);
        $this->assertTrue($h->created_at->isToday());
        $this->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('Historia de correcciones')->assertSee('Operativa de prueba');
    }

    public function test_eliminar_conserva_la_foto_y_no_suma_en_caja_ni_reporte(): void
    {
        $this->actingAs($this->admin)->delete($this->url('destroy'))->assertSessionHas('success');
        $this->assertNull($this->movimiento->fresh());
        $this->assertSame('10000.00', app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado']);
        $this->assertEmpty(app(ReporteMensualService::class)->obtener(today()->format('Y-m'))['filas']);
        $h = MovimientoOperativoCorreccion::sole();
        $this->assertSame('ELIMINAR', $h->accion);
        $this->assertNull($h->despues);
        $this->assertSame($this->movimiento->id, $h->movimiento_id);
        $this->assertSame('Escobas', $h->antes['observaciones']);
        $this->assertSame('Dueño de prueba', $h->autor);
        $this->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('Eliminado')->assertSee('Escobas');
    }

    public function test_otra_operativa_consulta_el_rastro_operativo_pero_no_modifica_la_caja(): void
    {
        app(CajaCorreccionService::class)->corregir($this->caja->id, $this->movimiento->id, $this->admin, $this->datos());
        $otra = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->actingAs($otra)->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('Historia de correcciones');
        $this->put($this->url('update'), $this->datos())->assertForbidden();
        $this->delete($this->url('destroy'))->assertForbidden();
        $this->assertDatabaseCount('movimiento_operativo_correcciones', 1);
    }

    public function test_profesor_no_lee_ni_edita_ni_elimina(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]));
        $this->get(route('web.caja.detalle', $this->caja->id))->assertForbidden();
        $this->put($this->url('update'), $this->datos())->assertForbidden();
        $this->delete($this->url('destroy'))->assertForbidden();
        $this->assertDatabaseCount('movimiento_operativo_correcciones', 0);
    }

    public function test_reservado_no_ofrece_acciones_ni_admite_rutas_forzadas(): void
    {
        $this->movimiento->subrubro->update(['es_reservado_sistema' => true]);
        $this->actingAs($this->admin)->get(route('web.caja.detalle', $this->caja->id))->assertOk()
            ->assertDontSee($this->url('editar'), false)->assertDontSee('action="'.$this->url('destroy').'"', false);
        $this->get($this->url('editar'))->assertForbidden();
        $this->put($this->url('update'), $this->datos())->assertSessionHas('error');
        $this->delete($this->url('destroy'))->assertSessionHasErrors('movimiento');
        $this->assertSame('1000.00', $this->movimiento->fresh()->monto);
        $this->assertDatabaseCount('movimiento_operativo_correcciones', 0);
    }

    public function test_cobro_no_es_editable_aunque_su_subrubro_no_este_reservado(): void
    {
        $deporte = \App\Models\Deporte::create(['nombre' => 'Patín de prueba', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = \App\Models\Nivel::create(['nombre' => 'Inicial de prueba']);
        $grupo = \App\Models\Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $alumno = \App\Models\Alumno::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'dni' => '70000001',
            'fecha_nacimiento' => '2010-01-01', 'celular' => '1140000000', 'activo' => true, 'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id]);
        $this->movimiento->update(['alumno_id' => $alumno->id]);
        $this->actingAs($this->operativa)->get(route('web.caja.detalle', $this->caja->id))->assertOk()
            ->assertDontSee($this->url('editar'), false)->assertDontSee('action="'.$this->url('destroy').'"', false)->assertSee('Cancelar');
        $this->get($this->url('editar'))->assertForbidden();
        $this->put($this->url('update'), $this->datos())->assertSessionHas('error');
        $this->delete($this->url('destroy'))->assertSessionHasErrors('movimiento');
        $this->assertDatabaseCount('movimiento_operativo_correcciones', 0);
    }

    public function test_caja_cerrada_no_se_corrige_y_fallo_de_fondos_no_deja_historia(): void
    {
        $this->actingAs($this->operativa)->put($this->url('update'), $this->datos(10001))->assertSessionHas('error');
        $this->caja->update(['estado' => 'CERRADA']);
        $this->put($this->url('update'), $this->datos())->assertSessionHas('error');
        $this->delete($this->url('destroy'))->assertSessionHasErrors('movimiento');
        $this->assertDatabaseCount('movimiento_operativo_correcciones', 0);
        $this->assertSame('1000.00', $this->movimiento->fresh()->monto);
    }

    public function test_fallo_al_guardar_historia_revierte_la_eliminacion(): void
    {
        MovimientoOperativoCorreccion::creating(fn () => throw new \RuntimeException('Fallo de auditoría de prueba'));
        try {
            app(CajaCorreccionService::class)->corregir($this->caja->id, $this->movimiento->id, $this->admin, null);
            $this->fail('Debía fallar');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo de auditoría de prueba', $e->getMessage());
        } finally {
            MovimientoOperativoCorreccion::flushEventListeners();
        }
        $this->assertNotNull($this->movimiento->fresh());
        $this->assertDatabaseCount('movimiento_operativo_correcciones', 0);
    }

    public function test_historia_conserva_edicion_y_eliminacion_y_exporta_pantallas_reales(): void
    {
        $service = app(CajaCorreccionService::class);
        $service->corregir($this->caja->id, $this->movimiento->id, $this->operativa, $this->datos());
        $service->corregir($this->caja->id, $this->movimiento->id, $this->admin, null);
        $this->assertDatabaseCount('movimiento_operativo_correcciones', 2);
        if (getenv('WINGS_CAPTURAS') === '1') $this->withVite();
        $admin = $this->actingAs($this->admin)->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('Historia de correcciones');
        $op = $this->actingAs($this->operativa)->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('Antes:')->assertSee('Después:');
        if (getenv('WINGS_CAPTURAS') !== '1') return;
        Auth::logout();
        $paginas = ['00-login' => $this->get(route('login'))->assertOk()->getContent(), '01-detalle-admin' => $admin->getContent(), '02-detalle-operativa' => $op->getContent()];
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t51');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        foreach ($paginas as $nombre => $html) {
            $html = preg_replace('~https?://[^/"\s]+/(build/|img/|favicon|apple-touch|site.webmanifest)~', '../../../../public/$1', $html);
            $html = preg_replace('~(<meta name="csrf-token" content=")[^"]*~', '$1', $html);
            $html = preg_replace('~(<input[^>]*name="_token"[^>]*value=")[^"]*~', '$1', $html);
            file_put_contents($dir.'/'.$nombre.'.html', rtrim(preg_replace('/[\t ]+$/m', '', $html)).PHP_EOL);
        }
    }
}
