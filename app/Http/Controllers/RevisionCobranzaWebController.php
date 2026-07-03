<?php

namespace App\Http\Controllers;

use App\Models\AlumnoRevisionCobranza;
use App\Services\RevisionCobranzaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RevisionCobranzaWebController extends Controller
{
    public function __construct(private RevisionCobranzaService $revisionService) {}

    public function index(Request $request)
    {
        $estadoFiltro  = $request->input('estado', 'PENDIENTE');
        $periodoFiltro = $request->input('periodo');

        $revisiones = AlumnoRevisionCobranza::with(['alumno.deporte', 'alumno.grupo', 'usuarioResolucion'])
            ->when($estadoFiltro, fn($q) => $q->where('estado_revision', $estadoFiltro))
            ->when($periodoFiltro, fn($q) => $q->where('periodo_objetivo', $periodoFiltro))
            ->orderBy('periodo_objetivo', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        $totalPendientes = AlumnoRevisionCobranza::where('estado_revision', AlumnoRevisionCobranza::ESTADO_PENDIENTE)->count();

        $periodos = AlumnoRevisionCobranza::select('periodo_objetivo')
            ->distinct()
            ->orderBy('periodo_objetivo', 'desc')
            ->pluck('periodo_objetivo');

        return view('revision-cobranza.index', compact(
            'revisiones', 'totalPendientes', 'estadoFiltro', 'periodoFiltro', 'periodos'
        ));
    }

    public function resolver(Request $request, int $id)
    {
        $request->validate([
            'resolucion'      => 'required|in:CONTINUA,INACTIVO',
            'nota_resolucion' => 'required|string|min:5|max:500',
        ]);

        try {
            $this->revisionService->resolver(
                $id,
                $request->input('resolucion'),
                $request->input('nota_resolucion'),
                Auth::id()
            );

            $msg = $request->input('resolucion') === 'CONTINUA'
                ? 'Alumno confirmado como activo. Se generó la deuda del período.'
                : 'Alumno marcado como inactivo.';

            return redirect()->route('web.revision-cobranza.index')->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
