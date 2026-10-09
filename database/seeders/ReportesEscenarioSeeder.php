<?php

namespace Database\Seeders;

use App\Models\{Alumno, AlumnoPlan, AlumnoRevisionCobranza, CajaOperativa, CashflowMovimiento, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Liquidacion, Nivel, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\{CajaService, CashflowIntegracionCajaService, PagoCuotaService};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Escenario repetible para B12/A23. Jamás admite una base del club. */
class ReportesEscenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDatabaseName() !== 'wings_testing_codex' || Alumno::exists()) {
            throw new RuntimeException('El escenario requiere wings_testing_codex sin alumnos.');
        }
        $reloj = Carbon::getTestNow();
        try {
            Carbon::setTestNow('2026-04-01 10:00:00');
            DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2026-04-01']);
            $this->call(CatalogosSeeder::class);
            $admin = User::factory()->create(['name' => 'Admin de prueba', 'email' => 'reportes-admin@wings.test', 'rol' => 'ADMIN', 'activo' => true]);
            $operativo = User::factory()->create(['name' => 'Operativo de prueba', 'rol' => 'OPERATIVO', 'activo' => true]);
            $efectivo = TipoCaja::where('abreviatura', 'EFT')->firstOrFail();
            $mp = TipoCaja::where('abreviatura', 'MP')->firstOrFail();
            $banco = TipoCaja::where('abreviatura', 'BNA')->firstOrFail();
            $efectivo->update(['saldo_inicial' => 100000]);
            $mp->update(['saldo_inicial' => 60000]);
            $banco->update(['saldo_inicial' => 200000]);
            $rubroIngreso = Rubro::create(['nombre' => 'Ventas de prueba', 'tipo' => 'INGRESO']);
            $ingreso = Subrubro::create(['nombre' => 'Venta de prueba', 'rubro_id' => $rubroIngreso->id,
                'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
            TipoCaja::create(['nombre' => 'Billetera de prueba', 'abreviatura' => 'BLT', 'saldo_inicial' => 40000, 'activo' => true]);
            $alumnos = [];
            foreach (Deporte::orderBy('id')->get() as $deporte) {
                foreach (Nivel::orderBy('id')->take(2)->get() as $nivel) {
                    $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
                    $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
                    foreach (range(1, 6) as $i) {
                        $numero = count($alumnos) + 1;
                        $alumno = Alumno::create(['nombre' => 'Alumno '.$numero, 'apellido' => 'Prueba', 'dni' => (string) (30000000 + $numero),
                            'fecha_nacimiento' => '2000-01-01', 'celular' => '11-4000-0000', 'deporte_id' => $deporte->id,
                            'grupo_id' => $grupo->id, 'fecha_alta' => '2026-03-01', 'activo' => true]);
                        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
                        $alumnos[] = $alumno;
                    }
                }
            }
            $servicioPago = new PagoCuotaService(app(CajaService::class));
            foreach (range(4, 10) as $mes) {
                $periodo = sprintf('2026-%02d', $mes);
                Carbon::setTestNow($periodo.'-01 10:00:00');
                foreach ($alumnos as $alumno) {
                    DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => $periodo, 'monto_original' => 30000,
                        'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
                }
                Carbon::setTestNow($periodo.'-06 10:00:00');
                // Veinte pagan; cuatro acumulan deuda. No se salta FIFO.
                foreach (array_slice($alumnos, 0, 20) as $i => $alumno) {
                    $servicioPago->registrarPagoCuotaAdmin(['alumno_id' => $alumno->id, 'tipo_caja_id' => ($i % 2 ? $mp : $banco)->id,
                        'usuario_admin_id' => $admin->id, 'items' => [['periodo' => $periodo, 'monto' => 30000]], 'fecha_pago' => $periodo.'-06']);
                }
                foreach ([['Luz', 45000], ['Internet', 18000], ['Limpieza', 12000]] as [$concepto, $importe]) {
                    CashflowMovimiento::create(['fecha' => $periodo.'-06', 'subrubro_id' => Subrubro::where('nombre', $concepto)->firstOrFail()->id,
                        'tipo_caja_id' => $banco->id, 'monto' => -$importe, 'usuario_admin_id' => $admin->id]);
                }
            }
            // Agosto se cobra realmente en septiembre; se conserva la deuda al cierre de agosto.
            Carbon::setTestNow('2026-09-15 10:00:00');
            $servicioPago->registrarPagoCuotaAdmin(['alumno_id' => $alumnos[20]->id, 'tipo_caja_id' => $mp->id, 'usuario_admin_id' => $admin->id,
                'items' => array_map(fn ($m) => ['periodo' => sprintf('2026-%02d', $m), 'monto' => 30000], range(4, 8)), 'fecha_pago' => '2026-09-15']);
            Carbon::setTestNow('2026-10-09 10:00:00');
            // Fecha real de agosto, carga posterior: prueba explícita de caja sin alterar la fecha.
            CashflowMovimiento::create(['fecha' => '2026-08-25', 'subrubro_id' => $ingreso->id,
                'tipo_caja_id' => $efectivo->id, 'monto' => 25000, 'usuario_admin_id' => $admin->id]);
            foreach ([['APORTE', 'INGRESO', 90000], ['RETIRO', 'EGRESO', -40000]] as [$clase, $tipo, $importe]) {
                $rubro = Rubro::create(['nombre' => 'Dueño '.$clase, 'tipo' => $tipo]);
                $subrubro = Subrubro::create(['nombre' => $clase.' de prueba', 'rubro_id' => $rubro->id, 'permitido_para' => 'ADMIN',
                    'afecta_caja' => true, 'clasificacion_resultado' => $clase, 'activo' => true]);
                CashflowMovimiento::create(['fecha' => '2026-10-09', 'subrubro_id' => $subrubro->id, 'tipo_caja_id' => $efectivo->id,
                    'monto' => $importe, 'usuario_admin_id' => $admin->id]);
            }
            foreach (['VALIDADA', 'CERRADA'] as $estado) {
                $caja = CajaOperativa::create(['usuario_operativo_id' => $operativo->id, 'apertura_at' => now(), 'cierre_at' => now(),
                    'estado' => $estado, 'validada_at' => $estado === 'VALIDADA' ? now() : null, 'tipo_caja_efectivo_id' => $efectivo->id,
                    'efectivo_inicial' => 0, 'efectivo_contado' => 30000, 'cambio_retenido' => 0]);
                \App\Models\MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'fecha' => today(), 'tipo_caja_id' => $efectivo->id,
                    'subrubro_id' => $ingreso->id, 'monto' => 30000, 'usuario_id' => $operativo->id, 'estado' => 'ACTIVO']);
                if ($estado === 'VALIDADA') app(CashflowIntegracionCajaService::class)->reflejarCajaEnCashflow($caja->id, $admin->id);
            }
            $sueldo = Subrubro::create(['nombre' => 'Sueldo de prueba', 'rubro_id' => Rubro::where('nombre', 'Sueldos')->firstOrFail()->id,
                'permitido_para' => 'ADMIN', 'afecta_caja' => false, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
            $profesor = Profesor::create(['deporte_id' => $alumnos[0]->deporte_id, 'subrubro_id' => $sueldo->id,
                'nombre' => 'Docente', 'apellido' => 'Prueba', 'dni' => '31000001',
                'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Domicilio ficticio', 'localidad' => 'Prueba', 'valor_hora' => 15000, 'activo' => true]);
            foreach (['CERRADA', 'ABIERTA'] as $estado) {
                Liquidacion::create(['profesor_id' => $profesor->id, 'mes' => 10, 'anio' => 2026, 'tipo' => 'HORA',
                    'total_calculado' => 90000, 'estado' => $estado, 'estado_pago' => 'PENDIENTE']);
            }
            $clase = Clase::create(['grupo_id' => $alumnos[0]->grupo_id, 'fecha' => '2026-10-07', 'hora_inicio' => '18:00:00', 'hora_fin' => '19:00:00',
                'validada_para_liquidacion' => false, 'cancelada' => false]);
            $clase->profesores()->attach($profesor->id);
            AlumnoRevisionCobranza::create(['alumno_id' => $alumnos[23]->id, 'periodo_objetivo' => '2026-11',
                'motivo' => 'Sin asistencia en escenario ficticio', 'estado_revision' => 'PENDIENTE']);
            // Este escenario representa un club operativo. No simula una carga
            // Excel deshacible ni modifica la protección P1 de otras bases.
            DB::table('primera_carga')->where('id', 1)->update([
                'estado' => 'TERMINADA', 'usuario_id' => $admin->id, 'detalle' => null,
            ]);
        } finally {
            Carbon::setTestNow($reloj);
        }
    }
}
