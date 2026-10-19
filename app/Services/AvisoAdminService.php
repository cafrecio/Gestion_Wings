<?php

namespace App\Services;

use App\Models\AlumnoRevisionCobranza;
use App\Models\CajaOperativa;
use App\Models\Clase;
use App\Models\Configuracion;
use App\Models\GeneracionCuotas;
use App\Models\Liquidacion;
use App\Models\MovimientoOperativo;
use App\Models\User;
use App\Notifications\AvisoOperativo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Único lugar desde donde el sistema avisa al ADMIN de algo operativo.
 *
 * Existe para que quien deba avisar no tenga que saber por qué canal sale ni a
 * quién: llama a un método con nombre de lo que pasó y sigue con lo suyo.
 *
 * **Un aviso nunca puede voltear la operación.** Todos los métodos de acá se
 * llaman después de que la plata ya quedó registrada. Si el aviso falla, se
 * anota en el log y nada más: el club tiene que poder seguir cobrando aunque
 * Telegram esté caído.
 */
class AvisoAdminService
{
    /**
     * Un movimiento o cobro cargado con fecha de un mes ya cerrado (FIN-09).
     *
     * El caso real: un cobro por Mercado Pago del 31/08 que recién se descubre
     * en septiembre. El movimiento conserva su fecha real pero entra en la caja
     * de hoy, así que el resultado del mes viejo cambia después de haberse
     * mirado. Eso es exactamente lo que el ADMIN tiene que saber.
     */
    public function fechaVieja(
        string $que,
        string $fechaDelMovimiento,
        string $monto,
        ?string $quienLoCargo = null,
        ?string $dondeEntra = null,
    ): void {
        $datos = [
            'Qué se cargó' => $que,
            'Fecha puesta' => $fechaDelMovimiento,
            'Importe'      => $monto,
        ];

        if ($quienLoCargo) {
            $datos['Lo cargó'] = $quienLoCargo;
        }
        if ($dondeEntra) {
            $datos['Entra en'] = $dondeEntra;
        }

        $this->enviar(
            'se cargó algo con fecha de un mes ya cerrado',
            $datos,
            'El movimiento queda con su fecha real, así que el resultado de ese mes cambia.',
        );
    }

    /**
     * Las cuotas del mes se generaron después del día 1 (T40, DU-52).
     *
     * Que se hayan generado solas no alcanza: el dueño tiene que saber que el
     * proceso del día 1 no corrió, porque algo pasó con el servidor ese día.
     */
    public function cuotasGeneradasConAtraso(GeneracionCuotas $generacion, Carbon $hoy): void
    {
        $this->enviar(
            'las cuotas del mes se generaron con atraso',
            [
                'Mes'                 => Carbon::parse($generacion->periodo.'-01')->format('m/Y'),
                'Generadas el'        => $hoy->format('d/m/Y'),
                'Cuotas creadas'      => (string) $generacion->creadas,
                'Enviados a revisión' => (string) $generacion->a_revision,
            ],
            'El proceso del día 1 no corrió; se recuperó solo. Conviene revisar qué pasó con el servidor ese día.',
        );
    }

    /**
     * El intento de generar las cuotas del mes falló (T40, DU-52).
     */
    public function cuotasNoGeneradas(Carbon $hoy): void
    {
        $this->enviar(
            'no se pudieron generar las cuotas del mes',
            [
                'Mes'     => $hoy->format('m/Y'),
                'Intento' => $hoy->format('d/m/Y H:i'),
            ],
            'Se vuelve a intentar solo mañana a las 6. Lo que ya se generó no se duplica. Si se repite, avisá al técnico.',
        );
    }

