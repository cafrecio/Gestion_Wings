<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CobranzaWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_abrir_cobranza_sin_columna_nombre_en_grupos(): void
    {
        $this->assertFalse(Schema::hasColumn('grupos', 'nombre'));

        $admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);
        $deporte = Deporte::create(['nombre' => 'Vóley', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->actingAs($admin)
            ->get(route('web.cobranza.index'))
            ->assertOk();

        // Se comparan los identificadores sin las comillas del motor: SQLite cita
        // con comillas dobles y MariaDB con acentos graves. Buscar una de las dos
        // ataba la prueba al motor, y al pasar la suite a MariaDB dejo de encontrar
        // una consulta que era correcta.
        $sinComillas = fn(string $sql): string => str_replace(['"', '`'], '', $sql);

        $this->assertTrue(collect($queries)->contains(
            fn(string $sql) => str_contains($sinComillas($sql), 'from grupos')
                && str_contains($sinComillas($sql), 'join deportes')
                && str_contains($sinComillas($sql), 'join niveles')
        ));
    }
}
