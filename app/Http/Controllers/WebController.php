<?php

namespace App\Http\Controllers;

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
        return view('admin.dashboard');
    }

    public function caja()
    {
        return view('operativo.caja');
    }

    private function redirectByRole($user)
    {
        if ($user->rol === 'ADMIN') {
            return redirect('/admin/dashboard');
        }

        return redirect('/caja');
    }
}
