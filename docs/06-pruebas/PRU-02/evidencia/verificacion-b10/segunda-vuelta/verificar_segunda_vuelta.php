<?php

/**
 * Verificación B10 - Segunda Vuelta (Gemini)
 * Base de testing: wings_testing_gemini
 */

$baseDir = dirname(__DIR__, 6);
require $baseDir . '/vendor/autoload.php';
$app = require $baseDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Deporte;
use App\Models\Profesor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

if (DB::connection()->getDatabaseName() !== 'wings_testing_gemini') {
    fwrite(STDERR, "Error: la base de datos debe ser wings_testing_gemini, actual: " . DB::connection()->getDatabaseName() . "\n");
    exit(1);
}

echo "=== INICIANDO VERIFICACIÓN B10 SEGUNDA VUELTA ===\n";
echo "Base de datos: " . DB::connection()->getDatabaseName() . "\n";

// 1. Preparar base limpia
echo "Migrando y sembrando catálogos en wings_testing_gemini...\n";
Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--class' => Database\Seeders\CatalogosSeeder::class, '--force' => true]);
DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);

// Crear Admin para las sesiones
$admin = User::create([
    'name' => 'Admin Verificador B10',
    'email' => 'admin-b10@wings.test',
    'password' => Hash::make('password123'),
    'rol' => User::ROL_ADMIN,
    'activo' => true,
]);

$deporte = Deporte::where('activo', true)->firstOrFail();

$resultados = [];

function ejecutarPeticion($app, $admin, $method, $uri, $params = []) {
    // Simular sesión iniciada
    Auth::login($admin);
    Session::flush();
    Session::start();
    
    // Configurar sesión para old input y errors
    $request = Request::create($uri, $method, $params, [], [], [
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
    ]);
    $request->setLaravelSession(app('session.store'));
    
    // Manejar a través del HTTP Kernel
    $httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $httpKernel->handle($request);
    
    $session = $request->session();
    $errors = $session->get('errors');
    $errorMsg = null;
    if ($errors) {
        $errorMsg = $errors->first('cbu_alias');
    }
    $oldInput = $session->get('_old_input', []);
    
    $httpKernel->terminate($request, $response);
    
    return [
        'status' => $response->getStatusCode(),
        'error' => $errorMsg,
        'old' => $oldInput,
        'headers' => $response->headers->all(),
    ];
}

// -------------------------------------------------------------------------
// CASOS DE PRUEBA
// -------------------------------------------------------------------------

