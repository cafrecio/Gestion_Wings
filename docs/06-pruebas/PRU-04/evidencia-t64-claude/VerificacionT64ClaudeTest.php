<?php

namespace Tests\Feature;

use App\Models\ReglaPrimerPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verificación independiente de T64 (Claude CyE, 20/10/2026). Vive fuera de tests/ para no
 * alterar el contador permanente. Busca los casos que rompen la regla, no el camino feliz.
 * Segunda vuelta (520bafd1): ahora se exige el rechazo de H2/H3 y que se conserven las inactivas.
 */
class VerificacionT64ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
            $this->markTestSkipped('Solo en wings_testing_claude.');
        }
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        ReglaPrimerPago::query()->delete();
        foreach ([[1, 15, 100], [16, 23, 70], [24, 31, 40]] as [$d, $h, $p]) {
            ReglaPrimerPago::create(['nombre' => "Tramo {$d}", 'dia_desde' => $d, 'dia_hasta' => $h, 'porcentaje' => $p, 'activo' => true]);
        }
    }

    private function ids(): array
    {
        return ReglaPrimerPago::orderBy('dia_desde')->pluck('id')->all();
    }

    private function cubreSinHuecos(): bool
    {
        $dias = [];
        foreach (ReglaPrimerPago::where('activo', true)->get() as $r) {
            foreach (range($r->dia_desde, $r->dia_hasta) as $d) {
                $dias[$d] = ($dias[$d] ?? 0) + 1;
            }
        }
        foreach (range(1, 31) as $d) {
            if (($dias[$d] ?? 0) !== 1) {
                return false;
            }
        }
        return true;
    }

    /** El mismo id dos veces en el lote: el validador ve dos reglas, la base guarda una. */
    public function test_lote_con_id_repetido_no_deja_hueco(): void
    {
        [$a] = $this->ids();
        $r = $this->actingAs($this->admin)->putJson(route('web.config.primer-pago.guardar-todas'), ['reglas' => [
            ['id' => $a, 'nombre' => 'Uno', 'dia_desde' => 1, 'dia_hasta' => 15, 'porcentaje' => 100],
            ['id' => $a, 'nombre' => 'Dos', 'dia_desde' => 16, 'dia_hasta' => 31, 'porcentaje' => 50],
        ]]);
        file_put_contents(__DIR__.'/id-repetido.json', json_encode(['status' => $r->status(),
            'reglas' => ReglaPrimerPago::orderBy('dia_desde')->get(['id', 'dia_desde', 'dia_hasta', 'porcentaje'])->toArray()], JSON_PRETTY_PRINT));
        $this->assertTrue($this->cubreSinHuecos(), 'Quedaron días sin cubrir o pisados después de un lote con id repetido.');
        $this->assertSame(422, $r->status(), 'Un id repetido debe rechazarse.');
        $this->assertCount(3, ReglaPrimerPago::all());
    }

    /** Un id que no existe: no debe borrar nada ni dejar el conjunto a medias. */
    public function test_lote_con_id_inexistente_no_cambia_nada(): void
    {
        $antes = ReglaPrimerPago::orderBy('id')->get()->toArray();
        $r = $this->actingAs($this->admin)->putJson(route('web.config.primer-pago.guardar-todas'), ['reglas' => [
            ['id' => 999999, 'nombre' => 'Fantasma', 'dia_desde' => 1, 'dia_hasta' => 31, 'porcentaje' => 80],
        ]]);
        file_put_contents(__DIR__.'/id-inexistente.json', json_encode(['status' => $r->status(), 'cuerpo' => mb_substr($r->getContent(), 0, 300)], JSON_PRETTY_PRINT));
        $this->assertSame($antes, ReglaPrimerPago::orderBy('id')->get()->toArray());
        $this->assertSame(422, $r->status());
        $this->assertStringNotContainsString('No query results', $r->getContent());
    }

    /** El cambio de tramos que motivó el diseño, en un solo envío. */
    public function test_cambio_de_tramos_en_un_envio(): void
    {
        [$a, $b, $c] = $this->ids();
        $this->actingAs($this->admin)->putJson(route('web.config.primer-pago.guardar-todas'), ['reglas' => [
            ['id' => $a, 'nombre' => 'A', 'dia_desde' => 1, 'dia_hasta' => 10, 'porcentaje' => 100],
            ['id' => $b, 'nombre' => 'B', 'dia_desde' => 11, 'dia_hasta' => 31, 'porcentaje' => 60],
        ]])->assertOk();
        $this->assertTrue($this->cubreSinHuecos());
        $this->assertNull(ReglaPrimerPago::find($c));
    }

    /** Operativo y profesor no pueden tocar las reglas. */
    public function test_solo_el_duenio(): void
    {
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $u = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $this->actingAs($u)->putJson(route('web.config.primer-pago.guardar-todas'), ['reglas' => [
                ['nombre' => 'X', 'dia_desde' => 1, 'dia_hasta' => 31, 'porcentaje' => 50],
            ]])->assertForbidden();
        }
        $this->assertCount(3, ReglaPrimerPago::all());
    }

    /** Una regla inactiva no cuenta para cubrir, pero el lote la borra sin que se haya pedido. */
    public function test_regla_inactiva_y_lote(): void
    {
        $inactiva = ReglaPrimerPago::create(['nombre' => 'Vieja', 'dia_desde' => 5, 'dia_hasta' => 6, 'porcentaje' => 10, 'activo' => false]);
        [$a, $b, $c] = array_values(array_diff($this->ids(), [$inactiva->id]));
        $this->actingAs($this->admin)->putJson(route('web.config.primer-pago.guardar-todas'), ['reglas' => [
            ['id' => $a, 'nombre' => 'A', 'dia_desde' => 1, 'dia_hasta' => 15, 'porcentaje' => 100],
            ['id' => $b, 'nombre' => 'B', 'dia_desde' => 16, 'dia_hasta' => 23, 'porcentaje' => 70],
            ['id' => $c, 'nombre' => 'C', 'dia_desde' => 24, 'dia_hasta' => 31, 'porcentaje' => 40],
        ]])->assertOk();
        file_put_contents(__DIR__.'/inactiva.json', json_encode(['inactiva_sigue' => ReglaPrimerPago::find($inactiva->id) !== null], JSON_PRETTY_PRINT));
        $this->assertNotNull(ReglaPrimerPago::find($inactiva->id), 'El lote borró una regla inactiva que no se pidió borrar.');
    }
}
