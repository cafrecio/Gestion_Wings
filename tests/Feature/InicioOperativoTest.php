<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InicioOperativoTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'America/Argentina/Buenos_Aires';

    private User $admin;
    private User $sandra;
    private User $marcos;
    private TipoCaja $tipoEfectivo;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Carlos Admin',
            'email' => 'admin@wings.com',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);
        $this->sandra = User::factory()->create([
            'name' => 'Sandra Vidal',
            'email' => 'sandra@wings.com',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);
        $this->marcos = User::factory()->create([
            'name' => 'Marcos Peña',
            'email' => 'marcos@wings.com',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->tipoEfectivo = TipoCaja::first();
        $this->actingAs($this->admin)->post('/caja/configuracion', ['tipo_caja_id' => $this->tipoEfectivo->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function abrirCaja(User $operativo, string $fechaHora, string $monto = '10.000,00'): CajaOperativa
    {
        $dt = Carbon::parse($fechaHora, self::TZ);
        Carbon::setTestNow($dt);

        $r = $this->actingAs($operativo)->post('/caja/apertura', [
            'efectivo_inicial' => $monto,
            'confirmacion' => '1',
            'caja_origen_id' => null,
            'motivo_apertura' => 'Test apertura',
        ]);
        $this->assertSame(302, $r->getStatusCode());

        return CajaOperativa::where('usuario_operativo_id', $operativo->id)
            ->where('estado', 'ABIERTA')
            ->latest('id')
            ->firstOrFail();
    }

    private function cobrarManual(User $operativo, float $monto): void
    {
        $sub = Subrubro::where('permitido_para', 'OPERATIVO')
            ->where('es_reservado_sistema', false)
            ->firstOrFail();

        $caja = CajaOperativa::where('usuario_operativo_id', $operativo->id)
            ->where('estado', 'ABIERTA')
            ->firstOrFail();

        $this->actingAs($operativo)->post('/caja/movimiento', [
            'tipo' => 'INGRESO',
            'subrubro_id' => $sub->id,
            'monto' => number_format($monto, 2, ',', '.'),
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'concepto' => 'Cobro prueba',
        ]);
    }

    /**
     * Extrae todos los enlaces dentro de #tarjeta-cajon y verifica que:
     * 1. Haya al menos un botón de acción.
     * 2. Cada botón responda con status 200 directo (sin rebotes, sin 403, sin 500).
     */
    private function verificarBotonesTarjeta(string $html, User $usuario): void
    {
        $libxmlState = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_use_internal_errors($libxmlState);

        $xpath = new DOMXPath($dom);
        $tarjeta = $xpath->query('//*[@id="tarjeta-cajon"]')->item(0);
        $this->assertNotNull($tarjeta, 'Debe existir el elemento #tarjeta-cajon en la pantalla');

        $links = $xpath->query('.//a[@href]', $tarjeta);
        $this->assertGreaterThan(0, $links->length, 'La tarjeta debe tener al menos una acción disponible');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $etiqueta = trim(preg_replace('/\s+/', ' ', $link->textContent));
            $ruta = parse_url($href, PHP_URL_PATH);

            $destino = $this->actingAs($usuario)->get($ruta);

            $this->assertSame(
                200,
                $destino->getStatusCode(),
                "El botón '{$etiqueta}' con url '{$ruta}' falló con código {$destino->getStatusCode()}"
            );
            $this->assertFalse(
                $destino->isRedirect(),
                "El botón '{$etiqueta}' con url '{$ruta}' no debe rebotar, pero rebotó a " . $destino->headers->get('Location')
            );
        }
    }

    public function test_situacion_1_recien_llega_nadie_abrio(): void
    {
        $r = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $r->assertStatus(200);

        $html = $r->getContent();
        $this->assertStringContainsString('Cajón listo para iniciar', $html);
        $this->assertStringContainsString('No hay turno abierto hoy', $html);
        $this->assertStringContainsString('Abrir', $html);

        $this->verificarBotonesTarjeta($html, $this->sandra);
    }

    public function test_situacion_2_turno_propio_abierto_hoy(): void
    {
        $this->abrirCaja($this->sandra, '2026-10-08 10:00:00');
        $this->cobrarManual($this->sandra, 25000);

        $r = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $r->assertStatus(200);

        $html = $r->getContent();
        $this->assertStringContainsString('Mostrador activo', $html);
        $this->assertStringContainsString('Caja abierta', $html);
        $this->assertStringContainsString('Cobrar', $html);
        $this->assertStringContainsString('Registrar', $html);
        $this->assertStringContainsString('Resumen', $html);

        $this->verificarBotonesTarjeta($html, $this->sandra);
    }

    public function test_situacion_3_companero_abrio_hoy(): void
    {
        $this->abrirCaja($this->marcos, '2026-10-08 10:00:00');
        $this->cobrarManual($this->marcos, 18000);

        Carbon::setTestNow('2026-10-08 11:30:00');
        $r = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $r->assertStatus(200);

        $html = $r->getContent();
        $this->assertStringContainsString('TURNO EN CURSO', strtoupper($html));
        $this->assertStringContainsString('Caja abierta de Marcos Peña', $html);
        $this->assertStringContainsString('Para atender tu turno, Marcos debe cerrar su caja', $html);
        $this->assertStringContainsString('Caja', $html);

        // No debe haber botones muertos que reboten o den 403
        $this->assertStringNotContainsString('Cajón compartido en curso', $html);

        $this->verificarBotonesTarjeta($html, $this->sandra);
    }

    public function test_situacion_4_sandra_cerro_y_espera_validacion(): void
    {
        $caja = $this->abrirCaja($this->sandra, '2026-10-08 10:00:00');
        $rCierre = $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '10.000,00',
            'cambio_retenido' => '10.000,00',
        ]);
        $this->assertSame(302, $rCierre->getStatusCode());

        // 4. Sandra entra después de cerrar
        $r4 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $r4->assertStatus(200);
        $html4 = $r4->getContent();
        $this->assertStringContainsString('Turno cerrado', $html4);
        $this->assertStringContainsString('Última caja:', $html4);
        $this->assertStringContainsString('Abrir', $html4);
        $this->verificarBotonesTarjeta($html4, $this->sandra);

        // 4b. Marcos entra: no hay caja abierta, cajón listo para iniciar
        $r4b = $this->actingAs($this->marcos)->get(route('web.operativo.dashboard'));
        $r4b->assertStatus(200);
        $html4b = $r4b->getContent();
        $this->assertStringContainsString('Cajón listo para iniciar', $html4b);
        $this->assertStringContainsString('Abrir', $html4b);
        $this->verificarBotonesTarjeta($html4b, $this->marcos);
    }

    public function test_situacion_5_caja_rechazada(): void
    {
        $caja = $this->abrirCaja($this->sandra, '2026-10-08 10:00:00');
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '10.000,00',
            'cambio_retenido' => '10.000,00',
        ]);
        $this->actingAs($this->admin)->post(route('web.cajas.rechazar', $caja->id), [
            'motivo' => 'Revisar arqueo',
            'motivo_rechazo' => 'Revisar arqueo',
        ]);

        $r = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $r->assertStatus(200);

        $html = $r->getContent();
        $this->assertStringContainsString('Tenés 1 caja rechazada pendiente de corrección', $html);
        $this->assertStringContainsString('Abrir', $html);
        $this->verificarBotonesTarjeta($html, $this->sandra);
    }

    public function test_situacion_6_companero_dejo_abierta_ayer(): void
    {
        $this->abrirCaja($this->marcos, '2026-10-07 18:30:00');

        Carbon::setTestNow('2026-10-08 10:00:00');
        $r = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $r->assertStatus(200);

        $html = $r->getContent();
        $this->assertStringContainsString('TURNO EN CURSO', strtoupper($html));
        $this->assertStringContainsString('Caja abierta de Marcos Peña', $html);
        $this->assertStringContainsString('ayer a las 18:30', $html);
        $this->assertStringContainsString('Para atender tu turno, Marcos debe cerrar su caja', $html);
        $this->assertStringContainsString('Caja', $html);

        // No debe decir cajón listo para iniciar ni ofrecer abrir
        $this->assertStringNotContainsString('Cajón listo para iniciar', $html);

        $this->verificarBotonesTarjeta($html, $this->sandra);
    }

    public function test_situacion_7_propia_abierta_ayer(): void
    {
        $this->abrirCaja($this->sandra, '2026-10-07 18:30:00');

        Carbon::setTestNow('2026-10-08 10:00:00');
        $r = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $r->assertStatus(200);

        $html = $r->getContent();
        $this->assertStringContainsString('Turno pendiente de cierre', $html);
        $this->assertStringContainsString('Tu caja sigue abierta', $html);
        $this->assertStringContainsString('ayer a las 18:30', $html);
        $this->assertStringContainsString('Cerrá este turno para poder comenzar el día', $html);
        $this->assertStringContainsString('Cerrar', $html);

        // No debe decir "Cajón listo para iniciar"
        $this->assertStringNotContainsString('Cajón listo para iniciar', $html);

        $this->verificarBotonesTarjeta($html, $this->sandra);
    }

    public function test_situacion_8_de_noche(): void
    {
        $this->abrirCaja($this->sandra, '2026-10-08 22:30:00');
        $this->cobrarManual($this->sandra, 9000);

        $r = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $r->assertStatus(200);

        $html = $r->getContent();
        $this->assertStringContainsString('Mostrador activo', $html);
        $this->assertStringContainsString('Caja abierta', $html);
        $this->assertStringContainsString('22:30', $html);
        $this->assertStringContainsString('Cobrar', $html);
        $this->assertStringContainsString('Registrar', $html);
        $this->assertStringContainsString('Resumen', $html);

        $this->verificarBotonesTarjeta($html, $this->sandra);
    }
}
