<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, Asistencia, CajaOperativa, CashflowMovimiento, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, Profesor, Rubro, Subrubro, TipoCaja, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T12/T13 etapa C (Claude CAB, 30/10/2026). No es una prueba de la suite: guarda el HTML
 * real que devuelve Wings en cada pantalla del manual, con el usuario del rol de la
 * página, y recorre los caminos prohibidos de cada «Qué hago si». Lo único que se toca
 * del HTML es mostrar un panel que en pantalla abre un botón (display:none → visible) y
 * ocultar con CSS secciones de arriba para que la captura muestre la parte del paso.
 * Nunca se agrega texto. Correr solo con DB_DATABASE=wings_testing_claude.
 */
class CapturasEtapaCTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;
    private array $prohibidos = [];
    private User $admin, $operativa, $profe;
    private Deporte $patin, $futbol;
    private Grupo $escuelita, $competencia;
    private GrupoPlan $plan2;
    private Profesor $lucia;
    private TipoCaja $efectivo, $banco;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00'));
        $this->dir = base_path('docs/06-pruebas/PRU-04/evidencia-t12-t13/etapa-c/html');
        File::ensureDirectoryExists($this->dir);
        $this->seed(CatalogosSeeder::class);
        foreach (['reporte_cobertura', 'reporte_analitico_cobertura'] as $t) {
            if (\Schema::hasTable($t)) \DB::table($t)->where('id', 1)->update(['desde' => '2026-08-01 00:00:00']);
        }
        $this->admin = User::factory()->create(['name' => 'Laura Gómez', 'email' => 'duena@club.test', 'rol' => 'ADMIN', 'activo' => true]);
        $this->operativa = User::factory()->create(['name' => 'Sandra Ríos', 'email' => 'mostrador@club.test', 'rol' => 'OPERATIVO', 'activo' => true]);
    }

    public function test_capturas_y_caminos_prohibidos_de_la_etapa_c(): void
    {
        $this->armarClub();
        $this->capturasDelDuenio();
        $this->capturasDeLaOperativa();
        $this->capturasDeLaProfesora();
        File::put(dirname($this->dir) . '/caminos-prohibidos.json', json_encode($this->prohibidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->assertNotEmpty($this->prohibidos);
    }

    // ── Datos ──────────────────────────────────────────────────────────────
    private function armarClub(): void
    {
        $this->actingAs($this->admin);
        $this->efectivo = TipoCaja::create(['nombre' => 'Efectivo', 'abreviatura' => 'EFT', 'saldo_inicial' => 80000, 'activo' => true]);
        $this->banco = TipoCaja::create(['nombre' => 'Banco del club', 'abreviatura' => 'BCO', 'saldo_inicial' => 350000, 'activo' => true]);
        $this->post(route('web.caja.configuracion.store'), ['tipo_caja_id' => $this->efectivo->id])->assertSessionHas('success');
        $this->get(route('web.caja.index'));
        $this->guardar('admin/configurar-mostrador-paso1', $this->get(route('web.caja.configuracion')));

        $this->patin = Deporte::updateOrCreate(['nombre' => 'Patín'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->futbol = Deporte::create(['nombre' => 'Vóley', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nEsc = Nivel::firstOrCreate(['nombre' => 'Escuelita'], ['descripcion' => 'Alumnos que inician la actividad']);
        $nComp = Nivel::firstOrCreate(['nombre' => 'Competencia']);
        $this->post(route('web.grupos.store'), ['deporte_id' => $this->patin->id, 'nivel_id' => $nEsc->id,
            'planes' => [['clases_por_semana' => 2, 'precio_mensual' => '28000'], ['clases_por_semana' => 3, 'precio_mensual' => '34000']]])->assertSessionHas('success');
        $this->post(route('web.grupos.store'), ['deporte_id' => $this->patin->id, 'nivel_id' => $nComp->id,
            'planes' => [['clases_por_semana' => 3, 'precio_mensual' => '39000']]])->assertSessionHas('success');
        $this->escuelita = Grupo::where('nivel_id', $nEsc->id)->firstOrFail();
        $this->competencia = Grupo::where('nivel_id', $nComp->id)->firstOrFail();
        $this->plan2 = GrupoPlan::where('grupo_id', $this->escuelita->id)->where('clases_por_semana', 2)->firstOrFail();

        $this->post(route('web.profesores.store'), ['nombre' => 'Lucía', 'apellido' => 'Méndez', 'deporte_id' => $this->patin->id, 'dni' => '28999888',
            'fecha_nacimiento' => '1988-04-12', 'direccion' => 'Av. San Martín 1234', 'localidad' => 'Rosario', 'telefono' => '3415556677', 'valor_hora' => '4500'])->assertSessionHas('success');
        $this->lucia = Profesor::where('dni', '28999888')->firstOrFail();
        $this->post(route('web.usuarios.store'), ['name' => 'Lucía Méndez', 'email' => 'lucia@club.test', 'password' => 'clave-segura-1234',
            'password_confirmation' => 'clave-segura-1234', 'rol' => 'PROFESOR', 'profesor_id' => $this->lucia->id])->assertSessionHas('success');
        $this->profe = User::where('email', 'lucia@club.test')->firstOrFail();

        $nombres = [['Sofía', 'Acosta'], ['Martina', 'Benítez'], ['Valentina', 'Castro'], ['Emma', 'Díaz'], ['Julia', 'Ferreyra']];
        foreach ($nombres as $i => [$n, $a]) {
            $al = Alumno::create(['nombre' => $n, 'apellido' => $a, 'dni' => '4700000' . $i, 'fecha_nacimiento' => '2014-0' . ($i + 1) . '-10',
                'celular' => '341000000' . $i, 'fecha_alta' => '2026-08-01', 'deporte_id' => $this->patin->id, 'grupo_id' => $this->escuelita->id, 'activo' => true]);
            AlumnoPlan::create(['alumno_id' => $al->id, 'plan_id' => $this->plan2->id, 'fecha_desde' => '2026-08-01', 'activo' => true]);
            DeudaCuota::create(['alumno_id' => $al->id, 'periodo' => '2026-10', 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        }
        // Emma debe septiembre: queda Deudor.
        DeudaCuota::create(['alumno_id' => Alumno::where('nombre', 'Emma')->value('id'), 'periodo' => '2026-09', 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);

        $ayer = Clase::create(['grupo_id' => $this->escuelita->id, 'fecha' => '2026-10-29', 'hora_inicio' => '18:00', 'hora_fin' => '19:00', 'cancelada' => false, 'validada_para_liquidacion' => false]);
        $sinLista = Clase::create(['grupo_id' => $this->escuelita->id, 'fecha' => '2026-10-27', 'hora_inicio' => '18:00', 'hora_fin' => '19:00', 'cancelada' => false, 'validada_para_liquidacion' => false]);
        $hoy = Clase::create(['grupo_id' => $this->escuelita->id, 'fecha' => '2026-10-30', 'hora_inicio' => '18:00', 'hora_fin' => '19:00', 'cancelada' => false, 'validada_para_liquidacion' => false]);
        foreach ([$ayer, $sinLista, $hoy] as $c) $c->profesores()->attach($this->lucia->id);
        $this->claseAyer = $ayer; $this->claseHoy = $hoy;

        // La profesora toma la lista de ayer (primera carga: sin motivo).
        $items = Alumno::where('grupo_id', $this->escuelita->id)->where('nombre', '!=', 'Emma')->get()
            ->map(fn ($a) => ['alumno_id' => $a->id, 'presente' => $a->nombre !== 'Julia'])->all();
        $this->actingAs($this->profe)->postJson(route('web.clases.asistencias', $ayer->id), ['items' => $items])->assertOk();

        // Serie de clases futuras de Competencia (camino real de Nuevo).
        $this->actingAs($this->admin)->post(route('web.clases.store'), ['tipo_creacion' => 'recurrente', 'fecha_desde' => '2026-11-02', 'fecha_hasta' => '2026-11-27',
            'dias_semana' => [1, 3], 'horarios' => [1 => ['hora_inicio' => '19:00', 'hora_fin' => '20:00'], 3 => ['hora_inicio' => '19:00', 'hora_fin' => '20:00']],
            'grupo_id' => $this->competencia->id, 'profesores' => [$this->lucia->id]])->assertSessionHas('success');

        // Caja abierta de la operativa y un movimiento directo de Cashflow.
        CajaDeclarada::crear($this->operativa->id, $this->efectivo->id)->update(['efectivo_inicial' => 15000]);
        $limpieza = Subrubro::where('nombre', 'Limpieza')->firstOrFail();
        $this->actingAs($this->admin)->post(route('web.cashflow.movimiento.store'), ['tipo' => 'EGRESO', 'tipo_caja_id' => $this->banco->id, 'subrubro_id' => $limpieza->id,
            'rubro_id' => $limpieza->rubro_id, 'monto' => '18500', 'fecha' => '2026-10-28', 'observaciones' => 'Artículos de limpieza del gimnasio']);
        $this->movimiento = CashflowMovimiento::where('observaciones', 'Artículos de limpieza del gimnasio')->first();
    }

    private Clase $claseAyer, $claseHoy;
    private ?CashflowMovimiento $movimiento = null;

    // ── Dueño ──────────────────────────────────────────────────────────────
    private function capturasDelDuenio(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('admin.dashboard')); // consume el aviso del último paso de armarClub
        $this->guardar('admin/administrar-deportes-paso1', $this->get(route('web.deportes.create')));
        $r = $this->followingRedirects()->put(route('web.deportes.update', $this->futbol->id), ['nombre' => 'Vóley', 'tipo_liquidacion' => 'COMISION']);
        $this->guardar('admin/administrar-deportes-paso2', $r);
        $this->prohibido('Desactivar un deporte con alumnos activos', $this->patch(route('web.deportes.toggle-activo', $this->patin->id)));

        $this->guardar('admin/administrar-niveles-paso1', $this->get(route('web.niveles.create')));
        $this->guardar('admin/administrar-niveles-paso2', $this->get(route('web.niveles.index')));
        $this->prohibido('Eliminar un nivel que usa un grupo', $this->delete(route('web.niveles.destroy', $this->escuelita->nivel_id)));

        $this->guardar('admin/administrar-grupos-paso1', $this->get(route('web.grupos.create')));
        $this->guardar('admin/administrar-grupos-paso2', $this->get(route('web.grupos.edit', $this->escuelita->id)));
        $plan3 = GrupoPlan::where('grupo_id', $this->escuelita->id)->where('clases_por_semana', 3)->firstOrFail();
        $r = $this->put(route('web.grupos.update', $this->escuelita->id), ['planes' => [['id' => $plan3->id, 'clases_por_semana' => 3, 'precio_mensual' => '34000']]]);
        $this->prohibido('Sacar del grupo una frecuencia con alumnos activos', $r, 'La frecuencia de 2 veces sigue: ' . (GrupoPlan::whereKey($this->plan2->id)->exists() ? 'sí' : 'no'));
        $this->prohibido('Desactivar un grupo con alumnos activos', $this->patch(route('web.grupos.toggle-activo', $this->escuelita->id)));

        $this->guardar('admin/administrar-profesores-paso1', $this->get(route('web.profesores.create')), ['field-valor-hora']);
        $r = $this->post(route('web.profesores.store'), ['nombre' => 'Prueba', 'apellido' => 'Sin tarifa', 'deporte_id' => $this->patin->id, 'dni' => '30111222',
            'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Calle 1', 'localidad' => 'Rosario', 'telefono' => '341']);
        $this->prohibido('Guardar un profesor sin valor por hora (el formulario lo marca obligatorio)', $r, 'Se guardó: ' . (Profesor::where('dni', '30111222')->exists() ? 'sí' : 'no'));
        $this->prohibido('Pasar a inactiva a una profesora con clases futuras', $this->patch(route('web.profesores.toggle-activo', $this->lucia->id)));

        $gastos = Rubro::where('nombre', 'Gastos Operativos')->firstOrFail();
        $this->guardar('admin/administrar-rubros-paso1', $this->get(route('web.subrubros.create', $gastos->id)));
        $this->guardar('admin/administrar-rubros-paso2', $this->get(route('web.rubros.index')));
        $this->prohibido('Eliminar un rubro con subrubros', $this->delete(route('web.rubros.destroy', $gastos->id)));

        $this->guardar('admin/administrar-tipos-caja-paso1', $this->get(route('web.tipos-caja.create')));
        $this->prohibido('Desactivar la cuenta del cajón del mostrador', $this->patch(route('web.tipos-caja.toggle-activo', $this->efectivo->id)));
        $this->prohibido('Editar una cuenta con movimientos: ¿se ofrece el saldo inicial?', $this->get(route('web.tipos-caja.edit', $this->banco->id)),
            'Campo saldo_inicial en el formulario: ' . (str_contains((string) $this->get(route('web.tipos-caja.edit', $this->banco->id))->getContent(), 'name="saldo_inicial"') ? 'sí' : 'no'));

        $sofia = Alumno::where('nombre', 'Sofía')->firstOrFail();
        $deuda = DeudaCuota::where('alumno_id', $sofia->id)->where('periodo', '2026-10')->firstOrFail();
        $this->guardar('admin/condonar-deuda-alumno-paso1', $this->get(route('web.alumnos.show', $sofia->id)), [], ['id="modal-condonar" style="display:none;' => 'id="modal-condonar" style="display:flex;']);
        $this->prohibido('Condonar con un motivo corto', $this->post(route('web.deudas.condonar', $deuda->id), ['motivo' => 'beca']));
        $this->prohibido('Eliminar un alumno con asistencias', $this->delete(route('web.alumnos.destroy', $sofia->id)));

        $nuevo = Alumno::create(['nombre' => 'Tomás', 'apellido' => 'Cargado Por Error', 'dni' => '47999999', 'fecha_nacimiento' => '2015-05-05', 'celular' => '3410000099',
            'fecha_alta' => '2026-10-30', 'deporte_id' => $this->patin->id, 'grupo_id' => $this->competencia->id, 'activo' => true]);
        $this->guardar('admin/eliminar-alumno-paso1', $this->get(route('web.alumnos.show', $nuevo->id)), ['panel-eliminar-alumno']);

        $this->guardar('admin/configurar-parametros-paso1', $this->get(route('web.configuraciones.index')), [], [], 1500);
        $this->guardar('admin/configurar-parametros-paso2', $this->get(route('web.configuraciones.index')), ['panel-add-regla'], [],
            900, 'section[aria-labelledby="configuracion-plata"],section[aria-labelledby="configuracion-cobranza"] > article:nth-of-type(-n+4){display:none !important}');
        $this->prohibido('Guardar reglas de primera cuota que dejan días sin cubrir', $this->putJson(route('web.config.primer-pago.guardar-todas'),
            ['reglas' => [['nombre' => 'Segunda quincena', 'porcentaje' => 50, 'dia_desde' => 16, 'dia_hasta' => 31]]]));

        $this->guardar('admin/administrar-usuarios-paso1', $this->get(route('web.usuarios.create')));
        $this->prohibido('Desactivarse a uno mismo', $this->patch(route('web.usuarios.toggle-activo', $this->admin->id)));
        $this->prohibido('Desactivar a la operativa con su caja abierta', $this->patch(route('web.usuarios.toggle-activo', $this->operativa->id)));

        $this->guardar('admin/reportes-ingresos-egresos-paso1', $this->get(route('web.reportes.index')), [], [], 1200);
        $this->guardar('admin/reportes-alumnos-paso1', $this->get(route('web.reportes.alumnos')), [], [], 1200);
        $this->guardar('admin/reportes-sueldos-paso1', $this->get(route('web.reportes.sueldos')), [], [], 1200);
        $this->prohibido('Pedir un reporte de un mes futuro', $this->get(route('web.reportes.index', ['mes' => '2026-12'])));

        $this->guardar('admin/programar-clases-paso1', $this->get(route('web.clases.create')));
        $futura = Clase::where('grupo_id', $this->competencia->id)->orderBy('fecha')->firstOrFail();
        $this->guardar('admin/programar-clases-paso2', $this->get(route('web.clases.show', $futura->id)));
        $this->prohibido('Eliminar una clase con asistencias', $this->delete(route('web.clases.destroy', $this->claseAyer->id)));
        $this->prohibido('Cambiar la fecha de una clase pasada', $this->put(route('web.clases.update', $this->claseAyer->id), ['fecha' => '2026-10-31',
            'hora_inicio' => '18:00', 'hora_fin' => '19:00', 'profesores' => [$this->lucia->id]]));

        $this->prohibido('Cambiar el medio del mostrador con una caja abierta', $this->post(route('web.caja.configuracion.store'), ['tipo_caja_id' => $this->banco->id]));
        $this->prohibido('Volver a la configuración del mostrador desde Caja una vez configurado', $this->get(route('web.caja.index')),
            'Botón Configurar en Caja: ' . (str_contains((string) $this->get(route('web.caja.index'))->getContent(), '>Configurar<') ? 'sí' : 'no'));

        $this->guardar('admin/inicio-dueno-paso1', $this->get(route('admin.dashboard')), [], [], 1300);
        if ($this->movimiento) {
            $this->guardar('admin/corregir-movimiento-cashflow-paso1', $this->get(route('web.cashflow.movimientos.show', $this->movimiento->id)));
            $this->guardar('admin/corregir-movimiento-cashflow-paso2', $this->get(route('web.cashflow.movimientos.eliminar', $this->movimiento->id)));
        } else {
            $this->prohibido('No se pudo crear el movimiento directo de Cashflow', $this->get(route('web.cashflow.index')));
        }
    }

    // ── Operativa ──────────────────────────────────────────────────────────
    private function capturasDeLaOperativa(): void
    {
        $this->actingAs($this->operativa);
        $this->guardar('operativo/inicio-operativa-paso1', $this->get(route('web.operativo.dashboard')), [], [], 1200);
    }

    // ── Profesora ──────────────────────────────────────────────────────────
    private function capturasDeLaProfesora(): void
    {
        $this->actingAs($this->profe);
        $this->guardar('profesor/consultar-mis-clases-paso1', $this->get(route('web.clases.index')));
        $this->guardar('profesor/corregir-asistencia-anterior-paso1', $this->get(route('web.clases.show', $this->claseAyer->id)), [], [], 1200);
        $this->guardar('profesor/clases-sin-asistencia-paso1', $this->get(route('web.clases.index', ['estado' => 'finalizada'])));
        $this->guardar('profesor/alumnos-de-mi-clase-paso1', $this->get(route('web.clases.show', $this->claseHoy->id)), [], [], 1200);
        $items = Alumno::where('grupo_id', $this->escuelita->id)->where('nombre', '!=', 'Emma')->get()->map(fn ($a) => ['alumno_id' => $a->id, 'presente' => true])->all();
        $this->prohibido('Corregir la lista de ayer sin motivo', $this->postJson(route('web.clases.asistencias', $this->claseAyer->id), ['items' => $items]));
        $emma = Alumno::where('nombre', 'Emma')->firstOrFail();
        $this->prohibido('Marcar presente sin motivo a una alumna nueva (nunca pagó) en Deudor con menos de 2 presentes: no se pide (pedido directo)', $this->postJson(route('web.clases.asistencias', $this->claseHoy->id),
            ['items' => [['alumno_id' => $emma->id, 'presente' => true]]]));
        // Con historia previa (primera carga terminada que la incluye) ya no es nueva: Deudor pide motivo.
        \App\Models\PrimeraCarga::updateOrCreate(['id' => 1], ['estado' => 'TERMINADA', 'detalle' => ['alumnos' => [$emma->id], 'cargos' => [], 'deudas' => [], 'planes' => [], 'personas_nuevas' => []]]);
        $this->prohibido('Alumna con historia en Deudor: marcarla ausente no pide motivo (pedido directo)', $this->postJson(route('web.clases.asistencias', $this->claseHoy->id),
            ['items' => [['alumno_id' => $emma->id, 'presente' => false]]]));
        $this->prohibido('Alumna con historia en Deudor: marcarla presente sin motivo (pedido directo, sin la pantalla)', $this->postJson(route('web.clases.asistencias', $this->claseHoy->id),
            ['items' => [['alumno_id' => $emma->id, 'presente' => true]]]));
        $this->prohibido('La profesora intenta cancelar una clase', $this->patch(route('web.clases.toggle-cancelada', $this->claseHoy->id), ['motivo_cancelacion' => 'Lluvia']));
    }

    // ── Ayudas ─────────────────────────────────────────────────────────────
    private function guardar(string $nombre, $respuesta, array $mostrar = [], array $reemplazos = [], int $alto = 900, string $css = ''): void
    {
        $this->assertSame(200, $respuesta->getStatusCode(), "$nombre respondió {$respuesta->getStatusCode()}");
        $html = (string) $respuesta->getContent();
        $build = 'file:///' . str_replace('\\', '/', public_path('build')) . '/';
        $img = 'file:///' . str_replace('\\', '/', public_path('img')) . '/';
        $html = preg_replace('#https?://[^/"\']+/build/#', $build, $html);
        $html = preg_replace('#https?://[^/"\']+/img/#', $img, $html);
        $html = str_replace(['src="/build/', 'href="/build/', 'src="/img/', 'href="/img/'], ['src="' . $build, 'href="' . $build, 'src="' . $img, 'href="' . $img], $html);
        foreach ($mostrar as $id) {
            $html = preg_replace('#(id="' . preg_quote($id, '#') . '"[^>]*?)style="display:\s*none;?#', '$1style="', $html, 1, $hecho);
            $this->assertSame(1, $hecho, "$nombre: no se encontró el panel $id");
        }
        foreach ($reemplazos as $de => $a) {
            $this->assertStringContainsString($de, $html, "$nombre: no se encontró $de");
            $html = str_replace($de, $a, $html);
        }
        if ($css) $html = str_replace('</head>', "<style>$css</style></head>", $html);
        File::ensureDirectoryExists(dirname("{$this->dir}/$nombre.html"));
        File::put("{$this->dir}/$nombre.html", $html);
        File::put("{$this->dir}/$nombre.alto", (string) $alto);
    }

    private function prohibido(string $caso, $respuesta, ?string $observado = null): void
    {
        $sesion = $respuesta->baseResponse->headers->get('content-type') && str_contains($respuesta->baseResponse->headers->get('content-type'), 'json')
            ? json_decode((string) $respuesta->getContent(), true) : null;
        $this->prohibidos[] = [
            'caso' => $caso,
            'http' => $respuesta->getStatusCode(),
            'error' => session('error') ?? ($sesion['error'] ?? $sesion['message'] ?? null) ?? (session('errors')?->first() ?: null),
            'success' => session('success'),
            'observado' => $observado,
        ];
        session()->forget(['error', 'success', 'errors']);
    }
}
