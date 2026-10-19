<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\Alumno;
use App\Models\GrupoPlan;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\User;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreT34CodexTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesorUser;
    private Profesor $profesor;
    private Grupo $grupo;
    private Alumno $alumno;

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

        $this->travelTo(Carbon::parse('2026-10-18 12:00:00'));

        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);

        $deporte = Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Lucia',
            'apellido' => 'Mendez',
            'dni' => '30111222',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Prueba',
            'valor_hora' => 1000.00,
            'activo' => true,
        ]);

        $this->profesorUser = User::factory()->create([
            'rol' => 'PROFESOR',
            'profesor_id' => $this->profesor->id,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Sofia',
            'apellido' => 'Gomez',
            'dni' => '42123456',
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => '2026-09-01',
            'activo' => true,
        ]);

        $plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 15000.00,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-09-01',
            'activo' => true,
        ]);
    }

    private function crearClasePasada(string $fecha = '2026-10-10', string $inicio = '10:00', string $fin = '11:00'): Clase
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => $fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'cancelada' => false,
            'validada_para_liquidacion' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    private function registrar(string $caso, array $datos): void
    {
        file_put_contents(__DIR__.'/observaciones.jsonl', json_encode(['caso'=>$caso]+$datos, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
    }

    private function guardarHtml(string $nombre, string $html): void
    {
        $build = 'file:///'.str_replace('\\', '/', public_path('build')).'/';
        $html = preg_replace('~https?://[^/"\s]+/build/~', $build, $html);
        $html = preg_replace('/(<meta name="csrf-token" content=")[^"]+/', '$1omitido', $html);
        $html = preg_replace('/(name="_token" value=")[^"]+/', '$1omitido', $html);
        file_put_contents(__DIR__.'/'.$nombre.'.html', $html);
    }

    public function test_estados_tarjeta_y_ficha_coinciden(): void
    {
        foreach ([null=>'Pendiente', 0=>'Sin presentes', 1=>'Cargada'] as $valor=>$etiqueta) {
            $clase = $this->crearClasePasada('2026-10-10');
            if ($valor !== '') {
                Asistencia::create(['clase_id'=>$clase->id, 'alumno_id'=>$this->alumno->id, 'presente'=>(bool)$valor]);
            }
            $indice = $this->actingAs($this->admin)->get(route('web.clases.index', ['fecha'=>'2026-10-10']));
            $indice->assertOk();
            $html = $indice->getContent();
            // La última tarjeta corresponde al último ID en el mismo horario.
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xp = new \DOMXPath($dom);
            $nodos = $xp->query('//div[contains(concat(" ",normalize-space(@class)," ")," alumno-card ")]');
            $card = $nodos->item($nodos->length-1);
            $this->assertNotNull($card);
            $this->assertStringContainsString($etiqueta, $card->textContent);
            $ficha = $this->get(route('web.clases.show', $clase->id))->assertOk();
            $this->assertSame($valor !== '', $ficha->viewData('correccionAsistencia'));
            $this->assertCount($valor === '' ? 0 : 1, $ficha->viewData('asistenciasMap'));
            if ($valor === '') $ficha->assertDontSee('Estás corrigiendo una lista ya tomada');
            else $ficha->assertSee('Estás corrigiendo una lista ya tomada');
            $this->assertSame($valor === 1 ? 1 : 0, $ficha->viewData('asistenciasMap')->where('presente', true)->count());
            $this->guardarHtml('ficha-'.($valor === ''?'pendiente':$valor), $ficha->getContent());
            $this->registrar('estado', ['id'=>$clase->id,'etiqueta'=>$etiqueta,'lista_tomada'=>$ficha->viewData('correccionAsistencia')]);
        }
        $this->guardarHtml('listado-estados', $html);
        auth()->logout();
        $this->guardarHtml('login-control', $this->get(route('login'))->assertOk()->getContent());
    }

    public function test_consultas_no_crecen_por_tarjeta_en_hoy_y_listado(): void
    {
        $this->actingAs($this->admin);
        foreach (['2026-10-10', '2026-10-18'] as $fecha) {
            $base=$this->crearClasePasada($fecha);
            Asistencia::create(['clase_id'=>$base->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        }
        $medir=function () {
            \Illuminate\Support\Facades\DB::enableQueryLog();
            \Illuminate\Support\Facades\DB::flushQueryLog();
            $r=$this->get(route('web.clases.index',['fecha'=>'2026-10-10']))->assertOk();
            $queries=\Illuminate\Support\Facades\DB::getQueryLog();
            \Illuminate\Support\Facades\DB::disableQueryLog();
            return [count($queries),$queries,$r];
        };
        $medir(); // Calentar resolución de rutas y vistas antes de comparar.
        [$antes,$qa]=$medir();
        foreach (['2026-10-10','2026-10-18'] as $fecha) {
            for ($i=0;$i<7;$i++) {
                $c=$this->crearClasePasada($fecha);
                Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumno->id,'presente'=>(bool)($i%2)]);
            }
        }
        [$despues,$qd,$resp]=$medir();
        $this->assertCount(8,$resp->viewData('clasesHoy'));
        $this->assertCount(8,$resp->viewData('clasesFiltradas'));
        $this->registrar('consultas', ['antes'=>$antes,'despues'=>$despues,'hoy'=>8,'listado'=>8,'sql_antes'=>$qa,'sql_despues'=>$qd]);
        $this->assertSame($antes,$despues);
    }

    public static function cerradas(): array
    {
        return ['hora_pendiente'=>['HORA','PENDIENTE'],'hora_pagada'=>['HORA','PAGADA'],
            'comision_pendiente'=>['COMISION','PENDIENTE'],'comision_pagada'=>['COMISION','PAGADA']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cerradas')]
    public function test_cerradas_bloquean_json_y_formulario_y_conservan_filas(string $tipo,string $pago): void
    {
        $this->profesor->deporte->update(['tipo_liquidacion'=>$tipo]);
        $this->profesor->update(['porcentaje_comision'=>20]);
        $clase=$this->crearClasePasada('2026-09-10');
        Asistencia::create(['clase_id'=>$clase->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        $liq=\App\Models\Liquidacion::create(['profesor_id'=>$this->profesor->id,'mes'=>9,'anio'=>2026,
            'tipo'=>$tipo,'estado'=>'ABIERTA','estado_pago'=>$pago,'total_calculado'=>1000]);
        if ($tipo==='HORA') {
            \App\Models\LiquidacionDetalle::create(['liquidacion_id'=>$liq->id,'tipo_referencia'=>'clase',
                'referencia_id'=>$clase->id,'monto'=>1000,'minutos'=>60]);
        } else {
            \App\Models\LiquidacionDetalle::create(['liquidacion_id'=>$liq->id,'tipo_referencia'=>'alumno',
                'referencia_id'=>$this->alumno->id,'monto'=>1000]);
        }
        app(LiquidacionService::class)->cerrarLiquidacion($liq->id);
        $this->assertTrue($clase->fresh()->integraLiquidacionCerrada());
        $this->actingAs($this->admin);
        foreach ([true,false] as $validada) {
            $clase->refresh()->update(['validada_para_liquidacion'=>$validada]); // Preparación sintética, sin ruta.
            $antes=$clase->fresh()->getAttributes();
            $economia=$liq->fresh()->getAttributes();
            $detalle=$liq->detalles()->sole()->getAttributes();
            foreach (['json','form'] as $formato) {
                $url=route('web.clases.toggle-validada',$clase->id);
                if ($formato==='json') $this->patchJson($url)->assertStatus(422)->assertJson(['success'=>false]);
                else $this->from(route('web.clases.show',$clase->id))->patch($url)->assertRedirect()->assertSessionHas('error');
                $this->assertSame($antes,$clase->fresh()->getAttributes());
                $this->assertSame($economia,$liq->fresh()->getAttributes());
                $this->assertSame($detalle,$liq->detalles()->sole()->getAttributes());
                $this->assertDatabaseHas('clases',['id'=>$clase->id,'validada_para_liquidacion'=>$validada]);
            }
            if ($validada) {
                $r=$this->get(route('web.clases.index',['estado'=>'sin_presentes']))->assertOk();
                $this->assertFalse($r->viewData('clasesFiltradas')->pluck('id')->contains($clase->id));
            }
            $this->registrar('cerrada', ['tipo'=>$tipo,'pago'=>$pago,'antes'=>$antes,'despues'=>$clase->fresh()->getAttributes(),
                'liquidacion'=>$liq->fresh()->getAttributes(),'detalle'=>$liq->detalles()->sole()->getAttributes()]);
        }
    }

    public static function tipos(): array
    {
        return ['hora'=>['HORA'], 'comision'=>['COMISION']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tipos')]
    public function test_sin_cerrada_permite_validar_y_desvalidar(string $tipo): void
    {
        $this->profesor->deporte->update(['tipo_liquidacion'=>$tipo]);
        $this->profesor->update(['porcentaje_comision'=>20]);
        $clase=$this->crearClasePasada('2026-09-10');
        $this->actingAs($this->admin);
        foreach (['sin_liquidacion','ABIERTA','CANCELADA'] as $estado) {
            if ($estado!=='sin_liquidacion') {
                $liq=\App\Models\Liquidacion::create(['profesor_id'=>$this->profesor->id,'mes'=>9,'anio'=>2026,
                    'tipo'=>$tipo,'estado'=>'ABIERTA','total_calculado'=>1000]);
                \App\Models\LiquidacionDetalle::create(['liquidacion_id'=>$liq->id,'tipo_referencia'=>$tipo==='HORA'?'clase':'alumno',
                    'referencia_id'=>$tipo==='HORA'?$clase->id:$this->alumno->id,'monto'=>1000,'minutos'=>$tipo==='HORA'?60:null]);
                if ($estado==='CANCELADA') $liq->update(['estado'=>'CANCELADA']);
            }
            $this->assertFalse($clase->fresh()->integraLiquidacionCerrada());
            foreach ([true,false] as $esperado) {
                $this->patch(route('web.clases.toggle-validada',$clase->id))->assertRedirect()->assertSessionHas('success');
                $this->assertSame($esperado,$clase->fresh()->validada_para_liquidacion);
            }
            if (isset($liq) && $estado==='ABIERTA') $liq->delete();
        }
    }

    public function test_permisos_y_campo_inyectado_por_edicion(): void
    {
        $clase=$this->crearClasePasada();
        foreach ([$this->operativo,$this->profesorUser] as $usuario) {
            $this->actingAs($usuario)->patchJson(route('web.clases.toggle-validada',$clase->id))->assertForbidden();
            $this->assertFalse($clase->fresh()->validada_para_liquidacion);
            $this->get(route('web.clases.index'))->assertOk();
            $this->get(route('web.clases.show',$clase->id))->assertOk();
        }
        $this->actingAs($this->admin)->put(route('web.clases.update',$clase->id),[
            'fecha'=>'2026-10-10','hora_inicio'=>'10:00','hora_fin'=>'11:00','validada_para_liquidacion'=>true,
        ])->assertRedirect();
        $this->assertFalse($clase->fresh()->validada_para_liquidacion);
        auth()->logout();
        $this->patch(route('web.clases.toggle-validada',$clase->id))->assertRedirect(route('login'));
        $this->assertFalse($clase->fresh()->validada_para_liquidacion);
        $inactivo=User::factory()->create(['rol'=>'ADMIN']);
        $inactivo->forceFill(['activo'=>false])->save();
        $this->actingAs($inactivo)->patch(route('web.clases.toggle-validada',$clase->id))->assertRedirect(route('login'));
        $this->assertFalse($clase->fresh()->validada_para_liquidacion);
    }

    public function test_incluir_hoy_no_deja_validacion_tras_rechazo_ni_altera_clase_cerrada(): void
    {
        $pasada=$this->crearClasePasada('2026-09-10');
        $pasada->update(['validada_para_liquidacion'=>true]);
        $svc=app(LiquidacionService::class);
        $liq=$svc->generarLiquidacionMensual($this->profesor->id,9,2026);
        $svc->cerrarLiquidacion($liq->id);
        $hoy=$this->crearClasePasada('2026-10-18');
        $this->assertFalse($hoy->fresh()->validada_para_liquidacion);
        $this->actingAs($this->admin)->post(route('web.liquidaciones.store'),[
            'profesor_id'=>$this->profesor->id,'mes'=>9,'anio'=>2026,'incluir_hoy'=>1,
        ])->assertRedirect()->assertSessionHas('error');
        $this->assertTrue($pasada->fresh()->validada_para_liquidacion);
        $this->assertStringContainsString('Ya existe una liquidación', session('error'));
        $this->registrar('incluir_hoy', ['clase_cerrada_id'=>$pasada->id,'validada_cerrada'=>$pasada->fresh()->validada_para_liquidacion,
            'clase_hoy_id'=>$hoy->id,'validada_hoy'=>$hoy->fresh()->validada_para_liquidacion,'error'=>session('error')]);
        $this->assertFalse($hoy->fresh()->validada_para_liquidacion, 'Una generación rechazada no debería dejar validaciones nuevas.');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tipos')]
    public function test_rollback_real_del_mes_vigente_despues_de_intentar_update(string $tipo): void
    {
        $this->profesor->deporte->update(['tipo_liquidacion'=>$tipo]);
        $this->profesor->update(['porcentaje_comision'=>20]);
        $existente=app(LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id,10,2026);
        $hoy=$this->crearClasePasada('2026-10-18');
        Asistencia::create(['clase_id'=>$hoy->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        $antes=$hoy->fresh()->getAttributes();
        $liqAntes=$existente->fresh()->getAttributes();
        $detalles=$existente->detalles()->get()->toArray();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        \Illuminate\Support\Facades\DB::flushQueryLog();
        $this->actingAs($this->admin)->post(route('web.liquidaciones.store'),[
            'profesor_id'=>$this->profesor->id,'mes'=>10,'anio'=>2026,'incluir_hoy'=>1,
        ])->assertRedirect()->assertSessionHas('error');
        $sql=\Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();
        $updates=array_values(array_filter($sql,fn($q)=>str_starts_with(strtolower($q['query']),'update `clases`') && str_contains($q['query'],'validada_para_liquidacion')));
        $this->assertCount(1,$updates,'La prueba debe entrar a la escritura, antes de comprobar el rollback.');
        $this->assertStringContainsString('Ya existe una liquidación',session('error'));
        $this->assertSame($antes,$hoy->fresh()->getAttributes());
        $this->assertSame($liqAntes,$existente->fresh()->getAttributes());
        $this->assertSame($detalles,$existente->detalles()->get()->toArray());
        $this->assertSame(1,\App\Models\Liquidacion::where('profesor_id',$this->profesor->id)->count());
        $this->registrar('rollback_real',['tipo'=>$tipo,'update_intentado'=>$updates,'clase_antes'=>$antes,'clase_despues'=>$hoy->fresh()->getAttributes(),'error'=>session('error')]);
    }

    public function test_badge_se_refresca_despues_de_validar_y_desvalidar_por_web(): void
    {
        $c=$this->crearClasePasada();
        $this->actingAs($this->admin);
        $this->get(route('web.clases.index'))->assertOk()->assertViewHas('badgeClasesPendientes',1);
        $this->patch(route('web.clases.toggle-validada',$c->id))->assertRedirect()->assertSessionHas('success');
        $this->assertTrue($c->fresh()->validada_para_liquidacion);
        $this->get(route('web.clases.index'))->assertOk()->assertViewHas('badgeClasesPendientes',0);
        $this->patch(route('web.clases.toggle-validada',$c->id))->assertRedirect()->assertSessionHas('success');
        $this->assertFalse($c->fresh()->validada_para_liquidacion);
        $this->get(route('web.clases.index'))->assertOk()->assertViewHas('badgeClasesPendientes',1);
        $this->registrar('badge_fresco',['secuencia'=>[1,0,1],'clase_id'=>$c->id]);
    }

    public function test_incluir_hoy_valida_solo_candidatas_y_conserva_las_otras(): void
    {
        $candidata=$this->crearClasePasada('2026-10-18');
        $cancelada=$this->crearClasePasada('2026-10-18');$cancelada->update(['cancelada'=>true]);
        $presente=$this->crearClasePasada('2026-10-18');
        Asistencia::create(['clase_id'=>$presente->id,'alumno_id'=>$this->alumno->id,'presente'=>true]);
        $validada=$this->crearClasePasada('2026-10-18');$validada->update(['validada_para_liquidacion'=>true]);
        $futura=$this->crearClasePasada('2026-10-19');
        $ayer=$this->crearClasePasada('2026-10-17');
        $ajena=$this->crearClasePasada('2026-10-18');$ajena->profesores()->detach($this->profesor->id);
        $otra=$this->profesor->replicate();$otra->dni='30999888';$otra->nombre='Otra';$otra->save();
        $ajena->profesores()->attach($otra->id);
        $this->actingAs($this->admin)->post(route('web.liquidaciones.store'),[
            'profesor_id'=>$this->profesor->id,'mes'=>10,'anio'=>2026,'incluir_hoy'=>1,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertTrue($candidata->fresh()->validada_para_liquidacion);
        $this->assertTrue($validada->fresh()->validada_para_liquidacion);
        foreach([$cancelada,$presente,$futura,$ayer,$ajena] as $c) $this->assertFalse($c->fresh()->validada_para_liquidacion);
        $liq=\App\Models\Liquidacion::where('profesor_id',$this->profesor->id)->sole();
        $this->assertEqualsCanonicalizing([$candidata->id,$presente->id,$validada->id],$liq->detalles()->pluck('referencia_id')->all());
        $this->registrar('incluir_hoy_exito',['validadas'=>[$candidata->id,$validada->id],'detalle'=>$liq->detalles()->get()->toArray()]);
    }

}
