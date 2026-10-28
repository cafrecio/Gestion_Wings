<?php

namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, Clase, Deporte, DeporteLiquidacionVigencia, Grupo, GrupoPlan, Liquidacion, Nivel, Pago, Profesor, User};
use App\Services\{DeporteLiquidacionService, LiquidacionService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class VerificacionT45ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $deporte;
    private Profesor $profesor;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        \Illuminate\Support\Facades\DB::table('reporte_analitico_cobertura')->where('id', 1)->update(['desde' => '2026-10-01 00:00:00']);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true, 'name' => 'Dueño de prueba']);
        $this->deporte = Deporte::create(['nombre' => 'Patín de prueba', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->profesor = Profesor::create(['deporte_id' => $this->deporte->id, 'nombre' => 'Ana', 'apellido' => 'Prueba',
            'dni' => '70000001', 'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba',
            'valor_hora' => 5000, 'porcentaje_comision' => 20, 'activo' => true]);
    }

    private function cambiar(string $tipo = 'COMISION', string $nombre = 'Patín de prueba')
    {
        return $this->actingAs($this->admin)->from(route('web.deportes.edit', $this->deporte->id))
            ->put(route('web.deportes.update', $this->deporte->id), ['nombre' => $nombre, 'tipo_liquidacion' => $tipo]);
    }

    private function clase(string $fecha): Clase
    {
        $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => $fecha,
            'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'cancelada' => false, 'validada_para_liquidacion' => true]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }
    /** Ataque: una liquidación abierta de OTRO deporte no frena el cambio de este. */
    public function test_abierta_de_otro_deporte_no_bloquea(): void
    {
        $otro = Deporte::create(['nombre' => 'Hockey de prueba', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $prof = Profesor::create(['deporte_id' => $otro->id, 'nombre' => 'Eva', 'apellido' => 'Otra', 'dni' => '70000009',
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'x', 'localidad' => 'x', 'valor_hora' => 5000, 'activo' => true]);
        app(LiquidacionService::class)->generarLiquidacionMensual($prof->id, 10, 2026);
        $this->cambiar()->assertSessionHasNoErrors();
        $this->assertSame('COMISION', $this->deporte->fresh()->tipoLiquidacionParaPeriodo(11, 2026));
        $this->assertSame('HORA', $otro->fresh()->tipoLiquidacionParaPeriodo(11, 2026));
    }
}
