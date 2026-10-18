<?php

namespace Tests\CapturasT75Observaciones;

use App\Models\{Alumno, AlumnoRevisionCobranza, Asistencia, CajaOperativa, CashflowMovimiento, Clase, DeudaCuota, Grupo, Liquidacion, LiquidacionDetalle, MovimientoOperativo, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\CajaService;
use Carbon\Carbon;
use Database\Seeders\PrimeraCargaCompletaSeeder;
use Illuminate\Support\Facades\{Auth, DB, Route, URL, View};
use Tests\TestCase;

/** Instrumentación de capturas; no pertenece a la suite ni modifica lógica del producto. */
class CapturaT75Test extends TestCase
{
    public function test_generar_respuestas_reales_para_el_relevamiento_visual(): void
    {
        $this->assertSame('wings_testing_codex', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', 'America/Argentina/Buenos_Aires'));
        URL::forceRootUrl('http://127.0.0.1:8735');
        $fase = getenv('T75_ETAPA') ?: 'antes';
        $this->assertContains($fase, ['antes', 'despues']);
        $fixturePath = storage_path('app/t75-fixture.json');
        if (getenv('T75_PREPARAR') === '1') {
            $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
            $this->seed(PrimeraCargaCompletaSeeder::class);
            $fixture = $this->prepararDatos();
            file_put_contents($fixturePath, json_encode($fixture, JSON_THROW_ON_ERROR));
        }
        $fixture = json_decode(file_get_contents($fixturePath), true, flags: JSON_THROW_ON_ERROR);
        $complementos = getenv('T75_COMPLEMENTOS') === '1';
        $casos = $complementos ? [
            ['slug' => 'admin-liquidaciones-filtradas', 'rol' => 'ADMIN', 'ruta' => '/liquidaciones?estado=abierta'],
            ['slug' => 'admin-clase-serie', 'rol' => 'ADMIN', 'ruta' => '/clases/'.$fixture['clases']['futura'], 'serie_clase_id' => $fixture['clases']['futura']],
        ] : $this->casos($fixture);
        if (getenv('T75_SOLO_COMISION') === '1') {
            $casos = [['slug' => 'admin-liquidacion-comision', 'rol' => 'ADMIN', 'ruta' => '/liquidaciones/'.$fixture['liquidaciones']['abierta'], 'comision' => true]];
        }
        $renderizadas = [];
        View::composer('*', function ($vista) use (&$renderizadas) {
            $renderizadas[$vista->name()] = str_replace('\\', '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $vista->getPath()));
        });
        $resultados = [];
        foreach ($casos as $caso) {
            $renderizadas = [];
            DB::beginTransaction();
            try {
                if (!empty($caso['comision'])) {
                    DB::table('liquidaciones')->where('id', $fixture['liquidaciones']['abierta'])->update(['tipo' => 'COMISION', 'porcentaje_comision_aplicado' => 10]);
                    DB::table('liquidacion_detalles')->where('liquidacion_id', $fixture['liquidaciones']['abierta'])->update(['tipo_referencia' => 'alumno', 'referencia_id' => $fixture['alumno'], 'descripcion' => 'Pago $120.000,00']);
                }
                $this->flushSession();
                Auth::forgetGuards();
                if (isset($caso['serie_clase_id'])) DB::table('clases')->where('id', $caso['serie_clase_id'])->update(['serie_id' => '00000000-0000-4000-8000-000000000075']);
                if ($caso['rol'] !== 'ANONIMO') $this->actingAs(User::findOrFail($caso['usuario'] ?? $fixture['usuarios'][$caso['rol']]));
                if (($caso['estado_turno'] ?? null) === 'cerrado') DB::table('cajas_operativas')->where('estado', 'ABIERTA')->update(['estado' => 'CERRADA', 'cierre_at' => '2026-10-18 10:00:00', 'efectivo_contado' => 88000.50, 'cambio_retenido' => 10000, 'efectivo_retirado' => 78000.50]);
                if (($caso['estado_turno'] ?? null) === 'antiguo') DB::table('cajas_operativas')->where('estado', 'ABIERTA')->update(['apertura_at' => '2026-10-17 08:00:00']);
                if (isset($caso['estado_carga'])) DB::table('primera_carga')->where('id', 1)->update(['estado' => $caso['estado_carga']]);
                if (isset($caso['vista_directa'])) {
                    $html = view($caso['vista_directa'])->render();
                    $status = 200;
                } else {
                    $respuesta = $this->get($caso['ruta']);
                    $status = $respuesta->getStatusCode();
                    $html = $respuesta->getContent();
                }
                $archivo = storage_path("app/html/$fase/{$caso['slug']}.html");
                if (!is_dir(dirname($archivo))) mkdir(dirname($archivo), 0775, true);
                file_put_contents($archivo, $html);
                $resultados[] = $caso + ['status' => $status, 'vistas' => array_values($renderizadas), 'nombres_vistas' => array_keys($renderizadas), 'bytes' => strlen($html)];
            } finally {
                while (DB::transactionLevel()) DB::rollBack();
            }
        }
        $salida = base_path('docs/06-pruebas/PRU-04/evidencia-t75-observaciones');
        $archivoManifiesto = getenv('T75_SOLO_COMISION') === '1' ? 'comision' : ($complementos ? 'complementos' : $fase);
        file_put_contents("$salida/respuestas-$archivoManifiesto.json", json_encode(['fase' => $fase, 'base' => DB::connection()->getDatabaseName(), 'casos' => $resultados], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        Carbon::setTestNow();
        $this->assertNotEmpty($resultados);
        $errores = array_values(array_filter($resultados, fn ($r) => $r['status'] !== ($r['status_esperado'] ?? 200)));
        $this->assertSame([], array_map(fn ($r) => [$r['slug'], $r['ruta'] ?? null, $r['status']], $errores));
    }

    private function prepararDatos(): array
    {
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
        $admin = User::where('rol', 'ADMIN')->firstOrFail();
        $operativa = User::where('rol', 'OPERATIVO')->firstOrFail();
        $docente = User::where('rol', 'PROFESOR')->firstOrFail();
        $admin->update(['name' => 'Administración T75']);
        $operativa->update(['name' => 'Mostrador T75']);
        $docente->update(['name' => 'Docente T75']);
        $auxiliar = User::where('rol', 'OPERATIVO')->whereKeyNot($operativa->id)->firstOrFail();
        $profesor = Profesor::findOrFail($docente->profesor_id);
        $grupo = Grupo::where('deporte_id', $profesor->deporte_id)->firstOrFail();
        $alumno = Alumno::where('grupo_id', $grupo->id)->firstOrFail();
        $medio = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        app(CajaService::class)->configurarMostrador($medio->id, $admin->id);
        $rubro = Rubro::create(['nombre' => 'Ingresos de prueba T75', 'tipo' => 'INGRESO']);
        $subrubro = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Indumentaria T75', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true, 'clasificacion_resultado' => 'NEGOCIO']);
        $cajas = []; $movimientos = [];
        foreach (['CERRADA', 'RECHAZADA', 'VALIDADA', 'ABIERTA'] as $estado) {
            $caja = CajaOperativa::create([
                'usuario_operativo_id' => $operativa->id, 'estado' => $estado, 'apertura_at' => '2026-10-18 08:00:00',
                'cierre_at' => $estado === 'ABIERTA' ? null : '2026-10-18 10:00:00',
                'tipo_caja_efectivo_id' => $medio->id, 'efectivo_inicial' => 10000,
                'efectivo_esperado' => 58000.50, 'efectivo_contado' => $estado === 'ABIERTA' ? null : 58000.50,
                'cambio_retenido' => $estado === 'ABIERTA' ? null : 10000, 'efectivo_retirado' => $estado === 'ABIERTA' ? null : 48000.50,
                'diferencia_efectivo' => $estado === 'ABIERTA' ? null : 0,
                'motivo_rechazo' => $estado === 'RECHAZADA' ? 'Revisar el comprobante del movimiento.' : null,
            ]);
            $mov = MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'fecha' => '2026-10-18',
                'tipo_caja_id' => $medio->id, 'subrubro_id' => $subrubro->id, 'monto' => 48000.50,
                'usuario_id' => $operativa->id, 'observaciones' => 'Equipo de entrenamiento de prueba', 'estado' => 'ACTIVO']);
            $cajas[$estado] = $caja->id; $movimientos[$estado] = $mov->id;
        }
        $pago = \App\Models\Pago::create(['alumno_id' => $alumno->id, 'mes' => 10, 'anio' => 2026,
            'monto_base' => 30000, 'porcentaje_aplicado' => 100, 'monto_final' => 30000,
            'fecha_pago' => '2026-10-18', 'estado' => 'COMPLETADO']);
        $cobro = MovimientoOperativo::create(['caja_operativa_id' => $cajas['ABIERTA'], 'fecha' => '2026-10-18',
            'tipo_caja_id' => $medio->id, 'subrubro_id' => Subrubro::where('nombre', 'Cuota Mensual')->value('id'),
            'monto' => 30000, 'usuario_id' => $operativa->id, 'alumno_id' => $alumno->id,
            'pago_id' => $pago->id, 'estado' => 'ACTIVO']);
        $clases = [];
        foreach (['hoy' => '2026-10-18', 'futura' => '2026-10-19', 'pasada' => '2026-09-18'] as $nombre => $fecha) {
            $clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => $fecha, 'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'validada_para_liquidacion' => true, 'cancelada' => false]);
            $clase->profesores()->attach($profesor->id);
            Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => true]);
            $clases[$nombre] = $clase->id;
        }
        $liquidaciones = [];
        foreach (['abierta' => [9, 'ABIERTA', 'PENDIENTE'], 'mes_actual' => [10, 'ABIERTA', 'PENDIENTE'], 'cerrada' => [8, 'CERRADA', 'PENDIENTE'], 'pagada' => [7, 'CERRADA', 'PAGADA']] as $nombre => [$mes, $estado, $pago]) {
            $liq = Liquidacion::create(['profesor_id' => $profesor->id, 'mes' => $mes, 'anio' => 2026, 'tipo' => 'HORA', 'valor_hora_aplicado' => 12000, 'total_calculado' => 12000, 'estado' => 'ABIERTA', 'estado_pago' => 'PENDIENTE']);
            LiquidacionDetalle::create(['liquidacion_id' => $liq->id, 'tipo_referencia' => 'clase', 'referencia_id' => $clases['pasada'], 'monto' => 12000, 'minutos' => 60, 'descripcion' => 'Clase de prueba T75']);
            if ($estado !== 'ABIERTA') $liq->update(['estado' => $estado, 'estado_pago' => $pago]);
            $liquidaciones[$nombre] = $liq->id;
        }
        $cashflow = CashflowMovimiento::create(['fecha' => '2026-10-18', 'subrubro_id' => $subrubro->id, 'tipo_caja_id' => $medio->id, 'monto' => 48000.50, 'usuario_admin_id' => $admin->id, 'observaciones' => 'Ingreso manual de prueba T75']);
        DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        AlumnoRevisionCobranza::create(['alumno_id' => $alumno->id, 'periodo_objetivo' => '2026-11', 'motivo' => 'Confirmar continuidad para el próximo mes.', 'estado_revision' => 'PENDIENTE']);
        return ['usuarios' => ['ADMIN' => $admin->id, 'OPERATIVO' => $operativa->id, 'PROFESOR' => $docente->id],
            'profesor' => $profesor->id, 'grupo' => $grupo->id, 'alumno' => $alumno->id, 'deporte' => $profesor->deporte_id,
            'nivel' => $grupo->nivel_id, 'medio' => $medio->id, 'rubro' => $rubro->id, 'subrubro' => $subrubro->id,
            'cashflow' => $cashflow->id, 'auxiliar' => $auxiliar->id, 'cobro' => $cobro->id, 'cajas' => $cajas, 'movimientos' => $movimientos, 'clases' => $clases, 'liquidaciones' => $liquidaciones];
    }

    private function casos(array $f): array
    {
        $casos = [];
        $excluir = '#(?:^/$|^login$|^logout$|^api/|autocomplete|preview|check-|^recibos/|^storage/|^sanctum/|^up$|fallback|primera-carga/(?:plantilla|informe)$)#';
        foreach (Route::getRoutes() as $ruta) {
            if (!in_array('GET', $ruta->methods()) || preg_match($excluir, $ruta->uri())) continue;
            if (str_contains($ruta->getActionName(), 'Closure')) continue;
            $uri = $ruta->uri();
            $parametros = [];
            foreach ($ruta->parameterNames() as $parametro) {
                $parametros[$parametro] = match (true) {
                    $parametro === 'alumnoId' => $f['alumno'],
                    $parametro === 'cajaId' => $f['cajas']['ABIERTA'],
                    $parametro === 'movId' => str_ends_with($uri, '/cancelar') ? $f['cobro'] : $f['movimientos']['ABIERTA'],
                    $parametro === 'rubroId' => $f['rubro'],
                    str_starts_with($uri, 'alumnos') => $f['alumno'],
                    str_starts_with($uri, 'caja') => $f['cajas']['ABIERTA'],
                    str_starts_with($uri, 'cashflow') => $f['cashflow'],
                    str_starts_with($uri, 'clases') => $f['clases']['hoy'],
                    str_starts_with($uri, 'deportes') => $f['deporte'],
                    str_starts_with($uri, 'grupos') => $f['grupo'],
                    str_starts_with($uri, 'liquidaciones') => $f['liquidaciones']['abierta'],
                    str_starts_with($uri, 'niveles') => $f['nivel'],
                    str_starts_with($uri, 'profesores') => $f['profesor'],
                    str_starts_with($uri, 'rubros') => str_contains($uri, 'subrubros') ? $f['subrubro'] : $f['rubro'],
                    str_starts_with($uri, 'tipos-caja') => $f['medio'],
                    str_starts_with($uri, 'usuarios') => $f['usuarios']['OPERATIVO'],
                    default => throw new \RuntimeException('Parámetro sin fixture: '.$uri),
                };
            }
            $url = '/'.preg_replace_callback('/\{([^}]+)\}/', fn ($m) => $parametros[$m[1]], $uri);
            $slug = 'admin-'.str_replace(['/', '{', '}'], ['-', '', ''], $uri);
            $casos[] = ['slug' => $slug, 'rol' => 'ADMIN', 'ruta' => $url, 'ruta_nombre' => $ruta->getName()];
        }
        $casos[] = ['slug' => 'login', 'rol' => 'ANONIMO', 'ruta' => '/login'];
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $rutas = $rol === 'PROFESOR' ? ['/clases', '/clases/'.$f['clases']['hoy']] : ['/operativo', '/alumnos', '/alumnos/'.$f['alumno'], '/clases', '/clases/'.$f['clases']['hoy'], '/caja', '/caja/'.$f['cajas']['ABIERTA'].'/detalle', '/caja/'.$f['cajas']['ABIERTA'].'/resumen', '/cobranza', '/revision-cobranza'];
            foreach ($rutas as $i => $url) $casos[] = ['slug' => strtolower($rol).'-'.$i, 'rol' => $rol, 'ruta' => $url];
        }
        foreach (['CERRADA', 'RECHAZADA', 'VALIDADA'] as $estado) foreach (['detalle', 'resumen'] as $pantalla) $casos[] = ['slug' => 'admin-caja-'.strtolower($estado).'-'.$pantalla, 'rol' => 'ADMIN', 'ruta' => '/caja/'.$f['cajas'][$estado].'/'.$pantalla];
        foreach (['mes_actual', 'cerrada', 'pagada'] as $estado) $casos[] = ['slug' => 'admin-liquidacion-'.$estado, 'rol' => 'ADMIN', 'ruta' => '/liquidaciones/'.$f['liquidaciones'][$estado]];
        $casos[] = ['slug' => 'admin-clase-futura', 'rol' => 'ADMIN', 'ruta' => '/clases/'.$f['clases']['futura']];
        $casos[] = ['slug' => 'primera-carga-pendiente', 'rol' => 'ADMIN', 'ruta' => '/sistema/primera-carga', 'estado_carga' => 'PENDIENTE'];
        foreach ([403, 404, 429, 500, 503] as $error) $casos[] = ['slug' => 'error-'.$error, 'rol' => 'ADMIN', 'vista_directa' => 'errors.'.$error];
        $casos[] = ['slug' => 'welcome-sin-ruta-en-wings', 'rol' => 'ANONIMO', 'vista_directa' => 'welcome'];
        $casos[] = ['slug' => 'operativo-companero', 'rol' => 'OPERATIVO', 'usuario' => $f['auxiliar'], 'ruta' => '/operativo'];
        $casos[] = ['slug' => 'operativo-turno-antiguo', 'rol' => 'OPERATIVO', 'estado_turno' => 'antiguo', 'ruta' => '/operativo'];
        $casos[] = ['slug' => 'operativo-ultimo-cierre', 'rol' => 'OPERATIVO', 'estado_turno' => 'cerrado', 'ruta' => '/operativo'];
        $casos[] = ['slug' => 'operativo-primera-apertura', 'rol' => 'OPERATIVO', 'usuario' => $f['auxiliar'], 'estado_turno' => 'cerrado', 'ruta' => '/operativo'];
        return $casos;
    }
}
