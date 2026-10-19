<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T74 (DU-60, decisión de Carlos 16/10): el Inicio de la operativa muestra los vencidos
 * —el mismo número que Cobranza— y, aparte, los que deben pero están en plazo. Las dos
 * cifras suman lo que anuncia el buscador de Cobrar.
 *
 * Antes el Inicio mostraba solo los vencidos bajo «Con deuda», y el buscador de Cobrar
 * anunciaba más alumnos con deuda: la operativa veía dos números distintos para lo mismo.
 */
class VerificacionT74CodexTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;

    public function createApplication()
    {
        $app = parent::createApplication();
        if (!$app->environment('testing') || $app['db']->connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('Solo testing/wings_testing_codex.');
        }
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogosSeeder::class);
        Configuracion::set('dias_gracia_cobranza', 10);
        Configuracion::set('inscripcion_importe', '5000');
        Carbon::setTestNow('2026-10-05 10:00:00');

        $this->operativo = User::factory()->create(['name'=>'Operativa de prueba', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, TipoCaja::firstOrFail()->id);
        $this->deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 48000,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function anotar(string $caso, array $datos): void
    {
        file_put_contents(__DIR__.'/observaciones.jsonl',json_encode(['caso'=>$caso]+$datos,JSON_UNESCAPED_UNICODE)."\n",FILE_APPEND);
    }

    private function contrastar(int $vencidos, int $enPlazo): void
    {
        $inicio=$this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertOk();
        $v=$this->get(route('web.cobranza.index'))->assertOk()->viewData('alumnos');
        $p=$this->get(route('web.cobranza.index',['estado'=>'EN_PLAZO']))->assertOk()->viewData('alumnos');
        $c=$this->get(route('web.caja.cobrar-cuota'))->assertOk();
        $this->assertSame($vencidos,$inicio->viewData('vencidos'));
        $this->assertSame($enPlazo,$inicio->viewData('enPlazo'));
        $this->assertCount($vencidos,$v);
        $this->assertCount($enPlazo,$p);
        $this->assertSame($vencidos+$enPlazo,$c->viewData('conDeuda'));
        $todos=app(\App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS');
        $this->assertEqualsCanonicalizing($todos->whereIn('estado_cobranza',['MOROSO','DEUDOR'])->pluck('id')->all(),$v->pluck('id')->all());
        $this->assertEqualsCanonicalizing($todos->where('estado_cobranza','EN_PLAZO')->pluck('id')->all(),$p->pluck('id')->all());
        $this->anotar('conteos',['fecha'=>now()->toDateString(),'vencidos'=>$vencidos,'en_plazo'=>$enPlazo,
            'cobrar'=>$c->viewData('conDeuda'),'filas'=>$todos->map(fn($a)=>['id'=>$a->id,'estado'=>$a->estado_cobranza,'saldo'=>$a->deuda_total])->all()]);
    }

    public static function cortes(): array
    {
        return ['antes'=>['2026-10-09',2,2],'ultimo_dia'=>['2026-10-10',2,2],'despues'=>['2026-10-11',4,0]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cortes')]
    public function test_corte_gracia_contra_las_dos_listas_y_cobrar(string $fecha,int $v,int $p): void
    {
        $this->padronMezclado();
        Carbon::setTestNow($fecha.' 10:00:00');
        \App\Models\CajaOperativa::query()->delete();
        \Tests\Support\CajaDeclarada::crear($this->operativo->id,TipoCaja::firstOrFail()->id);
        $this->contrastar($v,$p);
    }

    public function test_saldo_parcial_futuro_e_inactiva_se_comparan_por_filas(): void
    {
        $this->padronMezclado();
        $this->contrastar(2,2);
        $todos=app(\App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS');
        $parcial=$todos->first(fn($a)=>$a->apellido==='Plazo' && $a->nombre==='Dos');
        $this->assertSame(28000.0,(float)$parcial->deuda_total);
        $this->assertSame(0.0,(float)$todos->firstWhere('apellido','Adelantada')->deuda_total);
        $this->assertFalse($todos->contains('apellido','Inactiva'));
        $this->assertTrue(Alumno::where('apellido','Inactiva')->where('activo',false)->exists());
    }

    public function test_solo_inscripcion_y_cuota_parcial_no_duplican_alumno(): void
    {
        $vieja=$this->alumno('Inscripcion','Vieja','2026-09-01');$this->inscripcion($vieja);
        $actual=$this->alumno('Inscripcion','Actual','2026-10-01');$this->inscripcion($actual);
        $this->deuda($actual,'2026-10',pagado:20000.50);
        $this->contrastar(1,1);
        $todos=app(\App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS');
        $this->assertSame(32999.5,(float)$todos->firstWhere('id',$actual->id)->deuda_total);
    }

    public function test_plazo_del_alta_que_cruza_el_mes_respeta_su_corte(): void
    {
        Configuracion::set('dias_plazo_alta',7);
        $this->deuda($this->alumno('Alta','Cruza mes','2026-09-28'),'2026-09');
        foreach (['2026-10-04'=>[0,1],'2026-10-05'=>[1,0]] as $fecha=>[$v,$p]) {
            Carbon::setTestNow($fecha.' 10:00:00');
            \App\Models\CajaOperativa::query()->delete();
            \Tests\Support\CajaDeclarada::crear($this->operativo->id,TipoCaja::firstOrFail()->id);
            $this->contrastar($v,$p);
        }
    }

    public function test_cobro_adelantado_real_no_se_cuenta_como_deuda_corriente(): void
    {
        $a=$this->alumno('Adelanto','Real');
        $this->actingAs($this->operativo)->post(route('web.caja.pagar',$a->id),[
            'tipo_caja_id'=>TipoCaja::firstOrFail()->id,'periodos'=>['2026-11'],
            'montos_cuota'=>['2026-11'=>48000],'fecha_pago'=>'2026-10-05',
            'confirmar_pago_adelantado'=>1,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $d=DeudaCuota::where('alumno_id',$a->id)->where('periodo','2026-11')->sole();
        $this->assertSame('PAGADA',$d->estado);
        $this->assertSame(48000.0,(float)$d->monto_pagado);
        $this->contrastar(0,0);
    }

    public function test_enlaces_salen_de_su_tarjeta_y_roles(): void
    {
        $this->padronMezclado();
        $r=$this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertOk();
        $dom=new \DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$r->getContent());$xp=new \DOMXPath($dom);
        foreach (['Vencidos'=>route('web.cobranza.index'),'En plazo'=>route('web.cobranza.index',['estado'=>'EN_PLAZO']),
            'Posibles inactivos'=>route('web.revision-cobranza.index')] as $label=>$href) {
            $node=$xp->query('//a[contains(@class,"filtros-card")][p[normalize-space(.)="'.$label.'"]]')->item(0);
            $this->assertNotNull($node);$this->assertSame($href,$node->getAttribute('href'));
            $this->get($href)->assertOk();
        }
        $admin=User::factory()->create(['rol'=>'ADMIN','activo'=>true]);
        $this->actingAs($admin)->get(route('web.operativo.dashboard'))->assertOk();
        $prof=User::factory()->create(['rol'=>'PROFESOR','activo'=>true]);
        $this->actingAs($prof)->get(route('web.operativo.dashboard'))->assertForbidden();
        auth()->logout();$this->get(route('web.operativo.dashboard'))->assertRedirect(route('login'));
        $this->operativo->forceFill(['activo'=>false])->save();
        $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertRedirect(route('login'));
    }

    public function test_consultas_del_inicio_no_crecen_por_alumno(): void
    {
        $this->deuda($this->alumno('Consulta','Uno'),'2026-10');
        $this->actingAs($this->operativo);
        $medir=function () {
            \Illuminate\Support\Facades\DB::enableQueryLog();\Illuminate\Support\Facades\DB::flushQueryLog();
            $this->get(route('web.operativo.dashboard'))->assertOk();
            $q=\Illuminate\Support\Facades\DB::getQueryLog();\Illuminate\Support\Facades\DB::disableQueryLog();return $q;
        };
        $medir();$antes=$medir();
        for($i=0;$i<11;$i++)$this->deuda($this->alumno('Consulta',(string)$i),'2026-10');
        $despues=$medir();
        $this->anotar('consultas',['alumnos_antes'=>1,'alumnos_despues'=>12,'antes'=>count($antes),'despues'=>count($despues),'sql_antes'=>$antes,'sql_despues'=>$despues]);
        $this->assertSame(count($antes),count($despues));
    }

    private function html(string $nombre,string $contenido): void
    {
        $fase=getenv('T74_FASE') ?: 'antes';
        if(!in_array($fase,['antes','despues'],true))throw new \RuntimeException('Fase invalida');
        $dir=__DIR__.'/'.$fase;if(!is_dir($dir))mkdir($dir,0775,true);
        $contenido=preg_replace('~https?://[^/"\s]+/build/~','file:///'.str_replace('\\','/',public_path('build')).'/',$contenido);
        $contenido=preg_replace('/(<meta name="csrf-token" content=")[^"]+/','$1omitido',$contenido);
        $contenido=preg_replace('/(name="_token" value=")[^"]+/','$1omitido',$contenido);
        file_put_contents($dir.'/'.$nombre.'.html',$contenido);
    }

    public function test_capturas_de_las_variantes_reales(): void
    {
        $this->padronMezclado();$this->actingAs($this->operativo);
        $this->html('propia',$this->get(route('web.operativo.dashboard'))->assertOk()->getContent());
        $caja=\App\Models\CajaOperativa::firstOrFail();
        $otro=User::factory()->create(['name'=>'Otra operativa','rol'=>'OPERATIVO','activo'=>true]);
        $caja->update(['usuario_operativo_id'=>$otro->id,'usuario_apertura_id'=>$otro->id]);
        $this->html('companero',$this->get(route('web.operativo.dashboard'))->assertOk()->getContent());
        $caja->update(['usuario_operativo_id'=>$this->operativo->id,'usuario_apertura_id'=>$this->operativo->id,'apertura_at'=>now()->subDay()]);
        $this->html('pendiente',$this->get(route('web.operativo.dashboard'))->assertOk()->getContent());
        \App\Models\CajaOperativa::query()->delete();
        $this->html('sin-caja',$this->get(route('web.operativo.dashboard'))->assertOk()->getContent());
        auth()->logout();$this->html('login',$this->get(route('login'))->assertOk()->getContent());
    }

    private function padronMezclado(): void
    {
        $this->deuda($this->alumno('Vencida', 'Uno'), '2026-09');
        $doble = $this->alumno('Vencida', 'Dos');
        $this->deuda($doble, '2026-09');
        $this->deuda($doble, '2026-10');
        $this->deuda($this->alumno('Plazo', 'Uno'), '2026-10');
        $this->deuda($this->alumno('Plazo', 'Dos'), '2026-10', pagado: 20000);
        $this->deuda($this->alumno('Aldia', 'Pagada'), '2026-10', pagado: 48000, estado: DeudaCuota::ESTADO_PAGADA);
        $this->alumno('Aldia', 'SinCuotas');
        $this->deuda($this->alumno('Adelantada', 'Noviembre'), '2026-11');
        $inactiva = $this->alumno('Inactiva', 'Deudora');
        $this->deuda($inactiva, '2026-09');
        $inactiva->update(['activo' => false]);
    }

    private function cobranza(?string $estado = null): int
    {
        $parametros = $estado ? ['estado' => $estado] : [];

        return $this->actingAs($this->operativo)->get(route('web.cobranza.index', $parametros))
            ->assertOk()->viewData('alumnos')->count();
    }

    private function buscadorDeCobrar(): int
    {
        return $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))
            ->assertOk()->viewData('conDeuda');
    }

    private function alumno(string $apellido, string $nombre, string $alta = '2026-09-01'): Alumno
    {
        $alumno = Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => (string) (30000000 + Alumno::count() + 1),
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0000',
            'fecha_alta' => $alta,
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => $alta,
            'activo' => true,
        ]);

        return $alumno;
    }

    private function deuda(Alumno $alumno, string $periodo, float $pagado = 0, string $estado = DeudaCuota::ESTADO_PENDIENTE): DeudaCuota
    {
        return DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodo,
            'monto_original' => 48000,
            'monto_pagado' => $pagado,
            'monto_condonado' => 0,
            'estado' => $estado,
        ]);
    }

    private function inscripcion(Alumno $alumno): void
    {
        CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'dni' => $alumno->dni,
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'inscripcion:dni:' . $alumno->dni,
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => 5000,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => $alumno->fecha_alta],
            'estado' => 'VIGENTE',
        ]);
    }
}
