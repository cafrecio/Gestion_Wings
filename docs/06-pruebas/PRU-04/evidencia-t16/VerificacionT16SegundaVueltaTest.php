<?php

namespace Tests\Feature;

use App\Models\{Rubro, Subrubro, User};
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Controles adicionales de T16; ejecutar por ruta en la base propia. */
class VerificacionT16SegundaVueltaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertContains(DB::connection()->getDatabaseName(),
            ['wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini']);
        $this->seed(CatalogosSeeder::class);
        $this->actingAs(User::factory()->create(['rol' => 'ADMIN', 'activo' => true]));
    }

    private function sub(string $nombre): Subrubro
    {
        return Subrubro::where('nombre', $nombre)->sole();
    }

    private function datos(Subrubro $s, string $clase): array
    {
        return ['nombre' => $s->nombre, 'permitido_para' => 'ADMIN',
            'afecta_caja' => $s->afecta_caja, 'clasificacion_resultado' => $clase];
    }

    private function migrar(): void
    {
        (require base_path('database/migrations/2026_10_10_180000_clasificar_subrubros_existentes.php'))->up();
    }

    private function evidencia(string $caso, array $datos): void
    {
        if ($ruta = getenv('T16_V2_EVIDENCIA')) {
            file_put_contents($ruta, json_encode(['caso' => $caso] + $datos,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND);
        }
    }

    public function test_alta_de_rubro_no_importa_hijos_ni_edita_el_id_enviado(): void
    {
        $s = $this->sub('Patines');
        $original = $s->rubro->getAttributes();
        $hijo = $s->getAttributes();
        $cantidad = Rubro::count();
        $this->post(route('web.rubros.store'), ['nombre' => 'Rubro falsificado T16', 'tipo' => 'EGRESO',
            'id' => $s->rubro_id, 'rubro_id' => $s->rubro_id, 'es_reservado_sistema' => true,
            'subrubros' => [['id' => $s->id, 'clasificacion_resultado' => 'APORTE']],
            'clasificacion_resultado' => 'APORTE'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $nuevo = Rubro::where('nombre', 'Rubro falsificado T16')->sole();
        $this->assertSame($cantidad + 1, Rubro::count());
        $this->assertNotSame($s->rubro_id, $nuevo->id);
        $this->assertFalse($nuevo->es_reservado_sistema);
        $this->assertSame([], $nuevo->subrubros->all());
        $this->assertSame($original, $s->rubro->fresh()->getAttributes());
        $this->assertSame($hijo, $s->fresh()->getAttributes());
        $this->post(route('web.rubros.store'), ['nombre' => $s->rubro->nombre, 'tipo' => 'EGRESO'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_alta_de_subrubro_usa_el_padre_de_la_ruta_y_no_el_falsificado(): void
    {
        $ingreso = $this->sub('Patines')->rubro_id;
        $egreso = $this->sub('Luz')->rubro_id;
        foreach ([[$ingreso, $egreso, 'RETIRO'], [$egreso, $ingreso, 'APORTE']] as [$ruta, $falso, $clase]) {
            $nombre = 'Alta falsificada '.$clase;
            $this->post(route('web.subrubros.store', $ruta), ['nombre' => $nombre,
                'permitido_para' => 'ADMIN', 'rubro_id' => $falso, 'clasificacion_resultado' => $clase])
                ->assertSessionHasErrors('clasificacion_resultado');
            $this->assertDatabaseMissing('subrubros', ['nombre' => $nombre]);
            $this->post(route('web.subrubros.store', $ruta), ['nombre' => $nombre,
                'permitido_para' => 'ADMIN', 'rubro_id' => $falso, 'clasificacion_resultado' => 'NEGOCIO',
                'es_reservado_sistema' => true, 'activo' => false, 'id' => $this->sub('Cuota Mensual')->id])
                ->assertSessionHasNoErrors()->assertSessionHas('success');
            $creado = $this->sub($nombre);
            $this->assertSame($ruta, $creado->rubro_id);
            $this->assertSame('NEGOCIO', $creado->clasificacion_resultado);
            $this->assertFalse($creado->es_reservado_sistema);
            $this->assertTrue($creado->activo);
        }
    }

    public function test_editar_no_mueve_subrubro_y_el_padre_cruzado_no_lo_encuentra(): void
    {
        foreach (['Patines' => ['Luz', 'APORTE'], 'Retiro de dueños' => ['Patines', 'RETIRO']] as $nombre => [$otro, $clase]) {
            $s = $this->sub($nombre);
            $rubroId = $s->rubro_id;
            $falsoId = $this->sub($otro)->rubro_id;
            $datos = $this->datos($s, $clase) + ['rubro_id' => $falsoId,
                'id' => $this->sub('Cuota Mensual')->id, 'es_reservado_sistema' => true, 'activo' => false];
            $this->put(route('web.subrubros.update', [$rubroId, $s->id]), $datos)
                ->assertSessionHasNoErrors()->assertSessionHas('success');
            $s->refresh();
            $this->assertSame($rubroId, $s->rubro_id);
            $this->assertSame($clase, $s->clasificacion_resultado);
            $this->assertFalse($s->es_reservado_sistema);
            $this->assertTrue($s->activo);
            $original = $s->getAttributes();
            $this->get(route('web.subrubros.edit', [$falsoId, $s->id]))->assertNotFound();
            $this->put(route('web.subrubros.update', [$falsoId, $s->id]), $datos)->assertNotFound();
            $this->patch(route('web.subrubros.toggle-activo', [$falsoId, $s->id]), $datos)->assertNotFound();
            $this->assertSame($original, $s->fresh()->getAttributes());
            $this->patch(route('web.subrubros.toggle-activo', [$rubroId, $s->id]),
                array_replace($datos, ['clasificacion_resultado' => 'NEGOCIO']))->assertSessionHas('success');
            $s->refresh();
            $this->assertFalse($s->activo);
            $this->assertSame($rubroId, $s->rubro_id);
            $this->assertSame($clase, $s->clasificacion_resultado);
        }
    }

    public function test_hijos_inactivos_y_campos_falsificados_no_eluden_el_rechazo(): void
    {
        foreach (['Patines' => 'EGRESO', 'Retiro de dueños' => 'INGRESO'] as $nombre => $nuevoTipo) {
            $s = $this->sub($nombre);
            $r = $s->rubro;
            DB::table('subrubros')->where('rubro_id', $r->id)->update(['activo' => false]);
            $original = $r->getAttributes();
            $datos = ['nombre' => $r->nombre.' falsificado', 'tipo' => $nuevoTipo,
                'subrubros' => [], 'clasificacion_resultado' => 'NEGOCIO', 'activo' => false,
                'rubro_id' => $this->sub('Luz')->rubro_id, 'es_reservado_sistema' => false];
            $this->from(route('web.rubros.edit', $r->id))->put(route('web.rubros.update', $r->id), $datos)
                ->assertRedirect(route('web.rubros.edit', $r->id))->assertSessionHas('error',
                    fn ($mensaje) => str_contains($mensaje, 'Cambiá primero qué es ese subrubro'));
            $this->assertSame($original, $r->fresh()->getAttributes());
            $this->post(route('web.rubros.update', $r->id), $datos + ['_method' => 'PUT'])
                ->assertSessionHas('error');
            $this->assertSame($original, $r->fresh()->getAttributes());
            $this->put(route('web.rubros.update', $r->id), array_replace($datos, ['tipo' => ['INGRESO']]))
                ->assertSessionHasErrors('tipo');
            $this->assertSame($original, $r->fresh()->getAttributes());
            $this->evidencia('tipo_bloqueado_con_hijos_inactivos', ['subrubro' => $nombre,
                'tipo_actual' => $r->fresh()->tipo, 'tipo_rechazado' => $nuevoTipo]);
        }
    }

    public function test_corregir_que_es_permite_cambio_valido_y_luego_rechaza_aporte(): void
    {
        $s = $this->sub('Patines');
        $r = $s->rubro;
        foreach ($r->subrubros as $hijo) {
            $this->put(route('web.subrubros.update', [$r->id, $hijo->id]), $this->datos($hijo, 'NEGOCIO'))
                ->assertSessionHas('success');
        }
        $this->put(route('web.rubros.update', $r->id), ['nombre' => $r->nombre, 'tipo' => 'EGRESO'])
            ->assertSessionHas('success');
        $this->assertSame('EGRESO', $r->fresh()->tipo);
        $this->put(route('web.subrubros.update', [$r->id, $s->id]), $this->datos($s, 'APORTE'))
            ->assertSessionHasErrors('clasificacion_resultado');
        $this->assertSame('NEGOCIO', $s->fresh()->clasificacion_resultado);
    }

    public function test_nombres_nuevos_en_ingreso_quedan_null_sin_duplicar_y_admin_puede_resolver(): void
    {
        $ingreso = Rubro::create(['nombre' => 'Nombre ambiguo T16', 'tipo' => 'INGRESO']);
        foreach (['Retiro de dueños', 'Pago al organizador'] as $nombre) {
            DB::table('subrubros')->where('nombre', $nombre)->update(['rubro_id' => $ingreso->id,
                'clasificacion_resultado' => null]);
        }
        $originales = DB::table('subrubros')->where('rubro_id', $ingreso->id)->orderBy('id')->get()->all();
        $cantidadRubros = Rubro::count();
        $cantidadSubs = Subrubro::count();
        $this->migrar();
        $this->migrar();
        $actuales = DB::table('subrubros')->where('rubro_id', $ingreso->id)->orderBy('id')->get()->all();
        $this->assertEquals($originales, $actuales);
        $this->assertSame($cantidadRubros, Rubro::count());
        $this->assertSame($cantidadSubs, Subrubro::count());
        foreach ($actuales as $fila) $this->assertNull($fila->clasificacion_resultado);
        $this->get(route('web.rubros.index'))->assertOk()->assertSee('(sin clasificar)');
        $this->evidencia('nombres_nuevos_en_ingreso', ['filas' => array_map(fn ($fila) => [
            'nombre' => $fila->nombre, 'rubro_id' => $fila->rubro_id,
            'clasificacion_resultado' => $fila->clasificacion_resultado], $actuales)]);
        $retiro = $this->sub('Retiro de dueños');
        $this->put(route('web.subrubros.update', [$ingreso->id, $retiro->id]), $this->datos($retiro, 'RETIRO'))
            ->assertSessionHasErrors('clasificacion_resultado');
        $this->put(route('web.rubros.update', $ingreso->id), ['nombre' => $ingreso->nombre, 'tipo' => 'EGRESO'])
            ->assertSessionHas('success');
        foreach (['Retiro de dueños' => 'RETIRO', 'Pago al organizador' => 'NEGOCIO'] as $nombre => $clase) {
            $s = $this->sub($nombre);
            $this->put(route('web.subrubros.update', [$ingreso->id, $s->id]), $this->datos($s, $clase))
                ->assertSessionHas('success');
            $this->assertSame($clase, $s->fresh()->clasificacion_resultado);
        }
    }

    public function test_nombres_nuevos_en_otro_egreso_se_completan_y_no_pisa_clasificacion(): void
    {
        $otro = $this->sub('Luz')->rubro_id;
        foreach (['Retiro de dueños', 'Pago al organizador'] as $nombre) {
            DB::table('subrubros')->where('nombre', $nombre)->update(['rubro_id' => $otro,
                'clasificacion_resultado' => null]);
        }
        $cantidad = Subrubro::count();
        $this->migrar();
        foreach (['Retiro de dueños' => 'RETIRO', 'Pago al organizador' => 'NEGOCIO'] as $nombre => $clase) {
            $s = $this->sub($nombre);
            $this->assertSame($otro, $s->rubro_id);
            $this->assertSame($clase, $s->clasificacion_resultado);
            $this->assertSame(1, Subrubro::where('nombre', $nombre)->count());
        }
        $retiro = $this->sub('Retiro de dueños');
        $retiro->update(['clasificacion_resultado' => 'NEGOCIO']);
        $original = $retiro->getAttributes();
        $this->migrar();
        $this->assertSame($original, $retiro->fresh()->getAttributes());
        $this->assertSame($cantidad, Subrubro::count());
    }

    public function test_api_no_registrada_y_demo_bloqueado_antes_de_escribir(): void
    {
        $s = $this->sub('Patines');
        $original = $s->getAttributes();
        foreach (app('router')->getRoutes() as $ruta) $this->assertFalse(str_starts_with($ruta->uri(), 'api/'));
        foreach (['/api/admin/rubros/'.$s->rubro_id, '/api/admin/subrubros/'.$s->id] as $ruta) {
            $this->assertContains($this->putJson($ruta, ['tipo' => 'EGRESO',
                'rubro_id' => $this->sub('Luz')->rubro_id, 'clasificacion_resultado' => 'APORTE'])->status(), [404, 405]);
        }
        $this->assertSame($original, $s->fresh()->getAttributes());
        $rubros = DB::table('rubros')->orderBy('id')->get()->all();
        $subrubros = DB::table('subrubros')->orderBy('id')->get()->all();
        $entorno = app()->environment();
        try {
            app()->instance('env', 'production');
            (new \Database\Seeders\DemoSeeder())->run();
            $this->fail('DemoSeeder debio detenerse antes de ejecutar la limpieza.');
        } catch (\RuntimeException $e) {
            $this->assertSame('DemoSeeder no puede ejecutarse en produccion.', $e->getMessage());
        } finally {
            app()->instance('env', $entorno);
        }
        $this->assertEquals($rubros, DB::table('rubros')->orderBy('id')->get()->all());
        $this->assertEquals($subrubros, DB::table('subrubros')->orderBy('id')->get()->all());
    }
}
