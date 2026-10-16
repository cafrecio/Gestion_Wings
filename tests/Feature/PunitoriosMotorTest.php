<?php

namespace Tests\Feature;

use App\Models\{Alumno, CargoAlumno, Configuracion, Deporte, DeudaCuota, Grupo, Nivel, User};
use App\Services\PunitorioService;
use App\Support\RelojSimulado;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PunitoriosMotorTest extends TestCase
{
    use RefreshDatabase;

    private Grupo $grupo;
    private int $dni = 47000000;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-11 09:00:00');
        $deporte = Deporte::create(['nombre' => 'Deporte de prueba mora', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel de prueba mora']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        RelojSimulado::aplicar(null, 'testing');
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function alumno(string $alta = '2025-02-01', bool $activo = true): Alumno
    {
        return Alumno::create(['nombre' => 'Alumno de prueba', 'apellido' => 'Mora', 'dni' => (string) ++$this->dni,
            'fecha_nacimiento' => '2005-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => $alta,
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => $activo]);
    }

    private function cuota(Alumno $alumno, string $periodo = '2026-10', string $original = '10000.00'): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => $periodo,
            'monto_original' => $original, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    }

    public function test_migracion_crea_configuraciones_y_catalogo_reservado(): void
    {
        $this->assertSame(10, Configuracion::get('mora_dia_desde'));
        $this->assertSame('0', Configuracion::get('mora_porcentaje'));
        $this->assertDatabaseHas('configuraciones', ['clave' => 'mora_porcentaje', 'tipo' => 'string']);
        $this->assertDatabaseHas('rubros', ['nombre' => 'Punitorios', 'tipo' => 'INGRESO', 'es_reservado_sistema' => true]);
        $this->assertDatabaseHas('subrubros', ['nombre' => 'Punitorio Cuota', 'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true, 'es_reservado_sistema' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
    }

    public function test_porcentaje_cero_no_escribe_cargos_eventos_ni_modifica_cuotas(): void
    {
        $cuota = $this->cuota($this->alumno());
        $antes = $cuota->fresh()->getAttributes();
        $this->assertSame(0, app(PunitorioService::class)->aplicar());
        $this->assertDatabaseCount('cargos_alumno', 0);
        $this->assertDatabaseCount('cargo_alumno_eventos', 0);
        $this->assertSame($antes, $cuota->fresh()->getAttributes());
    }

    public function test_no_aplica_el_dia_configurado_y_aplica_una_vez_al_dia_siguiente(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $cuota = $this->cuota($this->alumno());
        Carbon::setTestNow('2026-10-10 23:59:00');
        $this->assertSame(0, app(PunitorioService::class)->aplicar());
        Carbon::setTestNow('2026-10-11 00:00:00');
        $this->assertSame(1, app(PunitorioService::class)->aplicar());
        $this->assertSame(0, app(PunitorioService::class)->aplicar());
        $cargo = CargoAlumno::sole();
        $this->assertSame('punitorio:deuda:'.$cuota->id, $cargo->clave_origen);
        $this->assertSame('500.00', $cargo->monto_original);
        $this->assertSame(['porcentaje' => '5.00', 'base' => '10000.00',
            'fecha_aplicacion' => '2026-10-11', 'periodo' => '2026-10'], $cargo->calculo);
        $this->assertDatabaseCount('cargo_alumno_eventos', 1);
    }

    public function test_subir_porcentaje_o_apagarlo_no_recalcula_ni_borra_el_cargo_aplicado(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $this->cuota($this->alumno());
        app(PunitorioService::class)->aplicar();
        $antes = CargoAlumno::sole()->getAttributes();
        foreach (['12,5', '0'] as $porcentaje) {
            Configuracion::set('mora_porcentaje', $porcentaje);
            Carbon::setTestNow('2026-10-20');
            $this->assertSame(0, app(PunitorioService::class)->aplicar());
            $this->assertSame($antes, CargoAlumno::sole()->getAttributes());
        }
    }

    public function test_prender_la_mora_no_aplica_recargos_a_meses_anteriores(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $alumno = $this->alumno();
        $vieja1 = $this->cuota($alumno, '2026-08');
        $vieja2 = $this->cuota($alumno, '2026-09');
        $actual = $this->cuota($alumno);
        $this->assertSame(1, app(PunitorioService::class)->aplicar());
        $this->assertDatabaseMissing('cargos_alumno', ['deuda_cuota_id' => $vieja1->id]);
        $this->assertDatabaseMissing('cargos_alumno', ['deuda_cuota_id' => $vieja2->id]);
        $this->assertDatabaseHas('cargos_alumno', ['deuda_cuota_id' => $actual->id, 'monto_original' => '500.00']);
    }

    public function test_tres_cuotas_reciben_cada_una_su_recargo_en_su_propio_mes(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $alumno = $this->alumno();
        foreach (['2026-10' => ['10000.00', '500.00'], '2026-11' => ['20000.00', '1000.00'],
            '2026-12' => ['30000.00', '1500.00']] as $mes => [$base, $recargo]) {
            Carbon::setTestNow($mes.'-11');
            $cuota = $this->cuota($alumno, $mes, $base);
            $this->assertSame(1, app(PunitorioService::class)->aplicar());
            $this->assertDatabaseHas('cargos_alumno', ['deuda_cuota_id' => $cuota->id, 'monto_original' => $recargo]);
        }
        $this->assertDatabaseCount('cargos_alumno', 3);
        $this->assertSame(0, app(PunitorioService::class)->aplicar());
    }

    public function test_alumno_nuevo_conserva_su_plazo_propio_hasta_que_vence(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $this->cuota($this->alumno('2026-10-20'));
        foreach (['2026-10-25', '2026-10-26'] as $dia) {
            Carbon::setTestNow($dia);
            $this->assertSame(0, app(PunitorioService::class)->aplicar());
        }
        Carbon::setTestNow('2026-10-27');
        $this->assertSame(1, app(PunitorioService::class)->aplicar());
    }

    public function test_alumno_nuevo_conserva_el_plazo_general_si_es_mayor_que_el_propio(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        Configuracion::set('mora_dia_desde', 5);
        $this->cuota($this->alumno('2026-10-01'));
        Carbon::setTestNow('2026-10-08');
        $this->assertSame(0, app(PunitorioService::class)->aplicar());
        Carbon::setTestNow('2026-10-11');
        $this->assertSame(1, app(PunitorioService::class)->aplicar());
    }

    public function test_no_crea_recargos_para_inactivos_ni_cuotas_condonadas(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $this->cuota($this->alumno(activo: false));
        $this->cuota($this->alumno())->update(['estado' => 'CONDONADA']);
        $this->assertSame(0, app(PunitorioService::class)->aplicar());
        $this->assertDatabaseCount('cargos_alumno', 0);
    }

    public function test_porcentaje_con_coma_usa_el_original_y_redondea_el_medio_centavo(): void
    {
        Configuracion::set('mora_porcentaje', '12,5');
        $cuota = $this->cuota($this->alumno(), original: '100.04');
        $cuota->update(['monto_pagado' => 20]);
        $this->assertSame(1, app(PunitorioService::class)->aplicar());
        $this->assertSame('12.51', CargoAlumno::sole()->monto_original);
        $this->assertSame('12.50', CargoAlumno::sole()->calculo['porcentaje']);
    }

    public function test_comando_usa_el_reloj_simulado_y_repetirlo_no_duplica(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $this->cuota($this->alumno(), '2032-02');
        RelojSimulado::aplicar('2032-02-11 07:00:00', 'testing');
        $this->assertSame(0, Artisan::call('cobranza:aplicar-punitorios'));
        $this->assertStringContainsString('Recargos aplicados: 1', Artisan::output());
        $this->assertSame(0, Artisan::call('cobranza:aplicar-punitorios'));
        $this->assertStringContainsString('Recargos aplicados: 0', Artisan::output());
        $this->assertSame('2032-02-11', CargoAlumno::sole()->calculo['fecha_aplicacion']);
    }

    public function test_solo_duenio_condona_y_reactiva_con_motivo_y_eventos(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $this->cuota($this->alumno());
        app(PunitorioService::class)->aplicar();
        $cargo = CargoAlumno::sole();
        $operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        try {
            app(PunitorioService::class)->condonar($cargo->id, 'Motivo suficientemente largo', $operativo->id);
            $this->fail('Un operativo no debe condonar.');
        } catch (AuthorizationException) {
            $this->assertSame('0.00', $cargo->fresh()->monto_condonado);
            $this->assertDatabaseCount('cargo_alumno_eventos', 1);
        }
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        foreach ([str_repeat('a', 9), str_repeat('a', 501)] as $motivo) {
            try {
                app(PunitorioService::class)->condonar($cargo->id, $motivo, $admin->id);
                $this->fail('Motivo inválido aceptado.');
            } catch (\InvalidArgumentException) {
                $this->assertSame('0.00', $cargo->fresh()->monto_condonado);
            }
        }
        app(PunitorioService::class)->condonar($cargo->id, 'Motivo de prueba válido', $admin->id);
        $this->assertSame('500.00', $cargo->fresh()->monto_condonado);
        $this->assertSame(0.0, $cargo->fresh()->saldo_pendiente);
        app(PunitorioService::class)->reactivar($cargo->id, 'Reactivar por error de prueba', $admin->id);
        $this->assertSame(500.0, $cargo->fresh()->saldo_pendiente);
        $this->assertSame(['CREAR', 'CONDONAR', 'REACTIVAR'], DB::table('cargo_alumno_eventos')->orderBy('id')->pluck('accion')->all());
    }
}
