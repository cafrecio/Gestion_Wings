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

        $this->assertTrue(collect($queries)->contains(
            fn(string $sql) => str_contains($sql, 'from "grupos"')
                && str_contains($sql, 'join "deportes"')
                && str_contains($sql, 'join "niveles"')
        ));
    }
}
