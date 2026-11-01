# Verificación T100 — Codex CAB, 01/11/2026

**Resultado vigente, segunda vuelta: DEVUELTA a Claude solo por H1 residual en dos campos de la ficha de fuentes. H2 resuelto.** Véase la segunda vuelta al final.

**Primera vuelta (histórica): DEVUELTA a Claude por dos hallazgos medios de instrucciones y mensajes.** Los once casos de negocio se reprodujeron con los importes indicados. No se corrigió producto, vistas, CSS ni tabla de pruebas.

## Entorno y evidencia

Inicio con `git pull --ff-only` (actualizado) y T100 en curso, tiene Codex. Fuentes de T100 tomadas de `ad965a33`, en copia aislada `storage/app/control-gw-261101/app`, con vendor físico. Los tres archivos principales conservan sus hashes contra el checkout actual: [iniciales](evidencia-control-t100-codex/fuentes-iniciales.json), [finales](evidencia-control-t100-codex/fuentes-finales.json). Única base: `wings_testing_codex`. Navegador Chrome sobre `http://127.0.0.1:8111`; reloj e identidades ficticias solo en esa copia. Se ajustó allí la duración de sesión para permitir saltos de fecha. Sin despliegue ni cambios en la base local del club.

La copia consistente del sitio ficticio `wingstest`, autorizada expresamente por Carlos, quedó en storage ignorado, excluyendo usuarios, sesiones y tokens. No se publica SQL. Commit del sitio leído: `6214fd4a8e2e3bfe493171a2edbb27abc2d8dbfb`. La migración se ejecutó exclusivamente sobre la copia importada en `wings_testing_codex`.

## Casos ejecutados

En los casos de borde, la preparación de alumnos, cobros y vínculos fue por fixture usando modelos y servicios reales; la cancelación/anulación y consulta del resultado se hicieron en pantalla. Los pedidos directos independientes están en [ControlT100CodexTest](evidencia-control-t100-codex/ControlT100CodexTest.php). No se presenta la preparación por consola como carga humana.

