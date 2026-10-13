<?php

namespace Tests\Capturas;

use App\Models\{CashflowMovimiento, Configuracion, Liquidacion, MovimientoOperativo, Rubro, Subrubro};
use App\Services\{CashflowService, CashflowIntegracionCajaService, LiquidacionPagoService, PagoCuotaService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification, Storage};
use Illuminate\Support\Str;
use Tests\Support\ImportesT24;
use Tests\TestCase;

/** Exporta respuestas de rutas de Wings reales, sin rutas o vistas de maqueta. */
class CapturaImplementacionT27Test extends TestCase
{
    use RefreshDatabase;
    private array $rutas = [];

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || !in_array(config('database.connections.mysql.database'),
            ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('La captura solo permite bases descartables de pruebas.');
        }
    }

    public function test_capturar_recorrido_real_de_edicion_anulacion_y_consultas(): void
    {
        Carbon::setTestNow('2026-10-12 12:00:00'); Notification::fake(); Storage::fake();
        try {
            $f = new ImportesT24('apertura');
            $f->admin->update(['name' => 'Administración · T27']);
            $f->operativo->update(['name' => 'Mostrador · T27']);
            $f->alumno->update(['nombre' => 'Lucía', 'apellido' => 'Demostración']);
            $f->medio->update(['nombre' => 'Banco del club', 'abreviatura' => 'BAN', 'saldo_inicial' => '2000000.00']);
            $crear = function ($nombre, $tipo, $clasificacion, $monto) use ($f) {
                $r = Rubro::create(['nombre' => $nombre, 'tipo' => $tipo]);
                $s = Subrubro::create(['nombre' => $nombre, 'rubro_id' => $r->id, 'permitido_para' => 'ADMIN',
                    'clasificacion_resultado' => $clasificacion, 'activo' => true, 'afecta_caja' => false]);
                return app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $f->admin->id,
                    'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $s->id, 'monto' => $monto,
                    'fecha' => '2026-10-12', 'observaciones' => $nombre]);
            };
            $directo = $crear('Intereses Mercado Pago', 'INGRESO', 'NEGOCIO', '48.50');
            $crear('Servicio de internet', 'EGRESO', 'NEGOCIO', '23500.75');
            $crear('Aporte del dueño', 'INGRESO', 'APORTE', '1250000.50');
            $crear('Retiro del dueño', 'EGRESO', 'RETIRO', '35000.25');
            Configuracion::set('inscripcion_importe', '5000.00');
            app(\App\Services\InscripcionService::class)->sincronizar($f->alumno, $f->admin->id);
            app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $f->alumno->id,
                'tipo_caja_id' => $f->medio->id, 'usuario_admin_id' => $f->admin->id, 'fecha_pago' => '2026-10-12',
                'items' => [['periodo' => '2026-10', 'monto' => '48000.50']]]);
            // Sin detalles, el cálculo original es cero. El importe pactado se registra
            // por el ajuste existente, evitando inventar una suma que la vista no calcula.
            $f->liquidacion->update(['total_calculado' => '0.00']);
            $f->liquidacion->ajustarMontoFinal('48000.50', $f->admin->id, 'Monto acordado para la muestra T27', '0.00');
            $f->liquidacion->refresh()->update(['estado' => 'CERRADA']);
            $sueldos = Subrubro::create(['nombre' => 'Sueldo del docente', 'rubro_id' => Rubro::where('nombre', 'Sueldos')->value('id'),
                'permitido_para' => 'ADMIN', 'activo' => true, 'clasificacion_resultado' => 'NEGOCIO', 'afecta_caja' => false]);
            app(LiquidacionPagoService::class)->marcarComoPagada($f->liquidacion->id,
                ['admin_id' => $f->admin->id, 'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $sueldos->id]);
            $sinPagar = Liquidacion::create(['profesor_id' => $f->profesor->id, 'mes' => 9, 'anio' => 2026,
                'tipo' => 'COMISION', 'total_calculado' => '0.00', 'porcentaje_comision_aplicado' => 20,
                'estado' => 'CERRADA', 'estado_pago' => 'PENDIENTE']);
            $sinPagar = $sinPagar->ajustarMontoFinal('34000.75', $f->admin->id, 'Monto acordado para la muestra pendiente T27', '0.00');
            $validada = \Tests\Support\CajaDeclarada::crear($f->operativo->id, $f->medio->id);
            MovimientoOperativo::create(['caja_operativa_id' => $validada->id, 'fecha' => '2026-10-12',
                'subrubro_id' => $f->ingreso->id, 'tipo_caja_id' => $f->medio->id, 'monto' => '1250.50',
                'usuario_id' => $f->operativo->id, 'estado' => 'ACTIVO', 'observaciones' => 'Ingreso de mostrador']);
            $validada->update(['estado' => 'VALIDADA', 'usuario_admin_validacion_id' => $f->admin->id]);
            app(CashflowIntegracionCajaService::class)->reflejarCajaEnCashflow($validada->id, $f->admin->id);
            $abierta = \Tests\Support\CajaDeclarada::crear($f->operativo->id, $f->medio->id);
            $enCaja = app(\App\Services\CajaService::class)->registrarMovimientoEnCaja($abierta->id,
                ['subrubro_id' => $f->ingreso->id, 'tipo_caja_id' => $f->medio->id, 'monto' => '875.75',
                    'observaciones' => 'Ingreso a revisar en mostrador']);

            $this->actingAs($f->admin);
            $base = '/cashflow/movimientos/'.$directo->id;
            $antes = $this->foto();
            foreach ([
                'cashflow' => '/cashflow?periodo=mes&anio=2026&mes=10',
                'crear-directo' => '/cashflow/movimiento', 'editar-directo' => $base.'/editar', 'eliminar-directo' => $base.'/eliminar',
                'liquidacion-sin-pagar' => '/liquidaciones/'.$sinPagar->id.'?editar=1',
                'liquidacion-pagada' => '/liquidaciones/'.$f->liquidacion->id,
                'ficha-alumno' => '/alumnos/'.$f->alumno->id, 'caja-validada' => '/caja/'.$validada->id.'/detalle',
                'caja-abierta' => '/caja/'.$abierta->id.'/detalle',
                'editar-en-caja' => '/caja/'.$abierta->id.'/movimientos/'.$enCaja->id.'/editar',
            ] as $id => $ruta) $this->guardar($id, $ruta);
            $this->assertSame($antes, $this->foto(), 'Los GET no deben corregir datos');
            $protegidos = DB::table('cashflow_movimientos')->whereNotNull('referencia_tipo')->orderBy('id')->get()->toJson();
            $this->put($base, ['clave' => (string) Str::uuid(), 'version' => 0, 'monto' => '75.25',
                'fecha' => '2026-10-12', 'subrubro_id' => $directo->subrubro_id, 'tipo_caja_id' => $f->medio->id,
                'observaciones' => 'Intereses corregidos con centavos'])->assertRedirect();
            $this->guardar('detalle-editado', $base);
            $this->guardar('eliminar-editado', $base.'/eliminar');
            $this->delete($base, ['clave' => (string) Str::uuid(), 'version' => 1])->assertRedirect();
            $this->assertSame('75.25', $directo->fresh()->monto);
            $this->assertSame(2, DB::table('cashflow_correcciones')->count());
            $this->assertSame($protegidos, DB::table('cashflow_movimientos')->whereNotNull('referencia_tipo')
                ->where('referencia_tipo', '!=', CashflowMovimiento::REF_CORRECCION)->orderBy('id')->get()->toJson());
            $antes = $this->foto();
            foreach (['detalle-anulado' => $base, 'cashflow-anulado' => '/cashflow?periodo=mes&anio=2026&mes=10',
                'historial-anulado' => '/caja/historial?desde=2026-10-01&hasta=2026-10-12',
                'inicio-anulado' => '/admin/dashboard', 'reportes-anulado' => '/reportes?mes=2026-10'] as $id => $ruta) $this->guardar($id, $ruta);
            $this->assertSame($antes, $this->foto());
            foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
                $this->actingAs(\App\Models\User::factory()->create(['rol' => $rol, 'activo' => true]));
                foreach (['/cashflow', $base, $base.'/editar', $base.'/eliminar'] as $ruta) $this->get($ruta)->assertForbidden();
            }
            $this->app['auth']->forgetGuards();
            $this->guardar('login', '/login');
            $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t27/final');
            file_put_contents($dir.'/rutas.json', json_encode($this->rutas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            file_put_contents($dir.'/casos.json', json_encode(['directo' => $directo->id, 'pendiente' => $sinPagar->id,
                'pagada' => $f->liquidacion->id, 'caja_validada' => $validada->id, 'alumno' => $f->alumno->id], JSON_PRETTY_PRINT));
        } finally { Carbon::setTestNow(); }
    }

    private function foto(): array
    {
        return collect(['cashflow_movimientos', 'cashflow_correcciones', 'pagos', 'pago_deuda_cuota', 'pago_cargo_alumno',
            'deuda_cuotas', 'movimientos_operativos', 'cajas_operativas', 'liquidaciones', 'liquidacion_ajustes'])
            ->mapWithKeys(fn ($tabla) => [$tabla => DB::table($tabla)->orderBy('id')->get()->toJson()])->all();
    }

    private function guardar(string $id, string $ruta): void
    {
        $html = $this->get($ruta)->assertOk()->getContent();
        $html = preg_replace('~https?://[^/"\s]+(/(?:build|img|favicon|apple-touch|site\.webmanifest)[^"\s]*)~', '$1', $html);
        $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1captura-t27', $html);
        $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1captura-t27', $html);
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t27/final/html');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents("$dir/$id.html", $html);
        file_put_contents("$dir/$id-marco-360.html", '<!doctype html><html lang="es"><meta charset="utf-8"><style>body{margin:0;background:rgb(244,246,248);display:flex;justify-content:center;padding:20px}iframe{width:360px;height:820px;border:0;background:white}</style><iframe title="Wings · T27 a 360" src="'.$id.'.html"></iframe></html>');
        $this->rutas[$ruta] = $id;
    }
}
