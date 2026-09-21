<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\User;
use App\Notifications\AvisoOperativo;
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
