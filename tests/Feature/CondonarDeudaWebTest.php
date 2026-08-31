<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CondonarDeudaWebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private Alumno $alumno;
    private DeudaCuota $deuda;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-20 10:00:00');
        Configuracion::set('dias_gracia_cobranza', 10);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Condonación']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->alumno = Alumno::create([
            'nombre' => 'Alumno',
            'apellido' => 'Con historia',
            'dni' => '30999888',
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-01-01',
            'activo' => true,
        ]);
        Pago::create([
            'alumno_id' => $this->alumno->id,
            'mes' => 6,
            'anio' => 2026,
            'monto_base' => 20000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 20000,
            'fecha_pago' => '2026-06-05',
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);
        $this->deuda = DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-07',
            'monto_original' => 28000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_condona_desde_web_sin_generar_movimientos_y_sale_de_deudores(): void
    {
        $motivo = 'La familia solicitó una excepción este mes.';

        $this->actingAs($this->admin)
            ->get(route('web.alumnos.show', $this->alumno->id))
            ->assertOk()
            ->assertSee('Condonar')
            ->assertSee('modal-condonar');

        $this->actingAs($this->admin)
            ->from(route('web.alumnos.show', $this->alumno->id))
            ->post(route('web.deudas.condonar', $this->deuda->id), ['motivo' => $motivo])
            ->assertRedirect(route('web.alumnos.show', $this->alumno->id))
            ->assertSessionHas('success');

        $this->deuda->refresh();
        $this->assertSame(DeudaCuota::ESTADO_CONDONADA, $this->deuda->estado);
        $this->assertStringContainsString($motivo, $this->deuda->observaciones);
        $this->assertStringContainsString("admin ID:{$this->admin->id}", $this->deuda->observaciones);
        $this->assertDatabaseCount('movimientos_operativos', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
        $this->assertSame(
            CobranzaEstadoService::ESTADO_AL_DIA,
            app(CobranzaEstadoService::class)->estadoAlumno($this->alumno->id)['estado']
        );
        $this->assertFalse(
            app(CobranzaEstadoService::class)
                ->filtrarAlumnosPorEstado(CobranzaEstadoService::ESTADO_DEUDOR)
                ->contains('id', $this->alumno->id)
        );
    }

    public function test_operativo_no_puede_condonar_aunque_arme_el_pedido(): void
    {
        $estadoOriginal = $this->deuda->estado;
        $observacionesOriginales = $this->deuda->observaciones;

        $this->actingAs($this->operativo)
            ->get(route('web.alumnos.show', $this->alumno->id))
            ->assertOk()
            ->assertDontSee('Condonar');

        $this->actingAs($this->operativo)
            ->post(route('web.deudas.condonar', $this->deuda->id), [
                'motivo' => 'Intento manual del operativo.',
            ]);

        $this->deuda->refresh();
        $this->assertSame($estadoOriginal, $this->deuda->estado);
        $this->assertSame($observacionesOriginales, $this->deuda->observaciones);
    }

    public function test_deuda_pagada_muestra_error_y_no_se_modifica(): void
    {
        $this->deuda->update([
            'monto_pagado' => 28000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        $this->actingAs($this->admin)
            ->from(route('web.alumnos.show', $this->alumno->id))
            ->post(route('web.deudas.condonar', $this->deuda->id), [
                'motivo' => 'Intento sobre una deuda ya pagada.',
            ])
            ->assertRedirect(route('web.alumnos.show', $this->alumno->id))
            ->assertSessionHas('error', 'Solo se pueden condonar deudas con estado PENDIENTE. Estado actual: PAGADA');

        $this->assertDatabaseHas('deuda_cuotas', [
            'id' => $this->deuda->id,
            'estado' => DeudaCuota::ESTADO_PAGADA,
            'observaciones' => null,
        ]);
    }

    public function test_servicio_rechaza_motivos_fuera_del_rango_antes_de_tocar_la_deuda(): void
    {
        foreach (['   ', 'Muy corto', str_repeat('a', 501)] as $motivoInvalido) {
            try {
                app(PagoCuotaService::class)->condonarDeuda($this->deuda->id, $motivoInvalido, $this->admin->id);
                $this->fail('El servicio aceptó un motivo fuera del rango permitido.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertSame('El motivo de la condonación debe tener entre 10 y 500 caracteres.', $exception->getMessage());
            }
        }

        $this->assertDatabaseHas('deuda_cuotas', [
            'id' => $this->deuda->id,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
            'observaciones' => null,
        ]);
    }

    public function test_alumno_sin_pagos_sigue_deudor_aunque_su_deuda_se_condone(): void
    {
        Pago::where('alumno_id', $this->alumno->id)->delete();

        app(PagoCuotaService::class)->condonarDeuda(
            $this->deuda->id,
            'Excepción documentada para comprobar el contrato.',
            $this->admin->id
        );

        $this->assertSame(
            CobranzaEstadoService::ESTADO_DEUDOR,
            app(CobranzaEstadoService::class)->estadoAlumno($this->alumno->id)['estado']
        );
    }
}
