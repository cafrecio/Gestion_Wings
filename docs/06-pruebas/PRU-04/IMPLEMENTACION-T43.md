# T43 — Decisión del dueño sobre comisiones atrasadas

20/10/2026, Codex CyE. Implementación local tras T42 (`6c8602f3`). Sin despliegue ni escritura en `wingstest`, `wings` o la base del club. Completa en verde. Carlos aprobó la corrección visual y de interacción: «Ahora esta OK». Pendientes: publicación y verificación independiente de lógica por Claude.

## Regla y cálculo

DU-36: cada parte atrasada se registra en la liquidación del mes real del cobro, inicialmente PENDIENTE. ADMIN activo valida o rechaza cada parte. Las pendientes y rechazadas no forman el importe confirmado ni el anexo del recibo. Hasta resolver todas no se permite cerrar ni pagar. Las cuotas del mes y adelantadas quedan incluidas automáticamente; las adelantadas conservan el mes efectivo definido en T42/DU-19 y su requisito de asistencia.

`total_calculado` conserva la suma original potencial; `total_atrasadas_excluidas` suma pendientes y rechazadas; `total_confirmado` es su diferencia. El monto a pagar usa el confirmado salvo que exista `monto_final`, que conserva su carácter de **total absoluto autorizado**, con auditoría A23. En ese caso el recibo separa las comisiones incluidas del ajuste necesario para llegar a ese total, sin imprimir cuotas rechazadas.

El redondeo sigue por pago y mes. Se distribuyen sus centavos entre partes por resto mayor, con orden estable por período/deuda. No se redondea cada parte independientemente creando o perdiendo centavos.

## Persistencia y límites

- Migración `2026_10_20_180000_registrar_decisiones_comision_atrasada`: origen, pago/deuda, mes de cuota, base, fecha real de cobro, estado/responsable/fecha de decisión; exclusiones en cabecera y auditoría permanente `liquidacion_decisiones_atrasadas`.
- Sin backfill: documentos previos conservan origen desconocido y exclusión cero. Pagos sin imputaciones mantienen el fallback histórico T42; no se inventa origen.
- Decisión, detalle, auditoría y total se guardan en una transacción con bloqueo de cabecera. Cierre, recálculo y pago toman ese mismo bloqueo. Formulario viejo rechazado; repetición idéntica sin duplicar auditoría.
- Recálculo solo de abiertas sin pagar. Conserva decisiones únicamente si coinciden pago, deuda, período, base y comisión; un cambio económico exige otra revisión. Anulados salen al recalcular. Cerradas/pagadas no se reescriben.
- La auditoría sobrevive al recálculo o eliminación de una abierta, como la auditoría de ajustes A23. `down()` no elimina evidencia financiera: un retroceso del código no revierte este esquema.
- Cada decisión no crea movimientos de caja. El pago usa el monto confirmado/final en Cashflow. Historial de Reportes y sueldos conserva el mismo importe efectivo y sus cortes anteriores.

## Pruebas y evidencia

15 regresiones permanentes en `DecisionComisionAtrasadaT43Test`: tres orígenes, cobro mixto, cierre pendiente, validar/rechazar/reenvío, formulario viejo, recálculo equivalente/cambio de base, anulación, pagada intacta, cambio de año, centavos, fallback, roles/inactivo/detalle ajeno, recibo y ajuste absoluto, pago sin duplicación.

Primera dirigida: 20 aprobadas/3 fallidas por preparación propia de pruebas (deuda repetida, intento de alterar monto inmutable y mock PDF sin tipo). Corregidas las pruebas; dirigidas T43/T42/A23/históricos/contador: **39 aprobadas, 225 aserciones, 12,03 s, salida 0** (antes de agregar las dos regresiones de permisos finales).

Suite rápida: **895 aprobadas, 6 omitidas, 7147 aserciones, 186,82 s, salida 0**, corte 1016. [Salida](evidencia-t43/suite-rapida.txt). Corte posterior **1018** con dos regresiones adicionales. Suite completa en `wings_testing_codex`, iniciada sin otra prueba activa: **1012 aprobadas, 6 omitidas, 8743 aserciones, 740,41 s, salida 0**. [Salida terminal](evidencia-t43/suite-completa.txt).

