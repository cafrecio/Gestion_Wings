<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UsuarioWebController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('name')->paginate(30);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = User::getRoles();

        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => ['required', 'confirmed', Password::min(8)],
            'rol'                   => 'required|in:ADMIN,OPERATIVO,PROFESOR',
        ], [
            'name.required'         => 'El nombre es obligatorio.',
            'email.required'        => 'El email es obligatorio.',
            'email.email'           => 'El email no tiene un formato válido.',
            'email.unique'          => 'Ya existe un usuario con ese email.',
            'password.required'     => 'La contraseña es obligatoria.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'rol.required'          => 'El rol es obligatorio.',
            'rol.in'                => 'El rol seleccionado no es válido.',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'rol'      => $request->rol,
        ]);

        return redirect()->route('web.usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(int $id)
    {
        $usuario = User::findOrFail($id);
        $roles   = User::getRoles();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, int $id)
    {
        $usuario = User::findOrFail($id);

        // Prevenir que el usuario cambie su propio rol
        if (Auth::id() === $usuario->id && $request->rol !== $usuario->rol) {
            return back()->withInput()->with('error', 'No podés cambiar tu propio rol.');
        }

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$usuario->id}",
            'rol'   => 'required|in:ADMIN,OPERATIVO,PROFESOR',
        ], [
            'name.required'  => 'El nombre es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.email'    => 'El email no tiene un formato válido.',
            'email.unique'   => 'Ya existe un usuario con ese email.',
            'rol.required'   => 'El rol es obligatorio.',
            'rol.in'         => 'El rol seleccionado no es válido.',
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'rol'   => $request->rol,
        ];

        // Contraseña es opcional en edición
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Password::min(8)],
            ], [
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]);
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        return redirect()->route('web.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $usuario = User::findOrFail($id);

        if ($usuario->id === Auth::id()) {
            return back()->with('error', 'No podés inactivarte a vos mismo.');
        }

        $usuario->update(['activo' => !$usuario->activo]);

        return back();
    }

    public function checkEmail(Request $request)
    {
        $email     = trim($request->input('email', ''));
        $usuarioId = $request->input('usuario_id');

        if ($email === '') {
            return response()->json(['disponible' => true]);
        }

        $existe = User::where('email', $email)
            ->when($usuarioId, fn($q) => $q->where('id', '!=', $usuarioId))
            ->exists();

        return response()->json(['disponible' => !$existe]);
    }
}
