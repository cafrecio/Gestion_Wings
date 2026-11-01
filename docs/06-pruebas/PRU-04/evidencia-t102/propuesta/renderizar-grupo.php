<?php
// T102: propuesta de editar un grupo y del aviso al inactivar. Datos ficticios; no escribe en ninguna base.
$raiz = 'C:/xampp/htdocs/gestion-wings';
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);

use App\Models\{Deporte, Grupo, GrupoPlan, Nivel, User};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Auth, Blade, View};

View::prependLocation(__DIR__.'/vistas');
View::share('errors', new \Illuminate\Support\ViewErrorBag);
Auth::setUser((new User)->forceFill(['id' => 1, 'name' => 'Laura Gómez', 'email' => 'laura@ejemplo.test', 'rol' => User::ROL_ADMIN, 'activo' => true]));

$nivel = fn ($id, $nombre, $desc, $grupos = 0, $activo = true) => (new Nivel)->forceFill(['id' => $id, 'nombre' => $nombre, 'descripcion' => $desc, 'grupos_count' => $grupos, 'activo' => $activo]);
$opciones = collect([$nivel(1, 'Adultos', 'Mayores de 18 años'), $nivel(2, 'Competencia', 'Alumnos que compiten en torneos'), $nivel(3, 'Escuelita', 'Alumnos que inician la actividad')]);
$grupo = (new Grupo)->forceFill(['id' => 7, 'deporte_id' => 1, 'nivel_id' => 3, 'activo' => true]);
$grupo->exists = true;
$grupo->setRelation('deporte', (new Deporte)->forceFill(['id' => 1, 'nombre' => 'Patín']));
$grupo->setRelation('nivel', $opciones[2]);
$grupo->setRelation('planes', collect([(new GrupoPlan)->forceFill(['id' => 1, 'grupo_id' => 7, 'clases_por_semana' => 2, 'precio_mensual' => '30000.00', 'activo' => true])]));
file_put_contents(__DIR__.'/grupo-editar.html', view('grupos.edit', ['grupo' => $grupo, 'niveles' => $opciones])->render());

session()->flash('error', 'No se puede inactivar el nivel porque tiene 2 grupos activos. Pasalos a otro nivel o desactivalos.');
$lista = collect([$nivel(2, 'Competencia', 'Alumnos que compiten en torneos', 2), $nivel(3, 'Escuelita', 'Alumnos que inician la actividad', 3), $nivel(4, 'Federadas', 'Ya no se abren grupos nuevos', 1, false)]);
$html = Blade::render(file_get_contents(__DIR__.'/index-propuesta.blade.php'), ['niveles' => new LengthAwarePaginator($lista, 3, 20, 1)]);
file_put_contents(__DIR__.'/niveles-aviso.html', str_replace('</head>', '<style>'.file_get_contents(__DIR__.'/movil.css').'</style></head>', $html));
echo "ok\n";
