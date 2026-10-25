# Día 6 — jueves 15/10/2026

Sitio: https://test.gestionar-te.com.ar (wingstest). Ejecución autorizada: Carlos, chat Prueba humana III. Ejecutor: Codex CAB, 24/10/2026.

Navegación humana en Chrome por menús, botones y formularios; sin consultas de datos de negocio. Esperados copiados literalmente del pedido antes de operar. Diferencias se registran sin corregir. T14 sigue en curso; revisión independiente pendiente.

| # | Situación | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|---|
| 6.1 | Sandra abre caja | Propone $10.000 (lo que retuvo el miércoles). | Propuso $10.000 del último cierre #3. Confirmó recibido y abrió caja #4 de Sandra el 15/10 15:02, inicial/esperado $10.000 y estado ABIERTA. Capturas 6.1-*.jpg. | Sí |
| 6.2 | **Emma Fernández** paga octubre, $45.000 en efectivo | Pasa de Moroso a Al día. | Emma debía octubre $45.000, Moroso. Sandra cobró $45.000 EFE, CUOTA-14, confirmado en Caja. Ficha final Al día con pago de octubre 15/10 $45.000. Sin reemplazo. Capturas 6.2-*.jpg. | Sí |
| 6.3 | La mamá de **Zoe Flores** paga **solo octubre**, $30.000 en efectivo; agosto lo paga a fin de mes | Avisa que queda agosto impago y pide motivo; sin motivo no cobra. Con el motivo «La familia paga agosto a fin de mes», cobra. Zoe sigue **Deudor** con agosto $30.000. Los dueños reciben el aviso: si llega a Telegram o mail, anotalo. | Avisó 1 mes anterior impago por $30.000 (2026-08). Intento sin motivo: Completa este campo, no guardó. Con La familia paga agosto a fin de mes guardó CUOTA-15 de $30.000 EFE solo octubre. Ficha Deudor, agosto pendiente $30.000 y octubre en historial. Recibo descargado. Recepción real de Telegram/mail no comprobada desde este recorrido. Capturas 6.3-*.jpg. | Parcial |
| 6.4 | Sandra cierra y cuenta $85.000 | Esperado $85.000, diferencia $0. Retiene $10.000 y entrega $75.000. | Caja #4: esperado y contado $85.000; diferencia $0, cambio $10.000, retiro/entrega $75.000. Cerró y Resumen confirma CERRADA con esos valores. Dos cobros EFE, neto $75.000. Capturas 6.4-*.jpg. | Sí |
| 6.5 | **Pablo Ledesma** abre el turno de la tarde | Propone $10.000. Hay dos cajas con fecha de hoy. | Pablo ingresó y abrió #5 el 15/10 15:05, con $10.000 propuestos del cierre #4. Su listado muestra solo su propia caja; ADMIN comprobó ambos turnos del 15/10, #5 Pablo y #4 Sandra, antes de validar. Sin sustitución. Capturas 6.5-pablo-*.jpg, 6.5-listado-pablo.jpg y 6.5-dos-cajas-hoy-admin.jpg. | Sí |
| 6.6 | **Felipe Sosa** (Deudor: julio, agosto y octubre, $38.000 cada uno) paga julio, $38.000 por Banco Galicia | Se aplica a julio. Sigue Deudor con $76.000. | Felipe debía julio/agosto/octubre, $38.000 cada uno. Pablo marcó solo julio y cobró $38.000 BGA, CUOTA-16. Recibo descargado dice Julio 2026/Banco Galicia. Ficha final Deudor, agosto $38.000 y octubre $38.000, total restante $76.000. Capturas 6.6-*.jpg y 6.6-recibo-felipe.pdf/png. | Sí |
| 6.7 | **Paula Ledesma** paga octubre, $43.000 en efectivo | Pasa de Moroso a Al día. | Pablo cobró octubre $43.000 EFE, CUOTA-17. Ficha final Paula Ledesma Al día y pago de octubre 15/10 $43.000, sin cuotas pendientes. Sin reemplazo. Capturas 6.7-*.jpg. | Sí |
| 6.8 | Pablo registra un gasto de **Librería**, $3.200 en efectivo, «Fotocopias de planillas» | Movimiento en su caja; el efectivo esperado baja $3.200. | Pablo guardó EGRESO, EFE, Gastos Operativos/Librería, $3.200, Fotocopias de planillas. Detalle 15/10 y Resumen caja #5: egresos $3.200, EFE neto $39.800, esperado con cambio $49.800 (bajó desde $53.000). Capturas 6.8-*.jpg. | Sí |
| 6.9 | **Lucía** toma lista en la #29, Patín Avanzadas 17–18. **Verónica Salinas** cubre a Mariela en la #40, Patín Federadas 18–19. **Hernán** toma la #67, Fútbol Avanzadas 19–20 | #29: 12 alumnas; ausente Martina Herrera; 11 presentes. #40: Verónica abre la clase de su compañera y guarda; 8 alumnas; ausente Helena Iglesias; 7 presentes; la clase sigue asignada a Mariela. #67: **15 alumnos, Lucas Gómez incluido**; ausente Felipe Sosa; 14 presentes. | Lucía #29: 12/11, Martina Herrera ausente. Verónica ingresó y abrió #40 de Mariela: 8/7, Helena Iglesias ausente; sigue asignada a Mariela. Hernán #67: 15/14, Lucas incluido y presente, Felipe Sosa ausente. Todas las listas comprobadas tras recarga. La primera recarga de #67 fue antes de que terminara Guardar y mostró 0; una segunda recarga, sin reenviar, mostró los 14 guardados. Detalle debajo. Capturas 6.9-*.jpg. | Sí |
| 6.10 | Pablo cierra y cuenta $49.800 | Esperado $49.800, diferencia $0. Retiene $10.000 y entrega $39.800. | Caja #5 de Pablo: esperado/contado $49.800, diferencia $0, cambio $10.000 y entrega $39.800. Guardó CERRADA, Resumen lo confirma. Ingresos $81.000 (BGA $38.000 + EFE $43.000), egresos EFE $3.200. Se hizo antes de 6.9 para agrupar las operaciones de Pablo. Capturas 6.10-*.jpg. | Sí |
| 6.11 | El dueño crea una clase única: sábado 17/10, 10–11, Patín Federadas, con Mariela («Práctica extra para el torneo») | Solo ofrece profesoras de Patín. Aparece en Clases el 17/10. Mariela la ve. La lista trae las 8 alumnas; no se puede tomar asistencia todavía. | Clases → Nuevo → Clase única: completó 17/10, 10–11, Patín Federadas y Mariela. Ofreció solo profesoras de Patín (Vanina, Lucía, Mariela, Verónica). Al Guardar, el navegador bloqueó Fecha: El valor debe ser igual o posterior a 24/10/2026, aunque Wings está en 15/10. No se creó la clase ni se eludió el control; no hay campo para la descripción del guion. Se omiten las comprobaciones de Mariela/lista de la nueva clase porque el alta no pudo realizarse. Ya se veía una clase preexistente de Mariela el 17/10 10–11; no se la modifica ni se usa como prueba de alta. Captura 6.11-fecha-bloqueada.jpg. Se continúa 6.12. | No |
| 6.12 | El dueño valida **primero la caja de Pablo y después la de Sandra** | Cada una se valida por separado; los movimientos quedan con la fecha de su día. | Primero caja #5 Pablo: tildó 3 movimientos y confirmó VALIDADA, mientras #4 Sandra seguía CERRADA (captura). Después #4: tildó 2 y confirmó VALIDADA. Cashflow conserva cinco renglones del 15/10, cada uno con Sandra/Pablo como registrador: Emma $45.000 EFE, Zoe $30.000 EFE, Felipe $38.000 BGA, Paula $43.000 EFE y Librería -$3.200 EFE. Capturas 6.12-*.jpg. | Sí |
| 6.13 | Cierre del dueño | Deuda **$1.612.000**; **69 Al día, 0 En plazo, 12 Morosos, 20 Deudores**. Reportes de octubre: ingresos **$607.000**, egresos **$73.700**, resultado **$533.300**. Disponible **$2.148.300** (Efectivo $535.800, MP $1.489.000, Galicia $123.500). Sin asistencia sigue en **32**. | Cobranza $1.612.000; 69 Al día, 0 En plazo, 12 Morosos, 20 Deudores. Reportes octubre: ingresos $607.000, egresos $73.700 y resultado $533.300. Disponible $2.148.300: EFE $535.800, MP $1.489.000, BGA $123.500; demás medios $0, por validar $0. Todo financiero coincide. Sin asistencia NO coincide: menú y listado filtrado muestran 34, no 32; primeras filas pendientes del 13/10 son Fútbol Avanzadas 19–20 y Patín Federadas 18–19. No se toman listas ajenas al guion. Capturas 6.13-*.jpg. | Parcial |


