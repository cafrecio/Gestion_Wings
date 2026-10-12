<?php

namespace Tests\Unit;

use App\Support\FormatoImporte;
use PHPUnit\Framework\TestCase;

class FormatoImporteTest extends TestCase
{
    public function test_muestra_centavos_y_enteros_sin_perder_precision_de_la_base(): void
    {
        foreach (['0' => '$0.00', '1250.50' => '$1,250.50', '48000' => '$48,000.00',
            '1250000.01' => '$1,250,000.01', '9999999999.99' => '$9,999,999,999.99',
            '12345678901234567890.01' => '$12,345,678,901,234,567,890.01',
            '-1250.50' => '$-1,250.50', '00048.5' => '$48.50'] as $importe => $esperado) {
            $this->assertSame($esperado, FormatoImporte::pesos((string) $importe));
        }
        $this->assertSame('$1,250.50', FormatoImporte::desdeArgentino('1.250,50'));
        $this->assertSame('Pago $1,250.50 (20%)', FormatoImporte::textoHistorico('Pago $1.250,50 (20%)'));
    }

    public function test_suma_visible_conserva_los_centavos_de_cada_renglon(): void
    {
        $renglones = [1250.50, 250.25, -100.10];
        $this->assertSame(['$1,250.50', '$250.25', '$-100.10'], array_map(FormatoImporte::pesos(...), $renglones));
        $this->assertSame('$1,400.65', FormatoImporte::pesos(array_sum($renglones)));
        $this->assertSame([1250.50, 250.25, -100.10], $renglones);
    }

    public function test_publica_la_misma_politica_para_browser_y_excel(): void
    {
        $politica = FormatoImporte::configuracion();
        $this->assertSame('en-US', $politica['locale']);
        $this->assertSame(2, $politica['decimales']);
        $this->assertSame('.', $politica['decimal']);
        $this->assertSame(',', $politica['miles']);
        $this->assertSame('$', $politica['moneda']);
        $this->assertSame('#,##0.00', FormatoImporte::excel());
    }
}
