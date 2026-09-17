<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Services\LiquidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Liquidacion por hora: cada clase se paga tarifa x minutos / 60.
 *
 * Decision de Carlos (17/09/2026): hoy todas las clases duran una hora, pero una
 * de hora y media se paga $5.000 x 1,5. Antes se pagaba la tarifa completa por
 * clase sin mirar la duracion. La tarifa y los minutos quedan congelados al
 * liquidar: cambiar despues la tarifa del profesor o el horario de la clase no
 * reescribe lo liquidado.
 */
class LiquidacionHoraPorDuracionTest extends TestCase
{
    use RefreshDatabase;

    private LiquidacionService $service;
    private Grupo $grupo;
    private Profesor $profesor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LiquidacionService::class);

        $deporte = Deporte::create([
            'nombre' => 'Tenis',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $this->profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Profesor',
            'apellido' => 'Hora',
            'dni' => '20999888',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle 1',
            'localidad' => 'CABA',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'valor_hora' => 5000,
            'activo' => true,
        ]);
    }

    public function test_cada_clase_se_paga_por_su_duracion(): void
    {
        // Una clase por dia, cada una con su duracion: [inicio, fin, minutos, importe].
        $casos = [
            '2026-09-01' => ['10:00', '11:00', 60, 5000.00],
            '2026-09-02' => ['10:00', '11:30', 90, 7500.00],
            '2026-09-03' => ['10:00', '11:20', 80, 6666.67],
            '2026-09-04' => ['10:00', '10:30', 30, 2500.00],
            '2026-09-05' => ['18:00', '20:00', 120, 10000.00],
        ];
        $clases = [];
        foreach ($casos as $fecha => [$inicio, $fin]) {
            $clases[$this->crearClase($fecha, $inicio, $fin)->id] = $fecha;
        }

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);

        $this->assertCount(5, $liquidacion->detalles);
        foreach ($liquidacion->detalles as $detalle) {
            [$inicio, $fin, $minutos, $importe] = $casos[$clases[$detalle->referencia_id]];
            $this->assertSame($minutos, (int) $detalle->minutos, "Minutos de la clase {$inicio}-{$fin}");
            $this->assertSame($importe, (float) $detalle->monto, "Importe de la clase {$inicio}-{$fin}");
        }
        $this->assertSame(31666.67, (float) $liquidacion->total_calculado);
        $this->assertSame(5000.00, (float) $liquidacion->valor_hora_aplicado);
    }

    public function test_vista_previa_y_liquidacion_calculan_lo_mismo(): void
    {
        $this->crearClase('2026-09-03', '10:00', '11:00');
        $this->crearClase('2026-09-10', '10:00', '11:30');
        $this->crearClase('2026-09-17', '10:00', '11:20');

        $previa = $this->service->previsualizarLiquidacion($this->profesor->id, 9, 2026);
        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);

        $this->assertSame(19166.67, (float) $previa['total_estimado']);
        $this->assertSame((float) $previa['total_estimado'], (float) $liquidacion->total_calculado);
        $this->assertSame(
            array_map('floatval', array_column($previa['detalles'], 'monto')),
            $liquidacion->detalles->sortBy('referencia_id')->pluck('monto')->map(fn ($m) => (float) $m)->values()->all()
        );
        $this->assertSame([60, 90, 80], array_column($previa['detalles'], 'minutos'));
    }

    public function test_cambiar_la_tarifa_despues_no_altera_la_liquidacion_recalculada(): void
    {
        $this->crearClase('2026-09-10', '10:00', '11:30');
        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);

        $this->profesor->update(['valor_hora' => 8000]);
        $recalculada = $this->service->recalcularLiquidacion($liquidacion->id);

        $this->assertSame(5000.00, (float) $recalculada->valor_hora_aplicado);
        $this->assertSame(7500.00, (float) $recalculada->total_calculado);
    }

    public function test_cambiar_el_horario_despues_de_cerrar_no_altera_minutos_ni_monto(): void
    {
        $clase = $this->crearClase('2026-09-10', '10:00', '11:30');
        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $this->service->cerrarLiquidacion($liquidacion->id);

        $clase->update(['hora_fin' => '11:00']);
        $this->profesor->update(['valor_hora' => 8000]);

        $cerrada = $liquidacion->fresh('detalles');
        $this->assertSame(90, (int) $cerrada->detalles->first()->minutos);
        $this->assertSame(7500.00, (float) $cerrada->detalles->first()->monto);
        $this->assertSame(5000.00, (float) $cerrada->valor_hora_aplicado);
    }

    public function test_clase_sin_duracion_valida_frena_la_liquidacion(): void
    {
        $clase = $this->crearClase('2026-09-10', '10:00', '11:00');
        // Dato roto que la pantalla no deja cargar: fin igual a inicio.
        DB::table('clases')->where('id', $clase->id)->update(['hora_fin' => '10:00:00']);

        $this->expectExceptionMessage('10/09/2026');
        $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);
    }

    public function test_la_migracion_no_recalcula_lo_ya_liquidado(): void
    {
        $clase = $this->crearClase('2026-09-10', '10:00', '11:30');
        // Liquidacion vieja: pagaba la tarifa entera por clase, sin congelar nada.
        $liquidacionId = DB::table('liquidaciones')->insertGetId([
            'profesor_id' => $this->profesor->id,
            'mes' => 9,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 4000,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('liquidacion_detalles')->insert([
            'liquidacion_id' => $liquidacionId,
            'tipo_referencia' => 'CLASE',
            'referencia_id' => $clase->id,
            'monto' => 4000,
            'descripcion' => 'Clase vieja',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migracion = require database_path('migrations/2026_09_17_120000_congelar_tarifa_y_minutos_en_liquidacion_hora.php');
        $migracion->rellenar();

        $this->assertSame('4000.00', DB::table('liquidaciones')->where('id', $liquidacionId)->value('valor_hora_aplicado'));
        $this->assertSame('4000.00', DB::table('liquidaciones')->where('id', $liquidacionId)->value('total_calculado'));
        $detalle = DB::table('liquidacion_detalles')->where('liquidacion_id', $liquidacionId)->first();
        $this->assertSame('4000.00', $detalle->monto);
        // Se pago como una hora: el detalle dice lo que se pago, no lo que dura hoy la clase.
        $this->assertSame(60, (int) $detalle->minutos);
    }

    private function crearClase(string $fecha, string $inicio, string $fin): Clase
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => $fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);

        return $clase;
    }
}
