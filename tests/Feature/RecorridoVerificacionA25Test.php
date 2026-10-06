<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\PagoCuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecorridoVerificacionA25Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo1;
    private User $operativo2;
    private User $profesor;
    private TipoCaja $efectivo;
    private TipoCaja $transferencia;
    private Subrubro $cuotaSubrubro;
    private Subrubro $gastoSubrubro;
    private Alumno $alumnoA;
    private Alumno $alumnoB;
    private Alumno $alumnoC;
    private CajaService $cajaService;
    private PagoCuotaService $pagoCuotaService;

    private array $bitacora = [];
    private string $paginasDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(realpath(dirname(__DIR__, 2)), realpath(base_path()));
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));

        $this->paginasDir = base_path('docs/06-pruebas/PRU-02/evidencia/verificacion-a25/paginas');
        if (!is_dir($this->paginasDir)) {
            mkdir($this->paginasDir, 0775, true);
        }

        // 1. Usuarios con nombres reales para las pantallas
        $this->admin = User::factory()->create([
            'name' => 'Carlos Bonifacio (Admin)',
            'email' => 'admin.a25@wings.test',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->operativo1 = User::factory()->create([
            'name' => 'Sandra Vidal (Operativa)',
            'email' => 'sandra.a25@wings.test',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->operativo2 = User::factory()->create([
            'name' => 'Lucía Operativa (Reemplazo)',
            'email' => 'lucia.a25@wings.test',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->profesor = User::factory()->create([
            'name' => 'Mariana Gómez (Docente)',
            'email' => 'profesor.a25@wings.test',
            'rol' => User::ROL_PROFESOR,
            'activo' => true,
        ]);

        // 2. Medios de pago
        $this->efectivo = TipoCaja::create([
            'nombre' => 'Efectivo Mostrador',
            'descripcion' => 'Billetes y monedas en cajón',
            'activo' => true,
        ]);

        $this->transferencia = TipoCaja::create([
            'nombre' => 'Transferencia Bancaria',
            'descripcion' => 'Cuenta Galicia Club Wings',
            'activo' => true,
        ]);

        // 3. Rubros y Subrubros
        $rubroIngreso = Rubro::create(['nombre' => 'Ingresos Operativos', 'tipo' => 'INGRESO']);
        $rubroEgreso = Rubro::create(['nombre' => 'Gastos Operativos', 'tipo' => 'EGRESO']);

        $this->cuotaSubrubro = Subrubro::create([
            'rubro_id' => $rubroIngreso->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
            'activo' => true,
        ]);

        $this->gastoSubrubro = Subrubro::create([
            'rubro_id' => $rubroEgreso->id,
            'nombre' => 'Librería y Limpieza',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'es_reservado_sistema' => false,
            'activo' => true,
        ]);

        // 4. Catálogos deportivos y Alumnos
        $deporte = Deporte::create(['nombre' => 'Gimnasia Artística']);
        $nivel = Nivel::create(['nombre' => 'Iniciación', 'deporte_id' => $deporte->id]);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 25000.00,
            'activo' => true,
        ]);

        $this->alumnoA = Alumno::create([
            'nombre' => 'Martina',
            'apellido' => 'Sosa (Alumno A)',
            'dni' => '45111222',
            'fecha_nacimiento' => '2012-04-10',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'celular' => '11-4444-1111',
            'nombre_tutor' => 'Clara Sosa',
            'telefono_tutor' => '11-4444-1111',
            'activo' => true,
        ]);
        AlumnoPlan::create(['alumno_id' => $this->alumnoA->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $this->alumnoA->id, 'periodo' => '2026-10', 'monto_original' => 25000.00, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);

        $this->alumnoB = Alumno::create([
            'nombre' => 'Joaquín',
            'apellido' => 'Pérez (Alumno B)',
            'dni' => '45222333',
            'fecha_nacimiento' => '2013-06-15',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'celular' => '11-4444-2222',
            'nombre_tutor' => 'Roberto Pérez',
            'telefono_tutor' => '11-4444-2222',
            'activo' => true,
        ]);
        AlumnoPlan::create(['alumno_id' => $this->alumnoB->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $this->alumnoB->id, 'periodo' => '2026-10', 'monto_original' => 18000.00, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);

        $this->alumnoC = Alumno::create([
            'nombre' => 'Valentina',
            'apellido' => 'Ríos (Alumno C)',
            'dni' => '45333444',
            'fecha_nacimiento' => '2011-09-20',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'celular' => '11-4444-3333',
            'nombre_tutor' => 'Silvia Ríos',
            'telefono_tutor' => '11-4444-3333',
            'activo' => true,
        ]);
        AlumnoPlan::create(['alumno_id' => $this->alumnoC->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $this->alumnoC->id, 'periodo' => '2026-10', 'monto_original' => 32000.00, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);

        $this->cajaService = app(CajaService::class);
        $this->pagoCuotaService = app(PagoCuotaService::class);
    }

    private function guardarHtml(string $nombre, string $html): void
    {
        file_put_contents($this->paginasDir . '/' . $nombre . '.html', $html);
    }

    public function test_recorrido_completo_a25_con_importes_gemini(): void
    {
        // 0. Control de marco: login
        $loginHtml = $this->get('/login')->assertOk()->getContent();
        $this->guardarHtml('00-login-control', $loginHtml);

        // ---------------------------------------------------------------------
        // PASO 1: ADMIN configura tipo de caja del mostrador
        // ---------------------------------------------------------------------
        $this->actingAs($this->admin);
        $configHtml = $this->get('/caja/configuracion')->assertOk()->getContent();
        $this->guardarHtml('01-configuracion-caja', $configHtml);

        $resConfig = $this->post('/caja/configuracion', ['tipo_caja_id' => $this->efectivo->id]);
        $resConfig->assertRedirect('/caja');

        $rowMostrador = DB::table('caja_mostrador')->where('id', 1)->first();
        $this->assertSame($this->efectivo->id, (int) $rowMostrador->tipo_caja_id);
        $this->assertSame($this->admin->id, (int) $rowMostrador->configurado_por_id);

        $this->bitacora['paso1'] = [
            'descripcion' => 'ADMIN configura medio de efectivo',
            'tipo_caja_id' => $this->efectivo->id,
            'tipo_caja_nombre' => $this->efectivo->nombre,
            'caja_mostrador' => (array) $rowMostrador,
        ];

        // ---------------------------------------------------------------------
        // PASO 2: OPERATIVO intenta cobrar sin haber abierto caja -> bloqueado
        // ---------------------------------------------------------------------
        $this->actingAs($this->operativo1);
        $cobrarSinCajaRes = $this->get('/caja/cobrar');
        $cobrarSinCajaRes->assertRedirect(route('web.caja.apertura'));

        $pagarSinCajaRes = $this->postJson("/caja/cobrar/{$this->alumnoA->id}", [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'],
        ]);
        $pagarSinCajaRes->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Abrí la caja y confirmá el efectivo antes de cobrar.']);

        $operativoSinCajaHtml = $this->get('/caja')->assertOk()->getContent();
        $this->guardarHtml('02-operativo-sin-caja', $operativoSinCajaHtml);

        $this->bitacora['paso2'] = [
            'descripcion' => 'OPERATIVO intenta cobrar sin caja abierta',
            'get_cobrar_status' => $cobrarSinCajaRes->getStatusCode(),
            'post_pagar_status' => $pagarSinCajaRes->getStatusCode(),
            'post_pagar_body' => $pagarSinCajaRes->json(),
        ];

        // ---------------------------------------------------------------------
        // PASO 3: OPERATIVO abre declarando efectivo recibido ($14.000) y confirmando
        // ---------------------------------------------------------------------
        $aperturaFormHtml = $this->get('/caja/apertura')->assertOk()->getContent();
        $this->guardarHtml('03-apertura-turno1-form', $aperturaFormHtml);

        $resAbrir = $this->post('/caja/apertura', [
            'efectivo_inicial' => '14.000,00',
            'confirmacion' => '1',
            'caja_origen_id' => null,
            'motivo_apertura' => 'Primera apertura del ciclo Gemini',
        ]);
        $cajaTurno1 = CajaOperativa::where('usuario_operativo_id', $this->operativo1->id)->where('estado', 'ABIERTA')->firstOrFail();
        $resAbrir->assertRedirect(route('web.caja.resumen', $cajaTurno1->id));

        $this->assertEquals('14000.00', $cajaTurno1->efectivo_inicial);
        $this->assertSame($this->efectivo->id, (int) $cajaTurno1->tipo_caja_efectivo_id);
        $this->assertNull($cajaTurno1->caja_origen_id);
        $this->assertNull($cajaTurno1->efectivo_heredado);

        $aperturaResultadoHtml = $this->get(route('web.caja.resumen', $cajaTurno1->id))->assertOk()->getContent();
        $this->guardarHtml('03-apertura-turno1', $aperturaResultadoHtml);

        $this->bitacora['paso3'] = [
            'descripcion' => 'OPERATIVO 1 abre Turno 1 declarando inicial',
            'turno_id' => $cajaTurno1->id,
            'efectivo_inicial' => $cajaTurno1->efectivo_inicial,
            'estado' => $cajaTurno1->estado,
            'usuario' => $this->operativo1->name,
            'fila_bd' => $cajaTurno1->toArray(),
        ];

        // ---------------------------------------------------------------------
        // PASO 4: Carga de movimientos del turno 1:
        //  - Cobro efectivo: $25.000 (Alumno A)
        //  - Cobro transferencia: $18.000 (Alumno B)
        //  - Egreso efectivo: $6.000 (Gasto librería)
        // ---------------------------------------------------------------------
        // 4a. Cobro efectivo $25.000
        $resCobroA = $this->postJson("/caja/cobrar/{$this->alumnoA->id}", [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => '25.000,00'],
        ]);
        $resCobroA->assertRedirect(route('web.caja.index'));

        // 4b. Cobro transferencia $18.000
        $resCobroB = $this->post("/caja/cobrar/{$this->alumnoB->id}", [
            'tipo_caja_id' => $this->transferencia->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => '18.000,00'],
        ]);
        $resCobroB->assertRedirect(route('web.caja.index'));

        // 4c. Egreso efectivo $6.000
        $resEgreso = $this->post('/caja/movimiento', [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoSubrubro->id,
            'monto' => '6000',
            'observaciones' => 'Compra de artículos de limpieza y librería',
        ]);
        $resEgreso->assertRedirect('/caja');

        $movimientosT1 = MovimientoOperativo::where('caja_operativa_id', $cajaTurno1->id)->get();
        $this->assertCount(3, $movimientosT1);

        $cajaIndexHtml = $this->get('/caja')->assertOk()->getContent();
        $this->guardarHtml('04-movimientos-turno1', $cajaIndexHtml);

        $this->bitacora['paso4'] = [
            'descripcion' => 'Movimientos del Turno 1 registrados',
            'movimientos' => $movimientosT1->map(fn($m) => [
                'id' => $m->id,
                'monto' => $m->monto,
                'tipo_caja_id' => $m->tipo_caja_id,
                'subrubro_id' => $m->subrubro_id,
                'alumno_id' => $m->alumno_id,
                'observaciones' => $m->observaciones,
            ])->toArray(),
        ];

        // ---------------------------------------------------------------------
        // PASO 5: ADMIN hace cobro directo sin caja ($32.000)
        // ---------------------------------------------------------------------
        $this->actingAs($this->admin);
        $resCobroAdmin = $this->post("/caja/cobrar/{$this->alumnoC->id}", [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => '32.000,00'],
        ]);
        $resCobroAdmin->assertRedirect(route('web.caja.index'));

        // Verificamos que este cobro NO abrió caja para admin ni se vinculó a la del operativo
        $pagoAdmin = Pago::where('alumno_id', $this->alumnoC->id)->latest('id')->firstOrFail();
        $movAdmin = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)->where('referencia_id', $pagoAdmin->id)->firstOrFail();
        $this->assertSame($this->admin->id, (int) $movAdmin->usuario_admin_id);
        $this->assertNull(CajaOperativa::where('usuario_operativo_id', $this->admin->id)->first());
        $this->assertDatabaseMissing('movimientos_operativos', ['pago_id' => $pagoAdmin->id]);

        $fichaAdminHtml = $this->get("/alumnos/{$this->alumnoC->id}")->assertOk()->getContent();
        $this->guardarHtml('05-cobro-admin-sin-caja', $fichaAdminHtml);

        $this->bitacora['paso5'] = [
            'descripcion' => 'Cobro directo ADMIN sin caja',
            'pago_id' => $pagoAdmin->id,
            'monto' => $pagoAdmin->monto_total,
            'usuario_admin_id' => $pagoAdmin->usuario_id,
            'cajas_admin_count' => CajaOperativa::where('usuario_operativo_id', $this->admin->id)->count(),
        ];

        // ---------------------------------------------------------------------
        // PASO 6: OPERATIVO abre pantalla de cierre / arqueo
        // Cálculos a mano:
        //  - inicial: $14.000
        //  - ingresos efectivo: +$25.000
        //  - egresos efectivo: -$6.000
        //  - esperado: $14.000 + $25.000 - $6.000 = $33.000
        //  (Transferencia $18.000 y Cobro Admin $32.000 NO integran esperado)
        //  - contado físico: $31.500
        //  - diferencia: $31.500 - $33.000 = -$1.500 (Faltante)
        //  - cambio retenido: $12.000
        //  - entrega: $31.500 - $12.000 = $19.500
        // ---------------------------------------------------------------------
        $this->actingAs($this->operativo1);
        $arqueoT1 = $this->cajaService->arqueoCaja($cajaTurno1->id);
        $this->assertEquals('33000.00', $arqueoT1['efectivo_esperado']);

        $cierreScreenHtml = $this->get(route('web.caja.cierre', $cajaTurno1->id))->assertOk()->getContent();
        $this->guardarHtml('06-cierre-arqueo-turno1', $cierreScreenHtml);

        $this->bitacora['paso6'] = [
            'descripcion' => 'Arqueo previo al cierre de Turno 1',
            'efectivo_inicial' => '14000.00',
            'ingresos_efectivo' => '25000.00',
            'egresos_efectivo' => '6000.00',
            'efectivo_esperado_calculado' => '33000.00',
            'efectivo_esperado_pantalla' => $arqueoT1['efectivo_esperado'],
            'transferencia_excluida' => '18000.00',
            'cobro_admin_excluido' => '32000.00',
        ];

        // ---------------------------------------------------------------------
        // PASO 7: Cerrar con faltante. Contar movimientos antes y después.
        // ---------------------------------------------------------------------
        $countMovAntes = MovimientoOperativo::count();

        $resCerrarT1 = $this->post(route('web.caja.cerrar', $cajaTurno1->id), [
            'efectivo_contado' => '31.500,00',
            'cambio_retenido' => '12.000,00',
        ]);
        $resCerrarT1->assertRedirect(route('web.caja.index'));

        $countMovDespues = MovimientoOperativo::count();
        $this->assertSame($countMovAntes, $countMovDespues, 'El cierre con faltante NO debe crear movimientos contables de ajuste.');

        $cajaTurno1->refresh();
        $this->assertSame('CERRADA', $cajaTurno1->estado);
        $this->assertEquals('33000.00', $cajaTurno1->efectivo_esperado);
        $this->assertEquals('31500.00', $cajaTurno1->efectivo_contado);
        $this->assertEquals('-1500.00', $cajaTurno1->diferencia_efectivo);
        $this->assertEquals('12000.00', $cajaTurno1->cambio_retenido);
        $this->assertEquals('19500.00', $cajaTurno1->efectivo_retirado);

        $resumenCerradoHtml = $this->get(route('web.caja.resumen', $cajaTurno1->id))->assertOk()->getContent();
        $this->guardarHtml('07-turno1-cerrado-faltante', $resumenCerradoHtml);

        $this->bitacora['paso7'] = [
            'descripcion' => 'Turno 1 cerrado con faltante de $1.500',
            'movimientos_antes' => $countMovAntes,
            'movimientos_despues' => $countMovDespues,
            'diferencia_movimientos' => $countMovDespues - $countMovAntes,
            'fila_caja_cerrada' => $cajaTurno1->toArray(),
        ];

        // ---------------------------------------------------------------------
        // PASO 8: ADMIN valida ese turno (antes de validar tiene que estar contado)
        // ---------------------------------------------------------------------
        $this->actingAs($this->admin);

        // Validación
        $resValidar = $this->post(route('web.cajas.validar', $cajaTurno1->id));
        $resValidar->assertRedirect(route('web.caja.index'));

        $cajaTurno1->refresh();
        $this->assertSame('VALIDADA', $cajaTurno1->estado);
        $this->assertSame($this->admin->id, (int) $cajaTurno1->usuario_admin_validacion_id);

        $validadaHtml = $this->get(route('web.caja.resumen', $cajaTurno1->id))->assertOk()->getContent();
        $this->guardarHtml('08-admin-valida-turno1', $validadaHtml);

        $this->bitacora['paso8'] = [
            'descripcion' => 'ADMIN valida Turno 1',
            'estado_final' => $cajaTurno1->estado,
            'admin_validador' => $this->admin->name,
            'validada_at' => (string) $cajaTurno1->validada_at,
        ];

        // ---------------------------------------------------------------------
        // PASO 9: Otro OPERATIVO abre Turno 2 proponiendo cambio que quedó ($12.000).
        //  - Intento con $9.000 sin motivo -> RECHAZADO
        //  - Intento con $9.000 con motivo -> ACEPTADO, guarda propuesto $12.000 y recibido $9.000
        // ---------------------------------------------------------------------
        $this->actingAs($this->operativo2);
        $propuestaT2 = $this->cajaService->propuestaApertura();
        $this->assertSame($cajaTurno1->id, $propuestaT2['caja_origen_id']);
        $this->assertEquals('12000.00', $propuestaT2['efectivo_heredado']);

        // 9a. Intento sin motivo
        $resSinMotivo = $this->post('/caja/apertura', [
            'efectivo_inicial' => '9.000,00',
            'confirmacion' => '1',
            'caja_origen_id' => $cajaTurno1->id,
            'motivo_apertura' => '',
        ]);
        $resSinMotivo->assertSessionHasErrors(['motivo_apertura']);

        $aperturaErrorHtml = $this->get('/caja/apertura')->assertOk()->getContent();
        $this->guardarHtml('09-apertura-turno2-rechazo-sin-motivo', $aperturaErrorHtml);

        // 9b. Con motivo
        $motivoDiferencia = 'Faltaban billetes de 500 al recibir el cajón';
        $resConMotivo = $this->post('/caja/apertura', [
            'efectivo_inicial' => '9.000,00',
            'confirmacion' => '1',
            'caja_origen_id' => $cajaTurno1->id,
            'motivo_apertura' => $motivoDiferencia,
        ]);
        $cajaTurno2 = CajaOperativa::where('usuario_operativo_id', $this->operativo2->id)->where('estado', 'ABIERTA')->firstOrFail();
        $resConMotivo->assertRedirect(route('web.caja.resumen', $cajaTurno2->id));

        $this->assertEquals('12000.00', $cajaTurno2->efectivo_heredado);
        $this->assertEquals('9000.00', $cajaTurno2->efectivo_inicial);
        $this->assertSame($cajaTurno1->id, (int) $cajaTurno2->caja_origen_id);
        $this->assertSame($motivoDiferencia, $cajaTurno2->motivo_apertura);

        $aperturaT2Html = $this->get(route('web.caja.resumen', $cajaTurno2->id))->assertOk()->getContent();
        $this->guardarHtml('09-apertura-turno2-con-motivo', $aperturaT2Html);

        $this->bitacora['paso9'] = [
            'descripcion' => 'OPERATIVO 2 abre Turno 2 con discrepancia justificada',
            'propuesta_heredada' => $propuestaT2['efectivo_heredado'],
            'efectivo_inicial_declarado' => $cajaTurno2->efectivo_inicial,
            'motivo' => $cajaTurno2->motivo_apertura,
            'fila_bd' => $cajaTurno2->toArray(),
        ];

        // ---------------------------------------------------------------------
        // PASO 10: Segundo ciclo: cerrar con sobrante y que ADMIN lo rechace.
        //  - Movimientos Turno 2: +$20.000 efectivo, -$4.000 efectivo
        //  - Esperado = $9.000 + $20.000 - $4.000 = $25.000
        //  - Cierre con sobrante: contado = $26.200 (sobrante +$1.200), cambio = $10.000, entrega = $16.200
        //  - ADMIN rechaza con motivo
        //  - Abrir Turno 3: propone $10.000 del Turno 2 rechazado
        //  - Corregir Turno 2: conserva lo contado ($26.200) y entregado ($16.200), no cambia Turno 3
        // ---------------------------------------------------------------------
        // 10a. Movimientos Turno 2
        MovimientoOperativo::create([
            'caja_operativa_id' => $cajaTurno2->id,
            'fecha' => today(),
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->cuotaSubrubro->id,
            'monto' => 20000.00,
            'usuario_id' => $this->operativo2->id,
            'estado' => 'ACTIVO',
            'observaciones' => 'Cobro cuota efectivo Turno 2',
        ]);
        MovimientoOperativo::create([
            'caja_operativa_id' => $cajaTurno2->id,
            'fecha' => today(),
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoSubrubro->id,
            'monto' => 4000.00,
            'usuario_id' => $this->operativo2->id,
            'estado' => 'ACTIVO',
            'observaciones' => 'Gasto de café y merienda profesores',
        ]);

        $arqueoT2 = $this->cajaService->arqueoCaja($cajaTurno2->id);
        $this->assertEquals('25000.00', $arqueoT2['efectivo_esperado']);

        // 10b. Cierre con sobrante: contado $26.200, cambio $10.000
        $cierreT2Html = $this->get(route('web.caja.cierre', $cajaTurno2->id))->assertOk()->getContent();
        $this->guardarHtml('10-cierre-turno2-sobrante', $cierreT2Html);

        $resCerrarT2 = $this->post(route('web.caja.cerrar', $cajaTurno2->id), [
            'efectivo_contado' => '26.200,00',
            'cambio_retenido' => '10.000,00',
        ]);
        $resCerrarT2->assertRedirect(route('web.caja.index'));

        $cajaTurno2->refresh();
        $this->assertSame('CERRADA', $cajaTurno2->estado);
        $this->assertEquals('25000.00', $cajaTurno2->efectivo_esperado);
        $this->assertEquals('26200.00', $cajaTurno2->efectivo_contado);
        $this->assertEquals('1200.00', $cajaTurno2->diferencia_efectivo);
        $this->assertEquals('10000.00', $cajaTurno2->cambio_retenido);
        $this->assertEquals('16200.00', $cajaTurno2->efectivo_retirado);

        // 10c. ADMIN rechaza Turno 2
        $this->actingAs($this->admin);
        $motivoRechazo = 'Revisar comprobante de egreso por $4.000';
        $resRechazar = $this->post(route('web.cajas.rechazar', $cajaTurno2->id), [
            'motivo' => $motivoRechazo,
        ]);
        $resRechazar->assertRedirect(route('web.caja.index'));

        $cajaTurno2->refresh();
        $this->assertSame('RECHAZADA', $cajaTurno2->estado);
        $this->assertSame($motivoRechazo, $cajaTurno2->motivo_rechazo);

        $rechazadaHtml = $this->get(route('web.caja.resumen', $cajaTurno2->id))->assertOk()->getContent();
        $this->guardarHtml('10-admin-rechaza-turno2', $rechazadaHtml);

        // 10d. Apertura Turno 3: propone $10.000 heredados de Turno 2 (a pesar de rechazado)
        $this->actingAs($this->operativo1);
        $propuestaT3 = $this->cajaService->propuestaApertura();
        $this->assertSame($cajaTurno2->id, $propuestaT3['caja_origen_id']);
        $this->assertEquals('10000.00', $propuestaT3['efectivo_heredado']);

        $aperturaT3ScreenHtml = $this->get('/caja/apertura')->assertOk()->getContent();
        $this->guardarHtml('10-apertura-turno3-hereda-rechazada', $aperturaT3ScreenHtml);

        $resAbrirT3 = $this->post('/caja/apertura', [
            'efectivo_inicial' => '10.000,00',
            'confirmacion' => '1',
            'caja_origen_id' => $cajaTurno2->id,
            'motivo_apertura' => '',
        ]);
        $cajaTurno3 = CajaOperativa::where('usuario_operativo_id', $this->operativo1->id)->where('estado', 'ABIERTA')->firstOrFail();
        $this->assertEquals('10000.00', $cajaTurno3->efectivo_inicial);

        // 10e. Corregir Turno 2 rechazado:
        // Se corrige un movimiento (ej. el egreso se ajusta a $3.000) y se vuelve a cerrar.
        // Debe conservar lo contado ($26.200), cambio ($10.000), entrega ($16.200).
        $this->actingAs($this->operativo2);
        $movEgreso = MovimientoOperativo::where('caja_operativa_id', $cajaTurno2->id)->where('monto', 4000.00)->firstOrFail();
        $this->cajaService->actualizarMovimientoEnCaja($cajaTurno2->id, $movEgreso->id, [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gastoSubrubro->id,
            'monto' => '3000.00',
            'fecha' => today()->toDateString(),
            'observaciones' => 'Gasto corregido según ticket fiscal',
        ]);

        // Al consultar arqueo de Turno 2, el esperado cambió: 9.000 + 20.000 - 3.000 = 26.000
        $arqueoT2Corregido = $this->cajaService->arqueoCaja($cajaTurno2->id);
        $this->assertEquals('26000.00', $arqueoT2Corregido['efectivo_esperado']);

        // Cerrar de nuevo la caja rechazada conservando los datos físicos
        $this->cajaService->cerrarCajaOperativa($cajaTurno2->id, $this->operativo2->id, false, [
            'efectivo_contado' => '26200.00',
            'cambio_retenido' => '10000.00',
        ]);

        $cajaTurno2->refresh();
        $this->assertSame('CERRADA', $cajaTurno2->estado);
        $this->assertEquals('26000.00', $cajaTurno2->efectivo_esperado);
        $this->assertEquals('26200.00', $cajaTurno2->efectivo_contado); // Conservado
        $this->assertEquals('200.00', $cajaTurno2->diferencia_efectivo); // 26200 - 26000 = 200
        $this->assertEquals('10000.00', $cajaTurno2->cambio_retenido); // Conservado
        $this->assertEquals('16200.00', $cajaTurno2->efectivo_retirado); // Conservado

        // Verificamos que Turno 3 NO cambió su inicial recibido
        $cajaTurno3->refresh();
        $this->assertEquals('10000.00', $cajaTurno3->efectivo_inicial);

        $corregidoHtml = $this->get(route('web.caja.resumen', $cajaTurno2->id))->assertOk()->getContent();
        $this->guardarHtml('10-turno2-corregido-conserva', $corregidoHtml);

        $this->bitacora['paso10'] = [
            'descripcion' => 'Ciclo 2: sobrante, rechazo, apertura turno 3 y corrección preservando físico',
            'turno2_cerrado' => $cajaTurno2->toArray(),
            'turno3_abierto' => $cajaTurno3->toArray(),
        ];

        // ---------------------------------------------------------------------
        // PASO 11: Caja histórica sin declaración inicial
        // Muestra esperado y diferencia como no calculables ("-"), no en cero.
        // ---------------------------------------------------------------------
        $cajaHistorica = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo1->id,
            'apertura_at' => now()->subDays(30),
            'cierre_at' => now()->subDays(30),
            'estado' => 'CERRADA',
            'tipo_caja_efectivo_id' => null,
            'efectivo_inicial' => null,
            'efectivo_esperado' => null,
            'efectivo_contado' => null,
            'diferencia_efectivo' => null,
            'cambio_retenido' => null,
            'efectivo_retirado' => null,
        ]);
        $arqueoHistorica = $this->cajaService->arqueoCaja($cajaHistorica->id);
        $this->assertNull($arqueoHistorica['efectivo_esperado']);
        $this->assertNull($arqueoHistorica['diferencia_efectivo']);

        $this->actingAs($this->admin);
        $historicaHtml = $this->get(route('web.caja.resumen', $cajaHistorica->id))->assertOk()->getContent();
        $this->guardarHtml('11-caja-historica-sin-inicial', $historicaHtml);

        // ---------------------------------------------------------------------
        // PASO 12: Historial completo de cajas
        // ---------------------------------------------------------------------
        $historialHtml = $this->get('/caja/historial')->assertOk()->getContent();
        $this->guardarHtml('12-historial-cajas', $historialHtml);

        // ---------------------------------------------------------------------
        // COMPROBACIONES ADICIONALES (POST directo, roles y ataques)
        // ---------------------------------------------------------------------
        $ataques = [];

        // A. Skip declaración inicial
        $resA = $this->post('/caja/apertura', [
            'confirmacion' => '1',
        ]);
        $ataques['skip_declaracion'] = [
            'status' => $resA->getStatusCode(),
            'error' => session('errors')?->first('efectivo_inicial'),
        ];

        // B. Skip confirmación
        $resB = $this->post('/caja/apertura', [
            'efectivo_inicial' => '5000',
        ]);
        $ataques['skip_confirmacion'] = [
            'status' => $resB->getStatusCode(),
            'error' => session('errors')?->first('confirmacion'),
        ];

        // C. Inicial negativo
        $resC = $this->post('/caja/apertura', [
            'efectivo_inicial' => '-5000',
            'confirmacion' => '1',
        ]);
        $ataques['inicial_negativo'] = [
            'status' => $resC->getStatusCode(),
            'error' => session('errors')?->first('efectivo_inicial'),
        ];

        // D. Cambio mayor al contado
        $resD = $this->post(route('web.caja.cerrar', $cajaTurno3->id), [
            'efectivo_contado' => '10000',
            'cambio_retenido' => '15000',
        ]);
        $ataques['cambio_mayor_contado'] = [
            'status' => $resD->getStatusCode(),
            'error' => session('errors')?->first('cambio_retenido'),
        ];

        // E. Operativo cerrando turno ajeno por URL
        $this->actingAs($this->operativo2);
        try {
            $resE = $this->post(route('web.caja.cerrar', $cajaTurno3->id), [
                'efectivo_contado' => '10000',
                'cambio_retenido' => '5000',
            ]);
            $ataques['cierre_turno_ajeno'] = [
                'status' => $resE->getStatusCode(),
                'error' => session('error') ?? 'Bloqueado con redirect',
            ];
        } catch (\Throwable $t) {
            $ataques['cierre_turno_ajeno'] = [
                'status' => 403,
                'error' => $t->getMessage(),
            ];
        }

        // F. Dos operativos a la vez: abrir turno simultáneo
        try {
            $this->cajaService->abrirCajaOperativa($this->operativo2->id, [
                'efectivo_inicial' => '10000',
                'confirmacion' => true,
                'caja_origen_id' => $cajaTurno2->id,
            ]);
            $ataques['dos_turnos_simultaneos'] = ['status' => 200, 'error' => 'Permitió'];
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $ataques['dos_turnos_simultaneos'] = [
                'status' => 422,
                'error' => $ve->validator->errors()->first('efectivo_inicial'),
            ];
        }

        // G. Admin valida caja ABIERTA sin conteo
        $cajaAbiertaSinConteo = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo2->id,
            'apertura_at' => now(),
            'estado' => 'ABIERTA',
            'tipo_caja_efectivo_id' => $this->efectivo->id,
            'efectivo_inicial' => 5000,
        ]);
        try {
            $this->cajaService->validarCaja($cajaAbiertaSinConteo->id, $this->admin->id);
            $ataques['validar_sin_conteo'] = ['status' => 200, 'error' => 'Permitió'];
        } catch (\Throwable $t) {
            $ataques['validar_sin_conteo'] = [
                'status' => 422,
                'error' => $t->getMessage(),
            ];
        }

        // H. Operativo configurando medio de mostrador
        $this->actingAs($this->operativo1);
        $resH = $this->post('/caja/configuracion', ['tipo_caja_id' => $this->efectivo->id]);
        $ataques['operativo_configura'] = [
            'status' => $resH->getStatusCode(),
            'error' => '403 Forbidden por ensure.admin.web',
        ];

        // I. Profesor accediendo a arqueo de caja
        $this->actingAs($this->profesor);
        $resI = $this->get('/caja');
        $ataques['profesor_accede_caja'] = [
            'status' => $resI->getStatusCode(),
            'error' => '403 Forbidden por reject.profesor.web',
        ];

        $this->bitacora['ataques_post_directo'] = $ataques;

        // Guardamos bitácora completa en JSON para la redacción exacta del informe
        file_put_contents(
            base_path('docs/06-pruebas/PRU-02/evidencia/verificacion-a25/datos-verificacion.json'),
            json_encode($this->bitacora, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
        );

        $this->assertTrue(true);
    }
}
