<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CashflowMovimiento, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Liquidacion, LiquidacionDetalle, Nivel, Profesor, Subrubro, TipoCaja, User};
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Evidencia independiente T115. Ejecutar con DB_DATABASE=wings_testing_codex. */
class ControlT115CodexTest extends TestCase
{
    use RefreshDatabase;

    private const AVISO = 'No se puede liquidar un mes que todavía no empezó. Revisá el mes y el año.';
    private User $admin;
    private Profesor $hora;
    private Profesor $comision;
    private TipoCaja $banco;
    private array $grupos = [];

    protected function setUp(): void
    {
        if (getenv('DB_DATABASE') !== 'wings_testing_codex') throw new \RuntimeException('Asignar DB_DATABASE=wings_testing_codex antes de ejecutar.');
        parent::setUp();
        Carbon::setTestNow('2027-01-02 12:00:00');
        $this->seed(CatalogosSeeder::class);
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $sub = Subrubro::where('nombre', 'Luz')->firstOrFail();
        $nivel = Nivel::create(['nombre' => 'Control T115']);
        foreach (['HORA', 'COMISION'] as $i => $tipo) {
            $dep = Deporte::create(['nombre' => 'Control '.$tipo, 'tipo_liquidacion' => $tipo, 'activo' => true]);
            $grupo = Grupo::create(['deporte_id' => $dep->id, 'nivel_id' => $nivel->id, 'activo' => true]);
            $prof = Profesor::create(['deporte_id' => $dep->id, 'nombre' => 'Control', 'apellido' => $tipo, 'dni' => (string) (39001150 + $i),
                'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Ficticia', 'localidad' => 'Ficticia', 'telefono' => '1111111111',
                'activo' => true, 'valor_hora' => $tipo === 'HORA' ? 1000 : null, 'porcentaje_comision' => $tipo === 'COMISION' ? 35 : null, 'subrubro_id' => $sub->id]);
            $this->grupos[$prof->id] = $grupo->id;
            if ($tipo === 'HORA') $this->hora = $prof; else $this->comision = $prof;
        }
        $this->banco = TipoCaja::create(['nombre' => 'Banco control T115', 'abreviatura' => 'T115', 'activo' => true, 'saldo_inicial' => 100000]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function clase(Profesor $prof, string $fecha, bool $validada = true): Clase
    {
        $clase = Clase::create(['grupo_id' => $this->grupos[$prof->id], 'fecha' => $fecha, 'hora_inicio' => '09:00', 'hora_fin' => '10:00',
            'cancelada' => false, 'validada_para_liquidacion' => $validada]);
        $clase->profesores()->attach($prof->id);
        return $clase;
    }

    private function generar(Profesor $prof, mixed $mes, mixed $anio, array $extra = [])
    {
        return $this->actingAs($this->admin)->from(route('web.liquidaciones.create'))
            ->post(route('web.liquidaciones.store'), ['profesor_id' => $prof->id, 'mes' => $mes, 'anio' => $anio] + $extra);
    }

    private function sinEscrituras(): array
    {
        return [Liquidacion::orderBy('id')->get()->toJson(), LiquidacionDetalle::orderBy('id')->get()->toJson(),
            Clase::orderBy('id')->get()->toJson(), CashflowMovimiento::orderBy('id')->get()->toJson()];
    }

    private function dom(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return new \DOMXPath($dom);
    }

    private function guardarFormulario(string $html, string $nombre): void
    {
        $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1REDACTADO', $html);
        $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1REDACTADO', $html);
        $html = preg_replace('/[ \t]+$/m', '', $html);
        file_put_contents(__DIR__.'/'.$nombre, $html);
    }

    public function test_diciembre_futuro_ambos_tipos_avisa_conserva_formulario_y_permite_corregir_solo_anio(): void
    {
        $inicial = $this->actingAs($this->admin)->get(route('web.liquidaciones.create'))->assertOk();
        $formularioInicial = $this->dom($inicial->getContent());
        $this->assertSame('2027', $formularioInicial->query('//input[@name="anio"]')->item(0)->getAttribute('value'));
        $this->assertSame('1', $formularioInicial->query('//select[@name="mes"]/option[@selected]')->item(0)->getAttribute('value'));
        foreach ([$this->hora, $this->comision] as $prof) {
            $this->clase($prof, '2026-12-10');
            $antes = $this->sinEscrituras();
            $this->generar($prof, 12, 2027)->assertRedirect(route('web.liquidaciones.create'))->assertSessionHas('error', self::AVISO)
                ->assertSessionHasInput('profesor_id', $prof->id)->assertSessionHasInput('mes', 12)->assertSessionHasInput('anio', 2027);
            $this->assertSame($antes, $this->sinEscrituras());
            $r = $this->get(route('web.liquidaciones.create'))->assertOk()->assertSee(self::AVISO);
            $x = $this->dom($r->getContent());
            $this->assertSame('2027', $x->query('//input[@name="anio"]')->item(0)->getAttribute('value'));
            $this->assertSame('12', $x->query('//select[@name="mes"]/option[@selected]')->item(0)->getAttribute('value'));
            $radio = $x->query('//input[@name="profesor_id" and @checked]')->item(0);
            $this->assertSame((string) $prof->id, $radio->getAttribute('value'));
            $this->assertFalse($radio->hasAttribute('disabled'));
            $this->guardarFormulario($r->getContent(), 'aviso-'.$prof->deporte->tipo_liquidacion.'.html');
            // Se reenvían los valores conservados y se cambia solamente el año.
            $this->generar($prof, (int) $x->query('//select[@name="mes"]/option[@selected]')->item(0)->getAttribute('value'), 2026)
                ->assertSessionMissing('error')->assertSessionHas('success');
            $this->assertSame(1, Liquidacion::where('profesor_id', $prof->id)->where('mes', 12)->where('anio', 2026)->count());
        }
    }

    public function test_febrero_rechazado_el_dos_y_el_ultimo_minuto_de_enero_sin_escribir(): void
    {
        foreach (['2027-01-02 12:00:00', '2027-01-31 23:59:59'] as $momento) {
            Carbon::setTestNow($momento);
            foreach ([$this->hora, $this->comision] as $prof) {
                $antes = $this->sinEscrituras();
                $this->generar($prof, 2, 2027, ['incluir_hoy' => 1])->assertSessionHas('error', self::AVISO);
                $this->assertSame($antes, $this->sinEscrituras());
            }
        }
    }

    public function test_diciembre_con_clases_se_genera_recalcula_cierra_y_paga_una_vez(): void
    {
        $clase = $this->clase($this->hora, '2026-12-10');
        $this->generar($this->hora, 12, 2026)->assertSessionMissing('error')->assertSessionHas('success');
        $l = Liquidacion::sole();
        $this->assertSame('1000.00', $l->total_calculado);
        $this->assertSame($clase->id, (int) $l->detalles()->sole()->referencia_id);
        $this->post(route('web.liquidaciones.recalcular', $l->id))->assertSessionMissing('error')->assertSessionHas('success');
        $this->assertSame('1000.00', $l->fresh()->total_calculado);
        $this->post(route('web.liquidaciones.cerrar', $l->id))->assertSessionMissing('error')->assertSessionHas('success');
        $this->assertSame('CERRADA', $l->fresh()->estado);
        $datos = ['tipo_caja_id' => $this->banco->id, 'fecha_pago' => '2027-01-02'];
        $this->post(route('web.liquidaciones.pagar', $l->id), $datos)->assertSessionMissing('error')->assertSessionHas('success');
        $this->post(route('web.liquidaciones.pagar', $l->id), $datos)->assertSessionMissing('error');
        $this->assertSame('PAGADA', $l->fresh()->estado_pago);
        $cf = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_LIQUIDACION)->where('referencia_id', $l->id)->sole();
        $this->assertSame('-1000.00', $cf->monto);
    }

    public static function incluirHoy(): array { return ['sin_hoy' => [false, 1], 'con_hoy' => [true, 2]]; }

    #[DataProvider('incluirHoy')]
    public function test_enero_actual_con_y_sin_incluir_hoy(bool $incluir, int $cantidad): void
    {
        $this->clase($this->hora, '2027-01-01');
        $hoy = $this->clase($this->hora, '2027-01-02', false);
        $this->generar($this->hora, 1, 2027, ['incluir_hoy' => $incluir])->assertSessionMissing('error')->assertSessionHas('success');
        $l = Liquidacion::sole();
        $this->assertEquals($cantidad * 1000, (float) $l->total_calculado);
        $this->assertSame($cantidad, $l->detalles()->count());
        $this->assertSame($incluir, $hoy->fresh()->validada_para_liquidacion);
    }

    public function test_mes_y_anio_invalidos_rechazan_sin_error_del_servidor_ni_filas(): void
    {
        foreach ([[13,2027],[0,2027],[1,2019],[1,2028],['letras',2027],[12,'letras']] as [$mes,$anio]) {
            $antes = $this->sinEscrituras();
            $r = $this->generar($this->hora, $mes, $anio);
            $r->assertRedirect();
            $this->assertTrue(session()->has('errors') || session()->has('error'), 'Falta rechazo para '.json_encode([$mes,$anio]));
            $this->assertSame($antes, $this->sinEscrituras());
            $this->flushSession();
        }
    }

    public function test_inactiva_con_clase_dictada_pendiente_se_ofrece_y_se_liquida(): void
    {
        $this->clase($this->hora, '2026-12-10');
        $this->hora->update(['activo' => false]);
        $r = $this->actingAs($this->admin)->get(route('web.liquidaciones.create', ['mes' => 12, 'anio' => 2026]))->assertOk();
        $radio = $this->dom($r->getContent())->query('//input[@name="profesor_id" and @value="'.$this->hora->id.'"]')->item(0);
        $this->assertNotNull($radio);
        $this->assertFalse($radio->hasAttribute('disabled'));
        $this->generar($this->hora, 12, 2026)->assertSessionMissing('error')->assertSessionHas('success');
        $this->assertSame('1000.00', Liquidacion::sole()->total_calculado);
        $this->assertSame(1, LiquidacionDetalle::count());
    }

    public function test_cambio_de_mes_se_decide_en_buenos_aires_aunque_utc_ya_haya_cambiado(): void
    {
        $this->assertSame('America/Argentina/Buenos_Aires', config('app.timezone'));
        Carbon::setTestNow(Carbon::parse('2027-02-01 02:59:59', 'UTC'));
        $this->assertSame('2027-01-31 23:59:59', now()->format('Y-m-d H:i:s'));
        $this->generar($this->hora, 2, 2027)->assertSessionHas('error', self::AVISO);
        $this->assertSame(0, Liquidacion::count());
        Carbon::setTestNow(Carbon::parse('2027-02-01 03:00:00', 'UTC'));
        $this->assertSame('2027-02-01 00:00:00', now()->format('Y-m-d H:i:s'));
        $this->generar($this->hora, 2, 2027)->assertSessionMissing('error')->assertSessionHas('success');
        $this->assertSame(1, Liquidacion::where('mes',2)->where('anio',2027)->count());
    }

    public function test_futura_heredada_recalcula_pero_no_cierra_ni_paga_antes_del_fin_de_mes(): void
    {
        // Así quedaba la generación anterior: ABIERTA, vacía, de un mes futuro.
        $l = Liquidacion::create(['profesor_id'=>$this->hora->id,'mes'=>12,'anio'=>2027,'tipo'=>'HORA','valor_hora_aplicado'=>1000,
            'total_calculado'=>0,'estado'=>'ABIERTA']);
        $this->actingAs($this->admin)->post(route('web.liquidaciones.recalcular',$l->id))->assertSessionMissing('error')->assertSessionHas('success');
        $this->assertSame('0.00', $l->fresh()->total_calculado);
        $this->post(route('web.liquidaciones.cerrar',$l->id))->assertSessionHas('error','No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/01/2028.');
        $this->assertSame('ABIERTA',$l->fresh()->estado);
        $this->post(route('web.liquidaciones.pagar',$l->id),['tipo_caja_id'=>$this->banco->id,'fecha_pago'=>'2027-01-02'])->assertSessionHas('error');
        $this->assertSame('PENDIENTE',$l->fresh()->estado_pago);
        $this->assertSame(0,CashflowMovimiento::count());
    }

    public function test_cero_pasado_no_se_elige_sin_clases_pero_si_con_pendientes_de_otro_mes(): void
    {
        $r = $this->actingAs($this->admin)->get(route('web.liquidaciones.create'))->assertOk();
        $radio = $this->dom($r->getContent())->query('//input[@name="profesor_id" and @value="'.$this->hora->id.'"]')->item(0);
        $this->assertTrue($radio->hasAttribute('disabled'));
        $this->clase($this->hora,'2026-12-10');
        $r = $this->get(route('web.liquidaciones.create',['mes'=>11,'anio'=>2026]))->assertOk();
        $radio = $this->dom($r->getContent())->query('//input[@name="profesor_id" and @value="'.$this->hora->id.'"]')->item(0);
        $this->assertFalse($radio->hasAttribute('disabled'));
        $this->generar($this->hora,11,2026)->assertSessionMissing('error')->assertSessionHas('success');
        $l = Liquidacion::sole();
        $this->assertSame('0.00',$l->total_calculado);
        $this->assertSame(0,$l->detalles()->count());
    }

    public function test_servicio_directo_tambien_rechaza_futuro_antes_de_escribir(): void
    {
        foreach ([$this->hora,$this->comision] as $prof) {
            $antes=$this->sinEscrituras();
            try { app(LiquidacionService::class)->generarLiquidacionMensual($prof->id,12,2027); $this->fail('Aceptó futuro'); }
            catch (\Exception $e) { $this->assertSame(self::AVISO,$e->getMessage()); }
            $this->assertSame($antes,$this->sinEscrituras());
        }
    }
}