Después de la completa se reforzaron seis aserciones en pruebas existentes para comprobar directamente el historial tras aprobar/rechazar, conservación del corte anterior y saldo por pagar antes/después del pago; no cambió el producto. Dirigidas finales T43/T42/A23/históricos/contador: **41 aprobadas, 240 aserciones, 10,82 s, salida 0**. [Salida final](evidencia-t43/dirigidas-finales.txt). Sintaxis PHP correcta y `view:cache`/`view:clear` sin errores. El MCP de estructura informó transporte cerrado; barrido con `rg` y lectura real de servicios, modelos, observadores, controlador, rutas y vistas. [Huellas finales](evidencia-t43/fuentes.json).

Navegador: aplicación real local en `127.0.0.1:8761`, base sintética separada `wings_testing_codex_t61`. Julio rechazado y agosto validado mediante los formularios reales; responsable/fecha visibles. Cierre y pago por servicios de Wings en esa misma base ficticia: Cashflow **-60000.00**. PDF real generado por `ReciboService`, dos páginas renderizadas con Poppler y texto extraído con pypdf: agosto/septiembre incluidos, julio ausente. El visor de PDF en Chrome abortó su apertura; no se presenta una captura de ese visor ni una prueba física de teléfono.

[Galería para Carlos](APROBAR-T43.html): liquidación en 1366/375 CSS reales; ancho documental 1351/360 respectivamente, sin desborde; botones de decisión 96 × 32. PDF A5 original y sus páginas completas para ampliar. Scripts de preparación restringidos explícitamente a la base sintética de capturas. Sin HTML de aplicación fabricado.

## Entrega pendiente

Aprobación de Carlos registrada el 20/10: «Ahora esta OK». Publicar y pasar T43 a verificar por Claude. Carlos autorizó el envío: «Mandale el mensaje. No frenes la tarea». LIQ-002 actualizado en el editor de Claude: criterios e/h y fuente releídos en catálogo LIQ versión 5 (el estado ya era vigente). Al abrir el caso se detectó que a aún decía cuotas cobradas en octubre; se alineó con DU-19/T42 y la copia del repo, y se releyó la versión 6: solo cambió el texto de a. [Relectura e/h y diff](evidencia-t43/tabla-viva-relectura.txt) · [Relectura final de a](evidencia-t43/tabla-viva-punto-a-relectura.txt) · [Captura real](evidencia-t43/tabla-viva-final.png). No se modificaron otros casos, resultados ni respuestas de dudas. No tocar ni incluir la evidencia T34 regenerada por la suite ni cambios de otros agentes.

## Corrección visual posterior a la revisión de Carlos

Carlos: «El boton validar esta en cualquier lado, visualmente corregir eso». No aprobó el aspecto; quiere probar el circuito personalmente. Se corrigió únicamente la botonera de la vista: Rechazar a la izquierda y Validar a la derecha, ambas acciones siempre presentes y la decisión actual deshabilitada. Se retiraron los huecos de reserva anteriores; no cambia la lógica del servidor ni el PDF.

Capturas nuevas pendientes/resueltas a 1366/375 en `evidencia-t43/correccion-botones/`, medidas DOM guardadas en `medidas.json`: 96 × 32, posición estable y sin desbordes. Las capturas anteriores se conservan como antecedente; la galería enlaza las corregidas. Dos decisiones reales por formulario en la base sintética: julio rechazado, agosto validado, confirmado $60.000.

Se dejó otro ejemplo sin resolver para la prueba manual de Carlos: `http://127.0.0.1:8761/liquidaciones/4`, usuario ficticio ya autenticado en Chrome, base `wings_testing_codex_t61`. El script de preparación admite `--manual` y conserva el caso si vuelve a ejecutarse. No se tocó la base del club.

