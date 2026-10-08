<?php

namespace Tests\Evidencia;

use App\Models\CajaOperativa;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verificacion de A12 y A24 (inicio del operativo, implementado por Gemini en a6ce0f5).
 * Fuera de la suite permanente. No juzga el aspecto, que aprobo Carlos: comprueba que en
 * cada situacion del dia la pantalla diga lo que pasa y que cada boton lleve a algo que
 * el sistema deje hacer.
 *
 *   DB_DATABASE=wings_testing_claude php vendor/bin/phpunit docs/06-pruebas/PRU-02/evidencia/verificacion-a12-a24/VerificacionA12A24Test.php
 *
 * Escribe resultado.json en esta carpeta. No afirma nada: registra lo que respondio.
 */
class VerificacionA12A24Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $sandra;
    private User $marcos;
    private static array $registro = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Carlos Admin', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->sandra = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->marcos = User::factory()->create(['name' => 'Marcos Peña', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->actingAs($this->admin)->post('/caja/configuracion', ['tipo_caja_id' => TipoCaja::first()->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        file_put_contents(
            __DIR__ . '/resultado.json',
            json_encode(self::$registro, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        parent::tearDown();
    }

    private function abrir(User $quien, string $monto = '10.000,00'): array
    {
        $r = $this->actingAs($quien)->post('/caja/apertura', [
            'efectivo_inicial' => $monto,
            'confirmacion' => '1',
            'caja_origen_id' => null,
            'motivo_apertura' => 'Verificación A12/A24',
        ]);

        return [
            'status' => $r->getStatusCode(),
            'va_a' => $r->headers->get('Location'),
            'errores' => session('errors')?->all(),
            'aviso' => session('error'),
        ];
    }

    private function cobrar(User $quien, float $monto): void
    {
        // Un ingreso que el mostrador puede cargar a mano (los de cuota son del sistema).
        $sub = Subrubro::where('permitido_para', 'OPERATIVO')->where('es_reservado_sistema', false)
            ->where('afecta_caja', true)
            ->whereHas('rubro', fn ($q) => $q->where('tipo', 'INGRESO'))->firstOrFail();
        app(CajaService::class)->registrarMovimientoOperativo([
            'usuario_operativo_id' => $quien->id,
            'tipo_caja_id' => TipoCaja::first()->id,
            'subrubro_id' => $sub->id,
            'monto' => $monto,
        ]);
    }

    /** Abre el inicio como $quien y sigue cada boton que la pantalla le ofrece arriba. */
    private function mirar(string $situacion, User $quien): void
    {
        $r = $this->actingAs($quien)->get(route('web.operativo.dashboard'));
        $html = $r->getContent();
        $cuerpo = substr($html, (int) strpos($html, 'Mostrador Operativo'));
        $hasta = strpos($cuerpo, 'Clases de hoy');
        $zona = $hasta ? substr($cuerpo, 0, $hasta) : $cuerpo;
        $texto = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($zona))));

        preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/su', $zona, $m, PREG_SET_ORDER);
        $botones = [];
        foreach ($m as $coincidencia) {
            $etiqueta = trim(preg_replace('/\s+/u', ' ', strip_tags($coincidencia[2])));
            $ruta = parse_url($coincidencia[1], PHP_URL_PATH);
            $destino = $this->actingAs($quien)->get($ruta);
            $botones[] = [
                'boton' => $etiqueta,
                'lleva_a' => $ruta,
                'responde' => $destino->getStatusCode(),
                'rebota_a' => $destino->isRedirect() ? parse_url($destino->headers->get('Location'), PHP_URL_PATH) : null,
                'aviso' => session('error') ?? session('warning'),
            ];
        }

        $datos = $r->getStatusCode() === 200 ? $r->original->getData() : [];
        self::$registro[$situacion] = [
            'usuario' => $quien->name,
            'pantalla_responde' => $r->getStatusCode(),
            'texto_arriba' => mb_substr($texto, 0, 520),
            'botones' => $botones,
            'cobrado_hoy' => $datos['totalCobradoHoy'] ?? null,
            'cobros' => $datos['numCobrosHoy'] ?? null,
            'cajas_hoy' => isset($datos['cajas']) ? $datos['cajas']->count() : null,
            'caja_propia' => ($datos['cajaPropia'] ?? null)?->id,
            'caja_del_club' => ($datos['cajaClub'] ?? null)?->id,
            'bloqueo_activo' => $datos['estado']['bloqueo']['activo'] ?? null,
            'cajas_abiertas_en_la_base' => CajaOperativa::where('estado', 'ABIERTA')->count(),
        ];
        $this->assertTrue(true);
    }

    public function test_1_recien_llega(): void
    {
        $this->mirar('1 recién llega, nadie abrió', $this->sandra);
    }

    public function test_2_turno_propio(): void
    {
        self::$registro['2 apertura de Sandra'] = $this->abrir($this->sandra);
        $this->cobrar($this->sandra, 25000);
        $this->mirar('2 turno propio abierto, con un cobro de $25.000', $this->sandra);
    }

    public function test_3_turno_de_un_companero(): void
    {
        self::$registro['3 apertura de Marcos'] = $this->abrir($this->marcos);
        $this->cobrar($this->marcos, 18000);
        $this->mirar('3 el turno lo abrió Marcos; entra Sandra', $this->sandra);
        self::$registro['3 Sandra intenta abrir igual'] = $this->abrir($this->sandra);
        self::$registro['3 cajas abiertas después del intento'] = CajaOperativa::where('estado', 'ABIERTA')
            ->pluck('usuario_operativo_id')->all();
    }

    public function test_4_cerro_y_espera_validacion(): void
    {
        $this->abrir($this->sandra);
        $caja = CajaOperativa::where('usuario_operativo_id', $this->sandra->id)->firstOrFail();
        $r = $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '10.000,00',
            'cambio_retenido' => '10.000,00',
        ]);
        self::$registro['4 cierre de Sandra'] = ['status' => $r->getStatusCode(), 'estado' => $caja->fresh()->estado];
        $this->mirar('4 Sandra cerró su turno, espera validación', $this->sandra);
        $this->mirar('4b la caja de Sandra está cerrada; entra Marcos', $this->marcos);
    }

    public function test_5_caja_rechazada(): void
    {
        $this->abrir($this->sandra);
        $caja = CajaOperativa::where('usuario_operativo_id', $this->sandra->id)->firstOrFail();
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '10.000,00',
            'cambio_retenido' => '10.000,00',
        ]);
        $r = $this->actingAs($this->admin)->post(route('web.cajas.rechazar', $caja->id), [
            'motivo' => 'Revisar el egreso',
            'motivo_rechazo' => 'Revisar el egreso',
        ]);
        self::$registro['5 rechazo del admin'] = [
            'status' => $r->getStatusCode(),
            'estado' => $caja->fresh()->estado,
            'errores' => session('errors')?->all(),
        ];
        $this->mirar('5 Sandra tiene una caja rechazada', $this->sandra);
    }

    public function test_6_turno_abierto_ayer_por_un_companero(): void
    {
        Carbon::setTestNow('2026-10-07 10:00:00');
        self::$registro['6 apertura de Marcos AYER'] = $this->abrir($this->marcos);
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->mirar('6 Marcos dejó el turno abierto ayer; hoy entra Sandra', $this->sandra);
        self::$registro['6 Sandra intenta abrir'] = $this->abrir($this->sandra);
    }

    public function test_7_turno_propio_abierto_ayer(): void
    {
        Carbon::setTestNow('2026-10-07 10:00:00');
        $this->abrir($this->sandra);
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->mirar('7 Sandra dejó su turno abierto ayer; entra hoy', $this->sandra);
    }

    public function test_8_de_noche(): void
    {
        Carbon::setTestNow('2026-10-08 22:30:00');
        self::$registro['8 apertura de Sandra a las 22:30'] = $this->abrir($this->sandra);
        $this->cobrar($this->sandra, 9000);
        $this->mirar('8 turno propio abierto a las 22:30, con un cobro de $9.000', $this->sandra);
    }
}
