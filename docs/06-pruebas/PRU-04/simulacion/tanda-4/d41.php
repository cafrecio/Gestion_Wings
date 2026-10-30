<?php
// Día 41, jueves 19/11, 11:00 — por pantalla: el dueño arma Fútbol Infantiles para diciembre (nivel, grupo con
// errores primero, profesor con errores primero); Sandra da de alta a Renzo con ingreso el 1/12 y cobra la
// inscripción; recibos y caja ajena vistos por la operativa y por la profesora.
require __DIR__.'/lib.php';
use App\Http\Controllers\{NivelWebController, GrupoWebController, ProfesorWebController, AlumnoWebController, CajaWebController, ReciboController};
use App\Models\{Alumno, Grupo, Nivel, Deporte, DeudaCuota, Pago, Profesor};
use Illuminate\Support\Str;
$admin = U('Admin Prueba'); $sandra = U('Sandra Vidal'); $lucia = U('Lucía Gaitán'); $caja = app(CajaWebController::class);
estado('inicio 41 (después de la rutina de la mañana)');
$futbol = Deporte::where('nombre', 'Fútbol')->firstOrFail(); $patin = Deporte::where('nombre', 'Patín')->firstOrFail();

// 41.1 Niveles (CAT-004)
paso('41.1a', 'nivel «Infantil»', enviar($admin, 'POST', '/niveles', ['nombre' => 'Infantil'], fn ($r) => app(NivelWebController::class)->store($r)), 200);
$niv = Nivel::where('nombre', 'Infantil')->firstOrFail();
paso('41.1b', 'lo corrige a «Infantiles»', enviar($admin, 'PUT', "/niveles/{$niv->id}", ['nombre' => 'Infantiles'], fn ($r) => app(NivelWebController::class)->update($r, $niv->id)), 200);
paso('41.1c', 'otro «infantiles » (repetido)', enviar($admin, 'POST', '/niveles', ['nombre' => 'infantiles '], fn ($r) => app(NivelWebController::class)->store($r)), 422);
echo '   niveles: '.Nivel::orderBy('id')->pluck('nombre')->implode(', ')."\n";

// 41.2 Grupo mal cargado y después bien (GRU-002)
$g = fn (array $d) => enviar($admin, 'POST', '/grupos', $d, fn ($r) => app(GrupoWebController::class)->store($r));
$pri = Nivel::where('nombre', 'Principiantes')->value('id');
paso('41.2a', 'Patín Principiantes otra vez', $g(['deporte_id' => $patin->id, 'nivel_id' => $pri, 'planes' => [['clases_por_semana' => 1, 'precio_mensual' => '30000']]]), 422);
paso('41.2b', 'Fútbol Infantiles sin planes', $g(['deporte_id' => $futbol->id, 'nivel_id' => $niv->id, 'planes' => []]), 422);
paso('41.2c', 'plan a $0', $g(['deporte_id' => $futbol->id, 'nivel_id' => $niv->id, 'planes' => [['clases_por_semana' => 1, 'precio_mensual' => '0']]]), 422);
paso('41.2d', '8 por semana', $g(['deporte_id' => $futbol->id, 'nivel_id' => $niv->id, 'planes' => [['clases_por_semana' => 8, 'precio_mensual' => '25000']]]), 422);
paso('41.2e', 'dos planes de 1 por semana', $g(['deporte_id' => $futbol->id, 'nivel_id' => $niv->id, 'planes' => [['clases_por_semana' => 1, 'precio_mensual' => '25000'], ['clases_por_semana' => 1, 'precio_mensual' => '26000']]]), 422);
echo '   grupos de Fútbol Infantiles: '.Grupo::where('nivel_id', $niv->id)->count()."\n";
paso('41.2f', 'Fútbol Infantiles bien: 1 por semana $25.000 y 2 por semana $38.000', $g(['deporte_id' => $futbol->id, 'nivel_id' => $niv->id,
    'planes' => [['clases_por_semana' => 1, 'precio_mensual' => '25000'], ['clases_por_semana' => 2, 'precio_mensual' => '38000']]]), 200);
$gi = Grupo::where('nivel_id', $niv->id)->firstOrFail();
echo '   grupo #'.$gi->id.' planes: '.DB::table('grupo_planes')->where('grupo_id', $gi->id)->get()->map(fn ($p) => $p->clases_por_semana.'x '.plata($p->precio_mensual))->implode(', ')."\n";

// 41.3 Profesor mal cargado y después bien (PRO-002)
$base = ['nombre' => 'Gonzalo', 'apellido' => 'Ibarra', 'deporte_id' => $futbol->id, 'dni' => '33444555', 'fecha_nacimiento' => '1990-06-15',
    'direccion' => 'Av. Rivadavia 4500', 'localidad' => 'CABA', 'telefono' => '11-4444-5555', 'email' => 'gonzalo.ibarra@example.com', 'porcentaje_comision' => '35'];
