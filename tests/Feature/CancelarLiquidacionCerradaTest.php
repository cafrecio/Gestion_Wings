<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\LiquidacionPagoService;
use App\Services\LiquidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FIN-12: Cancelar liquidación cerrada no pagada.
 *
 * Contrato §2.4 (enmienda 13/09/2026):
 * - CERRADA + PENDIENTE: Solo ADMIN puede cancelarla para revisar asistencias y rehacerla.
 * - CERRADA + PAGADA: Intocable para todos (la corrección es compensatoria en la siguiente).
 * - La liquidación cancelada y su detalle se conservan para auditoría.
 * - Mientras esté cerrada, bloquea asistencias para todos los perfiles (también ADMIN).
 * - Tras la cancelación, se pueden revisar asistencias y regenerar el período.
 */
class CancelarLiquidacionCerradaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesorUser;
    private Profesor $profesor;
    private Deporte $deporte;
    private Grupo $grupo;
    private LiquidacionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LiquidacionService::class);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $this->deporte = Deporte::create([
            'nombre' => 'Tenis',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Avanzado']);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $this->deporte->id,
            'nombre' => 'Carlos',
            'apellido' => 'Bielsa',
            'dni' => '24111222',
            'fecha_nacimiento' => '1980-05-10',
            'direccion' => 'Calle 1',
            'localidad' => 'Rosario',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'valor_hora' => 10000,
            'activo' => true,
        ]);

        $this->profesorUser = User::factory()->create([
            'rol' => User::ROL_PROFESOR,
            'profesor_id' => $this->profesor->id,
            'activo' => true,
        ]);
    }

    public function test_admin_cancela_liquidacion_cerrada_no_pagada(): void
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-10',
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->service->cerrarLiquidacion($liquidacion->id);
        $this->assertTrue($liquidacion->fresh()->estaCerrada());
        $this->assertTrue($liquidacion->fresh()->estado_pago === Liquidacion::ESTADO_PAGO_PENDIENTE);

        $detalleCountAntes = $liquidacion->detalles()->count();
        $this->assertGreaterThan(0, $detalleCountAntes);

        $response = $this->actingAs($this->admin)->post(route('web.liquidaciones.cancelar', $liquidacion->id), [
            'motivo' => 'Asistencias mal cargadas en clase del 10/08',
        ]);

        $response->assertRedirect(route('web.liquidaciones.show', $liquidacion->id));
        $response->assertSessionHas('success');

        $liqActualizada = $liquidacion->fresh(['usuarioCancelacion']);
        $this->assertTrue($liqActualizada->estaCancelada());
        $this->assertSame(Liquidacion::ESTADO_CANCELADA, $liqActualizada->estado);
        $this->assertSame($this->admin->id, $liqActualizada->usuario_cancelacion_id);
        $this->assertNotNull($liqActualizada->cancelada_at);
        $this->assertSame('Asistencias mal cargadas en clase del 10/08', $liqActualizada->motivo_cancelacion);

        // El detalle y los importes se conservan intactos
        $this->assertSame($detalleCountAntes, $liqActualizada->detalles()->count());
        $this->assertGreaterThan(0, (float) $liqActualizada->total_calculado);
    }

    public function test_liquidacion_pagada_es_intocable_y_rechaza_cancelacion(): void
    {
        $tipoCaja = TipoCaja::create(['nombre' => 'Caja Central', 'saldo_inicial' => 500000, 'activo' => true]);
        $subrubro = Subrubro::create([
            'nombre' => 'Sueldo - Bielsa, Carlos',
            'rubro_id' => \App\Models\Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO'])->id,
            'permitido_para' => 'ADMIN',
            'afecta_caja' => false,
            'activo' => true,
        ]);
        $this->profesor->update(['subrubro_id' => $subrubro->id]);

        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-10',
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->service->cerrarLiquidacion($liquidacion->id);

        app(LiquidacionPagoService::class)->marcarComoPagada($liquidacion->id, [
            'fecha_pago' => '2026-09-01',
            'tipo_caja_id' => $tipoCaja->id,
            'subrubro_id' => $subrubro->id,
            'admin_id' => $this->admin->id,
        ]);

        $this->assertTrue($liquidacion->fresh()->estaPagada());

        // Intento de cancelar por ADMIN vía web
        $response = $this->actingAs($this->admin)->post(route('web.liquidaciones.cancelar', $liquidacion->id), [
            'motivo' => 'Intento de cancelar una liquidación pagada',
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue($liquidacion->fresh()->estaPagada());
        $this->assertFalse($liquidacion->fresh()->estaCancelada());
    }

    public function test_operativo_profesor_y_anonimo_son_rechazados_al_cancelar(): void
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-10',
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->service->cerrarLiquidacion($liquidacion->id);

        // Operativo: rechazado por EnsureAdminWeb con redirect a su home
        $respOperativo = $this->actingAs($this->operativo)->post(route('web.liquidaciones.cancelar', $liquidacion->id), [
            'motivo' => 'Intento operativo de cancelacion',
        ]);
        $respOperativo->assertRedirect(route('web.caja.index'));

        // Profesor: rechazado por EnsureAdminWeb con redirect a su home
        $respProfesor = $this->actingAs($this->profesorUser)->post(route('web.liquidaciones.cancelar', $liquidacion->id), [
            'motivo' => 'Intento profesor de cancelacion',
        ]);
        $respProfesor->assertRedirect(route('web.clases.index'));

        // Anónimo: redirect a login
        auth()->logout();
        $respAnonimo = $this->post(route('web.liquidaciones.cancelar', $liquidacion->id), [
            'motivo' => 'Intento anonimo de cancelacion',
        ]);
        $respAnonimo->assertRedirect(route('login'));

        $this->assertTrue($liquidacion->fresh()->estaCerrada());
        $this->assertFalse($liquidacion->fresh()->estaCancelada());
    }

    public function test_liquidacion_cerrada_bloquea_asistencias_y_cancelada_las_desbloquea(): void
    {
        $alumno = Alumno::create([
            'nombre' => 'Marcos',
            'apellido' => 'Perez',
            'dni' => '32111222',
            'celular' => '1144556677',
            'fecha_nacimiento' => '2005-01-01',
            'grupo_id' => $this->grupo->id,
            'deporte_id' => $this->deporte->id,
            'activo' => true,
        ]);

        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-10',
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->service->cerrarLiquidacion($liquidacion->id);

        // Con liquidación CERRADA, intentar guardar asistencias da error 422
        $respBloqueada = $this->actingAs($this->admin)->postJson(route('web.clases.asistencias', $clase->id), [
            'items' => [
                ['alumno_id' => $alumno->id, 'presente' => true],
            ],
            'motivo' => 'Corrección de asistencia',
        ]);

        $respBloqueada->assertStatus(422);
        $respBloqueada->assertJsonFragment([
            'message' => 'No se pueden modificar asistencias: la clase integra una liquidación cerrada.',
        ]);
        $this->assertFalse($clase->asistencias()->where('alumno_id', $alumno->id)->exists());

        // Cancelamos la liquidación
        $this->service->cancelarLiquidacion($liquidacion->id, 'Revisión de asistencias', $this->admin->id);
        $this->assertTrue($liquidacion->fresh()->estaCancelada());

        // Ahora el ADMIN puede guardar las asistencias
        $respDesbloqueada = $this->actingAs($this->admin)->postJson(route('web.clases.asistencias', $clase->id), [
            'items' => [
                ['alumno_id' => $alumno->id, 'presente' => true],
            ],
            'motivo' => 'Corrección tras cancelar liquidación',
        ]);

        $respDesbloqueada->assertOk();
        $this->assertTrue($clase->asistencias()->where('alumno_id', $alumno->id)->where('presente', true)->exists());
    }

    public function test_bloqueo_de_asistencia_en_liquidacion_comision_cerrada(): void
    {
        $depComision = Deporte::create([
            'nombre' => 'Fútbol Comisión',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Intermedio']);
        $grupoComision = Grupo::create([
            'deporte_id' => $depComision->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $profComision = Profesor::create([
            'deporte_id' => $depComision->id,
            'nombre' => 'Juan',
            'apellido' => 'Comision',
            'dni' => '28111333',
            'fecha_nacimiento' => '1982-01-01',
            'direccion' => 'Calle 2',
            'localidad' => 'Rosario',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION,
            'porcentaje_comision' => 20,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Lucas',
            'apellido' => 'Comisionado',
            'dni' => '35111222',
            'celular' => '1155667788',
            'fecha_nacimiento' => '2004-01-01',
            'grupo_id' => $grupoComision->id,
            'deporte_id' => $depComision->id,
            'activo' => true,
        ]);

        $clase = Clase::create([
            'grupo_id' => $grupoComision->id,
            'fecha' => '2026-08-12',
            'hora_inicio' => '18:00',
            'hora_fin' => '19:00',
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($profComision->id);

        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);

        Pago::create([
            'alumno_id' => $alumno->id,
            'mes' => 8,
            'anio' => 2026,
            'monto_base' => 30000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 30000,
            'estado' => 'COMPLETADO',
            'fecha_pago' => '2026-08-05',
        ]);

        $liquidacion = $this->service->generarLiquidacionMensual($profComision->id, 8, 2026);
        $this->service->cerrarLiquidacion($liquidacion->id);

        // Comprobar que integraLiquidacionCerrada reconoce el vínculo de comisión
        $this->assertTrue($clase->integraLiquidacionCerrada());

        // Asistencia bloqueada
        $resp = $this->actingAs($this->admin)->postJson(route('web.clases.asistencias', $clase->id), [
            'items' => [
                ['alumno_id' => $alumno->id, 'presente' => false],
            ],
            'motivo' => 'Quitar presente',
        ]);
        $resp->assertStatus(422);

        // Cancelar liquidación desbloquea
        $this->service->cancelarLiquidacion($liquidacion->id, 'Error en comisiones', $this->admin->id);
        $this->assertFalse($clase->integraLiquidacionCerrada());
    }

    public function test_regenerar_liquidacion_tras_cancelacion_vincula_reemplazada_por(): void
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-10',
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);

        $liqOriginal = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->service->cerrarLiquidacion($liqOriginal->id);

        $this->service->cancelarLiquidacion($liqOriginal->id, 'Rehacer período por error', $this->admin->id);
        $this->assertNull($liqOriginal->fresh()->reemplazada_por_id);

        // Generar la nueva liquidación para el mismo período
        $liqNueva = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);

        $this->assertNotSame($liqOriginal->id, $liqNueva->id);
        $this->assertSame(Liquidacion::ESTADO_ABIERTA, $liqNueva->estado);

        // La cancelada debe apuntar a la nueva
        $liqOriginalActualizada = $liqOriginal->fresh();
        $this->assertSame($liqNueva->id, $liqOriginalActualizada->reemplazada_por_id);
        $this->assertSame($liqNueva->id, $liqOriginalActualizada->reemplazadaPor->id);
    }

    public function test_idempotencia_cancelacion(): void
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-10',
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);

        $liq = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->service->cerrarLiquidacion($liq->id);

        $primera = $this->service->cancelarLiquidacion($liq->id, 'Primer motivo', $this->admin->id);
        $timestamp = $primera->cancelada_at;

        // Segunda llamada no falla ni altera datos
        $segunda = $this->service->cancelarLiquidacion($liq->id, 'Segundo motivo', $this->admin->id);
        $this->assertSame('Primer motivo', $segunda->motivo_cancelacion);
        $this->assertEquals($timestamp, $segunda->cancelada_at);
    }

    public function test_modelo_impide_modificar_o_eliminar_liquidacion_cancelada(): void
    {
        $liq = Liquidacion::create([
            'profesor_id' => $this->profesor->id,
            'mes' => 8,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 10000,
            'estado' => Liquidacion::ESTADO_CANCELADA,
            'cancelada_at' => now(),
            'usuario_cancelacion_id' => $this->admin->id,
            'motivo_cancelacion' => 'Test inmutabilidad',
        ]);

        // Intentar borrar
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se puede eliminar una liquidación cerrada o cancelada.');
        $liq->delete();
    }

    public function test_modelo_impide_editar_campos_de_liquidacion_cancelada_salvo_reemplazo(): void
    {
        $liq = Liquidacion::create([
            'profesor_id' => $this->profesor->id,
            'mes' => 8,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 10000,
            'estado' => Liquidacion::ESTADO_CANCELADA,
            'cancelada_at' => now(),
            'usuario_cancelacion_id' => $this->admin->id,
            'motivo_cancelacion' => 'Test inmutabilidad campos',
        ]);

        // Intentar cambiar total calculado
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se puede modificar una liquidación cancelada.');
        $liq->update(['total_calculado' => 20000]);
    }
}