    /**
     * Resumen diario de pendientes operativos para el ADMIN (ENT-06).
     *
     * Informa:
     * 1. Cajas cerradas sin validar (cantidad, monto total neto y la más vieja).
     * 2. Revisiones de cobranza pendientes (cantidad y la más vieja).
     * 3. Liquidaciones cerradas sin pagar (cantidad y total a pagar) y abiertas (aparte, no se suman).
     *
     * Si no hay nada pendiente en ninguna de las tres áreas, no envía nada y retorna false.
     * Si hay al menos un pendiente, envía el aviso por correo y Telegram y retorna true.
     */
    public function resumenDiario(): bool
    {
        $cajasCerradas = CajaOperativa::query()
            ->where('estado', CajaOperativa::ESTADO_CERRADA)
            ->with(['usuarioOperativo', 'movimientos.subrubro.rubro'])
            ->orderBy('apertura_at', 'asc')
            ->get();

        $revisiones = AlumnoRevisionCobranza::query()
            ->where('estado_revision', AlumnoRevisionCobranza::ESTADO_PENDIENTE)
            ->with('alumno')
            ->orderBy('created_at', 'asc')
            ->get();

        $liqCerradasSinPagar = Liquidacion::query()
            ->where('estado', Liquidacion::ESTADO_CERRADA)
            ->where('estado_pago', Liquidacion::ESTADO_PAGO_PENDIENTE)
            ->get();

        // Clases que ya pasaron y nadie tomó lista. Traban el pago del profesor:
        // sin asistencia no se liquidan, y hasta hoy esto solo se veía como un
        // número en el menú de quien entraba. Misma consulta que ese contador
        // (AppServiceProvider), para que los dos digan siempre lo mismo.
        $clasesSinAsistencia = Clase::query()
            ->where('cancelada', false)
            ->whereDate('fecha', '<', today())
            ->where('validada_para_liquidacion', false)
            ->whereDoesntHave('asistencias', fn ($q) => $q->where('presente', true))
            ->with(['grupo.deporte', 'grupo.nivel'])
            ->orderBy('fecha', 'asc')
            ->get();

        $cantLiqAbiertas = Liquidacion::query()
            ->where('estado', Liquidacion::ESTADO_ABIERTA)
            ->count();

        // T40: si las cuotas del mes siguen sin generarse, que el resumen lo diga
        // todos los días hasta que se generen.
        $hoy = Carbon::now();
        $cuotasSinGenerar = GeneracionCuotas::faltaGenerar($hoy);

        $cantClasesSinAsistencia = $clasesSinAsistencia->count();
        $cantCajas = $cajasCerradas->count();
        $cantRevisiones = $revisiones->count();
        $cantLiqCerradas = $liqCerradasSinPagar->count();

        if ($cantCajas === 0 && $cantRevisiones === 0 && $cantLiqCerradas === 0 && $cantLiqAbiertas === 0
            && $cantClasesSinAsistencia === 0 && !$cuotasSinGenerar) {
            return false;
        }

        $formatoMonto = fn (float|int $monto): string => \App\Support\FormatoImporte::pesos($monto);

        $datos = [];

        if ($cuotasSinGenerar) {
            $datos['Cuotas del mes sin generar'] = sprintf(
                '%s — se reintenta solo cada día a las 6; hasta entonces Cobranza no muestra la deuda del mes',
                $hoy->format('m/Y'),
            );
        }

        if ($cantCajas > 0) {
            $montoTotalCajas = 0.0;
            foreach ($cajasCerradas as $caja) {
                foreach ($caja->movimientos as $mov) {
                    if ($mov->estado !== MovimientoOperativo::ESTADO_ACTIVO) {
                        continue;
                    }
                    $tipo = $mov->subrubro?->rubro?->tipo;
                    if ($tipo === 'INGRESO') {
                        $montoTotalCajas += (float) $mov->monto;
                    } elseif ($tipo === 'EGRESO') {
                        $montoTotalCajas -= (float) $mov->monto;
                    }
                }
            }

            $cajaVieja = $cajasCerradas->first();
            $quien = $cajaVieja->usuarioOperativo?->name ?? 'Sin asignar';
            $fecha = $cajaVieja->apertura_at?->format('d/m/Y') ?? 'Sin fecha';

            $datos['Cajas cerradas sin validar'] = sprintf(
                '%d (%s) — más vieja: %s (%s) — %s',
                $cantCajas,
                $formatoMonto($montoTotalCajas),
                $quien,
                $fecha,
                route('web.caja.index')
            );
        }

        if ($cantClasesSinAsistencia > 0) {
            $claseVieja = $clasesSinAsistencia->first();
            $queGrupo = $claseVieja->grupo?->deporte?->nombre
                ? $claseVieja->grupo->deporte->nombre . ' — ' . ($claseVieja->grupo->nivel?->nombre ?? $claseVieja->grupo->nombre ?? '')
                : 'Sin grupo';

            $datos['Clases sin lista tomada'] = sprintf(
                '%d — la más vieja: %s del %s — hasta que se carguen no se le puede pagar al profesor — %s',
                $cantClasesSinAsistencia,
                trim($queGrupo, ' —'),
                $claseVieja->fecha?->format('d/m/Y') ?? 'sin fecha',
                route('web.clases.index')
            );
        }

        if ($cantRevisiones > 0) {
            $revVieja = $revisiones->first();
            $quien = trim(($revVieja->alumno?->apellido ?? '') . ', ' . ($revVieja->alumno?->nombre ?? ''));
            if ($quien === '' || $quien === ',') {
                $quien = 'Sin alumno';
            }
            $fecha = $revVieja->created_at?->format('d/m/Y') ?? 'Sin fecha';

            $datos['Revisiones de cobranza pendientes'] = sprintf(
                '%d — más vieja: %s (%s) — %s',
                $cantRevisiones,
                $quien,
                $fecha,
                route('web.revision-cobranza.index')
            );
        }

        if ($cantLiqCerradas > 0 || $cantLiqAbiertas > 0) {
            $totalLiqCerradas = (float) $liqCerradasSinPagar->sum('monto_a_pagar');

            if ($cantLiqCerradas > 0) {
                $datos['Liquidaciones cerradas sin pagar'] = sprintf(
                    '%d (%s) — %s',
                    $cantLiqCerradas,
                    $formatoMonto($totalLiqCerradas),
                    route('web.liquidaciones.index')
                );
            }

            if ($cantLiqAbiertas > 0) {
                $datos['Liquidaciones abiertas'] = sprintf(
                    '%d — %s',
                    $cantLiqAbiertas,
                    route('web.liquidaciones.index')
                );
            }
        }

        $this->enviar(
            'resumen diario de pendientes',
            $datos,
            'Revisá cada sección ingresando al enlace correspondiente.'
        );

        return true;
    }