$casos = [
    // GRUPO A: Tienen que RECHAZARSE
    [
        'id' => 'A1_alias_con_espacio',
        'grupo' => 'A. Rechazos',
        'descripcion' => '«alias con espacio»',
        'valor' => 'alias con espacio',
        'espera_rechazo' => true,
    ],
    [
        'id' => 'A2_alias_con_tab',
        'grupo' => 'A. Rechazos',
        'descripcion' => 'Alias con tabulación en el medio',
        'valor' => "alias\tcon\ttab",
        'espera_rechazo' => true,
    ],
    [
        'id' => 'A3_alias_con_salto',
        'grupo' => 'A. Rechazos',
        'descripcion' => 'Alias con salto de línea en el medio',
        'valor' => "alias\ncon\nsalto",
        'espera_rechazo' => true,
    ],
    [
        'id' => 'A4_texto_200_chars',
        'grupo' => 'A. Rechazos',
        'descripcion' => 'Texto de 200 caracteres',
        'valor' => str_repeat('a', 200),
        'espera_rechazo' => true,
    ],

    // GRUPO B: Tienen que seguir ANDANDO
    [
        'id' => 'B1_cbu_espacios_internos',
        'grupo' => 'B. Sigue andando',
        'descripcion' => 'CBU pegado con espacios internos y de borde',
        'valor' => ' 0170 0992 2000 0067 7979 12 ',
        'espera_rechazo' => false,
        'espera_bd' => '0170099220000067797912',
    ],
    [
        'id' => 'B2_cbu_con_tabs',
        'grupo' => 'B. Sigue andando',
        'descripcion' => 'CBU con tabulaciones entre los números',
        'valor' => "0170\t0992\t2000\t0067\t7979\t12",
        'espera_rechazo' => false,
        'espera_bd' => '0170099220000067797912',
    ],
    [
        'id' => 'B3_alias_espacio_bordes',
        'grupo' => 'B. Sigue andando',
        'descripcion' => 'Alias con espacio al principio y al final',
        'valor' => ' mi.alias ',
        'espera_rechazo' => false,
        'espera_bd' => 'mi.alias',
    ],
    [
        'id' => 'B4_alias_valido',
        'grupo' => 'B. Sigue andando',
        'descripcion' => 'Alias válido estándar',
        'valor' => 'mi.alias.valido',
        'espera_rechazo' => false,
        'espera_bd' => 'mi.alias.valido',
    ],
    [
        'id' => 'B5_cbu_valido',
        'grupo' => 'B. Sigue andando',
        'descripcion' => 'CBU válido de 22 dígitos',
        'valor' => '0170099220000067797912',
        'espera_rechazo' => false,
        'espera_bd' => '0170099220000067797912',
    ],
    [
        'id' => 'B6_campo_vacio',
        'grupo' => 'B. Sigue andando',
        'descripcion' => 'Campo vacío',
        'valor' => '',
        'espera_rechazo' => false,
        'espera_bd' => null,
    ],

    // GRUPO C: Casos nuevos de la corrección
    [
        'id' => 'C1_arreglo_post',
        'grupo' => 'C. Nuevos',
        'descripcion' => 'Mandar el campo como arreglo por POST directo',
        'valor' => ['x'],
        'espera_rechazo' => true,
    ],
    [
        'id' => 'C2_solo_espacios',
        'grupo' => 'C. Nuevos',
        'descripcion' => 'Mandar solo espacios en blanco',
        'valor' => '     ',
        'espera_rechazo' => false,
        'espera_bd' => null,
    ],

    // GRUPO D: Casos adicionales y bordes
    [
        'id' => 'D1_alias_mayusculas_minusculas',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => 'Alias con mayúsculas y minúsculas (conserva casing exacto)',
        'valor' => 'Mi.Alias.Banco',
        'espera_rechazo' => false,
        'espera_bd' => 'Mi.Alias.Banco',
    ],
    [
        'id' => 'D2_alias_guiones_puntos',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => 'Alias con guiones y puntos',
        'valor' => 'alias-valido.b10',
        'espera_rechazo' => false,
        'espera_bd' => 'alias-valido.b10',
    ],
    [
        'id' => 'D3_alias_demasiado_corto',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => 'Alias de 5 caracteres (mínimo es 6)',
        'valor' => 'abcde',
        'espera_rechazo' => true,
    ],
    [
        'id' => 'D4_alias_demasiado_largo',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => 'Alias de 21 caracteres (máximo es 20)',
        'valor' => 'abcdefghijklmnopqrstu',
        'espera_rechazo' => true,
    ],
    [
        'id' => 'D5_cbu_21_digitos',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => 'CBU de 21 dígitos (incompleto)',
        'valor' => '123456789012345678901',
        'espera_rechazo' => true,
    ],
    [
        'id' => 'D6_cbu_23_digitos',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => 'CBU de 23 dígitos (excede 22)',
        'valor' => '12345678901234567890123',
        'espera_rechazo' => true,
    ],
    [
        'id' => 'D7_alias_solo_numeros',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => '8 dígitos numéricos (alias puramente numérico se rechaza por regla)',
        'valor' => '12345678',
        'espera_rechazo' => true,
    ],
    [
        'id' => 'D8_cbu_con_letra',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => '22 caracteres numéricos con una letra en el medio',
        'valor' => '1111111111a11111111111',
        'espera_rechazo' => true,
    ],
    [
        'id' => 'D9_cbu_con_espacio_no_rompible',
        'grupo' => 'D. Casos adicionales',
        'descripcion' => 'CBU con non-breaking spaces (\u{00A0})',
        'valor' => "0170\u{00A0}0992\u{00A0}2000\u{00A0}0067\u{00A0}7979\u{00A0}12",
        // En PHP \s por defecto no atrapa NBSP a menos que tenga flag /u o trim especial
        'evaluar_comportamiento' => true,
    ]
];

$contadorDni = 98000000;
$contadorEmail = 1000;

