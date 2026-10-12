<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
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
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * Generador de evidencia para T28:
 * Crea movimientos reales de cuotas, inscripciones y gastos (de caja validada y directos del dueño)
 * y guarda el HTML de Cashflow y Login para las capturas reglamentarias.
 */
class CapturaT28Test extends TestCase
{
    use RefreshDatabase;

    public function test_generar_htmls_cashflow_para_capturas(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');

        $admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
            'name' => 'Carlos Administrador',
        ]);
        $sandra = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
            'name' => 'Sandra Mostrador',
        ]);

        $tipoEfe = TipoCaja::first() ?? TipoCaja::create([
            'nombre' => 'Efectivo Mostrador',
            'abreviatura' => 'EFE',
            'activo' => true,
        ]);

        $rubroIngresos = Rubro::firstOrCreate(['nombre' => 'Cuotas', 'tipo' => 'INGRESO'], ['observacion' => '']);
        $rubroEgresos = Rubro::firstOrCreate(['nombre' => 'Gastos', 'tipo' => 'EGRESO'], ['observacion' => '']);

        $subrubroCuota = Subrubro::firstOrCreate(
            ['nombre' => 'Cuota Mensual'],
            [
                'rubro_id' => $rubroIngresos->id,
                'permitido_para' => User::ROL_OPERATIVO,
                'afecta_caja' => true,
                'es_reservado_sistema' => true,
            ]
        );

        $subrubroInscripcion = Subrubro::firstOrCreate(
            ['nombre' => 'Inscripción al club'],
            [
                'rubro_id' => $rubroIngresos->id,
                'permitido_para' => User::ROL_OPERATIVO,
                'afecta_caja' => true,
                'es_reservado_sistema' => true,
            ]
        );

        $subrubroGasto = Subrubro::firstOrCreate(
            ['nombre' => 'Artículos de Limpieza'],
            [
                'rubro_id' => $rubroEgresos->id,
                'permitido_para' => User::ROL_OPERATIVO,
                'afecta_caja' => true,
            ]
        );

        $cajaService = app(CajaService::class);
        $cajaService->configurarMostrador($tipoEfe->id, $admin->id);

        $deporte = Deporte::first() ?? Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::first() ?? Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::first() ?? GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 35000, 'activo' => true]);

        $crearAlumno = function (string $apellido, string $nombre, string $dni) use ($deporte, $grupo, $plan) {
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
        };

        $juan = $crearAlumno('Pérez', 'Juan', '38111222');
        $martina = $crearAlumno('Gómez', 'Martina', '38222333');
        $sofia = $crearAlumno('López', 'Sofía', '38333444');

        // 1. Caja de Sandra con cobro de Juan y gasto de limpieza
        $caja = CajaDeclarada::crear($sandra->id, $tipoEfe->id);
        app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $juan->id,
            'tipo_caja_id' => $tipoEfe->id,
            'usuario_operativo_id' => $sandra->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => '2026-10-12',
            'tipo_caja_id' => $tipoEfe->id,
            'subrubro_id' => $subrubroGasto->id,
            'monto' => 4200,
            'observaciones' => 'Compra de lavandina y trapos de piso',
            'usuario_id' => $sandra->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        // Cierre y validación de la caja por Carlos
        $caja->estado = CajaOperativa::ESTADO_CERRADA;
        $caja->efectivo_contado = 30800;
        $caja->save();
        $cajaService->validarCaja($caja->id, $admin->id);

        // 2. Cobro directo del dueño sin caja para Martina
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin([
            'alumno_id' => $martina->id,
            'tipo_caja_id' => $tipoEfe->id,
            'usuario_admin_id' => $admin->id,
            'items' => [['periodo' => '2026-10', 'monto' => 35000]],
            'fecha_pago' => '2026-10-12',
        ]);

        // 3. Gasto directo del dueño en Cashflow
        $this->actingAs($admin)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $tipoEfe->id,
            'subrubro_id' => $subrubroGasto->id,
            'monto' => '8500.00',
            'fecha' => '2026-10-12',
            'observaciones' => 'Abono mensual servicio de internet',
        ]);

        // Guardar Cashflow
        $htmlCashflow = $this->actingAs($admin)->get(route('web.cashflow.index'))->assertOk()->getContent();
        $this->guardar('cashflow', $htmlCashflow);

        // Guardar Login control
        auth()->logout();
        $htmlLogin = $this->get(route('login'))->assertOk()->getContent();
        $this->guardar('login-control', $htmlLogin);

        Carbon::setTestNow();
    }

    private function guardar(string $nombre, string $html): void
    {
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t28');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $build = str_replace(chr(92), '/', public_path('build'));
        $html = str_replace(['src="/build/', 'href="/build/'], ['src="'.$build.'/', 'href="'.$build.'/'], $html);
        file_put_contents("{$dir}/{$nombre}.html", $html);
        file_put_contents(
            "{$dir}/{$nombre}-marco-360.html",
            '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · '.$nombre.' (360px)</title></head>'
            .'<body style="margin:0;background:#888;display:flex;justify-content:center;padding:24px 0;">'
            .'<iframe src="'.$nombre.'.html" style="width:360px;height:1150px;border:0;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.35);border-radius:16px;"></iframe>'
            .'</body></html>'
        );
    }
}
