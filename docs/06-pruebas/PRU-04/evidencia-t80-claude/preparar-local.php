<?php
// T80: usuarios con clave conocida y una caja abierta sobre el volumen de dos años, solo en la base descartable.
// Uso: DB_DATABASE=wings_testing_claude php docs/06-pruebas/PRU-04/evidencia-t80-claude/preparar-local.php
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') throw new RuntimeException('Solo wings_testing_claude.');
use Illuminate\Support\Facades\DB;
$datos = [];
foreach (['ADMIN', 'OPERATIVO', 'PROFESOR'] as $rol) {
    $u = DB::table('users')->where('rol', $rol)->orderBy('id')->first();
    $clave = bin2hex(random_bytes(16));
    DB::table('users')->where('id', $u->id)->update(['password' => bcrypt($clave)]);
    $datos[$rol] = ['email' => $u->email, 'password' => $clave, 'id' => $u->id];
}
$efectivo = DB::table('tipos_caja')->where('nombre', 'Efectivo')->value('id');
DB::table('cajas_operativas')->insert(['usuario_operativo_id' => $datos['OPERATIVO']['id'], 'usuario_apertura_id' => $datos['OPERATIVO']['id'],
    'apertura_at' => now(), 'estado' => 'ABIERTA', 'tipo_caja_efectivo_id' => $efectivo, 'efectivo_inicial' => 10000, 'efectivo_heredado' => 10000,
    'cerrada_por_admin' => 0, 'created_at' => now(), 'updated_at' => now()]);
$datos['ids'] = ['alumno' => DB::table('alumnos')->orderBy('id')->value('id'), 'caja' => DB::table('cajas_operativas')->where('estado', 'VALIDADA')->orderByDesc('id')->value('id'),
    'clase' => DB::table('asistencias')->orderByDesc('id')->value('clase_id'), 'grupo' => DB::table('grupos')->value('id'), 'profesor' => DB::table('profesores')->value('id'),
    'pago' => DB::table('pagos')->orderByDesc('id')->value('id'), 'usuario' => $datos['OPERATIVO']['id'], 'rubro' => DB::table('rubros')->value('id'),
    'deporte' => DB::table('deportes')->value('id'), 'nivel' => DB::table('niveles')->value('id'), 'medio' => $efectivo];
file_put_contents(storage_path('app/t80-local.json'), json_encode($datos, JSON_THROW_ON_ERROR));
echo "Listo.\n";
