<?php
// Día 41, antes de la rutina: Sandra se equivoca al abrir (CAJ-008).
require __DIR__.'/lib.php';
use App\Http\Controllers\CajaWebController;
$s = U('Sandra Vidal'); $c = app(CajaWebController::class);
Illuminate\Support\Facades\Auth::guard('web')->setUser($s);
$p = app(App\Services\CajaService::class)->propuestaApertura();
echo '   propuesta: '.json_encode($p)."\n";
$b = ['confirmacion' => '1', 'caja_origen_id' => $p['caja_origen_id']];
paso('41.0a', 'vacío', enviar($s, 'POST', '/caja/apertura', $b + ['efectivo_inicial' => ''], fn ($r) => $c->abrir($r)), 422);
paso('41.0b', 'letras', enviar($s, 'POST', '/caja/apertura', $b + ['efectivo_inicial' => 'siete mil'], fn ($r) => $c->abrir($r)), 422);
paso('41.0c', 'negativo', enviar($s, 'POST', '/caja/apertura', $b + ['efectivo_inicial' => '-7000'], fn ($r) => $c->abrir($r)), 422);
paso('41.0d', 'tres decimales', enviar($s, 'POST', '/caja/apertura', $b + ['efectivo_inicial' => '7000.505'], fn ($r) => $c->abrir($r)), 422);
paso('41.0e', 'sin tildar que contó', enviar($s, 'POST', '/caja/apertura', ['caja_origen_id' => $p['caja_origen_id'], 'efectivo_inicial' => (string) $p['efectivo_heredado']], fn ($r) => $c->abrir($r)), 422);
echo '   cajas abiertas: '.DB::table('cajas_operativas')->where('estado', 'ABIERTA')->count()."\n";
