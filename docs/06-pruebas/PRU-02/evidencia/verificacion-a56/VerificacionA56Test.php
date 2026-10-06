<?php

namespace Tests\Verificacion;

require_once dirname(__DIR__, 5).'/tests/Feature/AdminCobraSinCajaTest.php';

use App\Models\CashflowMovimiento;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;

/** Ensayo independiente, fuera del contador de la suite permanente. Base descartable. */
class VerificacionA56Test extends \Tests\Feature\AdminCobraSinCajaTest
{
    public function test_verificacion_independiente_a56(): void
    {
        $this->test_el_dueno_anula_su_cobro_y_la_deuda_vuelve();
        $admin = User::where('rol', User::ROL_ADMIN)->firstOrFail();
        $medio = TipoCaja::firstOrFail();
        TipoCaja::query()->update(['saldo_inicial' => 0]);
        $medio->update(['saldo_inicial' => 10000]);
        $rubro = Rubro::create(['nombre' => 'Gasto de prueba A56', 'tipo' => 'EGRESO']);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id, 'nombre' => 'Gasto normal A56',
            'permitido_para' => User::ROL_ADMIN, 'afecta_caja' => true,
            'es_reservado_sistema' => false,
        ]);
        $this->actingAs($admin)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $medio->id, 'subrubro_id' => $subrubro->id,
            'monto' => 2500, 'fecha' => '2026-10-05', 'observaciones' => 'Gasto normal A56',
        ])->assertSessionHas('success');
        $gasto = CashflowMovimiento::where('subrubro_id', $subrubro->id)->sole();
        $this->assertSame(-2500.0, (float) $gasto->monto);
        $respuesta = $this->actingAs($admin)->get(route('web.cashflow.index'))->assertOk();
        $respuesta->assertViewHas('totalIngresos', fn ($v) => (float) $v === 0.0);
        $respuesta->assertViewHas('totalEgresos', fn ($v) => (float) $v === 2500.0);
        $respuesta->assertViewHas('saldoInicial', fn ($v) => (float) $v === 10000.0);
        $this->assertSame(7500.0, 10000.0 + (float) CashflowMovimiento::sum('monto'));
        $html = $respuesta->getContent();
        $this->assertStringContainsString('$7.500', $html);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xp = new \DOMXPath($dom);
        $filas = $xp->query('//tbody/tr');
        $this->assertCount(3, $filas);
        $datos = [];
        foreach ($filas as $fila) {
            $celdas = $xp->query('td', $fila);
            $monto = trim($celdas->item(5)->textContent);
            $tipo = trim($celdas->item(1)->textContent);
            $color = $celdas->item(5)->getAttribute('style');
            $datos[$monto] = ['tipo' => $tipo, 'estilo' => $color];
        }
        foreach (['−$48.000', '−$2.500'] as $salida) {
            $this->assertSame('E', $datos[$salida]['tipo']);
            $this->assertStringContainsString('var(--color-danger)', $datos[$salida]['estilo']);
        }
        $this->assertSame('I', $datos['$48.000']['tipo']);
        $this->assertStringContainsString('var(--color-success)', $datos['$48.000']['estilo']);
        file_put_contents(__DIR__.'/resultado-filas.json', json_encode([
            'ingresos_netos' => 0, 'egresos' => 2500, 'saldo_inicial' => 10000,
            'balance' => 7500, 'filas' => $datos,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->guardarHtml('cashflow.html', $html);
        $this->app['auth']->forgetGuards();
        $this->guardarHtml('login.html', $this->get(route('login'))->assertOk()->getContent());
        if (getenv('WINGS_VERIFICACION_HTTP') === '1') {
            $this->assertSame('wings_testing_codex', \Illuminate\Support\Facades\DB::connection()->getDatabaseName());
            $admin->update([
                'name' => 'Verificación Codex', 'email' => 'a56.verificacion@example.test',
                'password' => \Illuminate\Support\Facades\Hash::make('Verificacion-A56-2026'),
            ]);
            \Illuminate\Support\Facades\DB::commit();
        }
    }

    private function guardarHtml(string $nombre, string $html): void
    {
        $raiz = 'file:///'.str_replace('\\', '/', public_path()).'/';
        $html = preg_replace('#https?://[^/"\s]+/(build/|favicon|apple-touch|site\.webmanifest)#', $raiz.'$1', $html);
        file_put_contents(__DIR__.'/'.$nombre, $html);
    }
}
