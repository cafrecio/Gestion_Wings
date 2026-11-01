<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, TipoCaja, User};
use App\Services\TransferenciaCashflowService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Blade, DB, Notification};
use Illuminate\Support\Str;
use Tests\TestCase;

/** Propuesta aislada: HTML de Wings y componentes reales; no implementa la anulación. */
class CapturasPropuestaT97Test extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') {
            throw new \RuntimeException('La propuesta T97 solo permite wings_testing_codex.');
        }
    }

    public function test_renderiza_propuesta_sin_cambiar_vistas_del_producto(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') $this->markTestSkipped('Renderizado explícito de propuesta.');
        Carbon::setTestNow('2026-10-20 12:00:00');
        Notification::fake();
        $this->seed(CatalogosSeeder::class);
        $admin = User::factory()->create(['name' => 'Laura Gómez', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($admin);
        $salida = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $destino = TipoCaja::where('nombre', 'Banco Nación')->firstOrFail();
        $salida->update(['saldo_inicial' => '600000.00']);
        $destino->update(['saldo_inicial' => '100000.00']);
        DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2026-09-01']);
        // El observador fecha el capital con el reloj real. Para este escenario de octubre,
        // fijar explícitamente la vigencia de ambos capitales en la base descartable.
        DB::table('reporte_eventos')->where('tipo', 'CAJA_INICIAL')
            ->whereIn('origen_id', [$salida->id, $destino->id])->update(['fecha' => '2026-09-01']);

        $id = app(TransferenciaCashflowService::class)->registrar($admin->id, [
            'clave' => (string) Str::uuid(), 'salida_id' => $salida->id, 'destino_id' => $destino->id,
            'monto' => '300000.00', 'fecha' => '2026-10-20', 'observaciones' => 'Depósito del efectivo en el banco',
        ]);
        $patas = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)
            ->where('referencia_id', $id)->orderBy('id')->get();
        $this->assertCount(2, $patas);
        $motivo = 'Se eligió una cuenta de destino equivocada';
        $this->guardar('01-cashflow-anular', $this->listado($id, false, $motivo));

        // Se toma la pantalla existente de eliminación, conservando su layout y reemplazando
        // únicamente el formulario por la propuesta, renderizada con los componentes de Wings.
        $html = view('cashflow.eliminar', ['movimiento' => $patas->first()->load('subrubro', 'tipoCaja'),
            'clave' => (string) Str::uuid()])->render();
        $html = str_replace('Eliminar movimiento', 'Anular transferencia', $html);
        $form = Blade::render(file_get_contents(__DIR__.'/confirmacion.blade.php'), compact('id', 'motivo'));
        $html = preg_replace_callback('~<form method="POST" action="[^"]*/cashflow/movimientos/\d+">.*?</form>~s', fn () => $form, $html, 1, $n);
        $this->assertSame(1, $n);
        $this->assertStringContainsString('$300,000.00', $html);
        $this->guardar('02-confirmacion', $html);

        // SOLO fixture visual del resultado propuesto. No llama ni prueba un servicio de
        // anulación inexistente: no constituye evidencia de implementación financiera.
        foreach ($patas as $pata) {
            CashflowMovimiento::create([
                'fecha' => $pata->fecha->toDateString(), 'subrubro_id' => $pata->subrubro_id,
                'tipo_caja_id' => $pata->tipo_caja_id, 'monto' => str_starts_with($pata->monto, '-')
                    ? substr($pata->monto, 1) : '-'.$pata->monto,
                'observaciones' => 'Anulación de transferencia #'.$id,
                'usuario_admin_id' => $admin->id, 'referencia_tipo' => 'ANULACION_TRANSFERENCIA',
                'referencia_id' => $id, 'contra_origen_id' => $pata->id,
                'reporte_tipo' => $pata->reporte_tipo, 'reporte_clasificacion' => $pata->reporte_clasificacion,
                'reporte_deporte_id' => $pata->reporte_deporte_id,
            ]);
            $pata->forceFill(['anulado_at' => now()])->save();
        }
        $this->guardar('03-cashflow-anulada', $this->listado($id, true, $motivo));
        $this->assertSame('0.00', (string) CashflowMovimiento::sum('monto'));
        auth()->logout();
        $this->guardar('00-login-control', $this->get(route('login'))->assertOk()->getContent());
        file_put_contents(__DIR__.'/origen.json', json_encode([
            'base' => 'wings_testing_codex', 'fecha_fixture' => '2026-10-20',
            'transferencia' => $id, 'importe' => '300000.00', 'motivo' => $motivo,
            'nota' => 'Propuesta visual: ruta real de Cashflow; confirmación sobre plantilla de eliminar; estado anulado armado solo como fixture.',
            'fuentes' => collect(['resources/views/cashflow/index.blade.php', 'resources/views/cashflow/eliminar.blade.php',
                'resources/views/cashflow/_acciones.blade.php', 'resources/css/app.css'])
                ->mapWithKeys(fn ($f) => [$f => hash_file('sha256', base_path($f))])->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        Carbon::setTestNow();
    }

    private function listado(int $id, bool $anulada, string $motivo): string
    {
        $respuesta = $this->get(route('web.cashflow.index', ['periodo' => 'mes', 'fecha' => '2026-10-20']))->assertOk();
        $respuesta->assertViewIs('cashflow.index');
        $datos = $respuesta->baseResponse->original->getData();
        foreach ($datos['movimientos'] as $mov) {
            $esContra = $mov->contra_origen_id !== null;
            $mov->origenCorreccion = $esContra ? 'Anulación de transferencia #'.$id
                : 'Transferencia #'.$id.($anulada ? ' · Anulada' : ' · Efectivo → Banco Nación');
            $mov->motivoPropuesta = $anulada ? $motivo : null;
            $mov->accionesCorreccion = [['nombre' => 'Ver', 'url' => '#transferencia-'.$id]];
            if (!$anulada) $mov->accionesCorreccion = [...$mov->accionesCorreccion, ['nombre' => 'Anular', 'url' => '#anular-transferencia']];
        }
        $vista = file_get_contents(resource_path('views/cashflow/index.blade.php'));
        $vista = str_replace('{{ $mov->origenCorreccion }}',
            '<span style="display:block;white-space:normal;">{{ $mov->origenCorreccion }}</span>@if($mov->motivoPropuesta)<span style="display:block;white-space:normal;">Motivo: {{ $mov->motivoPropuesta }}</span>@endif', $vista, $n);
        $this->assertSame(2, $n); // Escritorio y tarjetas móviles.
        $html = Blade::render($vista, $datos);
        // Ajuste pedido por Carlos sobre la propuesta: evita que los botones pisen el conteo.
        // Solo estas dos capturas de Cashflow; no modifica el CSS compartido del producto.
        return str_replace('</head>', '<style>'.file_get_contents(__DIR__.'/barra-movil.css').'</style></head>', $html);
    }

    private function guardar(string $nombre, string $html): void
    {
        $destino = __DIR__.'/html';
        if (!is_dir($destino)) mkdir($destino, 0775, true);
        $publico = 'file:///'.str_replace('\\', '/', public_path());
        $html = preg_replace('~(src|href)="(?:https?://[^"/]+)?/(build|images|img|fonts)/~', '$1="'.$publico.'/$2/', $html);
        file_put_contents($destino.'/'.$nombre.'.html', $html);
    }
}
