<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Rules\MedioDePagoActivo;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/** Control independiente: fixture de catálogos compartido, recorridos y aserciones propios. Ejecutar sólo en wings_testing_codex. */
class ControlT119CodexTest extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $operativa;
    private TipoCaja $efectivo;
    private TipoCaja $inactivo;
    private TipoCaja $activo;
    private Alumno $alumno;
    private Clase $clase;
    private CajaOperativa $caja;
    private int $luz;
    private int $insumos;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 18:00:00');
        $this->seed(CatalogosSeeder::class);
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
        $this->duenio = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Federadas'])->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Zoe', 'apellido' => 'Flores', 'dni' => '50000001', 'fecha_nacimiento' => '2012-01-01', 'celular' => '1111111111',
            'nombre_tutor' => 'Tutora', 'telefono_tutor' => '1111111111', 'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha_alta' => '2026-09-01', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10', 'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->efectivo->update(['saldo_inicial' => 500000]);
        $this->activo = TipoCaja::create(['nombre' => 'Banco de prueba', 'abreviatura' => 'BDP', 'activo' => true, 'saldo_inicial' => 500000]);
        $this->inactivo = TipoCaja::create(['nombre' => 'Caja vieja', 'abreviatura' => 'VJA', 'activo' => false, 'saldo_inicial' => 500000]);
        app(\App\Services\CajaService::class)->configurarMostrador($this->efectivo->id, $this->duenio->id);
        $this->caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => '10000.00', 'efectivo_esperado' => '10000.00']);
        $this->clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => '2026-10-20', 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false, 'serie_id' => (string) Str::uuid()]);
        $this->luz = Subrubro::where('nombre', 'Luz')->value('id');
        $this->insumos = Subrubro::where('permitido_para', 'OPERATIVO')->where('afecta_caja', true)->whereHas('rubro', fn ($q) => $q->where('tipo', 'EGRESO'))->value('id');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_matriz_de_roles_y_medios_inactivos_o_inexistentes_no_escribe(): void
    {
        $prof = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]);
        $doc = Profesor::create(['deporte_id' => $this->alumno->deporte_id, 'nombre' => 'Control', 'apellido' => 'Ficticio', 'dni' => '39000001', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'x', 'localidad' => 'x', 'telefono' => '1', 'valor_hora' => 1000, 'activo' => true, 'subrubro_id' => $this->luz]);
        $liq = Liquidacion::create(['profesor_id' => $doc->id, 'mes' => 9, 'anio' => 2026, 'tipo' => 'HORA', 'total_calculado' => 1000, 'estado' => 'CERRADA', 'estado_pago' => 'PENDIENTE']);
        foreach ([$this->operativa, $this->duenio, $prof] as $actor) {
            foreach ([$this->inactivo->id, 999999] as $medio) {
                $mov = ['subrubro_id' => $this->insumos, 'monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Control', 'tipo_caja_id' => $medio];
                $trans = ['monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Control', 'clave' => (string) Str::uuid()];
                $paths = [
                    [route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $medio, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 30000], 'fecha_pago' => '2026-10-15'], 'tipo_caja_id', false],
                    [route('web.caja.movimiento.store'), $mov, 'tipo_caja_id', false],
                    [route('web.caja.editar.store', $this->caja->id), $mov, 'tipo_caja_id', false],
                    [route('web.cashflow.movimiento.store'), array_replace($mov, ['subrubro_id' => $this->luz]), 'tipo_caja_id', true],
                    [route('web.cashflow.transferencia.store'), $trans + ['salida_id' => $medio, 'destino_id' => $this->activo->id], 'salida_id', true],
                    [route('web.cashflow.transferencia.store'), $trans + ['salida_id' => $this->activo->id, 'destino_id' => $medio], 'destino_id', true],
                    [route('web.liquidaciones.pagar', $liq->id), ['fecha_pago' => '2026-10-15', 'tipo_caja_id' => $medio], 'tipo_caja_id', true],
                ];
                foreach ($paths as [$url, $datos, $campo, $admin]) {
                    $antes = $this->estadoFinanciero();
                    $r = $this->actingAs($actor)->postJson($url, $datos);
                    $autorizado = $actor->rol !== 'PROFESOR' && (!$admin || $actor->isAdmin());
                    if ($actor->isAdmin() && $url === route('web.caja.movimiento.store')) {
                        $r->assertRedirect(route('web.cashflow.movimiento'))->assertSessionHas('error', 'Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.');
                    } elseif (!$autorizado) {
                        $r->assertForbidden();
                    } else {
                        $r->assertStatus(422)->assertJsonValidationErrors($campo);
                        $esperado = $medio === $this->inactivo->id ? MedioDePagoActivo::MENSAJE : match ($campo) {
                            'salida_id' => 'El valor elegido para cuenta de salida no existe.',
                            'destino_id' => 'El valor elegido para cuenta de destino no existe.',
                            default => 'El valor elegido para medio de pago no existe.',
                        };
                        $this->assertSame($esperado, $r->json('errors.'.$campo.'.0'));
                    }
                    $this->assertSame($antes, $this->estadoFinanciero(), $actor->rol.' '.$url);
                }
            }
        }
    }

    private function estadoFinanciero(): array
    {
        $estado = [];
        foreach (['pagos', 'pago_deuda_cuota', 'pago_cargo_alumno', 'deuda_cuotas', 'movimientos_operativos', 'cashflow_movimientos', 'cashflow_transferencias', 'liquidaciones', 'cajas_operativas'] as $tabla) {
            if (\Illuminate\Support\Facades\Schema::hasTable($tabla)) {
                $estado[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
            }
        }
        return $estado;
    }

    private function movimientoBanco(): MovimientoOperativo
    {
        $this->actingAs($this->operativa)->post(route('web.caja.movimiento.store'), ['tipo_caja_id' => $this->activo->id, 'subrubro_id' => $this->insumos, 'monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Control'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $mov = MovimientoOperativo::orderByDesc('id')->firstOrFail();
        $this->actingAs($this->duenio)->patchJson(route('web.tipos-caja.toggle-activo', $this->activo->id))->assertOk()->assertJsonPath('activo', false);
        return $mov;
    }

    public function test_correccion_real_con_medio_desactivado_conserva_medio_y_formulario_debe_ofrecerlo(): void
    {
        $mov = $this->movimientoBanco();
        $datos = ['subrubro_id' => $this->insumos, 'monto' => '80', 'fecha' => '2026-10-15', 'observaciones' => 'Control corregido'];
        foreach ([$this->operativa, $this->duenio] as $actor) {
            $this->actingAs($actor)->putJson(route('web.caja.movimientos.update', [$this->caja->id, $mov->id]), ['tipo_caja_id' => $this->inactivo->id] + $datos)->assertStatus(422)->assertJsonValidationErrors('tipo_caja_id');
            $this->assertSame($this->activo->id, $mov->fresh()->tipo_caja_id);
            $this->actingAs($actor)->put(route('web.caja.movimientos.update', [$this->caja->id, $mov->id]), ['tipo_caja_id' => $this->activo->id] + $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertEquals(80, (float) $mov->fresh()->monto);
        }
        $r = $this->actingAs($this->operativa)->get(route('web.caja.movimientos.editar', [$this->caja->id, $mov->id]))->assertOk();
        preg_match('/<select[^>]*id="tipo_caja_id"[\s\S]*?<\/select>/', $r->getContent(), $match);
        file_put_contents(__DIR__.'/selector-caja-inactivo.html', $match[0] ?? 'SELECT NO ENCONTRADO');
        $r->assertViewHas('tiposCaja', fn ($tipos) => $tipos->contains('id', $this->activo->id));
    }

    public function test_movimiento_de_medio_desactivado_se_cierra_verifica_y_valida_sin_cambiarlo(): void
    {
        $mov = $this->movimientoBanco();
        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $this->caja->id), ['efectivo_contado' => '10000', 'cambio_retenido' => '10000', 'esperado_visto' => '10000.00'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->actingAs($this->duenio)->patchJson(route('web.cajas.movimientos.verificar', [$this->caja->id, $mov->id]), ['verificado' => true, 'huella' => $mov->fresh()->huellaVerificacion()])->assertOk()->assertJsonPath('verificado', true);
        $this->actingAs($this->duenio)->post(route('web.cajas.validar', $this->caja->id))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('VALIDADA', $this->caja->fresh()->estado);
        $cf = CashflowMovimiento::where('movimiento_operativo_id', $mov->id)->sole();
        $this->assertSame($this->activo->id, $cf->tipo_caja_id);
        $this->assertEquals(-100, (float) $cf->monto);
    }

    public function test_duenio_reactiva_medio_y_puede_vaciarlo_por_transferencia(): void
    {
        $datos = ['salida_id' => $this->inactivo->id, 'destino_id' => $this->activo->id, 'monto' => '500000', 'fecha' => '2026-10-15', 'observaciones' => 'Vaciar cuenta', 'clave' => (string) Str::uuid()];
        $this->actingAs($this->duenio)->postJson(route('web.cashflow.transferencia.store'), $datos)->assertStatus(422)->assertJsonValidationErrors('salida_id');
        $this->assertSame(0, DB::table('cashflow_transferencias')->count());
        $this->actingAs($this->duenio)->patchJson(route('web.tipos-caja.toggle-activo', $this->inactivo->id))->assertOk()->assertJsonPath('activo', true);
        $this->actingAs($this->duenio)->post(route('web.cashflow.transferencia.store'), $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(1, DB::table('cashflow_transferencias')->count());
        $this->assertEquals(0, 500000 + (float) CashflowMovimiento::where('tipo_caja_id', $this->inactivo->id)->sum('monto'));
    }

    public function test_segunda_ficha_y_edicion_normal_en_ambos_roles(): void
    {
        $sport = Deporte::create(['nombre' => 'Segundo control', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $sport->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 1000, 'activo' => true]);
        foreach ([$this->operativa, $this->duenio] as $i => $actor) {
            $datos = $this->alumno->only(['apellido', 'dni', 'celular', 'nombre_tutor', 'telefono_tutor']) + ['nombre' => 'Control '.$i, 'fecha_nacimiento' => '2012-01-01', 'fecha_alta' => '2026-09-01', 'deporte_id' => $this->alumno->deporte_id, 'grupo_id' => $this->alumno->grupo_id];
            $this->actingAs($actor)->put(route('web.alumnos.update', $this->alumno->id), $datos)->assertSessionHasNoErrors();
            $this->assertSame('Control '.$i, $this->alumno->fresh()->nombre);
            $datos = array_replace($datos, ['fecha_alta' => '2026-10-15', 'deporte_id' => $sport->id, 'grupo_id' => $grupo->id, 'plan_id' => $plan->id]);
            if ($i === 1) {
                $nuevoSport = $sport->replicate(); $nuevoSport->nombre = 'Tercer control'; $nuevoSport->save();
                $nuevoGrupo = $grupo->replicate(); $nuevoGrupo->deporte_id = $nuevoSport->id; $nuevoGrupo->save();
                $nuevoPlan = $plan->replicate(); $nuevoPlan->grupo_id = $nuevoGrupo->id; $nuevoPlan->save();
                $datos = array_replace($datos, ['deporte_id' => $nuevoSport->id, 'grupo_id' => $nuevoGrupo->id, 'plan_id' => $nuevoPlan->id]);
            }
            $this->actingAs($actor)->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertSame(2 + $i, Alumno::where('dni', $this->alumno->dni)->count());
            $this->assertSame($this->alumno->deporte_id, $this->alumno->fresh()->deporte_id);
        }
    }
    public function test_deporte_grupo_y_plan_quedan_iguales_tras_envio_directo_en_ambos_roles(): void
    {
        $otro = Deporte::create(['nombre' => 'Otro control', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $otro->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 1000, 'activo' => true]);
        $datos = $this->alumno->only(['nombre', 'apellido', 'dni', 'celular', 'nombre_tutor', 'telefono_tutor']) + ['fecha_nacimiento' => '2012-01-01', 'fecha_alta' => '2026-09-01', 'deporte_id' => $otro->id, 'grupo_id' => $grupo->id, 'plan_id' => $plan->id];
        $antes = [$this->alumno->fresh()->getAttributes(), AlumnoPlan::where('alumno_id', $this->alumno->id)->get()->toJson()];
        foreach ([$this->operativa, $this->duenio] as $actor) {
            $this->actingAs($actor)->putJson(route('web.alumnos.update', $this->alumno->id), $datos)->assertStatus(422)->assertJsonPath('errors.deporte_id.0', 'No se puede cambiar el deporte de un alumno: cargá otra ficha para el otro deporte.');
            $this->assertSame($antes, [$this->alumno->fresh()->getAttributes(), AlumnoPlan::where('alumno_id', $this->alumno->id)->get()->toJson()]);
        }
    }

    public function test_subrubro_desactivado_no_se_ofrece_pero_envio_directo_sigue_cargandolo(): void
    {
        $sub = Subrubro::findOrFail($this->insumos);
        $this->actingAs($this->duenio)->patch(route('web.subrubros.toggle-activo', [$sub->rubro_id, $sub->id]))->assertSessionHas('success');
        $this->assertFalse((bool) $sub->fresh()->activo);
        foreach ([$this->operativa, $this->duenio] as $actor) {
            $formulario = $actor->isAdmin() ? route('web.caja.editar', $this->caja->id) : route('web.caja.movimiento');
            $destino = $actor->isAdmin() ? route('web.caja.editar.store', $this->caja->id) : route('web.caja.movimiento.store');
            $this->actingAs($actor)->get($formulario)->assertOk()->assertViewHas('rubros', fn ($rubros) => !$rubros->flatMap->subrubros->contains('id', $sub->id));
            $antes = MovimientoOperativo::count();
            $this->post($destino, ['subrubro_id' => $sub->id, 'tipo_caja_id' => $this->activo->id, 'monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Control directo'])->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertSame($antes + 1, MovimientoOperativo::count());
            $this->assertSame($sub->id, MovimientoOperativo::orderByDesc('id')->firstOrFail()->subrubro_id);
        }
    }

    public function test_formulario_cashflow_y_correccion_conservan_medio_desactivado(): void
    {
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => $this->luz, 'tipo_caja_id' => $this->activo->id, 'monto' => '100', 'fecha' => '2026-10-15', 'observaciones' => 'Control'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $mov = CashflowMovimiento::orderByDesc('id')->firstOrFail();
        $this->actingAs($this->duenio)->patchJson(route('web.tipos-caja.toggle-activo', $this->activo->id))->assertOk()->assertJsonPath('activo', false);
        $this->get(route('web.cashflow.movimientos.editar', $mov->id))->assertOk()->assertViewHas('tiposCaja', fn ($tipos) => $tipos->contains('id', $this->activo->id));
        $datos = ['clave' => (string) Str::uuid(), 'version' => $mov->version_correccion, 'monto' => '80', 'fecha' => '2026-10-15', 'subrubro_id' => $this->luz, 'observaciones' => 'Control', 'motivo' => 'Control'];
        $this->putJson(route('web.cashflow.movimientos.update', $mov->id), ['tipo_caja_id' => $this->inactivo->id] + $datos)->assertStatus(422)->assertJsonValidationErrors('tipo_caja_id');
        $this->assertEquals(-100, (float) $mov->fresh()->monto);
        $this->put(route('web.cashflow.movimientos.update', $mov->id), ['tipo_caja_id' => $this->activo->id] + $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertEquals(-80, (float) $mov->fresh()->monto);
        $this->assertSame($this->activo->id, $mov->fresh()->tipo_caja_id);
    }
}
