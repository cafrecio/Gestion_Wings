<?php

namespace Tests\Feature;

use App\Models\{Clase, Deporte, Grupo, Liquidacion, Nivel, Profesor, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verificación independiente de T46 (Claude CAB, 28/10/2026): ataques que no
 * cubren las pruebas de la entrega de Codex.
 */
class VerificacionT46ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Profesor $profesora;
    private Profesor $suplente;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $d = Deporte::create(['nombre' => 'Patín verificación', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $n = Nivel::create(['nombre' => 'Nivel verificación']);
        $this->grupo = Grupo::create(['deporte_id' => $d->id, 'nivel_id' => $n->id, 'activo' => true]);
        $this->profesora = $this->docente('Ana', '71000001');
        $this->suplente = $this->docente('Luz', '71000002');
    }

    private function docente(string $nombre, string $dni): Profesor
    {
        return Profesor::create(['deporte_id' => $this->grupo->deporte_id, 'nombre' => $nombre, 'apellido' => 'Verifica', 'dni' => $dni,
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 5000,
            'porcentaje_comision' => 20, 'activo' => true]);
    }

    private function clase(string $fecha, array $profesoras, array $extra = []): Clase
    {
        $c = Clase::create($extra + ['grupo_id' => $this->grupo->id, 'fecha' => $fecha,
            'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'cancelada' => false, 'validada_para_liquidacion' => true]);
        $c->profesores()->attach(collect($profesoras)->pluck('id')->all());
        return $c;
    }

    private function baja()
    {
        return $this->actingAs($this->admin)->patchJson(route('web.profesores.toggle-activo', $this->profesora->id), ['activo' => false]);
    }

    /** Una clase futura compartida con otra profesora también la bloquea: sigue a su nombre. */
    public function test_clase_futura_compartida_bloquea_hasta_sacarla(): void
    {
        $c = $this->clase('2026-11-02', [$this->profesora, $this->suplente]);

        $this->baja()->assertUnprocessable()->assertJsonFragment(['error' => 'Tiene 1 clases futuras o en curso. Reasignalas antes de desactivar a la profesora.']);
        $this->assertTrue($this->profesora->fresh()->activo);

        $this->patchJson(route('web.clases.profesores', $c->id), ['profesores' => [$this->suplente->id]])->assertOk();
        $this->baja()->assertOk()->assertJson(['activo' => false]);
        $this->assertSame([$this->suplente->id], $c->profesores()->pluck('profesores.id')->all());
    }

    /** La clase de hoy ya terminada no bloquea; después no se la puede correr a la tarde con la inactiva adentro. */
    public function test_clase_de_hoy_terminada_no_se_reprograma_con_la_inactiva(): void
    {
        $c = $this->clase('2026-10-28', [$this->profesora]);
        $this->baja()->assertOk();

        $this->putJson(route('web.clases.update', $c->id), ['fecha' => '2026-10-28', 'hora_inicio' => '18:00', 'hora_fin' => '19:00',
            'profesores' => [$this->profesora->id]])->assertUnprocessable();
        $this->putJson(route('web.clases.update', $c->id), ['fecha' => '2026-10-28', 'hora_inicio' => '18:00', 'hora_fin' => '19:00'])
            ->assertUnprocessable();

        $c->refresh();
        $this->assertSame('10:00', $c->hora_inicio->format('H:i'));
        $this->assertSame([$this->profesora->id], $c->profesores()->pluck('profesores.id')->all());
        $this->assertFalse($this->profesora->clases()->noTerminadas()->exists());
    }

    /** Dos meses dictados: se liquida cada uno una vez; no se duplica forzando la ruta. */
    public function test_inactiva_con_dos_meses_liquida_cada_uno_una_sola_vez(): void
    {
        $this->clase('2026-09-15', [$this->profesora]);
        $this->clase('2026-10-10', [$this->profesora]);
        $this->baja()->assertOk();

        $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesora->id, 'mes' => 9, 'anio' => 2026])
            ->assertSessionHas('success');
        $this->get(route('web.liquidaciones.create'))->assertOk()
            ->assertViewHas('profesores', fn ($p) => $p->contains('id', $this->profesora->id));

        $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesora->id, 'mes' => 9, 'anio' => 2026])
            ->assertSessionHas('error');
        $this->assertSame(1, Liquidacion::where('mes', 9)->count());

        $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesora->id, 'mes' => 10, 'anio' => 2026])
            ->assertSessionHas('success');
        $this->assertSame(['5000.00', '5000.00'], Liquidacion::orderBy('mes')->pluck('total_calculado')->all());
        $this->get(route('web.liquidaciones.create'))->assertOk()
            ->assertViewHas('profesores', fn ($p) => !$p->contains('id', $this->profesora->id));
    }

    /** Una clase dictada sin lista se valida a mano después de la baja y entonces se puede liquidar. */
    public function test_validar_a_mano_despues_de_la_baja_habilita_liquidar_lo_dictado(): void
    {
        $c = $this->clase('2026-10-20', [$this->profesora], ['validada_para_liquidacion' => false]);
        $this->baja()->assertOk();
        $this->get(route('web.liquidaciones.create'))->assertOk()
            ->assertViewHas('profesores', fn ($p) => !$p->contains('id', $this->profesora->id));

        $this->patch(route('web.clases.toggle-validada', $c->id))->assertSessionHas('success');
        $this->assertTrue($c->fresh()->validada_para_liquidacion);

        $this->get(route('web.liquidaciones.create'))->assertOk()
            ->assertViewHas('profesores', fn ($p) => $p->contains('id', $this->profesora->id));
        $this->post(route('web.liquidaciones.store'), ['profesor_id' => $this->profesora->id, 'mes' => 10, 'anio' => 2026])
            ->assertSessionHas('success');
        $this->assertSame('5000.00', Liquidacion::sole()->total_calculado);
    }
}