## Preparación

Reloj cambiado solo al iniciar el día: `/usr/bin/php82 artisan wings:fecha-simulada "2026-10-15 15:00"`, como wingstest en /home/wingstest/app. SSH confirmó staging/wingstest; sin consulta de datos de negocio. Se conserva el resultado del Día 5, sin reset ni correcciones.

### Evidencia de 6.9 — Lucía

#29: 12 alumnas, 11 presentes, Martina Herrera ausente. Guardado y recarga conservan las marcas. Cuatro alumnas excedían su frecuencia semanal: el formulario mostró Clase adicional (EXTRA) preseleccionada y se conservó esa opción; no se eligió recuperación. Captura 6.9-lucia-11-persistida.jpg.

### Evidencia de 6.9 — Verónica

Ingresó Verónica Salinas; abrió Otras clases de hoy y la #40 de Ocampo, Mariela. Guardó 7 presentes de 8, Helena Iglesias ausente. Recargada conserva siete marcas y sigue asignada a Mariela. No se usó reemplazo. Capturas 6.9-veronica-clase-ajena.jpg y 6.9-veronica-7-persistida.jpg.

## Resultado del día

13 situaciones recorridas: 10 Sí, 2 Parcial (6.3 recepción del aviso no comprobada y 6.13 contador de asistencia), 1 No (6.11 alta bloqueada por fecha). Sin reemplazos de alumnos ni de usuarios. No hubo Demasiados intentos ni limpieza de contadores.

