<?php

namespace Tests\Unit;

use App\Services\ProgramacionClasesService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProgramacionClasesTest extends TestCase
{
    private ProgramacionClasesService $programacion;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-24 09:00:00');
        $this->programacion = new ProgramacionClasesService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function unica(string $inicio = '17:30', string $fin = '18:30'): array
    {
        return $this->programacion->validar(['fecha' => '2026-09-24', 'hora_inicio' => $inicio, 'hora_fin' => $fin]);
    }

    private function serie(array $horarios): array
    {
        return $this->programacion->validar(['tipo_creacion' => 'recurrente', 'fecha_desde' => '2026-09-24',
            'fecha_hasta' => '2026-10-31', 'dias_semana' => array_keys($horarios), 'horarios' => $horarios]);
    }

    public function test_media_hora_cuenta_dos_bloques_y_exige_confirmar(): void
    {
        $datos = $this->unica();
        $aviso = $this->programacion->aviso($datos, 10, [20], 30);
        $this->assertSame(2, $aviso['horarios'][0]['bloques']);
        $this->assertStringContainsString('dura 1 hora', $aviso['horarios'][0]['detalle']);
        $this->assertStringContainsString('17:00–18:00, 18:00–19:00', $aviso['horarios'][0]['detalle']);
        $this->assertTrue($this->programacion->requiereConfirmacion($aviso, null));
        $this->assertFalse($this->programacion->requiereConfirmacion($aviso, $aviso['firma']));
    }

    public function test_fin_en_punto_no_ocupa_el_bloque_siguiente(): void
    {
        $aviso = $this->programacion->aviso($this->unica('17:30', '18:00'), 10, [], 30);
        $this->assertSame(1, $aviso['horarios'][0]['bloques']);
        $this->assertStringContainsString('17:00–18:00.', $aviso['horarios'][0]['detalle']);
        $this->assertStringNotContainsString('18:00–19:00', $aviso['horarios'][0]['detalle']);
    }

    public function test_horarios_en_punto_no_piden_confirmacion(): void
    {
        $aviso = $this->programacion->aviso($this->unica('17:00', '19:00'), 10, [], 30);
        $this->assertNull($aviso);
        $this->assertFalse($this->programacion->requiereConfirmacion($aviso, null));
    }

    public function test_confirmacion_no_sirve_para_otra_carga(): void
    {
        $datos = $this->unica();
        $firma = $this->programacion->aviso($datos, 10, [20], 30)['firma'];
        $otroDia = $datos;
        $otroDia['fecha'] = '2026-09-25';
        foreach ([[$this->unica('18:30', '19:30'), 10, [20], 30], [$otroDia, 10, [20], 30],
            [$datos, 11, [20], 30], [$datos, 10, [21], 30], [$datos, 10, [20], 31]] as $carga) {
            $this->assertTrue($this->programacion->requiereConfirmacion($this->programacion->aviso(...$carga), $firma));
        }
        $this->assertTrue($this->programacion->requiereConfirmacion($this->programacion->aviso($datos, 10, [20], 30), true));
    }

    public function test_seis_programaciones_generan_76_clases_con_el_horario_correcto(): void
    {
        $grupos = [
            [1 => ['16:00', '17:00'], 3 => ['16:00', '17:00']],
            [1 => ['17:00', '18:00'], 5 => ['16:00', '17:00']],
            [2 => ['17:00', '18:00'], 4 => ['17:00', '18:00']],
            [2 => ['18:00', '19:00'], 4 => ['18:00', '19:00'], 6 => ['10:00', '11:00']],
            [1 => ['16:00', '17:00'], 3 => ['18:00', '19:00']],
            [2 => ['19:00', '20:00'], 4 => ['19:00', '20:00'], 6 => ['11:00', '12:00']],
        ];
        $total = 0;
        foreach ($grupos as $i => $horarios) {
            $horarios = array_map(fn ($h) => ['hora_inicio' => $h[0], 'hora_fin' => $h[1]], $horarios);
            $datos = $this->serie($horarios);
            $clases = iterator_to_array($this->programacion->clases($datos));
            $this->assertCount([10, 11, 11, 17, 10, 17][$i], $clases);
            foreach ($clases as $clase) {
                $dia = Carbon::parse($clase['fecha'])->dayOfWeek;
                $this->assertSame($horarios[$dia]['hora_inicio'], $clase['hora_inicio']);
                $this->assertSame($horarios[$dia]['hora_fin'], $clase['hora_fin']);
                $this->assertGreaterThanOrEqual('2026-09-24', $clase['fecha']);
                $this->assertLessThanOrEqual('2026-10-31', $clase['fecha']);
            }
            $total += count($clases);
        }
        $this->assertSame(76, $total);
    }

    public function test_serie_con_horario_comun_sigue_siendo_valida(): void
    {
        $datos = $this->programacion->validar(['tipo_creacion' => 'recurrente', 'fecha_desde' => '2026-09-24',
            'fecha_hasta' => '2026-09-30', 'dias_semana' => [1, 5], 'hora_inicio' => '17:00', 'hora_fin' => '18:00']);
        $this->assertSame(['2026-09-25', '2026-09-28'], array_column(iterator_to_array($this->programacion->clases($datos)), 'fecha'));
        $this->assertSame($datos['horarios'][1], $datos['horarios'][5]);
    }

    public function test_no_acepta_fin_anterior_o_faltante_en_un_dia_elegido(): void
    {
        foreach ([['hora_inicio' => '17:00', 'hora_fin' => '16:00'], ['hora_inicio' => '17:00']] as $horario) {
            try {
                $this->serie([1 => ['hora_inicio' => '17:00', 'hora_fin' => '18:00'], 5 => $horario]);
                $this->fail('Debe rechazar el horario del viernes.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('horarios.5.hora_fin', $e->errors());
            }
        }
    }

    public function test_rechaza_fechas_pasadas_y_dias_inexistentes(): void
    {
        foreach ([['fecha' => '2026-09-23', 'hora_inicio' => '17:00', 'hora_fin' => '18:00'],
            ['tipo_creacion' => 'recurrente', 'fecha_desde' => '2026-09-24', 'fecha_hasta' => '2026-09-30',
                'dias_semana' => [7], 'hora_inicio' => '17:00', 'hora_fin' => '18:00']] as $entrada) {
            try {
                $this->programacion->validar($entrada);
                $this->fail('Debe rechazar la entrada.');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_aviso_de_serie_identifica_el_dia_y_renueva_la_firma_si_cambia(): void
    {
        $datos = $this->serie([1 => ['hora_inicio' => '17:00', 'hora_fin' => '18:00'],
            5 => ['hora_inicio' => '16:30', 'hora_fin' => '17:30']]);
        $aviso = $this->programacion->aviso($datos, 10, [20], 30);
        $this->assertCount(1, $aviso['horarios']);
        $this->assertStringStartsWith('Viernes:', $aviso['horarios'][0]['detalle']);
        $this->assertSame('horario-5-inicio', $aviso['campo']);
        $datos['horarios'][1]['hora_inicio'] = '16:00';
        $this->assertTrue($this->programacion->requiereConfirmacion($this->programacion->aviso($datos, 10, [20], 30), $aviso['firma']));
    }
}