Suite rápida de esta corrección: **897 aprobadas, 6 omitidas, 7162 aserciones, 300,20 s, salida 0**, wings_testing_codex. [Salida](evidencia-t43/correccion-botones/suite-rapida.txt). Se conserva el corte completo verde anterior a esta presentación. En ese corte todavía se esperaba la aprobación de las vistas (otorgada después: «Ahora esta OK»). Inicialmente Carlos pidió contexto sobre la lista de pruebas; después autorizó el envío. La actualización quedó guardada y releída, como consta arriba.

Dirigidas de esta corrección: **15 aprobadas, 96 aserciones, 14,46 s, salida 0**. [Salida](evidencia-t43/correccion-botones/dirigidas.txt). Sintaxis del preparador local y compilación/limpieza Blade correctas. En ese corte quedaba la prueba manual y aprobación de Carlos (después: «Ahora esta OK»); la actualización documental en Claude ya está enviada, guardada y releída.

## Revisión de Carlos: cabecera, guardado parcial y total confirmado

Carlos rechazó el aviso mezclado con las acciones y la recarga completa al validar/rechazar. La cabecera separa datos, aviso y botonera al pie, alineada a la derecha. Se mantienen tamaños y reglas de cierre. No se modificó el CSS compartido.

Las decisiones con JavaScript usan POST JSON, sin redirección ni GET de `show`: devuelven estado, responsable, fecha, importes y dos fragmentos acotados (acciones y revisión). Conservan la tarjeta y el formulario de ajuste; los datos escritos no se pisan. El contador del menú no se consulta al renderizar estos fragmentos. El POST normal sigue disponible sin JavaScript. Auditoría, bloqueo, permisos y protección contra formularios viejos siguen en el servidor.

El pie de COMISIÓN muestra **Total confirmado**, excluyendo pendientes/rechazadas. El cálculo original permanece separado; un ajuste final absoluto conserva su regla A23. En el ejemplo, julio rechazado y agosto validado dejan $60.000 confirmados frente a $72.000 originales.

Pruebas permanentes T43: **17 aprobadas, 127 aserciones, 11,19 s, salida 0**. Incluyen respuesta JSON, totales, cierre habilitado, idempotencia, conflicto de otra pestaña y ausencia de consultas de alumnos, grupos, cobros, asistencia, deuda y cuentas al decidir. [Salida](evidencia-t43/cabecera-separada/pruebas-json.txt). La rápida previa dio cinco fallos por entrada Vite todavía sin compilar; después se compiló correctamente. No se presenta esa corrida como verde. Suite completa posterior: **1017 aprobadas, 6 omitidas, 8791 aserciones, 844,65 s, salida 0**, base `wings_testing_codex`, corte 1023. Dirigidas finales + contador: **18 aprobadas, 134 aserciones, 8,93 s, salida 0**; vistas compiladas y caché retirada sin errores. [Dirigidas finales](evidencia-t43/cabecera-separada/dirigidas-finales-json.txt). Completa: [salida](evidencia-t43/cabecera-separada/suite-completa-json.txt).

Navegador real, caso propio #5 (no se alteró el caso #4 de Carlos): rechazo dejó la tarjeta en y=380,59375 y total $40.000; validación mantuvo exactamente esa y y pasó a $60.000. Un texto de ajuste sin guardar sobrevivió ambas acciones: prueba directa de que no hubo navegación completa. [Mediciones](evidencia-t43/cabecera-separada/control-navegador.json). [Captura escritorio](evidencia-t43/cabecera-separada/confirmado-1366.png) · [Cabecera 375](evidencia-t43/cabecera-separada/cabecera-375.png). En 375: ancho documental 360, sin desborde y cuatro acciones de 96 × 32. El acceso al navegador se deshabilitó desde Work durante la última interacción móvil; no se declara validado ese clic ni las capturas restantes de estados de cabecera.

Carlos aprobó la corrección visual y la interacción después de probar: «Ahora esta OK» (20/10). Se habilita publicar las vistas de T43; la lógica sigue pendiente de verificación independiente por Claude. La última interacción móvil del agente continúa sin comprobarse; no se convierte en prueba realizada por esta aprobación. Sin despliegue.
