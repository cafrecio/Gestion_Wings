<?php

namespace App\Support;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

/**
 * T47 (DU-02, ACC-014): el texto que ve la persona cuando una acción de pantalla falla.
 *
 * Las pantallas atrapan la excepción y muestran su texto. Las reglas del negocio ya
 * hablan en castellano, pero un registro que no existe decía «No query results for
 * model [App\Models\CargoAlumno]» y un fallo de base mostraba la consulta SQL.
 * Esos casos se traducen; los fallos técnicos quedan además en el log.
 */
final class AvisoError
{
    public const REGISTRO_INEXISTENTE = 'Ese registro no existe o ya no está. Volvé a la pantalla y probá de nuevo.';
    public const FALLO_TECNICO = 'No se pudo completar. Probá de nuevo y, si se repite, avisá al dueño.';

    public static function texto(\Throwable $e): string
    {
        if ($e instanceof ModelNotFoundException) {
            return self::REGISTRO_INEXISTENTE;
        }

        if ($e instanceof QueryException || $e instanceof \PDOException || $e instanceof \ErrorException) {
            report($e);

            return self::FALLO_TECNICO;
        }

        return $e->getMessage();
    }
}
