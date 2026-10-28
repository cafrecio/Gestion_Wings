<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\CargoAlumno;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\Subrubro;
use App\Models\User;
use App\Services\InscripcionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EliminarAlumnoYClaseT65Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesorUser;
    private Deporte $patin;
    private Deporte $futbol;
    private Grupo $grupoPatin;
    private Grupo $grupoFutbol;
    private GrupoPlan $planPatin;
    private Profesor $profesor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->profesorUser = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]);

        $this->patin = Deporte::create(['nombre' => 'Patin', 'activo' => true]);
        $this->futbol = Deporte::create(['nombre' => 'Futbol', 'activo' => true]);

        $nivel = Nivel::create(['nombre' => 'Inicial', 'activo' => true]);

        $this->grupoPatin = Grupo::create([
            'nombre' => 'Patin Inicial',
            'deporte_id' => $this->patin->id,
            'nivel_id' => $nivel->id,
            'color' => '#FF0000',
            'activo' => true,
        ]);
        $this->grupoFutbol = Grupo::create([
            'nombre' => 'Futbol Juvenil',
            'deporte_id' => $this->futbol->id,
            'nivel_id' => $nivel->id,
            'color' => '#00FF00',
            'activo' => true,
        ]);

        $this->planPatin = GrupoPlan::create([
            'grupo_id' => $this->grupoPatin->id,
            'nombre' => 'Plan 2 dias',
            'clases_por_semana' => 2,
            'precio_mensual' => 15000,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'nombre' => 'Lucia',
            'apellido' => 'Mendez',
            'dni' => '30111222',
            'fecha_nacimiento' => '1995-03-20',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Quilmes',
            'deporte_id' => $this->patin->id,
            'tipo_liquidacion' => 'HORA',
            'valor_hora' => 12000,
            'activo' => true,
        ]);

        // Subrubro inscripcion
        $rubro = DB::table('rubros')->where('nombre', 'Inscripciones')->first();
        $rubroId = $rubro?->id ?? DB::table('rubros')->insertGetId([
            'nombre' => 'Inscripciones',
            'tipo' => 'INGRESO',
            'es_reservado_sistema' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if (!DB::table('subrubros')->where('nombre', 'Inscripción al club')->exists()) {
            DB::table('subrubros')->insert([
                'rubro_id' => $rubroId,
                'nombre' => 'Inscripción al club',
                'permitido_para' => 'OPERATIVO',
                'afecta_caja' => true,
                'es_reservado_sistema' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('configuraciones')->updateOrInsert(['clave' => 'inscripcion_importe'], ['valor' => '5000.00', 'tipo' => 'string']);
    }

    private function crearAlumnoLimpio(string $dni = '40123456', ?Deporte $deporte = null, ?Grupo $grupo = null): Alumno
    {
        $deporte = $deporte ?? $this->patin;
        $grupo = $grupo ?? $this->grupoPatin;

        $alumno = Alumno::create([
            'nombre' => 'Tomas',
            'apellido' => 'Prueba',
            'dni' => $dni,
            'fecha_nacimiento' => '2005-05-15',
            'celular' => '1155556666',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-10-01',
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->planPatin->id,
            'fecha_desde' => '2026-10-01',
            'activo' => true,
        ]);

        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 15000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        app(InscripcionService::class)->sincronizar($alumno, $this->admin->id);

        return $alumno;
    }

    private function crearClaseLimpia(string $fecha = '2026-10-28', ?string $serieId = null): Clase
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupoPatin->id,
            'fecha' => $fecha,
            'hora_inicio' => '17:00:00',
            'hora_fin' => '18:00:00',
            'cancelada' => false,
            'validada_para_liquidacion' => false,
            'serie_id' => $serieId,
        ]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    private function crearPago(Alumno $alumno, string $estado = 'COMPLETADO', float $monto = 15000): Pago
    {
        return Pago::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->planPatin->id,
            'mes' => 10,
            'anio' => 2026,
            'monto_base' => $monto,
            'porcentaje_aplicado' => 100,
            'monto_final' => $monto,
            'fecha_pago' => '2026-10-05',
            'medio_pago' => 'EFECTIVO',
            'estado' => $estado,
        ]);
    }

    // ── 1. Roles: Operativo y Profesor reciben 403 al intentar eliminar alumno ──
    public function test_operativo_y_profesor_reciben_403_al_intentar_eliminar_alumno(): void
    {
        $alumno = $this->crearAlumnoLimpio();

        $this->actingAs($this->operativo)
            ->delete(route('web.alumnos.destroy', $alumno->id))
            ->assertStatus(403);

        $this->actingAs($this->profesorUser)
            ->delete(route('web.alumnos.destroy', $alumno->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    // ── 2. Roles: Operativo y Profesor reciben 403 al intentar eliminar clase ──
    public function test_operativo_y_profesor_reciben_403_al_intentar_eliminar_clase(): void
    {
        $clase = $this->crearClaseLimpia();

        $this->actingAs($this->operativo)
            ->delete(route('web.clases.destroy', $clase->id))
            ->assertStatus(403);

        $this->actingAs($this->profesorUser)
            ->delete(route('web.clases.destroy', $clase->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('clases', ['id' => $clase->id]);
    }

    // ── 3. Dueño elimina alumno sin cobros ni asistencias: limpia todo y libera DNI ──
    public function test_duenio_elimina_alumno_sin_cobros_ni_asistencias_limpia_deuda_e_inscripcion_y_libera_dni(): void
    {
        $alumno = $this->crearAlumnoLimpio('40123456');

        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno->id));

        $response->assertRedirect(route('web.alumnos.index'));
        $response->assertSessionHas('success', 'Alumno eliminado correctamente.');

        // Se elimina el alumno
        $this->assertDatabaseMissing('alumnos', ['id' => $alumno->id]);
        // Se eliminan planes del alumno
        $this->assertDatabaseMissing('alumno_planes', ['alumno_id' => $alumno->id]);
        // Se elimina la cuota de alta sin pagar
        $this->assertDatabaseMissing('deuda_cuotas', ['alumno_id' => $alumno->id]);
        // Se elimina el cargo de inscripción no cobrado
        $this->assertDatabaseMissing('cargos_alumno', ['alumno_id' => $alumno->id]);
        // Se libera el DNI: se puede volver a crear un alumno con el mismo DNI en el mismo deporte
        $nuevo = $this->crearAlumnoLimpio('40123456');
        $this->assertDatabaseHas('alumnos', ['id' => $nuevo->id, 'dni' => '40123456']);
    }

    // ── 4. Alumno con pago (incluso anulado) no se elimina: explica y ofrece dar de baja ──
    public function test_alumno_con_pago_no_se_elimina_explica_por_que_y_ofrece_darlo_de_baja(): void
    {
        $alumno = $this->crearAlumnoLimpio();
        $pago = $this->crearPago($alumno);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno->id));

        $response->assertRedirect(route('web.alumnos.show', $alumno->id));
        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertStringContainsString('pagos', strtolower($error));
        $this->assertStringContainsString('inactivo', strtolower($error));

        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    // ── 5. Alumno con pago anulado tampoco se elimina ──
    public function test_alumno_con_pago_anulado_tampoco_se_elimina(): void
    {
        $alumno = $this->crearAlumnoLimpio();
        $this->crearPago($alumno, 'ANULADO');

        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno->id));

        $response->assertRedirect(route('web.alumnos.show', $alumno->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    // ── 6. Alumno con asistencia (presente o ausente) no se elimina ──
    public function test_alumno_con_asistencia_presente_o_ausente_no_se_elimina(): void
    {
        $alumno = $this->crearAlumnoLimpio();
        $clase = $this->crearClaseLimpia();

        // Asistencia ausente (presente = false)
        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno->id));

        $response->assertRedirect(route('web.alumnos.show', $alumno->id));
        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertStringContainsString('asistencia', strtolower($error));
        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    // ── 7. Alumno con movimiento de caja no se elimina ──
    public function test_alumno_con_movimiento_de_caja_no_se_elimina(): void
    {
        $alumno = $this->crearAlumnoLimpio();
        $tipoCajaId = DB::table('tipos_caja')->insertGetId([
            'nombre' => 'Efectivo',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cajaId = DB::table('cajas_operativas')->insertGetId([
            'usuario_operativo_id' => $this->operativo->id,
            'estado' => 'ABIERTA',
            'apertura_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subrubro = Subrubro::where('nombre', 'Inscripción al club')->first();
        DB::table('movimientos_operativos')->insert([
            'caja_operativa_id' => $cajaId,
            'tipo_caja_id' => $tipoCajaId,
            'subrubro_id' => $subrubro->id,
            'usuario_id' => $this->operativo->id,
            'alumno_id' => $alumno->id,
            'fecha' => today(),
            'monto' => 5000,
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno->id));

        $response->assertRedirect(route('web.alumnos.show', $alumno->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    // ── 8. Alumno con cuota con pago parcial o condonada no se elimina ──
    public function test_alumno_con_cuota_con_pago_o_condonada_no_se_elimina(): void
    {
        $alumno = $this->crearAlumnoLimpio();
        $cuota = $alumno->deudaCuotas()->first();
        $cuota->update([
            'estado' => DeudaCuota::ESTADO_CONDONADA,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno->id));

        $response->assertRedirect(route('web.alumnos.show', $alumno->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    // ── 9. Alumno con inscripción cobrada no se elimina ──
    public function test_alumno_con_inscripcion_cobrada_no_se_elimina(): void
    {
        $alumno = $this->crearAlumnoLimpio();
        $cargo = CargoAlumno::where('alumno_id', $alumno->id)->first();
        $pago = $this->crearPago($alumno, 'COMPLETADO', 5000);
        DB::table('pago_cargo_alumno')->insert([
            'pago_id' => $pago->id,
            'cargo_alumno_id' => $cargo->id,
            'monto_aplicado' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno->id));

        $response->assertRedirect(route('web.alumnos.show', $alumno->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    // ── 10. Alumno en segundo deporte con inscripción compartida: se elimina sin tocar la inscripción del otro deporte ──
    public function test_alumno_en_segundo_deporte_con_inscripcion_compartida_se_elimina_sin_tocar_la_inscripcion_del_otro_deporte(): void
    {
        $dni = '41999888';
        // Primer deporte: Patin (crea el cargo de inscripcion)
        $alumno1 = $this->crearAlumnoLimpio($dni, $this->patin, $this->grupoPatin);
        $cargoOriginal = CargoAlumno::where('clave_origen', 'inscripcion:dni:' . $dni)->first();
        $this->assertNotNull($cargoOriginal);
        $this->assertEquals($alumno1->id, $cargoOriginal->alumno_id);

        // Segundo deporte: Futbol (mismo DNI, cargado por error en futbol)
        $alumno2 = Alumno::create([
            'nombre' => 'Tomas',
            'apellido' => 'Prueba',
            'dni' => $dni,
            'fecha_nacimiento' => '2005-05-15',
            'celular' => '1155556666',
            'deporte_id' => $this->futbol->id,
            'grupo_id' => $this->grupoFutbol->id,
            'fecha_alta' => '2026-10-15',
            'activo' => true,
        ]);
        DeudaCuota::create([
            'alumno_id' => $alumno2->id,
            'periodo' => '2026-10',
            'monto_original' => 12000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Se elimina alumno2 (el de Futbol cargado por error)
        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno2->id));

        $response->assertRedirect(route('web.alumnos.index'));
        $this->assertDatabaseMissing('alumnos', ['id' => $alumno2->id]);
        $this->assertDatabaseMissing('deuda_cuotas', ['alumno_id' => $alumno2->id]);

        // La inscripción compartida y alumno1 siguen existiendo intactos
        $this->assertDatabaseHas('alumnos', ['id' => $alumno1->id]);
        $this->assertDatabaseHas('cargos_alumno', ['id' => $cargoOriginal->id, 'alumno_id' => $alumno1->id]);
    }

    /**
     * T65 Devolución H1: Recargo impago con otro alumno del mismo DNI.
     * El recargo no se transfiere al otro alumno y la inscripción compartida se conserva.
     */
    public function test_recargo_impago_con_otro_alumno_mismo_dni_no_se_transfiere_y_conserva_inscripcion(): void
    {
        $dni = '44111222';
        $alumno1 = Alumno::create([
            'nombre' => 'Mateo',
            'apellido' => 'Doble',
            'dni' => $dni,
            'fecha_nacimiento' => '2008-03-10',
            'celular' => '1133334444',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'fecha_alta' => '2026-03-01',
            'activo' => true,
        ]);
        app(InscripcionService::class)->sincronizar($alumno1, $this->admin->id);
        $cargoInscripcion = CargoAlumno::where('dni', $dni)->where('tipo', 'INSCRIPCION')->sole();

        // Alumno 2 en otro deporte con mismo DNI
        $alumno2 = Alumno::create([
            'nombre' => 'Mateo',
            'apellido' => 'Doble',
            'dni' => $dni,
            'fecha_nacimiento' => '2008-03-10',
            'celular' => '1133334444',
            'deporte_id' => $this->futbol->id,
            'grupo_id' => $this->grupoFutbol->id,
            'fecha_alta' => '2026-10-15',
            'activo' => true,
        ]);
        $cuota2 = DeudaCuota::create([
            'alumno_id' => $alumno2->id,
            'periodo' => '2026-10',
            'monto_original' => 15000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Cargo de punitorio ligado a la cuota del alumno2
        $cargoPunitorio = CargoAlumno::create([
            'alumno_id' => $alumno2->id,
            'deuda_cuota_id' => $cuota2->id,
            'tipo' => 'PUNITORIO',
            'subrubro_id' => Subrubro::first()->id,
            'clave_origen' => 'PUNITORIO:cuota:' . $cuota2->id,
            'dni' => $dni,
            'monto_original' => 1500,
            'monto_condonado' => 0,
            'calculo' => ['porcentaje' => 10, 'base' => 15000],
            'estado' => 'VIGENTE',
        ]);
        DB::table('cargo_alumno_eventos')->insert([
            'cargo_alumno_id' => $cargoPunitorio->id,
            'accion' => 'CREACION',
            'motivo' => 'Mora cuota',
            'usuario_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        $this->assertTrue($alumno2->esEliminable());

        // Se elimina alumno2
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('web.alumnos.destroy', $alumno2->id));

        $response->assertOk();
        $this->assertDatabaseMissing('alumnos', ['id' => $alumno2->id]);
        $this->assertDatabaseMissing('deuda_cuotas', ['id' => $cuota2->id]);

        // El recargo de alumno2 se eliminó por completo: NO se transfirió a alumno1
        $this->assertDatabaseMissing('cargos_alumno', ['id' => $cargoPunitorio->id]);
        $this->assertDatabaseMissing('cargos_alumno', ['alumno_id' => $alumno1->id, 'tipo' => 'PUNITORIO']);
        $this->assertDatabaseMissing('cargo_alumno_eventos', ['cargo_alumno_id' => $cargoPunitorio->id]);

        // La inscripción compartida y alumno1 siguen intactos
        $this->assertDatabaseHas('alumnos', ['id' => $alumno1->id]);
        $this->assertDatabaseHas('cargos_alumno', [
            'id' => $cargoInscripcion->id,
            'tipo' => 'INSCRIPCION',
            'alumno_id' => $alumno1->id,
        ]);
    }

    // ── 11. Dueño elimina clase sin asistencias ni liquidaciones ──
    public function test_duenio_elimina_clase_sin_asistencias_ni_liquidaciones(): void
    {
        $clase = $this->crearClaseLimpia();
        $this->assertDatabaseHas('clase_profesor', ['clase_id' => $clase->id, 'profesor_id' => $this->profesor->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.clases.destroy', $clase->id));

        $response->assertRedirect(route('web.clases.index'));
        $response->assertSessionHas('success', 'Clase eliminada correctamente.');

        $this->assertDatabaseMissing('clases', ['id' => $clase->id]);
        $this->assertDatabaseMissing('clase_profesor', ['clase_id' => $clase->id]);
    }

    // ── 12. Clase con asistencia no se elimina: debe cancelarse con motivo ──
    public function test_clase_con_asistencia_no_se_elimina_debe_cancelarse(): void
    {
        $clase = $this->crearClaseLimpia();
        $alumno = $this->crearAlumnoLimpio();
        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.clases.destroy', $clase->id));

        $response->assertRedirect(route('web.clases.show', $clase->id));
        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertStringContainsString('asistencia', strtolower($error));
        $this->assertStringContainsString('cancelar', strtolower($error));

        $this->assertDatabaseHas('clases', ['id' => $clase->id]);
    }

    // ── 13. Clase en liquidación (abierta o cerrada) no se elimina ──
    public function test_clase_en_liquidacion_abierta_o_cerrada_no_se_elimina(): void
    {
        $clase = $this->crearClaseLimpia();

        $liq = Liquidacion::create([
            'profesor_id' => $this->profesor->id,
            'tipo' => 'HORA',
            'mes' => 10,
            'anio' => 2026,
            'total_calculado' => 12000,
            'total_final' => 12000,
            'estado' => Liquidacion::ESTADO_ABIERTA,
            'creada_por' => $this->admin->id,
        ]);

        LiquidacionDetalle::create([
            'liquidacion_id' => $liq->id,
            'tipo_referencia' => LiquidacionDetalle::TIPO_CLASE,
            'referencia_id' => $clase->id,
            'monto' => 12000,
            'descripcion' => 'Clase prueba',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.clases.destroy', $clase->id));

        $response->assertRedirect(route('web.clases.show', $clase->id));
        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertStringContainsString('liquidaci', strtolower($error));

        $this->assertDatabaseHas('clases', ['id' => $clase->id]);
    }

    // ── 14. Clase validada para liquidación no se elimina ──
    public function test_clase_validada_para_liquidacion_no_se_elimina(): void
    {
        $clase = $this->crearClaseLimpia();
        $clase->update(['validada_para_liquidacion' => true]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.clases.destroy', $clase->id));

        $response->assertRedirect(route('web.clases.show', $clase->id));
        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertStringContainsString('validada', strtolower($error));

        $this->assertDatabaseHas('clases', ['id' => $clase->id]);
    }

    // ── 15. Serie se elimina completa si todas las clases están limpias ──
    public function test_serie_se_elimina_completa_si_todas_estan_limpias(): void
    {
        $serieId = 'serie-uuid-12345';
        $c1 = $this->crearClaseLimpia('2026-11-03', $serieId);
        $c2 = $this->crearClaseLimpia('2026-11-10', $serieId);
        $c3 = $this->crearClaseLimpia('2026-11-17', $serieId);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.clases.destroy', $c1->id), ['eliminar_serie' => 1]);

        $response->assertRedirect(route('web.clases.index'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Serie de 3 clases eliminada', session('success'));

        $this->assertDatabaseMissing('clases', ['id' => $c1->id]);
        $this->assertDatabaseMissing('clases', ['id' => $c2->id]);
        $this->assertDatabaseMissing('clases', ['id' => $c3->id]);
        $this->assertDatabaseMissing('clase_profesor', ['clase_id' => $c1->id]);
        $this->assertDatabaseMissing('clase_profesor', ['clase_id' => $c2->id]);
        $this->assertDatabaseMissing('clase_profesor', ['clase_id' => $c3->id]);
    }

    // ── 16. Serie rechaza eliminación si alguna clase tiene asistencia o liquidación e indica cuál ──
    public function test_serie_rechaza_eliminacion_si_alguna_clase_tiene_asistencia_o_liquidacion_e_indica_cual(): void
    {
        $serieId = 'serie-uuid-67890';
        $c1 = $this->crearClaseLimpia('2026-11-03', $serieId);
        $c2 = $this->crearClaseLimpia('2026-11-10', $serieId);
        $c3 = $this->crearClaseLimpia('2026-11-17', $serieId);

        $alumno = $this->crearAlumnoLimpio();
        // Ponemos asistencia en c2
        Asistencia::create([
            'clase_id' => $c2->id,
            'alumno_id' => $alumno->id,
            'presente' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.clases.destroy', $c1->id), ['eliminar_serie' => 1]);

        $response->assertRedirect(route('web.clases.show', $c1->id));
        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertStringContainsString('10/11/2026', $error);
        $this->assertStringContainsString('asistencias', strtolower($error));

        // Ninguna clase de la serie fue eliminada
        $this->assertDatabaseHas('clases', ['id' => $c1->id]);
        $this->assertDatabaseHas('clases', ['id' => $c2->id]);
        $this->assertDatabaseHas('clases', ['id' => $c3->id]);
    }

    // ── 17. Concurrencia: un cobro entrante durante la transacción rechaza el borrado ──
    public function test_concurrencia_cobro_entrante_durante_intento_de_eliminacion_rechaza_el_borrado(): void
    {
        $alumno = $this->crearAlumnoLimpio();

        // Simulamos que durante la transacción bajo lock, se detecta un pago recién registrado
        $this->crearPago($alumno);

        $response = $this->actingAs($this->admin)
            ->delete(route('web.alumnos.destroy', $alumno->id));

        $response->assertRedirect(route('web.alumnos.show', $alumno->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    // ── 18. Pantallas: botón Eliminar visible solo para admin y solo si es eliminable ──
    public function test_pantalla_ficha_alumno_y_clase_muestran_boton_eliminar_solo_para_admin_y_cuando_es_eliminable(): void
    {
        $alumnoLimpio = $this->crearAlumnoLimpio('40888777');
        $claseLimpia = $this->crearClaseLimpia('2026-11-05');

        // Admin ve el botón Eliminar en alumno limpio y clase limpia
        $resAlumnoAdmin = $this->actingAs($this->admin)->get(route('web.alumnos.show', $alumnoLimpio->id));
        $resAlumnoAdmin->assertOk();
        $resAlumnoAdmin->assertSee('id="btn-abrir-eliminar-alumno"', false);

        $resClaseAdmin = $this->actingAs($this->admin)->get(route('web.clases.show', $claseLimpia->id));
        $resClaseAdmin->assertOk();
        $resClaseAdmin->assertSee('id="btn-abrir-eliminar-clase"', false);

        // Operativo NO ve el botón Eliminar en ninguna
        $resAlumnoOp = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $alumnoLimpio->id));
        $resAlumnoOp->assertOk();
        $resAlumnoOp->assertDontSee('id="btn-abrir-eliminar-alumno"', false);

        $resClaseOp = $this->actingAs($this->operativo)->get(route('web.clases.show', $claseLimpia->id));
        $resClaseOp->assertOk();
        $resClaseOp->assertDontSee('id="btn-abrir-eliminar-clase"', false);

        // Si el alumno tiene pago, Admin ya NO ve el botón Eliminar
        $this->crearPago($alumnoLimpio);
        $resAlumnoConPago = $this->actingAs($this->admin)->get(route('web.alumnos.show', $alumnoLimpio->id));
        $resAlumnoConPago->assertOk();
        $resAlumnoConPago->assertDontSee('id="btn-abrir-eliminar-alumno"', false);

        // Si la clase tiene asistencia, Admin ya NO ve el botón Eliminar
        Asistencia::create([
            'clase_id' => $claseLimpia->id,
            'alumno_id' => $alumnoLimpio->id,
            'presente' => true,
        ]);
        $resClaseConAsistencia = $this->actingAs($this->admin)->get(route('web.clases.show', $claseLimpia->id));
        $resClaseConAsistencia->assertOk();
        $resClaseConAsistencia->assertDontSee('id="btn-abrir-eliminar-clase"', false);
    }
}
