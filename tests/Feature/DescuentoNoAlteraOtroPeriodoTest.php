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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * COB-03: el descuento de primer pago solo puede tocar el mes de alta.
 *
 * `ajustarDeudas()` existe para bajar `monto_original` del periodo con descuento, pero
 * recorre todos los items del pago. Si en el mismo cobro entra un pago parcial de otro
 * mes, ese mes se queda con el parcial como monto original y el saldo restante
 * desaparece: el alumno figura al dia debiendo la diferencia.
 */
class DescuentoNoAlteraOtroPeriodoTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private TipoCaja $tipoCaja;
    private Alumno $alumno;

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
        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 28000,
            'activo' => true,
        ]);

        // Alta el 20/08: cae en la regla de segunda quincena, 70%.
        $this->alumno = Alumno::create([
            'nombre' => 'Parcial',
            'apellido' => 'Descuento',
            'dni' => '30987654',
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-08-20',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-08-01',
            'activo' => true,
        ]);
        ReglaPrimerPago::create([
            'nombre' => 'Segunda quincena',
            'dia_desde' => 16,
            'dia_hasta' => 23,
            'porcentaje' => 70,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_un_parcial_de_otro_periodo_conserva_su_monto_original(): void
    {
        // Septiembre ya esta cargado y sin pagar; el alumno deja una seña.
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 28000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Paga agosto entero (con descuento) y deja seña de septiembre.
        $respuesta = $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => ['2026-08', '2026-09'],
                'montos_cuota' => [
                    '2026-08' => 28000,
                    '2026-09' => 10000,
                ],
                'fecha_pago' => '2026-08-25',
            ]);

        $respuesta->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('success');

        // El mes de alta si recibe el descuento.
        $agosto = DeudaCuota::where('alumno_id', $this->alumno->id)
            ->where('periodo', '2026-08')
            ->firstOrFail();
        $this->assertSame(19600.0, (float) $agosto->monto_original);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $agosto->estado);

        // Septiembre conserva su monto y queda debiendo la diferencia.
        $septiembre = DeudaCuota::where('alumno_id', $this->alumno->id)
            ->where('periodo', '2026-09')
            ->firstOrFail();
        $this->assertSame(
            28000.0,
            (float) $septiembre->monto_original,
            'El descuento del mes de alta no puede bajar el monto original de otro periodo.'
        );
        $this->assertSame(10000.0, (float) $septiembre->monto_pagado);
        $this->assertSame(18000.0, $septiembre->saldo_pendiente);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $septiembre->estado);
    }
}
