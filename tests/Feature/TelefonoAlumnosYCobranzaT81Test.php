<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelefonoAlumnosYCobranzaT81Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private Deporte $deporte;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'activo' => true,
        ]);

        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'name' => 'Operativo Test',
            'email' => 'operativo@test.com',
            'activo' => true,
        ]);

        $this->deporte = Deporte::create([
            'nombre' => 'Patín Artístico',
            'activo' => true,
        ]);

        $nivel = Nivel::create([
            'nombre' => 'Iniciación',
            'activo' => true,
        ]);

        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'nombre' => 'Grupo A',
            'activo' => true,
        ]);
    }

    public function test_alumno_con_celular_muestra_su_celular_en_alumnos_y_en_cobranza(): void
    {
        $alumno = Alumno::create([
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'dni' => '30111222',
            'fecha_nacimiento' => '1995-05-10',
            'fecha_alta' => '2026-01-01',
            'celular' => '11-5555-1234',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ]);

        // En Alumnos: aparece "Teléfono:" y el número
        $response = $this->actingAs($this->admin)->get(route('web.alumnos.index'));
        $response->assertOk();
        $response->assertSee('Teléfono:');
        $response->assertSee('11-5555-1234');
        $response->assertSee('DNI:');
        $response->assertSee('30111222');

        // En Cobranza: aparece el número debajo del nombre en lugar del DNI
        $responseCobranza = $this->actingAs($this->admin)->get(route('web.cobranza.index', ['estado' => 'TODOS']));
        $responseCobranza->assertOk();
        $responseCobranza->assertSee('11-5555-1234');
        $responseCobranza->assertDontSee('DNI: 30.111.222');
    }

    public function test_alumno_menor_sin_celular_muestra_el_telefono_del_tutor_en_alumnos_y_en_cobranza(): void
    {
        $menor = Alumno::create([
            'nombre' => 'Lucía',
            'apellido' => 'Gómez',
            'dni' => '55222333',
            'fecha_nacimiento' => Carbon::now()->subYears(10)->toDateString(),
            'fecha_alta' => '2026-01-01',
            'celular' => '11-4444-9876', // AlumnoWebController guarda el del tutor en celular cuando es menor sin celular propio
            'nombre_tutor' => 'María Gómez',
            'telefono_tutor' => '11-4444-9876',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ]);

        $this->assertEquals('11-4444-9876', $menor->telefono_contacto);

        // En Alumnos: muestra el teléfono del tutor bajo "Teléfono:"
        $response = $this->actingAs($this->operativo)->get(route('web.alumnos.index'));
        $response->assertOk();
        $response->assertSee('Teléfono:');
        $response->assertSee('11-4444-9876');

        // En Cobranza: muestra el teléfono del tutor y no el DNI
        $responseCobranza = $this->actingAs($this->operativo)->get(route('web.cobranza.index', ['estado' => 'TODOS']));
        $responseCobranza->assertOk();
        $responseCobranza->assertSee('11-4444-9876');
        $responseCobranza->assertDontSee('DNI: 55.222.333');
    }

    public function test_alumno_sin_telefono_muestra_guion_en_alumnos_y_nada_en_cobranza(): void
    {
        $alumno = Alumno::create([
            'nombre' => 'Juan',
            'apellido' => 'SinTel',
            'dni' => '28999888',
            'fecha_nacimiento' => '1990-01-01',
            'fecha_alta' => '2026-01-01',
            'celular' => '',
            'telefono_tutor' => null,
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ]);

        $this->assertNull($alumno->telefono_contacto);

        // En Alumnos muestra '–'
        $response = $this->actingAs($this->admin)->get(route('web.alumnos.index'));
        $response->assertOk();
        $response->assertDontSee('Sin teléfono');

        // En Cobranza: no muestra nada de teléfono ni 'Sin teléfono' ni DNI
        $responseCobranza = $this->actingAs($this->admin)->get(route('web.cobranza.index', ['estado' => 'TODOS']));
        $responseCobranza->assertOk();
        $responseCobranza->assertDontSee('Sin teléfono');
        $responseCobranza->assertDontSee('DNI: 28.999.888');
    }

    public function test_busqueda_por_dni_en_alumnos_y_autocomplete_sigue_funcionando(): void
    {
        $alumno = Alumno::create([
            'nombre' => 'Esteban',
            'apellido' => 'Búsqueda',
            'dni' => '42333444',
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-01-01',
            'celular' => '11-3333-2222',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ]);

        // Búsqueda en Alumnos por DNI
        $response = $this->actingAs($this->admin)->get(route('web.alumnos.index', ['search' => '42333444']));
        $response->assertOk();
        $response->assertSee('Esteban');
        $response->assertSee('Búsqueda');
        $response->assertSee('11-3333-2222');

        // Autocomplete por DNI
        $responseAuto = $this->actingAs($this->admin)->getJson(route('web.alumnos.autocomplete', ['q' => '42333']));
        $responseAuto->assertOk();
        $responseAuto->assertJsonFragment([
            'id' => $alumno->id,
            'label' => 'Búsqueda, Esteban',
        ]);
    }
}
