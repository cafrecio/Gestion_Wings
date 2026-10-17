<?php

// Solo datos sintéticos en la base descartable de Codex, sin suite simultánea.
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_codex') {
    throw new RuntimeException('Solo wings_testing_codex.');
}
Illuminate\Support\Facades\DB::transaction(function () {
    Illuminate\Support\Facades\DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    $password = bin2hex(random_bytes(20));
    $usuarios = [];
    foreach (['ADMIN', 'OPERATIVO'] as $rol) {
        $usuarios[$rol] = App\Models\User::factory()->create([
            'name' => $rol === 'ADMIN' ? 'Dueño prueba móvil' : 'Operativa prueba móvil',
            'rol' => $rol, 'activo' => true, 'password' => bcrypt($password),
        ]);
    }
    $medio = App\Models\TipoCaja::create(['nombre' => 'Efectivo móvil', 'abreviatura' => 'EFE', 'activo' => true]);
    $rubro = App\Models\Rubro::create(['nombre' => 'Otros ingresos prueba', 'tipo' => 'INGRESO']);
    $subrubro = App\Models\Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Venta de indumentaria', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true]);
    $casos = [];
    foreach (['ABIERTA', 'CERRADA', 'VALIDADA', 'RECHAZADA'] as $estado) {
        foreach ([false, true] as $conMovimientos) {
            $caja = App\Models\CajaOperativa::create([
                'usuario_operativo_id' => $usuarios['OPERATIVO']->id, 'estado' => $estado,
                'apertura_at' => now()->subHours(2), 'cierre_at' => $estado === 'ABIERTA' ? null : now(),
                'tipo_caja_efectivo_id' => $medio->id, 'efectivo_inicial' => 10000,
                'efectivo_esperado' => $conMovimientos ? 58000.50 : 10000,
                'efectivo_contado' => $estado === 'ABIERTA' ? null : ($conMovimientos ? 58000.50 : 10000),
                'cambio_retenido' => 10000, 'efectivo_retirado' => $conMovimientos ? 48000.50 : 0,
                'diferencia_efectivo' => 0, 'motivo_rechazo' => $estado === 'RECHAZADA' ? 'Falta el comprobante.' : null,
            ]);
            if ($conMovimientos) {
                App\Models\MovimientoOperativo::create([
                    'caja_operativa_id' => $caja->id, 'fecha' => today(), 'tipo_caja_id' => $medio->id,
                    'subrubro_id' => $subrubro->id, 'monto' => 48000.50,
                    'observaciones' => 'Venta de equipo de entrenamiento',
                    'usuario_id' => $usuarios['OPERATIVO']->id, 'estado' => 'ACTIVO',
                ]);
            }
            $casos[] = ['id' => $caja->id, 'estado' => $estado, 'movimientos' => $conMovimientos];
        }
    }
    file_put_contents(storage_path('app/detalle-mobile-local.json'), json_encode([
        'usuarios' => array_map(fn ($u) => $u->email, $usuarios), 'password' => $password, 'casos' => $casos,
    ], JSON_THROW_ON_ERROR));
});
echo "Ocho cajas sintéticas preparadas en wings_testing_codex.\n";
