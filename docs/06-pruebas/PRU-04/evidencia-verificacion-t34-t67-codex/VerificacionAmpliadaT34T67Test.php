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

class VerificacionAmpliadaT34T67Test extends TestCase
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
            throw new \RuntimeException('Auditoria exclusiva de testing/wings_testing_codex.');
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

    private function ids($response): array
    {
        $response->assertOk();
        return $response->viewData('clasesFiltradas')->pluck('id')->sort()->values()->all();
    }

    private function estado(string $estado, ?User $user = null, array $otros = [])
    {
        return $this->actingAs($user ?? $this->admin)->get(route('web.clases.index', ['estado' => $estado] + $otros));
    }

    private function anotar(string $caso, array $datos): void
    {
        file_put_contents(__DIR__.'/observaciones.jsonl', json_encode(['caso' => $caso] + $datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND);
    }

    private function pendientes(int $esperados): void
    {
        $this->estado('finalizada')->assertViewHas('badgeClasesPendientes', $esperados);
        $this->assertSame($esperados, app(\App\Services\ReporteMensualService::class)->avisos()['asistencia']);
        $p = $this->actingAs($this->admin)->get(route('web.liquidaciones.create'))->assertOk()->viewData('profesores')->firstWhere('id', $this->profesor->id);
        $this->assertSame($esperados, (int) $p->clases_sin_asistencia);
    }

    private function otroAlumno(string $dni = '43123457'): Alumno
    {
        $a = $this->alumno->replicate();
        $a->dni = $dni;
        $a->save();
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => GrupoPlan::sole()->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        return $a;
    }

    public function test_auditoria_matriz_sincronizada_incluye_clases_antiguas_y_limite_del_dia(): void
    {
        $sin = $this->crearClasePasada();
        $vieja = $this->crearClasePasada('2026-01-01');
        $ausente = $this->crearClasePasada('2026-10-11');
        Asistencia::create(['clase_id' => $ausente->id, 'alumno_id' => $this->alumno->id, 'presente' => false]);
        $presente = $this->crearClasePasada('2026-10-12');
        Asistencia::create(['clase_id' => $presente->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);
        $validada = $this->crearClasePasada('2026-10-13');
        $validada->update(['validada_para_liquidacion' => true]);
        $cancelada = $this->crearClasePasada('2026-10-14');
        $cancelada->update(['cancelada' => true, 'motivo_cancelacion' => 'Prueba']);
        $hoy = $this->crearClasePasada('2026-10-18', '08:00', '09:00');
        $futura = $this->crearClasePasada('2026-10-19');
        $this->pendientes(2);
        $esperados = [$sin->id, $vieja->id]; sort($esperados);
        foreach ([$this->admin, $this->operativo, $this->profesorUser] as $u) {
            $this->assertSame($esperados, $this->ids($this->estado('finalizada', $u)));
            $this->actingAs($u)->get(route('web.clases.show', $vieja->id))->assertOk();
        }
        $this->assertSame([$ausente->id], $this->ids($this->estado('sin_presentes')));
        $cerradas = $this->ids($this->estado('cerrada'));
        $this->assertContains($presente->id, $cerradas); $this->assertContains($validada->id, $cerradas);
        $this->assertNotContains($hoy->id, $this->ids($this->estado('finalizada')));
        $this->assertNotContains($futura->id, $this->ids($this->estado('sin_presentes')));
        $this->anotar('matriz', ['sin_lista' => $esperados, 'todos_ausentes' => $ausente->id, 'pasada_antigua_accesible_todos_roles' => true]);
    }

    public function test_auditoria_paginacion_y_filtros_combinados_no_pierden_clases(): void
    {
        $ids=[];
        for ($i=1;$i<=25;$i++) $ids[]=$this->crearClasePasada(sprintf('2026-09-%02d',$i))->id;
        $this->pendientes(25);
        $p1=$this->estado('finalizada'); $p2=$this->estado('finalizada',null,['page'=>2]);
        $this->assertSame(25,$p1->viewData('clasesFiltradas')->total());
        $todos=array_merge($this->ids($p1),$this->ids($p2));sort($todos);sort($ids);$this->assertSame($ids,$todos);
        $p1->assertSee('estado=finalizada');
        $filtrado=$this->estado('finalizada',null,['fecha'=>'2026-09-01','grupo_id'=>$this->grupo->id,'profesor_id'=>$this->profesor->id,'deporte_id'=>$this->grupo->deporte_id]);
        $this->assertCount(1,$this->ids($filtrado));
    }

    public function test_auditoria_filtro_exclusivo_compara_ids_reales_para_cada_rol(): void
    {
        $a=$this->crearClasePasada();Asistencia::create(['clase_id'=>$a->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        $b=$this->crearClasePasada('2026-10-11');Asistencia::create(['clase_id'=>$b->id,'alumno_id'=>$this->alumno->id,'presente'=>true]);
        $this->crearClasePasada('2026-10-19');
        $this->crearClasePasada('2026-01-01');
        $this->assertSame([$a->id],$this->ids($this->estado('sin_presentes')));
        foreach ([$this->operativo,$this->profesorUser] as $u) {
            $r=$this->estado('sin_presentes',$u);$r->assertDontSee('value="sin_presentes"',false);
            $this->assertSame($this->ids($this->estado('desconocido',$u)),$this->ids($r));
            $this->assertContains($b->id,$this->ids($r));
        }
    }

    public function test_auditoria_post_real_todos_ausentes_actualiza_los_cuatro_consumidores(): void
    {
        $c=$this->crearClasePasada();$b=$this->otroAlumno();$this->pendientes(1);
        $this->actingAs($this->operativo)->postJson(route('web.clases.asistencias',$c->id),['items'=>[
            ['alumno_id'=>$this->alumno->id,'presente'=>0],['alumno_id'=>$b->id,'presente'=>0]
        ]])->assertOk()->assertJson(['success'=>true]);
        $this->assertSame(2,$c->asistencias()->count());$this->assertSame(0,$c->asistencias()->where('presente',true)->count());
        $this->pendientes(0);$this->assertSame([$c->id],$this->ids($this->estado('sin_presentes')));
        $this->assertFalse($c->fresh()->esLiquidable());
        $this->assertSame(0.0,(float)app(LiquidacionService::class)->previsualizarLiquidacion($this->profesor->id,10,2026)['total_estimado']);
    }

    public function test_auditoria_corregir_ausente_presente_y_ausente_exige_motivo_y_reclasifica(): void
    {
        $c=$this->crearClasePasada();Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        $payload=['items'=>[['alumno_id'=>$this->alumno->id,'presente'=>1]]];
        $this->actingAs($this->profesorUser)->postJson(route('web.clases.asistencias',$c->id),$payload)->assertStatus(422);
        $this->assertFalse($c->asistencias()->sole()->presente);
        $this->postJson(route('web.clases.asistencias',$c->id),$payload+['motivo'=>'Corregir la carga'])->assertOk();
        $this->assertSame([],$this->ids($this->estado('sin_presentes')));$this->assertContains($c->id,$this->ids($this->estado('cerrada')));
        $this->assertSame(1000.0,(float)app(LiquidacionService::class)->previsualizarLiquidacion($this->profesor->id,10,2026)['total_estimado']);
        $payload['items'][0]['presente']=0;
        $this->actingAs($this->admin)->postJson(route('web.clases.asistencias',$c->id),$payload+['motivo'=>'Correccion final'])->assertOk();
        $this->assertSame([$c->id],$this->ids($this->estado('sin_presentes')));$this->pendientes(0);
    }

    public function test_auditoria_validacion_admin_sale_de_pendientes_y_genera_detalle_persistido(): void
    {
        $c=$this->crearClasePasada('2026-09-10');Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        foreach([$this->operativo,$this->profesorUser] as $u){$this->actingAs($u)->patch(route('web.clases.toggle-validada',$c->id))->assertForbidden();$this->assertFalse($c->fresh()->validada_para_liquidacion);}
        $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada',$c->id))->assertRedirect();
        $this->assertSame([],$this->ids($this->estado('sin_presentes')));
        $l=app(LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id,9,2026);
        $this->assertSame('1000.00',$l->total_calculado);$this->assertSame($c->id,(int)$l->detalles()->sole()->referencia_id);
        $this->anotar('pago_decidido_por_duenio',['clase_id'=>$c->id,'liquidacion_id'=>$l->id,'monto'=>$l->total_calculado]);
    }

    public function test_auditoria_cancelacion_y_reactivacion_no_olvidan_la_lista(): void
    {
        $c=$this->crearClasePasada();Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        $this->actingAs($this->operativo)->patchJson(route('web.clases.toggle-cancelada',$c->id),['motivo_cancelacion'=>'Prueba'])->assertOk();
        $this->assertSame([],$this->ids($this->estado('sin_presentes')));$this->pendientes(0);
        $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada',$c->id))->assertOk();
        $this->assertSame([$c->id],$this->ids($this->estado('sin_presentes')));$this->assertSame(1,$c->asistencias()->count());$this->pendientes(0);
    }

    public function test_auditoria_tanda_con_alumno_ajeno_no_toma_lista_ni_cambia_contadores(): void
    {
        $c=$this->crearClasePasada();$b=$this->otroAlumno();$g=$this->grupo->replicate();$n=Nivel::create(['nombre'=>'Otro']);$g->nivel_id=$n->id;$g->save();$b->update(['grupo_id'=>$g->id]);
        $this->actingAs($this->operativo)->postJson(route('web.clases.asistencias',$c->id),['items'=>[['alumno_id'=>$this->alumno->id,'presente'=>0],['alumno_id'=>$b->id,'presente'=>0]]])->assertStatus(422);
        $this->assertSame(0,$c->asistencias()->count());$this->pendientes(1);
    }

    public function test_auditoria_todos_los_roles_reciben_solo_sufijos_y_homonimos_se_distinguen(): void
    {
        $c=$this->crearClasePasada();$a2=$this->otroAlumno();$a3=$this->otroAlumno('00123000');
        foreach([$this->admin,$this->operativo,$this->profesorUser] as $u){
            $r=$this->actingAs($u)->get(route('web.clases.show',$c->id))->assertOk();
            foreach([$this->alumno,$a2,$a3] as $a){$r->assertSee('DNI …'.substr($a->dni,-3))->assertDontSee($a->dni);}
            $d=new \DOMDocument();@$d->loadHTML('<?xml encoding="UTF-8">'.$r->getContent());$x=new \DOMXPath($d);
            $this->assertSame(3,$x->query('//*[contains(concat(" ",normalize-space(@class)," ")," alumno-asistencia-card ")]')->length);
            $this->assertSame(3,$x->query('//*[contains(concat(" ",normalize-space(@class)," ")," alumno-asistencia-card ")]//input[@type="checkbox"]')->length);
            $this->assertSame(1,$x->query('//*[@id="btn-guardar-asistencias"]')->length);
        }
    }

    public function test_auditoria_profesor_no_accede_a_rutas_que_exponen_dni_completo(): void
    {
        $this->actingAs($this->profesorUser);
        foreach(['/alumnos','/alumnos/'.$this->alumno->id,'/alumnos/autocomplete?q=Gomez','/alumnos/'.$this->alumno->id.'/edit','/alumnos/inscripcion-preview','/alumnos/cuota-alta-preview','/caja','/caja/cobrar','/caja/cobrar/'.$this->alumno->id,'/cobranza','/profesores','/usuarios','/reportes'] as $url){
            $this->get($url)->assertForbidden()->assertDontSee($this->alumno->dni);
        }
        $this->patchJson(route('web.clases.profesores',$this->crearClasePasada()->id),['profesores'=>[$this->profesor->id]])->assertForbidden();
    }

    public function test_auditoria_dni_ausente_y_sufijo_con_ceros_no_rompen_el_formulario(): void
    {
        $c=$this->crearClasePasada();$this->alumno->update(['dni'=>'00123000']);$b=$this->otroAlumno();$b->update(['dni'=>null]);
        $r=$this->actingAs($this->profesorUser)->get(route('web.clases.show',$c->id))->assertOk();
        $r->assertSee('DNI …000')->assertDontSee('00123000')->assertDontSee('DNI …457');
    }

    public function test_auditoria_futuras_y_canceladas_rechazan_lista_sin_crear_registros(): void
    {
        $f=$this->crearClasePasada('2026-10-19');$c=$this->crearClasePasada();$c->update(['cancelada'=>true,'motivo_cancelacion'=>'Prueba']);
        foreach([$f,$c] as $clase){$this->actingAs($this->admin)->postJson(route('web.clases.asistencias',$clase->id),['items'=>[['alumno_id'=>$this->alumno->id,'presente'=>0]]])->assertStatus(422);$this->assertSame(0,$clase->asistencias()->count());}
    }

    public function test_auditoria_tarjeta_no_debe_marcar_pendiente_una_lista_ya_tomada(): void
    {
        $c=$this->crearClasePasada();Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        $r=$this->estado('sin_presentes');$d=new \DOMDocument();@$d->loadHTML('<?xml encoding="UTF-8">'.$r->getContent());$x=new \DOMXPath($d);
        $labels=$x->query('//span[contains(@class,"info-label") and normalize-space(.)="Asistencia:"]/following-sibling::span[1]');
        $this->assertSame(1,$labels->length);$label=trim($labels->item(0)->textContent);
        $this->anotar('lista_tomada_etiqueta',['asistencias'=>$c->asistencias()->count(),'etiqueta'=>$label,'filtro'=>'sin_presentes']);
        $this->assertNotSame('Pendiente',$label,'Una lista guardada no debe presentarse como pendiente de carga.');
    }

    public function test_auditoria_clase_cerrada_no_debe_reaparecer_para_decidir_pago(): void
    {
        $c=$this->crearClasePasada('2026-09-10');$c->update(['validada_para_liquidacion'=>true]);Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        $s=app(LiquidacionService::class);$l=$s->generarLiquidacionMensual($this->profesor->id,9,2026);$s->cerrarLiquidacion($l->id);
        $this->assertTrue($c->fresh()->integraLiquidacionCerrada());
        $r=$this->actingAs($this->admin)->patch(route('web.clases.toggle-validada',$c->id));
        $ids=$this->ids($this->estado('sin_presentes'));
        $this->anotar('clase_en_liquidacion_cerrada',['clase_id'=>$c->id,'http'=>$r->status(),'validada'=>$c->fresh()->validada_para_liquidacion,'reaparece_en_sin_presentes'=>in_array($c->id,$ids),'total_cerrado'=>$l->fresh()->total_calculado]);
        $this->assertNotContains($c->id,$ids,'Una clase ya liquidada no debe volver a la cola de decidir si se paga.');
    }

    public function test_auditoria_guardar_sin_items_y_lista_parcial_se_registran_como_limites(): void
    {
        $c=$this->crearClasePasada();$b=$this->otroAlumno();
        $r=$this->actingAs($this->operativo)->postJson(route('web.clases.asistencias',$c->id),['items'=>[]]);
        $this->anotar('lista_vacia',['http'=>$r->status(),'success'=>$r->json('success'),'asistencias'=>$c->asistencias()->count()]);
        $this->assertSame(0,$c->asistencias()->count());
        $this->postJson(route('web.clases.asistencias',$c->id),['items'=>[['alumno_id'=>$this->alumno->id,'presente'=>0]]])->assertOk();
        $this->anotar('lista_parcial',['alumnos_activos'=>2,'asistencias'=>$c->asistencias()->count(),'sale_del_contador'=>count($this->ids($this->estado('finalizada')))===0]);
        $this->assertSame(1,$c->asistencias()->count());
    }

    public function test_auditoria_clase_pagada_no_debe_reaparecer_para_decidir_pago(): void
    {
        $c=$this->crearClasePasada('2026-09-10');$c->update(['validada_para_liquidacion'=>true]);Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        $s=app(LiquidacionService::class);$l=$s->generarLiquidacionMensual($this->profesor->id,9,2026);$s->cerrarLiquidacion($l->id);
        // Fixture de una liquidación ya pagada; no emite pagos ni movimientos.
        $l->update(['estado_pago'=>'PAGADA']);
        $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada',$c->id))->assertRedirect();
        $ids=$this->ids($this->estado('sin_presentes'));
        $this->anotar('clase_en_liquidacion_pagada',['clase_id'=>$c->id,'estado'=>$l->fresh()->estado,'estado_pago'=>$l->fresh()->estado_pago,'validada'=>$c->fresh()->validada_para_liquidacion,'reaparece_en_sin_presentes'=>in_array($c->id,$ids),'total_conservado'=>$l->fresh()->total_calculado]);
        $this->assertNotContains($c->id,$ids,'Una clase en una liquidación pagada no debe volver a la cola de decisión.');
    }

    public function test_auditoria_anonimo_e_inactivo_no_pueden_leer_el_dni(): void
    {
        $c=$this->crearClasePasada();
        $this->get(route('web.clases.show',$c->id))->assertRedirect(route('login'));
        $this->profesorUser->forceFill(['activo'=>false])->save();
        $this->assertFalse($this->profesorUser->fresh()->activo);
        $this->actingAs($this->profesorUser)->get(route('web.clases.show',$c->id))->assertRedirect(route('login'))->assertDontSee($this->alumno->dni);
    }

    public function test_auditoria_capturas_reales_para_comprobar_interaccion_y_etiqueta(): void
    {
        $c=$this->crearClasePasada();$b=$this->otroAlumno();
        Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumno->id,'presente'=>false]);
        Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$b->id,'presente'=>false]);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8734');
        $pages=[];
        foreach(['sin-presentes'=>[route('web.clases.index',['estado'=>'sin_presentes']),$this->admin],
                 'asistencia-profesor'=>[route('web.clases.show',$c->id),$this->profesorUser]] as $slug=>[$url,$user]) {
            $this->flushSession();\Illuminate\Support\Facades\Auth::forgetGuards();
            $r=$this->actingAs($user)->get($url)->assertOk();$html=str_replace("\r\n","\n",$r->getContent());
            if(!is_dir(__DIR__.'/html'))mkdir(__DIR__.'/html',0777,true);
            file_put_contents(__DIR__.'/html/'.$slug.'.html',$html);
            $pages[]=['slug'=>$slug,'estado'=>$r->status(),'sha256'=>hash('sha256',$html),'normalizacion'=>'CRLF a LF'];
        }
        $this->flushSession();\Illuminate\Support\Facades\Auth::forgetGuards();
        $r=$this->get(route('login'))->assertOk();$html=str_replace("\r\n","\n",$r->getContent());
        file_put_contents(__DIR__.'/html/login.html',$html);
        $pages[]=['slug'=>'login','estado'=>$r->status(),'sha256'=>hash('sha256',$html),'normalizacion'=>'CRLF a LF'];
        file_put_contents(__DIR__.'/respuestas.json',json_encode($pages,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}
