<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CargoAlumno, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, Subrubro, TipoCaja, User};
use App\Services\{CobranzaEstadoService, InscripcionService, ReporteMensualService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verificación de Claude sobre T33 (Codex). Vive fuera de tests/: no es parte de la suite.
 *
 *   DB_DATABASE=wings_testing_claude php vendor/bin/phpunit --configuration phpunit.xml \
 *     docs/06-pruebas/PRU-04/evidencia-t33/VerificacionT33ClaudeTest.php
 */
class VerificacionT33ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesor;
    private Alumno $alumno;
    private TipoCaja $efectivo;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 10:00:00');
        $this->seed(CatalogosSeeder::class);
        Configuracion::set('dias_gracia_cobranza', 10);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->profesor = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $deporte = Deporte::where('nombre', 'Patín')->firstOrFail();
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::firstOrCreate(['nombre' => 'Inicial'])->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Becada', 'dni' => '41000001', 'fecha_nacimiento' => '2010-01-01',
            'fecha_alta' => '2026-03-15', 'celular' => '1140000000', 'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-03-15', 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function inscripcion(): CargoAlumno
    {
        return CargoAlumno::create(['alumno_id' => $this->alumno->id, 'tipo' => 'INSCRIPCION', 'dni' => InscripcionService::dni($this->alumno->dni),
            'clave_origen' => 'inscripcion:dni:'.InscripcionService::dni($this->alumno->dni),
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => 5000, 'monto_condonado' => 0, 'calculo' => ['fecha_ingreso' => '2026-03-15'], 'estado' => 'VIGENTE']);
    }

    private function cuota(string $periodo): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => $periodo, 'monto_original' => 30000,
            'monto_pagado' => 0, 'monto_condonado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }

    private function deudaEnPantallas(): array
    {
        return [
            (int) round(app(CobranzaEstadoService::class)->resumenDashboard()['total_adeudado'] * 100),
            app(ReporteMensualService::class)->obtener('2026-10')['deuda']['total'],
            $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->viewData('reporte')['deuda']['total'],
        ];
    }

    /** El motivo de T33: con T31, quien debe la inscripción es deudor; el becado no puede quedar así. */
    public function test_el_becado_deja_de_ser_deudor_al_perdonarle_la_inscripcion_y_vuelve_si_se_reactiva(): void
    {
        $cargo = $this->inscripcion();
        $estado = fn () => app(CobranzaEstadoService::class)->estadoAlumno($this->alumno->id)['estado'];
        $this->assertSame('DEUDOR', $estado(), 'Necesita T31 en la carpeta: debe la inscripción desde marzo.');
        $this->assertSame([500000, 500000, 500000], $this->deudaEnPantallas());

        $this->actingAs($this->admin)->post(route('web.inscripciones.condonar', $cargo->id), ['motivo' => 'Beca completa del club'])
            ->assertSessionHas('success');
        $this->assertSame('AL_DIA', $estado());
        $this->assertSame([0, 0, 0], $this->deudaEnPantallas());
        $this->assertSame(0, Pago::count());
        $this->assertSame(0, DB::table('cashflow_movimientos')->count());

        $this->actingAs($this->admin)->post(route('web.inscripciones.reactivar', $cargo->id), ['motivo' => 'Se perdono por error'])
            ->assertSessionHas('success');
        $this->assertSame('DEUDOR', $estado());
        $this->assertSame([500000, 500000, 500000], $this->deudaEnPantallas());

        $acciones = DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $cargo->id)->orderBy('id')->pluck('accion')->all();
        $this->assertSame(['CONDONAR', 'REACTIVAR'], $acciones, 'Quedan las dos decisiones en el historial.');
    }

    public function test_solo_el_duenio_perdona_y_reactiva(): void
    {
        $cargo = $this->inscripcion();
        $cuota = $this->cuota('2026-09');
        $rutas = [route('web.inscripciones.condonar', $cargo->id), route('web.deudas.condonar', $cuota->id)];
        foreach ([$this->operativo, $this->profesor] as $usuario) {
            foreach ($rutas as $ruta) {
                $respuesta = $this->actingAs($usuario)->post($ruta, ['motivo' => 'Intento sin permiso suficiente']);
                $this->assertContains($respuesta->status(), [302, 403], $usuario->rol.' '.$ruta);
                $respuesta->assertSessionMissing('success');
            }
        }
        $this->assertEquals(0.0, (float) $cargo->fresh()->monto_condonado);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $cuota->fresh()->estado);

        foreach ([$this->operativo, $this->profesor] as $usuario) {
            if ($usuario->isProfesor()) continue; // el profesor no entra a fichas
            $this->actingAs($usuario)->get(route('web.alumnos.show', $this->alumno->id))->assertOk()
                ->assertDontSee(route('web.inscripciones.condonar', $cargo->id))
                ->assertDontSee(route('web.deudas.condonar', $cuota->id));
        }
    }

    public function test_cuota_perdonada_no_se_cobra_y_reactivada_si(): void
    {
        $cuota = $this->cuota('2026-09');
        $this->actingAs($this->admin)->post(route('web.deudas.condonar', $cuota->id), ['motivo' => 'Perdonada por error de dedo']);
        $this->assertSame(DeudaCuota::ESTADO_CONDONADA, $cuota->fresh()->estado);

        $cobrar = fn () => $this->actingAs($this->admin)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-09'], 'montos_cuota' => ['2026-09' => 30000]]);
        $cobrar();
        $this->assertSame(0, Pago::count(), 'Una cuota perdonada no se cobra.');

        $this->actingAs($this->admin)->get(route('web.alumnos.show', $this->alumno->id))->assertOk()
            ->assertSee(route('web.deudas.reactivar', $cuota->id));
        $this->actingAs($this->admin)->post(route('web.deudas.reactivar', $cuota->id), ['motivo' => 'No correspondia perdonarla'])
            ->assertSessionHas('success');
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $cuota->fresh()->estado);
        $this->assertStringContainsString('CONDONADA', $cuota->fresh()->observaciones);
        $this->assertStringContainsString('REACTIVADA', $cuota->fresh()->observaciones);

        $cobrar()->assertSessionHas('success');
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $cuota->fresh()->estado);
        $this->assertSame(1, Pago::count());
    }

    public function test_los_rechazos_salen_en_castellano_y_no_escriben(): void
    {
        $cargo = $this->inscripcion();
        $casos = [
            'motivo corto' => [route('web.inscripciones.condonar', $cargo->id), ['motivo' => 'corto']],
            'reactivar sin perdon' => [route('web.inscripciones.reactivar', $cargo->id), ['motivo' => 'No hay nada que reactivar']],
            'inscripcion inexistente' => [route('web.inscripciones.condonar', 999999), ['motivo' => 'El cargo no existe en la base']],
            'cuota inexistente' => [route('web.deudas.reactivar', 999999), ['motivo' => 'La cuota no existe en la base']],
        ];
        foreach ($casos as $nombre => [$ruta, $datos]) {
            $respuesta = $this->actingAs($this->admin)->from(route('web.alumnos.show', $this->alumno->id))->post($ruta, $datos);
            $this->assertContains($respuesta->status(), [302, 404], $nombre);
            $mensaje = (string) session('error');
            $this->assertStringNotContainsString('No query results', $mensaje, "$nombre: el aviso sale en inglés.");
            $this->assertStringNotContainsString('App\\Models', $mensaje, "$nombre: el aviso muestra nombres internos.");
        }
        $this->assertEquals(0.0, (float) $cargo->fresh()->monto_condonado);
        $this->assertSame(0, DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $cargo->id)->count());
    }

    public function test_perdonar_dos_veces_no_duplica(): void
    {
        $cargo = $this->inscripcion();
        $this->actingAs($this->admin)->post(route('web.inscripciones.condonar', $cargo->id), ['motivo' => 'Beca completa del club']);
        $this->actingAs($this->admin)->post(route('web.inscripciones.condonar', $cargo->id), ['motivo' => 'Beca completa otra vez'])
            ->assertSessionHas('error');
        $this->assertEquals(5000.0, (float) $cargo->fresh()->monto_condonado);
        $this->assertSame(1, DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $cargo->id)->count());
    }
}
