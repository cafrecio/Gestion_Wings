<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NuevaLiquidacionConteoMesT87Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Profesor $lucia;
    private Profesor $profesorSinClases;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 10:00:00');
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);

        $deporte = Deporte::create([
            'nombre' => 'Patín Artístico',
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Avanzado']);
        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->lucia = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Lucía',
            'apellido' => 'Gaitán',
            'dni' => '30111222',
            'fecha_nacimiento' => '1990-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Quilmes',
            'valor_hora' => 12000,
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);

        $this->profesorSinClases = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Mariana',
            'apellido' => 'Sin Clases',
            'dni' => '30333444',
            'fecha_nacimiento' => '1992-08-20',
            'direccion' => 'Calle 456',
            'localidad' => 'Bernal',
            'valor_hora' => 10000,
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);

        // 17 clases en octubre de 2026 (validadas para liquidación)
        for ($i = 1; $i <= 17; $i++) {
            $c = Clase::create([
                'grupo_id' => $this->grupo->id,
                'fecha' => '2026-10-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'hora_inicio' => '10:00',
                'hora_fin' => '11:00',
                'cancelada' => false,
                'validada_para_liquidacion' => true,
            ]);
            $c->profesores()->attach($this->lucia->id);
        }

        // 3 clases en septiembre de 2026 (validadas para liquidación)
        for ($i = 1; $i <= 3; $i++) {
            $c = Clase::create([
                'grupo_id' => $this->grupo->id,
                'fecha' => '2026-09-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'hora_inicio' => '10:00',
                'hora_fin' => '11:00',
                'cancelada' => false,
                'validada_para_liquidacion' => true,
            ]);
            $c->profesores()->attach($this->lucia->id);
        }

        // 2 clases sin asistencia en octubre (pasadas, no validadas, sin asistencias)
        for ($i = 1; $i <= 2; $i++) {
            $c = Clase::create([
                'grupo_id' => $this->grupo->id,
                'fecha' => '2026-10-2' . $i,
                'hora_inicio' => '15:00',
                'hora_fin' => '16:00',
                'cancelada' => false,
                'validada_para_liquidacion' => false,
            ]);
            $c->profesores()->attach($this->lucia->id);
        }

        // 1 clase sin asistencia en septiembre (pasada, no validada, sin asistencias)
        $cSepSin = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-09-28',
            'hora_inicio' => '15:00',
            'hora_fin' => '16:00',
            'cancelada' => false,
            'validada_para_liquidacion' => false,
        ]);
        $cSepSin->profesores()->attach($this->lucia->id);
    }

    public function test_pantalla_nueva_liquidacion_cuenta_solo_el_mes_elegido_y_aviso_sin_asistencia(): void
    {
        // 1. Al consultar octubre de 2026
        $resOct = $this->actingAs($this->admin)->get(route('web.liquidaciones.create', ['mes' => 10, 'anio' => 2026]));
        $resOct->assertOk();

        $profesoresOct = collect($resOct->viewData('profesores'));
        $luciaOct = $profesoresOct->firstWhere('id', $this->lucia->id);

        // Clases liquidables de octubre debe ser 17 (no 20)
        $this->assertSame(17, (int) $luciaOct->clases_count, 'Octubre debe contar 17 clases liquidables');
        // Total acumulado en todos los meses debe ser 20
        $this->assertSame(20, (int) $luciaOct->clases_count_total, 'Total histórico de clases liquidables debe ser 20');
        // Clases sin asistencia de octubre debe ser 2 (no 3)
        $this->assertSame(2, (int) $luciaOct->clases_sin_asistencia, 'Octubre debe contar 2 clases sin asistencia');

        // La vista HTML renderiza exactamente 17 clases liquidables y 2 sin asistencia
        $resOct->assertSee('17 clases liquidables');
        $resOct->assertSee('2 clases sin asistencia');

        // El profesor sin clases tiene radio deshabilitado y leyenda
        $sinClases = $profesoresOct->firstWhere('id', $this->profesorSinClases->id);
        $this->assertSame(0, (int) $sinClases->clases_count_total);
        $resOct->assertSee('Sin clases pendientes');

        // Los datos JSON para el script en cliente contienen el desglose por período
        $json = $resOct->viewData('profesoresJson');
        $this->assertArrayHasKey($this->lucia->id, $json);
        $this->assertSame(17, $json[$this->lucia->id]['periodos']['2026-10']['liquidables']);
        $this->assertSame(2, $json[$this->lucia->id]['periodos']['2026-10']['sin_asistencia']);
        $this->assertSame(3, $json[$this->lucia->id]['periodos']['2026-9']['liquidables']);
        $this->assertSame(1, $json[$this->lucia->id]['periodos']['2026-9']['sin_asistencia']);

        // 2. Al consultar septiembre de 2026
        $resSep = $this->actingAs($this->admin)->get(route('web.liquidaciones.create', ['mes' => 9, 'anio' => 2026]));
        $resSep->assertOk();

        $profesoresSep = collect($resSep->viewData('profesores'));
        $luciaSep = $profesoresSep->firstWhere('id', $this->lucia->id);

        $this->assertSame(3, (int) $luciaSep->clases_count, 'Septiembre debe contar 3 clases liquidables');
        $this->assertSame(1, (int) $luciaSep->clases_sin_asistencia, 'Septiembre debe contar 1 clase sin asistencia');

        $resSep->assertSee('3 clases liquidables');
        $resSep->assertSee('1 clase sin asistencia');
    }

    public function test_liquidacion_generada_coincide_con_el_numero_mostrado_en_pantalla(): void
    {
        // Generar liquidación de octubre
        $resStoreOct = $this->actingAs($this->admin)->post(route('web.liquidaciones.store'), [
            'profesor_id' => $this->lucia->id,
            'mes' => 10,
            'anio' => 2026,
        ]);
        $resStoreOct->assertRedirect();

        $liqOct = Liquidacion::where('profesor_id', $this->lucia->id)
            ->where('mes', 10)
            ->where('anio', 2026)
            ->firstOrFail();

        // Debe tener 17 clases y $204.000 ($12.000 x 17)
        $this->assertSame(17, $liqOct->detalles()->count());
        $this->assertEquals(204000.0, (float) $liqOct->total_calculado);

        // Generar liquidación de septiembre
        $resStoreSep = $this->actingAs($this->admin)->post(route('web.liquidaciones.store'), [
            'profesor_id' => $this->lucia->id,
            'mes' => 9,
            'anio' => 2026,
        ]);
        $resStoreSep->assertRedirect();

        $liqSep = Liquidacion::where('profesor_id', $this->lucia->id)
            ->where('mes', 9)
            ->where('anio', 2026)
            ->firstOrFail();

        // Debe tener 3 clases y $36.000 ($12.000 x 3)
        $this->assertSame(3, $liqSep->detalles()->count());
        $this->assertEquals(36000.0, (float) $liqSep->total_calculado);
    }

    /**
     * MF12 (prueba por días, día 27): una liquidación por comisión guarda detalles por
     * alumno, no por clase, y el contador seguía mostrando el mes ya liquidado como
     * pendiente. El mes cerrado por comisión no cuenta; uno cancelado sí vuelve a contar.
     */
    public function test_mes_ya_liquidado_por_comision_no_cuenta_como_pendiente(): void
    {
        $deporte = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::firstOrCreate(['nombre' => 'Principiantes'])->id, 'activo' => true]);
        $hernan = Profesor::create([
            'deporte_id' => $deporte->id, 'nombre' => 'Hernán', 'apellido' => 'Ocampo', 'dni' => '30555666',
            'fecha_nacimiento' => '1988-02-02', 'direccion' => 'Calle 789', 'localidad' => 'Quilmes',
            'porcentaje_comision' => 40, 'tipo_liquidacion' => 'COMISION', 'activo' => true,
        ]);
        foreach (['2026-10-05', '2026-10-12', '2026-09-07'] as $fecha) {
            $c = Clase::create(['grupo_id' => $grupo->id, 'fecha' => $fecha, 'hora_inicio' => '18:00', 'hora_fin' => '19:00',
                'cancelada' => false, 'validada_para_liquidacion' => true]);
            $c->profesores()->attach($hernan->id);
        }
        $liquidacion = Liquidacion::create(['profesor_id' => $hernan->id, 'mes' => 10, 'anio' => 2026, 'tipo' => Liquidacion::TIPO_COMISION,
            'porcentaje_comision_aplicado' => 40, 'total_calculado' => 0, 'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE]);
        $this->assertSame(0, $liquidacion->detalles()->where('tipo_referencia', 'clase')->count(), 'La comisión no guarda detalles por clase');

        $res = $this->actingAs($this->admin)->get(route('web.liquidaciones.create', ['mes' => 10, 'anio' => 2026]));
        $res->assertOk();
        $fila = collect($res->viewData('profesores'))->firstWhere('id', $hernan->id);
        $this->assertSame(0, (int) $fila->clases_count, 'Octubre ya está liquidado por comisión: no hay clases pendientes');
        $this->assertSame(1, (int) $fila->clases_count_total, 'Solo queda pendiente la clase de septiembre');
        $periodos = $res->viewData('profesoresJson')[$hernan->id]['periodos'];
        $this->assertArrayNotHasKey('2026-10', $periodos);
        $this->assertSame(1, $periodos['2026-9']['liquidables']);

        // Lucía (por hora) no se ve afectada por la liquidación de Hernán.
        $lucia = collect($res->viewData('profesores'))->firstWhere('id', $this->lucia->id);
        $this->assertSame(17, (int) $lucia->clases_count);

        // Si se cancela la liquidación, octubre vuelve a estar pendiente.
        $liquidacion->update(['estado' => Liquidacion::ESTADO_CANCELADA]);
        $res = $this->actingAs($this->admin)->get(route('web.liquidaciones.create', ['mes' => 10, 'anio' => 2026]));
        $fila = collect($res->viewData('profesores'))->firstWhere('id', $hernan->id);
        $this->assertSame(2, (int) $fila->clases_count);
        $this->assertSame(3, (int) $fila->clases_count_total);
    }
}
