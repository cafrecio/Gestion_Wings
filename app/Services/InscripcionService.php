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

    public function parametros(): array
    {
        $corte = Configuracion::get('inscripcion_fecha_corte');
        $importe = Configuracion::get('inscripcion_importe');
        if (!is_string($corte) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $corte) || !is_numeric($importe) || (float) $importe <= 0) {
            throw ValidationException::withMessages(['fecha_alta' => 'La configuración de inscripción está incompleta. ADMIN debe corregirla antes del alta.']);
        }
        return [$corte, round((float) $importe, 2)];
    }

    public function sincronizar(Alumno $alumno, ?int $usuarioId, ?string $fechaAnterior = null, ?string $motivo = null): void
    {
        $this->bloquear($alumno->dni);
        $cargo = $this->cargo($alumno->dni);
        $fecha = $alumno->fecha_alta->format('Y-m-d');
        if ($fechaAnterior !== null && $cargo && $cargo->monto_cobrado > 0) {
            throw ValidationException::withMessages(['fecha_alta' => 'No se puede modificar el ingreso: la inscripción tiene pagos registrados.']);
        }
        [$corte, $importe] = $this->parametros();
        if ($fechaAnterior === null && $cargo) return;
        if ($fecha < $corte) {
            if ($cargo && $cargo->estado !== 'ANULADO') {
                $cargo->update(['estado' => 'ANULADO']);
                $this->evento($cargo, $usuarioId, 'ANULAR', $motivo, ['fecha_anterior' => $fechaAnterior, 'fecha_nueva' => $fecha]);
            }
            return;
        }
        if (!$cargo) {
            $cargo = CargoAlumno::create(['alumno_id' => $alumno->id, 'tipo' => 'INSCRIPCION', 'dni' => self::dni($alumno->dni),
                'clave_origen' => 'inscripcion:dni:'.self::dni($alumno->dni),
                'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
                'monto_original' => $importe, 'calculo' => ['fecha_ingreso' => $fecha, 'corte' => $corte], 'estado' => 'VIGENTE']);
            $this->evento($cargo, $usuarioId, 'CREAR', $motivo ?? 'Alta de persona desde fecha de corte', ['fecha_anterior' => $fechaAnterior, 'fecha_nueva' => $fecha]);
        } elseif ($cargo->estado === 'ANULADO') {
            $cargo->update(['estado' => 'VIGENTE']);
            $this->evento($cargo, $usuarioId, 'REACTIVAR', $motivo, ['fecha_anterior' => $fechaAnterior, 'fecha_nueva' => $fecha]);
        }
    }

    private function evento(CargoAlumno $cargo, ?int $usuario, string $accion, ?string $motivo, array $detalle): void
    {
        DB::table('cargo_alumno_eventos')->insert(['cargo_alumno_id' => $cargo->id, 'usuario_id' => $usuario,
            'accion' => $accion, 'motivo' => $motivo ?? 'Corrección de fecha de ingreso', 'detalle' => json_encode($detalle), 'created_at' => now()]);
    }
}
