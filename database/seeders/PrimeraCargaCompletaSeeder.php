<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\SubrubroSueldoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Deja la base en el punto exacto en que terminó la primera carga manual
 * del 06/09/2026, sin tener que repetirla por pantalla.
 *
 * Qué reproduce, según `docs/06-pruebas/RESULTADO-PRIMERA-CARGA-V1.md`:
 * dos deportes, cuatro niveles, seis grupos con doce planes, dos tipos de
 * caja con sus saldos iniciales, cuatro profesores con su subrubro de
 * sueldo, siete cuentas de usuario y sesenta alumnos con plan activo.
 *
 * Qué NO crea, porque la primera carga tampoco los tenía: deudas, pagos,
 * cajas, clases, asistencias ni liquidaciones. Ese es justamente el punto
 * de partida de la prueba humana.
 *
 * Uso:
 *
 *     php artisan migrate:fresh
 *     php artisan db:seed --class=PrimeraCargaCompletaSeeder
 *
 * Los once primeros alumnos son los que en septiembre se cargaron a mano.
 * Aquella base (`wings_test`) se perdió, así que no son las mismas personas:
 * son once equivalentes que respetan la distribución que la etapa manual
 * dejó verificada — seis de Patín, cinco de Fútbol, dos altas de 2025, una
 * del primer trimestre y ocho del segundo, más la dupla de Sofía Morales
 * inscripta en los dos deportes con el mismo DNI. `PrimeraCargaAlumnosSeeder`
 * valida esa distribución antes de completar los cuarenta y nueve restantes,
 * así que si algo de esto se toca, falla ahí.
 */
