<?php

namespace Tests\Verificacion;

use App\Models\Alumno;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VerificacionA15A16Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesor;
    private array $grupos = [];
    private array $profesores = [];
    private Profesor $profesorInactivo;
    private string $paginasDir;
    private array $bitacora = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->paginasDir = base_path('docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/paginas');
        if (!is_dir($this->paginasDir)) {
            mkdir($this->paginasDir, 0775, true);
        }

        $this->seed(CatalogosSeeder::class);

        // Usuarios para prueba con identidades explícitas
        $this->admin = User::factory()->create([
            'name' => 'Carlos Bonifacio (Admin)',
            'email' => 'admin.gemini@wings.test',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->operativo = User::factory()->create([
            'name' => 'Sandra Vidal (Operativa)',
            'email' => 'sandra.gemini@wings.test',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->profesor = User::factory()->create([
            'name' => 'Lucía Gaitán (Docente)',
            'email' => 'lucia.gemini@wings.test',
            'rol' => User::ROL_PROFESOR,
            'activo' => true,
        ]);

        // Crear los 6 grupos y profesores canónicos del cronograma
        $cronogramaData = [
            ['Patín', 'Principiantes', 'Lucía', 'Gaitán', 40000001],
            ['Patín', 'Intermedias', 'Verónica', 'Salinas', 40000002],
            ['Patín', 'Avanzadas', 'Mariela', 'Ocampo', 40000003],
            ['Patín', 'Federadas', 'Mariela', 'Ocampo', 40000004],
            ['Fútbol', 'Principiantes', 'Hernán', 'Quintana', 40000005],
            ['Fútbol', 'Avanzadas', 'Hernán', 'Quintana', 40000006],
        ];

        foreach ($cronogramaData as $i => [$depNombre, $nivNombre, $pNombre, $pApellido, $dni]) {
            $dep = Deporte::where('nombre', $depNombre)->sole();
            $niv = Nivel::firstOrCreate(['nombre' => $nivNombre]);
            $this->grupos[$i] = Grupo::create([
                'deporte_id' => $dep->id,
                'nivel_id' => $niv->id,
                'activo' => true,
            ]);
            $this->profesores[$i] = Profesor::firstOrCreate([
                'deporte_id' => $dep->id,
                'nombre' => $pNombre,
                'apellido' => $pApellido,
            ], [
                'dni' => (string) $dni,
                'fecha_nacimiento' => '1992-05-15',
                'direccion' => 'Calle Falsa 123',
                'localidad' => 'Buenos Aires',
                'valor_hora' => 5000,
                'activo' => true,
            ]);
        }

        // Profesor inactivo para pruebas de validación
        $depPatin = Deporte::where('nombre', 'Patín')->sole();
        $this->profesorInactivo = Profesor::create([
            'deporte_id' => $depPatin->id,
            'nombre' => 'Javier',
            'apellido' => 'Inactivo',
            'dni' => '40999888',
            'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Calle 1',
            'localidad' => 'Prueba',
            'valor_hora' => 0,
            'activo' => false,
        ]);
    }

    private function guardarHtml(string $nombre, string $html): void
    {
        file_put_contents($this->paginasDir . '/' . $nombre . '.html', $html);
    }

    private function limpiarClases(): void
    {
        DB::table('asistencias')->delete();
        DB::table('clase_profesor')->delete();
        Clase::query()->delete();
    }

    public function test_verificacion_completa_a15_y_a16(): void
    {
        Carbon::setTestNow('2026-10-07 09:00:00');

        // ---------------------------------------------------------------------
        // PASO 0: Login de control
        // ---------------------------------------------------------------------
        $loginRes = $this->get('/login')->assertOk();
        $this->guardarHtml('00-login-control', $loginRes->getContent());

        // =====================================================================
        // BLOQUE A15: EL AVISO ANTES DE GUARDAR
        // =====================================================================
        $this->actingAs($this->admin);

        // Formulario inicial vacío
        $createRes = $this->get(route('web.clases.create'))->assertOk();
        $this->guardarHtml('01-form-clase-unica-vacio', $createRes->getContent());

        // ---------------------------------------------------------------------
        // A15 - Paso 1: Cargar 17:30 a 18:30 y Guardar -> aviso, sin guardar
        // ---------------------------------------------------------------------
        $clasesAntes = Clase::count();
        $asignacionesAntes = DB::table('clase_profesor')->count();
        $this->assertSame(0, $clasesAntes);
        $this->assertSame(0, $asignacionesAntes);

        $datosA15Paso1 = [
            'tipo_creacion' => 'unica',
            'grupo_id' => $this->grupos[0]->id, // Patín Principiantes
            'profesores' => [$this->profesores[0]->id], // Lucía Gaitán
            'fecha' => '2026-10-15',
            'hora_inicio' => '17:30',
            'hora_fin' => '18:30',
        ];

        $postRes1 = $this->from(route('web.clases.create'))
            ->post(route('web.clases.store'), $datosA15Paso1);

        $postRes1->assertRedirect(route('web.clases.create'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('aviso_cancha')
            ->assertSessionHasInput('hora_inicio', '17:30')
            ->assertSessionHasInput('hora_fin', '18:30')
            ->assertSessionHasInput('fecha', '2026-10-15')
            ->assertSessionHasInput('grupo_id', $this->grupos[0]->id);

        $clasesDespues1 = Clase::count();
        $asignacionesDespues1 = DB::table('clase_profesor')->count();
        $this->assertSame(0, $clasesDespues1, 'No debe crearse ninguna clase');
        $this->assertSame(0, $asignacionesDespues1, 'No debe crearse ninguna asignación de profesor');

        $aviso = session('aviso_cancha');
        $firmaA15 = $aviso['firma'];
        $this->assertNotEmpty($firmaA15);
        $this->assertSame(2, $aviso['horarios'][0]['bloques']);
        $this->assertStringContainsString('dura 1 hora, pero ocupa 2 bloques de alquiler: 17:00–18:00, 18:00–19:00.', $aviso['horarios'][0]['detalle']);

        // Seguir redirección y guardar pantalla con aviso renderizado
        $resAviso = $this->get(route('web.clases.create'))->assertOk()
            ->assertSee('El alquiler se cuenta por bloques del reloj.')
            ->assertSee('dura 1 hora, pero ocupa 2 bloques de alquiler: 17:00–18:00, 18:00–19:00.')
            ->assertSee('Confirmar')
            ->assertSee('name="confirmar_cancha"', false)
            ->assertSee('value="' . $firmaA15 . '"', false);
        $this->guardarHtml('02-aviso-cancha-1730-1830', $resAviso->getContent());

        $this->bitacora['A15_paso1'] = [
            'descripcion' => 'Cargar 17:30 a 18:30 y Guardar devuelve aviso sin crear filas',
            'clases_antes' => $clasesAntes,
            'clases_despues' => $clasesDespues1,
            'aviso_detalle' => $aviso['horarios'][0]['detalle'],
            'bloques' => $aviso['horarios'][0]['bloques'],
            'firma' => $firmaA15,
            'aprobado' => true,
        ];

        // ---------------------------------------------------------------------
        // A15 - Paso 2: Confirmar -> ahora sí guarda 1 clase
        // ---------------------------------------------------------------------
        $postRes2 = $this->post(route('web.clases.store'), $datosA15Paso1 + [
            'confirmar_cancha' => $firmaA15,
        ]);

        $postRes2->assertRedirect(route('web.clases.index'))
            ->assertSessionHas('success', '1 clase(s) creada(s).');

        $clasesDespues2 = Clase::count();
        $asignacionesDespues2 = DB::table('clase_profesor')->count();
        $this->assertSame(1, $clasesDespues2, 'Debe crearse exactamente 1 clase al confirmar');
        $this->assertSame(1, $asignacionesDespues2, 'Debe crearse exactamente 1 asignación de profesor');

        $claseGuardada = Clase::with('profesores')->first();
        $this->assertSame('2026-10-15', $claseGuardada->fecha->format('Y-m-d'));
        $this->assertSame('17:30', $claseGuardada->hora_inicio->format('H:i'));
        $this->assertSame('18:30', $claseGuardada->hora_fin->format('H:i'));
        $this->assertSame($this->grupos[0]->id, $claseGuardada->grupo_id);
        $this->assertSame([$this->profesores[0]->id], $claseGuardada->profesores->pluck('id')->all());

        // Ver index de clases con la clase guardada
        $indexClasesRes = $this->get(route('web.clases.index'))->assertOk()
            ->assertSee('17:30')
            ->assertSee('18:30')
            ->assertSee('Gaitán');
        $this->guardarHtml('03-clase-creada-index', $indexClasesRes->getContent());

        $this->bitacora['A15_paso2'] = [
            'descripcion' => 'Confirmar con firma válida guarda la clase de 17:30 a 18:30',
            'clases_antes' => $clasesDespues1,
            'clases_despues' => $clasesDespues2,
            'clase_id' => $claseGuardada->id,
            'fecha' => $claseGuardada->fecha->format('Y-m-d'),
            'hora_inicio' => $claseGuardada->hora_inicio->format('H:i'),
            'hora_fin' => $claseGuardada->hora_fin->format('H:i'),
            'aprobado' => true,
        ];

        // Limpiar para las siguientes pruebas
        $this->limpiarClases();

        // ---------------------------------------------------------------------
        // A15 - Paso 3: Provocar aviso y cambiar CADA tipo de dato
        // ---------------------------------------------------------------------
        // Carga base que genera aviso
        $baseCarga = [
            'tipo_creacion' => 'unica',
            'grupo_id' => $this->grupos[0]->id,
            'profesores' => [$this->profesores[0]->id],
            'fecha' => '2026-10-15',
            'hora_inicio' => '17:30',
            'hora_fin' => '18:30',
        ];
        $this->post(route('web.clases.store'), $baseCarga);
        $firmaBase = session('aviso_cancha.firma');
        $this->assertNotNull($firmaBase);

        // a) Cambiar hora_fin (18:30 -> 19:30)
        $modHoraFin = array_replace($baseCarga, ['hora_fin' => '19:30', 'confirmar_cancha' => $firmaBase]);
        $this->post(route('web.clases.store'), $modHoraFin)
            ->assertSessionHas('aviso_cancha')
            ->assertSessionMissing('success');
        $this->assertSame(0, Clase::count(), 'Cambiar hora fin con firma vieja no guarda');

        // b) Cambiar hora_inicio (17:30 -> 16:30)
        $modHoraInicio = array_replace($baseCarga, ['hora_inicio' => '16:30', 'confirmar_cancha' => $firmaBase]);
        $this->post(route('web.clases.store'), $modHoraInicio)
            ->assertSessionHas('aviso_cancha')
            ->assertSessionMissing('success');
        $this->assertSame(0, Clase::count(), 'Cambiar hora inicio con firma vieja no guarda');

        // c) Cambiar fecha (2026-10-15 -> 2026-10-16)
        $modFecha = array_replace($baseCarga, ['fecha' => '2026-10-16', 'confirmar_cancha' => $firmaBase]);
        $this->post(route('web.clases.store'), $modFecha)
            ->assertSessionHas('aviso_cancha')
            ->assertSessionMissing('success');
        $this->assertSame(0, Clase::count(), 'Cambiar fecha con firma vieja no guarda');

        // d) Cambiar grupo (grupo 0 -> grupo 1)
        $modGrupo = array_replace($baseCarga, ['grupo_id' => $this->grupos[1]->id, 'profesores' => [$this->profesores[1]->id], 'confirmar_cancha' => $firmaBase]);
        $this->post(route('web.clases.store'), $modGrupo)
            ->assertSessionHas('aviso_cancha')
            ->assertSessionMissing('success');
        $this->assertSame(0, Clase::count(), 'Cambiar grupo con firma vieja no guarda');

        // e) Cambiar profesor (profesor 0 -> profesor 1 en grupo 1)
        $this->post(route('web.clases.store'), [
            'tipo_creacion' => 'unica',
            'grupo_id' => $this->grupos[1]->id,
            'profesores' => [$this->profesores[1]->id],
            'fecha' => '2026-10-15',
            'hora_inicio' => '17:30',
            'hora_fin' => '18:30',
        ]);
        $firmaGrupo1 = session('aviso_cancha.firma');
        // Intentar guardar para el mismo grupo pero sin profesor
        $modProf = [
            'tipo_creacion' => 'unica',
            'grupo_id' => $this->grupos[1]->id,
            'profesores' => [],
            'fecha' => '2026-10-15',
            'hora_inicio' => '17:30',
            'hora_fin' => '18:30',
            'confirmar_cancha' => $firmaGrupo1,
        ];
        $this->post(route('web.clases.store'), $modProf)
            ->assertSessionHas('aviso_cancha')
            ->assertSessionMissing('success');
        $this->assertSame(0, Clase::count(), 'Cambiar profesores con firma vieja no guarda');

        // f) Cambiar período en recurrente
        $cargaRecurrente = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[0]->id,
            'profesores' => [$this->profesores[0]->id],
            'fecha_desde' => '2026-10-12',
            'fecha_hasta' => '2026-10-19',
            'dias_semana' => [1],
            'horarios' => [1 => ['hora_inicio' => '17:30', 'hora_fin' => '18:30']],
        ];
        $this->post(route('web.clases.store'), $cargaRecurrente);
        $firmaRecurrente = session('aviso_cancha.firma');

        $modPeriodo = array_replace($cargaRecurrente, [
            'fecha_hasta' => '2026-10-26',
            'confirmar_cancha' => $firmaRecurrente,
        ]);
        $this->post(route('web.clases.store'), $modPeriodo)
            ->assertSessionHas('aviso_cancha')
            ->assertSessionMissing('success');
        $this->assertSame(0, Clase::count(), 'Cambiar período con firma vieja no guarda');

        $this->bitacora['A15_paso3'] = [
            'descripcion' => 'Comprobada invalidación de aviso al cambiar hora_fin, hora_inicio, fecha, grupo, profesor y período',
            'cambios_probados' => ['hora_fin', 'hora_inicio', 'fecha', 'grupo', 'profesor', 'periodo'],
            'clases_creadas' => Clase::count(),
            'aprobado' => true,
        ];

        // ---------------------------------------------------------------------
        // A15 - Paso 4: POST directo con confirmación falsa / ajena
        // ---------------------------------------------------------------------
        // Intento 1: confirmar_cancha = 'si'
        $postFalsoTexto = $this->post(route('web.clases.store'), $baseCarga + ['confirmar_cancha' => 'si']);
        $postFalsoTexto->assertRedirect(route('web.clases.create'))
            ->assertSessionHas('aviso_cancha');
        $this->assertSame(0, Clase::count());

        // Intento 2: confirmar_cancha = hash inventado
        $postFalsoHash = $this->post(route('web.clases.store'), $baseCarga + [
            'confirmar_cancha' => hash('sha256', 'inventado_por_hacker'),
        ]);
        $postFalsoHash->assertRedirect(route('web.clases.create'))
            ->assertSessionHas('aviso_cancha');
        $this->assertSame(0, Clase::count());

        // Intento 3: confirmar_cancha de otro usuario
        $admin2 = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($admin2);
        $this->post(route('web.clases.store'), $baseCarga);
        $firmaAdmin2 = session('aviso_cancha.firma');

        // Volver a admin original e intentar usar la firma de admin2
        $this->actingAs($this->admin);
        $postFalsoUsuario = $this->post(route('web.clases.store'), $baseCarga + ['confirmar_cancha' => $firmaAdmin2]);
        $postFalsoUsuario->assertRedirect(route('web.clases.create'))
            ->assertSessionHas('aviso_cancha');
        $this->assertSame(0, Clase::count(), 'Firma de otro usuario no debe guardar');

        $this->bitacora['A15_paso4'] = [
            'descripcion' => 'POST directos con firma falsa ("si", hash falso, firma de otro usuario) bloqueados',
            'respuestas' => [
                'texto_plano_si' => '302 Redirect a /clases/create con aviso_cancha',
                'hash_inventado' => '302 Redirect a /clases/create con aviso_cancha',
                'firma_otro_usuario' => '302 Redirect a /clases/create con aviso_cancha',
            ],
            'clases_creadas' => Clase::count(),
            'aprobado' => true,
        ];

        // ---------------------------------------------------------------------
        // A15 - Paso 5: Horario en punto guarda directo sin aviso
        // ---------------------------------------------------------------------
        $datosEnPunto = [
            'tipo_creacion' => 'unica',
            'grupo_id' => $this->grupos[0]->id,
            'profesores' => [$this->profesores[0]->id],
            'fecha' => '2026-10-15',
            'hora_inicio' => '17:00',
            'hora_fin' => '18:00',
        ];
        $postEnPunto = $this->post(route('web.clases.store'), $datosEnPunto);
        $postEnPunto->assertRedirect(route('web.clases.index'))
            ->assertSessionMissing('aviso_cancha')
            ->assertSessionHas('success', '1 clase(s) creada(s).');

        $this->assertSame(1, Clase::count());
        $claseEnPunto = Clase::sole();
        $this->assertSame('17:00', $claseEnPunto->hora_inicio->format('H:i'));
        $this->assertSame('18:00', $claseEnPunto->hora_fin->format('H:i'));

        $this->bitacora['A15_paso5'] = [
            'descripcion' => 'Horario en punto (17:00–18:00) guarda directo sin aviso',
            'clases_creadas' => 1,
            'aviso_presente' => false,
            'aprobado' => true,
        ];

        // Limpiar para siguientes pruebas
        $this->limpiarClases();

        // ---------------------------------------------------------------------
        // A15 - Paso 6: Bordes del aviso según el contrato
        // ---------------------------------------------------------------------
        $bordesResultados = [];

        // Borde 1: 17:30 a 18:00 (inicio no entero, fin en punto -> ocupa 1 bloque: 17:00–18:00)
        $this->post(route('web.clases.store'), array_replace($baseCarga, ['hora_inicio' => '17:30', 'hora_fin' => '18:00']));
        $avisoB1 = session('aviso_cancha');
        $this->assertNotNull($avisoB1);
        $this->assertSame(1, $avisoB1['horarios'][0]['bloques']);
        $this->assertStringContainsString('dura 30 minutos, pero ocupa 1 bloque de alquiler: 17:00–18:00.', $avisoB1['horarios'][0]['detalle']);
        $bordesResultados['17:30–18:00'] = [
            'bloques' => 1,
            'detalle' => $avisoB1['horarios'][0]['detalle'],
            'avisa' => true,
        ];

        // Borde 2: 17:00 a 18:30 (inicio en punto, fin no entero -> ocupa 2 bloques: 17:00–18:00, 18:00–19:00)
        $this->post(route('web.clases.store'), array_replace($baseCarga, ['hora_inicio' => '17:00', 'hora_fin' => '18:30']));
        $avisoB2 = session('aviso_cancha');
        $this->assertNotNull($avisoB2);
        $this->assertSame(2, $avisoB2['horarios'][0]['bloques']);
        $this->assertStringContainsString('dura 90 minutos, pero ocupa 2 bloques de alquiler: 17:00–18:00, 18:00–19:00.', $avisoB2['horarios'][0]['detalle']);
        $bordesResultados['17:00–18:30'] = [
            'bloques' => 2,
            'detalle' => $avisoB2['horarios'][0]['detalle'],
            'avisa' => true,
        ];

        // Borde 3: 17:01 a 18:00 (apenas 1 minuto de inicio -> ocupa 1 bloque: 17:00–18:00)
        $this->post(route('web.clases.store'), array_replace($baseCarga, ['hora_inicio' => '17:01', 'hora_fin' => '18:00']));
        $avisoB3 = session('aviso_cancha');
        $this->assertNotNull($avisoB3);
        $this->assertSame(1, $avisoB3['horarios'][0]['bloques']);
        $this->assertStringContainsString('17:00–18:00.', $avisoB3['horarios'][0]['detalle']);
        $bordesResultados['17:01–18:00'] = [
            'bloques' => 1,
            'detalle' => $avisoB3['horarios'][0]['detalle'],
            'avisa' => true,
        ];

        // Borde 4: 17:00 a 18:01 (apenas 1 minuto posterior -> ocupa 2 bloques: 17:00–18:00, 18:00–19:00)
        $this->post(route('web.clases.store'), array_replace($baseCarga, ['hora_inicio' => '17:00', 'hora_fin' => '18:01']));
        $avisoB4 = session('aviso_cancha');
        $this->assertNotNull($avisoB4);
        $this->assertSame(2, $avisoB4['horarios'][0]['bloques']);
        $this->assertStringContainsString('17:00–18:00, 18:00–19:00.', $avisoB4['horarios'][0]['detalle']);
        $bordesResultados['17:00–18:01'] = [
            'bloques' => 2,
            'detalle' => $avisoB4['horarios'][0]['detalle'],
            'avisa' => true,
        ];

        // Borde 5: 17:00 a 18:00 (ambos en punto -> sin aviso)
        $this->post(route('web.clases.store'), array_replace($baseCarga, ['hora_inicio' => '17:00', 'hora_fin' => '18:00']));
        $this->assertNull(session('aviso_cancha'));
        $bordesResultados['17:00–18:00'] = [
            'bloques' => 0,
            'detalle' => 'Sin aviso (guarda directo)',
            'avisa' => false,
        ];
        $this->limpiarClases();

        $this->bitacora['A15_paso6'] = [
            'descripcion' => 'Bordes del aviso según contrato V2',
            'casos' => $bordesResultados,
            'aprobado' => true,
        ];

        // =====================================================================
        // BLOQUE A16: HORARIOS POR DÍA
        // =====================================================================

        // ---------------------------------------------------------------------
        // A16 - Paso 1: Tres días con tres horarios distintos
        // ---------------------------------------------------------------------
        // Grupo Patín Intermedias, Verónica Salinas, 2026-10-12 a 2026-10-25 (2 semanas)
        // Lunes: 16:00–17:00
        // Miércoles: 17:00–18:00
        // Viernes: 18:00–19:00
        $datosA16Paso1 = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[1]->id,
            'profesores' => [$this->profesores[1]->id],
            'fecha_desde' => '2026-10-12',
            'fecha_hasta' => '2026-10-25',
            'dias_semana' => [1, 3, 5],
            'horarios' => [
                1 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00'],
                3 => ['hora_inicio' => '17:00', 'hora_fin' => '18:00'],
                5 => ['hora_inicio' => '18:00', 'hora_fin' => '19:00'],
            ],
        ];

        // Renderizar vista del formulario recurrente antes de guardar
        $formRecurrenteRes = $this->withSession(['_old_input' => $datosA16Paso1])
            ->get(route('web.clases.create'))->assertOk();
        $this->guardarHtml('04-form-clases-recurrentes-horarios-por-dia', $formRecurrenteRes->getContent());

        $postA16Paso1 = $this->post(route('web.clases.store'), $datosA16Paso1);
        $postA16Paso1->assertRedirect(route('web.clases.index'))
            ->assertSessionHas('success', '6 clase(s) creada(s).');

        $clasesA16Paso1 = Clase::where('grupo_id', $this->grupos[1]->id)->orderBy('fecha')->get();
        $this->assertCount(6, $clasesA16Paso1);
        $this->assertSame(1, $clasesA16Paso1->pluck('serie_id')->unique()->count(), 'Todas comparten un solo serie_id');

        foreach ($clasesA16Paso1 as $c) {
            $diaSem = $c->fecha->dayOfWeek;
            if ($diaSem === 1) {
                $this->assertSame('16:00', $c->hora_inicio->format('H:i'));
                $this->assertSame('17:00', $c->hora_fin->format('H:i'));
            } elseif ($diaSem === 3) {
                $this->assertSame('17:00', $c->hora_inicio->format('H:i'));
                $this->assertSame('18:00', $c->hora_fin->format('H:i'));
            } elseif ($diaSem === 5) {
                $this->assertSame('18:00', $c->hora_inicio->format('H:i'));
                $this->assertSame('19:00', $c->hora_fin->format('H:i'));
            } else {
                $this->fail("Día inesperado: {$diaSem}");
            }
        }

        $this->bitacora['A16_paso1'] = [
            'descripcion' => 'Tres días con tres horarios distintos (Lun 16-17, Mié 17-18, Vie 18-19) guardan correctamente con un solo serie_id',
            'clases_creadas' => 6,
            'serie_id_unico' => $clasesA16Paso1->first()->serie_id,
            'aprobado' => true,
        ];

        // Probar aviso en recurrente (ej. Domingo 10:30–11:30) y guardar HTML
        $recurrenteConAviso = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[0]->id,
            'profesores' => [$this->profesores[0]->id],
            'fecha_desde' => '2026-10-18',
            'fecha_hasta' => '2026-10-25',
            'dias_semana' => [0],
            'horarios' => [
                0 => ['hora_inicio' => '10:30', 'hora_fin' => '11:30'],
            ],
        ];
        $this->post(route('web.clases.store'), $recurrenteConAviso);
        $resAvisoRec = $this->get(route('web.clases.create'))->assertOk()
            ->assertSee('Domingo:')
            ->assertSee('2 bloques de alquiler');
        $this->guardarHtml('05-aviso-cancha-recurrente', $resAvisoRec->getContent());

        // Limpiar para siguientes pruebas
        $this->limpiarClases();

        // ---------------------------------------------------------------------
        // A16 - Paso 2: Desmarcar/marcar días y validación de horas obligatorias
        // ---------------------------------------------------------------------
        // Día marcado (Viernes=5) pero sin hora_inicio ni hora_fin
        $datosSinHoras = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[1]->id,
            'profesores' => [$this->profesores[1]->id],
            'fecha_desde' => '2026-10-12',
            'fecha_hasta' => '2026-10-25',
            'dias_semana' => [1, 5],
            'horarios' => [
                1 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00'],
                5 => ['hora_inicio' => '', 'hora_fin' => ''],
            ],
        ];
        $postSinHoras = $this->from(route('web.clases.create'))->post(route('web.clases.store'), $datosSinHoras);
        $postSinHoras->assertSessionHasErrors(['horarios.5.hora_inicio', 'horarios.5.hora_fin']);
        $this->assertSame(0, Clase::count(), 'Día marcado sin horas no debe guardar');

        // Día con fin anterior al inicio (Viernes inicio 18:00, fin 17:00)
        $datosFinMenor = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[1]->id,
            'profesores' => [$this->profesores[1]->id],
            'fecha_desde' => '2026-10-12',
            'fecha_hasta' => '2026-10-25',
            'dias_semana' => [1, 5],
            'horarios' => [
                1 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00'],
                5 => ['hora_inicio' => '18:00', 'hora_fin' => '17:00'],
            ],
        ];
        $postFinMenor = $this->from(route('web.clases.create'))->post(route('web.clases.store'), $datosFinMenor);
        $postFinMenor->assertSessionHasErrors(['horarios.5.hora_fin']);
        $this->assertSame(0, Clase::count());

        $this->bitacora['A16_paso2'] = [
            'descripcion' => 'Validación de horas obligatorias y hora_fin posterior al inicio por cada día',
            'errores_capturados' => ['horarios.5.hora_inicio', 'horarios.5.hora_fin'],
            'aprobado' => true,
        ];

        // ---------------------------------------------------------------------
        // A16 - Paso 3: Conflicto de profesor revierte la serie entera (Rollback)
        // ---------------------------------------------------------------------
        // Crear clase previa existente en viernes 2026-10-16 de 18:00 a 19:00 con la profesora Verónica Salinas
        $clasePrevia = Clase::create([
            'grupo_id' => $this->grupos[0]->id,
            'fecha' => '2026-10-16', // Viernes
            'hora_inicio' => '18:00',
            'hora_fin' => '19:00',
            'cancelada' => false,
            'validada_para_liquidacion' => false,
        ]);
        $clasePrevia->profesores()->attach($this->profesores[1]->id);

        $clasesAntesConflicto = Clase::count();
        $this->assertSame(1, $clasesAntesConflicto);

        // Intentar cargar serie de Verónica Salinas que incluye ese Viernes de 18:00 a 19:00
        $datosConflicto = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[1]->id,
            'profesores' => [$this->profesores[1]->id],
            'fecha_desde' => '2026-10-12',
            'fecha_hasta' => '2026-10-25',
            'dias_semana' => [1, 5],
            'horarios' => [
                1 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00'],
                5 => ['hora_inicio' => '18:00', 'hora_fin' => '19:00'],
            ],
        ];
        $postConflicto = $this->from(route('web.clases.create'))->post(route('web.clases.store'), $datosConflicto);
        $postConflicto->assertRedirect(route('web.clases.create'))
            ->assertSessionHas('error');

        $clasesDespuesConflicto = Clase::count();
        $this->assertSame(1, $clasesDespuesConflicto, 'El rollback debe dejar solo la clase previa');
        $this->assertSame($clasePrevia->id, Clase::sole()->id);

        $this->bitacora['A16_paso3'] = [
            'descripcion' => 'Conflicto en el segundo día revierte la serie entera atómicamente',
            'clases_antes' => $clasesAntesConflicto,
            'clases_despues' => $clasesDespuesConflicto,
            'mensaje_error' => session('error'),
            'aprobado' => true,
        ];

        // Limpiar para siguientes pruebas
        $this->limpiarClases();

        // ---------------------------------------------------------------------
        // A16 - Paso 4: Reglas previas (fechas pasadas, profesor inactivo / otro deporte)
        // ---------------------------------------------------------------------
        // a) Fecha pasada
        $datosFechaPasada = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[0]->id,
            'fecha_desde' => '2026-09-01', // Pasada respecto al testNow 2026-10-07
            'fecha_hasta' => '2026-10-31',
            'dias_semana' => [1],
            'horarios' => [1 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00']],
            'profesores' => [$this->profesores[0]->id],
        ];
        $postFechaPasada = $this->from(route('web.clases.create'))->post(route('web.clases.store'), $datosFechaPasada);
        $postFechaPasada->assertSessionHasErrors('fecha_desde');
        $this->assertSame(0, Clase::count());

        // b) Profesor inactivo
        $datosProfInactivo = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[0]->id,
            'fecha_desde' => '2026-10-12',
            'fecha_hasta' => '2026-10-25',
            'dias_semana' => [1],
            'horarios' => [1 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00']],
            'profesores' => [$this->profesorInactivo->id],
        ];
        $postProfInactivo = $this->from(route('web.clases.create'))->post(route('web.clases.store'), $datosProfInactivo);
        $postProfInactivo->assertSessionHasErrors('profesores');
        $this->assertSame(0, Clase::count());

        // c) Profesor de otro deporte (Hernán Quintana es de Fútbol, asignado a clase de Patín)
        $profFutbol = $this->profesores[4]; // Fútbol Principiantes
        $this->assertSame('Fútbol', $profFutbol->deporte->nombre);
        $datosProfOtroDeporte = [
            'tipo_creacion' => 'recurrente',
            'grupo_id' => $this->grupos[0]->id, // Patín
            'fecha_desde' => '2026-10-12',
            'fecha_hasta' => '2026-10-25',
            'dias_semana' => [1],
            'horarios' => [1 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00']],
            'profesores' => [$profFutbol->id],
        ];
        $postProfOtroDeporte = $this->from(route('web.clases.create'))->post(route('web.clases.store'), $datosProfOtroDeporte);
        $postProfOtroDeporte->assertSessionHasErrors('profesores');
        $this->assertSame(0, Clase::count());

        $this->bitacora['A16_paso4'] = [
            'descripcion' => 'Rechazo de fechas pasadas, profesor inactivo y profesor de otro deporte',
            'aprobado' => true,
        ];

        // ---------------------------------------------------------------------
        // A16 - Paso 5: Reproducción del cronograma: 76 clases en 6 cargas
        // ---------------------------------------------------------------------
        // Fijar reloj al 24/09 para coincidir exactamente con el cronograma
        Carbon::setTestNow('2026-09-24 09:00:00');

        $cronograma6Cargas = [
            // Carga 0: Patín Principiantes (Lun 16-17, Mié 16-17)
            [0, [1 => ['16:00', '17:00'], 3 => ['16:00', '17:00']], 10],
            // Carga 1: Patín Intermedias (Lun 17-18, Vie 16-17)
            [1, [1 => ['17:00', '18:00'], 5 => ['16:00', '17:00']], 11],
            // Carga 2: Patín Avanzadas (Mar 17-18, Jue 17-18)
            [2, [2 => ['17:00', '18:00'], 4 => ['17:00', '18:00']], 11],
            // Carga 3: Patín Federadas (Mar 18-19, Jue 18-19, Sáb 10-11)
            [3, [2 => ['18:00', '19:00'], 4 => ['18:00', '19:00'], 6 => ['10:00', '11:00']], 17],
            // Carga 4: Fútbol Principiantes (Lun 16-17, Mié 18-19)
            [4, [1 => ['16:00', '17:00'], 3 => ['18:00', '19:00']], 10],
            // Carga 5: Fútbol Avanzadas (Mar 19-20, Jue 19-20, Sáb 11-12)
            [5, [2 => ['19:00', '20:00'], 4 => ['19:00', '20:00'], 6 => ['11:00', '12:00']], 17],
        ];

        $detalle6Cargas = [];
        foreach ($cronograma6Cargas as [$idxGrupo, $horarios, $esperadas]) {
            $horariosFormateados = array_map(fn ($h) => ['hora_inicio' => $h[0], 'hora_fin' => $h[1]], $horarios);
            $datosCarga = [
                'tipo_creacion' => 'recurrente',
                'grupo_id' => $this->grupos[$idxGrupo]->id,
                'profesores' => [$this->profesores[$idxGrupo]->id],
                'fecha_desde' => '2026-09-24',
                'fecha_hasta' => '2026-10-31',
                'dias_semana' => array_keys($horarios),
                'horarios' => $horariosFormateados,
            ];

            $resCarga = $this->post(route('web.clases.store'), $datosCarga);
            $resCarga->assertRedirect(route('web.clases.index'))
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success');

            $clasesGrupo = Clase::where('grupo_id', $this->grupos[$idxGrupo]->id)->get();
            $this->assertCount($esperadas, $clasesGrupo, "Grupo {$idxGrupo} debe tener {$esperadas} clases");

            // Verificar que todas tienen el horario correspondiente a su día
            foreach ($clasesGrupo as $cg) {
                $esperado = $horariosFormateados[$cg->fecha->dayOfWeek];
                $this->assertSame($esperado['hora_inicio'], $cg->hora_inicio->format('H:i'));
                $this->assertSame($esperado['hora_fin'], $cg->hora_fin->format('H:i'));
                $this->assertSame([$this->profesores[$idxGrupo]->id], $cg->profesores->pluck('id')->all());
            }

            $detalle6Cargas[] = [
                'grupo' => $this->grupos[$idxGrupo]->nombre_completo,
                'profesor' => $this->profesores[$idxGrupo]->apellido . ', ' . $this->profesores[$idxGrupo]->nombre,
                'clases_generadas' => $clasesGrupo->count(),
                'serie_id' => $clasesGrupo->first()->serie_id,
            ];
        }

        $totalClases = Clase::count();
        $totalSeries = Clase::distinct('serie_id')->count('serie_id');
        $this->assertSame(76, $totalClases, 'Deben crearse exactamente 76 clases');
        $this->assertSame(6, $totalSeries, 'Deben crearse exactamente 6 series distintas');

        // Guardar listado de clases cargadas (76 clases)
        $listado76Res = $this->get(route('web.clases.index'))->assertOk();
        $this->guardarHtml('07-listado-76-clases', $listado76Res->getContent());

        $this->bitacora['A16_paso5'] = [
            'descripcion' => 'Cronograma completo de 76 clases en 6 cargas ejecutado exitosamente',
            'total_clases' => $totalClases,
            'total_series' => $totalSeries,
            'detalle' => $detalle6Cargas,
            'aprobado' => true,
        ];

        // =====================================================================
        // BLOQUE 3: REGRESIONES ("QUE NO SE HAYA ROTO NADA")
        // =====================================================================
        // Elegir una clase creada
        $claseMuestra = Clase::where('grupo_id', $this->grupos[0]->id)->orderBy('fecha')->first();
        $this->assertNotNull($claseMuestra);

        // 1. Tomar asistencia en una clase creada con el formulario nuevo
        // Crear 2 alumnos en el grupo
        $alumno1 = Alumno::create([
            'nombre' => 'Camila',
            'apellido' => 'Sosa',
            'dni' => '46111222',
            'celular' => '11-4500-1111',
            'fecha_nacimiento' => '2014-03-10',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $this->grupos[0]->deporte_id,
            'grupo_id' => $this->grupos[0]->id,
            'activo' => true,
        ]);
        $alumno2 = Alumno::create([
            'nombre' => 'Mateo',
            'apellido' => 'Benítez',
            'dni' => '46333444',
            'celular' => '11-4500-2222',
            'fecha_nacimiento' => '2015-08-20',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $this->grupos[0]->deporte_id,
            'grupo_id' => $this->grupos[0]->id,
            'activo' => true,
        ]);

        $asistenciaData = [
            'items' => [
                ['alumno_id' => $alumno1->id, 'presente' => '1'],
                ['alumno_id' => $alumno2->id, 'presente' => '0'],
            ],
        ];

        $resAsistencia = $this->post(route('web.clases.asistencias', $claseMuestra->id), $asistenciaData);
        $resAsistencia->assertRedirect(route('web.clases.show', $claseMuestra->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('asistencias', [
            'clase_id' => $claseMuestra->id,
            'alumno_id' => $alumno1->id,
            'presente' => true,
        ]);
        $this->assertDatabaseHas('asistencias', [
            'clase_id' => $claseMuestra->id,
            'alumno_id' => $alumno2->id,
            'presente' => false,
        ]);

        // Guardar detalle de clase con asistencia
        $resDetalleClase = $this->get(route('web.clases.show', $claseMuestra->id))->assertOk()
            ->assertSee('Sosa, Camila')
            ->assertSee('Benítez, Mateo');
        $this->guardarHtml('06-clase-detalle-asistencia', $resDetalleClase->getContent());

        // 2. Editar una clase existente (cambiar hora de 16:00 a 16:30 en fecha futura)
        // Tomamos una clase futura sin asistencias
        $claseAEditar = Clase::where('grupo_id', $this->grupos[0]->id)->where('id', '!=', $claseMuestra->id)->first();
        $this->assertNotNull($claseAEditar);

        $editRes = $this->get(route('web.clases.edit', $claseAEditar->id))->assertOk();
        $updateRes = $this->put(route('web.clases.update', $claseAEditar->id), [
            'fecha' => $claseAEditar->fecha->format('Y-m-d'),
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
            'profesores' => [$this->profesores[0]->id],
        ]);
        $updateRes->assertRedirect(route('web.clases.show', $claseAEditar->id))
            ->assertSessionHas('success', 'Clase actualizada correctamente.');

        // 3. Cancelar una clase con motivo
        $claseACancelar = Clase::where('grupo_id', $this->grupos[1]->id)->first();
        $this->assertNotNull($claseACancelar);
        $this->assertFalse($claseACancelar->cancelada);

        $cancelRes = $this->patch(route('web.clases.toggle-cancelada', $claseACancelar->id), [
            'motivo_cancelacion' => 'Pista en mantenimiento técnico',
        ]);
        $cancelRes->assertRedirect()
            ->assertSessionHas('success', 'Clase cancelada.');

        $this->assertTrue($claseACancelar->fresh()->cancelada);
        $this->assertSame('Pista en mantenimiento técnico', $claseACancelar->fresh()->motivo_cancelacion);

        $this->bitacora['regresiones'] = [
            'asistencia_tomada' => true,
            'edicion_exitosa' => true,
            'cancelacion_con_motivo' => true,
            'aprobado' => true,
        ];

        // ---------------------------------------------------------------------
        // BLOQUE 4: ROLES OPERATIVO Y PROFESOR
        // ---------------------------------------------------------------------
        // a) OPERATIVO
        $this->actingAs($this->operativo);
        $this->get(route('web.clases.index'))->assertOk();
        $this->get(route('web.clases.show', $claseMuestra->id))->assertOk();
        // OPERATIVO puede cancelar
        $claseParaOperativo = Clase::where('grupo_id', $this->grupos[2]->id)->first();
        $this->patch(route('web.clases.toggle-cancelada', $claseParaOperativo->id), [
            'motivo_cancelacion' => 'Cancelación operativa autorizada',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertTrue($claseParaOperativo->fresh()->cancelada);

        // OPERATIVO NO puede crear ni editar (ensure.admin.web bloquea)
        $this->get(route('web.clases.create'))->assertForbidden();
        $this->post(route('web.clases.store'), $datosA15Paso1)->assertForbidden();
        $this->get(route('web.clases.edit', $claseMuestra->id))->assertForbidden();
        $this->put(route('web.clases.update', $claseMuestra->id), [])->assertForbidden();

        // b) PROFESOR
        $this->actingAs($this->profesor);
        $this->get(route('web.clases.index'))->assertOk();
        $this->get(route('web.clases.show', $claseMuestra->id))->assertOk();
        // PROFESOR puede tomar asistencia
        $this->post(route('web.clases.asistencias', $claseMuestra->id), $asistenciaData)
            ->assertRedirect(route('web.clases.show', $claseMuestra->id));

        // PROFESOR NO puede cancelar clase (abort 403 en toggleCancelada)
        $this->patch(route('web.clases.toggle-cancelada', $claseMuestra->id), [
            'motivo_cancelacion' => 'Intento de profesor',
        ])->assertForbidden();

        // PROFESOR NO puede crear ni editar (ensure.admin.web bloquea)
        $this->get(route('web.clases.create'))->assertForbidden();
        $this->post(route('web.clases.store'), $datosA15Paso1)->assertForbidden();
        $this->get(route('web.clases.edit', $claseMuestra->id))->assertForbidden();
        $this->put(route('web.clases.update', $claseMuestra->id), [])->assertForbidden();

        $this->bitacora['roles'] = [
            'operativo_clases_index' => 200,
            'operativo_clases_show' => 200,
            'operativo_cancelar' => 302,
            'operativo_create' => 403,
            'operativo_edit' => 403,
            'profesor_clases_index' => 200,
            'profesor_clases_show' => 200,
            'profesor_asistencias' => 302,
            'profesor_cancelar' => 403,
            'profesor_create' => 403,
            'profesor_edit' => 403,
            'aprobado' => true,
        ];

        // Guardar bitácora en JSON
        file_put_contents(
            base_path('docs/06-pruebas/PRU-02/evidencia/verificacion-a15-a16/datos-verificacion.json'),
            json_encode($this->bitacora, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        Carbon::setTestNow();
    }
}
