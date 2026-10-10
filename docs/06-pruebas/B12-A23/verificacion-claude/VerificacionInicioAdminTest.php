<?php

namespace Tests\Evidencia;

use App\Models\Alumno;
use App\Models\AlumnoRevisionCobranza;
use App\Models\CajaOperativa;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verificacion de la logica de A23 (inicio del admin, hecho por Codex). El aspecto lo aprobo
 * Carlos. Fuera de la suite permanente.
 *
 * No carga datos armados para el reporte: opera por la aplicacion como lo haria el club
 * (alta, apertura, cobros, egreso, cierre, validacion) y en cada momento compara lo que
 * muestra el inicio contra la cuenta hecha a mano.
 *
 *   DB_DATABASE=wings_testing_claude php vendor/bin/phpunit docs/06-pruebas/B12-A23/verificacion-claude/VerificacionInicioAdminTest.php
 */
class VerificacionInicioAdminTest extends TestCase
{
    use RefreshDatabase;

    private array $registro = [];

    private function pesos(float $monto): string
    {
        return number_format($monto, 2, ',', '.');
    }

    private function alta(User $quien, GrupoPlan $plan, string $nombre, string $dni): Alumno
    {
        $this->actingAs($quien)->post(route('web.alumnos.store'), [
            'nombre' => $nombre, 'apellido' => 'Prueba', 'dni' => $dni, 'fecha_nacimiento' => '1995-05-05',
            'celular' => '1150000000', 'fecha_alta' => '2026-10-02',
            'deporte_id' => $plan->grupo->deporte_id, 'grupo_id' => $plan->grupo_id, 'plan_id' => $plan->id,
        ])->assertSessionHasNoErrors();

        return Alumno::where('dni', $dni)->firstOrFail();
    }

    private function inicio(User $admin, string $momento, array $esperado): void
    {
        $r = $this->actingAs($admin)->get(route('admin.dashboard'));
        $r->assertOk();
        $rep = $r->viewData('reporte');
        $cajas = collect($rep['cajas'] ?? [])->keyBy('id');
        $visto = [
            'ingresos' => $rep['ingresos'] === null ? null : $rep['ingresos'] / 100,
            'egresos' => $rep['egresos'] === null ? null : $rep['egresos'] / 100,
            'resultado' => $rep['resultado'] === null ? null : $rep['resultado'] / 100,
            'sin_clasificar' => $rep['sin_clasificar'],
            'disponible' => $rep['disponible'] === null ? null : $rep['disponible'] / 100,
            'efectivo_confirmado' => isset($cajas[$esperado['_efectivo']]) ? $cajas[$esperado['_efectivo']]['confirmado'] / 100 : null,
            'efectivo_pendiente' => isset($cajas[$esperado['_efectivo']]) ? $cajas[$esperado['_efectivo']]['pendiente'] / 100 : null,
            'transferencia_confirmado' => isset($cajas[$esperado['_transferencia']]) ? $cajas[$esperado['_transferencia']]['confirmado'] / 100 : null,
            'transferencia_pendiente' => isset($cajas[$esperado['_transferencia']]) ? $cajas[$esperado['_transferencia']]['pendiente'] / 100 : null,
            'deuda_cuotas' => ($rep['deuda']['total'] ?? null) === null ? null : $rep['deuda']['total'] / 100,
            'aviso_cajas' => $rep['avisos']['cajas'],
            'aviso_asistencia' => $rep['avisos']['asistencia'],
            'aviso_revision' => $rep['avisos']['revision'],
            'aviso_liquidaciones' => $rep['avisos']['liquidaciones'],
        ];
        $filas = [];
        foreach ($visto as $clave => $valor) {
            $e = $esperado[$clave] ?? '(sin cuenta)';
            $filas[$clave] = ['inicio' => $valor, 'a_mano' => $e, 'coincide' => $e === '(sin cuenta)' ? null : (abs((float) $valor - (float) $e) < 0.005 && $valor !== null)];
        }
        $this->registro[$momento] = $filas;
    }