class PrimeraCargaCompletaSeeder extends Seeder
{
    /**
     * Contraseña única de las siete cuentas. Es de prueba y está a la vista
     * a propósito: este seeder aborta en producción, así que nunca llega a
     * una base real. Doce caracteres para respetar el mínimo de SEG-03.
     */
    public const CLAVE_DE_PRUEBA = 'PruebaWings2026';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('PrimeraCargaCompletaSeeder no puede ejecutarse en producción.');
        }

        $this->rechazarBaseConDatos();

        DB::transaction(function (): void {
            $this->call(CatalogosSeeder::class);

            $deportes = $this->deportes();
            $this->crearNivelFederadas();
            $this->ajustarTiposCaja();
            $this->crearGruposYPlanes($deportes);
            $this->crearProfesores($deportes);
            $this->crearUsuarios();
            $this->crearAlumnosManuales($deportes);
            $this->asegurarConfiguraciones();
        });

        // Fuera de la transacción: abre la suya y valida el resultado.
        $this->call(PrimeraCargaAlumnosSeeder::class);

        $this->verificar();
    }

    /**
     * Correrlo sobre una base que ya tiene operación cargada mezclaría datos
     * de prueba con datos de trabajo y dejaría un estado que no es ni uno ni
     * otro. Los catálogos sí pueden estar: `CatalogosSeeder` es idempotente.
     */
    private function rechazarBaseConDatos(): void
    {
        foreach (['alumnos', 'pagos', 'deuda_cuotas', 'cajas_operativas', 'clases', 'liquidaciones'] as $tabla) {
            if (DB::table($tabla)->exists()) {
                throw new LogicException(
                    "La tabla {$tabla} ya tiene filas. Este seeder parte de una base recién migrada: "
                    . 'correr `php artisan migrate:fresh` antes.'
                );
            }
        }
    }

    /** @return array<string, Deporte> */
    private function deportes(): array
    {
        return [
            'Patín'  => Deporte::query()->where('nombre', 'Patín')->sole(),
            'Fútbol' => Deporte::query()->where('nombre', 'Fútbol')->sole(),
        ];
    }

    /**
     * CatalogosSeeder trae tres niveles. El club agregó Federadas durante la
     * carga manual (C06) y Fútbol también lo usa, por decisión de Carlos del
     * 06/09.
     */
    private function crearNivelFederadas(): void
    {
        Nivel::updateOrCreate(['nombre' => 'Federadas'], ['descripcion' => null]);
    }

    /**
     * La primera carga dejó dos tipos de caja con saldo (C15 y C16). Los
     * demás que trae CatalogosSeeder quedan inactivos: no existían en esa
     * base y aparecerían en los selectores de cobro.
     */
    private function ajustarTiposCaja(): void
    {
        TipoCaja::updateOrCreate(['nombre' => 'Efectivo'], [
            'abreviatura'         => 'EFE',
            'descripcion'         => null,
            'permite_descubierto' => false,
            'saldo_inicial'       => 250000,
            'activo'              => true,
        ]);

        TipoCaja::updateOrCreate(['nombre' => 'Mercado Pago'], [
            'abreviatura'         => 'MP',
            'descripcion'         => null,
            'permite_descubierto' => false,
            'saldo_inicial'       => 1320000,
            'activo'              => true,
        ]);

        TipoCaja::query()->whereNotIn('nombre', ['Efectivo', 'Mercado Pago'])->update(['activo' => false]);
    }

    /**
     * Seis grupos y doce planes (C07 a C12). Cada grupo tiene las dos
     * frecuencias que `PrimeraCargaAlumnosSeeder` exige encontrar activas.
     *
     * @param array<string, Deporte> $deportes
     */
    private function crearGruposYPlanes(array $deportes): void
    {
        $definicion = [
            ['Patín',  'Principiantes', 30000, 40000],
            ['Patín',  'Intermedias',   33000, 43000],
            ['Patín',  'Avanzadas',     35000, 45000],
            ['Patín',  'Federadas',     40000, 50000],
            ['Fútbol', 'Principiantes', 28000, 35000],
            ['Fútbol', 'Avanzadas',     38000, 48000],
        ];

        foreach ($definicion as [$deporte, $nivel, $precioUna, $precioDos]) {
            $grupo = Grupo::updateOrCreate(
                [
                    'deporte_id' => $deportes[$deporte]->id,
                    'nivel_id'   => Nivel::query()->where('nombre', $nivel)->sole()->id,
                ],
                ['activo' => true],
            );

            foreach ([1 => $precioUna, 2 => $precioDos] as $frecuencia => $precio) {
                GrupoPlan::updateOrCreate(
                    ['grupo_id' => $grupo->id, 'clases_por_semana' => $frecuencia],
                    ['precio_mensual' => $precio, 'activo' => true],
                );
            }
        }
    }

    /**
     * Tres profesoras de Patín por hora y un profesor de Fútbol por comisión,
     * con los importes que Carlos definió el 06/09 (H03). El subrubro de
     * sueldo lo crea el mismo servicio que usa el alta por pantalla, para que
     * la liquidación lo encuentre por FK.
     *
     * @param array<string, Deporte> $deportes
     */
    private function crearProfesores(array $deportes): void
    {
        $servicio = app(SubrubroSueldoService::class);

        $definicion = [
            ['Lucía',    'Gaitán',   '30481726', '1984-04-11', 'Cerviño 1420',    'Patín',  12000, null],
            ['Verónica', 'Salinas',  '29763018', '1982-09-27', 'Rivadavia 8350',  'Patín',  15000, null],
            ['Mariela',  'Ocampo',   '28914537', '1981-02-15', 'San Martín 245',  'Patín',  18000, null],
            ['Hernán',   'Quintana', '30125849', '1983-07-03', 'Av. Mitre 1877',  'Fútbol', null,  40],
        ];

        foreach ($definicion as [$nombre, $apellido, $dni, $nacimiento, $direccion, $deporte, $valorHora, $comision]) {
            $profesor = Profesor::updateOrCreate(
                ['dni' => $dni],
                [
                    'deporte_id'          => $deportes[$deporte]->id,
                    'nombre'              => $nombre,
                    'apellido'            => $apellido,
                    'fecha_nacimiento'    => $nacimiento,
                    'direccion'           => $direccion,
                    'localidad'           => 'Ciudad de Buenos Aires',
                    'email'               => $this->correo($nombre, $apellido),
                    'telefono'            => $this->telefono($dni),
                    'valor_hora'          => $valorHora,
                    'porcentaje_comision' => $comision,
                    'activo'              => true,
                ],
            );

            $servicio->paraProfesor($profesor);
        }
    }

    /**
     * Un ADMIN, dos OPERATIVO y cuatro PROFESOR vinculados uno a uno a las
     * fichas de profesor (C21 a C26). El subrubro de sueldo del operativo lo
     * crea el mismo servicio que el alta por pantalla.
     */
    private function crearUsuarios(): void
    {
        $servicio = app(SubrubroSueldoService::class);

        $this->crearUsuario('Admin Prueba', 'admin@wings.test', User::ROL_ADMIN);

        foreach (['Sandra Vidal', 'Pablo Ledesma'] as $nombre) {
            $operativo = $this->crearUsuario($nombre, $this->correo(...explode(' ', $nombre)), User::ROL_OPERATIVO);
            $servicio->paraUsuarioOperativo($operativo);
        }

        foreach (Profesor::query()->orderBy('id')->get() as $profesor) {
            $this->crearUsuario(
                $profesor->nombre . ' ' . $profesor->apellido,
                $this->correo($profesor->nombre, $profesor->apellido),
                User::ROL_PROFESOR,
                $profesor->id,
            );
        }
    }

    private function crearUsuario(string $nombre, string $email, string $rol, ?int $profesorId = null): User
    {
        $usuario = User::query()->firstOrNew(['email' => $email]);

        $usuario->name        = $nombre;
        $usuario->password    = self::CLAVE_DE_PRUEBA;
        $usuario->profesor_id = $profesorId;
        $usuario->rol         = $rol;
        $usuario->activo      = true;
        $usuario->save();

        return $usuario;
    }

    /**
     * Los once que la etapa manual dejó cargados por pantalla. Sofía Morales
     * ocupa dos filas: misma persona y mismo DNI en los dos deportes, que es
     * lo que habilita el único compuesto (dni, deporte_id).
     *
     * @param array<string, Deporte> $deportes
     */
    private function crearAlumnosManuales(array $deportes): void
    {
        $definicion = [
            ['Sofía',     'Morales',   '32123456', 'Patín',  'Principiantes', '2025-03-05'],
            ['Sofía',     'Morales',   '32123456', 'Fútbol', 'Principiantes', '2025-09-02'],
            ['Thiago',    'Peralta',   '45329018', 'Fútbol', 'Avanzadas',     '2026-02-10'],
            ['Abril',     'Benegas',   '45012387', 'Patín',  'Intermedias',   '2026-04-14'],
            ['Renata',    'Cuello',    '44238951', 'Patín',  'Avanzadas',     '2026-05-07'],
            ['Julieta',   'Medina',    '44917264', 'Patín',  'Federadas',     '2026-04-25'],
            ['Emilia',    'Sandoval',  '45183726', 'Patín',  'Principiantes', '2026-06-02'],
            ['Guadalupe', 'Rivas',     '44672390', 'Patín',  'Intermedias',   '2026-06-18'],
            ['Bautista',  'Aguirre',   '44851273', 'Fútbol', 'Principiantes', '2026-04-09'],
            ['Lisandro',  'Cabral',    '44760925', 'Fútbol', 'Avanzadas',     '2026-05-21'],
            ['Valentino', 'Ferrari',   '45094618', 'Fútbol', 'Principiantes', '2026-06-12'],
        ];

        foreach ($definicion as $indice => [$nombre, $apellido, $dni, $deporte, $nivel, $fechaAlta]) {
            $grupo = Grupo::query()
                ->where('deporte_id', $deportes[$deporte]->id)
                ->whereHas('nivel', fn ($query) => $query->where('nombre', $nivel))
                ->with(['planes' => fn ($query) => $query->where('activo', true)->orderBy('clases_por_semana')])
                ->sole();

            $alumno = Alumno::query()->create([
                'nombre'           => $nombre,
                'apellido'         => $apellido,
                'dni'              => $dni,
                'fecha_nacimiento' => sprintf('%04d-%02d-%02d', 1990 + ($indice % 15), 1 + ($indice % 12), 2 + ($indice % 26)),
                'celular'          => $this->telefono($dni . $deporte),
                'email'            => $this->correo($nombre, $apellido),
                'deporte_id'       => $deportes[$deporte]->id,
                'grupo_id'         => $grupo->id,
                'fecha_alta'       => $fechaAlta,
                'activo'           => true,
            ]);

            AlumnoPlan::create([
                'alumno_id'   => $alumno->id,
                'plan_id'     => $grupo->planes[$indice % 2]->id,
                'fecha_desde' => $fechaAlta,
                'activo'      => true,
            ]);
        }
    }

    /**
     * Las crea la migración de `configuraciones`; si alguien las borró (pasó
     * en la carga manual, H04) el listado de alumnos devuelve 500 y no hay
     * pantalla para recrearlas.
     */
    private function asegurarConfiguraciones(): void
    {
        Configuracion::updateOrCreate(
            ['clave' => 'dias_gracia_cobranza'],
            [
                'valor'       => '10',
                'tipo'        => 'integer',
                'descripcion' => 'Días del mes durante los cuales una cuota corriente impaga se considera En plazo',
            ],
        );

        Configuracion::updateOrCreate(
            ['clave' => 'dia_generacion_deuda'],
            [
                'valor'       => '1',
                'tipo'        => 'integer',
                'descripcion' => 'Día del mes en que se genera automáticamente la deuda mensual',
            ],
        );
    }

    /**
     * `PrimeraCargaAlumnosSeeder` ya valida los alumnos. Acá se comprueba lo
     * que ese seeder no mira: catálogos, cuentas y que no haya aparecido
     * operación que la primera carga no tenía.
     */
    private function verificar(): void
    {
        $esperado = [
            'niveles'     => 4,
            'grupos'      => 6,
            'planes'      => 12,
            'tipos_caja'  => 2,
            'profesores'  => 4,
            'usuarios'    => 7,
            'alumnos'     => 60,
        ];

        $real = [
            'niveles'     => Nivel::query()->count(),
            'grupos'      => Grupo::query()->where('activo', true)->count(),
            'planes'      => GrupoPlan::query()->where('activo', true)->count(),
            'tipos_caja'  => TipoCaja::query()->where('activo', true)->count(),
            'profesores'  => Profesor::query()->where('activo', true)->count(),
            'usuarios'    => User::query()->where('activo', true)->count(),
            'alumnos'     => Alumno::query()->count(),
        ];

        foreach ($esperado as $que => $cuantos) {
            if ($real[$que] !== $cuantos) {
                throw new LogicException("Se esperaban {$cuantos} {$que} y quedaron {$real[$que]}.");
            }
        }

        if (Profesor::query()->whereNull('subrubro_id')->exists()) {
            throw new LogicException('Hay profesores sin subrubro de sueldo: la liquidación no los va a poder pagar.');
        }

        if (User::query()->where('rol', User::ROL_PROFESOR)->whereNull('profesor_id')->exists()) {
            throw new LogicException('Hay cuentas PROFESOR sin ficha de profesor vinculada.');
        }

        foreach (['pagos', 'deuda_cuotas', 'cajas_operativas', 'clases', 'liquidaciones'] as $tabla) {
            if (DB::table($tabla)->exists()) {
                throw new LogicException("La primera carga no tenía {$tabla}: el seeder dejó filas de más.");
            }
        }

        $this->command?->info('Primera carga reconstruida: 60 alumnos, 6 grupos, 12 planes, 4 profesores, 7 cuentas.');
        $this->command?->info('Clave de todas las cuentas: ' . self::CLAVE_DE_PRUEBA);
    }

    /**
     * `iconv` con TRANSLIT convierte "í" en "'i" según la locale, así que los
     * acentos dejaban correos como "luc.ia.gait.an". Los signos sueltos se
     * descartan antes de armar el slug.
     */
    private function correo(string $nombre, string $apellido): string
    {
        $texto = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre . '.' . $apellido);
        $texto = str_replace(['\'', '`', '^', '~', '"'], '', $texto);
        $slug  = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '.', $texto));

        return trim($slug, '.') . '@wings.test';
    }

    private function telefono(string $semilla): string
    {
        return '11' . str_pad((string) (10000000 + (crc32($semilla) % 89999999)), 8, '0', STR_PAD_LEFT);
    }
}
