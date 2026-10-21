<?php
require dirname(__DIR__,4).'/vendor/autoload.php';
$app=require dirname(__DIR__,4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(config('database.connections.mysql.database')!=='wings_testing_codex'||config('database.connections.mysql.host')!=='127.0.0.1') throw new RuntimeException('Solo base propia');
(new Database\Seeders\CatalogosSeeder)->run();
$usuario=App\Models\User::firstOrCreate(['email'=>'admin.t61.segunda@wings.test'],['name'=>'Control ficticio T61','rol'=>'ADMIN','activo'=>true,'password'=>Illuminate\Support\Facades\Hash::make('SoloT61Prueba!')]);
$usuario->forceFill(['rol'=>'ADMIN','activo'=>true])->save();
App\Models\PrimeraCarga::whereKey(1)->update(['estado'=>'TERMINADA']);
$rubro=App\Models\Rubro::where('nombre','Gastos Operativos')->firstOrFail();
$sub=App\Models\Subrubro::where('nombre','Limpieza')->firstOrFail();
echo json_encode(['base'=>config('database.connections.mysql.database'),'rubro'=>$rubro->id,'subrubro'=>$sub->id,'usuario'=>$usuario->email]).PHP_EOL;