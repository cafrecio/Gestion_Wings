<?php
// Simulación local de la prueba por días (copia de wingstest en wings_testing_guion).
// Se corre con DB_DATABASE=wings_testing_guion y FECHA_SIMULADA fijada. Solo para armar el guion.
$raiz = dirname(__DIR__, 5);
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Alumno, User, TipoCaja, Subrubro, Profesor, Clase, CajaOperativa};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Artisan};

if (config('database.connections.mysql.database') !== 'wings_testing_guion') { fwrite(STDERR, "base equivocada\n"); exit(2); }
config(['session.driver' => 'array']);

function U(string $n): User { return User::where('name', $n)->firstOrFail(); }
function A(string $n): Alumno {
    [$ap, $no] = array_map('trim', explode(',', $n, 2));
    $a = Alumno::where('apellido', $ap)->where('nombre', $no)->get();
    if ($a->count() !== 1) throw new RuntimeException("Alumno {$n}: {$a->count()}");
    return $a->first();
}
function M(string $n): int { return TipoCaja::where('nombre', $n)->firstOrFail()->id; }
function S(string $n): int { return Subrubro::where('nombre', $n)->firstOrFail()->id; }
function P(string $n): Profesor { [$ap, $no] = array_map('trim', explode(',', $n, 2)); return Profesor::where('apellido', $ap)->where('nombre', $no)->firstOrFail(); }

/** Envía como la pantalla: devuelve [status, mensaje, json]. No tira excepción salvo error interno. */
function enviar(User $como, string $metodo, string $uri, array $datos, callable $accion, bool $json = false): array {
    global $app;
    Auth::guard('web')->setUser($como);
    $req = Request::create($uri, $metodo, $datos);
    if ($json) { $req->headers->set('X-Requested-With', 'XMLHttpRequest'); $req->headers->set('Accept', 'application/json'); }
    $ses = $app['session']->driver();
    $ses->forget(['error', 'success', 'errors', 'warning', 'info']);
    $req->setLaravelSession($ses);
    $req->setUserResolver(fn () => $como);
    $app->instance('request', $req);
    try {
        $r = $accion($req);
    } catch (Illuminate\Validation\ValidationException $e) {
        return [422, implode(' ', array_merge(...array_values($e->errors()))), null];
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        return [$e->getStatusCode(), $e->getMessage() ?: 'HTTP '.$e->getStatusCode(), null];
    } catch (Throwable $e) {
        return [500, get_class($e).': '.$e->getMessage(), null];
    }
    $st = method_exists($r, 'getStatusCode') ? $r->getStatusCode() : 200;
    $body = method_exists($r, 'getContent') ? $r->getContent() : '';
    $d = json_decode($body, true);
    if (is_array($d)) return [$st, $d['message'] ?? ($d['mensaje'] ?? json_encode($d, JSON_UNESCAPED_UNICODE)), $d];
    $msg = $ses->get('error') ?? ($ses->get('errors') ? implode(' ', $ses->get('errors')->all()) : null) ?? $ses->get('success') ?? $ses->get('warning') ?? '';
    if ($ses->get('error') || $ses->get('errors')) $st = max($st, 400) === 302 ? 400 : max($st, 400);
    return [$st, (string) $msg, null];
}

function paso(string $id, string $txt, array $r, ?int $esperado = null) {
    [$st, $msg] = $r;
    $ok = $esperado === null ? '' : (($esperado < 400) === ($st < 400) ? ' OK' : ' <<< NO ESPERADO');
    echo sprintf("[%s] %s → %d %s%s\n", $id, $txt, $st, mb_substr($msg, 0, 300), $ok);
}

function plata($n) { return '$'.number_format((float) $n, 2, ',', '.'); }

function estado(string $titulo = '') {
    $c = app(App\Services\CobranzaEstadoService::class)->resumenDashboard();
    $sal = DB::select("select t.nombre n, t.saldo_inicial + coalesce((select sum(m.monto) from cashflow_movimientos m where m.tipo_caja_id=t.id),0) s from tipos_caja t order by t.id");
    $mes = now()->format('Y-m');
    $rep = app(App\Services\ReporteMensualService::class);
    echo "== {$titulo} ".now()->toDateTimeString()."\n";
    echo "   Cobranza: ".plata($c['total_adeudado'])." activos {$c['total_alumnos_activos']} ".json_encode($c['por_estado'])."\n";
    echo "   Saldos: ".implode(' · ', array_map(fn ($x) => $x->n.' '.plata($x->s), $sal))."\n";
    $cajas = DB::select("select id,usuario_operativo_id u,estado,efectivo_esperado,efectivo_contado from cajas_operativas where date(apertura_at)=? or estado='ABIERTA'", [today()->toDateString()]);
    foreach ($cajas as $k) echo "   Caja #{$k->id} u{$k->u} {$k->estado} esperado ".plata($k->efectivo_esperado)."\n";
}

/** Pide una pantalla entera, pasando por las rutas y sus filtros (como el navegador). Devuelve [status, destino o título, html]. */
function http(User $como, string $uri, string $metodo = 'GET', array $datos = []): array {
    global $app;
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    app('auth')->forgetGuards();
    app('auth')->guard('web')->setUser($como);
    $req = Request::create($uri, $metodo, $datos);
    $req->setLaravelSession($app['session']->driver());
    $resp = $kernel->handle($req);
    $html = (string) $resp->getContent();
    $dest = $resp->isRedirection() ? 'redirige a '.$resp->headers->get('Location') : (preg_match('/<title>(.*?)<\/title>/s', $html, $m) ? trim(html_entity_decode($m[1])) : '');
    return [$resp->getStatusCode(), $dest, $html];
}
/** Las opciones del menú lateral de una pantalla. */
function menu(string $html): string {
    preg_match_all('/<a[^>]+class="ds-nav-link[^"]*"[^>]*>(.*?)<\/a>/s', $html, $m);
    return implode(' · ', array_filter(array_map(fn ($x) => trim(preg_replace('/\s+/', ' ', strip_tags($x))), $m[1])));
}
