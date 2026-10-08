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

        // El criterio de visibilidad es el RUBRO, nunca quién lo registró
        // (PERMISOS-ROLES.md §"Historial de movimientos"). Hasta el 23/09/2026 esta
        // pantalla filtraba por caja propia, y eso dejaba al mostrador sin saber que
        // una madre ya había pagado en el turno del otro operativo. Lo que el
        // operativo no puede cargar —subrubros de ADMIN— sigue sin verlo.
        if (!$user->isAdmin()) {
            $query->whereHas('subrubro', fn($q) => $q->where('permitido_para', 'OPERATIVO'));
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

        // Totales solo de movimientos activos, separados por tipo (nunca mezclar I+E)
        $totalIngresos = (clone $query)->where('estado', 'ACTIVO')
            ->whereHas('subrubro.rubro', fn($q) => $q->where('tipo', 'INGRESO'))->sum('monto');
        $totalEgresos  = (clone $query)->where('estado', 'ACTIVO')
            ->whereHas('subrubro.rubro', fn($q) => $q->where('tipo', 'EGRESO'))->sum('monto');
        $movimientos = $query->orderByDesc('fecha')->orderByDesc('created_at')->paginate(30)->withQueryString();

        $tiposCaja  = TipoCaja::orderBy('nombre')->get();
        // Los filtros siguen el mismo criterio que la lista: el operativo no ve ni
        // los nombres de lo que el admin reserva para si (sueldos, alquileres). Antes
        // el desplegable los listaba todos aunque la lista no mostrara sus movimientos.
        $subrubros  = Subrubro::when(!$user->isAdmin(), fn ($q) => $q->where('permitido_para', 'OPERATIVO'))
            ->orderBy('nombre')->get();
        $rubros     = Rubro::when(!$user->isAdmin(), fn ($q) => $q->whereIn('id', $subrubros->pluck('rubro_id')))
            ->orderBy('nombre')->get();
        $operativos = $user->isAdmin()
            ? User::where('rol', User::ROL_OPERATIVO)->orderBy('name')->get()
            : collect();

        return view('movimientos.index', compact(
            'movimientos', 'totalIngresos', 'totalEgresos', 'tiposCaja', 'rubros', 'subrubros', 'operativos'
        ));
    }
}
