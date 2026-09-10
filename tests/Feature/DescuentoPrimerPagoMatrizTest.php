<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\ReglaPrimerPago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Barrido completo del descuento de primer pago sobre el mes de alta.
 *
 * La regla es una sola: **el descuento baja el precio del mes, no el importe que se
 * paga.** Un alumno que entra el 20 con plan de 60.000 debe 42.000; si entrega 10.000,
 * quedan 32.000 pendientes.
 *
 * Existe como matriz y no como prueba suelta porque el defecto aparecio tres veces
 * seguidas por corregir un caso sin barrer los vecinos: se aplicaba el porcentaje al
 * importe tipeado, asi que una seña de 10.000 se cobraba 7.000 y cerraba el mes entero.
 */
class DescuentoPrimerPagoMatrizTest extends TestCase
{
    use RefreshDatabase;

    private const PRECIO = 60000.0;

    private User $operativo;
    private TipoCaja $tipoCaja;
    private GrupoPlan $plan;

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
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => self::PRECIO,
            'activo' => true,
        ]);

        ReglaPrimerPago::create(['nombre' => 'Primera quincena', 'dia_desde' => 1,  'dia_hasta' => 15, 'porcentaje' => 100, 'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Segunda quincena', 'dia_desde' => 16, 'dia_hasta' => 23, 'porcentaje' => 70,  'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Fin de mes',       'dia_desde' => 24, 'dia_hasta' => 31, 'porcentaje' => 40,  'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_pago_completo_con_deuda_existente_deja_el_mes_en_el_precio_con_descuento(): void
    {
        $alumno = $this->alumnoConAlta('2026-08-20');
        $this->crearDeuda($alumno);

        $this->cobrar($alumno, self::PRECIO);

        $this->assertDeuda($alumno, 42000.0, 42000.0, DeudaCuota::ESTADO_PAGADA);
    }

    public function test_pago_completo_sin_deuda_previa_crea_el_mes_con_descuento(): void
    {
        $alumno = $this->alumnoConAlta('2026-08-20');

        $this->cobrar($alumno, self::PRECIO);

        $this->assertDeuda($alumno, 42000.0, 42000.0, DeudaCuota::ESTADO_PAGADA);
    }

    public function test_un_parcial_con_deuda_existente_no_cierra_el_mes(): void
    {
        $alumno = $this->alumnoConAlta('2026-08-20');
        $this->crearDeuda($alumno);

        $this->cobrar($alumno, 10000);

        $this->assertDeuda($alumno, 42000.0, 10000.0, DeudaCuota::ESTADO_PENDIENTE);
    }

    public function test_un_parcial_sin_deuda_previa_no_cierra_el_mes(): void
    {
        $alumno = $this->alumnoConAlta('2026-08-20');

        $this->cobrar($alumno, 10000);

        $this->assertDeuda($alumno, 42000.0, 10000.0, DeudaCuota::ESTADO_PENDIENTE);
    }

    public function test_el_saldo_que_queda_despues_del_parcial_se_puede_terminar_de_cobrar(): void
    {
        $alumno = $this->alumnoConAlta('2026-08-20');
        $this->crearDeuda($alumno);

        $this->cobrar($alumno, 10000);
        $this->cobrar($alumno, 32000);

        $this->assertDeuda($alumno, 42000.0, 42000.0, DeudaCuota::ESTADO_PAGADA);
    }

    public function test_el_tramo_de_fin_de_mes_tambien_descuenta_el_precio_y_no_la_sena(): void
    {
        $alumno = $this->alumnoConAlta('2026-08-25');
        $this->crearDeuda($alumno);

        $this->cobrar($alumno, 10000);

        $this->assertDeuda($alumno, 24000.0, 10000.0, DeudaCuota::ESTADO_PENDIENTE);
    }

    public function test_sin_descuento_el_parcial_se_imputa_contra_el_precio_entero(): void
    {
        $alumno = $this->alumnoConAlta('2026-08-05');
        $this->crearDeuda($alumno);

        $this->cobrar($alumno, 10000);

        $this->assertDeuda($alumno, self::PRECIO, 10000.0, DeudaCuota::ESTADO_PENDIENTE);
    }

    public function test_un_cobro_cancelado_no_le_saca_el_descuento_al_mes_de_alta(): void
    {
        // Adelanta septiembre, que no es su mes de alta: sin descuento, y esta bien.
        $alumno = $this->alumnoConAlta('2026-08-20');
        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-09',
            'monto_original' => self::PRECIO,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
        $this->cobrar($alumno, self::PRECIO, '2026-09');
        $this->cancelarUltimoCobro($alumno);

        // Ahora le cobran agosto, su mes de alta. El cobro cancelado no ocurrio.
        $this->crearDeuda($alumno);
        $this->cobrar($alumno, self::PRECIO);

        $this->assertDeuda($alumno, 42000.0, 42000.0, DeudaCuota::ESTADO_PAGADA);
    }

    public function test_cancelar_y_volver_a_cobrar_el_mes_de_alta_no_descuenta_dos_veces(): void
    {
        $alumno = $this->alumnoConAlta('2026-08-20');
        $this->crearDeuda($alumno);

        $this->cobrar($alumno, self::PRECIO);
        $this->cancelarUltimoCobro($alumno);

        $this->assertDeuda($alumno, 42000.0, 0.0, DeudaCuota::ESTADO_PENDIENTE);

        $this->cobrar($alumno, 42000);

        $this->assertDeuda($alumno, 42000.0, 42000.0, DeudaCuota::ESTADO_PAGADA);
    }

    private function cancelarUltimoCobro(Alumno $alumno): void
    {
        $movimiento = MovimientoOperativo::where('alumno_id', $alumno->id)
            ->whereNotNull('pago_id')
            ->where('estado', '!=', 'CANCELADO')
            ->latest('id')
            ->firstOrFail();

        app(PagoCuotaService::class)->cancelarCobroOperativo(
            $movimiento->id,
            'Error de carga',
            $this->operativo->id
        );
    }

    private function assertDeuda(Alumno $alumno, float $original, float $pagado, string $estado): void
    {
        $deuda = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-08')->firstOrFail();

        $this->assertSame($original, (float) $deuda->monto_original, 'Monto original del mes de alta.');
        $this->assertSame($pagado, (float) $deuda->monto_pagado, 'Importe imputado.');
        $this->assertSame($estado, $deuda->estado, 'Estado de la deuda.');
        $this->assertSame($original - $pagado, $deuda->saldo_pendiente, 'Saldo restante.');
    }

    private function cobrar(Alumno $alumno, float $monto, string $periodo = '2026-08'): void
    {
        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => [$periodo],
                'montos_cuota' => [$periodo => $monto],
                'fecha_pago' => '2026-08-25',
            ])
            ->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('success');
    }

    private function crearDeuda(Alumno $alumno): void
    {
        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-08',
            'monto_original' => self::PRECIO,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    private function alumnoConAlta(string $fechaAlta): Alumno
    {
        $grupo = Grupo::findOrFail($this->plan->grupo_id);

        $alumno = Alumno::create([
            'nombre' => 'Prueba',
            'apellido' => 'Alta '.$fechaAlta,
            'dni' => (string) random_int(20000000, 49999999),
            'fecha_nacimiento' => '2010-01-01',
            'celular' => '1111111111',
            'deporte_id' => $grupo->deporte_id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => $fechaAlta,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => $fechaAlta,
            'activo' => true,
        ]);

        return $alumno;
    }
}
