<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\DeudaCuota;
use App\Models\User;
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
            if (!Auth::user()->isActivo()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta fue desactivada.',
                ])->onlyInput('email');
            }

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

    private function redirectByRole($user)
    {
        return match ($user->rol) {
            User::ROL_ADMIN    => redirect()->route('admin.dashboard'),
            User::ROL_PROFESOR => redirect()->route('web.clases.index'),
            default            => redirect()->route('web.operativo.dashboard'),
        };
    }
}
