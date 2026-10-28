<?php

namespace App\Http\Controllers;

use App\Services\DeudaInactivosService;
use Illuminate\Http\Request;

class DeudaInactivosWebController extends Controller
{
    public function perder(Request $request, int $id, DeudaInactivosService $servicio)
    {
        $datos = $request->validate(['centavos' => ['required', 'integer', 'min:1']]);
        $perdida = $servicio->perder($id, (int) $datos['centavos'], $request->user());
        return response()->json(['mensaje' => 'Deuda dada por perdida.', 'id' => $id, 'centavos' => (int) round((float) $perdida->importe * 100)]);
    }
}
