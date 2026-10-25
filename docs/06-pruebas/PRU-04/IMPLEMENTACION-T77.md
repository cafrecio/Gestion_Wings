# T77 — Cobrar muestra solamente la deuda guardada

Codex CAB, 25/10/2026. **Implementada localmente; aspecto aprobado por Carlos. Control independiente de lógica pendiente. No desplegada.**

Decisión de Carlos: «Si no tiene deuda, ¿por qué ofrece cobrar octubre? No se puede cobrar dos veces.»
Autorización de trabajo y vista en este chat: «Hace lo que tengas que hacer, para eso te paso el prompt.
Despues yo apruebo la parte visual». Después de ver la captura corregida, Carlos aprobó el aspecto:
«Ahora esta Ok» (25/10/2026). La revisión independiente de reglas sigue pendiente.

## Cambio

`CajaWebController::cobrar` deja de agregar una `DeudaCuota` corriente en memoria. La lista y «Total
pendiente» toman únicamente las cuotas guardadas. El selector T18 admite el corriente cuando no
tiene cuota, además de los doce futuros, con el mismo plan aplicable, tarifa y descuento de primera
cuota. Los períodos guardados (incluidos pagados/condonados) no se ofrecen en ese selector.

La selección manual envía `periodos_manuales[]`. `pagar` verifica, bajo bloqueo del alumno y dentro
de la transacción, que el corriente ausente se haya elegido a mano y tenga importe explícito.
Enviar solo `periodos[]` no crea ni cobra la cuota. `PagoCuotaService` conserva su implementación:
creación, descuentos, parciales, imputación, resolución de Revisión, pago operativo/cashflow y
rechazo de un segundo cobro siguen por sus cuerpos existentes, leídos para esta tarea.

El corriente no adopta la regla de congelar un precio libre de los meses futuros. El precio del plan
y los parciales conservan las reglas previas. La previsualización del primer pago y del cambio de
plan incluye al corriente manual para no anunciar un precio distinto del que registra el servidor.

## Alcance comprobado

- Buscador de Cobrar: `cobrarCuotaSelect` obtiene `saldoDeAlumnos` y lista por deuda guardada;
  permite buscar al alumno al día. No se modifica.
- Cobranza: `CobranzaWebController::index`, `listadoCobranza`, `resumenDashboard` y `saldoDeAlumnos`
  leen cuotas/cargos guardados. No se modifican.
- Ficha: `AlumnoWebController::show` consulta `estadoAlumno`, relaciones de cuotas y pagos;
  la vista obtiene `deudaCuotas` de la relación. No se modifica.
- Inicio operativo: usa `listadoCobranza`; Inicio ADMIN: recibe el reporte, cuya deuda se reconstruye
  con eventos de cuotas guardadas (`saldoHistorico`), inscripciones y punitorios. No se modifican.
- El recorrido de grafo se contrastó con fuentes reales. La cobertura del índice informó metadatos
  cambiados/no disponibles y rangos parciales de Blade; se leyeron directamente los cuerpos/rangos.

Sin servidor, despliegue, bases `wings`/`wingstest`, reparación de datos ni cambios de tabla de pruebas.
T77 no modifica `caja/detalle`, `alumnos/show`, `ReporteMensualService`, `CashflowWebController`,
`SecurityHeaders` ni CSS compartido. Durante el trabajo aparecieron cambios de T78 y T80 en la
carpeta compartida; se conservan y quedan fuera del commit propio. Las cifras de suite describen
el código que cargó cada corrida y no certifican trabajos ajenos todavía en curso.

## Pruebas y evidencia

`tests/Feature/CobrarMesSinDeudaT77Test.php`: nueve pruebas permanentes. Cubren ausencia de fila,
total $0 y ninguna escritura al entrar, selector corriente + doce futuros, envío como deuda rechazado
para ambos roles sin alterar filas financieras, cobro manual, reintento desde ambos roles rechazado,
cashflow ADMIN, deuda guardada que sigue ofreciéndose/cobrándose, Revisión resuelta al pagar,
elección sin importe rechazada y coherencia de las otras pantallas.

Las pruebas históricas de primera cuota/descuentos se adaptan a la elección manual cuando no
guardaron una cuota. Sus importes esperados y comprobaciones financieras se conservan. T18 del
total pendiente ahora crea la deuda de octubre que su escenario ya declaraba tener.

