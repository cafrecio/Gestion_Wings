<?php

namespace Tests\Propuestas;

use App\Http\Controllers\CashflowWebController;
use App\Models\{CashflowMovimiento, Configuracion, MovimientoOperativo, Rubro, Subrubro, User};
use App\Services\{CashflowService, CashflowIntegracionCajaService, LiquidacionPagoService, PagoCuotaService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification, Route, Storage, View};
use Tests\Support\ImportesT24;
use Tests\TestCase;

/** Etapa 1: renderizar una propuesta aislada; no instala rutas ni correcciones en Wings. */
class CapturaPropuestaT27Test extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || !in_array(config('database.connections.mysql.database'),
            ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('La propuesta solo permite bases descartables de pruebas.');
        }
    }

    public function test_capturar_propuesta_con_datos_reales_del_controlador_y_sin_corregir_importes(): void
    {
        Carbon::setTestNow('2026-10-12 12:00:00');
        Notification::fake(); Storage::fake();
        try {
            $f = new ImportesT24('apertura');
            $f->admin->update(['name' => 'Administración · Propuesta']);
            $f->operativo->update(['name' => 'Mostrador · Propuesta']);
            $f->alumno->update(['nombre' => 'Lucía', 'apellido' => 'Demostración']);
            $f->medio->update(['nombre' => 'Banco del club', 'abreviatura' => 'BAN', 'saldo_inicial' => '2000000.00']);
            $mapa = [];
            $manual = function ($nombre, $tipo, $clasificacion, $monto) use ($f, &$mapa) {
                $rubro = Rubro::create(['nombre' => $nombre, 'tipo' => $tipo]);
                $sub = Subrubro::create(['nombre' => $nombre, 'rubro_id' => $rubro->id,
                    'permitido_para' => 'ADMIN', 'afecta_caja' => false,
                    'clasificacion_resultado' => $clasificacion, 'activo' => true]);
                $mov = app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $f->admin->id,
                    'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $sub->id, 'monto' => $monto,
                    'fecha' => today()->toDateString(), 'observaciones' => $nombre]);
                $origen = match ($clasificacion) {'APORTE' => 'Aporte', 'RETIRO' => 'Retiro', default => 'Directo'};
                $mapa[$mov->id] = ['origen' => $origen, 'acciones' => ['Editar', 'Eliminar'], 'ruta' => null,
                    'ayuda' => 'Este movimiento directo todavía no tiene edición ni eliminación. La forma de corregirlo se define con Carlos antes de habilitarla.'];
            };
            $manual('Intereses Mercado Pago', 'INGRESO', 'NEGOCIO', '48.50');
            $manual('Servicio de internet', 'EGRESO', 'NEGOCIO', '23500.75');
            $manual('Aporte del dueño', 'INGRESO', 'APORTE', '1250000.50');
            $manual('Retiro del dueño', 'EGRESO', 'RETIRO', '35000.25');
            Configuracion::set('inscripcion_importe', '5000.00');
            app(\App\Services\InscripcionService::class)->sincronizar($f->alumno, $f->admin->id);
            $pago = app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $f->alumno->id,
                'tipo_caja_id' => $f->medio->id, 'usuario_admin_id' => $f->admin->id,
                'fecha_pago' => today()->toDateString(), 'items' => [['periodo' => '2026-10', 'monto' => '48000.50']]]);
            foreach (CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)->get() as $mov) {
                $esInscripcion = $mov->subrubro->nombre === 'Inscripción al club';
                $mapa[$mov->id] = ['origen' => $esInscripcion ? 'Inscripción' : 'Cobro del dueño', 'acciones' => ['Anular'],
                    'ruta' => route('web.alumnos.show', $f->alumno->id),
                    'ayuda' => 'La anulación se hace desde el historial del alumno, con motivo. Si cuota e inscripción se cobraron juntas, se anula el pago completo.'];
            }
            $f->liquidacion->update(['estado' => 'CERRADA']);
            $sueldos = Subrubro::create(['nombre' => 'Sueldo del docente', 'rubro_id' => Rubro::where('nombre', 'Sueldos')->value('id'),
                'permitido_para' => 'ADMIN', 'afecta_caja' => false, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
            $liq = (new LiquidacionPagoService())->marcarComoPagada($f->liquidacion->id,
                ['admin_id' => $f->admin->id, 'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $sueldos->id]);
            $mapa[$liq['cashflow_movimiento']->id] = ['origen' => 'Liquidación pagada', 'acciones' => ['Ver'],
                'ruta' => route('web.liquidaciones.show', $f->liquidacion->id),
                'ayuda' => 'Hoy una liquidación pagada no se cancela ni se modifica. Su inclusión en esta pantalla de correcciones requiere una decisión de Carlos.'];
            $caja = \Tests\Support\CajaDeclarada::crear($f->operativo->id, $f->medio->id);
            $operativo = MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'fecha' => today(),
                'subrubro_id' => $f->ingreso->id, 'tipo_caja_id' => $f->medio->id, 'monto' => '1250.50',
                'usuario_id' => $f->operativo->id, 'estado' => 'ACTIVO', 'observaciones' => 'Ingreso de mostrador']);
            $caja->update(['estado' => 'VALIDADA', 'usuario_admin_validacion_id' => $f->admin->id]);
            app(CashflowIntegracionCajaService::class)->reflejarCajaEnCashflow($caja->id, $f->admin->id);
            $reflejo = CashflowMovimiento::where('movimiento_operativo_id', $operativo->id)->firstOrFail();
            $mapa[$reflejo->id] = ['origen' => 'Caja validada', 'acciones' => ['Ver'],
                'ruta' => route('web.caja.detalle', $caja->id),
                'ayuda' => 'Hoy no se corrige un movimiento de caja validada. Rechazar también exige caja cerrada, no validada. Este camino queda pendiente de definición.'];
            $abierta = \Tests\Support\CajaDeclarada::crear($f->operativo->id, $f->medio->id);
            $enCaja = app(\App\Services\CajaService::class)->registrarMovimientoEnCaja($abierta->id,
                ['subrubro_id' => $f->ingreso->id, 'tipo_caja_id' => $f->medio->id,
                    'monto' => '875.75', 'observaciones' => 'Ingreso a revisar en mostrador']);

            View::addNamespace('t27', base_path('docs/06-pruebas/PRU-04/evidencia-t27'));
            // Ruta exclusivamente del proceso de pruebas: datos y controlador de Wings reales.
            Route::get('/cashflow/propuesta-t27', function (\Illuminate\Http\Request $request) use ($mapa) {
                $vista = app(CashflowWebController::class)->index($request);
                return View::file(base_path('docs/06-pruebas/PRU-04/evidencia-t27/maqueta.blade.php'),
                    $vista->getData() + ['propuestaPorId' => $mapa,
                        'enCajasEditables' => MovimientoOperativo::with(['cajaOperativa', 'subrubro', 'usuario'])
                            ->where('estado', 'ACTIVO')->whereHas('cajaOperativa', fn ($q) => $q->whereIn('estado', ['ABIERTA', 'RECHAZADA']))->get()]);
            })->middleware(['web', 'auth', 'ensure.active.web', 'ensure.admin.web']);
            $this->actingAs($f->admin);
            $antes = $this->foto();
            $res = $this->get('/cashflow/propuesta-t27?periodo=mes&anio=2026&mes=10')->assertOk();
            $res->assertSee('aria-label="Editar"', false)->assertSee('Liquidación pagada')->assertSee('Caja validada');
            $this->assertSame($antes, $this->foto(), 'La propuesta no puede corregir datos');
            $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t27');
            $this->guardar($dir, 'propuesta', $res->getContent());
            foreach (['cashflow-actual' => '/cashflow', 'movimientos-actual' => '/movimientos',
                'historial-actual' => '/caja/historial', 'ficha-alumno' => '/alumnos/'.$f->alumno->id,
                'caja-validada' => '/caja/'.$caja->id.'/detalle', 'liquidacion-pagada' => '/liquidaciones/'.$f->liquidacion->id,
                'caja-abierta' => '/caja/'.$abierta->id.'/detalle',
                'editar-en-caja' => '/caja/'.$abierta->id.'/movimientos/'.$enCaja->id.'/editar'] as $id => $ruta) {
                $this->guardar($dir, $id, $this->get($ruta)->assertOk()->getContent());
            }
            $this->assertSame($antes, $this->foto());
            foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
                $usuario = User::factory()->create(['rol' => $rol, 'activo' => true]);
                $this->actingAs($usuario)->get('/cashflow')->assertForbidden();
                $this->get('/cashflow/propuesta-t27')->assertForbidden();
            }
            $this->app['auth']->forgetGuards();
            $this->guardar($dir, 'login', $this->get('/login')->assertOk()->getContent());
            file_put_contents($dir.'/casos.json', json_encode($mapa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function foto(): array
    {
        $foto = [];
        foreach (['pagos', 'pago_deuda_cuota', 'pago_cargo_alumno', 'cargos_alumno', 'deuda_cuotas',
            'cashflow_movimientos', 'movimientos_operativos', 'cajas_operativas', 'liquidaciones'] as $tabla) {
            $foto[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        return $foto;
    }

    private function guardar(string $dir, string $id, string $html): void
    {
        if (!is_dir($dir.'/html')) mkdir($dir.'/html', 0775, true);
        $html = preg_replace('~https?://[^/"\s]+(/(?:build|img|favicon|apple-touch|site\.webmanifest)[^"\s]*)~', '$1', $html);
        $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1propuesta-t27', $html);
        $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1propuesta-t27', $html);
        file_put_contents("$dir/html/$id.html", $html);
        file_put_contents("$dir/html/$id-marco-360.html", '<!doctype html><html lang="es"><meta charset="utf-8"><style>body{margin:0;background:rgb(244,246,248);display:flex;justify-content:center;padding:20px}iframe{width:360px;height:820px;border:0;background:white}</style><iframe title="Wings · propuesta a 360" src="'.$id.'.html"></iframe></html>');
    }
}
