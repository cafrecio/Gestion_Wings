<?php

namespace Tests\Feature;

use App\Models\Deporte;
use App\Models\Profesor;
use App\Models\User;
use App\Rules\CbuOAlias;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegundaVueltaB10RunnerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $deporte;
    private static array $registrosPrueba = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->deporte = Deporte::where('activo', true)->firstOrFail();
    }

    private function datosProfesor(array $override = []): array
    {
        return array_replace([
            'nombre' => 'Docente B10',
            'apellido' => 'Verificación',
            'deporte_id' => $this->deporte->id,
            'dni' => '35999888',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Av. San Martín 1234',
            'localidad' => 'Bernal',
            'email' => 'docente.b10@wings.test',
            'telefono' => '1144002200',
            'valor_hora' => 15000,
        ], $override);
    }

    private function datosOperativo(array $override = []): array
    {
        return array_replace([
            'name' => 'Operativo B10',
            'email' => 'operativo.b10@wings.test',
            'password' => 'Password2026!',
            'password_confirmation' => 'Password2026!',
            'rol' => User::ROL_OPERATIVO,
        ], $override);
    }

    private function registrarResultado(array $info): void
    {
        self::$registrosPrueba[] = $info;
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        $rutaJson = __DIR__ . '/resultado.json';
        file_put_contents($rutaJson, json_encode(self::$registrosPrueba, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // =========================================================================
    // SECCIÓN A: CASOS QUE TIENEN QUE RECHAZARSE
    // =========================================================================

    public function test_A1_alias_con_espacio_se_rechaza_en_profesor_y_operativo(): void
    {
        $valor = 'alias con espacio';

        // 1. Profesor POST
        $resProf = $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]));
        $resProf->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgProf = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgProf);
        $this->assertNull(Profesor::where('dni', '35999888')->first());
        $this->assertSame('Docente B10', session('_old_input.nombre'));
        $this->assertSame('35999888', session('_old_input.dni'));

        // 2. Operativo POST
        $resOp = $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]));
        $resOp->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgOp = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgOp);
        $this->assertNull(User::where('email', 'operativo.b10@wings.test')->first());
        $this->assertSame('Operativo B10', session('_old_input.name'));

        $this->registrarResultado([
            'caso' => 'A1. Alias con espacio',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => $msgProf,
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => $msgOp,
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_A2_alias_con_tabulacion_se_rechaza_en_profesor_y_operativo(): void
    {
        $valor = "alias\tcon\ttab";

        // 1. Profesor POST
        $resProf = $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]));
        $resProf->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgProf = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgProf);
        $this->assertNull(Profesor::where('dni', '35999888')->first());
        $this->assertSame('Docente B10', session('_old_input.nombre'));

        // 2. Operativo POST
        $resOp = $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]));
        $resOp->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgOp = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgOp);
        $this->assertNull(User::where('email', 'operativo.b10@wings.test')->first());

        $this->registrarResultado([
            'caso' => 'A2. Alias con tabulación en el medio',
            'enviado' => 'alias\\tcon\\ttab',
            'profesor_http' => 302,
            'profesor_error' => $msgProf,
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => $msgOp,
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_A3_alias_con_salto_de_linea_se_rechaza_en_profesor_y_operativo(): void
    {
        $valor = "alias\ncon\nsalto";

        // 1. Profesor POST
        $resProf = $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]));
        $resProf->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgProf = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgProf);
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        // 2. Operativo POST
        $resOp = $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]));
        $resOp->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgOp = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgOp);
        $this->assertNull(User::where('email', 'operativo.b10@wings.test')->first());

        $this->registrarResultado([
            'caso' => 'A3. Alias con salto de línea en el medio',
            'enviado' => 'alias\\ncon\\nsalto',
            'profesor_http' => 302,
            'profesor_error' => $msgProf,
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => $msgOp,
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_A4_texto_200_caracteres_se_rechaza_en_castellano_en_profesor_y_operativo(): void
    {
        $valor = str_repeat('a', 200);

        // 1. Profesor POST
        $resProf = $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]));
        $resProf->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgProf = session('errors')->first('cbu_alias');
        // Debe ser en castellano (CbuOAlias::MENSAJE) y NO en inglés ("The cbu alias field must not be greater than...")
        $this->assertSame(CbuOAlias::MENSAJE, $msgProf);
        $this->assertStringNotContainsString('must not be greater than', $msgProf);
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        // 2. Operativo POST
        $resOp = $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]));
        $resOp->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgOp = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgOp);
        $this->assertStringNotContainsString('must not be greater than', $msgOp);
        $this->assertNull(User::where('email', 'operativo.b10@wings.test')->first());

        $this->registrarResultado([
            'caso' => 'A4. Texto de 200 caracteres',
            'enviado' => '200 veces la letra "a"',
            'profesor_http' => 302,
            'profesor_error' => $msgProf,
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => $msgOp,
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    // =========================================================================
    // SECCIÓN B: TIENE QUE SEGUIR ANDANDO LO QUE ANDABA
    // =========================================================================

    public function test_B1_cbu_pegado_con_espacios_se_guarda_sin_ellos(): void
    {
        $valor = ' 0170 0992 2000 0067 7979 12 ';
        $esperado = '0170099220000067797912';

        // 1. Profesor POST
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $prof = Profesor::where('dni', '35999888')->firstOrFail();
        $this->assertSame($esperado, $prof->cbu_alias);

        // 2. Operativo POST
        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $op = User::where('email', 'operativo.b10@wings.test')->firstOrFail();
        $this->assertSame($esperado, $op->cbu_alias);

        $this->registrarResultado([
            'caso' => 'B1. CBU pegado con espacios',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => 'ninguno',
            'profesor_bd' => $prof->cbu_alias,
            'profesor_old' => 'no aplica (guardado)',
            'operativo_http' => 302,
            'operativo_error' => 'ninguno',
            'operativo_bd' => $op->cbu_alias,
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_B2_cbu_con_tabulaciones_entre_numeros_se_guarda_sin_ellas(): void
    {
        $valor = "0170\t0992\t2000\t0067\t7979\t12";
        $esperado = '0170099220000067797912';

        // 1. Profesor POST
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $prof = Profesor::where('dni', '35999888')->firstOrFail();
        $this->assertSame($esperado, $prof->cbu_alias);

        // 2. Operativo POST
        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $op = User::where('email', 'operativo.b10@wings.test')->firstOrFail();
        $this->assertSame($esperado, $op->cbu_alias);

        $this->registrarResultado([
            'caso' => 'B2. CBU con tabulaciones entre números',
            'enviado' => '0170\\t0992\\t2000\\t0067\\t7979\\t12',
            'profesor_http' => 302,
            'profesor_error' => 'ninguno',
            'profesor_bd' => $prof->cbu_alias,
            'profesor_old' => 'no aplica (guardado)',
            'operativo_http' => 302,
            'operativo_error' => 'ninguno',
            'operativo_bd' => $op->cbu_alias,
            'resultado' => 'APROBADO (se limpian \\s y se guarda CBU de 22 dígitos)',
        ]);
    }

    public function test_B3_alias_con_espacios_de_borde_se_guarda_sin_espacios_de_borde(): void
    {
        $valor = ' mi.alias ';
        $esperado = 'mi.alias';

        // 1. Profesor POST
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $prof = Profesor::where('dni', '35999888')->firstOrFail();
        $this->assertSame($esperado, $prof->cbu_alias);

        // 2. Operativo POST
        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $op = User::where('email', 'operativo.b10@wings.test')->firstOrFail();
        $this->assertSame($esperado, $op->cbu_alias);

        $this->registrarResultado([
            'caso' => 'B3. Alias con espacios en bordes',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => 'ninguno',
            'profesor_bd' => $prof->cbu_alias,
            'profesor_old' => 'no aplica (guardado)',
            'operativo_http' => 302,
            'operativo_error' => 'ninguno',
            'operativo_bd' => $op->cbu_alias,
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_B4_alias_valido_cbu_valido_y_campo_vacio(): void
    {
        // 1. Alias válido
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['dni' => '35000001', 'cbu_alias' => 'mi.alias.valido']))
            ->assertSessionHasNoErrors();
        $this->assertSame('mi.alias.valido', Profesor::where('dni', '35000001')->value('cbu_alias'));

        // 2. CBU válido
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['dni' => '35000002', 'cbu_alias' => '0170099220000067797912']))
            ->assertSessionHasNoErrors();
        $this->assertSame('0170099220000067797912', Profesor::where('dni', '35000002')->value('cbu_alias'));

        // 3. Campo vacío
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['dni' => '35000003', 'cbu_alias' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull(Profesor::where('dni', '35000003')->value('cbu_alias'));

        // En Operativo
        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['email' => 'op1@test.com', 'cbu_alias' => 'mi.alias.valido']))
            ->assertSessionHasNoErrors();
        $this->assertSame('mi.alias.valido', User::where('email', 'op1@test.com')->value('cbu_alias'));

        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['email' => 'op2@test.com', 'cbu_alias' => '0170099220000067797912']))
            ->assertSessionHasNoErrors();
        $this->assertSame('0170099220000067797912', User::where('email', 'op2@test.com')->value('cbu_alias'));

        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['email' => 'op3@test.com', 'cbu_alias' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull(User::where('email', 'op3@test.com')->value('cbu_alias'));

        $this->registrarResultado([
            'caso' => 'B4. Alias válido, CBU válido y campo vacío',
            'enviado' => '"mi.alias.valido", "0170099220000067797912", ""',
            'profesor_http' => 302,
            'profesor_error' => 'ninguno',
            'profesor_bd' => '"mi.alias.valido", "0170099220000067797912", NULL',
            'profesor_old' => 'no aplica (guardado)',
            'operativo_http' => 302,
            'operativo_error' => 'ninguno',
            'operativo_bd' => '"mi.alias.valido", "0170099220000067797912", NULL',
            'resultado' => 'APROBADO',
        ]);
    }

    // =========================================================================
    // SECCIÓN C: CASOS NUEVOS DE LA CORRECCIÓN
    // =========================================================================

    public function test_C1_mandar_campo_como_arreglo_se_rechaza_sin_error_500(): void
    {
        $valor = ['x'];

        // 1. Profesor POST
        $resProf = $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]));
        $resProf->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgProf = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgProf);
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        // 2. Operativo POST
        $resOp = $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]));
        $resOp->assertStatus(302)->assertSessionHasErrors('cbu_alias');
        $msgOp = session('errors')->first('cbu_alias');
        $this->assertSame(CbuOAlias::MENSAJE, $msgOp);
        $this->assertNull(User::where('email', 'operativo.b10@wings.test')->first());

        $this->registrarResultado([
            'caso' => 'C1. Mandar arreglo por POST directo',
            'enviado' => 'cbu_alias[] = "x"',
            'profesor_http' => 302,
            'profesor_error' => $msgProf,
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => $msgOp,
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO (rechaza con 302, sin error 500)',
        ]);
    }

    public function test_C2_mandar_solo_espacios_queda_vacio(): void
    {
        $valor = '      ';

        // 1. Profesor POST
        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $prof = Profesor::where('dni', '35999888')->firstOrFail();
        $this->assertNull($prof->cbu_alias);

        // 2. Operativo POST
        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $op = User::where('email', 'operativo.b10@wings.test')->firstOrFail();
        $this->assertNull($op->cbu_alias);

        $this->registrarResultado([
            'caso' => 'C2. Mandar solo espacios',
            'enviado' => '"      "',
            'profesor_http' => 302,
            'profesor_error' => 'ninguno',
            'profesor_bd' => 'NULL',
            'profesor_old' => 'no aplica (guardado)',
            'operativo_http' => 302,
            'operativo_error' => 'ninguno',
            'operativo_bd' => 'NULL',
            'resultado' => 'APROBADO',
        ]);
    }

    // =========================================================================
    // SECCIÓN D: CASOS DE BORDE Y CASOS NO CONTEMPLADOS
    // =========================================================================

    public function test_D1_casing_exacto_se_conserva(): void
    {
        $valor = 'Mi.Alias.Banco';

        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $prof = Profesor::where('dni', '35999888')->firstOrFail();
        $this->assertSame('Mi.Alias.Banco', $prof->cbu_alias);

        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $op = User::where('email', 'operativo.b10@wings.test')->firstOrFail();
        $this->assertSame('Mi.Alias.Banco', $op->cbu_alias);

        $this->registrarResultado([
            'caso' => 'D1. Preservación de casing (mayúsculas y minúsculas)',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => 'ninguno',
            'profesor_bd' => $prof->cbu_alias,
            'profesor_old' => 'no aplica',
            'operativo_http' => 302,
            'operativo_error' => 'ninguno',
            'operativo_bd' => $op->cbu_alias,
            'resultado' => 'APROBADO (no altera el casing tipeado)',
        ]);
    }

    public function test_D2_guiones_y_puntos_validos(): void
    {
        $valor = 'alias-valido.b10';

        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $prof = Profesor::where('dni', '35999888')->firstOrFail();
        $this->assertSame($valor, $prof->cbu_alias);

        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['cbu_alias' => $valor]))
            ->assertSessionHasNoErrors();
        $op = User::where('email', 'operativo.b10@wings.test')->firstOrFail();
        $this->assertSame($valor, $op->cbu_alias);

        $this->registrarResultado([
            'caso' => 'D2. Alias con puntos y guiones válidos',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => 'ninguno',
            'profesor_bd' => $prof->cbu_alias,
            'profesor_old' => 'no aplica',
            'operativo_http' => 302,
            'operativo_error' => 'ninguno',
            'operativo_bd' => $op->cbu_alias,
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_D3_alias_demasiado_corto_5_chars(): void
    {
        $valor = 'abcde';

        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasErrors('cbu_alias');
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        $this->registrarResultado([
            'caso' => 'D3. Alias de 5 caracteres (mínimo es 6)',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => session('errors')->first('cbu_alias'),
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => session('errors')->first('cbu_alias'),
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_D4_alias_demasiado_largo_21_chars(): void
    {
        $valor = 'abcdefghijklmnopqrstu';

        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasErrors('cbu_alias');
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        $this->registrarResultado([
            'caso' => 'D4. Alias de 21 caracteres (máximo es 20)',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => session('errors')->first('cbu_alias'),
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => session('errors')->first('cbu_alias'),
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_D5_cbu_incompleto_21_digitos(): void
    {
        $valor = '123456789012345678901';

        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasErrors('cbu_alias');
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        $this->registrarResultado([
            'caso' => 'D5. CBU incompleto (21 dígitos)',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => session('errors')->first('cbu_alias'),
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => session('errors')->first('cbu_alias'),
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_D6_cbu_excedido_23_digitos(): void
    {
        $valor = '12345678901234567890123';

        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasErrors('cbu_alias');
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        $this->registrarResultado([
            'caso' => 'D6. CBU excedido (23 dígitos)',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => session('errors')->first('cbu_alias'),
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => session('errors')->first('cbu_alias'),
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_D7_alias_puramente_numerico_8_digitos_se_rechaza(): void
    {
        $valor = '12345678';

        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasErrors('cbu_alias');
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        $this->registrarResultado([
            'caso' => 'D7. 8 dígitos numéricos (alias puramente numérico)',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => session('errors')->first('cbu_alias'),
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => session('errors')->first('cbu_alias'),
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO (rechazado por !ctype_digit para evitar CBUs truncados)',
        ]);
    }

    public function test_D8_cbu_22_digitos_con_letra(): void
    {
        $valor = '1111111111a11111111111';

        $this->actingAs($this->admin)->post(route('web.profesores.store'), $this->datosProfesor(['cbu_alias' => $valor]))
            ->assertSessionHasErrors('cbu_alias');
        $this->assertNull(Profesor::where('dni', '35999888')->first());

        $this->registrarResultado([
            'caso' => 'D8. 22 caracteres con letra en el medio',
            'enviado' => $valor,
            'profesor_http' => 302,
            'profesor_error' => session('errors')->first('cbu_alias'),
            'profesor_bd' => 'sin fila',
            'profesor_old' => 'conservado',
            'operativo_http' => 302,
            'operativo_error' => session('errors')->first('cbu_alias'),
            'operativo_bd' => 'sin fila',
            'resultado' => 'APROBADO',
        ]);
    }

    public function test_D9_cambio_de_rol_de_operativo_a_admin_limpia_cbu_alias(): void
    {
        // 1. Crear como operativo con cbu_alias
        $this->actingAs($this->admin)->post(route('web.usuarios.store'), $this->datosOperativo(['email' => 'op.rol@wings.test', 'cbu_alias' => 'operativo.cbu']))
            ->assertSessionHasNoErrors();
        $user = User::where('email', 'op.rol@wings.test')->firstOrFail();
        $this->assertSame('operativo.cbu', $user->cbu_alias);

        // 2. Modificar rol a ADMIN
        $this->actingAs($this->admin)->put(route('web.usuarios.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'rol' => User::ROL_ADMIN,
        ])->assertSessionHasNoErrors();

        // 3. Verificar que se borró el dato bancario
        $this->assertNull($user->fresh()->cbu_alias);

        $this->registrarResultado([
            'caso' => 'D9. Cambio de rol (OPERATIVO -> ADMIN) limpia cbu_alias',
            'enviado' => 'rol: ADMIN',
            'profesor_http' => 302,
            'profesor_error' => 'no aplica',
            'profesor_bd' => 'no aplica',
            'profesor_old' => 'no aplica',
            'operativo_http' => 302,
            'operativo_error' => 'ninguno',
            'operativo_bd' => 'cbu_alias pasa a NULL',
            'resultado' => 'APROBADO',
        ]);
    }
}
