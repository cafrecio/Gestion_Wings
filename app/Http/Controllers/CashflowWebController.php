<?php

namespace App\Http\Controllers;

use App\Models\Rubro;
use App\Models\TipoCaja;
use App\Services\CashflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashflowWebController extends Controller
{
    public function __construct(private CashflowService $cashflowService) {}

    public function create()
    {
        $rubros = Rubro::with(['subrubros' => function ($q) {
            $q->where('es_reservado_sistema', false)
              ->where('permitido_para', 'ADMIN')
              ->where('nombre', '!=', 'Cuota Mensual')
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

        return redirect()->route('web.caja.index')->with('success', 'Movimiento directo registrado en cashflow.');
    }
}
