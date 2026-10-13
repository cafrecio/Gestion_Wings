<?php

namespace App\Http\Controllers;

use App\Models\{CashflowMovimiento, TipoCaja};
use App\Rules\ImportePesos;
use App\Services\CashflowCorreccionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CashflowCorreccionWebController extends Controller
{
    public function __construct(private CashflowCorreccionService $correcciones) {}

    public function show(int $id)
    {
        $movimiento = CashflowMovimiento::with(['subrubro.rubro', 'tipoCaja', 'contraOrigen'])->findOrFail($id);
        $origen = $movimiento->contraOrigen ?? $movimiento;
        $historial = $origen->correcciones()->with('admin')->orderByDesc('id')->get();
        return view('cashflow.detalle', compact('movimiento', 'historial'));
    }

    public function edit(int $id)
    {
        $movimiento = $this->editable($id);
        $datos = app(CashflowWebController::class)->create()->getData();
        $datos['tiposCaja'] = TipoCaja::where('activo', true)->orWhere('id', $movimiento->tipo_caja_id)->orderBy('nombre')->get();
        return view('cashflow.movimiento', $datos + ['movimiento' => $movimiento, 'clave' => (string) Str::uuid(),
            'historial' => $movimiento->correcciones()->with('admin')->orderByDesc('id')->get()]);
    }

    public function eliminarForm(int $id)
    {
        $movimiento = $this->editable($id);
        return view('cashflow.eliminar', ['movimiento' => $movimiento, 'clave' => (string) Str::uuid()]);
    }

    public function update(Request $request, int $id)
    {
        ImportePesos::preparar($request, ['monto']);
        $this->correcciones->editar($id, $request->user()->id, $request->all());
        return redirect()->route('web.cashflow.movimientos.show', $id)->with('success', 'Movimiento actualizado. El historial se conserva.');
    }

    public function destroy(Request $request, int $id)
    {
        $this->correcciones->eliminar($id, $request->user()->id, $request->all());
        return redirect()->route('web.cashflow.movimientos.show', $id)->with('success', 'Movimiento anulado. El original y el asiento en contra se conservan.');
    }

    private function editable(int $id): CashflowMovimiento
    {
        abort_unless(CashflowCorreccionService::disponible(), 503, 'Primero debe prepararse el registro de correcciones.');
        $mov = CashflowMovimiento::with(['subrubro.rubro', 'tipoCaja'])->findOrFail($id);
        abort_unless($mov->puedeCorregirse(), 403, 'Usá el recorrido propio de este movimiento.');
        return $mov;
    }
}
