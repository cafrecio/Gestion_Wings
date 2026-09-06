<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Completa la primera carga manual hasta 60 alumnos.
 *
 * La base inicial no es genérica: contiene las once altas verificadas de la
 * etapa manual. Diez son las altas previstas y la undécima es Sofía Morales,
 * inscripta también en Fútbol, excepción autorizada por Carlos el 06/09/2026.
 */
class PrimeraCargaAlumnosSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('PrimeraCargaAlumnosSeeder no puede ejecutarse en producción.');
        }

        DB::transaction(function (): void {
            $deportes = [
                'Patín' => Deporte::query()->where('nombre', 'Patín')->sole(),
                'Fútbol' => Deporte::query()->where('nombre', 'Fútbol')->sole(),
            ];

            $totalActual = Alumno::query()->count();
            if ($totalActual === 11) {
                $this->validarBaseManual($deportes);
                $this->crearAlumnosFaltantes($deportes);
            } elseif ($totalActual !== 60) {
                throw new LogicException("La carga inicial requiere 11 o 60 alumnos; hay {$totalActual}.");
            }

            $this->validarResultado($deportes);
        });
    }

    /** @param array<string, Deporte> $deportes */
    private function validarBaseManual(array $deportes): void
    {
        $porDeporte = Alumno::query()
            ->selectRaw('deporte_id, count(*) as total')
            ->groupBy('deporte_id')
            ->pluck('total', 'deporte_id');

        if ((int) ($porDeporte[$deportes['Patín']->id] ?? 0) !== 6
            || (int) ($porDeporte[$deportes['Fútbol']->id] ?? 0) !== 5) {
            throw new LogicException('La base manual no tiene la distribución esperada: Patín 6 y Fútbol 5.');
        }

        $tramos = $this->conteoPorTramo();
        if ($tramos !== ['2025' => 2, '2026-01-03' => 1, '2026-04-06' => 8]) {
            throw new LogicException('La base manual no tiene los tramos esperados: 2, 1 y 8.');
        }

        $sofia = Alumno::query()
            ->where('dni', '32123456')
            ->whereIn('deporte_id', [$deportes['Patín']->id, $deportes['Fútbol']->id])
            ->orderBy('deporte_id')
            ->get(['nombre', 'apellido']);

        if ($sofia->count() !== 2 || $sofia->contains(fn (Alumno $alumno) => $alumno->nombre !== 'Sofía' || $alumno->apellido !== 'Morales')) {
            throw new LogicException('Falta la dupla autorizada de Sofía Morales en Patín y Fútbol.');
        }
    }

    /** @param array<string, Deporte> $deportes */
    private function crearAlumnosFaltantes(array $deportes): void
    {
        $grupos = Grupo::query()
            ->with(['nivel', 'planes' => fn ($query) => $query->where('activo', true)->orderBy('clases_por_semana')])
            ->whereIn('deporte_id', [$deportes['Patín']->id, $deportes['Fútbol']->id])
            ->get()
            ->keyBy(fn (Grupo $grupo) => $grupo->deporte_id . ':' . $grupo->nivel->nombre);

        foreach ($this->registros() as $indice => $registro) {
            $deporte = $deportes[$registro['deporte']];
            $grupo = $grupos->get($deporte->id . ':' . $registro['nivel']);
            if (!$grupo || $grupo->planes->count() !== 2) {
                throw new LogicException("Faltan las dos frecuencias activas para {$registro['deporte']} {$registro['nivel']}.");
            }

            $alumno = Alumno::query()->firstOrCreate(
                ['dni' => $registro['dni'], 'deporte_id' => $deporte->id],
                [
                    'nombre' => $registro['nombre'],
                    'apellido' => $registro['apellido'],
                    'fecha_nacimiento' => $this->fechaNacimiento($indice),
                    'celular' => $this->celular($registro['dni']),
                    'email' => $this->email($registro),
                    'grupo_id' => $grupo->id,
                    'fecha_alta' => $registro['fecha_alta'],
                    'activo' => true,
                ],
            );

            if (!AlumnoPlan::query()->where('alumno_id', $alumno->id)->where('activo', true)->exists()) {
                AlumnoPlan::create([
                    'alumno_id' => $alumno->id,
                    'plan_id' => $grupo->planes[$indice % 2]->id,
                    'fecha_desde' => $registro['fecha_alta'],
                    'activo' => true,
                ]);
            }
        }
    }

    /** @param array<string, Deporte> $deportes */
    private function validarResultado(array $deportes): void
    {
        $porDeporte = Alumno::query()
            ->selectRaw('deporte_id, count(*) as total')
            ->groupBy('deporte_id')
            ->pluck('total', 'deporte_id');

        if (Alumno::query()->count() !== 60
            || (int) ($porDeporte[$deportes['Patín']->id] ?? 0) !== 40
            || (int) ($porDeporte[$deportes['Fútbol']->id] ?? 0) !== 20) {
            throw new LogicException('El seeder no dejó la distribución esperada: 40 Patín y 20 Fútbol.');
        }

        if ($this->conteoPorTramo() !== ['2025' => 12, '2026-01-03' => 18, '2026-04-06' => 30]) {
            throw new LogicException('El seeder no dejó los tramos de fecha 12, 18 y 30.');
        }

        if (Alumno::query()->where('fecha_alta', '>=', '2026-07-01')->exists()) {
            throw new LogicException('No puede haber altas desde julio de 2026.');
        }

        if (Alumno::query()->selectRaw('dni, deporte_id, count(*) as total')->groupBy('dni', 'deporte_id')->having('total', '>', 1)->exists()) {
            throw new LogicException('Hay DNI repetidos dentro del mismo deporte.');
        }

        if (Alumno::query()->whereDoesntHave('planActivo')->exists()) {
            throw new LogicException('Hay alumnos sin plan activo.');
        }

        if (DB::table('deuda_cuotas')->exists() || DB::table('pagos')->exists()) {
            throw new LogicException('La primera carga no puede crear ni conservar deudas o pagos.');
        }
    }

    /** @return array<string, int> */
    private function conteoPorTramo(): array
    {
        $tramos = Alumno::query()
            ->selectRaw("case
                when fecha_alta between '2025-01-01' and '2025-12-31' then '2025'
                when fecha_alta between '2026-01-01' and '2026-03-31' then '2026-01-03'
                when fecha_alta between '2026-04-01' and '2026-06-30' then '2026-04-06'
                else 'fuera'
            end as tramo, count(*) as total")
            ->groupBy('tramo')
            ->pluck('total', 'tramo')
            ->map(fn ($total) => (int) $total)
            ->all();

        ksort($tramos);

        return $tramos;
    }

    /** @return array<int, array{nombre: string, apellido: string, dni: string, nivel: string, deporte: string, fecha_alta: string}> */
    private function registros(): array
    {
        return [
            ...$this->conFechas('Patín', [
                ['Valentina', 'Arias', '41738592', 'Principiantes'], ['Milagros', 'Cabrera', '39862471', 'Intermedias'],
                ['Catalina', 'Domínguez', '42691735', 'Avanzadas'], ['Florencia', 'Escudero', '40518376', 'Federadas'],
                ['Agustina', 'Ferreyra', '43827019', 'Principiantes'], ['Martina', 'Godoy', '39281654', 'Intermedias'],
                ['Luciana', 'Herrera', '42159308', 'Avanzadas'], ['Camila', 'Ibarra', '40926713', 'Federadas'],
                ['Josefina', 'Ledesma', '43385126', 'Principiantes'], ['Malena', 'Molina', '39647280', 'Intermedias'],
                ['Natalia', 'Núñez', '41863957', 'Avanzadas'], ['Olivia', 'Ortega', '40274518', 'Federadas'],
                ['Paula', 'Paz', '42916483', 'Principiantes'], ['Rocío', 'Quiroga', '39481562', 'Intermedias'],
                ['Sabrina', 'Roldán', '43529704', 'Avanzadas'], ['Tatiana', 'Suárez', '40738195', 'Federadas'],
                ['Victoria', 'Toledo', '42370481', 'Principiantes'], ['Ailén', 'Urrutia', '39726845', 'Intermedias'],
                ['Bianca', 'Vargas', '43185072', 'Avanzadas'], ['Clara', 'Zárate', '40492637', 'Federadas'],
                ['Daniela', 'Bustos', '42731596', 'Principiantes'], ['Elena', 'Correa', '39984721', 'Intermedias'],
                ['Giselle', 'Figueroa', '43462810', 'Avanzadas'], ['Inés', 'López', '40615294', 'Federadas'],
                ['Karina', 'Maidana', '41957362', 'Principiantes'], ['Lorena', 'Ponce', '40389617', 'Intermedias'],
                ['Micaela', 'Rey', '43218750', 'Avanzadas'], ['Noelia', 'Serrano', '39562841', 'Federadas'],
                ['Paola', 'Tissera', '42571936', 'Principiantes'], ['Romina', 'Valdez', '40138572', 'Intermedias'],
                ['Sofía', 'Yáñez', '43029618', 'Avanzadas'], ['Celeste', 'Benítez', '40857193', 'Federadas'],
                ['Eugenia', 'Cáceres', '42486305', 'Principiantes'], ['Mariana', 'Delgado', '39715428', 'Intermedias'],
            ], [
                '2025-01-17', '2025-02-20', '2025-03-12', '2025-04-23', '2025-06-09', '2025-08-14', '2025-10-22',
                '2026-01-05', '2026-01-28', '2026-02-11', '2026-02-24', '2026-03-04', '2026-03-17', '2026-03-29',
                '2026-01-09', '2026-01-21', '2026-02-07', '2026-02-27', '2026-03-22',
                '2026-05-29', '2026-06-05', '2026-06-11', '2026-06-19', '2026-06-27', '2026-04-08', '2026-05-09',
                '2026-06-16', '2026-04-20', '2026-05-24', '2026-06-08', '2026-04-30', '2026-05-18', '2026-06-23',
                '2026-06-29',
            ]),
            ...$this->conFechas('Fútbol', [
                ['Alan', 'Acosta', '41627593', 'Principiantes'], ['Benjamín', 'Barrios', '39815267', 'Avanzadas'],
                ['Cristóbal', 'Cejas', '42509736', 'Principiantes'], ['Damián', 'Díaz', '40468125', 'Avanzadas'],
                ['Esteban', 'Farías', '43175682', 'Principiantes'], ['Facundo', 'Giménez', '39724619', 'Avanzadas'],
                ['Gonzalo', 'Herrera', '42391857', 'Principiantes'], ['Iván', 'Luna', '40537268', 'Avanzadas'],
                ['Joaquín', 'Mansilla', '42865109', 'Principiantes'], ['Kevin', 'Navarro', '39916473', 'Avanzadas'],
                ['Lautaro', 'Ojeda', '43370526', 'Principiantes'], ['Marcos', 'Pérez', '40291864', 'Avanzadas'],
                ['Nicolás', 'Ramos', '42638195', 'Principiantes'], ['Pablo', 'Sosa', '39682714', 'Avanzadas'],
                ['Ramiro', 'Vera', '43057286', 'Principiantes'],
            ], [
                '2025-05-08', '2025-07-16', '2025-09-25',
                '2026-01-14', '2026-02-03', '2026-02-18', '2026-03-10', '2026-03-25',
                '2026-04-06', '2026-04-16', '2026-05-02', '2026-05-16', '2026-06-04', '2026-06-17', '2026-06-25',
            ]),
        ];
    }

    /** @param array<int, array{0: string, 1: string, 2: string, 3: string}> $personas @param array<int, string> $fechas */
    private function conFechas(string $deporte, array $personas, array $fechas): array
    {
        if (count($personas) !== count($fechas)) {
            throw new LogicException('La lista de personas y fechas debe tener igual cantidad.');
        }

        return array_map(fn (array $persona, string $fecha) => [
            'nombre' => $persona[0], 'apellido' => $persona[1], 'dni' => $persona[2], 'nivel' => $persona[3],
            'deporte' => $deporte, 'fecha_alta' => $fecha,
        ], $personas, $fechas);
    }

    private function fechaNacimiento(int $indice): string
    {
        return sprintf('%04d-%02d-%02d', 1986 + ($indice % 20), 1 + (($indice * 3) % 12), 1 + (($indice * 7) % 27));
    }

    private function celular(string $dni): string
    {
        return '11' . str_pad((string) (10000000 + (crc32($dni) % 89999999)), 8, '0', STR_PAD_LEFT);
    }

    /** @param array{nombre: string, apellido: string, dni: string} $registro */
    private function email(array $registro): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $registro['nombre'] . '.' . $registro['apellido']);
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '.', $texto));

        return trim($slug, '.') . '.' . $registro['dni'] . '@wings.test';
    }
}
