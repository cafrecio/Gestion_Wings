<?php

// Ensayo independiente: nunca usar contra la base del club ni como herramienta web.
if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'testing'
    || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    throw new RuntimeException('Solo CLI / testing / wings_testing_codex.');
}
require dirname(__DIR__, 6).'/vendor/autoload.php';
$app = require dirname(__DIR__, 6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') {
    throw new RuntimeException('Base ajena a Codex.');
}
config(['filesystems.default'=>'local', 'filesystems.disks.local.root' => storage_path('app/verificacion-a13-a54'), 'mail.default' => 'array']);
// Fecha del ensayo original: los modos de reproducción no dependen del día de ejecución.
Illuminate\Support\Carbon::setTestNow('2026-10-05 10:00:00');
use App\Models\{Alumno, AlumnoPlan, CajaOperativa, CargoAlumno, CashflowMovimiento, Deporte, DeudaCuota, Grupo, GrupoPlan, MovimientoOperativo, Nivel, Pago, Subrubro, TipoCaja, User};
use App\Services\{CajaService, CobranzaEstadoService, InscripcionService, PagoCuotaService};
use Illuminate\Support\Facades\DB;

function vCheck(bool $ok, string $mensaje, mixed $dato = null): void {
    echo json_encode(['comprobacion' => $mensaje, 'resultado' => $ok ? 'OK' : 'FALLA', 'dato' => $dato], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    if (!$ok) throw new RuntimeException($mensaje);
}
function vAlumno(string $apellido, string $dni, Grupo $grupo, GrupoPlan $plan): Alumno {
    $alumno = Alumno::create(['nombre'=>'Prueba', 'apellido'=>$apellido, 'dni'=>$dni,
        'fecha_nacimiento'=>'2000-01-01', 'fecha_alta'=>'2026-08-01', 'celular'=>'1111111111',
        'deporte_id'=>$grupo->deporte_id, 'grupo_id'=>$grupo->id, 'activo'=>true]);
    AlumnoPlan::create(['alumno_id'=>$alumno->id,'plan_id'=>$plan->id,'fecha_desde'=>'2026-08-01','activo'=>true]);
    DB::table('inscripcion_personas')->insertOrIgnore(['dni'=>$dni]);
    return $alumno;
}
function vDeuda(Alumno $alumno, string $periodo, float $monto=48000, float $pagado=0): DeudaCuota {
    return DeudaCuota::create(['alumno_id'=>$alumno->id,'periodo'=>$periodo,'monto_original'=>$monto,
        'monto_pagado'=>$pagado,'estado'=>$pagado >= $monto ? 'PAGADA':'PENDIENTE']);
}
function vCargo(Alumno $alumno): CargoAlumno {
    return CargoAlumno::create(['alumno_id'=>$alumno->id,'dni'=>$alumno->dni,'tipo'=>'INSCRIPCION',
        'clave_origen'=>'inscripcion:dni:'.$alumno->dni,'subrubro_id'=>Subrubro::where('nombre','Inscripción al club')->firstOrFail()->id,
        'monto_original'=>5000,'monto_condonado'=>0,'estado'=>'VIGENTE','calculo'=>['fecha_ingreso'=>'2026-08-01']]);
}
function vPago(Alumno $a, array $items, User $u, TipoCaja $tipo, ?float $entregado=null, string $fecha='2026-10-05'): array {
    $datos=['alumno_id'=>$a->id,'tipo_caja_id'=>$tipo->id,'items'=>$items,'fecha_pago'=>$fecha];
    if ($entregado !== null) $datos['monto_entregado']=$entregado;
    return $u->isAdmin() ? app(PagoCuotaService::class)->registrarPagoCuotaAdmin($datos+['usuario_admin_id'=>$u->id])
        : app(PagoCuotaService::class)->registrarPagoCuotaOperativo($datos+['usuario_operativo_id'=>$u->id]);
}
$modo=$argv[1] ?? 'inspect';
if ($modo === 'multi-setup') {
    // Misma persona en dos deportes: una sola inscripción por DNI.
    $deporte=Deporte::where('id','!=',Grupo::firstOrFail()->deporte_id)->firstOrFail();
    $grupo=Grupo::create(['deporte_id'=>$deporte->id,'nivel_id'=>Nivel::firstOrFail()->id,'activo'=>true]);
    $plan=GrupoPlan::create(['grupo_id'=>$grupo->id,'clases_por_semana'=>2,'precio_mensual'=>48000,'activo'=>true]);
    $a=vAlumno('K Segunda disciplina','47130005',$grupo,$plan);
    echo json_encode(['alumno'=>$a->id,'dni'=>$a->dni,'saldo'=>app(CobranzaEstadoService::class)->saldoDeAlumnos(Alumno::where('activo',true)->get())[$a->id],
        'inscripcion_en_ficha'=>vCargoSaldo($a)],JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit;
}
if ($modo === 'cero') {
    DeudaCuota::where('alumno_id', Alumno::where('dni','47130003')->sole()->id)->update(['estado'=>'PENDIENTE']);
    echo "Caso explícito: deuda PENDIENTE de importe cero.".PHP_EOL;
    exit;
}
if ($modo === 'race-setup') {
    $grupo=Grupo::firstOrFail(); $plan=GrupoPlan::firstOrFail();
    $admin=User::where('rol','ADMIN')->firstOrFail(); $tipo=TipoCaja::where('nombre','Efectivo prueba')->sole();
    foreach (['G Anular mientras cobra','H Cobrar mientras anula','I Doble anulación','J Dos pestañas'] as $i=>$nombre) {
        $a=vAlumno($nombre, (string)(47130007+$i),$grupo,$plan); vDeuda($a,'2026-10');
        $p=vPago($a,[['periodo'=>'2026-10','monto'=>10000]],$admin,$tipo)['pago'];
        echo json_encode(['alumno'=>$a->id,'pago'=>$p->id,'apellido'=>$nombre],JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
    exit;
}
if ($modo === 'race') {
    $accion=$argv[2] ?? ''; $pagoId=(int)($argv[3] ?? 0); $retener=(int)($argv[4] ?? 0);
    if (!in_array($accion,['anular','cobrar'],true) || !in_array($retener,[0,3],true)) throw new RuntimeException('Ensayo inválido.');
    $pago=Pago::findOrFail($pagoId); $admin=User::where('rol','ADMIN')->firstOrFail();
    $a=Alumno::findOrFail($pago->alumno_id); $tipo=TipoCaja::where('nombre','Efectivo prueba')->sole();
    $inicio=microtime(true); DB::beginTransaction();
    try {
        if ($accion==='anular') app(PagoCuotaService::class)->anularCobroAdmin($pagoId,'Verificación concurrente independiente',$admin->id);
        else vPago($a,[['periodo'=>'2026-10','monto'=>8000]],$admin,$tipo);
        echo json_encode(['fase'=>'retenida','accion'=>$accion,'pago'=>$pagoId,'segundos'=>round(microtime(true)-$inicio,3)]).PHP_EOL;
        flush();
        if ($retener) DB::select('SELECT SLEEP(3)');
        DB::commit();
        echo json_encode(['fase'=>'confirmada','accion'=>$accion,'pago'=>$pagoId,'segundos'=>round(microtime(true)-$inicio,3)]).PHP_EOL;
    } catch (Throwable $e) {
        if (DB::transactionLevel()) DB::rollBack();
        echo json_encode(['fase'=>'rechazada','accion'=>$accion,'pago'=>$pagoId,'segundos'=>round(microtime(true)-$inicio,3),'mensaje'=>$e->getMessage()],JSON_UNESCAPED_UNICODE).PHP_EOL;
        if ($e->getMessage()!=='Este cobro ya fue anulado.') throw $e;
    }
    exit;
}
if ($modo === 'race-checks') {
    foreach (['47130007','47130008','47130009'] as $dni) {
        $a=Alumno::where('dni',$dni)->sole(); $d=$a->deudaCuotas()->sole();
        $esperado=$dni==='47130009'?0.0:8000.0;
        vCheck((float)$d->monto_pagado===$esperado && $d->estado==='PENDIENTE','Concurrencia: deuda '.$dni.' conserva solo el cobro válido',$d->only(['monto_original','monto_pagado','estado']));
        $p=Pago::where('alumno_id',$a->id)->where('estado','ANULADO')->sole();
        $asientos=CashflowMovimiento::where('referencia_tipo',CashflowMovimiento::REF_PAGO_CUOTA)->where('referencia_id',$p->id)->get();
        vCheck($asientos->count()===2 && (float)$asientos->sum('monto')===0.0,'Concurrencia: un solo contraasiento por pago '.$p->id,$asientos->pluck('monto')->all());
    }
    exit;
}
if ($modo === 'fixture') {
    if (Alumno::count() || Pago::count()) throw new RuntimeException('Fixture exige base propia vacía.');
    DB::table('primera_carga')->where('id',1)->update(['estado'=>'TERMINADA']);
    foreach (['ADMIN','OPERATIVO'] as $rol) (new User)->forceFill(['name'=>'Verificación '.$rol,'email'=>strtolower($rol).'@a13.invalid',
        'password'=>bcrypt('Prueba-local-A13!'),'rol'=>$rol,'activo'=>true])->save();
    $tipo=TipoCaja::create(['nombre'=>'Efectivo prueba','abreviatura'=>'EF','activo'=>true,'saldo_inicial'=>200000]);
    TipoCaja::create(['nombre'=>'Transferencia prueba','abreviatura'=>'TR','activo'=>true]);
    $dep=Deporte::firstOrFail(); $nivel=Nivel::firstOrFail();
    $grupo=Grupo::create(['deporte_id'=>$dep->id,'nivel_id'=>$nivel->id,'activo'=>true]);
    $plan=GrupoPlan::create(['grupo_id'=>$grupo->id,'clases_por_semana'=>2,'precio_mensual'=>48000,'activo'=>true]);
    $a=vAlumno('A Parcial','47130001',$grupo,$plan); vDeuda($a,'2026-10'); vCargo($a);
    $a=vAlumno('B Varios meses','47130002',$grupo,$plan); vDeuda($a,'2026-09'); vDeuda($a,'2026-10'); vCargo($a);
    $a=vAlumno('C Cero pendiente','47130003',$grupo,$plan); vDeuda($a,'2026-10',0);
    $a=vAlumno('D Adelantado','47130004',$grupo,$plan);
    $a=vAlumno('E Solo inscripción','47130005',$grupo,$plan); vCargo($a);
    $a=vAlumno('F Operativo','47130006',$grupo,$plan); vDeuda($a,'2026-10');
    echo Alumno::orderBy('id')->get(['id','apellido','dni'])->toJson(JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit;
}
if ($modo === 'inspect') {
    $saldos=app(CobranzaEstadoService::class)->saldoDeAlumnos(Alumno::where('activo',true)->get());
    echo json_encode(['saldos'=>$saldos,'deudas'=>DeudaCuota::get(['id','alumno_id','periodo','monto_original','monto_pagado','estado']),
        'pagos'=>Pago::get(['id','alumno_id','monto_final','estado','detalle_anulacion']),
        'imputaciones'=>DB::table('pago_deuda_cuota')->get(), 'cargos'=>DB::table('pago_cargo_alumno')->get(),
        'cajas'=>CajaOperativa::get(['id','usuario_operativo_id','estado']),
        'movimientos'=>MovimientoOperativo::get(['id','pago_id','caja_operativa_id','monto','estado']),
        'cashflow'=>CashflowMovimiento::get(['id','fecha','tipo_caja_id','monto','referencia_tipo','referencia_id'])],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
if ($modo === 'pdf') {
    $servicio=app(App\Services\ReciboService::class);
    foreach ([3,5] as $id) {
        $ruta=$servicio->generarReciboCuota($id,true);
        echo Illuminate\Support\Facades\Storage::path($ruta).PHP_EOL;
    }
    exit;
}
if ($modo === 'checks') {
    $admin=User::where('rol','ADMIN')->firstOrFail(); $operativo=User::where('rol','OPERATIVO')->firstOrFail(); $tipo=TipoCaja::where('nombre','Efectivo prueba')->firstOrFail();
    $parcial=Alumno::where('dni','47130001')->firstOrFail();
    // Se conservan dos cobros distintos: anular la seña no borra lo que pagó después.
    $primero=vPago($parcial,[['periodo'=>'2026-10','monto'=>10000]],$admin,$tipo,15000)['pago'];
    $segundo=vPago($parcial,[['periodo'=>'2026-10','monto'=>8000]],$admin,$tipo)['pago'];
    app(PagoCuotaService::class)->anularCobroAdmin($primero->id,'Prueba de anulación parcial',$admin->id);
    $d=$parcial->deudaCuotas()->sole();
    vCheck((float)$d->monto_pagado===8000.0 && $d->estado==='PENDIENTE','Anular seña conserva otro pago: deuda 48.000, pagados 8.000', $d->only(['monto_original','monto_pagado','estado']));
    vCheck(vCargoSaldo($parcial)===5000.0,'Inscripción restituida al anular el primer cobro');
    vCheck(Pago::findOrFail($segundo->id)->estado==='COMPLETADO','Segundo cobro no se anula');
    $multi=Alumno::where('dni','47130002')->firstOrFail();
    $r=vPago($multi,[['periodo'=>'2026-09','monto'=>48000],['periodo'=>'2026-10','monto'=>10000]],$admin,$tipo,63000,'2026-09-30');
    $id=$r['pago']->id;
    app(PagoCuotaService::class)->anularCobroAdmin($id,'Prueba con meses e inscripción',$admin->id);
    $deudas=$multi->deudaCuotas()->orderBy('periodo')->get();
    vCheck($deudas->every(fn($d)=>(float)$d->monto_original===48000.0 && (float)$d->monto_pagado===0.0 && $d->estado==='PENDIENTE'),'Anulación multi-mes restituye ambas deudas', $deudas->toArray());
    vCheck(vCargoSaldo($multi)===5000.0,'Inscripción multi-mes vuelve a deber 5.000');
    $pago=Pago::findOrFail($id);
    vCheck((float)$pago->monto_final===63000.0 && array_column($pago->detalle_anulacion['periodos'],'periodo')===['2026-09','2026-10'],'Detalle anulado conserva períodos, importe y cargo', $pago->detalle_anulacion);
    $asientos=CashflowMovimiento::where('referencia_tipo',CashflowMovimiento::REF_PAGO_CUOTA)->where('referencia_id',$id)->get();
    vCheck($asientos->count()===4 && (float)$asientos->sum('monto')===0.0 && $asientos->where('monto','<',0)->every(fn($m)=>$m->fecha->format('Y-m-d')==='2026-10-05'),'Dos originales en septiembre y dos contraasientos hoy; saldo neto cero', $asientos->toArray());
    vCheck(CajaOperativa::count()===0 && MovimientoOperativo::count()===0,'ADMIN no abrió caja ni movimientos operativos');
    $a=Alumno::where('dni','47130006')->firstOrFail();
    $r=vPago($a,[['periodo'=>'2026-10','monto'=>48000]],$operativo,$tipo); $caja=CajaOperativa::sole();
    vCheck($caja->usuario_operativo_id===$operativo->id && MovimientoOperativo::where('pago_id',$r['pago']->id)->count()===1,'Operativo conserva caja y movimiento propios');
    app(CajaService::class)->cerrarCajaOperativa($caja->id,$operativo->id);
    vCheck($caja->fresh()->estado==='CERRADA','Operativo cierra su caja');
    app(CajaService::class)->validarCaja($caja->id,$admin->id); app(CajaService::class)->validarCaja($caja->id,$admin->id);
    vCheck($caja->fresh()->estado==='VALIDADA' && (float)CashflowMovimiento::where('referencia_tipo',CashflowMovimiento::REF_CAJA)->where('referencia_id',$caja->id)->sum('monto')===48000.0,'Admin valida: 48.000 al cashflow una sola vez');
    exit;
}
throw new RuntimeException('Modo inválido.');
function vCargoSaldo(Alumno $a): float {return app(InscripcionService::class)->cargo($a->dni)->saldo_pendiente;}
