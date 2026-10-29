<?php
/**
 * PRU-04 · Lo rutinario de un día de la prueba, por consola (decisión de Carlos, 25/10/2026).
 *
 * Solo lo que ya dio bien siempre: abrir caja, cobros simples, gastos e ingresos chicos, listas de
 * las clases del día, cierre sin diferencia y validación. Cada paso pasa por el mismo controlador que
 * la pantalla, con el usuario de ese rol, así quedan iguales Cobranza, Cashflow, Reportes y la caja.
 * Lo nuevo y las reparaciones los recorre una persona por pantalla.
 *
 * Uso, en el servidor de prueba, como wingstest y DESPUÉS de fijar el reloj del día:
 *   php scripts/pruebas/rutina/rutina.php docs/06-pruebas/PRU-04/rutina/dia-13-manana.json [--simular]
 * Con --simular hace todo dentro de una transacción y la revierte al final.
 *
 * Solo corre en el sitio de prueba (staging + wingstest) o en una base descartable wings_testing_*.
 */
$raiz = getenv("WINGS_APP") ?: dirname(__DIR__, 3);
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\{CajaWebController, ClaseWebController};
use App\Models\{Grupo, Profesor};
use App\Models\{Alumno, CajaOperativa, Clase, Subrubro, TipoCaja, User};
use App\Services\CajaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

$base = config('database.connections.mysql.database');
if (!(($base === 'wingstest' && app()->environment('staging')) || str_starts_with($base, 'wings_testing_'))) {
    fwrite(STDERR, "Solo corre en el sitio de prueba o en una base descartable (base: {$base}).\n");
    exit(2);
}
$archivo = $argv[1] ?? '';
$simular = in_array('--simular', $argv, true);
$plan = json_decode(@file_get_contents($archivo), true);
if (!is_array($plan) || !isset($plan['dia'], $plan['pasos'])) {
    fwrite(STDERR, "Plan inválido: {$archivo}\n");
    exit(2);
}
// Solo para simular un día que todavía no llegó, al armar el guion: no toca el reloj del sitio.
foreach ($argv as $a) {
    if (str_starts_with($a, '--fecha=')) {
        if (!$simular) { fwrite(STDERR, "--fecha solo se usa con --simular.\n"); exit(2); }
        Carbon\Carbon::setTestNow(substr($a, 8));
    }
}
if (now()->toDateString() !== $plan['dia']) {
    fwrite(STDERR, 'El reloj de Wings dice '.now()->toDateString().", el plan es del {$plan['dia']}. Fijá el reloj primero.\n");
    exit(2);
}
config(['session.driver' => 'array']);

$usuario = fn (string $nombre) => User::where('name', $nombre)->where('activo', true)->firstOrFail();
$alumno = function (string $nombre) {
    [$apellido, $nom] = array_map('trim', explode(',', $nombre, 2));
    $a = Alumno::where('apellido', $apellido)->where('nombre', $nom)->where('activo', true)->get();
    if ($a->count() !== 1) throw new RuntimeException("Alumno «{$nombre}»: hay {$a->count()} activos con ese nombre.");
    return $a->first();
};
$medio = fn (string $nombre) => TipoCaja::where('nombre', $nombre)->firstOrFail()->id;

/** Corre una acción del controlador como ese usuario, igual que el envío de la pantalla. */
$enviar = function (User $como, string $uri, array $datos, callable $accion, bool $json = false) use ($app) {
    Auth::guard('web')->setUser($como);
    $req = Request::create($uri, 'POST', $datos);
    if ($json) $req->headers->set('X-Requested-With', 'XMLHttpRequest');
    $sesion = $app['session']->driver();
    $sesion->forget(['error', 'success', 'errors']);
    $req->setLaravelSession($sesion);
    $req->setUserResolver(fn () => $como);
    $app->instance('request', $req);
    try {
        $resp = $accion($req);
    } catch (Illuminate\Validation\ValidationException $e) {
        throw new RuntimeException(implode(' ', array_merge(...array_values($e->errors()))));
    }
    if ($json) {
        $d = json_decode($resp->getContent(), true);
        if (empty($d['success'])) throw new RuntimeException($d['message'] ?? 'Rechazado.');
        return $d['message'] ?? 'ok';
    }
    if ($resp->getStatusCode() >= 400) throw new RuntimeException('Respuesta '.$resp->getStatusCode());
    if ($error = $sesion->get('error')) throw new RuntimeException($error);
    if ($errores = $sesion->get('errors')) throw new RuntimeException(implode(' ', $errores->all()));
    return $sesion->get('success') ?? 'ok';
};

