<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CajaOperativa, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, MovimientoOperativo, Nivel, Pago, Profesor, Subrubro, TipoCaja, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PRU-04 · Prueba técnica · Seguridad (Claude, 25/10/2026).
 *
 * Las mismas operaciones de los días 4 a 6, forzadas por fuera de la pantalla y por cada rol.
 * Fuera de tests/ a propósito: no cambia el contador. Solo en la base descartable de Claude.
 *   DB_DATABASE=wings_testing_claude php artisan test docs/06-pruebas/PRU-04/prueba-tecnica/SeguridadDiasPruebaTest.php
 */
class SeguridadDiasPruebaTest extends TestCase
{
    use RefreshDatabase;

    private User $duenio, $sandra, $pablo, $lucia, $mariela;
    private TipoCaja $efectivo, $galicia;
    private Alumno $alumno;
    private CajaOperativa $cajaSandra;
    private Clase $claseMariela;
    private Pago $pago;

    protected function beforeRefreshingDatabase(): void
    {
        if (!in_array(config('database.connections.mysql.database'), ['wings_testing_claude', 'wings_testing_gemini', 'wings_testing_codex'], true)) {
            throw new \RuntimeException('Solo bases wings_testing_*.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 18:00:00');
        Notification::fake();
        $this->seed(CatalogosSeeder::class);
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);

        $this->duenio = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->sandra = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->pablo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $pl = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Lucía', 'apellido' => 'Gaitán', 'dni' => '30000001', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'x', 'localidad' => 'x', 'valor_hora' => 9000, 'activo' => true]);
        $pm = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Mariela', 'apellido' => 'Ocampo', 'dni' => '30000002', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'x', 'localidad' => 'x', 'valor_hora' => 9000, 'activo' => true]);
        $this->lucia = User::factory()->create(['rol' => 'PROFESOR', 'profesor_id' => $pl->id, 'activo' => true]);
        $this->mariela = User::factory()->create(['rol' => 'PROFESOR', 'profesor_id' => $pm->id, 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Federadas'])->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Zoe', 'apellido' => 'Flores', 'dni' => '50000001', 'fecha_nacimiento' => '2012-01-01', 'celular' => '1111111111',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha_alta' => '2026-09-01', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10', 'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-09', 'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->galicia = TipoCaja::create(['nombre' => 'Banco Galicia', 'abreviatura' => 'BGA', 'activo' => true, 'saldo_inicial' => 0]);
        $this->cajaSandra = \Tests\Support\CajaDeclarada::crear($this->sandra->id, $this->efectivo->id);
        \Tests\Support\CajaDeclarada::crear($this->pablo->id, $this->efectivo->id);
        $this->claseMariela = Clase::create(['grupo_id' => $grupo->id, 'fecha' => '2026-10-15', 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false, 'validada_para_liquidacion' => false]);
        $this->claseMariela->profesores()->attach($pm->id);

        // Sandra cobra septiembre: el cobro de verdad que después intentan tocar los demás.
        $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-09'], 'montos_cuota' => ['2026-09' => 30000], 'fecha_pago' => '2026-10-15',
        ])->assertSessionHasNoErrors();
        $this->pago = Pago::latest('id')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Foto de todo lo que una operación prohibida no debería cambiar. */
    private function foto(): array
    {
        return collect(['pagos', 'pago_deuda_cuota', 'movimientos_operativos', 'cashflow_movimientos', 'cashflow_transferencias', 'cajas_operativas',
            'deuda_cuotas', 'asistencias', 'clases', 'liquidaciones'])->mapWithKeys(fn ($t) => [$t => md5(DB::table($t)->orderBy('id')->get()->toJson())])->all();
    }

    private function prohibido($respuesta): bool
    {
        return in_array($respuesta->getStatusCode(), [302, 403, 404, 405, 419, 422], true) && !session('success');
    }

    public function test_la_operativa_no_puede_hacer_lo_del_duenio_ni_por_envio_directo(): void
    {
        $antes = $this->foto();
        $intentos = [
            ['get', route('admin.dashboard')], ['get', route('web.cashflow.index')], ['get', route('web.reportes.index')],
            ['get', route('web.liquidaciones.index')], ['get', route('web.configuraciones.index')], ['get', route('web.cashflow.transferencia')],
            ['post', route('web.cashflow.transferencia.store'), ['clave' => (string) Str::uuid(), 'salida_id' => $this->efectivo->id, 'destino_id' => $this->galicia->id, 'monto' => '1000', 'fecha' => '2026-10-15']],
            ['post', route('web.cashflow.movimiento.store'), ['subrubro_id' => Subrubro::where('nombre', 'Luz')->value('id'), 'tipo_caja_id' => $this->galicia->id, 'monto' => '62000', 'fecha' => '2026-10-15', 'observaciones' => 'x']],
            ['post', route('web.cajas.validar', $this->cajaSandra->id)],
            ['post', route('web.deudas.condonar', DeudaCuota::where('periodo', '2026-10')->value('id')), ['motivo' => 'Motivo largo de prueba de seguridad']],
            ['post', route('web.alumnos.cuotas-pasadas', $this->alumno->id), ['periodo' => '2026-08']],
            ['post', route('web.pagos.anular', $this->pago->id), ['motivo' => 'Motivo largo de prueba de seguridad']],
        ];
        $resultados = [];
        foreach ($intentos as $i) {
            $r = $this->actingAs($this->sandra)->{$i[0]}($i[1], $i[2] ?? []);
            $resultados[] = $i[1].' → '.$r->getStatusCode();
            if ($i[0] === 'get') $this->assertNotSame(200, $r->getStatusCode(), "La operativa abrió {$i[1]}");
            else $this->assertTrue($this->prohibido($r), "La operativa ejecutó {$i[1]}: ".$r->getStatusCode());
        }
        $this->assertSame($antes, $this->foto(), 'Un intento prohibido de la operativa cambió datos');
        file_put_contents(__DIR__.'/seguridad-operativa.txt', implode("\n", $resultados)."\n");
    }

    public function test_la_profesora_no_toca_plata_ni_alumnos_y_puede_cubrir_una_suplencia(): void
    {
        $antes = $this->foto();
        foreach ([route('web.cobranza.index'), route('web.caja.index'), route('web.alumnos.index'), route('web.alumnos.show', $this->alumno->id),
                  route('web.cashflow.index'), route('web.recibos.cuota', $this->pago->id)] as $url) {
            $this->assertNotSame(200, $this->actingAs($this->lucia)->get($url)->getStatusCode(), "La profesora abrió $url");
        }
        foreach ([['post', route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 30000], 'fecha_pago' => '2026-10-15']],
                  ['patch', route('web.clases.toggle-cancelada', $this->claseMariela->id), ['motivo' => 'Prueba de seguridad']],
                  ['post', route('web.clases.store'), ['grupo_id' => $this->claseMariela->grupo_id, 'fecha' => '2026-10-17', 'hora_inicio' => '10:00', 'hora_fin' => '11:00']]] as $i) {
            $r = $this->actingAs($this->lucia)->{$i[0]}($i[1], $i[2]);
            $this->assertTrue($this->prohibido($r), "La profesora ejecutó {$i[1]}: ".$r->getStatusCode());
        }
        $this->assertSame($antes, $this->foto(), 'Un intento prohibido de la profesora cambió datos');

