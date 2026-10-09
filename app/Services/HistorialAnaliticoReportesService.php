<?php

namespace App\Services;

use App\Models\{DeudaCuota, Liquidacion, Pago, Profesor};
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Estados conocidos desde la activación; nunca reconstruye un cierre previo. */
class HistorialAnaliticoReportesService
{
    public const TABLA = 'reporte_analitico_historial';
    public const COBERTURA = 'reporte_analitico_cobertura';

    public function disponible(?string $conexion = null): bool
    {
        $schema = DB::connection($conexion)->getSchemaBuilder();
        return $schema->hasTable(self::TABLA) && $schema->hasTable(self::COBERTURA);
    }

    /** La migración captura datos existentes con su fecha de conocimiento actual. */
    public function iniciar(): void
    {
        $this->exigirMantenimiento();
        DB::transaction(function () {
            DB::table(self::COBERTURA)->insert(['id'=>1, 'desde'=>now()->format('Y-m-d H:i:s.u')]);
            foreach ([DeudaCuota::class, Profesor::class, Pago::class, Liquidacion::class] as $modelo) {
                $modelo::orderBy('id')->chunkById(200, function ($filas) {
                    foreach ($filas as $fila) $this->registrar($fila);
                });
            }
            // La cobertura empieza cuando terminó la captura inicial completa.
            DB::table(self::COBERTURA)->where('id', 1)->update(['desde'=>now()->format('Y-m-d H:i:s.u')]);
        });
    }

    /** La captura inicial necesita la aplicación sin altas/cobros concurrentes. */
    public function exigirMantenimiento(): void
    {
        $base = DB::connection()->getDatabaseName();
        $descartable = in_array($base, ['wings_testing','wings_testing_codex','wings_testing_claude','wings_testing_gemini'], true);
        if (!$descartable && !app()->isDownForMaintenance()) {
            throw new \RuntimeException('El historial analítico debe activarse con Wings en mantenimiento.');
        }
    }

    public function registrar(Model $modelo, bool $eliminado = false): void
    {
        $conexion = $modelo->getConnectionName();
        if (!$this->disponible($conexion)) return;
        DB::connection($conexion)->transaction(fn () => $this->guardar($modelo, $eliminado));
    }

    private function guardar(Model $modelo, bool $eliminado): void
    {
        $conexion = $modelo->getConnectionName();
        $db = DB::connection($conexion);
        $desde = $db->table(self::COBERTURA)->where('id', 1)->value('desde');
        if (!$desde) return;
        if (!$eliminado) {
            // Una ficha cargada antes de otra escritura puede tener atributos viejos.
            $modelo = $modelo->newQuery()->whereKey($modelo->getKey())->lockForUpdate()->firstOrFail();
        }
        $tipo = match (true) {
            $modelo instanceof DeudaCuota => 'CUOTA',
            $modelo instanceof Profesor => 'TARIFA',
            $modelo instanceof Pago => 'PAGO',
            $modelo instanceof Liquidacion => 'LIQUIDACION',
            default => throw new \InvalidArgumentException('Modelo sin historial analítico.'),
        };
        $ultimo = $db->table(self::TABLA)->where('tipo', $tipo)->where('origen_id', $modelo->id)->orderByDesc('id')->first();
        $datos = $this->datos($modelo);
        $persona = $modelo instanceof Profesor ? $modelo->id : ($modelo instanceof Liquidacion ? $modelo->profesor_id : $modelo->alumno_id);
        // El cambio de ficha de la persona no reasigna cuotas/cobros anteriores.
        $deporte = $ultimo ? $ultimo->deporte_id : ($modelo instanceof Profesor ? $modelo->deporte_id
            : $db->table($modelo instanceof Liquidacion ? 'profesores' : 'alumnos')->where('id', $persona)->value('deporte_id'));
        if ($modelo instanceof Profesor) $deporte = $modelo->deporte_id;
        $periodo = $modelo instanceof Profesor ? null : ($modelo instanceof DeudaCuota ? $modelo->periodo : sprintf('%04d-%02d', $modelo->anio, $modelo->mes));
        ksort($datos);
        $anteriores = $ultimo ? json_decode($ultimo->datos, true, 512, JSON_THROW_ON_ERROR) : null;
        if ($anteriores !== null) ksort($anteriores);
        $json = json_encode($datos, JSON_THROW_ON_ERROR);
        if ($ultimo && (bool) $ultimo->vigente === !$eliminado
            && $anteriores === $datos
            && $ultimo->persona_id == $persona && $ultimo->deporte_id == $deporte && $ultimo->periodo === $periodo) return;
        // Un reloj retrocedido no puede insertar estados conocidos después en un cierre anterior.
        $observado = max(now()->format('Y-m-d H:i:s.u'), (string) $desde, (string) ($ultimo?->observado_en ?? ''));
        $db->table(self::TABLA)->insert([
            'tipo'=>$tipo, 'origen_id'=>$modelo->id, 'persona_id'=>$persona,
            'deporte_id'=>$deporte, 'periodo'=>$periodo, 'datos'=>$json,
            'vigente'=>!$eliminado, 'observado_en'=>$observado,
        ]);
    }

