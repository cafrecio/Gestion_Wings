<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Control Caso 14 sobre T108:
 * Primera carga por Excel: Subir por la carga real un Excel con «Inscripción: Sí» y con «No»,
 * también con dos filas del mismo DNI, y mirar qué queda.
 *
 * Se verifica a través del flujo HTTP real (web.primera-carga.revisar y web.primera-carga.cargar).
 */
class PrimeraCargaExcelRealT108Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $archivos = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:00:00');
        Storage::fake('local');

        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);

        // Catálogos requeridos por el Excel (Fútbol Juveniles y Patín Inicial)
        foreach ([['Fútbol', 'Juveniles', 48000], ['Patín', 'Inicial', 52000]] as [$nombre, $nivel, $precio]) {
            $deporte = Deporte::create(['nombre' => $nombre, 'tipo_liquidacion' => 'HORA', 'activo' => true]);
            $n = Nivel::firstOrCreate(['nombre' => $nivel]);
            $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $n->id, 'activo' => true]);
            GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => $precio, 'activo' => true]);
        }

        // Matrícula de inscripción configurada en $8.000
        Configuracion::updateOrCreate(
            ['clave' => 'monto_matricula'],
            ['valor' => '8000.00']
        );

        if (DB::getSchemaBuilder()->hasTable('primera_carga')) {
            DB::table('primera_carga')->where('id', 1)->update(['estado' => 'PENDIENTE', 'detalle' => null]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->archivos as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Prepara un Excel real a partir de la plantilla/ejemplo club-corregido.xlsx:
     * - Fila 2: DNI 50.300.001 (Fútbol) -> Debe inscripción = 'Sí'
     * - Fila 3: DNI 50300002 (Fútbol) -> Debe inscripción = 'No'
     * - Fila 4: DNI 50300003 (Patín) -> Debe inscripción = 'Sí'
     * - Fila 5: DNI 50300003 (Fútbol) -> Debe inscripción = 'Sí' (mismo DNI en segundo deporte)
     */
    private function crearExcelConCasos(): string
    {
        $origen = base_path('docs/05-pendientes/maqueta-primera-carga/ejemplos/club-corregido.xlsx');
        $libro = IOFactory::load($origen);
        $hoja = $libro->getSheetByName('Alumnos');

        // Fila 2: Sí debe inscripción
        $hoja->setCellValue('M2', 'Sí');

        // Fila 3: No debe inscripción
        $hoja->setCellValue('M3', 'No');

        // Fila 4: Carla Vidal (Patín Inicial) con DNI 50300003 -> Sí debe inscripción
        $hoja->setCellValue('M4', 'Sí');

        // Fila 5: Carla Vidal ahora en Fútbol Juveniles con el MISMO DNI 50300003 -> Sí debe inscripción
        $hoja->setCellValue('A5', '50300003');
        $hoja->setCellValue('B5', 'Vidal');
        $hoja->setCellValue('C5', 'Carla');
        $hoja->setCellValue('D5', $hoja->getCell('D4')->getValue());
        $hoja->setCellValue('E5', '01/09/2026');
        $hoja->setCellValue('F5', '1100000003');
        $hoja->setCellValue('G5', 'carla@example.invalid');
        $hoja->setCellValue('H5', 'Vidal, Laura');
        $hoja->setCellValue('I5', '1100000013');
        $hoja->setCellValue('J5', 'Fútbol');
        $hoja->setCellValue('K5', 'Juveniles');
        $hoja->setCellValue('L5', 'Fútbol / Juveniles / 2 clases');
        $hoja->setCellValue('M5', 'Sí');
        $hoja->setCellValue('N5', 'No');
        for ($c = 15; $c <= 38; $c++) {
            $hoja->setCellValue([$c, 5], null);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'excel-t108-') . '.xlsx';
        $this->archivos[] = $tempPath;
        (new Xlsx($libro))->save($tempPath);

        return $tempPath;
    }

    public function test_primera_carga_excel_subida_real_con_inscripcion_si_no_y_mismo_dni(): void
    {
        $archivoXlsx = $this->crearExcelConCasos();

        $uploadedFile = new UploadedFile(
            $archivoXlsx,
            'primera-carga-prueba.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // 1. Paso Revisar por HTTP real
        $respRevisar = $this->actingAs($this->admin)
            ->post(route('web.primera-carga.revisar'), [
                'archivo' => $uploadedFile,
            ]);

        $respRevisar->assertRedirect(route('web.primera-carga.index'));

        // Verificar que en la sesión no haya errores y que el resumen contemple las inscripciones únicas
        $revision = session('primera_carga_revision');
        $this->assertNotNull($revision, 'Faltó guardar la revisión en sesión.');
        $this->assertSame([], $revision['errores'], 'El archivo arrojó errores en la revisión: ' . json_encode($revision['errores']));
        $this->assertSame(4, $revision['resumen']['alumnos']);
        // Deben ser 2 personas con inscripción (50300001 y 50300003), no 3
        $this->assertSame(2, $revision['resumen']['inscripciones']);

        // 2. Paso Cargar por HTTP real
        $respCargar = $this->actingAs($this->admin)
            ->post(route('web.primera-carga.cargar'), [
                'confirmar' => '1',
            ]);

        $respCargar->assertRedirect(route('web.primera-carga.index'));

        // 3. Inspección exhaustiva de lo que quedó en la base de datos:
        // A. Se crearon 4 alumnos (2 de Fútbol, 1 de Patín, y la segunda ficha de Fútbol de Carla Vidal)
        $this->assertDatabaseCount('alumnos', 4);

        // B. En cargos_alumno, SOLO debe haber 2 cargos de inscripción (para 50300001 y 50300003)
        $cargos = CargoAlumno::where('tipo', 'INSCRIPCION')->get();
        $this->assertCount(2, $cargos, 'Debe haber exactamente 2 cargos de inscripción en total.');

        // Caso "Sí" (DNI 50.300.001 - Ana Luna): tiene cargo
        $alumno1 = Alumno::where('dni', '50300001')->sole();
        $cargo1 = CargoAlumno::where('alumno_id', $alumno1->id)->where('tipo', 'INSCRIPCION')->first();
        $this->assertNotNull($cargo1, 'Ana Luna (Inscripción: Sí) debe tener cargo de inscripción.');
        $this->assertEquals(5000.00, (float) $cargo1->monto_original);

        // Caso "No" (DNI 50300002 - Bruno Rey): NO tiene cargo
        $alumno2 = Alumno::where('dni', '50300002')->sole();
        $cargo2 = CargoAlumno::where('alumno_id', $alumno2->id)->where('tipo', 'INSCRIPCION')->first();
        $this->assertNull($cargo2, 'Bruno Rey (Inscripción: No) NO debe tener cargo de inscripción.');

        // Caso "Dos filas del mismo DNI" (DNI 50300003 - Carla Vidal en Patín y Fútbol):
        // Tiene 2 fichas de alumno (una por deporte)
        $fichasCarla = Alumno::where('dni', '50300003')->get();
        $this->assertCount(2, $fichasCarla, 'Carla Vidal debe tener 2 fichas de alumno.');

        // Pero entre ambas fichas, SOLO una tiene el cargo de inscripción, la segunda NO duplica
        $cargosCarla = CargoAlumno::whereIn('alumno_id', $fichasCarla->pluck('id'))->where('tipo', 'INSCRIPCION')->get();
        $this->assertCount(1, $cargosCarla, 'Carla Vidal (mismo DNI en dos filas) debe tener una ÚNICA inscripción.');

        // C. En inscripcion_personas: exactamente 2 DNIs registrados
        $personasInscriptas = DB::table('inscripcion_personas')->pluck('dni')->all();
        sort($personasInscriptas);
        $this->assertSame(['50300001', '50300003'], $personasInscriptas);
    }
}
