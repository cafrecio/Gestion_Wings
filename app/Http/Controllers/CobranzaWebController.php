<?php

namespace App\Http\Controllers;

use App\Models\Deporte;
use App\Models\Grupo;
use App\Services\CobranzaEstadoService;
use Illuminate\Http\Request;

class CobranzaWebController extends Controller
{
    public function __construct(private CobranzaEstadoService $cobranzaService) {}

    public function index(Request $request)
    {
        $estadoFiltro = in_array($request->input('estado'), ['AL_DIA', 'MOROSO', 'DEUDOR'])
            ? $request->input('estado')
            : null;
        $deporteId = $request->filled('deporte_id') ? (int) $request->input('deporte_id') : null;
        $grupoId   = $request->filled('grupo_id')   ? (int) $request->input('grupo_id')   : null;

        $alumnos = $this->cobranzaService->filtrarAlumnosPorEstado($estadoFiltro, $deporteId, $grupoId);

        $resumen = $this->cobranzaService->resumenDashboard();

        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos   = Grupo::where('activo', true)->orderBy('nombre')->get();

        return view('cobranza.index', compact(
            'alumnos', 'resumen', 'deportes', 'grupos',
            'estadoFiltro', 'deporteId', 'grupoId'
        ));
    }
}
