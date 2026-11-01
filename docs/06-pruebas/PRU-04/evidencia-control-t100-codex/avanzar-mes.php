<?php
$base=getenv('CONTROL_APP');require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,Artisan,Mail,Notification};
use App\Models\{DeudaCuota,AlumnoRevisionCobranza};
if(DB::selectOne('select database() db')->db!=='wings_testing_codex')throw new RuntimeException('BASE PROHIBIDA');Mail::fake();Notification::fake();
\Carbon\Carbon::setTestNow('2026-10-31 23:00:00');\Carbon\CarbonImmutable::setTestNow('2026-10-31 23:00:00');$antes=app(App\Services\ReporteMensualService::class)->obtener('2026-10');
\Carbon\Carbon::setTestNow('2026-11-01 06:00:00');\Carbon\CarbonImmutable::setTestNow('2026-11-01 06:00:00');Artisan::call('cobranza:generar-deudas');$salida=Artisan::output();$despues=app(App\Services\ReporteMensualService::class)->obtener('2026-10');
foreach(['ingresos','egresos','disponible','deuda','por_pagar','resultado'] as $campo)if($antes[$campo]!==$despues[$campo])throw new RuntimeException('Cambio cierre de octubre: '.$campo);
$q3=DeudaCuota::where('alumno_id',3)->where('periodo','2026-11')->first();$q4=DeudaCuota::where('alumno_id',4)->where('periodo','2026-11')->first();$rev=AlumnoRevisionCobranza::where('alumno_id',4)->where('periodo_objetivo','2026-11')->first();
if($q3?->monto_original!=='30000.00'||$q4!==null||!$rev)throw new RuntimeException('No cumple casos 3/4');
file_put_contents($base.'/storage/control-clock.txt','2026-11-01 10:00:00');
$r=['reloj'=>'2026-11-01 10:00:00','salida'=>$salida,'cuota_con_asistencia'=>$q3,'cuota_sin_asistencia'=>$q4,'revision_sin_asistencia'=>$rev,'octubre_antes'=>$antes,'octubre_despues'=>$despues,'cierre_monetario_intacto'=>true];file_put_contents(__DIR__.'/casos3-4-generacion.json',json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo json_encode(['con_asistencia'=>$q3,'sin_asistencia'=>$q4,'revision'=>$rev,'cierre_intacto'=>true],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
