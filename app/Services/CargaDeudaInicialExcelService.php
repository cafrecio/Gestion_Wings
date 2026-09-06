<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\DeudaCuota;
use App\Models\Deporte;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CargaDeudaInicialExcelService
{
    /**
     * @return array{errores: array<int, array{fila: int, mensaje: string}>, items: array<int, array{alumno_id: int, periodo: string, monto: string}>}
     */
    public function validar(string $archivo): array
    {
        if (!is_file($archivo) || !is_readable($archivo)) {
            return ['errores' => [['fila' => 0, 'mensaje' => 'No se puede leer el archivo indicado.']], 'items' => []];
        }

        try {
            $hoja = IOFactory::load($archivo)->getActiveSheet();
        } catch (\Throwable) {
            return ['errores' => [['fila' => 0, 'mensaje' => 'El archivo no es un Excel .xlsx válido o está dañado.']], 'items' => []];
        }

        $ultimaColumna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($hoja->getHighestDataColumn());
        $ultimaFila = $hoja->getHighestDataRow();
        $errores = $this->validarEncabezado($hoja, $ultimaColumna);
        $items = [];
        $filasPorAlumno = [];
        $deportes = $this->deportesPorNombre();
        $alumnos = $this->alumnosPorDniYDeporte();

        for ($fila = 2; $fila <= $ultimaFila; $fila++) {
            $valores = [];
            for ($columna = 1; $columna <= $ultimaColumna; $columna++) {
                $valores[$columna] = $this->valorCelda($hoja, $columna, $fila, $columna >= 3 && $columna % 2 === 1);
            }

            if (collect($valores)->every(fn (string $valor) => $valor === '')) {
                continue;
            }

            $dni = $valores[1] ?? '';
            $deporteTexto = $valores[2] ?? '';
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
                $filasPorAlumno[$claveAlumno] = $filasPorAlumno[$claveAlumno] ?? $fila;
            }

            $alumno = $claveAlumno !== null ? ($alumnos[$claveAlumno] ?? null) : null;
            if ($claveAlumno !== null && $alumno === null) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'No existe un alumno para ese DNI y deporte.'];
            }

            $pares = $this->validarPares($valores, $fila, $errores);
            if ($pares === [] && !$this->tieneAlgunValorDesdeTerceraColumna($valores)) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'Debe informar al menos un monto y período.'];
            }

            if ($alumno !== null) {
                foreach ($pares as $par) {
                    $items[] = ['alumno_id' => $alumno->id, 'periodo' => $par['periodo'], 'monto' => $par['monto'], 'fila' => $fila];
                }
            }
        }

        $this->agregarConflictosConDeudasExistentes($items, $errores);

        return ['errores' => $errores, 'items' => $items];
    }

    /** @return array{errores: array<int, array{fila: int, mensaje: string}>, cantidad: int} */
    public function importar(string $archivo): array
    {
        $resultado = $this->validar($archivo);
        if ($resultado['errores'] !== []) {
            return ['errores' => $resultado['errores'], 'cantidad' => 0];
        }

        DB::transaction(function () use ($resultado, $archivo): void {
            foreach ($resultado['items'] as $item) {
                DeudaCuota::create([
                    'alumno_id' => $item['alumno_id'],
                    'periodo' => $item['periodo'],
                    'monto_original' => $item['monto'],
                    'monto_pagado' => 0,
                    'estado' => DeudaCuota::ESTADO_PENDIENTE,
                    'observaciones' => 'Carga inicial desde Excel: '.basename($archivo),
                ]);
            }
        });

        return ['errores' => [], 'cantidad' => count($resultado['items'])];
    }

    /** @return array{errores: array<int, array{fila: int, mensaje: string}>, cantidad: int} */
    public function revertir(string $archivo): array
    {
        $resultado = $this->validarSinConflictosExistentes($archivo);
        if ($resultado['errores'] !== []) {
            return ['errores' => $resultado['errores'], 'cantidad' => 0];
        }

        $deudas = [];
        foreach ($resultado['items'] as $item) {
            $deuda = DeudaCuota::where('alumno_id', $item['alumno_id'])->where('periodo', $item['periodo'])->first();
            if ($deuda === null || $deuda->estado !== DeudaCuota::ESTADO_PENDIENTE || (float) $deuda->monto_pagado !== 0.0 || $deuda->pagosDeuda()->exists() || (float) $deuda->monto_original !== (float) $item['monto']) {
                $resultado['errores'][] = ['fila' => $item['fila'], 'mensaje' => "La deuda de {$item['periodo']} no está pendiente e intacta; no se puede revertir."];
                continue;
            }
            $deudas[] = $deuda;
        }
        if ($resultado['errores'] !== []) {
            return ['errores' => $resultado['errores'], 'cantidad' => 0];
        }

        DB::transaction(function () use ($deudas): void {
            foreach ($deudas as $deuda) {
                $deuda->delete();
            }
        });

        return ['errores' => [], 'cantidad' => count($deudas)];
    }

    /** @return array{errores: array<int, array{fila: int, mensaje: string}>, items: array<int, array{alumno_id: int, periodo: string, monto: string, fila: int}>} */
    private function validarSinConflictosExistentes(string $archivo): array
    {
        $resultado = $this->validar($archivo);
        $resultado['errores'] = array_values(array_filter($resultado['errores'], fn (array $error) => !str_starts_with($error['mensaje'], 'Ya existe una deuda')));
        return $resultado;
    }

    /** @return array<int, array{fila: int, mensaje: string}> */
    private function validarEncabezado($hoja, int $ultimaColumna): array
    {
        $esperados = ['dni', 'deporte'];
        for ($columna = 1; $columna <= $ultimaColumna; $columna++) {
            $valor = $this->normalizar($this->valorCelda($hoja, $columna, 1));
            $esperado = $columna <= 2 ? $esperados[$columna - 1] : ($columna % 2 === 1 ? 'monto' : 'mmyyyy');
            if ($valor !== $esperado) {
                return [['fila' => 1, 'mensaje' => 'El encabezado debe ser DNI, deporte y pares repetidos de monto, mmYYYY.']];
            }
        }
        return [];
    }

    /** @param array<int, string> $valores @param array<int, array{fila: int, mensaje: string}> $errores @return array<int, array{periodo: string, monto: string}> */
    private function validarPares(array $valores, int $fila, array &$errores): array
    {
        $pares = [];
        $periodos = [];
        $ultimaColumna = max(array_keys($valores));
        for ($columna = 3; $columna <= $ultimaColumna; $columna += 2) {
            $monto = $valores[$columna] ?? '';
            $mes = $valores[$columna + 1] ?? '';
            if ($monto === '' && $mes === '') {
                continue;
            }
            if ($monto === '' || $mes === '') {
                $errores[] = ['fila' => $fila, 'mensaje' => 'Cada monto debe tener su período mmYYYY y viceversa.'];
                continue;
            }
            if (!is_numeric($monto) || (float) $monto <= 0) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'El monto debe ser numérico y mayor que cero.'];
                continue;
            }
            if (!preg_match('/^(0[1-9]|1[0-2])(202[5-9]|20[3-9]\d)$/', $mes, $coincidencia)) {
                $errores[] = ['fila' => $fila, 'mensaje' => 'El período debe tener formato mmYYYY válido desde 2025.'];
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
    private function tieneAlgunValorDesdeTerceraColumna(array $valores): bool
    {
        foreach ($valores as $columna => $valor) {
            if ($columna >= 3 && $valor !== '') {
                return true;
            }
        }
        return false;
    }

    /** @param array<int, array{alumno_id: int, periodo: string, monto: string, fila: int}> $items @param array<int, array{fila: int, mensaje: string}> $errores */
    private function agregarConflictosConDeudasExistentes(array $items, array &$errores): void
    {
        if ($items === []) {
            return;
        }
        $existentes = DeudaCuota::whereIn('alumno_id', array_unique(array_column($items, 'alumno_id')))
            ->whereIn('periodo', array_unique(array_column($items, 'periodo')))
            ->get(['alumno_id', 'periodo'])
            ->mapWithKeys(fn (DeudaCuota $deuda) => ["{$deuda->alumno_id}|{$deuda->periodo}" => true]);
        foreach ($items as $item) {
            if (isset($existentes["{$item['alumno_id']}|{$item['periodo']}"])) {
                $errores[] = ['fila' => $item['fila'], 'mensaje' => "Ya existe una deuda para el período {$item['periodo']}."];
            }
        }
    }

    /** @return array<string, int> */
    private function deportesPorNombre(): array
    {
        return Deporte::query()->get(['id', 'nombre'])->mapWithKeys(fn (Deporte $deporte) => [$this->normalizar($deporte->nombre) => $deporte->id])->all();
    }

    /** @return array<string, Alumno> */
    private function alumnosPorDniYDeporte(): array
    {
        return Alumno::query()->get(['id', 'dni', 'deporte_id'])->mapWithKeys(fn (Alumno $alumno) => ["{$alumno->dni}|{$alumno->deporte_id}" => $alumno])->all();
    }

    private function normalizar(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }

    private function valorCelda($hoja, int $columna, int $fila, bool $usarValorOriginal = false): string
    {
        $celda = $hoja->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columna).$fila);
        $valor = $celda->getValue();
        if ($usarValorOriginal && is_numeric($valor)) {
            return (string) $valor;
        }
        if ($valor === 0 || $valor === 0.0 || $valor === '0') {
            return '0';
        }

        return trim((string) $celda->getFormattedValue());
    }
}
