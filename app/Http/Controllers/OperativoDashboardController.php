<?php

namespace App\Http\Controllers;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Services\OperativoEstadoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class OperativoDashboardController extends Controller
{
    private const TZ = 'America/Argentina/Buenos_Aires';

    public function __construct(private OperativoEstadoService $estadoService) {}

    public function index()
    {
        $user = Auth::user();
        $hoyAr = Carbon::now(self::TZ);
        $estado = $this->estadoService->obtenerEstadoHoy($user->id, $hoyAr);

        // Stats del día actual para este operativo
        $cajas = CajaOperativa::where('usuario_operativo_id', $user->id)
            ->whereDate('apertura_at', $hoyAr->toDateString())
            ->with(['movimientos' => fn($q) => $q->where('estado', 'ACTIVO')->with('subrubro.rubro')])
            ->get();

        $totalCobradoHoy = 0;
        $numCobrosHoy    = 0;
        foreach ($cajas as $caja) {
            foreach ($caja->movimientos as $mov) {
                if ($mov->subrubro?->rubro?->tipo === 'INGRESO') {
                    $totalCobradoHoy += (float) $mov->monto;
                    $numCobrosHoy++;
                }
            }
        }

        return view('operativo.dashboard', compact(
            'estado', 'cajas', 'totalCobradoHoy', 'numCobrosHoy', 'hoyAr'
        ));
    }
}
