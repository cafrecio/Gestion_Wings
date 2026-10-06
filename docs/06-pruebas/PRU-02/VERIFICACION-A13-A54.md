# Verificación independiente — A13, B1, A54 y A55

**Autor:** Codex CAB. **Ensayos:** 05/10/2026. **Registro y contraste final:** 06/10/2026.
Entregas de Claude: `4571dbc` (A54/A55) y `8869263` (A13/B1), integradas en main.
Navegador local con usuarios ficticios ADMIN/OPERATIVO, MariaDB `wings_testing_codex`.
No se tocó la base del club ni el servidor. No se corrigió código de aplicación.

## Dictamen

| Defecto | Resultado independiente | Estado que deja este informe |
|---|---|---|
| A54 | No cuenta como deudor a quien tiene saldo cero; conserva la oferta de cobro adelantado | **CERRADO**, verificado por Codex |
| A55 | Una inscripción por persona se muestra distinta en selector y ficha cuando hay dos deportes | **ABIERTO**, devuelto para corrección |
| A13 / B1 | Cobro sin caja y reversión financiera funcionan; historial y representación del contraasiento tienen errores | **ABIERTOS**, devueltos para corrección |

Los tres hallazgos se registran **dentro de esos IDs**, sin crear otros: A55 no cumple
la coincidencia de saldos que prometía; los otros dos son parte de la cadena de anulación
exigida a A13/B1. Dividirlos inflaría el seguimiento de la misma entrega.
El avance pasa de 25 a **26 cerrados de 71**, con **45 abiertos**. No se cierra P3 ni A25.

## Evidencia y alcance

[Carpeta ordenada y reproducción](evidencia/verificacion-a13-a54/README.md).
Capturas reales del navegador del 05/10; PDFs originales generados por Wings y sus renders.
[Estado concreto de la base descartable, releído el 06/10](evidencia/verificacion-a13-a54/estado-descartable-2026-10-06.json):
deudas, pagos, imputaciones, cajas y asientos, sin usuarios ni credenciales reales.
Los pasos modifican esa misma base en secuencia: las capturas no representan todas el mismo instante.

El 06/10 se releen los cuerpos de `saldoDeAlumnos`, `inscripcionPendientePorAlumno`,
`anularCobroAdmin`, `generarReciboCuota` y el historial real. El grafo se reindexó y se
contrastó con archivos, porque informó frescura parcial. Hay un **cambio ajeno sin commit
en cashflow**: se conserva fuera de esta entrega; no se certifica ese arreglo con capturas
anteriores ni se dice que el defecto siga vigente después de una futura corrección.

## 1. Circuito del OPERATIVO

**Esperaba:** caja propia, movimiento operativo, cierre y validación sin usar el circuito ADMIN.

**Verificado:** cobro por servicio de $48.000, pago 4, movimiento 1, caja 1 del operativo.
`cerrarCajaOperativa` dejó CERRADA; `validarCaja`, ejecutado dos veces, dejó VALIDADA y
un único ingreso de $48.000 al cashflow. Más tarde se cobró por **pantalla** el saldo de
$40.000 del alumno G: pago 12, movimiento 2, caja 2 del mismo operativo, sin ingreso directo
al cashflow antes de validar. [Caja](evidencia/verificacion-a13-a54/operativo-cobro-caja.png)
y [resumen por medio de pago](evidencia/verificacion-a13-a54/operativo-resumen.png).

**Límite importante:** el cierre operativo se comprobó **por servicio, NO por pantalla**.
La confirmación de Cerrar trabó el control del navegador; no se da por aceptada. La caja 2
seguía ABIERTA en la lectura final. El resumen real muestra Efectivo $40.000 y neto $40.000.
Esto no certifica un arqueo físico ni demuestra que exista conteo de billetes.

## 2. Anular ADMIN: parcial, varios meses e inscripción

**Esperaba:** restituir solo las imputaciones de ese pago, conservar otros cobros y reabrir inscripción.

**Verificado, parcial:** deuda original $48.000; primer pago 1 de $15.000 ($10.000 cuota +
$5.000 inscripción); segundo pago 2 de $8.000. Al anular el primero: deuda PENDIENTE,
$8.000 pagados, $40.000 de cuota restantes; inscripción $5.000 pendiente. El segundo pago
permanece COMPLETADO. [Ficha ADMIN](evidencia/verificacion-a13-a54/ficha-parcial-anulado.png).

**Verificado, varios meses:** pago 3, fecha 30/09, $63.000: septiembre $48.000, octubre
$10.000, inscripción $5.000. Anular restituyó ambas cuotas a $48.000 originales, cero
pagado, PENDIENTE, más inscripción $5.000. El pago mantiene importe y detalle de ambos meses.
[Ficha](evidencia/verificacion-a13-a54/ficha-varios-meses-anulado.png) y filas del JSON.
El historial de esa ficha solo enumera septiembre: también pierde octubre, por el hallazgo del punto 4.

