<?php

namespace App\Services;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CajaService
{
    private ?CashflowIntegracionCajaService $cashflowIntegracion = null;

    /**
     * Obtener el servicio de integración cashflow (lazy load).
     */
    private function getCashflowIntegracion(): CashflowIntegracionCajaService
    {
        if ($this->cashflowIntegracion === null) {
            $this->cashflowIntegracion = app(CashflowIntegracionCajaService::class);
        }
        return $this->cashflowIntegracion;
    }

    /** Una fila persistente serializa la configuración y los turnos del mismo cajón. */
    public function bloquearMostrador(): object
    {
        return DB::table('caja_mostrador')->where('id', 1)->lockForUpdate()->firstOrFail();
    }

    public function configurarMostrador(int $tipoCajaId, int $adminId): void
    {
        DB::transaction(function () use ($tipoCajaId, $adminId) {
            $admin = User::findOrFail($adminId);
            abort_unless($admin->isAdmin() && $admin->activo, 403);
            $configuracion = $this->bloquearMostrador();
            $tipo = TipoCaja::whereKey($tipoCajaId)->where('activo', true)->first();
            if (!$tipo) {
                throw ValidationException::withMessages(['tipo_caja_id' => 'Elegí un medio de pago activo.']);
            }
            if ($configuracion->tipo_caja_id !== null && (int) $configuracion->tipo_caja_id !== $tipoCajaId) {
                throw ValidationException::withMessages(['tipo_caja_id' => 'El medio del cajón ya está configurado.']);
            }
            if ($configuracion->tipo_caja_id === null) {
                DB::table('caja_mostrador')->where('id', 1)->update([
                    'tipo_caja_id' => $tipoCajaId, 'configurado_por_id' => $adminId, 'configurado_at' => now(),
                ]);
            }
        });
    }

    public function propuestaApertura(): array
    {
        $configuracion = DB::table('caja_mostrador')->where('id', 1)->firstOrFail();
        $origen = CajaOperativa::whereNotNull('cambio_retenido')->orderByDesc('id')->first();
        return [
            'tipo_caja_id' => $configuracion->tipo_caja_id,
            'caja_origen_id' => $origen?->id,
            'efectivo_heredado' => $origen?->cambio_retenido,
        ];
    }

