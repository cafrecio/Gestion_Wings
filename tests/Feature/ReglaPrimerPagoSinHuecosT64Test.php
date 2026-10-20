<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\ReglaPrimerPago;
use App\Models\User;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T64 — Las reglas de primer pago no pueden superponerse ni dejar días sin cubrir.
 * Decisión de Carlos: DU-45 («Impedir reglas superpuestas y días sin cubrir»). Caso: CFG-004.
 */
class ReglaPrimerPagoSinHuecosT64Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'rol'    => User::ROL_ADMIN,
            'activo' => true,
        ]);
    }

    public function test_hueco_al_crear_es_rechazado(): void
    {
        ReglaPrimerPago::create([
            'nombre'     => 'Primera quincena',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        // Se intenta crear una regla de 18 a 31 (faltan días 16 y 17)
        $respuesta = $this->actingAs($this->admin)->postJson('/configuraciones/primer-pago', [
            'nombre'     => 'Fin de mes con hueco',
            'dia_desde'  => 18,
            'dia_hasta'  => 31,
            'porcentaje' => 50,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJson([
            'error' => 'Los días 16 a 17 quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.',
        ]);

        $this->assertSame(1, ReglaPrimerPago::count(), 'La regla no debe guardarse si deja días sin cubrir.');
    }

    public function test_hueco_al_editar_es_rechazado(): void
    {
        $regla1 = ReglaPrimerPago::create([
            'nombre'     => 'Primera quincena',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        $regla2 = ReglaPrimerPago::create([
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 31,
            'porcentaje' => 50,
            'activo'     => true,
        ]);

        // Se intenta achicar la regla 1 de 1 a 10 (dejando del 11 al 15 sin cubrir)
        $respuesta = $this->actingAs($this->admin)->putJson("/configuraciones/primer-pago/{$regla1->id}", [
            'nombre'     => 'Primera decena',
            'dia_desde'  => 1,
            'dia_hasta'  => 10,
            'porcentaje' => 100,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJson([
            'error' => 'Los días 11 a 15 quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.',
        ]);

        $this->assertSame(15, $regla1->fresh()->dia_hasta, 'No debe persistirse un cambio parcial que deje huecos.');
    }

    public function test_hueco_al_borrar_es_rechazado(): void
    {
        $regla1 = ReglaPrimerPago::create([
            'nombre'     => 'Primera quincena',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        $regla2 = ReglaPrimerPago::create([
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 31,
            'porcentaje' => 50,
            'activo'     => true,
        ]);

        // Se intenta borrar la regla 2 dejando del 16 al 31 sin cubrir
        $respuesta = $this->actingAs($this->admin)->deleteJson("/configuraciones/primer-pago/{$regla2->id}");

        $respuesta->assertStatus(422);
        $respuesta->assertJson([
            'error' => 'Los días 16 a 31 quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.',
        ]);

        $this->assertNotNull(ReglaPrimerPago::find($regla2->id), 'La regla no debe borrarse si deja huecos.');
    }

    public function test_borrar_la_unica_regla_es_rechazado(): void
    {
        $regla = ReglaPrimerPago::create([
            'nombre'     => 'Mes completo',
            'dia_desde'  => 1,
            'dia_hasta'  => 31,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        $respuesta = $this->actingAs($this->admin)->deleteJson("/configuraciones/primer-pago/{$regla->id}");

        $respuesta->assertStatus(422);
        $respuesta->assertJson([
            'error' => 'Debe existir al menos una regla que cubra del día 1 al 31.',
        ]);

        $this->assertSame(1, ReglaPrimerPago::count());
    }

    public function test_superposicion_es_rechazada(): void
    {
        ReglaPrimerPago::create([
            'nombre'     => 'Primera quincena',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        ReglaPrimerPago::create([
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 31,
            'porcentaje' => 50,
            'activo'     => true,
        ]);

        // Intento individual superpuesto
        $respuesta = $this->actingAs($this->admin)->postJson('/configuraciones/primer-pago', [
            'nombre'     => 'Pisa del 10 al 20',
            'dia_desde'  => 10,
            'dia_hasta'  => 20,
            'porcentaje' => 70,
        ]);

        $respuesta->assertStatus(422);
        $this->assertStringContainsString('se pisan con la regla', $respuesta->json('error'));

        // Intento en lote superpuesto
        $respuestaLote = $this->actingAs($this->admin)->putJson('/configuraciones/primer-pago', [
            'reglas' => [
                ['id' => null, 'nombre' => 'Tramo A', 'dia_desde' => 1, 'dia_hasta' => 15, 'porcentaje' => 100],
                ['id' => null, 'nombre' => 'Tramo B', 'dia_desde' => 12, 'dia_hasta' => 31, 'porcentaje' => 50],
            ],
        ]);

        $respuestaLote->assertStatus(422);
        $respuestaLote->assertJson([
            'error' => 'Los días 12 a 15 se pisan con la regla "Tramo A", que va del 1 al 15. Un mismo día no puede tener dos porcentajes.',
        ]);
    }

    public function test_cambio_de_tramos_1_15_y_16_31_a_1_10_y_11_31_en_lote_pasa(): void
    {
        $regla1 = ReglaPrimerPago::create([
            'nombre'     => 'Primera quincena',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        $regla2 = ReglaPrimerPago::create([
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 31,
            'porcentaje' => 50,
            'activo'     => true,
        ]);

        $respuesta = $this->actingAs($this->admin)->putJson('/configuraciones/primer-pago', [
            'reglas' => [
                [
                    'id'         => $regla1->id,
                    'nombre'     => 'Primeros diez días',
                    'dia_desde'  => 1,
                    'dia_hasta'  => 10,
                    'porcentaje' => 100,
                ],
                [
                    'id'         => $regla2->id,
                    'nombre'     => 'Resto del mes',
                    'dia_desde'  => 11,
                    'dia_hasta'  => 31,
                    'porcentaje' => 60,
                ],
            ],
        ]);

        $respuesta->assertOk();

        $this->assertSame(10, $regla1->fresh()->dia_hasta);
        $this->assertSame(11, $regla2->fresh()->dia_desde);
        $this->assertSame(31, $regla2->fresh()->dia_hasta);
        $this->assertEquals(60, $regla2->fresh()->porcentaje);
        $this->assertSame(2, ReglaPrimerPago::count());
    }

    public function test_lote_invalido_hace_rollback_y_no_guarda_nada(): void
    {
        $regla1 = ReglaPrimerPago::create([
            'nombre'     => 'Primera quincena',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        $regla2 = ReglaPrimerPago::create([
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 31,
            'porcentaje' => 50,
            'activo'     => true,
        ]);

        // El lote deja sin cubrir el día 11 (1 a 10 y 12 a 31)
        $respuesta = $this->actingAs($this->admin)->putJson('/configuraciones/primer-pago', [
            'reglas' => [
                [
                    'id'         => $regla1->id,
                    'nombre'     => 'Primeros diez días',
                    'dia_desde'  => 1,
                    'dia_hasta'  => 10,
                    'porcentaje' => 100,
                ],
                [
                    'id'         => $regla2->id,
                    'nombre'     => 'Resto del mes con hueco',
                    'dia_desde'  => 12,
                    'dia_hasta'  => 31,
                    'porcentaje' => 50,
                ],
            ],
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJson([
            'error' => 'El día 11 queda sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.',
        ]);

        // Ambos conservan sus valores originales intactos
        $this->assertSame(15, $regla1->fresh()->dia_hasta);
        $this->assertSame(16, $regla2->fresh()->dia_desde);
    }

    public function test_primera_cuota_del_alta_toma_la_regla_del_dia(): void
    {
        ReglaPrimerPago::query()->delete();

        ReglaPrimerPago::create(['nombre' => 'Días 1 a 10', 'dia_desde' => 1, 'dia_hasta' => 10, 'porcentaje' => 100, 'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Días 11 a 20', 'dia_desde' => 11, 'dia_hasta' => 20, 'porcentaje' => 70, 'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Días 21 a 31', 'dia_desde' => 21, 'dia_hasta' => 31, 'porcentaje' => 40, 'activo' => true]);

        $deporte = Deporte::create(['nombre' => 'Tenis', 'activo' => true]);
        $nivel   = Nivel::create(['nombre' => 'Inicial']);
        $grupo   = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan    = GrupoPlan::create([
            'grupo_id'          => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual'    => 28000,
            'activo'            => true,
        ]);

        $pagoCuotaService = app(PagoCuotaService::class);

        $crearAlumno = function (string $dni, Carbon $fechaAlta) use ($deporte, $grupo, $plan) {
            $alumno = Alumno::create([
                'nombre'           => 'Alumno',
                'apellido'         => 'Prueba ' . $dni,
                'dni'              => $dni,
                'fecha_nacimiento' => '2000-01-01',
                'celular'          => '1111111111',
                'deporte_id'       => $deporte->id,
                'grupo_id'         => $grupo->id,
                'fecha_alta'       => $fechaAlta,
                'activo'           => true,
            ]);
            AlumnoPlan::create([
                'alumno_id'   => $alumno->id,
                'plan_id'     => $plan->id,
                'fecha_desde' => $fechaAlta->copy()->startOfMonth(),
                'activo'      => true,
            ]);
            return $alumno;
        };

        // Alumno 1: alta el día 5 -> 100%
        $alumno1 = $crearAlumno('64000001', Carbon::create(2026, 10, 5));
        [$pct1] = $pagoCuotaService->calcularReglaPrimerPago($alumno1->id, [['periodo' => '2026-10', 'monto' => 28000]]);
        $this->assertEquals(100.0, $pct1, 'El día 5 debe tomar la regla del 100%');

        // Alumno 2: alta el día 15 -> 70%
        $alumno2 = $crearAlumno('64000002', Carbon::create(2026, 10, 15));
        [$pct2] = $pagoCuotaService->calcularReglaPrimerPago($alumno2->id, [['periodo' => '2026-10', 'monto' => 28000]]);
        $this->assertEquals(70.0, $pct2, 'El día 15 debe tomar la regla del 70%');

        // Alumno 3: alta el día 25 -> 40%
        $alumno3 = $crearAlumno('64000003', Carbon::create(2026, 10, 25));
        [$pct3] = $pagoCuotaService->calcularReglaPrimerPago($alumno3->id, [['periodo' => '2026-10', 'monto' => 28000]]);
        $this->assertEquals(40.0, $pct3, 'El día 25 debe tomar la regla del 40%');
    }

    public function test_lote_con_id_repetido_es_rechazado(): void
    {
        $regla1 = ReglaPrimerPago::create([
            'nombre'     => 'Primera quincena',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        $respuesta = $this->actingAs($this->admin)->putJson('/configuraciones/primer-pago', [
            'reglas' => [
                [
                    'id'         => $regla1->id,
                    'nombre'     => 'Tramo 1',
                    'dia_desde'  => 1,
                    'dia_hasta'  => 15,
                    'porcentaje' => 100,
                ],
                [
                    'id'         => $regla1->id,
                    'nombre'     => 'Tramo 2',
                    'dia_desde'  => 16,
                    'dia_hasta'  => 31,
                    'porcentaje' => 50,
                ],
            ],
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors(['reglas.0.id']);
        $this->assertSame(
            'Cada regla debe tener un identificador único; no se puede repetir una misma regla.',
            $respuesta->json('errors')['reglas.0.id'][0]
        );
    }

    public function test_lote_con_id_inexistente_es_rechazado_con_mensaje_en_castellano(): void
    {
        $respuesta = $this->actingAs($this->admin)->putJson('/configuraciones/primer-pago', [
            'reglas' => [
                [
                    'id'         => 999999,
                    'nombre'     => 'Tramo único',
                    'dia_desde'  => 1,
                    'dia_hasta'  => 31,
                    'porcentaje' => 100,
                ],
            ],
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors(['reglas.0.id']);
        $this->assertSame(
            'Una de las reglas enviadas no existe o ya no está disponible.',
            $respuesta->json('errors')['reglas.0.id'][0]
        );
    }

    public function test_lote_conserva_reglas_inactivas_existentes(): void
    {
        $reglaActiva = ReglaPrimerPago::create([
            'nombre'     => 'Mes completo activo',
            'dia_desde'  => 1,
            'dia_hasta'  => 31,
            'porcentaje' => 100,
            'activo'     => true,
        ]);

        $reglaInactiva = ReglaPrimerPago::create([
            'nombre'     => 'Regla histórica desactivada',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 80,
            'activo'     => false,
        ]);

        $respuesta = $this->actingAs($this->admin)->putJson('/configuraciones/primer-pago', [
            'reglas' => [
                [
                    'id'         => $reglaActiva->id,
                    'nombre'     => 'Mes completo actualizado',
                    'dia_desde'  => 1,
                    'dia_hasta'  => 31,
                    'porcentaje' => 100,
                ],
            ],
        ]);

        $respuesta->assertOk();

        $this->assertNotNull(ReglaPrimerPago::find($reglaInactiva->id), 'Las reglas inactivas históricas deben conservarse.');
        $this->assertFalse($reglaInactiva->fresh()->activo);
    }
}
