<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobranzaEntrega1Test extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private Deporte $patin;
    private Deporte $futbol;
    private Grupo $grupoPatin;
    private Grupo $grupoFutbol;

    protected function setUp(): void
    {
        parent::setUp();

        Configuracion::set('dias_gracia_cobranza', 10);
        Carbon::setTestNow('2026-10-04 10:00:00');

        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
            'es_superadmin' => false,
        ]);

        $this->patin = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $this->futbol = Deporte::create([
            'nombre' => 'Fútbol',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);

        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupoPatin = Grupo::create([
            'deporte_id' => $this->patin->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->grupoFutbol = Grupo::create([
            'deporte_id' => $this->futbol->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_por_defecto_abre_mostrando_solo_deudores_ordenados_por_antiguedad(): void
    {
        // Alumno 1: Al día (sin deuda)
        $alDia = $this->crearAlumno('Gómez', 'Ana', '30000001', $this->patin, $this->grupoPatin);

        // Alumno 2: Deuda reciente (septiembre 2026)
        $deudaReciente = $this->crearAlumno('Pérez', 'Juan', '30000002', $this->patin, $this->grupoPatin);
        $this->crearDeuda($deudaReciente, '2026-09', 10000);

        // Alumno 3: Deuda más vieja (julio 2026)
        $deudaVieja = $this->crearAlumno('Álvarez', 'Carlos', '30000003', $this->patin, $this->grupoPatin);
        $this->crearDeuda($deudaVieja, '2026-07', 10000);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index'));

        $response->assertOk();

        // No debe mostrar al alumno que está al día
        $response->assertDontSee('Gómez, Ana');

        // Debe mostrar a los deudores
        $response->assertSee('Álvarez, Carlos');
        $response->assertSee('Pérez, Juan');

        // El de deuda más vieja (Álvarez) debe aparecer antes que el de deuda reciente (Pérez)
        $html = $response->getContent();
        $posVieja = strpos($html, 'Álvarez, Carlos');
        $posReciente = strpos($html, 'Pérez, Juan');

        $this->assertNotFalse($posVieja);
        $this->assertNotFalse($posReciente);
        $this->assertLessThan($posReciente, $posVieja, 'El alumno con deuda más vieja debe aparecer primero en la lista.');
    }

    public function test_filtro_todos_permite_ver_a_los_que_estan_al_dia(): void
    {
        $alDia = $this->crearAlumno('Gómez', 'Ana', '30000001', $this->patin, $this->grupoPatin);
        $deudor = $this->crearAlumno('Pérez', 'Juan', '30000002', $this->patin, $this->grupoPatin);
        $this->crearDeuda($deudor, '2026-09', 10000);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index', ['estado' => 'TODOS']));

        $response->assertOk();
        $response->assertSee('Gómez, Ana');
        $response->assertSee('Pérez, Juan');
    }

    public function test_una_fila_por_registro_y_renglon_de_ayuda_si_repite_dni_con_deuda(): void
    {
        $dni = '40000001';
        $moralesPatin = $this->crearAlumno('Morales', 'Sofía', $dni, $this->patin, $this->grupoPatin);
        $moralesFutbol = $this->crearAlumno('Morales', 'Sofía', $dni, $this->futbol, $this->grupoFutbol);

        $this->crearDeuda($moralesPatin, '2026-08', 15000);
        $this->crearDeuda($moralesFutbol, '2026-09', 12000);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index'));

        $response->assertOk();
        $html = $response->getContent();

        // Son dos registros: van en dos filas
        $this->assertSame(2, substr_count($html, 'Morales, Sofía'), 'Deben existir dos filas para la misma persona anotada en dos deportes.');

        // En la fila de Patín debe verse el monto propio (15.000) y renglón de ayuda indicando que también debe en Fútbol (12.000)
        $response->assertSee('también debe $ 12.000,00 en Fútbol', false);
        // En la fila de Fútbol debe verse el monto propio (12.000) y renglón de ayuda indicando que también debe en Patín (15.000)
        $response->assertSee('también debe $ 15.000,00 en Patín', false);

        // La columna se llama "Deuda", no "Total deuda"
        $response->assertSee('Deuda');
        $response->assertDontSee('Total deuda');
    }

    public function test_si_el_otro_registro_no_debe_nada_no_muestra_renglon_de_ayuda(): void
    {
        $dni = '40000002';
        $moralesPatin = $this->crearAlumno('Morales', 'Sofía', $dni, $this->patin, $this->grupoPatin);
        $moralesFutbol = $this->crearAlumno('Morales', 'Sofía', $dni, $this->futbol, $this->grupoFutbol);

        // Solo debe en Patín; en Fútbol está al día
        $this->crearDeuda($moralesPatin, '2026-08', 15000);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index'));

        $response->assertOk();
        $response->assertDontSee('también debe');
    }

    public function test_deuda_por_fila_no_cambia_al_filtrar_por_deporte(): void
    {
        $dni = '40000003';
        $moralesPatin = $this->crearAlumno('Morales', 'Sofía', $dni, $this->patin, $this->grupoPatin);
        $moralesFutbol = $this->crearAlumno('Morales', 'Sofía', $dni, $this->futbol, $this->grupoFutbol);

        $this->crearDeuda($moralesPatin, '2026-08', 15000);
        $this->crearDeuda($moralesFutbol, '2026-09', 12000);

        // Filtrando solo Fútbol
        $response = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index', ['deporte_id' => $this->futbol->id]));

        $response->assertOk();
        // Debe mostrar exactamente la fila de Fútbol con sus $12.000,00
        $response->assertSee('$ 12.000,00', false);
        // Debe mantener el renglón de ayuda informando la deuda en el otro deporte
        $response->assertSee('también debe $ 15.000,00 en Patín', false);
    }

    public function test_inscripcion_impaga_no_convierte_en_deudor_en_listado_ficha_ni_resumen(): void
    {
        $alumno = $this->crearAlumno('Gaitán', 'Lucía', '30000099', $this->patin, $this->grupoPatin);
        $rubro = Rubro::firstOrCreate(['nombre' => 'Ingresos'], ['tipo' => 'INGRESO', 'activo' => true]);
        $subrubro = Subrubro::firstOrCreate(['nombre' => 'Inscripción'], ['rubro_id' => $rubro->id, 'activo' => true]);

        CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'dni' => '30000099',
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'INSCRIPCION-30000099',
            'subrubro_id' => $subrubro->id,
            'monto_original' => 5000,
            'monto_condonado' => 0,
            'estado' => 'VIGENTE',
            'calculo' => ['tipo' => 'fijo'],
        ]);

        // 1. En Cobranza con filtro TODOS: su estado debe ser AL_DIA (no DEUDOR)
        $respCobranza = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index', ['estado' => 'TODOS']));
        $respCobranza->assertOk();
        $respCobranza->assertSee('Al día');

        // Y no debe aparecer en el filtro por defecto de DEUDORES porque no tiene cuotas vencidas
        $respDefecto = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index'));
        $respDefecto->assertDontSee('Gaitán, Lucía');

        // 2. En Ficha del alumno: estado de cobranza sigue siendo Al día
        $respFicha = $this->actingAs($this->operativo)
            ->get(route('web.alumnos.show', $alumno->id));
        $respFicha->assertOk();
        $respFicha->assertSee('Al día');

        // 3. En Resumen superior / servicio: se cuenta como AL_DIA
        $servicio = app(\App\Services\CobranzaEstadoService::class);
        $resumen = $servicio->resumenDashboard();
        $this->assertEquals(1, $resumen['por_estado']['AL_DIA'] ?? 0);
        $this->assertEquals(0, $resumen['por_estado']['DEUDOR'] ?? 0);
    }

    public function test_muestra_total_adeudado_en_tarjetas_superiores(): void
    {
        $a1 = $this->crearAlumno('Pérez', 'Juan', '30000001', $this->patin, $this->grupoPatin);
        $this->crearDeuda($a1, '2026-09', 15000);

        $a2 = $this->crearAlumno('Gómez', 'Ana', '30000002', $this->patin, $this->grupoPatin);
        $this->crearDeuda($a2, '2026-08', 20000);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index'));

        $response->assertOk();

        // Tarjeta de Total adeudado (15.000 + 20.000 = 35.000)
        $response->assertSee('Total adeudado');
        $response->assertSee('$ 35.000,00', false);
    }

    public function test_cada_fila_tiene_botones_cobrar_y_ver(): void
    {
        $deudor = $this->crearAlumno('Pérez', 'Juan', '30000002', $this->patin, $this->grupoPatin);
        $this->crearDeuda($deudor, '2026-09', 10000);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index'));

        $response->assertOk();
        $response->assertSee('Cobrar');
        $response->assertSee('Ver');
        $response->assertSee(route('web.caja.cobrar', $deudor->id));
        $response->assertSee(route('web.alumnos.show', $deudor->id));
    }

    private function crearAlumno(string $apellido, string $nombre, string $dni, Deporte $deporte, Grupo $grupo): Alumno
    {
        return Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => $dni,
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'email' => strtolower($nombre . '.' . $apellido . '@test.com'),
            'celular' => '11-4000-0000',
            'activo' => true,
        ]);
    }

    private function crearDeuda(Alumno $alumno, string $periodo, float $monto): DeudaCuota
    {
        return DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodo,
            'monto_original' => $monto,
            'monto_pagado' => 0,
            'monto_condonado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }
}
