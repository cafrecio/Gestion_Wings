<?php

namespace Tests\Feature;

use App\Support\FormatoImporte;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Tests\Support\ImportesT30;
use Tests\TestCase;

class MostrarImportesT30Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        Carbon::setTestNow('2026-10-12 12:00:00');
        parent::setUp();
        Notification::fake();
        Storage::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cada_pantalla_muestra_centavos_e_importes_largos_sin_cambiar_lo_guardado(): void
    {
        $fase = getenv('WINGS_T30_EXPORTAR');
        $exportar = in_array($fase, ['antes', 'despues'], true);
        $carpeta = getenv('WINGS_T30_EVIDENCIA_DIR') ?: 'docs/06-pruebas/PRU-04/evidencia-t30';
        $destino = base_path($carpeta.($fase === 'antes' ? '/antes' : ''));
        if ($exportar && !is_dir($destino.'/html')) mkdir($destino.'/html', 0775, true);
        $resultados = []; $fallas = []; $rutas = [];
        foreach (['centavos' => '1250.50', 'millones' => '1250000.50'] as $perfil => $importe) {
            DB::beginTransaction();
            $escenario = new ImportesT30($importe);
            $f = $escenario->f;
            foreach ($escenario->pantallas() as $caso => [$ruta, $muestra, $rol, $campo]) {
                $usuario = $rol === 'OPERATIVO' ? $f->operativo : $f->admin;
                $antes = $escenario->importesGuardados();
                $respuesta = $this->actingAs($usuario)->withSession(['_old_input' => [
                    'monto' => $importe, 'saldo_inicial' => $importe, 'valor_hora' => $importe,
                    'valor' => $importe, 'efectivo_inicial' => $importe, 'efectivo_contado' => $importe,
                    'planes' => [['id' => $f->plan->id, 'clases_por_semana' => 2, 'precio_mensual' => $importe]],
                ]])->get($ruta);
                $html = $respuesta->getContent();
                if ($respuesta->status() !== 200) {
                    $fallas[] = "$caso/$perfil: HTTP ".$respuesta->status();
                    $resultados[] = compact('caso', 'perfil', 'ruta') + ['http' => $respuesta->status()];
                    continue;
                }
                $datos = $respuesta->original->getData();
                if (in_array($caso, ['admin', 'reportes'])) $muestra = ($datos['reporte']['ingresos'] ?? 0) / 100;
                if ($caso === 'operativo') $muestra = $datos['totalCobradoHoy'];
                if ($caso === 'sueldos') $muestra = ($datos['reporte']['costo'] ?? 0) / 100;
                $esperado = $campo ? FormatoImporte::numero($muestra) : FormatoImporte::pesos($muestra);
                $presente = str_contains($html, $esperado);
                if (in_array($caso, ['alumno-alta', 'alumno-editar'])) {
                    // JSON conserva el dato: puede representar 1250.50 como 1250.5. Chrome prueba su texto.
                    $doc = new \DOMDocument();
                    @$doc->loadHTML('<?xml encoding="UTF-8">'.$html);
                    $planes = json_decode($doc->getElementById('plan-section')->getAttribute('data-planes'), true);
                    $precios = array_merge(...array_values($planes));
                    $presente = in_array((float) $importe, array_map(fn ($p) => (float) $p['precio'], $precios), true);
                    $esperado = FormatoImporte::pesos($importe);
                }
                if (!$presente) $fallas[] = "$caso/$perfil: falta $esperado";
                $this->assertSame($antes, $escenario->importesGuardados(), "$caso: un GET cambió datos");
                $resultados[] = compact('caso', 'perfil', 'ruta', 'esperado', 'presente');
                if ($exportar) {
                    $id = "$caso-$perfil";
                    $this->guardarHtml($destino, $id, $html);
                    $rutas[] = compact('id', 'caso', 'perfil', 'ruta', 'esperado', 'campo');
                }
            }
            $this->actingAs($f->admin);
            if ($exportar) {
                // Respuestas reales de los GET que el formulario consulta desde JavaScript.
                foreach (['inscripcion' => '/alumnos/inscripcion-preview?'.http_build_query([
                    'dni' => $f->alumno->dni, 'fecha_alta' => '2026-08-01', 'alumno_id' => $f->alumno->id]),
                    'cuota' => '/alumnos/cuota-alta-preview?'.http_build_query([
                        'fecha_alta' => '2026-08-01', 'plan_id' => $f->plan->id])] as $tipo => $url) {
                    $json = $this->getJson($url)->assertOk()->getContent();
                    file_put_contents("$destino/html/api-$tipo-$perfil.json", $json);
                }
            }
            // Primera carga presenta un resumen guardado: sus importes vienen de datos ficticios.
            \App\Models\PrimeraCarga::findOrFail(1)->update(['detalle' => ['resumen' => [
                'alumnos' => 1, 'cuotas' => 1, 'inscripciones' => 1, 'cuotas_monto' => $importe,
                'inscripcion_monto' => '250.50', 'total' => $escenario->totalMovimientos]]]);
            $respuesta = $this->get('/sistema/primera-carga')->assertOk();
            $html = $respuesta->getContent();
            $esperado = FormatoImporte::pesos($importe);
            $presente = str_contains($html, $esperado);
            if (!$presente) $fallas[] = "primera-carga/$perfil: falta $esperado";
            $resultados[] = ['caso' => 'primera-carga', 'perfil' => $perfil, 'esperado' => $esperado, 'presente' => $presente];
            if ($exportar) {
                $id = "primera-carga-$perfil"; $campo = false; $caso = 'primera-carga'; $ruta = '/sistema/primera-carga';
                $this->guardarHtml($destino, $id, $html);
                $rutas[] = compact('id', 'caso', 'perfil', 'ruta', 'esperado', 'campo');
            }
            // Apertura: un operativo distinto con un cierre anterior real en el escenario.
            $f->caja->update(['estado' => 'CERRADA']);
            $operativo = \App\Models\User::factory()->create(['name' => 'Apertura T30', 'rol' => 'OPERATIVO', 'activo' => true]);
            \App\Models\CajaOperativa::create(['usuario_operativo_id' => $operativo->id,
                'apertura_at' => now()->subDay(), 'cierre_at' => now()->subDay()->addHours(2),
                'estado' => 'CERRADA', 'tipo_caja_efectivo_id' => $f->medio->id,
                'efectivo_inicial' => $importe, 'efectivo_contado' => $importe, 'cambio_retenido' => $importe]);
            $respuesta = $this->actingAs($operativo)->withSession(['_old_input' => ['efectivo_inicial' => $importe]])->get('/caja/apertura')->assertOk();
            $html = $respuesta->getContent();
            $esperado = FormatoImporte::pesos($importe);
            $presente = str_contains($html, $esperado);
            if (!$presente) $fallas[] = "caja-apertura/$perfil: falta $esperado";
            $resultados[] = ['caso' => 'caja-apertura', 'perfil' => $perfil, 'esperado' => $esperado, 'presente' => $presente];
            if ($exportar) {
                $id = "caja-apertura-$perfil"; $campo = false; $caso = 'caja-apertura'; $ruta = '/caja/apertura';
                $this->guardarHtml($destino, $id, $html);
                $rutas[] = compact('id', 'caso', 'perfil', 'ruta', 'esperado', 'campo');
            }
            $this->actingAs($f->admin);
            foreach ([$escenario->hora, $f->liquidacion] as $liquidacion) {
                $liquidacion->update(['estado' => 'CERRADA']);
                $liquidacion->update(['estado_pago' => 'PAGADA', 'pagada_fecha' => today(),
                    'pagada_tipo_caja_id' => $f->medio->id, 'pagada_subrubro_id' => $f->ingreso->id,
                    'pagada_por_admin_id' => $f->admin->id]);
            }
            foreach (['cuota' => "/recibos/cuota/{$escenario->pago->id}",
                'hora' => "/recibos/liquidacion/{$escenario->hora->id}",
                'comision' => "/recibos/liquidacion/{$f->liquidacion->id}"] as $tipo => $url) {
                $pdf = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();
                $this->assertStringStartsWith('%PDF-', $pdf);
                if ($exportar) {
                    if (!is_dir($destino.'/pdf')) mkdir($destino.'/pdf', 0775, true);
                    file_put_contents("$destino/pdf/recibo-$tipo-$perfil.pdf", $pdf);
                }
            }
            // Evita que el segundo perfil lea los registros del primero.
            DB::rollBack();
        }
        if ($exportar) {
            $this->app['auth']->forgetGuards();
            $this->guardarHtml($destino, 'login', $this->get('/login')->assertOk()->getContent());
            file_put_contents($destino.'/pantallas.json', json_encode($rutas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            file_put_contents($destino.'/resultado-servidor.json', json_encode(compact('resultados', 'fallas'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $this->assertSame([], $fallas, implode("\n", $fallas));
    }

    public function test_avisos_del_servidor_mail_y_telegram_conservan_centavos_sin_enviar_mensajes_reales(): void
    {
        $escenario = new ImportesT30(); $f = $escenario->f;
        $this->actingAs($f->admin);
        $preview = $this->getJson('/alumnos/inscripcion-preview?'.http_build_query([
            'dni' => '39999300', 'fecha_alta' => '2026-08-01']))->assertOk();
        $this->assertStringContainsString('$1,250.50', $preview->json('mensaje'));
        \App\Models\DeudaCuota::create(['alumno_id' => $f->alumno->id, 'periodo' => '2026-08',
            'monto_original' => '1250.50', 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $antes = $escenario->importesGuardados();
        $respuesta = $this->actingAs($f->operativo)->postJson("/caja/cobrar/{$f->alumno->id}", [
            'tipo_caja_id' => $f->medio->id, 'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => '1250.50'], 'fecha_pago' => today()->toDateString(),
        ])->assertStatus(409);
        $this->assertStringContainsString('$1,250.50', $respuesta->json('message'));
        $this->assertSame($antes, $escenario->importesGuardados());
        $notificacion = new \App\Notifications\CobroConDeudaAnteriorNotification($f->alumno,
            $escenario->pago->id, ['2026-08'], 1250.50, 'Acuerdo ficticio T30', $f->operativo);
        $mail = $notificacion->toMail($f->admin);
        $this->assertStringContainsString('$1,250.50', implode(' ', $mail->introLines));
        $f->caja->update(['estado' => 'CERRADA']);
        $this->assertTrue(app(\App\Services\AvisoAdminService::class)->resumenDiario());
        $aviso = null;
        Notification::assertSentTo($f->admin, \App\Notifications\AvisoOperativo::class, function ($n) use (&$aviso) {
            $aviso = $n; return true;
        });
        $telegram = $aviso->toTelegram($f->admin);
        $this->assertStringContainsString('$2,751.50', $telegram);
        $this->assertStringContainsString('$2,751.50', implode(' ', $aviso->toMail($f->admin)->introLines));
        if (getenv('WINGS_T30_EXPORTAR') === 'despues') {
            $destino = base_path('docs/06-pruebas/PRU-04/evidencia-t30/comunicaciones');
            if (!is_dir($destino)) mkdir($destino, 0775, true);
            file_put_contents($destino.'/telegram-resumen.txt', $telegram);
            file_put_contents($destino.'/mail-deuda-anterior.html', (string) $mail->render());
            file_put_contents($destino.'/mail-resumen.html', (string) $aviso->toMail($f->admin)->render());
            file_put_contents($destino.'/aviso-deuda-anterior.json', $respuesta->getContent());
        }
    }

    public function test_excel_mantiene_celdas_numericas_y_el_informe_conserva_el_original(): void
    {
        new ImportesT30('1250000.50');
        $servicio = app(\App\Services\PrimeraCargaExcelService::class);
        $libro = $servicio->plantilla();
        $precio = $libro->getSheetByName('Catálogos')->getCell('D2');
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC, $precio->getDataType());
        $this->assertSame(1250000.50, (float) $precio->getValue());
        $this->assertSame('#,##0.00', $precio->getStyle()->getNumberFormat()->getFormatCode());
        $libro->getSheetByName('Alumnos')->setCellValue('P2', 1250.50);
        $libro->getSheetByName('Alumnos')->getStyle('P2')->getNumberFormat()->setFormatCode('0.0');
        $origen = tempnam(sys_get_temp_dir(), 't30-orig-'); $destino = tempnam(sys_get_temp_dir(), 't30-informe-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro))->save($origen);
            $servicio->guardarInforme($origen, [['fila' => 2, 'celda' => 'P2', 'columna' => 'Monto 1',
                'mensaje' => 'Aviso ficticio T30', 'esperado' => 'Revisar']], $destino);
            $salida = \PhpOffice\PhpSpreadsheet\IOFactory::load($destino);
            $celda = $salida->getSheetByName('Alumnos')->getCell('P2');
            $this->assertSame(1250.50, (float) $celda->getValue());
            $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC, $celda->getDataType());
            $this->assertSame('0.0', $celda->getStyle()->getNumberFormat()->getFormatCode());
            $this->assertStringContainsString('Aviso ficticio T30', $salida->getSheetByName('Alumnos')->getCell('AM2')->getValue());
            if (getenv('WINGS_T30_EXPORTAR') === 'despues') {
                $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t30/excel');
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                copy($origen, $dir.'/original-ficticio.xlsx'); copy($destino, $dir.'/informe-ficticio.xlsx');
            }
        } finally {
            unlink($origen); unlink($destino);
        }
    }

    public function test_totales_visibles_coinciden_con_la_suma_de_renglones_visibles(): void
    {
        $escenario = new ImportesT30(); $f = $escenario->f;
        $this->actingAs($f->admin);
        foreach ([
            '/cashflow' => [1501.00, '$1,501.00'],
            "/caja/{$f->caja->id}/detalle" => [2751.50, '$2,751.50'],
            '/caja/historial' => [4252.50, '$4,252.50'],
            '/movimientos' => [2751.50, '$2,751.50'],
        ] as $ruta => [$esperado, $textoTotal]) {
            $respuesta = $this->get($ruta)->assertOk()->assertSee($textoTotal);
            $doc = new \DOMDocument(); @$doc->loadHTML('<?xml encoding="UTF-8">'.$respuesta->getContent());
            $importes = [];
            foreach ($doc->getElementsByTagName('table') as $tabla) {
                $columna = null;
                foreach ($tabla->getElementsByTagName('th') as $i => $th) {
                    if (trim($th->textContent) === 'Monto') $columna = $i;
                }
                if ($columna === null) continue;
                foreach ($tabla->getElementsByTagName('tbody') as $tbody) {
                    foreach ($tbody->getElementsByTagName('tr') as $tr) {
                        $td = $tr->getElementsByTagName('td')->item($columna);
                        if (!$td) continue;
                        $importes[] = $this->leerPesosVisibles($td->textContent);
                    }
                }
            }
            $this->assertNotEmpty($importes, $ruta);
            $this->assertEqualsWithDelta($esperado, array_sum($importes), .0001, $ruta);
        }
        foreach ([$escenario->hora->id => ['Subtotal:', 1875.75, '$1,875.75'],
            $f->liquidacion->id => ['Comisión:', 250.10, '$250.10']] as $id => [$rotulo, $esperado, $textoTotal]) {
            $respuesta = $this->get("/liquidaciones/$id")->assertOk()->assertSee($textoTotal);
            $doc = new \DOMDocument(); @$doc->loadHTML('<?xml encoding="UTF-8">'.$respuesta->getContent());
            $xpath = new \DOMXPath($doc); $importes = [];
            foreach ($xpath->query('//span[contains(@class,"info-label")]') as $etiqueta) {
                if (trim($etiqueta->textContent) !== $rotulo) continue;
                foreach ($etiqueta->parentNode->getElementsByTagName('span') as $valor) {
                    if (str_contains($valor->getAttribute('class'), 'info-value')) $importes[] = $this->leerPesosVisibles($valor->textContent);
                }
            }
            $this->assertNotEmpty($importes);
            $this->assertEqualsWithDelta($esperado, array_sum($importes), .0001);
        }
    }

    private function leerPesosVisibles(string $texto): float
    {
        $this->assertMatchesRegularExpression('/\$\s*-?[0-9][0-9,]*\.[0-9]{2}/u', $texto);
        preg_match('/\$\s*(-?[0-9][0-9,]*\.[0-9]{2})/u', $texto, $partes);
        $valor = (float) str_replace(',', '', $partes[1]);
        return preg_match('/[−–-]\s*\$/u', $texto) ? -$valor : $valor;
    }

    public function test_un_recibo_ya_emitido_se_conserva_sin_regenerarlo(): void
    {
        $escenario = new ImportesT30();
        $servicio = app(\App\Services\ReciboService::class);
        $ruta = $servicio->generarReciboCuota($escenario->pago->id);
        $contenido = Storage::get($ruta);
        Carbon::setTestNow('2026-10-13 12:00:00');
        $this->assertSame($ruta, $servicio->generarReciboCuota($escenario->pago->id));
        $this->assertSame($contenido, Storage::get($ruta));
    }

    private function guardarHtml(string $destino, string $id, string $html): void
    {
        $html = preg_replace('~https?://[^/"\s]+(/(?:build|img|favicon|apple-touch|site\.webmanifest)[^"\s]*)~', '$1', $html);
        $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1captura-t30', $html);
        $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1captura-t30', $html);
        file_put_contents("$destino/html/$id.html", $html);
        file_put_contents("$destino/html/$id-marco-360.html", '<!doctype html><html lang="es"><meta charset="utf-8"><title>Wings · marco360 T30</title><style>body{margin:0;background:rgb(244,246,248);display:flex;justify-content:center;padding:20px}iframe{width:360px;height:820px;border:0;background:white}</style><iframe title="Wings a 360" src="'.$id.'.html"></iframe></html>');
    }
}
