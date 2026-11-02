<?php

namespace App\Services;

use App\Models\AceptacionLegal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LegalService
{
    public function aceptacionVigente(User $user): ?AceptacionLegal
    {
        return AceptacionLegal::where('user_id', $user->id)
            ->where('version', config('legal.version'))
            ->first();
    }

    public function registrar(User $user, Request $request): AceptacionLegal
    {
        return AceptacionLegal::firstOrCreate(
            ['user_id' => $user->id, 'version' => config('legal.version')],
            [
                'nombre' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
                'hash_contenido' => hash('sha256', $this->texto('terminos') . $this->texto('privacidad')),
                'ip' => $request->ip(),
                'navegador' => Str::limit((string) $request->userAgent(), 250, ''),
                'aceptado_en' => now(),
            ]
        );
    }

    public function html(string $documento): string
    {
        return Str::markdown($this->texto($documento), ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    private function texto(string $documento): string
    {
        return str_replace(':prestador', config('legal.prestador'), File::get(resource_path("legal/{$documento}.md")));
    }
}
