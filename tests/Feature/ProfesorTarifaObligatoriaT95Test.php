<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Profesor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfesorTarifaObligatoriaT95Test extends TestCase
{
    use RefreshDatabase;

    private Deporte $hora;
    private Deporte $comision;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $this->actingAs(User::factory()->create(['rol' => 'ADMIN', 'activo' => true]));
        $this->hora = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->comision = Deporte::create(['nombre' => 'Vóley', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
    }

    private function datos(Deporte $deporte, array $tarifa): array
    {
        return $tarifa + ['nombre' => 'Ana', 'apellido' => 'Prueba', 'deporte_id' => $deporte->id, 'dni' => '70000001',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'telefono' => '1140000000'];
    }

    private function profesorViejo(array $tarifa = []): Profesor
    {
        return Profesor::create($tarifa + ['deporte_id' => $this->hora->id, 'nombre' => 'Ana', 'apellido' => 'Prueba', 'dni' => '70000001',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'activo' => true]);
    }

    public function test_sin_la_tarifa_de_su_deporte_no_se_guarda(): void
    {
        $casos = [[$this->hora, 'valor_hora', 'Cargá el valor por hora.'], [$this->comision, 'porcentaje_comision', 'Cargá el porcentaje de comisión.']];
        foreach ($casos as [$deporte, $campo, $aviso]) {
            foreach ([[], [$campo => '']] as $tarifa) {
                $this->post(route('web.profesores.store'), $this->datos($deporte, $tarifa))->assertSessionHasErrors([$campo => $aviso]);
            }
        }
        $this->assertDatabaseCount('profesores', 0);
    }

    public function test_cero_no_es_una_tarifa(): void
    {
        $this->post(route('web.profesores.store'), $this->datos($this->hora, ['valor_hora' => 0]))
            ->assertSessionHasErrors(['valor_hora' => 'El valor por hora debe ser mayor a cero.']);
        $this->post(route('web.profesores.store'), $this->datos($this->comision, ['porcentaje_comision' => 0]))
            ->assertSessionHasErrors(['porcentaje_comision' => 'El porcentaje de comisión debe ser mayor a cero.']);
        $this->assertDatabaseCount('profesores', 0);
    }

    public function test_con_la_tarifa_de_su_deporte_se_guarda_sin_pedir_la_otra(): void
    {
        $this->post(route('web.profesores.store'), $this->datos($this->hora, ['valor_hora' => 5000]))->assertSessionHasNoErrors();
        $this->assertSame('5000.00', Profesor::firstOrFail()->valor_hora);
        $this->assertNull(Profesor::firstOrFail()->porcentaje_comision);
    }

    public function test_editar_una_profesora_vieja_sin_tarifa_pide_completarla(): void
    {
        $profesor = $this->profesorViejo();
        $this->put(route('web.profesores.update', $profesor->id), $this->datos($this->hora, []))->assertSessionHasErrors('valor_hora');
        $this->put(route('web.profesores.update', $profesor->id), $this->datos($this->hora, ['valor_hora' => 5000]))->assertSessionHasNoErrors();
        $this->assertSame('5000.00', $profesor->fresh()->valor_hora);
    }

    public function test_pasar_a_un_deporte_que_liquida_distinto_pide_su_tarifa(): void
    {
        $profesor = $this->profesorViejo(['valor_hora' => 5000]);
        $this->put(route('web.profesores.update', $profesor->id), $this->datos($this->comision, ['valor_hora' => 5000]))->assertSessionHasErrors('porcentaje_comision');
        $this->assertSame($this->hora->id, $profesor->fresh()->deporte_id);
    }

    public function test_con_cambio_programado_pide_las_dos_tarifas_como_el_formulario(): void
    {
        $this->put(route('web.deportes.update', $this->hora->id), ['nombre' => 'Patín', 'tipo_liquidacion' => 'COMISION'])->assertSessionHasNoErrors();
        $this->post(route('web.profesores.store'), $this->datos($this->hora, ['valor_hora' => 5000]))->assertSessionHasErrors('porcentaje_comision');
        $this->post(route('web.profesores.store'), $this->datos($this->hora, ['porcentaje_comision' => 20]))->assertSessionHasErrors('valor_hora');
        $this->post(route('web.profesores.store'), $this->datos($this->hora, ['valor_hora' => 5000, 'porcentaje_comision' => 20]))->assertSessionHasNoErrors();
    }
}
