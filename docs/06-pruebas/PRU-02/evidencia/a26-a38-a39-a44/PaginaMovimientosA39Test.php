<?php

namespace Tests\Evidencia;

use App\Models\{CajaOperativa, MovimientoOperativo, Subrubro, TipoCaja, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reproductor de evidencia, fuera de la suite: la pantalla Movimientos vista por un
 * operativo, con movimientos cargados por ella, por un compañero y uno de un rubro que
 * solo ve el admin.
 */
class PaginaMovimientosA39Test extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_la_pagina(): void
    {
        Carbon::setTestNow('2026-10-07 18:00:00');
        $this->seed(CatalogosSeeder::class);
        $sandra = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $marcos = User::factory()->create(['name' => 'Marcos Peña', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $tipo = TipoCaja::first();
        $deOperativo = fn (string $t) => Subrubro::where('permitido_para', 'OPERATIVO')
            ->whereHas('rubro', fn ($q) => $q->where('tipo', $t))->firstOrFail();
        $soloAdmin = Subrubro::where('permitido_para', 'ADMIN')->firstOrFail();

        $cargar = function (User $quien, Subrubro $sub, float $monto, string $hora) use ($tipo) {
            $caja = CajaOperativa::firstOrCreate(
                ['usuario_operativo_id' => $quien->id, 'estado' => CajaOperativa::ESTADO_CERRADA],
                ['apertura_at' => '2026-10-07 08:00:00', 'cierre_at' => '2026-10-07 17:00:00']
            );
            MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'fecha' => '2026-10-07', 'tipo_caja_id' => $tipo->id,
                'subrubro_id' => $sub->id, 'monto' => $monto, 'usuario_id' => $quien->id,
                'estado' => MovimientoOperativo::ESTADO_ACTIVO, 'created_at' => "2026-10-07 $hora"]);
        };
        $cargar($marcos, $deOperativo('INGRESO'), 48000, '09:10:00');
        $cargar($marcos, $deOperativo('EGRESO'), 6000, '10:30:00');
        $cargar($sandra, $deOperativo('INGRESO'), 52000, '15:20:00');
        $cargar($sandra, $soloAdmin, 99000, '16:00:00');

        $html = $this->actingAs($sandra)->get(route('web.movimientos.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('99.000', $html);

        @mkdir(public_path('_capturas-a26-a39'), 0777, true);
        file_put_contents(public_path('_capturas-a26-a39/a39-movimientos.html'), $html);
        Carbon::setTestNow();
    }
}
