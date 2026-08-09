<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerarDeudasMensualesCommandTest extends TestCase
{
    use RefreshDatabase;

    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;
    private int $siguienteDni = 10000000;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-01 09:00:00');

        $this->deporte = Deporte::create([
            'nombre' => 'Vóley',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Prueba mensual']);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 30000,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_genera_el_mes_que_empieza_segun_asistencia_o_alta_y_pago_recientes(): void
    {
        $conAsistencia = $this->crearAlumno('Ana', '2026-06-01');
        $soloPagoAnterior = $this->crearAlumno('Beto', '2026-06-01');
        $altaYPagoRecientes = $this->crearAlumno('Carla', '2026-08-25');

        $claseAgosto = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-20',
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);
        Asistencia::create([
            'clase_id' => $claseAgosto->id,
            'alumno_id' => $conAsistencia->id,
            'presente' => true,
        ]);

        $this->crearPago($soloPagoAnterior, '2026-08-25');
        $this->crearPago($altaYPagoRecientes, '2026-08-25');

        $this->artisan('cobranza:generar-deudas')->assertSuccessful();

        foreach ([$conAsistencia, $altaYPagoRecientes] as $alumno) {
            $this->assertDatabaseHas('deuda_cuotas', [
                'alumno_id' => $alumno->id,
                'periodo' => '2026-09',
                'monto_original' => '30000.00',
            ]);
        }

        $this->assertDatabaseMissing('deuda_cuotas', [
            'alumno_id' => $soloPagoAnterior->id,
            'periodo' => '2026-09',
        ]);
        $this->assertDatabaseHas('alumnos_revision_cobranza', [
            'alumno_id' => $soloPagoAnterior->id,
            'periodo_objetivo' => '2026-09',
            'estado_revision' => 'PENDIENTE',
        ]);
        $this->assertDatabaseMissing('deuda_cuotas', ['periodo' => '2026-10']);
    }

    private function crearAlumno(string $nombre, string $fechaAlta): Alumno
    {
        $alumno = Alumno::create([
            'nombre' => $nombre,
            'apellido' => 'Prueba',
            'dni' => (string) $this->siguienteDni++,
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => $fechaAlta,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-06-01',
            'activo' => true,
        ]);

        return $alumno;
    }

    private function crearPago(Alumno $alumno, string $fechaPago): void
    {
        Pago::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'mes' => 8,
            'anio' => 2026,
            'monto_base' => 30000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 30000,
            'fecha_pago' => $fechaPago,
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);
    }
}
