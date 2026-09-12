<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\User;
use App\Services\ReciboService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * SEG-05: Ocultar excepciones crudas de recibos al usuario.
 *
 * Ante un fallo en la generación del PDF, ReciboController debe devolver un
 * mensaje entendible en castellano sin filtrar rutas internas, SQL ni trazas del
 * servidor, y registrar el detalle técnico completo en los logs de Laravel.
 */
class ReciboErrorSanitizadoTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private User $admin;
    private Pago $pago;
    private Liquidacion $liquidacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $deporte = Deporte::create([
            'nombre' => 'Patin',
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $alumno = Alumno::create([
            'nombre' => 'Alumno',
            'apellido' => 'Test',
            'dni' => '12345678',
            'fecha_nacimiento' => '2010-01-01',
            'celular' => '1122334455',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-01-01',
            'activo' => true,
        ]);

        $this->pago = Pago::create([
            'alumno_id' => $alumno->id,
            'mes' => 9,
            'anio' => 2026,
            'monto_base' => 10000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 10000,
            'fecha_pago' => '2026-09-10',
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);

        $profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Profesor',
            'apellido' => 'Test',
            'dni' => '20123456',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Rosario',
            'telefono' => '1199887766',
            'email' => 'profe@test.com',
            'valor_hora' => 10000,
            'porcentaje_comision' => 0,
            'activo' => true,
        ]);

        $this->liquidacion = Liquidacion::create([
            'profesor_id' => $profesor->id,
            'mes' => 9,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 50000,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PAGADA,
            'pagada_at' => now(),
            'pagada_por_admin_id' => $this->admin->id,
            'pagada_fecha' => '2026-09-10',
        ]);
    }

    public function test_error_en_recibo_cuota_devuelve_mensaje_amigable_sin_detalles_tecnicos(): void
    {
        $trazaSecreta = 'SQLSTATE[HY000]: Server error in /var/www/internal/storage/file.php line 42';

        $mock = Mockery::mock(ReciboService::class);
        $mock->shouldReceive('generarReciboCuota')
            ->once()
            ->with($this->pago->id, false)
            ->andThrow(new \RuntimeException($trazaSecreta));

        $this->app->instance(ReciboService::class, $mock);
        Log::spy();

        $response = $this->actingAs($this->operativo)
            ->get(route('web.recibos.cuota', $this->pago->id));

        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Error al generar recibo',
            'message' => 'No se pudo generar el comprobante. Por favor, intentá nuevamente o comunicate con administración.',
        ]);

        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertStringNotContainsString('/var/www/internal', $response->getContent());
        $this->assertStringNotContainsString($trazaSecreta, $response->getContent());

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) use ($trazaSecreta) {
                return str_contains($message, 'Error al generar recibo de cuota')
                    && isset($context['exception'])
                    && $context['exception']->getMessage() === $trazaSecreta;
            });
    }

    public function test_error_en_recibo_liquidacion_devuelve_mensaje_amigable_sin_detalles_tecnicos(): void
    {
        $trazaSecreta = 'Dompdf\\Exception: Canvas failure at /secret/disk/render.php';

        $mock = Mockery::mock(ReciboService::class);
        $mock->shouldReceive('generarReciboLiquidacion')
            ->once()
            ->with($this->liquidacion->id, false)
            ->andThrow(new \RuntimeException($trazaSecreta));

        $this->app->instance(ReciboService::class, $mock);
        Log::spy();

        $response = $this->actingAs($this->admin)
            ->get(route('web.recibos.liquidacion', $this->liquidacion->id));

        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Error al generar recibo',
            'message' => 'No se pudo generar el comprobante. Por favor, intentá nuevamente o comunicate con administración.',
        ]);

        $this->assertStringNotContainsString('Dompdf', $response->getContent());
        $this->assertStringNotContainsString('/secret/disk', $response->getContent());
        $this->assertStringNotContainsString($trazaSecreta, $response->getContent());

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) use ($trazaSecreta) {
                return str_contains($message, 'Error al generar recibo de liquidación')
                    && isset($context['exception'])
                    && $context['exception']->getMessage() === $trazaSecreta;
            });
    }
}