## 3. Cashflow, saldo y fecha del contraasiento

**Esperaba:** no borrar el asiento original; crear su inverso y conservar el saldo correcto.

**Verificado en filas:** pago 3 tiene originales +$58.000 y +$5.000 del 30/09 y
contraasientos −$58.000 y −$5.000 del **05/10**, fecha de anulación. Suma neta cero.
Pago 5, Transferencia: +$48.000 y −$48.000, neto cero; el balance de la pantalla también es cero.

**Falla visual comprobada el 05/10:** las dos filas del pago 5 aparecen como **I**, verdes,
$48.000 sin signo negativo. [Pantalla](evidencia/verificacion-a13-a54/cashflow-transferencia-anulada.png).
La versión ensayada elegía signo/color por rubro y aplicaba `abs(monto)`; Cuotas es INGRESO
aunque el contraasiento tenga monto negativo. **No se perdió plata: la presentación miente.**
El arreglo ajeno visto el 06/10 no se incluye ni se aprueba en esta verificación.

**Evaluación, no nueva regla de negocio:** fechar la reversión el día de anulación es
coherente con registrar cuándo ocurrió y no reescribir el mes original. Llevarla al mes del
cobro alteraría el histórico; requeriría una decisión explícita, no cambiarlo durante este control.

## 4. Recibo anulado e historial

**Esperaba:** PDF con importe y períodos originales, marcado ANULADO; historial con los mismos períodos.

**PDF verificado:** pago 3 conserva septiembre $48.000, octubre $10.000, inscripción $5.000,
total $63.000 y Efectivo. Pago 5 conserva **noviembre $48.000**, Transferencia. Ambos tienen
marca de anulación y fecha. Se generaron desde `ReciboService`, se extrajo texto y se
inspeccionaron renders: [PDF 3](evidencia/verificacion-a13-a54/recibo-3-anulado.pdf),
[imagen 3](evidencia/verificacion-a13-a54/recibo-3-anulado.png),
[PDF 5](evidencia/verificacion-a13-a54/recibo-5-anulado.pdf),
[imagen 5](evidencia/verificacion-a13-a54/recibo-5-anulado.png).

**Historial falla:** tras anular pago 5, la ficha dice **Oct 2026**, aunque cobró Nov 2026.
[Captura](evidencia/verificacion-a13-a54/historial-mes-equivocado.png). `detalle_anulacion`
conserva noviembre y el PDF lo usa. La ficha busca la relación viva `deudasCuota`, ya retirada
por la anulación; si está vacía, usa `pago.mes/anio`, que no representa los meses imputados.
El mismo camino explica que el pago de dos meses muestre solo uno. Queda dentro de A13/B1.

## 5. Permisos del OPERATIVO

**Esperaba:** que no pueda anular **el cobro ADMIN** desde menú, ficha ni URL directa.
No significa prohibir la cancelación legítima de sus movimientos por el circuito de caja.

**Verificado:** ficha sin acción/modal de anulación ADMIN; POST `/pagos/2/anular` responde
403 y conserva el pago COMPLETADO y la cantidad de asientos. No existe movimiento operativo
para ese pago. La ruta antigua con movimiento inexistente responde 404, sin modificarlo.
[Ficha OPERATIVO](evidencia/verificacion-a13-a54/operativo-ficha-sin-anular.png).
Ensayo HTTP independiente: 2 pruebas, 9 aserciones, en la herramienta adjunta.
El 404 de un ID inexistente no demuestra por sí solo todas las combinaciones de permisos;
el 403 y la ausencia de movimiento sí comprueban este cobro ADMIN por el camino nuevo.

## 6. Concurrencia

**Esperaba:** no duplicar reversión ni perder un cobro válido ante pedidos simultáneos.

**Verificado con dos procesos PHP/conexiones MariaDB:** primera transacción retenida 3 s.

| Orden forzado | Segundo pedido | Resultado observado al finalizar |
|---|---|---|
| Anular, luego cobrar | Esperó 2,712 s | Seña $10.000 revertida; nuevo $8.000 conservado |
| Cobrar, luego anular | Esperó 2,792 s | Nuevo $8.000 conservado; solo se revierte la seña |
| Anular, luego anular | Esperó 2,729 s; rechazado | «Este cobro ya fue anulado»; un solo contraasiento |

Se comprobó deuda e imputaciones y un único par original/inverso para cada pago. El alumno G
fue después cobrado por OPERATIVO: su saldo actual cero es posterior al ensayo de concurrencia.
También se anularon desde dos pestañas con una ficha anterior al primer pedido: la segunda
fue rechazada y no creó otro contraasiento.
[Segunda pestaña](evidencia/verificacion-a13-a54/doble-pestana-rechazada.png).

