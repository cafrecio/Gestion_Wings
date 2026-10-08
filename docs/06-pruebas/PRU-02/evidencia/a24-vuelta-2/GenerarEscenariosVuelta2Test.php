<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerarEscenariosVuelta2Test extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'America/Argentina/Buenos_Aires';

    private User $admin;
    private User $sandra;
    private User $marcos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Carlos Admin',
            'email' => 'admin@wings.com',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);
        $this->sandra = User::factory()->create([
            'name' => 'Sandra Vidal',
            'email' => 'sandra@wings.com',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);
        $this->marcos = User::factory()->create([
            'name' => 'Marcos Peña',
            'email' => 'marcos@wings.com',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $deporte = Deporte::first();
        $nivel = Nivel::first();
        $grupo = Grupo::create([
            'nombre' => 'Acrobacia Inicial',
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $profe = Profesor::create([
            'nombre' => 'Laura',
            'apellido' => 'Gómez',
            'dni' => '33111222',
            'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'CABA',
            'activo' => true,
        ]);
        Clase::create([
            'grupo_id' => $grupo->id,
            'profesor_id' => $profe->id,
            'fecha' => '2026-10-08',
            'hora_inicio' => '17:00:00',
            'hora_fin' => '18:30:00',
            'cancelada' => false,
        ]);
    }

    private function abrirCaja(User $operativo, string $fechaHora): CajaOperativa
    {
        $tipoEfe = TipoCaja::first();
        return CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'usuario_apertura_id' => $operativo->id,
            'tipo_caja_efectivo_id' => $tipoEfe->id,
            'efectivo_inicial' => 10000,
            'apertura_at' => Carbon::parse($fechaHora, self::TZ)->setTimezone('UTC'),
            'estado' => 'ABIERTA',
        ]);
    }

    public function test_generar_htmls(): void
    {
        $destDir = base_path('docs/06-pruebas/PRU-02/evidencia/a24-vuelta-2/html');
        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        $opcion = env('VUELTA2_OPCION', '1');

        // Situación 3: Marcos abrió hoy a las 10:00. Sandra entra a las 11:30.
        Carbon::setTestNow('2026-10-08 11:30:00');
        $this->abrirCaja($this->marcos, '2026-10-08 10:00:00');
        $r3 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $this->assertSame(200, $r3->getStatusCode());
        file_put_contents("{$destDir}/opcion-{$opcion}-situacion-3.html", $r3->getContent());

        // Limpiar cajas
        CajaOperativa::query()->delete();

        // Situación 6: Marcos abrió ayer a las 18:30. Sandra entra hoy a las 10:00.
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->abrirCaja($this->marcos, '2026-10-07 18:30:00');
        $r6 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $this->assertSame(200, $r6->getStatusCode());
        file_put_contents("{$destDir}/opcion-{$opcion}-situacion-6.html", $r6->getContent());

        // Limpiar cajas
        CajaOperativa::query()->delete();

        // Situación 7: Sandra abrió ayer a las 18:30. Sandra entra hoy a las 10:00.
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->abrirCaja($this->sandra, '2026-10-07 18:30:00');
        $r7 = $this->actingAs($this->sandra)->get(route('web.operativo.dashboard'));
        $this->assertSame(200, $r7->getStatusCode());
        file_put_contents("{$destDir}/opcion-{$opcion}-situacion-7.html", $r7->getContent());

        $this->assertTrue(true);
    }
}
