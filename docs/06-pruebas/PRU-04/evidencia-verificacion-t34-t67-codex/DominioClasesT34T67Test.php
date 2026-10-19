<?php
namespace Tests\Feature;
require_once __DIR__.'/VerificacionAmpliadaT34T67Test.php';
use App\Models\{Profesor,User,Clase,Grupo,Alumno};
use App\Services\ReporteMensualService;

class DominioClasesT34T67Test extends VerificacionAmpliadaT34T67Test
{
    public function test_dominio_dos_profesores_cuentan_sin_duplicar_y_profesor_ve_otra_clase(): void
    {
        $p1=Profesor::sole();$p2=$p1->replicate();$p2->dni='30222333';$p2->apellido='Otro';$p2->save();
        $g=Grupo::sole();$a=Alumno::sole();
        $c1=Clase::create(['grupo_id'=>$g->id,'fecha'=>'2026-10-10','hora_inicio'=>'10:00','hora_fin'=>'11:00','cancelada'=>false,'validada_para_liquidacion'=>false]);
        $c1->profesores()->attach([$p1->id,$p2->id]);
        $c2=Clase::create(['grupo_id'=>$g->id,'fecha'=>'2026-10-11','hora_inicio'=>'10:00','hora_fin'=>'11:00','cancelada'=>false,'validada_para_liquidacion'=>false]);
        $c2->profesores()->attach($p2->id);
        $admin=User::where('rol','ADMIN')->sole();$prof=User::where('rol','PROFESOR')->sole();
        $r=$this->actingAs($admin)->get(route('web.clases.index',['estado'=>'finalizada']))->assertOk()->assertViewHas('badgeClasesPendientes',2);
        $this->assertSame(2,$r->viewData('clasesFiltradas')->total());
        $this->assertSame(2,app(ReporteMensualService::class)->avisos()['asistencia']);
        $profes=$this->get(route('web.liquidaciones.create'))->assertOk()->viewData('profesores');
        $this->assertSame(1,(int)$profes->firstWhere('id',$p1->id)->clases_sin_asistencia);
        $this->assertSame(2,(int)$profes->firstWhere('id',$p2->id)->clases_sin_asistencia);
        $this->actingAs($prof)->get(route('web.clases.show',$c2->id))->assertOk()->assertSee('DNI …456')->assertDontSee($a->dni);
        $this->assertFalse($c2->profesores->contains('id',$prof->profesor_id));
    }
    public function test_dominio_desmarcar_clase_cerrada_conserva_filas_y_total_economico(): void
    {
        $p=Profesor::sole();$a=Alumno::sole();$g=Grupo::sole();$admin=User::where('rol','ADMIN')->sole();
        $c=Clase::create(['grupo_id'=>$g->id,'fecha'=>'2026-09-10','hora_inicio'=>'10:00','hora_fin'=>'11:00','cancelada'=>false,'validada_para_liquidacion'=>true]);
        $c->profesores()->attach($p->id);
        \App\Models\Asistencia::create(['clase_id'=>$c->id,'alumno_id'=>$a->id,'presente'=>false]);
        $service=app(\App\Services\LiquidacionService::class);$l=$service->generarLiquidacionMensual($p->id,9,2026);$service->cerrarLiquidacion($l->id);
        $antes=$l->detalles()->sole()->getAttributes();$total=$l->fresh()->total_calculado;
        $observaciones=[];
        foreach(['PENDIENTE','PAGADA'] as $estado){
            // Preparar dos variantes de fixture; el cambio bajo prueba es el PATCH.
            $c->refresh()->update(['validada_para_liquidacion'=>true]);$l->update(['estado_pago'=>$estado]);
            $this->actingAs($admin)->patch(route('web.clases.toggle-validada',$c->id))->assertRedirect();
            $this->assertFalse($c->fresh()->validada_para_liquidacion);
            $detalle=$l->detalles()->sole();
            $this->assertSame($antes,$detalle->getAttributes());$this->assertSame($total,$l->fresh()->total_calculado);
            $observaciones[]=['estado_pago'=>$estado,'clase_id'=>$c->id,'validada'=>$c->fresh()->validada_para_liquidacion,'detalle'=>$detalle->only(['id','liquidacion_id','referencia_id','tipo_referencia','monto']),'total'=>$l->fresh()->total_calculado];
        }
        file_put_contents(__DIR__.'/detalle-economico.json',json_encode($observaciones,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }

}
