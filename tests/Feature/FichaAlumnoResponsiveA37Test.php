<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica la resolución del defecto A37:
 * En la ficha del alumno (/alumnos/{id}), la cabecera de acciones, las deudas pendientes,
 * el historial de pagos y el botón Volver deben contener clases responsivas que permitan
 * fluidez en pantallas móviles (<=375px) sin desbordar ni usar justify-content: flex-end inline rígido.
 */
class FichaAlumnoResponsiveA37Test extends TestCase
{
    use RefreshDatabase;

    public function test_ficha_alumno_posee_estructura_responsive_en_acciones_deudas_e_historial(): void
    {
        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-05 10:00:00');

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $tipoCaja = TipoCaja::first() ?? TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $deporte = Deporte::first() ?? Deporte::create([
            'nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true,
        ]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true,
        ]);

        $alumno = Alumno::create([
            'apellido' => 'Gómez', 'nombre' => 'Luz', 'dni' => '31222333',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01', 'celular' => '11-4000-0000',
            'fecha_alta' => '2026-09-01', 'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true,
        ]);
        DeudaCuota::create([
            'alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 48000,
            'saldo_pendiente' => 48000, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
        DeudaCuota::create([
            'alumno_id' => $alumno->id, 'periodo' => '2026-11', 'monto_original' => 48000,
            'saldo_pendiente' => 48000, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Registrar un cobro del dueño para que haya historial con Recibo y Anular
        $this->actingAs($admin)->post(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $tipoCaja->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 48000],
            'fecha_pago' => '2026-10-05',
        ])->assertSessionHas('success');

        $response = $this->actingAs($admin)->get(route('web.alumnos.show', $alumno->id));
        $response->assertOk();

        $html = $response->getContent();

        // 1. Barra de acciones superior: no debe tener justify-content: flex-end inline que ignore móvil
        $this->assertStringNotContainsString('class="filtros-actions mb-4" style="justify-content: flex-end;', $html);
        $this->assertStringContainsString('justify-start sm:justify-end', $html);

        // 2. Botones esenciales presentes
        $this->assertStringContainsString('Cobrar', $html);
        $this->assertStringContainsString('Editar', $html);
        $this->assertStringContainsString('Recibo', $html);
        $this->assertStringContainsString('Anular', $html);
        $this->assertStringContainsString('Volver', $html);

        // 3. Fila de deudas e historial: contenedor responsive con flex-wrap y w-full sm:w-auto en acciones
        $this->assertStringContainsString('flex flex-wrap sm:flex-nowrap justify-between items-center', $html);
        $this->assertStringContainsString('w-full sm:w-auto', $html);

        Carbon::setTestNow();
    }
}
