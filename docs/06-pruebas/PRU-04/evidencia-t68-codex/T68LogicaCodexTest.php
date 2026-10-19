<?php

namespace Tests\Feature;

use App\Models\{Clase, Deporte, Grupo, GrupoPlan, Liquidacion, Nivel, Profesor, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Evidencia independiente; fuera de tests/ para no alterar el corte permanente.
class T68LogicaCodexTest extends TestCase
{
    use RefreshDatabase;
    private User $admin;
    private User $operativo;
    private Grupo $grupo;
    private Profesor $profesor;

    protected function setUp(): void
    {
        if (getenv('DB_DATABASE') !== 'wings_testing_codex') {
            throw new \RuntimeException('Este instrumento exige DB_DATABASE=wings_testing_codex antes de iniciar Laravel.');
        }
        parent::setUp();
        $this->assertSame('wings_testing_codex', DB::connection()->getDatabaseName());
        Carbon::setTestNow('2026-10-19 18:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol'=>'ADMIN', 'activo'=>true]);
        $this->operativo = User::factory()->create(['rol'=>'OPERATIVO', 'activo'=>true]);
        $this->grupo = Grupo::create(['deporte_id'=>Deporte::where('tipo_liquidacion', 'COMISION')->firstOrFail()->id, 'nivel_id'=>Nivel::first()->id, 'activo'=>true]);
        GrupoPlan::create(['grupo_id'=>$this->grupo->id, 'clases_por_semana'=>2, 'precio_mensual'=>25000, 'activo'=>true]);
        $this->profesor = Profesor::create(['nombre'=>'Prueba', 'apellido'=>'Codex', 'dni'=>'30111222', 'fecha_nacimiento'=>'1990-01-01', 'direccion'=>'Direccion de prueba', 'localidad'=>'Prueba', 'telefono'=>'1100000000', 'deporte_id'=>$this->grupo->deporte_id, 'porcentaje_comision'=>20, 'activo'=>true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function clase(string $fecha, string $hora='16:00', bool $cancelada=false, ?Grupo $grupo=null): Clase
    {
        $clase = Clase::create(['grupo_id'=>($grupo ?? $this->grupo)->id, 'fecha'=>$fecha, 'hora_inicio'=>$hora, 'hora_fin'=>'23:00', 'cancelada'=>$cancelada, 'validada_para_liquidacion'=>false]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    private function liq(int $mes, int $anio=2026, string $pago='PENDIENTE'): Liquidacion
    {
        return Liquidacion::create(['profesor_id'=>$this->profesor->id, 'mes'=>$mes, 'anio'=>$anio, 'tipo'=>'COMISION', 'total_calculado'=>'15000.00', 'estado'=>'CERRADA', 'estado_pago'=>$pago]);
    }

    private function guardar(string $nombre, string $html): void
    {
        $html = preg_replace_callback('~(?:https?://[^/"\s]+)?/build/([^"\s]+)~', fn($m)=>'file:///'.str_replace('\\', '/', public_path('build/'.$m[1])), $html);
        $html = preg_replace('/(name="_token"[^>]*value=")[^"]*/', '$1TOKEN_SINTETICO_OMITIDO', $html);
        $html = preg_replace('/(name="csrf-token"[^>]*content=")[^"]*/', '$1TOKEN_SINTETICO_OMITIDO', $html);
        file_put_contents(__DIR__.'/'.$nombre.'.html', $html);
    }

    public function test_filtros_hoy_orden_limites_y_relaciones(): void
    {
        $this->clase('2026-10-18');
        $this->clase('2026-10-19', '07:00', true);
        $futura = $this->clase('2026-10-20');
        $hoyTarde = $this->clase('2026-10-19', '20:00');
        $hoyTemprano = $this->clase('2026-10-19', '08:00');
        for ($i=21; $i<=26; $i++) $this->clase('2026-10-'.$i);
        $ids = [$hoyTemprano->id, $hoyTarde->id, $futura->id];
        $g = $this->actingAs($this->admin)->get(route('web.grupos.show', $this->grupo->id))->assertOk();
        $p = $this->get(route('web.profesores.show', $this->profesor->id))->assertOk();
        $this->assertSame($ids, $g->viewData('clasesFuturas')->take(3)->pluck('id')->all());
        $this->assertSame($ids, $p->viewData('clasesProximas')->take(3)->pluck('id')->all());
        $this->assertCount(6, $g->viewData('clasesFuturas'));
        $this->assertCount(5, $p->viewData('clasesProximas'));
        foreach ($p->viewData('clasesProximas') as $clase) {
            $this->assertTrue($clase->relationLoaded('grupo'));
            $this->assertTrue($clase->grupo->relationLoaded('deporte'));
            $this->assertTrue($clase->grupo->relationLoaded('nivel'));
        }
        $this->guardar('grupo-datos', $g->getContent());
        $this->guardar('profesor-clases', $p->getContent());
    }

    public function test_vacios_operativo_sin_acciones_admin_y_profesor_sin_deporte(): void
    {
        $this->actingAs($this->operativo)->get(route('web.grupos.show', $this->grupo->id))->assertOk()
            ->assertSee('Sin clases futuras programadas.')->assertDontSee('+ Programar clases')->assertDontSee('Eliminar')->assertDontSee('Editar');
        $r = $this->actingAs($this->admin)->get(route('web.grupos.show', $this->grupo->id))->assertOk()->assertSee('+ Programar clases');
        $this->guardar('grupo-vacio', $r->getContent());
        $this->profesor->update(['deporte_id'=>null]);
        $r = $this->get(route('web.profesores.show', $this->profesor->id))->assertOk()
            ->assertSee('Sin liquidaciones registradas.')->assertSee('Sin clases asignadas próximas.')->assertDontSee('Próximamente');
        $this->guardar('profesor-vacio-sin-deporte', $r->getContent());
    }

    public function test_operativo_y_profesor_sin_acceso_a_importes_por_rutas_alternativas(): void
    {
        $liq = $this->liq(9);
        $clase = $this->clase('2026-10-19');
        foreach ([$this->operativo, User::factory()->create(['rol'=>'PROFESOR', 'activo'=>true])] as $usuario) {
            $this->actingAs($usuario);
            foreach ([route('web.profesores.index'), route('web.profesores.show', $this->profesor->id), route('web.liquidaciones.index'), route('web.liquidaciones.show', $liq->id), route('web.recibos.liquidacion', $liq->id), '/reportes/sueldos', '/cashflow'] as $url) {
                $this->get($url)->assertForbidden()->assertDontSee('$15,000.00');
            }
            $this->get(route('web.clases.show', $clase->id))->assertOk()->assertDontSee('$15,000.00')->assertDontSee('Historial de liquidaciones');
        }
        $this->actingAs($this->operativo)->get(route('web.grupos.show', $this->grupo->id))->assertOk()->assertDontSee('$15,000.00');
    }

    public function test_liquidaciones_orden_periodo_importe_y_pago(): void
    {
        $this->liq(12, 2025);
        $vieja = $this->liq(1);
        $pagada = $this->liq(9, 2026, 'PAGADA');
        $nueva = $this->liq(10);
        $r = $this->actingAs($this->admin)->get(route('web.profesores.show', $this->profesor->id))->assertOk();
        $this->assertSame([$nueva->id,$pagada->id,$vieja->id], $r->viewData('liquidaciones')->take(3)->pluck('id')->all());
        $r->assertSeeInOrder(['10/2026', '$15,000.00', 'PENDIENTE', '09/2026', '$15,000.00', 'PAGADA', '01/2026', '12/2025']);
        $this->guardar('profesor-liquidaciones', $r->getContent());
    }

    public function test_importe_ajustado_debe_coincidir_con_detalle_alcanzable(): void
    {
        $liq = $this->liq(9);
        $this->actingAs($this->admin)->post(route('web.liquidaciones.ajustar', $liq->id), [
            'monto_final'=>'18000.50', 'monto_anterior'=>'15000.00', 'motivo'=>'Ajuste de prueba Codex',
        ])->assertRedirect(route('web.liquidaciones.show', $liq->id))->assertSessionHasNoErrors();
        $this->assertSame('18000.50', $liq->fresh()->monto_a_pagar);
        $this->actingAs($this->admin)->get(route('web.liquidaciones.show', $liq->id))->assertOk()->assertSee('$18,000.50');
        $r = $this->get(route('web.profesores.show', $this->profesor->id))->assertOk();
        $this->guardar('profesor-ajuste', $r->getContent());
        $r->assertSee('$18,000.50');
    }

    public function test_no_hay_javascript_incrustado_en_las_dos_fichas(): void
    {
        foreach (['grupos', 'profesores'] as $vista) {
            $texto = file_get_contents(resource_path('views/'.$vista.'/show.blade.php'));
            $this->assertDoesNotMatchRegularExpression('/\bon[a-z]+\s*=|<script(?![^>]*\bsrc\s*=)/i', $texto, $vista);
        }
    }

    public function test_cantidad_consultas_no_crece_con_mas_clases_y_grupos(): void
    {
        $this->clase('2026-10-19');
        $this->actingAs($this->admin);
        $medir = function (string $ruta): int {
            DB::enableQueryLog(); DB::flushQueryLog();
            $this->get($ruta)->assertOk();
            $n = count(DB::getQueryLog()); DB::disableQueryLog();
            return $n;
        };
        $rutaG = route('web.grupos.show', $this->grupo->id);
        $rutaP = route('web.profesores.show', $this->profesor->id);
        $medir($rutaG); $medir($rutaP);
        $antes = [$medir($rutaG), $medir($rutaP)];
        for ($i=0; $i<4; $i++) {
            $nivel = Nivel::create(['nombre'=>'Nivel Codex '.$i, 'activo'=>true]);
            $grupo = Grupo::create(['deporte_id'=>$this->grupo->deporte_id, 'nivel_id'=>$nivel->id, 'activo'=>true]);
            $this->clase('2026-10-20', '16:00', false, $grupo);
            $this->clase('2026-10-21', '16:00');
        }
        $despues = [$medir($rutaG), $medir($rutaP)];
        file_put_contents(__DIR__.'/consultas.json', json_encode(compact('antes','despues'), JSON_PRETTY_PRINT));
        $this->assertSame($antes, $despues);
    }

    public function test_aislamiento_de_grupo_y_profesor_y_limite_del_historial(): void
    {
        $otroGrupo = Grupo::create(['deporte_id'=>$this->grupo->deporte_id, 'nivel_id'=>Nivel::create(['nombre'=>'Otro nivel'])->id, 'activo'=>true]);
        $propia = $this->clase('2026-10-19');
        $ajenaGrupo = $this->clase('2026-10-20', '16:00', false, $otroGrupo);
        $ajenaProfesor = $this->clase('2026-10-21');
        $ajenaProfesor->profesores()->detach($this->profesor->id);
        $g = $this->actingAs($this->admin)->get(route('web.grupos.show', $this->grupo->id))->assertOk();
        $p = $this->get(route('web.profesores.show', $this->profesor->id))->assertOk();
        $this->assertSame([$propia->id, $ajenaProfesor->id], $g->viewData('clasesFuturas')->pluck('id')->all());
        $this->assertSame([$propia->id, $ajenaGrupo->id], $p->viewData('clasesProximas')->pluck('id')->all());
        for ($mes=1; $mes<=7; $mes++) $this->liq($mes);
        $r = $this->get(route('web.profesores.show', $this->profesor->id))->assertOk();
        $this->assertSame([7,6,5,4,3], $r->viewData('liquidaciones')->pluck('mes')->all());
    }

    public function test_muestra_con_importe_largo_y_control_de_login(): void
    {
        $liq = $this->liq(9);
        $liq->ajustarMontoFinal('9999999999.99', $this->admin->id, 'Importe largo de prueba');
        // Se conserva también el cálculo largo para medir el texto que hoy utiliza T68.
        $abierta = Liquidacion::create(['profesor_id'=>$this->profesor->id, 'mes'=>10, 'anio'=>2026, 'tipo'=>'COMISION', 'total_calculado'=>'9999999999.99', 'estado'=>'ABIERTA', 'estado_pago'=>'PENDIENTE']);
        $r = $this->actingAs($this->admin)->get(route('web.profesores.show', $this->profesor->id))->assertOk()->assertSee('$9,999,999,999.99');
        $this->guardar('profesor-importe-largo', $r->getContent());
        auth()->logout();
        $this->guardar('login-control', $this->get('/login')->assertOk()->getContent());
    }
}
