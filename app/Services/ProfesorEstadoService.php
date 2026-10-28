<?php

namespace App\Services;

use App\Models\{CajaOperativa, Profesor, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfesorEstadoService
{
    public function cambiar(int $id, User $actor, ?bool $activo = null): Profesor
    {
        return DB::transaction(function () use ($id, $actor, $activo) {
            $admin = User::findOrFail($actor->id);
            abort_unless($admin->activo && $admin->isAdmin(), 403);
            $profesor = Profesor::whereKey($id)->lockForUpdate()->firstOrFail();
            $nuevo = $activo ?? !$profesor->activo;
            if (!$nuevo) {
                // Lectura actual después de esperar el bloqueo, no una foto anterior.
                $futuras = $profesor->clases()->where('clases.cancelada', false)->noTerminadas()->lockForUpdate()->get(['clases.id']);
                if ($futuras->isNotEmpty()) {
                    throw ValidationException::withMessages(['profesor' => 'Tiene '.$futuras->count().' clases futuras o en curso. Reasignalas antes de desactivar a la profesora.']);
                }
                $usuarios = $profesor->user()->lockForUpdate()->get();
                foreach ($usuarios as $usuario) {
                    if ($usuario->es_superadmin || $usuario->id === $admin->id) {
                        throw ValidationException::withMessages(['profesor' => 'La cuenta vinculada está protegida. No se puede desactivar desde Profesores.']);
                    }
                    if (CajaOperativa::where('usuario_operativo_id', $usuario->id)->where('estado', 'ABIERTA')->exists()) {
                        throw ValidationException::withMessages(['profesor' => 'Primero cerrá la caja abierta del usuario vinculado.']);
                    }
                    $usuario->forceFill(['activo' => false, 'remember_token' => Str::random(60)])->save();
                }
                $conexion = DB::connection(config('session.connection'));
                if ($conexion->getSchemaBuilder()->hasTable(config('session.table', 'sessions'))) {
                    $conexion->table(config('session.table', 'sessions'))->whereIn('user_id', $usuarios->pluck('id'))->delete();
                }
            }
            $profesor->update(['activo' => $nuevo]);
            // Reactivar la profesora no reactiva ni cambia la cuenta de acceso.
            return $profesor;
        }, 3);
    }
}
