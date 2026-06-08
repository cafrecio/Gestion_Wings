<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\DeudaCuota;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\PagoCuotaService;
use Illuminate\Http\Request;
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

            $cajas = CajaOperativa::where('usuario_operativo_id', $user->id)
                ->whereYear('apertura_at', now()->year)
                ->whereMonth('apertura_at', now()->month)
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
            'movimientos.alumno',
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

        if ($caja->estado !== 'ABIERTA') {
            return back()->with('error', 'Solo se pueden editar movimientos de una caja abierta.');
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

        if ($caja->estado !== 'ABIERTA') {
            return back()->with('error', 'Solo se pueden eliminar movimientos de una caja abierta.');
        }

        $movimiento = MovimientoOperativo::where('caja_operativa_id', $cajaId)->findOrFail($movId);

        if ($movimiento->subrubro?->es_reservado_sistema) {
            return back()->with('error', 'No se puede eliminar un movimiento generado automáticamente por el sistema.');
        }

        $movimiento->delete();

        return back()->with('success', 'Movimiento eliminado.');
    }

    // ── Cerrar / Validar / Rechazar ───────────────────────────────────────

    public function cerrar(Request $request, int $id)
    {
        $user    = Auth::user();
        $esAdmin = $user->isAdmin();

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
            $s = $request->input('search');
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
            'deporte', 'grupo',
            'deudaCuotas' => fn($q) => $q->where('estado', DeudaCuota::ESTADO_PENDIENTE)->orderBy('periodo'),
        ])->findOrFail($alumnoId);

        $tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();

        return view('caja.cobrar', compact('alumno', 'tiposCaja'));
    }

    public function pagar(Request $request, int $alumnoId)
    {
        $user   = Auth::user();
        $alumno = Alumno::findOrFail($alumnoId);

        $request->validate([
            'tipo_caja_id'   => 'required|exists:tipos_caja,id',
            'periodos'       => 'required|array|min:1',
            'periodos.*'     => 'required|string|regex:/^\d{4}-\d{2}$/',
            'observaciones'  => 'nullable|string|max:500',
            'montos_cuota'   => 'array',
            'montos_cuota.*' => 'nullable|numeric|min:0.01',
            'fecha_pago'     => 'nullable|date|before_or_equal:today',
        ]);

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
