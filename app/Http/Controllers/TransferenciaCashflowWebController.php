<?php

namespace App\Http\Controllers;

use App\Models\TipoCaja;
use App\Rules\ImportePesos;
use App\Services\TransferenciaCashflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransferenciaCashflowWebController extends Controller
{
    public function create()
    {
        $tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $clave = (string) Str::uuid();
        return view('cashflow.transferencia', compact('tiposCaja', 'clave'));
    }

    public function store(Request $request, TransferenciaCashflowService $servicio)
    {
        ImportePesos::preparar($request, ['monto']);
        $id = $servicio->registrar($request->user()->id, $request->all());
        return redirect()->route('web.cashflow.index')
            ->with('success', 'Transferencia #'.$id.' registrada: salida y entrada guardadas.');
    }
}
