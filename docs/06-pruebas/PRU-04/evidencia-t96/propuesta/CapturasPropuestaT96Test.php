<?php

namespace Tests\Feature;

use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Propuesta de T96 para que Carlos la apruebe: no toca las vistas del producto.
 * Toma el HTML real de cada pantalla y reemplaza solo la parte que cambiaría, renderizada
 * con los componentes reales. Se corre por ruta, con WINGS_CAPTURAS=1; no queda en la suite.
 */
class CapturasPropuestaT96Test extends TestCase
{
    use RefreshDatabase;

    private const ETIQUETA = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';

    public function test_guarda_html_de_la_propuesta(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        $this->seed(CatalogosSeeder::class);
        $admin = User::factory()->create(['name' => 'Laura Gómez', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $efectivo->update(['saldo_inicial' => 2310000]);

        $destino = base_path('docs/06-pruebas/PRU-04/evidencia-t96/propuesta/html');
        if (!is_dir($destino)) {
            mkdir($destino, 0775, true);
        }
        $build = 'file:///'.str_replace('\\', '/', public_path('build'));
        $guardar = function (string $nombre, string $html) use ($destino, $build) {
            $html = preg_replace('#(src|href)="(https?://[^"/]+)?/build/#', '$1="'.$build.'/', $html);
            file_put_contents($destino.'/'.$nombre.'.html', $html);
        };

        // 1. Caja antes de configurar.
        $guardar('caja-sin-configurar', $this->conFila(
            $this->actingAs($admin)->get(route('web.caja.index'))->assertOk()->getContent(),
            'Falta cargar el saldo de Efectivo', 'Configurar'
        ));

        // 2 y 3. La pantalla de configuración, con el saldo editable y con Efectivo ya usado.
        $pantalla = $this->actingAs($admin)->get(route('web.caja.configuracion'))->assertOk()->getContent();
        $guardar('configuracion-saldo-editable', $this->conFormulario($pantalla, true, ''));
        $guardar('configuracion-con-movimientos', $this->conFormulario($pantalla, false, 2310000));

        // 4. Caja ya configurada: sin Configurar.
        app(CajaService::class)->configurarMostrador($efectivo->id, $admin->id);
        $guardar('caja-configurada', $this->conFila(
            $this->actingAs($admin)->get(route('web.caja.index'))->assertOk()->getContent(),
            'Cajón del mostrador: abrile la caja a un operativo', 'Abrir'
        ));

        // 5. Tipos de Caja: el nombre de Efectivo no se edita.
        $edicion = $this->actingAs($admin)->get(route('web.tipos-caja.edit', $efectivo->id))->assertOk()->getContent();
        $guardar('tipo-caja-efectivo', $this->conNombreBloqueado($edicion));
    }

    private function conFila(string $html, string $texto, string $boton): string
    {
        $fila = Blade::render(
            '<div class="stats-bar mb-4"><span class="stats-info">{{ $texto }}</span><x-ds.button variant="primary" href="#">{{ $boton }}</x-ds.button></div>',
            compact('texto', 'boton')
        );
        $nuevo = preg_replace('#<div class="stats-bar mb-4">\s*<span class="stats-info">Cajón del mostrador.*?</div>#s', $fila, $html, 1, $cambios);
        $this->assertSame(1, $cambios);

        return $nuevo;
    }

    private function conFormulario(string $html, bool $editable, $saldo): string
    {
        $etiqueta = self::ETIQUETA;
        $parte = Blade::render(<<<'BLADE'
<div class="filtros-card">
    @if($editable)
    <p class="text-sm text-wings-muted mb-4">Indicá cuánto efectivo tiene el club hoy. Después del primer movimiento de la cuenta, este saldo no se puede cambiar.</p>
    @else
    <p class="text-sm text-wings-muted mb-4">Efectivo ya tiene movimientos: su saldo inicial no se puede cambiar. Se ajusta con un movimiento.</p>
    @endif
    <form method="POST" action="#">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="cuenta" class="{{ $etiqueta }}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18v12H3V7zm3-3h12M12 11v4"/></svg>Cuenta</label>
                <input type="text" id="cuenta" value="Efectivo" readonly class="w-full px-4 py-2.5 text-sm wings-input">
            </div>
            <div>
                <label for="saldo_inicial" class="{{ $etiqueta }}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v2m0 12v2m8-8a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>Saldo inicial @if($editable)<span class="form-required">*</span>@endif</label>
                @if($editable)
                <x-ds.money-input id="saldo_inicial" name="saldo_inicial" :value="$saldo" required />
                @else
                <x-ds.money-input id="saldo_inicial" :value="$saldo" readonly />
                @endif
            </div>
        </div>
        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="#">Volver</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
BLADE, compact('editable', 'saldo', 'etiqueta'));

        $nuevo = preg_replace('#<div class="filtros-card">.*?</form>\s*</div>#s', $parte, $html, 1, $cambios);
        $this->assertSame(1, $cambios);

        return str_replace('Efectivo del mostrador', 'Saldo inicial de Efectivo', $nuevo);
    }

    private function conNombreBloqueado(string $html): string
    {
        $nuevo = preg_replace(
            '#<input type="text" id="nombre" name="nombre"(.*?)>\s*#s',
            '<input type="text" id="nombre" readonly$1><p class="text-xs mt-1 text-wings-muted">El nombre de esta cuenta no se puede cambiar.</p>',
            $html, 1, $cambios
        );
        $this->assertSame(1, $cambios);

        return str_replace(' required autofocus', '', $nuevo);
    }
}
