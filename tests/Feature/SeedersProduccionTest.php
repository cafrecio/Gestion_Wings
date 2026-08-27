<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SeedersProduccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_aborta_en_produccion(): void
    {
        $this->assertSeederBloqueadoEnProduccion(DemoSeeder::class);
    }

    public function test_test_seeder_aborta_en_produccion(): void
    {
        $this->assertSeederBloqueadoEnProduccion(TestSeeder::class);
    }

    private function assertSeederBloqueadoEnProduccion(string $seeder): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        $excepcion = null;

        try {
            app()->make($seeder)->run();
        } catch (RuntimeException $error) {
            $excepcion = $error;
        } finally {
            app()->detectEnvironment(fn (): string => 'testing');
        }

        $this->assertInstanceOf(RuntimeException::class, $excepcion);
        $this->assertStringContainsString('no puede ejecutarse en produccion', $excepcion->getMessage());
    }
}
