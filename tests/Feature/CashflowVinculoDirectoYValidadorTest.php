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
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T28 (segunda vuelta): Vincular sin adivinar y mostrar quién validó.
 *
 * Criterio de aceptación:
 * 1. Validación de caja guarda el id del movimiento operativo en cada renglón de Cashflow generado.
 * 2. Renglón con id muestra el alumno del movimiento y a quien lo cargó, sin mirar la observación.
 * 3. Renglón viejo de caja validada (sin id guardado): si la observación coincide exacto con un único
 *    movimiento de la caja, muestra el alumno; si no coincide exacto, muestra la observación original y no adivina.
 * 4. La columna de validó muestra las iniciales correctas cuando viene de caja y queda vacía cuando es directo
 *    del dueño; el title tiene el nombre completo.
 */
class CashflowVinculoDirectoYValidadorTest extends TestCase
{
    use \Tests\Concerns\VerificaMovimientosDeCaja;
    use RefreshDatabase;

    private User $adminCarlos;
    private User $sandra;
    private TipoCaja $tipoEfectivo;
    private Subrubro $subrubroCuota;
    private Subrubro $subrubroGasto;
    private CajaService $cajaService;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 10:00:00');

        $this->adminCarlos = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
            'name' => 'Carlos Bonifacio',
        ]);

        $this->sandra = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
            'name' => 'Sandra Mostrador',
        ]);

        $this->tipoEfectivo = TipoCaja::first() ?? TipoCaja::create([
            'nombre' => 'Efectivo',
            'abreviatura' => 'EFE',
            'activo' => true,
        ]);

        $rubroIngresos = Rubro::firstOrCreate(['nombre' => 'Cuotas', 'tipo' => 'INGRESO'], ['observacion' => '']);
        $rubroEgresos  = Rubro::firstOrCreate(['nombre' => 'Gastos', 'tipo' => 'EGRESO'], ['observacion' => '']);

        $this->subrubroCuota = Subrubro::firstOrCreate(
            ['nombre' => 'Cuota Mensual'],
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
        $this->cajaService->configurarMostrador($this->tipoEfectivo->id, $this->adminCarlos->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. Validación de caja guarda el id del movimiento operativo en cada renglón de Cashflow generado.
     */
    public function test_validacion_de_caja_guarda_el_id_del_movimiento_operativo_en_cada_renglon_de_cashflow(): void
    {
        $alumno = $this->crearAlumno('Pérez', 'Juan', '38111222');
        $caja = CajaDeclarada::crear($this->sandra->id, $this->tipoEfectivo->id);

        app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_operativo_id' => $this->sandra->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);

        $movGasto = MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => '2026-10-12',
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $this->subrubroGasto->id,
            'monto' => 4200,
            'observaciones' => 'Compra de lavandina',
            'usuario_id' => $this->sandra->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 30800;
        $caja->save();

        $this->verificarMovimientosDeCaja($caja, $this->adminCarlos);
        $this->cajaService->validarCaja($caja->id, $this->adminCarlos->id);

        $movimientosCashflow = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_CAJA)
            ->where('referencia_id', $caja->id)
            ->get();

        $this->assertCount(2, $movimientosCashflow);

        foreach ($movimientosCashflow as $cfMov) {
            $this->assertNotNull($cfMov->movimiento_operativo_id, 'Cada renglón debe tener su movimiento_operativo_id');
            $this->assertInstanceOf(MovimientoOperativo::class, $cfMov->movimientoOperativo);
        }

        $cfGasto = $movimientosCashflow->firstWhere('subrubro_id', $this->subrubroGasto->id);
        $this->assertSame($movGasto->id, $cfGasto->movimiento_operativo_id);
    }

    /**
     * 2. Renglón con id muestra el alumno del movimiento y a quien lo cargó, sin mirar la observación.
     */
    public function test_renglon_con_id_muestra_alumno_del_movimiento_y_quien_lo_cargo_sin_mirar_observacion(): void
    {
        $alumno = $this->crearAlumno('Gómez', 'Martina', '38222333');
        $caja = CajaDeclarada::crear($this->sandra->id, $this->tipoEfectivo->id);

        app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_operativo_id' => $this->sandra->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);

        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 35000;
        $caja->save();

        $this->verificarMovimientosDeCaja($caja, $this->adminCarlos);
        $this->cajaService->validarCaja($caja->id, $this->adminCarlos->id);

        $cfMov = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_CAJA)
            ->where('referencia_id', $caja->id)
            ->firstOrFail();

        // Modificamos arbitrariamente la observación del cashflow para probar que NO depende del texto
        $cfMov->observaciones = 'Texto arbitrario modificado que no contiene ningun numero ni nombre';
        $cfMov->save();

        $response = $this->actingAs($this->adminCarlos)->get(route('web.cashflow.index'))->assertOk();
        $movs = $response->viewData('movimientos');
        $fila = $movs->first();

        $this->assertSame('Gómez, Martina', $fila->alumnoNombre);
        $this->assertSame('Sandra Mostrador', $fila->quienRegistro);
        $response->assertSee('Gómez, Martina');
        $response->assertSee('Sandra Mostrador');
    }

    /**
     * 3. Renglón viejo de caja validada (sin id guardado):
     *    si la observación coincide exacto con un único movimiento de la caja, muestra el alumno;
     *    si no coincide exacto, muestra la observación original y no adivina.
     */
    public function test_renglon_viejo_sin_id_coincidencia_exacta_muestra_alumno_y_sin_coincidencia_exacta_muestra_observacion(): void
    {
        $alumno1 = $this->crearAlumno('López', 'Sofía', '38333444');
        $alumno2 = $this->crearAlumno('Fernández', 'Lucas', '38444555');

        $caja = CajaDeclarada::crear($this->sandra->id, $this->tipoEfectivo->id);

        $pago1 = app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno1->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_operativo_id' => $this->sandra->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);

        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 35000;
        $caja->save();

        $this->verificarMovimientosDeCaja($caja, $this->adminCarlos);
        $this->cajaService->validarCaja($caja->id, $this->adminCarlos->id);

        $cfMov = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_CAJA)
            ->where('referencia_id', $caja->id)
            ->firstOrFail();

        // Simulamos que es un renglón viejo: borramos movimiento_operativo_id
        $cfMov->movimiento_operativo_id = null;
        $cfMov->save();

        // Caso A: La observación coincide exacto con el movimiento de la caja
        $response = $this->actingAs($this->adminCarlos)->get(route('web.cashflow.index'))->assertOk();
        $fila = $response->viewData('movimientos')->first();
        $this->assertSame('López, Sofía', $fila->alumnoNombre);

        // Caso B: La observación fue cambiada y NO coincide exacto con ningún movimiento
        $cfMov->observaciones = 'Cobro modificado sin match exacto';
        $cfMov->save();

        $response2 = $this->actingAs($this->adminCarlos)->get(route('web.cashflow.index'))->assertOk();
        $fila2 = $response2->viewData('movimientos')->first();
        $this->assertNull($fila2->alumnoNombre, 'Nunca debe adivinar un alumno sin certeza');
        $this->assertSame('Cobro modificado sin match exacto', $fila2->observaciones);
        $response2->assertSee('Cobro modificado sin match exacto');
        $response2->assertDontSee('López, Sofía');
    }

    /**
     * 4. La columna de validó muestra las iniciales correctas cuando viene de caja y queda vacía cuando es directo
     *    del dueño; el title tiene el nombre completo.
     */
    public function test_columna_valido_muestra_iniciales_correctas_cuando_viene_de_caja_y_vacia_cuando_es_directo(): void
    {
        // Administrador que valida la caja
        $adminVanina = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
            'name' => 'Vanina Attorresi',
        ]);

        // 1. Movimiento que vino de caja validada por Vanina
        $alumno = $this->crearAlumno('Díaz', 'Camila', '38999000');
        $caja = CajaDeclarada::crear($this->sandra->id, $this->tipoEfectivo->id);
        app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_operativo_id' => $this->sandra->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);
        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 35000;
        $caja->save();
        $this->verificarMovimientosDeCaja($caja, $adminVanina);
        $this->cajaService->validarCaja($caja->id, $adminVanina->id);

        // 2. Movimiento directo del dueño sin caja (cargado por Carlos)
        $alumnoDirecto = $this->crearAlumno('Morales', 'Mateo', '38999111');
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin([
            'alumno_id' => $alumnoDirecto->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'usuario_admin_id' => $this->adminCarlos->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);

        $response = $this->actingAs($this->adminCarlos)->get(route('web.cashflow.index'))->assertOk();
        $movs = $response->viewData('movimientos');

        // Movimiento de caja: Validador es Vanina Attorresi -> Iniciales 'VA'
        $filaCaja = $movs->firstWhere('alumnoNombre', 'Díaz, Camila');
        $this->assertNotNull($filaCaja);
        $this->assertSame('VA', $filaCaja->validadorIniciales);
        $this->assertSame('Vanina Attorresi', $filaCaja->validadorNombre);

        // Movimiento directo: Validador es null
        $filaDirecto = $movs->firstWhere('alumnoNombre', 'Morales, Mateo');
        $this->assertNotNull($filaDirecto);
        $this->assertNull($filaDirecto->validadorIniciales);
        $this->assertNull($filaDirecto->validadorNombre);

        // Verificación en el HTML:
        // Columna de escritorio muestra VA con title Vanina Attorresi
        $response->assertSee('title="Vanina Attorresi"', false);
        $response->assertSee('VA');

        // En celular (opción 2) badge con Val: VA
        $response->assertSee('Val: VA');
    }

    /**
     * Prueba adicional: Desempate de iniciales entre administradores si colisionan (ej. CB y CZ).
     */
    public function test_desempate_de_iniciales_cuando_dos_administradores_comparten_iniciales(): void
    {
        // $this->adminCarlos es 'Carlos Bonifacio' -> CB
        $adminBenitez = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
            'name' => 'Carlos Benítez',
        ]);

        $this->assertSame('CB', $this->adminCarlos->iniciales());
        // Desempata la segunda letra con la última del apellido Benítez (Z)
        $this->assertSame('CZ', $adminBenitez->iniciales());
    }

    private function crearAlumno(string $apellido, string $nombre, string $dni): Alumno
    {
        $deporte = Deporte::first() ?? Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
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
}
