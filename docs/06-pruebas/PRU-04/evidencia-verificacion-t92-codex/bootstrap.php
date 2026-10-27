<?php

$raiz = dirname(__DIR__, 4);
require $raiz.'/vendor/autoload.php';
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex'
    || !in_array(getenv('DB_HOST'), ['127.0.0.1', 'localhost'], true)
    || (getenv('DB_URL') !== false && getenv('DB_URL') !== '')
    || is_file($raiz.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('T92: solo testing local/wings_testing_codex, sin DB_URL ni cache de configuracion.');
}
