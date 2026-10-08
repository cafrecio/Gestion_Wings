<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VerificacionA26A39A35Test extends TestCase
{
    private const TZ = 'America/Argentina/Buenos_Aires';

    private User $admin;
    private User $operativoSandra;
    private User $operativoMarcos;
    private User $profesor;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;
    private TipoCaja $tipoEfectivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('wings_testing_gemini', config('database.connections.mysql.database'));
        $this->prepararDatosBase();
    }

    private function prepararDatosBase(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MovimientoOperativo::truncate();
        CajaOperativa::truncate();
        DB::table('pagos')->truncate();
        DB::table('deuda_cuotas')->truncate();
        DB::table('cargo_alumno_eventos')->truncate();
        DB::table('cargos_alumno')->truncate();
        DB::table('alumno_planes')->truncate();
        Alumno::truncate();
        GrupoPlan::truncate();
        Grupo::truncate();
        Nivel::truncate();
        Deporte::truncate();
        Subrubro::truncate();
        Rubro::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->seed(\Database\Seeders\CatalogosSeeder::class);
        \App\Models\Configuracion::set('inscripcion_importe', '5000');

        $this->admin = User::factory()->create([
            'name' => 'Carlos Admin',
            'email' => 'admin@wings.com',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->operativoSandra = User::factory()->create([
            'name' => 'Sandra Vidal',
            'email' => 'sandra@wings.com',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->operativoMarcos = User::factory()->create([
            'name' => 'Marcos Peña',
            'email' => 'marcos@wings.com',
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->profesor = User::factory()->create([
            'name' => 'Laura Gómez',
            'email' => 'laura@wings.com',
            'rol' => User::ROL_PROFESOR,
            'activo' => true,
        ]);

        $this->tipoEfectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], [
            'abreviatura' => 'EFE',
            'activo' => true,
        ]);

        $this->deporte = Deporte::create(['nombre' => 'Acrobacia', 'activo' => true]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create([
            'nombre' => 'Acrobacia Niños',
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 25000,
            'activo' => true,
        ]);
    }

    public function test_verificacion_a26_servidor_y_base(): void
    {
        $hoy = Carbon::now(self::TZ);
        $resultados = [];

        $payload1 = [
            'nombre' => 'Mateo',
            'apellido' => 'González',
            'dni' => '50111222',
            'fecha_nacimiento' => $hoy->copy()->subYears(10)->toDateString(),
            'fecha_alta' => $hoy->toDateString(),
            'celular' => '',
            'nombre_tutor' => 'María Pérez',
            'telefono_tutor' => '11-4444-5555',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ];
        $res1 = $this->actingAs($this->operativoSandra)->post(route('web.alumnos.store'), $payload1);
        if ($res1->getStatusCode() !== 302) {
            dump("Store returned: " . $res1->getStatusCode() . " content: " . substr($res1->getContent(), 0, 300));
        }
        $res1->assertRedirect(route('web.alumnos.index'));
        $a1 = Alumno::where('dni', '50111222')->first();
        $this->assertNotNull($a1);
        $this->assertSame('11-4444-5555', $a1->celular);
        $this->assertSame('11-4444-5555', $a1->telefono_tutor);
        $resultados['caso_1'] = [
            'descripcion' => 'Alta menor sin celular',
            'status' => $res1->getStatusCode(),
            'bd_celular' => $a1->celular,
            'bd_telefono_tutor' => $a1->telefono_tutor,
            'exito' => ($a1->celular === '11-4444-5555'),
        ];

        // 2. Alta de un menor con celular propio: lo conserva.
        $payload2 = [
            'nombre' => 'Joaquín',
            'apellido' => 'López',
            'dni' => '50222333',
            'fecha_nacimiento' => $hoy->copy()->subYears(12)->toDateString(),
            'fecha_alta' => $hoy->toDateString(),
            'celular' => '11-9999-8888',
            'nombre_tutor' => 'Carlos López',
            'telefono_tutor' => '11-3333-2222',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ];
        $res2 = $this->actingAs($this->operativoSandra)->post(route('web.alumnos.store'), $payload2);
        $res2->assertRedirect(route('web.alumnos.index'));
        $a2 = Alumno::where('dni', '50222333')->first();
        $this->assertNotNull($a2);
        $this->assertSame('11-9999-8888', $a2->celular);
        $this->assertSame('11-3333-2222', $a2->telefono_tutor);
        $resultados['caso_2'] = [
            'descripcion' => 'Alta menor con celular propio',
            'status' => $res2->getStatusCode(),
            'bd_celular' => $a2->celular,
            'bd_telefono_tutor' => $a2->telefono_tutor,
            'exito' => ($a2->celular === '11-9999-8888'),
        ];

        // 3. Alta de un mayor sin celular: rechaza, con mensaje.
        $payload3 = [
            'nombre' => 'Esteban',
            'apellido' => 'Suárez',
            'dni' => '38111222',
            'fecha_nacimiento' => $hoy->copy()->subYears(25)->toDateString(),
            'fecha_alta' => $hoy->toDateString(),
            'celular' => '',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ];
        $res3 = $this->actingAs($this->operativoSandra)->post(route('web.alumnos.store'), $payload3);
        $res3->assertSessionHasErrors(['celular']);
        $a3 = Alumno::where('dni', '38111222')->first();
        $this->assertNull($a3);
        $resultados['caso_3'] = [
            'descripcion' => 'Alta mayor sin celular',
            'status' => $res3->getStatusCode(),
            'mensaje_error' => session('errors')->first('celular'),
            'exito' => ($a3 === null && session('errors')->has('celular')),
        ];

        // 4. Edición de un menor, vaciando el celular: queda el del tutor.
        $payload4 = [
            'nombre' => $a2->nombre,
            'apellido' => $a2->apellido,
            'dni' => $a2->dni,
            'fecha_nacimiento' => $a2->fecha_nacimiento->toDateString(),
            'fecha_alta' => $a2->fecha_alta->toDateString(),
            'celular' => '', // Vaciamos
            'nombre_tutor' => $a2->nombre_tutor,
            'telefono_tutor' => $a2->telefono_tutor,
            'deporte_id' => $a2->deporte_id,
            'grupo_id' => $a2->grupo_id,
            'plan_id' => $this->plan->id,
        ];
        $res4 = $this->actingAs($this->operativoSandra)->put(route('web.alumnos.update', $a2->id), $payload4);
        $res4->assertRedirect(route('web.alumnos.index'));
        $a2->refresh();
        $this->assertSame('11-3333-2222', $a2->celular);
        $resultados['caso_4'] = [
            'descripcion' => 'Edición menor vaciando celular',
            'status' => $res4->getStatusCode(),
            'bd_celular' => $a2->celular,
            'exito' => ($a2->celular === '11-3333-2222'),
        ];

        // 5. El borde: alguien que cumple 18 hoy, y alguien que los cumple mañana.
        // Borde A: Cumple 18 hoy exactos -> $hoy->copy()->subYears(18)
        // En PHP: diffInYears($hoy) da 18, 18 < 18 es FALSE => es MAYOR => celular obligatorio.
        $payloadBordeHoy = [
            'nombre' => 'Lucas',
            'apellido' => 'Borde Hoy',
            'dni' => '46000001',
            'fecha_nacimiento' => $hoy->copy()->subYears(18)->toDateString(),
            'fecha_alta' => $hoy->toDateString(),
            'celular' => '',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ];
        $resBordeHoy = $this->actingAs($this->operativoSandra)->post(route('web.alumnos.store'), $payloadBordeHoy);
        $resBordeHoy->assertSessionHasErrors(['celular']);
        $aBordeHoy = Alumno::where('dni', '46000001')->first();
        $this->assertNull($aBordeHoy);

        // Borde B: Cumple 18 mañana -> $hoy->copy()->subYears(18)->addDay()
        // En PHP: diffInYears($hoy) da 17, 17 < 18 es TRUE => es MENOR => celular opcional, tutor obligatorio.
        $payloadBordeManana = [
            'nombre' => 'Julián',
            'apellido' => 'Borde Mañana',
            'dni' => '46000002',
            'fecha_nacimiento' => $hoy->copy()->subYears(18)->addDay()->toDateString(),
            'fecha_alta' => $hoy->toDateString(),
            'celular' => '',
            'nombre_tutor' => 'Tutor Julián',
            'telefono_tutor' => '11-7777-6666',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ];
        $resBordeManana = $this->actingAs($this->operativoSandra)->post(route('web.alumnos.store'), $payloadBordeManana);
        $resBordeManana->assertRedirect(route('web.alumnos.index'));
        $aBordeManana = Alumno::where('dni', '46000002')->first();
        $this->assertNotNull($aBordeManana);
        $this->assertSame('11-7777-6666', $aBordeManana->celular);

        $resultados['caso_5_borde_hoy'] = [
            'fecha' => $payloadBordeHoy['fecha_nacimiento'],
            'es_menor_servidor' => false,
            'celular_exigido' => true,
            'guardado' => ($aBordeHoy !== null),
            'session_errors' => session('errors') ? session('errors')->keys() : [],
        ];
        $resultados['caso_5_borde_manana'] = [
            'fecha' => $payloadBordeManana['fecha_nacimiento'],
            'es_menor_servidor' => true,
            'celular_exigido' => false,
            'guardado' => ($aBordeManana !== null),
            'bd_celular' => $aBordeManana ? $aBordeManana->celular : null,
        ];

        // 6. Menor sin celular Y sin teléfono de tutor: tiene que rechazar por el tutor, no dar error 500.
        $payload6 = [
            'nombre' => 'Tomás',
            'apellido' => 'Sin Contacto',
            'dni' => '51000001',
            'fecha_nacimiento' => $hoy->copy()->subYears(8)->toDateString(),
            'fecha_alta' => $hoy->toDateString(),
            'celular' => '',
            'nombre_tutor' => 'Tutor Tomás',
            'telefono_tutor' => '', // Sin teléfono de tutor
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'plan_id' => $this->plan->id,
        ];
        $res6 = $this->actingAs($this->operativoSandra)->post(route('web.alumnos.store'), $payload6);
        $this->assertSame(302, $res6->getStatusCode()); // Redirect back con errores de validación, NUNCA 500
        $res6->assertSessionHasErrors(['telefono_tutor']);
        $a6 = Alumno::where('dni', '51000001')->first();
        $this->assertNull($a6);
        $resultados['caso_6'] = [
            'descripcion' => 'Menor sin celular y sin tel tutor',
            'status' => $res6->getStatusCode(),
            'errores' => session('errors')->keys(),
            'mensaje_tutor' => session('errors')->first('telefono_tutor'),
            'exito' => ($res6->getStatusCode() === 302 && session('errors')->has('telefono_tutor') && $a6 === null),
        ];

        file_put_contents(
            base_path('docs/06-pruebas/PRU-02/evidencia/verificacion-a26-a39-a35/resultados-a26.json'),
            json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_verificacion_a39_movimientos_reglas_y_catalogos(): void
    {
        $hoy = Carbon::now(self::TZ);

        // Catálogos
        $rubroOperativoIng = Rubro::create(['nombre' => 'Cobranzas', 'tipo' => 'INGRESO']);
        $rubroOperativoEgr = Rubro::create(['nombre' => 'Gastos Mostrador', 'tipo' => 'EGRESO']);
        $rubroAdminSueldo = Rubro::create(['nombre' => 'Sueldos Profesores', 'tipo' => 'EGRESO']);
        $rubroAdminAlquiler = Rubro::create(['nombre' => 'Alquiler Cancha', 'tipo' => 'EGRESO']);
        $rubroMixto = Rubro::create(['nombre' => 'Mantenimiento General', 'tipo' => 'EGRESO']);

        $subOperativoIng = Subrubro::create([
            'rubro_id' => $rubroOperativoIng->id,
            'nombre' => 'Cuotas Gimnasia',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'activo' => true,
        ]);
        $subOperativoEgr = Subrubro::create([
            'rubro_id' => $rubroOperativoEgr->id,
            'nombre' => 'Librería y Papelería',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'activo' => true,
        ]);
        $subAdminSueldo = Subrubro::create([
            'rubro_id' => $rubroAdminSueldo->id,
            'nombre' => 'Sueldo Profesor Titular',
            'permitido_para' => 'ADMIN',
            'afecta_caja' => true,
            'activo' => true,
        ]);
        $subAdminAlquiler = Subrubro::create([
            'rubro_id' => $rubroAdminAlquiler->id,
            'nombre' => 'Alquiler Predio Mensual',
            'permitido_para' => 'ADMIN',
            'afecta_caja' => true,
            'activo' => true,
        ]);
        $subMixtoOperativo = Subrubro::create([
            'rubro_id' => $rubroMixto->id,
            'nombre' => 'Artículos de Limpieza',
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'activo' => true,
        ]);
        $subMixtoAdmin = Subrubro::create([
            'rubro_id' => $rubroMixto->id,
            'nombre' => 'Reparación Estructural Mayor',
            'permitido_para' => 'ADMIN',
            'afecta_caja' => true,
            'activo' => true,
        ]);

        // Cajas
        $cajaSandra = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativoSandra->id,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'efectivo_inicial' => 10000,
            'apertura_at' => $hoy->copy()->setTime(8, 0),
            'estado' => 'ABIERTA',
        ]);
        $cajaMarcos = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativoMarcos->id,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'efectivo_inicial' => 10000,
            'apertura_at' => $hoy->copy()->subDay()->setTime(8, 0),
            'cierre_at' => $hoy->copy()->subDay()->setTime(14, 0),
            'estado' => 'CERRADA',
        ]);
        $cajaAdmin = CajaOperativa::create([
            'usuario_operativo_id' => $this->admin->id,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'efectivo_inicial' => 50000,
            'apertura_at' => $hoy->copy()->setTime(9, 0),
            'estado' => 'ABIERTA',
        ]);

        // Movimientos
        // 1. Sandra en rubro operativo
        $m1 = MovimientoOperativo::create([
            'caja_operativa_id' => $cajaSandra->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $subOperativoIng->id,
            'monto' => 15000,
            'fecha' => $hoy->toDateString(),
            'usuario_id' => $this->operativoSandra->id,
            'estado' => 'ACTIVO',
            'observaciones' => 'Movimiento Sandra Operativo',
        ]);
        // 2. Marcos (otro operativo) en rubro operativo
        $m2 = MovimientoOperativo::create([
            'caja_operativa_id' => $cajaMarcos->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $subOperativoEgr->id,
            'monto' => 4000,
            'fecha' => $hoy->toDateString(),
            'usuario_id' => $this->operativoMarcos->id,
            'estado' => 'ACTIVO',
            'observaciones' => 'Movimiento Marcos Operativo',
        ]);
        // 3. Admin en rubro de sueldos (admin)
        $m3 = MovimientoOperativo::create([
            'caja_operativa_id' => $cajaAdmin->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $subAdminSueldo->id,
            'monto' => 85000,
            'fecha' => $hoy->toDateString(),
            'usuario_id' => $this->admin->id,
            'estado' => 'ACTIVO',
            'observaciones' => 'Sueldo Profesor Laura',
        ]);
        // 4. Admin en rubro de alquileres (admin)
        $m4 = MovimientoOperativo::create([
            'caja_operativa_id' => $cajaAdmin->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $subAdminAlquiler->id,
            'monto' => 120000,
            'fecha' => $hoy->toDateString(),
            'usuario_id' => $this->admin->id,
            'estado' => 'ACTIVO',
            'observaciones' => 'Alquiler Cancha Mes Corriente',
        ]);
        // 5. En rubro mixto: subrubro operativo
        $m5 = MovimientoOperativo::create([
            'caja_operativa_id' => $cajaSandra->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $subMixtoOperativo->id,
            'monto' => 3500,
            'fecha' => $hoy->toDateString(),
            'usuario_id' => $this->operativoSandra->id,
            'estado' => 'ACTIVO',
            'observaciones' => 'Compra lavandina y trapos',
        ]);
        // 6. En rubro mixto: subrubro admin
        $m6 = MovimientoOperativo::create([
            'caja_operativa_id' => $cajaAdmin->id,
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'subrubro_id' => $subMixtoAdmin->id,
            'monto' => 95000,
            'fecha' => $hoy->toDateString(),
            'usuario_id' => $this->admin->id,
            'estado' => 'ACTIVO',
            'observaciones' => 'Reparación techo tinglado',
        ]);

        $resultadosA39 = [];

        // ── Entrando como Sandra (OPERATIVO) ──
        $responseSandra = $this->actingAs($this->operativoSandra)->get(route('web.movimientos.index'));
        $this->assertSame(200, $responseSandra->getStatusCode());
        $htmlSandra = $responseSandra->getContent();

        // 1. Menú tiene Movimientos y la pantalla abre
        $this->assertStringContainsString('Movimientos', $htmlSandra);
        $this->assertStringContainsString(route('web.movimientos.index'), $htmlSandra);

        // 2. Ve su movimiento (m1, m5) y el del compañero (m2)
        $this->assertStringContainsString('Movimiento Sandra Operativo', $htmlSandra);
        $this->assertStringContainsString('Movimiento Marcos Operativo', $htmlSandra);
        $this->assertStringContainsString('Compra lavandina y trapos', $htmlSandra);

        // 3. No ve los del admin (m3, m4, m6): ni fila ni importe
        $this->assertStringNotContainsString('Sueldo Profesor Laura', $htmlSandra);
        $this->assertStringNotContainsString('Alquiler Cancha Mes Corriente', $htmlSandra);
        $this->assertStringNotContainsString('Reparación techo tinglado', $htmlSandra);
        $this->assertStringNotContainsString('85.000', $htmlSandra);
        $this->assertStringNotContainsString('120.000', $htmlSandra);
        $this->assertStringNotContainsString('95.000', $htmlSandra);

        // 4. Totales calculados a mano:
        // Ingresos operativos: $15.000 (m1)
        // Egresos operativos: $4.000 (m2) + $3.500 (m5) = $7.500
        // Neto: $15.000 - $7.500 = $7.500
        // Los sueldos ($85k), alquiler ($120k) y reparación ($95k) NO deben sumarse.
        $totalIngresosSandra = $responseSandra->viewData('totalIngresos');
        $totalEgresosSandra = $responseSandra->viewData('totalEgresos');
        $this->assertEquals(15000, $totalIngresosSandra);
        $this->assertEquals(7500, $totalEgresosSandra);

        // 5. Desplegables de filtro: no listan rubros ni subrubros reservados al admin
        $rubrosSandra = $responseSandra->viewData('rubros')->pluck('nombre')->toArray();
        $subrubrosSandra = $responseSandra->viewData('subrubros')->pluck('nombre')->toArray();

        $this->assertContains('Cobranzas', $rubrosSandra);
        $this->assertContains('Gastos Mostrador', $rubrosSandra);
        $this->assertContains('Mantenimiento General', $rubrosSandra); // Mixto
        $this->assertNotContains('Sueldos Profesores', $rubrosSandra);
        $this->assertNotContains('Alquiler Cancha', $rubrosSandra);

        $this->assertContains('Cuotas Gimnasia', $subrubrosSandra);
        $this->assertContains('Librería y Papelería', $subrubrosSandra);
        $this->assertContains('Artículos de Limpieza', $subrubrosSandra);
        $this->assertNotContains('Sueldo Profesor Titular', $subrubrosSandra);
        $this->assertNotContains('Alquiler Predio Mensual', $subrubrosSandra);
        $this->assertNotContains('Reparación Estructural Mayor', $subrubrosSandra);

        // 6. Por GET directo: pedir con subrubro_id o rubro_id del admin
        $resFiltroSubrubroAdmin = $this->actingAs($this->operativoSandra)->get(route('web.movimientos.index', [
            'subrubro_id' => $subAdminSueldo->id,
        ]));
        $this->assertCount(0, $resFiltroSubrubroAdmin->viewData('movimientos'));
        $this->assertEquals(0, $resFiltroSubrubroAdmin->viewData('totalIngresos'));
        $this->assertEquals(0, $resFiltroSubrubroAdmin->viewData('totalEgresos'));

        $resFiltroRubroAdmin = $this->actingAs($this->operativoSandra)->get(route('web.movimientos.index', [
            'rubro_id' => $rubroAdminAlquiler->id,
        ]));
        $this->assertCount(0, $resFiltroRubroAdmin->viewData('movimientos'));

        // Combinado con fechas y tipo
        $resFiltroCombinado = $this->actingAs($this->operativoSandra)->get(route('web.movimientos.index', [
            'rubro_id' => $rubroAdminSueldo->id,
            'desde' => $hoy->copy()->subDays(5)->toDateString(),
            'hasta' => $hoy->toDateString(),
            'tipo' => 'EGRESO',
        ]));
        $this->assertCount(0, $resFiltroCombinado->viewData('movimientos'));

        // 7. Rubro mixto: Mantenimiento General
        // Sandra filtra por Mantenimiento General: solo debe ver m5 ($3.500), NUNCA m6 ($95.000)
        $resMixtoSandra = $this->actingAs($this->operativoSandra)->get(route('web.movimientos.index', [
            'rubro_id' => $rubroMixto->id,
        ]));
        $movsMixtos = $resMixtoSandra->viewData('movimientos');
        $this->assertCount(1, $movsMixtos);
        $this->assertSame('Compra lavandina y trapos', $movsMixtos->first()->observaciones);
        $this->assertEquals(3500, $resMixtoSandra->viewData('totalEgresos'));

        // ── Entrando como ADMIN: ve todo ──
        $responseAdmin = $this->actingAs($this->admin)->get(route('web.movimientos.index'));
        $movsAdmin = $responseAdmin->viewData('movimientos');
        $this->assertCount(6, $movsAdmin); // Ve los 6 movimientos
        $this->assertEquals(15000, $responseAdmin->viewData('totalIngresos'));
        // Egresos admin = 4000 + 85000 + 120000 + 3500 + 95000 = 307500
        $this->assertEquals(307500, $responseAdmin->viewData('totalEgresos'));
        $this->assertContains('Sueldos Profesores', $responseAdmin->viewData('rubros')->pluck('nombre')->toArray());

        // ── Entrando como PROFESOR: no tiene acceso en menú y la ruta lo rechaza ──
        $responseProfesor = $this->actingAs($this->profesor)->get(route('web.movimientos.index'));
        $this->assertSame(403, $responseProfesor->getStatusCode()); // Rechazado por reject.profesor.web

        // Guardar HTML del operativo para capturar
        $dirEvidencia = public_path('a26-a39-a35-evidencia');
        if (!is_dir($dirEvidencia)) mkdir($dirEvidencia, 0755, true);
        file_put_contents("{$dirEvidencia}/sandra-movimientos.html", $htmlSandra);

        $resultadosA39 = [
            'menu_tiene_movimientos' => true,
            've_sandra_propio' => true,
            've_marcos_companero' => true,
            'bloquea_sueldos_admin' => true,
            'bloquea_alquileres_admin' => true,
            'bloquea_reparacion_admin_en_rubro_mixto' => true,
            'total_ingresos_operativo_calculado' => $totalIngresosSandra,
            'total_egresos_operativo_calculado' => $totalEgresosSandra,
            'rubros_visibles_operativo' => $rubrosSandra,
            'subrubros_visibles_operativo' => $subrubrosSandra,
            'ataque_get_subrubro_admin_filas' => 0,
            'ataque_get_rubro_admin_filas' => 0,
            'admin_ve_total_filas' => $movsAdmin->count(),
            'admin_total_egresos' => $responseAdmin->viewData('totalEgresos'),
            'profesor_status' => $responseProfesor->getStatusCode(),
        ];

        file_put_contents(
            base_path('docs/06-pruebas/PRU-02/evidencia/verificacion-a26-a39-a35/resultados-a39.json'),
            json_encode($resultadosA39, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_verificacion_a35_auditoria_botones_caja(): void
    {
        $hoy = Carbon::now(self::TZ);

        // Crear una caja para que las pantallas no estén vacías
        CajaOperativa::create([
            'usuario_operativo_id' => $this->operativoSandra->id,
            'tipo_caja_efectivo_id' => $this->tipoEfectivo->id,
            'efectivo_inicial' => 10000,
            'apertura_at' => $hoy->copy()->setTime(8, 0),
            'estado' => 'ABIERTA',
        ]);

        $rutas = [
            'admin_caja' => route('web.caja.index'),
            'admin_historial' => route('web.caja.historial'),
            'operativo_caja' => route('web.caja.index'),
            'operativo_historial' => route('web.caja.historial'),
        ];

        $relevamientoBotones = [];

        foreach ([
            'ADMIN' => $this->admin,
            'OPERATIVO' => $this->operativoSandra,
        ] as $rol => $user) {
            // 1. Pantalla /caja
            $resCaja = $this->actingAs($user)->get(route('web.caja.index'));
            $this->assertSame(200, $resCaja->getStatusCode());
            $htmlCaja = $resCaja->getContent();
            $linksCaja = $this->extraerBotonesYLinks($htmlCaja);

            // 2. Pantalla /caja/historial
            $resHistorial = $this->actingAs($user)->get(route('web.caja.historial'));
            $this->assertSame(200, $resHistorial->getStatusCode());
            $htmlHistorial = $resHistorial->getContent();
            $linksHistorial = $this->extraerBotonesYLinks($htmlHistorial);

            $relevamientoBotones[$rol] = [
                'pantalla_caja_url' => route('web.caja.index'),
                'botones_en_caja' => $linksCaja,
                'pantalla_historial_url' => route('web.caja.historial'),
                'botones_en_historial' => $linksHistorial,
            ];
        }

        file_put_contents(
            base_path('docs/06-pruebas/PRU-02/evidencia/verificacion-a26-a39-a35/resultados-a35.json'),
            json_encode($relevamientoBotones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->assertTrue(true);
    }

    private function extraerBotonesYLinks(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        $items = [];

        // Buscar enlaces <a>
        $nodes = $xpath->query('//a[@href]');
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            $texto = trim(preg_replace('/\s+/', ' ', $node->textContent));
            // Filtrar links vacíos o del sidebar general
            if (!empty($texto) && !empty($href)) {
                $items[] = [
                    'tipo' => 'link',
                    'texto' => $texto,
                    'href' => $href,
                ];
            }
        }

        // Buscar botones submit <button>
        $btnNodes = $xpath->query('//button');
        foreach ($btnNodes as $node) {
            $texto = trim(preg_replace('/\s+/', ' ', $node->textContent));
            $type = $node->getAttribute('type') ?: 'button';
            if (!empty($texto)) {
                $items[] = [
                    'tipo' => 'button',
                    'texto' => $texto,
                    'button_type' => $type,
                ];
            }
        }

        return $items;
    }
}
