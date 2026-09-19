<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\AlumnoRevisionCobranza;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use App\Services\RevisionCobranzaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La cola de revisión existe para una sola pregunta: ¿a este alumno le generamos
 * la deuda del mes o no? Es un proceso hacia adelante.
 *
 * Hasta el 19/09/2026, resolver con INACTIVO además condonaba la deuda pendiente
 * del mes anterior. Esa deuda se había generado porque en su momento cumplía las
 * condiciones, y esa decisión ya estaba tomada: dar de baja al alumno hoy no la
 * vuelve inexistente. Condonar es una decisión aparte del ADMIN, con motivo, para
 * el alumno que no pudo asistir — acá se hacía sola, sin que nadie la pidiera y
 * sin quedar registrada como condonación de nadie.
 */
class RevisionCobranzaNoTocaElPasadoTest extends TestCase
{
    use RefreshDatabase;

    private Alumno $alumno;
    private User $admin;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $this->plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 30000,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Renata',
            'apellido' => 'Cuello',
            'dni' => '44238951',
            'fecha_nacimiento' => '2011-04-02',
            'celular' => '1155667788',
            'email' => 'renata.cuello@wings.test',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-03-01',
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-03-01',
            'activo' => true,
        ]);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
    }

    private function revisionDe(string $periodo): AlumnoRevisionCobranza
    {
        return AlumnoRevisionCobranza::create([
            'alumno_id' => $this->alumno->id,
            'periodo_objetivo' => $periodo,
            'motivo' => 'Sin asistencias el mes anterior',
            'estado_revision' => AlumnoRevisionCobranza::ESTADO_PENDIENTE,
        ]);
    }

    private function deudaDe(string $periodo, string $estado = DeudaCuota::ESTADO_PENDIENTE): DeudaCuota
    {
        return DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => $periodo,
            'monto_original' => 30000,
            'monto_pagado' => 0,
            'saldo_pendiente' => 30000,
            'estado' => $estado,
        ]);
    }

    public function test_marcar_inactivo_no_condona_la_deuda_del_mes_anterior(): void
    {
        // La deuda de agosto se generó en su momento porque correspondía.
        $deudaVieja = $this->deudaDe('2026-08');
        $revision = $this->revisionDe('2026-09');

        app(RevisionCobranzaService::class)
            ->resolver($revision->id, AlumnoRevisionCobranza::RESOLUCION_INACTIVO, null, $this->admin->id);

        $deudaVieja->refresh();

        $this->assertSame(
            DeudaCuota::ESTADO_PENDIENTE,
            $deudaVieja->estado,
            'Dar de baja al alumno no borra una deuda que ya se había generado.',
        );
        $this->assertEquals(30000, $deudaVieja->saldo_pendiente);
    }

    public function test_marcar_inactivo_da_de_baja_y_no_genera_la_deuda_del_periodo(): void
    {
        $revision = $this->revisionDe('2026-09');

        app(RevisionCobranzaService::class)
            ->resolver($revision->id, AlumnoRevisionCobranza::RESOLUCION_INACTIVO, null, $this->admin->id);

        $this->assertFalse((bool) $this->alumno->fresh()->activo);
        $this->assertSame(
            0,
            DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-09')->count(),
            'Al alumno que se da de baja no se le genera la deuda del mes.',
        );
    }

    public function test_marcar_continua_genera_la_deuda_del_periodo(): void
    {
        // Es lo que el proceso vino a hacer: si sigue, se le cobra.
        $revision = $this->revisionDe('2026-09');

        app(RevisionCobranzaService::class)
            ->resolver($revision->id, AlumnoRevisionCobranza::RESOLUCION_CONTINUA, null, $this->admin->id);

        $deuda = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-09')->first();

        $this->assertNotNull($deuda, 'El alumno que continúa tiene que quedar con su deuda del mes.');
        $this->assertEquals(30000, $deuda->monto_original);
        $this->assertTrue((bool) $this->alumno->fresh()->activo);
    }

    public function test_continuar_tampoco_toca_lo_ya_generado(): void
    {
        $deudaVieja = $this->deudaDe('2026-08');
        $revision = $this->revisionDe('2026-09');

        app(RevisionCobranzaService::class)
            ->resolver($revision->id, AlumnoRevisionCobranza::RESOLUCION_CONTINUA, null, $this->admin->id);

        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $deudaVieja->refresh()->estado);
    }

    public function test_una_revision_ya_resuelta_no_se_vuelve_a_resolver(): void
    {
        $revision = $this->revisionDe('2026-09');
        $servicio = app(RevisionCobranzaService::class);

        $servicio->resolver($revision->id, AlumnoRevisionCobranza::RESOLUCION_INACTIVO, null, $this->admin->id);

        $this->expectException(\Exception::class);
        $servicio->resolver($revision->id, AlumnoRevisionCobranza::RESOLUCION_CONTINUA, null, $this->admin->id);
    }
}