echo "\n--- VERIFICANDO CASOS EN PROFESOR ---\n";
foreach ($casos as &$caso) {
    $dni = (string)($contadorDni++);
    $email = "prof{$contadorEmail}@b10.test";
    
    $params = [
        'nombre' => 'Profesor ' . $caso['id'],
        'apellido' => 'Test',
        'deporte_id' => $deporte->id,
        'dni' => $dni,
        'fecha_nacimiento' => '1985-05-15',
        'direccion' => 'Calle Falsa 123',
        'localidad' => 'CABA',
        'email' => $email,
        'telefono' => '1144556677',
        'cbu_alias' => $caso['valor'],
        'valor_hora' => '15000',
    ];

    $resp = ejecutarPeticion($app, $admin, 'POST', '/profesores', $params);
    $fila = Profesor::where('dni', $dni)->first();

    $caso['profesor'] = [
        'http_status' => $resp['status'],
        'error_mensaje' => $resp['error'],
        'old_conservado' => isset($resp['old']['nombre']) && $resp['old']['nombre'] === $params['nombre'] && isset($resp['old']['dni']) && $resp['old']['dni'] === $params['dni'],
        'bd_fila_existe' => $fila !== null,
        'bd_cbu_alias' => $fila ? $fila->cbu_alias : null,
    ];

    if ($caso['espera_rechazo'] ?? false) {
        $ok = ($resp['status'] === 302 && !$fila && $resp['error'] !== null);
    } elseif (isset($caso['espera_bd'])) {
        $ok = ($resp['status'] === 302 && $fila && $fila->cbu_alias === $caso['espera_bd'] && $resp['error'] === null);
    } else {
        $ok = true;
    }
    $caso['profesor']['ok'] = $ok;

    echo sprintf("• [%s] Profesor: %s -> HTTP %d | Error: %s | BD: %s | Result: %s\n",
        $ok ? 'OK' : 'FALLA',
        $caso['descripcion'],
        $resp['status'],
        $resp['error'] ?? 'ninguno',
        $fila ? ($fila->cbu_alias ?? 'NULL') : 'NO CREADO',
        $ok ? 'APROBADO' : 'REVISAR'
    );
}
unset($caso);

echo "\n--- VERIFICANDO CASOS EN USUARIO OPERATIVO ---\n";
foreach ($casos as &$caso) {
    $email = "operativo_{$contadorEmail}@b10.test";
    $contadorEmail++;

    $params = [
        'name' => 'Operativo ' . $caso['id'],
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rol' => User::ROL_OPERATIVO,
        'cbu_alias' => $caso['valor'],
    ];

    $resp = ejecutarPeticion($app, $admin, 'POST', '/usuarios', $params);
    $fila = User::where('email', $email)->first();

    $caso['operativo'] = [
        'http_status' => $resp['status'],
        'error_mensaje' => $resp['error'],
        'old_conservado' => isset($resp['old']['name']) && $resp['old']['name'] === $params['name'] && isset($resp['old']['email']) && $resp['old']['email'] === $params['email'],
        'bd_fila_existe' => $fila !== null,
        'bd_cbu_alias' => $fila ? $fila->cbu_alias : null,
    ];

    if ($caso['espera_rechazo'] ?? false) {
        $ok = ($resp['status'] === 302 && !$fila && $resp['error'] !== null);
    } elseif (isset($caso['espera_bd'])) {
        $ok = ($resp['status'] === 302 && $fila && $fila->cbu_alias === $caso['espera_bd'] && $resp['error'] === null);
    } else {
        $ok = true;
    }
    $caso['operativo']['ok'] = $ok;

    echo sprintf("• [%s] Operativo: %s -> HTTP %d | Error: %s | BD: %s | Result: %s\n",
        $ok ? 'OK' : 'FALLA',
        $caso['descripcion'],
        $resp['status'],
        $resp['error'] ?? 'ninguno',
        $fila ? ($fila->cbu_alias ?? 'NULL') : 'NO CREADO',
        $ok ? 'APROBADO' : 'REVISAR'
    );
}
unset($caso);

// Guardar resultados JSON
$jsonPath = __DIR__ . '/resultado-segunda-vuelta.json';
file_put_contents($jsonPath, json_encode($casos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nResultados guardados en: $jsonPath\n";
