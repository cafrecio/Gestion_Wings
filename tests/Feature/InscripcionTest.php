<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CargoAlumno, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, TipoCaja, User};
use App\Services\{CobranzaEstadoService, InscripcionService, PagoCuotaService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InscripcionTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-24 12:00:00');
        $this->seed(CatalogosSeeder::class);
        \App\Models\ReglaPrimerPago::query()->delete(); // Aislar inscripción del descuento de primer mes.
        $this->usuario = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($this->usuario);
        $this->grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function datos(string $fecha = '2026-09-23', string $dni = '41000111'): array
    {
        return ['nombre' => 'Prueba', 'apellido' => 'Inscripcion', 'dni' => $dni,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111', 'fecha_alta' => $fecha,
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'plan_id' => $this->plan->id];
    }

    private function alta(string $fecha = '2026-09-23', string $dni = '41000111'): Alumno
    {
        $this->post(route('web.alumnos.store'), $this->datos($fecha, $dni))->assertSessionHasNoErrors();
        return Alumno::where('dni', $dni)->firstOrFail();
    }

    private function cobrar(Alumno $alumno, int $importe): array
    {
        DeudaCuota::firstOrCreate(['alumno_id' => $alumno->id, 'periodo' => '2026-09'], ['monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        return app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno->id, 'tipo_caja_id' => TipoCaja::first()->id,
            'usuario_operativo_id' => $this->usuario->id, 'fecha_pago' => '2026-09-24',
            'monto_entregado' => $importe, 'items' => [['periodo' => '2026-09', 'monto' => 30000]],
        ]);
    }

    public function test_alta_manual_genera_inscripcion_sin_corte_incluso_con_ingreso_antiguo(): void
    {
        foreach (['2026-09-22', '2026-09-23', '2026-09-24', '2020-01-01'] as $i => $fecha) {
            $datos = $this->datos($fecha, '4100011'.$i);
            // A43 exige decidir cuota para mes cerrado; No conserva inscripción.
            if (Carbon::parse($fecha)->startOfMonth()->lt(now()->startOfMonth())) $datos['generar_cuota_actual'] = '0';
            $this->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors();
            $alumno = Alumno::where('dni', $datos['dni'])->firstOrFail();
            $this->assertSame(1, DB::table('cargos_alumno')->where('alumno_id', $alumno->id)->count());
        }
        $this->assertDatabaseHas('cargos_alumno', ['monto_original' => 5000]);
    }

    public function test_dos_deportes_no_duplican_inscripcion(): void
    {
        $this->alta();
        $this->grupo = Grupo::create(['deporte_id' => Deporte::whereKeyNot($this->grupo->deporte_id)->first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->post(route('web.alumnos.store'), $this->datos())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('alumnos', 2);
        $this->assertDatabaseCount('cargos_alumno', 1);
        $this->assertDatabaseHas('cargos_alumno', ['clave_origen' => 'inscripcion:dni:41000111']);
        $segundo = Alumno::latest('id')->firstOrFail();
        $this->postJson(route('web.caja.pagar', $segundo), [
            'tipo_caja_id' => TipoCaja::first()->id, 'monto_entregado' => 5000,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(0, CargoAlumno::first()->saldo_pendiente);
        $this->assertEquals(0, Pago::first()->monto_cuota);
        $this->assertEquals($segundo->id, Pago::first()->alumno_id);
    }

    public function test_configuracion_invalida_no_deja_alta_parcial(): void
    {
        Configuracion::where('clave', 'inscripcion_importe')->delete();
        $this->post(route('web.alumnos.store'), $this->datos())->assertSessionHasErrors();
        $this->assertDatabaseCount('alumnos', 0);
        $this->assertDatabaseCount('alumno_planes', 0);
    }

    public function test_reintento_no_duplica_y_precio_queda_congelado(): void
    {
        $datos = $this->datos() + ['alta_token' => 'c15bf1da-f920-45f8-89ce-ac64cb482eb1'];
        $this->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors();
        Configuracion::set('inscripcion_importe', '9000');
        $this->post(route('web.alumnos.store'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('alumnos', 1);
        $this->assertDatabaseCount('cargos_alumno', 1);
        $this->assertDatabaseHas('cargos_alumno', ['monto_original' => 5000]);
    }

    public function test_fallo_durante_el_cargo_revierte_alumno_y_plan(): void
    {
        $this->partialMock(InscripcionService::class, function ($mock) {
            $mock->shouldReceive('sincronizar')->once()->andThrow(new \RuntimeException('Fallo controlado ENT-01'));
        });
        $this->withoutExceptionHandling();
        try {
            $this->post(route('web.alumnos.store'), $this->datos());
            $this->fail('Debió fallar el alta');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo controlado ENT-01', $e->getMessage());
        }
        $this->assertDatabaseCount('alumnos', 0);
        $this->assertDatabaseCount('alumno_planes', 0);
    }

    public function test_cobro_completo_separa_conceptos_y_caja(): void
    {
        $alumno = $this->alta();
        $resultado = $this->cobrar($alumno, 35000);
        $pago = $resultado['pago'];
        $this->assertEquals(35000, $pago->monto_final);
        $this->assertEquals(30000, $pago->monto_cuota);
        $this->assertEquals(5000, DB::table('pago_cargo_alumno')->sum('monto_aplicado'));
        $this->assertEquals(35000, DB::table('movimientos_operativos')->where('pago_id', $pago->id)->sum('monto'));
        $this->assertSame(2, DB::table('movimientos_operativos')->where('pago_id', $pago->id)->count());
    }

    public function test_pago_insuficiente_cubre_inscripcion_primero_y_no_cambia_estado_mensual(): void
    {
        $alumno = $this->alta();
        $pago = $this->cobrar($alumno, 3000)['pago'];
        $this->assertEquals(3000, $pago->monto_final);
        $this->assertEquals(0, $pago->monto_cuota);
        $this->assertEquals(0, DeudaCuota::first()->monto_pagado);
        $this->assertEquals(2000, CargoAlumno::first()->saldo_pendiente);
        // Enmienda 23/09: solo debe el mes vigente, pasada la gracia.
        $this->assertSame('MOROSO', app(CobranzaEstadoService::class)->estadoAlumno($alumno->id)['estado']);
        $this->cobrar($alumno, 7000);
        $this->assertEquals(5000, DeudaCuota::first()->monto_pagado);
        $this->assertEquals(0, CargoAlumno::first()->saldo_pendiente);
    }

    public function test_editar_fecha_conserva_inscripcion_e_importe_con_auditoria(): void
    {
        $alumno = $this->alta('2026-09-22');
        $cargo = CargoAlumno::firstOrFail();
        $this->put(route('web.alumnos.update', $alumno), $this->datos('2020-01-20') + ['motivo_fecha_alta' => 'Corrección de ingreso real'])->assertSessionHasNoErrors();
        $this->assertSame('VIGENTE', $cargo->fresh()->estado);
        $this->assertEquals(5000, $cargo->fresh()->monto_original);
        $this->assertDatabaseHas('cargo_alumno_eventos', ['cargo_alumno_id' => $cargo->id, 'usuario_id' => $this->usuario->id, 'motivo' => 'Corrección de ingreso real']);
        $this->assertDatabaseCount('cargos_alumno', 1);
    }

    public function test_no_se_edita_fecha_con_inscripcion_parcialmente_pagada(): void
    {
        $alumno = $this->alta();
        $this->cobrar($alumno, 1000);
        $this->put(route('web.alumnos.update', $alumno), $this->datos('2026-09-22') + ['motivo_fecha_alta' => 'Corrección de ingreso'])->assertSessionHasErrors('fecha_alta');
        $this->assertSame('2026-09-23', $alumno->fresh()->fecha_alta->format('Y-m-d'));
    }

    public function test_cancelar_restituye_cuota_inscripcion_y_todos_los_movimientos(): void
    {
        $alumno = $this->alta();
        $resultado = $this->cobrar($alumno, 35000);
        app(PagoCuotaService::class)->cancelarCobroOperativo($resultado['movimiento']->id, 'Prueba de cancelación completa', $this->usuario->id);
        $this->assertEquals(0, DeudaCuota::first()->monto_pagado);
        $this->assertEquals(5000, CargoAlumno::first()->saldo_pendiente);
        $this->assertSame(0, DB::table('movimientos_operativos')->where('estado', 'ACTIVO')->count());
        $this->assertDatabaseCount('pago_cargo_alumno', 0);
    }

    public function test_importe_inscripcion_obligatorio_sin_parametro_corte(): void
    {
        $this->assertDatabaseMissing('configuraciones', ['clave' => 'inscripcion_fecha_corte']);
        $this->patch('/configuraciones/inscripcion_importe', ['valor' => ''])->assertSessionHasErrors();
        $this->get(route('web.configuraciones.index'))->assertOk()->assertDontSee('inscripcion_fecha_corte');
        $this->getJson(route('web.alumnos.inscripcion-preview', ['dni' => '41000111', 'fecha_alta' => '2020-01-20']))
            ->assertOk()->assertJsonPath('importe', 5000)->assertJsonPath('mensaje', 'Corresponde inscripción por única vez: $5.000,00');
    }

    public function test_retirar_parametro_legacy_no_altera_cargos_ni_importe(): void
    {
        $this->alta();
        $cargo = CargoAlumno::firstOrFail();
        Configuracion::set('inscripcion_fecha_corte', '2026-09-23');
        $migration = require database_path('migrations/2026_10_04_000000_remove_inscripcion_fecha_corte.php');
        $migration->up();
        $this->assertDatabaseMissing('configuraciones', ['clave' => 'inscripcion_fecha_corte']);
        $this->assertEquals('5000.00', Configuracion::get('inscripcion_importe'));
        $this->assertEquals(5000, $cargo->fresh()->monto_original);
        $this->assertDatabaseCount('cargos_alumno', 1);
        $this->assertDatabaseCount('deuda_cuotas', 1);
    }

    public function test_editar_alumno_existente_sin_cargo_no_crea_inscripcion(): void
    {
        $alumno = Alumno::create(collect($this->datos('2020-01-20'))->except('plan_id')->all());
        $this->put(route('web.alumnos.update', $alumno), $this->datos('2021-02-05') + ['motivo_fecha_alta' => 'Corrección de ingreso'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cargos_alumno', 0);
        $this->assertDatabaseCount('deuda_cuotas', 0);
    }

    public function test_recibo_pdf_contiene_cuota_e_inscripcion_y_conserva_detalle_anulado(): void
    {
        \Illuminate\Support\Facades\Storage::fake();
        $pago = $this->cobrar($this->alta(), 35000);
        $conceptos = [];
        \Illuminate\Support\Facades\View::composer('pdfs.recibo-cuota', function ($view) use (&$conceptos) {
            $conceptos = $view->getData()['periodos'];
        });
        $recibos = app(\App\Services\ReciboService::class);
        $ruta = $recibos->generarReciboCuota($pago['pago']->id, true);
        $this->assertStringStartsWith('%PDF', \Illuminate\Support\Facades\Storage::get($ruta));
        $this->assertCount(2, $conceptos);
        $this->assertStringContainsString('Inscripción', $conceptos[1]['periodo_texto']);
        $this->assertEquals(35000, array_sum(array_column($conceptos, 'monto_aplicado')));
        app(PagoCuotaService::class)->cancelarCobroOperativo($pago['movimiento']->id, 'Anulación con detalle', $this->usuario->id);
        $recibos->generarReciboCuota($pago['pago']->id, true);
        $this->assertCount(2, $conceptos);
        $this->assertStringContainsString('Inscripción', $conceptos[1]['periodo_texto']);
    }

    public function test_el_descuento_solo_alcanza_la_cuota(): void
    {
        \App\Models\ReglaPrimerPago::create(['nombre' => 'Descuento prueba', 'dia_desde' => 1, 'dia_hasta' => 31, 'porcentaje' => 70, 'activo' => true]);
        $alumno = $this->alta();
        $pago = $this->cobrar($alumno, 26000)['pago'];
        $this->assertEquals(21000, $pago->monto_cuota);
        $this->assertEquals(5000, $pago->cargos()->sum('monto_aplicado'));
        $this->assertEquals(30000, $pago->monto_base);
    }

    public function test_pagos_historicos_conservan_su_importe_de_cuota(): void
    {
        $alumno = Alumno::create(collect($this->datos('2020-01-01'))->except('plan_id')->all());
        $pago = Pago::create(['alumno_id' => $alumno->id, 'mes' => 9, 'anio' => 2026, 'fecha_pago' => '2026-09-24',
            'monto_base' => 30000, 'monto_final' => 30000, 'porcentaje_aplicado' => 100, 'estado' => 'COMPLETADO']);
        $this->assertEquals(30000, $pago->fresh()->monto_cuota);
        $this->assertTrue(Pago::conCuota()->whereKey($pago->id)->exists());
        $this->assertDatabaseCount('cargos_alumno', 0);
    }

    public function test_configuracion_cambiada_no_cobra_otro_importe_sin_avisar(): void
    {
        Configuracion::set('inscripcion_importe', 9000);
        $this->post(route('web.alumnos.store'), $this->datos() + ['inscripcion_importe_visto' => 5000])->assertSessionHasErrors();
        $this->assertDatabaseCount('alumnos', 0);
    }
}