    /**
     * @param array<string,string> $datos
     */
    private function enviar(string $titulo, array $datos, ?string $detalle = null): void
    {
        try {
            $aviso = new AvisoOperativo($titulo, $datos, $detalle);

            // Telegram va una sola vez: el chat es uno solo para todo el club, y
            // mandarlo por cada administrador llenaría el canal de mensajes
            // repetidos.
            Notification::route('telegram', true)->notify($aviso);

            // El correo: si el club configuró una casilla propia para avisos, va
            // ahí y solo ahí. Si no, a cada ADMIN activo, que es el comportamiento
            // razonable mientras nadie eligió un destino.
            //
            // Hoy `mail.default` es `log`, así que termina en el archivo de log en
            // vez de en una casilla; cuando se configure un SMTP empieza a llegar
            // sin tocar nada de acá.
            $casillaDelClub = trim((string) Configuracion::get('avisos_email', ''));

            if ($casillaDelClub !== '') {
                Notification::route('mail', $casillaDelClub)->notify($aviso);
                return;
            }

            $admins = User::query()
                ->where('rol', User::ROL_ADMIN)
                ->where('activo', true)
                ->whereNotNull('email')
                ->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, $aviso);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar al administrador.', [
                'titulo' => $titulo,
                'motivo' => $e->getMessage(),
            ]);
        }
    }
}
