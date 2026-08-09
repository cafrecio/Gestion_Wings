<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobranzaEstadoServiceTest extends TestCase
{
    use RefreshDatabase;

    private CobranzaEstadoService $service;
    private Deporte $deporte;
    private Grupo $grupo;
    private int $siguienteDni = 20000000;

    protected function setUp(): void
    {
        parent::setUp();

        Configuracion::set('dias_gracia_cobranza', 10);
        $this->service = app(CobranzaEstadoService::class);
        $this->deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Estados']);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
    }

    public function test_aplica_los_cuatro_estados_en_el_orden_contractual(): void
    {
        $alDia = $this->crearAlumno('Al día');
        $this->deuda($alDia, '2026-07', true);
        $this->deuda($alDia, '2026-08', true);
        $this->pago($alDia, 7, 2026);

        $enPlazoAgosto = $this->crearAlumno('En plazo agosto');
        $this->deuda($enPlazoAgosto, '2026-07', true);
        $this->deuda($enPlazoAgosto, '2026-08', false);
        $this->pago($enPlazoAgosto, 7, 2026);

        $moroso = $this->crearAlumno('Moroso');
        $this->deuda($moroso, '2026-07', true);
        $this->deuda($moroso, '2026-08', false);
        $this->pago($moroso, 7, 2026);

        $deudorConPagoActual = $this->crearAlumno('Deudor anterior');
        $this->deuda($deudorConPagoActual, '2026-07', false);
        $this->deuda($deudorConPagoActual, '2026-08', true);
        $this->pago($deudorConPagoActual, 8, 2026);

        $enPlazoSeptiembre = $this->crearAlumno('En plazo septiembre');
        $this->deuda($enPlazoSeptiembre, '2026-08', true);
        $this->deuda($enPlazoSeptiembre, '2026-09', false);
        $this->pago($enPlazoSeptiembre, 8, 2026);

        $deudorSeptiembre = $this->crearAlumno('Deudor septiembre');
        $this->deuda($deudorSeptiembre, '2026-08', false);
        $this->deuda($deudorSeptiembre, '2026-09', false);
        $this->pago($deudorSeptiembre, 7, 2026);

        $nuncaPago = $this->crearAlumno('Nunca pagó');

        $this->assertEstado($alDia, '2026-08-05', CobranzaEstadoService::ESTADO_AL_DIA);
        $this->assertEstado($enPlazoAgosto, '2026-08-05', CobranzaEstadoService::ESTADO_EN_PLAZO);
        $this->assertEstado($moroso, '2026-08-15', CobranzaEstadoService::ESTADO_MOROSO);
        $this->assertEstado($deudorConPagoActual, '2026-08-05', CobranzaEstadoService::ESTADO_DEUDOR);
        $this->assertEstado($enPlazoSeptiembre, '2026-09-01', CobranzaEstadoService::ESTADO_EN_PLAZO);
        $this->assertEstado($deudorSeptiembre, '2026-09-01', CobranzaEstadoService::ESTADO_DEUDOR);
        $this->assertEstado($nuncaPago, '2026-08-05', CobranzaEstadoService::ESTADO_DEUDOR);
    }

    public function test_usa_los_dias_de_gracia_configurados(): void
    {
        Configuracion::set('dias_gracia_cobranza', 15);
        $alumno = $this->crearAlumno('Gracia configurable');
        $this->deuda($alumno, '2026-07', true);
        $this->deuda($alumno, '2026-08', false);
        $this->pago($alumno, 7, 2026);

        $this->assertEstado($alumno, '2026-08-15', CobranzaEstadoService::ESTADO_EN_PLAZO);
        $this->assertEstado($alumno, '2026-08-16', CobranzaEstadoService::ESTADO_MOROSO);
    }

    private function crearAlumno(string $nombre): Alumno
    {
        return Alumno::create([
            'nombre' => $nombre,
            'apellido' => 'Prueba',
            'dni' => (string) $this->siguienteDni++,
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => '2026-01-01',
            'activo' => true,
        ]);
    }

    private function deuda(Alumno $alumno, string $periodo, bool $pagada): void
    {
        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodo,
            'monto_original' => 10000,
            'monto_pagado' => $pagada ? 10000 : 0,
            'estado' => $pagada ? DeudaCuota::ESTADO_PAGADA : DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    private function pago(Alumno $alumno, int $mes, int $anio): void
    {
        Pago::create([
            'alumno_id' => $alumno->id,
            'mes' => $mes,
            'anio' => $anio,
            'monto_base' => 10000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 10000,
            'fecha_pago' => sprintf('%04d-%02d-05', $anio, $mes),
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);
    }

    private function assertEstado(Alumno $alumno, string $fecha, string $esperado): void
    {
        $resultado = $this->service->estadoAlumno($alumno->id, Carbon::parse($fecha));

        $this->assertSame($esperado, $resultado['estado']);
    }
}