Primer corte dirigido: 9 aprobadas/61 aserciones, 24,78 s. No certifica los ajustes posteriores.
Último corte dirigido: **62 aprobadas, 410 aserciones, 33,16 s, salida 0** en `wings_testing_codex`;
[salida](evidencia-t77/dirigidas.txt). Primera rápida de diagnóstico: 905 aprobadas, 6 omitidas y
12 fallidas (279,99 s); escenarios antiguos con cuota ausente y previsualización de descuento.
Se conserva [esa salida](evidencia-t77/suite-rapida-inicial.txt). Luego se adaptó la elección manual
y se conservó la previsualización; la dirigida anterior pasó sin cambiar los importes esperados.
Segunda rápida: **916 aprobadas, 6 omitidas, 4 fallidas, 7351 aserciones, 251,27 s**;
[salida](evidencia-t77/suite-rapida-con-t78-en-curso.txt). Tres fallos son del fixture nuevo de T78
(constante de deporte inexistente en el código cargado por esa corrida), y uno del contador:
al incorporar las tres pruebas de T78 el total pasó de 1038 a 1041 durante el trabajo paralelo.
Se actualizó el contador de los tres documentos a **1041 métodos: 9 nuevos propios y 3 nuevos ajenos**.

Repetición aislada posterior, sin modificar T78: **3 aprobadas, 1 fallida, 24 aserciones, 12,02 s**;
[salida](evidencia-t77/repeticion-ajenas-y-contador.txt). Contador y dos pruebas T78 verdes; la otra
crea un `Pago` sin el campo obligatorio `mes`. No se atribuye causa ni se repara trabajo ajeno.
Suite completa posterior en `wings_testing_codex`: **1034 aprobadas, 6 omitidas, 1 fallida,
8959 aserciones, 1073,34 s, salida 2** ([salida completa](evidencia-t77/suite-completa.txt)). El único
fallo está en el fixture T78: inserta un movimiento sin `usuario_id`; no es un fallo de T77.

Después del ajuste visual y de los cambios ajenos del fixture: **68 aprobadas, 442 aserciones,
31,80 s, salida 0** ([dirigidas finales](evidencia-t77/dirigidas-finales.txt)). Incluye las 62 del
área, las tres T78, el contador y los dos controles de CSP. No se presenta la corrida completa
como verde ni como aceptación de tareas ajenas. El fallo ajeno se repitió sobre su archivo actual
y sus tres pruebas pasaron; no se modificó ese archivo. Sin despliegue.

El HTML `evidencia-correccion-t34/sin-presentes.html` se devolvió con `git checkout --` después de
la completa. Se comprobó que el diff solo tenía nombres Faker, IDs y tokens generados por el test;
por la revisión automática se conservó antes una copia idéntica en
`storage/app/t77-01a172b6-sin-presentes-generado.html` (ignorada), sin borrar contenido irrecuperable.
La salida de diagnóstico inicial omite tres tokens CSRF de las sesiones descartables; las salidas
de texto normalizan espacios al final de línea y líneas vacías al final del archivo, sin cambiar resultados.

Build Vite, sintaxis PHP de cada archivo propio y JavaScript, compilación/limpieza de vistas:
[salida estática](evidencia-t77/validacion-estatica.txt) y [build](evidencia-t77/build.txt).
Faltaba Vite en la instalación local; se ejecutó `npm ci` con el lock existente, sin modificar
dependencias versionadas ([salida](evidencia-t77/npm-ci.txt)).

## Navegador y capturas reales

[Galería para Carlos](APROBAR-T77.html): sin deuda, selector corriente, mes agregado a mano y deuda
guardada, cada una a 1366 y 375. [Ingreso de control](evidencia-t77/login-375.jpg).
Chrome real contra `http://127.0.0.1:8777`, con `wings_testing_codex` y dos alumnos sintéticos;
[fixture inicial](evidencia-t77/fixture-navegador.json) y [preparador reproducible](evidencia-t77/preparar-navegador.php).
El preparador rechaza cualquier otra base/entorno antes de migrar; la cuenta recibe su clave desde
una variable temporal, no desde el repositorio. La base se restableció después del cobro para
recapturar el estado inicial; no se repitió el cobro.

Cobro manual por clics: «Pago registrado», un movimiento por $30.000, una cuota `PAGADA`, un pago
y una imputación de $30.000. [Confirmación](evidencia-t77/cobro-confirmado-1366.jpg) y
[filas concretas](evidencia-t77/cobro-navegador.json). Consola: [sin errores/avisos capturados](evidencia-t77/consola.json).
El rechazo del segundo envío se prueba en PHPUnit para ambos roles; no se afirma una segunda
operación de cobro hecha en el navegador.

