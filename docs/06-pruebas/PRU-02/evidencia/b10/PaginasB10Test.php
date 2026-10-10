<?php

namespace Tests\Evidencia;

use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\Profesor;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reproductor de evidencia de B10, fuera de la suite. Pide las páginas reales a Laravel y
 * las guarda en public/_capturas-b10/ para capturarlas con Chrome; esa carpeta se borra
 * al terminar.
 *
 *   DB_DATABASE=wings_testing_claude php vendor/bin/phpunit docs/06-pruebas/PRU-02/evidencia/b10/PaginasB10Test.php
 */
class PaginasB10Test extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_las_paginas(): void
    {
        $this->seed(CatalogosSeeder::class);
        $admin = User::factory()->create(['name' => 'Admin Prueba', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $profesor = Profesor::create([
            'nombre' => 'Lucía', 'apellido' => 'Gaitán', 'deporte_id' => Deporte::first()->id, 'dni' => '30111222',
            'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Calle 1', 'localidad' => 'Quilmes',
            'telefono' => '1144445555', 'email' => 'lucia@wings.test', 'valor_hora' => 5000, 'activo' => true,
            'cbu_alias' => 'lucia.gaitan.mp',
        ]);
        $sinDato = Profesor::create([
            'nombre' => 'Hernán', 'apellido' => 'Quintana', 'deporte_id' => Deporte::first()->id, 'dni' => '30111333',
            'fecha_nacimiento' => '1988-01-01', 'direccion' => 'Calle 2', 'localidad' => 'Quilmes',
            'telefono' => '1144446666', 'valor_hora' => 5000, 'activo' => true,
        ]);
        $liquidar = fn (Profesor $p) => Liquidacion::create([
            'profesor_id' => $p->id, 'mes' => 9, 'anio' => 2026, 'tipo' => Liquidacion::TIPO_HORA,
            'valor_hora_aplicado' => 5000, 'porcentaje_comision_aplicado' => 0, 'total_calculado' => 50000,
            'estado' => Liquidacion::ESTADO_CERRADA, 'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        $destino = public_path('_capturas-b10');
        @mkdir($destino, 0777, true);
        $guardar = fn (string $n, string $html) => file_put_contents("$destino/$n.html", $html);
        $this->actingAs($admin);

        $guardar('profesor-editar', $this->get(route('web.profesores.edit', $profesor->id))->assertOk()->getContent());
        $guardar('profesor-ficha', $this->get(route('web.profesores.show', $profesor->id))->assertOk()->getContent());
        $guardar('liquidacion-con-dato', $this->get(route('web.liquidaciones.show', $liquidar($profesor)->id))->assertOk()->getContent());
        $guardar('liquidacion-sin-dato', $this->get(route('web.liquidaciones.show', $liquidar($sinDato)->id))->assertOk()->getContent());

        // Alta de usuario devuelta con el rol Operativo elegido, para que se vea el campo.
        $this->from(route('web.usuarios.create'))->post(route('web.usuarios.store'), ['rol' => User::ROL_OPERATIVO, 'cbu_alias' => 'sandra.vidal']);
        $guardar('usuario-operativo', $this->get(route('web.usuarios.create'))->assertOk()->getContent());
        $this->from(route('web.profesores.create'))->post(route('web.profesores.store'), ['cbu_alias' => 'abc']);
        $guardar('profesor-error', $this->get(route('web.profesores.create'))->assertOk()->getContent());

        $this->assertFileExists("$destino/usuario-operativo.html");
    }
}
