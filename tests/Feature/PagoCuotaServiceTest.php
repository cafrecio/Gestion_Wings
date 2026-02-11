<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\DeudaCuota;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagoCuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    private PagoCuotaService $service;
    private TipoCaja $tipoCaja;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Infraestructura común
        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);
        $this->tipoCaja = TipoCaja::create(['nombre' => 'Caja General', 'activo' => true]);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'rol' => 'ADMIN',
        ]);

        // Usar flujo admin en los tests para evitar complejidad de CajaOperativa
        $this->service = app(PagoCuotaService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Limpiar freeze de tiempo
        parent::tearDown();
    }

    /**
     * Helper: crea Deporte → Grupo → GrupoPlan → Alumno → AlumnoPlan
     */
    private function crearAlumnoConPlan(
        float $precioMensual = 20000,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        bool $activo = true
    ): array {
        $deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);

        $grupo = Grupo::create([
            'nombre' => 'Patín Inicial',
            'deporte_id' => $deporte->id,
            'activo' => true,
        ]);

        $grupoPlan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => $precioMensual,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'dni' => '12345678',
            'celular' => '1122334455',
            'fecha_nacimiento' => '2000-01-01',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);

        $alumnoPlan = AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $grupoPlan->id,
            'fecha_desde' => $fechaDesde ?? Carbon::now()->startOfMonth()->toDateString(),
            'fecha_hasta' => $fechaHasta,
            'activo' => $activo,
        ]);

        return compact('deporte', 'grupo', 'grupoPlan', 'alumno', 'alumnoPlan');
    }

    /**
     * Ejecuta pago admin (evita dependencia de caja operativa).
     */
    private function pagarAdmin(int $alumnoId, array $items, ?string $fechaPago = null): array
    {
        return $this->service->registrarPagoCuotaAdmin([
            'alumno_id' => $alumnoId,
            'tipo_caja_id' => $this->tipoCaja->id,
            'usuario_admin_id' => $this->admin->id,
            'items' => $items,
            'fecha_pago' => $fechaPago,
        ]);
    }

    // ---------------------------------------------------------------
    // TEST 1: Auto-creación de deuda futura
    // ---------------------------------------------------------------

    public function test_autocrea_deuda_futura_con_monto_del_plan(): void
    {
        Carbon::setTestNow('2026-02-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 25000,
            fechaDesde: '2026-01-01',
        );

        // No existe deuda para 2026-03
        $this->assertDatabaseMissing('deuda_cuotas', [
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-03',
        ]);

        $resultado = $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-03', 'monto' => 25000],
        ]);

        // Debe haberse creado la deuda automáticamente
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-03',
            'monto_original' => '25000.00',
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        // Pago creado correctamente
        $this->assertEquals('25000.00', $resultado['pago']->monto_final);

        // Pivote creado
        $this->assertDatabaseHas('pago_deuda_cuota', [
            'pago_id' => $resultado['pago']->id,
            'monto_aplicado' => '25000.00',
        ]);
    }

    public function test_autocrea_deuda_periodo_vigente(): void
    {
        Carbon::setTestNow('2026-02-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
        );

        // Pagar el mes vigente (febrero) sin deuda previa
        $resultado = $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-02', 'monto' => 20000],
        ]);

        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-02',
            'monto_original' => '20000.00',
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
    }

    public function test_idempotencia_no_duplica_deuda_existente(): void
    {
        Carbon::setTestNow('2026-02-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
        );

        // Crear deuda manualmente
        DeudaCuota::create([
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-03',
            'monto_original' => 20000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Pagar sobre deuda existente → no debe crear otra
        $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-03', 'monto' => 20000],
        ]);

        $count = DeudaCuota::where('alumno_id', $data['alumno']->id)
            ->where('periodo', '2026-03')
            ->count();

        $this->assertEquals(1, $count);
    }

    // ---------------------------------------------------------------
    // TEST 2: Bloqueo de período pasado
    // ---------------------------------------------------------------

    public function test_bloquea_periodo_pasado_sin_deuda_previa(): void
    {
        Carbon::setTestNow('2026-03-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('período pasado (2026-02)');

        $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-02', 'monto' => 20000],
        ]);
    }

    public function test_permite_periodo_pasado_si_deuda_ya_existe(): void
    {
        Carbon::setTestNow('2026-03-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
        );

        // Deuda creada previamente (por admin o motor mensual)
        DeudaCuota::create([
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-02',
            'monto_original' => 20000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Debe pasar sin error
        $resultado = $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-02', 'monto' => 20000],
        ]);

        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-02',
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
    }

    public function test_error_alumno_sin_plan_activo(): void
    {
        Carbon::setTestNow('2026-02-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
            fechaHasta: '2026-01-31', // Plan ya vencido
            activo: false,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sin plan aplicable para el período 2026-03');

        $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-03', 'monto' => 20000],
        ]);
    }

    // ---------------------------------------------------------------
    // TEST 3: FIFO fuerte
    // ---------------------------------------------------------------

    public function test_fifo_rechaza_deuda_vieja_parcial(): void
    {
        Carbon::setTestNow('2026-05-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
        );

        // Crear deudas para Mar, Abr, May
        foreach (['2026-03', '2026-04', '2026-05'] as $periodo) {
            DeudaCuota::create([
                'alumno_id' => $data['alumno']->id,
                'periodo' => $periodo,
                'monto_original' => 20000,
                'monto_pagado' => 0,
                'estado' => DeudaCuota::ESTADO_PENDIENTE,
            ]);
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('FIFO');

        // Mar queda parcial (15k de 20k) → FIFO violation
        $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-03', 'monto' => 15000],
            ['periodo' => '2026-04', 'monto' => 20000],
            ['periodo' => '2026-05', 'monto' => 15000],
        ]);
    }

    public function test_fifo_permite_ultima_deuda_parcial(): void
    {
        Carbon::setTestNow('2026-05-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
        );

        foreach (['2026-03', '2026-04', '2026-05'] as $periodo) {
            DeudaCuota::create([
                'alumno_id' => $data['alumno']->id,
                'periodo' => $periodo,
                'monto_original' => 20000,
                'monto_pagado' => 0,
                'estado' => DeudaCuota::ESTADO_PENDIENTE,
            ]);
        }

        // Mar=20k (completa), Abr=20k (completa), May=10k (parcial) → válido FIFO
        $resultado = $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-03', 'monto' => 20000],
            ['periodo' => '2026-04', 'monto' => 20000],
            ['periodo' => '2026-05', 'monto' => 10000],
        ]);

        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-03',
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-04',
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-05',
            'monto_pagado' => '10000.00',
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    public function test_fifo_item_unico_sin_restriccion(): void
    {
        Carbon::setTestNow('2026-03-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
        );

        DeudaCuota::create([
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-03',
            'monto_original' => 20000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Un solo item parcial → sin validación FIFO
        $resultado = $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-03', 'monto' => 5000],
        ]);

        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $data['alumno']->id,
            'periodo' => '2026-03',
            'monto_pagado' => '5000.00',
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    public function test_fifo_ordena_items_automaticamente(): void
    {
        Carbon::setTestNow('2026-04-15');

        $data = $this->crearAlumnoConPlan(
            precioMensual: 20000,
            fechaDesde: '2026-01-01',
        );

        foreach (['2026-03', '2026-04'] as $periodo) {
            DeudaCuota::create([
                'alumno_id' => $data['alumno']->id,
                'periodo' => $periodo,
                'monto_original' => 20000,
                'monto_pagado' => 0,
                'estado' => DeudaCuota::ESTADO_PENDIENTE,
            ]);
        }

        // Items enviados en orden inverso → debe reordenar internamente
        $resultado = $this->pagarAdmin($data['alumno']->id, [
            ['periodo' => '2026-04', 'monto' => 10000],
            ['periodo' => '2026-03', 'monto' => 20000],
        ]);

        $this->assertDatabaseHas('deuda_cuotas', [
            'periodo' => '2026-03',
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
        $this->assertDatabaseHas('deuda_cuotas', [
            'periodo' => '2026-04',
            'monto_pagado' => '10000.00',
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    // ---------------------------------------------------------------
    // TEST 4: Cambio de plan programado (crítico)
    // ---------------------------------------------------------------

    public function test_cambio_plan_programado_usa_plan_del_periodo(): void
    {
        Carbon::setTestNow('2026-02-25');

        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);

        $grupo = Grupo::create([
            'nombre' => 'Hockey Inicial',
            'deporte_id' => $deporte->id,
            'activo' => true,
        ]);

        // Plan A: 15000/mes, vigente hasta fin de febrero
        $planA = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 15000,
            'activo' => true,
        ]);

        // Plan B: 25000/mes, vigente desde marzo
        $planB = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 25000,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'María',
            'apellido' => 'García',
            'dni' => '87654321',
            'celular' => '1199887766',
            'fecha_nacimiento' => '2000-06-15',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);

        // AlumnoPlan A: vigente hasta 2026-02-28
        // Nota: creamos primero el plan inactivo (el viejo) para no triggear el boot
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $planA->id,
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-02-28',
            'activo' => false,
        ]);

        // AlumnoPlan B: vigente desde 2026-03-01, sin fecha_hasta
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $planB->id,
            'fecha_desde' => '2026-03-01',
            'fecha_hasta' => null,
            'activo' => true,
        ]);

        // Cobrar marzo el 25/02 (sin deuda previa)
        $resultado = $this->pagarAdmin($alumno->id, [
            ['periodo' => '2026-03', 'monto' => 25000],
        ]);

        // Debe usar precio del Plan B (25000), no Plan A (15000)
        $deuda = DeudaCuota::where('alumno_id', $alumno->id)
            ->where('periodo', '2026-03')
            ->first();

        $this->assertNotNull($deuda);
        $this->assertEquals('25000.00', $deuda->monto_original);
        $this->assertEquals(DeudaCuota::ESTADO_PAGADA, $deuda->estado);
    }

    public function test_cambio_plan_cobra_febrero_con_plan_viejo(): void
    {
        Carbon::setTestNow('2026-02-25');

        $deporte = Deporte::create([
            'nombre' => 'Voley',
            'tipo_liquidacion' => 'HORA',
            'activo' => true,
        ]);

        $grupo = Grupo::create([
            'nombre' => 'Voley Mixto',
            'deporte_id' => $deporte->id,
            'activo' => true,
        ]);

        $planA = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 18000,
            'activo' => true,
        ]);

        $planB = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 30000,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Pedro',
            'apellido' => 'López',
            'dni' => '11223344',
            'celular' => '1155667788',
            'fecha_nacimiento' => '2000-01-01',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $planA->id,
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-02-28',
            'activo' => false,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $planB->id,
            'fecha_desde' => '2026-03-01',
            'fecha_hasta' => null,
            'activo' => true,
        ]);

        // Cobrar febrero (mes vigente) → debe usar Plan A (18000)
        $resultado = $this->pagarAdmin($alumno->id, [
            ['periodo' => '2026-02', 'monto' => 18000],
        ]);

        $deuda = DeudaCuota::where('alumno_id', $alumno->id)
            ->where('periodo', '2026-02')
            ->first();

        $this->assertNotNull($deuda);
        $this->assertEquals('18000.00', $deuda->monto_original);
        $this->assertEquals(DeudaCuota::ESTADO_PAGADA, $deuda->estado);
    }
}
