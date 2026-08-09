<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MoneyLockingTest extends TestCase
{
    public function test_validacion_de_caja_bloquea_la_fila_antes_de_cambiar_estado(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Services/CajaService.php'
        );

        $this->assertMatchesRegularExpression(
            '/function validarCaja\(.*?lockForUpdate\(\).*?estado =/s',
            $source
        );
    }

    public function test_ambos_flujos_de_pago_serializan_al_alumno_y_bloquean_la_deuda(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Services/PagoCuotaService.php'
        );

        $this->assertSame(3, substr_count($source, 'bloquearAlumnoParaPago'));
        $this->assertMatchesRegularExpression(
            '/function bloquearAlumnoParaPago\(.*?lockForUpdate\(\)/s',
            $source
        );
        $this->assertMatchesRegularExpression(
            '/function obtenerOcrearDeuda\(.*?lockForUpdate\(\).*?first\(\)/s',
            $source
        );
    }
}
