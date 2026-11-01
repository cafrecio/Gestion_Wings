<?php
// T102: renderiza las pantallas reales con datos ficticios para el manual. No escribe en ninguna base.
$raiz = 'C:/xampp/htdocs/gestion-wings';
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);

use App\Models\{Deporte, Grupo, GrupoPlan, Nivel, User};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Auth, View};

View::share('errors', new \Illuminate\Support\ViewErrorBag);
Auth::setUser((new User)->forceFill(['id' => 1, 'name' => 'Laura Gómez', 'email' => 'laura@ejemplo.test', 'rol' => User::ROL_ADMIN, 'activo' => true]));

$nivel = fn ($id, $nombre, $desc, $grupos = 0, $activo = true) => (new Nivel)->forceFill(['id' => $id, 'nombre' => $nombre, 'descripcion' => $desc, 'grupos_count' => $grupos, 'activo' => $activo]);
$lista = collect([$nivel(1, 'Adultos', 'Mayores de 18 años'), $nivel(2, 'Competencia', 'Alumnos que compiten en torneos', 2), $nivel(3, 'Escuelita', 'Alumnos que inician la actividad', 3), $nivel(4, 'Federadas', 'Ya no se abren grupos nuevos', 1, false), $nivel(5, 'Intermedio', null, 0, false)]);
file_put_contents(__DIR__.'/niveles.html', view('niveles.index', ['niveles' => new LengthAwarePaginator($lista, 5, 20, 1)])->render());

$grupo = (new Grupo)->forceFill(['id' => 7, 'deporte_id' => 1, 'nivel_id' => 3, 'activo' => true]);
$grupo->exists = true;
$grupo->setRelation('deporte', (new Deporte)->forceFill(['id' => 1, 'nombre' => 'Patín']));
$grupo->setRelation('nivel', $lista[2]);
$grupo->setRelation('planes', collect([(new GrupoPlan)->forceFill(['id' => 1, 'grupo_id' => 7, 'clases_por_semana' => 2, 'precio_mensual' => '30000.00', 'activo' => true])]));
file_put_contents(__DIR__.'/grupo-editar.html', view('grupos.edit', ['grupo' => $grupo, 'niveles' => $lista->take(3)])->render());
echo "ok\n";
