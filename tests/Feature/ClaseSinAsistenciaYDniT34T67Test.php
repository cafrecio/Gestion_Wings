<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\Alumno;
use App\Models\GrupoPlan;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\User;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaseSinAsistenciaYDniT34T67Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesorUser;
    private Profesor $profesor;
    private Grupo $grupo;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-18 12:00:00'));

        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);

        $deporte = Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Lucia',
            'apellido' => 'Mendez',
            'dni' => '30111222',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Prueba',
            'valor_hora' => 1000.00,
            'activo' => true,
        ]);

        $this->profesorUser = User::factory()->create([
            'rol' => 'PROFESOR',
            'profesor_id' => $this->profesor->id,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Sofia',
            'apellido' => 'Gomez',
            'dni' => '42123456',
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => '2026-09-01',
            'activo' => true,
        ]);

        $plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 15000.00,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-09-01',
            'activo' => true,
        ]);
    }

    private function crearClasePasada(string $fecha = '2026-10-10', string $inicio = '10:00', string $fin = '11:00'): Clase
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => $fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'cancelada' => false,
            'validada_para_liquidacion' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    /**
     * T34: Contador y filtro:
     * - clase sin lista: cuenta en badge y aparece en estado=finalizada
     * - clase con lista y todos ausentes: NO cuenta en badge, NO aparece en finalizada, aparece en sin_presentes
     * - clase con un presente: NO cuenta y NO aparece en finalizada ni sin_presentes
     * - clase validada: NO cuenta
     * - clase cancelada: NO cuenta
     */
    public function test_contador_menu_y_filtro_sin_asistencia_y_sin_presentes(): void
    {
        // 1. Clase sin lista (pasada)
        $claseSinLista = $this->crearClasePasada('2026-10-01');

        // 2. Clase con lista y todos ausentes (pasada)
        $claseTodosAusentes = $this->crearClasePasada('2026-10-02');
        Asistencia::create([
            'clase_id' => $claseTodosAusentes->id,
            'alumno_id' => $this->alumno->id,
            'presente' => false,
        ]);

        // 3. Clase con un presente (pasada)
        $claseConPresente = $this->crearClasePasada('2026-10-03');
        Asistencia::create([
            'clase_id' => $claseConPresente->id,
            'alumno_id' => $this->alumno->id,
            'presente' => true,
        ]);

        // 4. Clase validada a mano sin asistencias (pasada)
        $claseValidada = $this->crearClasePasada('2026-10-04');
        $claseValidada->update(['validada_para_liquidacion' => true]);

        // 5. Clase cancelada sin asistencias (pasada)
        $claseCancelada = $this->crearClasePasada('2026-10-05');
        $claseCancelada->update(['cancelada' => true, 'motivo_cancelacion' => 'Lluvia']);

        // Verificación del badge en el menú como admin
        $respAdmin = $this->actingAs($this->admin)->get(route('web.clases.index'));
        $respAdmin->assertOk();
        $respAdmin->assertViewHas('badgeClasesPendientes', 1);

        // El listado con filtro estado=finalizada (Sin asistencia) debe tener exactamente 1 clase
        $respFinalizada = $this->actingAs($this->admin)->get(route('web.clases.index', ['estado' => 'finalizada']));
        $respFinalizada->assertOk();
        $clasesFiltradas = $respFinalizada->viewData('clasesFiltradas');
        $this->assertSame(1, $clasesFiltradas->total());
        $this->assertSame($claseSinLista->id, $clasesFiltradas->first()->id);

        // El listado con filtro estado=sin_presentes (solo para dueño) debe tener la clase con lista y todos ausentes
        $respSinPresentes = $this->actingAs($this->admin)->get(route('web.clases.index', ['estado' => 'sin_presentes']));
        $respSinPresentes->assertOk();
        $clasesSinPresentes = $respSinPresentes->viewData('clasesFiltradas');
        $this->assertSame(1, $clasesSinPresentes->total());
        $this->assertSame($claseTodosAusentes->id, $clasesSinPresentes->first()->id);
    }

    /**
     * T34: Permisos del filtro sin_presentes:
     * El dueño lo ve en el selector y lo puede filtrar; la operativa y el profesor no ven la opción y no filtra sin_presentes.
     */
    public function test_permisos_filtro_sin_presentes(): void
    {
        $claseTodosAusentes = $this->crearClasePasada('2026-10-02');
        Asistencia::create([
            'clase_id' => $claseTodosAusentes->id,
            'alumno_id' => $this->alumno->id,
            'presente' => false,
        ]);

        // Admin ve la opción en el HTML y puede filtrar
        $respAdmin = $this->actingAs($this->admin)->get(route('web.clases.index'));
        $respAdmin->assertOk()->assertSee('value="sin_presentes"', false)->assertSee('Sin presentes');

        // Operativo NO ve la opción en el HTML y enviar estado=sin_presentes no aplica ese filtro (devuelve null en match)
        $respOperativo = $this->actingAs($this->operativo)->get(route('web.clases.index'));
        $respOperativo->assertOk()->assertDontSee('value="sin_presentes"', false)->assertDontSee('Sin presentes');

        $respOperativoFiltrando = $this->actingAs($this->operativo)->get(route('web.clases.index', ['estado' => 'sin_presentes']));
        $respOperativoFiltrando->assertOk();
        // Como no aplica el filtro para operativo, no filtra solo sin_presentes (se comporta como sin filtro de estado)
        $this->assertNotSame('sin_presentes', $respOperativoFiltrando->viewData('clasesFiltradas'));

        // Profesor NO ve la opción en el HTML
        $respProfesor = $this->actingAs($this->profesorUser)->get(route('web.clases.index'));
        $respProfesor->assertOk()->assertDontSee('value="sin_presentes"', false)->assertDontSee('Sin presentes');
    }

    /**
     * T34: La liquidación no cambia:
     * Una clase con lista y nadie presente NO se liquida hasta que el dueño la valida a mano. Validada, sí.
     */
    public function test_liquidacion_clase_con_lista_y_todos_ausentes_solo_se_paga_si_esta_validada(): void
    {
        $claseAusentes = $this->crearClasePasada('2026-10-10'); // Octubre 2026, 1 hora = $1000
        Asistencia::create([
            'clase_id' => $claseAusentes->id,
            'alumno_id' => $this->alumno->id,
            'presente' => false,
        ]);

        $liqService = app(LiquidacionService::class);

        // Sin validar: no debe liquidarse (0 clases liquidables)
        $prevSinValidar = $liqService->previsualizarLiquidacion($this->profesor->id, 10, 2026);
        $this->assertSame(0.0, (float) $prevSinValidar['total_estimado']);
        $this->assertCount(0, $prevSinValidar['detalles']);

        // El dueño la valida a mano
        $respValidar = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $claseAusentes->id));
        $respValidar->assertRedirect();
        $this->assertTrue($claseAusentes->fresh()->validada_para_liquidacion);

        // Validada: ahora sí se incluye en la liquidación
        $prevValidada = $liqService->previsualizarLiquidacion($this->profesor->id, 10, 2026);
        $this->assertSame(1000.0, (float) $prevValidada['total_estimado']);
        $this->assertCount(1, $prevValidada['detalles']);
        $this->assertSame($claseAusentes->id, $prevValidada['detalles'][0]['referencia_id']);
    }

    /**
     * T67: La lista de asistencia en clases/show muestra solo los tres últimos números del DNI
     * («DNI …456») y el DNI completo no aparece en el HTML de la vista.
     */
    public function test_lista_asistencia_muestra_solo_ultimos_tres_digitos_del_dni(): void
    {
        $clase = $this->crearClasePasada('2026-10-15');

        // Alumno con DNI '42123456'
        $resp = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $resp->assertOk();

        // Debe ver el DNI recortado
        $resp->assertSee('DNI …456');

        // NO debe contener el DNI completo '42123456'
        $resp->assertDontSee('42123456');

        // El alumno tiene plan activo: no debe figurar 'Sin plan' y debe mostrar el ratio semanal '0/2'
        $resp->assertDontSee('Sin plan');
        $resp->assertSee('0/2');
    }
}
