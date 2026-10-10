<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Qué es cada subrubro para Inicio y Reportes: plata del club, o plata de los dueños.
 *
 * T16 (10/10/2026): la columna se agregó el 09/10 y solo se completó para los sueldos.
 * En una instalación que ya existía, «Cuota Mensual» quedó sin clasificar y todo lo
 * cobrado aparecía como «por clasificar»: Inicio mostraba ingresos $0 con la caja llena.
 */
class ClasificacionSubrubros
{
    public const NEGOCIO = 'NEGOCIO';
    public const APORTE = 'APORTE';
    public const RETIRO = 'RETIRO';

    public const ETIQUETAS = [
        self::NEGOCIO => 'Del club',
        self::APORTE => 'Aporte de los dueños',
        self::RETIRO => 'Retiro de los dueños',
    ];

    /**
     * Clasificación del catálogo conocido, definida por Carlos uno por uno el 10/10/2026.
     * Lo que no figura acá no se adivina: queda sin clasificar hasta que el admin lo
     * defina desde el formulario del subrubro.
     */
    public const CATALOGO = [
        'Cuotas' => ['Cuota Mensual' => self::NEGOCIO],
        'Inscripciones' => ['Inscripción al club' => self::NEGOCIO],
        'Clases Particulares' => ['Clase particular' => self::NEGOCIO],
        // Negocio aparte de los dueños que usa la caja del club.
        'Indumentaria' => ['Patines' => self::APORTE, 'Indumentaria institucional' => self::APORTE, 'VG Indumentaria' => self::APORTE],
        'Intereses' => ['Intereses Mercado Pago' => self::NEGOCIO, 'Intereses Banco' => self::NEGOCIO],
        'Torneos' => ['Inscripciones' => self::NEGOCIO],
        'Alquileres' => ['San Carlos' => self::NEGOCIO, 'Centenera' => self::NEGOCIO, 'Eventos' => self::NEGOCIO],
        'Gastos Operativos' => ['Limpieza' => self::NEGOCIO, 'Librería' => self::NEGOCIO, 'Insumos Varios' => self::NEGOCIO],
        'Mantenimiento y arreglos' => ['Reparaciones menores' => self::NEGOCIO],
        'Servicios' => ['Luz' => self::NEGOCIO, 'Internet' => self::NEGOCIO],
    ];

    /** Subrubros que faltaban para registrar lo que sale. Solo los usa el admin. */
    public const NUEVOS = [
        ['rubro' => 'Retiros', 'observacion' => 'Plata que se llevan los dueños; no es gasto del club',
            'subrubro' => 'Retiro de dueños', 'clasificacion' => self::RETIRO],
        ['rubro' => 'Gastos de torneos', 'observacion' => 'Lo que el club paga por participar en torneos',
            'subrubro' => 'Pago al organizador', 'clasificacion' => self::NEGOCIO],
    ];

    /** Un aporte solo puede entrar y un retiro solo puede salir. */
    public static function permitidas(string $tipoRubro): array
    {
        return $tipoRubro === 'INGRESO' ? [self::NEGOCIO, self::APORTE] : [self::NEGOCIO, self::RETIRO];
    }

    /** Completa lo que falta en una instalación existente. Nunca pisa una clasificación ya puesta. */
    public static function aplicarCatalogo(): void
    {
        foreach (self::CATALOGO as $rubro => $subrubros) {
            $rubroIds = DB::table('rubros')->where('nombre', $rubro)->pluck('id');
            foreach ($subrubros as $nombre => $clasificacion) {
                DB::table('subrubros')->whereIn('rubro_id', $rubroIds)->where('nombre', $nombre)
                    ->whereNull('clasificacion_resultado')->update(['clasificacion_resultado' => $clasificacion]);
            }
        }

        foreach (self::NUEVOS as $nuevo) {
            if (DB::table('subrubros')->where('nombre', $nuevo['subrubro'])->exists()) continue;
            $rubroId = DB::table('rubros')->where('nombre', $nuevo['rubro'])->where('tipo', 'EGRESO')->value('id')
                ?? DB::table('rubros')->insertGetId(['nombre' => $nuevo['rubro'], 'tipo' => 'EGRESO',
                    'observacion' => $nuevo['observacion'], 'created_at' => now(), 'updated_at' => now()]);
            DB::table('subrubros')->insert(['rubro_id' => $rubroId, 'nombre' => $nuevo['subrubro'], 'permitido_para' => 'ADMIN',
                'afecta_caja' => true, 'activo' => true, 'clasificacion_resultado' => $nuevo['clasificacion'],
                'created_at' => now(), 'updated_at' => now()]);
        }

        self::completarMovimientos();
    }

    /**
     * Cada movimiento guarda la clasificación que tenía su subrubro al registrarse. Los que
     * se registraron antes de clasificarlo quedaron vacíos: se completan, sin tocar el resto.
     */
    public static function completarMovimientos(?int $subrubroId = null): void
    {
        $subrubros = DB::table('subrubros')->whereNotNull('clasificacion_resultado')
            ->when($subrubroId, fn ($q) => $q->where('id', $subrubroId))->pluck('clasificacion_resultado', 'id');

        foreach ($subrubros as $id => $clasificacion) {
            foreach (['cashflow_movimientos', 'movimientos_operativos'] as $tabla) {
                DB::table($tabla)->where('subrubro_id', $id)->whereNull('reporte_clasificacion')
                    ->update(['reporte_clasificacion' => $clasificacion]);
            }
        }
    }
}
