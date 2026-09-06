<?php

namespace Tests\Feature;

use App\Models\ReglaPrimerPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las reglas de primer pago dicen qué porcentaje de la cuota paga un alumno nuevo
 * según el día del mes en que se dio de alta.
 *
 * Dos reglas no pueden cubrir el mismo día. Y el motivo no es de prolijidad: cuando
 * dos tramos se pisaban, `ReglaPrimerPago::obtenerReglaPorDia()` devolvía las dos y
 * quien la usa solo aplica la regla si viene exactamente una. O sea que el descuento
 * **desaparecía sin aviso** y nadie podía saber por qué.
 */
class ReglaPrimerPagoSinSuperposicionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'rol'    => User::ROL_ADMIN,
            'activo' => true,
        ]);

        ReglaPrimerPago::create([
            'nombre'     => 'Mes completo',
            'dia_desde'  => 1,
            'dia_hasta'  => 15,
            'porcentaje' => 100,
            'activo'     => true,
        ]);
    }

    public function test_no_se_puede_crear_un_tramo_que_pisa_a_otro(): void
    {
        $respuesta = $this->actingAs($this->admin)->postJson('/configuraciones/primer-pago', [
            'nombre'     => 'Se pisa',
            'dia_desde'  => 10,
            'dia_hasta'  => 20,
            'porcentaje' => 70,
        ]);

        $respuesta->assertStatus(422);

        $this->assertSame(
            1,
            ReglaPrimerPago::count(),
            'El rechazo tiene que dejar la base como estaba. Si quedó la regla creada, '.
            'los días 10 al 15 tienen dos porcentajes y el descuento deja de aplicarse.'
        );
    }

    public function test_un_tramo_que_no_pisa_a_ninguno_se_crea(): void
    {
        $respuesta = $this->actingAs($this->admin)->postJson('/configuraciones/primer-pago', [
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 23,
            'porcentaje' => 70,
        ]);

        $respuesta->assertOk();
        $this->assertSame(2, ReglaPrimerPago::count());
    }

    public function test_tampoco_se_puede_editar_un_tramo_hasta_pisar_a_otro(): void
    {
        $segunda = ReglaPrimerPago::create([
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 23,
            'porcentaje' => 70,
            'activo'     => true,
        ]);

        $respuesta = $this->actingAs($this->admin)->putJson("/configuraciones/primer-pago/{$segunda->id}", [
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 12,
            'dia_hasta'  => 23,
            'porcentaje' => 70,
        ]);

        $respuesta->assertStatus(422);

        $this->assertSame(
            16,
            $segunda->fresh()->dia_desde,
            'El rechazo no puede dejar el cambio aplicado a medias.'
        );
    }

    public function test_editar_un_tramo_sin_pisar_a_nadie_funciona(): void
    {
        $segunda = ReglaPrimerPago::create([
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 23,
            'porcentaje' => 70,
            'activo'     => true,
        ]);

        $respuesta = $this->actingAs($this->admin)->putJson("/configuraciones/primer-pago/{$segunda->id}", [
            'nombre'     => 'Segunda quincena',
            'dia_desde'  => 16,
            'dia_hasta'  => 25,
            'porcentaje' => 60,
        ]);

        $respuesta->assertOk();
        $this->assertSame(25, $segunda->fresh()->dia_hasta);
    }

    public function test_con_los_tres_tramos_cargados_cada_dia_del_mes_tiene_una_sola_regla(): void
    {
        ReglaPrimerPago::create(['nombre' => 'Segunda quincena', 'dia_desde' => 16, 'dia_hasta' => 23, 'porcentaje' => 70, 'activo' => true]);
        ReglaPrimerPago::create(['nombre' => 'Fin de mes', 'dia_desde' => 24, 'dia_hasta' => 31, 'porcentaje' => 40, 'activo' => true]);

        for ($dia = 1; $dia <= 31; $dia++) {
            $this->assertCount(
                1,
                ReglaPrimerPago::obtenerReglaPorDia($dia),
                "El día {$dia} tiene que resolver a exactamente una regla. Con cero o con ".
                'dos, el cobro no aplica ningún descuento y no avisa.'
            );
        }
    }
}
