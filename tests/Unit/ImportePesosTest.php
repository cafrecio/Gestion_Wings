<?php

namespace Tests\Unit;

use App\Rules\ImportePesos;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ImportePesosTest extends TestCase
{
    public function test_formato_y_preparacion_no_redondean_pedidos_invalidos(): void
    {
        foreach (['48000' => '48000', '48.50' => '48.50', '48,50' => '48.50', '48,000.50' => '48000.50',
            '0' => '0', '48.505' => null, '48.000' => null, '48.0000' => null, '-1' => null,
            'letras' => null, '1e2' => null, '48,50.5' => null, '48.50 '."\n" => null] as $entrada => $esperado) {
            $this->assertSame($esperado, ImportePesos::normalizar((string) $entrada), (string) $entrada);
        }
        $this->assertNull(ImportePesos::normalizar(['48.50']));
        $this->assertNull(ImportePesos::normalizar(true));
        $this->assertSame('48,000.50', ImportePesos::mostrar('48000.50'));
        $this->assertSame('48.505', ImportePesos::mostrar('48.505')); // El error repoblado no se esconde.
        $request = new Request(['planes' => [['precio_mensual' => '48,50'], ['precio_mensual' => '48.505']],
            'montos_cuota' => ['2026-10' => '48,000.50'], 'porcentaje' => '48,50']);
        ImportePesos::preparar($request, ['planes.*.precio_mensual', 'montos_cuota.*']);
        $this->assertSame('48.50', $request->input('planes.0.precio_mensual'));
        $this->assertSame('48.505', $request->input('planes.1.precio_mensual'));
        $this->assertSame('48000.50', $request->input('montos_cuota.2026-10'));
        $this->assertSame('48,50', $request->input('porcentaje')); // Fuera de los importes.
    }
}
