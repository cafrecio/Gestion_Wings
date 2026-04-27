<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use Illuminate\Http\Request;

class NivelWebController extends Controller
{
    public function index()
    {
        $niveles = Nivel::withCount('grupos')->orderBy('nombre')->paginate(20);
        return view('niveles.index', compact('niveles'));
    }

    public function create()
    {
        $nivelesExistentes = Nivel::orderBy('nombre')->get();
        return view('niveles.create', compact('nivelesExistentes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede tener más de 100 caracteres.',
        ]);

        $nombreNormalizado = $this->normalizarNombre($validated['nombre']);

        $existe = Nivel::whereRaw(
            'LOWER(CONVERT(nombre USING utf8mb4)) = ?',
            [$nombreNormalizado]
        )->exists();

        if ($existe) {
            return back()
                ->withErrors(['nombre' => 'Ya existe un nivel con un nombre similar.'])
                ->withInput();
        }

        Nivel::create($request->only('nombre', 'descripcion'));

        return redirect()->route('web.niveles.index')->with('success', 'Nivel creado correctamente.');
    }

    public function checkDisponible(Request $request)
    {
        $nombre  = $request->input('nombre', '');
        $nivelId = $request->input('nivel_id');

        if (empty(trim($nombre))) {
            return response()->json(['disponible' => true]);
        }

        $normalizado = $this->normalizarNombre($nombre);

        $existe = Nivel::whereRaw(
            'LOWER(CONVERT(nombre USING utf8mb4)) = ?',
            [$normalizado]
        )->when($nivelId, fn($q) => $q->where('id', '!=', $nivelId))
         ->exists();

        return response()->json(['disponible' => !$existe]);
    }

    public function edit(int $id)
    {
        $nivel = Nivel::findOrFail($id);
        return view('niveles.edit', compact('nivel'));
    }

    public function update(Request $request, int $id)
    {
        $nivel = Nivel::findOrFail($id);

        $validated = $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede tener más de 100 caracteres.',
        ]);

        $nombreNormalizado = $this->normalizarNombre($validated['nombre']);

        $existe = Nivel::whereRaw(
            'LOWER(CONVERT(nombre USING utf8mb4)) = ?',
            [$nombreNormalizado]
        )->where('id', '!=', $nivel->id)->exists();

        if ($existe) {
            return back()
                ->withErrors(['nombre' => 'Ya existe un nivel con un nombre similar.'])
                ->withInput();
        }

        $nivel->update($request->only('nombre', 'descripcion'));

        return redirect()->route('web.niveles.index')->with('success', 'Nivel actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $nivel = Nivel::withCount('grupos')->findOrFail($id);

        if ($nivel->grupos_count > 0) {
            return back()->with('error', "No se puede eliminar: hay {$nivel->grupos_count} grupo(s) que usan este nivel.");
        }

        $nivel->delete();

        return redirect()->route('web.niveles.index')->with('success', 'Nivel eliminado.');
    }

    private function normalizarNombre(string $nombre): string
    {
        $nombre = mb_strtolower(trim($nombre));
        $nombre = strtr($nombre, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e','ì'=>'i',
            'ò'=>'o','ù'=>'u',
        ]);
        return preg_replace('/\s+/', ' ', $nombre);
    }
}
