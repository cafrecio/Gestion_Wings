<?php

namespace Tests\Feature;

use App\Models\{Alumno, CajaOperativa, CargoAlumno, CashflowMovimiento, DeudaCuota, MovimientoOperativo, Pago, TipoCaja, User};
use App\Services\{CajaService, CobranzaEstadoService};
use Illuminate\Support\Facades\DB;

require_once getcwd().'/tests/Feature/SaldoYAnulacionCoherentesTest.php';

/** Control independiente por HTTP real, fuera de la suite permanente; solo base Codex. */
class VerificarCierreTest extends SaldoYAnulacionCoherentesTest
{
    public function test_circuito_integrado_y_exporta_evidencia(): void
    {
        $this->assertSame('wings_testing_codex', DB::connection()->getDatabaseName());
        $datos = [];
        foreach (['admin', 'patin', 'futbol', 'tipoCaja'] as $campo) {
            $datos[$campo] = (new \ReflectionProperty(SaldoYAnulacionCoherentesTest::class, $campo))->getValue($this);
        }
        extract($datos);
        $admin->update(['email' => 'cierre.admin@example.test']);
        $operativo = User::factory()->create(['name'=>'Mostrador de prueba', 'email'=>'cierre.operativo@example.test', 'rol'=>User::ROL_OPERATIVO, 'activo'=>true]);
        $carpeta = __DIR__.'/respuestas';
        if (!is_dir($carpeta)) mkdir($carpeta, 0775, true);
        $guardar = function (string $nombre, string $ruta, User $usuario) use ($carpeta) {
            $respuesta = $this->actingAs($usuario)->get($ruta)->assertOk();
            file_put_contents($carpeta.'/'.$nombre.'.html', $respuesta->getContent());
            return $respuesta;
        };

        $saldos = app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$patin, $futbol]));
        $this->assertSame(5000.0, $saldos[$patin->id]['total']);
        $this->assertSame(0.0, $saldos[$futbol->id]['total']);
        $this->assertSame(1, CargoAlumno::where('dni', $patin->dni)->count());
        $guardar('selector', route('web.caja.cobrar-cuota'), $admin);
        $guardar('patin-inicial', route('web.alumnos.show', $patin->id), $admin)->assertSee('Saldo pendiente: $5.000,00', false);
        $guardar('futbol-inicial', route('web.alumnos.show', $futbol->id), $admin)
            ->assertSee('Podés consultar el estado de ese cargo en su ficha de Patín.', false)
            ->assertDontSee('Saldo pendiente: $5.000,00', false);

        // A25 no le exige apertura al dueño, aun sin configuración ni turno.
        $guardar('cobrar-admin', route('web.caja.cobrar', $patin->id), $admin);
        $this->assertSame(0, CajaOperativa::count());
        foreach (['2026-08', '2026-09'] as $periodo) {
            DeudaCuota::create(['alumno_id'=>$patin->id, 'periodo'=>$periodo, 'monto_original'=>48000, 'monto_pagado'=>0, 'estado'=>'PENDIENTE']);
        }
        $this->actingAs($admin)->post(route('web.caja.pagar', $patin->id), [
            'tipo_caja_id'=>$tipoCaja->id, 'periodos'=>['2026-08','2026-09'],
            'montos_cuota'=>['2026-08'=>48000,'2026-09'=>48000], 'monto_entregado'=>101000, 'fecha_pago'=>'2026-10-06',
        ])->assertSessionHas('success');
        $pagoConInscripcion = Pago::where('alumno_id', $patin->id)->sole();
        $this->assertEquals(101000, $pagoConInscripcion->monto_final);
        $this->assertSame(0, CajaOperativa::count());
        $this->assertSame(0, MovimientoOperativo::count());
        $guardar('ficha-antes-anular', route('web.alumnos.show', $patin->id), $admin)->assertSee('data-abrir-anular', false);
        $this->actingAs($admin)->post(route('web.pagos.anular', $pagoConInscripcion->id), ['motivo'=>''])->assertSessionHasErrors('motivo');
        $this->assertSame('COMPLETADO', $pagoConInscripcion->fresh()->estado);
        $this->actingAs($admin)->post(route('web.pagos.anular', $pagoConInscripcion->id), ['motivo'=>'Control independiente: medio equivocado'])->assertSessionHas('success');
        $this->assertSame(['2026-08','2026-09'], array_column($pagoConInscripcion->fresh()->detalle_anulacion['periodos'], 'periodo'));
        $this->assertEquals(5000, CargoAlumno::sole()->saldo_pendiente);
        $guardar('historial-con-inscripcion', route('web.alumnos.show', $patin->id), $admin);

        // Salda solo inscripción; permite probar luego varios meses sin ese cargo.
        $this->actingAs($admin)->post(route('web.caja.pagar', $patin->id), [
            'tipo_caja_id'=>$tipoCaja->id, 'periodos'=>[], 'montos_cuota'=>[], 'monto_entregado'=>5000, 'fecha_pago'=>'2026-10-06',
        ])->assertSessionHas('success');
        $guardar('futbol-inscripcion-pagada', route('web.alumnos.show', $futbol->id), $admin);

        // El operativo debe declarar el cajón; solo sus movimientos forman su esperado.
        $this->actingAs($operativo)->get(route('web.caja.cobrar', $futbol->id))->assertRedirect(route('web.caja.apertura'));
        $caja = \Tests\Support\CajaDeclarada::crear($operativo->id, $tipoCaja->id);
        $caja->update(['efectivo_inicial'=>10000]);
        $this->assertEquals(10000, app(CajaService::class)->arqueoCaja($caja->id)['efectivo_esperado']);
        $this->actingAs($admin)->post(route('web.caja.pagar', $patin->id), [
            'tipo_caja_id'=>$tipoCaja->id, 'periodos'=>['2026-08','2026-09'],
            'montos_cuota'=>['2026-08'=>48000,'2026-09'=>48000], 'monto_entregado'=>96000, 'fecha_pago'=>'2026-10-06',
        ])->assertSessionHas('success');
        $pagoSinInscripcion = Pago::where('alumno_id', $patin->id)->latest('id')->firstOrFail();
        $this->assertEquals(96000, $pagoSinInscripcion->monto_final);
        $this->assertEquals(10000, app(CajaService::class)->arqueoCaja($caja->id)['efectivo_esperado']);
        $this->actingAs($admin)->post(route('web.pagos.anular', $pagoSinInscripcion->id), ['motivo'=>'Control independiente: segunda anulación'])->assertSessionHas('success');
        $guardar('historial-sin-inscripcion', route('web.alumnos.show', $patin->id), $admin);
        $this->assertSame(1, CajaOperativa::count());
        $this->assertSame(0, MovimientoOperativo::count());
        $this->assertEquals(10000, app(CajaService::class)->arqueoCaja($caja->id)['efectivo_esperado']);

        $this->actingAs($operativo)->post(route('web.caja.pagar', $futbol->id), [
            'tipo_caja_id'=>$tipoCaja->id, 'periodos'=>['2026-10'], 'montos_cuota'=>['2026-10'=>48000], 'fecha_pago'=>'2026-10-06',
        ])->assertSessionHas('success');
        $this->assertSame(1, MovimientoOperativo::where('caja_operativa_id',$caja->id)->count());
        $this->assertEquals(58000, app(CajaService::class)->arqueoCaja($caja->id)['efectivo_esperado']);
        $guardar('caja-operativo', route('web.caja.resumen', $caja->id), $operativo);
        $guardar('cashflow', '/cashflow?anio=2026&mes=10', $admin);

        $estado = [
            'saldos_iniciales'=>$saldos,
            'alumnos'=>Alumno::select('id','dni','deporte_id')->get()->toArray(),
            'deudas'=>DeudaCuota::select('id','alumno_id','periodo','monto_original','monto_pagado','estado')->get()->toArray(),
            'pagos'=>Pago::select('id','alumno_id','monto_final','estado','detalle_anulacion')->get()->toArray(),
            'asientos'=>CashflowMovimiento::select('id','referencia_id','referencia_tipo','monto','tipo_caja_id')->get()->toArray(),
            'cajas'=>CajaOperativa::select('id','usuario_operativo_id','efectivo_inicial','estado')->get()->toArray(),
            'arqueo'=>app(CajaService::class)->arqueoCaja($caja->id),
        ];
        file_put_contents(__DIR__.'/estado-ensayo.json', json_encode($estado, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        if (getenv('WINGS_CIERRE_HTTP') === '1') {
            // Conserva solo datos ficticios en esta base descartable para el recorrido de navegador.
            while (DB::transactionLevel() > 0) DB::commit();
        }
    }
}
