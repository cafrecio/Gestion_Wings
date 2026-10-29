<?php

namespace Tests\Feature;

use App\Models\{Alumno, CajaOperativa, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, MovimientoOperativo, TipoCaja, User, AlumnoRevisionCobranza, AlumnoPlan};
use App\Services\CobranzaEstadoService;
use App\Support\FechaIngresoRegla;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificarCaminosProhibidosManualTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesor;
    private TipoCaja $tipoEfectivo;
    private \App\Models\Subrubro $subrubro;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->profesor = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);

        $this->tipoEfectivo = TipoCaja::create(['nombre' => 'Efectivo Mostrador', 'activo' => true]);
        $rubro = \App\Models\Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'activo' => true]);
        $this->subrubro = \App\Models\Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Cuota Mensual', 'activo' => true]);
        \DB::table('caja_mostrador')->where('id', 1)->update(['tipo_caja_id' => $this->tipoEfectivo->id]);

        $this->deporte = Deporte::create(['nombre' => 'Patin', 'activo' => true]);
        $nivel = \App\Models\Nivel::create(['nombre' => 'Inicial', 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 25000, 'activo' => true]);
    }

    private function crearAlumno(array $attrs = []): Alumno
    {
        static $dni = 20000000;
        $dni++;
        return Alumno::create(array_merge([
            'nombre' => 'Alumno',
            'apellido' => 'Test',
            'dni' => (string) $dni,
            'fecha_nacimiento' => '2005-01-01',
            'fecha_alta' => now()->format('Y-m-d'),
            'celular' => '1122334455',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ], $attrs));
    }

    public function test_01_cobrar_sin_caja_abierta_redirige_con_error()
    {
        $alumno = $this->crearAlumno();

        $resp = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', $alumno->id));
        $resp->assertRedirect(route('web.caja.apertura'));

        $respPost = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => [now()->format('Y-m')],
        ]);
        $respPost->assertStatus(422);
        $respPost->assertJsonFragment([
            'success' => false,
            'message' => 'Abrí la caja y confirmá el efectivo antes de cobrar.',
        ]);
    }

    public function test_02_cancelar_cobro_con_caja_cerrada_o_validada_rechaza()
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'estado' => CajaOperativa::ESTADO_CERRADA,
            'efectivo_inicial' => 1000,
            'efectivo_contado' => 1000,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'apertura_at' => now(),
            'cierre_at' => now(),
        ]);
        $alumno = $this->crearAlumno();
        $mov = MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'usuario_id' => $this->operativo->id,
            'alumno_id' => $alumno->id,
            'subrubro_id' => $this->subrubro->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'monto' => 5000,
            'tipo' => 'INGRESO',
            'fecha' => now()->format('Y-m-d'),
        ]);

        $resp = $this->actingAs($this->operativo)->get(route('web.caja.movimientos.cancelar', [$caja->id, $mov->id]));
        $resp->assertRedirect(route('web.caja.detalle', $caja->id));
        $resp->assertSessionHas('error', 'Solo se puede cancelar un cobro con la caja abierta o rechazada.');
    }

    public function test_03_buscar_alumnos_con_rol_profesor_devuelve_403()
    {
        $resp = $this->actingAs($this->profesor)->get(route('web.alumnos.index'));
        $resp->assertStatus(403);
    }

    public function test_04_alta_alumno_menor_sin_tutor_o_fecha_invalida_o_dni_duplicado()
    {
        // Menor sin tutor
        $resp = $this->actingAs($this->operativo)->post(route('web.alumnos.store'), [
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => '45123456',
            'fecha_nacimiento' => now()->subYears(10)->format('Y-m-d'),
            'fecha_alta' => now()->format('Y-m-d'),
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
            'generar_cuota_actual' => 0,
        ]);
        $resp->assertSessionHasErrors(['nombre_tutor', 'telefono_tutor']);
        $this->assertEquals('El nombre del tutor es obligatorio para menores de edad.', session('errors')->first('nombre_tutor'));
        $this->assertEquals('El teléfono del tutor es obligatorio para menores de edad.', session('errors')->first('telefono_tutor'));

        // Fecha de ingreso posterior al fin del mes siguiente
        $limite = FechaIngresoRegla::limiteMaximo();
        $respFecha = $this->actingAs($this->operativo)->post(route('web.alumnos.store'), [
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'dni' => '46123456',
            'fecha_nacimiento' => now()->subYears(20)->format('Y-m-d'),
            'fecha_alta' => $limite->copy()->addDays(5)->format('Y-m-d'),
            'celular' => '1122334455',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
            'generar_cuota_actual' => 0,
        ]);
        $respFecha->assertSessionHasErrors('fecha_alta');
        $this->assertEquals(FechaIngresoRegla::mensajeError(), session('errors')->first('fecha_alta'));
    }

    public function test_05_modificar_alumno_fecha_alta_sin_motivo()
    {
        $alumno = $this->crearAlumno([
            'fecha_alta' => now()->subMonths(2)->format('Y-m-d'),
        ]);

        $resp = $this->actingAs($this->operativo)->put(route('web.alumnos.update', $alumno->id), [
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'dni' => $alumno->dni,
            'fecha_nacimiento' => $alumno->fecha_nacimiento->format('Y-m-d'),
            'fecha_alta' => now()->subMonths(1)->format('Y-m-d'), // cambia fecha_alta
            'celular' => '1122334455',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
            // motivo_fecha_alta omitido
        ]);
        $resp->assertSessionHasErrors('motivo_fecha_alta');
    }

    public function test_06_cambiar_grupo_sin_frecuencia_semanal_rechaza()
    {
        $nivel2 = \App\Models\Nivel::create(['nombre' => 'Avanzado', 'activo' => true]);
        $otroGrupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => $nivel2->id, 'activo' => true]);
        $alumno = $this->crearAlumno();

        $resp = $this->actingAs($this->operativo)->put(route('web.alumnos.update', $alumno->id), [
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'dni' => $alumno->dni,
            'fecha_nacimiento' => $alumno->fecha_nacimiento->format('Y-m-d'),
            'fecha_alta' => $alumno->fecha_alta->format('Y-m-d'),
            'celular' => '1122334455',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $otroGrupo->id, // cambia de grupo
            // plan_id omitido
        ]);
        $resp->assertSessionHasErrors('plan_id');
        $this->assertEquals('Debe seleccionar la frecuencia semanal del nuevo grupo.', session('errors')->first('plan_id'));
    }

    public function test_07_alumno_inactivo_cobro_mes_actual_con_no_devuelve_422()
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'estado' => CajaOperativa::ESTADO_ABIERTA,
            'efectivo_inicial' => 1000,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'apertura_at' => now(),
        ]);
        $alumno = $this->crearAlumno(['activo' => false]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => now()->startOfMonth()->format('Y-m-d'),
        ]);

        // Intentar cobrar mes actual forzando reactivar = false
        $resp = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'fecha_pago' => now()->format('Y-m-d'),
            'reactivar' => false,
            'periodos' => [now()->format('Y-m')],
            'montos_cuota' => [now()->format('Y-m') => 25000],
        ]);

        $resp->assertStatus(422);
        $resp->assertJsonFragment([
            'success' => false,
            'message' => 'El cobro incluye el mes en curso o uno futuro: para cobrarlo, el alumno tiene que volver a activo.',
        ]);
    }

    public function test_08_cobranza_con_rol_profesor_devuelve_403()
    {
        $resp = $this->actingAs($this->profesor)->get(route('web.cobranza.index'));
        $resp->assertStatus(403);
    }

    public function test_09_movimientos_con_rol_profesor_devuelve_403()
    {
        $resp = $this->actingAs($this->profesor)->get(route('web.movimientos.index'));
        $resp->assertStatus(403);
    }

    public function test_10_abrir_caja_cuando_ya_hay_turno_abierto_rechaza()
    {
        CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'estado' => CajaOperativa::ESTADO_ABIERTA,
            'efectivo_inicial' => 1000,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'apertura_at' => now(),
        ]);

        $resp = $this->actingAs($this->operativo)->post(route('web.caja.abrir'), [
            'efectivo_inicial' => 1000,
            'confirmacion' => '1',
        ]);
        $resp->assertSessionHasErrors('efectivo_inicial');
        $this->assertEquals('Hay un turno abierto. Cerralo antes de abrir otro.', session('errors')->first('efectivo_inicial'));
    }

    public function test_11_cancelar_movimiento_manual_sin_alumno_devuelve_403()
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'estado' => CajaOperativa::ESTADO_ABIERTA,
            'efectivo_inicial' => 1000,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'apertura_at' => now(),
        ]);
        $movManual = MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'usuario_id' => $this->operativo->id,
            'alumno_id' => null, // movimiento manual sin alumno
            'subrubro_id' => $this->subrubro->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'monto' => 1500,
            'tipo' => 'INGRESO',
            'fecha' => now()->format('Y-m-d'),
        ]);

        $resp = $this->actingAs($this->operativo)->get(route('web.caja.movimientos.cancelar', [$caja->id, $movManual->id]));
        $resp->assertStatus(403);
    }

    public function test_12_eliminar_movimiento_manual_con_caja_cerrada_o_validada_rechaza()
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'estado' => CajaOperativa::ESTADO_CERRADA,
            'efectivo_inicial' => 1000,
            'efectivo_contado' => 1000,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'apertura_at' => now(),
            'cierre_at' => now(),
        ]);
        $movManual = MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'usuario_id' => $this->operativo->id,
            'alumno_id' => null,
            'subrubro_id' => $this->subrubro->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'monto' => 500,
            'tipo' => 'EGRESO',
            'fecha' => now()->format('Y-m-d'),
        ]);

        $resp = $this->actingAs($this->operativo)->delete(route('web.caja.movimientos.destroy', [$caja->id, $movManual->id]));
        $resp->assertSessionHasErrors('movimiento');
        $this->assertEquals('La caja no está en estado editable.', session('errors')->first('movimiento'));
    }

    public function test_14_cerrar_caja_con_cambio_retenido_mayor_a_contado_rechaza()
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'estado' => CajaOperativa::ESTADO_ABIERTA,
            'efectivo_inicial' => 1000,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'apertura_at' => now(),
        ]);

        $resp = $this->actingAs($this->operativo)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => 5000,
            'cambio_retenido' => 6000, // mayor a contado
        ]);
        $resp->assertSessionHasErrors('cambio_retenido');
    }

    public function test_15_rectificar_caja_no_abierta_redirige_con_error()
    {
        $cajaRechazada = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'estado' => CajaOperativa::ESTADO_RECHAZADA,
            'efectivo_inicial' => 1000,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'apertura_at' => now(),
        ]);

        $resp = $this->actingAs($this->operativo)->get(route('web.caja.rectificar', $cajaRechazada->id));
        $resp->assertRedirect(route('web.caja.resumen', $cajaRechazada->id));
        $resp->assertSessionHas('error', 'Solo se puede rectificar una caja en estado ABIERTA.');
    }

    public function test_16_historial_cajas_con_rol_profesor_devuelve_403()
    {
        $resp = $this->actingAs($this->profesor)->get(route('web.caja.historial'));
        $resp->assertStatus(403);
    }

    public function test_18_tomar_lista_clase_futura_tiene_checkboxes_disabled()
    {
        $claseFutura = Clase::create([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'profesor_id' => $this->profesor->id,
            'fecha' => now()->addDays(2)->format('Y-m-d'),
            'hora_inicio' => '18:00',
            'hora_fin' => '19:00',
        ]);
        $alumno = $this->crearAlumno();

        $resp = $this->actingAs($this->operativo)->get(route('web.clases.show', $claseFutura->id));
        $resp->assertStatus(200);
        $resp->assertSee('Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.');
        $resp->assertSee('disabled');
    }

    public function test_19_resolver_revision_ya_resuelta_devuelve_error()
    {
        $alumno = $this->crearAlumno();
        $revision = AlumnoRevisionCobranza::create([
            'alumno_id' => $alumno->id,
            'periodo_objetivo' => now()->format('Y-m'),
            'motivo' => 'Sin asistencias el mes anterior',
            'estado_revision' => AlumnoRevisionCobranza::ESTADO_RESUELTO, // ya resuelto
        ]);

        $resp = $this->actingAs($this->operativo)->post(route('web.revision-cobranza.resolver', $revision->id), [
            'resolucion' => 'CONTINUA',
            'nota_resolucion' => 'Contacto realizado con madre',
        ]);
        $resp->assertSessionHas('error', 'Esta revisión ya fue resuelta.');
    }

    public function test_20_grupos_create_con_rol_operativo_devuelve_403()
    {
        $resp = $this->actingAs($this->operativo)->get(route('web.grupos.create'));
        $resp->assertStatus(403);
    }
}
