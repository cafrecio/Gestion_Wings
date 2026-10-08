<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, Rubro, Subrubro, TipoCaja, User};
use App\Services\CashflowSaldoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Ensayo documental de Claude CyE para verificar A10 (08/10/2026). No es parte de la suite.
 *
 *   DB_DATABASE=wings_testing_claude php artisan test \
 *     docs/06-pruebas/PRU-02/evidencia/a10-verificacion-claude/VerificacionA10ClaudeTest.php
 *
 * Lo esperado se calcula acá con DateTimeImmutable sobre una lista de movimientos propia,
 * sin usar el controlador ni Carbon::startOfWeek: si el controlador y este ensayo coinciden
 * no es porque compartan la cuenta. Importes y fechas distintos de los del autor.
 */
class VerificacionA10ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private const MESES = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
        'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    private const FECHAS = [
        '2023-12-25', '2023-12-31', '2024-01-01', '2024-01-07', '2024-01-08', '2024-01-31',
        '2024-02-01', '2024-02-25', '2024-02-26', '2024-02-28', '2024-02-29', '2024-03-01',
        '2024-03-03', '2024-03-04', '2024-03-31', '2024-04-01', '2024-04-30', '2024-05-01',
        '2024-11-30', '2024-12-01', '2024-12-29', '2024-12-30', '2024-12-31', '2025-01-01',
        '2025-01-05', '2025-01-06', '2025-01-31', '2025-02-01', '2025-02-28', '2025-03-01',
        '2025-03-30', '2025-03-31',
    ];

    private static array $informe = [];

    private User $admin;
    private TipoCaja $cajaA;
    private TipoCaja $cajaB;
    private Subrubro $ingreso;
    private Subrubro $egreso;
    /** @var array<int, array{id:int, fecha:string, monto:float, tipo:string, caja:int}> */
    private array $filas = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('wings_testing_claude', DB::connection()->getDatabaseName(), 'Este ensayo solo corre en la base de Claude.');
        // Un día 31: sin fecha explícita, febrero tiene que quedar limitado a 28 o 29.
        $this->travelTo(Carbon::parse('2025-03-31 10:00:00'));
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->cajaA = TipoCaja::create(['nombre' => 'Caja Alfa', 'abreviatura' => 'ALF', 'activo' => true, 'saldo_inicial' => 777000]);
        $this->cajaB = TipoCaja::create(['nombre' => 'Caja Beta', 'abreviatura' => 'BET', 'activo' => true, 'saldo_inicial' => 0]);
        foreach (['INGRESO' => 'ingreso', 'EGRESO' => 'egreso'] as $tipo => $propiedad) {
            $rubro = Rubro::create(['nombre' => 'Rubro '.$tipo, 'tipo' => $tipo]);
            $this->{$propiedad} = Subrubro::create([
                'rubro_id' => $rubro->id, 'nombre' => 'Sub '.$tipo,
                'permitido_para' => User::ROL_ADMIN, 'afecta_caja' => false,
                'es_reservado_sistema' => false, 'activo' => true,
            ]);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        file_put_contents(__DIR__.'/resultado-http.json', json_encode([
            'ensayo' => 'VerificacionA10ClaudeTest', 'base' => 'wings_testing_claude',
            'reloj_del_ensayo' => '2025-03-31 10:00', 'revision' => 'segunda, 08/10/2026', 'resultados' => self::$informe,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    }

    private function mov(string $fecha, float $monto, string $tipo, ?TipoCaja $caja = null, string $obs = 'Ensayo Claude'): CashflowMovimiento
    {
        $caja ??= $this->cajaA;
        $m = CashflowMovimiento::create([
            'fecha' => $fecha, 'monto' => $monto, 'tipo_caja_id' => $caja->id,
            'subrubro_id' => ($tipo === 'EGRESO' ? $this->egreso : $this->ingreso)->id,
            'usuario_admin_id' => $this->admin->id, 'observaciones' => $obs,
        ]);
        $this->filas[] = ['id' => $m->id, 'fecha' => $fecha, 'monto' => $monto, 'tipo' => $tipo, 'caja' => $caja->id];

        return $m;
    }

    /** Cobro y pago cada fecha; cada tres, además una devolución de cobro y una reversión parcial de egreso. */
    private function sembrar(): void
    {
        foreach (self::FECHAS as $i => $fecha) {
            $this->mov($fecha, 1300 + 17 * $i, 'INGRESO', $this->cajaA);
            $this->mov($fecha, -(910 + 13 * $i), 'EGRESO', $this->cajaB);
            $this->mov($fecha, 205 + 3 * $i, 'INGRESO', $this->cajaB);
            if ($i % 3 === 0) {
                $this->mov($fecha, -(111 + $i), 'INGRESO', $this->cajaA, 'Anulación de cobro');
                $this->mov($fecha, 59 + $i, 'EGRESO', $this->cajaB, 'Reversión parcial de egreso');
            }
        }
    }

    /** Intervalo esperado, calculado sin Carbon. */
    private function intervalo(string $modo, string $fecha): array
    {
        $f = new \DateTimeImmutable($fecha);

        return match ($modo) {
            'dia' => [$fecha, $fecha],
            'semana' => [
                $f->modify('-'.((int) $f->format('N') - 1).' days')->format('Y-m-d'),
                $f->modify('-'.((int) $f->format('N') - 1).' days')->modify('+6 days')->format('Y-m-d'),
            ],
            'mes' => [$f->format('Y-m-01'), $f->format('Y-m-').$f->format('t')],
            'anio' => [$f->format('Y-01-01'), $f->format('Y-12-31')],
        };
    }

    private function textoEsperado(string $modo, string $desde, string $hasta): string
    {
        $d = new \DateTimeImmutable($desde);

        return match ($modo) {
            'dia' => (int) $d->format('j').' de '.self::MESES[(int) $d->format('n')].' de '.$d->format('Y'),
            'semana' => 'Del '.$d->format('d/m/Y').' al '.(new \DateTimeImmutable($hasta))->format('d/m/Y'),
            'mes' => ucfirst(self::MESES[(int) $d->format('n')]).' '.$d->format('Y'),
            'anio' => 'Año '.$d->format('Y').' completo',
        };
    }

    private function esperado(string $desde, string $hasta, ?int $caja): array
    {
        $en = array_filter($this->filas, fn ($f) => $f['fecha'] >= $desde && $f['fecha'] <= $hasta && (!$caja || $f['caja'] === $caja));

        return [
            'filas' => $en,
            'ingresos' => array_sum(array_map(fn ($f) => $f['tipo'] === 'INGRESO' ? $f['monto'] : 0, $en)),
            'egresosConSigno' => array_sum(array_map(fn ($f) => $f['tipo'] === 'EGRESO' ? $f['monto'] : 0, $en)),
        ];
    }

    /** Recorre todas las páginas siguiendo el enlace real de la paginación. */
    private function todasLasPaginas(array $parametros): array
    {
        $r = $this->actingAs($this->admin)->get(route('web.cashflow.index', $parametros))->assertOk();
        $primera = $r;
        $ids = [];
        $paginas = 0;
        while (true) {
            $paginas++;
            $p = $r->viewData('movimientos');
            $ids = array_merge($ids, $p->pluck('id')->all());
            $this->assertEquals($primera->viewData('totalIngresos'), $r->viewData('totalIngresos'), 'Ingresos iguales en todas las páginas');
            $this->assertEquals($primera->viewData('totalEgresos'), $r->viewData('totalEgresos'), 'Egresos iguales en todas las páginas');
            $this->assertSame($primera->viewData('periodoTexto'), $r->viewData('periodoTexto'));
            if (!$p->hasMorePages()) {
                break;
            }
            $r = $this->get($p->nextPageUrl())->assertOk();
            $this->assertLessThan(20, $paginas);
        }

        return [$primera, $ids, $paginas];
    }

    public function test_01_matriz_de_intervalos_filtros_y_paginas_contra_calculo_propio(): void
    {
        $this->sembrar();
        $casos = [];
        foreach (['2023-12-31', '2024-01-01', '2024-02-26', '2024-02-29', '2024-03-03', '2024-03-04', '2024-12-29',
            '2024-12-30', '2024-12-31', '2025-01-01', '2025-01-05', '2025-01-06', '2025-03-31', '2024-07-17'] as $f) {
            $casos[] = ['dia', $f, ['periodo' => 'dia', 'fecha' => $f]];
            $casos[] = ['semana', $f, ['periodo' => 'semana', 'fecha' => $f]];
        }
        foreach ([[2024, 2], [2025, 2], [2024, 12], [2025, 1], [2024, 4], [2023, 12], [2024, 6]] as [$a, $m]) {
            $casos[] = ['mes', sprintf('%04d-%02d-01', $a, $m), ['periodo' => 'mes', 'anio' => $a, 'mes' => $m]];
            $casos[] = ['mes', sprintf('%04d-%02d-01', $a, $m), ['anio' => $a, 'mes' => $m]]; // enlace anterior
        }
        foreach ([2023, 2024, 2025] as $a) {
            $casos[] = ['anio', "$a-01-01", ['periodo' => 'anio', 'anio' => $a]];
            $casos[] = ['anio', "$a-01-01", ['anio' => $a]]; // enlace anterior
        }

        $pedidos = 0;
        $conVariasPaginas = 0;
        $maxPaginas = 0;
        $paginasPartidasEnUnDia = 0;
        foreach ($casos as [$modo, $fecha, $base]) {
            [$desde, $hasta] = $this->intervalo($modo, $fecha);
            foreach ([null, $this->cajaA->id, $this->cajaB->id] as $caja) {
                foreach ([null, 'INGRESO', 'EGRESO'] as $tipo) {
                    $parametros = $base + array_filter(['tipo_caja_id' => $caja, 'tipo' => $tipo]);
                    $etiqueta = json_encode($parametros);
                    $esp = $this->esperado($desde, $hasta, $caja);
                    [$r, $ids, $paginas] = $this->todasLasPaginas($parametros);
                    $pedidos += $paginas;

                    $this->assertSame($modo, $r->viewData('modo'), $etiqueta);
                    $this->assertSame($this->textoEsperado($modo, $desde, $hasta), $r->viewData('periodoTexto'), $etiqueta);
                    // Los totales no dependen del filtro Tipo; la caja sí los recorta.
                    $this->assertEqualsWithDelta($esp['ingresos'], (float) $r->viewData('totalIngresos'), 0.001, 'ingresos '.$etiqueta);
                    $this->assertEqualsWithDelta(-$esp['egresosConSigno'], (float) $r->viewData('totalEgresos'), 0.001, 'egresos '.$etiqueta);
                    // Las filas: mismo intervalo y caja, y además el filtro Tipo.
                    $idsEsperados = array_column(array_filter($esp['filas'], fn ($f) => !$tipo || $f['tipo'] === $tipo), 'id');
                    sort($idsEsperados);
                    $this->assertSame(count($ids), count(array_unique($ids)), 'Ninguna fila repetida entre páginas: '.$etiqueta);
                    $this->assertSame($r->viewData('movimientos')->total(), count($ids), $etiqueta);
                    sort($ids);
                    $this->assertSame($idsEsperados, $ids, 'filas '.$etiqueta);

                    // Lo que se lee en pantalla coincide con las variables.
                    $resultado = $esp['ingresos'] + $esp['egresosConSigno'];
                    $r->assertSeeInOrder([
                        'Período:', $this->textoEsperado($modo, $desde, $hasta),
                        '$'.number_format($esp['ingresos'], 0, ',', '.'), 'ingresos',
                        '$'.number_format(-$esp['egresosConSigno'], 0, ',', '.'), 'egresos',
                        '$'.number_format($resultado, 0, ',', '.'), 'resultado del período',
                    ]);
                    $r->assertDontSee('777.000')->assertDontSee('saldo inicial');
                    if ($paginas > 1) {
                        $conVariasPaginas++;
                        $maxPaginas = max($maxPaginas, $paginas);
                    }
                }
            }
        }
        $this->assertGreaterThan(0, $conVariasPaginas, 'La matriz tiene que ejercitar la paginación.');
        self::$informe['matriz'] = [
            'movimientos_sembrados' => count($this->filas), 'combinaciones' => count($casos) * 9,
            'pedidos_http' => $pedidos, 'combinaciones_con_varias_paginas' => $conVariasPaginas, 'maximo_de_paginas' => $maxPaginas,
            'resultado' => 'modo, texto del período, ingresos, egresos, resultado en pantalla y filas (todas las páginas) coinciden con el cálculo propio',
        ];
    }

    public function test_02_semana_devuelve_lo_mismo_desde_cualquiera_de_sus_siete_dias(): void
    {
        $this->sembrar();
        $tabla = [];
        // Tres semanas: cruce de año, febrero bisiesto → marzo, y una común.
        foreach (['2024-12-30' => '2025-01-05', '2024-02-26' => '2024-03-03', '2025-03-24' => '2025-03-30', '2023-12-25' => '2023-12-31'] as $lunes => $domingo) {
            $this->assertSame('1', (new \DateTimeImmutable($lunes))->format('N'));
            $this->assertSame('7', (new \DateTimeImmutable($domingo))->format('N'));
            $referencia = null;
            for ($d = 0; $d < 7; $d++) {
                $fecha = (new \DateTimeImmutable($lunes))->modify("+$d days")->format('Y-m-d');
                $r = $this->actingAs($this->admin)->get(route('web.cashflow.index', ['periodo' => 'semana', 'fecha' => $fecha]))->assertOk();
                $actual = [$r->viewData('periodoTexto'), $r->viewData('movimientos')->pluck('id')->sort()->values()->all(),
                    (float) $r->viewData('totalIngresos'), (float) $r->viewData('totalEgresos')];
                $referencia ??= $actual;
                $this->assertSame($referencia, $actual, "Semana del $lunes vista desde $fecha");
                $this->assertSame($fecha, $r->viewData('fechaReferencia')->toDateString(), 'La fecha elegida no se mueve al lunes');
            }
            $fechasDeLasFilas = CashflowMovimiento::whereIn('id', $referencia[1])->pluck('fecha')->map->toDateString()->unique()->sort()->values()->all();
            foreach ($fechasDeLasFilas as $f) {
                $this->assertTrue($f >= $lunes && $f <= $domingo, "$f fuera de $lunes..$domingo");
            }
            $tabla[] = ['lunes' => $lunes, 'domingo' => $domingo, 'texto' => $referencia[0], 'filas' => count($referencia[1]), 'fechas_de_las_filas' => $fechasDeLasFilas];
        }
        self::$informe['semana_desde_sus_siete_dias'] = $tabla;
    }

    public function test_03_signos_devolucion_de_cobro_y_reversion_de_egreso(): void
    {
        // Día con cobro, devolución, egreso y reversión parcial: 1250 − 300 = 950; 420 − 40 = 380; 570.
        $this->mov('2024-12-31', 1250, 'INGRESO');
        $this->mov('2024-12-31', -300, 'INGRESO', null, 'Anulación del cobro');
        $this->mov('2024-12-31', -420, 'EGRESO');
        $this->mov('2024-12-31', 40, 'EGRESO', null, 'Reversión parcial');
        $this->mov('2024-12-30', 9000, 'INGRESO');
        $this->mov('2025-01-01', -8000, 'EGRESO');
        $r = $this->actingAs($this->admin)->get(route('web.cashflow.index', ['periodo' => 'dia', 'fecha' => '2024-12-31']))->assertOk();
        $this->assertEquals(950, $r->viewData('totalIngresos'));
        $this->assertEquals(380, $r->viewData('totalEgresos'));
        $r->assertSeeInOrder(['31 de diciembre de 2024', '$950', 'ingresos', '$380', 'egresos', '$570', 'resultado del período']);
        $r->assertSee('−$300')->assertDontSee('777.000');
        self::$informe['signos']['dia_mixto'] = ['ingresos' => 950, 'egresos' => 380, 'resultado' => 570, 'ok' => true];

        // La devolución cae en otro período que el cobro (el caso real de anularPago): ingresos negativos.
        $this->mov('2025-02-10', -48000, 'INGRESO', null, 'Anulación del cobro de enero');
        $this->mov('2025-02-11', -2000, 'EGRESO');
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'mes', 'anio' => 2025, 'mes' => 2]))->assertOk();
        $this->assertEquals(-48000, $r->viewData('totalIngresos'));
        $this->assertEquals(2000, $r->viewData('totalEgresos'));
        $r->assertSeeInOrder(['$-48.000', 'ingresos', '$2.000', 'egresos', '$-50.000', 'resultado del período']);
        self::$informe['signos']['solo_devolucion_en_el_periodo'] = ['ingresos' => -48000, 'egresos' => 2000, 'resultado' => -50000, 'ok' => true];

        // Semana con egresos mayores que ingresos: resultado negativo.
        $this->mov('2025-03-04', 100, 'INGRESO');
        $this->mov('2025-03-09', -350, 'EGRESO');
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'semana', 'fecha' => '2025-03-06']))->assertOk();
        $r->assertSeeInOrder(['Del 03/03/2025 al 09/03/2025', '$100', '$350', '$-250', 'resultado del período']);
        self::$informe['signos']['semana_negativa'] = ['ingresos' => 100, 'egresos' => 350, 'resultado' => -250, 'ok' => true];
    }

    /**
     * Caracterización, no criterio: una reversión de egreso que supera a los egresos del período.
     * Ningún camino de la aplicación crea hoy un egreso positivo (los tres servicios fuerzan −abs),
     * así que esto solo se arma escribiendo la fila a mano. Se registra lo que hace la pantalla.
     */
    public function test_04_caracterizacion_reversion_de_egreso_sola_en_el_periodo(): void
    {
        $this->mov('2025-01-15', 500, 'INGRESO');
        $this->mov('2025-01-15', 70, 'EGRESO', null, 'Reversión de un egreso de diciembre');
        $r = $this->actingAs($this->admin)->get(route('web.cashflow.index', ['periodo' => 'dia', 'fecha' => '2025-01-15']))->assertOk();
        $observado = ['ingresos' => (float) $r->viewData('totalIngresos'), 'egresos' => (float) $r->viewData('totalEgresos')];
        $observado['resultado_en_pantalla'] = $observado['ingresos'] - $observado['egresos'];
        self::$informe['signos']['reversion_de_egreso_sola_NO_ALCANZABLE'] = [
            'movimientos' => ['+500 ingreso', '+70 en subrubro de egreso'], 'suma_real_de_la_plata' => 570,
            'observado' => $observado,
            'nota' => 'abs() sobre la suma de egresos: si el neto del período es positivo se muestra como egreso y se resta. Ya estaba antes de A10 (y en CashflowSaldoService). Ningún servicio crea egresos positivos hoy.',
        ];
        $this->assertSame(500.0, $observado['ingresos']);
        $this->assertSame(70.0, $observado['egresos']);
        $this->assertSame(430.0, $observado['resultado_en_pantalla']);
    }

    public function test_05_saldo_inicial_separado_y_saldo_disponible_sin_cambios(): void
    {
        $this->mov('2025-03-10', 1000, 'INGRESO', $this->cajaA);
        $this->mov('2025-03-11', -250, 'EGRESO', $this->cajaA);
        foreach ([[], ['tipo_caja_id' => $this->cajaA->id], ['periodo' => 'mes', 'anio' => 2025, 'mes' => 3], ['periodo' => 'dia', 'fecha' => '2025-03-10']] as $p) {
            $this->actingAs($this->admin)->get(route('web.cashflow.index', $p))->assertOk()
                ->assertDontSee('777.000')->assertDontSee('777.750')->assertDontSee('778.000')->assertDontSee('saldo inicial')
                ->assertViewMissing('saldoInicial');
        }
        // El saldo disponible que usan Caja y Liquidaciones sigue arrastrando el saldo inicial.
        $saldos = app(CashflowSaldoService::class);
        $metodo = collect(get_class_methods($saldos))->first(fn ($m) => !str_starts_with($m, '__'));
        $datos = $saldos->{$metodo}();
        $alfa = collect($datos['por_tipo_caja'])->firstWhere('tipo_caja_id', $this->cajaA->id);
        $this->assertEquals(777000, $alfa['saldo_inicial']);
        $this->assertEquals(777750, $alfa['saldo']);
        $this->assertEquals(777750, (float) $this->cajaA->saldo_inicial + (float) CashflowMovimiento::where('tipo_caja_id', $this->cajaA->id)->sum('monto'));
        self::$informe['saldo_inicial'] = ['saldo_inicial_caja_alfa' => 777000, 'resultado_del_periodo' => 750,
            'saldo_disponible_CashflowSaldoService::'.$metodo => $alfa['saldo'], 'pantalla_cashflow_muestra_saldo_inicial' => false];
    }

    /** Lee el formulario de filtros del HTML devuelto, como lo enviaría el navegador. */
    private function formulario(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $xp = new \DOMXPath($dom);
        $form = $xp->query('//form[@id="filtros-form"]')->item(0);
        $this->assertNotNull($form);
        $this->assertSame('get', strtolower($form->getAttribute('method')));
        $campos = [];
        $opciones = [];
        foreach ($xp->query('.//input[@name]|.//select[@name]', $form) as $el) {
            $nombre = $el->getAttribute('name');
            if ($el->nodeName === 'input') {
                $campos[$nombre] = $el->getAttribute('value');
                continue;
            }
            $this->assertTrue($el->hasAttribute('data-enviar-al-cambiar'), "$nombre se envía al cambiar");
            $valor = null;
            foreach ($xp->query('.//option', $el) as $o) {
                $opciones[$nombre][] = $o->getAttribute('value');
                if ($o->hasAttribute('selected')) {
                    $valor = $o->getAttribute('value');
                }
            }
            $campos[$nombre] = $valor ?? $opciones[$nombre][0];
        }
        $enlace = fn (string $t) => ($a = $xp->query('//a[normalize-space()="'.$t.'"]')->item(0)) ? $a->getAttribute('href') : null;
        // Segunda revisión: el href, decodificado una sola vez como hace el navegador (sin Request::create),
        // no puede traer «amp;» ni en el texto ni en los nombres de los parámetros.
        if (($l = $enlace('Limpiar')) !== null) {
            $this->assertStringNotContainsString('amp;', $l);
            parse_str((string) parse_url($l, PHP_URL_QUERY), $q);
            $this->assertSame(['periodo', 'anio', 'mes', 'fecha'], array_keys($q));
            self::$informe['limpiar_href_sin_Request_create'][] = ['href' => $l, 'parametros' => $q];
        }

        return ['accion' => $form->getAttribute('action'), 'campos' => $campos, 'opciones' => $opciones,
            'limpiar' => $enlace('Limpiar'), 'nuevo' => $enlace('Nuevo'),
            'periodo' => trim($xp->query('//*[@id="cashflow-periodo"]//strong')->item(0)?->textContent ?? '')];
    }

    /** Cambia un campo del formulario recibido y lo envía con todos sus campos, vacíos incluidos. */
    private function cambiar(array $f, string $campo, string $valor): array
    {
        $this->assertArrayHasKey($campo, $f['campos'], "El formulario tiene el campo $campo");
        if (isset($f['opciones'][$campo])) {
            $this->assertContains($valor, $f['opciones'][$campo], "$campo ofrece la opción $valor");
        }
        $campos = [$campo => $valor] + $f['campos'];
        $url = $f['accion'].'?'.http_build_query($campos);
        $nuevo = $this->formulario($this->actingAs($this->admin)->get($url)->assertOk()->getContent());
        self::$informe['conservacion_de_fecha'][] = ['cambio' => "$campo=$valor", 'enviado' => $campos, 'periodo_mostrado' => $nuevo['periodo'], 'campos_devueltos' => $nuevo['campos']];

        return $nuevo;
    }

    public function test_06_fecha_conservada_al_alternar_modos_con_el_formulario_real(): void
    {
        $this->mov('2023-06-15', 10, 'INGRESO'); // para que el selector ofrezca 2023
        $this->mov('2024-01-31', 10, 'INGRESO');
        $f = $this->formulario($this->actingAs($this->admin)->get(route('web.cashflow.index', ['periodo' => 'dia', 'fecha' => '2024-01-31']))->assertOk()->getContent());
        $this->assertSame(['periodo', 'fecha', 'tipo_caja_id', 'tipo'], array_keys($f['campos']));
        $this->assertSame('31 de enero de 2024', $f['periodo']);

        $f = $this->cambiar($f, 'periodo', 'semana');
        $this->assertSame('Del 29/01/2024 al 04/02/2024', $f['periodo']);
        $this->assertSame('2024-01-31', $f['campos']['fecha']);

        $f = $this->cambiar($f, 'periodo', 'mes');
        $this->assertSame('Enero 2024', $f['periodo']);
        $this->assertSame(['periodo', 'fecha', 'anio', 'mes', 'tipo_caja_id', 'tipo'], array_keys($f['campos']));
        $this->assertSame(['mes', '2024-01-31', '2024', '1'], [$f['campos']['periodo'], $f['campos']['fecha'], $f['campos']['anio'], $f['campos']['mes']]);

        $f = $this->cambiar($f, 'mes', '2'); // 31 de enero → febrero bisiesto
        $this->assertSame('Febrero 2024', $f['periodo']);
        $this->assertSame('2024-02-29', $f['campos']['fecha']);

        $f = $this->cambiar($f, 'anio', '2023'); // 29 de febrero → año no bisiesto
        $this->assertSame('Febrero 2023', $f['periodo']);
        $this->assertSame('2023-02-28', $f['campos']['fecha']);

        $f = $this->cambiar($f, 'periodo', 'anio');
        $this->assertSame('Año 2023 completo', $f['periodo']);
        $this->assertSame(['periodo', 'fecha', 'anio', 'tipo_caja_id', 'tipo'], array_keys($f['campos']));
        $this->assertSame('2023-02-28', $f['campos']['fecha']);

        $f = $this->cambiar($f, 'anio', '2024');
        $this->assertSame('Año 2024 completo', $f['periodo']);
        $this->assertSame('2024-02-28', $f['campos']['fecha']);

        $f = $this->cambiar($f, 'periodo', 'dia');
        $this->assertSame('28 de febrero de 2024', $f['periodo']);

        // Con filtros puestos: se conservan al cambiar de modo, y Limpiar los quita sin perder el período.
        $this->assertNull($f['limpiar'], 'Sin filtros no hay Limpiar');
        $f = $this->cambiar($f, 'tipo_caja_id', (string) $this->cajaB->id);
        $f = $this->cambiar($f, 'tipo', 'EGRESO');
        $f = $this->cambiar($f, 'periodo', 'semana');
        $this->assertSame([(string) $this->cajaB->id, 'EGRESO', '2024-02-28'], [$f['campos']['tipo_caja_id'], $f['campos']['tipo'], $f['campos']['fecha']]);
        $this->assertSame('Del 26/02/2024 al 03/03/2024', $f['periodo']);
        $this->assertNotNull($f['limpiar']);
        $limpio = $this->formulario($this->get($f['limpiar'])->assertOk()->getContent());
        $this->assertSame(['semana', '2024-02-28', '', ''], [$limpio['campos']['periodo'], $limpio['campos']['fecha'], $limpio['campos']['tipo_caja_id'], $limpio['campos']['tipo']]);
        $this->assertSame('Del 26/02/2024 al 03/03/2024', $limpio['periodo']);
        $this->assertNull($limpio['limpiar']);
        self::$informe['limpiar'] = ['enlace' => $f['limpiar'], 'despues' => $limpio['campos'], 'periodo' => $limpio['periodo']];

        // Limpiar desde Mes y desde Año tampoco cambia el modo.
        foreach ([['periodo' => 'mes', 'anio' => 2024, 'mes' => 4, 'tipo' => 'INGRESO'], ['periodo' => 'anio', 'anio' => 2023, 'tipo_caja_id' => $this->cajaA->id], ['anio' => 2024, 'mes' => 1, 'tipo' => 'EGRESO']] as $p) {
            $antes = $this->formulario($this->get(route('web.cashflow.index', $p))->assertOk()->getContent());
            $despues = $this->formulario($this->get($antes['limpiar'])->assertOk()->getContent());
            $this->assertSame($antes['periodo'], $despues['periodo']);
            $this->assertSame($antes['campos']['periodo'], $despues['campos']['periodo']);
            $this->assertSame(['', ''], [$despues['campos']['tipo_caja_id'], $despues['campos']['tipo']]);
        }

        // Nuevo lleva al formulario de alta.
        $this->assertSame(route('web.cashflow.movimiento'), $limpio['nuevo']);
        $this->get($limpio['nuevo'])->assertOk()->assertSee('Nuevo movimiento')->assertSee('id="mov-form"', false);
    }

    public function test_07_sin_parametros_y_enlaces_anteriores_con_reloj_en_dia_31(): void
    {
        $this->mov('2025-03-31', 10, 'INGRESO');
        $r = $this->actingAs($this->admin)->get(route('web.cashflow.index'))->assertOk();
        $this->assertSame(['anio', 'Año 2025 completo', '2025-03-31'], [$r->viewData('modo'), $r->viewData('periodoTexto'), $r->viewData('fechaReferencia')->toDateString()]);
        $tabla = [];
        foreach ([
            [['anio' => 2024, 'mes' => 2], 'mes', 'Febrero 2024', '2024-02-29'],
            [['anio' => 2025, 'mes' => 2], 'mes', 'Febrero 2025', '2025-02-28'],
            [['mes' => 4], 'mes', 'Abril 2025', '2025-04-30'],
            [['anio' => 2024], 'anio', 'Año 2024 completo', '2024-03-31'],
            [['anio' => 2024, 'mes' => ''], 'anio', 'Año 2024 completo', '2024-03-31'],
            [['periodo' => 'mes'], 'mes', 'Marzo 2025', '2025-03-31'],
            [['periodo' => 'dia'], 'dia', '31 de marzo de 2025', '2025-03-31'],
            [['periodo' => 'semana'], 'semana', 'Del 31/03/2025 al 06/04/2025', '2025-03-31'],
            [['periodo' => 'dia', 'anio' => 2024, 'mes' => 2], 'dia', '31 de marzo de 2025', '2025-03-31'],
            [['periodo' => 'mes', 'fecha' => '2024-02-29'], 'mes', 'Febrero 2024', '2024-02-29'],
            [['periodo' => 'anio', 'fecha' => '2024-02-29'], 'anio', 'Año 2024 completo', '2024-02-29'],
            [['periodo' => 'anio', 'fecha' => '2024-02-29', 'anio' => 2025], 'anio', 'Año 2025 completo', '2025-02-28'],
        ] as [$p, $modo, $texto, $fecha]) {
            $r = $this->get(route('web.cashflow.index', $p))->assertOk();
            $obs = [$r->viewData('modo'), $r->viewData('periodoTexto'), $r->viewData('fechaReferencia')->toDateString()];
            $this->assertSame([$modo, $texto, $fecha], $obs, json_encode($p));
            $tabla[] = ['parametros' => $p, 'modo' => $modo, 'periodo' => $texto, 'fecha_de_referencia' => $fecha];
        }
        self::$informe['enlaces_anteriores_y_sin_parametros'] = $tabla;
    }

    public function test_08_parametros_invalidos_o_raros_nunca_dan_500(): void
    {
        $this->mov('2025-03-31', 10, 'INGRESO');
        $rechaza = [
            'periodo' => [['periodo' => 'xyz'], ['periodo' => 'DIA'], ['periodo' => ['dia']], ['periodo' => 'dia;drop']],
            'fecha' => [['fecha' => '31/12/2024'], ['fecha' => '2025-02-29'], ['fecha' => '2024-13-01'], ['fecha' => '2024-1-5'],
                ['fecha' => ['2024-01-01']], ['fecha' => '2024-01-01 10:00'], ['fecha' => 'hoy'], ['periodo' => 'semana', 'fecha' => '2024-02-30']],
            'anio' => [['anio' => 1899], ['anio' => 2101], ['anio' => 'abc'], ['anio' => '2024.5'], ['anio' => [2024]], ['anio' => -2024]],
            'mes' => [['mes' => 0], ['mes' => 13], ['mes' => -1], ['mes' => 'abc'], ['mes' => '2.5'], ['mes' => [3]], ['anio' => 2024, 'mes' => 99]],
        ];
        $tabla = [];
        foreach ($rechaza as $campo => $lista) {
            foreach ($lista as $p) {
                $r = $this->actingAs($this->admin)->from(route('web.cashflow.index'))->get(route('web.cashflow.index', $p));
                $tabla[] = ['parametros' => $p, 'http' => $r->status(), 'esperado' => 'rechazo con error en '.$campo];
                $r->assertStatus(302)->assertRedirect(route('web.cashflow.index'))->assertSessionHasErrors($campo);
                $this->get(route('web.cashflow.index'))->assertOk();
            }
        }
        $tolera = [
            ['tipo_caja_id' => 'abc'], ['tipo_caja_id' => [1]], ['tipo_caja_id' => 999999], ['tipo_caja_id' => -1],
            ['tipo' => 'OTRO'], ['tipo' => ['INGRESO']], ['tipo' => 'ingreso'],
            ['page' => 'abc'], ['page' => -1], ['page' => 9999], ['page' => [2]],
            ['periodo' => 'semana', 'fecha' => '0001-01-01'], ['periodo' => 'semana', 'fecha' => '9999-12-31'],
            ['periodo' => 'dia', 'fecha' => '9999-12-31'], ['periodo' => 'mes', 'fecha' => '0001-01-01'],
            ['periodo' => 'mes', 'anio' => 1900, 'mes' => 1], ['periodo' => 'mes', 'anio' => 2100, 'mes' => 12],
            ['periodo' => 'anio', 'anio' => 2100, 'fecha' => '2024-02-29'],
            ['periodo' => '', 'fecha' => '', 'anio' => '', 'mes' => ''], ['periodo' => 'dia', 'fecha' => ''],
        ];
        foreach ($tolera as $p) {
            $r = $this->actingAs($this->admin)->from(route('web.cashflow.index'))->get(route('web.cashflow.index', $p));
            $tabla[] = ['parametros' => $p, 'http' => $r->status(), 'esperado' => 'sin 500', 'periodo' => $r->status() === 200 ? $r->viewData('periodoTexto') : null];
            $this->assertContains($r->status(), [200, 302], json_encode($p).' → '.$r->status());
        }
        self::$informe['parametros_invalidos'] = $tabla;
    }

    public function test_09_permisos_y_registro_de_movimientos_intactos(): void
    {
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $profesor = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);
        $alta = ['tipo_caja_id' => $this->cajaA->id, 'subrubro_id' => $this->egreso->id, 'monto' => '1234.50', 'fecha' => '2025-03-31', 'observaciones' => 'Alta del ensayo'];
        $tabla = [];

        foreach (['anónimo' => null, 'OPERATIVO' => $operativo, 'PROFESOR' => $profesor] as $nombre => $usuario) {
            $usuario ? $this->actingAs($usuario) : $this->app['auth']->forgetGuards();
            foreach ([['GET', '/cashflow'], ['GET', '/cashflow?periodo=dia&fecha=2025-03-31'], ['GET', '/cashflow/movimiento'], ['POST', '/cashflow/movimiento']] as [$verbo, $ruta]) {
                $r = $verbo === 'GET' ? $this->get($ruta) : $this->post($ruta, $alta);
                $tabla[] = ['quien' => $nombre, 'pedido' => "$verbo $ruta", 'http' => $r->status(), 'destino' => $r->headers->get('Location')];
                $this->assertContains($r->status(), [302, 403], "$nombre $verbo $ruta");
                $this->assertStringNotContainsString('resultado del período', (string) $r->getContent());
                if ($nombre === 'anónimo') {
                    $r->assertRedirect(route('login'));
                }
            }
        }
        $this->assertSame(0, CashflowMovimiento::count(), 'Nadie sin permiso registró un movimiento');

        // El admin sí: egreso guardado negativo, ingreso positivo, y los dos entran en el día.
        $this->actingAs($this->admin);
        $r = $this->post(route('web.cashflow.movimiento.store'), $alta);
        $tabla[] = ['quien' => 'ADMIN', 'pedido' => 'POST /cashflow/movimiento (egreso 1234.50)', 'http' => $r->status(), 'destino' => $r->headers->get('Location')];
        $r->assertRedirect(route('web.cashflow.index'))->assertSessionHas('success');
        $this->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => $this->ingreso->id, 'monto' => '2000'] + $alta)->assertRedirect(route('web.cashflow.index'));
        $this->assertEqualsCanonicalizing([-1234.50, 2000.00], CashflowMovimiento::pluck('monto')->map(fn ($m) => (float) $m)->all());
        $this->assertSame($this->admin->id, CashflowMovimiento::first()->usuario_admin_id);
        $r = $this->get(route('web.cashflow.index', ['periodo' => 'dia', 'fecha' => '2025-03-31']))->assertOk();
        $r->assertSeeInOrder(['$2.000', 'ingresos', '$1.235', 'egresos', '$766', 'resultado del período']);
        $this->assertSame(2, $r->viewData('movimientos')->total());

        // Las validaciones del alta siguen: fecha futura, monto cero y sin observación no guardan.
        foreach ([['fecha' => '2025-04-01'], ['monto' => '0'], ['observaciones' => ''], ['subrubro_id' => 999999]] as $mal) {
            $this->from(route('web.cashflow.movimiento'))->post(route('web.cashflow.movimiento.store'), $mal + $alta)
                ->assertRedirect(route('web.cashflow.movimiento'))->assertSessionHasErrors(array_key_first($mal));
        }
        // Mes cerrado: pide confirmación antes de guardar.
        $this->from(route('web.cashflow.movimiento'))->post(route('web.cashflow.movimiento.store'), ['fecha' => '2025-02-28'] + $alta)
            ->assertRedirect(route('web.cashflow.movimiento'))->assertSessionHas('aviso_fecha_vieja');
        $this->assertSame(2, CashflowMovimiento::count());
        self::$informe['permisos_y_alta'] = $tabla;
    }
}
