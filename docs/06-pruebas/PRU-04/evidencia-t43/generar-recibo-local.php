<?php

require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_codex_t61') {
    throw new RuntimeException('Solo base de capturas ficticia.');
}

use App\Models\{Liquidacion, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\{LiquidacionPagoService, LiquidacionService, ReciboService};

$profesor = Profesor::where('dni', '32990043')->firstOrFail();
$liq = Liquidacion::where('profesor_id', $profesor->id)->where('mes', 9)->where('anio', 2026)->firstOrFail();
if ($liq->tieneAtrasadasPendientes() || $liq->total_confirmado !== '60000.00') {
    throw new RuntimeException('Primero revisar julio y agosto desde Wings.');
}
$admin = User::where('email', 'admin.t61@wings.test')->firstOrFail();
$caja = TipoCaja::where('nombre', 'like', '%Banco%')->where('activo', true)->firstOrFail();
if (!$profesor->subrubro_id) {
    $rubro = Rubro::firstOrCreate(['nombre' => 'Sueldos de prueba'], ['tipo' => 'EGRESO']);
    $sub = Subrubro::firstOrCreate(['rubro_id' => $rubro->id, 'nombre' => 'Docente de demostración'],
        ['permitido_para' => 'ADMIN', 'afecta_caja' => false, 'activo' => true]);
    $profesor->update(['subrubro_id' => $sub->id]);
}
if ($liq->estaAbierta()) app(LiquidacionService::class)->cerrarLiquidacion($liq->id);
$resultado = app(LiquidacionPagoService::class)->marcarComoPagada($liq->id, [
    'fecha_pago' => '2026-10-20', 'tipo_caja_id' => $caja->id, 'subrubro_id' => $profesor->subrubro_id,
    'admin_id' => $admin->id, 'monto_esperado' => '60000.00',
]);
$ruta = app(ReciboService::class)->generarReciboLiquidacion($liq->id, true);
$pdf = Illuminate\Support\Facades\Storage::get($ruta);
file_put_contents(__DIR__.'/recibo-real.pdf', $pdf);
echo json_encode(['liquidacion_id' => $liq->id, 'importe_cashflow' => $resultado['cashflow_movimiento']->monto,
    'recibo_bytes' => strlen($pdf), 'decisiones' => Illuminate\Support\Facades\DB::table('liquidacion_decisiones_atrasadas')
        ->where('liquidacion_id', $liq->id)->get(['periodo_cuota', 'nueva', 'comision'])], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
