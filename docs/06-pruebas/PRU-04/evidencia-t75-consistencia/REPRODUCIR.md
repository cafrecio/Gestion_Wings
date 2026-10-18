# Reproducción de evidencia T75

Ejecutar desde la raíz del repositorio, con PHP, Node, Chrome y dependencias instaladas.
El instrumental está fuera de `tests/`: no agrega pruebas permanentes de negocio.
Antes y después corresponden a las fuentes históricas y finales identificadas en el informe;
no se genera un «antes» ejecutando dos veces la fuente nueva.

## Renderizar Wings

Usar una copia de trabajo aislada con configuración sintética. Las guardas exigen
`APP_ENV=testing` y `DB_DATABASE=wings_testing_codex`; **T75_PREPARAR=1 borra y recrea esa
base descartable**. No ejecutar simultáneamente con la suite Codex.

En PowerShell:

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:LARAVEL_STORAGE_PATH=Join-Path (Get-Location) 'storage/app/t75-consistencia-runtime'
New-Item -ItemType Directory -Force -Path "$env:LARAVEL_STORAGE_PATH/app", "$env:LARAVEL_STORAGE_PATH/framework/views", "$env:LARAVEL_STORAGE_PATH/framework/cache/data", "$env:LARAVEL_STORAGE_PATH/framework/sessions", "$env:LARAVEL_STORAGE_PATH/logs" | Out-Null
npm run build
$env:T75_ETAPA='despues'
$env:T75_PREPARAR='1'
$env:T75_COMPLEMENTOS='0'
php artisan test docs/06-pruebas/PRU-04/evidencia-t75-consistencia/CapturaT75Test.php
$env:T75_PREPARAR='0'
$env:T75_COMPLEMENTOS='1'
php artisan test docs/06-pruebas/PRU-04/evidencia-t75-consistencia/CapturaT75Test.php
```

El HTML queda en el almacenamiento exclusivo; no se publica la base ni la configuración.
Si se usa la instantánea `app/assets-build`, actualizarla desde `public/build` después
de compilar. No cambiar ni recompilar assets durante una captura; deben existir todos
los archivos que referencian las respuestas reales.

## Fotografiar y medir

En una terminal con las variables anteriores, servir únicamente los archivos reales:

```powershell
php -S 127.0.0.1:8735 -t public docs/06-pruebas/PRU-04/evidencia-t75-consistencia/servidor.php
```

En otra terminal, desde la misma copia:

```powershell
node docs/06-pruebas/PRU-04/evidencia-t75-consistencia/recorrer-local.mjs despues
$env:T75_MANIFIESTO='complementos'
node docs/06-pruebas/PRU-04/evidencia-t75-consistencia/recorrer-local.mjs despues
Remove-Item Env:T75_MANIFIESTO
node docs/06-pruebas/PRU-04/evidencia-t75-consistencia/sondear.mjs
python -X utf8 docs/06-pruebas/PRU-04/evidencia-t75-consistencia/verificar.py
python -X utf8 docs/06-pruebas/PRU-04/evidencia-t75-consistencia/publicar.py
```

El verificador comprueba la fuente actual contra sus hashes y el punto anterior `1d73322`,
medidas de cada botón con texto, filas al mismo borde, ausencia de desborde, escritorio
y casos con/sin acciones complementarias. No prueba envíos financieros ni reemplaza
la revisión visual. `publicar.py` solo copia fotos referidas por las mediciones y valida
cada hash antes y después de copiarlas. Para revisar una entrega ya guardada no hace falta
regenerar datos: abrir [GALERIA.html](GALERIA.html) o comprobar el manifiesto PNG.

## Suite de código

Se ejecuta después del renderizado y en otra copia con almacenamiento exclusivo:

```powershell
$env:DB_DATABASE='wings_testing_codex'
$env:LARAVEL_STORAGE_PATH=Join-Path (Get-Location) 'storage/app/t75-suite-runtime'
php artisan view:cache
php artisan view:clear
php artisan test
```

La copia debe tener configuración sintética propia, nunca la configuración del club.
El `migrate:fresh` de la suite borra los datos sintéticos usados para renderizar; por eso
el renderizado se completa primero y no se ejecutan simultáneamente.
El [alcance de esta corrida](alcance-suite.json) identifica su base de código y los tres
contadores documentales adaptados únicamente a las 849 pruebas incluidas en esa copia.
No copiar ese número a un repositorio que contenga otras pruebas.
