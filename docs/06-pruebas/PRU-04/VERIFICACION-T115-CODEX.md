# T115 — Control independiente de Codex CAB

01/11/2026, CAB; consultas al servidor 02/11 UTC. Arreglo de Claude
`30ea9a41`, controlado sobre `main` `34730b17`, rama `codex/control-t115`.
Los dos servicios controlados son idénticos a los de `30ea9a41`.
Trabajo compartido sin publicar excluido; base local exclusiva
`wings_testing_codex`, personas y movimientos ficticios. Sin corregir producto
ni desplegar. T119 y T82 llegaron a `main` en `b80d3c9e` y `34730b17`;
se comprobó este último SHA con `git ls-remote`.

**Resultado: aprobado y T115 cerrada, verifica=Codex, tiene=nadie. Sin hallazgos
graves ni medios atribuibles a T115.** La liquidación pasada vacía queda como observación
fuera de alcance, según el pedido. No se hereda la suite completa de Claude.

## Ocho casos ejecutados

Pedidos HTTP reales a Laravel con administrador autenticado y lectura de las
filas resultantes. El reloj se simuló exclusivamente dentro de las pruebas locales.
Se inspeccionó el HTML que Wings devuelve; no se hizo un recorrido en navegador
ni se acredita una aprobación visual con estas pruebas.

| Caso | Resultado comprobado |
|---|---|
| 1. HORA, diciembre 2027 el 2/1/2027 | Rechazo con «No se puede liquidar un mes que todavía no empezó. Revisá el mes y el año.». Liquidaciones, detalles, clases y Cashflow intactos. |
| 2. COMISION, mismo período | Mismo aviso y conservación de las cuatro tablas. |
| 1–2. Formulario después del rechazo | GET 200; conserva profesora elegida, diciembre y 2027. El radio sigue habilitado. Reenvío cambiando únicamente el año a 2026 genera la liquidación. No se afirma un importe positivo para COMISION: este fixture no tiene cuotas cobradas. |
| 3. Febrero 2027 | Rechazado para ambas modalidades el 2/1 y el 31/1 a las 23:59:59, incluso enviando `incluir_hoy=1`; sin escrituras en las cuatro tablas. |
| 4. Diciembre 2026 con clase dictada | HORA: una clase de una hora produce un detalle de $1.000; recalcular conserva $1.000. Cerrar deja CERRADA; pagar deja PAGADA y un solo egreso de $1.000. Repetir el pago no lo duplica. |
| 5. Enero 2027, con/sin hoy | Una clase del 1/1 y otra del 2/1 sin validar: sin hoy, un detalle/$1.000; con hoy, dos/$2.000 y clase de hoy validada. |
| 6. Valores inválidos | Mes 13 y 0, años 2019 y 2028, letras en mes o año: redirección con rechazo, sin 500 ni cambios en las cuatro tablas. |
| 7. Inactiva, pendiente de diciembre | Se ofrece habilitada en el formulario; el POST genera su clase pendiente: un detalle/$1.000. |
| 8. Medianoche argentina | A `2027-02-01 02:59:59 UTC`, Wings está en `31/1 23:59:59` y rechaza febrero. A `03:00:00 UTC`, está en `1/2 00:00:00` y permite febrero. Zona comprobada: `America/Argentina/Buenos_Aires`. |

También se comprueba que el formulario inicial del 2/1 propone **enero de 2027**;
no se cambió ese valor por defecto. [Código independiente](evidencia-t115-codex/ControlT115CodexTest.php),
[ejecución](evidencia-t115-codex/control-http.txt), formularios devueltos por Wings
[HORA](evidencia-t115-codex/aviso-HORA.html) y
[COMISION](evidencia-t115-codex/aviso-COMISION.html), con CSRF redactado.

## Otros caminos, liquidaciones anteriores y manual

**Leído, alcance de la búsqueda:** grafo de llamadas y búsqueda actual en `app/`,
`routes/` y `database/seeders/`. En aplicación, la creación está en
`LiquidacionService.php:42`; llaman al método el controlador web (`:262`) y
`LiquidacionController.php:28`. El servicio rechaza antes de la transacción
(`LiquidacionService.php:31`). La API está deshabilitada en `bootstrap/app.php:17`;
`route:list --path=api` no encontró rutas. Se ejecutó además la llamada directa
al servicio para HORA y COMISION: mismo rechazo, sin escrituras.

