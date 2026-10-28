<?php

/** Fixtures visuales exclusivamente locales; no ejecuta migraciones ni modifica producto. */
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex'
    || !in_array(getenv('DB_HOST'), ['127.0.0.1', 'localhost'], true) || getenv('DB_URL')) {
    throw new RuntimeException('Solo testing / wings_testing_codex en localhost.');
}
$raiz = dirname(__DIR__, 4);
if (is_file($raiz.'/bootstrap/cache/config.php')) throw new RuntimeException('No usar configuración cacheada.');
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') throw new RuntimeException('Base incorrecta.');
Carbon\Carbon::setTestNow('2026-11-02 19:00:00');
Carbon\CarbonImmutable::setTestNow('2026-11-02 19:00:00');

use App\Models\{Alumno, AlumnoPlan, Asistencia, CajaOperativa, CashflowMovimiento, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, PrimeraCarga, Profesor, Rubro, Subrubro, TipoCaja, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$privado = $raiz.'/storage/app/aprobaciones-t54-t87-t89.json';
if (is_file($privado)) throw new RuntimeException('Fixtures ya preparados: reutilizar el archivo privado; no duplicar.');
$datos = DB::transaction(function () {
    $clave = bin2hex(random_bytes(12));
    $cuentas = [];
    foreach (['duenio' => ['Carlos Demo', 'ADMIN'], 'operativa' => ['Ana Demo', 'OPERATIVO'], 'otra' => ['Rocío Demo', 'OPERATIVO']] as $tipo => [$nombre, $rol]) {
        $u = new User(['name' => $nombre, 'email' => $tipo.'.galerias@wings.invalid', 'password' => $clave]);
        $u->forceFill(['rol' => $rol, 'activo' => true])->save();
        $cuentas[$tipo] = $u;
    }
    PrimeraCarga::whereKey(1)->update(['estado' => 'TERMINADA', 'detalle' => json_encode(['alumnos' => []])]);
    $dep = Deporte::create(['nombre' => 'Patín', 'abreviatura' => 'PAT', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
    $nivel = Nivel::create(['nombre' => 'Inicial', 'activo' => true]);
    $grupo = Grupo::create(['deporte_id' => $dep->id, 'nivel_id' => $nivel->id, 'activo' => true]);
    $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'nombre' => 'Dos clases', 'clases_por_semana' => 2, 'precio_mensual' => 28000, 'activo' => true]);
    $medio = TipoCaja::create(['nombre' => 'Efectivo', 'abreviatura' => 'EF', 'activo' => true, 'saldo_inicial' => 0]);
    $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO']);
    $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Cuota Mensual', 'permitido_para' => 'OPERATIVO',
        'afecta_caja' => true, 'activo' => true, 'es_reservado_sistema' => true]);
    $cajas = [];
    foreach (['operativa', 'otra'] as $i => $tipo) {
        $cajas[$tipo] = CajaOperativa::create(['usuario_operativo_id' => $cuentas[$tipo]->id,
            'usuario_apertura_id' => $cuentas[$tipo]->id, 'apertura_at' => '2026-11-02 '.($i ? '11' : '17').':00:00',
            'cierre_at' => $i ? '2026-11-02 16:00:00' : null, 'estado' => $i ? 'CERRADA' : 'ABIERTA',
            'tipo_caja_efectivo_id' => $medio->id, 'efectivo_inicial' => 10000]);
    }
    $alumnos = [];
    foreach ([['Juan','Pérez'],['Lucía','Gómez'],['Ana','García'],['Mario','Pérez'],['Sara','López']] as $i => [$nombre, $apellido]) {
        $a = Alumno::create(['nombre' => $nombre, 'apellido' => $apellido, 'dni' => (string)(44950101+$i),
            'fecha_nacimiento' => '2005-05-15', 'celular' => '1100000000', 'fecha_alta' => '2026-09-01',
            'deporte_id' => $dep->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        $deuda = DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => '2026-11', 'monto_original' => 28000, 'monto_pagado' => 28000, 'estado' => 'PAGADA']);
        $pago = Pago::create(['alumno_id' => $a->id, 'plan_id' => $plan->id, 'mes' => 11, 'anio' => 2026,
            'monto_base' => 28000, 'porcentaje_aplicado' => 100, 'monto_final' => 28000, 'monto_cuota' => 28000,
            'fecha_pago' => '2026-11-02', 'estado' => 'COMPLETADO']);
        $pago->deudasCuota()->attach($deuda->id, ['monto_aplicado' => 28000]);
        $tipo = $i % 2 ? 'otra' : 'operativa';
        $m = new App\Models\MovimientoOperativo(['caja_operativa_id' => $cajas[$tipo]->id, 'tipo_caja_id' => $medio->id,
            'subrubro_id' => $sub->id, 'usuario_id' => $cuentas[$tipo]->id, 'alumno_id' => $a->id, 'pago_id' => $pago->id,
            'fecha' => '2026-11-02', 'monto' => 28000, 'estado' => 'ACTIVO', 'observaciones' => "Cuota noviembre - {$apellido}, {$nombre}"]);
        $m->created_at = '2026-11-02 '.($i % 2 ? '14' : '18').':'.str_pad((string)(10+$i*5), 2, '0', STR_PAD_LEFT).':00';
        $m->save();
        $alumnos[] = $a;
    }
    $lucia = Profesor::create(['deporte_id' => $dep->id, 'nombre' => 'Lucía', 'apellido' => 'Méndez', 'dni' => '33995101',
        'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Calle de ejemplo 100', 'localidad' => 'Quilmes',
        'valor_hora' => 12000, 'activo' => true]);
    foreach ([10 => 17, 9 => 3] as $mes => $cantidad) {
        for ($dia=1; $dia<=$cantidad; $dia++) {
            $c = Clase::create(['grupo_id' => $grupo->id, 'fecha' => sprintf('2026-%02d-%02d', $mes, $dia),
                'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false, 'validada_para_liquidacion' => true]);
            $c->profesores()->attach($lucia->id);
        }
    }
    foreach (['2026-10-21','2026-10-22','2026-09-28'] as $fecha) {
        $c=Clase::create(['grupo_id' => $grupo->id, 'fecha' => $fecha, 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false, 'validada_para_liquidacion' => false]);
        $c->profesores()->attach($lucia->id);
    }
    $ausentes = Clase::create(['grupo_id' => $grupo->id, 'fecha' => '2026-11-02', 'hora_inicio' => '17:00',
        'hora_fin' => '18:00', 'cancelada' => false, 'validada_para_liquidacion' => false]);
    $ausentes->profesores()->attach($lucia->id);
    foreach ($alumnos as $a) Asistencia::create(['clase_id' => $ausentes->id, 'alumno_id' => $a->id, 'presente' => false]);
    $rubroG = Rubro::create(['nombre' => 'Gastos operativos', 'tipo' => 'EGRESO']);
    $subG = Subrubro::create(['rubro_id' => $rubroG->id, 'nombre' => 'Librería', 'permitido_para' => 'OPERATIVO',
        'afecta_caja' => true, 'activo' => true, 'es_reservado_sistema' => false]);
    $cash = CashflowMovimiento::create(['fecha' => '2026-11-02', 'subrubro_id' => $subG->id, 'tipo_caja_id' => $medio->id,
        'monto' => -4000, 'observaciones' => 'Materiales para el taller', 'usuario_admin_id' => $cuentas['duenio']->id]);
    app(App\Services\CashflowCorreccionService::class)->editar($cash->id, $cuentas['duenio']->id, ['clave' => (string)Str::uuid(),
        'version' => 0, 'motivo' => 'Se corrigió el importe del comprobante de librería.', 'monto' => 4500, 'fecha' => '2026-11-02',
        'subrubro_id' => $subG->id, 'tipo_caja_id' => $medio->id, 'observaciones' => 'Materiales para el taller - importe corregido']);
    return ['clave' => $clave, 'cuentas' => array_map(fn($u) => ['id'=>$u->id,'email'=>$u->email], $cuentas),
        'cajas' => array_map(fn($c) => $c->id, $cajas), 'profesora'=>$lucia->id, 'clase_ausentes'=>$ausentes->id,
        'cashflow_corregido'=>$cash->id, 'alumnos'=>array_map(fn($a)=>$a->id,$alumnos)];
});
file_put_contents($privado, json_encode($datos, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "Fixtures sintéticos preparados en wings_testing_codex. Credenciales solo en storage/app, fuera de Git.\n";
