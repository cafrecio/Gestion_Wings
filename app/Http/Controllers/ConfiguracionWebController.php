<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\ReglaPrimerPago;
use Illuminate\Http\Request;

class ConfiguracionWebController extends Controller
{
    public function index()
    {
        $configuraciones  = Configuracion::orderBy('clave')->get();
        $reglasPrimerPago = ReglaPrimerPago::orderBy('dia_desde')->get();

        return view('configuraciones.index', compact('configuraciones', 'reglasPrimerPago'));
    }

    public function update(Request $request, string $clave)
    {
        $config = Configuracion::where('clave', $clave)->firstOrFail();

        $rules = match ($config->tipo) {
            'integer' => ['valor' => 'required|integer|min:1|max:31'],
            'boolean' => ['valor' => 'required|boolean'],
            default   => ['valor' => 'required|string|max:255'],
        };

        $validated = $request->validate($rules);

        $config->update(['valor' => (string) $validated['valor']]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'valor' => $config->valor]);
        }

        return redirect()->route('web.configuraciones.index')
            ->with('success', 'Configuración guardada.');
    }
}
