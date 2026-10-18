<?php
// Solo datos sintéticos y credenciales efímeras en almacenamiento ignorado.
require dirname(__DIR__).'/evidencia-detalle-mobile/preparar-local.php';
$archivo = storage_path('app/detalle-mobile-local.json');
$datos = json_decode(file_get_contents($archivo), true, flags: JSON_THROW_ON_ERROR);
$c = collect($datos['casos'])->first(fn ($c) => $c['estado'] === 'CERRADA' && $c['movimientos']);
$caja = App\Models\CajaOperativa::findOrFail($c['id']);
$segundo = $caja->movimientos()->firstOrFail()->replicate();
$segundo->monto = 500;
$segundo->estado = 'CANCELADO';
$segundo->motivo_cancelacion = 'Comprobante duplicado de prueba';
$segundo->save();
$abierta = collect($datos['casos'])->first(fn ($c) => $c['estado'] === 'ABIERTA' && $c['movimientos']);
$movimiento = App\Models\CajaOperativa::findOrFail($abierta['id'])->movimientos()->firstOrFail();
$deporte = App\Models\Deporte::create(['nombre' => 'Deporte T36', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivel = App\Models\Nivel::create(['nombre' => 'Nivel T36']);
$grupo = App\Models\Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
$alumno = App\Models\Alumno::create([
    'nombre' => 'Prueba', 'apellido' => 'T36', 'dni' => '40111999',
    'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => today(), 'activo' => true,
    'celular' => '1111111111', 'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id,
]);
$cobro = $movimiento->replicate();
$cobro->alumno_id = $alumno->id;
$cobro->observaciones = 'Cobro sintético para modal';
$cobro->save();
$datos['cerrada'] = $caja->id;
$datos['abierta'] = $abierta['id'];
file_put_contents(storage_path('app/t36-local.json'), json_encode($datos, JSON_THROW_ON_ERROR));
echo "T36: dos renglones revisables y modal de cancelación preparados.\n";
