<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T54 (DU-13, ALU-020): Buscar alumnos como busca la gente.
 * - Con y sin acento es lo mismo, mayúsculas y minúsculas también.
 * - Desde la primera letra (1 caracter) ofrece opciones en autocompletar.
 * - Múltiples palabras en cualquier orden (nombre y apellido juntos).
 * - Mismo criterio en listado, autocompletar y Cobrar (caja/cobrar-cuota).
 * - Filtros combinados, homónimos, sin resultados y preservación de T29.
 */
class BusquedaAlumnosT54Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private Deporte $patin;
    private Deporte $futbol;
    private Grupo $grupoPatin;
    private Grupo $grupoFutbol;

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

        $this->patin = Deporte::create(['nombre' => 'Patín Artístico', 'activo' => true]);
        $this->futbol = Deporte::create(['nombre' => 'Fútbol', 'activo' => true]);

        $nivel = Nivel::create(['nombre' => 'Intermedio', 'activo' => true]);

        $this->grupoPatin = Grupo::create([
            'nombre' => 'Juveniles Patín',
            'deporte_id' => $this->patin->id,
            'nivel_id' => $nivel->id,
            'color' => '#10B981',
            'activo' => true,
        ]);

        $this->grupoFutbol = Grupo::create([
            'nombre' => 'Infantiles Fútbol',
            'deporte_id' => $this->futbol->id,
            'nivel_id' => $nivel->id,
            'color' => '#3B82F6',
            'activo' => true,
        ]);
    }

    private function crearAlumno(array $attrs = []): Alumno
    {
        return Alumno::create(array_merge([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'dni' => '35123456',
            'fecha_nacimiento' => '2010-05-15',
            'celular' => '1144556677',
            'email' => 'test@alumno.com',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'fecha_alta' => '2026-03-01',
            'activo' => true,
        ], $attrs));
    }

    private function abrirCaja(User $user): CajaOperativa
    {
        return CajaOperativa::create([
            'usuario_operativo_id' => $user->id,
            'estado' => 'ABIERTA',
            'monto_apertura' => 5000.00,
            'apertura_at' => Carbon::now(),
        ]);
    }

    /**
     * 1. Acentos y mayúsculas/minúsculas: "gomez" encuentra a "Gómez", y viceversa.
     */
    public function test_busqueda_con_y_sin_acento_en_los_tres_lugares(): void
    {
        $gomez = $this->crearAlumno([
            'nombre' => 'Martín',
            'apellido' => 'Gómez',
            'dni' => '30111222',
        ]);

        $alvarez = $this->crearAlumno([
            'nombre' => 'Álvaro',
            'apellido' => 'Álvarez',
            'dni' => '30333444',
        ]);

        $nunez = $this->crearAlumno([
            'nombre' => 'Iñaki',
            'apellido' => 'Núñez',
            'dni' => '30555666',
        ]);

        $this->actingAs($this->admin);

        // A) Listado de alumnos con "gomez" sin acento
        $resp = $this->get(route('web.alumnos.index', ['search' => 'gomez']));
        $resp->assertOk();
        $resp->assertSee('Gómez');
        $resp->assertDontSee('Álvarez');

        // Listado con "GÓMEZ" todo mayúscula con acento
        $resp = $this->get(route('web.alumnos.index', ['search' => 'GÓMEZ']));
        $resp->assertOk();
        $resp->assertSee('Gómez');

        // Listado con "alvaro" sin acento
        $resp = $this->get(route('web.alumnos.index', ['search' => 'alvaro']));
        $resp->assertOk();
        $resp->assertSee('Álvarez');

        // Listado con "nunez" sin tilde ni eñe
        $resp = $this->get(route('web.alumnos.index', ['search' => 'nunez']));
        $resp->assertOk();
        $resp->assertSee('Núñez');

        // B) Autocompletar con "gomez"
        $resp = $this->getJson(route('web.alumnos.autocomplete', ['q' => 'gomez']));
        $resp->assertOk();
        $data = $resp->json();
        $this->assertCount(1, $data);
        $this->assertSame($gomez->id, $data[0]['id']);

        // Autocompletar con "inaki" sin eñe
        $resp = $this->getJson(route('web.alumnos.autocomplete', ['q' => 'inaki']));
        $resp->assertOk();
        $data = $resp->json();
        $this->assertCount(1, $data);
        $this->assertSame($nunez->id, $data[0]['id']);

        // C) Búsqueda en Cobrar cuota con "gomez"
        $this->abrirCaja($this->admin);
        $resp = $this->get(route('web.caja.cobrar-cuota', ['search' => 'gomez']));
        $resp->assertOk();
        $resp->assertSee('Gómez');
        $resp->assertDontSee('Álvarez');
    }

    /**
     * 2. Búsqueda desde la primera letra (1 letra / carácter):
     * "g" encuentra a cualquier alumno con 'g' en nombre, apellido o DNI.
     */
    public function test_autocompletar_funciona_desde_la_primera_letra(): void
    {
        $gaston = $this->crearAlumno([
            'nombre' => 'Gastón',
            'apellido' => 'López',
            'dni' => '20111222',
        ]);

        $rodrigo = $this->crearAlumno([
            'nombre' => 'Rodrigo',
            'apellido' => 'Díaz',
            'dni' => '20333444',
        ]);

        $ana = $this->crearAlumno([
            'nombre' => 'Ana',
            'apellido' => 'Sosa',
            'dni' => '20555666',
        ]);

        $this->actingAs($this->admin);

        // Autocompletar con 1 sola letra 'g'
        $resp = $this->getJson(route('web.alumnos.autocomplete', ['q' => 'g']));
        $resp->assertOk();
        $data = $resp->json();

        // Gastón (nombre con G) y Rodrigo (nombre con g) deben estar, Ana no
        $ids = array_column($data, 'id');
        $this->assertContains($gaston->id, $ids);
        $this->assertContains($rodrigo->id, $ids);
        $this->assertNotContains($ana->id, $ids);

        // Con string vacío no debe traer nada
        $respVacio = $this->getJson(route('web.alumnos.autocomplete', ['q' => '']));
        $respVacio->assertOk();
        $this->assertSame([], $respVacio->json());
    }

    /**
     * 3. Múltiples palabras en cualquier orden:
     * "perez juan" encuentra a Juan Pérez.
     */
    public function test_varias_palabras_en_cualquier_orden(): void
    {
        $juanPerez = $this->crearAlumno([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'dni' => '40111222',
        ]);

        $carlosPerez = $this->crearAlumno([
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'dni' => '40333444',
        ]);

        $juanGomez = $this->crearAlumno([
            'nombre' => 'Juan',
            'apellido' => 'Gómez',
            'dni' => '40555666',
        ]);

        $this->actingAs($this->admin);

        // A) En listado: "perez juan" encuentra a Juan Pérez y descarta a Carlos Pérez y Juan Gómez
        $resp = $this->get(route('web.alumnos.index', ['search' => 'perez juan']));
        $resp->assertOk();
        $resp->assertSee('40111222');
        $resp->assertSee('Pérez, Juan');
        $resp->assertDontSee('Pérez, Carlos');
        $resp->assertDontSee('Gómez, Juan');

        // En listado: "juan perez" en orden inverso también lo encuentra
        $resp = $this->get(route('web.alumnos.index', ['search' => 'juan perez']));
        $resp->assertOk();
        $resp->assertSee('Pérez, Juan');
        $resp->assertDontSee('Pérez, Carlos');

        // B) En autocompletar: "perez juan"
        $resp = $this->getJson(route('web.alumnos.autocomplete', ['q' => 'perez juan']));
        $resp->assertOk();
        $data = $resp->json();
        $this->assertCount(1, $data);
        $this->assertSame($juanPerez->id, $data[0]['id']);

        // C) En Cobrar cuota: "perez juan"
        $this->abrirCaja($this->admin);
        $resp = $this->get(route('web.caja.cobrar-cuota', ['search' => 'perez juan']));
        $resp->assertOk();
        $resp->assertSee('Pérez, Juan');
        $resp->assertDontSee('Pérez, Carlos');
    }

    /**
     * 4. Búsqueda por parte del DNI y combinación nombre + DNI:
     */
    public function test_busqueda_por_dni_y_combinacion_nombre_dni(): void
    {
        $alumno = $this->crearAlumno([
            'nombre' => 'Lucía',
            'apellido' => 'Fernández',
            'dni' => '45987654',
        ]);

        $this->actingAs($this->admin);

        // Solo parte del DNI "9876"
        $resp = $this->get(route('web.alumnos.index', ['search' => '9876']));
        $resp->assertOk();
        $resp->assertSee('Fernández, Lucía');

        // Nombre y parte del DNI "lucia 9876"
        $resp = $this->get(route('web.alumnos.index', ['search' => 'lucia 9876']));
        $resp->assertOk();
        $resp->assertSee('Fernández, Lucía');

        // Apellido y parte del DNI en orden inverso "9876 fernandez"
        $resp = $this->get(route('web.alumnos.index', ['search' => '9876 fernandez']));
        $resp->assertOk();
        $resp->assertSee('Fernández, Lucía');
    }

    /**
     * 5. Filtros combinados en el listado de alumnos:
     * El search se suma a deporte_id, grupo_id y activo.
     */
    public function test_filtros_combinados_con_deporte_grupo_y_estado(): void
    {
        $patinActivo = $this->crearAlumno([
            'nombre' => 'Martina',
            'apellido' => 'Rossi',
            'dni' => '35111111',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'activo' => true,
        ]);

        $futbolActivo = $this->crearAlumno([
            'nombre' => 'Martina',
            'apellido' => 'Rossi',
            'dni' => '35222222',
            'deporte_id' => $this->futbol->id,
            'grupo_id' => $this->grupoFutbol->id,
            'activo' => true,
        ]);

        $patinInactivo = $this->crearAlumno([
            'nombre' => 'Martina',
            'apellido' => 'Rossi',
            'dni' => '35333333',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'activo' => false,
        ]);

        $this->actingAs($this->admin);

        // Buscar Martina filtrando solo deporte Patín
        $resp = $this->get(route('web.alumnos.index', [
            'search' => 'martina rossi',
            'deporte_id' => $this->patin->id,
        ]));
        $resp->assertOk();
        $resp->assertSee('35111111');
        $resp->assertDontSee('35222222');

        // Filtrar además por activo = 1
        $resp = $this->get(route('web.alumnos.index', [
            'search' => 'martina',
            'deporte_id' => $this->patin->id,
            'activo' => '1',
        ]));
        $resp->assertOk();
        $this->assertEquals(1, $resp->viewData('alumnos')->total());
        $this->assertEquals($patinActivo->id, $resp->viewData('alumnos')->first()->id);
    }

    /**
     * 6. Paginación: conserva los filtros y el parámetro search.
     */
    public function test_paginacion_conserva_parametros_de_busqueda(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->crearAlumno([
                'nombre' => 'Alumno' . $i,
                'apellido' => 'Gómez',
                'dni' => '100000' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($this->admin);

        $resp = $this->get(route('web.alumnos.index', ['search' => 'gomez']));
        $resp->assertOk();
        $alumnos = $resp->viewData('alumnos');
        $this->assertEquals(15, $alumnos->total());
        $this->assertTrue($alumnos->hasPages());

        // Verificar que el link a la página 2 contiene search=gomez
        $resp->assertSee('search=gomez');
    }

    /**
     * 7. Homónimos: dos alumnos con mismo nombre y apellido se distinguen por DNI y grupo.
     */
    public function test_homonimos_se_distinguen_por_dni_y_grupo(): void
    {
        $hom1 = $this->crearAlumno([
            'nombre' => 'Camila',
            'apellido' => 'Torres',
            'dni' => '41111111',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
        ]);

        $hom2 = $this->crearAlumno([
            'nombre' => 'Camila',
            'apellido' => 'Torres',
            'dni' => '42222222',
            'deporte_id' => $this->futbol->id,
            'grupo_id' => $this->grupoFutbol->id,
        ]);

        $this->actingAs($this->admin);

        // Listado
        $resp = $this->get(route('web.alumnos.index', ['search' => 'camila torres']));
        $resp->assertOk();
        $resp->assertSee('Torres, Camila');
        $resp->assertSee('41111111');
        $resp->assertSee('42222222');
        $resp->assertSee('Patín Artístico — Intermedio');
        $resp->assertSee('Fútbol — Intermedio');

        // Autocompletar
        $respJson = $this->getJson(route('web.alumnos.autocomplete', ['q' => 'camila torres']));
        $respJson->assertOk();
        $data = $respJson->json();
        $this->assertCount(2, $data);
        $this->assertStringContainsString('41111111', $data[0]['sub'] . ' ' . $data[1]['sub']);
        $this->assertStringContainsString('42222222', $data[0]['sub'] . ' ' . $data[1]['sub']);
    }

    /**
     * 8. Búsqueda sin coincidencias: muestra el mensaje claro sin error.
     */
    public function test_busqueda_sin_coincidencias_muestra_mensaje(): void
    {
        $this->crearAlumno(['nombre' => 'Valeria', 'apellido' => 'Paz']);
        $this->actingAs($this->admin);

        // Listado
        $resp = $this->get(route('web.alumnos.index', ['search' => 'inexistentexyz']));
        $resp->assertOk();
        $resp->assertSee('No se encontraron alumnos');

        // Autocompletar
        $respJson = $this->getJson(route('web.alumnos.autocomplete', ['q' => 'inexistentexyz']));
        $respJson->assertOk();
        $this->assertSame([], $respJson->json());

        // Cobrar cuota
        $this->abrirCaja($this->admin);
        $respCaja = $this->get(route('web.caja.cobrar-cuota', ['search' => 'inexistentexyz']));
        $respCaja->assertOk();
        $respCaja->assertSee('Sin resultados');
        $respCaja->assertSee('Ningún alumno activo coincide con');
        $respCaja->assertSee('inexistentexyz');
    }

    /**
     * 9. En Cobrar, sin buscar sigue mostrando solo a los que deben (T29).
     */
    public function test_en_cobrar_sin_buscar_muestra_solo_los_que_deben_t29(): void
    {
        $conDeuda = $this->crearAlumno([
            'nombre' => 'Alumno',
            'apellido' => 'ConDeuda',
            'dni' => '50111111',
        ]);

        $sinDeuda = $this->crearAlumno([
            'nombre' => 'Alumno',
            'apellido' => 'AlDia',
            'dni' => '50222222',
        ]);

        // Generar deuda para el primero
        DeudaCuota::create([
            'alumno_id' => $conDeuda->id,
            'periodo' => '2026-10',
            'mes' => 10,
            'anio' => 2026,
            'monto_original' => 15000.00,
            'monto_pagado' => 0.00,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
            'fecha_vencimiento' => '2026-10-10',
        ]);

        $this->actingAs($this->admin);
        $this->abrirCaja($this->admin);

        // Sin search: solo el que tiene deuda
        $resp = $this->get(route('web.caja.cobrar-cuota'));
        $resp->assertOk();
        $resp->assertSee('ConDeuda, Alumno');
        $resp->assertDontSee('AlDia, Alumno');

        // Con search buscando al que está al día: lo encuentra aunque no tenga deuda (para cobrar adelantado)
        $respConBusqueda = $this->get(route('web.caja.cobrar-cuota', ['search' => 'aldia']));
        $respConBusqueda->assertOk();
        $respConBusqueda->assertSee('AlDia, Alumno');
    }

    /**
     * 10. Rendimiento y límite de 8 en autocompletar con 300 alumnos.
     */
    public function test_rendimiento_y_limite_de_8_con_300_alumnos(): void
    {
        // Crear 300 alumnos con 'g' en el apellido
        $now = now();
        $insertData = [];
        for ($i = 1; $i <= 300; $i++) {
            $insertData[] = [
                'nombre' => 'Nombre' . $i,
                'apellido' => 'Gómez' . $i,
                'dni' => '60' . str_pad((string)$i, 6, '0', STR_PAD_LEFT),
                'fecha_nacimiento' => '2012-01-01',
                'celular' => '1100000000',
                'email' => "a{$i}@test.com",
                'deporte_id' => $this->patin->id,
                'grupo_id' => $this->grupoPatin->id,
                'fecha_alta' => '2026-03-01',
                'activo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Alumno::insert($insertData);

        $this->actingAs($this->admin);

        $inicio = microtime(true);
        $resp = $this->getJson(route('web.alumnos.autocomplete', ['q' => 'g']));
        $duracionMs = (microtime(true) - $inicio) * 1000;

        $resp->assertOk();
        $data = $resp->json();

        // Debe limitar a 8 resultados
        $this->assertCount(8, $data);

        // Debe responder en menos de 500 ms en entorno de pruebas
        $this->assertLessThan(500, $duracionMs, "La búsqueda tardó {$duracionMs} ms, excediendo el umbral esperado.");
    }
}
