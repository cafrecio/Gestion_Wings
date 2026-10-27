# T88 y B3 — Dinero: gasto disponible y cobros justificados

Codex CAB, 27/10/2026. Implementación local en validación. Objetivo escrito en el adjunto T88 y B3; no se despliega ni se usa el servidor, sitio de prueba o base del club.

## Comportamiento

- **Gasto en efectivo:** el servidor proyecta el efectivo resultante bajo el bloqueo de la caja. Rechaza si el alta o una edición que reduce el efectivo deja un saldo negativo. Indica que primero se registre un aporte. Reemplaza el efecto anterior al editar, contempla cambios de medio y no confunde transferencias con el cajón. No implementa MF2.
- **Cobro parcial:** calcula primero descuento, inscripción, cuotas y recargos. Si una cuota queda parcial, exige motivo; con motivo se cobra. No transforma el parcial en prohibición.
- **Meses anteriores:** compara las deudas residuales contra el último mes realmente cobrado, descontando lo incluido en el pago. Detecta también el mes omitido entre dos elegidos, no solo el anterior al primero. Informa cantidad, meses e importe. Si todos los anteriores quedan cubiertos, no pide excepción.
- **Mecanismo común:** `excepciones_cobranza` conserva tipo, motivo, autor, fecha, alumno y vínculo al pago, con contexto de la decisión e índice único por registro/tipo. Si el movimiento falla, pago, imputaciones, deuda y excepción se revierten juntos. Un pago puede registrar ambos tipos sin duplicarlos.
- **Roles:** se aplica a OPERATIVO en su caja y a ADMIN desde Cobrar/ficha, cuyo pago va a Cashflow. No se amplían rutas ni permisos.
- **Dueño:** Inicio muestra historial paginado, tipo, alumno, motivo, autor, fecha y vínculo para consultar. `AvisoAdminService::resumenDiario` agrega las excepciones de las últimas24h por tipo al mismo aviso existente, una vez por canal; no manda un mensaje al guardar cada excepción. Una corrida repetida del resumen conserva la ventana móvil de24h; no incorpora acuse de lectura.

Los dos tipos de asistencia usarán este mismo registro en la entrega2. El exceso del plan queda fuera de B3 por DU-34 y conserva su anotación previa.

## Interfaz y capturas

[Galería real1366/375](APROBAR-T88-B3-DINERO.html). Se recorrió Wings funcionando en localhost como operativa y como ADMIN. Cobrar tiene un único panel dentro de la página: confirma excepción con motivo, adelanto o fecha anterior; se retiraron `window.confirm` y `window.alert` de `cobrar.js`. Los errores del pedido se muestran dentro de la página. No se agregaron scripts/eventos en Blade ni CSS compartido.

El registro de gasto conserva el aviso global existente, que se cierra a los3s; el control no depende de que el aviso siga visible. El diagnóstico temporal que comprobó su paso por la sesión fue retirado. Una captura móvil demasiado temprana y las capturas completas con cabecera fija desplazada se descartaron como fallos de medición, no del producto.

Los PNG definitivos se renderizaron en Chrome desde los DOM reales observados, con los valores de campos que `outerHTML` no serializa por sí solo. Solo se adaptaron URLs de assets y se neutralizaron tokens; no se inventó HTML de Wings ni se editaron las imágenes. Marco375 real y login de calibración incluidos. Aprobación visual de Carlos pendiente.

## Evidencia de servidor y navegador

