<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\ReglaPrimerPago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cambio de plan combinado con el descuento de primer pago.
 *
 * Cada caso hace lo mismo que la operativa en el mostrador: abre la pantalla, lee el
 * importe que se le anuncia para el plan elegido, cobra exactamente ese importe, y el
 * mes tiene que quedar pago a ese importe. Si lo anunciado y lo registrado difieren,
 * la operativa le pidio a la familia una cifra y el sistema guardo otra.
 *
 * Existe porque el script del cambio de plan calculaba su propio numero —precio nuevo
 * menos lo pagado— sin saber del descuento ni de la regla que difiere una bajada al mes
 * siguiente. Subiendo de 40.000 a 60.000 en el mes de alta con 70%, anunciaba 60.000 y
 * se registraban 42.000.
 */
class CambioPlanConDescuentoMatrizTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private TipoCaja $tipoCaja;
    private Grupo $grupo;
    private GrupoPlan $planBajo;
    private GrupoPlan $planMedio;
    private GrupoPlan $planAlto;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-25 10:00:00');

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);
        $this->tipoCaja = TipoCaja::create(['nombre' => 'Caja General', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $this->planBajo = $this->crearPlan(2, 20000);
        $this->planMedio = $this->crearPlan(4, 40000);
        $this->planAlto = $this->crearPlan(6, 60000);

        ReglaPrimerPago::create(['nombre' => 'Primera quincena', 'dia_desde' => 1,  'dia_hasta' => 15, 'porcentaje' => 100, 'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Segunda quincena', 'dia_desde' => 16, 'dia_hasta' => 23, 'porcentaje' => 70,  'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Fin de mes',       'dia_desde' => 24, 'dia_hasta' => 31, 'porcentaje' => 40,  'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_subida_en_el_mes_de_alta_con_descuento(): void
    {
        $alumno = $this->alumnoNuevoDel20();

        $this->cobrarLoAnunciado($alumno, $this->planAlto, 42000.0);
    }

    public function test_subida_sin_descuento(): void
    {
        $alumno = $this->alumnoConAntiguedad();

        $this->cobrarLoAnunciado($alumno, $this->planAlto, 60000.0);
    }

    public function test_bajada_sin_asistencia_en_el_mes_de_alta_con_descuento(): void
    {
        $alumno = $this->alumnoNuevoDel20();

        $this->cobrarLoAnunciado($alumno, $this->planBajo, 14000.0);
    }

    public function test_bajada_con_asistencia_se_difiere_y_el_mes_conserva_el_precio_con_descuento(): void
    {
        $alumno = $this->alumnoNuevoDel20();
        $this->registrarAsistencia($alumno);

        // La bajada rige el mes que viene: agosto se sigue cobrando con el plan actual.
        $this->cobrarLoAnunciado($alumno, $this->planBajo, 28000.0);
    }

    public function test_bajada_con_asistencia_sin_descuento_conserva_el_precio_del_plan_actual(): void
    {
        $alumno = $this->alumnoConAntiguedad();
        $this->registrarAsistencia($alumno);

        $this->cobrarLoAnunciado($alumno, $this->planBajo, 40000.0);
    }

    public function test_sin_cambio_de_plan_con_descuento(): void
    {
        $alumno = $this->alumnoNuevoDel20();

        $this->cobrarLoAnunciado($alumno, $this->planMedio, 28000.0);
    }

    /**
     * La suite corre sin JavaScript, asi que esto verifica la linea y no el
     * comportamiento: el script del cambio de plan tiene que leer el precio que calcula
     * el servidor, no rehacer la cuenta con el precio de lista. El comportamiento lo
     * cubren los casos de arriba, que usan ese mismo numero para cobrar.
     */
    public function test_el_script_del_cambio_de_plan_lee_el_precio_que_calcula_el_servidor(): void
    {
        $alumno = $this->alumnoNuevoDel20();

        $html = $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $alumno->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('parseFloat(this.dataset.precioMes)', $html);
        $this->assertStringNotContainsString('parseInt(this.dataset.precio, 10)', $html);
    }

    /**
     * Lee lo que la pantalla anuncia para el plan, lo cobra tal cual, y exige que el mes
     * quede pago exactamente a ese importe.
     */
    private function cobrarLoAnunciado(Alumno $alumno, GrupoPlan $plan, float $esperado): void
    {
        $anunciado = $this->precioAnunciado($alumno, $plan);

        $this->assertSame(
            $esperado,
            $anunciado,
            'La pantalla anuncia un importe distinto del que corresponde al mes con este plan.'
        );

        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => ['2026-08'],
                'montos_cuota' => ['2026-08' => $anunciado],
                'nuevo_plan_id' => $plan->id,
                'fecha_pago' => '2026-08-25',
            ])
            ->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('success');

        $deuda = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-08')->firstOrFail();

        $this->assertSame($anunciado, (float) $deuda->monto_pagado, 'Lo cobrado no es lo que se anuncio.');
        $this->assertSame($anunciado, (float) $deuda->monto_original, 'El mes vale otra cosa que lo anunciado.');
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deuda->estado, 'Pagar lo anunciado tiene que cerrar el mes.');
    }

    private function precioAnunciado(Alumno $alumno, GrupoPlan $plan): float
    {
        $html = $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $alumno->id))
            ->assertOk()
            ->getContent();

        $encontrado = preg_match(
            '/name="nuevo_plan_id"\s+value="'.$plan->id.'"[^>]*?data-precio-mes="([0-9.]+)"/s',
            $html,
            $coincidencia
        );

        $this->assertSame(1, $encontrado, 'La opcion del plan no informa cuanto cuesta el mes con ese plan.');

        return (float) $coincidencia[1];
    }

    /** Alumno nuevo del 20/08: su mes de alta lleva 70% y todavia no tiene deuda generada. */
    private function alumnoNuevoDel20(): Alumno
    {
        return $this->crearAlumno('2026-08-20', false);
    }

    /** Alumno de enero, con agosto ya generado por la corrida mensual: sin descuento. */
    private function alumnoConAntiguedad(): Alumno
    {
        return $this->crearAlumno('2026-01-05', true);
    }

    private function crearAlumno(string $fechaAlta, bool $conDeudaDeAgosto): Alumno
    {
        $alumno = Alumno::create([
            'nombre' => 'Plan',
            'apellido' => 'Descuento '.$fechaAlta,
            'dni' => (string) random_int(20000000, 49999999),
            'fecha_nacimiento' => '2010-01-01',
            'celular' => '1111111111',
            'deporte_id' => $this->grupo->deporte_id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => $fechaAlta,
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->planMedio->id,
            'fecha_desde' => $fechaAlta,
            'activo' => true,
        ]);

        if ($conDeudaDeAgosto) {
            DeudaCuota::create([
                'alumno_id' => $alumno->id,
                'periodo' => '2026-08',
                'monto_original' => 40000,
                'monto_pagado' => 0,
                'estado' => DeudaCuota::ESTADO_PENDIENTE,
            ]);
        }

        return $alumno;
    }

    private function registrarAsistencia(Alumno $alumno): void
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-22',
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);
        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);
    }

    private function crearPlan(int $clasesPorSemana, float $precio): GrupoPlan
    {
        return GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => $clasesPorSemana,
            'precio_mensual' => $precio,
            'activo' => true,
        ]);
    }
}
