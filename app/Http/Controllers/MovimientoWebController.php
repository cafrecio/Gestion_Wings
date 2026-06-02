<?php

namespace App\Http\Controllers;

use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovimientoWebController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = MovimientoOperativo::with([
            'cajaOperativa.usuarioOperativo',
            'tipoCaja',
            'subrubro.rubro',
            'alumno',
        ]);

        if (!$user->isAdmin()) {
            $query->whereHas('cajaOperativa', fn($q) =>
                $q->where('usuario_operativo_id', $user->id)
            );
        }

        if ($request->filled('desde')) {
            $query->where('fecha', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->where('fecha', '<=', $request->input('hasta'));
        }
        if ($request->filled('tipo_caja_id')) {
            $query->where('tipo_caja_id', $request->input('tipo_caja_id'));
        }
        if ($request->filled('rubro_id')) {
            $query->whereHas('subrubro', fn($q) =>
                $q->where('rubro_id', $request->input('rubro_id'))
            );
        }
        if ($request->filled('subrubro_id')) {
            $query->where('subrubro_id', $request->input('subrubro_id'));
        }
        if ($request->filled('tipo')) {
            $query->whereHas('subrubro.rubro', fn($q) =>
                $q->where('tipo', $request->input('tipo'))
            );
        }
        if ($user->isAdmin() && $request->filled('usuario_id')) {
            $query->whereHas('cajaOperativa', fn($q) =>
                $q->where('usuario_operativo_id', $request->input('usuario_id'))
            );
        }

        $total       = $query->sum('monto');
        $movimientos = $query->orderByDesc('fecha')->orderByDesc('created_at')->paginate(30)->withQueryString();

        $tiposCaja  = TipoCaja::orderBy('nombre')->get();
        $rubros     = Rubro::orderBy('nombre')->get();
        $subrubros  = Subrubro::orderBy('nombre')->get();
        $operativos = $user->isAdmin()
            ? User::where('rol', User::ROL_OPERATIVO)->orderBy('name')->get()
            : collect();

        return view('movimientos.index', compact(
            'movimientos', 'total', 'tiposCaja', 'rubros', 'subrubros', 'operativos'
        ));
    }
}
