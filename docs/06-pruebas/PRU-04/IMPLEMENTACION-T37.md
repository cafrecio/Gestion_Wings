# T37 — Rechazar una caja exige motivo

17/10/2026 — Codex CAB. Implementación local; entrega para revisión de Claude.
No desplegada. Inicio de tarea registrado en `cca651d`, solo los tres archivos del tablero.

## Regla y cambio

Decisión de Carlos: motivo obligatorio, hasta 500 caracteres, sin mínimo adicional.
Solo espacios se considera vacío. No se modifican cajas rechazadas históricas sin motivo.

- `CajaWebController::rechazar` valida texto obligatorio y máximo 500, con mensajes en castellano.
  El middleware de Laravel recorta el texto; no se envía un vacío por defecto al servicio.
- `CajaService::rechazarCaja` recorta y valida antes de escribir. También protege la llamada directa.
- Los dos modales conservan su estructura: se retira «opcional», se agrega `required`
  y se repone el valor anterior. Se conserva el máximo 500.
- Un error de `motivo` aparece al principio de Detalle y Resumen con `ds-flash--error`
  y `role=alert`, aunque el modal esté cerrado después de la redirección.
- El resumen ya mostraba el motivo guardado: se conserva esa presentación.

No se agregan scripts ni eventos dentro de Blade. No se mueve el código pendiente de T73.
Sin cambios de T36, usuarios, clases, permisos, rutas ni CSS compartido por parte de Codex.

## Verificado

Código real leído, rutas web alcanzables y llamadas al servicio contrastadas con el índice.
El índice viejo señaló cobertura insuficiente en Blade; se leyeron los archivos reales y se reindexó.

Pruebas nuevas en `tests/Feature/CajaRechazoMotivoT37Test.php`:

1. Sin motivo desde ambas pantallas: vuelve con error visible, caja CERRADA y motivo intacto.
2. Solo espacios/tabulaciones/salto: mismo rechazo, sin reflejo en Cashflow.
3. Más de 500 caracteres o un valor que no es texto: rechazo en castellano,
   sin escritura ni error al reponer el campo anterior.
4. Motivo válido: se guarda recortado, caja RECHAZADA, operativa lo ve en el resumen;
   contado conservado y ningún asiento nuevo en Cashflow.
5. Servicio directo vacío o espacios: `ValidationException`, sin cambios en la caja.
6. Servicio directo: rechaza 501 y acepta 500 caracteres, incluyendo texto acentuado.
7. Ambos formularios requieren motivo; se acepta un texto de un solo carácter.
8. Consultar una rechazada histórica sin motivo no lo rellena.

Primera corrida focalizada: **30 aprobadas, 201 aserciones, 14,15 s**.
Incluye las 8 nuevas y las 22 de `CajaCambioInicialA25Test`, con sus tres rechazos existentes.
Todas las pruebas de Codex se ejecutan exclusivamente en `wings_testing_codex`.

Primera suite completa: **763 aprobadas, 2 omitidas, 14 fallidas, 7249 aserciones,
1035,43 s**; 779 ejecutadas. Los fallos de la ficha del alumno muestran
`Unable to locate file in Vite manifest: resources/js/alumnos-show.js` (los dos
fallos por contenido también recibían HTTP 500/«Algo falló» en esa ficha).
El archivo fuente y su entrada en `vite.config.js` existían; faltaba el compilado local.
Mientras se verificaba, Claude registró `npm run build` en su log T35 y el
manifiesto local pasó a incluir `assets/alumnos-show-_IuC7pus.js` (07:41 del 17/10).
Codex comprobó esa entrada; no cambió código de alumnos ni atribuye un arreglo funcional.

Repetición con código actual y manifiesto completo: **102 aprobadas, 853 aserciones,
86,02 s**. Incluye T37/A25 y las ocho clases con los 14 fallos anteriores.
También cubre reenvío de `motivo` como array: error visible, sin HTTP 500.
Repetición adicional con controlador y servicio anteriores a T37 (`cca651d`),
cargados desde `storage/app/t37-baseline/bootstrap.php`: **69 aprobadas, 622 aserciones,
63,944 s**. El manifiesto ya había cambiado: esa repetición no aísla por sí sola
el efecto de T37. No se reemplazó ningún archivo de aplicación para hacerla.

Segunda suite completa: **784 aprobadas, 2 omitidas, 7395 aserciones, 965,22 s**,
salida 0; **786 pruebas** en `wings_testing_codex`, con manifiesto completo.
Los 14 fallos anteriores ya no se reproducen. Las dos omisiones son capturas
opcionales históricas que requieren `WINGS_CAPTURAS=1`; las de T37 se hicieron por navegador.

La corrida compartida incluye 7 pruebas nuevas de usuarios de Gemini. Antes de
registrar esta entrega quedaron en su propio commit `e4c2424`; Codex no las agrega.
El corte versionado queda en 778 pruebas de HEAD (T35 y T48/T49 incluidos) más 8
propias = **786 pruebas**. Los tres contadores documentales y el resumen coinciden.
Se comprueba además el contador en una copia de ese HEAD más la prueba de T37,
sin escribir en el origen ni usar otra base. No se presenta la corrida local
como una corrida aislada del contenido exclusivo de T37.
Contador del corte fijo `e4c2424` más T37: **1 prueba/7 aserciones verde**.
Guardianes finales después del pase de tablero: **14 pruebas/57 aserciones verdes**.
T37 figura `a_verificar`, hizo Codex, tiene/verifica Claude; no se autocierra.
Sintaxis PHP de controlador, servicio, dos vistas, prueba y preparador correcta.
Sintaxis del capturador JavaScript correcta. `view:cache` y `view:clear` correctos.

