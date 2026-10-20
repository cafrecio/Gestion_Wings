<?php

namespace Tests\Feature;

use App\Models\{Deporte, Grupo, GrupoPlan, Nivel, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CapturaT50Test extends TestCase
{
    use RefreshDatabase;

    private array $archivos = [];

    protected function tearDown(): void
    {
        foreach ($this->archivos as $archivo) {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }
        parent::tearDown();
    }

    public function test_genera_html_para_capturas_t50(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-20 10:00:00');

        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $deporte = Deporte::first();
        $nivel = Nivel::first();
        $grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::firstOrCreate([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 25000,
            'activo' => true,
        ]);

        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t50');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $publicBuild = 'file:///' . str_replace('\\', '/', public_path('build')) . '/';
        $publicImg = 'file:///' . str_replace('\\', '/', public_path('img')) . '/';

        // 1. Alta de alumno con fecha_alta fuera de límite (2026-12-01 > 2026-11-30)
        $postResponse = $this->actingAs($admin)
            ->from(route('web.alumnos.create'))
            ->post(route('web.alumnos.store'), [
                'nombre' => 'Lucas',
                'apellido' => 'Benítez',
                'dni' => '45123987',
                'fecha_nacimiento' => '2008-05-10',
                'fecha_alta' => '2026-12-01',
                'deporte_id' => $deporte->id,
                'grupo_id' => $grupo->id,
                'plan_id' => $plan->id,
                'celular' => '11-4455-6677',
            ]);
        $postResponse->assertRedirect(route('web.alumnos.create'));

        $htmlAlta = $this->actingAs($admin)->get(route('web.alumnos.create'))->assertOk()->getContent();
        $this->assertStringContainsString('La fecha de ingreso no puede ser posterior al 30/11/2026.', $htmlAlta);

        $htmlAlta = str_replace(
            ['src="http://gestion-wings/build/', 'href="http://gestion-wings/build/', 'src="/build/', 'href="/build/', 'src="http://localhost/build/', 'href="http://localhost/build/'],
            ['src="' . $publicBuild, 'href="' . $publicBuild, 'src="' . $publicBuild, 'href="' . $publicBuild, 'src="' . $publicBuild, 'href="' . $publicBuild],
            $htmlAlta
        );
        $htmlAlta = str_replace(
            ['src="http://gestion-wings/img/', 'src="/img/', 'src="http://localhost/img/'],
            ['src="' . $publicImg, 'src="' . $publicImg, 'src="' . $publicImg],
            $htmlAlta
        );
        file_put_contents($dir . '/alumno-alta-error.html', $htmlAlta);

        // 2. Primera carga Excel con fila fuera de límite (fila 3 con fecha 01/12/2026)
        if (DB::getSchemaBuilder()->hasTable('primera_carga')) {
            DB::table('primera_carga')->where('id', 1)->update(['estado' => 'PENDIENTE', 'detalle' => null]);
        }

        $ejemploPath = base_path('docs/05-pendientes/maqueta-primera-carga/ejemplos/club-corregido.xlsx');
        $libro = IOFactory::load($ejemploPath);
        $hoja = $libro->getSheetByName('Alumnos');
        // Columna E (5) es fecha_alta en la plantilla
        $hoja->setCellValue('E3', '01/12/2026');
        $tempExcel = tempnam(sys_get_temp_dir(), 'p1-t50-') . '.xlsx';
        $this->archivos[] = $tempExcel;
        (new Xlsx($libro))->save($tempExcel);

        $uploaded = new UploadedFile($tempExcel, 'club-t50-futuro.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $postExcel = $this->actingAs($admin)
            ->post(route('web.primera-carga.revisar'), ['archivo' => $uploaded]);
        $postExcel->assertRedirect(route('web.primera-carga.index'));

        $htmlExcel = $this->actingAs($admin)->get(route('web.primera-carga.index'))->assertOk()->getContent();
        $this->assertStringContainsString('La fecha de ingreso no puede ser posterior al 30/11/2026.', $htmlExcel);

        $htmlExcel = str_replace(
            ['src="http://gestion-wings/build/', 'href="http://gestion-wings/build/', 'src="/build/', 'href="/build/', 'src="http://localhost/build/', 'href="http://localhost/build/'],
            ['src="' . $publicBuild, 'href="' . $publicBuild, 'src="' . $publicBuild, 'href="' . $publicBuild, 'src="' . $publicBuild, 'href="' . $publicBuild],
            $htmlExcel
        );
        $htmlExcel = str_replace(
            ['src="http://gestion-wings/img/', 'src="/img/', 'src="http://localhost/img/'],
            ['src="' . $publicImg, 'src="' . $publicImg, 'src="' . $publicImg],
            $htmlExcel
        );
        file_put_contents($dir . '/primera-carga-error.html', $htmlExcel);

        // 3. Crear envoltorios marco-375.html
        $marcoAlta = '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Alta Alumno Error · a375</title></head><body style="margin:0;background:#eef2f6"><iframe title="Alta Alumno a375" src="alumno-alta-error.html" style="display:block;width:375px;height:1200px;border:0;margin:20px auto;box-shadow:0 4px 12px rgba(0,0,0,0.15);background:#fff"></iframe></body></html>';
        file_put_contents($dir . '/alumno-alta-error-marco-375.html', $marcoAlta);

        $marcoExcel = '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Primera Carga Error · a375</title></head><body style="margin:0;background:#eef2f6"><iframe title="Primera Carga a375" src="primera-carga-error.html" style="display:block;width:375px;height:1200px;border:0;margin:20px auto;box-shadow:0 4px 12px rgba(0,0,0,0.15);background:#fff"></iframe></body></html>';
        file_put_contents($dir . '/primera-carga-error-marco-375.html', $marcoExcel);

        $this->assertTrue(file_exists($dir . '/alumno-alta-error.html'));
        $this->assertTrue(file_exists($dir . '/primera-carga-error.html'));
    }
}
