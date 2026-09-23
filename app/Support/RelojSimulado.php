<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Le dice a Wings qué día es, para poder vivir tres meses en unas horas.
 *
 * Existe por PRU-02: la prueba grande tiene que recorrer varios meses de club
 * —generación de cuotas, mora, liquidaciones, avisos— y el servidor de prueba
 * comparte máquina con producción, así que cambiarle la fecha al sistema
 * operativo no es una opción. Wings lee la fecha en un solo lugar (`Carbon::now()`),
 * así que alcanza con moverla ahí.
 *
 * **En producción no se aplica nunca.** No es una restricción de configuración: si
 * el entorno es `production` el valor se ignora aunque esté escrito, se deja
 * registrado en el log y la aplicación sigue con la fecha real. Lo cubre
 * `RelojSimuladoTest`.
 */
class RelojSimulado
{
    /**
     * @param  string|null  $valor  fecha como `2026-10-15` o `2026-10-15 09:30:00`
     * @return string|null          la fecha aplicada, o null si no se aplicó ninguna
     */
    public static function aplicar(?string $valor, string $entorno): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        if ($entorno === 'production') {
            Log::warning('Se ignoró FECHA_SIMULADA: en producción Wings usa siempre la fecha real.', [
                'valor' => $valor,
            ]);

            return null;
        }

        try {
            $fecha = Carbon::parse(trim($valor));
        } catch (\Throwable $e) {
            Log::warning('FECHA_SIMULADA no es una fecha válida; se usa la fecha real.', [
                'valor'  => $valor,
                'motivo' => $e->getMessage(),
            ]);

            return null;
        }

        Carbon::setTestNow($fecha);

        return $fecha->toDateTimeString();
    }
}
