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

        // 1. Cajas del club: misma regla que CajaService / CajaWebController
        // Cualquier caja ABIERTA en el club (de hoy o de un turno anterior)
        $cajaAbiertaClub = CajaOperativa::where('estado', 'ABIERTA')
            ->with('usuarioOperativo')
            ->first();

        $cajaPropia = null;
        $cajaCompanero = null;
        $aperturaCompaneroTexto = null;
        $aperturaPropiaTexto = null;

        if ($cajaAbiertaClub) {
            if ($cajaAbiertaClub->usuario_operativo_id === $user->id) {
                $cajaPropia = $cajaAbiertaClub;
                if ($cajaPropia->apertura_at) {
                    $apPropiaAr = Carbon::parse($cajaPropia->apertura_at)->setTimezone(self::TZ);
                    $aperturaPropiaTexto = $apPropiaAr->isToday()
                        ? 'hoy a las ' . $apPropiaAr->format('H:i')
                        : ($apPropiaAr->isYesterday()
                            ? 'ayer a las ' . $apPropiaAr->format('H:i')
                            : 'el ' . $apPropiaAr->format('d/m') . ' a las ' . $apPropiaAr->format('H:i'));
                }
            } else {
                $cajaCompanero = $cajaAbiertaClub;
                if ($cajaCompanero->apertura_at) {
                    $apCompAr = Carbon::parse($cajaCompanero->apertura_at)->setTimezone(self::TZ);
                    $aperturaCompaneroTexto = $apCompAr->isToday()
                        ? 'hoy a las ' . $apCompAr->format('H:i')
                        : ($apCompAr->isYesterday()
                            ? 'ayer a las ' . $apCompAr->format('H:i')
                            : 'el ' . $apCompAr->format('d/m') . ' a las ' . $apCompAr->format('H:i'));
                }
            }
        }

        // Mantener compatibilidad de variable
        $cajaClub = $cajaCompanero;

        // Última caja propia si no hay ninguna caja abierta en el club
        $ultimaCaja = null;
        if (!$cajaAbiertaClub) {
            $inicioHoyUtc = $hoyAr->copy()->startOfDay()->setTimezone('UTC');
            $finHoyUtc    = $hoyAr->copy()->endOfDay()->setTimezone('UTC');
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
            'cajaPropia', 'cajaClub', 'cajaCompanero', 'ultimaCaja',
            'aperturaCompaneroTexto', 'aperturaPropiaTexto'
        ));
    }
}
