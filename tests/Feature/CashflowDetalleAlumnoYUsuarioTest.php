<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CargoAlumno;
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
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T28: En Cashflow, un cobro muestra el apellido y nombre del alumno y a quién cobró.
 *
 * Carlos, 12/10/2026:
 * - Un cobro de cuota o inscripción muestra el apellido y nombre del alumno.
 * - «Registró» muestra a quien cargó el movimiento: la operativa si vino de una caja, el dueño si cobró directo.
 * - Un gasto o movimiento sin alumno sigue mostrando su observación, como hoy.
 * - No cambia importes, saldos, totales ni orden.
 * - Validar dos veces no duplica.
 */
class CashflowDetalleAlumnoYUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $tipoEfectivo;
    private Subrubro $subrubroCuota;
    private Subrubro $subrubroInscripcion;
    private Subrubro $subrubroGasto;
    private CajaService $cajaService;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-12 10:00:00');

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
            'name' => 'Carlos Administrador',
        ]);
        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
            'name' => 'Sandra Mostrador',
        ]);

        $this->tipoEfectivo = TipoCaja::first() ?? TipoCaja::create([
            'nombre' => 'Efectivo Mostrador',
            'abreviatura' => 'EFE',
            'activo' => true,
        ]);

        $rubroIngresos = Rubro::firstOrCreate(['nombre' => 'Cuotas', 'tipo' => 'INGRESO'], ['observacion' => '']);
        $rubroEgresos = Rubro::firstOrCreate(['nombre' => 'Gastos', 'tipo' => 'EGRESO'], ['observacion' => '']);

        $this->subrubroCuota = Subrubro::firstOrCreate(
            ['nombre' => 'Cuota Mensual'],
            [
                'rubro_id' => $rubroIngresos->id,
                'permitido_para' => User::ROL_OPERATIVO,
                'afecta_caja' => true,
                'es_reservado_sistema' => true,
            ]
        );

        $this->subrubroInscripcion = Subrubro::firstOrCreate(
            ['nombre' => 'Inscripción al club'],
            [
                'rubro_id' => $rubroIngresos->id,
                'permitido_para' => User::ROL_OPERATIVO,
                'afecta_caja' => true,
                'es_reservado_sistema' => true,
            ]
        );

        $this->subrubroGasto = Subrubro::firstOrCreate(
            ['nombre' => 'Artículos de Limpieza'],
            [
                'rubro_id' => $rubroEgresos->id,
                'permitido_para' => User::ROL_OPERATIVO,
                'afecta_caja' => true,
            ]
        );

        $this->cajaService = app(CajaService::class);
        $this->cajaService->configurarMostrador($this->tipoEfectivo->id, $this->admin->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearAlumno(string $apellido = 'Perez', string $nombre = 'Juan', string $dni = '38111222'): Alumno
    {
        $deporte = Deporte::first() ?? Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::first() ?? Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::first() ?? GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 35000, 'activo' => true]);

        $alumno = Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => $dni,
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0001',
            'fecha_alta' => '2026-10-01',
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-10-01',
            'activo' => true,
        ]);

        DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 35000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        return $alumno;
    }

    /**
     * 1. Cobro de operativa validado:
     * En Cashflow muestra el apellido y nombre del alumno y a Sandra Mostrador en Registró.
     */
    public function test_cobro_de_operativa_validado_muestra_alumno_y_operativa_en_cashflow(): void
    {
        $alumno = $this->crearAlumno('Perez', 'Juan', '38111222');
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        // Operativa Sandra cobra en mostrador
        $pago = app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_operativo_id' => $this->operativo->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ])['pago'];

        // Cierre y validación de la caja por el admin
        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 35000;
        $caja->save();
        $this->cajaService->validarCaja($caja->id, $this->admin->id);

        // Verificación en Cashflow
        $respuesta = $this->actingAs($this->admin)->get(route('web.cashflow.index'))->assertOk();
        $html = $respuesta->getContent();

        // Muestra al alumno
        $this->assertStringContainsString('Perez, Juan', $html, 'Cashflow debe mostrar el apellido y nombre del alumno');
        // Muestra a la operativa que cobró
        $this->assertStringContainsString('Sandra Mostrador', $html, 'Cashflow debe mostrar en Registró a la operativa que cobró');
        // No debe mostrar al admin como quien registró
        $movsCollection = $respuesta->viewData('movimientos');
        $fila = $movsCollection->first();
        $this->assertNotNull($fila);
        $this->assertSame('Perez, Juan', $fila->alumnoNombre);
        $this->assertSame('Sandra Mostrador', $fila->quienRegistro);
    }

    /**
     * 2. Cobro del dueño sin caja:
     * En Cashflow muestra el apellido y nombre del alumno y a Carlos Administrador en Registró.
     */
    public function test_cobro_del_duenio_sin_caja_muestra_alumno_y_duenio_en_cashflow(): void
    {
        $alumno = $this->crearAlumno('Gomez', 'Martina', '38222333');

        // Admin cobra directo sin caja
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_admin_id' => $this->admin->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);

        $respuesta = $this->actingAs($this->admin)->get(route('web.cashflow.index'))->assertOk();
        $html = $respuesta->getContent();

        $this->assertStringContainsString('Gomez, Martina', $html, 'Cashflow debe mostrar al alumno en cobro del dueño');
        $this->assertStringContainsString('Carlos Administrador', $html, 'Cashflow debe mostrar al dueño en Registró');

        $fila = $respuesta->viewData('movimientos')->first();
        $this->assertSame('Gomez, Martina', $fila->alumnoNombre);
        $this->assertSame('Carlos Administrador', $fila->quienRegistro);
    }

    /**
     * 3. Inscripción:
     * Tanto la cobrada en caja validada como la cobrada directo por el dueño
     * muestran el apellido y nombre del alumno y quién cobró.
     */
    public function test_inscripcion_muestra_alumno_y_quien_cobro_en_caja_y_sin_caja(): void
    {
        // 3.1 Inscripción cobrada por operativa en caja validada
        $alumno1 = $this->crearAlumno('Lopez', 'Sofia', '38333444');
        $cargo1 = CargoAlumno::create([
            'alumno_id' => $alumno1->id,
            'subrubro_id' => $this->subrubroInscripcion->id,
            'tipo' => 'INSCRIPCION',
            'dni' => preg_replace('/[.\s-]+/', '', trim($alumno1->dni)),
            'clave_origen' => 'inscripcion:dni:' . preg_replace('/[.\s-]+/', '', trim($alumno1->dni)),
            'monto_original' => 5000,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => [],
            'estado' => 'VIGENTE',
        ]);
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno1->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_operativo_id' => $this->operativo->id,
            'items' => [],
            'monto_entregado' => 5000,
            'fecha_pago' => '2026-10-12',
        ]);

        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 5000;
        $caja->save();
        $this->cajaService->validarCaja($caja->id, $this->admin->id);

        // 3.2 Inscripción cobrada por dueño directo
        $alumno2 = $this->crearAlumno('Diaz', 'Mateo', '38444555');
        CargoAlumno::create([
            'alumno_id' => $alumno2->id,
            'subrubro_id' => $this->subrubroInscripcion->id,
            'tipo' => 'INSCRIPCION',
            'dni' => preg_replace('/[.\s-]+/', '', trim($alumno2->dni)),
            'clave_origen' => 'inscripcion:dni:' . preg_replace('/[.\s-]+/', '', trim($alumno2->dni)),
            'monto_original' => 5000,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => [],
            'estado' => 'VIGENTE',
        ]);

        app(PagoCuotaService::class)->registrarPagoCuotaAdmin([
            'alumno_id' => $alumno2->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_admin_id' => $this->admin->id,
            'items' => [],
            'monto_entregado' => 5000,
            'fecha_pago' => '2026-10-12',
        ]);

        $respuesta = $this->actingAs($this->admin)->get(route('web.cashflow.index'))->assertOk();
        $movs = $respuesta->viewData('movimientos');

        // Buscar fila de Sofia (operativa)
        $filaSofia = $movs->first(fn($m) => $m->alumnoNombre === 'Lopez, Sofia');
        $this->assertNotNull($filaSofia, 'Debe existir fila para inscripción de Sofia Lopez');
        $this->assertSame('Sandra Mostrador', $filaSofia->quienRegistro);

        // Buscar fila de Mateo (dueño)
        $filaMateo = $movs->first(fn($m) => $m->alumnoNombre === 'Diaz, Mateo');
        $this->assertNotNull($filaMateo, 'Debe existir fila para inscripción de Mateo Diaz');
        $this->assertSame('Carlos Administrador', $filaMateo->quienRegistro);
    }

    /**
     * 4. Gasto sin alumno:
     * Conserva su observación en la columna de Detalle y muestra quién lo cargó.
     */
    public function test_gasto_sin_alumno_conserva_su_observacion_y_muestra_quien_cargo(): void
    {
        // 4.1 Gasto cargado por Sandra en caja y validado
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);
        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => '2026-10-12',
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroGasto->id,
            'monto' => 4200,
            'observaciones' => 'Compra de lavandina y trapos de piso',
            'usuario_id' => $this->operativo->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);
        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 0;
        $caja->save();
        $this->cajaService->validarCaja($caja->id, $this->admin->id);

        // 4.2 Gasto directo del admin en cashflow
        $this->actingAs($this->admin)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroGasto->id,
            'monto' => '8500.00',
            'fecha' => '2026-10-12',
            'observaciones' => 'Abono mensual servicio de internet',
        ])->assertRedirect(route('web.cashflow.index'));

        $respuesta = $this->actingAs($this->admin)->get(route('web.cashflow.index'))->assertOk();
        $html = $respuesta->getContent();
        $movs = $respuesta->viewData('movimientos');

        // Gasto de Sandra
        $this->assertStringContainsString('Compra de lavandina y trapos de piso', $html);
        $this->assertStringContainsString('Sandra Mostrador', $html);
        $gastoSandra = $movs->first(fn($m) => $m->observaciones === 'Compra de lavandina y trapos de piso');
        $this->assertNotNull($gastoSandra);
        $this->assertNull($gastoSandra->alumnoNombre);
        $this->assertSame('Sandra Mostrador', $gastoSandra->quienRegistro);

        // Gasto de Carlos
        $this->assertStringContainsString('Abono mensual servicio de internet', $html);
        $this->assertStringContainsString('Carlos Administrador', $html);
        $gastoCarlos = $movs->first(fn($m) => $m->observaciones === 'Abono mensual servicio de internet');
        $this->assertNotNull($gastoCarlos);
        $this->assertNull($gastoCarlos->alumnoNombre);
        $this->assertSame('Carlos Administrador', $gastoCarlos->quienRegistro);
    }

    /**
     * 5. Validar dos veces no duplica movimientos en Cashflow.
     */
    public function test_validar_caja_dos_veces_no_duplica_movimientos_en_cashflow(): void
    {
        $alumno = $this->crearAlumno('Romero', 'Lucas', '38555666');
        $caja = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_operativo_id' => $this->operativo->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);

        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 35000;
        $caja->save();

        // Validar por primera vez
        $this->cajaService->validarCaja($caja->id, $this->admin->id);
        // Validar por segunda vez (idempotente)
        $this->cajaService->validarCaja($caja->id, $this->admin->id);

        $respuesta = $this->actingAs($this->admin)->get(route('web.cashflow.index'))->assertOk();
        $movs = $respuesta->viewData('movimientos');

        $this->assertSame(1, $movs->total(), 'No debe duplicar movimientos al validar dos veces');
        $this->assertEquals(35000.0, $respuesta->viewData('totalIngresos'));
    }
}
