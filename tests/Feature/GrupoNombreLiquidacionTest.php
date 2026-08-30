<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Services\LiquidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoNombreLiquidacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_nombre_completo_carga_las_relaciones_si_no_estaban_cargadas(): void
    {
        [$grupo] = $this->crearGrupo();
        $grupoSinRelaciones = Grupo::findOrFail($grupo->id);

        $this->assertFalse($grupoSinRelaciones->relationLoaded('deporte'));
        $this->assertFalse($grupoSinRelaciones->relationLoaded('nivel'));

        $this->assertSame('Patín — Inicial', $grupoSinRelaciones->nombre_completo);
        $this->assertTrue($grupoSinRelaciones->relationLoaded('deporte'));
        $this->assertTrue($grupoSinRelaciones->relationLoaded('nivel'));
    }

    public function test_detalle_de_liquidacion_por_hora_incluye_el_nombre_del_grupo(): void
    {
        [$grupo, $deporte] = $this->crearGrupo();
        $profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Ada',
            'apellido' => 'Lovelace',
            'dni' => '30111222',
            'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Calle 1',
            'localidad' => 'Buenos Aires',
            'valor_hora' => 15000,
            'activo' => true,
        ]);
        $clase = Clase::create([
            'grupo_id' => $grupo->id,
            'fecha' => '2026-03-02',
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($profesor->id);

        $liquidacion = app(LiquidacionService::class)
            ->generarLiquidacionMensual($profesor->id, 3, 2026);

        $this->assertCount(1, $liquidacion->detalles);
        $descripcion = $liquidacion->detalles->first()->descripcion;
        $this->assertStringContainsString('Patín — Inicial', $descripcion);
        $this->assertStringNotContainsString(' -  — ', $descripcion);
    }

    /**
     * @return array{Grupo, Deporte}
     */
    private function crearGrupo(): array
    {
        $deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        return [$grupo, $deporte];
    }
}
