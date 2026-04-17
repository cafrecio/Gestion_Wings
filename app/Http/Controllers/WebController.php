<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\DeudaCuota;
use App\Models\TipoCaja;
use App\Services\PagoCuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebController extends Controller
{
    public function loginForm()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->redirectByRole(Auth::user());
        }

        return back()->withErrors([
            'email' => 'Las credenciales ingresadas no son válidas.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function adminDashboard()
    {
        $alumnosActivos = Alumno::where('activo', true)->count();
        $alumnosInactivos = Alumno::where('activo', false)->count();
        $alumnosConDeuda = Alumno::where('activo', true)
            ->whereHas('deudaCuotas', fn($q) => $q->where('estado', DeudaCuota::ESTADO_PENDIENTE))
            ->count();
        $totalDeudaPendiente = DeudaCuota::where('estado', DeudaCuota::ESTADO_PENDIENTE)
            ->selectRaw('SUM(monto_original - monto_pagado) as total')
            ->value('total') ?? 0;
        $alumnosNuevosMes = Alumno::where('activo', true)
            ->whereMonth('fecha_alta', now()->month)
            ->whereYear('fecha_alta', now()->year)
            ->count();

        return view('admin.dashboard', compact(
            'alumnosActivos',
            'alumnosInactivos',
            'alumnosConDeuda',
            'totalDeudaPendiente',
            'alumnosNuevosMes'
        ));
    }

    public function caja(Request $request)
    {
        $query = Alumno::with(['deporte', 'grupo.deporte', 'grupo.nivel'])
            ->where('activo', true)
            ->whereHas('deudaCuotas', fn($q) => $q->where('estado', DeudaCuota::ESTADO_PENDIENTE))
            ->with(['deudaCuotas' => fn($q) => $q->where('estado', DeudaCuota::ESTADO_PENDIENTE)->orderBy('periodo')]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        $alumnos = $query->orderBy('apellido')->orderBy('nombre')->paginate(12)->withQueryString();

        return view('operativo.caja', compact('alumnos'));
    }

    public function cajaAlumnoCobro(int $id)
    {
        $alumno = Alumno::with([
            'deporte', 'grupo.deporte', 'grupo.nivel',
            'deudaCuotas' => fn($q) => $q->where('estado', DeudaCuota::ESTADO_PENDIENTE)->orderBy('periodo'),
        ])->findOrFail($id);

        $tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();

        return view('operativo.cobrar', compact('alumno', 'tiposCaja'));
    }

    public function cajaRegistrarPago(Request $request, int $id, PagoCuotaService $pagoCuotaService)
    {
        $alumno = Alumno::findOrFail($id);

        $request->validate([
            'tipo_caja_id'   => 'required|exists:tipos_caja,id',
            'periodos'       => 'required|array|min:1',
            'periodos.*'     => 'required|string|regex:/^\d{4}-\d{2}$/',
            'observaciones'  => 'nullable|string|max:500',
        ], [
            'tipo_caja_id.required' => 'Debe seleccionar el tipo de pago.',
            'tipo_caja_id.exists'   => 'El tipo de pago seleccionado no es válido.',
            'periodos.required'     => 'Debe seleccionar al menos un período a pagar.',
        ]);

        // Obtener las deudas pendientes seleccionadas, FIFO
        $deudas = DeudaCuota::where('alumno_id', $id)
            ->where('estado', DeudaCuota::ESTADO_PENDIENTE)
            ->whereIn('periodo', $request->input('periodos'))
            ->orderBy('periodo')
            ->get();

        if ($deudas->isEmpty()) {
            return back()->with('error', 'No se encontraron deudas pendientes para los períodos seleccionados.');
        }

        $items = $deudas->map(fn($d) => [
            'periodo' => $d->periodo,
            'monto'   => $d->saldo_pendiente,
        ])->values()->all();

        try {
            $pagoCuotaService->registrarPagoCuotaOperativo([
                'alumno_id'            => $id,
                'tipo_caja_id'         => $request->input('tipo_caja_id'),
                'usuario_operativo_id' => Auth::id(),
                'items'                => $items,
                'fecha_pago'           => today()->toDateString(),
                'observaciones'        => $request->input('observaciones'),
            ]);

            return redirect()->route('operativo.caja')->with('success', "Pago registrado para {$alumno->apellido}, {$alumno->nombre}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function redirectByRole($user)
    {
        if ($user->rol === 'ADMIN') {
            return redirect('/admin/dashboard');
        }

        return redirect('/caja');
    }
}
