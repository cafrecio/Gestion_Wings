<?php

namespace App\Support;

class ReglasPrimerPagoValidator
{
    /**
     * Valida que el conjunto de reglas cubra exactamente los días del 1 al 31
     * sin huecos y sin superposiciones.
     *
     * @param array<int, array{id?: int|null, nombre: string, dia_desde: int, dia_hasta: int, porcentaje: float|int}> $reglas
     * @return string|null Mensaje de error en castellano, o null si el conjunto es válido.
     */
    public static function validar(array $reglas): ?string
    {
        if (empty($reglas)) {
            return 'Debe existir al menos una regla que cubra del día 1 al 31.';
        }

        // Ordenar reglas por dia_desde ascendente.
        // Si empatan en dia_desde, ordenar por dia_hasta ascendente.
        usort($reglas, function ($a, $b) {
            $desdeDiff = ((int) $a['dia_desde']) <=> ((int) $b['dia_desde']);
            if ($desdeDiff !== 0) {
                return $desdeDiff;
            }
            return ((int) $a['dia_hasta']) <=> ((int) $b['dia_hasta']);
        });

        // 1. Validar que la primera regla comience en el día 1
        $primera = $reglas[0];
        $primerDesde = (int) $primera['dia_desde'];
        if ($primerDesde > 1) {
            $faltanHasta = $primerDesde - 1;
            return $faltanHasta === 1
                ? 'El día 1 queda sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.'
                : sprintf('Los días 1 a %d quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.', $faltanHasta);
        }

        // 2. Validar superposiciones y huecos entre reglas consecutivas
        $count = count($reglas);
        for ($i = 0; $i < $count - 1; $i++) {
            $actual = $reglas[$i];
            $siguiente = $reglas[$i + 1];

            $actualDesde = (int) $actual['dia_desde'];
            $actualHasta = (int) $actual['dia_hasta'];
            $siguienteDesde = (int) $siguiente['dia_desde'];
            $siguienteHasta = (int) $siguiente['dia_hasta'];

            // Superposición: la siguiente regla empieza antes o el mismo día que termina la actual
            if ($siguienteDesde <= $actualHasta) {
                $finChoque = min($actualHasta, $siguienteHasta);
                $diasTexto = $siguienteDesde === $finChoque
                    ? sprintf('El día %d se pisa', $siguienteDesde)
                    : sprintf('Los días %d a %d se pisan', $siguienteDesde, $finChoque);

                return sprintf(
                    '%s con la regla "%s", que va del %d al %d. Un mismo día no puede tener dos porcentajes.',
                    $diasTexto,
                    $actual['nombre'],
                    $actualDesde,
                    $actualHasta
                );
            }

            // Hueco: la siguiente regla empieza después de actualHasta + 1
            if ($siguienteDesde > $actualHasta + 1) {
                $huecoDesde = $actualHasta + 1;
                $huecoHasta = $siguienteDesde - 1;

                return $huecoDesde === $huecoHasta
                    ? sprintf('El día %d queda sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.', $huecoDesde)
                    : sprintf('Los días %d a %d quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.', $huecoDesde, $huecoHasta);
            }
        }

        // 3. Validar que la última regla termine en el día 31
        $ultima = $reglas[$count - 1];
        $ultimoHasta = (int) $ultima['dia_hasta'];
        if ($ultimoHasta < 31) {
            $faltanDesde = $ultimoHasta + 1;
            return $faltanDesde === 31
                ? 'El día 31 queda sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.'
                : sprintf('Los días %d a 31 quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.', $faltanDesde);
        }

        return null;
    }
}