El seeder de sueldos llama al servicio (`ReportesSueldosEscenarioSeeder.php:36`).
Hay creaciones directas de ficción en `ReportesEscenarioSeeder.php:100` y
`DemoSeeder.php:671`, que no pasan por esta defensa; no son otro formulario o
ruta de generación. No se ejecutó DemoSeeder ni se certifican herramientas
externas a ese alcance.

**Ejecutado, futura heredada:** se preparó localmente la forma que dejaba el error:
ABIERTA, diciembre 2027, vacía. Recalcular termina correctamente y conserva $0;
no vuelve a pasar por generar. Cerrar la rechaza hasta el **01/01/2028**, con
aviso de negocio; pagar la rechaza por seguir ABIERTA. No hubo 500 ni egreso.
Fuentes leídas: `LiquidacionService.php:286`, `:311`,
`Liquidacion.php:263` y `LiquidacionPagoService.php` (validación de CERRADA).
No se fabricó una liquidación futura CERRADA para atribuirle el comportamiento
de la ABIERTA que producía el defecto original.

**Sitio de prueba, comprobado en solo lectura:** el 02/11 a las 02:07:46 UTC,
Wings indicaba `05/12/2026 23:59:59 -03:00`. Transacción MySQL `READ ONLY`,
SELECT de períodos futuros y muestra de cinco liquidaciones existentes:
**cero futuras**, muestra reciente noviembre 2026/CERRADA. No se movió el reloj
ni se escribieron datos del sitio. [Resultado](evidencia-t115-codex/sitio-solo-lectura.json).
El primer intento usó el PHP por defecto (7.4) y no llegó a iniciar Laravel;
la consulta válida se repitió con `/opt/remi/php82/root/usr/bin/php`.

**Manual, leído:** la nueva pregunta en
`resources/manual/admin/generar-liquidacion-profesor.md:46` reproduce el aviso
y explica cambiar también el año para diciembre. Coincide con los envíos
ejecutados. Esto valida esa incorporación, no todas las instrucciones previas.

## Observación pedida: mes pasado vacío

**Comprobado, fuera de T115:** sin clases pendientes, el radio de la profesora
está deshabilitado. Con una clase pendiente de diciembre, al elegir noviembre
2026 el radio queda habilitado y el POST normal genera noviembre con $0 y sin
detalles. Por lo tanto, **sí es alcanzable desde los controles del formulario**
si hay pendientes de otro mes; no hace falta forzar un ID deshabilitado.
Se verificó por GET/DOM y POST; no se pulsó en navegador.

`resources/views/liquidaciones/create.blade.php:83` decide la habilitación por
`clases_count_total`, no por el período elegido; `resources/js/liquidaciones-create.js:49`
actualiza los conteos, sin recalcular ese atributo. Se deja como dato para el
trabajo posterior que Carlos excluyó expresamente de este control.

## Reportes: comparación antes/después sobre los mismos datos

Se cargó **una sola vez** el escenario ficticio de Reportes (abril–octubre).
Se comparó el servicio completo anterior a `30ea9a41` con el actual, sin cambiar
los datos entre lecturas: siete meses × todos los deportes y cada deporte
individual, **21 combinaciones**, más evolución y dos GET reales a `/reportes`.
La copia histórica solo adapta el nombre de clase para poder ejecutar ambas
versiones sin modificar `app/`.

**Comprobado:** todos los campos y todas las filas, comparados como conjuntos
con multiplicidad, son iguales. Las **14 diferencias** entre los resultados
sin normalizar corresponden exclusivamente al orden. Ingresos, egresos,
resultado, deuda, por pagar y disponible conservan sus centavos; también
coinciden reporte/evolución entregados a la vista por HTTP 200. Las **4.585
consultas** de la comparación de servicios fueron SELECT/SHOW; no hubo escrituras.
Octubre global conserva ingresos $660.000, egresos $75.000, resultado $585.000,
deuda $690.000, por pagar $90.000 y disponible $4.360.000.

[Comparador](evidencia-t115-codex/ComparacionReportesT115CodexTest.php),
[servicio anterior](evidencia-t115-codex/ReporteMensualServiceAntesT115.php),
[valores por combinación](evidencia-t115-codex/comparacion-reportes.json) y
[ejecución](evidencia-t115-codex/comparacion-reportes.txt).

