<?php

namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, Clase, Deporte, DeporteLiquidacionVigencia, Grupo, GrupoPlan, Liquidacion, Nivel, Pago, Profesor, User};
use App\Services\{DeporteLiquidacionService, LiquidacionService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DeporteLiquidacionVigenciaT45Test extends TestCase
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

    public function test_abierta_bloquea_y_explicita_numero_profesora_y_periodo(): void
    {
        $l = app(LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id, 10, 2026);
        $this->cambiar()->assertSessionHasErrors('tipo_liquidacion');
        $this->get(route('web.deportes.edit', $this->deporte->id))->assertOk()->assertSee('#'.$l->id)->assertSee('Ana Prueba')->assertSee('10/2026');
        $this->assertDatabaseCount('deporte_liquidacion_vigencias', 0);
        $this->assertSame('HORA', $this->deporte->fresh()->tipo_liquidacion);
    }

    public function test_programacion_guarda_vigencia_y_autor_y_conserva_mes_actual(): void
    {
        $this->cambiar()->assertSessionHasNoErrors()->assertRedirect(route('web.deportes.edit', $this->deporte->id));
        $v = DeporteLiquidacionVigencia::sole();
        $this->assertSame('2026-11-01', $v->vigente_desde->toDateString());
        $this->assertSame($this->admin->id, $v->usuario_id);
        $this->assertSame('HORA', $this->deporte->fresh()->tipo_liquidacion);
        $this->assertSame('HORA', $this->deporte->fresh()->tipoLiquidacionParaPeriodo(9, 2026));
        $this->assertSame('COMISION', $this->deporte->fresh()->tipoLiquidacionParaPeriodo(11, 2026));
        $this->assertSame('HORA', $this->deporte->fresh()->getRawOriginal('tipo_liquidacion'));
    }

    public function test_mes_actual_liquida_horas_y_siguiente_comision_sobre_cobro_real(): void
    {
        $this->clase('2026-10-10');
        $noviembre = $this->clase('2026-11-10');
        $plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 10000, 'activo' => true]);
        $a = Alumno::create(['nombre' => 'Luz', 'apellido' => 'Prueba', 'dni' => '70000002', 'fecha_nacimiento' => '2010-01-01',
            'celular' => '1140000000', 'deporte_id' => $this->deporte->id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        Asistencia::create(['clase_id' => $noviembre->id, 'alumno_id' => $a->id, 'presente' => true]);
        Pago::create(['alumno_id' => $a->id, 'plan_id' => $plan->id, 'mes' => 11, 'anio' => 2026,
            'monto_base' => 10000, 'monto_final' => 10000, 'monto_cuota' => 10000, 'porcentaje_aplicado' => 100, 'fecha_pago' => '2026-11-10', 'estado' => 'COMPLETADO']);
        $this->cambiar()->assertSessionHasNoErrors();
        $service = app(LiquidacionService::class);
        $actual = $service->generarLiquidacionMensual($this->profesor->id, 10, 2026);
        $this->assertSame('HORA', $actual->tipo);
        $this->assertSame('5000.00', $actual->total_calculado);
        $this->travelTo(Carbon::parse('2026-11-12'));
        $siguiente = $service->generarLiquidacionMensual($this->profesor->id, 11, 2026);
        $this->assertSame('COMISION', $siguiente->tipo);
        $this->assertSame('2000.00', $siguiente->total_calculado);
        $this->assertSame('20.00', $siguiente->porcentaje_comision_aplicado);
        $this->assertSame('COMISION', $this->profesor->fresh()->tipo_liquidacion);
        $this->assertSame('HORA', $service->previsualizarLiquidacion($this->profesor->id, 9, 2026)['tipo']);
    }

    public function test_cerradas_y_pagadas_conservan_tipo_importe_y_tarifa(): void
    {
        foreach ([8, 9] as $mes) {
            $this->clase('2026-'.str_pad($mes, 2, '0', STR_PAD_LEFT).'-10');
            $l = app(LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id, $mes, 2026);
            $l->update(['estado' => 'CERRADA']);
            if ($mes === 8) $l->update(['estado_pago' => 'PAGADA']);
        }
        $antes = Liquidacion::orderBy('id')->get()->map->getAttributes()->all();
        $this->cambiar()->assertSessionHasNoErrors();
        $this->assertSame($antes, Liquidacion::orderBy('id')->get()->map->getAttributes()->all());
        $this->travelTo(Carbon::parse('2026-11-10'));
        $this->assertSame($antes, Liquidacion::orderBy('id')->get()->map->getAttributes()->all());
    }

    public function test_cambiar_solo_nombre_con_abierta_no_programa_otra_forma(): void
    {
        app(LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id, 10, 2026);
        $this->cambiar('HORA', 'Patín renombrado')->assertSessionHasNoErrors()->assertRedirect(route('web.deportes.index'));
        $this->assertSame('Patín renombrado', $this->deporte->fresh()->nombre);
        $this->assertDatabaseCount('deporte_liquidacion_vigencias', 0);
    }

    public function test_sin_porcentaje_avisa_al_cambiar_y_rechaza_liquidacion_y_preview_sin_inventarlo(): void
    {
        $this->profesor->update(['porcentaje_comision' => null]);
        $this->cambiar()->assertSessionHasNoErrors();
        $this->get(route('web.deportes.edit', $this->deporte->id))->assertOk()->assertSee('falta cargar un porcentaje')->assertSee('Ana Prueba');
        $this->get(route('web.liquidaciones.create', ['mes' => 11, 'anio' => 2026]))->assertOk()->assertSee('Falta cargar el porcentaje de comisión');
        $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesor->id, 'mes' => 11, 'anio' => 2026])->assertSessionHas('error');
        $this->assertDatabaseCount('liquidaciones', 0);
        $this->assertNull($this->profesor->fresh()->porcentaje_comision);
        $this->expectExceptionMessage('porcentaje de comisión');
        app(LiquidacionService::class)->previsualizarLiquidacion($this->profesor->id, 11, 2026);
    }

    public function test_operativo_y_profesor_no_ven_formulario_ni_programan_por_ruta(): void
    {
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol, 'activo' => true]));
            $this->get(route('web.deportes.edit', $this->deporte->id))->assertForbidden();
            $this->put(route('web.deportes.update', $this->deporte->id), ['nombre' => 'Forzado', 'tipo_liquidacion' => 'COMISION'])->assertForbidden();
            $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesor->id, 'mes' => 11, 'anio' => 2026])->assertForbidden();
        }
        $this->assertDatabaseCount('deporte_liquidacion_vigencias', 0);
    }

    public function test_cambios_sucesivos_y_reenvios_conservan_vigencias_anteriores(): void
    {
        $this->cambiar()->assertSessionHasNoErrors();
        $this->cambiar()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('deporte_liquidacion_vigencias', 1);
        $this->travelTo(Carbon::parse('2026-11-10'));
        $this->cambiar('HORA')->assertSessionHasNoErrors();
        $d = $this->deporte->fresh();
        $this->assertSame('HORA', $d->tipoLiquidacionParaPeriodo(10, 2026));
        $this->assertSame('COMISION', $d->tipoLiquidacionParaPeriodo(11, 2026));
        $this->assertSame('HORA', $d->tipoLiquidacionParaPeriodo(12, 2026));
        $this->assertDatabaseCount('deporte_liquidacion_vigencias', 2);
    }

    public function test_programar_en_diciembre_empieza_en_enero_del_ano_siguiente(): void
    {
        $this->travelTo(Carbon::parse('2026-12-15'));
        $this->cambiar()->assertSessionHasNoErrors();
        $this->assertSame('2027-01-01', DeporteLiquidacionVigencia::sole()->vigente_desde->toDateString());
    }

    public function test_reporte_de_sueldos_usa_vigencia_para_estimado_y_conserva_corte_anterior(): void
    {
        \Illuminate\Support\Facades\DB::table('reporte_analitico_cobertura')->where('id', 1)->update(['desde' => '2026-10-01 00:00:00']);
        $this->profesor->update(['valor_hora' => 5001]);
        $this->clase('2026-10-10');
        $this->clase('2026-11-10');
        $this->cambiar()->assertSessionHasNoErrors();
        $reporte = app(\App\Services\ReporteSueldosService::class);
        $octubre = $reporte->obtener('2026-10');
        $this->assertSame(500100, $octubre['profesores'][0]['costo']);
        $this->travelTo(Carbon::parse('2026-11-12'));
        $this->assertSame(500100, $reporte->obtener('2026-10')['profesores'][0]['costo']);
        $noviembre = $reporte->obtener('2026-11');
        $this->assertSame('Comisión sobre cuotas cobradas', $noviembre['profesores'][0]['base']);
        $this->assertSame(0, $noviembre['profesores'][0]['costo']);
    }

    public function test_porcentaje_se_puede_completar_antes_de_la_vigencia_sin_borrar_tarifa_actual(): void
    {
        $this->profesor->update(['porcentaje_comision' => null]);
        $this->cambiar()->assertSessionHasNoErrors();
        $this->get(route('web.profesores.edit', $this->profesor->id))->assertOk()->assertSee('data-tipo-siguiente="COMISION"', false);
        $datos = $this->profesor->only(['nombre', 'apellido', 'dni', 'fecha_nacimiento', 'direccion', 'localidad', 'deporte_id']);
        $datos['fecha_nacimiento'] = '1980-01-01';
        $this->put(route('web.profesores.update', $this->profesor->id), $datos + ['telefono' => '1140000000', 'valor_hora' => 5000, 'porcentaje_comision' => 25])->assertSessionHasNoErrors();
        $this->assertSame('5000.00', $this->profesor->fresh()->valor_hora);
        $this->assertSame('25.00', $this->profesor->fresh()->porcentaje_comision);
        $this->assertSame('HORA', $this->profesor->fresh()->tipo_liquidacion);
    }

    public function test_nombre_solo_con_programacion_pendiente_no_la_cancela_aunque_haya_abierta(): void
    {
        $this->cambiar()->assertSessionHasNoErrors();
        app(LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id, 10, 2026);
        $this->cambiar('COMISION', 'Patín nuevo nombre')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('deporte_liquidacion_vigencias', 1);
        $this->assertSame('Patín nuevo nombre', $this->deporte->fresh()->nombre);
    }

    public function test_pantallas_explican_programacion_bloqueo_y_porcentaje_y_exportan_html_real(): void
    {
        $this->profesor->update(['porcentaje_comision' => null]);
        $this->clase('2026-11-10');
        $this->cambiar()->assertSessionHasNoErrors();
        if (getenv('WINGS_CAPTURAS') === '1') $this->withVite();
        $programado = $this->get(route('web.deportes.edit', $this->deporte->id))->assertOk()->assertSee('Programado: Por comisión')->assertSee('01/11/2026');
        $nueva = $this->get(route('web.liquidaciones.create', ['mes' => 11, 'anio' => 2026]))->assertOk()->assertSee('Falta cargar el porcentaje de comisión');
        $editarProfesora = $this->get(route('web.profesores.edit', $this->profesor->id))->assertOk();
        $crearProfesora = $this->get(route('web.profesores.create'))->assertOk();
        app(LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id, 10, 2026);
        $this->cambiar('HORA')->assertSessionHasErrors('tipo_liquidacion');
        $bloqueado = $this->get(route('web.deportes.edit', $this->deporte->id))->assertOk()->assertSee('hay liquidaciones abiertas');
        if (getenv('WINGS_CAPTURAS') !== '1') return;
        Auth::logout();
        $paginas = ['00-login' => $this->get(route('login'))->assertOk()->getContent(), '01-programado' => $programado->getContent(),
            '02-bloqueado' => $bloqueado->getContent(), '03-liquidacion-porcentaje' => $nueva->getContent(),
            '04-profesora-editar' => $editarProfesora->getContent(), '05-profesora-nueva' => $crearProfesora->getContent()];
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t45');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        foreach ($paginas as $nombre => $html) {
            $html = preg_replace('~https?://[^/"\s]+/(build/|img/|favicon|apple-touch|site.webmanifest)~', '../../../../public/$1', $html);
            $html = preg_replace('~(<meta name="csrf-token" content=")[^"]*~', '$1', $html);
            $html = preg_replace('~(<input[^>]*name="_token"[^>]*value=")[^"]*~', '$1', $html);
            file_put_contents($dir.'/'.$nombre.'.html', rtrim(preg_replace('/[\t ]+$/m', '', $html)).PHP_EOL);
        }
    }
}
