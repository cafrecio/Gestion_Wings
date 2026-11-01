<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * T105: un recibo pesaba 1,7 MB porque llevaba las fuentes enteras. Con el recorte
 * lleva solo las letras que usa. No toca la base: arma el PDF con datos fijos.
 */
class ReciboPesoT105Test extends TestCase
{
    private const LIMITE_BYTES = 400 * 1024;

    public function test_recibo_de_cuota_pesa_menos_de_400_kb(): void
    {
        $fecha = Carbon::create(2026, 11, 1, 10, 30);

        $this->assertPdfLiviano('pdfs.recibo-cuota', [
            'numero_recibo' => 'CUOTA-1', 'fecha_emision' => $fecha, 'fecha_pago' => $fecha,
            'anulado' => false, 'fecha_cancelacion' => null, 'cancelado_por' => 'Administración', 'motivo_cancelacion' => null,
            'alumno' => ['nombre' => 'Begoña Núñez', 'dni' => '45123456', 'deporte' => 'Patín'],
            'periodos' => [['periodo' => '2026-10', 'periodo_texto' => 'Octubre 2026', 'monto_aplicado' => 30000]],
            'monto_total' => 30000,
            'medio_cobro' => ['tipo_caja' => 'Efectivo', 'origen' => 'Operativo'],
            'observaciones' => null,
        ]);
    }

    public function test_recibo_de_liquidacion_pesa_menos_de_400_kb(): void
    {
        $fecha = Carbon::create(2026, 11, 1, 10, 30);

        $this->assertPdfLiviano('pdfs.recibo-liquidacion', [
            'numero_recibo' => 'LIQ-1', 'fecha_emision' => $fecha, 'fecha_pago' => $fecha,
            'profesor' => ['nombre' => 'Agustina Ibáñez', 'deporte' => 'Patín', 'tipo_liquidacion' => 'HORA', 'modalidad_texto' => 'Por Hora ($12,000.00 / hora)'],
            'periodo' => ['mes' => 10, 'anio' => 2026, 'nombre' => 'Octubre 2026', 'texto' => 'Octubre 2026', 'conteo_texto' => '1 clases dictadas · 1 h'],
            'monto_total' => 12000.0, 'total_liquidado' => 12000.0, 'total_calculado' => 12000.0, 'ajuste_final' => 0.0,
            'medio_pago' => ['tipo_caja' => 'Banco', 'subrubro' => 'Sueldos'],
            'observaciones' => null,
            'modalidad' => 'HORA', 'desglose_por_cuota' => false,
            'detalles' => [['fecha' => '05/10/2026', 'grupo' => 'Patín – Escuelita', 'minutos' => 60, 'horas' => 1.0, 'estado' => 'Validada', 'subtotal' => 12000.0]],
        ]);
    }

    private function assertPdfLiviano(string $vista, array $datos): void
    {
        $pdf = Pdf::loadView($vista, $datos)->setPaper('A5', 'portrait')->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertLessThan(self::LIMITE_BYTES, strlen($pdf), 'El recibo volvió a llevar las fuentes enteras.');
    }
}
