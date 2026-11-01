<?php
// T102: renderiza la propuesta con el layout y los componentes reales. Datos ficticios; no escribe en ninguna base.
$raiz = 'C:/xampp/htdocs/gestion-wings';
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Nivel, User};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Auth, Blade};

$admin = (new User)->forceFill(['id' => 1, 'name' => 'Laura Gómez', 'email' => 'laura@ejemplo.test', 'rol' => User::ROL_ADMIN, 'activo' => true]);
Auth::setUser($admin);

$filas = [
    [1, 'Adultos', 'Mayores de 18 años', 0, true],
    [2, 'Competencia', 'Alumnos que compiten en torneos', 2, true],
    [3, 'Escuelita', 'Alumnos que inician la actividad', 3, true],
    [4, 'Federadas', 'Ya no se abren grupos nuevos', 1, false],
    [5, 'Intermedio', null, 0, false],
];
$modelos = collect($filas)->map(fn ($f) => (new Nivel)->forceFill(['id' => $f[0], 'nombre' => $f[1], 'descripcion' => $f[2], 'grupos_count' => $f[3], 'activo' => $f[4]]));
$niveles = new LengthAwarePaginator($modelos, $modelos->count(), 20, 1);

$html = Blade::render(file_get_contents(__DIR__.'/index-propuesta.blade.php'), compact('niveles'));
file_put_contents(__DIR__.'/niveles.html', $html);
echo strlen($html), " bytes\n";
// Lo único que agregaría app.css: en celular el interruptor ocupa su fila y los botones van debajo.
file_put_contents(__DIR__.'/niveles.html', str_replace('</head>', '<style>'.file_get_contents(__DIR__.'/movil.css').'</style></head>', $html));
