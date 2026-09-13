<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\User;
use App\Services\LiquidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquidacionComisionHistoricaTest extends TestCase
{
    use RefreshDatabase;

    private LiquidacionService $service;
    private Deporte $deporteA;
    private Deporte $deporteB;
    private Grupo $grupoA;
    private Grupo $grupoB;
    private Profesor $profesor;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LiquidacionService::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $this->deporteA = Deporte::create([
            'nombre' => 'Deporte Comisión A',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION,
            'activo' => true,
        ]);

        $this->deporteB = Deporte::create([
            'nombre' => 'Deporte Otro B',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION,
            'activo' => true,
        ]);

        $nivel = Nivel::create(['nombre' => 'Nivel Único']);

        $this->grupoA = Grupo::create([
            'deporte_id' => $this->deporteA->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->grupoB = Grupo::create([
            'deporte_id' => $this->deporteB->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $this->deporteA->id,
            'nombre' => 'Profesor',
            'apellido' => 'Comisión',
            'dni' => '20123456',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'CABA',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION,
            'porcentaje_comision' => 40.00,
            'activo' => true,
        ]);
    }

    private function crearAlumno(array $overrides = []): Alumno
    {
        return Alumno::create(array_merge([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'dni' => '45123456',
            'fecha_nacimiento' => '2010-01-01',
            'celular' => '11-1234-5678',
            'deporte_id' => $this->deporteA->id,
            'grupo_id' => $this->grupoA->id,
            'fecha_alta' => '2026-01-01',
            'activo' => true,
        ], $overrides));
    }

    private function crearClaseConAsistencia(Alumno $alumno, string $fecha = '2026-09-10'): Clase
    {
        $clase = Clase::create([
            'grupo_id' => $alumno->grupo_id,
            'fecha' => $fecha,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cancelada' => false,
        ]);

        $clase->profesores()->attach($this->profesor->id);

        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);

        return $clase;
    }

    private function crearPago(Alumno $alumno, int $mes = 9, int $anio = 2026, float $monto = 10000.00): Pago
    {
        return Pago::create([
            'alumno_id' => $alumno->id,
            'mes' => $mes,
            'anio' => $anio,
            'monto_base' => $monto,
            'porcentaje_aplicado' => 100.00,
            'monto_final' => $monto,
            'fecha_pago' => '2026-09-05',
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);
    }

    public function test_alumno_dado_de_baja_posteriormente_aparece_en_liquidacion_del_mes_que_pago_y_asistio(): void
    {
        $alumno = $this->crearAlumno();
        $this->crearClaseConAsistencia($alumno, '2026-09-10');
        $this->crearPago($alumno, 9, 2026, 10000.00);

        // En octubre el alumno se da de baja
        $alumno->update(['activo' => false]);

        // En noviembre se liquida septiembre
        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);

        $this->assertSame(40.00, (float) $liquidacion->porcentaje_comision_aplicado);
        $this->assertSame(4000.00, (float) $liquidacion->total_calculado);
        $this->assertCount(1, $liquidacion->detalles);
        $this->assertSame($alumno->id, $liquidacion->detalles->first()->referencia_id);
    }

    public function test_alumno_que_cambia_de_deporte_posteriormente_aparece_en_liquidacion_del_mes_que_pago_y_asistio(): void
    {
        $alumno = $this->crearAlumno();
        $this->crearClaseConAsistencia($alumno, '2026-09-10');
        $this->crearPago($alumno, 9, 2026, 10000.00);

        // En octubre el alumno cambia de deporte y de grupo
        $alumno->update([
            'deporte_id' => $this->deporteB->id,
            'grupo_id' => $this->grupoB->id,
        ]);

        // Se liquida septiembre
        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);

        $this->assertSame(4000.00, (float) $liquidacion->total_calculado);
        $this->assertCount(1, $liquidacion->detalles);
        $this->assertSame($alumno->id, $liquidacion->detalles->first()->referencia_id);
    }

    public function test_cambio_posterior_en_porcentaje_del_profesor_no_altera_liquidacion_recalculada(): void
    {
        $alumno = $this->crearAlumno();
        $this->crearClaseConAsistencia($alumno, '2026-09-10');
        $this->crearPago($alumno, 9, 2026, 10000.00);

        // Se genera la liquidación de septiembre con comisión del 40%
        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $this->assertSame(40.00, (float) $liquidacion->porcentaje_comision_aplicado);
        $this->assertSame(4000.00, (float) $liquidacion->total_calculado);

        // Posteriormente cambia el porcentaje del profesor al 50%
        $this->profesor->update(['porcentaje_comision' => 50.00]);

        // Se recalcula la liquidación abierta
        $recalculada = $this->service->recalcularLiquidacion($liquidacion->id);

        $this->assertSame(40.00, (float) $recalculada->porcentaje_comision_aplicado);
        $this->assertSame(4000.00, (float) $recalculada->total_calculado);
        $this->assertCount(1, $recalculada->detalles);
        $this->assertSame(4000.00, (float) $recalculada->detalles->first()->monto);
    }

    public function test_liquidacion_legacy_con_porcentaje_nulo_calcula_con_porcentaje_actual_del_profesor(): void
    {
        $alumno = $this->crearAlumno();
        $this->crearClaseConAsistencia($alumno, '2026-09-10');
        $this->crearPago($alumno, 9, 2026, 10000.00);

        // Liquidación legacy sin porcentaje congelado (NULL)
        $liquidacion = Liquidacion::create([
            'profesor_id' => $this->profesor->id,
            'mes' => 9,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_COMISION,
            'porcentaje_comision_aplicado' => null,
            'total_calculado' => 0,
            'estado' => Liquidacion::ESTADO_ABIERTA,
        ]);

        $total = $this->service->calcularLiquidacionComision($liquidacion, $this->profesor, 9, 2026);

        $this->assertSame(4000.00, (float) $total);
    }

    public function test_anulacion_de_pago_hace_que_recalculo_remueva_la_comision(): void
    {
        $alumno = $this->crearAlumno();
        $this->crearClaseConAsistencia($alumno, '2026-09-10');
        $pago = $this->crearPago($alumno, 9, 2026, 10000.00);

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $this->assertSame(4000.00, (float) $liquidacion->total_calculado);
        $this->assertCount(1, $liquidacion->detalles);

        // Se anula el pago
        $pago->update(['estado' => Pago::ESTADO_ANULADO]);

        // Al recalcular, el pago anulado no se incluye
        $recalculada = $this->service->recalcularLiquidacion($liquidacion->id);

        $this->assertSame(0.00, (float) $recalculada->total_calculado);
        $this->assertCount(0, $recalculada->detalles);
    }

    public function test_alumno_con_pago_pero_sin_asistencia_a_clase_del_profesor_no_genera_comision(): void
    {
        $alumno = $this->crearAlumno();
        // Alumno tiene pago para septiembre
        $this->crearPago($alumno, 9, 2026, 10000.00);

        // Pero NO tiene asistencia con este profesor (por ejemplo, clase cancelada)
        $clase = Clase::create([
            'grupo_id' => $alumno->grupo_id,
            'fecha' => '2026-09-10',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cancelada' => true,
        ]);
        $clase->profesores()->attach($this->profesor->id);
        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);

        $this->assertSame(0.00, (float) $liquidacion->total_calculado);
        $this->assertCount(0, $liquidacion->detalles);
    }

    public function test_vista_show_muestra_porcentaje_congelado_de_la_liquidacion(): void
    {
        $alumno = $this->crearAlumno();
        $this->crearClaseConAsistencia($alumno, '2026-09-10');
        $this->crearPago($alumno, 9, 2026, 10000.00);

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $this->assertSame(40.00, (float) $liquidacion->porcentaje_comision_aplicado);

        // Se modifica el porcentaje del profesor al 55%
        $this->profesor->update(['porcentaje_comision' => 55.00]);

        $response = $this->actingAs($this->admin)->get(route('web.liquidaciones.show', $liquidacion->id));
        $response->assertOk();
        $response->assertSee('40.0%');
        $response->assertDontSee('55.0%');
    }
}