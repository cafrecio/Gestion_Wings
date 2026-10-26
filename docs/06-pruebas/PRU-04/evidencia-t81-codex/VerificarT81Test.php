<?php

namespace Tests\VerificacionT81Codex;

use App\Models\{Alumno, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, User};
use App\Services\{PrimeraCargaExcelService, ReciboService};
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

/** Verificación independiente, fuera del contador de pruebas permanentes. */
class VerificarT81Test extends TestCase
{
    use RefreshDatabase;

    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('wings_testing_codex', config('database.connections.mysql.database'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-25 15:00:00');
        $deporte = Deporte::create(['nombre' => 'Patín T81', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial T81', 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function alumno(string $nombre, string $dni, ?string $celular, ?string $tutor): Alumno
    {
        return Alumno::create(['nombre' => $nombre, 'apellido' => 'Control T81', 'dni' => $dni,
            'fecha_nacimiento' => '2015-01-01', 'fecha_alta' => '2026-01-01', 'celular' => $celular ?? '',
            'nombre_tutor' => 'Tutor de prueba', 'telefono_tutor' => $tutor,
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        return new DOMXPath($document);
    }

    private function texto(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    public function test_accessor_prioriza_conserva_formato_y_no_modifica_datos(): void
    {
        foreach ([
            ['+54 9 11-1234-5678', '011-8765-4321', '+54 9 11-1234-5678'],
            [null, '011-8765-4321', '011-8765-4321'],
            ['', '011-8765-4321', '011-8765-4321'],
            ['   ', '011-8765-4321', '011-8765-4321'],
            [null, null, null], ['', '', null], ['   ', '  ', null],
        ] as [$celular, $tutor, $expected]) {
            $alumno = new Alumno(['celular' => $celular, 'telefono_tutor' => $tutor]);
            $before = $alumno->getAttributes();
            $this->assertSame($expected, $alumno->telefono_contacto);
            $this->assertSame($before, $alumno->getAttributes());
            $this->assertArrayNotHasKey('telefono_contacto', $alumno->toArray());
        }
    }

    public function test_tres_casos_por_rol_y_cobranza_igual_a_la_vista_anterior(): void
    {
        $fixtures = [
            $this->alumno('Propio', '58181001', '+54 9 11-1234-5678', '011-8765-4321'),
            $this->alumno('Tutor', '58181002', null, '011-8765-4321'),
            $this->alumno('Vacio', '58181003', null, null),
        ];
        foreach ($fixtures as $index => $alumno) {
            DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-09', 'monto_original' => 20000 + $index * 1000,
                'monto_pagado' => $index * 100, 'estado' => 'PENDIENTE']);
        }
        $beforeDb = DB::table('alumnos')->orderBy('id')->get()->toJson();
        $beforeDeuda = DB::table('deuda_cuotas')->orderBy('id')->get()->toJson();
        foreach ([User::ROL_ADMIN, User::ROL_OPERATIVO] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol, 'activo' => true]));
            $lista = $this->get(route('web.alumnos.index'))->assertOk();
            $cobranza = $this->get(route('web.cobranza.index'))->assertOk();
            $data = $cobranza->getOriginalContent()->getData();
            $this->assertCount(3, $data['alumnos']);
            $old = view()->file(__DIR__.'/cobranza-antes-t81.blade.php', $data)->render();
            $new = $cobranza->getContent();
            preg_match_all('/\$[\d,.]+/u', $old, $oldMoney);
            preg_match_all('/\$[\d,.]+/u', $new, $newMoney);
            $this->assertNotEmpty($oldMoney[0]);
            $this->assertSame($oldMoney[0], $newMoney[0], 'Todos los importes renderizados deben coincidir con el padre de T81');
            $oldXPath = $this->xpath($old);
            $newXPath = $this->xpath($new);
            $this->assertSame($oldXPath->query('//main//tbody/tr')->length, $newXPath->query('//main//tbody/tr')->length);
            foreach ($oldXPath->query('//main//tbody/tr') as $i => $oldRow) {
                $newRow = $newXPath->query('//main//tbody/tr')->item($i);
                $this->assertSame($oldXPath->query('./td', $oldRow)->length, $newXPath->query('./td', $newRow)->length);
                foreach ($oldXPath->query('./td[position()>1]', $oldRow) as $j => $oldCell) {
                    $newCell = $newXPath->query('./td[position()>1]', $newRow)->item($j);
                    $this->assertSame($this->texto($oldCell->textContent), $this->texto($newCell->textContent));
                }
            }
            $cards = $this->xpath($lista->getContent());
            foreach ($fixtures as $i => $alumno) {
                $phone = ['+54 9 11-1234-5678', '011-8765-4321', ''][$i];
                $row = $newXPath->query('//main//tbody/tr[td[contains(., "'.$alumno->nombre.'")]]');
                $this->assertSame(1, $row->length);
                $cell = $newXPath->query('./td[1]', $row->item(0))->item(0);
                $this->assertSame('Control T81, '.$alumno->nombre.($phone ? ' '.$phone : ''), $this->texto($cell->textContent));
                $this->assertStringNotContainsString($alumno->dni, $cell->textContent);
                $phoneCell = $cards->query('//span[contains(@class,"info-label") and normalize-space(.)="Teléfono:"]/following-sibling::span[1]')->item($i);
                $this->assertSame($phone ?: '–', trim($phoneCell->textContent));
            }
            $cobranza->assertDontSee('Sin teléfono');
        }
        $this->assertSame($beforeDb, DB::table('alumnos')->orderBy('id')->get()->toJson());
        $this->assertSame($beforeDeuda, DB::table('deuda_cuotas')->orderBy('id')->get()->toJson());
    }

    public function test_busqueda_dni_y_autocomplete_discriminan_sin_perder_dni_en_la_ficha(): void
    {
        $target = $this->alumno('Objetivo', '58182001', null, '011-8765-4321');
        $this->alumno('Distinto', '59199002', '+54 9 11-1234-5678', null);
        foreach ([User::ROL_ADMIN, User::ROL_OPERATIVO] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol, 'activo' => true]));
            $list = $this->get(route('web.alumnos.index', ['search' => $target->dni]))->assertOk();
            $this->assertSame([$target->id], $list->getOriginalContent()->getData()['alumnos']->pluck('id')->all());
            $autocomplete = $this->getJson(route('web.alumnos.autocomplete', ['q' => '58182']))->assertOk();
            $this->assertCount(1, $autocomplete->json());
            $this->assertSame($target->id, $autocomplete->json('0.id'));
            $this->assertStringContainsString($target->dni, $autocomplete->json('0.sub'));
            $this->get(route('web.alumnos.show', $target->id))->assertOk()->assertSee($target->dni)->assertSee('011-8765-4321');
        }
    }

    public function test_serializacion_recibo_real_y_plantilla_excel_conservan_dni_y_campos_originales(): void
    {
        Storage::fake();
        $alumno = $this->alumno('Recibo', '58183001', null, '011-8765-4321');
        $serialized = $alumno->toArray();
        $this->assertSame('58183001', $serialized['dni']);
        $this->assertSame('', $serialized['celular']);
        $this->assertSame('011-8765-4321', $serialized['telefono_tutor']);
        $this->assertArrayNotHasKey('telefono_contacto', $serialized);
        $pago = Pago::create(['alumno_id' => $alumno->id, 'plan_id' => $this->plan->id, 'mes' => 10, 'anio' => 2026,
            'monto_base' => 20000, 'porcentaje_aplicado' => 100, 'monto_final' => 20000,
            'fecha_pago' => '2026-10-25', 'estado' => Pago::ESTADO_COMPLETADO]);
        $ruta = app(ReciboService::class)->generarReciboCuota($pago->id, true);
        $bytes = Storage::get($ruta);
        $this->assertStringStartsWith('%PDF-', $bytes);
        file_put_contents(__DIR__.'/recibo-contacto.pdf', $bytes);
        $book = app(PrimeraCargaExcelService::class)->plantilla();
        $sheet = $book->getSheetByName('Alumnos');
        $this->assertSame('DNI', $sheet->getCell('A1')->getValue());
        $this->assertSame('Celular', $sheet->getCell('F1')->getValue());
        $this->assertSame('Teléfono tutor', $sheet->getCell('I1')->getValue());
        $this->assertSame(['Alumnos', 'Catálogos', 'Guía'], $book->getSheetNames());
        $book->disconnectWorksheets();
    }
}
