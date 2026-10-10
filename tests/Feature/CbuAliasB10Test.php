<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\Profesor;
use App\Models\User;
use App\Rules\CbuOAlias;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B10 de PRU-02: no había dónde guardar a qué cuenta se le transfiere a un profesor.
 * Decisiones de Carlos, 10/10/2026: un solo campo «CBU o alias», opcional, que solo ve
 * el admin; y también para el operativo, que cobra sueldo.
 */
class CbuAliasB10Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
    }

    private function profesor(array $extras = []): array
    {
        return array_replace([
            'nombre' => 'Lucía', 'apellido' => 'Gaitán', 'deporte_id' => Deporte::first()->id, 'dni' => '30111222',
            'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Calle 1', 'localidad' => 'Quilmes',
            'telefono' => '1144445555', 'valor_hora' => 5000,
        ], $extras);
    }

    private function usuario(array $extras = []): array
    {
        return array_replace([
            'name' => 'Sandra Vidal', 'email' => 'sandra@wings.test', 'rol' => User::ROL_OPERATIVO,
            'password' => 'ClaveDePrueba2026', 'password_confirmation' => 'ClaveDePrueba2026',
        ], $extras);
    }

    public function test_un_profesor_se_guarda_con_su_alias_y_la_ficha_lo_muestra(): void
    {
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->profesor(['cbu_alias' => 'lucia.gaitan.mp']))
            ->assertSessionHasNoErrors();

        $profesor = Profesor::where('dni', '30111222')->firstOrFail();
        $this->assertSame('lucia.gaitan.mp', $profesor->cbu_alias);
        $this->actingAs($this->admin)->get(route('web.profesores.show', $profesor->id))->assertOk()->assertSee('lucia.gaitan.mp');
    }

    public function test_un_cbu_pegado_con_espacios_se_guarda_sin_ellos(): void
    {
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->profesor(['cbu_alias' => ' 0170 0992 2000 0067 7979 12 ']))
            ->assertSessionHasNoErrors();

        $this->assertSame('0170099220000067797912', Profesor::where('dni', '30111222')->value('cbu_alias'));
    }

    public function test_el_dato_es_opcional_y_se_puede_borrar_al_editar(): void
    {
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->profesor(['cbu_alias' => 'lucia.gaitan.mp']));
        $profesor = Profesor::where('dni', '30111222')->firstOrFail();

        $this->actingAs($this->admin)->put(route('web.profesores.update', $profesor->id), $this->profesor(['cbu_alias' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($profesor->fresh()->cbu_alias);
    }

    public function test_lo_que_no_es_ni_cbu_ni_alias_se_rechaza(): void
    {
        // Los tres ultimos los encontro Codex al verificar: un alias con espacio, tabulacion o
        // salto de linea se guardaba "arreglado" («alias con espacio» → «aliasconespacio»),
        // y un texto largo se rechazaba con un mensaje en ingles.
        $malos = ['abc', '12345678', '017009922000006779791', 'alias con espacios!', str_repeat('a', 21),
            'alias con espacio', "alias\tprueba", "alias\nprueba", str_repeat('a', 200), ['un', 'arreglo']];
        foreach ($malos as $malo) {
            $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->profesor(['cbu_alias' => $malo]))
                ->assertSessionHasErrors('cbu_alias');
        }

        $this->assertSame(0, Profesor::count());
        $this->assertSame(CbuOAlias::MENSAJE, session('errors')->first('cbu_alias'));
    }

    public function test_al_pagar_una_liquidacion_se_ve_a_donde_transferir(): void
    {
        $profesor = Profesor::create($this->profesor(['activo' => true, 'cbu_alias' => 'lucia.gaitan.mp']));
        $liquidacion = Liquidacion::create([
            'profesor_id' => $profesor->id, 'mes' => 9, 'anio' => 2026, 'tipo' => Liquidacion::TIPO_HORA,
            'valor_hora_aplicado' => 5000, 'porcentaje_comision_aplicado' => 0, 'total_calculado' => 50000,
            'estado' => Liquidacion::ESTADO_CERRADA, 'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        $this->actingAs($this->admin)->get(route('web.liquidaciones.show', $liquidacion->id))
            ->assertOk()->assertSee('lucia.gaitan.mp');

        $profesor->update(['cbu_alias' => null]);
        $this->actingAs($this->admin)->get(route('web.liquidaciones.show', $liquidacion->id))
            ->assertOk()->assertSee('no tiene CBU ni alias cargado');
    }

    public function test_el_operativo_guarda_su_cbu_o_alias(): void
    {
        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->usuario(['cbu_alias' => 'sandra.vidal']))
            ->assertSessionHasNoErrors();

        $this->assertSame('sandra.vidal', User::where('email', 'sandra@wings.test')->value('cbu_alias'));
    }

    public function test_para_otros_roles_el_dato_no_se_guarda_y_se_borra_al_cambiar_de_rol(): void
    {
        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->usuario(['rol' => User::ROL_ADMIN, 'cbu_alias' => 'no.corresponde']))
            ->assertSessionHasNoErrors();
        $this->assertNull(User::where('email', 'sandra@wings.test')->value('cbu_alias'));

        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $operativo->cbu_alias = 'era.operativo';
        $operativo->save();

        $this->actingAs($this->admin)->put(route('web.usuarios.update', $operativo->id), [
            'name' => $operativo->name, 'email' => $operativo->email, 'rol' => User::ROL_ADMIN, 'cbu_alias' => 'era.operativo',
        ])->assertSessionHasNoErrors();

        $this->assertNull($operativo->fresh()->cbu_alias);
    }

    public function test_solo_el_admin_llega_a_esos_datos(): void
    {
        $profesor = Profesor::create($this->profesor(['activo' => true, 'cbu_alias' => 'lucia.gaitan.mp']));
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $this->actingAs($operativo)->get(route('web.profesores.show', $profesor->id))->assertDontSee('lucia.gaitan.mp');
        $this->actingAs($operativo)->get(route('web.profesores.edit', $profesor->id))->assertDontSee('lucia.gaitan.mp');
        $this->actingAs($operativo)->get(route('web.usuarios.index'))->assertDontSee('lucia.gaitan.mp');
    }
}
