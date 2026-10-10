<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, CashflowMovimiento, Deporte, MovimientoOperativo, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\{ReporteMensualService, SubrubroSueldoService};
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Verificación independiente; ejecutar por ruta, fuera de la suite habitual. */
class VerificacionT16Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($this->admin);
    }

    private function migrar(): void
    {
        (require base_path('database/migrations/2026_10_10_180000_clasificar_subrubros_existentes.php'))->up();
    }

    private function sub(string $nombre): Subrubro
    {
        return Subrubro::where('nombre', $nombre)->firstOrFail();
    }

    private function datos(Subrubro $s, mixed $clase): array
    {
        return ['nombre' => $s->nombre, 'permitido_para' => $s->permitido_para,
            'afecta_caja' => $s->afecta_caja, 'clasificacion_resultado' => $clase];
    }

    private function evidencia(string $caso, array $datos): void
    {
        if ($ruta = getenv('T16_EVIDENCIA')) {
            file_put_contents($ruta, json_encode(['caso' => $caso] + $datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND);
        }
    }

    private function movimientos(Subrubro $s, ?string $clase, int $monto): array
    {
        $tipo = TipoCaja::firstOrFail();
        $caja = CajaOperativa::firstOrCreate(['usuario_operativo_id' => $this->admin->id],
            ['apertura_at' => now(), 'estado' => 'ABIERTA', 'tipo_caja_efectivo_id' => $tipo->id]);
        return [
            CashflowMovimiento::create(['fecha' => today(), 'subrubro_id' => $s->id, 'tipo_caja_id' => $tipo->id,
                'monto' => $monto, 'usuario_admin_id' => $this->admin->id, 'reporte_clasificacion' => $clase]),
            MovimientoOperativo::create(['fecha' => today(), 'subrubro_id' => $s->id, 'tipo_caja_id' => $tipo->id,
                'monto' => $monto, 'usuario_id' => $this->admin->id, 'caja_operativa_id' => $caja->id,
                'estado' => 'ACTIVO', 'reporte_clasificacion' => $clase]),
        ];
    }

    public function test_catalogo_existente_completo_segun_decision_de_carlos(): void
    {
        $esperado = [
            'Cuotas' => ['Cuota Mensual'], 'Inscripciones' => ['Inscripción al club'],
            'Clases Particulares' => ['Clase particular'],
            'Indumentaria' => ['Patines', 'Indumentaria institucional', 'VG Indumentaria'],
            'Intereses' => ['Intereses Mercado Pago', 'Intereses Banco'], 'Torneos' => ['Inscripciones'],
            'Alquileres' => ['San Carlos', 'Centenera', 'Eventos'],
            'Gastos Operativos' => ['Limpieza', 'Librería', 'Insumos Varios'],
            'Mantenimiento y arreglos' => ['Reparaciones menores'], 'Servicios' => ['Luz', 'Internet'],
        ];
        foreach ($esperado as $rubro => $nombres) {
            $r = Rubro::firstOrCreate(['nombre' => $rubro], ['tipo' => 'INGRESO']);
            foreach ($nombres as $nombre) {
                $s = Subrubro::firstOrCreate(['nombre' => $nombre], ['rubro_id' => $r->id, 'permitido_para' => 'ADMIN', 'afecta_caja' => false]);
                DB::table('subrubros')->where('id', $s->id)->update(['clasificacion_resultado' => null]);
            }
        }
        $this->migrar();
        foreach ($esperado as $rubro => $nombres) {
            foreach ($nombres as $nombre) {
                $this->assertSame($rubro === 'Indumentaria' ? 'APORTE' : 'NEGOCIO', $this->sub($nombre)->clasificacion_resultado, $nombre);
            }
        }
        $this->assertSame('RETIRO', $this->sub('Retiro de dueños')->clasificacion_resultado);
        $this->assertSame('NEGOCIO', $this->sub('Pago al organizador')->clasificacion_resultado);
    }

    public function test_migracion_preserva_clasificacion_desconocidos_y_no_duplica(): void
    {
        $luz = $this->sub('Luz');
        $luz->update(['clasificacion_resultado' => 'RETIRO']);
        $gas = Subrubro::create(['nombre' => 'Gas verificación', 'rubro_id' => $luz->rubro_id, 'permitido_para' => 'ADMIN', 'afecta_caja' => false]);
        $movimientosGas = $this->movimientos($gas, null, 10);
        DB::table('subrubros')->whereIn('nombre', ['Retiro de dueños', 'Pago al organizador'])->delete();
        $this->migrar();
        $primero = DB::table('subrubros')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $this->migrar();
        $this->assertSame($primero, DB::table('subrubros')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all());
        $this->assertSame('RETIRO', $luz->fresh()->clasificacion_resultado);
        $this->assertNull($gas->fresh()->clasificacion_resultado);
        foreach ($movimientosGas as $m) $this->assertNull($m->fresh()->reporte_clasificacion);
        foreach (['Retiro de dueños', 'Pago al organizador'] as $nombre) {
            $s = $this->sub($nombre);
            $this->assertSame('EGRESO', $s->rubro->tipo);
            $this->assertSame('ADMIN', $s->permitido_para);
            $this->assertSame(1, Subrubro::where('nombre', $nombre)->count());
            $this->assertSame(1, Rubro::where('nombre', $s->rubro->nombre)->count());
        }
    }

    public function test_migracion_completa_ambas_tablas_y_conserva_filas_clasificadas(): void
    {
        $cuota = $this->sub('Cuota Mensual');
        DB::table('subrubros')->where('id', $cuota->id)->update(['clasificacion_resultado' => null]);
        $vacios = $this->movimientos($cuota, null, 1000);
        $previos = $this->movimientos($cuota, 'APORTE', 250);
        $originales = array_map(fn ($m) => $m->fresh()->getAttributes(), $previos);
        $antes = app(ReporteMensualService::class)->obtener(today()->format('Y-m'));
        $this->assertSame(0, $antes['ingresos']);
        $this->assertSame(2, $antes['sin_clasificar']);
        $this->migrar();
        foreach ($vacios as $m) $this->assertSame('NEGOCIO', $m->fresh()->reporte_clasificacion);
        foreach ($previos as $i => $m) $this->assertSame($originales[$i], $m->fresh()->getAttributes());
        $despues = app(ReporteMensualService::class)->obtener(today()->format('Y-m'));
        $this->assertSame(200000, $despues['ingresos']);
        $this->assertSame(0, $despues['sin_clasificar']);
        $this->assertSame(50000, $despues['aportes']);
    }

    public function test_post_y_put_rechazan_ausencia_incompatibles_y_arrays(): void
    {
        foreach (['Luz' => 'APORTE', 'Intereses Banco' => 'RETIRO'] as $nombre => $invalida) {
            $s = $this->sub($nombre);
            foreach ([null, $invalida, ['NEGOCIO'], 'OTRA'] as $clase) {
                $datos = $this->datos($s, $clase);
                $datos['nombre'] = 'Nuevo '.$nombre;
                $this->post(route('web.subrubros.store', $s->rubro_id), $datos)->assertSessionHasErrors('clasificacion_resultado');
                $this->assertDatabaseMissing('subrubros', ['nombre' => $datos['nombre']]);
                $original = $s->fresh()->getAttributes();
                $this->put(route('web.subrubros.update', [$s->rubro_id, $s->id]), $this->datos($s, $clase))->assertSessionHasErrors('clasificacion_resultado');
                $this->assertSame($original, $s->fresh()->getAttributes());
            }
        }
    }

    public function test_put_valido_completa_ambas_tablas_sin_reescribir_historia(): void
    {
        $luz = $this->sub('Luz');
        $luz->update(['clasificacion_resultado' => null]);
        $vacios = $this->movimientos($luz, null, -300);
        $previos = $this->movimientos($luz, 'RETIRO', -200);
        $originales = array_map(fn ($m) => $m->fresh()->getAttributes(), $previos);
        $this->put(route('web.subrubros.update', [$luz->rubro_id, $luz->id]), $this->datos($luz, 'NEGOCIO'))->assertSessionHasNoErrors()->assertSessionHas('success');
        foreach ($vacios as $m) $this->assertSame('NEGOCIO', $m->fresh()->reporte_clasificacion);
        foreach ($previos as $i => $m) $this->assertSame($originales[$i], $m->fresh()->getAttributes());
        $r = app(ReporteMensualService::class)->obtener(today()->format('Y-m'));
        $this->assertSame(60000, $r['egresos']);
        $this->assertSame(40000, $r['retiros']);
        $this->assertSame(0, $r['sin_clasificar']);
        $this->put(route('web.subrubros.update', [$luz->rubro_id, $luz->id]), $this->datos($luz, 'RETIRO'))->assertSessionHasNoErrors();
        foreach ($vacios as $m) $this->assertSame('NEGOCIO', $m->fresh()->reporte_clasificacion);
    }

    public function test_post_y_put_aceptan_las_dos_opciones_validas_por_tipo(): void
    {
        foreach (['Intereses Banco' => ['NEGOCIO', 'APORTE'], 'Luz' => ['NEGOCIO', 'RETIRO']] as $nombre => $clases) {
            $s = $this->sub($nombre);
            foreach ($clases as $clase) {
                $datos = $this->datos($s, $clase);
                $datos['nombre'] = 'Verificación '.$nombre.' '.$clase;
                $this->post(route('web.subrubros.store', $s->rubro_id), $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
                $nuevo = $this->sub($datos['nombre']);
                $this->assertSame($clase, $nuevo->clasificacion_resultado);
                $this->put(route('web.subrubros.update', [$nuevo->rubro_id, $nuevo->id]), $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
                $this->assertSame($clase, $nuevo->fresh()->clasificacion_resultado);
            }
        }
    }

    public function test_reservados_no_cambian_por_put_patch_padre_cruzado_ni_api(): void
    {
        $this->assertFalse(collect(app('router')->getRoutes())->contains(fn ($r) => str_starts_with($r->uri(), 'api/')));
        $op = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $sueldo = app(SubrubroSueldoService::class)->paraUsuarioOperativo($op);
        foreach ([$this->sub('Cuota Mensual'), $this->sub('Inscripción al club'), $sueldo] as $s) {
            $original = $s->fresh()->getAttributes();
            foreach (['NEGOCIO', 'APORTE', 'RETIRO', null] as $clase) {
                $datos = $this->datos($s, $clase) + ['es_reservado_sistema' => false, 'rubro_id' => $this->sub('Luz')->rubro_id];
                $this->put(route('web.subrubros.update', [$s->rubro_id, $s->id]), $datos)->assertSessionHas('error');
                $this->patch(route('web.subrubros.toggle-activo', [$s->rubro_id, $s->id]), $datos)->assertSessionHas('error');
                $this->assertContains($this->put('/api/admin/subrubros/'.$s->id, $datos)->status(), [404, 405]);
                $this->put(route('web.subrubros.update', [$this->sub('Luz')->rubro_id, $s->id]), $datos)->assertNotFound();
                $this->assertSame($original, $s->fresh()->getAttributes());
            }
            $this->get(route('web.subrubros.edit', [$s->rubro_id, $s->id]))->assertSessionHas('error');
            $this->delete('/rubros/'.$s->rubro_id.'/subrubros/'.$s->id)->assertStatus(405);
            $this->assertSame($original, $s->fresh()->getAttributes());
        }
    }

    public function test_altas_automaticas_de_profesor_y_operativo_clasificadas(): void
    {
        $datos = ['deporte_id' => Deporte::firstOrFail()->id, 'nombre' => 'Verifica', 'apellido' => 'T16',
            'dni' => '30009999', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'Ficticia', 'localidad' => 'Prueba',
            'telefono' => '1100000000', 'valor_hora' => 100, 'clasificacion_resultado' => 'RETIRO',
            'subrubro_id' => $this->sub('Cuota Mensual')->id];
        $this->post(route('web.profesores.store'), $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
        $prof = Profesor::where('dni', '30009999')->firstOrFail();
        $s = $prof->subrubro;
        $this->assertSame('NEGOCIO', $s->clasificacion_resultado);
        $this->assertTrue($s->es_reservado_sistema);
        $datos['clasificacion_resultado'] = 'APORTE';
        $this->put(route('web.profesores.update', $prof->id), $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('NEGOCIO', $s->fresh()->clasificacion_resultado);
        $usuario = ['name' => 'Verificación T16', 'email' => 't16-verificacion@wings.test', 'rol' => 'OPERATIVO',
            'password' => 'VerificacionT162026', 'password_confirmation' => 'VerificacionT162026',
            'clasificacion_resultado' => 'RETIRO', 'subrubro_id' => $this->sub('Cuota Mensual')->id];
        $this->post(route('web.usuarios.store'), $usuario)->assertSessionHasNoErrors()->assertSessionHas('success');
        $op = User::where('email', $usuario['email'])->firstOrFail();
        $s = $op->subrubro;
        $this->assertSame('NEGOCIO', $s->clasificacion_resultado);
        $this->assertTrue($s->es_reservado_sistema);
        $usuario['clasificacion_resultado'] = 'APORTE';
        $this->put(route('web.usuarios.update', $op->id), $usuario)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('NEGOCIO', $s->fresh()->clasificacion_resultado);
    }

    public function test_observacion_creador_legacy_de_demo_deja_sueldos_sin_clasificar(): void
    {
        $seeder = new \Database\Seeders\DemoSeeder();
        $metodo = new \ReflectionMethod($seeder, 'fase2Profesores');
        $metodo->invoke($seeder, ['patin' => Deporte::where('nombre', 'Patín')->firstOrFail(),
            'futbol' => Deporte::where('nombre', 'Fútbol')->firstOrFail()]);
        $filas = DB::table('subrubros')->where('nombre', 'like', 'Sueldo - %')->get(['nombre', 'clasificacion_resultado']);
        $this->assertCount(6, $filas);
        foreach ($filas as $fila) $this->assertNull($fila->clasificacion_resultado);
        $this->evidencia('observacion_demo_solo_pruebas', ['filas' => $filas->all(),
            'alcance' => 'Fase de creación aislada; no se ejecutó DemoSeeder completo']);
    }

    public function test_cambiar_tipo_del_padre_no_debe_convertir_aporte_en_egreso(): void
    {
        $s = $this->sub('Patines');
        $r = $s->rubro;
        $this->get(route('web.rubros.edit', $r->id))->assertOk()->assertSee('EGRESO');
        $respuesta = $this->put(route('web.rubros.update', $r->id), ['nombre' => $r->nombre, 'tipo' => 'EGRESO']);
        $nuevo = CashflowMovimiento::create(['fecha' => today(), 'subrubro_id' => $s->id,
            'tipo_caja_id' => TipoCaja::firstOrFail()->id, 'monto' => -100, 'usuario_admin_id' => $this->admin->id]);
        $this->evidencia('aporte_convertido_en_egreso', ['ruta' => route('web.rubros.update', $r->id),
            'status' => $respuesta->status(), 'tipo_guardado' => $r->fresh()->tipo,
            'subrubro' => $s->nombre, 'clasificacion' => $s->fresh()->clasificacion_resultado,
            'movimiento_tipo' => $nuevo->reporte_tipo, 'movimiento_clasificacion' => $nuevo->reporte_clasificacion]);
        $this->assertSame('INGRESO', $r->fresh()->tipo);
    }

    public function test_cambiar_tipo_del_padre_no_debe_convertir_retiro_en_ingreso(): void
    {
        $s = $this->sub('Retiro de dueños');
        $r = $s->rubro;
        $this->get(route('web.rubros.edit', $r->id))->assertOk()->assertSee('INGRESO');
        $respuesta = $this->put(route('web.rubros.update', $r->id), ['nombre' => $r->nombre, 'tipo' => 'INGRESO']);
        $nuevo = CashflowMovimiento::create(['fecha' => today(), 'subrubro_id' => $s->id,
            'tipo_caja_id' => TipoCaja::firstOrFail()->id, 'monto' => 100, 'usuario_admin_id' => $this->admin->id]);
        $this->evidencia('retiro_convertido_en_ingreso', ['ruta' => route('web.rubros.update', $r->id),
            'status' => $respuesta->status(), 'tipo_guardado' => $r->fresh()->tipo,
            'subrubro' => $s->nombre, 'clasificacion' => $s->fresh()->clasificacion_resultado,
            'movimiento_tipo' => $nuevo->reporte_tipo, 'movimiento_clasificacion' => $nuevo->reporte_clasificacion]);
        $this->assertSame('EGRESO', $r->fresh()->tipo);
    }

    public function test_migracion_clasifica_nuevos_si_el_nombre_ya_existia_sin_clasificar(): void
    {
        DB::table('subrubros')->whereIn('nombre', ['Retiro de dueños', 'Pago al organizador'])->update(['clasificacion_resultado' => null]);
        $this->migrar();
        $this->evidencia('nombres_nuevos_ya_existentes', ['filas' => DB::table('subrubros')->whereIn('nombre',
            ['Retiro de dueños', 'Pago al organizador'])->get(['nombre', 'clasificacion_resultado'])->all()]);
        $this->assertSame('RETIRO', $this->sub('Retiro de dueños')->clasificacion_resultado);
        $this->assertSame('NEGOCIO', $this->sub('Pago al organizador')->clasificacion_resultado);
    }
}
