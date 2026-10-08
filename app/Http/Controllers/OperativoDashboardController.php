<?php

namespace App\Http\Controllers;

use App\Models\AlumnoRevisionCobranza;
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

        // 1. Cajas del día
        $inicioHoyUtc = $hoyAr->copy()->startOfDay()->setTimezone('UTC');
        $finHoyUtc    = $hoyAr->copy()->endOfDay()->setTimezone('UTC');

        // Caja abierta propia de hoy
        $cajaPropia = CajaOperativa::where('usuario_operativo_id', $user->id)
            ->where('estado', 'ABIERTA')
            ->whereBetween('apertura_at', [$inicioHoyUtc, $finHoyUtc])
            ->first();

        // Caja abierta en el club por cualquier usuario hoy (cajón compartido)
        $cajaClub = null;
        if (!$cajaPropia) {
            $cajaClub = CajaOperativa::where('estado', 'ABIERTA')
                ->whereBetween('apertura_at', [$inicioHoyUtc, $finHoyUtc])
                ->with('usuarioOperativo')
                ->first();
        }

        // Última caja propia si no hay abierta
        $ultimaCaja = null;
        if (!$cajaPropia && !$cajaClub) {
            $ultimaCaja = CajaOperativa::where('usuario_operativo_id', $user->id)
                ->whereBetween('apertura_at', [$inicioHoyUtc, $finHoyUtc])
                ->orderBy('apertura_at', 'desc')
                ->first();
        }

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
        $cajasRechazadasCount = CajaOperativa::where('usuario_operativo_id', $user->id)
            ->where('estado', 'RECHAZADA')
            ->count();

        // Clases del día con indicador de asistencia cargada
        $clasesHoy = Clase::with(['grupo.deporte', 'grupo.nivel'])
            ->withCount(['asistencias as presentes_count' => fn($q) => $q->where('presente', true)])
            ->whereDate('fecha', $hoyAr->toDateString())
            ->where('cancelada', false)
            ->orderBy('hora_inicio')
            ->get();

        // Cantidad de alumnos activos con deuda
        $alumnosConDeuda = DeudaCuota::whereNotIn('estado', [DeudaCuota::ESTADO_PAGADA, DeudaCuota::ESTADO_CONDONADA])
            ->whereRaw('monto_pagado < monto_original')
            ->whereHas('alumno', fn($q) => $q->where('activo', true))
            ->distinct('alumno_id')
            ->count('alumno_id');

        // Cantidad de alumnos en revisión (posibles inactivos)
        $posiblesInactivos = AlumnoRevisionCobranza::where('estado_revision', AlumnoRevisionCobranza::ESTADO_PENDIENTE)
            ->count();

        return view('operativo.dashboard', compact(
            'estado', 'cajas', 'totalCobradoHoy', 'numCobrosHoy', 'hoyAr',
            'cajasRechazadasCount', 'clasesHoy', 'alumnosConDeuda', 'posiblesInactivos',
            'cajaPropia', 'cajaClub', 'ultimaCaja'
        ));
    }
}
