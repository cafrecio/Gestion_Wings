# Evidencia B10 · Codex CyE · 10/10/2026

Los programas están fuera de `tests/` y no modifican la aplicación. El PHP exige
`APP_ENV=testing` y la base exacta `wings_testing_codex` antes de escribir.
**Preparar/seeders reconstruyen esa base descartable.** No ejecutar una suite propia
simultánea sobre ella. Las credenciales aleatorias quedan solo en un archivo ignorado
de `storage/app`; los resultados seleccionan campos, sin contraseñas ni hashes.

## Archivos

- `escenarios.php`: prepara ficción, consulta filas seleccionadas, genera Excel y controla seeders.
- `verificar.cjs`: Chrome real, formularios/pedidos al sistema, consultas posteriores y capturas.
- `resultado.json`: 73 casos completos y 150 GET de privacidad; conserva filas y payloads.
- `privacidad.md`: listado de URL, HTTP, redirección y búsqueda exacta por rol.
- `seeders.json`: siete ejecuciones aprobadas con sus prerrequisitos y filas concretas.
- `recibo-liquidacion.pdf` y `pdf.json`: descarga real y lectura de ambas páginas.
- `controles.json`: build y suite completa, cuyo log original queda ignorado en storage.
- `cerrar-evidencia.py`: comprueba resultados, lee PDF con pypdf y genera el informe.

## Entorno empleado

PowerShell, PHP de XAMPP, Chrome y Playwright del runtime disponible; Node/pypdf del
runtime de Codex. En las consolas de servidor y navegador:

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='wings_testing_codex'
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3306'
$env:DB_USERNAME='root'
$env:DB_PASSWORD=''
$env:LARAVEL_STORAGE_PATH='C:\xampp\htdocs\Gestion_Wings\storage\app\verificacion-b10-runtime'
$env:CACHE_STORE='array'
$env:SESSION_DRIVER='file'
$env:APP_URL='http://127.0.0.1:8010'
```

Crear en ese storage aislado las carpetas `logs`, `framework/cache/data`,
`framework/sessions`, `framework/views` y `app/private`. No usar caché de configuración
que fuerce otra base. Preparar escenario, iniciar Laravel en8010 y ejecutar el navegador:

```powershell
php docs/06-pruebas/PRU-02/evidencia/verificacion-b10/escenarios.php preparar
php artisan serve --host=127.0.0.1 --port=8010 --no-reload
```

En otra consola con el mismo entorno, ejecutar `verificar.cjs` con Node y Playwright
disponible. El script no arranca el servidor. Terminado el navegador, detener el servidor
y correr `escenarios.php seeders`: cada caso vuelve a migrar la base. UserSeeder se
prepara con PrimeraCargaCompletaSeeder; Asistencia con ReportesEscenarioSeeder.

El resultado final esperado en este commit incluye cuatro fallas de criterio
(`alias-espacio`, `alias-tab`, `alias-salto`, `texto-200`). Un programa que terminó
no significa que B10 haya aprobado. La corrida inicial interrumpida por un selector
incorrecto del programa fue corregida; `resultado.json` corresponde a la corrida completa.

La suite completa y el build se ejecutaron antes del escenario manual. No se ejecutan
otra vez por escribir el informe. El PDF y los archivos estáticos pertenecen solo a
ficción; las capturas no juzgan el aspecto aprobado por Carlos.
