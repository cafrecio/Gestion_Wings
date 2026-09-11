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
use App\Models\Pago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\PagoCuotaService;
use App\Services\ReciboService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * FIN-02: el recibo tiene que mostrar el medio del pago que documenta.
 *
 * El medio se buscaba por texto, importe y fecha, quedandose con el primer
 * movimiento que coincidiera. Con dos cobros iguales del mismo alumno el mismo dia
 * —o un cobro cancelado y vuelto a cobrar por otro medio— el recibo del segundo
 * mostraba el medio del primero. El movimiento ya guarda `pago_id`: se usa ese.
 */
class ReciboMedioDePagoTest extends TestCase
{
    use RefreshDatabase;

    private const PRECIO = 60000.0;

    private User $operativo;
    private TipoCaja $efectivo;
    private TipoCaja $transferencia;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-10 10:00:00');
        Storage::fake();

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);
        $this->efectivo = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $this->transferencia = TipoCaja::create(['nombre' => 'Transferencia', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => self::PRECIO,
            'activo' => true,
        ]);

        // Alta vieja: el mes de alta no se cobra aca, asi que no hay descuento en juego.
        $this->alumno = Alumno::create([
            'nombre' => 'Recibo',
            'apellido' => 'Medio',
            'dni' => '30555666',
            'fecha_nacimiento' => '2010-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-01-05',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-01-05',
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_recibo_muestra_el_medio_con_el_que_se_cobro(): void
    {
        $this->crearDeuda('2026-09');
        $this->cobrar('2026-09', $this->transferencia);

        $this->assertSame('Transferencia', $this->medioDelRecibo($this->ultimoPago()));
    }

    public function test_dos_cobros_iguales_el_mismo_dia_con_medios_distintos_tienen_cada_uno_su_medio(): void
    {
        $this->crearDeuda('2026-08');
        $this->crearDeuda('2026-09');

        $this->cobrar('2026-08', $this->efectivo);
        $primero = $this->ultimoPago();
        $this->cobrar('2026-09', $this->transferencia);
        $segundo = $this->ultimoPago();

        $this->assertSame('Efectivo', $this->medioDelRecibo($primero));
        $this->assertSame(
            'Transferencia',
            $this->medioDelRecibo($segundo),
            'Mismo alumno, mismo importe, mismo dia: el recibo del segundo cobro tomaba el medio del primero.'
        );
    }

    public function test_cancelar_y_volver_a_cobrar_por_otro_medio_no_hereda_el_medio_del_cancelado(): void
    {
        $this->crearDeuda('2026-09');

        $this->cobrar('2026-09', $this->efectivo);
        $cancelado = $this->ultimoPago();
        $this->cancelarUltimoCobro();

        $this->cobrar('2026-09', $this->transferencia);
        $vigente = $this->ultimoPago();

        $this->assertSame(
            'Transferencia',
            $this->medioDelRecibo($vigente),
            'El recibo del cobro vigente mostraba el medio del cobro cancelado.'
        );
        $this->assertSame('Efectivo', $this->medioDelRecibo($cancelado), 'El recibo anulado conserva su medio.');
    }

    /**
     * Devuelve el medio que el servicio le pasa a la vista del recibo, sin generar el PDF.
     */
    private function medioDelRecibo(Pago $pago): string
    {
        $capturado = null;

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturnUsing(function (string $vista, array $data) use (&$capturado) {
                $capturado = $data;
                $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
                $pdf->shouldReceive('setPaper')->andReturnSelf();
                $pdf->shouldReceive('output')->andReturn('%PDF-prueba');
                return $pdf;
            });

        app(ReciboService::class)->generarReciboCuota($pago->id, true);

        return $capturado['medio_cobro']['tipo_caja'];
    }

    private function cobrar(string $periodo, TipoCaja $tipoCaja): void
    {
        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $tipoCaja->id,
                'periodos' => [$periodo],
                'montos_cuota' => [$periodo => self::PRECIO],
                'fecha_pago' => '2026-09-10',
            ])
            ->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('success');
    }

    private function cancelarUltimoCobro(): void
    {
        $movimiento = MovimientoOperativo::where('alumno_id', $this->alumno->id)
            ->whereNotNull('pago_id')
            ->where('estado', '!=', 'CANCELADO')
            ->latest('id')
            ->firstOrFail();

        app(PagoCuotaService::class)->cancelarCobroOperativo($movimiento->id, 'Medio equivocado', $this->operativo->id);
    }

    private function ultimoPago(): Pago
    {
        return Pago::where('alumno_id', $this->alumno->id)->latest('id')->firstOrFail();
    }

    private function crearDeuda(string $periodo): void
    {
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => $periodo,
            'monto_original' => self::PRECIO,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }
}