| Caso | Pedido directo y resultado al peso | Pantalla comprobada |
|---|---|---|
| 1. Operativa, $300.000 → cancelar → $30.000 | Diciembre desaparece al cancelar y se crea pagado por $30.000, saldo $0. Caja vuelve a su efectivo previo y luego suma solo $30.000. No pide motivo de parcial. | Cobro y confirmación de adelanto, cancelación con motivo, recobro y ficha. [Ficha final](evidencia-control-t100-codex/gw-control-t100-caso1-ficha.jpg), [Caja cancelada](evidencia-control-t100-codex/caso-1-cancelado.jpg). |
| 2. Dueño, anulación en ficha | Mismo resultado; asientos +$300.000 y −$300.000, neto $0; recobro deja $30.000. | Anular desde ficha, Cashflow antes/después, Inicio, Cobranza y Reportes. [Cashflow en cero](evidencia-control-t100-codex/gw-control-t100-caso2-cashflow-despues.jpg), [recobro](evidencia-control-t100-codex/gw-control-t100-caso2-recobrado.jpg). |
| 3. Cancelar; llega el mes con asistencia anterior | Generación de noviembre: cuota pendiente $30.000, no $300.000. | Anulación del adelanto y [ficha con noviembre $30.000](evidencia-control-t100-codex/gw-control-t100-caso3-noviembre-generado.jpg). |
| 4. Sin asistencia | No crea cuota de noviembre; Revisión pendiente; cuota adeudada $0. | Anulación, [ficha sin deuda](evidencia-control-t100-codex/gw-control-t100-caso4-sin-deuda.jpg) y [Revisión](evidencia-control-t100-codex/gw-control-t100-caso4-revision.txt). |
| 5. Mes iniciado y caja rechazada | Cuota de diciembre conservada, original $30.000, pagado $0, pendiente; origen limpiado. Efectivo esperado $330.000 → $30.000 en el fixture compartido con el caso 1. | La titular cancela en caja RECHAZADA el 3/12; [ficha $30.000](evidencia-control-t100-codex/gw-control-t100-caso5-diciembre-pendiente-30000.jpg). Ir a Cobrar sin un nuevo turno remite a Apertura; no se abrió otro turno para esta consulta. |
| 6. Mes debido y adelantado en un cobro | Octubre vuelve a pendiente $30.000 y diciembre se borra; pago anulado $60.000 conserva ambos períodos. | [Ficha de varios meses](evidencia-control-t100-codex/gw-control-t100-caso6-multimes-despues.jpg). |
| 7. Cuota futura preexistente | Conserva su original $25.000, pendiente. No se le inventa origen. | Anulación y ficha; la conservación exacta se comprueba en las filas del [estado posterior](evidencia-control-t100-codex/casos6-11-tras-pantalla.json). |
| 8. Dos cobros | Original $50.000, cobros $30.000 y $20.000: anular el primero conserva $20.000 pagados y $30.000 por completar; anular el último borra la cuota. Directos también en orden inverso. | [Otro cobro vigente](evidencia-control-t100-codex/gw-control-t100-caso8-queda-otro-cobro.jpg), [último anulado](evidencia-control-t100-codex/gw-control-t100-caso8-cuota-deshacida.jpg). |
| 9. Recargo, liquidación o pérdida | Con recargo conserva cuota $30.000 y cargo $100. Con liquidación conserva cuota $30.000 y cierre $120.000 intacto. Con pérdida de $100 conserva original $300.000, saldo $299.900; sin error. | Tres anulaciones, Cobrar y liquidación cerrada. [Recargo](evidencia-control-t100-codex/gw-control-t100-caso9-recargo-pendiente.jpg), [cierre](evidencia-control-t100-codex/gw-control-t100-caso9-liquidacion-cierre-intacto.jpg), [pérdida](evidencia-control-t100-codex/gw-control-t100-caso9-perdida-pendiente.jpg). |
| 10. Primera cuota con descuento | Flujo normal: octubre al 70% de $30.000 = $21.000, más noviembre adelantado $30.000. Anular los $51.000 restituye octubre por $21.000 y deshace noviembre. Correcto: el cobro anulado no consume el descuento. | [Octubre conserva $21.000](evidencia-control-t100-codex/gw-control-t100-caso10-octubre-21000.jpg). Además se probó por pedido directo una fecha de alta futura preparada por modelo: su cuota de alta vale $21.000 al volver a cobrar. Esa prueba comprueba el cálculo, no que la pantalla admita un alta futura. |
| 11. Noviembre y diciembre, cancelar noviembre | Deshace noviembre; diciembre mantiene su ID, original/pagado $30.000 y estado PAGADA. Directamente se puede volver a cobrar noviembre. | [Diciembre intacto y noviembre anulado](evidencia-control-t100-codex/gw-control-t100-caso11-cancelar-noviembre-despues.jpg); [selector ofrece noviembre](evidencia-control-t100-codex/gw-control-t100-caso11-noviembre-disponible.txt). No se infiere que exista una deuda de noviembre antes de generarla. |

## Lecturas cruzadas y cierres

- Caja: $300.000 → $0 → $30.000 en el caso operativo; el caso 5 conserva los otros $30.000 del turno. Se miraron filas concretas además de las sumas.
- Cashflow del cobro del dueño: $300.000 → $0. Disponible total $330.000 → $30.000 porque permanecían $30.000 operativos sin validar. No se confunden tarjeta de Cashflow y dinero pendiente de validar.
- Inicio y Reportes de octubre: ingresos/egresos $0, deuda $0 antes y después del caso 2; el adelanto pertenece a diciembre para T42. Cobranza: $0. Los DOM completos antes/después están en `gw-control-t100-caso2-{inicio,reportes,cobranza}-{antes,despues}.txt`.
- Cascada: se comprobó el retiro de `reporte_eventos` de la cuota futura borrada. El corte de octubre conservó ingresos, egresos, resultado, disponible, deuda y por pagar después de avanzar y generar noviembre: [comparación](evidencia-control-t100-codex/casos3-4-generacion.json). El período futuro no se cuenta antes de su mes (`ReporteMensualService.php:203-210`). No se extrapola a todos los cierres históricos existentes.
- Comisión T43: control positivo $300.000 × 40% = $120.000. Anulación por pantalla; POST de Recalcular deja abierta en $0 y sin detalles, y conserva la cerrada en $120.000: [7 aserciones](evidencia-control-t100-codex/comision-post-prueba.txt), [filas](evidencia-control-t100-codex/comision-post-resultado.json), [pantalla final](evidencia-control-t100-codex/gw-control-t100-comision-cero-despues.jpg). Una abierta conserva el cálculo guardado hasta Recalcular; no se presentó ese importe almacenado como comisión vigente recalculada. **Límite:** el diálogo nativo de Recalcular bloqueó la automatización; el recálculo efectivo se verificó por POST y su resultado se abrió en otra pestaña. No se acredita ese diálogo como ejecutado de punta a punta.
- Recibo anulado: GET normal y regenerado devuelven PDF tras borrar la cuota; ambos conservan diciembre, $300.000, motivo y marca ANULADO. [PDF](evidencia-control-t100-codex/recibo-anulado-1.pdf), [render inspeccionado](evidencia-control-t100-codex/recibo-anulado-render.png), [texto](evidencia-control-t100-codex/recibo-anulado-texto.txt). Ficha muestra anulado más recobro, sin depender del pivot borrado.
- Migración sobre copia de sitio en uso: 55 tablas conservan exactamente sus filas y hashes al agregar la columna; todos los orígenes antiguos quedan NULL. Se miraron cinco adelantos concretos y se anuló en la copia el pago #399, diciembre $44.000: cuota conservada por $44.000, pagado $0, pendiente. [Evidencia](evidencia-control-t100-codex/migracion-copia-sitio.json). Esto **no** corrige retroactivamente el saldo de Clara del sitio.

