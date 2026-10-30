<?php
// Día 46, martes 24/11, 09:30 — por pantalla, antes de la rutina: Pablo abre la caja y da de alta a Milagros con
// ingreso hoy (tramo «Fin de mes», 40%) y la inscripción nueva de $8.000; paga todo por Mercado Pago.
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController};
use App\Models\{Grupo, DeudaCuota, Pago};
use Illuminate\Support\Str;
$pablo = U('Pablo Ledesma'); $caja = app(CajaWebController::class);
estado('inicio 46');
Illuminate\Support\Facades\Auth::guard('web')->setUser($pablo);
$p = app(App\Services\CajaService::class)->propuestaApertura();
paso('46.0', 'Pablo abre con lo propuesto ('.plata($p['efectivo_heredado']).')', enviar($pablo, 'POST', '/caja/apertura', ['efectivo_inicial' => (string) $p['efectivo_heredado'], 'confirmacion' => '1', 'caja_origen_id' => $p['caja_origen_id']], fn ($r) => $caja->abrir($r)), 200);
$g3 = Grupo::whereHas('deporte', fn ($d) => $d->where('nombre', 'Patín'))->whereHas('nivel', fn ($n) => $n->where('nombre', 'Avanzadas'))->firstOrFail();
$p1 = DB::table('grupo_planes')->where('grupo_id', $g3->id)->where('clases_por_semana', 1)->first();
echo '   aviso del alta: '.json_encode(app(App\Services\PagoCuotaService::class)->previsualizarCuotaAlta('2026-11-24', $p1->id), JSON_UNESCAPED_UNICODE)."\n";
paso('46.1a', 'alta Milagros Ferreyra', enviar($pablo, 'POST', '/alumnos', ['nombre' => 'Milagros', 'apellido' => 'Ferreyra', 'dni' => '52999888', 'fecha_nacimiento' => '2014-02-11', 'celular' => '',
    'nombre_tutor' => 'Silvina Ferreyra', 'telefono_tutor' => '1177889900', 'deporte_id' => $g3->deporte_id, 'grupo_id' => $g3->id, 'plan_id' => $p1->id, 'fecha_alta' => '2026-11-24',
    'alta_token' => (string) Str::uuid()], fn ($r) => app(AlumnoWebController::class)->store($r)), 200);
$mi = A('Ferreyra, Milagros');
echo '   Milagros: '.DeudaCuota::where('alumno_id', $mi->id)->get()->map(fn ($d) => "$d->periodo:$d->estado ".plata($d->monto_original).' ('.$d->porcentaje_alta.'%)')->implode(' ')
    .' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id', $mi->id)->where('tipo', 'INSCRIPCION')->value('monto_original'))."\n";
paso('46.1b', 'Milagros paga inscripción y noviembre por MP', enviar($pablo, 'POST', "/caja/cobrar/{$mi->id}", ['tipo_caja_id' => M('Mercado Pago'), 'periodos' => ['2026-11'], 'fecha_pago' => '2026-11-24'], fn ($r) => $caja->pagar($r, $mi->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $mi->id)->latest('id')->value('monto_final'))."\n";
estado('fin 46a');
