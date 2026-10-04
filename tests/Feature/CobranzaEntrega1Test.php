<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\Nivel;
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

    public function test_unifica_alumnos_con_mismo_dni_en_una_sola_fila(): void
    {
        // Misma persona (Sofía Morales) anotada en Patín y en Fútbol
        $dni = '40000001';
        $moralesPatin = $this->crearAlumno('Morales', 'Sofía', $dni, $this->patin, $this->grupoPatin);
        $moralesFutbol = $this->crearAlumno('Morales', 'Sofía', $dni, $this->futbol, $this->grupoFutbol);

        $this->crearDeuda($moralesPatin, '2026-08', 15000);
        $this->crearDeuda($moralesFutbol, '2026-09', 12000);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index'));

        $response->assertOk();

        $html = $response->getContent();

        // Debe aparecer una sola vez en el listado
        $this->assertSame(1, substr_count($html, 'Morales, Sofía'), 'La persona con dos deportes debe figurar en una sola fila.');

        // Debe listar ambos deportes/actividades
        $response->assertSee('Patín');
        $response->assertSee('Fútbol');

        // Debe mostrar el total adeudado por la persona (15000 + 12000 = 27000)
        $response->assertSee('$ 27.000,00', false);
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
