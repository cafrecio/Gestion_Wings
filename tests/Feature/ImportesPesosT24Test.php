<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Support\ImportesT24;
use Tests\TestCase;

class ImportesPesosT24Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-11 10:00:00');
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cada_pedido_directo_rechaza_tres_decimales_sin_escribir(): void
    {
        foreach (ImportesT24::CASOS as $caso) {
            DB::beginTransaction();
            try {
                $f = new ImportesT24($caso);
                foreach (['48.505', '48.000', '48.0000', '-1', 'letras', '1e2'] as $invalido) {
                    [$metodo, $ruta, $datos, $clave] = $f->pedido($caso, $invalido);
                    $antes = ImportesT24::fotoDatos();
                    $respuesta = $this->actingAs($caso === 'caja-movimiento' ? $f->operativo : $f->admin)->json($metodo, $ruta, $datos);
                    if ($respuesta->status() === 302) $respuesta->assertSessionHasErrors($clave);
                    else $respuesta->assertUnprocessable()->assertJsonValidationErrors($clave);
                    $this->assertSame($antes, ImportesT24::fotoDatos(), "$caso / $invalido escribió datos");
                }
            } finally {
                DB::rollBack();
            }
        }
    }

    public function test_cada_pantalla_guarda_dos_decimales_sin_perder_centavos(): void
    {
        foreach (ImportesT24::CASOS as $caso) {
            foreach (['48.50' => '48.50', '48,50' => '48.50', '48,000.50' => '48000.50', '48000' => '48000.00'] as $importe => $esperado) {
            DB::beginTransaction();
            try {
                $f = new ImportesT24($caso);
                [$metodo, $ruta, $datos] = $f->pedido($caso, (string) $importe);
                $respuesta = $this->actingAs($caso === 'caja-movimiento' ? $f->operativo : $f->admin)->json($metodo, $ruta, $datos);
                $this->assertContains($respuesta->status(), [200, 302], "$caso: ".$respuesta->getContent());
                $respuesta->assertSessionHasNoErrors();
                [$tabla, $columna, $filtro] = match ($caso) {
                    'apertura' => ['cajas_operativas', 'efectivo_inicial', ['usuario_operativo_id' => $f->operativo->id]],
                    'rectificacion' => ['cajas_operativas', 'efectivo_inicial', ['id' => $f->caja->id]],
                    'cierre-contado' => ['cajas_operativas', 'efectivo_contado', ['id' => $f->caja->id]],
                    'cierre-cambio' => ['cajas_operativas', 'cambio_retenido', ['id' => $f->caja->id]],
                    'cuota' => ['deuda_cuotas', 'monto_pagado', ['alumno_id' => $f->alumno->id, 'periodo' => '2026-10']],
                    'entregado' => ['pagos', 'monto_final', ['alumno_id' => $f->alumno->id]],
                    'adelantado' => ['deuda_cuotas', 'monto_original', ['alumno_id' => $f->alumno->id, 'periodo' => '2026-11']],
                    'caja-agregar', 'caja-editar', 'caja-movimiento' => ['movimientos_operativos', 'monto', ['subrubro_id' => $f->ingreso->id]],
                    'cashflow' => ['cashflow_movimientos', 'monto', ['subrubro_id' => $f->ingreso->id]],
                    'grupo-alta' => ['grupo_planes', 'precio_mensual', ['grupo_id' => DB::table('grupos')->where('nivel_id', DB::table('niveles')->where('nombre', 'Nivel nuevo T24')->value('id'))->value('id')]],
                    'grupo-editar' => ['grupo_planes', 'precio_mensual', ['id' => $f->plan->id]],
                    'plan-directo' => ['grupo_planes', 'precio_mensual', ['grupo_id' => $f->grupo->id, 'clases_por_semana' => 3]],
                    'profesor-alta' => ['profesores', 'valor_hora', ['dni' => '39999243']],
                    'profesor-editar' => ['profesores', 'valor_hora', ['id' => $f->profesor->id]],
                    'medio-alta' => ['tipos_caja', 'saldo_inicial', ['nombre' => 'Medio nuevo T24']],
                    'medio-editar' => ['tipos_caja', 'saldo_inicial', ['id' => $f->medio->id]],
                    'configuracion' => ['configuraciones', 'valor', ['clave' => 'inscripcion_importe']],
                    'liquidacion' => ['liquidaciones', 'monto_final', ['id' => $f->liquidacion->id]],
                };
                $guardado = $caso === 'liquidacion' ? $f->liquidacion->fresh()->monto_a_pagar : DB::table($tabla)->where($filtro)->value($columna);
                $this->assertSame($esperado, number_format((float) $guardado, 2, '.', ''), "$caso / $importe");
            } finally {
                DB::rollBack();
            }
            }
        }
    }

    public function test_el_cero_conserva_la_regla_de_cada_flujo(): void
    {
        $permitido = ['apertura', 'rectificacion', 'cierre-contado', 'cierre-cambio', 'profesor-alta',
            'profesor-editar', 'medio-alta', 'medio-editar', 'liquidacion'];
        foreach (ImportesT24::CASOS as $caso) {
            DB::beginTransaction();
            try {
                $f = new ImportesT24($caso);
                [$metodo, $ruta, $datos, $clave] = $f->pedido($caso, '0');
                $antes = ImportesT24::fotoDatos();
                $respuesta = $this->actingAs($caso === 'caja-movimiento' ? $f->operativo : $f->admin)->json($metodo, $ruta, $datos);
                if (in_array($caso, $permitido, true)) {
                    $this->assertContains($respuesta->status(), [200, 302], $caso);
                    $respuesta->assertSessionHasNoErrors();
                } else {
                    if ($respuesta->status() === 302) $respuesta->assertSessionHasErrors($clave);
                    else $respuesta->assertUnprocessable()->assertJsonValidationErrors($clave);
                    $this->assertSame($antes, ImportesT24::fotoDatos(), "$caso aceptó cero");
                }
            } finally {
                DB::rollBack();
            }
        }
    }

    public function test_el_saldo_con_historial_sigue_siendo_de_solo_lectura(): void
    {
        $f = new ImportesT24('medio-editar');
        \App\Models\MovimientoOperativo::create(['caja_operativa_id' => $f->caja->id, 'fecha' => today(),
            'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $f->ingreso->id,
            'monto' => '10.50', 'usuario_id' => $f->operativo->id, 'estado' => 'ACTIVO']);
        $this->actingAs($f->admin)->get("/tipos-caja/{$f->medio->id}/edit")
            ->assertOk()->assertSee('readonly', false)->assertSee('value="48,000.50"', false)
            ->assertDontSee('name="saldo_inicial"', false);
        [$metodo, $ruta, $datos] = $f->pedido('medio-editar', '48.505');
        $this->json($metodo, $ruta, $datos)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('48000.50', $f->medio->fresh()->saldo_inicial);
    }

    public function test_las_pantallas_renderizan_el_componente_comun_y_conservan_centavos_al_editar(): void
    {
        $exportar = getenv('WINGS_CAPTURAS_T24') === '1';
        $destino = base_path((getenv('WINGS_T24_EVIDENCIA_DIR') ?: 'docs/06-pruebas/PRU-04/evidencia-t24').'/html');
        $pantallas = [];
        if ($exportar && !is_dir($destino)) mkdir($destino, 0775, true);
        foreach (ImportesT24::CASOS as $caso) {
            if ($caso === 'plan-directo') continue;
            DB::beginTransaction();
            try {
                $f = new ImportesT24($caso);
                if ($exportar && $caso === 'configuracion') {
                    \App\Models\ReglaPrimerPago::create(['nombre' => 'Regla de prueba T24',
                        'dia_desde' => 1, 'dia_hasta' => 31, 'porcentaje' => 100, 'activo' => true]);
                }
                [, , , , $ruta, $selector] = $f->pedido($caso, '48.50');
                $html = $this->actingAs($caso === 'caja-movimiento' ? $f->operativo : $f->admin)->get($ruta)->assertOk()->getContent();
                $this->assertStringContainsString('data-money="true"', $html, $caso);
                $this->assertStringContainsString('inputmode="decimal"', $html, $caso);
                if (in_array($caso, ['caja-editar', 'grupo-editar', 'profesor-editar', 'medio-editar', 'liquidacion', 'rectificacion', 'cuota'])) {
                    $this->assertStringContainsString('value="48,000.50"', $html, $caso);
                }
                if ($exportar) {
                    // Respuesta real de Laravel: solo rutas de assets y token ficticio para la captura local.
                    $html = preg_replace('~https?://[^/"\s]+(/(?:build|img|favicon|apple-touch|site\.webmanifest)[^"\s]*)~', '$1', $html);
                    $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1captura-t24', $html);
                    $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1captura-t24', $html);
                    file_put_contents("$destino/$caso.html", $html);
                    file_put_contents("$destino/$caso-marco-360.html", '<!doctype html><html lang="es"><meta charset="utf-8"><title>Marco 360 · T24</title><style>body{margin:0;background:rgb(244,246,248);display:flex;justify-content:center;padding:20px}iframe{width:360px;height:820px;border:0;background:white}</style><iframe title="Wings a 360" src="'.$caso.'.html"></iframe></html>');
                    $pantallas[] = ['caso' => $caso, 'ruta' => $ruta, 'selector' => $selector];
                }
            } finally {
                DB::rollBack();
            }
        }
        if ($exportar) {
            foreach (['medio-historial', 'cierre-con-conteo'] as $variante) {
                DB::beginTransaction();
                try {
                    $f = new ImportesT24('medio-editar');
                    if ($variante === 'medio-historial') {
                        \App\Models\MovimientoOperativo::create(['caja_operativa_id' => $f->caja->id, 'fecha' => today(),
                            'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $f->ingreso->id,
                            'monto' => '10.50', 'usuario_id' => $f->operativo->id, 'estado' => 'ACTIVO']);
                        $ruta = "/tipos-caja/{$f->medio->id}/edit";
                        $selector = '#saldo_inicial';
                    } else {
                        $f->caja->update(['estado' => 'RECHAZADA', 'efectivo_contado' => '48000.50', 'cambio_retenido' => '0']);
                        $ruta = "/caja/{$f->caja->id}/cerrar";
                        $selector = '#efectivo_contado';
                    }
                    $html = $this->actingAs($f->admin)->get($ruta)->assertOk()->getContent();
                    $html = preg_replace('~https?://[^/"\s]+(/(?:build|img|favicon|apple-touch|site\.webmanifest)[^"\s]*)~', '$1', $html);
                    $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1captura-t24', $html);
                    $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1captura-t24', $html);
                    file_put_contents("$destino/$variante.html", $html);
                    file_put_contents("$destino/$variante-marco-360.html", '<!doctype html><html lang="es"><meta charset="utf-8"><title>Marco 360 · T24</title><style>body{margin:0;background:rgb(244,246,248);display:flex;justify-content:center;padding:20px}iframe{width:360px;height:820px;border:0;background:white}</style><iframe title="Wings a 360" src="'.$variante.'.html"></iframe></html>');
                    $pantallas[] = ['caso' => $variante, 'ruta' => $ruta, 'selector' => $selector, 'soloLectura' => true];
                } finally {
                    DB::rollBack();
                }
            }
            $this->app['auth']->forgetGuards();
            $html = $this->get('/login')->assertOk()->getContent();
            $html = preg_replace('~https?://[^/"\s]+(/(?:build|img|favicon|apple-touch|site\.webmanifest)[^"\s]*)~', '$1', $html);
            $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1captura-t24', $html);
            $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1captura-t24', $html);
            file_put_contents("$destino/login.html", $html);
            file_put_contents("$destino/login-marco-360.html", '<!doctype html><html lang="es"><meta charset="utf-8"><title>Login de control a 360</title><style>body{margin:0;display:flex;justify-content:center;padding:20px;background:rgb(244,246,248)}iframe{width:360px;height:820px;border:0;background:white}</style><iframe title="Login a 360" src="login.html"></iframe></html>');
            file_put_contents(dirname($destino).'/pantallas.json', json_encode($pantallas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }
}
