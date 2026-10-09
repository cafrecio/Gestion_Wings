<?php

namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Liquidacion, Nivel, Pago, Profesor, User};
use App\Services\ReporteSueldosService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Tests\TestCase;

class ReporteSueldosTest extends TestCase
{
    use RefreshDatabase;
    private array $profes = [];
    private array $alumnos = [];
    private Pago $pago;
    private Grupo $grupo;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-01 10:00:00');
        DB::table('reporte_analitico_cobertura')->where('id',1)->update(['desde'=>'2026-10-01 00:00:00']);
        DB::table('primera_carga')->where('id',1)->update(['estado'=>'TERMINADA']);
        $this->admin = User::factory()->create(['rol'=>'ADMIN','activo'=>true]);
        $d = Deporte::create(['nombre'=>'Comisión prueba','tipo_liquidacion'=>'COMISION','activo'=>true]);
        $n = Nivel::create(['nombre'=>'Nivel prueba']);
        $this->grupo = Grupo::create(['deporte_id'=>$d->id,'nivel_id'=>$n->id,'activo'=>true]);
        $plan = GrupoPlan::create(['grupo_id'=>$this->grupo->id,'clases_por_semana'=>2,'precio_mensual'=>100.01,'activo'=>true]);
        foreach ([20,30] as $i=>$porcentaje) $this->profes[] = Profesor::create(['deporte_id'=>$d->id,'nombre'=>'Docente '.$i,'apellido'=>'Prueba',
            'dni'=>(string)(31000100+$i),'fecha_nacimiento'=>'1980-01-01','direccion'=>'Prueba','localidad'=>'Prueba','porcentaje_comision'=>$porcentaje,'activo'=>true]);
        foreach ([100.01,80,300] as $i=>$monto) {
            $a = Alumno::create(['nombre'=>'Alumno '.$i,'apellido'=>'Prueba','dni'=>(string)(30000100+$i),'fecha_nacimiento'=>'2000-01-01',
                'celular'=>'11-4000-0000','deporte_id'=>$d->id,'grupo_id'=>$this->grupo->id,'fecha_alta'=>'2026-10-01','activo'=>true]);
            DeudaCuota::create(['alumno_id'=>$a->id,'periodo'=>'2026-10','monto_original'=>$monto,'monto_pagado'=>0,'estado'=>'PENDIENTE']);
            $this->alumnos[] = $a;
        }
        $this->pago = Pago::create(['alumno_id'=>$this->alumnos[0]->id,'plan_id'=>$plan->id,'mes'=>10,'anio'=>2026,
            'monto_base'=>40.03,'porcentaje_aplicado'=>100,'monto_final'=>40.03,'monto_cuota'=>40.03,'fecha_pago'=>'2026-10-02','estado'=>'COMPLETADO']);
        foreach ([[0,[0,1]], [0,[0,1]], [0,[0]], [1,[0]]] as $i=>[$p,$alumnos]) {
            $c = Clase::create(['grupo_id'=>$this->grupo->id,'fecha'=>'2026-10-0'.($i+2),'hora_inicio'=>'10:00','hora_fin'=>'11:00','cancelada'=>false]);
            $c->profesores()->attach($this->profes[$p]->id);
            foreach ($alumnos as $a) Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumnos[$a]->id,'presente'=>true]);
        }
        Carbon::setTestNow('2026-10-09 18:00:00');
    }

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    private function reporte(): array { return app(ReporteSueldosService::class)->obtener('2026-10'); }

    public function test_reparte_cuota_real_por_asistencias_sin_dividir_la_comision(): void
    {
        $r = $this->reporte();
        $p = collect($r['profesores'])->keyBy('id');
        $this->assertSame(15501,$p[$this->profes[0]->id]['ingreso']);
        $this->assertSame(2500,$p[$this->profes[1]->id]['ingreso']);
        $this->assertSame(801,$p[$this->profes[0]->id]['costo']);
        $this->assertSame(1201,$p[$this->profes[1]->id]['costo']);
        $this->assertSame(18001,$r['ingreso']);
        $this->assertSame(2002,$r['costo']);
        $this->assertSame(6,$r['asistencias']);
        $this->assertSame(334,$r['costo_por_asistencia']);
        $this->assertSame(30000,$r['sin_asistencia']);
    }

    public function test_pago_parcial_es_cobro_y_sin_pago_cuenta_cada_asistencia(): void
    {
        $p = collect($this->reporte()['profesores'])->keyBy('id')[$this->profes[0]->id];
        $this->assertSame(1,$p['alumnos_con_pago']);
        $this->assertSame(2,$p['asistencias_sin_pago']);
        $this->assertSame(5,$p['asistencias']);
        $this->assertSame(160,$p['costo_por_asistencia']);
        $this->alumnos[0]->update(['activo'=>false]);
        $this->assertSame($p,collect($this->reporte()['profesores'])->keyBy('id')[$this->profes[0]->id]);
    }

    public function test_falta_historia_o_cuota_no_se_convierte_en_cero(): void
    {
        $antes = app(ReporteSueldosService::class)->obtener('2026-09');
        $this->assertFalse($antes['disponible']);
        $this->assertNull($antes['costo']);
        $this->assertNull($antes['ingreso']);
        DB::table('reporte_analitico_historial')->where('tipo','CUOTA')->where('persona_id',$this->alumnos[0]->id)->delete();
        $r = $this->reporte();
        $this->assertNull($r['ingreso']);
        $this->assertNull($r['porcentaje_costo']);
    }

    public function test_canceladas_y_futuras_no_reparten_cuota(): void
    {
        $antes = $this->reporte();
        foreach ([['2026-10-30',false],['2026-10-02',true],['2026-10-09',false]] as [$fecha,$cancelada]) {
            $c = Clase::create(['grupo_id'=>$this->grupo->id,'fecha'=>$fecha,'hora_inicio'=>'20:00','hora_fin'=>'21:00','cancelada'=>$cancelada]);
            $c->profesores()->attach($this->profes[1]->id);
            Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$this->alumnos[0]->id,'presente'=>true]);
        }
        $this->assertSame($antes,$this->reporte());
    }

    public function test_mes_cerrado_conserva_tarifa_y_cobro_antes_de_la_anulacion(): void
    {
        Carbon::setTestNow('2026-11-02 18:00:00');
        $s = app(ReporteSueldosService::class);
        $antes = $s->obtener('2026-10');
        $this->pago->update(['estado'=>'ANULADO']);
        $this->profes[0]->update(['porcentaje_comision'=>80]);
        $this->assertSame($antes,$s->obtener('2026-10'));
    }

    public function test_liquidacion_ajustada_prevalece_sin_duplicar_abierta_y_cerrada(): void
    {
        $l = Liquidacion::create(['profesor_id'=>$this->profes[0]->id,'mes'=>10,'anio'=>2026,'tipo'=>'COMISION','total_calculado'=>8.01,'estado'=>'CERRADA']);
        Liquidacion::create(['profesor_id'=>$this->profes[0]->id,'mes'=>10,'anio'=>2026,'tipo'=>'COMISION','total_calculado'=>8.01,'estado'=>'ABIERTA']);
        $l->ajustarMontoFinal('20.00',$this->admin->id,'Acuerdo del mes');
        $p = collect($this->reporte()['profesores'])->keyBy('id')[$this->profes[0]->id];
        $this->assertSame(2000,$p['costo']);
        $this->assertSame('Liquidación cerrada',$p['base']);
    }

    public function test_ruta_filtros_roles_y_consulta_sin_escrituras(): void
    {
        Notification::fake();
        $tablas = ['deuda_cuotas','pagos','liquidaciones','liquidacion_ajustes','reporte_analitico_historial'];
        $antes = array_map(fn ($t) => DB::table($t)->orderBy('id')->get()->toJson(),$tablas);
        $this->actingAs($this->admin)->get(route('web.reportes.sueldos'))->assertOk()->assertSee('Alumnos con pagos registrados')->assertSee('Asistencias sin pago de cuota');
        $this->actingAs($this->admin)->getJson(route('web.reportes.sueldos',['mes'=>'2026-13']))->assertUnprocessable();
        $this->actingAs($this->admin)->getJson(route('web.reportes.sueldos',['deporte_id'=>999999]))->assertUnprocessable();
        $this->assertSame($antes,array_map(fn ($t) => DB::table($t)->orderBy('id')->get()->toJson(),$tablas));
        Notification::assertNothingSent();
        foreach (['OPERATIVO','PROFESOR'] as $rol) $this->actingAs(User::factory()->create(['rol'=>$rol,'activo'=>true]))->get(route('web.reportes.sueldos'))->assertForbidden();
        $this->actingAs($this->admin);
        DB::table('primera_carga')->where('id',1)->update(['estado'=>'PENDIENTE']);
        $this->get(route('web.reportes.sueldos'))->assertRedirect(route('web.primera-carga.index'));
    }

    public function test_cambiar_deporte_del_profesor_no_mueve_su_actividad_anterior(): void
    {
        $antes = $this->reporte();
        $otro = Deporte::create(['nombre'=>'Otro deporte','tipo_liquidacion'=>'COMISION','activo'=>true]);
        $this->profes[0]->update(['deporte_id'=>$otro->id]);
        $r = $this->reporte();
        $this->assertSame($antes,$r);
        $f = app(ReporteSueldosService::class)->obtener('2026-10',$this->grupo->deporte_id);
        $this->assertSame(0,$f['asistencias_sin_cuota']);
        $this->assertSame(18001,$f['ingreso']);
    }

    public function test_clase_con_dos_profesores_no_duplica_asistencia_global(): void
    {
        $c = Clase::whereDate('fecha','2026-10-04')->firstOrFail();
        $c->profesores()->attach($this->profes[1]->id);
        $r = $this->reporte();
        $this->assertSame(6,$r['asistencias']);
        $this->assertSame(18001,$r['ingreso']);
        $this->assertSame(7,array_sum(array_column($r['profesores'],'asistencias')));
        $this->assertSame(6,$r['deportes'][0]['asistencias']);
        $this->assertSame(2002,$r['costo']);
    }

    public function test_hora_usa_duracion_real_y_no_inventa_clases_previas_al_historial(): void
    {
        $d = Deporte::create(['nombre'=>'Por hora','tipo_liquidacion'=>'HORA','activo'=>true]);
        $p = Profesor::create(['deporte_id'=>$d->id,'nombre'=>'Hora','apellido'=>'Prueba','dni'=>'31999999',
            'fecha_nacimiento'=>'1980-01-01','direccion'=>'Prueba','localidad'=>'Prueba','valor_hora'=>100,'activo'=>true]);
        $g = Grupo::create(['deporte_id'=>$d->id,'nivel_id'=>$this->grupo->nivel_id,'activo'=>true]);
        $c = Clase::create(['grupo_id'=>$g->id,'fecha'=>'2026-10-09','hora_inicio'=>'10:00','hora_fin'=>'11:30',
            'validada_para_liquidacion'=>true,'cancelada'=>false]);
        $c->profesores()->attach($p->id);
        $r = app(ReporteSueldosService::class)->obtener('2026-10',$d->id);
        $this->assertSame(15000,$r['costo']);
        $this->assertNull($r['costo_por_asistencia']);
        DB::table('reporte_analitico_cobertura')->where('id',1)->update(['desde'=>'2026-10-09 12:00:00']);
        $r = app(ReporteSueldosService::class)->obtener('2026-10',$d->id);
        $this->assertNull($r['costo']);
        $this->assertSame('Sin historial',$r['profesores'][0]['base']);
    }
}
