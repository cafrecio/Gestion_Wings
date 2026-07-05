<?php

namespace App\Http\Controllers;

use App\Models\CashflowMovimiento;
use App\Models\Rubro;
use App\Models\TipoCaja;
use App\Services\CashflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashflowWebController extends Controller
{
    public function __construct(private CashflowService $cashflowService) {}

    public function index(Request $request)
    {
        $anio       = (int) $request->input('anio', now()->year);
        $mes        = $request->filled('mes') ? (int) $request->input('mes') : null;
        $tipoCajaId = $request->filled('tipo_caja_id') ? (int) $request->input('tipo_caja_id') : null;
        $tipo       = in_array($request->input('tipo'), ['INGRESO', 'EGRESO']) ? $request->input('tipo') : null;

        $movimientos = CashflowMovimiento::with(['subrubro.rubro', 'tipoCaja', 'usuarioAdmin'])
            ->whereYear('fecha', $anio)
            ->when($mes, fn($q) => $q->whereMonth('fecha', $mes))
            ->when($tipoCajaId, fn($q) => $q->where('tipo_caja_id', $tipoCajaId))
            ->when($tipo, fn($q) => $q->whereHas('subrubro.rubro', fn($r) => $r->where('tipo', $tipo)))
            ->orderBy('fecha', 'desc')
            ->paginate(30)
            ->withQueryString();

        $totalIngresos = CashflowMovimiento::whereYear('fecha', $anio)
            ->when($mes, fn($q) => $q->whereMonth('fecha', $mes))
            ->when($tipoCajaId, fn($q) => $q->where('tipo_caja_id', $tipoCajaId))
            ->whereHas('subrubro.rubro', fn($q) => $q->where('tipo', 'INGRESO'))
            ->sum('monto');

        // Los egresos se guardan negativos; para mostrar se usa el valor absoluto
        $totalEgresos = abs(CashflowMovimiento::whereYear('fecha', $anio)
            ->when($mes, fn($q) => $q->whereMonth('fecha', $mes))
            ->when($tipoCajaId, fn($q) => $q->where('tipo_caja_id', $tipoCajaId))
            ->whereHas('subrubro.rubro', fn($q) => $q->where('tipo', 'EGRESO'))
            ->sum('monto'));

        $tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $aniosDisponibles = CashflowMovimiento::selectRaw('YEAR(fecha) as anio')
            ->distinct()->orderBy('anio', 'desc')->pluck('anio');

        if ($aniosDisponibles->isEmpty()) {
            $aniosDisponibles = collect([now()->year]);
        }

        return view('cashflow.index', compact(
            'movimientos', 'tiposCaja', 'aniosDisponibles',
            'anio', 'mes', 'tipoCajaId', 'tipo',
            'totalIngresos', 'totalEgresos'
        ));
    }

    public function create()
    {
        // El admin puede usar cualquier subrubro no reservado (los OPERATIVO son "ambos")
        $rubros = Rubro::with(['subrubros' => function ($q) {
            $q->where('es_reservado_sistema', false)
              ->orderBy('nombre');
        }])->orderBy('nombre')->get()
        ->filter(fn($r) => $r->subrubros->isNotEmpty())
        ->values();

        $tiposCaja    = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $subrubrosMap = $rubros->mapWithKeys(fn($r) => [
            $r->id => $r->subrubros->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->values(),
        ]);

        return view('cashflow.movimiento', compact('rubros', 'tiposCaja', 'subrubrosMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'subrubro_id'   => 'required|exists:subrubros,id',
            'monto'         => 'required|numeric|min:0.01',
            'fecha'         => 'required|date',
            'observaciones' => 'required|string|max:500',
        ]);

        try {
            $this->cashflowService->registrarMovimientoAdmin([
                'usuario_admin_id' => Auth::id(),
                'tipo_caja_id'     => $request->input('tipo_caja_id'),
                'subrubro_id'      => $request->input('subrubro_id'),
                'monto'            => $request->input('monto'),
                'fecha'            => $request->input('fecha'),
                'observaciones'    => $request->input('observaciones'),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('web.cashflow.index')->with('success', 'Movimiento directo registrado en cashflow.');
    }
}
