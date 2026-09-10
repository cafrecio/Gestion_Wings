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

        $hoja = IOFactory::load($this->archivo)->getActiveSheet();

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
