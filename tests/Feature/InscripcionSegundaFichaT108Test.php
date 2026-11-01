<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use App\Services\InscripcionService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T108 (Carlos, 01/11/2026): quien ya tiene una ficha no vuelve a pagar inscripción, aunque no tenga una
 * registrada. Día 46.4: Thiago vino de la primera carga con «Inscripción: No» y en Patín le cobraba $8.000.
 */
class InscripcionSegundaFichaT108Test extends TestCase
{
    use RefreshDatabase;

    private User $operativa;
    private Deporte $futbol;
    private Deporte $patin;
    private Grupo $grupoPatin;
    private GrupoPlan $planPatin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-05 10:00:00');
        $this->seed(CatalogosSeeder::class);
        Configuracion::set('inscripcion_importe', '8000');
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Control T108']);
        $this->futbol = Deporte::create(['nombre' => 'Fútbol T108', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION, 'activo' => true]);
        $this->patin = Deporte::create(['nombre' => 'Patín T108', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $this->grupoPatin = Grupo::create(['deporte_id' => $this->patin->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->planPatin = GrupoPlan::create(['grupo_id' => $this->grupoPatin->id, 'clases_por_semana' => 1, 'precio_mensual' => 30000, 'activo' => true]);
    }

    /** Ficha ya existente sin inscripción, como deja la primera carga con «Inscripción: No». */
    private function fichaSinInscripcion(string $dni, array $datos = []): Alumno
    {
        $nivel = Nivel::firstOrCreate(['nombre' => 'Otro T108']);
        $grupo = Grupo::firstOrCreate(['deporte_id' => $this->futbol->id, 'nivel_id' => $nivel->id], ['activo' => true]);

        return Alumno::create($datos + ['nombre' => 'Thiago', 'apellido' => 'Ruiz', 'dni' => $dni, 'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-01-10', 'celular' => '1144445555', 'deporte_id' => $this->futbol->id, 'grupo_id' => $grupo->id, 'activo' => true]);
    }

    private function darDeAlta(string $dni)
    {
        return $this->actingAs($this->operativa)->post(route('web.alumnos.store'), [
            'nombre' => 'Thiago', 'apellido' => 'Ruiz', 'dni' => $dni, 'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => '2026-11-05',
            'celular' => '1144445555', 'direccion' => 'Calle 1', 'localidad' => 'Quilmes', 'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id, 'plan_id' => $this->planPatin->id,
        ]);
    }

    private function preview(string $dni): array
    {
        return $this->actingAs($this->operativa)->getJson(route('web.alumnos.inscripcion-preview', ['dni' => $dni, 'fecha_alta' => '2026-11-05']))->assertOk()->json();
    }

    public function test_quien_ya_tiene_una_ficha_no_paga_inscripcion(): void
    {
        $this->fichaSinInscripcion('54400002');

        $this->darDeAlta('54400002')->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(2, Alumno::where('dni', '54400002')->count());
        $this->assertSame(0, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
    }

    public function test_el_aviso_previo_lo_dice_antes_de_guardar(): void
    {
        $this->fichaSinInscripcion('54400002');

        $this->assertSame('Esta persona ya es del club: no paga inscripción.', $this->preview('54400002')['mensaje']);
    }

    public function test_una_persona_nueva_sigue_pagando(): void
    {
        $this->assertStringContainsString('Corresponde inscripción por única vez', $this->preview('54400009')['mensaje']);

        $this->darDeAlta('54400009')->assertSessionHasNoErrors();

        $cargo = CargoAlumno::where('tipo', 'INSCRIPCION')->sole();
        $this->assertSame('8000.00', (string) $cargo->monto_original);
    }

    public function test_el_dni_se_compara_sin_puntos_ni_espacios(): void
    {
        $this->fichaSinInscripcion('54.400.002');

        $this->assertSame('Esta persona ya es del club: no paga inscripción.', $this->preview('54400002')['mensaje']);
        $this->darDeAlta('54400002')->assertSessionHasNoErrors();

        $this->assertSame(0, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
    }

    public function test_una_ficha_inactiva_tambien_cuenta(): void
    {
        $this->fichaSinInscripcion('54400002', ['activo' => false]);

        $this->darDeAlta('54400002')->assertSessionHasNoErrors();

        $this->assertSame(0, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
    }

    public function test_quien_ya_tiene_inscripcion_sigue_con_una_sola(): void
    {
        $alumno = $this->fichaSinInscripcion('54400002');
        app(InscripcionService::class)->sincronizar($alumno, $this->operativa->id);

        $this->assertStringContainsString('ya tiene una inscripción registrada', $this->preview('54400002')['mensaje']);
        $this->darDeAlta('54400002')->assertSessionHasNoErrors();

        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
    }

    public function test_si_se_elimino_la_unica_ficha_vuelve_a_ser_persona_nueva(): void
    {
        $this->fichaSinInscripcion('54400002')->delete();

        $this->darDeAlta('54400002')->assertSessionHasNoErrors();

        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
    }

    public function test_la_primera_carga_con_inscripcion_si_la_sigue_creando(): void
    {
        $alumno = $this->fichaSinInscripcion('54400002');
        $otra = $this->fichaSinInscripcion('54400002', ['deporte_id' => $this->patin->id, 'grupo_id' => $this->grupoPatin->id]);

        app(InscripcionService::class)->sincronizar($otra, $this->operativa->id, motivo: 'Primera carga por Excel');

        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
        $this->assertNotNull($alumno->id);
    }
}
