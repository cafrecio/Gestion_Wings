<?php
// Día 46, martes 24/11, 11:00 — el dueño corrige la profesora de la clase de Intermedias del viernes (la dio Mariela);
// mira a Emma Villalba en su último día de plazo; corrige el Internet desde dos pestañas. A la noche (21:00) Pablo
// cierra con doble clic.
require __DIR__.'/lib.php';
use App\Http\Controllers\{ClaseWebController, CashflowCorreccionWebController, CajaWebController};
use App\Models\{Clase, Profesor, CashflowMovimiento, CajaOperativa};
use Illuminate\Support\Str;
$admin = U('Admin Prueba'); $pablo = U('Pablo Ledesma'); $caja = app(CajaWebController::class);
estado('inicio 46b (después de la rutina)');
$mi = A('Ferreyra, Milagros');
$av = Clase::whereDate('fecha', '2026-11-24')->whereTime('hora_inicio', '17:00:00')->firstOrFail();
echo '   Milagros en Avanzadas 17: presente='.DB::table('asistencias')->where('clase_id', $av->id)->where('alumno_id', $mi->id)->value('presente')
    .' · excepción: '.DB::table('excepciones_cobranza')->where('alumno_id', $mi->id)->count().' · contador semanal en la lista: '.json_encode(app(App\Services\ExcepcionCobranzaService::class)->condicionesAsistencia(App\Models\Alumno::whereKey($mi->id)->get(), $av)[$mi->id] ?? null, JSON_UNESCAPED_UNICODE)."\n";
// 46.2 cambiar la profesora de la clase pasada del viernes 20/11
$int = Clase::whereDate('fecha', '2026-11-20')->whereTime('hora_inicio', '16:00:00')->firstOrFail();
$mar = Profesor::where('nombre', 'Mariela')->firstOrFail();
$ctl = app(ClaseWebController::class);
paso('46.2a', "Intermedias del 20/11 (#{$int->id}) a Mariela, sin motivo", enviar($admin, 'PATCH', "/clases/{$int->id}/profesores", ['profesores' => [$mar->id]], fn ($r) => $ctl->actualizarProfesores($r, $int->id), true), 422);
paso('46.2b', 'con motivo', enviar($admin, 'PATCH', "/clases/{$int->id}/profesores", ['profesores' => [$mar->id], 'motivo' => 'La dio Mariela: Lucía estaba con médico'], fn ($r) => $ctl->actualizarProfesores($r, $int->id), true), 200);
echo '   profesora: '.$int->profesores()->pluck('nombre')->implode(', ').' · motivo «'.$int->fresh()->motivo_cambio_profesor."»\n";
paso('46.2c', 'Pablo intenta lo mismo con la clase del 19/11', enviar($pablo, 'PATCH', '/clases/224/profesores', ['profesores' => [$mar->id], 'motivo' => 'Prueba de la operativa'], fn ($r) => $ctl->actualizarProfesores($r, 224), true), 403);
// 46.3 Emma Villalba, último día del plazo
$fila = app(App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS')->firstWhere('id', A('Villalba, Emma')->id);
echo '   Emma: '.$fila?->estado_cobranza.' '.plata($fila?->deuda_total).' · recargo '.json_encode(DB::table('cargos_alumno')->where('alumno_id', A('Villalba, Emma')->id)->where('tipo', 'PUNITORIO')->first(['monto_original', 'created_at']))."\n";
[$st, $t, $h] = http($admin, '/cobranza?estado=MOROSO');
if (preg_match('/Villalba.{0,700}/s', $h, $m)) echo '   Cobranza Morosos: '.mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($m[0]))), 0, 300)."\n";
// 46.4 Internet corregido desde dos pestañas
$net = CashflowMovimiento::where('observaciones', 'Telecentro, noviembre')->whereNull('anulado_at')->whereNull('contra_origen_id')->firstOrFail();
$v = (int) $net->version_correccion; $clave = (string) Str::uuid();
$datos = ['clave' => $clave, 'version' => $v, 'subrubro_id' => $net->subrubro_id, 'tipo_caja_id' => $net->tipo_caja_id, 'monto' => '23500', 'fecha' => '2026-11-20', 'observaciones' => 'Telecentro, noviembre', 'motivo' => 'La factura vino con el aumento: $23.500'];
$cc = app(CashflowCorreccionWebController::class);
paso('46.4a', "corrige Internet (#{$net->id}) a $23.500", enviar($admin, 'PUT', "/cashflow/movimientos/{$net->id}", $datos, fn ($r) => $cc->update($r, $net->id)), 200);
paso('46.4b', 'doble clic: el mismo envío otra vez', enviar($admin, 'PUT', "/cashflow/movimientos/{$net->id}", $datos, fn ($r) => $cc->update($r, $net->id)), 200);
paso('46.4c', 'la otra pestaña, con la pantalla vieja, guarda $24.000', enviar($admin, 'PUT', "/cashflow/movimientos/{$net->id}", array_replace($datos, ['clave' => (string) Str::uuid(), 'monto' => '24000', 'motivo' => 'Otra corrección']), fn ($r) => $cc->update($r, $net->id)), 422);
echo '   Internet: '.plata($net->fresh()->monto).' · correcciones: '.DB::table('cashflow_correcciones')->where('movimiento_id', $net->id)->count()."\n";
estado('fin 46b');
