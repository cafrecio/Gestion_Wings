<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

/** Calendario y aviso de bloques de cancha para la creación web de clases. */
class ProgramacionClasesService
{
    private const DIAS = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];

    /** Valida y normaliza el calendario; grupo y profesores se validan en el controlador. */
    public function validar(array $entrada): array
    {
        $entrada['tipo_creacion'] ??= 'unica';
        $tipo = $entrada['tipo_creacion'];
        $porDia = $tipo === 'recurrente' && array_key_exists('horarios', $entrada);
        $reglas = ['tipo_creacion' => 'required|in:unica,recurrente'];
        if ($tipo === 'recurrente') {
            $reglas += [
                'fecha_desde' => 'required|date|after_or_equal:today',
                'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
                'dias_semana' => 'required|array|min:1',
                'dias_semana.*' => 'required|integer|in:0,1,2,3,4,5,6',
            ];
        } else {
            $reglas['fecha'] = 'required|date|after_or_equal:today';
        }
        if ($porDia) {
            $reglas['horarios'] = 'required|array|min:1';
        } else {
            $reglas += ['hora_inicio' => 'required|date_format:H:i', 'hora_fin' => 'required|date_format:H:i|after:hora_inicio'];
        }
        $datos = Validator::make($entrada, $reglas, [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'fecha_desde.required' => 'La fecha de inicio es obligatoria.',
            'fecha_desde.after_or_equal' => 'No se puede crear una clase con fecha pasada.',
            'fecha_hasta.required' => 'La fecha de fin es obligatoria.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a fecha desde.',
            'dias_semana.required' => 'Debe seleccionar al menos un día de la semana.',
            'dias_semana.min' => 'Debe seleccionar al menos un día de la semana.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_fin.required' => 'La hora de fin es obligatoria.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ])->validate();

        if ($tipo === 'unica') {
            return ['tipo_creacion' => 'unica', 'fecha' => Carbon::parse($datos['fecha'])->toDateString(),
                'horarios' => [['hora_inicio' => $datos['hora_inicio'], 'hora_fin' => $datos['hora_fin']]]];
        }

        $dias = array_values(array_unique(array_map('intval', $datos['dias_semana'])));
        sort($dias);
        $horarios = [];
        if ($porDia) {
            $reglasHorarios = [];
            $mensajes = [];
            foreach ($dias as $dia) {
                $inicio = "horarios.{$dia}.hora_inicio";
                $fin = "horarios.{$dia}.hora_fin";
                $reglasHorarios[$inicio] = 'required|date_format:H:i';
                $reglasHorarios[$fin] = "required|date_format:H:i|after:{$inicio}";
                $mensajes["{$inicio}.required"] = 'Falta la hora de inicio del '.self::DIAS[$dia].'.';
                $mensajes["{$fin}.required"] = 'Falta la hora de fin del '.self::DIAS[$dia].'.';
                $mensajes["{$fin}.after"] = self::DIAS[$dia].': la hora de fin debe ser posterior al inicio.';
            }
            $datosHorarios = Validator::make($entrada, $reglasHorarios, $mensajes)->validate();
            foreach ($dias as $dia) {
                $horarios[$dia] = ['hora_inicio' => $datosHorarios['horarios'][$dia]['hora_inicio'],
                    'hora_fin' => $datosHorarios['horarios'][$dia]['hora_fin']];
            }
        } else {
            foreach ($dias as $dia) {
                $horarios[$dia] = ['hora_inicio' => $datos['hora_inicio'], 'hora_fin' => $datos['hora_fin']];
            }
        }

        return ['tipo_creacion' => 'recurrente', 'fecha_desde' => Carbon::parse($datos['fecha_desde'])->toDateString(),
            'fecha_hasta' => Carbon::parse($datos['fecha_hasta'])->toDateString(), 'horarios' => $horarios];
    }

    /** Una serie conserva un único identificador y se guarda atómicamente en el controlador. */
    public function clases(array $programacion): iterable
    {
        if ($programacion['tipo_creacion'] === 'unica') {
            yield ['fecha' => $programacion['fecha']] + $programacion['horarios'][0];
            return;
        }
        $hasta = Carbon::parse($programacion['fecha_hasta']);
        for ($fecha = Carbon::parse($programacion['fecha_desde']); $fecha->lte($hasta); $fecha->addDay()) {
            if (isset($programacion['horarios'][$fecha->dayOfWeek])) {
                yield ['fecha' => $fecha->toDateString()] + $programacion['horarios'][$fecha->dayOfWeek];
            }
        }
    }

    /** El aviso cuenta bloques del reloj; no crea reservas ni calcula importes de alquiler. */
    public function aviso(array $programacion, int $grupoId, array $profesoresIds, int $usuarioId): ?array
    {
        $horarios = [];
        foreach ($programacion['horarios'] as $dia => $horario) {
            $inicio = $this->minutos($horario['hora_inicio']);
            $fin = $this->minutos($horario['hora_fin']);
            if ($inicio % 60 === 0 && $fin % 60 === 0) {
                continue;
            }
            $desde = intdiv($inicio, 60);
            $hasta = (int) ceil($fin / 60);
            $bloques = [];
            for ($hora = $desde; $hora < $hasta; $hora++) {
                $bloques[] = sprintf('%02d:00–%02d:00', $hora, $hora + 1);
            }
            $cantidad = count($bloques);
            $duracion = $fin - $inicio;
            $textoDuracion = $duracion === 60 ? '1 hora' : "{$duracion} minutos";
            $prefijo = $programacion['tipo_creacion'] === 'recurrente' ? self::DIAS[$dia].': ' : '';
            $horarios[] = ['detalle' => $prefijo.$horario['hora_inicio'].'–'.$horario['hora_fin'].
                ": dura {$textoDuracion}, pero ocupa {$cantidad} ".($cantidad === 1 ? 'bloque' : 'bloques').
                ' de alquiler: '.implode(', ', $bloques).'.', 'bloques' => $cantidad,
                'campo' => $programacion['tipo_creacion'] === 'recurrente' ? "horario-{$dia}-inicio" : 'hora_inicio'];
        }
        if ($horarios === []) {
            return null;
        }
        $profesoresIds = array_values(array_unique(array_map('intval', $profesoresIds)));
        sort($profesoresIds);
        $contenido = json_encode(['version' => 1, 'usuario_id' => $usuarioId, 'grupo_id' => $grupoId,
            'profesores' => $profesoresIds, 'programacion' => $programacion], JSON_THROW_ON_ERROR);

        return ['horarios' => $horarios, 'campo' => $horarios[0]['campo'],
            'firma' => hash_hmac('sha256', $contenido, config('app.key'))];
    }

    public function requiereConfirmacion(?array $aviso, mixed $confirmacion): bool
    {
        return $aviso !== null && (!is_string($confirmacion) || !hash_equals($aviso['firma'], $confirmacion));
    }

    private function minutos(string $hora): int
    {
        [$horas, $minutos] = array_map('intval', explode(':', $hora));
        return $horas * 60 + $minutos;
    }
}