**Inferido de las consultas actuales:** el desempate en
`ReporteMensualService.php:214` ordena por período y origen; resuelve los empates
entre orígenes distintos que aparecen en este escenario. No es un orden total
formal de todo el GROUP BY: también agrupa persona y deporte. Solo persistiría
un empate en esa consulta si un mismo período/origen produjera más de uno de
esos grupos. El observer conserva el deporte del primer evento
(`HistorialReporteObserver.php:43`); no se reprodujo ese empate por un flujo web
normal y no se lo presenta como un defecto comprobado.

Sí quedan otras listas del servicio sin orden explícito: cargos de punitorios
(`ReporteMensualService.php:143`), inscripciones (`:179`), movimientos confirmados
y pendientes (`:229–231`) y agrupaciones de alumnos (`:67`). Su orden SQL no
está garantizado. La vista consume las filas de deuda/pagos en el orden recibido
(`resources/views/reportes/index.blade.php:92`, `:101`). Esta es una conclusión
de lectura; no se provocó una alternancia entre dos GET idénticos ni se observó
un importe distinto. El desempate nuevo no promete ordenar esas otras listas.

## Pruebas y límites de la entrega

- Originales T115: **3 aprobadas, 16 aserciones**, 14,96 s, salida 0.
- Control propio HTTP/servicio: **11 aprobadas, 121 aserciones**, 45,56 s,
  salida 0; incluye la propuesta inicial enero/2027. El primer pase, previo a
  agregar esa comprobación, también pasó (11/118); se conserva la salida final.
- Comparación de Reportes: **1 aprobada, 4.612 aserciones**, 37,76 s, salida 0.
- Pruebas del área Reportes: **21 aprobadas, 194 aserciones**, 468,40 s,
  salida 0. [Ejecución](evidencia-t115-codex/reportes-area.txt).
- Suite rápida propia: **1.377 aprobadas, 6 omitidas, 10.051 aserciones**,
  378,69 s, salida 0. Inicio 23:16:57 y fin 23:23:17 CAB del 01/11.
  [Resumen](evidencia-t115-codex/suite-rapida.txt); salida completa conservada
  localmente en `storage/logs/t115-suite-rapida-completa.txt` (ignorada por Git).

Mutaciones de Claude leídas en `evidencia-t113/mutaciones-control-claude.txt`;
no se heredan ni se declaran reejecutadas. No se corrió suite completa propia:
es un control sin cambios de aplicación; sí se ejecuta el archivo lento del área
Reportes y la rápida solicitada. Durante las dirigidas y al iniciar la rápida
seguía una completa ajena en otra base: reserva de Claude T119 EN CURSO,
con «Las demás bases pueden seguir». No hubo otra prueba propia simultánea
contra `wings_testing_codex`. Las pruebas propias viven solo en la evidencia.

Grafo reindexado tras actualizar main; se comprobó cobertura de los archivos
utilizados. El MCP devolvió `coverage_unavailable/metadata_changed` y cobertura
parcial de Blade: se leyó el código real, y las ausencias se limitan a las rutas
y carpetas indicadas, sin asumir que el índice prueba inexistencia global.
Se revisan enlaces, sintaxis de la evidencia, integridad y diff; vistas/CSS de
producto y tabla de pruebas permanecen sin cambios.

### Integración de main antes de publicar

Durante la rápida se publicaron cambios ajenos. Se integró `origin/main`
`1cf07513` conservándolos: los dos servicios controlados, controlador y modelo
de liquidaciones, pago, formulario, JavaScript propio y pregunta del manual
no cambiaron respecto de `34730b17` (diff comprobado). El conflicto del tablero
se resolvió tomando ese main y regenerando **solo T115** con el comando oficial.
T116/T117/T118/T119 conservaron sus estados publicados, sin atribuir a este
control sus verificaciones posteriores.

Sobre esa integración se ejecutaron nuevamente los dos controles propios:
**12 aprobadas, 4.733 aserciones, 38,43 s, salida 0**.
[Ejecución posterior](evidencia-t115-codex/control-tras-integrar-main.txt).
La comparación JSON conserva las 21 combinaciones, mismos importes y 14 cambios
solo de orden, e incluye las huellas de los dos archivos de servicio.
Los formularios guardados se regeneraron en este pase.
La rápida y las 21 pruebas del área conservan su corte original explícito:
no certifican los cambios concurrentes de otros agentes.
La nueva regla publicada en `1cf07513` limita la completa a una por día en todo
el proyecto; este control no lanzó ninguna completa.
