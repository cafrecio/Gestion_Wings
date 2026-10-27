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
        if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
            throw new \RuntimeException('Solo wings_testing_claude.');
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

    public function test_hallazgo_dia_19_un_gasto_en_efectivo_mayor_que_el_cajon_se_acepta(): void
    {
        $esperadoAntes = app(\App\Services\CajaService::class)->arqueoCaja($this->cajaSandra->id)['efectivo_esperado'];
        $this->actingAs($this->sandra)->post(route('web.caja.movimiento.store'), [
            'subrubro_id' => Subrubro::where('nombre', 'Limpieza')->value('id'), 'tipo_caja_id' => $this->efectivo->id,
            'monto' => (string) ($esperadoAntes + 50000), 'observaciones' => 'Gasto mayor que el cajón',
        ]);
        $esperado = app(\App\Services\CajaService::class)->arqueoCaja($this->cajaSandra->id)['efectivo_esperado'];
        // MOV-004 pide rechazarlo y que el esperado nunca quede negativo. Hoy queda negativo.
        $this->assertLessThan(0, $esperado, 'Ya no se acepta: MOV-004 corregido, dar vuelta esta prueba');
    }

    public function test_hallazgo_dia_21_el_servidor_acepta_un_pago_parcial_sin_motivo(): void
    {
        $cuota = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-10')->firstOrFail();
        $this->actingAs($this->sandra)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->efectivo->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 10000], 'fecha_pago' => '2026-10-15',
        ]);
        // COB-009 pide el motivo antes de cobrar un parcial. Hoy se cobra sin motivo.
        $this->assertSame('10000.00', $cuota->fresh()->monto_pagado, 'Ya pide motivo: COB-009 corregido, dar vuelta esta prueba');
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
        // La pantalla muestra el número al lado del mes elegido, pero suma la clase de septiembre: Lucía, 20 en vez de 17.
        $this->assertSame(2, (int) $mariela->clases_count, 'Ya cuenta solo el mes: dar vuelta esta prueba');
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

    public function test_hallazgo_dia_22_el_sueldo_de_una_operativa_no_se_puede_elegir_en_cashflow(): void
    {
        $sub = app(\App\Services\SubrubroSueldoService::class)->paraUsuarioOperativo($this->sandra);
        $this->assertNotNull($sub);
        $ofrecidos = collect($this->actingAs($this->duenio)->get(route('web.cashflow.movimiento'))->assertOk()->viewData('rubros'))
            ->flatMap(fn ($r) => $r->subrubros->pluck('id'));
        $this->assertNotContains($sub->id, $ofrecidos->all(), 'Ya se ofrece: CSH-015 corregido, dar vuelta esta prueba');
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

    public function test_hallazgo_dia_23_una_clase_de_una_liquidacion_cerrada_se_puede_cancelar(): void
    {
        $liq = $this->liquidacionDeMarielaCerrada();
        // LIQ-008 c: «sus clases ya no se pueden editar». Asistencias, horario, validación y profesoras lo cumplen;
        // cancelarla no lo mira (ClaseWebController::toggleCancelada). La hace la operativa, sin pasar por la pantalla.
        $this->actingAs($this->sandra)->patchJson(route('web.clases.toggle-cancelada', $this->claseMariela->id), ['motivo_cancelacion' => 'Forzado']);
        $cancelada = (bool) $this->claseMariela->fresh()->cancelada;
        file_put_contents(__DIR__.'/cancelar-clase-liquidada.txt', 'Operativa cancela la clase de una liquidación CERRADA: '.($cancelada ? 'la cancela' : 'rechazado')
            ."\nLiquidación sigue ".$liq->fresh()->estado.' con '.$liq->fresh()->total_calculado." y la clase dentro.\n");
        $this->assertTrue($cancelada, 'Ya no se cancela: dar vuelta esta prueba');
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
}
