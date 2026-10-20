<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use App\Services\PagoCuotaService;
use App\Services\PrimeraCargaExcelService;
use App\Support\FechaIngresoRegla;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class FechaIngresoFuturaT50Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;
    private array $archivosTemp = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);

        $this->deporte = Deporte::first();
        $nivel = Nivel::first();

        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 25000,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->archivosTemp as $archivo) {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payloadAlumno(array $overrides = []): array
    {
        return array_merge([
            'dni' => '40123456',
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'fecha_nacimiento' => '2000-05-15',
            'fecha_alta' => '2026-10-20',
            'celular' => '11-4444-5555',
            'email' => 'juan@example.com',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ], $overrides);
    }

    public function test_fecha_ingreso_limite_exacto_ultimo_dia_mes_siguiente_acepta(): void
    {
        // Hoy 20/10/2026 -> límite 30/11/2026
        $this->assertSame('2026-11-30', FechaIngresoRegla::limiteMaximo()->format('Y-m-d'));

        $response = $this->actingAs($this->admin)->post(route('web.alumnos.store'), $this->payloadAlumno([
            'fecha_alta' => '2026-11-30',
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('web.alumnos.index'));

        $this->assertDatabaseHas('alumnos', [
            'dni' => '40123456',
            'fecha_alta' => '2026-11-30 00:00:00',
        ]);
        $this->assertDatabaseCount('alumnos', 1);
    }

    public function test_fecha_ingreso_dia_posterior_al_limite_rechaza_y_no_guarda_nada(): void
    {
        // 01/12/2026 es el día siguiente al 30/11/2026 -> debe rechazarse
        $response = $this->actingAs($this->admin)->post(route('web.alumnos.store'), $this->payloadAlumno([
            'fecha_alta' => '2026-12-01',
        ]));

        $response->assertSessionHasErrors(['fecha_alta']);
        $mensaje = session('errors')->first('fecha_alta');
        $this->assertSame('La fecha de ingreso no puede ser posterior al 30/11/2026.', $mensaje);

        // Nada debe haber sido guardado en la base de datos
        $this->assertDatabaseCount('alumnos', 0);
        $this->assertDatabaseCount('alumno_planes', 0);
        $this->assertDatabaseCount('deuda_cuotas', 0);
    }

    public function test_fecha_ingreso_pasada_y_de_hoy_aceptan(): void
    {
        // Fecha de hoy: 20/10/2026
        $r1 = $this->actingAs($this->admin)->post(route('web.alumnos.store'), $this->payloadAlumno([
            'dni' => '40111111',
            'fecha_alta' => '2026-10-20',
        ]));
        $r1->assertSessionHasNoErrors();

        // Fecha pasada: 15/09/2026 (mes cerrado requiere generar_cuota_actual)
        $r2 = $this->actingAs($this->admin)->post(route('web.alumnos.store'), $this->payloadAlumno([
            'dni' => '40222222',
            'fecha_alta' => '2026-09-15',
            'generar_cuota_actual' => 1,
        ]));
        $r2->assertSessionHasNoErrors();

        $this->assertDatabaseCount('alumnos', 2);
    }

    public function test_cambio_de_anio_en_diciembre_limite_es_31_de_enero(): void
    {
        Carbon::setTestNow('2026-12-15 11:00:00');

        $this->assertSame('2027-01-31', FechaIngresoRegla::limiteMaximo()->format('Y-m-d'));

        // Límite exacto: 31/01/2027 se acepta
        $r1 = $this->actingAs($this->admin)->post(route('web.alumnos.store'), $this->payloadAlumno([
            'dni' => '40333333',
            'fecha_alta' => '2027-01-31',
        ]));
        $r1->assertSessionHasNoErrors();

        // Día posterior: 01/02/2027 se rechaza
        $r2 = $this->actingAs($this->admin)->post(route('web.alumnos.store'), $this->payloadAlumno([
            'dni' => '40444444',
            'fecha_alta' => '2027-02-01',
        ]));
        $r2->assertSessionHasErrors(['fecha_alta']);
        $this->assertSame(
            'La fecha de ingreso no puede ser posterior al 31/01/2027.',
            session('errors')->first('fecha_alta')
        );

        $this->assertDatabaseMissing('alumnos', ['dni' => '40444444']);
    }

    public function test_edicion_permite_cambiar_fecha_hasta_el_limite_y_rechaza_posterior(): void
    {
        // Alumno creado con fecha de hoy
        $alumno = Alumno::create($this->payloadAlumno([
            'fecha_alta' => '2026-10-20',
        ]));
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-10-20',
            'activo' => true,
        ]);

        // Cambiar fecha a 30/11/2026 (límite válido) con motivo: se acepta
        $r1 = $this->actingAs($this->admin)->put(route('web.alumnos.update', $alumno), [
            'dni' => $alumno->dni,
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'fecha_nacimiento' => '2000-05-15',
            'fecha_alta' => '2026-11-30',
            'motivo_fecha_alta' => 'Corrección administrativa dentro de plazo',
            'celular' => $alumno->celular,
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ]);
        $r1->assertSessionHasNoErrors();
        $this->assertSame('2026-11-30', $alumno->fresh()->fecha_alta->format('Y-m-d'));

        // Intentar cambiar fecha a 01/12/2026 (fuera de límite) con motivo: se rechaza
        $r2 = $this->actingAs($this->admin)->put(route('web.alumnos.update', $alumno), [
            'dni' => $alumno->dni,
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'fecha_nacimiento' => '2000-05-15',
            'fecha_alta' => '2026-12-01',
            'motivo_fecha_alta' => 'Fecha fuera de límite',
            'celular' => $alumno->celular,
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ]);
        $r2->assertSessionHasErrors(['fecha_alta']);
        $this->assertSame('La fecha de ingreso no puede ser posterior al 30/11/2026.', session('errors')->first('fecha_alta'));

        // La fecha en base se mantiene en 2026-11-30 sin cambios
        $this->assertSame('2026-11-30', $alumno->fresh()->fecha_alta->format('Y-m-d'));
    }

    public function test_edicion_alumno_antiguo_con_fecha_fuera_de_limite_no_se_bloquea_si_no_se_cambia_la_fecha(): void
    {
        // Alumno viejo que ya tenía guardada una fecha futura fuera del límite actual
        $alumno = Alumno::create($this->payloadAlumno([
            'fecha_alta' => '2027-05-10',
        ]));
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2027-05-10',
            'activo' => true,
        ]);

        // Edición de teléfono sin cambiar la fecha_alta: debe permitirse sin error
        $response = $this->actingAs($this->admin)->put(route('web.alumnos.update', $alumno), [
            'dni' => $alumno->dni,
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'fecha_nacimiento' => '2000-05-15',
            'fecha_alta' => '2027-05-10',
            'celular' => '11-9999-8888',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('11-9999-8888', $alumno->fresh()->celular);
        $this->assertSame('2027-05-10', $alumno->fresh()->fecha_alta->format('Y-m-d'));
    }

    public function test_previsualizacion_cuota_alta_rechaza_fecha_posterior_al_limite(): void
    {
        $service = app(PagoCuotaService::class);

        // Fecha dentro del límite (30/11/2026) funciona correctamente
        $previewValida = $service->previsualizarCuotaAlta('2026-11-30', $this->plan->id);
        $this->assertSame('2026-11', $previewValida['periodo']);

        // Fecha fuera de límite (01/12/2026) lanza ValidationException
        $this->expectException(ValidationException::class);
        $service->previsualizarCuotaAlta('2026-12-01', $this->plan->id);
    }

    public function test_previsualizacion_cuota_endpoint_retorna_422_en_fecha_invalida(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('web.alumnos.cuota-alta-preview', [
            'fecha_alta' => '2026-12-01',
            'plan_id' => $this->plan->id,
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fecha_alta']);
        $this->assertSame(
            'La fecha de ingreso no puede ser posterior al 30/11/2026.',
            $response->json('errors.fecha_alta.0')
        );
    }

    public function test_previsualizacion_inscripcion_rechaza_fecha_posterior_al_limite(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('web.alumnos.inscripcion-preview', [
            'dni' => '40999999',
            'fecha_alta' => '2026-12-01',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fecha_alta']);
    }

    public function test_primera_carga_excel_revisar_marca_error_en_fecha_posterior_al_limite(): void
    {
        $ejemploPath = base_path('docs/05-pendientes/maqueta-primera-carga/ejemplos/club-corregido.xlsx');
        if (!file_exists($ejemploPath)) {
            $this->markTestSkipped('Archivo base de ejemplo no encontrado.');
        }

        // Modificar celda E3 (columna 5 = Ingreso de la primera fila de alumno) con fecha fuera de límite
        $libro = IOFactory::load($ejemploPath);
        $hoja = $libro->getSheetByName('Alumnos');
        $hoja->setCellValue('E3', '01/12/2026'); // Fuera de límite para hoy 20/10/2026

        $tempFile = tempnam(sys_get_temp_dir(), 'test_t50_');
        $this->archivosTemp[] = $tempFile;
        (new Xlsx($libro))->save($tempFile);

        $service = app(PrimeraCargaExcelService::class);
        $resultado = $service->revisar($tempFile);

        $this->assertNotEmpty($resultado['errores']);

        $errorIngreso = collect($resultado['errores'])->first(fn ($e) => $e['fila'] === 3 && $e['celda'] === 'E3');
        $this->assertNotNull($errorIngreso, 'Debe haber un error en la celda E3 por fecha de ingreso fuera de límite.');
        $this->assertSame('Fecha de ingreso fuera de límite.', $errorIngreso['mensaje']);
        $this->assertSame('La fecha de ingreso no puede ser posterior al 30/11/2026.', $errorIngreso['esperado']);

        // La fila con error no se incluye en filas a cargar
        $filasValidas = array_column($resultado['filas'], 'fila');
        $this->assertNotContains(3, $filasValidas);
    }
}
