<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Services\CargaSaldoInicialPadronService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * El padrón lo completa una persona en Excel, no un programa.
 *
 * Ensayo del 21/09/2026: el monto escrito como texto "52.000" se grababa como $52
 * —el mismo defecto que COB-01 en el cobro— porque `is_numeric('52.000')` es
 * verdadero y PHP lee el punto como decimal. Y el período `092026` tipeado en una
 * celda común llegaba como el número 92026, porque Excel se come el cero, y se
 * rechazaba con un mensaje que no explicaba por qué.
 *
 * Regla: un número de Excel se toma como número. Un texto se lee en formato
 * argentino (punto de miles, coma decimal) y lo que sea ambiguo se rechaza en vez
 * de adivinar un importe.
 */
class CargaPadronFormatosExcelTest extends TestCase
{
    use RefreshDatabase;

    private const CORTE = '2026-09';

    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-10 10:00:00');
        $this->archivo = storage_path('app/testing/padron-formatos-'.uniqid().'.xlsx');

        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 52000,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Uno',
            'apellido' => 'Debe',
            'dni' => '12345678',
            'fecha_nacimiento' => '2010-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-01-05',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-01-05',
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->archivo)) {
            unlink($this->archivo);
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_montos_que_se_aceptan_y_el_importe_que_queda(): void
    {
        // [lo que hay en la celda, importe que tiene que quedar]
        $casos = [
            'número de Excel' => [52000, '52000.00'],
            'número con decimales' => [52000.5, '52000.50'],
            'texto sin separadores' => ['52000', '52000.00'],
            'texto con punto de miles' => ['52.000', '52000.00'],
            'texto con millones' => ['1.052.000', '1052000.00'],
            'texto con miles y coma decimal' => ['52.000,50', '52000.50'],
            'texto con coma decimal' => ['52000,50', '52000.50'],
            'texto con espacios' => [' 52.000 ', '52000.00'],
        ];

        foreach ($casos as $caso => [$celda, $esperado]) {
            $resultado = $this->validar('092026', $celda);

            $this->assertSame([], $resultado['errores'], "{$caso}: no tenía que rechazarse.");
            $this->assertSame($esperado, $resultado['deudas'][0]['monto'], "{$caso}: importe mal leído.");
        }
    }

    public function test_montos_ambiguos_o_invalidos_se_rechazan_sin_adivinar(): void
    {
        $casos = [
            'coma de miles a la inglesa' => '52,000',
            'punto que no es de miles' => '52.5',
            'signo pesos' => '$52000',
            'letras' => 'cincuenta mil',
            'cero' => '0',
            'negativo' => '-100',
        ];

        foreach ($casos as $caso => $celda) {
            $resultado = $this->validar('092026', $celda);

            $this->assertNotSame([], $resultado['errores'], "{$caso}: se aceptó '{$celda}'.");
            $this->assertSame([], $resultado['deudas'], "{$caso}: no tenía que quedar deuda.");
        }
    }

    public function test_periodos_que_excel_escribe_como_numero(): void
    {
        $casos = [
            'texto con cero' => ['092026', '2026-09'],
            'número sin el cero que se comió Excel' => [92026, '2026-09'],
            'texto sin el cero' => ['92026', '2026-09'],
            'diciembre como número' => [122026, '2026-12'],
            'enero como número' => [12026, '2026-01'],
            'texto con espacios' => [' 082026 ', '2026-08'],
        ];

        foreach ($casos as $caso => [$celda, $esperado]) {
            $resultado = $this->validar($celda, 52000);

            $this->assertSame([], $resultado['errores'], "{$caso}: no tenía que rechazarse.");
            $this->assertSame($esperado, $resultado['deudas'][0]['periodo'], "{$caso}: período mal leído.");
        }
    }

    public function test_el_periodo_invalido_explica_el_formato_con_un_ejemplo(): void
    {
        foreach (['13/2026', '2026-09', '132026', '092024'] as $celda) {
            $resultado = $this->validar($celda, 52000);

            $this->assertCount(1, $resultado['errores'], "'{$celda}': se aceptó o dio errores de más.");
            $this->assertStringContainsString('092026', $resultado['errores'][0]['mensaje']);
        }
    }

    public function test_el_padron_exportado_trae_los_periodos_como_texto(): void
    {
        $this->artisan('wings:exportar-padron', ['archivo' => $this->archivo, '--pares' => 2])
            ->assertSuccessful();

        $hoja = IOFactory::load($this->archivo)->getActiveSheet();
        // Con la columna en texto Excel no se come el cero de 092026.
        foreach (['E2', 'G2'] as $celda) {
            $this->assertSame('@', $hoja->getStyle($celda)->getNumberFormat()->getFormatCode(), "{$celda} no es texto.");
        }
        // El monto queda como número: en Excel argentino 52.000 se lee como 52000.
        $this->assertNotSame('@', $hoja->getStyle('F2')->getNumberFormat()->getFormatCode());
    }

    /** @return array{errores: array, deudas: array, cierres: array} */
    private function validar(string|int|float $periodo, string|int|float $monto): array
    {
        $hoja = (new Spreadsheet())->getActiveSheet();
        $hoja->fromArray(['DNI', 'Alumno', 'Deporte', 'DEBE', 'Periodo 1', 'Monto 1'], null, 'A1');
        $hoja->setCellValueExplicit('A2', '12345678', DataType::TYPE_STRING);
        $hoja->setCellValue('B2', 'Debe, Uno');
        $hoja->setCellValue('C2', 'Hockey');
        $hoja->setCellValue('D2', 'SI');
        $this->escribir($hoja, 'E2', $periodo);
        $this->escribir($hoja, 'F2', $monto);

        if (!is_dir(dirname($this->archivo))) {
            mkdir(dirname($this->archivo), 0775, true);
        }
        (new Xlsx($hoja->getParent()))->save($this->archivo);

        return app(CargaSaldoInicialPadronService::class)->validar($this->archivo, self::CORTE);
    }

    /** Un texto se guarda como texto y un número como número, igual que en Excel. */
    private function escribir($hoja, string $celda, string|int|float $valor): void
    {
        is_string($valor)
            ? $hoja->setCellValueExplicit($celda, $valor, DataType::TYPE_STRING)
            : $hoja->setCellValueExplicit($celda, $valor, DataType::TYPE_NUMERIC);
    }
}
