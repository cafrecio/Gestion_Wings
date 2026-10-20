<?php
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex_t61') throw new RuntimeException('Solo base ficticia T61');
$root = dirname(__DIR__, 4);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$storage = $root.'/storage/t61-migracion';
foreach (['framework', 'framework/cache/data', 'framework/views', 'framework/sessions', 'logs'] as $dir) if (!is_dir($storage.'/'.$dir)) mkdir($storage.'/'.$dir, 0777, true);
$app->useStoragePath($storage);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
 Illuminate\Support\Facades\Artisan::call('down');
 $exit = Illuminate\Support\Facades\Artisan::call('migrate', ['--force'=>true]);
 echo Illuminate\Support\Facades\Artisan::output();
} finally { Illuminate\Support\Facades\Artisan::call('up'); }
exit($exit);
