<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que el operativo ve en /movimientos lo decide el RUBRO, nunca quién lo cargó.
 *
 * Está escrito en PERMISOS-ROLES.md y se había implementado al revés: la pantalla
 * filtraba por caja propia. El costo es concreto y diario: mañana viene la madre que
 * pagó en el turno del otro operativo, y el que está en el mostrador no encuentra el
 * cobro. El rol define el dominio de trabajo, no la propiedad de los registros.
 *
 * Lo que sí queda afuera es lo que el operativo no puede cargar: los subrubros de
 * ADMIN. Ahí visibilidad y permiso de carga son la misma regla.
 */
class MovimientosVisibilidadPorRubroTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_operativo_ve_el_cobro_que_registro_otro_y_no_ve_lo_que_no_puede_cargar(): void
    {
        $sandra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Sandra']);
        $pablo  = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Pablo']);
        $admin  = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $ingresos = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        $egresos  = Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO', 'observacion' => '']);

        $deMostrador = Subrubro::create([
            'rubro_id' => $ingresos->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
        ]);
        $soloDelAdmin = Subrubro::create([
            'rubro_id' => $egresos->id,
            'nombre' => 'Sueldo profesora',
            'permitido_para' => User::ROL_ADMIN,
            'afecta_caja' => true,
        ]);

        $cajaDePablo = CajaOperativa::create([
            'usuario_operativo_id' => $pablo->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);

        // El cobro que hizo Pablo ayer: Sandra tiene que poder encontrarlo.
        MovimientoOperativo::create([
            'caja_operativa_id' => $cajaDePablo->id,
            'fecha' => today(),
            'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $deMostrador->id,
            'monto' => 31500,
            'usuario_id' => $pablo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Un egreso de un subrubro que el operativo no puede cargar.
        MovimientoOperativo::create([
            'caja_operativa_id' => $cajaDePablo->id,
            'fecha' => today(),
            'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $soloDelAdmin->id,
            'monto' => 87000,
            'usuario_id' => $admin->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        $respuesta = $this->actingAs($sandra)->get(route('web.movimientos.index'));

        $respuesta->assertOk();
        $respuesta->assertSee('31.500', false);
        $respuesta->assertDontSee('87.000', false);
    }

    public function test_el_admin_sigue_viendo_todo(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO', 'observacion' => '']);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Sueldo profesora',
            'permitido_para' => User::ROL_ADMIN,
            'afecta_caja' => true,
        ]);
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'apertura_at' => now(),
            'estado' => CajaOperativa::ESTADO_ABIERTA,
        ]);
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => today(),
            'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $subrubro->id,
            'monto' => 87000,
            'usuario_id' => $admin->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        $this->actingAs($admin)
            ->get(route('web.movimientos.index'))
            ->assertOk()
            ->assertSee('87.000', false);
    }
}
