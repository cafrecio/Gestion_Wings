<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * El padron sale completo: tambien los alumnos que no deben nada.
 *
 * Esa es la razon de ser del archivo. Con la carga vieja, el alumno que no figuraba se
 * asumia sin deuda, asi que un olvido de Vanina y una persona sin deuda se veian igual.
 */
class ExportarPadronCargaInicialTest extends TestCase
{
    use RefreshDatabase;

    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->archivo = storage_path('app/testing/padron-'.uniqid().'.xlsx');
    }

    protected function tearDown(): void
    {
        if (is_file($this->archivo)) {
            unlink($this->archivo);
        }
        parent::tearDown();
    }

    public function test_el_padron_incluye_a_todos_los_alumnos_activos_con_la_columna_debe_vacia(): void
    {
        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->crearAlumno('Zapata', 'Ana', '30111111', $deporte, $grupo, true);
        $this->crearAlumno('Acosta', 'Beto', '30222222', $deporte, $grupo, true);
        $this->crearAlumno('Baja', 'Carlos', '30333333', $deporte, $grupo, false);

        $this->artisan('wings:exportar-padron', ['archivo' => $this->archivo])
            ->assertSuccessful();

        $libro = IOFactory::load($this->archivo);
        $hoja = $libro->getSheetByName('Padron');

        $this->assertNotNull($hoja, 'El padron tiene que estar en una hoja llamada Padron.');
        $this->assertSame('DNI', $hoja->getCell('A1')->getValue());
        $this->assertSame('DEBE', $hoja->getCell('D1')->getValue());
        $this->assertSame('Periodo 1', $hoja->getCell('E1')->getValue());
        $this->assertSame('Monto 1', $hoja->getCell('F1')->getValue());

        // Ordenado por apellido: Acosta antes que Zapata. El dado de baja no sale.
        $this->assertSame('30222222', $hoja->getCell('A2')->getValue());
        $this->assertSame('Acosta, Beto', $hoja->getCell('B2')->getValue());
        $this->assertSame('30111111', $hoja->getCell('A3')->getValue());
        $this->assertSame(3, $hoja->getHighestDataRow(), 'Solo los dos alumnos activos, mas el encabezado.');

        $this->assertNull(
            $hoja->getCell('D2')->getValue(),
            'DEBE tiene que salir vacio: lo completa el club, no el sistema.'
        );
    }

    /**
     * La hoja de instrucciones viaja adentro del archivo, y el importador lee el padron
     * por nombre de hoja. Si leyera la hoja activa, alcanzaria con que el club guardara
     * el archivo parado en las instrucciones para que la carga se cayera sin motivo claro.
     */
    public function test_el_archivo_trae_instrucciones_y_el_padron_se_lee_aunque_quede_activa_esa_hoja(): void
    {
        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $alumno = $this->crearAlumno('Acosta', 'Beto', '30222222', $deporte, $grupo, true);

        $this->artisan('wings:exportar-padron', ['archivo' => $this->archivo])->assertSuccessful();

        $libro = IOFactory::load($this->archivo);
        $instrucciones = $libro->getSheetByName('Instrucciones');

        $this->assertNotNull($instrucciones, 'Falta la hoja de instrucciones.');
        $this->assertSame('Instrucciones', $libro->getActiveSheet()->getTitle(), 'Al abrir el archivo tienen que verse las instrucciones.');

        $texto = '';
        foreach ($instrucciones->toArray() as $fila) {
            $texto .= implode(' ', array_map(fn ($celda) => (string) $celda, $fila))."\n";
        }

        $this->assertStringContainsString('092026 = septiembre de 2026', $texto, 'El formato de periodo tiene que estar con un ejemplo.');
        $this->assertStringContainsString('52.000', $texto, 'Los formatos de monto aceptados tienen que estar.');

        // Con las instrucciones como hoja activa, la carga tiene que encontrar el padron igual.
        $padron = $libro->getSheetByName('Padron');
        $padron->setCellValue('D2', 'SI');
        $padron->setCellValue('E2', '092026');
        $padron->setCellValue('F2', '52000');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro))->save($this->archivo);

        $this->artisan('wings:importar-padron', ['archivo' => $this->archivo, '--corte' => '2026-09'])
            ->assertSuccessful();

        $this->assertSame(1, \App\Models\DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-09')->count());
    }

    private function crearAlumno(
        string $apellido,
        string $nombre,
        string $dni,
        Deporte $deporte,
        Grupo $grupo,
        bool $activo
    ): Alumno {
        return Alumno::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'fecha_nacimiento' => '2010-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-01-01',
            'activo' => $activo,
        ]);
    }
}
