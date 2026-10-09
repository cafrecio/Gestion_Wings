<?php

namespace Tests\Feature;

use App\Models\{Alumno, CajaOperativa, CashflowMovimiento, DeudaCuota, Subrubro, User};
use App\Services\{CajaService, PagoCuotaService, ReporteMensualService};
use Carbon\Carbon;
use Database\Seeders\ReportesEscenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReporteMensualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Reproducir el estado real de migrate:fresh, sin heredar el club
        // operativo que Tests\TestCase prepara para los tests históricos.
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'PENDIENTE']);
        $this->seed(ReportesEscenarioSeeder::class);
        Carbon::setTestNow('2026-10-09 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_seis_meses_y_mes_actual_concilian_sin_arrastre_en_el_resultado(): void
    {
        $servicio = app(ReporteMensualService::class);
        $r = $servicio->obtener('2026-10');
        $this->assertSame(66000000, $r['ingresos']);
        $this->assertSame(7500000, $r['egresos']);
        $this->assertSame(58500000, $r['resultado']);
        $this->assertSame(436000000, $r['disponible']);
        $this->assertSame(9000000, $r['aportes']);
        $this->assertSame(4000000, $r['retiros']);
        $this->assertSame(69000000, $r['deuda']['total']);
        $this->assertSame(12000000, $r['deuda']['mes']);
        $this->assertSame(57000000, $r['deuda']['anteriores']);
        $this->assertSame(9000000, $r['por_pagar']['total']);
        $this->assertSame(['cajas' => 1, 'liquidaciones' => 1, 'liquidaciones_abiertas' => 1, 'asistencia' => 1, 'revision' => 1], $r['avisos']);
        $this->assertCount(6, $servicio->evolucion('2026-10'));
        $this->assertSame(24, array_sum(array_column($r['alumnos'], 'cantidad')));
        if (getenv('WINGS_CAPTURAS_ANTERIORES') === '1') $this->capturar($r, $servicio->evolucion('2026-10'));
    }

    public function test_cobro_posterior_no_borra_deuda_del_cierre_anterior(): void
    {
        $s = app(ReporteMensualService::class);
        $this->assertSame(60000000, $s->obtener('2026-08')['deuda']['total']);
        $this->assertSame(57000000, $s->obtener('2026-09')['deuda']['total']);
        $this->assertSame(55000000, $s->obtener('2026-08')['resultado']);
        $this->assertSame(67500000, $s->obtener('2026-09')['resultado']);
        $deuda = DeudaCuota::where('alumno_id', Alumno::orderBy('id')->skip(23)->first()->id)->where('periodo', '2026-08')->firstOrFail();
        app(PagoCuotaService::class)->condonarDeuda($deuda->id, 'Excepción ficticia para comprobar la historia', User::where('rol', 'ADMIN')->first()->id);
        $this->assertSame(60000000, $s->obtener('2026-08')['deuda']['total']);
        $this->assertSame(57000000, $s->obtener('2026-09')['deuda']['total']);
        $this->assertSame(66000000, $s->obtener('2026-10')['deuda']['total']);
    }

    public function test_validar_caja_solo_traslada_pendiente_a_confirmado_sin_duplicar(): void
    {
        $s = app(ReporteMensualService::class);
        $antes = $s->obtener('2026-10');
        $caja = CajaOperativa::where('estado', 'CERRADA')->firstOrFail();
        $admin = User::where('rol', 'ADMIN')->firstOrFail();
        app(CajaService::class)->validarCaja($caja->id, $admin->id);
        app(CajaService::class)->validarCaja($caja->id, $admin->id);
        $despues = $s->obtener('2026-10');
        $this->assertSame($antes['resultado'], $despues['resultado']);
        $this->assertSame($antes['disponible'], $despues['disponible']);
        $this->assertSame(0, array_sum(array_column($despues['cajas'], 'pendiente')));
        $this->assertSame(1, CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_CAJA)->where('referencia_id', $caja->id)->count());
    }

    public function test_gastos_comunes_se_restaron_una_vez_y_afecta_caja_no_es_clasificacion(): void
    {
        $s = app(ReporteMensualService::class);
        $todo = $s->obtener('2026-10');
        $this->assertFalse(Subrubro::where('nombre', 'Luz')->first()->afecta_caja);
        $sumas = Alumno::distinct()->pluck('deporte_id')->sum(fn ($id) => $s->obtener('2026-10', $id)['resultado']);
        // Ingresos sin deporte (ventas) se presentan aparte, sin repartirlos.
        $this->assertSame(60000000, $sumas);
        $this->assertSame(7500000, $todo['gastos_club']);
        $this->assertSame($sumas + 6000000 - $todo['gastos_club'], $todo['resultado']);
    }

    public function test_historia_insuficiente_y_clasificacion_desconocida_no_simulan_cero(): void
    {
        $s = app(ReporteMensualService::class);
        $this->assertNull($s->obtener('2026-03')['deuda']['total']);
        $this->assertNull($s->obtener('2026-03')['disponible']);
        DB::table('cashflow_movimientos')->where('fecha', '2026-10-06')->limit(1)->update(['reporte_clasificacion' => null]);
        $this->assertNull($s->obtener('2026-10')['resultado']);
        $this->assertSame(1, $s->obtener('2026-10')['sin_clasificar']);
    }

    public function test_fecha_real_anterior_corrige_su_mes_y_ajuste_actual_conserva_el_anterior(): void
    {
        $s = app(ReporteMensualService::class);
        $alumno = Alumno::orderBy('id')->skip(21)->firstOrFail();
        $admin = User::where('rol', 'ADMIN')->firstOrFail();
        $tipo = \App\Models\TipoCaja::where('abreviatura', 'MP')->firstOrFail();
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $alumno->id, 'tipo_caja_id' => $tipo->id,
            'usuario_admin_id' => $admin->id, 'fecha_pago' => '2026-09-20', 'items' => [['periodo' => '2026-04', 'monto' => 30000]]]);
        $this->assertSame(54000000, $s->obtener('2026-09')['deuda']['total']);
        $this->assertSame(60000000, $s->obtener('2026-08')['deuda']['total']);
        $deuda = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-09')->firstOrFail();
        $deuda->update(['monto_original' => 20000]);
        $this->assertSame(54000000, $s->obtener('2026-09')['deuda']['total']);
        $this->assertSame(65000000, $s->obtener('2026-10')['deuda']['total']);
    }

    public function test_creacion_y_pago_del_mismo_objeto_no_suman_dos_veces_y_deshacer_limpia(): void
    {
        $alumno = Alumno::firstOrFail();
        $deuda = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-11', 'monto_original' => 30000,
            'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $deuda->update(['monto_pagado' => 30000, 'estado' => 'PAGADA']);
        $this->assertSame(0, (int) DB::table('reporte_eventos')->where('deuda_cuota_id', $deuda->id)->sum('delta_centavos'));
        // Deshacer usa borrado por consulta: el FK debe cubrirlo aunque no haya observer.
        DeudaCuota::whereKey($deuda->id)->delete();
        $this->assertDatabaseMissing('reporte_eventos', ['deuda_cuota_id' => $deuda->id]);
    }

    public function test_cierre_y_pago_de_liquidacion_conservan_el_saldo_del_cierre_anterior(): void
    {
        $s = app(ReporteMensualService::class);
        $l = \App\Models\Liquidacion::where('estado', 'ABIERTA')->firstOrFail();
        Carbon::setTestNow('2026-10-12 10:00:00');
        $l->update(['estado' => 'CERRADA']);
        $this->assertSame(18000000, $s->obtener('2026-10')['por_pagar']['total']);
        Carbon::setTestNow('2026-11-03 10:00:00');
        $l->update(['estado_pago' => 'PAGADA', 'pagada_fecha' => '2026-11-03', 'pagada_at' => now()]);
        $this->assertSame(18000000, $s->obtener('2026-10')['por_pagar']['total']);
        $this->assertSame(9000000, $s->obtener('2026-11')['por_pagar']['total']);
    }

    public function test_evento_y_deuda_se_desatan_juntos_si_falla_guardar_la_historia(): void
    {
        $d = DeudaCuota::where('estado', 'PENDIENTE')->firstOrFail();
        $original = $d->monto_original;
        $dispatcher = DeudaCuota::getEventDispatcher();
        DeudaCuota::setEventDispatcher(clone $dispatcher);
        DeudaCuota::updated(fn () => throw new \RuntimeException('Fallo de prueba después de escribir la historia'));
        $eventos = DB::table('reporte_eventos')->count();
        try {
            try {
                $d->update(['monto_original' => 123]);
                $this->fail('Debió fallar la historia.');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('Fallo de prueba', $e->getMessage());
            }
            $this->assertSame($original, $d->fresh()->monto_original);
            $this->assertSame($eventos, DB::table('reporte_eventos')->count());
        } finally {
            DeudaCuota::setEventDispatcher($dispatcher);
        }
        // Activación diferida: la misma aplicación debe seguir escribiendo en
        // una base que todavía no tenga la migración de Reportes.
        $schema = \Illuminate\Support\Facades\Schema::getFacadeRoot();
        \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')->with('reporte_eventos')->andReturn(false);
        try {
            $d->update(['monto_original' => 20000]);
            $this->assertSame($eventos, DB::table('reporte_eventos')->count());
            $m = CashflowMovimiento::create(['fecha' => today(), 'subrubro_id' => Subrubro::where('nombre', 'Luz')->first()->id,
                'tipo_caja_id' => \App\Models\TipoCaja::first()->id, 'monto' => -100, 'usuario_admin_id' => User::where('rol', 'ADMIN')->first()->id,
                'reporte_tipo' => 'EGRESO', 'reporte_clasificacion' => 'NEGOCIO', 'reporte_deporte_id' => 1]);
            $this->assertArrayNotHasKey('reporte_tipo', $m->getAttributes());
            $this->assertArrayNotHasKey('reporte_clasificacion', $m->getAttributes());
            $this->assertArrayNotHasKey('reporte_deporte_id', $m->getAttributes());
            $p = \App\Models\Profesor::firstOrFail();
            $p->update(['nombre' => 'Otro ficticio']);
            $sub = app(\App\Services\SubrubroSueldoService::class)->paraProfesor($p);
            $this->assertNull($sub->clasificacion_resultado);
        } finally {
            \Illuminate\Support\Facades\Schema::swap($schema);
        }
    }

    private function capturar(array $reporte, array $evolucion): void
    {
        $sanear = fn ($html) => preg_replace([
            '/(<meta name="csrf-token" content=")[^"]+/', '/(name="_token" value=")[^"]+/',
        ], ['$1FICTICIO', '$1FICTICIO'], $html);
        $directorio = base_path('docs/06-pruebas/B12-A23/capturas');
        if (!is_dir($directorio)) mkdir($directorio, 0775, true);
        foreach (['reportes', 'inicio'] as $pantalla) {
            $ruta = '/__codex/propuesta-'.$pantalla;
            \Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'ensure.admin.web'])->get($ruta,
                fn () => view()->file(base_path('docs/06-pruebas/B12-A23/propuesta-'.$pantalla.'.blade.php'), ['reporte' => $reporte, 'evolucion' => $evolucion]));
            $html = $this->actingAs(User::where('rol', 'ADMIN')->firstOrFail())->get($ruta)->assertOk()->getContent();
            $html = str_replace(url('/').'/build/', 'file:///'.str_replace('\\', '/', public_path('build')).'/', $html);
            $html = str_replace(['src="/build/', 'href="/build/'], ['src="file:///'.str_replace('\\', '/', public_path('build')).'/',
                'href="file:///'.str_replace('\\', '/', public_path('build')).'/'], $html);
            file_put_contents($directorio.'/'.$pantalla.'.html', $sanear($html));
        }
        $login = $this->get('/login')->getContent();
        // Autenticado redirige: guardar login con sesión cerrada para calibrar 375.
        \Illuminate\Support\Facades\Auth::logout();
        $login = $this->get('/login')->assertOk()->getContent();
        $login = str_replace(url('/').'/build/', 'file:///'.str_replace('\\', '/', public_path('build')).'/', $login);
        file_put_contents($directorio.'/login.html', $sanear($login));
        file_put_contents($directorio.'/calculos.json', json_encode(['reporte' => $reporte, 'evolucion' => array_map(fn ($r) =>
            ['mes' => $r['mes'], 'resultado' => $r['resultado'], 'deuda' => $r['deuda']['total']], $evolucion)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function test_descuento_retroactivo_no_deja_deuda_artificial_en_su_mes(): void
    {
        $s = app(ReporteMensualService::class);
        $base = $s->obtener('2026-09')['deuda']['total'];
        Carbon::setTestNow('2026-09-20 10:00:00');
        $modelo = Alumno::firstOrFail();
        $a = Alumno::create(['nombre' => 'Retroactivo', 'apellido' => 'Prueba', 'dni' => '30001000', 'fecha_nacimiento' => '2000-01-01',
            'celular' => '1100000000', 'grupo_id' => $modelo->grupo_id, 'deporte_id' => $modelo->deporte_id, 'fecha_alta' => '2026-09-20', 'activo' => true]);
        \App\Models\AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $modelo->planActivo->plan_id, 'fecha_desde' => '2026-09-20', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => '2026-09', 'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        Carbon::setTestNow('2026-10-09 10:00:00');
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $a->id, 'tipo_caja_id' => \App\Models\TipoCaja::where('abreviatura', 'MP')->first()->id,
            'usuario_admin_id' => User::where('rol', 'ADMIN')->first()->id, 'fecha_pago' => '2026-09-25', 'items' => [['periodo' => '2026-09', 'monto' => 21000]]]);
        $this->assertSame($base, $s->obtener('2026-09')['deuda']['total']);
        $this->assertSame('PAGADA', DeudaCuota::where('alumno_id', $a->id)->first()->estado);
    }

    public function test_sueldo_generado_conserva_deporte_al_pagar_y_anular_no_borra_historia(): void
    {
        $l = \App\Models\Liquidacion::where('estado', 'CERRADA')->firstOrFail();
        $p = $l->profesor;
        $deporteOriginal = $p->deporte_id;
        $subrubro = app(\App\Services\SubrubroSueldoService::class)->paraProfesor($p);
        $this->assertSame('NEGOCIO', $subrubro->clasificacion_resultado);
        $subrubro->update(['clasificacion_resultado' => null]);
        $reutilizado = app(\App\Services\SubrubroSueldoService::class)->paraProfesor($p);
        $this->assertSame($subrubro->id, $reutilizado->id);
        $this->assertSame('NEGOCIO', $reutilizado->clasificacion_resultado);
        $p->update(['deporte_id' => \App\Models\Deporte::where('id', '!=', $p->deporte_id)->first()->id]);
        (new \App\Services\LiquidacionPagoService())->marcarComoPagada($l->id, [
            'admin_id' => User::where('rol', 'ADMIN')->first()->id, 'fecha_pago' => '2026-10-09',
            'tipo_caja_id' => \App\Models\TipoCaja::where('abreviatura', 'BNA')->first()->id, 'subrubro_id' => $subrubro->id]);
        $m = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_LIQUIDACION)->firstOrFail();
        $this->assertSame($deporteOriginal, (int) $m->reporte_deporte_id);
        $this->assertSame('NEGOCIO', $m->reporte_clasificacion);
        $s = app(ReporteMensualService::class);
        $septiembre = $s->obtener('2026-09');
        $pago = \App\Models\Pago::where('fecha_pago', '2026-09-06')->firstOrFail();
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Anulación ficticia para probar el cierre', User::where('rol', 'ADMIN')->first()->id);
        $this->assertSame($septiembre['deuda']['total'], $s->obtener('2026-09')['deuda']['total']);
        $this->assertSame($septiembre['resultado'], $s->obtener('2026-09')['resultado']);
    }

    public function test_inicio_admin_usa_el_motor_mensual_y_no_cambia_por_parametros(): void
    {
        $this->actingAs(User::where('rol', 'ADMIN')->first());
        $respuesta = $this->get(route('admin.dashboard', ['mes' => '2026-08', 'deporte_id' => 999]));
        $respuesta->assertOk()->assertViewIs('admin.dashboard');
        $this->assertSame(app(ReporteMensualService::class)->obtener('2026-10'), $respuesta->viewData('reporte'));
        $respuesta->assertSee('$660.000')->assertSee('$75.000')->assertSee('$585.000')
            ->assertSee('$4.360.000')->assertSee('$690.000')->assertSee('$90.000')->assertDontSee('Datos ficticios');
        // Reportes sigue esperando elección visual: no publicar vínculos rotos.
        $respuesta->assertSee('aria-disabled="true"', false)->assertDontSee('reportes.html');
        if (getenv('WINGS_CAPTURAS') === '1') {
            $html = preg_replace('/(<meta name="csrf-token" content=")[^"]+/', '$1FICTICIO', $respuesta->getContent());
            $html = preg_replace('/(name="_token" value=")[^"]+/', '$1FICTICIO', $html);
            $html = preg_replace('#https?://[^/]+/build/#', '../../../../public/build/', $html);
            $html = preg_replace('#https?://[^/]+/img/#', '../../../../public/img/', $html);
            $html = preg_replace('/[ \t]+(?=\r?$)/m', '', $html);
            file_put_contents(base_path('docs/06-pruebas/B12-A23/capturas/inicio-aplicado.html'), $html);
        }
    }

    public function test_avisos_abren_exactamente_los_pendientes_incluidos_meses_antiguos(): void
    {
        $vieja = CajaOperativa::where('estado', 'CERRADA')->firstOrFail()->replicate();
        $vieja->apertura_at = '2026-04-10 09:00:00';
        $vieja->save();
        $liq = \App\Models\Liquidacion::where('estado', 'CERRADA')->firstOrFail();
        $pagada = $liq->replicate();
        $pagada->mes = 5;
        $pagada->estado_pago = 'PAGADA';
        $pagada->save();
        $antigua = $liq->replicate();
        $antigua->mes = 4;
        $antigua->save();
        $this->actingAs(User::where('rol', 'ADMIN')->first());
        $inicio = $this->get(route('admin.dashboard'))->assertOk();
        $avisos = $inicio->viewData('reporte')['avisos'];
        $this->assertSame(2, $avisos['cajas']);
        $this->assertSame(2, $avisos['liquidaciones']);
        $cajas = $this->get(route('web.caja.index', ['pendientes' => 1]))->assertOk();
        $this->assertSame($avisos['cajas'], $cajas->viewData('cajas')->count());
        $this->assertTrue($cajas->viewData('cajas')->contains('id', $vieja->id));
        $this->assertSame('', $cajas->viewData('mes'));
        $this->assertSame(['CERRADA'], $cajas->viewData('cajas')->pluck('estado')->unique()->values()->all());
        $liqs = $this->get(route('web.liquidaciones.index', ['pendientes' => 1]))->assertOk()->viewData('liquidaciones');
        $this->assertSame($avisos['liquidaciones'], $liqs->total());
        $this->assertTrue($liqs->contains('id', $antigua->id));
        $this->assertFalse($liqs->contains('id', $pagada->id));
        $this->assertSame($avisos['asistencia'], $this->get(route('web.clases.index', ['estado' => 'finalizada']))->assertOk()->viewData('clasesFiltradas')->total());
        $this->assertSame($avisos['revision'], $this->get(route('web.revision-cobranza.index', ['estado' => 'PENDIENTE']))->assertOk()->viewData('revisiones')->total());
    }

    public function test_inicio_y_enlaces_admin_no_exponen_importes_a_otros_roles(): void
    {
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $this->actingAs($usuario)->get(route('admin.dashboard'))->assertForbidden()->assertDontSee('$4.360.000');
            $this->get(route('web.liquidaciones.index', ['pendientes' => 1]))->assertForbidden();
        }
        $this->post(route('logout'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_inicio_sin_migracion_muestra_indisponible_sin_consultar_historia(): void
    {
        $this->actingAs(User::where('rol', 'ADMIN')->first());
        $schema = \Illuminate\Support\Facades\Schema::getFacadeRoot();
        \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')->with('reporte_eventos')->andReturn(false);
        $reportes = \Mockery::mock(ReporteMensualService::class)->makePartial();
        $reportes->shouldNotReceive('obtener');
        $this->app->instance(ReporteMensualService::class, $reportes);
        try {
            $respuesta = $this->get(route('admin.dashboard'))->assertOk()->assertSee('Sin historial');
            $this->assertNull($respuesta->viewData('reporte')['disponible']);
            $this->assertNull($respuesta->viewData('reporte')['deuda']['total']);
            $this->assertSame(1, $respuesta->viewData('reporte')['avisos']['cajas']);
        } finally {
            \Illuminate\Support\Facades\Schema::swap($schema);
        }
    }

    public function test_modificar_saldo_inicial_no_reescribe_disponible_anterior(): void
    {
        $s = app(ReporteMensualService::class);
        $agosto = $s->obtener('2026-08')['disponible'];
        $actual = $s->obtener('2026-10')['disponible'];
        \App\Models\TipoCaja::where('abreviatura', 'MP')->first()->update(['saldo_inicial' => 80000]);
        $this->assertSame($agosto, $s->obtener('2026-08')['disponible']);
        $this->assertSame($actual + 2000000, $s->obtener('2026-10')['disponible']);
        $rubro = Subrubro::where('nombre', 'Venta de prueba')->firstOrFail()->rubro;
        $octubre = $s->obtener('2026-10');
        $rubro->update(['tipo' => 'EGRESO']);
        $despues = $s->obtener('2026-10');
        $this->assertSame($octubre['ingresos'], $despues['ingresos']);
        $this->assertSame($octubre['egresos'], $despues['egresos']);
        $this->assertSame($octubre['disponible'], $despues['disponible']);
        $pendiente = \App\Models\MovimientoOperativo::whereHas('cajaOperativa', fn ($q) => $q->where('estado', 'CERRADA'))->firstOrFail();
        app(CajaService::class)->validarCaja($pendiente->caja_operativa_id, User::where('rol', 'ADMIN')->first()->id);
        $this->assertSame($octubre['disponible'], $s->obtener('2026-10')['disponible']);
        $sinClasificar = Subrubro::create(['nombre' => 'Concepto sin clasificar', 'rubro_id' => $rubro->id,
            'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true]);
        $cajaEditable = CajaOperativa::create(['usuario_operativo_id' => $pendiente->usuario_id, 'apertura_at' => now(),
            'estado' => 'ABIERTA', 'tipo_caja_efectivo_id' => $pendiente->tipo_caja_id, 'efectivo_inicial' => 0]);
        $mov = \App\Models\MovimientoOperativo::create(['caja_operativa_id' => $cajaEditable->id,
            'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->first()->id, 'tipo_caja_id' => $pendiente->tipo_caja_id,
            'fecha' => today(), 'monto' => 100, 'usuario_id' => $pendiente->usuario_id, 'estado' => 'ACTIVO']);
        $mov->load('subrubro');
        $mov = app(CajaService::class)->actualizarMovimientoEnCaja($cajaEditable->id, $mov->id, [
            'subrubro_id' => $sinClasificar->id, 'tipo_caja_id' => $mov->tipo_caja_id, 'monto' => 100,
            'fecha' => today()->toDateString(), 'observaciones' => 'Edición ficticia']);
        $this->assertNull($mov->reporte_clasificacion);
        $mov = app(CajaService::class)->actualizarMovimientoEnCaja($cajaEditable->id, $mov->id, [
            'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->first()->id, 'tipo_caja_id' => $mov->tipo_caja_id,
            'monto' => 100, 'fecha' => today()->toDateString(), 'observaciones' => 'Corrección ficticia']);
        $this->assertSame('NEGOCIO', $mov->reporte_clasificacion);
    }
}