## Navegador y capturas reales

Chrome local, cuenta ADMIN sintética del dueño, cuatro cajas sintéticas en
`wings_testing_codex`; sin datos reales. Servidor `http://127.0.0.1:8737`.
Las cuatro combinaciones de pantalla/ancho pasaron: vacío bloqueado por HTML,
espacios enviados y rechazados por servidor con error visible, motivo válido guardado
y mostrado en el resumen RECHAZADA. Resultado medido en
[resultado-navegador.json](evidencia-t37/resultado-navegador.json).

| Origen/ancho | Vacío | Error tras espacios | Motivo guardado en Resumen |
|---|---|---|---|
| Detalle 1366 | [Captura](evidencia-t37/detalle-1366-vacio.png) | [Captura](evidencia-t37/detalle-1366-error-espacios.png) | [Captura](evidencia-t37/detalle-1366-rechazada.png) |
| Resumen 1366 | [Captura](evidencia-t37/resumen-1366-vacio.png) | [Captura](evidencia-t37/resumen-1366-error-espacios.png) | [Captura](evidencia-t37/resumen-1366-rechazada.png) |
| Detalle 360 | [Captura](evidencia-t37/detalle-360-vacio.png) | [Captura](evidencia-t37/detalle-360-error-espacios.png) | [Captura](evidencia-t37/detalle-360-rechazada.png) |
| Resumen 360 | [Captura](evidencia-t37/resumen-360-vacio.png) | [Captura](evidencia-t37/resumen-360-error-espacios.png) | [Captura](evidencia-t37/resumen-360-rechazada.png) |

Control del método: login a [360](evidencia-t37/login-control-360.png) y
[1366](evidencia-t37/login-control-1366.png), inspeccionados; formulario completo dentro del ancho.
Se inspeccionaron las 14 imágenes. El modal, el aviso y el motivo guardado se leen en ambos anchos.
Integridad de las imágenes: [SHA256SUMS.txt](evidencia-t37/SHA256SUMS.txt).
Las capturas finales se renovaron a las 07:47 del 17/10, después de completar el
manifiesto local, y se repitieron los cuatro recorridos con resultado correcto.

Interacción celular con viewport real 360 por CDP, sin usar `--window-size=360`.
Capturas celulares: DOM real entregado por Wings dentro de `iframe srcdoc` de 360,
con ventana de 1366; se comprueba `contentWindow.innerWidth === 360`.
Se usa srcdoc porque `X-Frame-Options: DENY` impide embeber la ruta directamente.
No se fabricó el contenido de las pantallas ni se modificó la protección del servidor.
La imagen celular muestra el DOM después de la interacción; el globo de validación nativo
no se traslada al srcdoc. Su bloqueo se comprobó con `validity.valueMissing` en la página viva.

Observación fuera del cambio: en Detalle a 360 se ve desborde horizontal del bloque
de información/acciones del fondo. T37 conserva esa estructura; no certifica que
toda la pantalla sea responsive. No se arregló ni se atribuye aquí su origen temporal.

## Reproducción

Con dependencias instaladas y recursos compilados (`npm run build`), sin suite
simultánea sobre esta base:

```powershell
$env:DB_DATABASE='wings_testing_codex'
$env:APP_ENV='testing'
php docs/06-pruebas/PRU-04/evidencia-t37/preparar-local.php
$env:SESSION_DRIVER='file'
$env:APP_URL='http://127.0.0.1:8737'
php artisan serve --host=127.0.0.1 --port=8737 --no-reload
# En otra consola, con el servidor activo:
node docs/06-pruebas/PRU-04/evidencia-t37/recorrer-local.mjs
```

El preparador exige exactamente `wings_testing_codex`, genera credenciales efímeras
en `storage/app/t37-local.json` y no las imprime ni las versiona. La suite posterior
rehace la base. Repetir la preparación antes de repetir el recorrido: sus cajas ya quedaron rechazadas.

## Inferencias y pendientes

**Alcance resuelto por Carlos:** «Entregar según el prompt; Inicio queda pendiente». CAJ-029.c de la copia de la tabla pide mostrar
el motivo en Inicio de la operativa. El prompt de T37 pide verlo en Resumen y
autoriza solo las dos vistas de caja. Se leyeron el cuerpo de
`OperativoDashboardController::index` y `operativo/dashboard.blade.php`: el aviso
recibe la cantidad de cajas rechazadas, sin motivos. Por AGENTS §6b se consultó
y Carlos decidió entregar el alcance del prompt dejando Inicio pendiente.
No se implementa Inicio ni se marca todo CAJ-029 cumplido con esta evidencia.

Verificado que la API está deshabilitada en `bootstrap/app.php`. Su request conserva
`required|string|max:1000`; no se modificó. Si se reactivara, una petición de 501–1000
pasaría ese request y fallaría ahora en el servicio: inferencia de lectura, sin ensayo API.

No se presenta esta entrega como verificación independiente ni aprobación visual de Carlos.
El recorrido del dueño fue con una cuenta ADMIN sintética, no una prueba humana de Carlos.
Claude debe repetir código/pantalla y registrar su revisión. Carlos puede revisar las capturas.
Sin despliegue ni cambio de datos del club o del sitio de prueba.