| Comprobación | Resultado |
|---|---|
| Primera corrida de12 casos antes del arreglo | 10 fallidas y2 aprobadas,15 aserciones; fallan gasto, edición, parcial y registro ausente. |
| Avisos antes de implementarlos | 2 fallidas,6 aserciones. |
| Primera propia verde | 16 aprobadas,54 aserciones. Después se agregaron cambio de medio y rollback tardío. |
| Regresiones de pago/descuento/inscripción/recibo/condonación/reingreso | 141 aprobadas,1652 aserciones,107,47s. Solo se agregaron motivos explícitos y efectivo inicial en fixtures válidos; saldos/importes esperados conservados. |
| Concurrencia real | 7 aprobadas,115 aserciones,26,83s. Incluye dos conexiones MariaDB distintas, foto vieja de movimientos, rechazo del segundo gasto y timeout sin escrituras; más los seis cruces FIN-11. |
| Rápida final del primer corte | 972 aprobadas,6 omitidas y1 fallo de contador,7652 aserciones,245,79s. El método de documentación encontró18 pruebas T65 nuevas durante la corrida (1098→1116); no falló código de dinero en ese corte. |
| Rápida de cierre | 991 aprobadas,6 omitidas,7761 aserciones,184,37s, salida0. Contador actual1116 métodos; incluye18 T65 concurrentes que no entran al commit propio. |
| Completa del checkout compartido | 1070 aprobadas,6 omitidas y40 fallidas,9148 aserciones,1191,77s, salida2. Un fixture propio de parcial sin motivo fue corregido; los otros39 casos se repitieron sin tocar el trabajo concurrente. No se declara suite completa verde. |
| Comparación final contra clases originales | 18 casos permanentes:14 fallidos y4 aprobados,31 aserciones,16,78s. Autocarga de las tres clases originales de7fde50fb, sin reemplazar archivos del checkout. |
| Propias finales y regresiones de confirmación | 32 aprobadas,143 aserciones,20,18s: las18 propias, T77, VerificacionEntrega2 y guardia CSP. |
| Prueba técnica invertida | Los dos casos solicitados:2 aprobadas,11 aserciones,12,52s. |
| Repetición aislada de los fallos | Los39 ajenos pasaron,375 aserciones,55,54s. El parcial de Punitorios pasó por separado:1/3,14,38s. |

Los fallos de la completa se conservan en `fallos-completa.json`:18 corresponden
al fixture T65 sin `precio_mensual`; varios GET de ficha/clase devolvieron500 por
`$puedeEliminar` no definido durante la corrida compartida; otros dos son aserciones
de esas pantallas. Los39 pasaron en la repetición aislada actual. No se
atribuyen esos fallos al dinero ni se corrigen archivos ajenos. El caso propio de
Punitorios que usa un parcial válido recibió un motivo explícito, sin cambiar su
importe ni el saldo esperado del recargo.

[Resultados](evidencia-t88-b3-dinero/) · [Filas verificadas del navegador](evidencia-t88-b3-dinero/resultado-navegador.json).

El recorrido local guardó exactamente dos pagos por$38.000: parcial10000 y octubre28000. Creó dos excepciones. Volver sobre la segunda seña5000 no guardó. Marzo y mayo quedaron intactos por56000. El gasto40000 se rechazó con39000 en el cajón; cero egresos. Se comprobó con filas concretas y arqueo, no solo contadores. El primer tipeo incorrecto de la herramienta se descartó reiniciando únicamente el fixture local; no se acredita como prueba del parcial. El servidor localhost se detuvo antes de volver a correr suites.

Todas las ejecuciones propias usan `APP_ENV=testing`, `DB_DATABASE=wings_testing_codex`, host127.0.0.1, `DB_URL` vacío y bootstrap estricto. Notificaciones simuladas en pruebas; sin envío de excepciones individual desde el recorrido. Sin procesos de prueba simultáneos propios. La suite completa observa el checkout compartido con T87/T65 ajenas; se conservan sus resultados sin atribuir causas no probadas.

## Pregunta registrada para Carlos, sin detener el trabajo

**Si se anula o corrige un cobro con excepción, ¿el resumen debe mantener la decisión histórica, marcarla como anulada o excluirla del aviso?** El registro de esta entrega describe lo que se decidió al guardar; no se borra ni se reescribe la historia ni se implementa una regla nueva para la anulación. La consulta del pago original sigue en su ficha.

## Entrega

T88 se entrega `a_verificar`, hizoCodex, verificaClaude, tieneClaude. B3 sigue `en_curso` hasta terminar asistencia. La tabla de pruebas externa y su copia no se modifican. Se invierten solo los dos casos técnicos solicitados; el producto y las pruebas T65 se preservan fuera del commit propio. El tablero compartido conserva también el estado T65 que Gemini ya movió a revisión; no se le atribuye esa implementación a Codex. T87 ya estaba commiteada. Sin push manual ni despliegue. Sintaxis PHP/JS y compilación Blade comprobadas; build Vite correcto. Ver `auditoria.json` para fuentes, resultados e imágenes definitivas.
