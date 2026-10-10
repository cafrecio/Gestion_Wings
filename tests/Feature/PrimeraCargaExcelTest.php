<?php

namespace Tests\Feature;

use App\Models\{Alumno, Deporte, Grupo, GrupoPlan, Nivel, Pago, User};
use App\Services\PrimeraCargaExcelService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PrimeraCargaExcelTest extends TestCase
{
    use RefreshDatabase;

    private array $archivos = [];
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:00:00');
        Storage::fake('local');
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        foreach ([['Fútbol', 'Juveniles', 48000], ['Patín', 'Inicial', 52000]] as [$nombre, $nivel, $precio]) {
            $deporte = Deporte::create(['nombre' => $nombre, 'tipo_liquidacion' => 'HORA', 'activo' => true]);
            $n = Nivel::firstOrCreate(['nombre' => $nivel]);
            $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $n->id, 'activo' => true]);
            GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => $precio, 'activo' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('primera_carga')) {
            DB::table('primera_carga')->where('id', 1)->update(['estado' => 'PENDIENTE', 'detalle' => null]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->archivos as $archivo) if (is_file($archivo)) unlink($archivo);
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function ejemplo(string $nombre = 'club-corregido.xlsx'): string
    {
        return base_path('docs/05-pendientes/maqueta-primera-carga/ejemplos/'.$nombre);
    }

    private function modificado(callable $editar): string
    {
        $libro = IOFactory::load($this->ejemplo());
        $editar($libro->getSheetByName('Alumnos'));
        $archivo = tempnam(sys_get_temp_dir(), 'p1-');
        $this->archivos[] = $archivo;
        (new Xlsx($libro))->save($archivo);
        return $archivo;
    }

    public function test_revision_devuelve_los_seis_errores_juntos_y_no_escribe(): void
    {
        $resultado = app(PrimeraCargaExcelService::class)->revisar($this->ejemplo('club-con-errores.xlsx'));
        $this->assertCount(6, $resultado['errores']);
        $this->assertSame(['J3', 'K3', 'L3', 'I4', 'O5', 'P5'], array_column($resultado['errores'], 'celda'));
        foreach ($resultado['errores'] as $error) {
            $this->assertNotEmpty($error['esperado']);
            $this->assertNotEmpty($error['columna']);
        }
        $this->assertDatabaseCount('alumnos', 0);
        $this->assertDatabaseCount('deuda_cuotas', 0);
        $this->assertDatabaseCount('cargos_alumno', 0);
        $this->assertSame('PENDIENTE', DB::table('primera_carga')->value('estado'));
    }

    public function test_excel_marcado_conserva_celdas_y_agrega_errores_en_am(): void
    {
        $service = app(PrimeraCargaExcelService::class);
        $archivo = $this->ejemplo('club-con-errores.xlsx');
        $resultado = $service->revisar($archivo);
        $destino = tempnam(sys_get_temp_dir(), 'p1-informe-');
        $this->archivos[] = $destino;
        $service->guardarInforme($archivo, $resultado['errores'], $destino);
        $marcado = IOFactory::load($destino);
        $original = IOFactory::load($archivo)->getSheetByName('Alumnos');
        $hoja = $marcado->getSheetByName('Alumnos');
        for ($r = 1; $r <= 5; $r++) for ($c = 1; $c <= 38; $c++) {
            $this->assertSame($original->getCell([$c, $r])->getValue(), $hoja->getCell([$c, $r])->getValue());
            $this->assertSame($original->getCell([$c, $r])->getDataType(), $hoja->getCell([$c, $r])->getDataType());
        }
        $this->assertSame('Errores', (string) $hoja->getCell('AM1')->getValue());
        $this->assertStringContainsString('Deporte', (string) $hoja->getCell('AM3')->getValue());
        $this->assertStringContainsString('Monto 1', (string) $hoja->getCell('AM5')->getValue());
        $this->assertCount(6, $service->revisar($destino)['errores']);
    }

    public function test_plantilla_vacia_tres_hojas_listas_reales_y_38_columnas(): void
    {
        $libro = app(PrimeraCargaExcelService::class)->plantilla();
        $this->assertSame(['Alumnos', 'Catálogos', 'Guía'], $libro->getSheetNames());
        $hoja = $libro->getSheetByName('Alumnos');
        $this->assertSame('Monto 12', $hoja->getCell('AL1')->getValue());
        $this->assertNull($hoja->getCell('A2')->getValue());
        $this->assertNull($hoja->getCell('O2')->getValue());
        $this->assertSame('No', $hoja->getCell('M2')->getValue());
        $this->assertSame('No', $hoja->getCell('N201')->getValue());
        foreach (['J2', 'K2', 'L2', 'M2', 'N2'] as $celda) {
            $this->assertSame('list', $hoja->getCell($celda)->getDataValidation()->getType());
        }
        $this->assertStringContainsString('Fútbol', json_encode($libro->getSheetByName('Catálogos')->toArray(), JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString('FutbolX', json_encode($libro->getSheetByName('Catálogos')->toArray()));
        $archivo = tempnam(sys_get_temp_dir(), 'p1-');
        $this->archivos[] = $archivo;
        (new Xlsx($libro))->save($archivo);
        $this->assertNotEmpty(app(PrimeraCargaExcelService::class)->revisar($archivo)['errores']);
        $this->assertDatabaseCount('alumnos', 0);
    }

    public function test_carga_correcta_exactamente_lo_declarado_sin_descuentos_ni_caja(): void
    {
        $service = app(PrimeraCargaExcelService::class);
        $revision = $service->revisar($this->ejemplo());
        $this->assertSame([], $revision['errores']);
        $this->assertSame(['alumnos' => 4, 'cuotas' => 6, 'inscripciones' => 1, 'cuotas_monto' => '296000.00', 'inscripcion_monto' => '5000.00', 'total' => '301000.00'], $revision['resumen']);
        $service->cargar($this->ejemplo(), $this->admin->id);
        $this->assertDatabaseCount('alumnos', 4);
        $this->assertDatabaseCount('alumno_planes', 4);
        $this->assertDatabaseCount('deuda_cuotas', 6);
        $this->assertDatabaseCount('cargos_alumno', 1);
        $this->assertEquals(296000, DB::table('deuda_cuotas')->sum('monto_original'));
        $this->assertDatabaseHas('alumnos', ['dni' => '50300001']);
        $this->assertDatabaseHas('deuda_cuotas', ['periodo' => '2026-09', 'monto_original' => 52000, 'monto_pagado' => 0, 'porcentaje_alta' => 100]);
        foreach (['pagos', 'movimientos_operativos', 'cashflow_movimientos'] as $tabla) $this->assertDatabaseCount($tabla, 0);
        $this->assertSame('TERMINADA', DB::table('primera_carga')->value('estado'));
    }

    public function test_grupo_y_plan_inexistentes_o_de_otro_deporte_rechazan(): void
    {
        foreach ([['K2', 'No existe'], ['L2', 'No existe'], ['K2', 'Inicial'], ['L2', 'Patín / Inicial / 2 clases']] as [$celda, $valor]) {
            $archivo = $this->modificado(fn ($h) => $h->setCellValue($celda, $valor));
            $this->assertNotEmpty(app(PrimeraCargaExcelService::class)->cargar($archivo, $this->admin->id)['errores']);
            $this->assertDatabaseCount('alumnos', 0);
            $this->assertDatabaseCount('grupos', 2);
            $this->assertDatabaseCount('grupo_planes', 2);
        }
    }

    public function test_error_ultima_fila_no_deja_filas_anteriores(): void
    {
        $archivo = $this->modificado(fn ($h) => $h->setCellValue('P5', '52x000'));
        $this->assertNotEmpty(app(PrimeraCargaExcelService::class)->cargar($archivo, $this->admin->id)['errores']);
        foreach (['alumnos', 'alumno_planes', 'cargos_alumno', 'deuda_cuotas'] as $tabla) $this->assertDatabaseCount($tabla, 0);
    }

    public function test_respuestas_independientes_y_pares_completos_sin_repetidos(): void
    {
        $casos = [
            fn ($h) => $h->setCellValue('N2', 'No'),
            fn ($h) => $h->setCellValue('N3', 'Sí'),
            fn ($h) => $h->setCellValue('Q2', '102026')->setCellValue('R2', 1000),
            fn ($h) => $h->setCellValue('P2', null),
        ];
        foreach ($casos as $editar) {
            $this->assertNotEmpty(app(PrimeraCargaExcelService::class)->revisar($this->modificado($editar))['errores']);
        }
        $archivo = $this->modificado(fn ($h) => $h->setCellValue('N2', 'No')->setCellValue('O2', null)->setCellValue('P2', null));
        $resultado = app(PrimeraCargaExcelService::class)->cargar($archivo, $this->admin->id);
        $this->assertSame([], $resultado['errores']);
        $this->assertDatabaseCount('cargos_alumno', 1);
        $this->assertDatabaseCount('deuda_cuotas', 5);
    }

    public function test_mismo_dni_en_dos_deportes_inscripcion_unica_y_duplicado_rechazado(): void
    {
        $archivo = $this->modificado(function ($h) {
            foreach (range(1, 9) as $c) $h->setCellValue([$c, 4], $h->getCell([$c, 2])->getValue());
            $h->setCellValue('M4', 'Sí')->setCellValue('E4', '01/09/2026');
        });
        $this->assertSame([], app(PrimeraCargaExcelService::class)->cargar($archivo, $this->admin->id)['errores']);
        $this->assertDatabaseCount('cargos_alumno', 1);
        app(PrimeraCargaExcelService::class)->deshacer($this->admin->id);
        $archivo = $this->modificado(function ($h) {
            for ($c = 1; $c <= 38; $c++) $h->setCellValue([$c, 3], $h->getCell([$c, 2])->getValue());
        });
        $this->assertNotEmpty(app(PrimeraCargaExcelService::class)->revisar($archivo)['errores']);
        $this->assertDatabaseCount('alumnos', 0);
    }

    public function test_deshacer_sin_cobros_conserva_usuarios_catalogos_y_rehabilita_carga(): void
    {
        $service = app(PrimeraCargaExcelService::class);
        $service->cargar($this->ejemplo(), $this->admin->id);
        $service->deshacer($this->admin->id);
        foreach (['alumnos', 'alumno_planes', 'deuda_cuotas', 'cargos_alumno', 'cargo_alumno_eventos', 'inscripcion_personas'] as $tabla) $this->assertDatabaseCount($tabla, 0);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
        $this->assertDatabaseCount('grupo_planes', 2);
        $this->assertSame('PENDIENTE', DB::table('primera_carga')->value('estado'));
        $this->assertSame([], $service->cargar($this->ejemplo(), $this->admin->id)['errores']);
    }

    public function test_deshacer_rechaza_cobro_incluso_anulado_y_conserva_todo(): void
    {
        $service = app(PrimeraCargaExcelService::class);
        $service->cargar($this->ejemplo(), $this->admin->id);
        Pago::create(['alumno_id' => Alumno::first()->id, 'mes' => 10, 'anio' => 2026, 'monto_base' => 1000, 'porcentaje_aplicado' => 100, 'monto_final' => 1000, 'fecha_pago' => now(), 'estado' => 'ANULADO']);
        try {
            $service->deshacer($this->admin->id);
            $this->fail('Deshacer debió rechazar un cobro existente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('cobro', $e->getMessage());
        }
        $this->assertDatabaseCount('alumnos', 4);
        $this->assertDatabaseCount('deuda_cuotas', 6);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_carga_duplicada_rechazada_y_rollback_por_fallo_durante_escritura(): void
    {
        $service = app(PrimeraCargaExcelService::class);
        $dispatcher = Alumno::getEventDispatcher();
        Alumno::setEventDispatcher(clone $dispatcher);
        Alumno::creating(function ($alumno) { if ($alumno->nombre === 'Diego') throw new \RuntimeException('Fallo simulado en última fila'); });
        try {
            $service->cargar($this->ejemplo(), $this->admin->id);
            $this->fail('Faltó simular fallo.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo simulado en última fila', $e->getMessage());
        } finally {
            Alumno::setEventDispatcher($dispatcher);
        }
        foreach (['alumnos', 'deuda_cuotas', 'cargos_alumno'] as $tabla) $this->assertDatabaseCount($tabla, 0);
        $service->cargar($this->ejemplo(), $this->admin->id);
        $this->assertNotEmpty($service->cargar($this->ejemplo(), $this->admin->id)['errores']);
        $this->assertDatabaseCount('alumnos', 4);
    }

    public function test_encabezados_y_formulas_invalidos_no_se_ignoran(): void
    {
        $archivo = $this->modificado(fn ($h) => $h->setCellValue('P5', '=48000'));
        $this->assertNotEmpty(app(PrimeraCargaExcelService::class)->revisar($archivo)['errores']);
        $archivo = $this->modificado(fn ($h) => $h->setCellValue('AL1', 'Otra cosa'));
        $this->assertNotEmpty(app(PrimeraCargaExcelService::class)->revisar($archivo)['errores']);
        $ilegible = tempnam(sys_get_temp_dir(), 'p1-ilegible-');
        try {
            $service = app(PrimeraCargaExcelService::class);
            $revision = $service->revisar($ilegible);
            $this->assertStringContainsString('leer', $revision['errores'][0]['mensaje']);
            try { $service->marcarErrores($ilegible, $revision['errores']); $this->fail('Archivo ilegible debe dar mensaje, no un informe falso.'); }
            catch (\Illuminate\Validation\ValidationException $e) { $this->assertStringContainsString('ilegible', $e->getMessage()); }
        } finally { unlink($ilegible); }
    }

    public function test_entrada_automatica_y_alta_directa_no_saltean_estado_pendiente(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertRedirect('/sistema/primera-carga');
        // Reportes está en el menú; al tocarlo con la carga pendiente tiene que decir por qué vuelve acá.
        $this->get('/reportes')->assertRedirect('/sistema/primera-carga')->assertSessionHas('error');
        $this->followingRedirects()->get('/reportes')->assertOk()->assertSee('Primero terminá la primera carga de alumnos');
        $this->get('/alumnos/create')->assertRedirect('/sistema/primera-carga');
        $this->post('/alumnos', [])->assertRedirect('/sistema/primera-carga');
        $this->get('/grupos')->assertOk();
        $this->get('/configuraciones')->assertOk();
        Alumno::create(['dni' => '90000000', 'nombre' => 'Previo', 'apellido' => 'No saltea', 'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => now(), 'celular' => '1100000000', 'deporte_id' => Deporte::first()->id, 'grupo_id' => Grupo::first()->id]);
        $this->get('/alumnos/create')->assertRedirect('/sistema/primera-carga');
    }

    public function test_primera_carga_admin_exclusivo_en_todas_las_acciones(): void
    {
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $user = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $this->actingAs($user)->get('/sistema/primera-carga')->assertForbidden();
            $this->get('/sistema/primera-carga/plantilla')->assertForbidden();
            $this->get('/sistema/primera-carga/informe')->assertForbidden();
            $this->post('/sistema/primera-carga/continuar')->assertForbidden();
            $this->post('/sistema/primera-carga/revisar')->assertForbidden();
            $this->post('/sistema/primera-carga/cargar')->assertForbidden();
            $this->post('/sistema/primera-carga/deshacer')->assertForbidden();
        }
    }

    public function test_carga_web_exige_revision_previa_y_no_admite_payload_inventado(): void
    {
        $this->actingAs($this->admin)->post('/sistema/primera-carga/cargar', ['alumnos' => [['dni' => '12345678']]])->assertSessionHasErrors();
        $this->assertDatabaseCount('alumnos', 0);
        $archivo = new UploadedFile($this->ejemplo(), 'club.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->post('/sistema/primera-carga/revisar', ['archivo' => $archivo])->assertRedirect('/sistema/primera-carga');
        $this->assertDatabaseCount('alumnos', 0);
        $this->post('/sistema/primera-carga/cargar', ['confirmar' => '1'])->assertRedirect('/sistema/primera-carga');
        $this->assertDatabaseCount('alumnos', 4);
        $this->get('/sistema/primera-carga')->assertOk()->assertSee('Deshacer')->assertDontSee('name="archivo"', false);
        $this->get('/alumnos')->assertOk()->assertDontSee('Primera carga</a>', false);
    }

    public function test_revision_con_errores_no_habilita_carga_y_descarga_informe_privado(): void
    {
        $archivo = new UploadedFile($this->ejemplo('club-con-errores.xlsx'), 'club.xlsx', null, null, true);
        $this->actingAs($this->admin)->post('/sistema/primera-carga/revisar', ['archivo' => $archivo])->assertRedirect();
        $this->get('/sistema/primera-carga')->assertSee('6 errores')->assertSee('Teléfono tutor');
        $this->get('/sistema/primera-carga/informe')->assertOk()->assertDownload('club-con-errores-revisado.xlsx');
        $this->post('/sistema/primera-carga/cargar', ['confirmar' => '1'])->assertSessionHasErrors();
        $this->assertDatabaseCount('alumnos', 0);
    }

    public function test_formatos_de_excel_y_duodecimo_par_se_importan_sin_perder_importe(): void
    {
        $archivo = $this->modificado(function ($h) {
            $h->setCellValueExplicit('A4', '50 300 003', DataType::TYPE_STRING);
            $h->setCellValue('J4', 'Patín ')->setCellValue('O4', 92026);
            $h->setCellValueExplicit('P4', '52.000', DataType::TYPE_STRING);
            $h->setCellValue('AK3', '082026')->setCellValue('AL3', 1000)->setCellValue('N3', 'Sí');
        });
        $resultado = app(PrimeraCargaExcelService::class)->cargar($archivo, $this->admin->id);
        $this->assertSame([], $resultado['errores']);
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => Alumno::where('dni', '50300003')->value('id'), 'periodo' => '2026-09', 'monto_original' => 52000]);
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => Alumno::where('nombre', 'Bruno')->value('id'), 'periodo' => '2026-08', 'monto_original' => 1000]);
        $this->assertDatabaseCount('deuda_cuotas', 7);
    }

    public function test_plan_inactivo_o_sin_precio_no_entra_y_cambio_tras_revision_rechaza(): void
    {
        $service = app(PrimeraCargaExcelService::class);
        $revision = $service->revisar($this->ejemplo());
        foreach ([['activo' => false], ['activo' => true, 'precio_mensual' => 0]] as $cambio) {
            GrupoPlan::where('precio_mensual', 48000)->orWhere('precio_mensual', 0)->update($cambio);
            $this->assertNotEmpty($service->cargar($this->ejemplo(), $this->admin->id, $revision['resumen'])['errores']);
            $this->assertDatabaseCount('alumnos', 0);
        }
    }

    public function test_revision_no_habilita_carga_si_otro_admin_o_archivo_modificado(): void
    {
        $archivo = new UploadedFile($this->ejemplo(), 'club.xlsx', null, null, true);
        $this->actingAs($this->admin)->post('/sistema/primera-carga/revisar', ['archivo' => $archivo])->assertRedirect();
        $revision = session('primera_carga_revision');
        Storage::disk('local')->put($revision['path'], 'Archivo sustituido');
        $this->post('/sistema/primera-carga/cargar', ['confirmar' => '1'])->assertSessionHasErrors('archivo');
        $otro = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($otro)->get('/sistema/primera-carga/informe')->assertSessionHasErrors('archivo');
        $this->assertDatabaseCount('alumnos', 0);
    }

    public function test_deshacer_no_borra_deudas_nuevas_creadas_despues_de_importar(): void
    {
        $service = app(PrimeraCargaExcelService::class);
        $service->cargar($this->ejemplo(), $this->admin->id);
        DB::table('deuda_cuotas')->insert(['alumno_id' => Alumno::first()->id, 'periodo' => '2026-11', 'monto_original' => 100, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        try { $service->deshacer($this->admin->id); $this->fail('No debe borrar actividad nueva.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertStringContainsString('posteriores', $e->getMessage()); }
        $this->assertDatabaseCount('alumnos', 4);
        $this->assertDatabaseCount('deuda_cuotas', 7);
        $this->assertSame('TERMINADA', DB::table('primera_carga')->value('estado'));
    }
}
