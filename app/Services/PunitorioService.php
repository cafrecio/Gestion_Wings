<?php

namespace App\Services;

use App\Models\{Alumno, CargoAlumno, Configuracion, DeudaCuota, Subrubro, User};
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PunitorioService
{
    /** Canoniza el porcentaje, sin perder decimales ni admitir exponentes. */
    public static function normalizarPorcentaje(mixed $valor): ?string
    {
        if (!is_string($valor) && !is_int($valor) && !is_float($valor)) return null;
        $texto = str_replace(',', '.', trim((string) $valor));
        if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D', $texto)) return null;
        $centesimas = (int) round((float) $texto * 100);
        return $centesimas <= 10000 ? number_format($centesimas / 100, 2, '.', '') : null;
    }

    /** B11/D1–D3: solo cuotas del mes de Wings, nunca deuda vieja o inactivos. */
    public function aplicar(): int
    {
        $porcentaje = self::normalizarPorcentaje(Configuracion::get('mora_porcentaje', '0'));
        if ($porcentaje === null) throw new \LogicException('El porcentaje de mora debe estar entre 0 y 100, con hasta dos decimales.');
        $centesimas = (int) round((float) $porcentaje * 100);
        if ($centesimas === 0) return 0;

        $dia = Configuracion::get('mora_dia_desde');
        if (!is_int($dia) || $dia < 1 || $dia > 28) {
            throw new \LogicException('El día de mora debe ser un entero del 1 al 28.');
        }
        // Carbon recibe el reloj de Wings desde RelojSimulado al arrancar la aplicación.
        $hoy = Carbon::now()->startOfDay();
        if ($hoy->day <= $dia) return 0;
        $subrubro = Subrubro::where('nombre', 'Punitorio Cuota')->firstOrFail();
        $aplicados = 0;

        DeudaCuota::where('periodo', $hoy->format('Y-m'))
            ->whereIn('estado', [DeudaCuota::ESTADO_PENDIENTE, DeudaCuota::ESTADO_AJUSTADA])
            ->whereHas('alumno', fn ($q) => $q->where('activo', true))
            ->orderBy('id')->chunkById(100, function ($deudas) use ($hoy, $porcentaje, $centesimas, $subrubro, &$aplicados) {
                foreach ($deudas as $referencia) {
                    $aplicados += DB::transaction(function () use ($referencia, $hoy, $porcentaje, $centesimas, $subrubro) {
                        // Mismo orden que cobrar/perdonar: alumno, cuota, cargo.
                        $alumno = Alumno::whereKey($referencia->alumno_id)->lockForUpdate()->firstOrFail();
                        $deuda = DeudaCuota::whereKey($referencia->id)->lockForUpdate()->firstOrFail();
                        if (!$alumno->activo || $deuda->periodo !== $hoy->format('Y-m')
                            || !in_array($deuda->estado, [DeudaCuota::ESTADO_PENDIENTE, DeudaCuota::ESTADO_AJUSTADA])
                            || $deuda->saldo_pendiente <= 0) return 0;

                        // D2 y T38: la cuota del alta o del reingreso no lleva recargo dentro de su plazo.
                        $limite = app(CobranzaEstadoService::class)->limiteDeLaCuota($alumno, $deuda);
                        if ($limite && $hoy->lt($limite)) return 0;
                        $origen = 'punitorio:deuda:'.$deuda->id;
                        if (CargoAlumno::where('clave_origen', $origen)->lockForUpdate()->exists()) return 0;
                        $baseCentavos = (int) round((float) $deuda->monto_original * 100);
                        // Redondeo de medio centavo hacia arriba con aritmética entera.
                        $importeCentavos = intdiv($baseCentavos * $centesimas + 5000, 10000);
                        if ($importeCentavos <= 0) return 0;
                        $cargo = CargoAlumno::create([
                            'alumno_id' => $alumno->id, 'deuda_cuota_id' => $deuda->id, 'tipo' => 'PUNITORIO',
                            'clave_origen' => $origen, 'dni' => InscripcionService::dni($alumno->dni ?? ''),
                            'subrubro_id' => $subrubro->id, 'estado' => 'VIGENTE',
                            'monto_original' => number_format($importeCentavos / 100, 2, '.', ''),
                            'calculo' => ['porcentaje' => $porcentaje, 'base' => $deuda->monto_original,
                                'fecha_aplicacion' => $hoy->toDateString(), 'periodo' => $deuda->periodo],
                        ]);
                        $this->evento($cargo, null, 'CREAR', 'Aplicación automática del recargo por mora', $cargo->calculo);
                        return 1;
                    });
                }
            });
        return $aplicados;
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
        $usuario = User::findOrFail($usuarioId);
        if (!$usuario->isAdmin() || !$usuario->isActivo()) throw new AuthorizationException('Solo el dueño puede perdonar o reactivar recargos.');
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 10 || mb_strlen($motivo) > 500) {
            throw new \InvalidArgumentException('El motivo debe tener entre 10 y 500 caracteres.');
        }
        return DB::transaction(function () use ($cargoId, $motivo, $usuarioId, $reactivar) {
            $referencia = CargoAlumno::findOrFail($cargoId);
            Alumno::whereKey($referencia->alumno_id)->lockForUpdate()->firstOrFail();
            if ($referencia->deuda_cuota_id) DeudaCuota::whereKey($referencia->deuda_cuota_id)->lockForUpdate()->firstOrFail();
            $cargo = CargoAlumno::whereKey($cargoId)->lockForUpdate()->firstOrFail();
            if ($cargo->tipo !== 'PUNITORIO' || $cargo->estado !== 'VIGENTE') {
                throw new \RuntimeException('Solo se puede modificar el perdón de un recargo vigente.');
            }
            $anterior = (float) $cargo->monto_condonado;
            if ($reactivar) {
                if ($anterior <= 0) throw new \RuntimeException('El recargo no tiene ningún importe condonado para reactivar.');
                $importe = $anterior;
                $cargo->monto_condonado = 0;
            } else {
                if ($anterior > 0) throw new \RuntimeException('El recargo ya tiene un perdón. Reactivalo antes de condonar nuevamente.');
                $importe = $cargo->saldo_pendiente;
                if ($importe <= 0) throw new \RuntimeException('El recargo no tiene saldo pendiente para condonar.');
                $cargo->monto_condonado = $importe;
            }
            $cargo->save();
            $this->evento($cargo, $usuarioId, $reactivar ? 'REACTIVAR' : 'CONDONAR', $motivo, [
                'importe' => $importe, 'monto_condonado_anterior' => $anterior,
                'monto_condonado_nuevo' => (float) $cargo->monto_condonado, 'saldo_pendiente' => $cargo->saldo_pendiente,
            ]);
            return $cargo;
        });
    }

    private function evento(CargoAlumno $cargo, ?int $usuarioId, string $accion, string $motivo, array $detalle): void
    {
        DB::table('cargo_alumno_eventos')->insert([
            'cargo_alumno_id' => $cargo->id, 'usuario_id' => $usuarioId, 'accion' => $accion,
            'motivo' => $motivo, 'detalle' => json_encode($detalle), 'created_at' => now(),
        ]);
    }
}
