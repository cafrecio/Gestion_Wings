# T80 — Política de contenido en modo bloqueo

**Claude, CAB, 25/10/2026. Local, sin despliegue. Estado: a verificar.**

## Qué cambia para la persona del club

Nada visible. Si alguna vez entra código ajeno a una pantalla (un dato mal escapado, una
extensión, un error futuro), el navegador **no lo ejecuta**. Hasta hoy solo lo anotaba.

De paso: una dirección con texto donde va un número (`/alumnos/abc`, o
`/liquidaciones/create` en vez de `/liquidaciones/crear`) mostraba «Algo falló». Ahora muestra
«Página no disponible», como cualquier dirección que no existe.

## Cambios

| Archivo | Cambio |
|---|---|
| `app/Http/Middleware/SecurityHeaders.php` | `Content-Security-Policy-Report-Only` → `Content-Security-Policy`. **La política es la misma**, letra por letra. |
| `app/Providers/AppServiceProvider.php` | `Route::patterns`: los parámetros numéricos (`id`, `cajaId`, `alumnoId`, …) aceptan solo dígitos. `{clave}` queda afuera. |
| `tests/Feature/SecurityHeadersTest.php`, `CspReporteTest.php`, `CspSinCodigoIncrustadoTest.php`, `docs/06-pruebas/PRU-04/prueba-tecnica/SeguridadDiasPruebaTest.php` | Leen el header que bloquea y exigen que el de solo reporte ya no esté. |
| `tests/Feature/DireccionConTextoT80Test.php` | 2 pruebas nuevas: texto en lugar de número da 404, y las rutas con texto propio (`/liquidaciones/crear`, `/alumnos/create`) siguen andando. |
| `AGENTS.md` §2 y §5-bis, `ESTADO-ACTUAL.md` | Registran que la CSP bloquea y con qué evidencia. |

## Los cuatro pasos de AGENTS.md §2

1. **En modo reporte:** desde septiembre, con `/csp-reporte` escribiendo en `storage/logs/csp-*.log`.
2. **Recorrido real:** en el sitio de prueba hubo 21 reportes del 17 al 21/10. 10 son del bloque de `/login` (línea 145); los otros 11, del 19/10, son de Alumnos, Clases, Grupos y Profesores, vistas de las que T73 también sacó bloques. **Corregido el 25/10 por la verificación de Codex:** antes acá decía que eran todos de `/login`, porque esos registros los conté por hora y no miré la página. Que los 11 sean esos bloques es inferido: el registro no guarda el fragmento. T73 se desplegó el **21/10 a las 14:04**; el último reporte es del **21/10 a las 00:07**. Del 21 al 25/10 la prueba por días recorrió unas 60 pantallas distintas (≈1.000 páginas servidas) con **cero reportes**. Verificado por `ssh vps` leyendo los registros del sitio, sin tocar la base.
3. **Endurecer:** la misma política pasa a bloquear. No hizo falta ninguna excepción nueva.
4. **Revisión visual, ya en bloqueo:** servidor local sobre `wings_testing_claude` con dos años de datos (`volumen-generar.php`). Chrome real recorrió **59 pantallas** de los tres roles y la de ingreso: **0 bloqueos, 0 errores de JavaScript**. Un espía inyectado antes de cada página (`securitypolicyviolation`) junta los bloqueos; se probó que detecta uno real metiendo un script incrustado a propósito, que fue bloqueado y no corrió. Capturas: alumnos, caja, cobrar, clase, nueva liquidación, inicio del dueño.

## Pruebas

- Dirigidas: `DireccionConTextoT80Test` + `PantallasErrorTest` + `AvisosCastellanoT47Test`: 22 verdes. Las 11 de CSP, verdes.
- Rápida (`--exclude-group=lento`) en `wings_testing_claude`: **922 aprobadas, 6 omitidas, 0 fallidas, 7.395 aserciones, 214,5 s**.
- Contador: 1041 → **1043**.

## Evidencia

`docs/06-pruebas/PRU-04/evidencia-t80-claude/`:

- `recorrido.json`: las 59 páginas, con su política, sus bloqueos y sus errores;
- `recorrer-local.mjs`, `capturas.mjs` y `preparar-local.php`: cómo repetirlo. Las claves se generan al azar, quedan en `storage/app/t80-local.json` y se borran al terminar; nunca van al repositorio;
- seis capturas `*-1366.png`.

## Qué queda fuera

- **`style-src` sigue con `'unsafe-inline'`:** quedan unos 1.054 estilos inline en las vistas. Sacarlo es otro trabajo y repite los cuatro pasos.
- **Sin desplegar:** al desplegar, mirar `storage/logs/csp-*.log` del sitio de prueba en los días siguientes. Un reporte ahora es algo que el navegador **ya bloqueó**.
- **Verificado contra inferido:** el recorrido local es verificado. Que en el sitio de prueba «no se rompe nada» es inferido de cuatro días de reportes en cero, con la misma política. No se recorrió el sitio de prueba en modo bloqueo porque todavía no tiene este cambio.
