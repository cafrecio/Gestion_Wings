<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
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
use App\Services\PagoCuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * El descuento de primer pago existe por un motivo concreto: quien se anota el 24 de
 * abril no usó abril entero, así que no paga abril entero.
 *
 * De ahí se sigue que **solo alcanza al mes en que el alumno entró**. Antes no se
 * miraba a qué mes correspondía la cuota: bastaba con que el alumno no tuviera pagos
 * registrados, y eso rompía en la carga inicial de un club que ya venía funcionando.
 * Sus alumnos entran con fecha de alta vieja y sin pagos en Wings, porque lo que
 * pagaron lo pagaron antes de que Wings existiera.
 *
 * Medido sobre los diez alumnos de la primera carga: cuatro se habían anotado después
 * del día 15 y se les descontaba $81.300 entre los cuatro, en su primer cobro.
 */
class DescuentoPrimerPagoSoloDelMesDeAltaTest extends TestCase
{
    use RefreshDatabase;

    private const PRECIO = 40000.0;

    private User $admin;
    private GrupoPlan $plan;
    private TipoCaja $tipoCaja;

    protected function setUp(): void
    {
        parent::setUp();

        // Hoy es el día 20, para que el tramo del día de hoy sea el del 70%.
        Carbon::setTestNow(Carbon::create(2026, 9, 20, 10, 0, 0));

        $this->admin = User::factory()->create([
            'rol'    => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO']);
        Subrubro::create([
            'rubro_id'             => $rubro->id,
            'nombre'               => 'Cuota Mensual',
            'permitido_para'       => 'OPERATIVO',
            'afecta_caja'          => true,
            'es_reservado_sistema' => true,
            'activo'               => true,
        ]);

        $this->tipoCaja = TipoCaja::create([
            'nombre'  => 'Efectivo',
            'activo'  => true,
        ]);

        $deporte = Deporte::create([
            'nombre'           => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo'           => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Avanzadas']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id'   => $nivel->id,
            'activo'     => true,
        ]);
        $this->plan = GrupoPlan::create([
            'grupo_id'          => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual'    => self::PRECIO,
            'activo'            => true,
        ]);

        // Los tres tramos reales del club.
        ReglaPrimerPago::create(['nombre' => 'Primera quincena', 'dia_desde' => 1,  'dia_hasta' => 15, 'porcentaje' => 100, 'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Segunda quincena', 'dia_desde' => 16, 'dia_hasta' => 23, 'porcentaje' => 70,  'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Fin de mes',       'dia_desde' => 24, 'dia_hasta' => 31, 'porcentaje' => 40,  'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_alumno_que_entra_este_mes_despues_del_dia_15_paga_menos(): void
    {
        // Se dio de alta el 20 de septiembre de 2026, el mes que se le cobra.
        $alumno = $this->alumnoConAlta('2026-09-20');

        $montoCobrado = $this->cobrar($alumno, '2026-09');

        $this->assertSame(
            28000.0,
            $montoCobrado,
            'Se anotó el día 20, así que le corresponde el tramo del 70%: 28.000 de 40.000. '.
            'Este es el caso para el que el descuento existe.'
        );
    }

    public function test_el_alumno_traido_de_una_carga_inicial_paga_la_cuota_entera(): void
    {
        // Alta de abril: el club ya venía funcionando cuando se cargó el sistema.
        // El día 25 caería en el tramo del 40% si no se mirara el mes.
        $alumno = $this->alumnoConAlta('2026-04-25');

        $montoCobrado = $this->cobrar($alumno, '2026-09');

        $this->assertSame(
            self::PRECIO,
            $montoCobrado,
            'Se anotó en abril y se le está cobrando septiembre: no hay ninguna razón para '.
            'descontarle nada. Antes se le cobraba 16.000 en lugar de 40.000, solo porque '.
            'no tenía pagos registrados en Wings.'
        );
    }

    public function test_en_un_pago_de_varios_meses_el_descuento_alcanza_solo_al_mes_de_entrada(): void
    {
        // Entra el 20 de agosto y en septiembre paga los dos meses juntos.
        $alumno = $this->alumnoConAlta('2026-08-20');

        $montoCobrado = $this->cobrar($alumno, '2026-08', '2026-09');

        $this->assertSame(
            68000.0,
            $montoCobrado,
            'Agosto es el mes en que entró y lleva el 70%: 28.000. Septiembre es un mes '.
            'completo que usó entero: 40.000. Total 68.000. Antes el descuento se aplicaba '.
            'a los dos meses y salía 56.000.'
        );
    }

    public function test_el_alumno_que_entra_en_la_primera_quincena_no_tiene_descuento(): void
    {
        $alumno = $this->alumnoConAlta('2026-09-03');

        $this->assertSame(
            self::PRECIO,
            $this->cobrar($alumno, '2026-09'),
            'El tramo del 1 al 15 es del 100%: no descuenta nada.'
        );
    }

    private function alumnoConAlta(string $fechaAlta): Alumno
    {
        $grupo = Grupo::findOrFail($this->plan->grupo_id);

        $alumno = Alumno::create([
            'nombre'           => 'Prueba',
            'apellido'         => 'Alta '.$fechaAlta,
            'dni'              => (string) random_int(20000000, 49999999),
            'celular'          => '3413334444',
            'fecha_nacimiento' => '2010-03-15',
            'fecha_alta'       => $fechaAlta,
            'deporte_id'       => $grupo->deporte_id,
            'grupo_id'         => $grupo->id,
            'activo'           => true,
        ]);

        AlumnoPlan::create([
            'alumno_id'   => $alumno->id,
            'plan_id'     => $this->plan->id,
            'fecha_desde' => $fechaAlta,
            'activo'      => true,
        ]);

        return $alumno;
    }

    /**
     * Cobra los períodos indicados y devuelve el total efectivamente cobrado.
     */
    private function cobrar(Alumno $alumno, string ...$periodos): float
    {
        foreach ($periodos as $periodo) {
            DeudaCuota::create([
                'alumno_id'      => $alumno->id,
                'periodo'        => $periodo,
                'monto_original' => self::PRECIO,
                'monto_pagado'   => 0,
                'estado'         => DeudaCuota::ESTADO_PENDIENTE,
            ]);
        }

        // Se usa el flujo admin, igual que PagoCuotaServiceTest: evita el armado de
        // una caja operativa y ejercita exactamente la misma regla de primer pago.
        $resultado = app(PagoCuotaService::class)->registrarPagoCuotaAdmin([
            'alumno_id'        => $alumno->id,
            'tipo_caja_id'     => $this->tipoCaja->id,
            'usuario_admin_id' => $this->admin->id,
            'items'            => array_map(
                fn($p) => ['periodo' => $p, 'monto' => self::PRECIO],
                $periodos
            ),
        ]);

        return (float) $resultado['pago']->monto_final;
    }
}
