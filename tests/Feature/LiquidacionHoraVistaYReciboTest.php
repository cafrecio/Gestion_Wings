<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\LiquidacionService;
use App\Services\ReciboService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * FIN-13: Recibo y pantalla de liquidacion por hora muestran la duracion
 * en formato legible ("1 h", "1 h 20 min", "30 min") y subtotales/totales con centavos.
 *
 * Muestran lo guardado al liquidar, no lo que diga la clase hoy.
 */
class LiquidacionHoraVistaYReciboTest extends TestCase
{
    use RefreshDatabase;

    private LiquidacionService $service;
    private User $admin;
    private Grupo $grupo;
    private Profesor $profesor;
    private TipoCaja $caja;
    private Subrubro $subrubroSueldos;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->service = app(LiquidacionService::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $deporte = Deporte::create([
            'nombre' => 'Patin Artistico',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Avanzado']);
        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Profesor',
            'apellido' => 'Hora',
            'dni' => '20888777',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'CABA',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'valor_hora' => 5000,
            'activo' => true,
        ]);

        $this->caja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO', 'observacion' => '']);
        $this->subrubroSueldos = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Sueldo - Hora, Profesor',
            'permitido_para' => User::ROL_ADMIN,
            'afecta_caja' => true,
            'es_reservado_sistema' => false,
        ]);
    }

    public function test_pantalla_y_recibo_muestran_duracion_en_horas_minutos_y_subtotales_con_centavos(): void
    {
        // Clases de 60, 80 y 90 minutos
        $this->crearClase('2026-09-01', '10:00', '11:00'); // 60 min -> $5.000,00
        $this->crearClase('2026-09-02', '10:00', '11:20'); // 80 min -> $6.666,67
        $this->crearClase('2026-09-03', '10:00', '11:30'); // 90 min -> $7.500,00
        // Total: 230 min (3 h 50 min), $19.166,67

        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);

        // 1. Verificacion de la pantalla show
        $response = $this->actingAs($this->admin)->get(route('web.liquidaciones.show', $liquidacion->id));
        $response->assertOk();
        $response->assertSee('Valor por hora');
        $response->assertDontSee('Valor por clase');
        $response->assertSee('1 h');
        $response->assertSee('1 h 20 min');
        $response->assertSee('1 h 30 min');
        $response->assertSee('6.666,67');
        $response->assertSee('19.166,67');

        // 2. Verificacion del recibo PDF (cerrada y pagada)
        $this->service->cerrarLiquidacion($liquidacion->id);
        $liquidacion->update([
            'estado_pago' => Liquidacion::ESTADO_PAGO_PAGADA,
            'pagada_fecha' => '2026-09-10',
            'pagada_tipo_caja_id' => $this->caja->id,
            'pagada_subrubro_id' => $this->subrubroSueldos->id,
            'pagada_por_admin_id' => $this->admin->id,
        ]);

        $capturado = null;
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturnUsing(function (string $vista, array $data) use (&$capturado) {
                $capturado = $data;
                $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
                $pdf->shouldReceive('setPaper')->andReturnSelf();
                $pdf->shouldReceive('output')->andReturn('%PDF-test');
                return $pdf;
            });

        app(ReciboService::class)->generarReciboLiquidacion($liquidacion->id, true);

        $this->assertNotNull($capturado);
        $htmlRecibo = view('pdfs.recibo-liquidacion', $capturado)->render();

        $this->assertStringContainsString('Duración', $htmlRecibo);
        $this->assertStringContainsString('1 h', $htmlRecibo);
        $this->assertStringContainsString('1 h 20 min', $htmlRecibo);
        $this->assertStringContainsString('1 h 30 min', $htmlRecibo);
        $this->assertStringContainsString('6.666,67', $htmlRecibo);
        $this->assertStringContainsString('3 clases dictadas · 3 h 50 min', $htmlRecibo);
    }

    public function test_cambiar_horario_despues_de_cerrar_no_cambia_duracion_en_pantalla_ni_recibo(): void
    {
        $clase = $this->crearClase('2026-09-02', '10:00', '11:20'); // 80 min ($6.666,67)
        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $this->service->cerrarLiquidacion($liquidacion->id);
        $liquidacion->update([
            'estado_pago' => Liquidacion::ESTADO_PAGO_PAGADA,
            'pagada_fecha' => '2026-09-10',
            'pagada_tipo_caja_id' => $this->caja->id,
            'pagada_subrubro_id' => $this->subrubroSueldos->id,
            'pagada_por_admin_id' => $this->admin->id,
        ]);

        // Cambiar el horario de la clase a 60 min y la tarifa del profesor a $9.000
        $clase->update(['hora_inicio' => '10:00:00', 'hora_fin' => '11:00:00']);
        $this->profesor->update(['valor_hora' => 9000]);

        // La pantalla debe seguir mostrando la duracion y monto guardados (80 min -> 1 h 20 min)
        $response = $this->actingAs($this->admin)->get(route('web.liquidaciones.show', $liquidacion->id));
        $response->assertOk();
        $response->assertSee('1 h 20 min');
        $response->assertDontSee('60 min');
        $response->assertSee('6.666,67');

        // El recibo debe seguir mostrando la duracion y monto guardados
        $capturado = null;
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturnUsing(function (string $vista, array $data) use (&$capturado) {
                $capturado = $data;
                $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
                $pdf->shouldReceive('setPaper')->andReturnSelf();
                $pdf->shouldReceive('output')->andReturn('%PDF-test');
                return $pdf;
            });

        app(ReciboService::class)->generarReciboLiquidacion($liquidacion->id, true);

        $this->assertNotNull($capturado);
        $htmlRecibo = view('pdfs.recibo-liquidacion', $capturado)->render();

        $this->assertStringContainsString('1 h 20 min', $htmlRecibo);
        $this->assertStringContainsString('1 clases dictadas · 1 h 20 min', $htmlRecibo);
        $this->assertStringContainsString('6.666,67', $htmlRecibo);
    }

    public function test_liquidacion_antigua_con_minutos_null_muestra_guion(): void
    {
        $clase = $this->crearClase('2026-09-02', '10:00', '11:20');
        $liquidacion = $this->service->generarLiquidacionMensual($this->profesor->id, 9, 2026);

        // Simular liquidacion antigua con minutos = null
        $detalle = $liquidacion->detalles->first();
        \Illuminate\Support\Facades\DB::table('liquidacion_detalles')
            ->where('id', $detalle->id)
            ->update(['minutos' => null]);

        $response = $this->actingAs($this->admin)->get(route('web.liquidaciones.show', $liquidacion->id));
        $response->assertOk();
        $response->assertSee('—');
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
