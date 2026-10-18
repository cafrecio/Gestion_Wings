# Evidencia T75

La [galería](GALERIA.html) y el [inventario](INVENTARIO.md) se abren sin servidor.
428 PNG originales, con huellas en `capturas.json`. Los datos son sintéticos.
`mediciones-antes.json` y `mediciones-despues.json` contienen los 297 casos de cada fase;
`respuestas-*.json` identifica las 99 respuestas y sus vistas realmente ejecutadas.

## Repetición en una copia aislada

No revertir vistas/CSS en el directorio compartido para reconstruir el antes.
Usar otra copia en `4a23634`, copiando solamente esta instrumentación; el después usa
el commit de entrega T75. Reservar `wings_testing_codex` durante ambas fases y la suite.
No ejecutar esto sobre una base del club: la preparación borra la base descartable.

En PowerShell, desde la raíz de la copia, con PHP, Node, Chrome y dependencias instaladas:

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:LARAVEL_STORAGE_PATH=Join-Path (Get-Location) 'storage/app/t75-runtime'
$env:T75_ETAPA='antes' # después: despues
$env:T75_PREPARAR='1' # solo primera fase; después: 0, conservar el fixture
php artisan test docs/06-pruebas/PRU-04/evidencia-t75/CapturaT75Test.php
npm run build
php -S 127.0.0.1:8735 -t public docs/06-pruebas/PRU-04/evidencia-t75/servidor.php
```

Crear previamente los directorios habituales de storage en esa ruta propia
(`framework/views`, `framework/sessions`, `framework/cache/data`, `logs`, `app`).
La preparación tiene dos guardas: ambiente testing y nombre exacto de la base.
No agregar esta instrumentación a `tests/`: no son pruebas nuevas de negocio.

En otra terminal de la misma copia, con servidor activo:

```powershell
node docs/06-pruebas/PRU-04/evidencia-t75/recorrer-local.mjs antes
# Repetir render/build/capturas con el producto T75 y fase despues.
node docs/06-pruebas/PRU-04/evidencia-t75/recorrer-local.mjs despues
node docs/06-pruebas/PRU-04/evidencia-t75/capturar-recaudacion.mjs
node docs/06-pruebas/PRU-04/evidencia-t75/comparar.mjs
node docs/06-pruebas/PRU-04/evidencia-t75/control-hijo.mjs
node docs/06-pruebas/PRU-04/evidencia-t75/verificar-limites.mjs
node docs/06-pruebas/PRU-04/evidencia-t75/publicar-evidencia.mjs
```

El renderer pide GET reales a Laravel autenticado como cada rol, conserva HTML literal
en storage ignorado y registra qué vistas se ejecutaron. Chrome lo carga con los assets
compilados de cada fase dentro de un iframe a 360/375/1366; no se fabrica la pantalla.
Los paneles ocultos existentes se muestran solo para medir/capturar su distribución;
esto no afirma que se hayan enviado sus formularios en el navegador.

La pasada posterior se reanudó desde su checkpoint tras un error de escritura de Windows;
la comparación exige los 297 casos completos, únicos por pantalla/ancho.
El HTML con tokens de sesión queda ignorado, fuera de lo versionado.
El control de hijo declara temporalmente `margin-left:auto` sobre Subrubro en el DOM
real: no agrega markup ni cambia el producto.
