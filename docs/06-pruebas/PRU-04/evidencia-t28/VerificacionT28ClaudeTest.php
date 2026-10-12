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
 * Verificación de Claude sobre T28 (hecho por Gemini). Evidencia, no prueba permanente.
 *
 * Cashflow no guarda a qué movimiento de caja corresponde cada renglón: lo deduce
 * comparando importe, rubro y observación. Acá se prueba eso donde puede fallar:
 * muchos cobros iguales en la misma caja, más de una página, y con filtros.
 */
class VerificacionT28ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $sandra;
    private TipoCaja $efectivo;
    private TipoCaja $mercadoPago;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 10:00:00');

        $this->admin  = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true, 'name' => 'Carlos Administrador']);
        $this->sandra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Sandra Mostrador']);
        $this->efectivo    = TipoCaja::create(['nombre' => 'Efectivo', 'abreviatura' => 'EFE', 'activo' => true]);
        $this->mercadoPago = TipoCaja::create(['nombre' => 'Mercado Pago', 'abreviatura' => 'MP', 'activo' => true]);

        $ingresos = Rubro::firstOrCreate(['nombre' => 'Cuotas', 'tipo' => 'INGRESO'], ['observacion' => '']);
        Subrubro::firstOrCreate(['nombre' => 'Cuota Mensual'], [
            'rubro_id' => $ingresos->id, 'permitido_para' => User::ROL_OPERATIVO, 'afecta_caja' => true, 'es_reservado_sistema' => true,
        ]);
        app(CajaService::class)->configurarMostrador($this->efectivo->id, $this->admin->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_muchos_cobros_del_mismo_importe_cada_renglon_muestra_a_su_alumno_en_todas_las_paginas(): void
    {
        // 40 alumnos pagan la misma cuota en el mismo turno: más de una página de Cashflow.
        $caja = CajaDeclarada::crear($this->sandra->id, $this->efectivo->id);
        foreach (range(1, 40) as $n) {
            $this->cobrar($this->alumno($n), $n % 2 ? $this->efectivo : $this->mercadoPago);
        }
        $this->validar($caja, 20 * 35000);

        $this->assertSame(40, CashflowMovimiento::count());

        $vistos = collect();
        foreach ([1, 2] as $pagina) {
            $filas = $this->actingAs($this->admin)->get(route('web.cashflow.index', ['page' => $pagina]))->assertOk()->viewData('movimientos');
            foreach ($filas as $fila) {
                $this->assertRenglonCoincideConSuAlumno($fila);
                $vistos->push($fila->alumnoNombre);
            }
        }
        $this->assertCount(40, $vistos);
        $this->assertCount(40, $vistos->unique(), 'Ningún alumno puede repetirse ni faltar entre las dos páginas.');
    }

    public function test_con_filtro_por_medio_de_pago_cada_renglon_sigue_mostrando_a_su_alumno(): void
    {
        $caja = CajaDeclarada::crear($this->sandra->id, $this->efectivo->id);
        foreach (range(1, 10) as $n) {
            $this->cobrar($this->alumno($n), $n % 2 ? $this->efectivo : $this->mercadoPago);
        }
        $this->validar($caja, 5 * 35000);

        foreach ([$this->efectivo, $this->mercadoPago] as $medio) {
            $filas = $this->actingAs($this->admin)->get(route('web.cashflow.index', ['tipo_caja_id' => $medio->id]))->assertOk()->viewData('movimientos');
            $this->assertCount(5, $filas);
            foreach ($filas as $fila) {
                $this->assertRenglonCoincideConSuAlumno($fila);
            }
        }
    }

    /** La observación del cobro nombra al alumno por número: el nombre mostrado tiene que ser el de ese número. */
    private function assertRenglonCoincideConSuAlumno(object $fila): void
    {
        $this->assertSame('Sandra Mostrador', $fila->quienRegistro);
        $this->assertSame(1, preg_match('/alumno #(\d+)/', (string) $fila->observaciones, $m), 'El cobro no trae el número de alumno en su observación.');
        $alumno = Alumno::findOrFail((int) $m[1]);
        $this->assertSame($alumno->apellido.', '.$alumno->nombre, $fila->alumnoNombre);
    }

    private function alumno(int $n): Alumno
    {
        $deporte = Deporte::first() ?? Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel   = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo   = Grupo::first() ?? Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan    = GrupoPlan::first() ?? GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 35000, 'activo' => true]);

        $alumno = Alumno::create([
            'apellido' => sprintf('Apellido%02d', $n), 'nombre' => sprintf('Nombre%02d', $n), 'dni' => (string) (38000000 + $n),
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0001', 'fecha_alta' => '2026-10-01', 'activo' => true,
        ]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-10-01', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 35000, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);

        return $alumno;
    }

    private function cobrar(Alumno $alumno, TipoCaja $medio): void
    {
        app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $medio->id,
            'usuario_operativo_id' => $this->sandra->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);
    }

    private function validar(CajaOperativa $caja, float $contado): void
    {
        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = $contado;
        $caja->save();
        app(CajaService::class)->validarCaja($caja->id, $this->admin->id);
    }
}
