<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\DeudaCuota;
use App\Models\FormaPago;
use App\Models\MovimientoOperativo;
use App\Models\Pago;
use App\Models\ReglaPrimerPago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CajaWebController extends Controller
{
    public function __construct(
        private CajaService $cajaService,
        private PagoCuotaService $pagoCuotaService
    ) {}

    // ── Índice: listado de cajas en cards ────────────────────────────────

    public function index(Request $request)
    {
        $user = Auth::user();

        $cajaVieja  = false;
        $sinCajaHoy = false;
        $operativos = collect();
        $mes        = now()->format('Y-m');

        if ($user->isAdmin()) {
            $operativos = User::where('rol', User::ROL_OPERATIVO)->orderBy('name')->get();
            $mes        = $request->input('mes', now()->format('Y-m'));
            [$year, $month] = explode('-', $mes);

            $query = CajaOperativa::with(['usuarioOperativo', 'movimientos'])
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
                ->with(['movimientos'])
                ->orderByDesc('apertura_at')
                ->get();

            $cajaAbiertaHoy = $cajas->first(
                fn($c) => $c->estado === 'ABIERTA' && $c->apertura_at->isToday()
            );
            $sinCajaHoy = !$cajaAbiertaHoy && !$cajaVieja;
        }

        return view('caja.index', compact('cajas', 'cajaVieja', 'sinCajaHoy', 'operativos', 'mes'));
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
            ->where(fn($q) => $q->whereNull('referencia_tipo')
                                ->orWhere('referencia_tipo', '!=', 'CAJA_OPERATIVA'))
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get();

        // Alumnos de los cobros de cuota hechos por admin (via Pago referenciado)
        $pagosIds = $movsAdmin->where('referencia_tipo', 'PAGO_CUOTA')->pluck('referencia_id')->filter();
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
            if ($m->referencia_tipo === 'PAGO_CUOTA') {
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

        $porTipo = $caja->movimientos
            ->groupBy('tipo_caja_id')
            ->map(fn($movs) => [
                'tipo'  => $movs->first()->tipoCaja,
                'total' => $movs->sum('monto'),
            ])->values();

        $porRubro = $caja->movimientos
            ->filter(fn($m) => $m->subrubro?->rubro !== null)
            ->groupBy(fn($m) => $m->subrubro->rubro_id)
            ->map(fn($movs) => [
                'rubro' => $movs->first()->subrubro->rubro,
                'total' => $movs->sum('monto'),
            ])->values();

        $ingresos = (float) $caja->movimientos
            ->filter(fn($m) => $m->subrubro?->rubro?->tipo === 'INGRESO')
            ->sum('monto');
        $egresos = (float) $caja->movimientos
            ->filter(fn($m) => $m->subrubro?->rubro?->tipo === 'EGRESO')
            ->sum('monto');
        $neto = $ingresos - $egresos;

        return view('caja.resumen', compact('caja', 'porTipo', 'porRubro', 'ingresos', 'egresos', 'neto'));
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
            'fecha'         => 'required|date',
            'observaciones' => 'required|string|max:500',
        ]);

        try {
            $this->cajaService->registrarMovimientoEnCaja($caja->id, [
                'tipo_caja_id'  => $request->input('tipo_caja_id'),
                'subrubro_id'   => $request->input('subrubro_id'),
                'monto'         => $request->input('monto'),
                'fecha'         => $request->input('fecha'),
                'observaciones' => $request->input('observaciones'),
            ]);
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

        $movimiento = MovimientoOperativo::where('caja_operativa_id', $cajaId)->findOrFail($movId);

        if ($movimiento->subrubro?->es_reservado_sistema) {
            return back()->with('error', 'No se puede editar un movimiento generado automáticamente por el sistema.');
        }

        $request->validate([
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'subrubro_id'   => 'required|exists:subrubros,id',
            'monto'         => 'required|numeric|min:0.01',
            'fecha'         => 'required|date',
            'observaciones' => 'required|string|max:500',
        ]);

        $movimiento->update([
            'tipo_caja_id'  => $request->input('tipo_caja_id'),
            'subrubro_id'   => $request->input('subrubro_id'),
            'monto'         => $request->input('monto'),
            'fecha'         => $request->input('fecha'),
            'observaciones' => $request->input('observaciones'),
        ]);

        return redirect()->route('web.caja.detalle', $cajaId)->with('success', 'Movimiento actualizado.');
    }

    public function destroyMovimiento(int $cajaId, int $movId)
    {
        $user = Auth::user();
        $caja = CajaOperativa::findOrFail($cajaId);

        if (!$user->isAdmin() && $caja->usuario_operativo_id !== $user->id) {
            abort(403);
        }

        if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])) {
            return back()->with('error', 'Solo se pueden eliminar movimientos de una caja abierta o rechazada.');
        }

        $movimiento = MovimientoOperativo::where('caja_operativa_id', $cajaId)->findOrFail($movId);

        if (!is_null($movimiento->alumno_id)) {
            return back()->with('error', 'Los cobros de cuota no se pueden eliminar directamente. Usá la opción Cancelar.');
        }

        if ($movimiento->subrubro?->es_reservado_sistema) {
            return back()->with('error', 'No se puede eliminar un movimiento generado automáticamente por el sistema.');
        }

        $movimiento->delete();

        return back()->with('success', 'Movimiento eliminado.');
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

        try {
            $this->pagoCuotaService->cancelarCobroOperativo($movId, $request->input('motivo'), Auth::id());
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

        try {
            $this->cajaService->cerrarCajaOperativa($id, $user->id, $esAdmin);
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

        try {
            $this->cajaService->validarCajaViejaAbierta($user->id);
        } catch (\Exception $e) {
            return redirect()->route('web.caja.index')->with('error', $e->getMessage());
        }

        $query = Alumno::with(['deporte', 'grupo'])
            ->where('activo', true)
            ->whereHas('deudaCuotas', fn($q) => $q->where('estado', DeudaCuota::ESTADO_PENDIENTE))
            ->with(['deudaCuotas' => fn($q) => $q->where('estado', DeudaCuota::ESTADO_PENDIENTE)->orderBy('periodo')]);

        if ($request->filled('search')) {
            $s = addcslashes($request->input('search'), '%_\\');
            $query->where(fn($q) => $q
                ->where('nombre', 'like', "%{$s}%")
                ->orWhere('apellido', 'like', "%{$s}%")
                ->orWhere('dni', 'like', "%{$s}%")
            );
        }

        $alumnos = $query->orderBy('apellido')->orderBy('nombre')->paginate(12)->withQueryString();

        return view('caja.cobrar-cuota', compact('alumnos'));
    }

    public function cobrar(int $alumnoId)
    {
        $user = Auth::user();

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

        $tiposCaja        = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $formasPago       = FormaPago::where('activo', true)->orderBy('nombre')->get();
        $planesDisponibles = $alumno->grupo
            ? $alumno->grupo->planesActivos->sortBy('clases_por_semana')->values()
            : collect();

        // Regla de primer pago — informativa para mostrar en el formulario
        $reglaPrimerPago     = null;
        $motivoPrimerPago    = null;
        $tienePagos          = Pago::where('alumno_id', $alumnoId)->exists();

        if (!$tienePagos && $alumno->fecha_alta) {
            // Alumno nuevo: usa día de fecha_alta
            $reglas = ReglaPrimerPago::obtenerReglaPorDia($alumno->fecha_alta->day);
            if ($reglas->count() === 1) {
                $reglaPrimerPago  = $reglas->first();
                $motivoPrimerPago = 'nuevo';
            }
        } elseif (!$alumno->activo && $tienePagos) {
            // Alumno inactivo que vuelve: usa día de hoy
            $reglas = ReglaPrimerPago::obtenerReglaPorDia(now()->day);
            if ($reglas->count() === 1) {
                $reglaPrimerPago  = $reglas->first();
                $motivoPrimerPago = 'reingreso';
            }
        }

        return view('caja.cobrar', compact('alumno', 'tiposCaja', 'formasPago', 'reglaPrimerPago', 'motivoPrimerPago', 'planesDisponibles'));
    }

    public function pagar(Request $request, int $alumnoId)
    {
        $user   = Auth::user();
        $alumno = Alumno::findOrFail($alumnoId);

        $request->validate([
            'tipo_caja_id'   => 'required|exists:tipos_caja,id',
            'forma_pago_id'  => 'nullable|exists:formas_pago,id',
            'periodos'       => 'required|array|min:1',
            'periodos.*'     => 'required|string|regex:/^\d{4}-\d{2}$/',
            'observaciones'  => 'nullable|string|max:500',
            'montos_cuota'   => 'array',
            'montos_cuota.*' => 'nullable|numeric|min:0.01',
            'fecha_pago'     => 'nullable|date|before_or_equal:today',
            'nuevo_plan_id'  => ['nullable', 'exists:grupo_planes,id'],
        ]);

        // Registrar cambio de plan antes del pago
        if ($request->filled('nuevo_plan_id')) {
            $nuevoPlanId = (int) $request->input('nuevo_plan_id');
            $alumno->loadMissing('planActivo');
            if (!$alumno->planActivo || $alumno->planActivo->plan_id !== $nuevoPlanId) {
                AlumnoPlan::create([
                    'alumno_id'   => $alumno->id,
                    'plan_id'     => $nuevoPlanId,
                    'fecha_desde' => today(),
                    'activo'      => true,
                ]);
            }
        }

        $deudas = DeudaCuota::where('alumno_id', $alumnoId)
            ->where('estado', DeudaCuota::ESTADO_PENDIENTE)
            ->whereIn('periodo', $request->input('periodos'))
            ->orderBy('periodo')
            ->get();

        if ($deudas->isEmpty()) {
            return back()->with('error', 'No se encontraron deudas pendientes para los períodos seleccionados.');
        }

        $montosEnviados = $request->input('montos_cuota', []);

        $items = $deudas->map(function ($d) use ($montosEnviados) {
            $montoSolicitado = isset($montosEnviados[$d->periodo])
                ? (float) $montosEnviados[$d->periodo]
                : (float) $d->saldo_pendiente;
            $monto = min($montoSolicitado, (float) $d->saldo_pendiente);
            return ['periodo' => $d->periodo, 'monto' => max($monto, 0.01)];
        })->values()->all();

        try {
            $this->pagoCuotaService->registrarPagoCuotaOperativo([
                'alumno_id'            => $alumnoId,
                'tipo_caja_id'         => $request->input('tipo_caja_id'),
                'forma_pago_id'        => $request->filled('forma_pago_id') ? (int) $request->input('forma_pago_id') : null,
                'usuario_operativo_id' => $user->id,
                'items'                => $items,
                'fecha_pago'           => $request->input('fecha_pago', today()->toDateString()),
                'observaciones'        => $request->input('observaciones'),
            ]);

            return redirect()->route('web.caja.index')
                ->with('success', "Pago registrado para {$alumno->apellido}, {$alumno->nombre}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Movimiento manual (sin caja existente, auto-abre) ────────────────

    public function movimientoForm()
    {
        $user = Auth::user();

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