## Hallazgos y propuesta, sin corregir producto

| ID | Gravedad | Archivo:línea y hecho reproducido | Propuesta |
|---|---|---|---|
| H1 | Media | `resources/manual/operativo/cancelar-movimiento-caja.md:42,51` promete restituir siempre a pendiente y que todo adelanto cancelado deja de tener cuota. Omite cuota preexistente, otros cobros vigentes, recargo/liquidación, pérdida y registros anteriores sin origen. Los casos 7, 8 y 9 contradicen esa generalización; la página fue cambiada por T100. La ficha de fuentes conserva la generalización en su Paso 3. | Paso 3: «El cobro queda anulado y deja de sumar a la caja. Se revierten sus importes; si la cuota nació con ese adelanto y todavía corresponde deshacerla, desaparece.» En Qué hago si: explicar todas las condiciones de `PagoCuotaService.php:1258-1274`, incluida la compatibilidad de cuotas anteriores. |
| H2 | Media | `resources/views/caja/detalle.blade.php:306` y `resources/views/alumnos/show.blade.php:442` anuncian deuda pendiente incluso cuando desaparece la cuota. Ambos paneles se abrieron sobre adelantos reales del fixture. Los avisos de éxito de `CajaWebController.php:648` y `AlumnoWebController.php:317` también generalizan «deuda revertida/restituida». No es un fallo monetario comprobado. | Caja: «El cobro dejará de sumar a la caja. Se revertirán sus importes y el adelanto se deshará cuando corresponda.» Ficha: «El cobro se anulará y su importe saldrá del cashflow. Se revertirán sus importes y el recibo quedará anulado.» Éxitos: «Cobro cancelado.» / «Cobro anulado.». Carlos decide el texto visual; no se pide autorización sin capturas. |

Propuesta de catálogo, **sin editarlo**: COB-019 debe distinguir cuota nacida del adelanto, último cobro vigente, mes empezado, cuota preexistente y protecciones por vínculos/pérdida; conservar una comprobación separada para cuotas anteriores a T100. No se modificaron casos ni respuestas DU.

## Pruebas, mutaciones y alcance del barrido