    public function abrirCajaOperativa(int $operativoId, array $datos, ?int $actorId = null): CajaOperativa
    {
        Validator::make($datos, [
            'efectivo_inicial' => 'required|numeric|min:0|max:9999999999.99|decimal:0,2',
            'confirmacion' => 'required|accepted',
            'caja_origen_id' => 'nullable|integer',
            'motivo_apertura' => 'nullable|string|max:500',
        ])->validate();

        return DB::transaction(function () use ($operativoId, $datos, $actorId) {
            $configuracion = $this->bloquearMostrador();
            $operativo = User::findOrFail($operativoId);
            abort_unless($operativo->isOperativo() && $operativo->activo, 403);
            $actor = User::findOrFail($actorId ?? $operativoId);
            abort_unless($actor->activo && ($actor->isAdmin() || $actor->id === $operativoId), 403);
            if (!$configuracion->tipo_caja_id || !TipoCaja::whereKey($configuracion->tipo_caja_id)->where('activo', true)->exists()) {
                throw ValidationException::withMessages(['efectivo_inicial' => 'ADMIN debe configurar el medio de efectivo antes de abrir.']);
            }
            if (CajaOperativa::where('estado', 'ABIERTA')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['efectivo_inicial' => 'Hay un turno abierto. Cerralo antes de abrir otro.']);
            }
            $propuesta = $this->propuestaApertura();
            if (($datos['caja_origen_id'] ?? null) != $propuesta['caja_origen_id']) {
                throw ValidationException::withMessages(['efectivo_inicial' => 'Cambió el último cierre. Volvé a abrir la pantalla y confirmá el efectivo.']);
            }
            $inicial = $this->centavos($datos['efectivo_inicial']);
            $motivo = trim($datos['motivo_apertura'] ?? '');
            if ($propuesta['efectivo_heredado'] !== null && $inicial !== $this->centavos($propuesta['efectivo_heredado']) && $motivo === '') {
                throw ValidationException::withMessages(['motivo_apertura' => 'Indicá por qué recibiste un importe distinto del último cierre.']);
            }
            return CajaOperativa::create([
                'usuario_operativo_id' => $operativoId, 'apertura_at' => now(), 'estado' => 'ABIERTA',
                'tipo_caja_efectivo_id' => $configuracion->tipo_caja_id,
                'caja_origen_id' => $propuesta['caja_origen_id'],
                'efectivo_heredado' => $propuesta['efectivo_heredado'],
                'efectivo_inicial' => $this->importe($inicial), 'motivo_apertura' => $motivo ?: null,
                'usuario_apertura_id' => $actor->id,
            ]);
        });
    }

    /** El efectivo del dueño está aparte; no se suma cashflow ni transferencias. */
    public function arqueoCaja(int $cajaId): array
    {
        $caja = CajaOperativa::findOrFail($cajaId);
        if ($caja->efectivo_inicial === null || $caja->tipo_caja_efectivo_id === null) {
            return ['efectivo_esperado' => null, 'diferencia_efectivo' => null];
        }
        $esperado = $this->centavos($caja->efectivo_inicial);
        foreach ($caja->movimientos()->where('estado', 'ACTIVO')
            ->where('tipo_caja_id', $caja->tipo_caja_efectivo_id)->with('subrubro.rubro')->get() as $movimiento) {
            $monto = abs($this->centavos($movimiento->monto));
            $esperado += $movimiento->subrubro->rubro->tipo === 'EGRESO' ? -$monto : $monto;
        }
        return [
            'efectivo_esperado' => $this->importe($esperado),
            'diferencia_efectivo' => $caja->efectivo_contado === null
                ? null : $this->importe($this->centavos($caja->efectivo_contado) - $esperado),
        ];
    }

    private function centavos(string|float|int $importe): int
    {
        return (int) round((float) $importe * 100);
    }

    private function importe(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }

    /**
     * Obtener la caja ABIERTA del usuario o null
     *
     * @param int $usuarioOperativoId
     * @return CajaOperativa|null
     */
    public function obtenerCajaAbierta(int $usuarioOperativoId): ?CajaOperativa
    {
        return CajaOperativa::where('usuario_operativo_id', $usuarioOperativoId)
            ->where('estado', 'ABIERTA')
            ->first();
    }

    /**
     * Validar que no exista una caja vieja abierta (de día anterior)
     *
     * Si el usuario tiene una caja ABIERTA cuya apertura_at no es del día actual,
     * lanza excepción bloqueando la operación.
     *
     * @param int $usuarioOperativoId
     * @throws \Exception
     */
    public function validarCajaViejaAbierta(int $usuarioOperativoId): void
    {
        $cajaAbierta = $this->obtenerCajaAbierta($usuarioOperativoId);

        if ($cajaAbierta && !$cajaAbierta->apertura_at->isToday()) {
            throw new \Exception(
                'Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.'
            );
        }
    }

    /**
     * Obtener la caja ya declarada. El nombre se conserva para los callers históricos.
     *
     * No abre automáticamente: el efectivo debe confirmarse antes de cualquier movimiento.
     *
     * @param int $usuarioOperativoId
     * @return CajaOperativa
     * @throws \Exception
     */
    public function abrirCajaSiNoExiste(int $usuarioOperativoId): CajaOperativa
    {
        return DB::transaction(function () use ($usuarioOperativoId) {
            $this->bloquearMostrador();

            // Lectura actual: una validación simultánea puede haber cerrado la caja.
            $cajaAbierta = CajaOperativa::where('usuario_operativo_id', $usuarioOperativoId)
                ->where('estado', 'ABIERTA')->lockForUpdate()->first();

            if ($cajaAbierta && !$cajaAbierta->apertura_at->isToday()) {
                throw new \Exception('Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.');
            }

            if ($cajaAbierta && $cajaAbierta->efectivo_inicial !== null && $cajaAbierta->tipo_caja_efectivo_id !== null) {
                return $cajaAbierta;
            }

            throw ValidationException::withMessages(['caja' => 'Debés abrir la caja y confirmar el efectivo antes de operar.']);
        });
    }

    /**
     * Registrar un movimiento operativo (uso manual por operativo).
     *
     * Exige una caja abierta con efectivo declarado.
     * Valida que el subrubro permita OPERATIVO.
     * BLOQUEA subrubros reservados del sistema.
     *
     * @param array $data [usuario_operativo_id, tipo_caja_id, subrubro_id, monto, observaciones?, fecha?]
     * @return MovimientoOperativo
     * @throws \Exception
     */
    public function registrarMovimientoOperativo(array $data): MovimientoOperativo
    {
        $this->validarSubrubroManual($data['subrubro_id']);

        return $this->registrarMovimientoOperativoInterno($data);
    }

    /**
     * Registrar un movimiento operativo (uso interno del sistema).
     *
     * Permite todos los subrubros de tipo OPERATIVO, incluyendo reservados.
     * Solo debe ser llamado por otros services del sistema.
     *
     * @param array $data [usuario_operativo_id, tipo_caja_id, subrubro_id, monto, observaciones?, fecha?]
     * @return MovimientoOperativo
     * @throws \Exception
     */
    public function registrarMovimientoOperativoInterno(array $data): MovimientoOperativo
    {
        return DB::transaction(function () use ($data) {
            $usuarioOperativoId = $data['usuario_operativo_id'];

            // Validar subrubro permitido para OPERATIVO
            $subrubro = Subrubro::findOrFail($data['subrubro_id']);
            if ($subrubro->permitido_para !== 'OPERATIVO') {
                throw new \Exception(
                    'El subrubro seleccionado no está permitido para usuarios operativos.'
                );
            }

            // Mantener el bloqueo hasta registrar el movimiento: el cierre no se intercala.
            $caja = $this->abrirCajaSiNoExiste($usuarioOperativoId);

            // Crear movimiento
            return MovimientoOperativo::create([
                'caja_operativa_id' => $caja->id,
                'fecha'             => $data['fecha'] ?? Carbon::now()->toDateString(),
                'tipo_caja_id'      => $data['tipo_caja_id'],
                'subrubro_id'       => $data['subrubro_id'],
                'monto'             => $data['monto'],
                'observaciones'     => $data['observaciones'] ?? null,
                'usuario_id'        => $usuarioOperativoId,
                'alumno_id'         => $data['alumno_id'] ?? null,
                'pago_id'           => $data['pago_id'] ?? null,
                'estado'            => 'ACTIVO',
            ]);
        });
    }

    /**
     * Cerrar una caja operativa
     *
     * @param int $cajaId
     * @param int $usuarioId
     * @param bool $esAdmin Si es admin cerrando caja de otro usuario
     * @return CajaOperativa
     * @throws \Exception
     */
    public function cerrarCajaOperativa(int $cajaId, int $usuarioId, bool $esAdmin = false, array $arqueo = []): CajaOperativa
    {
        Validator::make($arqueo, [
            'efectivo_contado' => 'required|numeric|min:0|max:9999999999.99|decimal:0,2',
            'cambio_retenido' => 'required|numeric|min:0|lte:efectivo_contado|decimal:0,2',
        ])->validate();
        return DB::transaction(function () use ($cajaId, $usuarioId, $esAdmin, $arqueo) {
            $this->bloquearMostrador();
            $caja = CajaOperativa::whereKey($cajaId)->lockForUpdate()->firstOrFail();
            if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])
                && !($caja->estado === 'CERRADA' && $caja->efectivo_contado === null)) {
                throw new \Exception('La caja no está en estado editable.');
            }
            $actor = User::findOrFail($usuarioId);
            abort_unless($actor->activo, 403);
            if (($esAdmin && !$actor->isAdmin()) || (!$esAdmin && $caja->usuario_operativo_id !== $usuarioId)) {
                throw new \Exception('No tenés permiso para cerrar esta caja.');
            }
            $contado = $this->centavos($arqueo['efectivo_contado']);
            $retenido = $this->centavos($arqueo['cambio_retenido']);
            if ($caja->efectivo_contado !== null && ($contado !== $this->centavos($caja->efectivo_contado)
                || $retenido !== $this->centavos($caja->cambio_retenido))) {
                throw ValidationException::withMessages(['efectivo_contado' => 'La corrección no puede cambiar el efectivo que ya se contó y entregó.']);
            }
            $esperado = $this->arqueoCaja($cajaId)['efectivo_esperado'];
            if ($caja->efectivo_contado === null) {
                $caja->cierre_at = now();
                $caja->usuario_cierre_id = $usuarioId;
                $caja->cerrada_por_admin = $esAdmin;
                $caja->usuario_admin_cierre_id = $esAdmin ? $usuarioId : null;
            }
            $caja->fill([
                'estado' => 'CERRADA', 'efectivo_esperado' => $esperado,
                'efectivo_contado' => $this->importe($contado), 'cambio_retenido' => $this->importe($retenido),
                'efectivo_retirado' => $this->importe($contado - $retenido),
                'diferencia_efectivo' => $esperado === null ? null : $this->importe($contado - $this->centavos($esperado)),
            ])->save();
            return $caja;
        });
    }

    /**
     * Validar una caja (solo ADMIN)
     *
     * Exige un cierre con efectivo contado; nunca lo inventa ni cierra automáticamente.
     * Al validar, refleja los movimientos en el cashflow.
     * Todo dentro de una transacción para garantizar consistencia.
     *
     * @param int $cajaId
     * @param int $adminId
     * @return CajaOperativa
     * @throws \Exception
     */
    public function validarCaja(int $cajaId, int $adminId): CajaOperativa
    {
        return DB::transaction(function () use ($cajaId, $adminId) {
            $admin = User::findOrFail($adminId);
            abort_unless($admin->isAdmin() && $admin->activo, 403);
            $this->bloquearMostrador();
            $caja = CajaOperativa::whereKey($cajaId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($caja->estado === 'ABIERTA') {
                throw new \Exception('Contá el efectivo y cerrá la caja antes de validarla.');
            }

            // Si ya está VALIDADA, es idempotente (el cashflow service también lo es)
            if ($caja->estado === 'VALIDADA') {
                // Asegurar que el cashflow esté reflejado (idempotente)
                $this->getCashflowIntegracion()->reflejarCajaEnCashflow($cajaId, $adminId);
                return $caja;
            }

            if ($caja->estado !== 'CERRADA') {
                throw new \Exception('Solo se pueden validar cajas cerradas.');
            }
            if ($caja->efectivo_contado === null) {
                throw new \Exception('Falta declarar el efectivo contado y el cambio retenido antes de validar.');
            }

            // Marcar como validada
            $caja->estado = 'VALIDADA';
            $caja->usuario_admin_validacion_id = $adminId;
            $caja->validada_at = Carbon::now();
            $caja->save();

            // Reflejar movimientos en cashflow
            $this->getCashflowIntegracion()->reflejarCajaEnCashflow($cajaId, $adminId);

            return $caja;
        });
    }

    /**
     * Rechazar una caja (solo ADMIN)
     *
     * @param int $cajaId
     * @param int $adminId
     * @param string $motivo
     * @return CajaOperativa
     * @throws \Exception
     */
    public function rechazarCaja(int $cajaId, int $adminId, string $motivo): CajaOperativa
    {
        return DB::transaction(function () use ($cajaId, $adminId, $motivo) {
            $admin = User::findOrFail($adminId);
            abort_unless($admin->isAdmin() && $admin->activo, 403);
            $this->bloquearMostrador();
            $caja = CajaOperativa::whereKey($cajaId)->lockForUpdate()->firstOrFail();
            if ($caja->estado !== 'CERRADA' || $caja->efectivo_contado === null) {
                throw new \Exception('Contá y cerrá la caja antes de rechazarla.');
            }
            $caja->update([
                'estado' => 'RECHAZADA', 'motivo_rechazo' => $motivo,
                'usuario_admin_validacion_id' => $adminId,
            ]);
            return $caja;
        });
    }

    /**
     * Registrar un movimiento en una caja ya abierta (sin abrir automáticamente).
     *
     * Solo subrubros OPERATIVO no reservados. Uso manual del operativo desde la vista.
     *
     * @param int $cajaId
     * @param array $data [tipo_caja_id, subrubro_id, monto, observaciones?, fecha?, alumno_id?]
     * @return MovimientoOperativo
     * @throws \Exception
     */
    public function registrarMovimientoEnCaja(int $cajaId, array $data): MovimientoOperativo
    {
        return DB::transaction(function () use ($cajaId, $data) {
            $caja = CajaOperativa::whereKey($cajaId)->lockForUpdate()->firstOrFail();

            if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])) {
                throw new \Exception('La caja no está en estado editable.');
            }

            if ($caja->estado === 'ABIERTA' && ($caja->efectivo_inicial === null || $caja->tipo_caja_efectivo_id === null)) {
                throw ValidationException::withMessages(['caja' => 'Debés cerrar la caja anterior sin declaración antes de operar.']);
            }

            $this->validarSubrubroManual($data['subrubro_id']);

            return MovimientoOperativo::create([
                'caja_operativa_id' => $cajaId,
                'fecha'             => $data['fecha'] ?? Carbon::now()->toDateString(),
                'tipo_caja_id'      => $data['tipo_caja_id'],
                'subrubro_id'       => $data['subrubro_id'],
                'monto'             => $data['monto'],
                'observaciones'     => $data['observaciones'] ?? null,
                'usuario_id'        => $caja->usuario_operativo_id,
                'alumno_id'         => $data['alumno_id'] ?? null,
            ]);
        });
    }

    /**
     * Actualizar un movimiento manual dentro de una caja editable.
     */
    public function actualizarMovimientoEnCaja(int $cajaId, int $movimientoId, array $data): MovimientoOperativo
    {
        return DB::transaction(function () use ($cajaId, $movimientoId, $data) {
            $caja = CajaOperativa::whereKey($cajaId)->lockForUpdate()->firstOrFail();

            if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])) {
                throw new \Exception('La caja no está en estado editable.');
            }

            if ($caja->estado === 'ABIERTA' && ($caja->efectivo_inicial === null || $caja->tipo_caja_efectivo_id === null)) {
                throw ValidationException::withMessages(['caja' => 'Debés cerrar la caja anterior sin declaración antes de operar.']);
            }

            $movimiento = MovimientoOperativo::where('caja_operativa_id', $cajaId)
                ->lockForUpdate()->findOrFail($movimientoId);

            if ($movimiento->subrubro?->es_reservado_sistema) {
                throw new \Exception('No se puede editar un movimiento generado automáticamente por el sistema.');
            }

            $this->validarSubrubroManual($data['subrubro_id']);

            $movimiento->update([
                'tipo_caja_id' => $data['tipo_caja_id'],
                'subrubro_id' => $data['subrubro_id'],
                'monto' => $data['monto'],
                'fecha' => $data['fecha'],
                'observaciones' => $data['observaciones'],
            ]);

            return $movimiento;
        });
    }

    private function validarSubrubroManual(int $subrubroId): Subrubro
    {
        $subrubro = Subrubro::findOrFail($subrubroId);

        if ($subrubro->es_reservado_sistema) {
            throw new \Exception(
                "El subrubro '{$subrubro->nombre}' es reservado del sistema y no puede usarse manualmente."
            );
        }

        if ($subrubro->permitido_para !== 'OPERATIVO') {
            throw new \Exception('El subrubro seleccionado no está permitido para usuarios operativos.');
        }

        if (!$subrubro->afecta_caja) {
            throw new \Exception('El subrubro seleccionado no afecta la caja operativa.');
        }

        return $subrubro;
    }

    /**
     * Obtener cajas pendientes de validación (CERRADAS)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerCajasPendientes()
    {
        return CajaOperativa::where('estado', 'CERRADA')
            ->with(['usuarioOperativo', 'movimientos'])
            ->orderBy('cierre_at', 'asc')
            ->get();
    }
}