        // Suplencia (ASI-002): Lucía guarda la lista de la clase de Mariela. Permitido por diseño.
        $this->actingAs($this->lucia)->postJson(route('web.clases.asistencias', $this->claseMariela->id),
            ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 1]]])->assertOk();
        $this->assertSame([$pmId = $this->claseMariela->profesores()->pluck('profesores.id')->first()], $this->claseMariela->fresh()->profesores()->pluck('profesores.id')->all());
    }

    public function test_una_operativa_no_toca_la_caja_abierta_de_otra(): void
    {
        $antes = $this->foto();
        $mov = MovimientoOperativo::where('caja_operativa_id', $this->cajaSandra->id)->firstOrFail();
        foreach ([['post', route('web.caja.movimientos.cancelar.store', [$this->cajaSandra->id, $mov->id]), ['motivo' => 'Motivo largo de prueba de seguridad']],
                  ['post', route('web.caja.cerrar', $this->cajaSandra->id), ['efectivo_contado' => '40000', 'cambio_retenido' => '10000']],
                  ['put', route('web.caja.movimientos.update', [$this->cajaSandra->id, $mov->id]), ['monto' => '1', 'tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => $mov->subrubro_id]]] as $i) {
            $r = $this->actingAs($this->pablo)->{$i[0]}($i[1], $i[2]);
            $this->assertTrue($this->prohibido($r), "Pablo ejecutó {$i[1]} sobre la caja de Sandra: ".$r->getStatusCode());
        }
        $this->assertSame($antes, $this->foto(), 'Pablo cambió la caja de Sandra');
    }

    public function test_cobrar_dos_veces_lo_mismo_no_duplica(): void
    {
        $datos = ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 30000], 'fecha_pago' => '2026-10-15'];
        $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), $datos);
        $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), $datos);
        $this->assertSame(2, Pago::where('alumno_id', $this->alumno->id)->where('estado', 'COMPLETADO')->count(), 'Septiembre del setUp y octubre una sola vez');
        $this->assertSame('30000.00', (string) DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-10')->value('monto_pagado'));
    }

    public function test_texto_con_codigo_en_campos_se_muestra_como_texto(): void
    {
        $ataque = '<script>alert(1)</script>';
        $this->alumno->update(['nombre' => $ataque, 'nombre_tutor' => $ataque]);
        $this->actingAs($this->sandra)->post(route('web.caja.movimiento.store'), ['tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->value('id'), 'monto' => '1000', 'observaciones' => $ataque]);
        foreach ([[$this->duenio, route('web.alumnos.index')], [$this->duenio, route('web.alumnos.show', $this->alumno->id)], [$this->duenio, route('web.cobranza.index')],
                  [$this->sandra, route('web.caja.cobrar-cuota')], [$this->duenio, route('web.caja.detalle', $this->cajaSandra->id)],
                  [$this->lucia, route('web.clases.show', $this->claseMariela->id)]] as [$u, $url]) {
            $html = $this->actingAs($u)->get($url)->getContent();
            $this->assertStringNotContainsString($ataque, $html, "Código sin escapar en $url");
        }
    }

    public function test_el_ingreso_frena_despues_de_cinco_intentos(): void
    {
        auth()->logout();
        $ultima = null;
        for ($i = 0; $i < 6; $i++) $ultima = $this->post('/login', ['email' => $this->sandra->email, 'password' => 'mal']);
        $this->assertSame(429, $ultima->getStatusCode());
    }

    public function test_las_pantallas_mandan_cabeceras_de_seguridad(): void
    {
        $r = $this->actingAs($this->duenio)->get(route('admin.dashboard'));
        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $r->headers->get('X-Frame-Options'));
        // Hallazgo del 25/10 (solo informaba) corregido en T80: ahora bloquea.
        $this->assertStringContainsString("script-src 'self'", (string) $r->headers->get('Content-Security-Policy'));
        $this->assertNull($r->headers->get('Content-Security-Policy-Report-Only'));
    }

    // ── Días 7 a 16 (Claude, 26/10/2026): lo nuevo de esas jornadas, forzado por fuera de la pantalla ──

    private function fotoCatalogos(): array
    {
        return collect(['users', 'configuraciones', 'profesores', 'grupos', 'grupo_planes', 'niveles', 'subrubros', 'liquidaciones', 'cashflow_movimientos'])
            ->mapWithKeys(fn ($t) => [$t => md5(DB::table($t)->orderBy('id')->get()->toJson())])->all();
    }

    public function test_dias_7_a_16_la_operativa_no_hace_lo_del_duenio_por_envio_directo(): void
    {
        $antes = [$this->foto(), $this->fotoCatalogos()];
        $sueldos = \App\Models\Rubro::where('nombre', 'Gastos Operativos')->value('id') ?? 1;
        $intentos = [
            ['post', route('web.cajas.rechazar', $this->cajaSandra->id), ['motivo' => 'Rechazo forzado por la operativa']],     // 13.7
            ['patch', route('web.configuraciones.update', 'dias_gracia_cobranza'), ['valor' => 3]],                             // 16.7
            ['post', route('web.usuarios.store'), ['name' => 'Intrusa', 'email' => 'intrusa@example.com', 'password' => 'Clave-Larga-123', 'password_confirmation' => 'Clave-Larga-123', 'rol' => 'ADMIN']], // 9.5
            ['put', route('web.usuarios.update', $this->sandra->id), ['name' => $this->sandra->name, 'email' => $this->sandra->email, 'rol' => 'ADMIN']], // se asciende sola
            ['patch', route('web.usuarios.toggle-activo', $this->pablo->id)],
            ['post', route('web.profesores.store'), ['nombre' => 'Martín', 'apellido' => 'Acosta', 'deporte_id' => $this->claseMariela->grupo->deporte_id, 'dni' => '31000000', 'fecha_nacimiento' => '1990-01-01', 'direccion' => 'x', 'localidad' => 'x', 'telefono' => '1', 'valor_hora' => 15000]], // 15.3
            ['post', route('web.niveles.store'), ['nombre' => 'Adultos']],                                                       // 16.8
            ['post', route('web.grupos.store'), ['deporte_id' => $this->claseMariela->grupo->deporte_id, 'nivel_id' => $this->claseMariela->grupo->nivel_id, 'planes' => [['clases_por_semana' => 1, 'precio_mensual' => 32000]]]],
            ['post', route('web.subrubros.store', $sueldos), ['nombre' => 'Botiquín', 'tipo' => 'EGRESO', 'permitido_para' => 'OPERATIVO']], // 16.9
            ['post', route('web.liquidaciones.store'), ['profesor_id' => $this->claseMariela->profesores()->value('profesores.id'), 'mes' => 10, 'anio' => 2026]], // 16.5
        ];
        $resultados = [];
        foreach ($intentos as $i) {
            $r = $this->actingAs($this->sandra)->{$i[0]}($i[1], $i[2] ?? []);
            $resultados[] = strtoupper($i[0]).' '.$i[1].' → '.$r->getStatusCode();
            $this->assertTrue($this->prohibido($r), "La operativa ejecutó {$i[1]}: ".$r->getStatusCode());
        }
        $this->assertSame($antes, [$this->foto(), $this->fotoCatalogos()], 'Un intento prohibido de la operativa cambió datos');
        file_put_contents(__DIR__.'/seguridad-operativa-dias-07-16.txt', implode("\n", $resultados)."\n");
    }

    public function test_dia_16_el_duenio_no_se_quita_el_rol_ni_se_desactiva_por_envio_directo(): void
    {
        $this->actingAs($this->duenio)->put(route('web.usuarios.update', $this->duenio->id),
            ['name' => $this->duenio->name, 'email' => $this->duenio->email, 'rol' => 'OPERATIVO']);
        $this->actingAs($this->duenio)->patch(route('web.usuarios.toggle-activo', $this->duenio->id));
        $this->actingAs($this->duenio)->patchJson(route('web.usuarios.toggle-activo', $this->duenio->id));
        $fresco = $this->duenio->fresh();
        $this->assertSame('ADMIN', $fresco->rol);
        $this->assertTrue((bool) $fresco->activo);
    }

    public function test_dias_14_y_16_los_datos_invalidos_se_frenan_en_el_servidor(): void
    {
        $gracia = DB::table('configuraciones')->where('clave', 'dias_gracia_cobranza')->value('valor');
        foreach (['-3', 'abc', '0', '29', ''] as $valor) {
            $this->actingAs($this->duenio)->patch(route('web.configuraciones.update', 'dias_gracia_cobranza'), ['valor' => $valor]);
            $this->assertSame($gracia, DB::table('configuraciones')->where('clave', 'dias_gracia_cobranza')->value('valor'), "Guardó gracia «{$valor}»");
        }
        $antes = DB::table('cashflow_movimientos')->count();
        $luz = Subrubro::where('nombre', 'Luz')->value('id');
        foreach ([['monto' => '0', 'fecha' => '2026-10-15'], ['monto' => '-1000', 'fecha' => '2026-10-15'], ['monto' => '1000', 'fecha' => '2026-10-30']] as $caso) {
            $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), $caso + ['subrubro_id' => $luz, 'tipo_caja_id' => $this->galicia->id, 'observaciones' => 'x']);
        }
        $this->assertSame($antes, DB::table('cashflow_movimientos')->count(), 'Cashflow guardó un importe cero, negativo o con fecha futura');

        // Control: con datos buenos sí guarda; si no, los rechazos de arriba no probarían nada.
        $this->actingAs($this->duenio)->patch(route('web.configuraciones.update', 'dias_gracia_cobranza'), ['valor' => '12']);
        $this->assertEquals('12', DB::table('configuraciones')->where('clave', 'dias_gracia_cobranza')->value('valor'));
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['monto' => '1000', 'fecha' => '2026-10-15', 'subrubro_id' => $luz, 'tipo_caja_id' => $this->galicia->id, 'observaciones' => 'x']);
        $this->assertSame($antes + 1, DB::table('cashflow_movimientos')->count(), 'El movimiento válido no se guardó');
    }

    public function test_dia_18_con_un_turno_abierto_otra_operativa_no_abre_ni_por_envio_directo(): void
    {
        $rocio = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        // Sin medio de efectivo configurado rechazaría por otro motivo y la prueba no probaría nada.
        $this->actingAs($this->duenio)->post(route('web.caja.configuracion.store'), ['tipo_caja_id' => $this->efectivo->id])->assertSessionHasNoErrors();
        $antes = CajaOperativa::count();
        $r = $this->actingAs($rocio)->post(route('web.caja.abrir'), ['efectivo_inicial' => '10000', 'confirmacion' => '1']);
        $this->assertTrue($this->prohibido($r), 'Abrió un turno con otro abierto: '.$r->getStatusCode());
        $r->assertSessionHasErrors(['efectivo_inicial' => 'Hay un turno abierto. Cerralo antes de abrir otro.']);
        $this->assertSame($antes, CajaOperativa::count());
        $this->assertSame(0, CajaOperativa::where('usuario_operativo_id', $rocio->id)->count());
    }

    // ── Días 19 a 22 (Claude, 27/10/2026). Las «hallazgo» afirman lo que Wings hace HOY contra lo que pide
    // el catálogo: se ponen en rojo cuando se corrija, y ahí se dan vuelta.

    public function test_dia_19_rechaza_gasto_en_efectivo_mayor_que_el_cajon(): void
    {
        $esperadoAntes = app(\App\Services\CajaService::class)->arqueoCaja($this->cajaSandra->id)['efectivo_esperado'];
        $cantidadAntes = MovimientoOperativo::count();
        $this->actingAs($this->sandra)->post(route('web.caja.movimiento.store'), [
            'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->value('id'), 'tipo_caja_id' => $this->efectivo->id,
            'monto' => (string) ($esperadoAntes + 50000), 'observaciones' => 'Gasto mayor que el cajón',
        ])->assertSessionHas('error', fn ($m) => str_contains($m, 'aporte'));
        $esperado = app(\App\Services\CajaService::class)->arqueoCaja($this->cajaSandra->id)['efectivo_esperado'];
        $this->assertSame($esperadoAntes, $esperado);
        $this->assertSame($cantidadAntes, MovimientoOperativo::count());
        $this->assertGreaterThanOrEqual(0, (float) $esperado);
    }

    public function test_dia_21_el_servidor_rechaza_pago_parcial_sin_motivo(): void
    {
        $cuota = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-10')->firstOrFail();
        $pagadoAntes = $cuota->monto_pagado;
        $pagosAntes = Pago::count();
        $this->actingAs($this->sandra)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 10000], 'fecha_pago' => '2026-10-15',
        ])->assertStatus(409)->assertJsonPath('requiere_motivo', true);
        $this->assertSame($pagadoAntes, $cuota->fresh()->monto_pagado);
        $this->assertSame($pagosAntes, Pago::count());
    }

    public function test_dia_22_la_liquidacion_del_mes_no_se_cierra_antes_de_fin_de_mes_ni_forzando(): void
    {
        $this->actingAs($this->mariela)->postJson(route('web.clases.asistencias', $this->claseMariela->id),
            ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 1]]])->assertOk();
        $profesoraId = $this->claseMariela->profesores()->value('profesores.id');
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.store'), ['profesor_id' => $profesoraId, 'mes' => 10, 'anio' => 2026]);
        $liq = \App\Models\Liquidacion::where('profesor_id', $profesoraId)->firstOrFail();
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.cerrar', $liq->id))
            ->assertSessionHas('error', 'No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/11/2026.');
        $this->assertSame('ABIERTA', $liq->fresh()->estado);
    }

    public function test_hallazgo_dia_22_nueva_liquidacion_cuenta_clases_de_otros_meses(): void
    {
        $profesoraId = $this->claseMariela->profesores()->value('profesores.id');
        $this->actingAs($this->mariela)->postJson(route('web.clases.asistencias', $this->claseMariela->id),
            ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 1]]])->assertOk();
        $septiembre = Clase::create(['grupo_id' => $this->claseMariela->grupo_id, 'fecha' => '2026-09-30', 'hora_inicio' => '17:00', 'hora_fin' => '18:00',
            'cancelada' => false, 'validada_para_liquidacion' => true]);
        $septiembre->profesores()->attach($profesoraId);
        $mariela = collect($this->actingAs($this->duenio)->get(route('web.liquidaciones.create'))->assertOk()->viewData('profesores'))->firstWhere('id', $profesoraId);
        // T87: La pantalla muestra el número del mes elegido (1 en octubre, no suma septiembre).
        $this->assertSame(1, (int) $mariela->clases_count);
    }

    public function test_hallazgo_t85_dos_toques_seguidos_del_interruptor_generan_una_cuota(): void
    {
        $otro = Alumno::create(['nombre' => 'Agustín', 'apellido' => 'Acosta', 'dni' => '54400005', 'fecha_nacimiento' => '2012-01-01', 'celular' => '1',
            'deporte_id' => $this->alumno->deporte_id, 'grupo_id' => $this->alumno->grupo_id, 'fecha_alta' => '2024-03-10', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $otro->id, 'plan_id' => AlumnoPlan::where('alumno_id', $this->alumno->id)->value('plan_id'), 'fecha_desde' => '2024-03-10', 'activo' => true]);
        $this->actingAs($this->sandra)->patchJson(route('web.alumnos.toggle-activo', $otro->id))->assertOk();
        $this->actingAs($this->sandra)->patchJson(route('web.alumnos.toggle-activo', $otro->id))->assertOk();
        $this->assertTrue((bool) $otro->fresh()->activo);
        // Quedó como estaba, pero con una cuota de reingreso: lo de Agustín Acosta el 27/10.
        $this->assertSame(1, DeudaCuota::where('alumno_id', $otro->id)->count(), 'Ya no genera cuota: T85 corregida, dar vuelta esta prueba');
    }

    public function test_dia_22_el_sueldo_de_una_operativa_se_elige_en_cashflow_t86(): void
    {
        $sub = app(\App\Services\SubrubroSueldoService::class)->paraUsuarioOperativo($this->sandra);
        $this->assertNotNull($sub);
        $ofrecidos = collect($this->actingAs($this->duenio)->get(route('web.cashflow.movimiento'))->assertOk()->viewData('rubros'))
            ->flatMap(fn ($r) => $r->subrubros->pluck('id'));
        // T86 (Codex, verificada por Claude 27/10): antes no se ofrecía (hallazgo del día 22.5).
        $this->assertContains($sub->id, $ofrecidos->all());
        // Forzando el envío, ¿lo acepta? Se registra el resultado para el informe, sin afirmar.
        $antes = DB::table('cashflow_movimientos')->count();
        $r = $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => $sub->id, 'tipo_caja_id' => $this->efectivo->id,
            'monto' => '1000', 'fecha' => '2026-10-15', 'observaciones' => 'Sueldo forzado']);
        file_put_contents(__DIR__.'/sueldo-operativa-forzado.txt', 'Respuesta '.$r->getStatusCode().'; movimientos creados: '.(DB::table('cashflow_movimientos')->count() - $antes)."\n");
    }

    /** Día 23: Mariela dio la clase del 15/10 con Zoe presente; el 1/11 el dueño genera y cierra octubre. */
    private function liquidacionDeMarielaCerrada(): \App\Models\Liquidacion
    {
        $this->actingAs($this->mariela)->postJson(route('web.clases.asistencias', $this->claseMariela->id),
            ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 1]]])->assertOk();
        Carbon::setTestNow('2026-11-01 10:00:00');
        $profesoraId = $this->claseMariela->profesores()->value('profesores.id');
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.store'), ['profesor_id' => $profesoraId, 'mes' => 10, 'anio' => 2026]);
        $liq = \App\Models\Liquidacion::where('profesor_id', $profesoraId)->firstOrFail();
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.cerrar', $liq->id))->assertSessionHasNoErrors();
        $this->assertSame('CERRADA', $liq->fresh()->estado);
        return $liq;
    }

    public function test_dia_23_cerrada_bloquea_asistencias_horario_y_profesoras_para_todos_forzando(): void
    {
        $liq = $this->liquidacionDeMarielaCerrada();
        $clase = $this->claseMariela;
        $antes = $this->foto();

        $this->actingAs($this->mariela)->postJson(route('web.clases.asistencias', $clase->id), ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 0]]]);
        $this->actingAs($this->duenio)->postJson(route('web.clases.asistencias', $clase->id), ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 0]], 'motivo' => 'Corrección forzada']);
        $this->actingAs($this->duenio)->put(route('web.clases.update', $clase->id), ['fecha' => '2026-10-15', 'hora_inicio' => '17:00', 'hora_fin' => '19:00', 'motivo' => 'Forzado']);
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-validada', $clase->id));
        $this->actingAs($this->duenio)->patchJson(route('web.clases.profesores', $clase->id), ['profesores' => [$this->lucia->profesor_id], 'motivo' => 'Forzado']);
        // Otra liquidación del mismo mes, recalcular la cerrada y pagar una abierta: tampoco.
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.store'), ['profesor_id' => $liq->profesor_id, 'mes' => 10, 'anio' => 2026]);
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.recalcular', $liq->id));

        $this->assertSame($antes, $this->foto(), 'Algo de la liquidación cerrada cambió');
        $this->assertSame(1, \App\Models\Liquidacion::where('profesor_id', $liq->profesor_id)->count());
    }

    public function test_dia_23_una_clase_de_una_liquidacion_cerrada_no_se_cancela_ni_forzando(): void
    {
        $liq = $this->liquidacionDeMarielaCerrada();
        // T90 (hallazgo de esta prueba, reparado el 27/10): antes la operativa la cancelaba y la liquidación
        // quedaba con una clase cancelada adentro. Ahora se rechaza con el texto de CLA-013.
        $this->actingAs($this->sandra)->patchJson(route('web.clases.toggle-cancelada', $this->claseMariela->id), ['motivo_cancelacion' => 'Forzado'])
            ->assertStatus(422)->assertJson(['message' => 'No se puede cancelar: la clase está en una liquidación cerrada o pagada.']);
        $cancelada = (bool) $this->claseMariela->fresh()->cancelada;
        file_put_contents(__DIR__.'/cancelar-clase-liquidada.txt', 'Operativa cancela la clase de una liquidación CERRADA: '.($cancelada ? 'la cancela' : 'rechazado')
            ."
Liquidación sigue ".$liq->fresh()->estado.' con '.$liq->fresh()->total_calculado." y la clase dentro.
");
        $this->assertFalse($cancelada);
    }

    public function test_dia_23_la_operativa_y_la_profesora_no_tocan_liquidaciones_forzando(): void
    {
        $this->actingAs($this->mariela)->postJson(route('web.clases.asistencias', $this->claseMariela->id),
            ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 1]]])->assertOk();
        Carbon::setTestNow('2026-11-01 10:00:00');
        $profesoraId = $this->claseMariela->profesores()->value('profesores.id');
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.store'), ['profesor_id' => $profesoraId, 'mes' => 10, 'anio' => 2026]);
        $liq = \App\Models\Liquidacion::firstOrFail();
        $antes = $this->foto();
        foreach ([$this->sandra, $this->mariela] as $quien) {
            foreach ([
                ['post', route('web.liquidaciones.store'), ['profesor_id' => $profesoraId, 'mes' => 9, 'anio' => 2026]],
                ['post', route('web.liquidaciones.cerrar', $liq->id), []],
                ['post', route('web.liquidaciones.recalcular', $liq->id), []],
                ['post', route('web.liquidaciones.pagar', $liq->id), ['fecha_pago' => '2026-11-01', 'tipo_caja_id' => $this->efectivo->id]],
                ['post', route('web.liquidaciones.ajustar', $liq->id), ['monto_final' => '1', 'monto_anterior' => '9000', 'motivo' => 'Forzado por otro rol']],
                ['post', route('web.liquidaciones.cancelar', $liq->id), ['motivo' => 'Forzado por otro rol']],
                ['delete', route('web.liquidaciones.eliminar', $liq->id), []],
            ] as [$verbo, $url, $datos]) {
                $r = $this->actingAs($quien)->$verbo($url, $datos);
                $this->assertTrue($this->prohibido($r), "$quien->rol pudo $url");
            }
        }
        $this->assertSame($antes, $this->foto());
        $this->assertSame('ABIERTA', $liq->fresh()->estado);
    }

    public function test_dia_23_una_liquidacion_abierta_no_se_paga_ni_forzando(): void
    {
        $this->actingAs($this->mariela)->postJson(route('web.clases.asistencias', $this->claseMariela->id),
            ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 1]]])->assertOk();
        Carbon::setTestNow('2026-11-01 10:00:00');
        $profesoraId = $this->claseMariela->profesores()->value('profesores.id');
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.store'), ['profesor_id' => $profesoraId, 'mes' => 10, 'anio' => 2026]);
        $liq = \App\Models\Liquidacion::firstOrFail();
        // Que el rechazo no venga de otra cosa: saldo de sobra y el subrubro de sueldo que se arma al guardar la ficha.
        $this->efectivo->update(['saldo_inicial' => 1000000]);
        app(\App\Services\SubrubroSueldoService::class)->paraProfesor(Profesor::findOrFail($profesoraId));
        $antes = DB::table('cashflow_movimientos')->count();
        $pagar = ['fecha_pago' => '2026-11-01', 'tipo_caja_id' => $this->efectivo->id];
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.pagar', $liq->id), $pagar)
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'debe estar CERRADA') || str_contains($m, 'cerrada'));
        $this->assertSame('PENDIENTE', $liq->fresh()->estado_pago);
        $this->assertSame($antes, DB::table('cashflow_movimientos')->count());
        // Control positivo: cerrada, el mismo envío sí paga.
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.cerrar', $liq->id))->assertSessionHasNoErrors();
        $this->actingAs($this->duenio)->post(route('web.liquidaciones.pagar', $liq->id), $pagar);
        $this->assertNotSame('PENDIENTE', $liq->fresh()->estado_pago, 'El envío de control no paga: la prueba no demuestra nada');
    }

    public function test_t86_la_operativa_no_se_carga_su_sueldo_desde_la_caja_ni_forzando(): void
    {
        // T86 sacó la reserva al sueldo de las operativas para que el dueño lo pague por Cashflow.
        // La reserva también la frenaba en la caja: comprobar que la caja la sigue frenando.
        $sub = app(\App\Services\SubrubroSueldoService::class)->paraUsuarioOperativo($this->sandra) ?? Subrubro::find($this->sandra->fresh()->subrubro_id);
        $this->assertFalse((bool) $sub->fresh()->es_reservado_sistema);
        $antes = $this->foto();
        $this->actingAs($this->sandra)->post(route('web.caja.movimiento.store'), ['subrubro_id' => $sub->id, 'tipo_caja_id' => $this->efectivo->id,
            'monto' => '5000', 'observaciones' => 'Mi sueldo, forzado']);
        $this->assertSame($antes, $this->foto(), 'La operativa cargó su sueldo desde la caja');
        // Control: el dueño sí lo paga por Cashflow.
        $this->efectivo->update(['saldo_inicial' => 1000000]);
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => $sub->id, 'tipo_caja_id' => $this->efectivo->id,
            'monto' => '5000', 'fecha' => '2026-10-15', 'observaciones' => 'Sueldo de Sandra'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('cashflow_movimientos')->where('subrubro_id', $sub->id)->count());
    }

    public function test_dia_13_la_caja_rechazada_no_cambia_lo_contado_al_recerrar(): void
    {
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $this->cajaSandra->id),
            ['efectivo_contado' => '35000', 'cambio_retenido' => '10000'])->assertSessionHasNoErrors();
        $this->actingAs($this->duenio)->post(route('web.cajas.rechazar', $this->cajaSandra->id), ['motivo' => 'La clase particular fue de $20.000'])
            ->assertSessionHasNoErrors();
        $this->assertSame('RECHAZADA', $this->cajaSandra->fresh()->estado);

        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $this->cajaSandra->id),
            ['efectivo_contado' => '40000', 'cambio_retenido' => '10000'])
            ->assertSessionHasErrors(['efectivo_contado' => 'La corrección no puede cambiar el efectivo que ya se contó y entregó.']);
        $this->assertSame('35000.00', $this->cajaSandra->fresh()->efectivo_contado);

        // Otra operativa tampoco recierra la caja rechazada de Sandra.
        $this->actingAs($this->pablo)->post(route('web.caja.cerrar', $this->cajaSandra->id), ['efectivo_contado' => '35000', 'cambio_retenido' => '10000']);
        $this->assertSame('RECHAZADA', $this->cajaSandra->fresh()->estado);
    }

    /**
     * Días 27.4 y 27.6: en pantalla el botón Eliminar no aparece si no se puede, así que Codex no pudo
     * provocar el rechazo. Acá se fuerza el envío: el dueño con lo que tiene historia, la operativa y la
     * profesora con lo que sí sería eliminable.
     */
    public function test_dias_27_eliminar_alumno_clase_y_serie_forzando(): void
    {
        // Alumno con cobro: el dueño forzando recibe el rechazo y el alumno sigue.
        $this->actingAs($this->duenio)->deleteJson(route('web.alumnos.destroy', $this->alumno->id))->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar el alumno: Tiene pagos registrados. Puede pasarlo a Inactivo.');
        $this->assertNotNull($this->alumno->fresh());

        // Alumno recién cargado, sin nada: la operativa y la profesora no lo eliminan; el dueño sí.
        $nuevo = Alumno::create(['nombre' => 'Iván', 'apellido' => 'Paredes', 'dni' => '53444777', 'fecha_nacimiento' => '2017-01-01', 'celular' => '1',
            'deporte_id' => $this->alumno->deporte_id, 'grupo_id' => $this->alumno->grupo_id, 'fecha_alta' => '2026-10-15', 'activo' => true]);
        $this->actingAs($this->sandra)->deleteJson(route('web.alumnos.destroy', $nuevo->id))->assertForbidden();
        $this->actingAs($this->lucia)->deleteJson(route('web.alumnos.destroy', $nuevo->id))->assertForbidden();
        $this->assertNotNull($nuevo->fresh());
        $this->actingAs($this->duenio)->deleteJson(route('web.alumnos.destroy', $nuevo->id))->assertOk();
        $this->assertNull($nuevo->fresh());

        // Clase con lista tomada: el dueño forzando recibe el rechazo y la clase sigue con su marca.
        $this->actingAs($this->mariela)->postJson(route('web.clases.asistencias', $this->claseMariela->id),
            ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 1]]])->assertOk();
        $this->actingAs($this->duenio)->deleteJson(route('web.clases.destroy', $this->claseMariela->id))->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar la clase: Tiene asistencias registradas: debe cancelarse con motivo.');
        $this->assertNotNull($this->claseMariela->fresh());
        $this->assertSame(1, DB::table('asistencias')->where('clase_id', $this->claseMariela->id)->count());

        // Serie de tres sábados sin lista: ni la operativa ni la profesora la eliminan forzando.
        $serie = (string) Str::uuid();
        $profesoraId = $this->claseMariela->profesores()->value('profesores.id');
        $clases = collect(['2026-10-17', '2026-10-24', '2026-10-31'])->map(function ($fecha) use ($serie, $profesoraId) {
            $c = Clase::create(['serie_id' => $serie, 'grupo_id' => $this->claseMariela->grupo_id, 'fecha' => $fecha, 'hora_inicio' => '14:00', 'hora_fin' => '15:00',
                'cancelada' => false, 'validada_para_liquidacion' => false]);
            $c->profesores()->attach($profesoraId);
            return $c;
        });
        foreach ([$this->sandra, $this->mariela] as $quien) {
            $this->actingAs($quien)->deleteJson(route('web.clases.destroy', $clases[0]->id), ['eliminar_serie' => 1])->assertForbidden();
        }
        $this->assertSame(3, Clase::where('serie_id', $serie)->count());

        // Si una clase de la serie ya tiene lista, el dueño tampoco la elimina entera: no se borra ninguna.
        $this->travelTo(Carbon::parse('2026-10-17 16:00'));
        $this->actingAs($this->mariela)->postJson(route('web.clases.asistencias', $clases[0]->id),
            ['items' => [['alumno_id' => $this->alumno->id, 'presente' => 1]]])->assertOk();
        $this->actingAs($this->duenio)->deleteJson(route('web.clases.destroy', $clases[1]->id), ['eliminar_serie' => 1])->assertStatus(422);
        $this->assertSame(3, Clase::where('serie_id', $serie)->count());
    }

    /** Días 26 y 28: cobrarle a un inactivo y perder su deuda, forzando por fuera de los paneles. */
    public function test_dias_26_28_inactivos_forzando(): void
    {
        $this->actingAs($this->duenio)->patchJson(route('web.alumnos.toggle-activo', $this->alumno->id))->assertOk();
        $this->assertFalse((bool) $this->alumno->fresh()->activo);
        $pagos = Pago::count();
        $cobro = ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 30000], 'fecha_pago' => '2026-10-15'];

        // Sin decir si vuelve: pregunta, no cobra.
        $this->actingAs($this->sandra)->postJson(route('web.caja.pagar', $this->alumno->id), $cobro)->assertStatus(409)
            ->assertJsonPath('requiere_reactivacion', true)->assertJsonPath('permite_no', false);
        // Forzando «No» con el mes en curso (el panel no lo ofrece): rechazado.
        $this->actingAs($this->sandra)->postJson(route('web.caja.pagar', $this->alumno->id), $cobro + ['reactivar' => 0])->assertStatus(422);
        $this->assertSame($pagos, Pago::count());
        $this->assertFalse((bool) $this->alumno->fresh()->activo);

        // La operativa no da por perdida la deuda de un inactivo, ni forzando.
        $this->actingAs($this->sandra)->postJson(route('web.inactivos.perder', $this->alumno->id), ['centavos' => 3000000])->assertForbidden();
        $this->assertSame(0, DB::table('deudas_perdidas')->count());
    }

    /**
     * Hallazgo día 28.1: Fútbol se liquida por comisión y esa liquidación guarda alumnos, no clases.
     * «Nueva liquidación» busca clases ya liquidadas y no las encuentra: octubre, cerrado y pagado,
     * sigue mostrando sus clases como liquidables. Generarla otra vez sí se rechaza.
     */
    public function test_hallazgo_dia_28_nueva_liquidacion_cuenta_un_mes_ya_liquidado_por_comision(): void
    {
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $hernan = Profesor::create(['deporte_id' => $futbol->id, 'nombre' => 'Hernán', 'apellido' => 'Quintana', 'dni' => '30000003', 'fecha_nacimiento' => '1985-01-01',
            'direccion' => 'x', 'localidad' => 'x', 'porcentaje_comision' => 40, 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $futbol->id, 'nivel_id' => Nivel::firstOrCreate(['nombre' => 'Avanzadas'])->id, 'activo' => true]);
        $clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => '2026-10-06', 'hora_inicio' => '18:00', 'hora_fin' => '19:00', 'cancelada' => false, 'validada_para_liquidacion' => true]);
        $clase->profesores()->attach($hernan->id);
        DB::table('liquidaciones')->insert(['profesor_id' => $hernan->id, 'mes' => 10, 'anio' => 2026, 'tipo' => 'COMISION', 'total_calculado' => 0,
            'estado' => 'CERRADA', 'estado_pago' => 'PAGADA', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertTrue($clase->integraLiquidacionCerrada(), 'La clase sí se reconoce liquidada en el resto de Wings');
        $vista = $this->actingAs($this->duenio)->get(route('web.liquidaciones.create', ['mes' => 10, 'anio' => 2026]))->assertOk();
        $fila = $vista->viewData('profesoresJson')[$hernan->id];
        // MF12 reparada el 29/10: octubre ya no figura pendiente (antes esta prueba retrataba el 1).
        $this->assertSame(0, $fila['clases_count'], 'MF12: un mes ya liquidado por comisión no cuenta como pendiente');
        $this->assertArrayNotHasKey('2026-10', $fila['periodos']);

        $this->actingAs($this->duenio)->post(route('web.liquidaciones.store'), ['profesor_id' => $hernan->id, 'mes' => 10, 'anio' => 2026])
            ->assertSessionHas('error', 'Ya existe una liquidación para el profesor en 10/2026.');
        $this->assertSame(1, DB::table('liquidaciones')->where('profesor_id', $hernan->id)->count());
    }

    // ── Días 29 a 33 ────────────────────────────────────────────────────────

    /**
     * Días 29.2, 31.1 y 31.2: el dueño cierra la caja ajena y queda registrado (Codex no lo pudo ver por pantalla);
     * una operativa no rectifica la caja de otra; abrir con más del 50% de diferencia exige motivo y confirmación
     * también por envío directo.
     */
    public function test_dias_29_y_31_cierre_del_duenio_rectificacion_ajena_y_apertura_con_diferencia_forzando(): void
    {
        $this->actingAs($this->duenio)->post(route('web.caja.cerrar', $this->cajaSandra->id), ['efectivo_contado' => '30000', 'cambio_retenido' => '10000'])
            ->assertSessionHasNoErrors();
        $caja = $this->cajaSandra->fresh();
        $this->assertSame('CERRADA', $caja->estado);
        $this->assertTrue((bool) $caja->cerrada_por_admin, 'El cierre por el dueño no quedó marcado');
        $this->assertSame($this->duenio->id, $caja->usuario_admin_cierre_id, 'No quedó registrado qué dueño cerró');

        // Pablo tiene su caja abierta: Sandra intenta rectificarla.
        $cajaPablo = CajaOperativa::where('usuario_operativo_id', $this->pablo->id)->firstOrFail();
        $this->actingAs($this->sandra)->post(route('web.caja.rectificar.guardar', $cajaPablo->id), ['efectivo_inicial' => '10000', 'motivo_rectificacion' => 'Escribí un cero de más'])
            ->assertForbidden();
        $this->assertSame('0.00', $cajaPablo->fresh()->efectivo_inicial);
        // Pablo sí, y queda con autor, importe anterior y motivo.
        $this->actingAs($this->pablo)->post(route('web.caja.rectificar.guardar', $cajaPablo->id), ['efectivo_inicial' => '10000', 'motivo_rectificacion' => 'Escribí un cero de más'])
            ->assertSessionHasNoErrors();
        $cajaPablo->refresh();
        $this->assertSame(['10000.00', '0.00', $this->pablo->id], [$cajaPablo->efectivo_inicial, $cajaPablo->efectivo_inicial_anterior, $cajaPablo->usuario_rectificacion_id]);
        $this->assertNotNull($cajaPablo->rectificado_at);

        // Pablo cierra dejando 10.000: es el último cierre que hereda la próxima apertura. (Sin el medio de efectivo
        // configurado, abrir rechazaría por otro motivo.)
        $this->actingAs($this->pablo)->post(route('web.caja.cerrar', $cajaPablo->id), ['efectivo_contado' => '10000', 'cambio_retenido' => '10000'])->assertSessionHasNoErrors();
        $this->actingAs($this->duenio)->post(route('web.caja.configuracion.store'), ['tipo_caja_id' => $this->efectivo->id])->assertSessionHasNoErrors();
        // Sandra abre de nuevo: hereda 10.000 y escribe 100.000.
        $cajas = CajaOperativa::count();
        // Sin decir qué cierre vio, el servidor rechaza («Cambió el último cierre…»): la apertura va atada al cierre propuesto.
        $this->actingAs($this->sandra)->post(route('web.caja.abrir'), ['efectivo_inicial' => '10000', 'confirmacion' => '1'])
            ->assertSessionHasErrors(['efectivo_inicial' => 'Cambió el último cierre. Volvé a abrir la pantalla y confirmá el efectivo.']);
        $base = ['efectivo_inicial' => '100000', 'confirmacion' => '1', 'caja_origen_id' => $cajaPablo->id];
        $this->actingAs($this->sandra)->post(route('web.caja.abrir'), $base)
            ->assertSessionHasErrors(['motivo_apertura' => 'Indicá por qué recibiste un importe distinto del último cierre.']);
        $this->actingAs($this->sandra)->post(route('web.caja.abrir'), $base + ['motivo_apertura' => 'Conté de nuevo el cajón'])
            ->assertSessionHasErrors();
        $this->assertSame($cajas, CajaOperativa::count(), 'Se abrió una caja sin confirmar la diferencia');
        $this->actingAs($this->sandra)->post(route('web.caja.abrir'), $base + ['motivo_apertura' => 'Conté de nuevo el cajón', 'confirmar_diferencia_grande' => '1'])
            ->assertSessionHasNoErrors();
        $nueva = CajaOperativa::orderByDesc('id')->first();
        $this->assertSame([$cajas + 1, '100000.00', '10000.00', 'Conté de nuevo el cajón'], [CajaOperativa::count(), $nueva->efectivo_inicial, $nueva->efectivo_heredado, $nueva->motivo_apertura]);
    }

    /**
     * Días 31.5, 33.2 y 33.3: el rechazo sin motivo lo frena el servidor con el texto del catálogo (Codex solo
     * vio el aviso del navegador); la operativa no rechaza; en la caja rechazada, el movimiento sin observación o
     * con fecha futura se rechaza en el servidor, y la caja de otra no se corrige.
     */
    public function test_dias_31_y_33_rechazo_y_correccion_de_la_caja_rechazada_forzando(): void
    {
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $this->cajaSandra->id), ['efectivo_contado' => '28000', 'cambio_retenido' => '10000'])->assertSessionHasNoErrors();
        $this->actingAs($this->duenio)->post(route('web.cajas.rechazar', $this->cajaSandra->id), [])
            ->assertSessionHasErrors(['motivo' => 'El motivo del rechazo es obligatorio.']);
        $this->actingAs($this->pablo)->post(route('web.cajas.rechazar', $this->cajaSandra->id), ['motivo' => 'Faltan $2.000'])->assertForbidden();
        $this->assertSame('CERRADA', $this->cajaSandra->fresh()->estado);
        $this->actingAs($this->duenio)->post(route('web.cajas.rechazar', $this->cajaSandra->id), ['motivo' => 'Faltan $2.000 en el cajón: ¿es la resma del lunes? Cargala'])->assertSessionHasNoErrors();
        $this->assertSame('RECHAZADA', $this->cajaSandra->fresh()->estado);

        $libreria = Subrubro::where('nombre', 'Librería')->value('id') ?? Subrubro::whereHas('rubro', fn ($q) => $q->where('tipo', 'EGRESO'))->value('id');
        $resma = ['tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => $libreria, 'monto' => '2000', 'fecha' => '2026-10-15'];
        $movs = MovimientoOperativo::count();
        $errores = fn ($r, $campo) => $r->baseResponse->getSession()->get('errors')->first($campo);
        $sin = $this->actingAs($this->sandra)->post(route('web.caja.editar.store', $this->cajaSandra->id), $resma);
        $sin->assertSessionHasErrors(['observaciones']);
        $textos = ['sin observación: '.$errores($sin, 'observaciones')];
        // «today» de la regla de validación es el día real de la máquina, no el reloj simulado de la prueba (en
        // producción son el mismo día); por eso acá la fecha futura va bien lejos.
        $futura = $this->actingAs($this->sandra)->post(route('web.caja.editar.store', $this->cajaSandra->id), array_replace($resma, ['observaciones' => 'Resma del lunes', 'fecha' => '2027-01-01']));
        $futura->assertSessionHasErrors(['fecha']);
        $textos[] = 'fecha futura: '.$errores($futura, 'fecha');
        $this->assertSame($movs, MovimientoOperativo::count());
        // SIS-006: lo que dice el servidor cuando se saltea el navegador queda registrado (en castellano desde el 30/10).
        // El archivo del día 33 conserva los avisos en inglés del hallazgo SIS-006; desde el arreglo del 30/10 se escribe aparte.
        file_put_contents(__DIR__.'/caja-rechazada-avisos-despues-de-sis-006.txt', implode("\n", $textos)."\n");
        // Pablo no corrige la caja rechazada de Sandra.
        $this->actingAs($this->pablo)->post(route('web.caja.editar.store', $this->cajaSandra->id), $resma + ['observaciones' => 'Resma del lunes'])->assertForbidden();
        // Sandra sí, y el esperado se recalcula sin tocar lo contado.
        $this->actingAs($this->sandra)->post(route('web.caja.editar.store', $this->cajaSandra->id), $resma + ['observaciones' => 'Resma del lunes'])->assertSessionHasNoErrors();
        $this->assertSame($movs + 1, MovimientoOperativo::count());
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $this->cajaSandra->id), ['efectivo_contado' => '28000', 'cambio_retenido' => '10000'])->assertSessionHasNoErrors();
        $caja = $this->cajaSandra->fresh();
        $this->assertSame(['CERRADA', '28000.00', '28000.00', '0.00'], [$caja->estado, $caja->efectivo_esperado, $caja->efectivo_contado, $caja->diferencia_efectivo]);
    }

    /**
     * Día 32.1: dar por perdida la deuda, forzando: no de un activo, no con otro importe que el que muestra la
     * lista, no dos veces; y no deja pago ni movimiento de plata.
     */
    public function test_dia_32_deuda_perdida_forzando(): void
    {
        $this->actingAs($this->duenio)->postJson(route('web.inactivos.perder', $this->alumno->id), ['centavos' => 3000000])
            ->assertStatus(422)->assertJsonPath('errors.deuda.0', 'El alumno volvió a activo. Actualizá la lista.');
        $this->actingAs($this->duenio)->patchJson(route('web.alumnos.toggle-activo', $this->alumno->id))->assertOk();
        $antes = $this->foto();
        $this->actingAs($this->duenio)->postJson(route('web.inactivos.perder', $this->alumno->id), ['centavos' => 2500000])
            ->assertStatus(422)->assertJsonPath('errors.deuda.0', 'La deuda cambió. Actualizá la lista antes de darla por perdida.');
        $this->assertSame(0, DB::table('deudas_perdidas')->count());

        $this->actingAs($this->duenio)->postJson(route('web.inactivos.perder', $this->alumno->id), ['centavos' => 3000000])
            ->assertOk()->assertJsonPath('mensaje', 'Deuda dada por perdida.')->assertJsonPath('centavos', 3000000);
        $perdida = DB::table('deudas_perdidas')->sole();
        $this->assertSame([$this->alumno->id, $this->duenio->id, '30000.00'], [$perdida->alumno_id, $perdida->usuario_id, $perdida->importe]);
        $this->assertSame('30000.00', DeudaCuota::where('periodo', '2026-10')->value('monto_perdido'));
        // Nada de plata se movió: mismos pagos, movimientos de caja y Cashflow que antes.
        $despues = $this->foto();
        foreach (['pagos', 'movimientos_operativos', 'cashflow_movimientos', 'cajas_operativas'] as $t) $this->assertSame($antes[$t], $despues[$t], "Perder la deuda tocó $t");
        // Dos veces, no.
        $this->actingAs($this->duenio)->postJson(route('web.inactivos.perder', $this->alumno->id), ['centavos' => 3000000])->assertStatus(422);
        $this->assertSame(1, DB::table('deudas_perdidas')->count());
    }

    /**
     * Día 32.3: reactivar una clase con choque de profesora se rechaza también forzando, y explica con cuál; la
     * profesora y la operativa no reactivan; al reactivar sin choque, el motivo de la cancelación se conserva.
     */
    public function test_dia_32_reactivar_clase_con_choque_forzando(): void
    {
        $mariela = Profesor::where('nombre', 'Mariela')->firstOrFail();
        $cancelada = Clase::create(['grupo_id' => $this->claseMariela->grupo_id, 'fecha' => '2026-10-15', 'hora_inicio' => '17:30', 'hora_fin' => '18:30',
            'cancelada' => true, 'motivo_cancelacion' => 'Torneo: el grupo viaja', 'validada_para_liquidacion' => false]);
        $cancelada->profesores()->attach($mariela->id);

        $r = $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $cancelada->id));
        $r->assertStatus(422);
        $this->assertStringStartsWith('No se puede reactivar: ', $r->json('message'));
        $this->assertStringContainsString('se solapa', $r->json('message'));
        $this->assertTrue((bool) $cancelada->fresh()->cancelada);

        $this->actingAs($this->lucia)->patchJson(route('web.clases.toggle-cancelada', $cancelada->id))->assertForbidden();
        $this->actingAs($this->sandra)->patchJson(route('web.clases.toggle-cancelada', $cancelada->id))->assertForbidden();
        $this->assertTrue((bool) $cancelada->fresh()->cancelada);

        // Sin el choque (se cancela la otra), reactiva y conserva el motivo.
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $this->claseMariela->id), ['motivo_cancelacion' => 'Amistoso suspendido'])->assertOk();
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $cancelada->id))->assertOk()->assertJsonPath('message', 'Clase reactivada.');
        $cancelada->refresh();
        $this->assertFalse((bool) $cancelada->cancelada);
        $this->assertSame('Torneo: el grupo viaja', $cancelada->motivo_cancelacion);
    }

    /**
     * Día 30.2: corregir un movimiento directo de Cashflow exige la versión que se vio en pantalla, queda en el
     * historial con antes, después, motivo y dueño, y la operativa no lo toca ni forzando.
     */
    public function test_dia_30_correccion_de_cashflow_forzando(): void
    {
        $luz = Subrubro::where('nombre', 'Luz')->value('id');
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => $luz, 'tipo_caja_id' => $this->galicia->id, 'monto' => '120000', 'fecha' => '2026-10-15', 'observaciones' => 'Centenera'])
            ->assertSessionHasNoErrors();
        $mov = \App\Models\CashflowMovimiento::orderByDesc('id')->firstOrFail();
        $datos = ['clave' => (string) Str::uuid(), 'version' => 0, 'subrubro_id' => $luz, 'tipo_caja_id' => $this->galicia->id, 'monto' => '125000', 'fecha' => '2026-10-15',
            'observaciones' => 'Centenera', 'motivo' => 'La factura de octubre era de $125.000'];

        $this->actingAs($this->sandra)->put(route('web.cashflow.movimientos.update', $mov->id), $datos)->assertForbidden();
        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $mov->id), ['version' => 7] + $datos)
            ->assertSessionHasErrors(['version' => 'El movimiento cambió mientras editabas. Recargá la pantalla antes de guardar.']);
        $this->assertSame('-120000.00', $mov->fresh()->monto);

        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $mov->id), $datos)->assertSessionHasNoErrors();
        $mov->refresh();
        $this->assertSame(['-125000.00', 1], [$mov->monto, (int) $mov->version_correccion]);
        $this->assertNull($mov->anulado_at);
        $corr = DB::table('cashflow_correcciones')->where('movimiento_id', $mov->id)->sole();
        $this->assertSame(['EDITAR', $this->duenio->id, 'La factura de octubre era de $125.000'], [$corr->accion, $corr->admin_id, $corr->motivo]);
        $this->assertSame(['-120000.00', '-125000.00'], [json_decode($corr->antes, true)['monto'], json_decode($corr->despues, true)['monto']]);
        // La misma clave otra vez no duplica la corrección.
        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $mov->id), $datos);
        $this->assertSame(1, DB::table('cashflow_correcciones')->where('movimiento_id', $mov->id)->count());
    }

    /** El cobro vuelve a Caja con «Pago registrado…»; uno rechazado vuelve sin ese aviso (con error, o con un pedido de confirmación). */
    private function cobroHecho($respuesta): bool
    {
        return $respuesta->getStatusCode() === 302 && str_starts_with((string) session('success'), 'Pago registrado');
    }

    /** Lo que imputó un cobro, en pesos: [cuota, recargo]. */
    private function imputado(Pago $pago): array
    {
        return [(float) DB::table('pago_deuda_cuota')->where('pago_id', $pago->id)->sum('monto_aplicado'),
            (float) DB::table('pago_cargo_alumno as pc')->join('cargos_alumno as cg', 'cg.id', '=', 'pc.cargo_alumno_id')
                ->where('pc.pago_id', $pago->id)->where('cg.tipo', 'PUNITORIO')->sum('pc.monto_aplicado')];
    }

    /**
     * Días 34.2 y 38.2: la cuota sola deja el recargo pendiente y el recargo se cobra después, solo. Forzando: no se
     * cobra el recargo salteando la cuota, no se entrega más que lo que se debe, y la operativa no perdona el recargo.
     */
    public function test_dias_34_y_38_cuota_y_recargo_por_separado_forzando(): void
    {
        \App\Models\Configuracion::set('mora_porcentaje', '10');
        $this->assertSame(1, app(\App\Services\PunitorioService::class)->aplicar());
        $cuota = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-10')->firstOrFail();
        $recargo = \App\Models\CargoAlumno::where('deuda_cuota_id', $cuota->id)->where('tipo', 'PUNITORIO')->sole();
        $this->assertSame('3000.00', $recargo->monto_original);
        $cobrar = fn (User $u, array $extra) => $this->actingAs($u)->postJson(route('web.caja.pagar', $this->alumno->id),
            array_replace(['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'fecha_pago' => '2026-10-15'], $extra));

        // Forzando: 1.000 a la cuota y el resto al recargo, para pagar el recargo antes que la cuota.
        $antes = $this->foto();
        $this->assertFalse($this->cobroHecho($cobrar($this->sandra, ['montos_cuota' => ['2026-10' => 1000], 'monto_entregado' => 4000, 'motivo' => 'Prueba de seguridad del recargo'])), 'Cobró el recargo salteando la cuota');
        // Forzando: entregar más que cuota y recargo juntos.
        $this->assertFalse($this->cobroHecho($cobrar($this->sandra, ['monto_entregado' => 40000])), 'Aceptó más que lo que se debe');
        // La operativa no perdona el recargo, ni por envío directo.
        $this->assertTrue($this->prohibido($this->actingAs($this->sandra)->post(route('web.punitorios.condonar', $recargo->id), ['motivo' => 'Motivo largo de prueba de seguridad'])));
        $this->assertSame($antes, $this->foto(), 'Un intento forzado del recargo cambió datos');
        $this->assertSame('0.00', $recargo->fresh()->monto_condonado);

        // 34.2: la cuota sola, sin ningún panel; el recargo queda entero.
        $this->assertTrue($this->cobroHecho($cobrar($this->sandra, ['montos_cuota' => ['2026-10' => 30000], 'monto_entregado' => 30000])), 'No cobró la cuota sola');
        $primero = Pago::latest('id')->firstOrFail();
        $this->assertSame([30000.0, 0.0], $this->imputado($primero));
        $this->assertSame([DeudaCuota::ESTADO_PAGADA, 3000.0], [$cuota->fresh()->estado, (float) $recargo->fresh()->saldo_pendiente]);
        // 38.2: el recargo solo; no se acepta un peso más que el recargo.
        $pagos = Pago::count();
        $this->assertFalse($this->cobroHecho($cobrar($this->sandra, ['monto_entregado' => 3001])), 'Aceptó un peso más que el recargo');
        $this->assertSame($pagos, Pago::count());
        $this->assertTrue($this->cobroHecho($cobrar($this->sandra, ['monto_entregado' => 3000])), 'No cobró el recargo solo');
        $segundo = Pago::latest('id')->firstOrFail();
        $this->assertSame([0.0, 3000.0], $this->imputado($segundo));
        $this->assertSame(0.0, (float) $recargo->fresh()->saldo_pendiente);
        // Pagado todo, otro cobro del mismo mes no mueve plata.
        $pagos = Pago::count();
        $this->assertFalse($this->cobroHecho($cobrar($this->sandra, ['monto_entregado' => 1])), 'Cobró un mes ya pagado con su recargo');
        $this->assertSame($pagos, Pago::count());
    }

    /**
     * Días 36.3 y 40.3: un mes que no empezó se cobra solo confirmando, con su importe y hasta doce meses; la
     * profesora no cobra; el adelanto queda pagado entero y no se cobra dos veces.
     */
    public function test_dias_36_y_40_adelanto_de_un_mes_forzando(): void
    {
        // Octubre pagada primero: con un mes anterior impago, el adelanto pediría además un motivo.
        $this->actingAs($this->sandra)->postJson(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'fecha_pago' => '2026-10-15']);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-10')->value('estado'));
        $antes = $this->foto();
        $adelanto = fn (User $u, array $extra) => $this->actingAs($u)->postJson(route('web.caja.pagar', $this->alumno->id),
            array_replace(['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-11'], 'montos_cuota' => ['2026-11' => 30000], 'fecha_pago' => '2026-10-15'], $extra));

        $adelanto($this->sandra, [])->assertStatus(409)->assertJsonPath('requiere_confirmacion_adelantado', true);
        $this->actingAs($this->sandra)->postJson(route('web.caja.pagar', $this->alumno->id),
            ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-11'], 'fecha_pago' => '2026-10-15', 'confirmar_pago_adelantado' => true])
            ->assertStatus(422)->assertJsonPath('message', 'Indicá el importe del mes que se cobra por adelantado.');
        $adelanto($this->sandra, ['periodos' => ['2027-11'], 'montos_cuota' => ['2027-11' => 30000], 'confirmar_pago_adelantado' => true])
            ->assertStatus(422)->assertJsonPath('message', 'Se puede cobrar por adelantado hasta 12 meses.');
        $this->assertTrue($this->prohibido($adelanto($this->lucia, ['confirmar_pago_adelantado' => true])), 'La profesora cobró un adelanto');
        $this->assertSame($antes, $this->foto(), 'Un adelanto rechazado cambió datos');

        $this->assertTrue($this->cobroHecho($adelanto($this->sandra, ['confirmar_pago_adelantado' => true])), 'No cobró el adelanto confirmado');
        $nov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertSame([DeudaCuota::ESTADO_PAGADA, '30000.00', '30000.00'], [$nov->estado, $nov->monto_original, $nov->monto_pagado]);
        $pagos = Pago::count();
        $this->assertFalse($this->cobroHecho($adelanto($this->sandra, ['confirmar_pago_adelantado' => true])), 'Cobró dos veces el mismo adelanto');
        $this->assertSame($pagos, Pago::count());
        $this->assertSame(1, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->count());
    }

    /**
     * Día 38.1: el cambio de grupo exige una frecuencia del grupo nuevo y un grupo del mismo deporte, también
     * forzando; la profesora no lo hace; el mes ya pagado no se reescribe.
     */
    public function test_dia_38_cambio_de_grupo_forzando(): void
    {
        $patin = $this->alumno->deporte_id;
        $planViejo = AlumnoPlan::where('alumno_id', $this->alumno->id)->value('plan_id');
        $intermedias = Grupo::create(['deporte_id' => $patin, 'nivel_id' => Nivel::firstOrCreate(['nombre' => 'Intermedias'])->id, 'activo' => true]);
        $planNuevo = GrupoPlan::create(['grupo_id' => $intermedias->id, 'clases_por_semana' => 2, 'precio_mensual' => 28000, 'activo' => true]);
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $grupoFutbol = Grupo::create(['deporte_id' => $futbol->id, 'nivel_id' => Nivel::firstOrCreate(['nombre' => 'Principiantes'])->id, 'activo' => true]);
        $planFutbol = GrupoPlan::create(['grupo_id' => $grupoFutbol->id, 'clases_por_semana' => 2, 'precio_mensual' => 28000, 'activo' => true]);
        // Octubre pagada, como noviembre de Lara.
        $this->assertTrue($this->cobroHecho($this->actingAs($this->sandra)->postJson(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'fecha_pago' => '2026-10-15'])));
        $datos = fn (array $extra) => array_replace(['nombre' => 'Zoe', 'apellido' => 'Flores', 'dni' => '50000001', 'fecha_nacimiento' => '2012-01-01',
            'fecha_alta' => '2026-09-01', 'celular' => '1111111111', 'nombre_tutor' => 'Ana Flores', 'telefono_tutor' => '1122223333',
            'deporte_id' => $patin, 'grupo_id' => $intermedias->id, 'plan_id' => $planNuevo->id], $extra);
        $antes = $this->foto();
        $planes = DB::table('alumno_planes')->orderBy('id')->get()->toJson();

        $this->actingAs($this->sandra)->put(route('web.alumnos.update', $this->alumno->id), $datos(['plan_id' => null]))
            ->assertSessionHasErrors(['plan_id' => 'Debe seleccionar la frecuencia semanal del nuevo grupo.']);
        $this->actingAs($this->sandra)->put(route('web.alumnos.update', $this->alumno->id), $datos(['plan_id' => $planViejo]))
            ->assertSessionHasErrors(['plan_id' => 'La frecuencia seleccionada no corresponde al grupo.']);
        $this->actingAs($this->sandra)->put(route('web.alumnos.update', $this->alumno->id), $datos(['grupo_id' => $grupoFutbol->id, 'plan_id' => $planFutbol->id]))
            ->assertSessionHasErrors('grupo_id');
        $this->assertTrue($this->prohibido($this->actingAs($this->lucia)->put(route('web.alumnos.update', $this->alumno->id), $datos([]))), 'La profesora cambió de grupo a un alumno');
        $this->assertSame([$antes, $planes], [$this->foto(), DB::table('alumno_planes')->orderBy('id')->get()->toJson()], 'Un cambio de grupo rechazado tocó datos');
        $this->assertSame($this->claseMariela->grupo_id, $this->alumno->fresh()->grupo_id);

        $this->actingAs($this->sandra)->put(route('web.alumnos.update', $this->alumno->id), $datos([]))->assertSessionHasNoErrors();
        $this->assertSame($intermedias->id, $this->alumno->fresh()->grupo_id);
        $oct = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-10')->sole();
        $this->assertSame([DeudaCuota::ESTADO_PAGADA, '30000.00', '30000.00'], [$oct->estado, $oct->monto_original, $oct->monto_pagado]);
        $nueva = AlumnoPlan::where('alumno_id', $this->alumno->id)->where('plan_id', $planNuevo->id)->sole();
        $this->assertSame('2026-11-01', $nueva->fecha_desde->format('Y-m-d'), 'El plan nuevo tiene que regir desde el mes siguiente al pagado');
    }

    /**
     * Día 39.4: una transferencia exige dos cuentas distintas y activas, un importe positivo y una fecha que ya
     * llegó; la misma clave no la duplica ni la cambia.
     */
    public function test_dia_39_transferencia_forzando(): void
    {
        $inactiva = TipoCaja::create(['nombre' => 'Banco Cerrado', 'abreviatura' => 'BCE', 'activo' => false, 'saldo_inicial' => 0]);
        $datos = ['clave' => (string) Str::uuid(), 'salida_id' => $this->efectivo->id, 'destino_id' => $this->galicia->id, 'monto' => '1500000', 'fecha' => '2026-10-15', 'observaciones' => 'Depósito en el Galicia'];
        $antes = $this->foto();
        foreach ([['destino_id' => $this->efectivo->id], ['monto' => '0'], ['monto' => '-1500000'], ['fecha' => '2026-10-16'], ['destino_id' => $inactiva->id], ['clave' => 'no-es-una-clave']] as $cambio) {
            $r = $this->actingAs($this->duenio)->post(route('web.cashflow.transferencia.store'), array_replace($datos, $cambio));
            $this->assertTrue($this->prohibido($r), 'Aceptó una transferencia con '.json_encode($cambio));
        }
        $this->assertSame($antes, $this->foto(), 'Una transferencia rechazada dejó rastro');

        $this->actingAs($this->duenio)->post(route('web.cashflow.transferencia.store'), $datos)->assertSessionHasNoErrors();
        $this->actingAs($this->duenio)->post(route('web.cashflow.transferencia.store'), $datos)->assertSessionHasNoErrors();
        $this->actingAs($this->duenio)->post(route('web.cashflow.transferencia.store'), ['monto' => '1'] + $datos)->assertSessionHasErrors('clave');
        $this->assertSame(1, DB::table('cashflow_transferencias')->count());
        $filas = DB::table('cashflow_movimientos')->where('referencia_tipo', 'TRANSFERENCIA')->get();
        $this->assertSame(2, $filas->count());
        $this->assertSame(0.0, round($filas->sum(fn ($f) => (float) $f->monto), 2));
        $this->assertEqualsCanonicalizing([$this->efectivo->id, $this->galicia->id], $filas->pluck('tipo_caja_id')->map(fn ($v) => (int) $v)->all());
    }

    /**
     * Día 40.1: cancelar una clase pide motivo, también forzando; la profesora no cancela; una clase con lista pide
     * confirmación antes de cancelarse.
     */
    public function test_dia_40_cancelar_clase_forzando(): void
    {
        $sinLista = Clase::create(['grupo_id' => $this->claseMariela->grupo_id, 'fecha' => '2026-10-19', 'hora_inicio' => '20:30', 'hora_fin' => '21:30', 'cancelada' => false, 'validada_para_liquidacion' => false]);
        $this->actingAs($this->lucia)->patchJson(route('web.clases.toggle-cancelada', $sinLista->id), ['motivo_cancelacion' => 'Mariela avisó que no puede: turno médico'])->assertForbidden();
        $this->actingAs($this->mariela)->patchJson(route('web.clases.toggle-cancelada', $sinLista->id), ['motivo_cancelacion' => 'Mariela avisó que no puede: turno médico'])->assertForbidden();
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $sinLista->id), [])->assertStatus(422);
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $sinLista->id), ['motivo_cancelacion' => str_repeat('x', 256)])->assertStatus(422);
        $this->assertFalse((bool) $sinLista->fresh()->cancelada);

        // Con lista tomada, pide confirmar.
        \App\Models\Asistencia::create(['clase_id' => $this->claseMariela->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $this->claseMariela->id), ['motivo_cancelacion' => 'Lluvia'])
            ->assertStatus(409)->assertJsonPath('requiere_confirmacion', true);
        $this->assertFalse((bool) $this->claseMariela->fresh()->cancelada);

        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $sinLista->id), ['motivo_cancelacion' => 'Mariela avisó que no puede: turno médico'])->assertOk();
        $sinLista->refresh();
        $this->assertSame([true, 'Mariela avisó que no puede: turno médico'], [(bool) $sinLista->cancelada, $sinLista->motivo_cancelacion]);
    }

    /**
     * Días 39.1 a 39.3: el alta de la segunda quincena lleva la cuota al 70% que calcula Wings, no la que llega en el
     * envío; la fecha de ingreso no puede ser futura; la profesora no da altas.
     */
    public function test_dia_39_alta_tardia_forzando(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $plan = GrupoPlan::where('grupo_id', $this->claseMariela->grupo_id)->firstOrFail();
        $datos = ['nombre' => 'Bautista', 'apellido' => 'Sosa', 'dni' => '54111222', 'fecha_nacimiento' => '2016-09-03', 'fecha_alta' => '2026-10-20',
            'celular' => '', 'nombre_tutor' => 'Carolina Sosa', 'telefono_tutor' => '1167890123',
            'deporte_id' => $this->alumno->deporte_id, 'grupo_id' => $plan->grupo_id, 'plan_id' => $plan->id];
        $antes = Alumno::count();

        $this->actingAs($this->sandra)->post(route('web.alumnos.store'), ['cuota_periodo_visto' => '2026-10', 'cuota_importe_visto' => '1000'] + $datos)
            ->assertSessionHasErrors(['generar_cuota_actual' => 'Cambió el mes o el importe de la cuota. Revisá el aviso antes de guardar.']);
        // La fecha de ingreso admite hasta fin del mes siguiente (FechaIngresoRegla); más allá, no.
        $this->actingAs($this->sandra)->post(route('web.alumnos.store'), array_replace($datos, ['fecha_alta' => '2026-12-01']))->assertSessionHasErrors('fecha_alta');
        $this->assertTrue($this->prohibido($this->actingAs($this->lucia)->post(route('web.alumnos.store'), $datos)), 'La profesora dio un alta');
        $this->assertSame($antes, Alumno::count());

        $this->actingAs($this->sandra)->post(route('web.alumnos.store'), ['cuota_periodo_visto' => '2026-10', 'cuota_importe_visto' => '21000'] + $datos)->assertSessionHasNoErrors();
        $nuevo = Alumno::where('dni', '54111222')->sole();
        $this->assertSame('21000.00', DeudaCuota::where('alumno_id', $nuevo->id)->where('periodo', '2026-10')->value('monto_original'), 'La cuota de la segunda quincena es el 70% de 30.000');
    }

    // ── Días 41 a 47 (Claude, 31/10/2026) ───────────────────────────────────

    private static bool $notasEmpezadas = false;

    /** Lo que Wings contesta en los rechazos que Codex no pudo ver por pantalla queda en un archivo, para el informe. */
    private function nota(string $texto): void
    {
        file_put_contents(__DIR__.'/seguridad-avisos-dias-41-47.txt', $texto."\n", self::$notasEmpezadas ? FILE_APPEND : 0);
        self::$notasEmpezadas = true;
    }

    private function avisoDe($respuesta): string
    {
        $errores = session('errors')?->all() ?? [];
        return $respuesta->getStatusCode().' · '.(session('error') ?? (implode(' | ', $errores) ?: (session('success') ?? mb_substr((string) $respuesta->getContent(), 0, 200))));
    }

    /** 45.7 (Solo prueba técnica): una caja abierta no se valida ni se rechaza forzando; la operativa y la profesora tampoco. */
    public function test_dia_45_caja_abierta_no_se_valida_ni_se_rechaza_forzando(): void
    {
        $antes = $this->foto();
        $validar = $this->actingAs($this->duenio)->post(route('web.cajas.validar', $this->cajaSandra->id));
        $validar->assertSessionHas('error', 'Contá el efectivo y cerrá la caja antes de validarla.');
        $this->nota('45.7 dueño valida caja abierta: '.$this->avisoDe($validar));
        $rechazar = $this->actingAs($this->duenio)->post(route('web.cajas.rechazar', $this->cajaSandra->id), ['motivo' => 'Rechazo forzado de una caja abierta']);
        $this->nota('45.7 dueño rechaza caja abierta: '.$this->avisoDe($rechazar));
        $this->assertNull(session('success'));
        foreach ([$this->sandra, $this->lucia] as $quien) {
            $this->assertTrue($this->prohibido($this->actingAs($quien)->post(route('web.cajas.validar', $this->cajaSandra->id))), "$quien->rol validó");
            $this->assertTrue($this->prohibido($this->actingAs($quien)->post(route('web.cajas.rechazar', $this->cajaSandra->id), ['motivo' => 'Rechazo forzado'])), "$quien->rol rechazó");
        }
        $this->assertSame($antes, $this->foto(), 'Algo de la caja abierta cambió');
        $this->assertSame('ABIERTA', $this->cajaSandra->fresh()->estado);
    }

    /** 43.1 y 43.5: Pablo ve el detalle de la caja de Sandra y nada más; el dueño no abre a su nombre ni a nombre de una profesora, ni forzando. */
    public function test_dia_43_caja_ajena_y_apertura_a_nombre_de_otro_forzando(): void
    {
        $mov = MovimientoOperativo::where('caja_operativa_id', $this->cajaSandra->id)->firstOrFail();
        $this->actingAs($this->pablo)->get(route('web.caja.detalle', $this->cajaSandra->id))->assertOk();
        foreach ([route('web.caja.resumen', $this->cajaSandra->id), route('web.caja.cierre', $this->cajaSandra->id), route('web.caja.rectificar', $this->cajaSandra->id), route('web.caja.editar', $this->cajaSandra->id),
                  route('web.caja.movimientos.editar', [$this->cajaSandra->id, $mov->id]), route('web.caja.movimientos.cancelar', [$this->cajaSandra->id, $mov->id])] as $url) {
            $this->actingAs($this->pablo)->get($url)->assertForbidden();
        }
        $cajas = CajaOperativa::count();
        foreach ([$this->duenio->id, $this->lucia->id, 999999] as $operativo) {
            $this->actingAs($this->duenio)->post(route('web.caja.abrir'), ['operativo_id' => $operativo, 'efectivo_inicial' => '15000', 'confirmacion' => '1'])->assertSessionHasErrors('operativo_id');
        }
        $this->assertTrue($this->prohibido($this->actingAs($this->lucia)->post(route('web.caja.abrir'), ['efectivo_inicial' => '15000', 'confirmacion' => '1'])), 'La profesora abrió una caja');
        $this->assertSame($cajas, CajaOperativa::count());
    }

    /** 42.3b, que Codex no pudo forzar: una caja validada no se toca, ni la dueña del turno ni el dueño, por ningún camino. */
    public function test_dia_42_caja_validada_no_se_toca_forzando(): void
    {
        $mov = MovimientoOperativo::where('caja_operativa_id', $this->cajaSandra->id)->firstOrFail();
        DB::table('cajas_operativas')->where('id', $this->cajaSandra->id)->update(['estado' => 'VALIDADA', 'validada_at' => now(), 'usuario_admin_validacion_id' => $this->duenio->id]);
        $id = $this->cajaSandra->id;
        $datos = ['tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => $mov->subrubro_id, 'monto' => '1', 'fecha' => '2026-10-15', 'observaciones' => 'Forzado'];
        $antes = $this->foto();
        foreach ([$this->sandra, $this->duenio] as $quien) {
            foreach ([['put', route('web.caja.movimientos.update', [$id, $mov->id]), $datos], ['delete', route('web.caja.movimientos.destroy', [$id, $mov->id]), []],
                      ['post', route('web.caja.movimientos.cancelar.store', [$id, $mov->id]), ['motivo' => 'Forzado sobre una caja validada']], ['post', route('web.caja.editar.store', $id), $datos],
                      ['post', route('web.caja.cerrar', $id), ['efectivo_contado' => '1', 'cambio_retenido' => '1']], ['post', route('web.caja.rectificar.guardar', $id), ['efectivo_inicial' => '1', 'motivo' => 'Forzado']],
                      ['post', route('web.cajas.rechazar', $id), ['motivo' => 'Forzado sobre una caja validada']]] as [$verbo, $url, $cuerpo]) {
                $r = $this->actingAs($quien)->$verbo($url, $cuerpo);
                $this->nota("42.3b $quien->rol $verbo $url: ".$this->avisoDe($r));
            }
        }
        $this->assertSame($antes, $this->foto(), 'Una caja validada cambió');
    }

    /** 42.0 y 42.1: el adelanto debiendo un mes anterior pide motivo, y lo mal escrito no se cobra, también forzando. */
    public function test_dia_42_adelanto_debiendo_y_con_importes_mal_escritos_forzando(): void
    {
        $base = ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-11'], 'fecha_pago' => '2026-10-15', 'confirmar_pago_adelantado' => true];
        $antes = $this->foto();
        $r = $this->actingAs($this->sandra)->postJson(route('web.caja.pagar', $this->alumno->id), $base + ['montos_cuota' => ['2026-11' => 30000]]);
        $r->assertStatus(409);
        $this->nota('42.0 adelanto debiendo octubre, sin motivo: '.$r->getStatusCode().' · '.json_encode($r->json(), JSON_UNESCAPED_UNICODE));
        $this->assertSame($antes, $this->foto());

        $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'fecha_pago' => '2026-10-15'])->assertSessionHasNoErrors();
        $antes = $this->foto();
        foreach (['0', '-30000', 'abc', '44.505', '', '1e9', '30000,001'] as $importe) {
            $r = $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), $base + ['montos_cuota' => ['2026-11' => $importe]]);
            $this->nota("42.1 adelanto con importe «{$importe}»: ".$this->avisoDe($r));
            $this->assertFalse($this->cobroHecho($r), "Cobró un adelanto de «{$importe}»");
        }
        $this->assertSame($antes, $this->foto(), 'Un importe mal escrito dejó rastro');
    }

    /**
     * Hallazgo T100 (42.1), retrato: cancelar un adelanto no borra la cuota creada con el importe equivocado. Se pone en
     * rojo cuando se repare, y ahí se da vuelta.
     */
    public function test_hallazgo_t100_adelanto_cancelado_deja_saldo_fantasma(): void
    {
        $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'fecha_pago' => '2026-10-15'])->assertSessionHasNoErrors();
        $base = ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-11'], 'fecha_pago' => '2026-10-15', 'confirmar_pago_adelantado' => true];
        $this->assertTrue($this->cobroHecho($this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), $base + ['montos_cuota' => ['2026-11' => 300000]])), 'No cobró los 300.000: el importe sin tope ya no es así');
        $pago = Pago::latest('id')->firstOrFail();
        $mov = MovimientoOperativo::where('pago_id', $pago->id)->firstOrFail();
        // Sin motivo, ni la dueña del turno; otra operativa, tampoco.
        $this->actingAs($this->sandra)->post(route('web.caja.movimientos.cancelar.store', [$this->cajaSandra->id, $mov->id]), [])->assertSessionHasErrors('motivo');
        $this->actingAs($this->pablo)->post(route('web.caja.movimientos.cancelar.store', [$this->cajaSandra->id, $mov->id]), ['motivo' => 'Forzado por otra operativa'])->assertForbidden();
        $this->assertNotSame('ANULADO', $pago->fresh()->estado);

        $this->actingAs($this->sandra)->post(route('web.caja.movimientos.cancelar.store', [$this->cajaSandra->id, $mov->id]), ['motivo' => 'Un cero de más: eran $30.000'])->assertSessionHas('success');
        $this->assertSame('ANULADO', $pago->fresh()->estado);
        $nov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->firstOrFail();
        $this->assertSame([DeudaCuota::ESTADO_PENDIENTE, '300000.00', '0.00'], [$nov->estado, $nov->monto_original, $nov->monto_pagado], 'T100 corregida: dar vuelta esta prueba');

        // El cobro correcto entra como parcial y queda el saldo fantasma.
        $this->actingAs($this->sandra)->postJson(route('web.caja.pagar', $this->alumno->id), $base + ['montos_cuota' => ['2026-11' => 30000]])->assertStatus(409)->assertJsonPath('requiere_motivo', true);
        $this->assertTrue($this->cobroHecho($this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), $base + ['montos_cuota' => ['2026-11' => 30000], 'motivo' => 'Corrijo el importe: eran $30.000'])));
        $nov->refresh();
        $this->assertSame('270000.00', number_format((float) $nov->monto_original - (float) $nov->monto_pagado, 2, '.', ''), 'T100 corregida: dar vuelta esta prueba');
    }

    /** 46.2 y 46.7: cobrar dos veces, cerrar dos veces y (hallazgo, retrato) cerrar con la pantalla vieja. */
    public function test_dia_46_dos_cobros_dos_cierres_y_cierre_con_pantalla_vieja(): void
    {
        $esperadoVisto = (float) app(\App\Services\CajaService::class)->arqueoCaja($this->cajaSandra->id)['efectivo_esperado'];
        $cobro = ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 30000], 'fecha_pago' => '2026-10-15'];
        $this->assertTrue($this->cobroHecho($this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), $cobro)));
        $pagos = Pago::count();
        $segundo = $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), $cobro);
        $this->assertFalse($this->cobroHecho($segundo), 'Cobró dos veces');
        $this->nota('46.2 segundo cobro desde otra pestaña: '.$this->avisoDe($segundo));
        $this->assertSame($pagos, Pago::count());

        // 46.7: cierra con lo que veía antes del cobro. Wings acepta y lo deja a la vista como faltante.
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $this->cajaSandra->id), ['efectivo_contado' => (string) $esperadoVisto, 'cambio_retenido' => '1000'])->assertSessionHas('success', 'Caja cerrada.');
        $caja = $this->cajaSandra->fresh();
        $this->assertSame('CERRADA', $caja->estado);
        $this->assertEqualsWithDelta($esperadoVisto + 30000, (float) $caja->efectivo_esperado, 0.001, 'El esperado no incluye el cobro: CAJ-023 no cumple');
        $this->assertEqualsWithDelta(-30000, (float) $caja->diferencia_efectivo, 0.001, 'Ya no cierra con la pantalla vieja: dar vuelta esta prueba');

        // Segundo cierre, con otro contado: rechazado y sin cambios.
        $otro = $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $this->cajaSandra->id), ['efectivo_contado' => (string) ($esperadoVisto + 30000), 'cambio_retenido' => '1000']);
        $this->nota('46.7 segundo cierre: '.$this->avisoDe($otro));
        $this->assertEqualsWithDelta($esperadoVisto, (float) $caja->fresh()->efectivo_contado, 0.001, 'El segundo cierre cambió lo contado');

        // CAJ-023 b: con la caja ya cerrada, cobrar desde la otra pestaña no entra en ese turno.
        $tardio = $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-11'], 'montos_cuota' => ['2026-11' => 30000], 'fecha_pago' => '2026-10-15', 'confirmar_pago_adelantado' => true]);
        $this->nota('46.7 cobro con el turno ya cerrado: '.$this->avisoDe($tardio));
        $this->assertFalse($this->cobroHecho($tardio), 'Cobró con el turno cerrado');
        $this->assertEqualsWithDelta($esperadoVisto + 30000, (float) $caja->fresh()->efectivo_esperado, 0.001);
    }

    /** 43.2 y 46.5, avisos que Codex no pudo ver: el cambio de profesora de una clase que ya pasó, forzando. */
    public function test_dias_43_y_46_cambiar_profesora_forzando(): void
    {
        // «Ya pasó» es desde el día siguiente (ClaseWebController::actualizarProfesores compara la fecha con hoy).
        Carbon::setTestNow('2026-10-16 10:00:00');
        $clase = $this->claseMariela;
        $titular = $clase->profesores()->pluck('profesores.id')->all();
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $hernan = Profesor::create(['deporte_id' => $futbol->id, 'nombre' => 'Hernán', 'apellido' => 'Quintana', 'dni' => '30000009', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'x', 'localidad' => 'x', 'porcentaje_comision' => 40, 'activo' => true]);
        $url = route('web.clases.profesores', $clase->id);

        $r = $this->actingAs($this->sandra)->patchJson($url, ['profesores' => [$this->lucia->profesor_id], 'motivo' => 'Forzado por la operativa']);
        $this->assertTrue(in_array($r->getStatusCode(), [403, 422], true));
        $this->nota('46.5 operativa, clase pasada: '.$r->getStatusCode().' · '.$r->json('message'));
        $this->assertSame(422, $this->actingAs($this->duenio)->patchJson($url, ['profesores' => [$this->lucia->profesor_id]])->getStatusCode(), 'El dueño cambió sin motivo');
        $this->nota('46.5 dueño sin motivo: '.$this->actingAs($this->duenio)->patchJson($url, ['profesores' => [$this->lucia->profesor_id]])->json('message'));
        $this->actingAs($this->duenio)->patchJson($url, ['profesores' => [$hernan->id], 'motivo' => 'Forzado: de otro deporte'])->assertStatus(422);
        $this->actingAs($this->duenio)->patchJson($url, ['profesores' => [], 'motivo' => 'Forzado: sin ninguna'])->assertStatus(422);
        $this->assertTrue($this->prohibido($this->actingAs($this->lucia)->patchJson($url, ['profesores' => [$this->lucia->profesor_id], 'motivo' => 'Forzado por la profesora'])) , 'La profesora cambió la asignación');
        $this->assertSame($titular, $clase->fresh()->profesores()->pluck('profesores.id')->all());

        $this->actingAs($this->duenio)->patchJson($url, ['profesores' => [$this->lucia->profesor_id], 'motivo' => 'La dio Lucía: Mariela estaba con médico'])->assertOk();
        $this->assertSame([$this->lucia->profesor_id], $clase->fresh()->profesores()->pluck('profesores.id')->all());
        $this->assertSame('La dio Lucía: Mariela estaba con médico', $clase->fresh()->motivo_cambio_profesor);

        // ACC-012: la profesora no cancela, no reasigna y no reactiva, también por pedido directo. La cancelación es del dueño.
        $futura = Clase::create(['grupo_id' => $clase->grupo_id, 'fecha' => '2026-10-19', 'hora_inicio' => '20:30', 'hora_fin' => '21:30', 'cancelada' => false, 'validada_para_liquidacion' => false]);
        $futura->profesores()->attach($this->lucia->profesor_id);
        foreach ([['patchJson', route('web.clases.toggle-cancelada', $futura->id), ['motivo_cancelacion' => 'Forzado por la profesora']], ['patchJson', route('web.clases.profesores', $futura->id), ['profesores' => [$this->mariela->profesor_id]]],
                  ['put', route('web.clases.update', $futura->id), ['fecha' => '2026-10-20', 'hora_inicio' => '10:00', 'hora_fin' => '11:00']]] as [$verbo, $destino, $cuerpo]) {
            $this->assertTrue($this->prohibido($this->actingAs($this->lucia)->$verbo($destino, $cuerpo)), "La profesora pudo $destino");
        }
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $futura->id), ['motivo_cancelacion' => 'Lluvia'])->assertOk();
        $this->assertTrue($this->prohibido($this->actingAs($this->lucia)->patchJson(route('web.clases.toggle-cancelada', $futura->id), [])), 'La profesora reactivó la clase');
        $futura->refresh();
        $this->assertTrue((bool) $futura->cancelada);
        $this->assertSame([$this->lucia->profesor_id], $futura->profesores()->pluck('profesores.id')->all());
        $this->assertSame('2026-10-19', $futura->fecha->format('Y-m-d'));
    }

    /** 46.6b, que no se pudo probar por pantalla: dos ediciones independientes del mismo movimiento; gana la primera. */
    public function test_dia_46_dos_ediciones_de_cashflow_forzando(): void
    {
        $luz = Subrubro::where('nombre', 'Luz')->value('id');
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => $luz, 'tipo_caja_id' => $this->galicia->id, 'monto' => '22000', 'fecha' => '2026-10-15', 'observaciones' => 'Telecentro'])->assertSessionHasNoErrors();
        $mov = \App\Models\CashflowMovimiento::orderByDesc('id')->firstOrFail();
        $base = ['version' => 0, 'subrubro_id' => $luz, 'tipo_caja_id' => $this->galicia->id, 'fecha' => '2026-10-15', 'observaciones' => 'Telecentro', 'motivo' => 'La factura vino con el aumento'];
        $una = (string) Str::uuid();
        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $mov->id), $base + ['clave' => $una, 'monto' => '23500'])->assertSessionHasNoErrors();
        // La otra pestaña, con su propia clave y la versión vieja.
        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $mov->id), $base + ['clave' => (string) Str::uuid(), 'monto' => '24000'])
            ->assertSessionHasErrors(['version' => 'El movimiento cambió mientras editabas. Recargá la pantalla antes de guardar.']);
        // El mismo formulario ya usado, con otro importe (lo que sí se vio por pantalla).
        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $mov->id), $base + ['clave' => $una, 'monto' => '24000'])->assertSessionHasErrors('clave');
        // Fecha de mañana, por envío directo.
        $this->actingAs($this->duenio)->put(route('web.cashflow.movimientos.update', $mov->id), array_replace($base, ['version' => 1, 'clave' => (string) Str::uuid(), 'monto' => '24000', 'fecha' => '2026-10-16']))->assertSessionHasErrors('fecha');
        $this->assertSame(['-23500.00', 1], [$mov->fresh()->monto, (int) $mov->fresh()->version_correccion]);
        $this->assertSame(1, DB::table('cashflow_correcciones')->where('movimiento_id', $mov->id)->count());
    }

    /** 47.5: los avisos que Codex no pudo acreditar, el reenvío, un solo renglón de sueldo; la operativa y la profesora no crean usuarios. */
    public function test_dia_47_usuario_forzando(): void
    {
        $patin = $this->alumno->deporte_id;
        $gonzalo = Profesor::create(['deporte_id' => $patin, 'nombre' => 'Gonzalo', 'apellido' => 'Ibarra', 'dni' => '33444555', 'fecha_nacimiento' => '1990-06-15', 'direccion' => 'x', 'localidad' => 'x', 'valor_hora' => 9000, 'activo' => true]);
        $base = ['name' => 'Gonzalo Ibarra', 'email' => 'gonzalo.ibarra@example.com', 'password' => 'Infantiles-2026!', 'password_confirmation' => 'Infantiles-2026!', 'rol' => 'PROFESOR', 'profesor_id' => $gonzalo->id];
        $crear = fn (array $cambios) => $this->actingAs($this->duenio)->post(route('web.usuarios.store'), array_replace($base, $cambios));
        $usuarios = User::count();
        $crear(['email' => $this->sandra->email])->assertSessionHasErrors(['email' => 'Ya existe un usuario con ese email.']);
        $crear(['email' => 'gonzalo.ibarra.example.com'])->assertSessionHasErrors(['email' => 'El email no tiene un formato válido.']);
        $crear(['password' => 'Futbol26', 'password_confirmation' => 'Futbol26'])->assertSessionHasErrors(['password' => 'El campo clave debe tener al menos 12 caracteres.']);
        $crear(['password_confirmation' => 'Infantiles-2027!'])->assertSessionHasErrors(['password' => 'Las contraseñas no coinciden.']);
        $crear(['profesor_id' => ''])->assertSessionHasErrors(['profesor_id' => 'Debe seleccionar el profesor vinculado.']);
        $crear(['profesor_id' => 999999])->assertSessionHasErrors(['profesor_id' => 'El profesor seleccionado no existe.']);
        $crear(['name' => ''])->assertSessionHasErrors(['name' => 'El nombre es obligatorio.']);
        $crear(['rol' => 'SUPERADMIN'])->assertSessionHasErrors('rol');
        foreach ([$this->sandra, $this->lucia] as $quien) {
            $this->assertTrue($this->prohibido($this->actingAs($quien)->post(route('web.usuarios.store'), array_replace($base, ['email' => 'intrusa@example.com', 'rol' => 'ADMIN']))), "$quien->rol creó un usuario");
        }
        $this->assertSame($usuarios, User::count());

        $crear([])->assertSessionHasNoErrors();
        $nuevo = User::where('email', $base['email'])->sole();
        $this->assertSame(['PROFESOR', $gonzalo->id], [$nuevo->rol, $nuevo->profesor_id]);
        $this->assertNotSame($base['password'], $nuevo->password);
        // Atrás y reenvía; y otro usuario para el mismo profesor.
        $crear([])->assertSessionHasErrors(['email' => 'Ya existe un usuario con ese email.']);
        $otro = $crear(['email' => 'otro.gonzalo@example.com']);
        $this->nota('47.5 segundo usuario para el mismo profesor: '.$this->avisoDe($otro));
        $this->assertSame(1, User::where('profesor_id', $gonzalo->id)->count(), 'Dos usuarios para el mismo profesor');
        $this->assertLessThanOrEqual(1, DB::table('subrubros')->where('nombre', 'like', '%Ibarra%')->count(), 'Más de un renglón de sueldo');
    }

    /** 47.4 y 41.6, que Codex no pudo acreditar: diez aperturas iguales sin escribir nada; la profesora no entra. */
    public function test_dias_41_y_47_recibo_diez_veces_y_permisos_forzando(): void
    {
        \Illuminate\Support\Facades\Storage::fake(config('filesystems.default'));
        $antes = $this->foto();
        $url = route('web.recibos.cuota', $this->pago->id);
        $huellas = [];
        for ($i = 0; $i < 10; $i++) {
            $r = $this->actingAs($i % 2 ? $this->pablo : $this->sandra)->get($url);
            $r->assertOk();
            $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
            $huellas[] = md5($r->getContent());
        }
        $this->assertCount(1, array_unique($huellas), 'El mismo recibo cambia entre aperturas');
        $this->assertSame($antes, $this->foto(), 'Abrir un recibo escribió datos');

        $this->actingAs($this->lucia)->get($url)->assertForbidden();
        $this->actingAs($this->mariela)->get($url)->assertForbidden();
        // Observación: el inexistente devuelve JSON crudo en el navegador, antes de mirar el rol.
        $inexistente = $this->actingAs($this->sandra)->get(route('web.recibos.cuota', 99999));
        $inexistente->assertNotFound();
        $this->nota('41.6 recibo 99999 operativa: '.$inexistente->getStatusCode().' · '.$inexistente->headers->get('Content-Type').' · '.$inexistente->getContent());
        $this->nota('41.6 recibo 99999 profesora: '.$this->actingAs($this->lucia)->get(route('web.recibos.cuota', 99999))->getStatusCode());
        auth()->logout();
        $this->assertNotSame(200, $this->get($url)->getStatusCode(), 'Un recibo abierto sin sesión');

        // ?regenerar=1 no está en ninguna pantalla: rehace el PDF con el mismo número, sin tocar datos.
        $this->actingAs($this->sandra)->get($url.'?regenerar=1')->assertOk();
        $this->assertSame($antes, $this->foto(), 'Regenerar escribió datos');
    }

    /** 41.1 a 41.3, avisos que Codex no pudo acreditar o que la pantalla frena antes: forzando el envío. */
    public function test_dia_41_nivel_grupo_y_profesor_forzando(): void
    {
        $patin = $this->alumno->deporte_id;
        $this->actingAs($this->duenio)->post(route('web.niveles.store'), ['nombre' => 'Infantil'])->assertSessionHasNoErrors();
        $this->actingAs($this->duenio)->post(route('web.niveles.store'), ['nombre' => 'infantil '])->assertSessionHasErrors(['nombre' => 'Ya existe un nivel con un nombre similar.']);
        $nivel = Nivel::where('nombre', 'Infantil')->sole();
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);

        $grupos = Grupo::count(); $planes = GrupoPlan::count();
        $g = fn (array $d) => $this->actingAs($this->duenio)->post(route('web.grupos.store'), $d);
        $g(['deporte_id' => $patin, 'nivel_id' => $this->claseMariela->grupo->nivel_id, 'planes' => [['clases_por_semana' => 1, 'precio_mensual' => '30000']]])->assertSessionHasErrors();
        foreach ([['planes' => []], ['planes' => [['clases_por_semana' => 1, 'precio_mensual' => '0']]], ['planes' => [['clases_por_semana' => 8, 'precio_mensual' => '25000']]], ['planes' => [['clases_por_semana' => 0, 'precio_mensual' => '25000']]],
                  ['planes' => [['clases_por_semana' => 1, 'precio_mensual' => '-5']]], ['planes' => [['clases_por_semana' => 1, 'precio_mensual' => '25000'], ['clases_por_semana' => 1, 'precio_mensual' => '26000']]]] as $caso) {
            $r = $g(['deporte_id' => $futbol->id, 'nivel_id' => $nivel->id] + $caso);
            $this->nota('41.2 grupo '.json_encode($caso['planes']).': '.$this->avisoDe($r));
            $this->assertNotSame(500, $r->getStatusCode(), 'Error del servidor: T99 (el sitio de prueba todavía no la tiene)');
        }
        $this->assertSame([$grupos, $planes], [Grupo::count(), GrupoPlan::count()], 'Un grupo mal cargado dejó rastro');

        $base = ['nombre' => 'Gonzalo', 'apellido' => 'Ibarra', 'deporte_id' => $futbol->id, 'dni' => '33444555', 'fecha_nacimiento' => '1990-06-15', 'direccion' => 'Av. Rivadavia 4500', 'localidad' => 'CABA', 'telefono' => '1144445555', 'email' => 'gonzalo.ibarra@example.com', 'porcentaje_comision' => '35'];
        $p = fn (array $cambios) => $this->actingAs($this->duenio)->post(route('web.profesores.store'), array_replace($base, $cambios));
        $profesores = Profesor::count();
        $p(['dni' => '30000001'])->assertSessionHasErrors(['dni' => 'Ya existe un profesor con ese DNI.']);
        $p(['porcentaje_comision' => '101'])->assertSessionHasErrors(['porcentaje_comision' => 'El porcentaje de comisión no puede superar 100.']);
        $p(['email' => 'gonzalo.ibarra.example.com'])->assertSessionHasErrors(['email' => 'El email debe ser una dirección válida.']);
        $p(['nombre' => ''])->assertSessionHasErrors(['nombre' => 'El nombre es obligatorio.']);
        $this->assertSame($profesores, Profesor::count());
        $p([])->assertSessionHasNoErrors();
        $this->assertSame($profesores + 1, Profesor::count());
    }

    /** 44.3 y 44.6: la configuración forzando, y el aviso al dueño de un movimiento con fecha de un mes cerrado. */
    public function test_dia_44_configuracion_y_fecha_vieja_forzando(): void
    {
        $valor = fn () => DB::table('configuraciones')->where('clave', 'inscripcion_importe')->value('valor');
        $antes = $valor();
        foreach ([$this->sandra, $this->lucia] as $quien) {
            $this->assertTrue($this->prohibido($this->actingAs($quien)->patch(route('web.configuraciones.update', 'inscripcion_importe'), ['valor' => '1'])), "$quien->rol cambió la inscripción");
        }
        foreach (['-1', 'abc', '', '12.345'] as $v) {
            $r = $this->actingAs($this->duenio)->patch(route('web.configuraciones.update', 'inscripcion_importe'), ['valor' => $v]);
            $this->nota("44.3 inscripción «{$v}»: ".$this->avisoDe($r));
            $this->assertSame($antes, $valor(), "Guardó la inscripción «{$v}»");
        }

        $aviso = $this->spy(\App\Services\AvisoAdminService::class);
        $luz = Subrubro::where('nombre', 'Luz')->value('id');
        $datos = ['subrubro_id' => $luz, 'tipo_caja_id' => $this->galicia->id, 'monto' => '9400', 'fecha' => '2026-09-28', 'observaciones' => 'Edesur: llegó tarde'];
        $cuantos = DB::table('cashflow_movimientos')->count();
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), $datos)->assertSessionHas('aviso_fecha_vieja');
        $this->assertSame($cuantos, DB::table('cashflow_movimientos')->count(), 'Guardó sin confirmar la fecha vieja');
        $aviso->shouldNotHaveReceived('fechaVieja');
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), $datos + ['confirmar_fecha_vieja' => 1])->assertSessionHasNoErrors();
        $this->assertSame($cuantos + 1, DB::table('cashflow_movimientos')->count());
        $aviso->shouldHaveReceived('fechaVieja')->once();
    }

    /** 45.3: el motivo corto de una anulación, que no quedó en captura; anular dos veces; la operativa no anula un cobro del dueño. */
    public function test_dia_45_anular_cobro_del_duenio_forzando(): void
    {
        $this->assertTrue($this->cobroHecho($this->actingAs($this->duenio)->post(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->galicia->id, 'periodos' => ['2026-10'], 'fecha_pago' => '2026-10-15'])));
        $pago = Pago::latest('id')->firstOrFail();
        $this->actingAs($this->duenio)->post(route('web.pagos.anular', $pago->id), ['motivo' => 'MP'])->assertSessionHasErrors(['motivo' => 'El motivo de la anulación debe tener al menos 10 caracteres.']);
        $this->assertTrue($this->prohibido($this->actingAs($this->sandra)->post(route('web.pagos.anular', $pago->id), ['motivo' => 'Forzado por la operativa'])));
        $this->assertTrue($this->prohibido($this->actingAs($this->lucia)->post(route('web.pagos.anular', $pago->id), ['motivo' => 'Forzado por la profesora'])));
        $this->assertNotSame(Pago::ESTADO_ANULADO, $pago->fresh()->estado);
        $datos = ['motivo' => 'Era transferencia al Galicia, no Mercado Pago'];
        $this->actingAs($this->duenio)->post(route('web.pagos.anular', $pago->id), $datos)->assertSessionHas('success');
        $filas = DB::table('cashflow_movimientos')->where('referencia_tipo', 'PAGO_CUOTA')->where('referencia_id', $pago->id)->count();
        $this->actingAs($this->duenio)->post(route('web.pagos.anular', $pago->id), $datos)->assertSessionHas('error');
        $this->assertSame($filas, DB::table('cashflow_movimientos')->where('referencia_tipo', 'PAGO_CUOTA')->where('referencia_id', $pago->id)->count(), 'Anular dos veces dejó otro asiento');
    }

    /**
     * 45.8, retrato de T101: el gasto que el dueño carga en el turno figura a nombre de la operativa. Se da vuelta al
     * repararla. Pablo no carga en la caja de Sandra y la profesora tampoco.
     */
    public function test_hallazgo_t101_gasto_del_duenio_en_el_turno_figura_a_nombre_de_la_operativa(): void
    {
        $id = $this->cajaSandra->id;
        $datos = ['tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->value('id'), 'monto' => '3000', 'fecha' => '2026-10-15', 'observaciones' => 'Bombitas de luz'];
        $antes = MovimientoOperativo::count();
        $this->actingAs($this->pablo)->post(route('web.caja.editar.store', $id), $datos)->assertForbidden();
        $this->assertTrue($this->prohibido($this->actingAs($this->lucia)->post(route('web.caja.editar.store', $id), $datos)));
        $this->assertSame($antes, MovimientoOperativo::count());
        $this->actingAs($this->duenio)->post(route('web.caja.editar.store', $id), $datos)->assertSessionHasNoErrors();
        $mov = MovimientoOperativo::where('observaciones', 'Bombitas de luz')->sole();
        $this->assertSame($this->sandra->id, $mov->usuario_id, 'T101 corregida: dar vuelta esta prueba');
    }

    /** 47.9: mandar campos de más en un cobro o un gasto no cambia de quién es ni cuánto vale ni en qué caja entra. */
    public function test_dia_47_campos_de_mas_en_un_cobro_y_un_gasto_forzando(): void
    {
        $cajaPablo = CajaOperativa::where('usuario_operativo_id', $this->pablo->id)->firstOrFail();
        $extra = ['usuario_id' => $this->pablo->id, 'usuario_operativo_id' => $this->pablo->id, 'caja_operativa_id' => $cajaPablo->id, 'monto_final' => 1, 'monto_base' => 1, 'estado' => 'ANULADO', 'alumno_id' => 999, 'operativo_id' => $this->pablo->id];
        $this->assertTrue($this->cobroHecho($this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), ['tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'fecha_pago' => '2026-10-15'] + $extra)));
        $pago = Pago::latest('id')->firstOrFail();
        $this->assertSame(['COMPLETADO', '30000.00', $this->alumno->id], [$pago->estado, $pago->monto_final, $pago->alumno_id]);
        $mov = MovimientoOperativo::where('pago_id', $pago->id)->sole();
        $this->assertSame([$this->cajaSandra->id, $this->sandra->id], [$mov->caja_operativa_id, $mov->usuario_id]);

        $this->actingAs($this->sandra)->post(route('web.caja.movimiento.store'), ['tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->value('id'), 'monto' => '1000', 'observaciones' => 'Detergente'] + $extra)->assertSessionHasNoErrors();
        $gasto = MovimientoOperativo::where('observaciones', 'Detergente')->sole();
        $this->assertSame([$this->cajaSandra->id, $this->sandra->id, '1000.00'], [$gasto->caja_operativa_id, $gasto->usuario_id, $gasto->monto]);
    }

    /** Texto con código en lo que se escribió por primera vez en esta tanda: motivos, nivel, profesor, usuario. */
    public function test_dias_41_47_texto_con_codigo_en_campos_nuevos(): void
    {
        $ataque = '<img src=x onerror=alert(1)>';
        $this->actingAs($this->duenio)->post(route('web.niveles.store'), ['nombre' => $ataque]);
        $nivelId = Nivel::where('nombre', $ataque)->value('id');
        $futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $this->actingAs($this->duenio)->post(route('web.profesores.store'), ['nombre' => $ataque, 'apellido' => 'Ibarra', 'deporte_id' => $futbol->id, 'dni' => '33444555', 'fecha_nacimiento' => '1990-06-15', 'direccion' => $ataque, 'localidad' => 'CABA', 'telefono' => '1144445555', 'email' => 'g@example.com', 'porcentaje_comision' => '35']);
        $profesorId = Profesor::where('dni', '33444555')->value('id');
        $this->actingAs($this->duenio)->post(route('web.usuarios.store'), ['name' => $ataque, 'email' => 'u@example.com', 'password' => 'Infantiles-2026!', 'password_confirmation' => 'Infantiles-2026!', 'rol' => 'OPERATIVO']);
        $this->actingAs($this->duenio)->patchJson(route('web.clases.profesores', $this->claseMariela->id), ['profesores' => [$this->lucia->profesor_id], 'motivo' => $ataque]);
        $canc = Clase::create(['grupo_id' => $this->claseMariela->grupo_id, 'fecha' => '2026-10-19', 'hora_inicio' => '20:30', 'hora_fin' => '21:30', 'cancelada' => false, 'validada_para_liquidacion' => false]);
        $this->actingAs($this->duenio)->patchJson(route('web.clases.toggle-cancelada', $canc->id), ['motivo_cancelacion' => $ataque]);
        $this->actingAs($this->duenio)->post(route('web.cashflow.movimiento.store'), ['subrubro_id' => Subrubro::where('nombre', 'Luz')->value('id'), 'tipo_caja_id' => $this->galicia->id, 'monto' => '1000', 'fecha' => '2026-10-15', 'observaciones' => $ataque]);
        $mov = \App\Models\CashflowMovimiento::orderByDesc('id')->first();
        $this->actingAs($this->sandra)->post(route('web.caja.cerrar', $this->cajaSandra->id), ['efectivo_contado' => '30000', 'cambio_retenido' => '1000']);
        $this->actingAs($this->duenio)->post(route('web.cajas.rechazar', $this->cajaSandra->id), ['motivo' => $ataque]);

        $pantallas = [[$this->duenio, route('web.niveles.index')], [$this->duenio, route('web.grupos.index')], [$this->duenio, route('web.profesores.index')], [$this->duenio, route('web.usuarios.index')],
            [$this->duenio, route('web.clases.show', $this->claseMariela->id)], [$this->duenio, route('web.clases.show', $canc->id)], [$this->duenio, route('web.clases.index')],
            [$this->duenio, route('web.cashflow.index')], [$this->duenio, route('web.cashflow.movimientos.show', $mov->id)], [$this->duenio, route('web.caja.detalle', $this->cajaSandra->id)], [$this->duenio, route('web.caja.historial')],
            [$this->sandra, route('web.caja.resumen', $this->cajaSandra->id)], [$this->sandra, route('web.caja.index')], [$this->duenio, route('web.movimientos.index')]];
        if ($profesorId) $pantallas[] = [$this->duenio, route('web.profesores.show', $profesorId)];
        foreach ($pantallas as [$u, $url]) {
            $r = $this->actingAs($u)->get($url);
            $this->assertStringNotContainsString($ataque, $r->getContent(), "Código sin escapar en $url");
        }
        $this->assertNotNull($nivelId, 'El nivel con código no se guardó: la prueba no probaría nada');
        $this->assertNotNull($profesorId, 'El profesor con código no se guardó: la prueba no probaría nada');
    }

    /** Filtros de las pantallas con basura en la dirección, por cada rol: lo que no se entiende no puede ser un error del servidor. */
    public function test_dias_41_47_filtros_con_basura_no_dan_error_del_servidor(): void
    {
        \App\Models\Configuracion::set('mora_porcentaje', '10');
        $pantallas = ['web.caja.historial', 'web.cashflow.index', 'web.movimientos.index', 'web.reportes.index', 'web.reportes.alumnos', 'web.reportes.sueldos', 'web.cobranza.index', 'web.clases.index',
            'web.alumnos.index', 'web.alumnos.autocomplete', 'web.liquidaciones.index', 'web.liquidaciones.create', 'admin.dashboard', 'web.operativo.dashboard', 'web.caja.index', 'web.revision-cobranza.index', 'web.caja.cobrar-cuota', 'web.grupos.index', 'web.profesores.index'];
        $parametros = ['desde', 'hasta', 'fecha', 'mes', 'anio', 'periodo', 'page', 'search', 'q', 'estado', 'tipo', 'subrubro_id', 'grupo_id', 'deporte_id', 'profesor_id', 'modo', 'tipo_caja_id'];
        $valores = ['abc', '9999-99-99', '-1', "1' OR '1'='1", '0'];
        $errores = []; $intentos = 0;
        foreach ($pantallas as $nombre) {
            foreach ($parametros as $p) {
                foreach ([...$valores, ['x']] as $v) {
                    foreach ([$this->duenio, $this->sandra] as $quien) {
                        $intentos++;
                        $r = $this->actingAs($quien)->get(route($nombre).'?'.http_build_query([$p => $v]));
                        if ($r->getStatusCode() >= 500) {
                            $errores[] = "$quien->rol $nombre ?$p=".(is_array($v) ? '[]' : $v);
                        }
                    }
                }
            }
        }
        file_put_contents(__DIR__.'/filtros-con-basura-dias-41-47.txt', "$intentos pedidos; ".count($errores)." con error del servidor\n".implode("\n", $errores)."\n");
        // Hallazgo, retrato: Historial y Cajas no validan fechas ni mes, y varias búsquedas no aceptan una lista en vez de un texto.
        // Se pone en rojo cuando se repare: ahí tiene que quedar en cero.
        $this->assertSame(41, count($errores), 'Cambió la cantidad de filtros que dan error del servidor: ver filtros-con-basura-dias-41-47.txt');
    }

    /** Los ayudantes de los formularios, que no tienen pantalla propia: qué devuelven a cada rol. */
    public function test_dias_41_47_ayudantes_de_formularios_por_rol(): void
    {
        $nivel = $this->claseMariela->grupo->nivel_id; $deporte = $this->alumno->deporte_id;
        $urls = ['autocomplete de alumnos' => route('web.alumnos.autocomplete', ['q' => 'Zoe']), 'previa de inscripción' => route('web.alumnos.inscripcion-preview', ['dni' => '50000001', 'deporte_id' => $deporte]),
            'previa de cuota' => route('web.alumnos.cuota-alta-preview', ['plan_id' => 1, 'fecha_alta' => '2026-10-15']), 'grupo disponible' => route('web.grupos.check-disponible', ['deporte_id' => $deporte, 'nivel_id' => $nivel]),
            'nivel disponible' => route('web.niveles.check-disponible', ['nombre' => 'x']), 'email disponible' => route('web.usuarios.check-email', ['email' => $this->sandra->email])];
        foreach ($urls as $nombre => $url) {
            foreach ([$this->sandra, $this->lucia] as $quien) {
                $r = $this->actingAs($quien)->get($url, ['Accept' => 'application/json']);
                $this->nota("ayudante «{$nombre}» $quien->rol: ".$r->getStatusCode().' · '.mb_substr((string) $r->getContent(), 0, 160));
            }
            auth()->logout();
            $this->assertNotSame(200, $this->get($url, ['Accept' => 'application/json'])->getStatusCode(), "$nombre responde sin sesión");
        }
        // La profesora no ve alumnos: el autocomplete no le puede contestar con datos.
        $this->assertStringNotContainsString('Zoe', (string) $this->actingAs($this->lucia)->get($urls['autocomplete de alumnos'], ['Accept' => 'application/json'])->getContent());
        $this->assertStringNotContainsString('50000001', (string) $this->actingAs($this->lucia)->get($urls['previa de inscripción'], ['Accept' => 'application/json'])->getContent());
    }

    /** El freno del ingreso cuenta por dirección real: inventar una cabecera X-Forwarded-For desde fuera de Cloudflare no lo saltea. */
    public function test_el_freno_del_ingreso_no_se_saltea_con_una_direccion_inventada(): void
    {
        auth()->logout();
        $ultima = null;
        for ($i = 0; $i < 6; $i++) {
            $ultima = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->withHeaders(['X-Forwarded-For' => "1.2.3.$i"])->post('/login', ['email' => "alguien$i@example.com", 'password' => 'mal']);
        }
        $this->assertSame(429, $ultima->getStatusCode());
    }
}
