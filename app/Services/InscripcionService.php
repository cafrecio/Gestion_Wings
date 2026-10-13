<?php

namespace App\Services;

use App\Models\{Alumno, CargoAlumno, Configuracion, Subrubro};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InscripcionService
{
    public static function dni(string $dni): string
    {
        return preg_replace('/[.\s-]+/', '', trim($dni));
    }

    // Fila estable por persona: protege también dos altas simultáneas en deportes distintos.
    public function bloquear(string $dni, bool $crear = true): void
    {
        $dni = self::dni($dni);
        $fila = DB::table('inscripcion_personas')->where('dni', $dni)->lockForUpdate()->first();
        if ($fila || !$crear) return;
        DB::table('inscripcion_personas')->insertOrIgnore(['dni' => $dni]);
        DB::table('inscripcion_personas')->where('dni', $dni)->lockForUpdate()->first();
    }

    public function cargo(string $dni): ?CargoAlumno
    {
        $query = CargoAlumno::where('clave_origen', 'inscripcion:dni:'.self::dni($dni));
        if (DB::transactionLevel() > 0) $query->lockForUpdate();
        return $query->first();
    }

    public function importe(): float
    {
        $importe = Configuracion::get('inscripcion_importe');
        if (!is_numeric($importe) || (float) $importe <= 0) {
            throw ValidationException::withMessages(['fecha_alta' => 'La configuración de inscripción está incompleta. ADMIN debe corregirla antes del alta.']);
        }
        return round((float) $importe, 2);
    }

    public function sincronizar(Alumno $alumno, ?int $usuarioId, ?string $fechaAnterior = null, ?string $motivo = null): void
    {
        $this->bloquear($alumno->dni);
        $cargo = $this->cargo($alumno->dni);
        $fecha = $alumno->fecha_alta->format('Y-m-d');
        if ($fechaAnterior !== null && $cargo && $cargo->monto_cobrado > 0) {
            throw ValidationException::withMessages(['fecha_alta' => 'No se puede modificar el ingreso: la inscripción tiene pagos registrados.']);
        }
        if ($fechaAnterior !== null) {
            // Corregir una fecha no crea ni anula cargos de alumnos existentes.
            if ($cargo) {
                $this->evento($cargo, $usuarioId, 'CORREGIR_FECHA', $motivo, ['fecha_anterior' => $fechaAnterior, 'fecha_nueva' => $fecha]);
            }
            return;
        }
        if ($cargo) return;
        $importe = $this->importe();
        if (!$cargo) {
            $cargo = CargoAlumno::create(['alumno_id' => $alumno->id, 'tipo' => 'INSCRIPCION', 'dni' => self::dni($alumno->dni),
                'clave_origen' => 'inscripcion:dni:'.self::dni($alumno->dni),
                'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
                'monto_original' => $importe, 'calculo' => ['fecha_ingreso' => $fecha], 'estado' => 'VIGENTE']);
            $this->evento($cargo, $usuarioId, 'CREAR', $motivo ?? 'Alta manual de persona', ['fecha_anterior' => $fechaAnterior, 'fecha_nueva' => $fecha]);
        }
    }

    public function condonar(int $cargoId, string $motivo, int $usuarioId): CargoAlumno
    {
        return $this->decidirPerdon($cargoId, $motivo, $usuarioId, false);
    }

    public function reactivar(int $cargoId, string $motivo, int $usuarioId): CargoAlumno
    {
        return $this->decidirPerdon($cargoId, $motivo, $usuarioId, true);
    }

    private function decidirPerdon(int $cargoId, string $motivo, int $usuarioId, bool $reactivar): CargoAlumno
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 10 || mb_strlen($motivo) > 500) {
            throw new \InvalidArgumentException('El motivo debe tener entre 10 y 500 caracteres.');
        }
        return DB::transaction(function () use ($cargoId, $motivo, $usuarioId, $reactivar) {
            $referencia = CargoAlumno::findOrFail($cargoId);
            $this->bloquear($referencia->dni, false);
            Alumno::whereKey($referencia->alumno_id)->lockForUpdate()->firstOrFail();
            $cargo = CargoAlumno::whereKey($cargoId)->lockForUpdate()->firstOrFail();
            if ($cargo->tipo !== 'INSCRIPCION' || $cargo->estado !== 'VIGENTE') {
                throw new \RuntimeException('Solo se puede modificar el perdón de una inscripción VIGENTE.');
            }
            $anterior = (float) $cargo->monto_condonado;
            if ($reactivar) {
                if ($anterior <= 0) throw new \RuntimeException('La inscripción no tiene ningún importe condonado para reactivar.');
                $importe = $anterior;
                $cargo->monto_condonado = 0;
            } else {
                if ($anterior > 0) throw new \RuntimeException('La inscripción ya tiene un perdón. Reactivala antes de condonar nuevamente.');
                $importe = $cargo->saldo_pendiente;
                if ($importe <= 0) throw new \RuntimeException('La inscripción no tiene saldo pendiente para condonar.');
                $cargo->monto_condonado = $importe;
            }
            $cargo->save();
            $this->evento($cargo, $usuarioId, $reactivar ? 'REACTIVAR' : 'CONDONAR', $motivo, [
                'importe' => $importe, 'monto_condonado_anterior' => $anterior,
                'monto_condonado_nuevo' => (float) $cargo->monto_condonado,
                'saldo_pendiente' => $cargo->saldo_pendiente,
            ]);
            return $cargo;
        });
    }

    private function evento(CargoAlumno $cargo, ?int $usuario, string $accion, ?string $motivo, array $detalle): void
    {
        DB::table('cargo_alumno_eventos')->insert(['cargo_alumno_id' => $cargo->id, 'usuario_id' => $usuario,
            'accion' => $accion, 'motivo' => $motivo ?? 'Corrección de fecha de ingreso', 'detalle' => json_encode($detalle), 'created_at' => now()]);
    }
}