$p = fn (array $d) => enviar($admin, 'POST', '/profesores', $d, fn ($r) => app(ProfesorWebController::class)->store($r));
paso('41.3a', 'DNI de Lucía', $p(array_replace($base, ['dni' => Profesor::find(1)->dni])), 422);
paso('41.3b', 'comisión 101', $p(array_replace($base, ['porcentaje_comision' => '101'])), 422);
paso('41.3c', 'mail sin arroba', $p(array_replace($base, ['email' => 'gonzalo.ibarra.example.com'])), 422);
paso('41.3d', 'sin nombre', $p(array_replace($base, ['nombre' => ''])), 422);
echo '   profesores: '.Profesor::count().' · subrubros de sueldo: '.DB::table('subrubros')->where('nombre', 'like', '%Ibarra%')->count()."\n";
paso('41.3e', 'Gonzalo Ibarra bien, comisión 35%', $p($base), 200);
echo '   subrubro de sueldo: '.DB::table('subrubros')->where('nombre', 'like', '%Ibarra%')->pluck('nombre')->implode(', ')."\n";

// 41.4 Sandra: alta de Renzo Acuña con ingreso el 1/12 (ALU-007)
$plan1 = DB::table('grupo_planes')->where('grupo_id', $gi->id)->where('clases_por_semana', 1)->first();
$svc = app(App\Services\PagoCuotaService::class);
$alta = fn (string $fecha) => enviar($sandra, 'POST', '/alumnos', ['nombre' => 'Renzo', 'apellido' => 'Acuña', 'dni' => '56123456', 'fecha_nacimiento' => '2019-03-14', 'celular' => '',
    'nombre_tutor' => 'Marcela Acuña', 'telefono_tutor' => '1155667788', 'deporte_id' => $futbol->id, 'grupo_id' => $gi->id, 'plan_id' => $plan1->id, 'fecha_alta' => $fecha,
    'alta_token' => (string) Str::uuid()], fn ($r) => app(AlumnoWebController::class)->store($r));
echo '   aviso con 4/1/2027: '.json_encode(rescue(fn () => $svc->previsualizarCuotaAlta('2027-01-04', $plan1->id), fn ($e) => $e->getMessage(), false), JSON_UNESCAPED_UNICODE)."\n";
paso('41.4a', 'Renzo con ingreso el 4/1/2027', $alta('2027-01-04'), 422);
echo '   aviso con 1/12: '.json_encode($svc->previsualizarCuotaAlta('2026-12-01', $plan1->id), JSON_UNESCAPED_UNICODE)."\n";
paso('41.4b', 'Renzo con ingreso el 1/12', $alta('2026-12-01'), 200);
$re = A('Acuña, Renzo');
$fila = app(App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS')->firstWhere('id', $re->id);
echo '   Renzo: activo '.$re->activo.' · cuotas: '.DeudaCuota::where('alumno_id', $re->id)->get()->map(fn ($d) => "$d->periodo:$d->estado ".plata($d->monto_original))->implode(' ')
    .' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id', $re->id)->where('tipo', 'INSCRIPCION')->value('monto_original'))
    .' · Cobranza: '.($fila ? $fila->estado_cobranza.' '.plata($fila->deuda_total) : 'no figura')."\n";
estado('después del alta de Renzo');
// 41.5 la mamá paga la inscripción en efectivo
paso('41.5', 'Renzo paga la inscripción', enviar($sandra, 'POST', "/caja/cobrar/{$re->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => [], 'monto_entregado' => '5000', 'fecha_pago' => '2026-11-19'],
    fn ($r) => $caja->pagar($r, $re->id), true), 200);
$fila = app(App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS')->firstWhere('id', $re->id);
echo '   Renzo después: '.($fila ? $fila->estado_cobranza.' '.plata($fila->deuda_total) : 'no figura en Cobranza')."\n";

// 41.6 recibos y caja ajena (REC-004, ACC-016)
$pd = Pago::whereHas('alumno', fn ($q) => $q->where('nombre', 'Dante')->where('apellido', 'Ramos'))->latest('id')->firstOrFail();
$rc = app(ReciboController::class);
paso('41.6a', "Sandra abre el recibo #{$pd->id} (lo cobró Rocío)", enviar($sandra, 'GET', "/recibos/cuota/{$pd->id}", [], fn ($r) => $rc->cuota($r, $pd->id)), 200);
paso('41.6b', 'Sandra cambia el número: 99999', enviar($sandra, 'GET', '/recibos/cuota/99999', [], fn ($r) => $rc->cuota($r, 99999)), 404);
paso('41.6c', 'Lucía (profesora) abre ese recibo', enviar($lucia, 'GET', "/recibos/cuota/{$pd->id}", [], fn ($r) => $rc->cuota($r, $pd->id)), 403);
$k72 = DB::table('cajas_operativas')->where('usuario_operativo_id', U('Rocío Paz')->id)->orderByDesc('id')->value('id');
paso('41.6d', "Sandra abre el detalle de la caja #{$k72} de Rocío", enviar($sandra, 'GET', "/caja/{$k72}/detalle", [], fn ($r) => $caja->detalle($k72)), 200);
paso('41.6e', "Sandra abre el resumen de la caja #{$k72} de Rocío", enviar($sandra, 'GET', "/caja/{$k72}/resumen", [], fn ($r) => $caja->resumen($k72)), 403);
estado('fin 41 (antes de la noche)');
