<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsistenciaRequest;
use App\Http\Requests\StoreAsistenciaBulkRequest;
use App\Models\Asistencia;
use App\Models\AsistenciaExceso;
use App\Models\Clase;
use App\Services\ClaseService;
use Illuminate\Http\JsonResponse;

class AsistenciaController extends Controller
{
    protected ClaseService $claseService;

    public function __construct(ClaseService $claseService)
    {
        $this->claseService = $claseService;
    }

    /**
     * Listar asistencias de una clase
     *
     * GET /api/asistencias/clase/{claseId}
     */
    public function indexByClase(int $claseId): JsonResponse
    {
        $clase = Clase::find($claseId);

        if (!$clase) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Clase no encontrada.'],
            ], 404);
        }

        $asistencias = Asistencia::with('alumno')
            ->where('clase_id', $claseId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'clase_id' => $claseId,
                'fecha' => $clase->fecha,
                'asistencias' => $asistencias,
                'total' => $asistencias->count(),
                'presentes' => $asistencias->where('presente', true)->count(),
            ],
        ]);
    }

    /**
     * Registrar asistencias bulk para una clase
     *
     * POST /api/asistencias/clase/{claseId}
     */
    public function storeBulk(StoreAsistenciaBulkRequest $request, int $claseId): JsonResponse
    {
        $clase = Clase::find($claseId);

        if (!$clase) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Clase no encontrada.'],
            ], 404);
        }

        if ($clase->cancelada) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CLASE_CANCELADA', 'message' => 'No se pueden registrar asistencias en una clase cancelada.'],
            ], 409);
        }

        $items = $request->validated()['items'];
        $registradas = [];
        $excesos = [];

        foreach ($items as $item) {
            $asistencia = Asistencia::updateOrCreate(
                [
                    'clase_id' => $claseId,
                    'alumno_id' => $item['alumno_id'],
                ],
                [
                    'presente' => $item['presente'],
                ]
            );
            $registradas[] = $asistencia;

            // Control de exceso si está presente
            if ($item['presente']) {
                $infoSemana = $this->claseService->contarAsistenciasSemana(
                    $item['alumno_id'],
                    $clase->fecha->format('Y-m-d')
                );

                if ($infoSemana['excede']) {
                    $motivoExceso = $item['motivo_exceso'] ?? null;
                    $excesoInfo = [
                        'alumno_id' => $item['alumno_id'],
                        'info_semana' => $infoSemana,
                        'motivo' => $motivoExceso,
                        'recuperacion_valida' => null,
                    ];

                    if ($motivoExceso) {
                        if ($motivoExceso === AsistenciaExceso::MOTIVO_RECUPERA) {
                            $excesoInfo['recuperacion_valida'] = $this->claseService->verificarRecuperacion(
                                $item['alumno_id'],
                                $clase->fecha->format('Y-m-d')
                            );
                            $detalle = $item['detalle_exceso'] ?? null;
                            if (!$excesoInfo['recuperacion_valida']) {
                                $detalle = ($detalle ? $detalle . ' | ' : '') . 'Recupero sin déficit previo';
                            }
                        }

                        AsistenciaExceso::create([
                            'asistencia_id' => $asistencia->id,
                            'alumno_id' => $item['alumno_id'],
                            'fecha_clase' => $clase->fecha->format('Y-m-d'),
                            'motivo' => $motivoExceso,
                            'detalle' => $detalle ?? $item['detalle_exceso'] ?? null,
                        ]);
                    }

                    $excesos[] = $excesoInfo;
                }
            }
        }

        $response = [
            'success' => true,
            'message' => 'Asistencias registradas.',
            'data' => [
                'clase_id' => $claseId,
                'registradas' => count($registradas),
            ],
        ];

        if (!empty($excesos)) {
            $response['data']['excesos'] = $excesos;
        }

        return response()->json($response, 201);
    }

    /**
     * Store a newly created asistencia in storage.
     *
     * Registra la asistencia de un alumno a una clase.
     * Valida que el alumno no tenga otra asistencia solapada si se marca como presente.
     */
    public function store(StoreAsistenciaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $asistencia = $this->claseService->registrarAsistencia(
                $validated['clase_id'],
                $validated['alumno_id'],
                $validated['presente']
            );

            $asistencia->load(['clase', 'alumno']);

            $responseData = $asistencia->toArray();

            // Control de exceso si está presente
            if ($validated['presente']) {
                $infoSemana = $this->claseService->contarAsistenciasSemana(
                    $validated['alumno_id'],
                    $asistencia->clase->fecha->format('Y-m-d')
                );

                $responseData['info_semana'] = $infoSemana;

                if ($infoSemana['excede']) {
                    $motivoExceso = $validated['motivo_exceso'] ?? null;
                    $detalle = $validated['detalle_exceso'] ?? null;
                    $recuperacionValida = null;

                    if ($motivoExceso) {
                        if ($motivoExceso === AsistenciaExceso::MOTIVO_RECUPERA) {
                            $recuperacionValida = $this->claseService->verificarRecuperacion(
                                $validated['alumno_id'],
                                $asistencia->clase->fecha->format('Y-m-d')
                            );
                            if (!$recuperacionValida) {
                                $detalle = ($detalle ? $detalle . ' | ' : '') . 'Recupero sin déficit previo';
                            }
                        }

                        AsistenciaExceso::create([
                            'asistencia_id' => $asistencia->id,
                            'alumno_id' => $validated['alumno_id'],
                            'fecha_clase' => $asistencia->clase->fecha->format('Y-m-d'),
                            'motivo' => $motivoExceso,
                            'detalle' => $detalle,
                        ]);
                    }

                    $responseData['exceso'] = [
                        'motivo' => $motivoExceso,
                        'recuperacion_valida' => $recuperacionValida,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente.',
                'data' => $responseData,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la asistencia.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verificar si un alumno puede asistir a una clase.
     */
    public function verificarDisponibilidadAlumno(int $claseId, int $alumnoId): JsonResponse
    {
        $resultado = $this->claseService->verificarDisponibilidadAlumno($claseId, $alumnoId);

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }
}