- Claude: [12 aprobadas / 56 aserciones](evidencia-control-t100-codex/suite-claude.txt).
- Independientes: 11 pasaron en la primera corrida; tres fallaron por errores de mi fixture/comparación (deudas previas que exigían motivo; comparación de fecha de corte). Tras corregir solo el control, [las tres pasaron / 34 aserciones](evidencia-control-t100-codex/independientes-casos-corregidos.txt). Son 14 métodos comprobados, no una corrida única verde de 14. Una repetición se lanzó antes de corregir porque usé una ruta relativa equivocada; se conserva su salida, sin atribuirla al producto.
- [Cuatro cruces concurrentes / 44 aserciones](evidencia-control-t100-codex/concurrencia-pruebas.txt): dos conexiones MariaDB distintas en ambos órdenes y ambos roles. El segundo intento agota la espera en SELECT FOR UPDATE y registra **cero escrituras**, con foto de tablas intacta. Después del commit se reintenta y concilia. [IDs y resultados](evidencia-control-t100-codex/concurrencia.jsonl). Esto demuestra bloqueo y reintento; no una reanudación automática del pedido que agotó la espera.
- Recálculo: 1 / 7. [Rechazos del manual](evidencia-control-t100-codex/manual-rechazos-prueba.txt): 1 / 14; motivo vacío, CERRADA, VALIDADA y segundo intento rechazados sin cambiar dinero. Total independiente: 20 métodos en ejecuciones separadas.
- [Trece mutaciones detectadas](evidencia-control-t100-codex/mutaciones-revisadas.json): origen, otro pago, pérdida, recargo, liquidación, mes empezado, borrado, precio, limpiar origen, guardar origen, cuota preexistente y ambos caminos de anulación. Algunas fallan por acceso a la cuota indebidamente borrada; no son errores de sintaxis. El primer clasificador no reconocía esa causa: se conserva el original y la revisión separada. [Hash restaurado](evidencia-control-t100-codex/mutaciones-restauracion.json).
- `php scripts/manual/verificar-manual.php`: [59 páginas, 0 problemas mecánicos](evidencia-control-t100-codex/manual-verificador.txt). Eso no detecta H1/H2. Se compararon condiciones y estados del manual contra controlador, servicio y vistas reales.
- Barrido con grafo y fuente: entradas web `CajaWebController::cancelarMovimiento` y `AlumnoWebController::anularPago` llegan a ambas funciones de reversión. `CajaCorreccionService` impide editar/eliminar cobros; `CashflowCorreccionService` exige origen directo. No apareció en el barrido acotado de servicios/controladores otra asignación de ANULADO a pagos. La API continúa deshabilitada en `bootstrap/app.php:17`. El índice informó cobertura no disponible/metadatos cambiados; se leyeron directamente las funciones y consumidores citados. No se declara una prueba exhaustiva de todo el repositorio.
- No se corrió suite completa en esta etapa: Carlos ordenó una sola al final de T102, reservando la máquina. No se hereda la completa T97 como si fuera un control actual de estas etapas.

**Entrega:** T100 devuelta a Claude por H1/H2; continúa T106 según el orden de Carlos. Sin reparar producto ni desplegar.


## Control de la corrección posterior de Claude — 01/11/2026

Claude publicó c91b4555 y volvió a dejar T100 a verificar. Se releyeron el manual actualizado, la ficha de fuentes, las condiciones de la función sin cambios monetarios y los cuerpos de ambos controladores: el manual distingue ahora cuota creada por el adelanto, preexistente, otro pago, pérdida, mes iniciado y vínculos; los éxitos dicen «Cobro cancelado.» / «Cobro anulado.». La completa final del corte4d87e925 incluye esos controladores y las 12 pruebas T100 pasan. Esto no se presenta como un nuevo recorrido completo de ambos mensajes en Chrome.

**H1 no queda resuelto por completo:** en la ficha `evidencia-t12-t13/fuentes/operativo-cancelar-movimiento-caja.md:18,31`, el último campo de Paso 3 todavía afirma que la cuota vuelve a PENDIENTE y la explicación del flash todavía dice que restituye la deuda a pendiente. La primera columna fue aclarada, las otras dos menciones no. Corregir esas generalizaciones sigue dentro de H1.

H2 permanece: los paneles Blade siguen diciendo deuda pendiente y esperan la aprobación visual de Carlos; no se tocaron. Por H1 residual y H2 **T100 vuelve a devuelto, tiene Claude**. El verificador final además señala cinco problemas mecánicos de formato/literales en el nuevo texto del manual (baja, líneas42 y55–58): título de la pregunta entre comillas y condiciones de negocio en negrita se tratan como literales de pantalla. Propuesta: ajustar ese formato sin cambiar las condiciones; el sexto problema del verificador es ajeno, recibo impago de T109. [Salida y cierre de las tres etapas](VERIFICACION-T102-CODEX.md). Sin cambios de producto ni catálogo por Codex.


## Segunda vuelta — 01/11/2026, Codex CAB

Control limitado a H1/H2 devueltos, sobre la corrección `103360f7`. No se repitieron los once casos de negocio, la migración del sitio ni las mutaciones del primer control. Pantallas servidas por Wings real en la copia aislada, únicamente `wings_testing_codex`; identidades ficticias preparadas por [fixture](evidencia-segunda-t100-t102-codex/preparar-pantallas.php). Sin cambios propios de producto ni despliegue.

