<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CargoAlumno;
use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
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

        // 4e. Períodos mal escritos o inválidos
        foreach (['2026-9', 'abc', '2026/11'] as $invalido) {
            $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => [$invalido],
            ])->assertStatus(422);
        }
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
    // 9. 'Total pendiente' y el buscador de Cobrar
    // =========================================================================

    public function test_09_total_pendiente_no_cuenta_meses_futuros(): void
    {
        $this->pagarOctubre();

        // 9a. En /caja/cobrar (pantalla de cobro del alumno)
        // Alumno con octubre pagado: Total pendiente muestra $0,00
        $res = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id));
        $res->assertOk();
        $res->assertSee('$0,00');

        // 9b. En /caja/cobrar (buscador de cobrar cuota)
        $resBuscador = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'Stoessel']));
        $resBuscador->assertOk();
        $saldos = $resBuscador->viewData('saldos');
        $this->assertEquals(0.0, $saldos[$this->alumno->id]['total']);

        // 9c. Si en la base de datos existiera una DeudaCuota futura PENDIENTE (por ej. cobro anulado):
        // En la ficha /caja/cobrar: Total pendiente sigue mostrando $0,00 porque filtra periodo <= mes actual.
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-11',
            'monto_original' => 35000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $resFichaConFuturaPendiente = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id));
        $resFichaConFuturaPendiente->assertOk();
        // El encabezado Total pendiente en la ficha ignora noviembre:
        $resFichaConFuturaPendiente->assertSee('$0,00');

        // En CobranzaEstadoService::saldoDeAlumnos se demuestra la divergencia:
        // saldoDeAlumnos suma 35000 porque no excluye periodos futuros.
        $saldoAlumnos = app(CobranzaEstadoService::class)->saldoDeAlumnos(collect([$this->alumno]));
        $this->assertEquals(35000.0, $saldoAlumnos[$this->alumno->id]['total']);

        // Y en el buscador de /caja/cobrar, el saldo mostrado al operador para ese alumno es 35.000:
        $resBuscadorConFutura = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'Stoessel']));
        $resBuscadorConFutura->assertOk();
        $resBuscadorConFutura->assertSee('35.000');
    }
}

