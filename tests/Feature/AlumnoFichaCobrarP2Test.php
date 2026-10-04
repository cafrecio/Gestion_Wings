<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumnoFichaCobrarP2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $deporte = Deporte::firstOrCreate(['nombre' => 'Patín'], ['activo' => true]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 38000,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Martina',
            'apellido' => 'Sosa',
            'dni' => '39123456',
            'fecha_nacimiento' => '2004-03-15',
            'fecha_alta' => '2026-08-01',
            'celular' => '1145678901',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);

        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => now()->subMonth()->format('Y-m'),
            'monto_original' => 38000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    public function test_ficha_alumno_tiene_boton_cobrar_para_admin_y_operativo(): void
    {
        // Como Admin
        $resAdmin = $this->actingAs($this->admin)->get(route('web.alumnos.show', $this->alumno->id));
        $resAdmin->assertOk();
        $resAdmin->assertSee(route('web.caja.cobrar', $this->alumno->id));
        $resAdmin->assertSee('Cobrar');

        // Como Operativo
        $resOp = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $this->alumno->id));
        $resOp->assertOk();
        $resOp->assertSee(route('web.caja.cobrar', $this->alumno->id));
        $resOp->assertSee('Cobrar');
    }

    public function test_ficha_alumno_muestra_enlace_a_recibo_en_historial_de_pagos(): void
    {
        $tipoCaja = TipoCaja::first();
        $subrubro = Subrubro::first();

        $pago = Pago::create([
            'alumno_id' => $this->alumno->id,
            'tipo_caja_id' => $tipoCaja->id,
            'monto_base' => 38000,
            'monto_total' => 38000,
            'monto_final' => 38000,
            'porcentaje_aplicado' => 100,
            'fecha_pago' => now()->subDays(5),
            'estado' => Pago::ESTADO_COMPLETADO,
            'mes' => (int) now()->subMonth()->format('m'),
            'anio' => (int) now()->subMonth()->format('Y'),
        ]);

        $res = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $this->alumno->id));
        $res->assertOk();

        // Debe ver "Recibo" con link al recibo del pago
        $res->assertSee(route('web.recibos.cuota', $pago->id));
        $res->assertSee('Recibo');
    }
}