$caja = app(CajaService::class);
$cajaCtrl = app(CajaWebController::class);
$claseCtrl = app(ClaseWebController::class);
$resumen = fn () => app(App\Services\CobranzaEstadoService::class)->resumenDashboard();

DB::beginTransaction();
$n = 0;
try {
    foreach ($plan['pasos'] as $paso) {
        $n++;
        $como = $usuario($paso['como']);
        switch ($paso['accion']) {
            case 'abrir_caja':
                $p = $caja->propuestaApertura();
                $msg = $enviar($como, '/caja/apertura', ['efectivo_inicial' => $p['efectivo_heredado'] ?? 0,
                    'confirmacion' => '1', 'caja_origen_id' => $p['caja_origen_id']], fn ($r) => $cajaCtrl->abrir($r));
                $texto = "abre caja con \$".number_format((float) ($p['efectivo_heredado'] ?? 0), 2, ',', '.');
                break;
            case 'cobrar':
                $a = $alumno($paso['alumno']);
                $datos = ['tipo_caja_id' => $medio($paso['medio']), 'periodos' => $paso['periodos'] ?? [], 'fecha_pago' => now()->toDateString(),
                    'observaciones' => $paso['obs'] ?? null];
                if (isset($paso['montos'])) $datos['montos_cuota'] = $paso['montos'];
                // Mes en curso sin cuota guardada: la pantalla lo manda como elegido a mano (T77).
                if (isset($paso['manuales'])) $datos['periodos_manuales'] = $paso['manuales'];
                if (isset($paso['entrega'])) $datos['monto_entregado'] = $paso['entrega'];
                $msg = $enviar($como, "/caja/cobrar/{$a->id}", $datos, fn ($r) => $cajaCtrl->pagar($r, $a->id));
                $pago = App\Models\Pago::where('alumno_id', $a->id)->latest('id')->first();
                $texto = "cobra a {$paso['alumno']} \$".number_format((float) $pago?->monto_final, 2, ',', '.')." por {$paso['medio']}";
                break;
            case 'movimiento':
                $sub = Subrubro::where('nombre', $paso['subrubro'])->firstOrFail();
                $msg = $enviar($como, '/caja/movimiento', ['subrubro_id' => $sub->id, 'tipo_caja_id' => $medio($paso['medio']),
                    'monto' => $paso['monto'], 'observaciones' => $paso['obs'] ?? null], fn ($r) => $cajaCtrl->movimientoStore($r));
                $texto = "{$paso['subrubro']} \${$paso['monto']} por {$paso['medio']}";
                break;
            case 'lista':
                // Por número, o por grupo y hora del día cuando el guion se arma sin ver la base.
                $clase = isset($paso['clase']) ? Clase::findOrFail($paso['clase']) : (function () use ($paso) {
                    [$dep, $niv] = array_map('trim', explode('—', $paso['grupo'], 2));
                    $c = Clase::whereDate('fecha', today())->whereTime('hora_inicio', $paso['hora'].':00')->where('cancelada', false)
                        ->whereHas('grupo', fn ($g) => $g->whereHas('deporte', fn ($d) => $d->where('nombre', $dep))
                            ->whereHas('nivel', fn ($n) => $n->where('nombre', $niv)))->get();
                    if ($c->count() !== 1) throw new RuntimeException("Clase {$paso['grupo']} {$paso['hora']}: hay {$c->count()} hoy.");
                    return $c->first();
                })();
                $ausentes = array_map(fn ($x) => $alumno($x)->id, $paso['ausentes'] ?? []);
                // B3 (27/10): el deudor y el nuevo sin primer pago entran solo con motivo. El plan lo
                // trae por alumno; si falta, Wings rechaza la lista entera y no se guarda nada del día.
                $motivos = [];
                foreach ($paso['motivos'] ?? [] as $quien => $motivo) $motivos[$alumno($quien)->id] = $motivo;
                $items = Alumno::enGrupoElDia($clase->grupo_id, $clase->fecha)->where('activo', true)->pluck('id')
                    ->map(fn ($id) => ['alumno_id' => $id, 'presente' => !in_array($id, $ausentes, true) ? 1 : 0, 'motivo_exceso' => 'EXTRA']
                        + (isset($motivos[$id]) ? ['motivo_excepcion' => $motivos[$id]] : []))->all();
                $msg = $enviar($como, "/clases/{$clase->id}/asistencias", ['items' => $items], fn ($r) => $claseCtrl->storeAsistencias($r, $clase->id), true);
                $presentes = count(array_filter($items, fn ($i) => $i['presente']));
                $texto = "lista de la #{$clase->id}: ".count($items)." alumnos, {$presentes} presentes";
                break;
            case 'crear_serie':
                // El calendario del mes, como lo carga el dueño en Clases → Nueva (serie con horario por día).
                // Grupo «Deporte — Nivel»; dias {"1": ["16:00", "17:00"]}, con 0 = domingo.
                [$dep, $niv] = array_map('trim', explode('—', $paso['grupo'], 2));
                $g = Grupo::whereHas('deporte', fn ($d) => $d->where('nombre', $dep))->whereHas('nivel', fn ($n) => $n->where('nombre', $niv))->firstOrFail();
                $profes = array_map(function ($nom) {
                    [$ape, $n] = array_map('trim', explode(',', $nom, 2));
                    return Profesor::where('apellido', $ape)->where('nombre', $n)->where('activo', true)->firstOrFail()->id;
                }, $paso['profesores']);
                $horarios = [];
                foreach ($paso['dias'] as $dia => [$ini, $fin]) $horarios[(int) $dia] = ['hora_inicio' => $ini, 'hora_fin' => $fin];
                $msg = $enviar($como, '/clases', ['grupo_id' => $g->id, 'profesores' => $profes, 'tipo_creacion' => 'recurrente',
                    'fecha_desde' => $paso['desde'], 'fecha_hasta' => $paso['hasta'], 'dias_semana' => array_keys($horarios),
                    'horarios' => $horarios], fn ($r) => $claseCtrl->store($r));
                $texto = "serie de {$paso['grupo']} del {$paso['desde']} al {$paso['hasta']}";
                break;
            case 'cerrar_caja':
                $c = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $usuario($paso['de'] ?? $paso['como'])->id)->firstOrFail();
                $esperado = $caja->arqueoCaja($c->id)['efectivo_esperado'];
                $contado = $paso['contado'] ?? $esperado;
                $msg = $enviar($como, "/caja/{$c->id}/cerrar", ['efectivo_contado' => $contado, 'cambio_retenido' => $paso['cambio'] ?? 10000],
                    fn ($r) => $cajaCtrl->cerrar($r, $c->id));
                $texto = "cierra la caja #{$c->id}: esperado \$".number_format((float) $esperado, 2, ',', '.').", contado \$".number_format((float) $contado, 2, ',', '.');
                break;
            case 'validar_caja':
                $c = CajaOperativa::where('estado', 'CERRADA')->where('usuario_operativo_id', $usuario($paso['de'])->id)->orderByDesc('id')->firstOrFail();
                foreach ($c->movimientos()->get() as $m) {
                    $enviar($como, "/cajas/{$c->id}/movimientos/{$m->id}/verificar", ['verificado' => true, 'huella' => $m->huellaVerificacion()],
                        fn ($r) => $cajaCtrl->verificarMovimiento($r, $c->id, $m->id));
                }
                $msg = $enviar($como, "/cajas/{$c->id}/validar", [], fn ($r) => $cajaCtrl->validar($c->id));
                $texto = "valida la caja #{$c->id} ({$c->movimientos()->count()} renglones tildados)";
                break;
            default:
                throw new RuntimeException("Acción desconocida: {$paso['accion']}");
        }
        echo sprintf("%2d. %s %s — %s\n", $n, $paso['como'], $texto, $msg);
    }
    $r = $resumen();
    echo 'Cobranza: $'.number_format($r['total_adeudado'], 2, ',', '.').' · '.json_encode($r['por_estado'])."\n";
    if ($simular) { DB::rollBack(); echo "SIMULADO: no se guardó nada.\n"; } else { DB::commit(); echo "GUARDADO.\n"; }
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, "Paso {$n} falló, no se guardó NADA del plan: ".$e->getMessage()."\n");
    exit(1);
}
