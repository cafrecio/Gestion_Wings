<?php
namespace Tests\Feature;

use App\Models\{Alumno,AlumnoPlan,Clase,DeudaCuota,Grupo,GrupoPlan,Nivel,Profesor,User,TipoCaja,CashflowMovimiento,Subrubro,MovimientoOperativo};
use Illuminate\Support\Facades\DB;
require_once getcwd().'/tests/Feature/SaldoYAnulacionCoherentesTest.php';

/** Escenario documental fuera de suite permanente; solo base descartable Codex. */
class PrepararPropuestasA6A10Test extends SaldoYAnulacionCoherentesTest
{
    public function test_prepara_pantallas_de_estres(): void
    {
        $this->assertSame('wings_testing_codex',DB::connection()->getDatabaseName());
        // El ensayo base congela octubre 6; la navegación real necesita la caja de hoy.
        \Illuminate\Support\Carbon::setTestNow();
        $campos=[];
        foreach(['admin','patin','futbol','tipoCaja'] as $campo) $campos[$campo]=(new \ReflectionProperty(SaldoYAnulacionCoherentesTest::class,$campo))->getValue($this);
        extract($campos);
        $admin->update(['name'=>'ADMIN de prueba','email'=>'celular.admin@example.test']);
        $operativo=User::factory()->create(['name'=>'OPERATIVO de prueba','email'=>'celular.operativo@example.test','rol'=>'OPERATIVO','activo'=>true]);
        $prof=Profesor::create(['deporte_id'=>$patin->deporte_id,'nombre'=>'Mariana','apellido'=>'Gómez de prueba','dni'=>'28999888','fecha_nacimiento'=>'1990-05-15','direccion'=>'Dirección ficticia de prueba','localidad'=>'Localidad ficticia','valor_hora'=>1000,'activo'=>true]);
        $profesor=User::factory()->create(['name'=>'PROFESOR de prueba','email'=>'celular.profesor@example.test','rol'=>'PROFESOR','profesor_id'=>$prof->id,'activo'=>true]);
        $caja=\Tests\Support\CajaDeclarada::crear($operativo->id,$tipoCaja->id);
        $caja->update(['efectivo_inicial'=>10000]);
        $cuotas=Subrubro::where('nombre','Cuota Mensual')->firstOrFail();
        MovimientoOperativo::create(['caja_operativa_id'=>$caja->id,'fecha'=>today(),'tipo_caja_id'=>$tipoCaja->id,'subrubro_id'=>$cuotas->id,'monto'=>30000,'usuario_id'=>$operativo->id,'alumno_id'=>$patin->id,'estado'=>'ACTIVO']);
        CashflowMovimiento::create(['fecha'=>today(),'subrubro_id'=>$cuotas->id,'tipo_caja_id'=>$tipoCaja->id,'monto'=>1570000,'usuario_admin_id'=>$admin->id,'observaciones'=>'Ingreso ficticio para estrés visual']);
        $patin->deporte->update(['nombre'=>'Patín artístico competitivo y preparación internacional']);
        $patin->grupo->nivel->update(['nombre'=>'Avanzadas de competencia y preparación de exhibiciones regionales']);
        GrupoPlan::where('grupo_id',$patin->grupo_id)->update(['precio_mensual'=>2345678.90]);
        foreach([1,3] as $frecuencia) GrupoPlan::create(['grupo_id'=>$patin->grupo_id,'clases_por_semana'=>$frecuencia,'precio_mensual'=>9876543.21,'activo'=>true]);
        $patin->update(['nombre'=>'María de los Ángeles Alejandra','apellido'=>'Fernández de la Cruz de prueba','celular'=>'11-4000-0000','nombre_tutor'=>'Tutor de prueba','telefono_tutor'=>'11-4000-0000']);
        $alumnos=collect([$patin]);
        for($i=1;$i<20;$i++) {
            $alumno=Alumno::create(['nombre'=>'Alumno de prueba '.$i,'apellido'=>'Apellido compuesto de prueba '.$i,'dni'=>(string)(35000000+$i),'fecha_nacimiento'=>'2010-01-01','fecha_alta'=>'2026-09-01','deporte_id'=>$patin->deporte_id,'grupo_id'=>$patin->grupo_id,'celular'=>'11-4000-0000','nombre_tutor'=>'Tutor de prueba','telefono_tutor'=>'11-4000-0000','activo'=>true]);
            AlumnoPlan::create(['alumno_id'=>$alumno->id,'plan_id'=>GrupoPlan::where('grupo_id',$patin->grupo_id)->where('clases_por_semana',2)->value('id'),'fecha_desde'=>'2026-09-01','activo'=>true]);
            $alumnos->push($alumno);
        }
        foreach($alumnos as $alumno) DeudaCuota::create(['alumno_id'=>$alumno->id,'periodo'=>'2026-09','monto_original'=>1234567.89,'monto_pagado'=>0,'estado'=>'PENDIENTE']);
        $clase=Clase::create(['grupo_id'=>$patin->grupo_id,'fecha'=>today()->addDay()->toDateString(),'hora_inicio'=>'18:00','hora_fin'=>'19:00','cancelada'=>false,'validada_para_liquidacion'=>true]);
        $clase->profesores()->attach($prof);
        // Estados concretos A8: se fotografían filas reales, incluidos los estados grises.
        $nivelInactivo=Nivel::create(['nombre'=>'Inicial de prueba','descripcion'=>'Nivel del grupo inactivo ficticio']);
        $grupoInactivo=Grupo::create(['deporte_id'=>$patin->deporte_id,'nivel_id'=>$nivelInactivo->id,'activo'=>false]);
        $nivelSinGrupos=Nivel::create(['nombre'=>'Sin grupos de prueba','descripcion'=>'Nivel ficticio sin grupos asociados']);
        $profInactivo=Profesor::create(['deporte_id'=>$patin->deporte_id,'nombre'=>'Lucía','apellido'=>'Prueba inactiva','dni'=>'28999889','fecha_nacimiento'=>'1990-05-15','direccion'=>'Dirección ficticia de prueba','localidad'=>'Localidad ficticia','valor_hora'=>1000,'activo'=>false]);
        $this->assertSame(20,Alumno::where('grupo_id',$patin->grupo_id)->count());
        $info=['base'=>DB::connection()->getDatabaseName(),'grupo'=>$patin->grupo_id,'alumno'=>$patin->id,'clase'=>$clase->id,'profesor'=>$prof->id,'caja'=>$caja->id,'filas_alumnos'=>$alumnos->take(5)->map(fn($a)=>$a->only(['id','nombre','apellido','dni']))->values()->all(),'alumnos_clase'=>20,'deuda_total'=>DeudaCuota::sum('monto_original'),'planes'=>GrupoPlan::where('grupo_id',$patin->grupo_id)->get()->toArray()];
        file_put_contents(__DIR__.'/escenario.json',json_encode($info,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
        file_put_contents(__DIR__.'/escenario-a8.json',json_encode(['base'=>DB::connection()->getDatabaseName(),'grupo_activo'=>$patin->grupo_id,'grupo_inactivo'=>$grupoInactivo->id,'nivel_con_grupos'=>$patin->grupo->nivel_id,'nivel_sin_grupos'=>$nivelSinGrupos->id,'profesor_activo'=>$prof->id,'profesor_inactivo'=>$profInactivo->id],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
        // Conserva exclusivamente esta base descartable para navegar después del ensayo.
        while(DB::connection()->transactionLevel()>0) DB::connection()->commit();
    }
}
