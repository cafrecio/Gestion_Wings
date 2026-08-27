<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NormalizarNivelesCatalogosTest extends TestCase
{
    use RefreshDatabase;

    public function test_borra_solo_niveles_legacy_sin_grupos(): void
    {
        Nivel::create(['nombre' => 'Principiantes']);
        Nivel::create(['nombre' => 'Avanzados']);
        $federadas = Nivel::create(['nombre' => 'Federadas']);
        $deporte = Deporte::create([
            'nombre' => 'Prueba',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $federadas->id,
            'activo' => true,
        ]);

        Log::spy();
        $migracion = require database_path('migrations/2026_08_27_000001_normalizar_niveles_catalogos.php');
        $migracion->up();

        $this->assertDatabaseMissing('niveles', ['nombre' => 'Principiantes']);
        $this->assertDatabaseMissing('niveles', ['nombre' => 'Avanzados']);
        $this->assertDatabaseHas('niveles', ['nombre' => 'Federadas']);
        Log::shouldHaveReceived('warning')->once();
    }
}
