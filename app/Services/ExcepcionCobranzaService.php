<?php

namespace App\Services;

use App\Exceptions\ExcepcionCobranzaRequerida;
use App\Models\{DeudaCuota, ExcepcionCobranza};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExcepcionCobranzaService
{
    /** Se llama bajo el bloqueo del alumno y antes de aplicar el pago. */
    public function validarCobro(int $alumnoId, array $items, array $precios, array $punitorios, ?string $motivo): array
    {
        $aplicados = collect($items)->keyBy('periodo');
        if ($aplicados->isEmpty()) return [];
        $deudas = DeudaCuota::where('alumno_id', $alumnoId)->orderBy('periodo')->lockForUpdate()->get()->keyBy('periodo');
        // La excepción no autoriza pagar una deuda condonada. El servicio de pago
        // mantiene ese rechazo y su mensaje antes de pedir un motivo innecesario.
        if ($deudas->whereIn('periodo', $aplicados->keys())->contains('estado', DeudaCuota::ESTADO_CONDONADA)) return [];
        $tipos = [];
        foreach ($aplicados as $periodo => $item) {
            $deuda = $deudas->get($periodo);
            $saldo = $deuda ? (float) $deuda->saldo_pendiente : (float) ($precios[$periodo]
                ?? app(PagoCuotaService::class)->obtenerPlanParaPeriodo($alumnoId, $periodo)?->plan?->precioParaPeriodo($periodo, \App\Models\Alumno::findOrFail($alumnoId)->fecha_alta));
            if ((int) round($item['monto'] * 100) < (int) round($saldo * 100)) $tipos['COBRO_PARCIAL'] = true;
        }
        $ultimo = $aplicados->keys()->sort()->last();
        $recargos = app(CobranzaEstadoService::class)->recargosPendientesPorAlumno(collect([$alumnoId]))->get($alumnoId, collect())->keyBy('deuda_cuota_id');
        $pagosRecargos = collect($punitorios)->mapWithKeys(fn ($p) => [$p['cargo']->deuda_cuota_id => $p['monto']]);
        $anteriores = [];
        foreach ($deudas as $periodo => $deuda) {
            if ($periodo >= $ultimo) continue;
            $capital = $deuda->estado === DeudaCuota::ESTADO_CONDONADA ? 0 : (float) $deuda->saldo_pendiente;
            $saldo = max(0, $capital - (float) ($aplicados->get($periodo)['monto'] ?? 0))
                + max(0, (float) ($recargos->get($deuda->id)?->saldo_para_cobranza ?? 0) - $pagosRecargos->get($deuda->id, 0));
            if (round($saldo, 2) > 0) $anteriores[$periodo] = (int) round($saldo * 100);
        }
        if ($anteriores) $tipos['COBRO_DEUDA_ANTERIOR'] = true;
        if (!$tipos) return [];

        $mensajes = [];
        if (isset($tipos['COBRO_PARCIAL'])) $mensajes[] = 'Es un cobro parcial: queda parte de la cuota pendiente.';
        if ($anteriores) {
            $nombres = collect(array_keys($anteriores))->map(fn ($p) => Carbon::parse($p.'-01')->locale('es')->translatedFormat('F Y'))->implode(', ');
            $mensajes[] = 'Quedan '.count($anteriores).' meses anteriores impagos por '.\App\Support\FormatoImporte::pesos(array_sum($anteriores) / 100).' ('.$nombres.'). ¿Cobrás igual solo los meses elegidos?';
        }
        $detalle = ['tipos' => array_keys($tipos), 'cantidad_meses' => count($anteriores), 'periodos' => array_keys($anteriores),
            'monto_total' => array_sum($anteriores) / 100, 'cuotas_cobradas' => $aplicados->keys()->all(),
            'message' => implode(' ', $mensajes).' Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.'];
        if (trim((string) $motivo) === '') throw new ExcepcionCobranzaRequerida($detalle);
        $this->exigirMotivo($motivo);
        return $detalle;
    }

    public function guardar(array $detalle, string $motivo, int $autorId, int $alumnoId, string $registroTipo, int $registroId): void
    {
        if (!$detalle) return;
        if (DB::transactionLevel() === 0) throw new \LogicException('La excepción se guarda dentro de la acción.');
        $this->exigirMotivo($motivo);
        foreach ($detalle['tipos'] as $tipo) {
            ExcepcionCobranza::firstOrCreate(['tipo' => $tipo, 'registro_tipo' => $registroTipo, 'registro_id' => $registroId],
                ['alumno_id' => $alumnoId, 'autor_id' => $autorId, 'motivo' => trim($motivo), 'ocurrida_at' => now(), 'contexto' => $detalle]);
        }
    }

    private function exigirMotivo(string $motivo): void
    {
        if (trim($motivo) === '' || mb_strlen($motivo) > 500) {
            throw ValidationException::withMessages(['motivo' => 'Indicá un motivo de hasta 500 caracteres.']);
        }
    }
}
