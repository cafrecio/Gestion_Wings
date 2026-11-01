<?php

namespace App\Http\Controllers;

use App\Models\TipoCaja;
use App\Rules\ImportePesos;
use App\Services\TransferenciaCashflowService;
use App\Services\AnulacionTransferenciaCashflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransferenciaCashflowWebController extends Controller
{
    public function anularForm(int $id, AnulacionTransferenciaCashflowService $servicio)
    {
        $transferencia = $servicio->desdeMovimiento($id);
        if ($transferencia->anulada_at !== null) {
            return redirect()->route('web.cashflow.index', ['periodo' => 'mes', 'fecha' => $transferencia->fecha])
                ->with('error', 'La transferencia ya está anulada.');
        }
        return view('cashflow.anular-transferencia', ['transferencia' => $transferencia,
            'fecha' => Carbon::parse($transferencia->fecha), 'movimientoId' => $id]);
    }

    public function anular(Request $request, int $id, AnulacionTransferenciaCashflowService $servicio)
    {
        $transferencia = $servicio->desdeMovimiento($id);
        $transferenciaId = $servicio->anular($id, $request->user()->id, $request->all());
        return redirect()->route('web.cashflow.index', ['periodo' => 'mes', 'fecha' => $transferencia->fecha])
            ->with('success', 'Transferencia #'.$transferenciaId.' anulada. Los originales y los dos asientos en contra se conservan.');
    }

    public function create()
    {
        $tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $clave = (string) Str::uuid();
        return view('cashflow.transferencia', compact('tiposCaja', 'clave'));
    }

    public function store(Request $request, TransferenciaCashflowService $servicio)
    {
        ImportePesos::preparar($request, ['monto']);
        $datos = $servicio->validarDatos($request->all());
        $fechaVieja = Carbon::parse($datos['fecha'])->lessThan(now()->startOfMonth()->startOfDay());
        if ($fechaVieja && !$request->boolean('confirmar_fecha_vieja')) {
            return back()->withInput()->with('aviso_fecha_vieja',
                'La transferencia quedará con esa fecha y cambiará los saldos de ambas cuentas desde ese mes. El resultado del club no cambia. Confirmá para guardar la salida y la entrada.');
        }
        $existia = \Illuminate\Support\Facades\DB::table('cashflow_transferencias')->where('clave', $datos['clave'])->exists();
        $id = $servicio->registrar($request->user()->id, $request->all());
        if ($fechaVieja && !$existia) {
            app(\App\Services\AvisoAdminService::class)->fechaVieja(
                que: 'Transferencia entre cuentas', fechaDelMovimiento: $datos['fecha'],
                monto: \App\Support\FormatoImporte::pesos($datos['monto']), quienLoCargo: $request->user()->name,
                dondeEntra: 'Salida y entrada en Cashflow, con su fecha real',
            );
        }
        return redirect()->route('web.cashflow.index')
            ->with('success', 'Transferencia #'.$id.' registrada: salida y entrada guardadas.');
    }
}
