<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\Clase;
use App\Models\DeudaCuota;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private string $now;
    private Carbon $hoy;
    private Carbon $inicioClases;
    private Carbon $finClases;
    private array  $cajasCache = []; // "opId:fecha:n" → ['caja_id'=>int,'caja'=>obj,'mov_count'=>int]

    public function run(): void
    {
        $this->now          = now()->toDateTimeString();
        $this->hoy          = Carbon::today();
        $this->inicioClases = Carbon::parse('2026-01-05');
        $this->finClases    = Carbon::parse('2026-07-31');

        $this->fase0Limpiar();

        $base   = $this->fase1Base();
        $prof   = $this->fase2Profesores($base['dep']);
        $alums  = $this->fase3Alumnos($base['dep'], $base['grp'], $base['pla']);
        $series = $this->fase4Clases($base['grp'], $prof);

        $this->fase5Asistencias($series);
        $this->fase6DeudasPagos($alums, $base['tipos']);
        $this->fase7CashflowCajas();
        $this->fase8CashflowDirecto($base['tipos']);
        $this->fase9Liquidaciones($prof, $series, $base['tipos']);
        $this->fase10Dump();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 0 — LIMPIEZA
    // ─────────────────────────────────────────────────────────────────────────

    private function fase0Limpiar(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'cashflow_movimientos','liquidacion_detalles','liquidaciones',
            'pago_deuda_cuota','pagos','deuda_cuotas','asistencias',
            'clase_profesor','clases','movimientos_operativos','cajas_operativas',
            'alumno_planes','alumnos','grupo_planes','grupos',
            'profesores','niveles','deportes',
        ] as $t) {
            if (Schema::hasTable($t)) DB::table($t)->truncate();
        }
        if (Schema::hasTable('liquidacion_pagos')) DB::table('liquidacion_pagos')->truncate();

        // Borrar TODOS los subrubros del rubro Sueldos
        $rSueldos = Rubro::where('nombre', 'Sueldos')->first();
        if ($rSueldos) Subrubro::where('rubro_id', $rSueldos->id)->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Tipos de caja
        foreach ([
            ['nombre' => 'Efectivo',      'abreviatura' => 'EFT', 'permite_descubierto' => false],
            ['nombre' => 'Mercado Pago',  'abreviatura' => 'MP',  'permite_descubierto' => false],
            ['nombre' => 'Banco Galicia', 'abreviatura' => 'BGA', 'permite_descubierto' => true],
            ['nombre' => 'Banco Nación',  'abreviatura' => 'BNA', 'permite_descubierto' => true],
        ] as $tc) {
            TipoCaja::updateOrCreate(['nombre' => $tc['nombre']], array_merge($tc, ['activo' => true]));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 1 — BASE
    // ─────────────────────────────────────────────────────────────────────────

    private function fase1Base(): array
    {
        $dep = [
            'patin'  => Deporte::create(['nombre' => 'Patín',  'tipo_liquidacion' => 'HORA',     'activo' => true]),
            'futbol' => Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]),
        ];

        $niv = [
            'princ' => Nivel::firstOrCreate(['nombre' => 'Principiantes']),
            'inter' => Nivel::firstOrCreate(['nombre' => 'Intermedias']),
            'avan'  => Nivel::firstOrCreate(['nombre' => 'Avanzadas']),
        ];

        $grp = [];
        foreach ([
            'futbol_princ' => [$dep['futbol'], $niv['princ']],
            'futbol_avan'  => [$dep['futbol'], $niv['avan']],
            'patin_princ'  => [$dep['patin'],  $niv['princ']],
            'patin_inter'  => [$dep['patin'],  $niv['inter']],
            'patin_avan'   => [$dep['patin'],  $niv['avan']],
        ] as $k => [$d, $n]) {
            $grp[$k] = Grupo::create(['deporte_id' => $d->id, 'nivel_id' => $n->id, 'activo' => true]);
        }

        $pla = [];
        foreach ([
            'futbol_princ' => [25000, 30000],
            'futbol_avan'  => [25000, 30000],
            'patin_princ'  => [28000, 33000],
            'patin_inter'  => [28000, 33000],
            'patin_avan'   => [28000, 33000],
        ] as $k => [$p1, $p2]) {
            $pla["{$k}_1x"] = GrupoPlan::create(['grupo_id' => $grp[$k]->id, 'clases_por_semana' => 1, 'precio_mensual' => $p1, 'activo' => true]);
            $pla["{$k}_2x"] = GrupoPlan::create(['grupo_id' => $grp[$k]->id, 'clases_por_semana' => 2, 'precio_mensual' => $p2, 'activo' => true]);
        }

        $tipos = [
            'eft' => TipoCaja::where('nombre', 'Efectivo')->first(),
            'mp'  => TipoCaja::where('nombre', 'Mercado Pago')->first(),
            'bga' => TipoCaja::where('nombre', 'Banco Galicia')->first(),
            'bna' => TipoCaja::where('nombre', 'Banco Nación')->first(),
        ];

        return compact('dep', 'niv', 'grp', 'pla', 'tipos');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 2 — PROFESORES
    // ─────────────────────────────────────────────────────────────────────────

    private function fase2Profesores(array $dep): array
    {
        $rSueldos = Rubro::where('nombre', 'Sueldos')->firstOrFail();

        $defs = [
            'mitre'     => ['apellido' => 'Mitre',     'nombre' => 'Jorge',   'dni' => '20100001', 'nac' => '1985-03-12', 'dep' => 'patin',  'vh' => 10000, 'pct' => null],
            'lopez'     => ['apellido' => 'López',     'nombre' => 'Ana',     'dni' => '20100002', 'nac' => '1990-07-25', 'dep' => 'patin',  'vh' => 10000, 'pct' => null],
            'rodriguez' => ['apellido' => 'Rodríguez', 'nombre' => 'Carlos',  'dni' => '20100003', 'nac' => '1988-11-03', 'dep' => 'patin',  'vh' => 10000, 'pct' => null],
            'fernandez' => ['apellido' => 'Fernández', 'nombre' => 'Marcela', 'dni' => '20100004', 'nac' => '1992-05-18', 'dep' => 'patin',  'vh' => 10000, 'pct' => null],
            'garcia'    => ['apellido' => 'García',    'nombre' => 'Roberto', 'dni' => '20100005', 'nac' => '1983-09-30', 'dep' => 'futbol', 'vh' => null,  'pct' => 30],
            'martinez'  => ['apellido' => 'Martínez',  'nombre' => 'Diego',   'dni' => '20100006', 'nac' => '1987-02-14', 'dep' => 'futbol', 'vh' => null,  'pct' => 30],
        ];

        $result = [];
        foreach ($defs as $k => $d) {
            $prof = Profesor::create([
                'deporte_id'          => $dep[$d['dep']]->id,
                'nombre'              => $d['nombre'],
                'apellido'            => $d['apellido'],
                'dni'                 => $d['dni'],
                'fecha_nacimiento'    => $d['nac'],
                'direccion'           => 'Sin datos',
                'localidad'           => 'Buenos Aires',
                'valor_hora'          => $d['vh'],
                'porcentaje_comision' => $d['pct'],
                'activo'              => true,
            ]);

            Subrubro::create([
                'rubro_id'             => $rSueldos->id,
                'nombre'               => "Sueldo - {$d['apellido']}, {$d['nombre']}",
                'permitido_para'       => 'ADMIN',
                'afecta_caja'          => false,
                'es_reservado_sistema' => false,
            ]);

            $result[$k] = $prof;
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 3 — ALUMNOS
    // ─────────────────────────────────────────────────────────────────────────

    private function fase3Alumnos(array $dep, array $grp, array $pla): array
    {
        $defs = [
            // FÚTBOL
            ['ap'=>'González',  'nb'=>'Lucas',     'dni'=>'30000001','nac'=>'2012-04-15','dep'=>'futbol','grp'=>'futbol_princ','plan'=>'futbol_princ_1x','alta'=>'2025-03-01','activo'=>true],
            ['ap'=>'Rodríguez', 'nb'=>'Matías',    'dni'=>'30000002','nac'=>'2013-08-22','dep'=>'futbol','grp'=>'futbol_princ','plan'=>'futbol_princ_2x','alta'=>'2025-05-15','activo'=>true],
            ['ap'=>'López',     'nb'=>'Sebastián', 'dni'=>'30000003','nac'=>'2011-11-03','dep'=>'futbol','grp'=>'futbol_princ','plan'=>'futbol_princ_1x','alta'=>'2025-07-22','activo'=>true],
            ['ap'=>'Fernández', 'nb'=>'Ezequiel',  'dni'=>'30000004','nac'=>'2014-02-28','dep'=>'futbol','grp'=>'futbol_princ','plan'=>'futbol_princ_2x','alta'=>'2025-09-10','activo'=>true],
            ['ap'=>'Martínez',  'nb'=>'Agustín',   'dni'=>'30000005','nac'=>'2012-07-10','dep'=>'futbol','grp'=>'futbol_princ','plan'=>'futbol_princ_1x','alta'=>'2025-11-18','activo'=>true],
            ['ap'=>'García',    'nb'=>'Tomás',     'dni'=>'30000006','nac'=>'2013-01-19','dep'=>'futbol','grp'=>'futbol_princ','plan'=>'futbol_princ_2x','alta'=>'2026-01-05','activo'=>true],
            ['ap'=>'Pérez',     'nb'=>'Federico',  'dni'=>'30000007','nac'=>'2010-09-07','dep'=>'futbol','grp'=>'futbol_avan', 'plan'=>'futbol_avan_1x', 'alta'=>'2025-03-15','activo'=>true],
            ['ap'=>'Sánchez',   'nb'=>'Nicolás',   'dni'=>'30000008','nac'=>'2011-05-14','dep'=>'futbol','grp'=>'futbol_avan', 'plan'=>'futbol_avan_2x', 'alta'=>'2025-06-08','activo'=>true],
            ['ap'=>'Romero',    'nb'=>'Ignacio',   'dni'=>'30000009','nac'=>'2010-12-30','dep'=>'futbol','grp'=>'futbol_avan', 'plan'=>'futbol_avan_1x', 'alta'=>'2025-08-20','activo'=>true],
            ['ap'=>'Torres',    'nb'=>'Leandro',   'dni'=>'30000010','nac'=>'2011-03-25','dep'=>'futbol','grp'=>'futbol_avan', 'plan'=>'futbol_avan_2x', 'alta'=>'2025-10-14','activo'=>true],
            ['ap'=>'Álvarez',   'nb'=>'Nahuel',    'dni'=>'30000011','nac'=>'2012-10-11','dep'=>'futbol','grp'=>'futbol_avan', 'plan'=>'futbol_avan_1x', 'alta'=>'2025-12-22','activo'=>true],
            ['ap'=>'Flores',    'nb'=>'Ramiro',    'dni'=>'30000012','nac'=>'2013-06-05','dep'=>'futbol','grp'=>'futbol_avan', 'plan'=>'futbol_avan_2x', 'alta'=>'2026-02-17','activo'=>false],
            // PATÍN
            ['ap'=>'Gómez',     'nb'=>'Valentina', 'dni'=>'38000001','nac'=>'2014-03-08','dep'=>'patin','grp'=>'patin_princ','plan'=>'patin_princ_1x','alta'=>'2024-08-10','activo'=>true],
            ['ap'=>'Díaz',      'nb'=>'Camila',    'dni'=>'38000002','nac'=>'2015-07-21','dep'=>'patin','grp'=>'patin_princ','plan'=>'patin_princ_2x','alta'=>'2024-10-05','activo'=>true],
            ['ap'=>'Ruiz',      'nb'=>'Sofía',     'dni'=>'38000003','nac'=>'2013-11-14','dep'=>'patin','grp'=>'patin_princ','plan'=>'patin_princ_1x','alta'=>'2024-12-18','activo'=>true],
            ['ap'=>'Moreno',    'nb'=>'Lucía',     'dni'=>'38000004','nac'=>'2014-01-30','dep'=>'patin','grp'=>'patin_princ','plan'=>'patin_princ_2x','alta'=>'2025-02-28','activo'=>true],
            ['ap'=>'Jiménez',   'nb'=>'Martina',   'dni'=>'38000005','nac'=>'2015-09-18','dep'=>'patin','grp'=>'patin_princ','plan'=>'patin_princ_1x','alta'=>'2025-05-12','activo'=>true],
            ['ap'=>'Herrera',   'nb'=>'Giuliana',  'dni'=>'38000006','nac'=>'2013-04-02','dep'=>'patin','grp'=>'patin_princ','plan'=>'patin_princ_2x','alta'=>'2025-08-07','activo'=>true],
            ['ap'=>'Castro',    'nb'=>'Florencia', 'dni'=>'38000007','nac'=>'2014-08-27','dep'=>'patin','grp'=>'patin_princ','plan'=>'patin_princ_1x','alta'=>'2025-11-25','activo'=>true],
            ['ap'=>'Vargas',    'nb'=>'Pilar',     'dni'=>'38000008','nac'=>'2015-12-09','dep'=>'patin','grp'=>'patin_princ','plan'=>'patin_princ_2x','alta'=>'2026-01-30','activo'=>true],
            ['ap'=>'Ramos',     'nb'=>'Agostina',  'dni'=>'38000009','nac'=>'2011-05-16','dep'=>'patin','grp'=>'patin_inter','plan'=>'patin_inter_1x','alta'=>'2024-08-22','activo'=>true],
            ['ap'=>'Ortiz',     'nb'=>'Milagros',  'dni'=>'38000010','nac'=>'2012-02-23','dep'=>'patin','grp'=>'patin_inter','plan'=>'patin_inter_2x','alta'=>'2024-11-03','activo'=>false],
            ['ap'=>'Delgado',   'nb'=>'Candela',   'dni'=>'38000011','nac'=>'2010-10-05','dep'=>'patin','grp'=>'patin_inter','plan'=>'patin_inter_1x','alta'=>'2025-01-14','activo'=>true],
            ['ap'=>'Gutiérrez', 'nb'=>'Ámbar',     'dni'=>'38000012','nac'=>'2011-08-13','dep'=>'patin','grp'=>'patin_inter','plan'=>'patin_inter_2x','alta'=>'2025-04-08','activo'=>true],
            ['ap'=>'Soto',      'nb'=>'Rocío',     'dni'=>'38000013','nac'=>'2012-06-29','dep'=>'patin','grp'=>'patin_inter','plan'=>'patin_inter_1x','alta'=>'2025-06-30','activo'=>true],
            ['ap'=>'Medina',    'nb'=>'Abril',     'dni'=>'38000014','nac'=>'2013-03-17','dep'=>'patin','grp'=>'patin_inter','plan'=>'patin_inter_2x','alta'=>'2025-09-15','activo'=>true],
            ['ap'=>'Navarro',   'nb'=>'Natalia',   'dni'=>'38000015','nac'=>'2011-11-22','dep'=>'patin','grp'=>'patin_inter','plan'=>'patin_inter_1x','alta'=>'2025-12-01','activo'=>true],
            ['ap'=>'Silva',     'nb'=>'Bianca',    'dni'=>'38000016','nac'=>'2012-09-04','dep'=>'patin','grp'=>'patin_inter','plan'=>'patin_inter_2x','alta'=>'2026-03-12','activo'=>true],
            ['ap'=>'Núñez',     'nb'=>'Isabella',  'dni'=>'38000017','nac'=>'2009-07-11','dep'=>'patin','grp'=>'patin_avan', 'plan'=>'patin_avan_1x', 'alta'=>'2024-09-05','activo'=>true],
            ['ap'=>'Mendoza',   'nb'=>'Aldana',    'dni'=>'38000018','nac'=>'2010-01-28','dep'=>'patin','grp'=>'patin_avan', 'plan'=>'patin_avan_2x', 'alta'=>'2024-11-19','activo'=>true],
            ['ap'=>'Ponce',     'nb'=>'Catalina',  'dni'=>'38000019','nac'=>'2009-04-15','dep'=>'patin','grp'=>'patin_avan', 'plan'=>'patin_avan_1x', 'alta'=>'2025-02-08','activo'=>true],
            ['ap'=>'Cruz',      'nb'=>'Victoria',  'dni'=>'38000020','nac'=>'2010-11-03','dep'=>'patin','grp'=>'patin_avan', 'plan'=>'patin_avan_2x', 'alta'=>'2025-04-22','activo'=>true],
            ['ap'=>'Reyes',     'nb'=>'Valentina', 'dni'=>'38000021','nac'=>'2009-08-20','dep'=>'patin','grp'=>'patin_avan', 'plan'=>'patin_avan_1x', 'alta'=>'2025-07-11','activo'=>true],
            ['ap'=>'Morales',   'nb'=>'Agustina',  'dni'=>'38000022','nac'=>'2010-05-07','dep'=>'patin','grp'=>'patin_avan', 'plan'=>'patin_avan_2x', 'alta'=>'2025-10-03','activo'=>true],
            ['ap'=>'Vega',      'nb'=>'Emilia',    'dni'=>'38000023','nac'=>'2009-12-25','dep'=>'patin','grp'=>'patin_avan', 'plan'=>'patin_avan_1x', 'alta'=>'2026-01-08','activo'=>true],
            ['ap'=>'Pereyra',   'nb'=>'Delfina',   'dni'=>'38000024','nac'=>'2010-06-18','dep'=>'patin','grp'=>'patin_avan', 'plan'=>'patin_avan_2x', 'alta'=>'2026-03-25','activo'=>true],
        ];

        $result = [];
        foreach ($defs as $d) {
            $alumno = Alumno::create([
                'apellido'         => $d['ap'],
                'nombre'           => $d['nb'],
                'dni'              => $d['dni'],
                'fecha_nacimiento' => $d['nac'],
                'celular'          => '11-45' . substr($d['dni'], -6, 2) . '-' . substr($d['dni'], -4),
                'deporte_id'       => $dep[$d['dep']]->id,
                'grupo_id'         => $grp[$d['grp']]->id,
                'fecha_alta'       => $d['alta'],
                'activo'           => $d['activo'],
            ]);

            if ($d['activo']) {
                DB::table('alumno_planes')->insert([
                    'alumno_id'   => $alumno->id,
                    'plan_id'     => $pla[$d['plan']]->id,
                    'fecha_desde' => $d['alta'],
                    'fecha_hasta' => null,
                    'activo'      => true,
                    'created_at'  => $this->now,
                    'updated_at'  => $this->now,
                ]);

                $result[] = [
                    'alumno'    => $alumno,
                    'precio'    => (float) $pla[$d['plan']]->precio_mensual,
                    'grupo_key' => $d['grp'],
                ];
            }
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 4 — CLASES
    // ─────────────────────────────────────────────────────────────────────────

    private function fase4Clases(array $grp, array $prof): array
    {
        $cfg = [
            'A' => [Carbon::MONDAY,    '17:00', 'patin_princ',  'mitre'],
            'B' => [Carbon::MONDAY,    '17:00', 'patin_inter',  'lopez'],
            'C' => [Carbon::MONDAY,    '18:00', 'patin_avan',   'rodriguez'],
            'D' => [Carbon::MONDAY,    '18:00', 'futbol_princ', 'garcia'],
            'E' => [Carbon::MONDAY,    '19:00', 'patin_princ',  'fernandez'],
            'F' => [Carbon::TUESDAY,   '17:00', 'futbol_princ', 'martinez'],
            'G' => [Carbon::TUESDAY,   '17:00', 'patin_inter',  'lopez'],
            'H' => [Carbon::TUESDAY,   '18:00', 'futbol_avan',  'garcia'],
            'I' => [Carbon::TUESDAY,   '18:00', 'patin_avan',   'fernandez'],
            'J' => [Carbon::WEDNESDAY, '17:00', 'patin_princ',  'mitre'],
            'K' => [Carbon::WEDNESDAY, '17:00', 'futbol_avan',  'martinez'],
            'L' => [Carbon::WEDNESDAY, '18:00', 'patin_inter',  'rodriguez'],
            'M' => [Carbon::WEDNESDAY, '19:00', 'patin_avan',   'lopez'],
            'N' => [Carbon::THURSDAY,  '17:00', 'futbol_princ', 'garcia'],
            'O' => [Carbon::THURSDAY,  '17:00', 'patin_princ',  'fernandez'],
            'P' => [Carbon::THURSDAY,  '18:00', 'futbol_avan',  'martinez'],
            'Q' => [Carbon::FRIDAY,    '17:00', 'patin_avan',   'mitre'],
            'R' => [Carbon::FRIDAY,    '17:00', 'patin_inter',  'rodriguez'],
            'S' => [Carbon::FRIDAY,    '18:00', 'futbol_princ', 'garcia'],
            'T' => [Carbon::FRIDAY,    '19:00', 'patin_princ',  'lopez'],
            'U' => [Carbon::SATURDAY,  '10:00', 'patin_princ',  'mitre'],
            'V' => [Carbon::SATURDAY,  '10:00', 'patin_avan',   'rodriguez'],
        ];

        $uuids  = [];
        $porDia = [];
        $sd     = [];

        foreach ($cfg as $l => [$dia, , $gk, $pk]) {
            $uuids[$l]  = (string) Str::uuid();
            $porDia[$dia][] = $l;
            $sd[$l] = [
                'grupo_id'  => $grp[$gk]->id,
                'grupo_key' => $gk,
                'prof_id'   => $prof[$pk]->id,
                'prof_key'  => $pk,
                'uuid'      => $uuids[$l],
                'clases'    => [],
            ];
        }

        $fecha = $this->inicioClases->copy();
        while ($fecha->lte($this->finClases)) {
            foreach ($porDia[$fecha->dayOfWeek] ?? [] as $l) {
                [, $hora, $gk, $pk] = $cfg[$l];
                $clase = Clase::create([
                    'serie_id'                  => $uuids[$l],
                    'grupo_id'                  => $grp[$gk]->id,
                    'fecha'                     => $fecha->toDateString(),
                    'hora_inicio'               => $hora . ':00',
                    'hora_fin'                  => Carbon::parse($hora)->addHour()->format('H:i:s'),
                    'validada_para_liquidacion' => false,
                    'cancelada'                 => false,
                ]);
                $clase->profesores()->attach($prof[$pk]->id);
                $sd[$l]['clases'][] = ['id' => $clase->id, 'fecha' => $fecha->copy(), 'grupo_id' => $grp[$gk]->id];
            }
            $fecha->addDay();
        }
        return $sd;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 5 — ASISTENCIAS
    // ─────────────────────────────────────────────────────────────────────────

    private function fase5Asistencias(array $sd): void
    {
        $sinJunio = ['C', 'H'];
        $alumPorGrupo = [];
        $usados = [];
        $rows   = [];

        foreach ($sd as $l => $data) {
            $gid = $data['grupo_id'];
            if (!isset($alumPorGrupo[$gid])) {
                $alumPorGrupo[$gid] = Alumno::where('grupo_id', $gid)
                    ->where('activo', true)->pluck('id')->toArray();
            }

            foreach ($data['clases'] as $c) {
                $f = $c['fecha'];
                if ($f->gt($this->hoy)) continue;

                // Sin asistencias: series C y H en junio 2026
                if (in_array($l, $sinJunio) && $f->year === 2026 && $f->month === 6) continue;

                $ds = $f->toDateString();
                foreach ($alumPorGrupo[$gid] as $aid) {
                    if (isset($usados[$aid][$ds])) continue;
                    $usados[$aid][$ds] = true;
                    // 92% presente: determinista — alumno_id % 12 === 0 → ausente
                    $presente = ($aid % 12 !== 0);
                    $rows[] = [
                        'clase_id'   => $c['id'],
                        'alumno_id'  => $aid,
                        'presente'   => $presente,
                        'created_at' => $this->now,
                        'updated_at' => $this->now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('asistencias')->insert($chunk);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 6 — DEUDAS Y PAGOS CON CAJAS OPERATIVAS
    // ─────────────────────────────────────────────────────────────────────────

    private function fase6DeudasPagos(array $alums, array $tipos): void
    {
        $operativo = User::where('rol', 'OPERATIVO')->first();
        $admin     = User::where('rol', 'ADMIN')->first();
        $subCuota  = Subrubro::where('nombre', 'Cuota Mensual')->first();
        $tipoEft   = $tipos['eft'];

        if (!$operativo || !$subCuota || !$tipoEft) return;

        $periodos = ['2026-01', '2026-02', '2026-03', '2026-04', '2026-05'];
        $total    = count($alums);

        foreach ($alums as $i => $alumData) {
            $alumno = $alumData['alumno'];
            $precio = $alumData['precio'];

            if ($i < (int) ($total * 0.60))      $pendientes = [];
            elseif ($i < (int) ($total * 0.80))  $pendientes = ['2026-05'];
            elseif ($i < (int) ($total * 0.95))  $pendientes = ['2026-04', '2026-05'];
            else                                   $pendientes = ['2026-02', '2026-03', '2026-04', '2026-05'];

            foreach ($periodos as $periodo) {
                $isPaid = !in_array($periodo, $pendientes);
                [$y, $m] = explode('-', $periodo);

                $deudaId = DB::table('deuda_cuotas')->insertGetId([
                    'alumno_id'      => $alumno->id,
                    'periodo'        => $periodo,
                    'monto_original' => $precio,
                    'monto_pagado'   => $isPaid ? $precio : 0,
                    'estado'         => $isPaid ? 'PAGADA' : 'PENDIENTE',
                    'created_at'     => $this->now,
                    'updated_at'     => $this->now,
                ]);

                if (!$isPaid) continue;

                $day       = 5 + ($alumno->id % 16);
                $fechaPago = Carbon::create((int) $y, (int) $m, $day)->toDateString();

                [$caja, $cajaKey] = $this->getOrCreateCaja($operativo->id, $fechaPago, $tipoEft->id, $admin?->id);

                $pagoId = DB::table('pagos')->insertGetId([
                    'alumno_id'           => $alumno->id,
                    'mes'                 => (int) $m,
                    'anio'                => (int) $y,
                    'monto_base'          => $precio,
                    'porcentaje_aplicado' => 100,
                    'monto_final'         => $precio,
                    'fecha_pago'          => $fechaPago,
                    'observaciones'       => "Cuota {$periodo}",
                    'estado'              => 'COMPLETADO',
                    'created_at'          => $this->now,
                    'updated_at'          => $this->now,
                ]);

                DB::table('pago_deuda_cuota')->insert([
                    'pago_id'        => $pagoId,
                    'deuda_cuota_id' => $deudaId,
                    'monto_aplicado' => $precio,
                    'created_at'     => $this->now,
                    'updated_at'     => $this->now,
                ]);

                DB::table('movimientos_operativos')->insert([
                    'caja_operativa_id' => $caja->id,
                    'fecha'             => $fechaPago,
                    'tipo_caja_id'      => $tipoEft->id,
                    'subrubro_id'       => $subCuota->id,
                    'monto'             => $precio,
                    'usuario_id'        => $operativo->id,
                    'alumno_id'         => $alumno->id,
                    'pago_id'           => $pagoId,
                    'estado'            => 'ACTIVO',
                    'created_at'        => $this->now,
                    'updated_at'        => $this->now,
                ]);

                $this->cajasCache[$cajaKey]['mov_count']++;
            }
        }
    }

    private function getOrCreateCaja(int $opId, string $fecha, int $tipoId, ?int $adminId): array
    {
        for ($n = 1; $n <= 99; $n++) {
            $key = "{$opId}:{$fecha}:{$n}";
            if (!isset($this->cajasCache[$key])) {
                $caja = CajaOperativa::create([
                    'usuario_operativo_id'       => $opId,
                    'apertura_at'                => "{$fecha} 09:00:00",
                    'cierre_at'                  => "{$fecha} 18:00:00",
                    'estado'                     => 'VALIDADA',
                    'cerrada_por_admin'          => false,
                    'usuario_admin_validacion_id' => $adminId,
                    'validada_at'                => "{$fecha} 18:30:00",
                ]);
                $this->cajasCache[$key] = ['caja_id' => $caja->id, 'caja' => $caja, 'mov_count' => 0];
                return [$caja, $key];
            }
            if ($this->cajasCache[$key]['mov_count'] < 15) {
                return [$this->cajasCache[$key]['caja'], $key];
            }
        }
        return [$this->cajasCache["{$opId}:{$fecha}:1"]['caja'], "{$opId}:{$fecha}:1"];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 7 — CASHFLOW DESDE CAJAS
    // ─────────────────────────────────────────────────────────────────────────

    private function fase7CashflowCajas(): void
    {
        $admin = User::where('rol', 'ADMIN')->first();
        if (!$admin || empty($this->cajasCache)) return;

        $cajaIds = array_unique(array_column($this->cajasCache, 'caja_id'));

        $movimientos = DB::table('movimientos_operativos')
            ->whereIn('caja_operativa_id', $cajaIds)
            ->get();

        $existentes = DB::table('cashflow_movimientos')
            ->where('referencia_tipo', 'MOVIMIENTO_OPERATIVO')
            ->pluck('referencia_id')
            ->toArray();

        $rows = [];
        foreach ($movimientos as $mov) {
            if (in_array($mov->id, $existentes)) continue;
            $rows[] = [
                'fecha'            => $mov->fecha,
                'subrubro_id'      => $mov->subrubro_id,
                'tipo_caja_id'     => $mov->tipo_caja_id,
                'monto'            => $mov->monto,
                'observaciones'    => "Integración caja - mov #{$mov->id}",
                'usuario_admin_id' => $admin->id,
                'referencia_tipo'  => 'MOVIMIENTO_OPERATIVO',
                'referencia_id'    => $mov->id,
                'created_at'       => $this->now,
                'updated_at'       => $this->now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) DB::table('cashflow_movimientos')->insert($chunk);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 8 — CASHFLOW DIRECTO (admin)
    // ─────────────────────────────────────────────────────────────────────────

    private function fase8CashflowDirecto(array $tipos): void
    {
        $admin = User::where('rol', 'ADMIN')->first();
        if (!$admin) return;

        $subAlquiler    = Subrubro::where('nombre', 'Alquiler')->first();
        $subLuz         = Subrubro::where('nombre', 'Luz')->first();
        $subSueldoMitre = Subrubro::where('nombre', 'Sueldo - Mitre, Jorge')->first();
        $subIntereses   = Subrubro::where('nombre', 'Intereses Mercado Pago')->first();

        if (!$subAlquiler || !$subLuz || !$subSueldoMitre || !$subIntereses) return;

        $rows = [];
        for ($mes = 1; $mes <= 5; $mes++) {
            $mm = str_pad($mes, 2, '0', STR_PAD_LEFT);
            $rows[] = ['fecha' => "2026-{$mm}-01", 'subrubro_id' => $subAlquiler->id,    'tipo_caja_id' => $tipos['bga']->id, 'monto' => 180000, 'observaciones' => "Alquiler {$mm}/2026",          'usuario_admin_id' => $admin->id, 'referencia_tipo' => null, 'referencia_id' => null, 'created_at' => $this->now, 'updated_at' => $this->now];
            $rows[] = ['fecha' => "2026-{$mm}-15", 'subrubro_id' => $subLuz->id,         'tipo_caja_id' => $tipos['bna']->id, 'monto' => 22000,  'observaciones' => "Luz {$mm}/2026",               'usuario_admin_id' => $admin->id, 'referencia_tipo' => null, 'referencia_id' => null, 'created_at' => $this->now, 'updated_at' => $this->now];
            $rows[] = ['fecha' => "2026-{$mm}-05", 'subrubro_id' => $subSueldoMitre->id, 'tipo_caja_id' => $tipos['bga']->id, 'monto' => 120000, 'observaciones' => "Sueldo Mitre {$mm}/2026",      'usuario_admin_id' => $admin->id, 'referencia_tipo' => null, 'referencia_id' => null, 'created_at' => $this->now, 'updated_at' => $this->now];
        }
        foreach ([2 => 6800, 4 => 7200] as $mes => $monto) {
            $mm = str_pad($mes, 2, '0', STR_PAD_LEFT);
            $rows[] = ['fecha' => "2026-{$mm}-28", 'subrubro_id' => $subIntereses->id, 'tipo_caja_id' => $tipos['mp']->id, 'monto' => $monto, 'observaciones' => "Intereses MP {$mm}/2026", 'usuario_admin_id' => $admin->id, 'referencia_tipo' => null, 'referencia_id' => null, 'created_at' => $this->now, 'updated_at' => $this->now];
        }
        DB::table('cashflow_movimientos')->insert($rows);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 9 — LIQUIDACIONES
    // ─────────────────────────────────────────────────────────────────────────

    private function fase9Liquidaciones(array $prof, array $sd, array $tipos): void
    {
        $seriesPorProf = [
            'mitre'     => ['A', 'J', 'U', 'Q'],
            'lopez'     => ['B', 'G', 'M', 'T'],
            'rodriguez' => ['C', 'L', 'R', 'V'],
            'fernandez' => ['E', 'I', 'O'],
            'garcia'    => ['D', 'H', 'N', 'S'],
            'martinez'  => ['F', 'K', 'P'],
        ];

        $admin   = User::where('rol', 'ADMIN')->first();
        $tipoEft = $tipos['eft'];

        foreach ([3 => 2026, 4 => 2026] as $mes => $anio) {
            $esPagada = ($mes === 3);

            foreach ($prof as $pk => $profesor) {
                $letras = $seriesPorProf[$pk] ?? [];
                $esHora = $profesor->liquidaPorHora();

                if ($esHora) {
                    $clasesLiquidables = [];
                    foreach ($letras as $l) {
                        foreach ($sd[$l]['clases'] ?? [] as $c) {
                            if ($c['fecha']->year !== $anio || $c['fecha']->month !== $mes) continue;
                            if (DB::table('asistencias')->where('clase_id', $c['id'])->where('presente', true)->exists()) {
                                $clasesLiquidables[] = $c;
                            }
                        }
                    }
                    if (empty($clasesLiquidables)) continue;

                    $totalCalc = count($clasesLiquidables) * (float) $profesor->valor_hora;
                    $liq = $this->crearLiquidacion($profesor->id, $mes, $anio, 'HORA', $totalCalc);

                    $detalles = [];
                    foreach ($clasesLiquidables as $c) {
                        $detalles[] = ['liquidacion_id' => $liq->id, 'tipo_referencia' => 'clase', 'referencia_id' => $c['id'], 'monto' => (float) $profesor->valor_hora, 'descripcion' => "Clase {$c['fecha']->toDateString()}", 'created_at' => $this->now, 'updated_at' => $this->now];
                    }
                    DB::table('liquidacion_detalles')->insert($detalles);

                } else {
                    $grupoIds = array_unique(array_filter(array_map(fn($l) => $sd[$l]['grupo_id'] ?? null, $letras)));
                    $pct      = (float) $profesor->porcentaje_comision / 100;
                    $comAlums = [];

                    foreach ($grupoIds as $gid) {
                        foreach (Alumno::where('grupo_id', $gid)->where('activo', true)->get() as $al) {
                            $tienePago = DB::table('pagos')->where('alumno_id', $al->id)->where('mes', $mes)->where('anio', $anio)->exists();
                            if (!$tienePago) continue;

                            $tieneAsist = DB::table('asistencias')
                                ->join('clases', 'clases.id', '=', 'asistencias.clase_id')
                                ->where('asistencias.alumno_id', $al->id)
                                ->where('asistencias.presente', true)
                                ->whereYear('clases.fecha', $anio)
                                ->whereMonth('clases.fecha', $mes)
                                ->exists();
                            if (!$tieneAsist) continue;

                            $precio = (float) (DB::table('alumno_planes')
                                ->join('grupo_planes', 'alumno_planes.plan_id', '=', 'grupo_planes.id')
                                ->where('alumno_planes.alumno_id', $al->id)
                                ->where('alumno_planes.activo', true)
                                ->value('grupo_planes.precio_mensual') ?? 25000);

                            $comAlums[] = ['alumno_id' => $al->id, 'precio' => $precio];
                        }
                    }
                    if (empty($comAlums)) continue;

                    $totalCalc = array_sum(array_column($comAlums, 'precio')) * $pct;
                    $liq = $this->crearLiquidacion($profesor->id, $mes, $anio, 'COMISION', $totalCalc);

                    $detalles = [];
                    foreach ($comAlums as $ca) {
                        $detalles[] = ['liquidacion_id' => $liq->id, 'tipo_referencia' => 'alumno', 'referencia_id' => $ca['alumno_id'], 'monto' => round($ca['precio'] * $pct, 2), 'descripcion' => "Comisión alumno #{$ca['alumno_id']}", 'created_at' => $this->now, 'updated_at' => $this->now];
                    }
                    DB::table('liquidacion_detalles')->insert($detalles);
                }

                // Cerrar
                $liq->estado = 'CERRADA';
                $liq->save();

                // Pagar (marzo)
                if ($esPagada) {
                    $liq->estado_pago         = 'PAGADA';
                    $liq->pagada_at           = Carbon::create($anio, $mes + 1, 10);
                    $liq->pagada_fecha        = Carbon::create($anio, $mes + 1, 10)->toDateString();
                    $liq->pagada_tipo_caja_id = $tipoEft->id;
                    $liq->pagada_por_admin_id = $admin?->id;
                    $liq->save();
                }
            }
        }
    }

    private function crearLiquidacion(int $profId, int $mes, int $anio, string $tipo, float $total): Liquidacion
    {
        return Liquidacion::create([
            'profesor_id'     => $profId,
            'mes'             => $mes,
            'anio'            => $anio,
            'tipo'            => $tipo,
            'total_calculado' => $total,
            'estado'          => 'ABIERTA',
            'estado_pago'     => 'PENDIENTE',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FASE 10 — DUMP
    // ─────────────────────────────────────────────────────────────────────────

    private function fase10Dump(): void
    {
        $out = base_path('database/dump.sql');
        exec("\"C:/xampp/mysql/bin/mysqldump.exe\" -u root gestion_wings > \"{$out}\"");
    }
}
