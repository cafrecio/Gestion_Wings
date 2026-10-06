<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\DeudaCuota;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Pago;
use App\Models\ReglaPrimerPago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Notifications\CobroConDeudaAnteriorNotification;
use App\Services\CajaService;
use App\Services\FormatoExcelCargaService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class CajaWebController extends Controller
{
    public function __construct(
        private CajaService $cajaService,
        private PagoCuotaService $pagoCuotaService
    ) {}

    public function configuracionMostrador()
    {
        $propuesta = $this->cajaService->propuestaApertura();
        $tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        return view('caja.configuracion', compact('propuesta', 'tiposCaja'));
    }

    public function configurarMostrador(Request $request)
    {
        $datos = $request->validate(['tipo_caja_id' => 'required|integer|exists:tipos_caja,id']);
        $this->cajaService->configurarMostrador((int) $datos['tipo_caja_id'], Auth::id());
        return redirect()->route('web.caja.index')->with('success', 'Medio de efectivo configurado.');
    }

    public function apertura()
    {
        $propuesta = $this->cajaService->propuestaApertura();
        $tipoEfectivo = TipoCaja::find($propuesta['tipo_caja_id']);
        $turnoAbierto = CajaOperativa::where('estado', 'ABIERTA')->with('usuarioOperativo')->first();
        $operativos = Auth::user()->isAdmin()
            ? User::where('rol', User::ROL_OPERATIVO)->where('activo', true)->orderBy('name')->get()
            : collect();
        return view('caja.apertura', compact('propuesta', 'tipoEfectivo', 'turnoAbierto', 'operativos'));
    }

    public function abrir(Request $request)
    {
        $this->normalizarImportesArqueo($request, ['efectivo_inicial']);
        $operativoId = Auth::id();
        if (Auth::user()->isAdmin()) {
            $request->validate(['operativo_id' => ['required', 'integer', Rule::exists('users', 'id')->where('rol', User::ROL_OPERATIVO)->where('activo', true)]]);
            $operativoId = (int) $request->input('operativo_id');
        }
        $caja = $this->cajaService->abrirCajaOperativa($operativoId, $request->all(), Auth::id());
        return redirect()->route('web.caja.resumen', $caja->id)->with('success', 'Caja abierta con el efectivo declarado.');
    }

    public function cierre(int $id)
    {
        $caja = CajaOperativa::with('usuarioOperativo')->findOrFail($id);
        abort_unless(Auth::user()->isAdmin() || $caja->usuario_operativo_id === Auth::id(), 403);
        if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA', 'CERRADA'])) {
            return redirect()->route('web.caja.resumen', $id)->with('error', 'La caja ya está validada.');
        }
        // Una caja histórica CERRADA sin conteo puede completar la declaración, no operar.
        if ($caja->estado === 'CERRADA' && $caja->efectivo_contado !== null) {
            return redirect()->route('web.caja.resumen', $id);
        }
        $arqueo = $this->cajaService->arqueoCaja($id);
        return view('caja.cierre', compact('caja', 'arqueo'));
    }

    private function normalizarImportesArqueo(Request $request, array $campos): void
    {
        foreach ($campos as $campo) {
            $crudo = $request->input($campo);
            $normalizado = FormatoExcelCargaService::monto($crudo);
            if ($normalizado !== null) {
                $request->merge([$campo => $normalizado]);
            }
        }
    }

    private function aperturaNecesaria(): bool
    {
        if (!Auth::user()->isOperativo()) return false;
        $caja = $this->cajaService->obtenerCajaAbierta(Auth::id());
        return !$caja || $caja->efectivo_inicial === null || $caja->tipo_caja_efectivo_id === null;
    }

    // ── Índice: listado de cajas en cards ────────────────────────────────

    public function index(Request $request)
    {
        if ($request->ajax()) {
            session()->reflash();
        }

        $user = Auth::user();

        $cajaVieja  = false;
        $sinCajaHoy = false;
        $operativos = collect();
        $mes        = now()->format('Y-m');

        if ($user->isAdmin()) {
            $operativos = User::where('rol', User::ROL_OPERATIVO)->orderBy('name')->get();
            $mes        = $request->input('mes', now()->format('Y-m'));
            [$year, $month] = explode('-', $mes);

            $query = CajaOperativa::with(['usuarioOperativo', 'movimientos.subrubro.rubro'])
                ->whereYear('apertura_at', $year)
                ->whereMonth('apertura_at', $month);

            if ($request->filled('operativo_id')) {
                $query->where('usuario_operativo_id', $request->operativo_id);
            }

            $cajas = $query->orderByDesc('apertura_at')->get();
        } else {
            try {
                $this->cajaService->validarCajaViejaAbierta($user->id);
            } catch (\Exception $e) {
                $cajaVieja = true;
            }

            // Últimos 30 días (no mes calendario: el día 1 debe verse la caja de ayer)
            $cajas = CajaOperativa::where('usuario_operativo_id', $user->id)
                ->where('apertura_at', '>=', now()->subDays(30)->startOfDay())
                ->with(['movimientos.subrubro.rubro'])
                ->orderByDesc('apertura_at')
                ->get();

            $cajaAbiertaHoy = $cajas->first(
                fn($c) => $c->estado === 'ABIERTA' && $c->apertura_at->isToday()
            );
            $sinCajaHoy = !$cajaAbiertaHoy && !$cajaVieja;
        }

        $mostradorConfigurado = $this->cajaService->propuestaApertura()['tipo_caja_id'] !== null;
        return view('caja.index', compact('cajas', 'cajaVieja', 'sinCajaHoy', 'operativos', 'mes', 'mostradorConfigurado'));
    }

    // ── Historial: movimientos del último trimestre (solo lectura) ───────
    // Une movimientos de cajas operativas y cashflow directo del admin,
    // limitado a subrubros permitidos para OPERATIVO. Los asientos de cashflow
    // con referencia CAJA_OPERATIVA se excluyen porque duplican la caja.

    public function historial(Request $request)
    {
        $tz          = 'America/Argentina/Buenos_Aires';
        $desdeLimite = now($tz)->subDays(90)->startOfDay();

        $desde = $desdeLimite;
        if ($request->filled('desde')) {
            $desdePedida = Carbon::parse($request->input('desde'), $tz)->startOfDay();
            $desde = $desdePedida->greaterThan($desdeLimite) ? $desdePedida : $desdeLimite;
        }
        $hasta = $request->filled('hasta')
            ? Carbon::parse($request->input('hasta'), $tz)->endOfDay()
            : now($tz)->endOfDay();

        $subrubrosVisibles = Subrubro::with('rubro')
            ->where('permitido_para', 'OPERATIVO')
            ->orderBy('nombre')
            ->get();
        $idsVisibles = $subrubrosVisibles->pluck('id');

        $subrubroFiltro = $request->input('subrubro_id');
        $tipoFiltro     = $request->input('tipo'); // INGRESO | EGRESO

        // Fuente 1: movimientos de cajas operativas (activos, cualquier operativo)
        $movsCaja = MovimientoOperativo::with(['subrubro.rubro', 'alumno', 'usuario', 'tipoCaja'])
            ->activos()
            ->whereIn('subrubro_id', $idsVisibles)
            ->when($subrubroFiltro, fn($q) => $q->where('subrubro_id', $subrubroFiltro))
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get();

        // Fuente 2: cashflow directo del admin (sin los reflejos de cajas)
        $movsAdmin = CashflowMovimiento::with(['subrubro.rubro', 'usuarioAdmin', 'tipoCaja'])
            ->whereIn('subrubro_id', $idsVisibles)
            ->when($subrubroFiltro, fn($q) => $q->where('subrubro_id', $subrubroFiltro))
            // Excluir los reflejos de caja (REF_CAJA): duplican lo que ya
            // se lista desde movimientos_operativos. Tras D2 el valor viejo
            // 'MOVIMIENTO_OPERATIVO' del seeder quedó normalizado a REF_CAJA.
            ->where(fn($q) => $q->whereNull('referencia_tipo')
                                ->orWhere('referencia_tipo', '!=', CashflowMovimiento::REF_CAJA))
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get();

        // Alumnos de los cobros de cuota hechos por admin (via Pago referenciado)
        $pagosIds = $movsAdmin->where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)->pluck('referencia_id')->filter();
        $alumnosPorPago = $pagosIds->isEmpty()
            ? collect()
            : Pago::with('alumno')->whereIn('id', $pagosIds)->get()->keyBy('id');

        $filas = collect();

        foreach ($movsCaja as $m) {
            $filas->push((object) [
                'fecha'    => $m->fecha,
                'tipo'     => $m->subrubro?->rubro?->tipo ?? 'INGRESO',
                'subrubro' => $m->subrubro?->nombre ?? '–',
                'medio'    => $m->tipoCaja?->nombre ?? '–',
                'alumno'   => $m->alumno ? $m->alumno->apellido . ', ' . $m->alumno->nombre : null,
                'obs'      => $m->observaciones,
                'usuario'  => $m->usuario?->name ?? '–',
                'monto'    => abs((float) $m->monto),
                'orden'    => $m->created_at,
            ]);
        }

        foreach ($movsAdmin as $m) {
            $alumno = null;
            if ($m->referencia_tipo === CashflowMovimiento::REF_PAGO_CUOTA) {
                $a = $alumnosPorPago->get($m->referencia_id)?->alumno;
                $alumno = $a ? $a->apellido . ', ' . $a->nombre : null;
            }
            $filas->push((object) [
                'fecha'    => $m->fecha,
                'tipo'     => $m->subrubro?->rubro?->tipo ?? 'INGRESO',
                'subrubro' => $m->subrubro?->nombre ?? '–',
                'medio'    => $m->tipoCaja?->nombre ?? '–',
                'alumno'   => $alumno,
                'obs'      => $m->observaciones,
                'usuario'  => $m->usuarioAdmin?->name ?? '–',
                'monto'    => abs((float) $m->monto),
                'orden'    => $m->created_at,
            ]);
        }

        if ($tipoFiltro === 'INGRESO' || $tipoFiltro === 'EGRESO') {
            $filas = $filas->where('tipo', $tipoFiltro);
        }

        if ($request->filled('search')) {
            $s = mb_strtolower(trim($request->input('search')));
            $filas = $filas->filter(fn($f) =>
                ($f->alumno && str_contains(mb_strtolower($f->alumno), $s)) ||
                ($f->obs && str_contains(mb_strtolower($f->obs), $s)) ||
                str_contains(mb_strtolower($f->subrubro), $s)
            );
        }

        $filas = $filas->sortBy([['fecha', 'desc'], ['orden', 'desc']])->values();

        $totalIngresos = $filas->where('tipo', 'INGRESO')->sum('monto');
        $totalEgresos  = $filas->where('tipo', 'EGRESO')->sum('monto');

        $porPagina  = 30;
        $pagina     = LengthAwarePaginator::resolveCurrentPage();
        $paginadas  = new LengthAwarePaginator(
            $filas->forPage($pagina, $porPagina)->values(),
            $filas->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('caja.historial', [
            'filas'          => $paginadas,
            'subrubros'      => $subrubrosVisibles,
            'totalIngresos'  => $totalIngresos,
            'totalEgresos'   => $totalEgresos,
            'desdeLimite'    => $desdeLimite,
        ]);
    }

    // ── Resumen: dashboard de una caja ───────────────────────────────────

    public function resumen(int $id)
    {
        $user = Auth::user();
        $caja = CajaOperativa::with([
            'usuarioOperativo',
            'movimientos.tipoCaja',
            'movimientos.subrubro.rubro',
            'movimientos.alumno',
        ])->findOrFail($id);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        // Solo movimientos activos: los cancelados no cuentan plata
        $movsActivos = $caja->movimientos->where('estado', 'ACTIVO');

        // Por medio: neto con signo (un egreso en efectivo resta del cajón)
        $porTipo = $movsActivos
            ->groupBy('tipo_caja_id')
            ->map(fn($movs) => [
                'tipo'  => $movs->first()->tipoCaja,
                'total' => $movs->sum(fn($m) => $m->subrubro?->rubro?->tipo === 'EGRESO'
                    ? -abs((float) $m->monto)
                    : abs((float) $m->monto)),
            ])->values();

        $porRubro = $movsActivos
            ->filter(fn($m) => $m->subrubro?->rubro !== null)
            ->groupBy(fn($m) => $m->subrubro->rubro_id)
            ->map(fn($movs) => [
                'rubro' => $movs->first()->subrubro->rubro,
                'total' => $movs->sum('monto'),
            ])->values();

        $ingresos = (float) $movsActivos
            ->filter(fn($m) => $m->subrubro?->rubro?->tipo === 'INGRESO')
            ->sum('monto');
        $egresos = (float) $movsActivos
            ->filter(fn($m) => $m->subrubro?->rubro?->tipo === 'EGRESO')
            ->sum('monto');
        $neto = $ingresos - $egresos;
        $numMovimientos = $movsActivos->count();
        $arqueo = $this->cajaService->arqueoCaja($id);
        $efectivoEsperado = $arqueo['efectivo_esperado'];
        $diferenciaEfectivo = $arqueo['diferencia_efectivo'];

        return view('caja.resumen', compact('caja', 'porTipo', 'porRubro', 'ingresos', 'egresos', 'neto', 'numMovimientos', 'efectivoEsperado', 'diferenciaEfectivo'));
    }

    // ── Detalle: tabla de movimientos ────────────────────────────────────

    public function detalle(int $id)
    {
        $user = Auth::user();
        $caja = CajaOperativa::with([
            'usuarioOperativo',
            'movimientos.tipoCaja',
            'movimientos.subrubro.rubro',
            'movimientos.alumno.deporte',
            'movimientos.pago.deudasCuota',
        ])->findOrFail($id);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        return view('caja.detalle', compact('caja'));
    }

    // ── Editar: agregar movimiento a caja existente ──────────────────────

    public function editarForm(int $id)
    {
        $user = Auth::user();
        $caja = CajaOperativa::findOrFail($id);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        $rubros = $this->cargarRubros($user);
        $tiposCaja    = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $subrubrosMap = $rubros->mapWithKeys(fn($r) => [
            $r->id => $r->subrubros->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->values(),
        ]);

        return view('caja.editar', compact('caja', 'rubros', 'tiposCaja', 'subrubrosMap'));
    }

    public function editarStore(Request $request, int $id)
    {
        $user = Auth::user();
        $caja = CajaOperativa::findOrFail($id);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'subrubro_id'   => 'required|exists:subrubros,id',
            'monto'         => 'required|numeric|min:0.01',
            'fecha'         => 'required|date|before_or_equal:today',
            'observaciones' => 'required|string|max:500',
        ]);

        $fechaMov = Carbon::parse($request->input('fecha'))->startOfDay();
        $esFechaVieja = $fechaMov->lessThan(now()->startOfMonth()->startOfDay());

        if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja')) {
            return back()->withInput()->with(
                'aviso_fecha_vieja',
                'Esta fecha es de un mes ya cerrado. El movimiento va a quedar registrado con esa fecha, pero entra en la caja de hoy y modificará el reporte de ese mes.'
            );
        }

        try {
            $this->cajaService->registrarMovimientoEnCaja($caja->id, [
                'tipo_caja_id'  => $request->input('tipo_caja_id'),
                'subrubro_id'   => $request->input('subrubro_id'),
                'monto'         => $request->input('monto'),
                'fecha'         => $request->input('fecha'),
                'observaciones' => $request->input('observaciones'),
            ]);

            if ($esFechaVieja) {
                app(\App\Services\AvisoAdminService::class)->fechaVieja(
                    que: "Movimiento de caja",
                    fechaDelMovimiento: $request->input("fecha"),
                    monto: "$" . number_format((float) $request->input("monto"), 2, ",", "."),
                    quienLoCargo: Auth::user()?->name,
                    dondeEntra: "Caja #" . $caja->id,
                );
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('web.caja.resumen', $id)->with('success', 'Movimiento registrado.');
    }

    // ── Editar movimiento existente ──────────────────────────────────────

    public function editarMovimientoForm(int $cajaId, int $movId)
    {
        $user = Auth::user();
        $caja = CajaOperativa::findOrFail($cajaId);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        $movimiento   = MovimientoOperativo::where('caja_operativa_id', $cajaId)->findOrFail($movId);
        $rubros       = $this->cargarRubros($user);
        $tiposCaja    = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $subrubrosMap = $rubros->mapWithKeys(fn($r) => [
            $r->id => $r->subrubros->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->values(),
        ]);

        return view('caja.editar', compact('caja', 'movimiento', 'rubros', 'tiposCaja', 'subrubrosMap'));
    }

    public function updateMovimiento(Request $request, int $cajaId, int $movId)
    {
        $user = Auth::user();
        $caja = CajaOperativa::findOrFail($cajaId);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])) {
            return back()->with('error', 'Solo se pueden editar movimientos de una caja abierta o rechazada.');
        }

        $request->validate([
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'subrubro_id'   => 'required|exists:subrubros,id',
            'monto'         => 'required|numeric|min:0.01',
            'fecha'         => 'required|date|before_or_equal:today',
            'observaciones' => 'required|string|max:500',
        ]);

        $fechaMov = Carbon::parse($request->input('fecha'))->startOfDay();
        $esFechaVieja = $fechaMov->lessThan(now()->startOfMonth()->startOfDay());

        if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja')) {
            return back()->withInput()->with(
                'aviso_fecha_vieja',
                'Esta fecha es de un mes ya cerrado. El movimiento va a quedar registrado con esa fecha, pero entra en la caja de hoy y modificará el reporte de ese mes.'
            );
        }

        try {
            $this->cajaService->actualizarMovimientoEnCaja($cajaId, $movId, [
                'tipo_caja_id' => $request->input('tipo_caja_id'),
                'subrubro_id' => $request->input('subrubro_id'),
                'monto' => $request->input('monto'),
                'fecha' => $request->input('fecha'),
                'observaciones' => $request->input('observaciones'),
            ]);

            if ($esFechaVieja) {
                app(\App\Services\AvisoAdminService::class)->fechaVieja(
                    que: "Movimiento de caja editado",
                    fechaDelMovimiento: $request->input("fecha"),
                    monto: "$" . number_format((float) $request->input("monto"), 2, ",", "."),
                    quienLoCargo: Auth::user()?->name,
                    dondeEntra: "Caja #" . $cajaId,
                );
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('web.caja.detalle', $cajaId)->with('success', 'Movimiento actualizado.');
    }

    public function destroyMovimiento(int $cajaId, int $movId)
    {
        return DB::transaction(function () use ($cajaId, $movId) {
            $user = Auth::user();
            $caja = CajaOperativa::whereKey($cajaId)->lockForUpdate()->firstOrFail();

            if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
                abort(403);
            }

            if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])) {
                return back()->with('error', 'Solo se pueden eliminar movimientos de una caja abierta o rechazada.');
            }

            $movimiento = MovimientoOperativo::where('caja_operativa_id', $cajaId)->lockForUpdate()->findOrFail($movId);

            if (!is_null($movimiento->alumno_id)) {
                return back()->with('error', 'Los cobros de cuota no se pueden eliminar directamente. Usá la opción Cancelar.');
            }

            if ($movimiento->subrubro?->es_reservado_sistema) {
                return back()->with('error', 'No se puede eliminar un movimiento generado automáticamente por el sistema.');
            }

            $movimiento->delete();

            return back()->with('success', 'Movimiento eliminado.');
        });
    }

    // ── Cancelar cobro de cuota ───────────────────────────────────────────

    public function cancelarMovimientoForm(int $cajaId, int $movId)
    {
        $user = Auth::user();
        $caja = CajaOperativa::findOrFail($cajaId);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])) {
            return redirect()->route('web.caja.detalle', $cajaId)
                ->with('error', 'Solo se puede cancelar un cobro con la caja abierta o rechazada.');
        }

        $movimiento = MovimientoOperativo::where('caja_operativa_id', $cajaId)->findOrFail($movId);

        if (is_null($movimiento->alumno_id)) {
            abort(403, 'Solo se pueden cancelar cobros de cuota.');
        }

        return view('caja.cancelar-movimiento', compact('caja', 'movimiento'));
    }

    public function cancelarMovimiento(Request $request, int $cajaId, int $movId)
    {
        $user = Auth::user();
        $caja = CajaOperativa::findOrFail($cajaId);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $movimiento = MovimientoOperativo::where('caja_operativa_id', $cajaId)
            ->findOrFail($movId);

        try {
            $this->pagoCuotaService->cancelarCobroOperativo($movimiento->id, $request->input('motivo'), Auth::id());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('web.caja.detalle', $cajaId)
            ->with('success', 'Cobro cancelado y deuda revertida.');
    }

    // ── Cerrar / Validar / Rechazar ───────────────────────────────────────

    public function cerrar(Request $request, int $id)
    {
        $user    = Auth::user();
        $esAdmin = $user->isAdmin();

        $caja = CajaOperativa::findOrFail($id);
        if (!$esAdmin && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        $this->normalizarImportesArqueo($request, ['efectivo_contado', 'cambio_retenido']);
        try {
            $this->cajaService->cerrarCajaOperativa($id, $user->id, $esAdmin, $request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('web.caja.index')->with('success', 'Caja cerrada.');
    }

    public function validar(int $id)
    {
        try {
            $this->cajaService->validarCaja($id, Auth::id());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('web.caja.index')->with('success', 'Caja validada y reflejada en cashflow.');
    }

    public function rechazar(Request $request, int $id)
    {
        $request->validate(['motivo' => 'nullable|string|max:500']);

        try {
            $this->cajaService->rechazarCaja($id, Auth::id(), $request->input('motivo', ''));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('web.caja.index')->with('success', 'Caja rechazada.');
    }

    // ── Cobrar cuota ─────────────────────────────────────────────────────

    public function cobrarCuotaSelect(Request $request)
    {
        $user = Auth::user();
        if ($this->aperturaNecesaria()) return redirect()->route('web.caja.apertura');

        try {
            $this->cajaService->validarCajaViejaAbierta($user->id);
        } catch (\Exception $e) {
            return redirect()->route('web.caja.index')->with('error', $e->getMessage());
        }

        // A54/A55: cuanto debe cada alumno lo decide un solo calculo, el de cobranza.
        // Antes esta pantalla sumaba por su cuenta las cuotas PENDIENTE —contando a quien
        // tenia una en cero— y no miraba la inscripcion, asi que decia otro numero.
        $saldos = app(\App\Services\CobranzaEstadoService::class)
            ->saldoDeAlumnos(Alumno::where('activo', true)->get(['id', 'dni']));

        // Se ofrece a quien tiene algo por cobrar: saldo pendiente, o el mes en curso
        // todavia sin generar, que es el cobro adelantado o la primera cuota (A3).
        $periodoVigente = now()->format('Y-m');
        $conDeudaDelMes = DeudaCuota::where('periodo', $periodoVigente)->pluck('alumno_id')->all();
        $porCobrar = array_keys(array_filter(
            $saldos,
            fn (array $saldo, int $id) => $saldo['total'] > 0 || !in_array($id, $conDeudaDelMes, true),
            ARRAY_FILTER_USE_BOTH
        ));

        $query = Alumno::with(['deporte', 'grupo'])
            ->where('activo', true);

        if ($request->filled('search')) {
            $s = addcslashes($request->input('search'), '%_\\');
            $query->where(fn($q) => $q
                ->where('nombre', 'like', "%{$s}%")
                ->orWhere('apellido', 'like', "%{$s}%")
                ->orWhere('dni', 'like', "%{$s}%")
            );
        } else {
            $query->whereIn('id', $porCobrar);
        }

        $alumnos = $query->orderBy('apellido')->orderBy('nombre')->paginate(12)->withQueryString();

        // El encabezado cuenta a los que deben, no a los que la lista ofrece: la lista
        // incluye además a quien se le puede cobrar el mes en curso por adelantado.
        $conDeuda = count(array_filter($saldos, fn (array $saldo) => $saldo['total'] > 0));

        return view('caja.cobrar-cuota', compact('alumnos', 'saldos', 'conDeuda'));
    }

    public function cobrar(int $alumnoId)
    {
        $user = Auth::user();
        if ($this->aperturaNecesaria()) return redirect()->route('web.caja.apertura');

        try {
            $this->cajaService->validarCajaViejaAbierta($user->id);
        } catch (\Exception $e) {
            return redirect()->route('web.caja.index')->with('error', $e->getMessage());
        }

        $alumno = Alumno::with([
            'deporte', 'grupo.planesActivos',
            'planActivo.plan',
            'deudaCuotas' => fn($q) => $q->where('estado', DeudaCuota::ESTADO_PENDIENTE)->orderBy('periodo'),
        ])->findOrFail($alumnoId);

        // Bloquear cobro si el alumno no tiene un plan activo con precio válido
        if (!$alumno->planActivo || !$alumno->planActivo->plan) {
            return redirect()
                ->route('web.alumnos.edit', $alumno->id)
                ->with('error', 'El alumno no tiene un plan activo. Asigná un plan antes de cobrar.');
        }

        $periodoVigente = now()->format('Y-m');
        $deudaVigenteExiste = DeudaCuota::where('alumno_id', $alumno->id)
            ->where('periodo', $periodoVigente)
            ->exists();

        if (!$deudaVigenteExiste) {
            $planVigente = $this->pagoCuotaService->obtenerPlanParaPeriodo($alumno->id, $periodoVigente);

            if (!$planVigente?->plan) {
                return redirect()
                    ->route('web.alumnos.edit', $alumno->id)
                    ->with('error', 'El alumno no tiene un plan aplicable al período vigente.');
            }

            $deudas = $alumno->deudaCuotas->push(new DeudaCuota([
                'alumno_id' => $alumno->id,
                'periodo' => $periodoVigente,
                'monto_original' => $planVigente->plan->precio_mensual,
                'monto_pagado' => 0,
                'estado' => DeudaCuota::ESTADO_PENDIENTE,
            ]))->sortBy('periodo')->values();

            $alumno->setRelation('deudaCuotas', $deudas);
        }

        // A3: Ofrecer períodos futuros para cobro adelantado al precio vigente del plan.
        $periodosExistentes = DeudaCuota::where('alumno_id', $alumno->id)->pluck('periodo')->all();
        $maxPeriodo = max(array_merge([$periodoVigente], $periodosExistentes));
        $maxDate = \Carbon\Carbon::parse($maxPeriodo . '-01');

        for ($i = 1; $i <= 2; $i++) {
            $periodoFuturo = $maxDate->copy()->addMonthsNoOverflow($i)->format('Y-m');
            if (!$alumno->deudaCuotas->contains('periodo', $periodoFuturo)) {
                $planFuturo = $this->pagoCuotaService->obtenerPlanParaPeriodo($alumno->id, $periodoFuturo);
                if ($planFuturo?->plan) {
                    $alumno->deudaCuotas->push(new DeudaCuota([
                        'alumno_id' => $alumno->id,
                        'periodo' => $periodoFuturo,
                        'monto_original' => (float) $planFuturo->plan->precio_mensual,
                        'monto_pagado' => 0,
                        'estado' => DeudaCuota::ESTADO_PENDIENTE,
                    ]));
                }
            }
        }
        $alumno->setRelation('deudaCuotas', $alumno->deudaCuotas->sortBy('periodo')->values());

        $tiposCaja        = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $planesDisponibles = $alumno->grupo
            ? $alumno->grupo->planesActivos->sortBy('clases_por_semana')->values()
            : collect();

        // Regla de primer pago — informativa para mostrar en el formulario
        $reglaPrimerPago     = null;
        $motivoPrimerPago    = null;
        // Mismo criterio que PagoCuotaService::calcularReglaPrimerPago(): un cobro
        // cancelado no cuenta como pago previo.
        $tienePagos          = Pago::where('alumno_id', $alumnoId)
            ->where('estado', Pago::ESTADO_COMPLETADO)->conCuota()
            ->exists();

        // La vista previa usa la misma decisión que el cobro, incluida la cuota
        // cuyo importe ya quedó fijado en el alta.
        $periodoActual = now()->format('Y-m');
        $itemsOfrecidos = $alumno->deudaCuotas->map(fn ($deuda) => [
            'periodo' => $deuda->periodo, 'monto' => $deuda->saldo_pendiente,
        ])->all();
        [, $reglaId, $periodoConDescuento] = $this->pagoCuotaService->calcularReglaPrimerPago($alumnoId, $itemsOfrecidos);
        if ($reglaId !== null) {
            $reglaPrimerPago = ReglaPrimerPago::find($reglaId);
            $motivoPrimerPago = $tienePagos ? 'reingreso' : 'nuevo';
        }

        // El mes de alta se muestra ya descontado, que es lo que realmente vale y lo que
        // el cobro va a registrar. Es en memoria: la deuda recien baja al confirmar.
        // Asi la pantalla y el servidor toman el mismo tope y no hay dos numeros.
        if ($periodoConDescuento !== null && $reglaPrimerPago) {
            // El importe lo calcula el servicio, no esta pantalla. Multiplicar acá el
            // monto de la deuda daba otro numero cuando la deuda ya venia descontada de
            // un cobro anulado: mostraba 29.400 y el cobro registraba 42.000.
            $precioConDescuento = $this->pagoCuotaService->precioConDescuento(
                $alumno->id,
                $periodoConDescuento,
                (float) $reglaPrimerPago->porcentaje
            );

            foreach ($alumno->deudaCuotas as $deuda) {
                if ($deuda->periodo === $periodoConDescuento) {
                    $deuda->monto_original = $precioConDescuento;
                }
            }
        }

        // Cuanto cuesta el mes en curso con cada plan posible. Lo calcula el servidor con las
        // mismas reglas del cobro —la bajada diferida y el descuento de primer pago— y la
        // pantalla solo lo muestra. Antes el script hacia su propia cuenta (precio nuevo
        // menos lo pagado) y anunciaba 60.000 donde el cobro registraba 42.000.
        $planActual = $alumno->planActivo;
        $mesConDescuento = $reglaPrimerPago && $periodoConDescuento === $periodoActual;

        foreach ($planesDisponibles as $plan) {
            $esElActual = $planActual && $planActual->plan_id === $plan->id;
            $rigeElMesSiguiente = !$esElActual
                && $this->cambioDePlanRigeElMesSiguiente($alumno, $planActual, $plan);

            $precioDelMes = $rigeElMesSiguiente && $planActual?->plan
                ? (float) $planActual->plan->precio_mensual
                : (float) $plan->precio_mensual;

            $plan->precio_mes = $mesConDescuento
                ? $this->pagoCuotaService->aplicarPorcentaje($precioDelMes, (float) $reglaPrimerPago->porcentaje)
                : $precioDelMes;
        }

        return view('caja.cobrar', compact('alumno', 'tiposCaja', 'reglaPrimerPago', 'motivoPrimerPago', 'planesDisponibles', 'periodoConDescuento'));
    }

    /**
     * Si el cambio al plan nuevo rige ya o recien el mes que viene.
     *
     * Una bajada con asistencias este mes se difiere: el mes en curso ya se uso con el
     * plan anterior. La usan el cobro y la pantalla que lo anuncia; si cada uno tuviera
     * su version de la regla, anunciarian importes distintos.
     */
    private function cambioDePlanRigeElMesSiguiente(Alumno $alumno, ?AlumnoPlan $planAnterior, GrupoPlan $nuevoPlan): bool
    {
        $esBaja = $planAnterior?->plan
            && $nuevoPlan->clases_por_semana < $planAnterior->plan->clases_por_semana;

        if (!$esBaja) {
            return false;
        }

        return Asistencia::where('alumno_id', $alumno->id)
            ->where('presente', true)
            ->whereHas('clase', fn($query) => $query
                ->whereBetween('fecha', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ]))
            ->exists();
    }

    public function pagar(Request $request, int $alumnoId)
    {
        $user   = Auth::user();
        if ($this->aperturaNecesaria()) {
            return response()->json(['success' => false, 'message' => 'Abrí la caja y confirmá el efectivo antes de cobrar.'], 422);
        }
        $alumno = Alumno::findOrFail($alumnoId);

        // El formulario arma su FormData antes de que el script de moneda limpie los
        // campos, asi que los montos pueden llegar formateados ("28.000"). Sin esto
        // `numeric` los acepta como 28 y el cobro queda silenciosamente mal.
        $montosCuota = $request->input('montos_cuota');
        if (is_array($montosCuota)) {
            $request->merge([
                'montos_cuota' => collect($montosCuota)
                    ->map(fn ($monto) => is_string($monto) ? str_replace(['.', ','], '', $monto) : $monto)
                    ->all(),
            ]);
        }

        $request->validate([
            'tipo_caja_id'   => 'required|exists:tipos_caja,id',
            'periodos'       => 'nullable|array',
            'periodos.*'     => 'required|string|regex:/^\d{4}-\d{2}$/',
            'observaciones'  => 'nullable|string|max:500',
            'montos_cuota'   => 'array',
            'montos_cuota.*' => 'nullable|numeric|min:0.01',
            'fecha_pago'     => 'nullable|date|before_or_equal:today',
            'nuevo_plan_id'  => ['nullable', Rule::exists('grupo_planes', 'id')->where('grupo_id', $alumno->grupo_id)],
            'confirmar_deuda_anterior' => 'nullable|boolean',
            'confirmar_fecha_vieja'    => 'nullable|boolean',
            'motivo' => 'nullable|string|max:500',
            'monto_entregado' => 'nullable|numeric|min:0.01|max:99999999.99',
        ]);

        $fechaPagoStr = $request->input('fecha_pago') ?: today()->toDateString();
        $fechaPago = Carbon::parse($fechaPagoStr)->startOfDay();
        $esFechaVieja = $fechaPago->lessThan(now()->startOfMonth()->startOfDay());

        if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja')) {
            return response()->json([
                'success' => false,
                'requiere_confirmacion_fecha_vieja' => true,
                'message' => 'Esta fecha de pago corresponde a un mes anterior ya cerrado. El cobro quedará registrado con esa fecha real, pero entrará en la caja de hoy y modificará el reporte de ese mes.',
            ], 409);
        }

        $request->merge(['periodos' => $request->input('periodos') ?? []]);
        $periodosSolicitados = collect($request->input('periodos'))->sort()->values();
        $periodoMasAntiguo = $periodosSolicitados->first();
        $deudasAnteriores = DeudaCuota::where('alumno_id', $alumnoId)
            ->when($periodoMasAntiguo === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->where('estado', DeudaCuota::ESTADO_PENDIENTE)
            ->when($periodoMasAntiguo !== null, fn ($query) => $query->where('periodo', '<', $periodoMasAntiguo))
            ->orderBy('periodo')
            ->get();
        $requiereAvisoDeudaAnterior = $deudasAnteriores->isNotEmpty();

        if ($requiereAvisoDeudaAnterior && !$request->boolean('confirmar_deuda_anterior')) {
            return response()->json([
                'success' => false,
                'requiere_confirmacion' => true,
                'cantidad_meses' => $deudasAnteriores->count(),
                'periodos' => $deudasAnteriores->pluck('periodo')->values()->all(),
                'monto_total' => (float) $deudasAnteriores->sum('saldo_pendiente'),
                'message' => sprintf(
                    'Quedan %d meses anteriores impagos por $%s (%s).',
                    $deudasAnteriores->count(),
                    number_format((float) $deudasAnteriores->sum('saldo_pendiente'), 0, ',', '.'),
                    $deudasAnteriores->pluck('periodo')->implode(', ')
                ),
            ], 409);
        }

        if ($requiereAvisoDeudaAnterior) {
            $request->validate([
                'motivo' => 'required|string|max:500',
            ]);
        }

        $motivoDeudaAnterior = $requiereAvisoDeudaAnterior
            ? trim((string) $request->input('motivo'))
            : null;
        $observacionesPago = implode("\n", array_filter([
            $request->input('observaciones'),
            $motivoDeudaAnterior ? "Motivo por deuda anterior: {$motivoDeudaAnterior}" : null,
        ]));

        try {
            $resultadoPago = DB::transaction(function () use ($request, $alumno, $alumnoId, $user, $observacionesPago) {
                app(\App\Services\InscripcionService::class)->bloquear($alumno->dni, false);
                $alumno = Alumno::whereKey($alumnoId)->lockForUpdate()->firstOrFail();
                if ($request->filled('nuevo_plan_id')) {
                    $nuevoPlanId = (int) $request->input('nuevo_plan_id');
                    $alumno->loadMissing('planActivo.plan');
                    if (!$alumno->planActivo || $alumno->planActivo->plan_id !== $nuevoPlanId) {
                        $planAnterior = $alumno->planActivo;
                        $nuevoPlan = GrupoPlan::findOrFail($nuevoPlanId);
                        $aplicaMesSiguiente = $this->cambioDePlanRigeElMesSiguiente($alumno, $planAnterior, $nuevoPlan);
                        $fechaDesde = $aplicaMesSiguiente
                            ? now()->addMonthNoOverflow()->startOfMonth()
                            : today();

                        AlumnoPlan::create([
                            'alumno_id'   => $alumno->id,
                            'plan_id'     => $nuevoPlanId,
                            'fecha_desde' => $fechaDesde,
                            'activo'      => true,
                        ]);

                        if ($aplicaMesSiguiente) {
                            $planAnterior?->update([
                                'fecha_hasta' => $fechaDesde->copy()->subDay(),
                            ]);
                        } else {
                            $precioNuevo = (float) $nuevoPlan->precio_mensual;
                            DeudaCuota::where('alumno_id', $alumno->id)
                                ->where('periodo', now()->format('Y-m'))
                                ->where('estado', DeudaCuota::ESTADO_PENDIENTE)
                                ->whereRaw('monto_pagado <= ?', [$precioNuevo])
                                ->update(['monto_original' => $precioNuevo]);
                        }
                    }
                }

                $deudas = DeudaCuota::where('alumno_id', $alumnoId)
                    ->where('estado', DeudaCuota::ESTADO_PENDIENTE)
                    ->whereIn('periodo', $request->input('periodos'))
                    ->orderBy('periodo')
                    ->get()
                    ->keyBy('periodo');

                $montosEnviados = $request->input('montos_cuota', []);
                $items = collect($request->input('periodos'))->map(function ($periodo) use ($deudas, $montosEnviados, $alumnoId) {
                    $deuda = $deudas->get($periodo);

                    if ($deuda) {
                        $montoSolicitado = isset($montosEnviados[$periodo])
                            ? (float) $montosEnviados[$periodo]
                            : (float) $deuda->saldo_pendiente;
                        $monto = min($montoSolicitado, (float) $deuda->saldo_pendiente);
                    } else {
                        $plan = $this->pagoCuotaService->obtenerPlanParaPeriodo($alumnoId, $periodo);
                        if (!$plan?->plan) {
                            throw new \RuntimeException("El alumno no tiene un plan aplicable al período {$periodo}.");
                        }

                        $monto = isset($montosEnviados[$periodo])
                            ? (float) $montosEnviados[$periodo]
                            : (float) $plan->plan->precio_mensual;
                    }

                    return ['periodo' => $periodo, 'monto' => max($monto, 0.01)];
                })->values()->all();

                $datosPago = [
                    'alumno_id'       => $alumnoId,
                    'tipo_caja_id'    => $request->input('tipo_caja_id'),
                    'items'           => $items,
                    'monto_entregado' => $request->input('monto_entregado'),
                    'fecha_pago'      => $request->input('fecha_pago', today()->toDateString()),
                    'observaciones'   => $observacionesPago ?: null,
                ];

                // A13 y B1: el dueño no tiene caja. Hasta el 05/10 esta pantalla mandaba a
                // todos por el camino del mostrador sin mirar el rol, así que al admin se le
                // abría una caja a su nombre y después tenía que cerrarla y validarse a sí
                // mismo. Su cobro va derecho al cashflow, que es lo que ya hacía la API.
                return $user->isAdmin()
                    ? $this->pagoCuotaService->registrarPagoCuotaAdmin($datosPago + ['usuario_admin_id' => $user->id])
                    : $this->pagoCuotaService->registrarPagoCuotaOperativo($datosPago + ['usuario_operativo_id' => $user->id]);
            });

            if ($requiereAvisoDeudaAnterior) {
                try {
                    $administradores = User::where('rol', User::ROL_ADMIN)
                        ->where('activo', true)
                        ->get();
                    Notification::send($administradores, new CobroConDeudaAnteriorNotification(
                        alumno: $alumno,
                        pagoId: $resultadoPago['pago']->id,
                        periodos: $deudasAnteriores->pluck('periodo')->values()->all(),
                        montoPendiente: (float) $deudasAnteriores->sum('saldo_pendiente'),
                        motivo: $motivoDeudaAnterior,
                        operativo: $user
                    ));
                } catch (\Throwable $errorNotificacion) {
                    Log::error('No se pudo notificar el cobro con deuda anterior.', [
                        'pago_id' => $resultadoPago['pago']->id,
                        'error' => $errorNotificacion->getMessage(),
                    ]);
                }
            }

            if ($esFechaVieja) {
                app(\App\Services\AvisoAdminService::class)->fechaVieja(
                    que: "Cobro de cuota de {$alumno->apellido}, {$alumno->nombre}",
                    fechaDelMovimiento: $request->input("fecha_pago"),
                    monto: "$" . number_format((float) ($resultadoPago["pago"]->monto_final ?? 0), 2, ",", "."),
                    quienLoCargo: Auth::user()?->name,
                    dondeEntra: "la caja abierta de hoy",
                );
            }

            return redirect()->route('web.caja.index')
                ->with('success', "Pago registrado para {$alumno->apellido}, {$alumno->nombre}.")
                ->with('recibo_pago_id', $resultadoPago['pago']->id);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Movimiento manual (sin caja existente, auto-abre) ────────────────

    public function movimientoForm()
    {
        $user = Auth::user();
        if ($this->aperturaNecesaria()) return redirect()->route('web.caja.apertura');

        try {
            $this->cajaService->validarCajaViejaAbierta($user->id);
        } catch (\Exception $e) {
            return redirect()->route('web.caja.index')->with('error', $e->getMessage());
        }

        $rubros       = $this->cargarRubros($user);
        $tiposCaja    = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $subrubrosMap = $rubros->mapWithKeys(fn($r) => [
            $r->id => $r->subrubros->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->values(),
        ]);

        return view('caja.movimiento', compact('rubros', 'tiposCaja', 'subrubrosMap'));
    }

    public function movimientoStore(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'subrubro_id'   => 'required|exists:subrubros,id',
            'monto'         => 'required|numeric|min:0.01',
            'observaciones' => 'required|string|max:500',
        ]);

        try {
            $caja = $this->cajaService->abrirCajaSiNoExiste($user->id);
        } catch (\Exception $e) {
            return redirect()->route('web.caja.index')->with('error', $e->getMessage());
        }

        try {
            $this->cajaService->registrarMovimientoEnCaja($caja->id, [
                'tipo_caja_id'  => $request->input('tipo_caja_id'),
                'subrubro_id'   => $request->input('subrubro_id'),
                'monto'         => $request->input('monto'),
                'observaciones' => $request->input('observaciones'),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('web.caja.index')->with('success', 'Movimiento registrado.');
    }

    // ── Helper privado ────────────────────────────────────────────────────

    private function cargarRubros($user)
    {
        return Rubro::with(['subrubros' => function ($q) use ($user) {
            $q->where('es_reservado_sistema', false)
              ->where('activo', true)
              ->where('nombre', '!=', 'Cuota Mensual');
            if (!$user->isAdmin()) {
                $q->where('permitido_para', 'OPERATIVO')->where('afecta_caja', true);
            }
            $q->orderBy('nombre');
        }])->orderBy('nombre')->get()
        ->filter(fn($r) => $r->subrubros->isNotEmpty())
        ->values();
    }
}
