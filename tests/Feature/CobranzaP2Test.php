<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobranzaP2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $patin;
    private Deporte $futbol;
    private Grupo $grupoPatin;
    private Grupo $grupoFutbol;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->patin = Deporte::firstOrCreate(['nombre' => 'Patín'], ['activo' => true]);
        $this->futbol = Deporte::firstOrCreate(['nombre' => 'Fútbol'], ['activo' => true]);

        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);

        $this->grupoPatin = Grupo::create([
            'deporte_id' => $this->patin->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->grupoFutbol = Grupo::create([
            'deporte_id' => $this->futbol->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
    }

    public function test_cobranza_muestra_total_adeudado_en_cabecera(): void
    {
        $alumno = Alumno::create([
            'nombre' => 'Lucía',
            'apellido' => 'Gómez',
            'dni' => '12345678',
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-08-01',
            'celular' => '11111111',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'activo' => true,
        ]);

        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => now()->subMonth()->format('Y-m'),
            'monto_original' => 45000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($this->admin)->get(route('web.cobranza.index'));
        $response->assertOk();

        // Debe mostrar "Total adeudado" y el monto $ 45.000
        $response->assertSee('Total adeudado');
        $response->assertSee('45.000');
    }

    public function test_cobranza_muestra_monto_adeudado_por_cada_alumno_y_boton_cobrar(): void
    {
        $alumno = Alumno::create([
            'nombre' => 'Sofía',
            'apellido' => 'Morales',
            'dni' => '44111222',
            'fecha_nacimiento' => '2005-05-05',
            'fecha_alta' => '2026-08-01',
            'celular' => '11111111',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'activo' => true,
        ]);

        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => now()->subMonth()->format('Y-m'),
            'monto_original' => 38000,
            'monto_pagado' => 10000,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($this->admin)->get(route('web.cobranza.index'));
        $response->assertOk();

        // Debe ver el saldo pendiente de 28.000 en la fila
        $response->assertSee('28.000');

        // Debe existir el botón "Cobrar" apuntando a la pasarela de cobro del alumno
        $response->assertSee(route('web.caja.cobrar', $alumno->id));
        $response->assertSee('Cobrar');
    }

    public function test_cobranza_distingue_alumno_en_dos_deportes_con_mismo_dni(): void
    {
        $dni = '44111222';

        $alumnoPatin = Alumno::create([
            'nombre' => 'Sofía',
            'apellido' => 'Morales',
            'dni' => $dni,
            'fecha_nacimiento' => '2005-05-05',
            'fecha_alta' => '2026-08-01',
            'celular' => '11111111',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'activo' => true,
        ]);

        $alumnoFutbol = Alumno::create([
            'nombre' => 'Sofía',
            'apellido' => 'Morales',
            'dni' => $dni,
            'fecha_nacimiento' => '2005-05-05',
            'fecha_alta' => '2026-08-01',
            'celular' => '11111111',
            'deporte_id' => $this->futbol->id,
            'grupo_id' => $this->grupoFutbol->id,
            'activo' => true,
        ]);

        DeudaCuota::create([
            'alumno_id' => $alumnoPatin->id,
            'periodo' => now()->subMonth()->format('Y-m'),
            'monto_original' => 30000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        DeudaCuota::create([
            'alumno_id' => $alumnoFutbol->id,
            'periodo' => now()->subMonth()->format('Y-m'),
            'monto_original' => 40000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($this->admin)->get(route('web.cobranza.index'));
        $response->assertOk();

        // Ambas filas deben tener sus respectivos botones de cobro diferenciados
        $response->assertSee(route('web.caja.cobrar', $alumnoPatin->id));
        $response->assertSee(route('web.caja.cobrar', $alumnoFutbol->id));

        // Debe mostrar las deudas de cada deporte
        $response->assertSee('30.000');
        $response->assertSee('40.000');
        // El total adeudado global debe ser la suma (70.000)
        $response->assertSee('70.000');
    }
}
