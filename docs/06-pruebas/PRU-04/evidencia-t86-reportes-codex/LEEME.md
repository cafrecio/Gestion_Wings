# T86 — Evidencia de operativas en Sueldos

Codex CAB, 27/10/2026. Solo MariaDB local `wings_testing_codex`, entorno `testing`, host `127.0.0.1`, `DB_URL` vacío, caché y sesión en memoria. No se ingresó al sitio ni se modificó la base del club. Todas las corridas fueron secuenciales.

- `rojo`: ocho casos permanentes antes de agregar el dato y la pantalla, ocho fallos esperados, salida 1.
- `dirigidas`: 28 casos; luego `dirigidas-final`: 30 casos con guardia CSP y gasto ajeno al sueldo añadido al fixture.
- `roles`: diagnóstico actualizado del pago por rutas reales, sin resolver la política de reservas antiguas.
- `captura`: Laravel devuelve la vista real con Vite y catálogos sintéticos; guarda con pagos, vacía y login. Solo se adaptan URLs locales de assets y se neutralizan tokens.
- `rapida` y `completa`: salidas y JUnit completos; el sufijo `-exit.txt` guarda el código de salida.
- `fuentes.json`: huellas de los archivos probados. `grafo-y-cobertura.json`: búsqueda, traza, fragmento y cobertura; las lagunas se completaron leyendo cuerpos y ruta real.

Desde la raíz, con las variables indicadas y el bootstrap estricto de Codex:

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:DB_HOST='127.0.0.1'
$env:DB_URL=''
$env:CACHE_STORE='array'
$env:SESSION_DRIVER='array'
php artisan test --bootstrap=docs/06-pruebas/PRU-04/evidencia-verificacion-t92-codex/bootstrap.php tests/Feature/ReporteSueldosOperativasT86Test.php tests/Feature/ReporteSueldosTest.php tests/Feature/SueldoOperativaT86Test.php tests/Feature/CspSinCodigoIncrustadoTest.php
php artisan test --bootstrap=docs/06-pruebas/PRU-04/evidencia-verificacion-t92-codex/bootstrap.php --exclude-group=lento
php artisan test --bootstrap=docs/06-pruebas/PRU-04/evidencia-verificacion-t92-codex/bootstrap.php
```

Las capturas son PNG originales de Chrome headless, escala 1, sin edición ni recorte. Escritorio usa ventana1366×1800. Celular usa una ventana600×2480 y un iframe375×2400; los enfoques usan ventana600×920 e iframe375×844. `medir-marco.js` solo mide el ancho y desplaza el marco para ver el bloque. Informa `innerWidth=375`, contenido360 por la barra de desplazamiento. El login de calibración entra entero; se inspeccionaron también ambas variantes de Sueldos. Los intentos de enfoque vacío que ocultaban el título por la barra fija se descartaron: la galería usa su captura completa válida.

Chrome se ejecutó oculto con `--headless=new --disable-gpu --allow-file-access-from-files --force-device-scale-factor=1 --hide-scrollbars`, presupuesto virtual4000/5000ms y perfiles propios ignorados bajo `storage/app/t86-chrome-*`. Los assets referidos por los HTML son los de `public/build` de este checkout; los PNG permiten revisar el corte aunque un build posterior cambie esos archivos. No se usa el perfil del navegador de Carlos.

[Galería](../APROBAR-T86-SUELDOS.html) · [Implementación](../IMPLEMENTACION-T86.md). La revisión visual de Carlos y la verificación independiente de Claude siguen pendientes; T86 se entrega `a_verificar`.

`verificar-entrega.py --guardar` comprueba resultados, fuentes, datos, dimensiones, enlaces y tablero antes de sellar el manifiesto; sin argumento verifica su integridad. No inicia PHP ni toca ninguna base. El auditor de `evidencia-t86-codex` comprueba por separado el corte histórico `f257fd6f`.
