<?php

namespace Tests\Feature;

use App\Models\{Alumno, Deporte, DeudaCuota, Grupo, GrupoPlan, Liquidacion, Nivel, Pago, Profesor};
use App\Services\HistorialAnaliticoReportesService;
use Carbon\{Carbon, CarbonImmutable};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistorialAnaliticoReportesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table(HistorialAnaliticoReportesService::COBERTURA)->where('id', 1)->update(['desde'=>'2026-10-09 00:00:00']);
        Carbon::setTestNow('2026-10-09 10:00:00');
        // Fixture mínimo portable: RefreshDatabase usa la base descartable de cada agente.
        // El seeder de capturas sigue cerrado exclusivamente a wings_testing_codex.
        $deporte = Deporte::create(['nombre'=>'Historia prueba','tipo_liquidacion'=>'HORA','activo'=>true]);
        $nivel = Nivel::create(['nombre'=>'Historia prueba']);
        $grupo = Grupo::create(['deporte_id'=>$deporte->id,'nivel_id'=>$nivel->id,'activo'=>true]);
        $plan = GrupoPlan::create(['grupo_id'=>$grupo->id,'clases_por_semana'=>2,'precio_mensual'=>30000,'activo'=>true]);
        foreach ([true,false] as $i=>$pagada) {
            $alumno = Alumno::create(['nombre'=>'Historia '.$i,'apellido'=>'Prueba','dni'=>(string)(30100000+$i),
                'fecha_nacimiento'=>'2000-01-01','celular'=>'11-4000-0000','deporte_id'=>$deporte->id,
                'grupo_id'=>$grupo->id,'fecha_alta'=>'2026-10-01','activo'=>true]);
            DeudaCuota::create(['alumno_id'=>$alumno->id,'periodo'=>'2026-10','monto_original'=>30000,
                'monto_pagado'=>$pagada ? 30000 : 0,'estado'=>$pagada ? 'PAGADA' : 'PENDIENTE']);
            if ($pagada) Pago::create(['alumno_id'=>$alumno->id,'plan_id'=>$plan->id,'mes'=>10,'anio'=>2026,
                'monto_base'=>30000,'porcentaje_aplicado'=>100,'monto_final'=>30000,'monto_cuota'=>30000,
                'fecha_pago'=>'2026-10-09','estado'=>'COMPLETADO']);
        }
        $profesor = Profesor::create(['deporte_id'=>$deporte->id,'nombre'=>'Historia','apellido'=>'Prueba','dni'=>'31100000',
            'fecha_nacimiento'=>'1980-01-01','direccion'=>'Prueba','localidad'=>'Prueba','valor_hora'=>15000,'activo'=>true]);
        Liquidacion::create(['profesor_id'=>$profesor->id,'mes'=>10,'anio'=>2026,'tipo'=>'HORA',
            'total_calculado'=>90000,'estado'=>'CERRADA','estado_pago'=>'PENDIENTE']);
        Carbon::setTestNow('2026-10-09 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fila(string $tipo, int $id, string $corte): array
    {
        $r = app(HistorialAnaliticoReportesService::class)->obtener($tipo, CarbonImmutable::parse($corte), $id);
        $this->assertTrue($r['disponible']);
        $this->assertCount(1, $r['filas']);
        return $r['filas'][0];
    }

    public function test_cobrar_no_reduce_cuota_neta_y_perdonar_solo_descuenta_el_saldo(): void
    {
        $d = DeudaCuota::where('periodo', '2026-10')->where('monto_pagado', 0)->firstOrFail();
        $d->update(['monto_pagado'=>10000]);
        $this->assertSame(3000000, $this->fila('CUOTA', $d->id, '2026-10-09 23:59:59')['datos']['neto_centavos']);
        Carbon::setTestNow('2026-10-10 12:00:00');
        $d->update(['estado'=>'CONDONADA']);
        $this->assertSame(1000000, $this->fila('CUOTA', $d->id, '2026-10-10 23:59:59')['datos']['neto_centavos']);
        $this->assertSame(3000000, $this->fila('CUOTA', $d->id, '2026-10-09 23:59:59')['datos']['neto_centavos']);
    }

    public function test_tarifa_y_modalidad_nuevas_no_reescriben_el_dia_anterior(): void
    {
        $p = Profesor::firstOrFail();
        $antes = $this->fila('TARIFA', $p->id, '2026-10-09 23:59:59');
        Carbon::setTestNow('2026-10-10 12:00:00');
        $p->update(['valor_hora'=>23456.78, 'porcentaje_comision'=>18.25]);
        $p->deporte->update(['tipo_liquidacion'=>Deporte::TIPO_LIQUIDACION_COMISION]);
        $nuevo = $this->fila('TARIFA', $p->id, '2026-10-10 23:59:59');
        $this->assertSame(2345678, $nuevo['datos']['hora_centavos']);
        $this->assertSame(1825, $nuevo['datos']['comision_centesimas']);
        $this->assertSame('COMISION', $nuevo['datos']['tipo']);
        $this->assertSame($antes, $this->fila('TARIFA', $p->id, '2026-10-09 23:59:59'));
    }

    public function test_anulacion_posterior_no_borra_el_cobro_conocido_en_el_corte_anterior(): void
    {
        $p = Pago::firstOrFail();
        $antes = $this->fila('PAGO', $p->id, '2026-10-09 23:59:59');
        Carbon::setTestNow('2026-10-10 12:00:00');
        $p->update(['estado'=>Pago::ESTADO_ANULADO]);
        $this->assertSame('ANULADO', $this->fila('PAGO', $p->id, '2026-10-10 23:59:59')['datos']['estado']);
        $this->assertSame('COMPLETADO', $this->fila('PAGO', $p->id, '2026-10-09 23:59:59')['datos']['estado']);
        $this->assertSame(3000000, $antes['datos']['cuota_centavos']);
    }

    public function test_pagar_liquidacion_conserva_el_costo_y_el_estado_anterior(): void
    {
        $l = Liquidacion::where('estado', 'CERRADA')->firstOrFail();
        $antes = $this->fila('LIQUIDACION', $l->id, '2026-10-09 23:59:59');
        Carbon::setTestNow('2026-10-10 12:00:00');
        $l->update(['estado_pago'=>'PAGADA']);
        $nuevo = $this->fila('LIQUIDACION', $l->id, '2026-10-10 23:59:59');
        $this->assertSame($antes['datos']['calculado_centavos'], $nuevo['datos']['calculado_centavos']);
        $this->assertSame('PAGADA', $nuevo['datos']['estado_pago']);
        $this->assertSame('PENDIENTE', $this->fila('LIQUIDACION', $l->id, '2026-10-09 23:59:59')['datos']['estado_pago']);
    }

    public function test_baja_no_borra_historia_y_mes_anterior_a_cobertura_es_indisponible(): void
    {
        $d = DeudaCuota::where('monto_pagado', 0)->firstOrFail();
        $antes = $this->fila('CUOTA', $d->id, '2026-10-09 23:59:59');
        Carbon::setTestNow('2026-10-10 12:00:00');
        $d->delete();
        $s = app(HistorialAnaliticoReportesService::class);
        $this->assertSame([], $s->obtener('CUOTA', CarbonImmutable::parse('2026-10-10 23:59:59'), $d->id)['filas']);
        $this->assertSame($antes, $this->fila('CUOTA', $d->id, '2026-10-09 23:59:59'));
        $r = $s->obtener('CUOTA', CarbonImmutable::parse('2026-09-30 23:59:59'));
        $this->assertFalse($r['disponible']);
        $this->assertNull($r['filas']);
    }

    public function test_si_falla_historial_se_revierten_tanto_edicion_como_baja(): void
    {
        $d = DeudaCuota::where('monto_pagado', 0)->firstOrFail();
        $original = $d->monto_original;
        $conteo = DB::table(HistorialAnaliticoReportesService::TABLA)->count();
        $mock = \Mockery::mock(HistorialAnaliticoReportesService::class);
        $mock->shouldReceive('registrar')->twice()->andThrow(new \RuntimeException('Falla ficticia de historial'));
        $this->app->instance(HistorialAnaliticoReportesService::class, $mock);
        foreach (['editar', 'borrar'] as $accion) {
            try {
                $d = DeudaCuota::findOrFail($d->id);
                $accion === 'editar' ? $d->update(['monto_original'=>12345]) : $d->delete();
                $this->fail('Debió fallar la escritura atómica.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Falla ficticia de historial', $e->getMessage());
            }
            $this->assertSame($original, DeudaCuota::findOrFail($d->id)->monto_original);
            $this->assertSame($conteo, DB::table(HistorialAnaliticoReportesService::TABLA)->count());
        }
    }

    public function test_activacion_con_datos_existentes_no_retrofecha_su_historia(): void
    {
        DB::table(HistorialAnaliticoReportesService::TABLA)->delete();
        DB::table(HistorialAnaliticoReportesService::COBERTURA)->delete();
        $s = app(HistorialAnaliticoReportesService::class);
        $s->iniciar();
        $corte = CarbonImmutable::parse('2026-10-09 12:00:00');
        $this->assertCount(DeudaCuota::count(), $s->obtener('CUOTA', $corte)['filas']);
        $this->assertCount(Profesor::count(), $s->obtener('TARIFA', $corte)['filas']);
        $this->assertCount(Pago::count(), $s->obtener('PAGO', $corte)['filas']);
        $this->assertCount(Liquidacion::count(), $s->obtener('LIQUIDACION', $corte)['filas']);
        $this->assertFalse($s->obtener('CUOTA', $corte->subSecond())['disponible']);
    }

    public function test_ficha_vieja_y_reloj_retrocedido_no_falsean_el_estado_conocido(): void
    {
        $viejo = Profesor::firstOrFail();
        $antes = $this->fila('TARIFA', $viejo->id, '2026-10-09 23:59:59');
        Carbon::setTestNow('2026-10-11 12:00:00');
        Profesor::findOrFail($viejo->id)->update(['valor_hora'=>20000]);
        $viejo->update(['porcentaje_comision'=>30]);
        $r = $this->fila('TARIFA', $viejo->id, '2026-10-11 23:59:59');
        $this->assertSame(2000000, $r['datos']['hora_centavos']);
        $this->assertSame(3000, $r['datos']['comision_centesimas']);
        Carbon::setTestNow('2026-10-10 12:00:00');
        $viejo->update(['valor_hora'=>21000]);
        $this->assertSame($antes, $this->fila('TARIFA', $viejo->id, '2026-10-10 23:59:59'));
        $this->assertSame(2100000, $this->fila('TARIFA', $viejo->id, '2026-10-11 23:59:59')['datos']['hora_centavos']);
    }

    public function test_formato_json_distinto_no_duplica_estados_economicos_identicos(): void
    {
        $d = DeudaCuota::where('monto_pagado', 0)->firstOrFail();
        $fila = DB::table(HistorialAnaliticoReportesService::TABLA)->where('tipo', 'CUOTA')->where('origen_id', $d->id)->orderByDesc('id')->first();
        DB::table(HistorialAnaliticoReportesService::TABLA)->where('id', $fila->id)
            ->update(['datos'=>json_encode(json_decode($fila->datos, true), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)]);
        $cantidad = DB::table(HistorialAnaliticoReportesService::TABLA)->count();
        $d->update(['monto_pagado'=>100]);
        $this->assertSame($cantidad, DB::table(HistorialAnaliticoReportesService::TABLA)->count());
        $p = Profesor::firstOrFail();
        $tarifa = DB::table(HistorialAnaliticoReportesService::TABLA)->where('tipo', 'TARIFA')->where('origen_id', $p->id)->orderByDesc('id')->first();
        DB::table(HistorialAnaliticoReportesService::TABLA)->where('id', $tarifa->id)
            ->update(['datos'=>json_encode(array_reverse(json_decode($tarifa->datos, true), true), JSON_THROW_ON_ERROR)]);
        app(HistorialAnaliticoReportesService::class)->registrar($p);
        $this->assertSame($cantidad, DB::table(HistorialAnaliticoReportesService::TABLA)->count());
    }
}
