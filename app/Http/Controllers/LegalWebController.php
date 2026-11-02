<?php

namespace App\Http\Controllers;

use App\Services\LegalService;
use Illuminate\Http\Request;

class LegalWebController extends Controller
{
    public function __construct(private LegalService $legal) {}

    public function aceptar(Request $request)
    {
        if ($this->legal->aceptacionVigente($request->user())) {
            return redirect()->route('login');
        }

        return view('legal.aceptar', [
            'terminos' => $this->legal->html('terminos'),
            'privacidad' => $this->legal->html('privacidad'),
        ]);
    }

    public function guardar(Request $request)
    {
        $request->validate(
            ['acepto' => 'accepted'],
            ['acepto.accepted' => 'Para usar Wings tenés que aceptar los términos y la política de privacidad.']
        );

        $this->legal->registrar($request->user(), $request);

        // /login reparte a cada rol a su pantalla de inicio.
        return redirect()->route('login');
    }

    public function index(Request $request)
    {
        return view('legal.index', [
            'terminos' => $this->legal->html('terminos'),
            'privacidad' => $this->legal->html('privacidad'),
            'aviso' => $request->user()->isProfesor() ? null : $this->legal->html('aviso-familias'),
            'aceptacion' => $this->legal->aceptacionVigente($request->user()),
        ]);
    }
}
