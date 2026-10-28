<?php

namespace App\Http\Controllers;

use App\Services\ManualService;
use Illuminate\Http\Request;

class AyudaWebController extends Controller
{
    public function __construct(
        protected ManualService $manualService
    ) {}

    /**
     * Índice general de la ayuda según el rol del usuario conectado.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $indice = $this->manualService->listarPorRol($user);

        return view('ayuda.index', [
            'agrupadas' => $indice['agrupadas'],
            'total'     => $indice['total'],
            'paginas'   => $indice['paginas'],
            'user'      => $user,
        ]);
    }

    /**
     * Muestra una página específica del manual del usuario.
     */
    public function show(Request $request, string $slug)
    {
        $user = $request->user();
        $pagina = $this->manualService->buscarPagina($slug, $user);

        if (!$pagina) {
            abort(404, 'La página del manual solicitada no existe.');
        }

        return view('ayuda.show', [
            'pagina' => $pagina,
            'user'   => $user,
        ]);
    }
}
