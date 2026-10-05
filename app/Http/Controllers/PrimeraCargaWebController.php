<?php

namespace App\Http\Controllers;

use App\Models\{Deporte, Grupo, Nivel, Pago, PrimeraCarga};
use App\Services\PrimeraCargaExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PrimeraCargaWebController extends Controller
{
    public function index(Request $request, PrimeraCargaExcelService $service)
    {
        $estado = PrimeraCarga::findOrFail(1);
        $catalogos = $service->catalogos();
        $conteos = ['deportes' => Deporte::where('activo', true)->count(), 'niveles' => Nivel::count(),
            'grupos' => Grupo::where('activo', true)->count(), 'planes' => count($catalogos)];
        $revision = $request->session()->get('primera_carga_revision');
        if ($estado->estado === 'PENDIENTE' && in_array($request->query('paso'), ['1', '2', '3'], true)) {
            $request->session()->put('primera_carga_paso', (int) $request->query('paso'));
        }
        $paso = $estado->estado === 'TERMINADA' ? 4 : (!$catalogos ? 1 : $request->session()->get('primera_carga_paso', 1));
        $hayCobros = Pago::exists();
        return view('primera-carga.index', compact('estado', 'catalogos', 'conteos', 'revision', 'paso', 'hayCobros'));
    }

    public function continuar(Request $request, PrimeraCargaExcelService $service)
    {
        $this->exigirPendiente();
        if (!$service->catalogos()) throw ValidationException::withMessages(['archivo' => 'Prepará al menos un grupo con un plan activo y precio.']);
        $request->session()->put('primera_carga_paso', 2);
        $this->limpiarRevision($request);
        return redirect()->route('web.primera-carga.index');
    }

    public function plantilla(Request $request, PrimeraCargaExcelService $service)
    {
        $this->exigirPendiente();
        if (!$service->catalogos()) throw ValidationException::withMessages(['archivo' => 'Faltan planes activos con precio.']);
        $request->session()->put('primera_carga_paso', 3);
        return $this->excel($service->plantilla(), 'primera-carga.xlsx');
    }

    public function revisar(Request $request, PrimeraCargaExcelService $service)
    {
        $this->exigirPendiente();
        $request->validate(['archivo' => ['required', 'file', 'mimes:xlsx', 'max:10240']], [
            'archivo.required' => 'Elegí el Excel que completaste.', 'archivo.mimes' => 'Guardá y elegí un archivo Excel .xlsx.', 'archivo.max' => 'El archivo puede pesar hasta 10 MB.',
        ]);
        $this->limpiarRevision($request);
        $path = $request->file('archivo')->store('primera-carga/'.$request->user()->id, 'local');
        $resultado = $service->revisar(Storage::disk('local')->path($path));
        // Solo rutas asignadas por el servidor, privadas y vinculadas a esta sesión/usuario.
        $request->session()->put('primera_carga_revision', ['path' => $path, 'usuario_id' => $request->user()->id,
            'sha256' => hash_file('sha256', Storage::disk('local')->path($path)), 'errores' => $resultado['errores'], 'resumen' => $resultado['resumen']]);
        $request->session()->put('primera_carga_paso', $resultado['errores'] ? 3 : 4);
        return redirect()->route('web.primera-carga.index');
    }

    public function informe(Request $request, PrimeraCargaExcelService $service)
    {
        $revision = $this->revision($request);
        $destino = tempnam(sys_get_temp_dir(), 'p1-informe-');
        try {
            $service->guardarInforme(Storage::disk('local')->path($revision['path']), $revision['errores'], $destino);
            return response()->download($destino, 'club-con-errores-revisado.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store'])->deleteFileAfterSend(true);
        } catch (\Throwable $e) { unlink($destino); throw $e; }
    }

    public function cargar(Request $request, PrimeraCargaExcelService $service)
    {
        $this->exigirPendiente();
        $request->validate(['confirmar' => ['required', 'accepted']], ['confirmar.accepted' => 'Confirmá el resumen antes de cargar.', 'confirmar.required' => 'Confirmá el resumen antes de cargar.']);
        $revision = $this->revision($request);
        if ($revision['errores']) throw ValidationException::withMessages(['archivo' => 'Corregí todos los errores y revisá otra vez.']);
        $resultado = $service->cargar(Storage::disk('local')->path($revision['path']), $request->user()->id, $revision['resumen']);
        if ($resultado['errores']) {
            $request->session()->put('primera_carga_revision', [...$revision, 'errores' => $resultado['errores']]);
            throw ValidationException::withMessages(['archivo' => 'El archivo ya no está listo. Mirá el informe y volvé a revisarlo.']);
        }
        $this->limpiarRevision($request);
        $request->session()->put('primera_carga_paso', 4);
        return redirect()->route('web.primera-carga.index');
    }

    public function deshacer(Request $request, PrimeraCargaExcelService $service)
    {
        $service->deshacer($request->user()->id);
        $this->limpiarRevision($request);
        $request->session()->put('primera_carga_paso', 1);
        return redirect()->route('web.primera-carga.index')->with('success', 'Se deshizo solamente la primera carga. Los catálogos y usuarios se conservan.');
    }

    private function exigirPendiente(): void
    {
        if (!PrimeraCarga::pendiente()) throw ValidationException::withMessages(['archivo' => 'La primera carga ya está terminada.']);
    }

    private function revision(Request $request): array
    {
        $r = $request->session()->get('primera_carga_revision');
        if (!$r || $r['usuario_id'] !== $request->user()->id || !Storage::disk('local')->exists($r['path'])
            || !hash_equals($r['sha256'], hash_file('sha256', Storage::disk('local')->path($r['path'])))) {
            throw ValidationException::withMessages(['archivo' => 'Primero revisá el Excel en esta sesión.']);
        }
        return $r;
    }

    private function limpiarRevision(Request $request): void
    {
        $r = $request->session()->get('primera_carga_revision');
        if ($r && $r['usuario_id'] === $request->user()->id) Storage::disk('local')->delete($r['path']);
        $request->session()->forget('primera_carga_revision');
    }

    private function excel($libro, string $nombre)
    {
        return response()->streamDownload(fn () => (new Xlsx($libro))->save('php://output'), $nombre,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
    }
}
