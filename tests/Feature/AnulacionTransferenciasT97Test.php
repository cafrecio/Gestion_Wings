<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, Pago, Subrubro, User};
use App\Services\{AnulacionTransferenciaCashflowService, CashflowService, ReciboService, ReporteMensualService, ReporteSueldosService};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Event};
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TransferenciasT97;
use Tests\TestCase;

class AnulacionTransferenciasT97Test extends TestCase
{
    use RefreshDatabase, TransferenciasT97 {
        TransferenciasT97::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    public static function patas(): array { return ['salida' => [0], 'entrada' => [1]]; }

    #[DataProvider('patas')]
    public function test_cualquier_pata_anula_el_par_y_conserva_motivo_autor_y_fecha(int $indice): void
    {
        $patas = $this->transferir('2026-10-20', '300000.75');
        $this->assertCuentasT97(29999925, 40000075);
        $id = $patas[$indice]->id;
        $this->get(route('web.cashflow.transferencia.anular', $id))->assertOk()
            ->assertSee('Transferencia #'.$patas[0]->referencia_id)->assertSee('Motivo')
            ->assertSee('Efectivo')->assertSee('Banco Nación')->assertSee('$300,000.75');
        $this->post($this->rutaT97($id), ['motivo' => '  Cuenta de destino equivocada  ', 'monto' => '1.00'])
            ->assertSessionHasNoErrors()->assertRedirect(route('web.cashflow.index', ['periodo' => 'mes', 'fecha' => '2026-10-20']));
        $this->assertCuentasT97();
        $cabecera = DB::table('cashflow_transferencias')->find($patas[0]->referencia_id);
        $this->assertSame('Cuenta de destino equivocada', $cabecera->motivo_anulacion);
        $this->assertSame($this->admin->id, $cabecera->anulo_admin_id);
        $this->assertNotNull($cabecera->anulada_at);
        $this->assertSame('2026-11-15 12:00:00', $cabecera->anulada_at);
        foreach ($patas as $pata) {
            $contra = CashflowMovimiento::where('contra_origen_id', $pata->id)->sole();
            $this->assertSame(CashflowMovimiento::REF_ANULACION_TRANSFERENCIA, $contra->referencia_tipo);
            foreach (['fecha', 'subrubro_id', 'tipo_caja_id', 'reporte_tipo', 'reporte_clasificacion', 'reporte_deporte_id', 'referencia_id'] as $campo) {
                $this->assertEquals($pata->$campo, $contra->$campo, $campo);
            }
            $this->assertSame(0, (int) str_replace('.', '', $pata->monto) + (int) str_replace('.', '', $contra->monto));
            $this->assertNotNull($pata->fresh()->anulado_at);
            $this->assertSame(1, $pata->fresh()->version_correccion);
        }
    }

    public static function motivosInvalidos(): array
    {
        return ['vacio' => [''], 'espacios' => [" \t\n "], 'nulo' => [null],
            'array' => [['otro']], 'numero' => [123], 'largo' => [str_repeat('a', 501)]];
    }

    #[DataProvider('motivosInvalidos')]
    public function test_motivo_invalido_rechazado_sin_escribir(mixed $motivo): void
    {
        $patas = $this->transferir(); $foto = $this->fotoT97();
        $this->postJson($this->rutaT97($patas[0]->id), ['motivo' => $motivo])->assertUnprocessable()->assertJsonValidationErrors('motivo');
        $this->assertSame($foto, $this->fotoT97());
        $this->assertCuentasT97(30000000, 40000000);
    }

    public function test_otro_admin_puede_anular_incluso_si_se_desactivo_una_cuenta(): void
    {
        $patas = $this->transferir();
        $this->destino->update(['activo' => false]);
        $otro = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($otro)->post($this->rutaT97($patas[1]->id), ['motivo' => str_repeat('m', 500)])
            ->assertSessionHasNoErrors();
        $this->assertSame($otro->id, DB::table('cashflow_transferencias')->value('anulo_admin_id'));
        $this->assertSame($this->admin->id, $patas[0]->fresh()->usuario_admin_id);
        $this->assertSame(str_repeat('m', 500), DB::table('cashflow_transferencias')->value('motivo_anulacion'));
        $this->assertCuentasT97();
    }

    public function test_repetir_anulacion_por_la_otra_pata_no_duplica_ni_cambia_auditoria(): void
    {
        $patas = $this->transferir();
        $this->post($this->rutaT97($patas[0]->id), ['motivo' => 'Primera decisión'])->assertSessionHasNoErrors();
        $foto = $this->fotoT97();
        $this->postJson($this->rutaT97($patas[1]->id), ['motivo' => 'Otra decisión'])
            ->assertUnprocessable()->assertJsonValidationErrors('motivo')
            ->assertJsonPath('errors.motivo.0', 'La transferencia ya está anulada.');
        $this->get(route('web.cashflow.transferencia.anular', $patas[1]->id))->assertRedirect();
        $this->assertSame($foto, $this->fotoT97());
        $this->assertDatabaseCount('cashflow_movimientos', 4);
        $this->assertCuentasT97();
    }

    public function test_originales_y_contras_no_admiten_correccion_individual_ni_reanular_contra(): void
    {
        $patas = $this->transferir();
        $this->post($this->rutaT97($patas[0]->id), ['motivo' => 'Destino equivocado'])->assertSessionHasNoErrors();
        $foto = $this->fotoT97();
        foreach (CashflowMovimiento::all() as $mov) {
            $this->assertFalse($mov->esDirecto()); $this->assertFalse($mov->puedeCorregirse());
            $this->get('/cashflow/movimientos/'.$mov->id.'/editar')->assertForbidden();
            $this->get('/cashflow/movimientos/'.$mov->id.'/eliminar')->assertForbidden();
            $this->putJson('/cashflow/movimientos/'.$mov->id, ['clave' => (string) Str::uuid(), 'version' => $mov->version_correccion,
                'monto' => '10.00', 'fecha' => '2026-11-15', 'tipo_caja_id' => $this->salida->id,
                'subrubro_id' => $mov->subrubro_id, 'observaciones' => 'Cambio indebido'])->assertForbidden();
            $this->deleteJson('/cashflow/movimientos/'.$mov->id, ['clave' => (string) Str::uuid(), 'version' => $mov->version_correccion])->assertForbidden();
            if ($mov->contra_origen_id) {
                $this->postJson($this->rutaT97($mov->id), ['motivo' => 'Anular el inverso'])->assertForbidden();
                $this->get(route('web.cashflow.transferencia.anular', $mov->id))->assertForbidden();
            }
        }
        $this->get('/cashflow?periodo=mes&fecha=2026-11-15')->assertOk()
            ->assertSee('Anulación de transferencia #'.$patas[0]->referencia_id)
            ->assertSee('Destino equivocado')->assertViewHas('movimientos', function ($movimientos) {
                return $movimientos->every(fn ($m) => array_column($m->accionesCorreccion, 'nombre') === ['Ver']);
            });
        $this->assertSame($foto, $this->fotoT97()); $this->assertCuentasT97();
    }

    public function test_patas_vigentes_siguen_fuera_del_circuito_de_directos_y_traspasos_sueltos(): void
    {
        $patas = $this->transferir(); $foto = $this->fotoT97();
        foreach ($patas as $pata) {
            $this->assertFalse($pata->esDirecto());
            $this->deleteJson('/cashflow/movimientos/'.$pata->id, ['clave' => (string) Str::uuid(), 'version' => 0])->assertForbidden();
            $this->postJson('/cashflow/movimiento', ['subrubro_id' => $pata->subrubro_id,
                'tipo_caja_id' => $pata->tipo_caja_id, 'monto' => '10.00', 'fecha' => '2026-11-15',
                'observaciones' => 'Pata suelta'])->assertRedirect()->assertSessionHas('error');
        }
        $this->assertSame($foto, $this->fotoT97()); $this->assertCuentasT97(30000000, 40000000);
    }

    public function test_permisos_get_post_y_servicio_sin_escrituras(): void
    {
        $patas = $this->transferir(); $foto = $this->fotoT97();
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $this->actingAs($usuario)->get(route('web.cashflow.transferencia.anular', $patas[0]->id))->assertForbidden();
            $this->postJson($this->rutaT97($patas[0]->id), ['motivo' => 'No autorizado'])->assertForbidden();
            $this->assertServicioDenegado($patas[0]->id, $usuario->id);
        }
        $inactivo = User::factory()->create(['rol' => 'ADMIN', 'activo' => false]);
        $this->assertServicioDenegado($patas[0]->id, $inactivo->id);
        $this->actingAs($inactivo)->post($this->rutaT97($patas[0]->id), ['motivo' => 'No autorizado'])->assertRedirect('/login');
        auth()->logout();
        $this->get(route('web.cashflow.transferencia.anular', $patas[0]->id))->assertRedirect('/login');
        $this->post($this->rutaT97($patas[0]->id), ['motivo' => 'No autorizado'])->assertRedirect('/login');
        $this->assertSame($foto, $this->fotoT97()); $this->assertCuentasT97(30000000, 40000000);
    }

