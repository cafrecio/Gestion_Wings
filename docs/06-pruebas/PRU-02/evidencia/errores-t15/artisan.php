<?php
// Entrada de comandos con storage aislado, también en Windows sin variables_order=E.
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex' || !getenv('LARAVEL_STORAGE_PATH')) {
    fwrite(STDERR, "Solo entorno testing de Codex con storage aislado.\n"); exit(1);
}
$_ENV['LARAVEL_STORAGE_PATH'] = getenv('LARAVEL_STORAGE_PATH');
require dirname(__DIR__, 5).'/artisan';
