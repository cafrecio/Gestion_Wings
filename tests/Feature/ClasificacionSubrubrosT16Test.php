<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\ReporteMensualService;
use App\Support\ClasificacionSubrubros;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T16, hallado en el día 1 de PRU-04: con $164.000 cobrados, Inicio mostraba ingresos $0.
 * «Cuota Mensual» no tenía clasificación y ninguna pantalla permitía ponérsela.
 */
class ClasificacionSubrubrosT16Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
    }

    public function test_el_catalogo_de_una_instalacion_nueva_no_deja_ningun_subrubro_sin_clasificar(): void
    {
        $this->assertSame([], Subrubro::whereNull('clasificacion_resultado')->pluck('nombre')->all());
        $this->assertSame('APORTE', Subrubro::where('nombre', 'Patines')->value('clasificacion_resultado'));
        $this->assertSame('RETIRO', Subrubro::where('nombre', 'Retiro de dueños')->value('clasificacion_resultado'));
        $this->assertSame('EGRESO', Subrubro::where('nombre', 'Pago al organizador')->first()->rubro->tipo);
    }

    public function test_una_instalacion_existente_queda_clasificada_y_lo_ya_cobrado_empieza_a_contar(): void
    {
        // Así quedó el sitio de prueba después de la migración del 09/10.
        DB::table('subrubros')->whereNotIn('nombre', ['Retiro de dueños', 'Pago al organizador'])->update(['clasificacion_resultado' => null]);
        DB::table('subrubros')->whereIn('nombre', ['Retiro de dueños', 'Pago al organizador'])->delete();
        $propio = Subrubro::create(['rubro_id' => Rubro::where('nombre', 'Servicios')->value('id'), 'nombre' => 'Gas',
            'permitido_para' => 'ADMIN', 'afecta_caja' => false, 'activo' => true]);
        $cuota = Subrubro::where('nombre', 'Cuota Mensual')->firstOrFail();
        $movimiento = CashflowMovimiento::create(['fecha' => today(), 'subrubro_id' => $cuota->id, 'tipo_caja_id' => TipoCaja::firstOrFail()->id,
            'monto' => 48000, 'usuario_admin_id' => $this->admin->id]);
        $this->assertNull($movimiento->fresh()->reporte_clasificacion);
        $this->assertSame(0, app(ReporteMensualService::class)->obtener(today()->format('Y-m'))['ingresos']);

        ClasificacionSubrubros::aplicarCatalogo();

        $this->assertSame('NEGOCIO', $cuota->fresh()->clasificacion_resultado);
        $this->assertSame('APORTE', Subrubro::where('nombre', 'VG Indumentaria')->value('clasificacion_resultado'));
        $this->assertSame('RETIRO', Subrubro::where('nombre', 'Retiro de dueños')->value('clasificacion_resultado'));
        $this->assertSame('NEGOCIO', $movimiento->fresh()->reporte_clasificacion);
        $reporte = app(ReporteMensualService::class)->obtener(today()->format('Y-m'));
        $this->assertSame(4800000, $reporte['ingresos']);
        $this->assertSame(0, $reporte['sin_clasificar']);
        // Lo que Carlos no clasificó no se adivina: queda para el admin.
        $this->assertNull($propio->fresh()->clasificacion_resultado);

        // Correrlo otra vez no duplica ni pisa lo que el admin haya cambiado.
        $cuota->update(['clasificacion_resultado' => 'APORTE']);
        ClasificacionSubrubros::aplicarCatalogo();
        $this->assertSame('APORTE', $cuota->fresh()->clasificacion_resultado);
        $this->assertSame(1, Subrubro::where('nombre', 'Retiro de dueños')->count());
    }

    public function test_el_formulario_pide_la_clasificacion_y_un_subrubro_nuevo_nace_del_club(): void
    {
        $servicios = Rubro::where('nombre', 'Servicios')->firstOrFail();
        $datos = ['nombre' => 'Gas', 'permitido_para' => 'ADMIN', 'afecta_caja' => 0];

        $this->actingAs($this->admin)->get(route('web.subrubros.create', $servicios->id))
            ->assertOk()->assertSee('Qué es')->assertSee('Del club')->assertSee('Retiro de los dueños')->assertDontSee('Aporte de los dueños');

        $this->actingAs($this->admin)->post(route('web.subrubros.store', $servicios->id), $datos)
            ->assertSessionHasErrors('clasificacion_resultado');
        // Un aporte no puede salir: en un rubro de egreso no se acepta.
        $this->actingAs($this->admin)->post(route('web.subrubros.store', $servicios->id), $datos + ['clasificacion_resultado' => 'APORTE'])
            ->assertSessionHasErrors('clasificacion_resultado');
        $this->assertSame(0, Subrubro::where('nombre', 'Gas')->count());

        $this->actingAs($this->admin)->post(route('web.subrubros.store', $servicios->id), $datos + ['clasificacion_resultado' => 'NEGOCIO'])
            ->assertSessionHasNoErrors();
        $this->assertSame('NEGOCIO', Subrubro::where('nombre', 'Gas')->value('clasificacion_resultado'));
    }

    public function test_clasificar_un_subrubro_viejo_desde_el_formulario_hace_contar_sus_movimientos(): void
    {
        $luz = Subrubro::where('nombre', 'Luz')->firstOrFail();
        DB::table('subrubros')->where('id', $luz->id)->update(['clasificacion_resultado' => null]);
        $movimiento = CashflowMovimiento::create(['fecha' => today(), 'subrubro_id' => $luz->id, 'tipo_caja_id' => TipoCaja::firstOrFail()->id,
            'monto' => -30000, 'usuario_admin_id' => $this->admin->id]);
        $this->assertNull($movimiento->fresh()->reporte_clasificacion);

        $this->actingAs($this->admin)->get(route('web.rubros.index'))->assertOk()->assertSee('(sin clasificar)');
        $this->actingAs($this->admin)->get(route('web.subrubros.edit', [$luz->rubro_id, $luz->id]))->assertOk()->assertSee('Elegí una opción');

        $this->actingAs($this->admin)->put(route('web.subrubros.update', [$luz->rubro_id, $luz->id]),
            ['nombre' => 'Luz', 'permitido_para' => 'ADMIN', 'afecta_caja' => 0, 'clasificacion_resultado' => 'NEGOCIO'])->assertSessionHasNoErrors();

        $this->assertSame('NEGOCIO', $movimiento->fresh()->reporte_clasificacion);
        $this->actingAs($this->admin)->get(route('web.rubros.index'))->assertOk()->assertDontSee('(sin clasificar)');
    }
}