Cobros del día $156.000, gasto de librería $3.200; cajas #4 y #5 validadas sin diferencia y en el orden pedido. Las cifras financieras finales coinciden con el guion. No se creó la práctica extra por la validación del navegador; no se la sustituyó por la clase preexistente. Sin asistencia quedó en 34.

Las operaciones de Pablo se agruparon antes de cambiar a las profesoras: su cierre 6.10 precedió 6.9. Se mantuvo el reloj del inicio, sin moverlo durante el día. La asistencia de las clases del día fue aceptada antes de su horario.

Pausas: se esperó alrededor de un minuto entre usuarios. Una medición inmediatamente antes de completar el formulario de Lucía marcó 59 segundos enteros; no se conserva una medición del instante exacto de envío y no se acredita allí el mínimo exacto de 60. Las demás mediciones registradas al cambiar de cuenta fueron de al menos 60 segundos.

No se cambió producto ni se consultó la base de negocio. Recepción de Telegram/correo, vigencia del precio de Lucas en noviembre y revisión independiente siguen sin acreditarse. T14 continúa en curso.

Captura complementaria de la cabecera de #29 consultada por ADMIN: 6.9-lucia-cabecera-control-admin.jpg, 12/11. No se volvió a guardar asistencia.

## Archivos de evidencia

[Capturas y recibos con enlaces](evidencia-dia-06/LEEME.md) · [Huellas e integridad](evidencia-dia-06/integridad.json).

La preparación, navegación y guardados tuvieron esperas de observación de Chrome; no se reenviaron pagos por esas esperas. No hubo bloqueo por intentos ni limpieza técnica de caché durante estos dos días.

## Control de entrega

Esperados y situaciones contrastados con el guion original; todos los resultados completados. Se verificaron 43 JPG, 2 PDF de una página y sus 2 PNG, huellas SHA-256 y enlaces locales. [Control documental](evidencia-dia-06/control-entrega.json). Los 116 enlaces de ambos informes/índices y las entradas nuevas de continuidad existen. Sin suite, migraciones ni cachés por esta entrega documental; sin cambios en vistas/CSS ni en la tabla externa o su copia. La revisión independiente de los resultados queda pendiente.
