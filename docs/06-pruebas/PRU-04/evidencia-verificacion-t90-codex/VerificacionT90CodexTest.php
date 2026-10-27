<?php

namespace Tests\VerificacionCodex;

use App\Http\Controllers\ClaseWebController;
use App\Models\{Alumno, Asistencia, Clase, Deporte, Grupo, Liquidacion, Nivel, Pago, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\{LiquidacionPagoService, LiquidacionService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Pruebas propias fuera del contador permanente. Cada escenario tiene base transaccional propia. */
class VerificacionT90CodexTest extends TestCase
{
    use RefreshDatabase;

    private const ERROR = 'No se puede cancelar: la clase está en una liquidación cerrada o pagada.';
    private User $admin;
    private User $operativa;
    private Profesor $profesor;
    private Grupo $grupo;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'wings_testing_codex'
            || DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('T90 independiente: solo wings_testing_codex antes de migrar.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-27 12:00:00');
        $this->withoutVite();
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Prueba T90', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'T90'])->id, 'activo' => true]);
        $this->profesor = Profesor::create([
            'nombre' => 'Profesor', 'apellido' => 'Verificación', 'dni' => '31000990',
            'fecha_nacimiento' => '1985-01-01', 'direccion' => 'Prueba 90', 'localidad' => 'Prueba',
            'deporte_id' => $deporte->id, 'tipo_liquidacion' => 'HORA', 'valor_hora' => 13000, 'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function clase(string $fecha = '2026-09-15', ?string $serie = null, ?Profesor $profesor = null): Clase
    {
        $c = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => $fecha, 'hora_inicio' => '14:00',
            'hora_fin' => '15:30', 'serie_id' => $serie, 'cancelada' => false, 'validada_para_liquidacion' => true]);
        $c->profesores()->attach(($profesor ?? $this->profesor)->id);
        return $c;
    }

    private function liquidacion(string $tipo, string $estado, Clase $clase): Liquidacion
    {
        if ($tipo === 'COMISION') {
            $this->profesor->deporte->update(['tipo_liquidacion' => 'COMISION']);
            $this->profesor->update(['tipo_liquidacion' => 'COMISION', 'porcentaje_comision' => 35]);
            $a = Alumno::create(['nombre' => 'Alumno', 'apellido' => 'T90', 'dni' => '51000990',
                'fecha_nacimiento' => '2010-01-01', 'fecha_alta' => '2026-01-01', 'celular' => '1100000090',
                'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
            Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $a->id, 'presente' => true]);
            Pago::create(['alumno_id' => $a->id, 'mes' => 9, 'anio' => 2026, 'fecha_pago' => '2026-09-05',
                'monto_base' => 52000, 'porcentaje_aplicado' => 100, 'monto_final' => 52000,
                'monto_cuota' => 52000, 'estado' => Pago::ESTADO_COMPLETADO]);
        }
        $s = app(LiquidacionService::class);
        $l = $s->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $this->assertSame($tipo, $l->tipo);
        $this->assertEquals($tipo === 'HORA' ? 19500 : 18200, $l->total_calculado);
        if ($estado !== 'ABIERTA') {
            $s->cerrarLiquidacion($l->id);
        }
        if ($estado === 'PAGADA') {
            $r = Rubro::firstOrCreate(['nombre' => 'Sueldos'], ['tipo' => 'EGRESO']);
            $sub = Subrubro::create(['rubro_id' => $r->id, 'nombre' => 'Sueldo prueba T90', 'permitido_para' => 'ADMIN',
                'afecta_caja' => false, 'activo' => true]);
            $caja = TipoCaja::create(['nombre' => 'Prueba T90', 'saldo_inicial' => 500000, 'activo' => true]);
            app(LiquidacionPagoService::class)->marcarComoPagada($l->id, ['fecha_pago' => '2026-10-01',
                'tipo_caja_id' => $caja->id, 'subrubro_id' => $sub->id, 'admin_id' => $this->admin->id]);
            $this->assertSame('CERRADA', $l->fresh()->estado);
            $this->assertTrue($l->fresh()->estaPagada());
        }
        if ($estado === 'CANCELADA') {
            $this->actingAs($this->admin)->post(route('web.liquidaciones.cancelar', $l->id),
                ['motivo' => 'Revisión autorizada para verificar FIN-12'])->assertSessionHas('success');
            $this->assertSame('CANCELADA', $l->fresh()->estado);
            $this->assertSame($this->admin->id, $l->fresh()->usuario_cancelacion_id);
        }
        return $l->fresh();
    }

    private function enviar(User $actor, Clase $clase, string $formato, bool $serie = false)
    {
        $this->actingAs($actor)->from(route('web.clases.show', $clase->id));
        $body = ['motivo_cancelacion' => 'Prueba independiente T90', 'cancelar_serie' => $serie];
        $url = route('web.clases.toggle-cancelada', $clase->id);
        return match ($formato) {
            'json' => $this->patchJson($url, $body),
            'xhr' => $this->patch($url, $body, ['X-Requested-With' => 'XMLHttpRequest']),
            default => $this->patch($url, $body),
        };
    }

    private function foto(): array
    {
        $foto = [];
        foreach (['clases', 'clase_profesor', 'asistencias', 'liquidaciones', 'liquidacion_detalles', 'cashflow_movimientos'] as $tabla) {
            $foto[$tabla] = DB::table($tabla)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        }
        return $foto;
    }

    public static function protegidas(): iterable
    {
        foreach (['HORA', 'COMISION'] as $tipo) foreach (['CERRADA', 'PAGADA'] as $estado)
            foreach (['ADMIN', 'OPERATIVO'] as $rol) foreach (['json', 'html', 'xhr'] as $formato)
                foreach ([false, true] as $serie) yield "$tipo-$estado-$rol-$formato-serie".(int) $serie => [$tipo, $estado, $rol, $formato, $serie];
    }

    #[DataProvider('protegidas')]
    public function test_rechaza_sin_modificar_clase_lista_detalles_ni_plata(string $tipo, string $estado, string $rol, string $formato, bool $serie): void
    {
        $uuid = (string) Str::uuid();
        $c = $this->clase(serie: $uuid);
        $this->liquidacion($tipo, $estado, $c);
        $this->clase('2026-10-28', $uuid);
        $antes = $this->foto();
        $r = $this->enviar($rol === 'ADMIN' ? $this->admin : $this->operativa, $c, $formato, $serie);
        if ($formato === 'html') {
            $r->assertRedirect(route('web.clases.show', $c->id))->assertSessionHas('error', self::ERROR);
        } else {
            $r->assertStatus(422)->assertExactJson(['success' => false, 'message' => self::ERROR]);
        }
        $this->assertSame($antes, $this->foto(), 'El rechazo debe conservar todas las filas de negocio.');
        if ($tipo === 'HORA' && $estado === 'CERRADA' && $rol === 'ADMIN' && $formato === 'json' && !$serie) {
            file_put_contents(__DIR__.'/respuesta-rechazo.json', json_encode($r->json(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
        }
    }

    public static function permitidas(): iterable
    {
        foreach (['HORA', 'COMISION'] as $tipo) foreach (['SIN', 'ABIERTA', 'CANCELADA'] as $estado)
            foreach (['ADMIN', 'OPERATIVO'] as $rol) foreach (['json', 'html'] as $formato)
                yield "$tipo-$estado-$rol-$formato" => [$tipo, $estado, $rol, $formato];
    }

    #[DataProvider('permitidas')]
    public function test_cancelaciones_permitidas_y_desbloqueo_real_fin12(string $tipo, string $estado, string $rol, string $formato): void
    {
        $c = $this->clase();
        if ($tipo === 'COMISION' && $estado === 'SIN') {
            $this->profesor->deporte->update(['tipo_liquidacion' => 'COMISION']);
            $this->profesor->update(['tipo_liquidacion' => 'COMISION', 'porcentaje_comision' => 35]);
        }
        $liq = $estado === 'SIN' ? null : $this->liquidacion($tipo, $estado, $c);
        $antes = $liq ? $liq->fresh()->getAttributes() : null;
        $detalles = DB::table('liquidacion_detalles')->get()->toArray();
        $r = $this->enviar($rol === 'ADMIN' ? $this->admin : $this->operativa, $c, $formato);
        if ($formato === 'json') $r->assertOk()->assertJson(['success' => true]);
        else $r->assertSessionHas('success', 'Clase cancelada.');
        $this->assertTrue($c->fresh()->cancelada);
        $this->assertSame('Prueba independiente T90', $c->fresh()->motivo_cancelacion);
        $this->assertEquals($detalles, DB::table('liquidacion_detalles')->get()->toArray());
        if ($liq) $this->assertSame($antes, $liq->fresh()->getAttributes());
    }

    public function test_comision_cubre_clase_sin_detalle_otro_profesor_no_y_otro_mes_no(): void
    {
        $principal = $this->clase();
        $otra = $this->clase('2026-09-25');
        $liq = $this->liquidacion('COMISION', 'CERRADA', $principal);
        $this->assertSame(0, $liq->detalles()->where('tipo_referencia', 'CLASE')->count());
        $this->enviar($this->operativa, $otra, 'json')->assertStatus(422);
        $this->assertFalse($otra->fresh()->cancelada);
        $deOtroMes = $this->clase('2026-10-15');
        $this->enviar($this->admin, $deOtroMes, 'json')->assertOk();
        $this->assertTrue($deOtroMes->fresh()->cancelada);
        $segundo = $this->profesor->replicate(); $segundo->dni = '31000991'; $segundo->save();
        $deOtroProfesor = $this->clase('2026-09-26', profesor: $segundo);
        $this->enviar($this->admin, $deOtroProfesor, 'json')->assertOk();
        $this->assertTrue($deOtroProfesor->fresh()->cancelada);
        $this->assertSame('CERRADA', $liq->fresh()->estado);
    }

    public function test_hora_no_extiende_bloqueo_a_clases_sin_detalle_y_mira_todos_los_profesores(): void
    {
        $c = $this->clase();
        $liq = $this->liquidacion('HORA', 'CERRADA', $c);
        $fuera = $this->clase('2026-09-22');
        $this->enviar($this->admin, $fuera, 'json')->assertOk();
        $this->assertTrue($fuera->fresh()->cancelada);
        $extra = $this->profesor->replicate(); $extra->dni = '31000991'; $extra->save();
        $c->profesores()->attach($extra->id);
        $this->enviar($this->admin, $c, 'json')->assertStatus(422);
        $this->assertFalse($c->fresh()->cancelada);
        $this->assertSame(1, $liq->detalles()->count());
    }

    public function test_serie_solo_cancela_hoy_y_futuras_sin_tocar_pasadas_ni_otra_serie(): void
    {
        $serie = (string) Str::uuid();
        $pasada = $this->clase('2026-10-26', $serie);
        $hoy = $this->clase('2026-10-27', $serie);
        $futura = $this->clase('2026-11-03', $serie);
        $otra = $this->clase('2026-11-03', (string) Str::uuid());
        $this->enviar($this->admin, $hoy, 'json', true)->assertOk()->assertJson(['success' => true, 'message' => '2 clase(s) de la serie canceladas.']);
        $this->assertFalse($pasada->fresh()->cancelada);
        $this->assertTrue($hoy->fresh()->cancelada);
        $this->assertTrue($futura->fresh()->cancelada);
        $this->assertFalse($otra->fresh()->cancelada);
    }

    public function test_reactivacion_del_duenio_conserva_asistencias_y_operativa_no_puede_reactivar(): void
    {
        $c = $this->clase();
        $this->liquidacion('COMISION', 'ABIERTA', $c);
        $asistencias = DB::table('asistencias')->get()->toArray();
        $this->assertCount(1, $asistencias);
        $this->enviar($this->operativa, $c, 'json')->assertOk();
        $this->enviar($this->operativa, $c, 'json')->assertForbidden();
        $this->assertTrue($c->fresh()->cancelada);
        $this->enviar($this->admin, $c, 'json')->assertOk()->assertJson(['success' => true, 'cancelada' => false]);
        $this->assertFalse($c->fresh()->cancelada);
        // Conducta anterior; conservar motivo y choques al reactivar quedan expresamente en T62.
        $this->assertNull($c->fresh()->motivo_cancelacion);
        $this->assertEquals($asistencias, DB::table('asistencias')->get()->toArray());
    }

    public function test_ficha_contiene_panel_y_no_existe_api_activa_para_saltar_el_control(): void
    {
        $c = $this->clase();
        $this->liquidacion('HORA', 'CERRADA', $c);
        $this->actingAs($this->admin)->get(route('web.clases.show', $c->id))->assertOk()
            ->assertSee('id="panel-cancelar"', false)->assertSee('id="flash-cancelar"', false);
        $rutas = collect(app('router')->getRoutes()->getRoutes());
        $cancelar = $rutas->filter(fn ($r) => str_ends_with($r->uri(), '/cancelar') && str_starts_with($r->uri(), 'clases/'));
        $this->assertCount(1, $cancelar);
        $this->assertSame(ClaseWebController::class.'@toggleCancelada', $cancelar->first()->getActionName());
        $this->assertCount(0, $rutas->filter(fn ($r) => str_starts_with($r->uri(), 'api/')));
        $antes = $this->foto();
        // El router actual contesta 405 a ese PUT, sin ruta API registrada ni escritura.
        $this->actingAs($this->admin)->putJson('/api/admin/clases/'.$c->id, ['cancelada' => true])->assertStatus(405);
        $this->assertSame($antes, $this->foto());
    }
}