**Inferencia limitada:** `lockForUpdate` sobre el pago basta para serializar la doble
anulación de ese pago, pero **no es la única protección** del cruce cobrar/anular. El servicio
también bloquea inscripción por DNI, alumno y deudas; esos recursos compartidos participan
del resultado. Estos tres órdenes pasaron: no es prueba exhaustiva de todos los intercalados,
deadlocks, cortes de conexión o recuperación tras caída.

## 7. Contador y saldo entre selector, Cobranza y ficha

**Esperaba:** contar solo saldos positivos y mostrar saldo consistente, incluida inscripción.

**A54 verificado:** caso inicial, tres registros positivos ($45.000, $101.000, $5.000),
encabezado 3 y lista de cuatro porque ofrece además al alumno D sin deuda para adelantar.
[Selector](evidencia/verificacion-a13-a54/selector.png). Se forzó aparte una cuota
**PENDIENTE de monto original cero**: no incrementó el contador ni apareció como deudor.
Con los casos de concurrencia había ocho saldos positivos y encabezado 8.
[Caso cero](evidencia/verificacion-a13-a54/selector-cero-pendiente.png). **A54 pasa.**

**Un deporte:** selector $45.000/$101.000/$5.000, Cobranza total $151.000 y ficha con cuota
e inscripción separadas coinciden. [Cobranza](evidencia/verificacion-a13-a54/cobranza-todos.png).

**Dos deportes, A55 falla:** misma persona ficticia, DNI 47130005, alumno 5 Patín y alumno
11 Fútbol; una única inscripción $5.000 asociada al alumno 5. Selector: Patín $5.000,
Fútbol **$0**. Ficha de Fútbol: inscripción pendiente **$5.000**.
[Selector](evidencia/verificacion-a13-a54/selector-dos-deportes.png),
[ficha del segundo deporte](evidencia/verificacion-a13-a54/ficha-segundo-deporte-inscripcion.png).

**Causa contrastada en código el 06/10:** `inscripcionPendientePorAlumno` elige primero
`cargo.alumno_id`, y solo busca por DNI si ese registro no está en la colección; adjudica
a uno. La ficha consulta el cargo por DNI. No es correcto afirmar que las tres pantallas
usan exactamente el mismo cálculo. No se duplicó el cargo ni se decide acá cómo repartirlo.
Claude debe corregir la inconsistencia respetando inscripción única por persona.

## 8. Cobro adelantado A3

**Esperaba:** que el filtro de saldo no elimine a quien puede adelantar el mes y que ADMIN no abra caja.

**Verificado por pantalla:** alumno D, saldo cero, permanece seleccionable; se cobró
noviembre $48.000 por Transferencia, pago 5, deuda noviembre PAGADA y sin movimiento ni
caja ADMIN. Luego se anuló: deuda noviembre vuelve a PENDIENTE en $48.000, pagado cero;
detalle y recibo preservan noviembre. Ver JSON y recibo 5.

**Límite:** la corrida mensual no se recorrió manualmente en navegador. La prueba existente
`P2Entrega2FichaCobroAdelantadoTest` pasó dentro de la selección automatizada; no se presenta
eso como observación humana del día 1. Se descartan tres capturas iniciales con assets
incompletos, sin usarlas para aprobar aspecto visual de esa pantalla.

## Pruebas y publicación

Resultados efectivamente obtenidos el **05/10**, no una suite nueva del 06/10:

- `SaldoUnicoPorAlumnoTest|VerificacionEntrega2Test|AdminCobraSinCajaTest|P2Entrega2FichaCobroAdelantadoTest`:
  **23 aprobadas, 106 aserciones**, 14,38 s, `wings_testing_codex`.
- `VerificacionA13PermisosTest` independiente: **2 aprobadas, 9 aserciones**, 0,368 s.
- Herramienta de servicios: **11 comprobaciones** correctas; control final de concurrencia:
  **6 comprobaciones** correctas. No son pruebas nuevas de la suite permanente.
- No se corrió suite completa para esta verificación. El último corte completo propio
  corresponde a A4/A5 + A37: 439 pruebas, 438 aprobadas y 1 omitida, 2960 aserciones, 05/10.
  No se extrapola ese resultado a cambios ajenos posteriores.

El cierre documental del 06/10 comprueba `DefectosNoDivergenTest`, sintaxis de herramientas,
enlaces/evidencia y el diff. Los ensayos adjuntos están bajo `docs`, fuera de la suite
permanente de `phpunit.xml`; esta entrega no aumenta su cantidad de pruebas.
Control documental efectivamente ejecutado el 06/10: **3 pruebas, 12 aserciones**, verde;
las tres herramientas PHP sin errores de sintaxis y los enlaces nuevos resuelven.
Los cambios ajenos de vistas/capturas/pruebas quedan sin incluir. Sin despliegue.
La corrección de A13/B1/A55 corresponde a Claude; después necesita otra verificación real.
