<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoRevisionCobranza;
use App\Models\CajaOperativa;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\AvisoAdminService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ENT-06: Resumen diario de avisos al ADMIN a las 08:00.
 *
 * Un aviso por día al admin con lo que tiene pendiente:
 * 1. Cajas cerradas sin validar (cantidad, monto total y la más vieja con quién y fecha).
 * 2. Revisiones de cobranza pendientes (cantidad y la más vieja).
 * 3. Liquidaciones cerradas sin pagar (cantidad y total a pagar) y abiertas (aparte, no se suman).
 *
 * Si no hay nada pendiente, no se envía nada.
 */
class AvisoAdminResumenDiarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $tipoCaja;
    private Rubro $rubroIngreso;
    private Rubro $rubroEgreso;
    private Subrubro $subrubroIngreso;
    private Subrubro $subrubroEgreso;
    private Deporte $deporte;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.bot_token', 'token-de-prueba');
        Configuracion::set('avisos_telegram_chat_id', '12345');

        $this->admin = User::factory()->create([
            'rol'    => User::ROL_ADMIN,
            'activo' => true,
            'email'  => 'admin@wings.test',
        ]);

        $this->operativo = User::factory()->create([
            'rol'    => User::ROL_OPERATIVO,
            'activo' => true,
            'name'   => 'Sandra Vidal',
        ]);

        $this->tipoCaja = TipoCaja::create([
            'nombre' => 'Efectivo',
            'activo' => true,
        ]);

        $this->rubroIngreso = Rubro::create([
            'nombre' => 'Cuotas',
            'tipo'   => 'INGRESO',
            'activo' => true,
        ]);
        $this->subrubroIngreso = Subrubro::create([
            'rubro_id' => $this->rubroIngreso->id,
            'nombre'   => 'Cuota Social',
            'activo'   => true,
        ]);

        $this->rubroEgreso = Rubro::create([
            'nombre' => 'Gastos',
            'tipo'   => 'EGRESO',
            'activo' => true,
        ]);
        $this->subrubroEgreso = Subrubro::create([
            'rubro_id' => $this->rubroEgreso->id,
            'nombre'   => 'Insumos',
            'activo'   => true,
        ]);

        $this->deporte = Deporte::create([
            'nombre'           => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo'           => true,
        ]);
    }

    private function crearAlumno(string $nombre = 'Sofía', string $apellido = 'Morales'): Alumno
    {
        static $dni = 40000000;
        $dni++;

        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);
        $grupo = Grupo::firstOrCreate([
            'deporte_id' => $this->deporte->id,
            'nivel_id'   => $nivel->id,
        ], ['activo' => true]);

        return Alumno::create([
            'nombre'           => $nombre,
            'apellido'         => $apellido,
            'dni'              => (string) $dni,
            'fecha_nacimiento' => '2012-05-10',
            'celular'          => '1122334455',
            'deporte_id'       => $this->deporte->id,
            'grupo_id'         => $grupo->id,
            'fecha_alta'       => '2026-03-01',
            'activo'           => true,
        ]);
    }

    private function crearProfesor(string $nombre = 'Carlos', string $apellido = 'Docente'): Profesor
    {
        static $dniProf = 30000000;
        $dniProf++;

        return Profesor::create([
            'deporte_id'       => $this->deporte->id,
            'nombre'           => $nombre,
            'apellido'         => $apellido,
            'dni'              => (string) $dniProf,
            'fecha_nacimiento' => '1990-01-01',
            'direccion'        => 'Calle Falsa 123',
            'localidad'        => 'Buenos Aires',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'valor_hora'       => 5000,
            'activo'           => true,
        ]);
    }

    public function test_sin_nada_pendiente_no_se_envia_nada(): void
    {
        Http::fake();

        $enviado = app(AvisoAdminService::class)->resumenDiario();

        $this->assertFalse($enviado);
        Http::assertNothingSent();

        $this->artisan('avisos:resumen-diario')
            ->expectsOutput('Sin pendientes: no se envió ningún aviso.')
            ->assertSuccessful();
    }

    public function test_con_caja_revision_y_liquidaciones_sale_un_solo_aviso_con_numeros_correctos(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        // 1. Caja cerrada sin validar con neto $8.000
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at'          => '2026-09-15 08:00:00',
            'cierre_at'            => '2026-09-15 20:00:00',
            'estado'               => CajaOperativa::ESTADO_CERRADA,
        ]);

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha'             => '2026-09-15',
            'tipo_caja_id'      => $this->tipoCaja->id,
            'subrubro_id'       => $this->subrubroIngreso->id,
            'monto'             => 10000,
            'usuario_id'        => $this->operativo->id,
            'estado'            => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha'             => '2026-09-15',
            'tipo_caja_id'      => $this->tipoCaja->id,
            'subrubro_id'       => $this->subrubroEgreso->id,
            'monto'             => 2000,
            'usuario_id'        => $this->operativo->id,
            'estado'            => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // 2. Revisión de cobranza pendiente
        $alumno = $this->crearAlumno('Sofía', 'Morales');

        $rev = AlumnoRevisionCobranza::create([
            'alumno_id'        => $alumno->id,
            'periodo_objetivo' => '2026-09',
            'motivo'           => 'Sin asistencias el mes anterior',
            'estado_revision'  => AlumnoRevisionCobranza::ESTADO_PENDIENTE,
        ]);
        $rev->created_at = '2026-09-10 10:00:00';
        $rev->saveQuietly();

        // 3. Liquidación cerrada sin pagar ($50.000)
        $profesor = $this->crearProfesor('Carlos', 'Docente');
        Liquidacion::create([
            'profesor_id'                  => $profesor->id,
            'mes'                          => 9,
            'anio'                         => 2026,
            'tipo'                         => Liquidacion::TIPO_HORA,
            'valor_hora_aplicado'          => 5000,
            'porcentaje_comision_aplicado' => 0,
            'total_calculado'              => 50000,
            'estado'                       => Liquidacion::ESTADO_CERRADA,
            'estado_pago'                  => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        // 4. Liquidación abierta ($30.000)
        $profesor2 = $this->crearProfesor('Laura', 'Profe');
        Liquidacion::create([
            'profesor_id'                  => $profesor2->id,
            'mes'                          => 9,
            'anio'                         => 2026,
            'tipo'                         => Liquidacion::TIPO_HORA,
            'valor_hora_aplicado'          => 5000,
            'porcentaje_comision_aplicado' => 0,
            'total_calculado'              => 30000,
            'estado'                       => Liquidacion::ESTADO_ABIERTA,
            'estado_pago'                  => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        $enviado = app(AvisoAdminService::class)->resumenDiario();

        $this->assertTrue($enviado);

        // Se envía un solo mensaje por Telegram
        Http::assertSent(function ($peticion) {
            $texto = $peticion->data()['text'];

            return str_contains($peticion->url(), 'api.telegram.org/bottoken-de-prueba/sendMessage')
                && str_contains($texto, 'Wings: resumen diario de pendientes')
                // Cajas
                && str_contains($texto, 'Cajas cerradas sin validar')
                && str_contains($texto, '8.000')
                && str_contains($texto, 'Sandra Vidal')
                && str_contains($texto, '15/09/2026')
                && str_contains($texto, route('web.caja.index'))
                // Revisiones
                && str_contains($texto, 'Revisiones de cobranza pendientes')
                && str_contains($texto, 'Morales')
                && str_contains($texto, '10/09/2026')
                && str_contains($texto, route('web.revision-cobranza.index'))
                // Liquidaciones
                && str_contains($texto, 'Liquidaciones cerradas sin pagar')
                && str_contains($texto, '50.000')
                && str_contains($texto, 'Liquidaciones abiertas')
                && str_contains($texto, route('web.liquidaciones.index'));
        });
    }

    public function test_una_liquidacion_abierta_no_se_suma_al_total_a_pagar(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $profesor = $this->crearProfesor('Esteban', 'Entrenador');

        // Cerrada sin pagar: $100.000
        Liquidacion::create([
            'profesor_id'                  => $profesor->id,
            'mes'                          => 8,
            'anio'                         => 2026,
            'tipo'                         => Liquidacion::TIPO_HORA,
            'valor_hora_aplicado'          => 5000,
            'porcentaje_comision_aplicado' => 0,
            'total_calculado'              => 100000,
            'estado'                       => Liquidacion::ESTADO_CERRADA,
            'estado_pago'                  => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        // Abiertas: $40.000 cada una (no deben sumarse al total a pagar)
        Liquidacion::create([
            'profesor_id'                  => $profesor->id,
            'mes'                          => 9,
            'anio'                         => 2026,
            'tipo'                         => Liquidacion::TIPO_HORA,
            'valor_hora_aplicado'          => 5000,
            'porcentaje_comision_aplicado' => 0,
            'total_calculado'              => 40000,
            'estado'                       => Liquidacion::ESTADO_ABIERTA,
            'estado_pago'                  => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        Liquidacion::create([
            'profesor_id'                  => $profesor->id,
            'mes'                          => 9,
            'anio'                         => 2026,
            'tipo'                         => Liquidacion::TIPO_COMISION,
            'valor_hora_aplicado'          => 0,
            'porcentaje_comision_aplicado' => 50,
            'total_calculado'              => 40000,
            'estado'                       => Liquidacion::ESTADO_ABIERTA,
            'estado_pago'                  => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        $enviado = app(AvisoAdminService::class)->resumenDiario();

        $this->assertTrue($enviado);

        Http::assertSent(function ($peticion) {
            $texto = $peticion->data()['text'];

            // El total a pagar debe ser exactamente 100.000 y no 180.000
            return str_contains($texto, '100.000')
                && ! str_contains($texto, '180.000')
                && str_contains($texto, 'Liquidaciones abiertas');
        });
    }

    public function test_las_cajas_validadas_revisiones_resueltas_y_liquidaciones_pagadas_no_aparecen(): void
    {
        Http::fake();

        // 1. Caja validada
        CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at'          => '2026-09-15 08:00:00',
            'cierre_at'            => '2026-09-15 20:00:00',
            'validada_at'          => '2026-09-16 09:00:00',
            'estado'               => CajaOperativa::ESTADO_VALIDADA,
        ]);

        // 2. Revisión resuelta
        $alumno = $this->crearAlumno('Lucas', 'Ríos');

        AlumnoRevisionCobranza::create([
            'alumno_id'             => $alumno->id,
            'periodo_objetivo'      => '2026-09',
            'motivo'                => 'Sin asistencias',
            'estado_revision'       => AlumnoRevisionCobranza::ESTADO_RESUELTO,
            'resolucion'            => AlumnoRevisionCobranza::RESOLUCION_CONTINUA,
            'nota_resolucion'       => 'Sigue normalmente',
            'usuario_resolucion_id' => $this->operativo->id,
            'resuelto_at'           => now(),
        ]);

        // 3. Liquidación pagada
        $profesor = $this->crearProfesor('Martín', 'Instructor');
        Liquidacion::create([
            'profesor_id'                  => $profesor->id,
            'mes'                          => 8,
            'anio'                         => 2026,
            'tipo'                         => Liquidacion::TIPO_HORA,
            'valor_hora_aplicado'          => 5000,
            'porcentaje_comision_aplicado' => 0,
            'total_calculado'              => 60000,
            'estado'                       => Liquidacion::ESTADO_CERRADA,
            'estado_pago'                  => Liquidacion::ESTADO_PAGO_PAGADA,
            'pagada_at'                    => now(),
        ]);

        $enviado = app(AvisoAdminService::class)->resumenDiario();

        $this->assertFalse($enviado);
        Http::assertNothingSent();
    }

    public function test_el_comando_queda_programado_a_las_08_00(): void
    {
        $schedule = app(Schedule::class);

        $evento = collect($schedule->events())->first(function (Event $event) {
            return str_contains($event->command, 'avisos:resumen-diario');
        });

        $this->assertNotNull($evento, 'El comando avisos:resumen-diario debe estar registrado en el Scheduler.');
        $this->assertSame('0 8 * * *', $evento->expression, 'El comando debe ejecutarse todos los días a las 08:00.');
    }
}
