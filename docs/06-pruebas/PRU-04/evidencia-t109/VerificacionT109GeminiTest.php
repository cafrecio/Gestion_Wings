<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Control independiente de Gemini para T109:
 * Comprobación exhaustiva de los 8 casos del prompt + verificación de filtros de pantalla.
 */
class VerificacionT109GeminiTest extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $operativa;
    private User $profesora;
    private Pago $pagoCuota;
    private Liquidacion $liquidacionPagada;
    private Liquidacion $liquidacionSinPagar;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->seed(CatalogosSeeder::class);

        $this->duenio = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $deporte = Deporte::firstOrCreate(['nombre' => 'Patín'], ['tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Escuela']);
        $grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id], ['activo' => true]);
        $plan = GrupoPlan::firstOrCreate(['grupo_id' => $grupo->id, 'clases_por_semana' => 2], ['precio_mensual' => 25000, 'activo' => true]);

        $profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Lucía',
            'apellido' => 'Méndez',
            'dni' => '29888777',
            'fecha_nacimiento' => '1988-03-20',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Rosario',
            'telefono' => '3415554433',
            'valor_hora' => 12000,
            'activo' => true,
        ]);

        $this->profesora = User::factory()->create([
            'rol' => User::ROL_PROFESOR,
            'profesor_id' => $profesor->id,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Valentina',
            'apellido' => 'Ríos',
            'dni' => '48123456',
            'fecha_nacimiento' => '2010-06-15',
            'celular' => '3416667788',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-03-01',
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-03-01',
            'activo' => true,
        ]);

        $deuda = DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-03',
            'monto_original' => 25000,
            'monto_pagado' => 25000,
            'estado' => 'PAGADA',
        ]);

        $efectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true]);

        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativa->id,
            'monto_apertura' => 10000,
            'estado' => CajaOperativa::ESTADO_ABIERTA,
            'apertura_at' => now(),
        ]);

        $this->pagoCuota = Pago::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'mes' => 3,
            'anio' => 2026,
            'monto_base' => 25000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 25000,
            'monto_cuota' => 25000,
            'fecha_pago' => '2026-03-10',
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);

        $subrubroCuota = Subrubro::where('nombre', 'Cuota Mensual')->first();
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'usuario_id' => $this->operativa->id,
            'pago_id' => $this->pagoCuota->id,
            'tipo_caja_id' => $efectivo->id,
            'subrubro_id' => $subrubroCuota?->id ?? 1,
            'concepto' => 'Cobro de cuota Valentina Ríos',
            'monto' => 25000,
            'tipo' => 'INGRESO',
            'fecha' => '2026-03-10',
        ]);

        $this->liquidacionPagada = Liquidacion::create([
            'profesor_id' => $profesor->id,
            'mes' => 3,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 48000,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PAGADA,
            'pagada_fecha' => '2026-04-05',
            'pagada_at' => now(),
        ]);

        $this->liquidacionSinPagar = Liquidacion::create([
            'profesor_id' => $profesor->id,
            'mes' => 4,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 52000,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);
    }

    /**
     * Caso 1: Historial: desde y hasta con texto, fecha imposible y comillas.
     * Resultado esperado: La pantalla abre (200), sin el filtro y con el campo vacío.
     */
    public function test_caso_1_historial_fechas_invalidas_abren_limpias_y_campo_vacio(): void
    {
        $valoresInvalidos = [
            'abc',
            '9999-99-99',
            '2026-02-31',
            "1' OR '1'='1",
            '0',
            '-1',
            '2026-13-45',
            '../../etc/passwd',
            '<script>alert(1)</script>',
        ];

        foreach (['desde', 'hasta'] as $param) {
            foreach ($valoresInvalidos as $val) {
                // Como dueño
                $resDueno = $this->actingAs($this->duenio)->get(route('web.caja.historial', [$param => $val]));
                $resDueno->assertOk();
                $resDueno->assertDontSee('value="' . $val . '"', false);

                // Como operativa
                $resOpe = $this->actingAs($this->operativa)->get(route('web.caja.historial', [$param => $val]));
                $resOpe->assertOk();
                $resOpe->assertDontSee('value="' . $val . '"', false);
            }
        }
    }

    /**
     * Caso 2: Listados: ?search[]=x, ?estado[]=x, ?fecha[]=x, ?q[]=x, ?desde[]=x.
     * Resultado esperado: La pantalla abre sin error del servidor (< 500).
     */
    public function test_caso_2_parametros_tipo_lista_no_provocan_error_500(): void
    {
        $pruebas = [
            ['web.caja.historial', 'search', ['x']],
            ['web.caja.historial', 'desde', ['x']],
            ['web.caja.historial', 'hasta', ['x']],
            ['web.movimientos.index', 'desde', ['x']],
            ['web.movimientos.index', 'hasta', ['x']],
            ['web.cobranza.index', 'estado', ['x']],
            ['web.clases.index', 'fecha', ['x']],
            ['web.clases.index', 'estado', ['x']],
            ['web.alumnos.index', 'search', ['x']],
            ['web.alumnos.autocomplete', 'q', ['x']],
            ['web.caja.cobrar-cuota', 'search', ['x']],
            ['web.profesores.index', 'search', ['x']],
            ['web.revision-cobranza.index', 'estado', ['x']],
            ['web.liquidaciones.index', 'estado', ['x']],
        ];

        foreach ($pruebas as [$ruta, $param, $val]) {
            $url = route($ruta) . '?' . http_build_query([$param => $val]);

            $resDueno = $this->actingAs($this->duenio)->get($url);
            $this->assertLessThan(500, $resDueno->getStatusCode(), "Dueño GET {$url} arrojó {$resDueno->getStatusCode()}");

            // Operativa en pantallas accesibles
            if (!in_array($ruta, ['web.liquidaciones.index', 'web.profesores.index'])) {
                $resOpe = $this->actingAs($this->operativa)->get($url);
                $this->assertLessThan(500, $resOpe->getStatusCode(), "Operativa GET {$url} arrojó {$resOpe->getStatusCode()}");
            }
        }
    }

    /**
     * Caso 3: Caja del dueño: ?mes=abc, ?mes=2026-13, ?mes[]=x.
     * Resultado esperado: Abre en el mes actual.
     */
    public function test_caso_3_caja_duenio_mes_invalido_abre_en_mes_actual(): void
    {
        $mesActual = now()->format('Y-m');
        $mesesInvalidos = ['abc', '2026-13', '2026-00', '9999-99', "1' OR '1'='1", ['x'], '-1', '0'];

        foreach ($mesesInvalidos as $mesInvalido) {
            $url = route('web.caja.index') . '?' . http_build_query(['mes' => $mesInvalido]);
            $res = $this->actingAs($this->duenio)->get($url);
            $res->assertOk();
            $res->assertViewHas('mes', $mesActual);
        }
    }

    /**
     * Caso 4: Fechas, mes y búsquedas válidos, usando los filtros de las pantallas.
     * Resultado esperado: Filtran igual que antes.
     */
    public function test_caso_4_filtros_validos_siguen_filtrando_correctamente(): void
    {
        // 1. Historial con fecha válida
        $resHist = $this->actingAs($this->duenio)->get(route('web.caja.historial', [
            'desde' => '2026-03-01',
            'hasta' => '2026-03-31',
        ]));
        $resHist->assertOk();
        $resHist->assertSee('value="2026-03-01"', false);
        $resHist->assertSee('value="2026-03-31"', false);

        // 2. Alumnos con búsqueda válida
        $resAlumnos = $this->actingAs($this->duenio)->get(route('web.alumnos.index', ['search' => 'Valentina']));
        $resAlumnos->assertOk();
        $resAlumnos->assertSee('value="Valentina"', false);
        $resAlumnos->assertSee('Valentina');

        // 3. Mes válido en Caja
        $resCajaMes = $this->actingAs($this->duenio)->get(route('web.caja.index', ['mes' => '2026-03']));
        $resCajaMes->assertOk();
        $resCajaMes->assertViewHas('mes', '2026-03');
    }

    /**
     * Caso 5: Recibo inexistente, como operativa y como dueño.
     * Resultado esperado: «Página no disponible» (HTTP 404 con vista errors.404, nunca JSON técnico).
     */
    public function test_caso_5_recibo_inexistente_muestra_pagina_no_disponible(): void
    {
        foreach ([$this->duenio, $this->operativa] as $usuario) {
            $res = $this->actingAs($usuario)->get(route('web.recibos.cuota', 999999));
            $res->assertNotFound();
            $res->assertSee('Página no disponible');
            $res->assertSee('La página no existe o el registro ya no está');
            $res->assertDontSee('Pago no encontrado'); // No expone JSON técnico
            $this->assertStringContainsString('text/html', $res->headers->get('Content-Type'));

            // Con texto en el ID de la URL
            $resTexto = $this->actingAs($usuario)->get('/recibos/cuota/no-numerico');
            $resTexto->assertNotFound();
            $resTexto->assertSee('Página no disponible');
        }
    }

    /**
     * Caso 6: Recibo que existe e inexistente, como profesora.
     * Resultado esperado: 403 en los dos (no revela existencia).
     */
    public function test_caso_6_profesora_recibe_403_exista_o_no_el_recibo(): void
    {
        // 1. Recibo que existe
        $resExiste = $this->actingAs($this->profesora)->get(route('web.recibos.cuota', $this->pagoCuota->id));
        $resExiste->assertForbidden();

        // 2. Recibo inexistente
        $resInexistente = $this->actingAs($this->profesora)->get(route('web.recibos.cuota', 999999));
        $resInexistente->assertForbidden();

        // 3. Recibo con texto (URL malformada rechazada por el router como 404 común)
        $resTexto = $this->actingAs($this->profesora)->get('/recibos/cuota/texto');
        $resTexto->assertNotFound();
        $resTexto->assertSee('Página no disponible');
    }

    /**
     * Caso 7: Liquidación inexistente o sin pagar, como dueño.
     * Resultado esperado: «Página no disponible» (HTTP 404, no JSON crudo ni HTTP 400).
     */
    public function test_caso_7_liquidacion_inexistente_o_sin_pagar_muestra_pagina_no_disponible(): void
    {
        // 1. Inexistente
        $resInexistente = $this->actingAs($this->duenio)->get(route('web.recibos.liquidacion', 999999));
        $resInexistente->assertNotFound();
        $resInexistente->assertSee('Página no disponible');
        $resInexistente->assertDontSee('Liquidación no encontrada');

        // 2. Sin pagar (estado CERRADA pero no PAGADA)
        $resSinPagar = $this->actingAs($this->duenio)->get(route('web.recibos.liquidacion', $this->liquidacionSinPagar->id));
        $resSinPagar->assertNotFound();
        $resSinPagar->assertSee('Página no disponible');
        $resSinPagar->assertDontSee('Liquidación no pagada');

        // 3. Con texto en ID
        $resTexto = $this->actingAs($this->duenio)->get('/recibos/liquidacion/invalido');
        $resTexto->assertNotFound();
        $resTexto->assertSee('Página no disponible');
    }

    /**
     * Caso 8: Recibo que existe, con ?inline=1 y descargándolo.
     * Resultado esperado: Funciona igual (PDF correcto, headers Content-Disposition adecuados).
     */
    public function test_caso_8_recibo_existente_con_inline_y_descarga(): void
    {
        // 1. Descarga habitual (sin inline): attachment
        $resDescarga = $this->actingAs($this->duenio)->get(route('web.recibos.cuota', $this->pagoCuota->id));
        $resDescarga->assertOk();
        $this->assertSame('application/pdf', $resDescarga->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="recibo-cuota-' . $this->pagoCuota->id . '.pdf"', $resDescarga->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $resDescarga->getContent());

        // 2. Visualización en navegador (?inline=1): inline
        $resInline = $this->actingAs($this->duenio)->get(route('web.recibos.cuota', [$this->pagoCuota->id, 'inline' => 1]));
        $resInline->assertOk();
        $this->assertSame('application/pdf', $resInline->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="recibo-cuota-' . $this->pagoCuota->id . '.pdf"', $resInline->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $resInline->getContent());

        // 3. Liquidación pagada: descarga y con inline=1
        $resLiqDescarga = $this->actingAs($this->duenio)->get(route('web.recibos.liquidacion', $this->liquidacionPagada->id));
        $resLiqDescarga->assertOk();
        $this->assertSame('application/pdf', $resLiqDescarga->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="recibo-liquidacion-' . $this->liquidacionPagada->id . '.pdf"', $resLiqDescarga->headers->get('Content-Disposition'));

        $resLiqInline = $this->actingAs($this->duenio)->get(route('web.recibos.liquidacion', [$this->liquidacionPagada->id, 'inline' => 1]));
        $resLiqInline->assertOk();
        $this->assertSame('application/pdf', $resLiqInline->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="recibo-liquidacion-' . $this->liquidacionPagada->id . '.pdf"', $resLiqInline->headers->get('Content-Disposition'));
    }
}
