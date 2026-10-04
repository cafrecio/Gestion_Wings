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
        $estadoInput = $request->input('estado');
        $estadoFiltro = $request->has('estado')
            ? ($estadoInput === '' ? 'TODOS' : $estadoInput)
            : 'DEUDORES';

        $deporteId = $request->filled('deporte_id') ? (int) $request->input('deporte_id') : null;
        $grupoId   = $request->filled('grupo_id')   ? (int) $request->input('grupo_id')   : null;

        $alumnos = $this->cobranzaService->listadoCobranza($estadoFiltro, $deporteId, $grupoId);
        $dnis = $alumnos->map(fn ($a) => \App\Services\InscripcionService::dni($a->dni))->filter()->unique();
        $inscripciones = $dnis->isEmpty()
            ? collect()
            : \App\Models\CargoAlumno::where('tipo', 'INSCRIPCION')->where('estado', 'VIGENTE')
                ->whereIn('dni', $dnis)
                ->with('pagos')->get()->mapWithKeys(fn ($c) => [$c->dni => round((float) $c->monto_original - (float) $c->monto_condonado - (float) $c->pagos->sum('pivot.monto_aplicado'), 2)]);

        $resumen = $this->cobranzaService->resumenDashboard();

        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos = Grupo::with(['deporte', 'nivel'])
            ->where('grupos.activo', true)
            ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
            ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
            ->orderBy('deportes.nombre')
            ->orderBy('niveles.nombre')
            ->select('grupos.*')
            ->get();

        return view('cobranza.index', compact(
            'alumnos', 'resumen', 'deportes', 'grupos',
            'estadoFiltro', 'deporteId', 'grupoId', 'inscripciones'
        ));
    }
}