    private function assertServicioDenegado(int $id, int $usuario): void
    {
        try { app(AnulacionTransferenciaCashflowService::class)->anular($id, $usuario, ['motivo' => 'No autorizado']); $this->fail('Servicio sin control de rol/actividad'); }
        catch (AuthorizationException) { $this->addToAssertionCount(1); }
    }

    public function test_un_directo_no_puede_entrar_en_el_circuito_de_transferencias(): void
    {
        $directo = app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $this->admin->id,
            'subrubro_id' => Subrubro::where('nombre', 'Intereses Banco')->value('id'),
            'tipo_caja_id' => $this->destino->id, 'monto' => '1.00', 'fecha' => '2026-11-15']);
        $foto = $this->fotoT97();
        $this->get(route('web.cashflow.transferencia.anular', $directo->id))->assertForbidden();
        $this->postJson($this->rutaT97($directo->id), ['motivo' => 'No es transferencia'])->assertForbidden();
        $this->postJson($this->rutaT97(999999), ['motivo' => 'No existe'])->assertNotFound();
        $this->assertSame($foto, $this->fotoT97()); $this->assertCuentasT97(60000000, 10000100);
    }

    public function test_motivo_se_conserva_completo_y_se_muestra_escapado(): void
    {
        $patas = $this->transferir(); $motivo = '<b>Cuenta equivocada & revisión</b>';
        $this->post($this->rutaT97($patas[0]->id), ['motivo' => $motivo])->assertSessionHasNoErrors();
        $this->get('/cashflow?periodo=mes&fecha=2026-11-15')->assertOk()->assertSee($motivo)->assertDontSee($motivo, false);
        $this->assertSame($motivo, DB::table('cashflow_transferencias')->value('motivo_anulacion'));
        $this->assertCuentasT97();
    }

    public static function inconsistencias(): array
    {
        return ['falta_pata' => ['falta'], 'tercera_pata' => ['extra'], 'sin_cabecera' => ['cabecera'], 'importe' => ['monto'], 'fecha' => ['fecha'],
            'cuenta' => ['cuenta'], 'origen_operativo' => ['operativo'], 'anulada' => ['anulada']];
    }

    #[DataProvider('inconsistencias')]
    public function test_par_inconsistente_se_rechaza_sin_reparar_ni_escribir(string $caso): void
    {
        $patas = $this->transferir();
        match ($caso) {
            'falta' => DB::table('cashflow_movimientos')->where('id', $patas[1]->id)->delete(),
            'extra' => CashflowMovimiento::create(['fecha' => '2026-11-15', 'monto' => '-1.00',
                'subrubro_id' => $patas[0]->subrubro_id, 'usuario_admin_id' => $this->admin->id,
                'tipo_caja_id' => DB::table('tipos_caja')->whereNotIn('id', [$this->salida->id, $this->destino->id])->value('id'),
                'referencia_tipo' => CashflowMovimiento::REF_TRANSFERENCIA, 'referencia_id' => $patas[0]->referencia_id]),
            'cabecera' => DB::table('cashflow_transferencias')->where('id', $patas[0]->referencia_id)->delete(),
            'monto' => DB::table('cashflow_movimientos')->where('id', $patas[1]->id)->update(['monto' => '299999.99']),
            'fecha' => DB::table('cashflow_movimientos')->where('id', $patas[1]->id)->update(['fecha' => '2026-11-14']),
            'cuenta' => DB::table('cashflow_movimientos')->where('id', $patas[1]->id)->update(['tipo_caja_id' => $this->salida->id]),
            'operativo' => DB::table('cashflow_movimientos')->where('id', $patas[1]->id)->update(['referencia_tipo' => CashflowMovimiento::REF_CAJA]),
            'anulada' => DB::table('cashflow_movimientos')->where('id', $patas[1]->id)->update(['anulado_at' => now()]),
        };
        $foto = $this->fotoT97();
        $saldos = app(ReporteMensualService::class)->saldosPorCaja('2026-11-15');
        $this->postJson($this->rutaT97($patas[0]->id), ['motivo' => 'Par incompleto'])->assertUnprocessable()->assertJsonValidationErrors('motivo');
        $this->assertSame($foto, $this->fotoT97());
        $this->assertSame($saldos, app(ReporteMensualService::class)->saldosPorCaja('2026-11-15'));
    }

    public function test_fallo_en_segundo_inverso_revierte_primer_inverso_marcas_y_cabecera(): void
    {
        $patas = $this->transferir(); $foto = $this->fotoT97();
        $evento = 'eloquent.creating: '.CashflowMovimiento::class;
        Event::listen($evento, function ($m) {
            if ($m->referencia_tipo === CashflowMovimiento::REF_ANULACION_TRANSFERENCIA && $m->tipo_caja_id === $this->destino->id) {
                throw new \RuntimeException('Fallo simulado del segundo inverso');
            }
        });
        try {
            app(AnulacionTransferenciaCashflowService::class)->anular($patas[0]->id, $this->admin->id, ['motivo' => 'Fallo controlado']);
            $this->fail('No llegó a la segunda escritura');
        } catch (\RuntimeException $e) { $this->assertSame('Fallo simulado del segundo inverso', $e->getMessage()); }
        finally { Event::forget($evento); }
        $this->assertSame($foto, $this->fotoT97()); $this->assertCuentasT97(30000000, 40000000);
    }

    public function test_cashflow_historial_reportes_inicio_y_filtros_restauran_mes_original(): void
    {
        foreach ([['Intereses Banco', $this->destino->id, '12000.50', '2026-10-10'],
            ['Luz', $this->salida->id, '5000.25', '2026-11-03']] as [$concepto, $cuentaBase, $importe, $fechaBase]) {
            app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $this->admin->id,
                'subrubro_id' => Subrubro::where('nombre', $concepto)->value('id'),
                'tipo_caja_id' => $cuentaBase, 'monto' => $importe, 'fecha' => $fechaBase]);
        }
        $antes = [];
        foreach (['2026-10', '2026-11'] as $mes) $antes[$mes] = app(ReporteMensualService::class)->obtener($mes);
        $patas = $this->transferir('2026-10-20');
        $this->get('/cashflow?periodo=mes&fecha=2026-10-20')->assertOk()
            ->assertViewHas('totalIngresos', fn ($n) => (int) round((float) $n * 100) === 1200050)
            ->assertViewHas('totalEgresos', fn ($n) => (float) $n === 0.0)
            ->assertViewHas('movimientos', fn ($m) => $m->where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)->count() === 2
                && $m->where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)
                    ->every(fn ($f) => array_column($f->accionesCorreccion, 'nombre') === ['Ver', 'Anular']));
        $this->post($this->rutaT97($patas[1]->id), ['motivo' => 'Revisar octubre'])->assertSessionHasNoErrors();
        foreach (['2026-10', '2026-11'] as $mes) {
            $despues = app(ReporteMensualService::class)->obtener($mes);
            foreach (['ingresos', 'egresos', 'resultado', 'resultado_global', 'aportes', 'retiros', 'disponible', 'cajas', 'deuda', 'por_pagar'] as $campo) {
                $this->assertSame($antes[$mes][$campo], $despues[$campo], $mes.' '.$campo);
            }
            $this->get('/reportes?mes='.$mes)->assertOk()->assertViewHas('reporte', fn ($r) => $r['cajas'] === $antes[$mes]['cajas'] && $r['resultado'] === $antes[$mes]['resultado']);
            foreach ([null, $this->salida->id, $this->destino->id] as $cuenta) {
                $ingreso = $mes === '2026-10' && ($cuenta === null || $cuenta === $this->destino->id) ? 1200050 : 0;
                $egreso = $mes === '2026-11' && ($cuenta === null || $cuenta === $this->salida->id) ? 500025 : 0;
                $cantidad = $mes === '2026-10' ? ($cuenta === null ? 5 : ($cuenta === $this->destino->id ? 3 : 2))
                    : ($cuenta === null || $cuenta === $this->salida->id ? 1 : 0);
                $url = '/cashflow?'.http_build_query(['periodo' => 'mes', 'fecha' => $mes.'-20', 'tipo_caja_id' => $cuenta]);
                $this->get($url)->assertOk()->assertViewHas('totalIngresos', fn ($n) => (int) round((float) $n * 100) === $ingreso)
                    ->assertViewHas('totalEgresos', fn ($n) => (int) round((float) $n * 100) === $egreso)
                    ->assertViewHas('movimientos', fn ($m) => $m->count() === $cantidad);
            }
            $this->get('/caja/historial?desde='.$mes.'-01&hasta='.$mes.'-28')->assertOk()
                ->assertViewHas('totalIngresos', fn ($n) => (int) round((float) $n * 100) === ($mes === '2026-10' ? 1200050 : 0))
                ->assertViewHas('totalEgresos', fn ($n) => (int) round((float) $n * 100) === ($mes === '2026-11' ? 500025 : 0));
        }
        $this->get('/admin/dashboard')->assertOk()->assertViewHas('reporte', fn ($r) => $r['cajas'] === $antes['2026-11']['cajas']
            && $r['disponible'] === 70700025 && $r['resultado'] === -500025);
        $this->assertCuentasT97(59499975, 11200050);
    }

    public function test_referencias_no_se_confunden_con_pago_ni_sueldos(): void
    {
        $patas = $this->transferir();
        $antes = app(ReporteSueldosService::class)->obtener('2026-11')['pagos_operativas'];
        $this->post($this->rutaT97($patas[0]->id), ['motivo' => 'Referencia propia'])->assertSessionHasNoErrors();
        $pago = new Pago; $pago->id = $patas[0]->referencia_id; // Colisión de ID deliberada; no es un cobro.
        $metodo = new \ReflectionMethod(ReciboService::class, 'obtenerTipoCajaPago');
        $this->assertSame(['nombre' => 'N/D', 'origen' => 'N/D'], $metodo->invoke(app(ReciboService::class), $pago));
        $this->assertSame($antes, app(ReporteSueldosService::class)->obtener('2026-11')['pagos_operativas']);
        $this->assertCuentasT97();
    }

    public function test_inversos_conservan_fotos_aunque_cambie_el_catalogo(): void
    {
        $patas = $this->transferir();
        $deporte = DB::table('deportes')->value('id');
        foreach ($patas as $pata) {
            $pata->reporte_deporte_id = $deporte; $pata->save();
            DB::table('subrubros')->where('id', $pata->subrubro_id)->update(['clasificacion_resultado' => 'NEGOCIO']);
            DB::table('rubros')->where('id', $pata->subrubro->rubro_id)->update([
                'tipo' => $pata->reporte_tipo === 'INGRESO' ? 'EGRESO' : 'INGRESO']);
        }
        $this->post($this->rutaT97($patas[0]->id), ['motivo' => 'Respetar foto original'])->assertSessionHasNoErrors();
        foreach ($patas as $pata) {
            $contra = CashflowMovimiento::where('contra_origen_id', $pata->id)->sole();
            foreach (['reporte_tipo', 'reporte_clasificacion', 'reporte_deporte_id'] as $campo) $this->assertSame($pata->$campo, $contra->$campo);
        }
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-11')['resultado']);
        $this->assertCuentasT97();
    }

    public function test_motivo_cero_se_muestra_en_el_listado(): void
    {
        $patas = $this->transferir();
        $this->post($this->rutaT97($patas[0]->id), ['motivo' => '0'])->assertSessionHasNoErrors();
        $this->get('/cashflow?periodo=mes&fecha=2026-11-15')->assertOk()->assertSee('Motivo: 0');
        $this->assertCuentasT97();
    }

    public function test_motivo_de_espacios_unicode_tambien_se_rechaza_en_el_servicio(): void
    {
        $patas = $this->transferir(); $foto = $this->fotoT97();
        try {
            app(AnulacionTransferenciaCashflowService::class)->anular($patas[0]->id, $this->admin->id, ['motivo' => "\u{00a0}\u{2003}"]);
            $this->fail('Se aceptaron solo espacios Unicode.');
        } catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('motivo', $e->errors()); }
        $this->assertSame($foto, $this->fotoT97()); $this->assertCuentasT97(30000000, 40000000);
    }

    public function test_reenvio_de_creacion_no_revive_la_transferencia_anulada(): void
    {
        $patas = $this->transferir();
        $this->post($this->rutaT97($patas[0]->id), ['motivo' => 'Anulación confirmada'])->assertSessionHasNoErrors();
        $cabecera = DB::table('cashflow_transferencias')->find($patas[0]->referencia_id); $foto = $this->fotoT97();
        $id = app(\App\Services\TransferenciaCashflowService::class)->registrar($this->admin->id, (array) $cabecera);
        $this->assertSame($patas[0]->referencia_id, $id);
        $this->assertSame($foto, $this->fotoT97()); $this->assertCuentasT97();
    }
}