### Comprobado

- Los tres paneles muestran el texto aprobado: [Detalle de caja](evidencia-segunda-t100-t102-codex/gw-segunda-panel-detalle.jpg), [formulario de cancelación](evidencia-segunda-t100-t102-codex/gw-segunda-panel-formulario.jpg) y [anulación desde ficha](evidencia-segunda-t100-t102-codex/gw-segunda-panel-anular.jpg). Sus DOM están junto a cada captura, con igual nombre y extensión `.txt`.
- Se ejecutaron la cancelación operativa y la anulación del dueño sobre cobros ficticios para comprobar los avisos. Se vieron **«Cobro cancelado.»** ([captura](evidencia-segunda-t100-t102-codex/gw-segunda-flash-cancelado-final.jpg)) y **«Cobro anulado.»** (leído en el snapshot visible de Chrome; [registro de observación](evidencia-segunda-t100-t102-codex/observacion-aviso-anulado.md)). La captura y DOM guardados después de la anulación muestran el resultado final, pero ya no contienen ese aviso transitorio: no se usan como prueba del literal. La primera captura de la cancelación llegó después de que el aviso se ocultara; se preparó otra operación ficticia para capturarlo. Eso no se presenta como repetición de los casos monetarios anteriores.
- `php scripts/manual/verificar-manual.php`: **59 páginas, 0 problemas, código 0** ([salida](evidencia-segunda-t100-t102-codex/manual-verificador.txt)). Los cinco avisos mecánicos de la entrega anterior ya no aparecen.
- Única completa final: **1.479 aprobadas, 0 fallidas, 6 omitidas, 11.614 aserciones, 1.311,52 s; código 0**. La ejecución y su alcance se registran al final de la segunda vuelta de T102; no se hereda la completa de Claude.

### Inferido — contraste por lectura de fuentes actuales

El Paso 3 del manual remite ahora a la excepción del adelanto. Su pregunta «¿Qué pasa si cancelo un cobro adelantado?» se contrastó con el cuerpo de `PagoCuotaService::deshacerCuotaNacidaDeCobro`, líneas 1256–1275:

| Condición de la función | Texto actual del manual | Resultado del contraste |
|---|---|---|
| `pago_origen_id === null` | Cuota previa o adelanto anterior a esta regla conserva el importe | Incluida |
| `monto_pagado > 0` | Otro cobro sigue vigente y solo queda pendiente lo que falta | Incluida |
| `monto_perdido > 0` | Parte dada por perdida conserva el importe | Incluida |
| `CargoAlumno` o `LiquidacionDetalle` vinculado | Recargo o liquidación impiden borrar y queda al precio del plan | Incluida |
| Período futuro y sin vínculos, después de superar las tres salidas anteriores | Cuota nacida de ese adelanto, sin otro cobro, se deshace; abajo explicita las excepciones | Incluida |
| Mes iniciado; existe el plan para el período | Pendiente al precio del plan | Incluida; la asignación del precio en código requiere encontrar ese plan |

Las cuatro protecciones omitidas en la primera redacción están explicadas: origen previo/legado, otro cobro, pérdida y recargo/liquidación. Esta tabla acredita lectura; los efectos monetarios corresponden a los casos ya ejecutados en la primera vuelta.

**H1 residual, media: sigue sin resolverse la ficha de fuentes.** En `evidencia-t12-t13/fuentes/operativo-cancelar-movimiento-caja.md:18`, la última columna de Paso 3 conserva «la cuota vuelve a PENDIENTE»; en la línea 31, el aviso dice que «restituye la deuda a pendiente». El [barrido literal](evidencia-segunda-t100-t102-codex/barrido-frases.txt) devuelve esas dos menciones dentro del alcance manual/paneles/ficha. No se atribuye otro defecto monetario. Propuesta: aplicar también en esos dos campos la distinción de la primera columna; no se editó la ficha.

**H2 resuelto en los tres paneles y los dos avisos. T100 se devuelve a Claude únicamente por H1 residual de la ficha.** No requiere una nueva decisión de diseño ni repetir el negocio ya controlado. La tabla de pruebas permanece intacta; sigue vigente la propuesta de catálogo de la primera vuelta.
