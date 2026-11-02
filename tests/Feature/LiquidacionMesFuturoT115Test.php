<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\Profesor;
use App\Models\Subrubro;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T115: en enero, cambiar solo el mes generaba diciembre del año nuevo, vacía y sin aviso. */
class LiquidacionMesFuturoT115Test extends TestCase
{
    use RefreshDatabase;

    private const AVISO = 'No se puede liquidar un mes que todavía no empezó. Revisá el mes y el año.';

    private User $duenio;
    private Profesor $porHora;
    private Profesor $porComision;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2027-01-02 10:00:00');
        $this->seed(CatalogosSeeder::class);
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
        $this->duenio = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $sub = Subrubro::where('nombre', 'Luz')->value('id');
        $datos = fn (string $apellido, string $dni, int $deporte) => ['deporte_id' => $deporte, 'nombre' => 'Prueba', 'apellido' => $apellido, 'dni' => $dni, 'fecha_nacimiento' => '1985-01-01',
            'direccion' => 'x', 'localidad' => 'x', 'telefono' => '1', 'activo' => true, 'subrubro_id' => $sub];
        $hora = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $comision = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $this->porHora = Profesor::create($datos('Hora', '30000001', $hora->id) + ['valor_hora' => 1000]);
        $this->porComision = Profesor::create($datos('Comision', '30000002', $comision->id) + ['porcentaje_comision' => 35]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function generar(Profesor $profesor, int $mes, int $anio)
    {
        return $this->actingAs($this->duenio)->from(route('web.liquidaciones.create'))
            ->post(route('web.liquidaciones.store'), ['profesor_id' => $profesor->id, 'mes' => $mes, 'anio' => $anio]);
    }

    public function test_cambiar_solo_el_mes_en_enero_no_genera_diciembre_del_anio_nuevo(): void
    {
        foreach ([$this->porHora, $this->porComision] as $profesor) {
            $this->generar($profesor, 12, 2027)
                ->assertRedirect(route('web.liquidaciones.create'))
                ->assertSessionHas('error', self::AVISO)
                ->assertSessionHasInput('mes', 12);
        }

        $this->assertSame(0, Liquidacion::count());
    }

    public function test_el_mes_siguiente_tampoco_se_liquida(): void
    {
        $this->generar($this->porHora, 2, 2027)->assertSessionHas('error', self::AVISO);
        Carbon::setTestNow('2027-01-31 23:59:00');
        $this->generar($this->porHora, 2, 2027)->assertSessionHas('error', self::AVISO);

        $this->assertSame(0, Liquidacion::count());
    }

    public function test_diciembre_del_anio_que_termino_y_el_mes_en_curso_se_siguen_generando(): void
    {
        $this->generar($this->porHora, 12, 2026)->assertSessionMissing('error');
        $this->assertSame(1, Liquidacion::where('mes', 12)->where('anio', 2026)->count());

        $this->generar($this->porComision, 1, 2027)->assertSessionMissing('error');
        $this->assertSame(1, Liquidacion::where('mes', 1)->where('anio', 2027)->count());
    }
}
