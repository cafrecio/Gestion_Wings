<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Carga el saldo inicial de TODO el padron, no solo de los que deben.
 *
 * Dos diferencias con `CargaDeudaInicialExcelService`, que carga unicamente deudores:
 *
 * 1. Cada alumno trae un DEBE explicito. Antes, el que no figuraba en el archivo se
 *    asumia sin deuda: un olvido y una persona al dia se veian igual.
 * 2. El mes de corte queda cerrado para todos. Al alumno que no declara deuda de ese
 *    mes se le escribe una deuda en cero ya pagada, que es lo que la pantalla de cobro
 *    mira para no volver a ofrecerlo. Wings empieza a facturar el mes siguiente.
 *
 * No se registra ningun pago. Un pago en cero diria que entro plata, y no entro: lo que
 * el club cobro antes de Wings no es parte de esta contabilidad.
 */
class CargaSaldoInicialPadronService
{
    private const COLUMNA_PRIMER_PAR = 5;

    /**
     * @return array{errores: array<int, array{fila: int, mensaje: string}>, deudas: array<int, array{alumno_id: int, periodo: string, monto: string, fila: int}>, cierres: array<int, array{alumno_id: int, fila: int}>}
     */
    public function validar(string $archivo, string $periodoCorte): array
    {
        $vacio = ['errores' => [], 'deudas' => [], 'cierres' => []];

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodoCorte)) {
            return [...$vacio, 'errores' => [['fila' => 0, 'mensaje' => 'El período de corte debe tener formato YYYY-MM.']]];
        }

        if (!is_file($archivo) || !is_readable($archivo)) {
            return [...$vacio, 'errores' => [['fila' => 0, 'mensaje' => 'No se puede leer el archivo indicado.']]];
        }

        try {
            $hoja = IOFactory::load($archivo)->getActiveSheet();
        } catch (\Throwable) {
            return [...$vacio, 'errores' => [['fila' => 0, 'mensaje' => 'El archivo no es un Excel .xlsx válido o está dañado.']]];
        }

        $ultimaColumna = Coordinate::columnIndexFromString($hoja->getHighestDataColumn());
        $ultimaFila = $hoja->getHighestDataRow();

        $errores = $this->validarEncabezado($hoja, $ultimaColumna);
        if ($errores !== []) {
            return [...$vacio, 'errores' => $errores];
        }

        $deudas = [];
        $cierres = [];
        $filasPorAlumno = [];
        $deportes = $this->deportesPorNombre();
        $alumnos = $this->alumnosPorDniYDeporte();

        for ($fila = 2; $fila <= $ultimaFila; $fila++) {
            $valores = [];
            for ($columna = 1; $columna <= $ultimaColumna; $columna++) {
                $valores[$columna] = $this->valorCelda($hoja, $columna, $fila);
            }

            if (collect($valores)->every(fn (string $valor) => $valor === '')) {
                continue;
            }

            $dni = $valores[1] ?? '';
            $deporteTexto = $valores[3] ?? '';

            if ($dni === '') {
                $errores[] = ['fila' => $fila, 'mensaje' => 'Falta el DNI.'];
            }
            if ($deporteTexto === '') {
                $errores[] = ['fila' => $fila, 'mensaje' => 'Falta el deporte.'];
            }

            $deporteId = $deportes[$this->normalizar($deporteTexto)] ?? null;
            if ($deporteTexto !== '' && $deporteId === null) {
                $errores[] = ['fila' => $fila, 'mensaje' => "El deporte '{$deporteTexto}' no existe."];
            }

            $claveAlumno = $dni !== '' && $deporteId !== null ? "{$dni}|{$deporteId}" : null;
            if ($claveAlumno !== null && isset($filasPorAlumno[$claveAlumno])) {
                $errores[] = ['fila' => $fila, 'mensaje' => "Se repite DNI + deporte; ya figura en la fila {$filasPorAlumno[$claveAlumno]}."];
            }
            if ($claveAlumno !== null) {
                $filasPorAlumno[$claveAlumno] ??= $fila;
            }

            $alumno = $claveAlumno !== null ? ($alumnos[$claveAlumno] ?? null) : null;
            if ($claveAlumno !== null && $alumno === null) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'No existe un alumno para ese DNI y deporte.'];
            }

            $debe = $this->interpretarDebe($valores[4] ?? '');
            if ($debe === null) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'La columna DEBE tiene que decir SI o NO.'];
                continue;
            }

            $pares = $this->validarPares($valores, $fila, $errores);

            if ($debe && $pares === []) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'Dice SI pero no informa ningún período con monto.'];
            }
            if (!$debe && $this->tieneAlgunValorEnLosPares($valores)) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'Dice NO pero informa períodos o montos. Definir una cosa o la otra.'];
            }

            if ($alumno === null) {
                continue;
            }

            foreach ($pares as $par) {
                $deudas[] = ['alumno_id' => $alumno->id, 'periodo' => $par['periodo'], 'monto' => $par['monto'], 'fila' => $fila];
            }

            // El corte se cierra salvo que el propio alumno haya declarado deuda de ese mes.
            if (!in_array($periodoCorte, array_column($pares, 'periodo'), true)) {
                $cierres[] = ['alumno_id' => $alumno->id, 'fila' => $fila];
            }
        }

        $this->agregarConflictosConDeudasExistentes($deudas, $cierres, $periodoCorte, $errores);

        return ['errores' => $errores, 'deudas' => $deudas, 'cierres' => $cierres];
    }

    /** @return array{errores: array<int, array{fila: int, mensaje: string}>, deudas: int, cierres: int} */
    public function importar(string $archivo, string $periodoCorte): array
    {
        $resultado = $this->validar($archivo, $periodoCorte);
        if ($resultado['errores'] !== []) {
            return ['errores' => $resultado['errores'], 'deudas' => 0, 'cierres' => 0];
        }

        $origen = 'Carga inicial de padrón: '.basename($archivo);

        DB::transaction(function () use ($resultado, $periodoCorte, $origen): void {
            foreach ($resultado['deudas'] as $deuda) {
                DeudaCuota::create([
                    'alumno_id' => $deuda['alumno_id'],
                    'periodo' => $deuda['periodo'],
                    'monto_original' => $deuda['monto'],
                    'monto_pagado' => 0,
                    'estado' => DeudaCuota::ESTADO_PENDIENTE,
                    'observaciones' => $origen,
                ]);
            }

            foreach ($resultado['cierres'] as $cierre) {
                DeudaCuota::create([
                    'alumno_id' => $cierre['alumno_id'],
                    'periodo' => $periodoCorte,
                    'monto_original' => 0,
                    'monto_pagado' => 0,
                    'estado' => DeudaCuota::ESTADO_PAGADA,
                    'observaciones' => $origen.' — saldo inicial cero, mes cerrado',
                ]);
            }
        });

        return ['errores' => [], 'deudas' => count($resultado['deudas']), 'cierres' => count($resultado['cierres'])];
    }

    /** @return array<int, array{fila: int, mensaje: string}> */
    private function validarEncabezado($hoja, int $ultimaColumna): array
    {
        if ($ultimaColumna < self::COLUMNA_PRIMER_PAR + 1) {
            return [['fila' => 1, 'mensaje' => 'El encabezado debe ser DNI, Alumno, Deporte, DEBE y pares de Periodo y Monto.']];
        }

        $fijos = ['dni', 'alumno', 'deporte', 'debe'];
        foreach ($fijos as $indice => $esperado) {
            if ($this->normalizar($this->valorCelda($hoja, $indice + 1, 1)) !== $esperado) {
                return [['fila' => 1, 'mensaje' => 'El encabezado debe ser DNI, Alumno, Deporte, DEBE y pares de Periodo y Monto.']];
            }
        }

        for ($columna = self::COLUMNA_PRIMER_PAR; $columna <= $ultimaColumna; $columna++) {
            $prefijo = ($columna - self::COLUMNA_PRIMER_PAR) % 2 === 0 ? 'periodo' : 'monto';
            if (!str_starts_with($this->normalizar($this->valorCelda($hoja, $columna, 1)), $prefijo)) {
                return [['fila' => 1, 'mensaje' => 'Después de DEBE tienen que alternarse columnas Periodo y Monto.']];
            }
        }

        return [];
    }

    private function interpretarDebe(string $valor): ?bool
    {
        return match ($this->normalizar($valor)) {
            'si' => true,
            'no' => false,
            default => null,
        };
    }

    /** @param array<int, string> $valores @param array<int, array{fila: int, mensaje: string}> $errores @return array<int, array{periodo: string, monto: string}> */
    private function validarPares(array $valores, int $fila, array &$errores): array
    {
        $pares = [];
        $periodos = [];
        $ultimaColumna = max(array_keys($valores));

        for ($columna = self::COLUMNA_PRIMER_PAR; $columna <= $ultimaColumna; $columna += 2) {
            $mes = $valores[$columna] ?? '';
            $monto = $valores[$columna + 1] ?? '';

            if ($mes === '' && $monto === '') {
                continue;
            }
            if ($mes === '' || $monto === '') {
                $errores[] = ['fila' => $fila, 'mensaje' => 'Cada período debe tener su monto y viceversa.'];
                continue;
            }
            if (!preg_match('/^(0[1-9]|1[0-2])(202[5-9]|20[3-9]\d)$/', $mes, $coincidencia)) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'El período debe tener formato mmYYYY válido desde 2025.'];
                continue;
            }
            if (!is_numeric($monto) || (float) $monto <= 0) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'El monto debe ser numérico y mayor que cero.'];
                continue;
            }

            $periodo = "{$coincidencia[2]}-{$coincidencia[1]}";
            if (isset($periodos[$periodo])) {
                $errores[] = ['fila' => $fila, 'mensaje' => "El período {$periodo} está repetido en la misma fila."];
                continue;
            }
            $periodos[$periodo] = true;
            $pares[] = ['periodo' => $periodo, 'monto' => number_format((float) $monto, 2, '.', '')];
        }

        return $pares;
    }

    /** @param array<int, string> $valores */
    private function tieneAlgunValorEnLosPares(array $valores): bool
    {
        foreach ($valores as $columna => $valor) {
            if ($columna >= self::COLUMNA_PRIMER_PAR && $valor !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int, array{alumno_id: int, periodo: string, monto: string, fila: int}> $deudas
     * @param array<int, array{alumno_id: int, fila: int}> $cierres
     * @param array<int, array{fila: int, mensaje: string}> $errores
     */
    private function agregarConflictosConDeudasExistentes(array $deudas, array $cierres, string $periodoCorte, array &$errores): void
    {
        $alumnoIds = array_unique([...array_column($deudas, 'alumno_id'), ...array_column($cierres, 'alumno_id')]);
        if ($alumnoIds === []) {
            return;
        }

        $periodos = array_unique([...array_column($deudas, 'periodo'), $periodoCorte]);
        $existentes = DeudaCuota::whereIn('alumno_id', $alumnoIds)
            ->whereIn('periodo', $periodos)
            ->get(['alumno_id', 'periodo'])
            ->mapWithKeys(fn (DeudaCuota $deuda) => ["{$deuda->alumno_id}|{$deuda->periodo}" => true]);

        foreach ($deudas as $deuda) {
            if (isset($existentes["{$deuda['alumno_id']}|{$deuda['periodo']}"])) {
                $errores[] = ['fila' => $deuda['fila'], 'mensaje' => "Ya existe una deuda para el período {$deuda['periodo']}."];
            }
        }

        foreach ($cierres as $cierre) {
            if (isset($existentes["{$cierre['alumno_id']}|{$periodoCorte}"])) {
                $errores[] = ['fila' => $cierre['fila'], 'mensaje' => "Ya existe una deuda para el período de corte {$periodoCorte}."];
            }
        }
    }

    /** @return array<string, int> */
    private function deportesPorNombre(): array
    {
        return Deporte::query()->get(['id', 'nombre'])
            ->mapWithKeys(fn (Deporte $deporte) => [$this->normalizar($deporte->nombre) => $deporte->id])
            ->all();
    }

    /** @return array<string, Alumno> */
    private function alumnosPorDniYDeporte(): array
    {
        return Alumno::query()->get(['id', 'dni', 'deporte_id'])
            ->mapWithKeys(fn (Alumno $alumno) => ["{$alumno->dni}|{$alumno->deporte_id}" => $alumno])
            ->all();
    }

    private function valorCelda($hoja, int $columna, int $fila): string
    {
        $valor = $hoja->getCell([$columna, $fila])->getValue();
        return trim((string) ($valor ?? ''));
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}