    /** Datos de negocio mínimos: sin nombres, DNI ni información de contacto. */
    private function datos(Model $modelo): array
    {
        $centavos = fn ($m) => (int) round((float) $m * 100);
        if ($modelo instanceof DeudaCuota) {
            $original = max(0, $centavos($modelo->monto_original));
            $neto = $modelo->estado === DeudaCuota::ESTADO_CONDONADA
                ? min($original, max(0, $centavos($modelo->monto_pagado)))
                : max(0, $original - $centavos($modelo->monto_condonado));
            return ['neto_centavos'=>$neto];
        }
        if ($modelo instanceof Profesor) {
            $tipo = DB::connection($modelo->getConnectionName())->table('deportes')->where('id', $modelo->deporte_id)->value('tipo_liquidacion');
            return ['tipo'=>$tipo, 'hora_centavos'=>$modelo->valor_hora === null ? null : $centavos($modelo->valor_hora),
                'comision_centesimas'=>$modelo->porcentaje_comision === null ? null : $centavos($modelo->porcentaje_comision)];
        }
        if ($modelo instanceof Pago) {
            return ['cuota_centavos'=>max(0, $centavos($modelo->monto_cuota)), 'estado'=>$modelo->estado,
                'fecha_pago'=>$modelo->fecha_pago?->toDateString()];
        }
        return ['tipo'=>$modelo->tipo, 'estado'=>$modelo->estado, 'estado_pago'=>$modelo->estado_pago,
            'calculado_centavos'=>$centavos($modelo->total_calculado),
            'final_centavos'=>$centavos($modelo->monto_a_pagar),
            'hora_centavos'=>$modelo->valor_hora_aplicado === null ? null : $centavos($modelo->valor_hora_aplicado),
            'comision_centesimas'=>$modelo->porcentaje_comision_aplicado === null ? null : $centavos($modelo->porcentaje_comision_aplicado)];
    }

    /** Último estado por objeto al corte; las bajas se filtran después de elegirlo. */
    public function obtener(string $tipo, CarbonImmutable $corte, ?int $origenId = null): array
    {
        if (!in_array($tipo, ['CUOTA','TARIFA','PAGO','LIQUIDACION'], true)) throw new \InvalidArgumentException('Tipo analítico inválido.');
        if (!$this->disponible()) return ['disponible'=>false, 'desde'=>null, 'filas'=>null];
        $desde = DB::table(self::COBERTURA)->where('id', 1)->value('desde');
        if (!$desde || $corte->lessThan(CarbonImmutable::parse($desde))) return ['disponible'=>false, 'desde'=>$desde, 'filas'=>null];
        $ids = DB::table(self::TABLA)->selectRaw('MAX(id)')->where('tipo', $tipo)
            ->where('observado_en', '<=', $corte->format('Y-m-d H:i:s.u'))
            ->when($origenId !== null, fn ($q) => $q->where('origen_id', $origenId))->groupBy('origen_id');
        $filas = DB::table(self::TABLA)->whereIn('id', $ids)->where('vigente', true)->orderBy('origen_id')->get()
            ->map(function ($f) { $f->datos = json_decode($f->datos, true, 512, JSON_THROW_ON_ERROR); return (array) $f; })->all();
        return ['disponible'=>true, 'desde'=>$desde, 'filas'=>$filas];
    }
}
