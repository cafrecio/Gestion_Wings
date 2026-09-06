<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Services\CargaDeudaInicialExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportarDeudaInicialExcelCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $archivo;
    private Alumno $alumnaPatin;
    private Alumno $alumnoFutbol;

    protected function setUp(): void
    {
        parent::setUp();

        $patin = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION, 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Prueba']);
        $grupoPatin = Grupo::create(['deporte_id' => $patin->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $grupoFutbol = Grupo::create(['deporte_id' => $futbol->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $this->alumnaPatin = $this->crearAlumno('33456789', $patin->id, $grupoPatin->id);
        $this->alumnoFutbol = $this->crearAlumno('33456789', $futbol->id, $grupoFutbol->id);
        $this->archivo = sys_get_temp_dir().DIRECTORY_SEPARATOR.'deuda-inicial-'.uniqid('', true).'.xlsx';
    }

    protected function tearDown(): void
    {
        if (is_file($this->archivo)) {
            unlink($this->archivo);
        }
        parent::tearDown();
    }

    public function test_importa_toda_la_planilla_y_la_segunda_corrida_se_rechaza_sin_duplicar(): void
    {
        $this->crearExcel([
            ['33456789', 'Patín', 32000, '052025', 35000, '062025'],
            ['33456789', 'Fútbol', 40000, '062026', '', ''],
        ]);

        $this->artisan('wings:importar-deuda-inicial', ['archivo' => $this->archivo])
            ->expectsOutputToContain('3 deuda(s) creadas')
            ->assertSuccessful();

        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $this->alumnaPatin->id, 'periodo' => '2025-05', 'monto_original' => '32000.00']);
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $this->alumnoFutbol->id, 'periodo' => '2026-06', 'monto_original' => '40000.00']);

        $this->artisan('wings:importar-deuda-inicial', ['archivo' => $this->archivo])
            ->expectsOutputToContain('Ya existe una deuda para el período 2025-05')
            ->expectsOutputToContain('Ya existe una deuda para el período 2026-06')
            ->assertFailed();
        $this->assertDatabaseCount('deuda_cuotas', 3);
    }

    public function test_lista_todos_los_errores_y_no_escribe_nada(): void
    {
        $this->crearExcel([
            ['33456789', 'Patín', 1, '052025', '', ''],
            ['33456789', 'Patín', 30000, '132025', '', ''],
            ['99999999', 'Fútbol', 30000, '062026', '', ''],
        ]);
        $this->actualizarCelda('C2', 0);

        $resultado = app(CargaDeudaInicialExcelService::class)->validar($this->archivo);
        $mensajes = array_column($resultado['errores'], 'mensaje');
        $this->assertCount(4, $mensajes);
        $this->assertContains('El monto debe ser numérico y mayor que cero.', $mensajes);
        $this->assertContains('Se repite DNI + deporte; ya figura en la fila 2.', $mensajes);
        $this->assertContains('El período debe tener formato mmYYYY válido desde 2025.', $mensajes);
        $this->assertContains('No existe un alumno para ese DNI y deporte.', $mensajes);

        $this->artisan('wings:importar-deuda-inicial', ['archivo' => $this->archivo])->assertFailed();

        $this->assertDatabaseCount('deuda_cuotas', 0);
    }

    public function test_reversion_solo_borra_deudas_pendientes_e_intactas_del_mismo_excel(): void
    {
        $this->crearExcel([
            ['33456789', 'Patín', 32000, '052025', '', ''],
        ]);
        $this->artisan('wings:importar-deuda-inicial', ['archivo' => $this->archivo])->assertSuccessful();

        $this->artisan('wings:importar-deuda-inicial', ['archivo' => $this->archivo, '--revertir' => true])
            ->expectsOutputToContain('1 deuda(s) retiradas')
            ->assertSuccessful();

        $this->assertDatabaseCount('deuda_cuotas', 0);
        $this->assertDatabaseHas('alumnos', ['id' => $this->alumnaPatin->id]);
        $this->assertDatabaseCount('pagos', 0);
    }

    private function crearAlumno(string $dni, int $deporteId, int $grupoId): Alumno
    {
        return Alumno::create([
            'nombre' => 'Alumno',
            'apellido' => 'Prueba',
            'dni' => $dni,
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporteId,
            'grupo_id' => $grupoId,
            'fecha_alta' => '2025-05-01',
            'activo' => true,
        ]);
    }

    /** @param array<int, array<int, string|int|float>> $filas */
    private function crearExcel(array $filas): void
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->fromArray(['DNI', 'deporte', 'monto', 'mmYYYY', 'monto', 'mmYYYY'], null, 'A1');
        $hoja->getStyle('D:D')->getNumberFormat()->setFormatCode('@');
        foreach ($filas as $indice => $fila) {
            $hoja->fromArray($fila, null, 'A'.($indice + 2));
        }
        (new Xlsx($libro))->save($this->archivo);
    }

    private function actualizarCelda(string $coordenada, int $valor): void
    {
        $libro = IOFactory::load($this->archivo);
        $libro->getActiveSheet()->setCellValue($coordenada, $valor);
        (new Xlsx($libro))->save($this->archivo);
    }
}
