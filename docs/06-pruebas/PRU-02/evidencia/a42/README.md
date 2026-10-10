# Evidencia A42 — navegador real, JavaScript externo

Programas: `preparar.php` crea únicamente fixtures en `wings_testing_codex`; `verificar.cjs` maneja Chrome con Playwright y consulta Wings real. No generan HTML ni incrustan JavaScript en él. Las capturas son recortes de los avisos del sistema funcionando.

Requisitos: PHP/composer y dependencias del proyecto instaladas; Node con Playwright disponible; Chrome. En esta computadora Playwright se resuelve con `NODE_PATH` del runtime de Codex. Si se usa otra instalación, proporcionar esa ruta. `CHROME_BIN` permite cambiar el ejecutable.

Ejecutar desde la raíz del checkout aislado, con ninguna suite usando esa base:

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='wings_testing_codex'
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3306'
$env:DB_USERNAME='root'
$env:DB_PASSWORD=''
$env:CACHE_STORE='array'
$env:SESSION_DRIVER='file'
$env:APP_URL='http://127.0.0.1:8042'
npm.cmd run build
php docs/06-pruebas/PRU-02/evidencia/a42/preparar.php
php artisan serve --host=127.0.0.1 --port=8042 --no-reload
```

En otra terminal, con esas variables y Playwright resoluble:

```powershell
node docs/06-pruebas/PRU-02/evidencia/a42/verificar.cjs
```

El preparador reconstruye la base descartable; se niega a escribir si no coinciden entorno testing y nombre exacto de base. La contraseña ficticia efímera queda únicamente en `storage/app/a42-fixture.json`, ignorado por Git. No se exportan usuarios ni contraseñas a la evidencia.

El programa prueba cinco aperturas exigidas, cambios de campos y cuota cerrada; captura avisos, comprueba ausencia de JS incrustado y filas conservadas. El error se produce mediante POST real inválido, no inyectando mensajes. El control negativo modifica una respuesta del bundle externo solamente en memoria y prueba que quitar la llamada inicial reproduce el aviso detenido.

La suite PHP se ejecuta por separado, tras detener este servidor; no ejecuta JavaScript. No ejecutar el preparador mientras la suite usa la misma base.

Resultados fechados en JSON. La entrega resume build/suite y sus límites. Todos los datos capturados son ficticios.