    public function test_recorrido(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $this->seed(CatalogosSeeder::class);
        $admin = User::factory()->create(['name' => 'Admin', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $sandra = User::factory()->create(['name' => 'Sandra', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true]);
        $tipos = TipoCaja::orderBy('id')->get();
        $efectivo = $tipos[0];
        $transferencia = $tipos[1];
        $ids = ['_efectivo' => $efectivo->id, '_transferencia' => $transferencia->id];
        $this->registro['medios'] = ['efectivo' => $efectivo->nombre, 'transferencia' => $transferencia->nombre];
        $this->actingAs($admin)->post('/caja/configuracion', ['tipo_caja_id' => $efectivo->id]);

        // Tres alumnos dados de alta por pantalla: cada uno queda debiendo su cuota de octubre.
        $a = $this->alta($sandra, $plan, 'Ana', '40000001');
        $b = $this->alta($sandra, $plan, 'Bruno', '40000002');
        $c = $this->alta($sandra, $plan, 'Carla', '40000003');
        $cuota = (float) DeudaCuota::where('alumno_id', $a->id)->where('periodo', '2026-10')->value('monto_original');
        $deudaInicial = (float) DeudaCuota::sum('monto_original');
        $this->registro['datos'] = ['cuota_de_cada_alumno' => $cuota, 'deuda_total_de_cuotas' => $deudaInicial,
            'cuotas_generadas' => DeudaCuota::count(), 'cargos_de_inscripcion' => \App\Models\CargoAlumno::count()];

        // Una clase de ayer sin lista y una revision pendiente, para los avisos.
        Clase::create(['grupo_id' => $grupo->id, 'fecha' => '2026-10-09', 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false]);
        AlumnoRevisionCobranza::create(['alumno_id' => $c->id, 'estado_revision' => 'PENDIENTE', 'periodo_objetivo' => '2026-09', 'motivo' => 'Sin asistencias el mes anterior']);

        $this->inicio($admin, '0 antes de operar', $ids + ['ingresos' => 0, 'egresos' => 0, 'resultado' => 0, 'sin_clasificar' => 0,
            'disponible' => 0, 'deuda_cuotas' => $deudaInicial, 'aviso_cajas' => 0, 'aviso_asistencia' => 1, 'aviso_revision' => 1, 'aviso_liquidaciones' => 0]);

        // Sandra abre con $10.000 de cambio y opera.
        $this->actingAs($sandra)->post('/caja/apertura', ['efectivo_inicial' => '10.000,00', 'confirmacion' => '1', 'motivo_apertura' => 'Verificación A23']);
        $caja = CajaOperativa::where('usuario_operativo_id', $sandra->id)->firstOrFail();
        $this->actingAs($sandra)->post("/caja/cobrar/{$a->id}", ['tipo_caja_id' => $efectivo->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => '48.000']])->assertSessionHasNoErrors();
        $this->actingAs($sandra)->post("/caja/cobrar/{$b->id}", ['tipo_caja_id' => $transferencia->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => '20.000']])->assertSessionHasNoErrors();
        $gasto = Subrubro::where('permitido_para', 'OPERATIVO')->where('es_reservado_sistema', false)->where('afecta_caja', true)
            ->whereHas('rubro', fn ($q) => $q->where('tipo', 'EGRESO'))->firstOrFail();
        $this->actingAs($sandra)->post('/caja/movimiento', ['tipo_caja_id' => $efectivo->id, 'subrubro_id' => $gasto->id, 'monto' => '6000', 'observaciones' => 'Limpieza'])->assertSessionHasNoErrors();
        // El admin cobra directo, sin caja.
        $this->actingAs($admin)->post("/caja/cobrar/{$c->id}", ['tipo_caja_id' => $efectivo->id, 'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => '10.000']])->assertSessionHasNoErrors();

        $pago = fn (Alumno $al) => (float) \App\Models\Pago::where('alumno_id', $al->id)->sum('monto_final');
        $pa = $pago($a); $pb = $pago($b); $pc = $pago($c);
        $saldoCuotas = (float) DeudaCuota::selectRaw('SUM(monto_original - monto_pagado) s')->value('s');
        $this->registro['cobrado_de_verdad'] = ['Ana, efectivo, Sandra (pidio 48.000)' => $pa, 'Bruno, transferencia, Sandra (pidio 20.000)' => $pb, 'Carla, efectivo, admin directo (pidio 10.000)' => $pc,
            'saldo_de_cuotas_en_la_base' => $saldoCuotas];
        $ingresos = $pa + $pb + $pc;
        $comun = $ids + ['ingresos' => $ingresos, 'egresos' => 6000, 'resultado' => $ingresos - 6000, 'sin_clasificar' => 0,
            'disponible' => $ingresos - 6000, 'deuda_cuotas' => $saldoCuotas,
            'aviso_asistencia' => 1, 'aviso_revision' => 1, 'aviso_liquidaciones' => 0];

        $this->inicio($admin, '1 caja de Sandra abierta, con cobros y un gasto', $comun + ['aviso_cajas' => 0,
            'efectivo_confirmado' => $pc, 'efectivo_pendiente' => $pa - 6000, 'transferencia_confirmado' => 0, 'transferencia_pendiente' => $pb]);

        $contado = 10000 + $pa - 6000;
        $this->actingAs($sandra)->post(route('web.caja.cerrar', $caja->id), ['efectivo_contado' => $this->pesos($contado), 'cambio_retenido' => '10.000,00'])->assertSessionHasNoErrors();
        $this->registro['caja_tras_cerrar'] = $caja->fresh()->only(['estado', 'efectivo_esperado', 'efectivo_contado', 'diferencia_efectivo']);

        $this->inicio($admin, '2 Sandra cerró; falta validar', $comun + ['aviso_cajas' => 1,
            'efectivo_confirmado' => $pc, 'efectivo_pendiente' => $pa - 6000, 'transferencia_confirmado' => 0, 'transferencia_pendiente' => $pb]);

        $this->actingAs($admin)->post(route('web.cajas.validar', $caja->id))->assertSessionHasNoErrors();
        $this->registro['caja_tras_validar'] = $caja->fresh()->only(['estado']);

        $this->inicio($admin, '3 el admin validó la caja', $comun + ['aviso_cajas' => 0,
            'efectivo_confirmado' => $pc + $pa - 6000, 'efectivo_pendiente' => 0, 'transferencia_confirmado' => $pb, 'transferencia_pendiente' => 0]);

        // Reportes del mismo mes tiene que decir lo mismo que el inicio.
        $rep = $this->actingAs($admin)->get('/reportes')->viewData('reporte');
        $this->registro['reportes_mismo_mes'] = $rep === null ? 'la pantalla no entrega "reporte"' : [
            'ingresos' => $rep['ingresos'] / 100, 'egresos' => $rep['egresos'] / 100,
            'resultado' => $rep['resultado'] === null ? null : $rep['resultado'] / 100, 'disponible' => $rep['disponible'] === null ? null : $rep['disponible'] / 100,
        ];

        file_put_contents(__DIR__ . '/resultado.json', json_encode($this->registro, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        Carbon::setTestNow();
        $this->assertTrue(true);
    }
}
