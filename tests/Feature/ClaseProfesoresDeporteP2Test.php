<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaseProfesoresDeporteP2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $patin;
    private Deporte $futbol;
    private Grupo $grupoPatin;
    private Profesor $profPatin;
    private Profesor $profFutbol;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $this->patin = Deporte::firstOrCreate(['nombre' => 'Patín'], ['activo' => true]);
        $this->futbol = Deporte::firstOrCreate(['nombre' => 'Fútbol'], ['activo' => true]);

        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);

        $this->grupoPatin = Grupo::create([
            'deporte_id' => $this->patin->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profPatin = Profesor::create([
            'nombre' => 'Paula',
            'apellido' => 'Ríos',
            'dni' => '28111222',
            'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Prueba',
            'localidad' => 'Prueba',
            'valor_hora' => 1000,
            'deporte_id' => $this->patin->id,
            'forma_cobro' => 'POR_HORA',
            'tarifa_hora' => 5000,
            'activo' => true,
        ]);

        $this->profFutbol = Profesor::create([
            'nombre' => 'Esteban',
            'apellido' => 'García',
            'dni' => '29333444',
            'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Prueba',
            'localidad' => 'Prueba',
            'valor_hora' => 1000,
            'deporte_id' => $this->futbol->id,
            'forma_cobro' => 'POR_HORA',
            'tarifa_hora' => 6000,
            'activo' => true,
        ]);
    }

    public function test_rechaza_crear_clase_con_profesor_de_otro_deporte(): void
    {
        $fecha = now()->addDays(2)->toDateString();

        $datos = [
            'tipo_creacion' => 'unica',
            'fecha' => $fecha,
            'grupo_id' => $this->grupoPatin->id,
            'hora_inicio' => '17:00',
            'hora_fin' => '18:00',
            'profesores' => [$this->profFutbol->id], // Fútbol en clase de Patín
        ];

        $res = $this->actingAs($this->admin)
            ->post(route('web.clases.store'), $datos);

        $res->assertSessionHasErrors(['profesores']);
        $this->assertDatabaseMissing('clases', [
            'grupo_id' => $this->grupoPatin->id,
            'fecha' => $fecha,
        ]);
    }

    public function test_permite_crear_clase_con_profesor_del_mismo_deporte(): void
    {
        $fecha = now()->addDays(2)->toDateString();

        $datos = [
            'tipo_creacion' => 'unica',
            'fecha' => $fecha,
            'grupo_id' => $this->grupoPatin->id,
            'hora_inicio' => '17:00',
            'hora_fin' => '18:00',
            'profesores' => [$this->profPatin->id], // Patín en clase de Patín
        ];

        $res = $this->actingAs($this->admin)
            ->post(route('web.clases.store'), $datos);

        $res->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('clases', [
            'grupo_id' => $this->grupoPatin->id,
            'fecha' => $fecha,
        ]);
    }

    public function test_rechaza_editar_clase_asignando_profesor_de_otro_deporte(): void
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupoPatin->id,
            'fecha' => now()->addDays(3)->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estado' => 'PENDIENTE',
        ]);

        $datos = [
            'fecha' => $clase->fecha->toDateString(),
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'profesores' => [$this->profFutbol->id], // Fútbol en clase de Patín
        ];

        $res = $this->actingAs($this->admin)
            ->put(route('web.clases.update', $clase->id), $datos);

        $res->assertSessionHasErrors(['profesores']);
    }
}
