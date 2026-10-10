<?php
// Verificación B10: exclusivamente datos ficticios en la base descartable de Codex.
$base = dirname(__DIR__, 5);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{Alumno, AlumnoPlan, Asistencia, CajaOperativa, CashflowMovimiento, Clase, Configuracion, Deporte, Grupo, GrupoPlan, Liquidacion, MovimientoOperativo, Nivel, Profesor, Subrubro, TipoCaja, User};
use Illuminate\Support\Facades\{Artisan, DB, Hash, Schema};
use PhpOffice\PhpSpreadsheet\{Cell\DataType, Writer\Xlsx};
if (!$app->environment('testing') || DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    fwrite(STDERR, "Se requiere APP_ENV=testing y wings_testing_codex.\n"); exit(1);
}
function salida(array $data): never { echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); exit; }
function comando(string $command, array $arguments = []): void {
    $code = Artisan::call($command, $arguments);
    if ($code !== 0) throw new RuntimeException("Falló $command; salida privada en Artisan, no exportada.");
}
function nuevaBase(): void { comando('migrate:fresh', ['--force'=>true]); comando('db:seed', ['--class'=>Database\Seeders\CatalogosSeeder::class, '--force'=>true]); }
$mode = $argv[1] ?? '';
if ($mode === 'fila') {
    $kind = $argv[2]; $value = $argv[3];
    if ($kind === 'profesor') salida(['filas'=>Profesor::where('dni',$value)->get(['id','dni','nombre','apellido','direccion','localidad','email','telefono','deporte_id','valor_hora','cbu_alias'])->toArray()]);
    if ($kind === 'usuario') salida(['filas'=>User::where('email',$value)->get(['id','name','email','rol','profesor_id','activo','cbu_alias','subrubro_id'])->toArray()]);
    if ($kind === 'pago') {
        $l=Liquidacion::findOrFail($value);
        salida(['liquidacion'=>$l->only(['id','estado','estado_pago','total_calculado','monto_final','pagada_fecha','pagada_tipo_caja_id','pagada_por_admin_id']), 'monto_a_pagar'=>$l->monto_a_pagar,
            'movimientos'=>CashflowMovimiento::where('referencia_tipo','LIQUIDACION')->where('referencia_id',$l->id)->get(['id','monto','tipo_caja_id','subrubro_id','referencia_tipo','referencia_id','usuario_admin_id'])->toArray()]);
    }
    throw new RuntimeException('Consulta no autorizada');
}
if ($mode === 'privacidad') {
    $f=json_decode(file_get_contents($base.'/storage/app/b10-fixture.json'),true,flags:JSON_THROW_ON_ERROR);
    $g=Grupo::findOrFail($f['grupo_id']); $plan=GrupoPlan::findOrFail($f['plan_id']);
    $a=Alumno::create(['nombre'=>'Alumno FICTICIO B10','apellido'=>'Privacidad','dni'=>'99101099','fecha_nacimiento'=>'2000-01-01','fecha_alta'=>today()->format('Y-m-d'),'deporte_id'=>$g->deporte_id,'grupo_id'=>$g->id,'celular'=>'1100000000','activo'=>true]);
    AlumnoPlan::create(['alumno_id'=>$a->id,'plan_id'=>$plan->id,'fecha_desde'=>today()->format('Y-m-d'),'activo'=>true]);
    $p=Profesor::findOrFail($f['profesor_id']);
    $clase=Clase::create(['grupo_id'=>$g->id,'fecha'=>today()->format('Y-m-d'),'hora_inicio'=>'09:00','hora_fin'=>'10:00','cancelada'=>false,'validada_para_liquidacion'=>false]);
    $clase->profesores()->attach($p->id); Asistencia::create(['clase_id'=>$clase->id,'alumno_id'=>$a->id,'presente'=>true]);
    $tc=TipoCaja::findOrFail($f['tipo_caja_id']);
    $caja=CajaOperativa::create(['usuario_operativo_id'=>$f['usuarios']['OPERATIVO']['id'],'usuario_apertura_id'=>$f['usuarios']['OPERATIVO']['id'],'apertura_at'=>now(),'estado'=>'ABIERTA','tipo_caja_efectivo_id'=>$tc->id,'efectivo_inicial'=>0,'efectivo_heredado'=>0]);
    $sub=Subrubro::where('permitido_para','OPERATIVO')->firstOrFail();
    $mov=MovimientoOperativo::create(['caja_operativa_id'=>$caja->id,'fecha'=>today(),'tipo_caja_id'=>$tc->id,'subrubro_id'=>$sub->id,'monto'=>100,'usuario_id'=>$f['usuarios']['OPERATIVO']['id'],'estado'=>'ACTIVO','observaciones'=>'FICTICIO B10 privacidad']);
    $f['alumno_id']=$a->id;$f['clase_privacidad_id']=$clase->id;$f['caja_id']=$caja->id;$f['mov_id']=$mov->id;
    file_put_contents($base.'/storage/app/b10-fixture.json',json_encode($f,JSON_THROW_ON_ERROR));
    salida(['alumno_id'=>$a->id,'clase_id'=>$clase->id,'caja_id'=>$caja->id,'mov_id'=>$mov->id,'profesor'=>Profesor::whereKey($p->id)->first(['id','cbu_alias'])->toArray(),'operativo'=>User::whereKey($f['usuarios']['OPERATIVO']['id'])->first(['id','rol','cbu_alias'])->toArray()]);
}
if ($mode === 'excel') {
    // No se recrea el esquema: profesores, usuarios y sus datos bancarios se conservan.
    DB::table('clase_profesor')->delete(); DB::table('asistencias')->delete(); DB::table('clases')->delete();
    DB::table((new AlumnoPlan)->getTable())->delete(); DB::table((new Alumno)->getTable())->delete();
    DB::table('primera_carga')->where('id',1)->update(['estado'=>'PENDIENTE','detalle'=>null]);
    $svc=app(App\Services\PrimeraCargaExcelService::class); $opcion=$svc->catalogos()[0];
    $libro=$svc->plantilla(); $h=$libro->getSheetByName('Alumnos');
    $h->fromArray(['99101098','Excel FICTICIO','B10','01/01/2000',today()->format('d/m/Y'),'1100000000','','','',$opcion['deporte'],$opcion['grupo'],$opcion['plan'],'No','No'],null,'A2');
    $h->setCellValueExplicit('A2','99101098',DataType::TYPE_STRING);
    (new Xlsx($libro))->save($base.'/storage/app/b10-carga.xlsx');
    $revision=$svc->revisar($base.'/storage/app/b10-carga.xlsx');
    salida(['archivo'=>'storage/app/b10-carga.xlsx','errores'=>$revision['errores'],'resumen'=>$revision['resumen']]);
}
if ($mode === 'excel-comprobar') {
    salida(['estado'=>DB::table('primera_carga')->where('id',1)->value('estado'),'alumno'=>Alumno::where('dni','99101098')->first(['id','dni','nombre','apellido','grupo_id'])->toArray(),'profesores'=>Profesor::whereNotNull('cbu_alias')->get(['id','cbu_alias'])->toArray(),'operativos'=>User::whereNotNull('cbu_alias')->get(['id','rol','cbu_alias'])->toArray()]);
}
if ($mode === 'preparar') {
    nuevaBase();
    DB::table('primera_carga')->where('id',1)->update(['estado'=>'TERMINADA']);
    $dep=Deporte::where('tipo_liquidacion','HORA')->firstOrFail(); $nivel=Nivel::firstOrFail();
    $grupo=Grupo::create(['deporte_id'=>$dep->id,'nivel_id'=>$nivel->id,'activo'=>true]);
    $plan=GrupoPlan::create(['grupo_id'=>$grupo->id,'clases_por_semana'=>2,'precio_mensual'=>30000,'activo'=>true]);
    $p=Profesor::create(['deporte_id'=>$dep->id,'nombre'=>'Profesor FICTICIO B10','apellido'=>'Privacidad','dni'=>'99101000','fecha_nacimiento'=>'1990-01-01','direccion'=>'Domicilio ficticio','localidad'=>'Ficticia','telefono'=>'1100000000','valor_hora'=>5000,'activo'=>true,'cbu_alias'=>'FICTICIO.PROF.B10']);
    app(App\Services\SubrubroSueldoService::class)->paraProfesor($p);
    $usuarios=[];
    foreach (['ADMIN','OPERATIVO','PROFESOR'] as $rol) {
        $password=bin2hex(random_bytes(12));
        $u=User::factory()->create(['name'=>"$rol FICTICIO B10",'email'=>strtolower($rol).'@b10.ficticio.test','password'=>Hash::make($password),'rol'=>$rol,'activo'=>true,'profesor_id'=>$rol==='PROFESOR'?$p->id:null]);
        if ($rol==='OPERATIVO') {$u->cbu_alias='FICTICIO.OP.B10';$u->save();app(App\Services\SubrubroSueldoService::class)->paraUsuarioOperativo($u);}
        $usuarios[$rol]=['id'=>$u->id,'email'=>$u->email,'password'=>$password];
    }
    $tc=TipoCaja::where('activo',true)->firstOrFail();$tc->saldo_inicial=100000;$tc->save();
    app(App\Services\CajaService::class)->configurarMostrador($tc->id,$usuarios['ADMIN']['id']);
    Configuracion::set('inscripcion_importe','5000');
    $clase=Clase::create(['grupo_id'=>$grupo->id,'fecha'=>today()->subDay()->format('Y-m-d'),'hora_inicio'=>'10:00','hora_fin'=>'11:30','cancelada'=>false,'validada_para_liquidacion'=>true]);
    $clase->profesores()->attach($p->id);
    $f=['database'=>DB::connection()->getDatabaseName(),'usuarios'=>$usuarios,'profesor_id'=>$p->id,'deporte_id'=>$dep->id,'grupo_id'=>$grupo->id,'plan_id'=>$plan->id,'tipo_caja_id'=>$tc->id,'hoy'=>today()->format('Y-m-d'),'mes'=>today()->month,'anio'=>today()->year,'clase_liquidar_id'=>$clase->id];
    file_put_contents($base.'/storage/app/b10-fixture.json',json_encode($f,JSON_THROW_ON_ERROR));
    salida(['database'=>$f['database'],'migracion_b10'=>DB::table('migrations')->where('migration','2026_10_10_120000_add_cbu_alias_to_profesores_and_users')->value('migration'),'profesor'=>Profesor::whereKey($p->id)->first(['id','dni','cbu_alias'])->toArray(),'operativo'=>User::whereKey($usuarios['OPERATIVO']['id'])->first(['id','rol','cbu_alias'])->toArray()]);
}
if ($mode === 'comision') {
    $d=Deporte::where('tipo_liquidacion','COMISION')->firstOrFail();
    $p=Profesor::create(['deporte_id'=>$d->id,'nombre'=>'COMISION FICTICIO B10','apellido'=>'Convivencia','dni'=>'99101097','fecha_nacimiento'=>'1990-01-01','direccion'=>'Domicilio ficticio','localidad'=>'Ficticia','telefono'=>'1100000000','porcentaje_comision'=>30,'activo'=>true,'cbu_alias'=>'FICTICIO.COM.B10']);
    app(App\Services\SubrubroSueldoService::class)->paraProfesor($p);
    $l=Liquidacion::create(['profesor_id'=>$p->id,'mes'=>today()->month,'anio'=>today()->year,'tipo'=>'COMISION','porcentaje_comision_aplicado'=>30,'total_calculado'=>10000,'estado'=>'CERRADA','estado_pago'=>'PENDIENTE']);
    salida(['id'=>$l->id,'profesor_id'=>$p->id,'monto'=>$l->monto_a_pagar]);
}
if ($mode === 'seeders') {
    $resultados=[];
    $clases=[Database\Seeders\PrimeraCargaCompletaSeeder::class, Database\Seeders\UserSeeder::class, Database\Seeders\TestSeeder::class,
        Database\Seeders\ReportesEscenarioSeeder::class, Database\Seeders\ReportesAsistenciaEscenarioSeeder::class, Database\Seeders\ReportesSueldosEscenarioSeeder::class];
    foreach ($clases as $clase) {
        try {
            nuevaBase();
            if ($clase === Database\Seeders\UserSeeder::class) {
                comando('db:seed',['--class'=>Database\Seeders\PrimeraCargaCompletaSeeder::class,'--force'=>true]);
            }
            if ($clase === Database\Seeders\ReportesAsistenciaEscenarioSeeder::class) {
                comando('db:seed',['--class'=>Database\Seeders\ReportesEscenarioSeeder::class,'--force'=>true]);
            }
            comando('db:seed',['--class'=>$clase,'--force'=>true]);
            $resultados[]=['seeder'=>$clase,'ok'=>true,
                'profesores'=>Profesor::orderBy('id')->limit(5)->get(['id','dni','cbu_alias'])->toArray(),
                'usuarios'=>User::orderBy('id')->limit(5)->get(['id','rol','profesor_id','cbu_alias'])->toArray()];
        } catch (Throwable $e) {
            file_put_contents($base.'/storage/app/b10-error-seeder.txt',$e->getMessage());
            $resultados[]=['seeder'=>$clase,'ok'=>false,'clase_error'=>get_class($e),'detalle_privado'=>'storage/app/b10-error-seeder.txt'];
        }
    }
    nuevaBase();
    try {
        comando('db:seed',['--class'=>Database\Seeders\DemoSeeder::class,'--force'=>true]);
        $resultados[]=['seeder'=>Database\Seeders\DemoSeeder::class,'ok'=>true,
            'profesores'=>Profesor::orderBy('id')->limit(5)->get(['id','dni','cbu_alias'])->toArray(),
            'usuarios'=>User::orderBy('id')->limit(5)->get(['id','rol','profesor_id','cbu_alias'])->toArray()];
    } catch (Throwable $e) {
        file_put_contents($base.'/storage/app/b10-error-demo.txt',$e->getMessage());
        $resultados[]=['seeder'=>Database\Seeders\DemoSeeder::class,'ok'=>false,'clase_error'=>get_class($e),'detalle_privado'=>'storage/app/b10-error-demo.txt'];
    }
    $data=['database'=>DB::connection()->getDatabaseName(),'fecha'=>now()->toIso8601String(),'casos'=>$resultados];
    file_put_contents(__DIR__.'/seeders.json',json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n");
    salida($data);
}
throw new RuntimeException('Modo desconocido');
