<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CargoAlumno;
use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Configuracion;
use App\Models\Pago;
use App\Models\ReglaPrimerPago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

class VerificacionT18Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $tipoCaja;
    private Alumno $alumno;
    private GrupoPlan $plan;
    private GrupoPlan $plan2;
    private Subrubro $subrubroCuota;
    private Subrubro $subrubroInscripcion;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 10:00:00');
        Storage::fake();
        Configuracion::set('dias_gracia_cobranza', 10);
        if (DB::getSchemaBuilder()->hasTable('reporte_cobertura')) {
            DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        }

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        $this->subrubroCuota = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);

        $this->subrubroInscripcion = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Inscripción',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);

        $this->tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        CajaDeclarada::crear($this->operativo->id, $this->tipoCaja->id);

        $deporte = Deporte::create([
            'nombre' => 'Patin Artistico',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $this->plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 35000.0,
            'activo' => true,
        ]);

        $this->plan2 = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 45000.0,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Martina',
            'apellido' => 'Stoessel',
            'dni' => '40111222',
            'fecha_nacimiento' => '2012-05-10',
            'celular' => '1144445555',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-01-10',
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-01-10',
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pagarOctubre(): void
    {
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 35000,
            'monto_pagado' => 35000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
    }

    // =========================================================================
    // 1. Sin 'confirmar_pago_adelantado' no se cobra un mes futuro
    // =========================================================================

    public function test_01_sin_confirmar_rechaza_por_todos_los_caminos(): void
    {
        $this->pagarOctubre();

        // Camino Operativo (JSON)
        $resOp = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
        ]);
        $resOp->assertStatus(409)
              ->assertJson(['requiere_confirmacion_adelantado' => true]);

        // Camino Admin (JSON)
        $resAdm = $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
        ]);
        $resAdm->assertStatus(409)
               ->assertJson(['requiere_confirmacion_adelantado' => true]);

        // Pedido armado a mano (POST tradicional HTML sin cabecera JSON)
        $resHtml = $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
        ]);
        $resHtml->assertStatus(409)
                ->assertJson(['requiere_confirmacion_adelantado' => true]);

        // Con confirmar_pago_adelantado = 0 explícito
        $resCero = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'confirmar_pago_adelantado' => 0,
        ]);
        $resCero->assertStatus(409)
                ->assertJson(['requiere_confirmacion_adelantado' => true]);

        // Nada fue creado en la base
        $this->assertSame(0, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->count());
        $this->assertSame(0, Pago::count());
    }

    // =========================================================================
    // 2. Importe cambiado (más alto y más bajo que el plan)
    // =========================================================================

    public function test_02_importe_adelantado_cambiado_mas_alto_y_mas_bajo(): void
    {
        $this->pagarOctubre();

        // Caso Más Alto: plan es 35.000, se cobra 42.000 (aumento anunciado)
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => '42.000'],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $deudaNov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertEquals(42000.0, (float) $deudaNov->monto_original);
        $this->assertEquals(42000.0, (float) $deudaNov->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deudaNov->estado);
        $this->assertEquals(0.0, (float) $deudaNov->saldo_pendiente);

        // Caso Más Bajo: plan es 35.000, admin cobra diciembre por 25.000
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-12'],
            'montos_cuota' => ['2026-12' => '25.000'],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $deudaDic = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-12')->sole();
        $this->assertEquals(25000.0, (float) $deudaDic->monto_original);
        $this->assertEquals(25000.0, (float) $deudaDic->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deudaDic->estado);
        $this->assertEquals(0.0, (float) $deudaDic->saldo_pendiente);
    }

    // =========================================================================
    // 3. Precio congelado frente a aumento y cobranza:generar-deudas
    // =========================================================================

    public function test_03_precio_congelado_no_se_duplica_ni_reliquida_al_subir_plan(): void
    {
        $this->pagarOctubre();

        // Cobrar noviembre adelantado a 38.000
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => '38.000'],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        // Plan sube de 35.000 a 55.000
        $this->plan->update(['precio_mensual' => 55000.0]);

        // Simular que llega el 01/11/2026 y corre el comando mensual
        Carbon::setTestNow('2026-11-01 07:00:00');
        Artisan::call('cobranza:generar-deudas');

        $deudasNov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->get();
        $this->assertCount(1, $deudasNov);
        $nov = $deudasNov->first();
        $this->assertEquals(38000.0, (float) $nov->monto_original);
        $this->assertEquals(38000.0, (float) $nov->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $nov->estado);
        $this->assertEquals(0.0, (float) $nov->saldo_pendiente);
    }

    // =========================================================================
    // 4. Bordes del período
    // =========================================================================

    public function test_04_bordes_del_periodo(): void
    {
        // 4a. Mes en curso (2026-10) NO es adelantado: no exige confirmar_pago_adelantado
        $resActual = $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 35000],
        ]);
        $resActual->assertSessionHas('success');
        $this->assertSame(1, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-10')->count());

        // 4b. Límite exacto de 12 meses (2027-10): permitido
        $res12Meses = $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2027-10'],
            'montos_cuota' => ['2027-10' => 35000],
            'confirmar_pago_adelantado' => 1,
        ]);
        $res12Meses->assertSessionHas('success');
        $this->assertSame(1, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2027-10')->count());

        // 4c. 13 meses hacia adelante (2027-11): rechazado con 422
        $res13Meses = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2027-11'],
            'montos_cuota' => ['2027-11' => 35000],
            'confirmar_pago_adelantado' => 1,
        ]);
        $res13Meses->assertStatus(422)
                   ->assertJson(['message' => 'Se puede cobrar por adelantado hasta 12 meses.']);

        // 4d. Mes pasado sin deuda guardada (2026-09 no existe): rechazado por servicio
        $resPasado = $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 35000],
        ]);
        // Redirige con error de sesión por excepción en transacción
        $resPasado->assertSessionHas('error');
        $this->assertSame(0, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-09')->count());

        // 4e. Períodos mal escritos o inválidos (incluye meses inexistentes 2026-13 y 2026-00)
        foreach (['2026-9', 'abc', '2026/11', '2026-13', '2026-00'] as $invalido) {
            $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => [$invalido],
                'montos_cuota' => [$invalido => 35000],
            ])->assertStatus(422);
        }
        $this->assertSame(0, DeudaCuota::where('alumno_id', $this->alumno->id)->whereIn('periodo', ['2026-13', '2026-00'])->count());
        $this->assertSame(2, DeudaCuota::where('alumno_id', $this->alumno->id)->count()); // solo 2026-10 y 2027-10 de los subtests anteriores
    }

    // =========================================================================
    // 5. Mes futuro que YA tiene deuda guardada (pendiente, pagada o condonada)
    // =========================================================================

    public function test_05_mes_futuro_con_deuda_preexistente(): void
    {
        $this->pagarOctubre();

        // 5a. Mes futuro ya PENDIENTE en base (ej: deuda generada previamente por 30.000)
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-11',
            'monto_original' => 30000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // En /caja/cobrar: no está en el selector de adelantados porque ya existe
        $resVista = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id));
        $resVista->assertOk();
        $mesesAdelantables = $resVista->viewData('mesesAdelantables');
        $this->assertFalse($mesesAdelantables->contains('periodo', '2026-11'));
        // Pero aparece en la lista de cuotas pendientes con el badge Adelantado
        $resVista->assertSee('Noviembre 2026');
        $resVista->assertSee('Adelantado');

        // Al cobrarlo: exige confirmar_pago_adelantado porque es futuro
        $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
        ])->assertStatus(409)->assertJson(['requiere_confirmacion_adelantado' => true]);

        // Con confirmación: se cancela conservando su monto_original previo (30.000)
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $deudaNov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertEquals(30000.0, (float) $deudaNov->monto_original);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deudaNov->estado);

        // 5b. Mes futuro ya PAGADO: si se intenta enviar por POST se rechaza con error
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('error');

        // 5c. Mes futuro CONDONADO: no admite pagos
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-12',
            'monto_original' => 35000,
            'monto_pagado' => 0,
            'monto_condonado' => 35000,
            'estado' => DeudaCuota::ESTADO_CONDONADA,
        ]);

        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-12'],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('error');
    }

    // =========================================================================
    // 6. Cruces
    // =========================================================================

    public function test_06a_cruce_adelantado_con_deuda_vieja_impaga(): void
    {
        // Alumno debe septiembre 2026 y quiere pagar noviembre adelantado
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 35000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // 1. Sin confirmar adelantado: primero exige confirmación de adelantado (409)
        $res1 = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
        ]);
        $res1->assertStatus(409)->assertJson(['requiere_confirmacion_adelantado' => true]);

        // 2. Con confirmar adelantado pero sin confirmar deuda anterior: exige confirmar deuda anterior (409)
        $res2 = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'confirmar_pago_adelantado' => 1,
        ]);
        $res2->assertStatus(409)->assertJson(['requiere_confirmacion' => true]);

        // 3. Con ambas confirmaciones pero sin motivo: 422 motivo obligatorio
        $res3 = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'confirmar_pago_adelantado' => 1,
            'confirmar_deuda_anterior' => 1,
        ]);
        $res3->assertStatus(422)->assertJsonValidationErrors(['motivo']);

        // 4. Con todo y motivo: procesa exitosamente
        $res4 = $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'confirmar_pago_adelantado' => 1,
            'confirmar_deuda_anterior' => 1,
            'motivo' => 'Promete regularizar septiembre la semana próxima',
        ]);
        $res4->assertSessionHas('success');

        $pago = Pago::latest('id')->first();
        $this->assertStringContainsString('Motivo por deuda anterior', $pago->observaciones);
    }

    public function test_06b_cruce_adelantado_con_inscripcion_pendiente(): void
    {
        $this->pagarOctubre();

        CargoAlumno::create([
            'alumno_id' => $this->alumno->id,
            'subrubro_id' => $this->subrubroInscripcion->id,
            'tipo' => 'INSCRIPCION',
            'monto_original' => 5000,
            'monto_pagado' => 0,
            'monto_condonado' => 0,
            'estado' => 'VIGENTE',
            'dni' => \App\Services\InscripcionService::dni($this->alumno->dni),
            'clave_origen' => 'inscripcion:dni:' . \App\Services\InscripcionService::dni($this->alumno->dni),
            'calculo' => [],
        ]);

        $res = $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'monto_entregado' => 40000,
            'confirmar_pago_adelantado' => 1,
        ]);
        $res->assertSessionHas('success');

        $cargo = CargoAlumno::where('alumno_id', $this->alumno->id)->sole();
        $this->assertEquals(5000.0, (float) $cargo->pagos->sum('pivot.monto_aplicado'));

        $deudaNov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deudaNov->estado);
        $this->assertEquals(35000.0, (float) $deudaNov->monto_pagado);
    }

    public function test_06c_cruce_adelantado_con_descuento_primer_pago(): void
    {
        // Alumno nuevo ingresado a mediados de octubre
        $alumnoNuevo = Alumno::create([
            'nombre' => 'Lucas',
            'apellido' => 'Pratto',
            'dni' => '40999888',
            'celular' => '1155556666',
            'fecha_nacimiento' => '2010-01-01',
            'deporte_id' => $this->alumno->deporte_id,
            'grupo_id' => $this->alumno->grupo_id,
            'fecha_alta' => '2026-10-16',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumnoNuevo->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-10-16',
            'activo' => true,
        ]);

        ReglaPrimerPago::create([
            'dia_desde' => 16,
            'dia_hasta' => 31,
            'porcentaje' => 50.0,
            'nombre' => 'Segunda quincena',
        ]);

        // Paga octubre (primer pago al 50% = 17.500) y noviembre adelantado (100% = 35.000)
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $alumnoNuevo->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-10', '2026-11'],
            'montos_cuota' => ['2026-10' => 35000, '2026-11' => 35000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $oct = DeudaCuota::where('alumno_id', $alumnoNuevo->id)->where('periodo', '2026-10')->sole();
        $nov = DeudaCuota::where('alumno_id', $alumnoNuevo->id)->where('periodo', '2026-11')->sole();

        $this->assertEquals(17500.0, (float) $oct->monto_original);
        $this->assertEquals(17500.0, (float) $oct->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $oct->estado);

        // Noviembre NO recibe descuento: se cobra al precio pactado
        $this->assertEquals(35000.0, (float) $nov->monto_original);
        $this->assertEquals(35000.0, (float) $nov->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $nov->estado);
    }

    public function test_06d_cruce_adelantado_con_cambio_de_plan(): void
    {
        $this->pagarOctubre();

        // En el cobro de noviembre adelantado, el alumno cambia a plan de 3 veces ($45.000)
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'nuevo_plan_id' => $this->plan2->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 45000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $deudaNov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertEquals(45000.0, (float) $deudaNov->monto_original);
        $this->assertEquals(45000.0, (float) $deudaNov->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deudaNov->estado);

        $nuevoPlanActivo = AlumnoPlan::where('alumno_id', $this->alumno->id)->where('plan_id', $this->plan2->id)->first();
        $this->assertNotNull($nuevoPlanActivo);
    }

    public function test_06e_dos_meses_adelantados_juntos(): void
    {
        $this->pagarOctubre();

        // Sin confirmación: mensaje en plural con ambos nombres
        $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11', '2026-12'],
            'montos_cuota' => ['2026-11' => 35000, '2026-12' => 35000],
        ])->assertStatus(409)
          ->assertJsonFragment(['message' => 'Estás cobrando por adelantado noviembre 2026 y diciembre 2026, que todavía no empezaron. El importe queda fijo aunque después cambie la cuota.']);

        // Con confirmación: ambos meses se cobran y quedan PAGADOS
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11', '2026-12'],
            'montos_cuota' => ['2026-11' => 35000, '2026-12' => 35000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $this->assertSame(2, DeudaCuota::where('alumno_id', $this->alumno->id)
            ->whereIn('periodo', ['2026-11', '2026-12'])
            ->where('estado', DeudaCuota::ESTADO_PAGADA)
            ->count());
    }

    // =========================================================================
    // 7. Anular un cobro adelantado
    // =========================================================================

    public function test_07_anular_cobro_adelantado_y_volver_a_cobrar(): void
    {
        $this->pagarOctubre();

        // Admin cobra noviembre adelantado
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $pago = Pago::latest('id')->first();
        $this->assertSame(Pago::ESTADO_COMPLETADO, $pago->estado);

        // Admin anula el cobro
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Error de tipeo', $this->admin->id);

        $pago->refresh();
        $this->assertSame('ANULADO', $pago->estado);

        $deudaNov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertEquals(0.0, (float) $deudaNov->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $deudaNov->estado);
        $this->assertStringContainsString('[ANULADO]', $deudaNov->observaciones);

        // Ahora se puede volver a cobrar normalmente
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $deudaNov->refresh();
        $this->assertEquals(35000.0, (float) $deudaNov->monto_pagado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deudaNov->estado);
    }

    // =========================================================================
    // 8. Caja, Cashflow, Recibo y Cobranza reflejan el cobro una sola vez
    // =========================================================================

    public function test_08_caja_cashflow_recibo_y_cobranza_una_sola_vez(): void
    {
        $this->pagarOctubre();

        // 8a. Camino Operativo: genera exactamente 1 movimiento operativo y 1 pago
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $pagoOp = Pago::latest('id')->first();
        $this->assertEquals(35000.0, (float) $pagoOp->monto_final);

        $movsOp = MovimientoOperativo::where('pago_id', $pagoOp->id)->get();
        $this->assertCount(1, $movsOp);
        $this->assertEquals(35000.0, (float) $movsOp->first()->monto);

        // Recibo accesible
        $reciboRes = $this->actingAs($this->operativo)->get(route('web.recibos.cuota', $pagoOp->id) . '?inline=1');
        $reciboRes->assertOk();
        $this->assertEquals('application/pdf', $reciboRes->headers->get('Content-Type'));

        // 8b. Camino Admin: genera exactamente 1 CashflowMovimiento
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-12'],
            'montos_cuota' => ['2026-12' => 35000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $pagoAdm = Pago::latest('id')->first();
        $movsCash = CashflowMovimiento::where('monto', 35000)->where('fecha', '2026-10-05')->get();
        $this->assertCount(1, $movsCash);

        // 8c. Cobranza: alumno queda sin saldo pendiente
        $saldos = app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$this->alumno]));
        $this->assertEquals(0.0, $saldos[$this->alumno->id]['total']);
    }

    // =========================================================================
    // 9. Hallazgo 1: Mes futuro pendiente no cuenta como deuda en ninguna pantalla
    // =========================================================================

    public function test_09_hallazgo_1_mes_futuro_pendiente_mismo_saldo_en_cinco_pantallas(): void
    {
        $this->pagarOctubre();

        // Admin cobra noviembre adelantado con importe pactado de $42.000 (plan era $35.000)
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 42000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $pago = Pago::latest('id')->firstOrFail();
        $this->assertSame(Pago::ESTADO_COMPLETADO, $pago->estado);

        // Admin anula el cobro adelantado (caso real de la decisión de Carlos del 10/10)
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Error de tipeo', $this->admin->id);

        // Verificar que en la base el mes queda guardado y PENDIENTE con el importe pactado $42.000 (no se borra)
        $deudaNov = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $deudaNov->estado);
        $this->assertEquals(42000.0, (float) $deudaNov->monto_original);
        $this->assertEquals(0.0, (float) $deudaNov->monto_pagado);
        $this->assertEquals(42000.0, (float) $deudaNov->saldo_pendiente);

        // ---------------------------------------------------------------------
        // Verificación 1: Mismo saldo en las 5 pantallas (ninguna incluye noviembre)
        // ---------------------------------------------------------------------

        // 1. Pantalla de cobro del alumno («Total pendiente» en /caja/cobrar/{id})
        $resFicha = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id));
        $resFicha->assertOk();
        $resFicha->assertSee('Total pendiente');
        $resFicha->assertSee('$0,00');

        // 2. Buscador de Cobrar (/caja/cobrar-cuota)
        $resBuscador = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'Stoessel']));
        $resBuscador->assertOk();
        $saldosBuscador = $resBuscador->viewData('saldos');
        $this->assertEquals(0.0, $saldosBuscador[$this->alumno->id]['total']);
        $this->assertEquals(0, $saldosBuscador[$this->alumno->id]['cuotas_impagas']);
        $resBuscador->assertSee('$0');
        $resBuscador->assertDontSee('42.000');

        // 3. Cobranza (/cobranza)
        $resCobranza = $this->actingAs($this->operativo)->get(route('web.cobranza.index'));
        $resCobranza->assertOk();
        $cobranzaService = app(CobranzaEstadoService::class);
        $resumenCobranza = $cobranzaService->resumenDashboard();
        $this->assertEquals(0.0, $resumenCobranza['total_adeudado']);
        // En el listado unificado, su deuda_total es 0.0 y no tiene cuotas impagas exigibles
        $listadoCobranza = $cobranzaService->listadoCobranza('TODOS');
        $alumnoEnCobranza = $listadoCobranza->firstWhere('id', $this->alumno->id);
        $this->assertEquals(0.0, (float) $alumnoEnCobranza->deuda_total);
        $this->assertEquals(0, (int) $alumnoEnCobranza->cantidad_cuotas_impagas);

        // 4. Inicio (/admin/dashboard)
        $resInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $resInicio->assertOk();
        $reporteInicio = $resInicio->viewData('reporte');
        $this->assertEquals(0, $reporteInicio['deuda']['total']);

        // 5. Reportes (/reportes)
        $resReportes = $this->actingAs($this->admin)->get(route('web.reportes.index'));
        $resReportes->assertOk();
        $reporteGeneral = $resReportes->viewData('reporte');
        $this->assertEquals(0, $reporteGeneral['deuda']['total']);
        $this->assertEquals(0, $reporteGeneral['deuda']['cuotas']);

        // ---------------------------------------------------------------------
        // Verificación 2: El estado del alumno NO es moroso ni deudor
        // ---------------------------------------------------------------------
        $estadoAlumnoInfo = $cobranzaService->estadoAlumno($this->alumno->id);
        $this->assertSame(CobranzaEstadoService::ESTADO_AL_DIA, $estadoAlumnoInfo['estado']);
        $this->assertCount(0, $estadoAlumnoInfo['deudas_pendientes']);
        $this->assertNotSame(CobranzaEstadoService::ESTADO_MOROSO, $estadoAlumnoInfo['estado']);
        $this->assertNotSame(CobranzaEstadoService::ESTADO_DEUDOR, $estadoAlumnoInfo['estado']);

        // ---------------------------------------------------------------------
        // Verificación 3: Cuando el mes empieza, la cuota sí cuenta como deuda
        // por el importe pactado ($42.000) y NO por el precio nuevo del plan ($55.000)
        // ---------------------------------------------------------------------
        // Aumentamos el plan a $55.000 antes de que empiece el mes
        $this->plan->update(['precio_mensual' => 55000.0]);

        // Simular que llega el 02 de noviembre de 2026 (mes ya empezó)
        Carbon::setTestNow('2026-11-02 10:00:00');
        CajaOperativa::query()->update(['estado' => CajaOperativa::ESTADO_VALIDADA]);
        CajaDeclarada::crear($this->operativo->id, $this->tipoCaja->id);
        Artisan::call('cobranza:generar-deudas');

        // La deuda no se duplicó: sigue siendo 1 sola cuota para 2026-11
        $deudasNovPostGeneracion = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->get();
        $this->assertCount(1, $deudasNovPostGeneracion);
        $novPost = $deudasNovPostGeneracion->first();
        // El monto original se mantiene en el pactado ($42.000), no en el del nuevo plan ($55.000)
        $this->assertEquals(42000.0, (float) $novPost->monto_original);

        // Ahora en las 5 pantallas sí cuenta como deuda por $42.000:
        // 1. Ficha
        $resFichaNov = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id));
        $resFichaNov->assertOk()->assertSee('$42.000,00');

        // 2. Buscador
        $resBuscadorNov = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'Stoessel']));
        $resBuscadorNov->assertOk();
        $saldosNov = $resBuscadorNov->viewData('saldos');
        $this->assertEquals(42000.0, $saldosNov[$this->alumno->id]['total']);
        $this->assertEquals(1, $saldosNov[$this->alumno->id]['cuotas_impagas']);

        // 3. Cobranza
        $resumenNov = $cobranzaService->resumenDashboard();
        $this->assertEquals(42000.0, $resumenNov['total_adeudado']);

        // 4. Inicio
        $resInicioNov = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $this->assertEquals(4200000, $resInicioNov->viewData('reporte')['deuda']['total']); // en centavos

        // 5. Reportes
        $resReportesNov = $this->actingAs($this->admin)->get(route('web.reportes.index'));
        $this->assertEquals(4200000, $resReportesNov->viewData('reporte')['deuda']['total']);

        // Estado del alumno: dentro de días de gracia (día 2 <= 10) es EN_PLAZO con 1 deuda pendiente
        $estadoNov = $cobranzaService->estadoAlumno($this->alumno->id);
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $estadoNov['estado']);
        $this->assertCount(1, $estadoNov['deudas_pendientes']);

        // Si avanza más allá de días de gracia (día 15): pasa a MOROSO
        Carbon::setTestNow('2026-11-15 10:00:00');
        $estadoNovMoroso = $cobranzaService->estadoAlumno($this->alumno->id);
        $this->assertSame(CobranzaEstadoService::ESTADO_MOROSO, $estadoNovMoroso['estado']);

        // ---------------------------------------------------------------------
        // Verificación 4: Volver a cobrar tras anular (no se duplica)
        // ---------------------------------------------------------------------
        // Volvemos la fecha a octubre (cuando todavía era adelantado) para probar el cobro adelantado tras anular
        Carbon::setTestNow('2026-10-06 10:00:00');
        CajaOperativa::query()->update(['estado' => CajaOperativa::ESTADO_VALIDADA]);
        CajaDeclarada::crear($this->operativo->id, $this->tipoCaja->id);
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 42000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        // La cuota pasa a PAGADA
        $novReCobrado = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->get();
        $this->assertCount(1, $novReCobrado);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $novReCobrado->first()->estado);
        $this->assertEquals(42000.0, (float) $novReCobrado->first()->monto_pagado);
        $this->assertEquals(0.0, (float) $novReCobrado->first()->saldo_pendiente);
    }

    // =========================================================================
    // 10. Hallazgo 2: Períodos con mes inexistente (2026-13 y 2026-00)
    // =========================================================================

    public function test_10_hallazgo_2_periodos_invalidos_mes_13_y_00(): void
    {
        $this->pagarOctubre();

        // 10a. Mes 13: se rechaza con HTTP 422 de validación
        $res13 = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-13'],
            'montos_cuota' => ['2026-13' => 35000],
            'confirmar_pago_adelantado' => 1,
        ]);
        $res13->assertStatus(422)
              ->assertJsonValidationErrors(['periodos.0']);

        // 10b. Mes 00: se rechaza con HTTP 422 de validación
        $res00 = $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-00'],
            'montos_cuota' => ['2026-00' => 35000],
            'confirmar_pago_adelantado' => 1,
        ]);
        $res00->assertStatus(422)
              ->assertJsonValidationErrors(['periodos.0']);

        // 10c. Ni deuda ni pago creados en la base
        $this->assertSame(0, DeudaCuota::where('alumno_id', $this->alumno->id)->whereIn('periodo', ['2026-13', '2026-00'])->count());
        $this->assertSame(0, Pago::count());

        // 10d. Un período válido (2026-11) sigue funcionando
        // Sin confirmar: 409
        $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
        ])->assertStatus(409)->assertJson(['requiere_confirmacion_adelantado' => true]);

        // Con confirmar: cobra exitosamente
        $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 35000],
            'confirmar_pago_adelantado' => 1,
        ])->assertSessionHas('success');

        $this->assertSame(1, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->count());
        $this->assertSame(1, Pago::count());
    }
}


