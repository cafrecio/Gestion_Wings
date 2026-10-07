<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoRevisionCobranza;
use App\Models\Asistencia;
use App\Models\CajaOperativa;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GenerarPropuestasTest extends TestCase
{
    private const TZ = 'America/Argentina/Buenos_Aires';

    private User $operativo1;
    private User $operativo2;
    private User $admin;
    private TipoCaja $tipoEfectivo;
    private Subrubro $subrubroIngreso;
    private Subrubro $subrubroEgreso;
    private Deporte $deporte;
    private Grupo $grupo1;
    private Grupo $grupo2;
    private Profesor $profesor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('wings_testing_gemini', config('database.connections.mysql.database'));
    }

    private function prepararCatalogoBase(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MovimientoOperativo::truncate();
        CajaOperativa::truncate();
        Asistencia::truncate();
        Clase::truncate();
        DeudaCuota::truncate();
        AlumnoRevisionCobranza::truncate();
        Alumno::truncate();
        Grupo::truncate();
        Nivel::truncate();
        Profesor::truncate();
        Deporte::truncate();
        Subrubro::truncate();
        Rubro::truncate();
        TipoCaja::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->admin = User::firstOrCreate(['email' => 'admin@wings.com'], [
            'name' => 'Admin Wings',
            'rol' => User::ROL_ADMIN,
            'password' => bcrypt('secret'),
            'activo' => true,
        ]);
        $this->operativo1 = User::firstOrCreate(['email' => 'operativo@wings.com'], [
            'name' => 'Sandra Vidal',
            'rol' => User::ROL_OPERATIVO,
            'password' => bcrypt('secret'),
            'activo' => true,
        ]);
        $this->operativo2 = User::firstOrCreate(['email' => 'operativo2@wings.com'], [
            'name' => 'Marcos Peña',
            'rol' => User::ROL_OPERATIVO,
            'password' => bcrypt('secret'),
            'activo' => true,
        ]);

        $this->tipoEfectivo = TipoCaja::create([
            'nombre' => 'Efectivo',
            'abreviatura' => 'EFE',
            'activo' => true,
        ]);

        $rubroIngreso = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO']);
        $rubroEgreso = Rubro::create(['nombre' => 'Gastos varios', 'tipo' => 'EGRESO']);

        $this->subrubroIngreso = Subrubro::create([
            'rubro_id' => $rubroIngreso->id,
            'nombre' => 'Cuota mensual',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'activo' => true,
        ]);

        $this->subrubroEgreso = Subrubro::create([
            'rubro_id' => $rubroEgreso->id,
            'nombre' => 'Librería e insumos',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'activo' => true,
        ]);

        $this->deporte = Deporte::create(['nombre' => 'Acrobacia']);
        $nivel1 = Nivel::firstOrCreate(['nombre' => 'Inicial']);
        $nivel2 = Nivel::firstOrCreate(['nombre' => 'Intermedio']);

        $this->profesor = Profesor::create([
            'nombre' => 'Laura',
            'apellido' => 'Gómez',
            'dni' => '28123456',
            'fecha_nacimiento' => '1990-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Buenos Aires',
            'activo' => true,
        ]);

        $this->grupo1 = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel1->id,
            'dia_semana' => Carbon::now(self::TZ)->dayOfWeekIso,
            'hora_inicio' => '17:00:00',
            'hora_fin' => '18:30:00',
            'profesor_id' => $this->profesor->id,
        ]);

        $this->grupo2 = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel2->id,
            'dia_semana' => Carbon::now(self::TZ)->dayOfWeekIso,
            'hora_inicio' => '19:00:00',
            'hora_fin' => '20:30:00',
            'profesor_id' => $this->profesor->id,
        ]);
    }

    private function crearAlumno(string $nombre, string $apellido, string $dni): Alumno
    {
        return Alumno::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-5000',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo1->id,
            'fecha_alta' => '2026-01-01',
            'activo' => true,
        ]);
    }

    public function test_generar_propuestas_opcion1_y_opcion2(): void
    {
        $hoyAr = Carbon::now(self::TZ);

        for ($esc = 1; $esc <= 7; $esc++) {
            $this->prepararCatalogoBase();

            $cajaPropia = null;
            $cajaClub = null;
            $ultimaCaja = null;
            $bloqueo = ['activo' => false];
            $cajasRechazadasCount = 0;

            switch ($esc) {
                case 1:
                    $this->crearAlumnosYDeudas();
                    $this->crearClasesHoy();
                    break;

                case 2:
                    $this->crearAlumnosYDeudas();
                    $this->crearClasesHoy();
                    $cajaPropia = CajaOperativa::create([
                        'usuario_operativo_id' => $this->operativo1->id,
                        'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
                        'efectivo_inicial' => 10000,
                        'apertura_at' => $hoyAr->copy()->setTime(9, 15, 0),
                        'estado' => 'ABIERTA',
                    ]);
                    MovimientoOperativo::create([
                        'caja_operativa_id' => $cajaPropia->id,
                        'tipo_caja_id' => $this->tipoEfectivo->id,
                        'subrubro_id' => $this->subrubroIngreso->id,
                        'monto' => 15000,
                        'fecha' => $hoyAr->toDateString(),
                        'usuario_id' => $this->operativo1->id,
                        'estado' => 'ACTIVO',
                    ]);
                    MovimientoOperativo::create([
                        'caja_operativa_id' => $cajaPropia->id,
                        'tipo_caja_id' => $this->tipoEfectivo->id,
                        'subrubro_id' => $this->subrubroEgreso->id,
                        'monto' => 2000,
                        'fecha' => $hoyAr->toDateString(),
                        'usuario_id' => $this->operativo1->id,
                        'estado' => 'ACTIVO',
                    ]);
                    break;

                case 3:
                    // Hay turno abierto por otro compañero (Marcos Peña)
                    $this->crearAlumnosYDeudas();
                    $this->crearClasesHoy();
                    $cajaClub = CajaOperativa::create([
                        'usuario_operativo_id' => $this->operativo2->id,
                        'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
                        'efectivo_inicial' => 10000,
                        'apertura_at' => $hoyAr->copy()->setTime(8, 30, 0),
                        'estado' => 'ABIERTA',
                    ]);
                    break;

                case 4:
                    // Cerró su turno y espera validación
                    $this->crearAlumnosYDeudas();
                    $this->crearClasesHoy();
                    $ultimaCaja = CajaOperativa::create([
                        'usuario_operativo_id' => $this->operativo1->id,
                        'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
                        'efectivo_inicial' => 10000,
                        'efectivo_contado' => 23000,
                        'cambio_retenido' => 10000,
                        'apertura_at' => $hoyAr->copy()->setTime(9, 0, 0),
                        'cierre_at' => $hoyAr->copy()->setTime(13, 0, 0),
                        'estado' => 'CERRADA',
                    ]);
                    break;

                case 5:
                    // Caja rechazada
                    $this->crearAlumnosYDeudas();
                    $this->crearClasesHoy();
                    $cajasRechazadasCount = 1;
                    CajaOperativa::create([
                        'usuario_operativo_id' => $this->operativo1->id,
                        'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
                        'efectivo_inicial' => 10000,
                        'efectivo_contado' => 21000,
                        'cambio_retenido' => 10000,
                        'apertura_at' => $hoyAr->copy()->subDay()->setTime(9, 0, 0),
                        'cierre_at' => $hoyAr->copy()->subDay()->setTime(13, 0, 0),
                        'estado' => 'RECHAZADA',
                        'motivo_rechazo' => 'Falta comprobante del egreso de librería de $2.000',
                    ]);
                    break;

                case 6:
                    // Clases con y sin lista
                    $this->crearAlumnosYDeudas();
                    $c1 = Clase::create([
                        'grupo_id' => $this->grupo1->id,
                        'profesor_id' => $this->profesor->id,
                        'fecha' => $hoyAr->toDateString(),
                        'hora_inicio' => '17:00:00',
                        'hora_fin' => '18:30:00',
                        'cancelada' => false,
                    ]);
                    for ($p = 1; $p <= 5; $p++) {
                        $al = $this->crearAlumno("Presente {$p}", 'Test', "4000000{$p}");
                        Asistencia::create(['clase_id' => $c1->id, 'alumno_id' => $al->id, 'presente' => true]);
                    }
                    Clase::create([
                        'grupo_id' => $this->grupo2->id,
                        'profesor_id' => $this->profesor->id,
                        'fecha' => $hoyAr->toDateString(),
                        'hora_inicio' => '18:30:00',
                        'hora_fin' => '20:00:00',
                        'cancelada' => false,
                    ]);
                    $nivel3 = Nivel::firstOrCreate(['nombre' => 'Avanzado']);
                    $g3 = Grupo::create([
                        'deporte_id' => $this->deporte->id,
                        'nivel_id' => $nivel3->id,
                        'dia_semana' => $hoyAr->dayOfWeekIso,
                        'hora_inicio' => '20:00:00',
                        'hora_fin' => '21:30:00',
                        'profesor_id' => $this->profesor->id,
                    ]);
                    Clase::create([
                        'grupo_id' => $g3->id,
                        'profesor_id' => $this->profesor->id,
                        'fecha' => $hoyAr->toDateString(),
                        'hora_inicio' => '20:00:00',
                        'hora_fin' => '21:30:00',
                        'cancelada' => false,
                    ]);
                    break;

                case 7:
                    // Día sin nada
                    break;
            }

            // Datos comunes
            $cajas = CajaOperativa::where('usuario_operativo_id', $this->operativo1->id)
                ->whereDate('apertura_at', $hoyAr->toDateString())
                ->with(['movimientos' => fn($q) => $q->where('estado', 'ACTIVO')->with('subrubro.rubro')])
                ->get();

            $totalCobradoHoy = 0;
            $numCobrosHoy = 0;
            foreach ($cajas as $caja) {
                foreach ($caja->movimientos as $mov) {
                    if ($mov->subrubro?->rubro?->tipo === 'INGRESO') {
                        $totalCobradoHoy += (float) $mov->monto;
                        $numCobrosHoy++;
                    }
                }
            }

            $clasesHoy = Clase::with(['grupo.deporte', 'grupo.nivel'])
                ->withCount(['asistencias as presentes_count' => fn($q) => $q->where('presente', true)])
                ->whereDate('fecha', $hoyAr->toDateString())
                ->where('cancelada', false)
                ->orderBy('hora_inicio')
                ->get();

            $alumnosConDeuda = DeudaCuota::whereNotIn('estado', [DeudaCuota::ESTADO_PAGADA, DeudaCuota::ESTADO_CONDONADA])
                ->whereRaw('monto_pagado < monto_original')
                ->whereHas('alumno', fn($q) => $q->where('activo', true))
                ->distinct('alumno_id')
                ->count('alumno_id');

            $posiblesInactivos = AlumnoRevisionCobranza::where('estado_revision', AlumnoRevisionCobranza::ESTADO_PENDIENTE)->count();

            // Renderizar como usuario Operativo 1
            $this->actingAs($this->operativo1);

            $viewData = compact(
                'cajaPropia', 'cajaClub', 'ultimaCaja', 'bloqueo', 'hoyAr',
                'cajasRechazadasCount', 'totalCobradoHoy', 'numCobrosHoy', 'cajas',
                'clasesHoy', 'alumnosConDeuda', 'posiblesInactivos'
            );

            // Opción 1
            $html1 = view()->file(base_path('docs/06-pruebas/PRU-02/evidencia/a12-a24/propuestas/opcion-1.blade.php'), $viewData)->render();
            $this->guardarHtml("opcion-1-escenario-{$esc}", $html1);

            // Opción 2
            $html2 = view()->file(base_path('docs/06-pruebas/PRU-02/evidencia/a12-a24/propuestas/opcion-2.blade.php'), $viewData)->render();
            $this->guardarHtml("opcion-2-escenario-{$esc}", $html2);
        }

        $this->assertTrue(true);
    }

    private function crearAlumnosYDeudas(): void
    {
        $a1 = $this->crearAlumno('Lucas', 'Benítez', '35111222');
        $a2 = $this->crearAlumno('Martina', 'Sosa', '36222333');
        $a3 = $this->crearAlumno('Agustín', 'Pérez', '37333444');

        DeudaCuota::create([
            'alumno_id' => $a1->id,
            'periodo' => Carbon::now(self::TZ)->format('Y-m'),
            'monto_original' => 20000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
        DeudaCuota::create([
            'alumno_id' => $a2->id,
            'periodo' => Carbon::now(self::TZ)->format('Y-m'),
            'monto_original' => 20000,
            'monto_pagado' => 5000,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        AlumnoRevisionCobranza::create([
            'alumno_id' => $a3->id,
            'periodo_objetivo' => Carbon::now(self::TZ)->format('Y-m'),
            'motivo' => 'Sin asistencia en el mes anterior',
            'estado_revision' => AlumnoRevisionCobranza::ESTADO_PENDIENTE,
        ]);
    }

    private function crearClasesHoy(): void
    {
        $hoyAr = Carbon::now(self::TZ);
        $c1 = Clase::create([
            'grupo_id' => $this->grupo1->id,
            'profesor_id' => $this->profesor->id,
            'fecha' => $hoyAr->toDateString(),
            'hora_inicio' => '17:00:00',
            'hora_fin' => '18:30:00',
            'cancelada' => false,
        ]);
        $al = $this->crearAlumno('Presente 1', 'Test', '41000001');
        Asistencia::create(['clase_id' => $c1->id, 'alumno_id' => $al->id, 'presente' => true]);

        Clase::create([
            'grupo_id' => $this->grupo2->id,
            'profesor_id' => $this->profesor->id,
            'fecha' => $hoyAr->toDateString(),
            'hora_inicio' => '19:00:00',
            'hora_fin' => '20:30:00',
            'cancelada' => false,
        ]);
    }

    private function guardarHtml(string $nombre, string $html): void
    {
        $dir = public_path('a12-a24-evidencia');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        file_put_contents("{$dir}/{$nombre}.html", $html);

        $marco = <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Wings - Celular 375 ({$nombre})</title>
<style>
  body { margin: 0; background: #e2e8f0; display: flex; justify-content: center; align-items: flex-start; padding: 20px 0; font-family: sans-serif; }
  .frame-container { width: 375px; height: 667px; background: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; }
  iframe { width: 375px; height: 667px; border: 0; display: block; }
</style>
</head>
<body>
<div class="frame-container">
  <iframe src="{$nombre}.html" title="Pantalla real de Wings a 375"></iframe>
</div>
</body>
</html>
HTML;
        file_put_contents("{$dir}/{$nombre}-375.html", $marco);
    }
}
