<?php

namespace Tests\Feature;

use App\Models\{Asistencia, CajaOperativa, CashflowMovimiento, Clase, Deporte, Grupo, MovimientoOperativo, Nivel, Subrubro, TipoCaja, User};
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PantallasPendientesT89Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private User $otra;
    private CajaOperativa $caja;
    private TipoCaja $medio;
    private Subrubro $subrubro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true, 'name' => 'Autor real T89']);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->otra = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true, 'name' => 'Titular caja T89']);
        $this->medio = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->subrubro = Subrubro::where('permitido_para', 'OPERATIVO')->firstOrFail();
        $this->caja = CajaOperativa::create(['usuario_operativo_id' => $this->otra->id,
            'estado' => 'ABIERTA', 'apertura_at' => now(), 'tipo_caja_efectivo_id' => $this->medio->id,
            'efectivo_inicial' => 0]);
    }

    private function movimiento(array $datos = []): MovimientoOperativo
    {
        return MovimientoOperativo::create(array_merge([
            'caja_operativa_id' => $this->caja->id, 'usuario_id' => $this->admin->id,
            'subrubro_id' => $this->subrubro->id, 'tipo_caja_id' => $this->medio->id,
            'fecha' => today(), 'monto' => 1234, 'observaciones' => 'Movimiento visible T89', 'estado' => 'ACTIVO',
        ], $datos));
    }

    public function test_movimientos_muestra_autor_real_y_no_lo_confunde_con_titular_de_caja(): void
    {
        $this->movimiento();
        $this->actingAs($this->operativa)->get(route('web.movimientos.index'))->assertOk()
            ->assertSee('Registró')->assertSee('Autor real T89')->assertDontSee('Titular caja T89')
            ->assertSee(route('web.caja.detalle', $this->caja->id), false);
    }

    public function test_otra_caja_es_consulta_filtrada_y_no_expone_operaciones_admin(): void
    {
        $this->movimiento();
        $privado = Subrubro::where('permitido_para', 'ADMIN')->firstOrFail();
        $this->movimiento(['subrubro_id' => $privado->id, 'observaciones' => 'Secreto ADMIN T89', 'monto' => 98765]);
        $antes = $this->caja->fresh()->getAttributes();
        $this->actingAs($this->operativa)->get(route('web.caja.detalle', $this->caja->id))->assertOk()
            ->assertSee('Movimiento visible T89')->assertDontSee('Secreto ADMIN T89')->assertDontSee('98.765')
            ->assertDontSee('data-abrir-cancelar', false)->assertDontSee('>Editar<', false)
            ->assertDontSee('>Resumen<', false)->assertDontSee('>Validar<', false);
        $this->assertSame($antes, $this->caja->fresh()->getAttributes());
        $this->actingAs($this->admin)->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('Secreto ADMIN T89');
    }

    public function test_consultar_no_habilita_cerrar_ni_editar_otra_caja(): void
    {
        $mov = $this->movimiento();
        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $this->caja->id), [])->assertForbidden();
        $this->get(route('web.caja.movimientos.editar', [$this->caja->id, $mov->id]))->assertForbidden();
        $this->put(route('web.caja.movimientos.update', [$this->caja->id, $mov->id]), [])->assertForbidden();
        $this->delete(route('web.caja.movimientos.destroy', [$this->caja->id, $mov->id]))->assertForbidden();
        $this->post(route('web.caja.movimientos.cancelar.store', [$this->caja->id, $mov->id]), ['motivo' => 'Intento ajeno'])->assertForbidden();
        $profesor = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]);
        $this->actingAs($profesor)->get(route('web.caja.detalle', $this->caja->id))->assertForbidden();
        $this->assertSame('ABIERTA', $this->caja->fresh()->estado);
        $this->assertSame('1234.00', $mov->fresh()->monto);
    }

    public function test_detalle_separa_fecha_contable_y_hora_real_del_registro(): void
    {
        $mov = $this->movimiento(['fecha' => '2026-10-01']);
        $mov->forceFill(['created_at' => '2026-10-02 18:47:00'])->save();
        $this->actingAs($this->admin)->get(route('web.caja.detalle', $this->caja->id))->assertOk()
            ->assertSee('Hora')->assertSee('01/10/2026')->assertSee('18:47');
    }

    public function test_cashflow_directo_tiene_acceso_a_historial_y_operativa_sigue_sin_acceso(): void
    {
        $editable = Subrubro::where('es_reservado_sistema', false)->firstOrFail();
        $mov = CashflowMovimiento::create(['fecha' => today(), 'tipo_caja_id' => $this->medio->id,
            'subrubro_id' => $editable->id, 'monto' => 1234, 'usuario_admin_id' => $this->admin->id]);
        $this->actingAs($this->admin)->get(route('web.cashflow.index'))->assertOk()
            ->assertSee('aria-label="Historial"', false)->assertSee(route('web.cashflow.movimientos.show', $mov->id), false);
        $this->get(route('web.cashflow.movimientos.show', $mov->id))->assertOk();
        $this->actingAs($this->operativa)->get(route('web.cashflow.movimientos.show', $mov->id))->assertForbidden();
    }

    public function test_inicio_distingue_sin_lista_todos_ausentes_y_presentes(): void
    {
        $grupo = Grupo::create(['deporte_id' => Deporte::firstOrFail()->id,
            'nivel_id' => Nivel::firstOrFail()->id, 'activo' => true]);
        $clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => today(), 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false]);
        $alumno = \App\Models\Alumno::create(['nombre' => 'Alumno', 'apellido' => 'T89', 'dni' => '44958901',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000',
            'deporte_id' => $grupo->deporte_id, 'grupo_id' => $grupo->id, 'fecha_alta' => today(), 'activo' => true]);
        $url = route('web.operativo.dashboard');
        $this->actingAs($this->operativa)->get($url)->assertOk()->assertSee('Sin lista')->assertDontSee('Todos ausentes');
        $asistencia = Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => false]);
        $this->get($url)->assertOk()->assertSee('Todos ausentes')->assertDontSee('Sin lista');
        $asistencia->update(['presente' => true]);
        $this->get($url)->assertOk()->assertSee('1 presente')->assertDontSee('Todos ausentes');
    }

    public function test_recibo_de_otro_turno_abre_el_pago_correcto_y_gasto_no_inventa_recibo(): void
    {
        $grupo = Grupo::create(['deporte_id' => Deporte::firstOrFail()->id, 'nivel_id' => Nivel::firstOrFail()->id, 'activo' => true]);
        $alumno = \App\Models\Alumno::create(['nombre' => 'Alumno', 'apellido' => 'Recibo T89', 'dni' => '44958902',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'fecha_alta' => today(),
            'deporte_id' => $grupo->deporte_id, 'grupo_id' => $grupo->id, 'activo' => true]);
        $plan = \App\Models\GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 1234, 'activo' => true]);
        $pago = \App\Models\Pago::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'mes' => now()->month,
            'anio' => now()->year, 'monto_base' => 1234, 'porcentaje_aplicado' => 100, 'monto_final' => 1234,
            'monto_cuota' => 1234, 'fecha_pago' => today(), 'estado' => 'COMPLETADO']);
        $this->movimiento(['alumno_id' => $alumno->id, 'pago_id' => $pago->id]);
        $this->movimiento(['observaciones' => 'Gasto sin pago T89']);
        $respuesta = $this->actingAs($this->operativa)->get(route('web.movimientos.index'))->assertOk();
        $url = route('web.recibos.cuota', $pago->id);
        $this->assertSame(2, substr_count($respuesta->getContent(), $url.'?inline=1'));
        $this->get($url.'?inline=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
