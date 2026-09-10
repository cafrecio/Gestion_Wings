<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * La carga del padrón declara el saldo inicial de todos, no solo de los deudores, y
 * cierra el mes de corte. Wings empieza a facturar el mes siguiente.
 */
class CargaSaldoInicialPadronTest extends TestCase
{
    use RefreshDatabase;

    private const CORTE = '2026-09';

    private User $operativo;
    private Alumno $deudor;
    private Alumno $alDia;
    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-10 10:00:00');
        $this->archivo = storage_path('app/testing/padron-'.uniqid().'.xlsx');

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);
        TipoCaja::create(['nombre' => 'Caja General', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

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

        $this->deudor = $this->crearAlumno('Debe', 'Uno', '12345678', $deporte, $grupo, $plan);
        $this->alDia = $this->crearAlumno('Nodebe', 'Dos', '12345679', $deporte, $grupo, $plan);
    }

    protected function tearDown(): void
    {
        if (is_file($this->archivo)) {
            unlink($this->archivo);
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_que_debe_queda_pendiente_y_el_que_no_queda_con_el_mes_cerrado(): void
    {
        $this->crearExcel([
            ['12345678', 'Debe, Uno', 'Hockey', 'SI', '092026', '52000'],
            ['12345679', 'Nodebe, Dos', 'Hockey', 'NO', '', ''],
        ]);

        $this->artisan('wings:importar-padron', ['archivo' => $this->archivo, '--corte' => self::CORTE])
            ->assertSuccessful();

        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->deudor->id,
            'periodo' => self::CORTE,
            'monto_original' => '52000.00',
            'monto_pagado' => '0.00',
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->alDia->id,
            'periodo' => self::CORTE,
            'monto_original' => '0.00',
            'monto_pagado' => '0.00',
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_el_mes_cerrado_no_se_ofrece_en_la_pantalla_de_cobro(): void
    {
        $this->crearExcel([
            ['12345678', 'Debe, Uno', 'Hockey', 'SI', '092026', '52000'],
            ['12345679', 'Nodebe, Dos', 'Hockey', 'NO', '', ''],
        ]);

        $this->artisan('wings:importar-padron', ['archivo' => $this->archivo, '--corte' => self::CORTE])
            ->assertSuccessful();

        // Al deudor sí se le puede cobrar septiembre.
        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $this->deudor->id))
            ->assertOk()
            ->assertSee('Septiembre 2026');

        // Al que estaba al día, septiembre ya no se le ofrece.
        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $this->alDia->id))
            ->assertOk()
            ->assertDontSee('Septiembre 2026');
    }

    public function test_rechaza_la_fila_que_no_declara_si_o_no(): void
    {
        $this->crearExcel([
            ['12345678', 'Debe, Uno', 'Hockey', '', '092026', '52000'],
        ]);

        $this->artisan('wings:importar-padron', ['archivo' => $this->archivo, '--corte' => self::CORTE])
            ->assertFailed();

        $this->assertDatabaseCount('deuda_cuotas', 0);
    }

    public function test_rechaza_al_que_dice_no_pero_informa_montos(): void
    {
        $this->crearExcel([
            ['12345678', 'Debe, Uno', 'Hockey', 'NO', '092026', '52000'],
        ]);

        $this->artisan('wings:importar-padron', ['archivo' => $this->archivo, '--corte' => self::CORTE])
            ->assertFailed();

        $this->assertDatabaseCount('deuda_cuotas', 0);
    }

    public function test_una_sola_fila_mala_no_escribe_ninguna(): void
    {
        $this->crearExcel([
            ['12345678', 'Debe, Uno', 'Hockey', 'SI', '092026', '52000'],
            ['99999999', 'Fantasma', 'Hockey', 'NO', '', ''],
        ]);

        $this->artisan('wings:importar-padron', ['archivo' => $this->archivo, '--corte' => self::CORTE])
            ->assertFailed();

        $this->assertDatabaseCount('deuda_cuotas', 0);
    }

    /** @param array<int, array<int, string>> $filas */
    private function crearExcel(array $filas): void
    {
        $hoja = (new Spreadsheet())->getActiveSheet();
        $hoja->fromArray(['DNI', 'Alumno', 'Deporte', 'DEBE', 'Periodo 1', 'Monto 1'], null, 'A1');

        $fila = 2;
        foreach ($filas as $valores) {
            foreach ($valores as $indice => $valor) {
                $hoja->setCellValueExplicit(
                    [$indice + 1, $fila],
                    $valor,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
            $fila++;
        }

        if (!is_dir(dirname($this->archivo))) {
            mkdir(dirname($this->archivo), 0775, true);
        }
        (new Xlsx($hoja->getParent()))->save($this->archivo);
    }

    private function crearAlumno(
        string $apellido,
        string $nombre,
        string $dni,
        Deporte $deporte,
        Grupo $grupo,
        GrupoPlan $plan
    ): Alumno {
        $alumno = Alumno::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
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

        return $alumno;
    }
}
