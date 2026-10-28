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

    /**
     * Sirve imágenes de captura del manual con control estricto de autenticación y rol.
     */
    public function imagen(Request $request, string $rol, string $archivo)
    {
        $user = $request->user();
        $permitidos = $this->manualService->rolesPermitidos($user);

        if (!in_array($rol, $permitidos, true)) {
            abort(403, 'No tenés permisos para ver esta imagen de la ayuda.');
        }

        $ruta = $this->manualService->obtenerRutaImagen($rol, $archivo);
        if (!$ruta) {
            abort(404, 'La imagen solicitada no existe.');
        }

        return response()->file($ruta, [
            'Content-Type' => \Illuminate\Support\Facades\File::mimeType($ruta) ?: 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
