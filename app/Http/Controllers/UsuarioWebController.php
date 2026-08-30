<?php

namespace App\Http\Controllers;

use App\Models\Profesor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UsuarioWebController extends Controller
{
    public function index()
    {
        $usuarios = User::with('profesor')
            ->where(function ($query) {
                $query->where('es_superadmin', false)
                    ->orWhere('id', Auth::id());
            })
            ->orderBy('name')
            ->paginate(30);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = User::getRoles();

        $profesoresSinUsuario = Profesor::where('activo', true)
            ->whereDoesntHave('user')
            ->orderBy('apellido')
            ->get();

        return view('usuarios.create', compact('roles', 'profesoresSinUsuario'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'rol'      => 'required|in:ADMIN,OPERATIVO,PROFESOR',
        ];

        if ($request->input('rol') === User::ROL_PROFESOR) {
            $rules['profesor_id'] = 'required|exists:profesores,id';
        }

        $request->validate($rules, [
            'name.required'         => 'El nombre es obligatorio.',
            'email.required'        => 'El email es obligatorio.',
            'email.email'           => 'El email no tiene un formato válido.',
            'email.unique'          => 'Ya existe un usuario con ese email.',
            'password.required'     => 'La contraseña es obligatoria.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'rol.required'          => 'El rol es obligatorio.',
            'rol.in'                => 'El rol seleccionado no es válido.',
            'profesor_id.required'  => 'Debe seleccionar el profesor vinculado.',
            'profesor_id.exists'    => 'El profesor seleccionado no existe.',
        ]);

        $profesorId = null;

        if ($request->input('rol') === User::ROL_PROFESOR) {
            $profesor = Profesor::findOrFail($request->profesor_id);
            if ($profesor->user) {
                return back()->withInput()->with('error', 'Ese profesor ya tiene un usuario asignado.');
            }
            $profesorId = $profesor->id;
        }

        $user = new User([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'profesor_id' => $profesorId,
        ]);
        $user->rol    = $request->rol;
        $user->activo = true;
        $user->save();

        return redirect()->route('web.usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(int $id)
    {
        $usuario = User::with('profesor')->findOrFail($id);
        $this->autorizarCuentaProtegida($usuario);
        $roles   = User::getRoles();

        $profesoresSinUsuario = Profesor::where('activo', true)
            ->where(function ($q) use ($usuario) {
                $q->whereDoesntHave('user')
                  ->orWhere('id', $usuario->profesor_id);
            })
            ->orderBy('apellido')
            ->get();

        return view('usuarios.edit', compact('usuario', 'roles', 'profesoresSinUsuario'));
    }

    public function update(Request $request, int $id)
    {
        $usuario = User::findOrFail($id);
        $this->autorizarCuentaProtegida($usuario);

        if (Auth::id() === $usuario->id && $request->rol !== $usuario->rol) {
            return back()->withInput()->with('error', 'No podés cambiar tu propio rol.');
        }

        $rules = [
            'name'  => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$usuario->id}",
            'rol'   => 'required|in:ADMIN,OPERATIVO,PROFESOR',
        ];

        if ($request->input('rol') === User::ROL_PROFESOR) {
            $rules['profesor_id'] = 'required|exists:profesores,id';
        }

        $request->validate($rules, [
            'name.required'         => 'El nombre es obligatorio.',
            'email.required'        => 'El email es obligatorio.',
            'email.email'           => 'El email no tiene un formato válido.',
            'email.unique'          => 'Ya existe un usuario con ese email.',
            'rol.required'          => 'El rol es obligatorio.',
            'rol.in'                => 'El rol seleccionado no es válido.',
            'profesor_id.required'  => 'Debe seleccionar el profesor vinculado.',
            'profesor_id.exists'    => 'El profesor seleccionado no existe.',
        ]);

        $profesorId = null;

        if ($request->input('rol') === User::ROL_PROFESOR) {
            $profesor = Profesor::findOrFail($request->profesor_id);
            // Verificar que no esté vinculado a otro usuario distinto al actual
            if ($profesor->user && $profesor->user->id !== $usuario->id) {
                return back()->withInput()->with('error', 'Ese profesor ya tiene un usuario asignado.');
            }
            $profesorId = $profesor->id;
        }
        $usuario->name        = $request->name;
        $usuario->email       = $request->email;
        $usuario->rol         = $request->rol;
        $usuario->profesor_id = $profesorId;

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Password::min(8)],
            ], [
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]);
            $usuario->password = Hash::make($request->password);
        }

        $usuario->save();

        return redirect()->route('web.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleActivo(Request $request, int $id)
    {
        $usuario = User::findOrFail($id);
        $this->autorizarCuentaProtegida($usuario);

        if ($usuario->id === Auth::id()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No podés inactivarte a vos mismo.'], 403);
            }
            return back()->with('error', 'No podés inactivarte a vos mismo.');
        }

        $usuario->activo = !$usuario->activo;
        $usuario->save();

        if ($request->expectsJson()) {
            return response()->json(['activo' => (bool) $usuario->activo]);
        }

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

    private function autorizarCuentaProtegida(User $usuario): void
    {
        if ($usuario->es_superadmin && $usuario->id !== Auth::id()) {
            abort(403);
        }
    }
}
