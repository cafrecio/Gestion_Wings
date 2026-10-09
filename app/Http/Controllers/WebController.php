<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReporteMensualService;
use App\Support\RegistroReporteDisponible;
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

    public function adminDashboard(ReporteMensualService $reportes)
    {
        $mes = today()->format('Y-m');
        // Inicio siempre muestra el mes en curso. No admite filtros que cambien
        // silenciosamente los avisos actuales ni requiere migrar la base del club.
        $reporte = RegistroReporteDisponible::existe() ? $reportes->obtener($mes) : [
            'mes' => $mes, 'fecha_corte' => today()->toDateString(), 'historial_desde' => null,
            'ingresos' => null, 'egresos' => null, 'resultado' => null, 'sin_clasificar' => 0,
            'deuda' => ['total' => null], 'por_pagar' => ['total' => null],
            'disponible' => null, 'cajas' => null, 'avisos' => $reportes->avisos(),
        ];
        return view('admin.dashboard', compact('reporte'));
    }

    private function redirectByRole($user)
    {
        if ($user->isAdmin() && \App\Models\PrimeraCarga::pendiente()) {
            return redirect()->route('web.primera-carga.index');
        }
        return match ($user->rol) {
            User::ROL_ADMIN    => redirect()->route('admin.dashboard'),
            User::ROL_PROFESOR => redirect()->route('web.clases.index'),
            default            => redirect()->route('web.operativo.dashboard'),
        };
    }
}
