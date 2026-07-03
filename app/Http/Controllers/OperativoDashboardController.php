<?php

namespace App\Http\Controllers;

use App\Models\CajaOperativa;
use App\Models\Clase;
use App\Models\DeudaCuota;
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

        // Cajas rechazadas pendientes de regularizar (cualquier fecha)
        $cajasRechazadas = CajaOperativa::where('usuario_operativo_id', $user->id)
            ->where('estado', 'RECHAZADA')
            ->orderByDesc('apertura_at')
            ->get();

        // Clases del día con indicador de asistencia cargada
        $clasesHoy = Clase::with(['grupo.deporte', 'grupo.nivel'])
            ->withCount(['asistencias as presentes_count' => fn($q) => $q->where('presente', true)])
            ->whereDate('fecha', $hoyAr->toDateString())
            ->where('cancelada', false)
            ->orderBy('hora_inicio')
            ->get();

        // Alumnos activos con deuda, ordenados por saldo (mayor primero)
        $deudores = DeudaCuota::selectRaw('alumno_id, SUM(monto_original - monto_pagado) as saldo, COUNT(*) as cuotas')
            ->whereNotIn('estado', [DeudaCuota::ESTADO_PAGADA, DeudaCuota::ESTADO_CONDONADA])
            ->whereRaw('monto_pagado < monto_original')
            ->whereHas('alumno', fn($q) => $q->where('activo', true))
            ->groupBy('alumno_id')
            ->orderByDesc('saldo')
            ->with('alumno.grupo')
            ->get();

        return view('operativo.dashboard', compact(
            'estado', 'cajas', 'totalCobradoHoy', 'numCobrosHoy', 'hoyAr',
            'cajasRechazadas', 'clasesHoy', 'deudores'
        ));
    }
}