**Método móvil:** el iframe a 375 devolvió vacío: los encabezados locales eran `X-Frame-Options: DENY`
y CSP con `frame-ancestors 'none'`. No se modificaron. Se usó la emulación de viewport de Chrome
a 375 px reales; las [medidas](evidencia-t77/mediciones-navegador.json) muestran `innerWidth=375`
y sin desborde horizontal. No es la ventana mínima de 500 recortada. Se verificó el ingreso completo
y se renovaron las capturas tras terminar la transición del menú. Queda documentada esta diferencia
respecto del método iframe de AGENTS. Servidor local detenido y ancho de Chrome restaurado.

### Observación visual de Carlos y ajuste móvil

Carlos señaló que el importe quedaba chico y desalineado con el mes, y envió
[esta imagen](evidencia-t77/observacion-carlos-importe.png). No aprobó ese aspecto.
Se ajustó únicamente la vista de Cobrar: en celular el mes y el importe ocupan la misma columna
completa; Agregar queda debajo a la derecha. Desde `sm` se conserva la fila y el ancho anterior
de 150 px. Sin cambios de CSS compartido, componente de importes, JavaScript ni reglas de cobro.
La completa ya estaba en ejecución al cambiar esas tres líneas de presentación. Las fuentes PHP
financieras permanecieron iguales; las 68 pruebas finales cubren la vista corregida.
Para renovar imágenes sin cruzarse con otra suite activa, se creó `wings_testing_codex_t77_visual`,
vacía y propia ([fixture](evidencia-t77/fixture-visual-corregido.json)). La suite completa y las
dirigidas usaron siempre `wings_testing_codex`. La migración visual exigía mantenimiento para su
historial: se activó solo en la caché en memoria del preparador, con esa base nueva sin tráfico;
no se puso en mantenimiento la aplicación compartida ni se modificó el servicio.
Capturas renovadas de las cuatro situaciones a ambos anchos. Medición final: Mes e Importe a
375 comparten `x=40`, `width=280`, `right=320`; Agregar mide 96 px, termina en 320 y queda 8 px
debajo del importe. A 1366 el importe sigue midiendo 150 px. [Medidas](evidencia-t77/mediciones-correccion-visual.json)
y [consola final](evidencia-t77/consola-corregida.json). Carlos aprobó esta corrección después de
recibir en el chat la imagen `selector-375.jpg`: «Ahora esta Ok» (25/10/2026).
La aprobación visual corresponde a las capturas de [la galería](APROBAR-T77.html), con
[huellas guardadas](evidencia-t77/imagenes.json), y al cambio entregado en `5f618c9c`.
Carlos verifica el aspecto según AGENTS.md §6a; esa aprobación no verifica la lógica financiera.

## Propuesta para COB-028 — solo Carlos/Claude actualizan la tabla

Fuente leída: `tabla-de-pruebas/catalogo/COB.json`, líneas 1419–1464 en el corte inicial.

| Punto | Texto actual | Texto propuesto |
|---|---|---|
| Acción, línea 1419 | «Entra a cobrarle a cada uno.» | «Entra a cobrarle a cada uno; comprueba que no se ofrece deuda y elige el mes actual en el selector.» |
| a, línea 1426 | «Ofrece la cuota del mes al precio de su plan aunque todavía no exista.» | «El mes sin cuota guardada no aparece entre pendientes, no está marcado ni suma al Total pendiente. Puede elegirse a mano en el selector, con el precio aplicable del plan.» |
| b, línea 1432 | «Al cobrar se crea y queda pagada.» | «Al elegir manualmente el mes y confirmar el importe completo se crea y queda pagada; enviarlo solo como deuda o repetir un cobro saldado se rechaza sin escrituras.» |

c (sale de Revisión al pagar), d (Al día) y e (entrar sin cobrar no crea cuota) conservan su regla.
La fuente y la referencia de tarea deberían incorporar T77; no se modifica la copia ni DU-12.

## Entrega y siguiente paso

Contrato: [Cuotas, deudas y pagos V2](../../02-contratos/Wings-contrato-cuotas-deudas-pagos-V2.md#3b-auto-creación-de-deudacuota-período-vigente-o-futuro).
Tablero T77 registrado en `a_verificar`, tiene nadie, hizo Codex, con referencia a esta entrega.
Guardianes finales del contador/tablero: **7 aprobadas, 34 aserciones, salida 0**
([salida](evidencia-t77/guardianes-finales.txt)). Aspecto aprobado por Carlos: «Ahora esta Ok».
Otro agente debe verificar reglas/código; no autocierre. Commit solo propio, sin push manual.
Al registrar esta aprobación, `ESTADO-ACTUAL.md` tenía cambios ajenos en curso: se conserva sin
editar ni incluir en el commit. Su frase de aprobación pendiente debe sincronizarse cuando quede libre;
el estado vigente de la aprobación queda registrado aquí, en la galería, el resumen y el tablero.
