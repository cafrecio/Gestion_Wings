# Día 8 — sábado 17/10/2026

Sitio: https://test.gestionar-te.com.ar (wingstest). Ejecutor: Codex CAB, 25/10/2026. Guion autorizado: GUION-DIAS-07-09.md, commit 5104fe5e.

Esperados y situaciones copiados literalmente del guion antes de operar. Navegación humana en Chrome por menús y botones; sin consultas de negocio en la base. Diferencias sin reparación ni reemplazos. Reloj solo al comenzar cada día; pausas entre usuarios de al menos 60 segundos.

| # | Situación | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|---|
| 8.1 | Pablo abre caja | Propone **$10.000**, lo que retuvo Sandra. Queda ABIERTA. | Propuso $10.000 del último cierre #6 de Sandra. Se contó/confirmó y abrió caja #7 de Pablo, 17/10 09:31, ABIERTA, inicial y esperado $10.000. Evidencia 8.1-apertura-propuesta.jpg y 8.1-caja-abierta.jpg. | Sí |
| 8.2 | Pablo cobra la **inscripción al torneo de primavera** de 4 patinadoras de Federadas, $8.000 cada una: un movimiento de ingreso **Torneos → Inscripciones** por $32.000 en efectivo, «Torneo de primavera: 4 inscripciones». | Queda en su caja como ingreso. | Un ingreso de $32.000 EFE, Torneos/Inscripciones, Torneo de primavera: 4 inscripciones. Caja #7: un movimiento, ingresos $32.000 y esperado $42.000. Evidencia 8.2-*.jpg. | Sí |
| 8.3 | **Joaquín Vega** (Fútbol Avanzadas, Deudor: agosto, septiembre y octubre, $48.000 cada uno) paga **$100.000** por Mercado Pago: agosto y septiembre completos, y $4.000 a cuenta de octubre. | Un solo cobro. El recibo trae agosto $48.000, septiembre $48.000 y octubre $4.000. Le queda octubre $44.000 y pasa de **Deudor a Moroso**: ya no debe meses anteriores, pero octubre está vencido. No pide motivo, porque paga lo más viejo primero. | DESVÍO DE EJECUCIÓN: al intentar cambiar octubre a $4.000, apareció No se pueden colocar más de dos decimales y el campo conservó $48.000. Codex envió sin detenerse a comprobar ese rechazo: CUOTA-25 cobró $144.000 MP (48.000/48.000/48.000), no $100.000. Joaquín quedó Al día, no Moroso con $44.000. No pidió motivo FIFO. No se canceló ni reparó. El caso de pago parcial queda sin acreditar; evidencia 8.3-*.jpg, ficha final y recibo. | No |
| 8.4 | Pablo registra un gasto en **Mantenimiento y arreglos → Reparaciones menores**: $9.500 en efectivo, «Ruedas del carro de conos». Se equivocó: eran **$5.900**, y lo corrige. | Queda **un solo** gasto, de $5.900. El efectivo esperado lo refleja. | Gasto EFE Mantenimiento y arreglos/Reparaciones menores, Ruedas del carro de conos, creado $9.500 y editado a $5.900. Detalle conserva un único gasto, movimiento #34; esperado de efectivo $36.100. Evidencia 8.4-*.jpg y 8.4-detalle-final.txt. | Sí |
| 8.5 | **Mariela** toma lista en la **#48**, Patín Federadas 10–11 | **8 alumnas.** Presentes: Helena Iglesias, Olivia Rossi, Guadalupe Núñez y Berenice Rossi (**4**). Ausentes: Martina Díaz, Catalina Díaz, Lucía Ramos y Florencia Vega. Propone «Clase adicional» para **Guadalupe Núñez y Berenice Rossi**, que ya fueron martes y jueves. Para Helena y Olivia no. Entre sus clases figura la nueva del **31/10 12–13**, si se pudo crear. | Clase #48: ocho alumnas; presentes Helena Iglesias, Olivia Rossi, Guadalupe Nuñez y Berenice Rossi (4); las otras cuatro ausentes. DIFERENCIA: propuso EXTRA para Helena también (1/1 usado), además de Guadalupe y Berenice; Olivia no. Se dejaron las tres propuestas, guardó y persistió al recargar. Desde Mariela se ve clase nueva #81 del 31/10 12–13. Evidencia 8.5-*.jpg/txt. | No |
| 8.6 | **Hernán** toma lista en la **#75**, Fútbol Avanzadas 11–12 | **15 alumnos, Lucas Gómez incluido.** Presentes: Joaquín Vega, Felipe Sosa, Lucas Gómez y Tomás Giménez (**4**). Propone «Clase adicional» para **Lucas** (paga 1 y vino el jueves) y **Tomás** (paga 2 y vino martes y jueves). Para Joaquín y Felipe no. | Clase #75: quince alumnos con Lucas Gomez. Se marcaron Joaquín Vega, Felipe Sosa, Lucas Gomez y Tomas Gimenez, cuatro presentes. Propuso EXTRA solo a Lucas y Tomas; no a Joaquín/Felipe. Se dejaron las propuestas, guardaron y persistieron al recargar. Evidencia 8.6-*.jpg/txt. | Sí |
| 8.7 | Pablo cierra y cuenta **$36.100** | Esperado **$36.100**, diferencia $0. Retiene $10.000 y entrega $26.100. | Caja #7 de Pablo cerrada. Esperado/contado $36.100, Sin diferencia $0 antes de confirmar; cambio $10.000 y entrega $26.100. Resumen persistido conserva esos valores. Evidencia 8.7-*.jpg/txt. | Sí |
| 8.8 | El dueño valida la caja de Pablo | Validada y reflejada. | El dueño tildó 3/3 movimientos y validó caja #7: Caja validada y reflejada en cashflow, estado VALIDADA. Se conserva el cobro real de Joaquín por $144.000, sin corregir el desvío de 8.3. Evidencia 8.8-verificados.jpg y 8.8-caja-validada.jpg. | Sí |
| 8.9 | Cierre del sábado | Deuda **$1.374.000**; **74 Al día, 0 En plazo, 10 Morosos, 17 Deudores**. Reportes de octubre: ingresos **$877.000**, que incluyen agosto y septiembre de Joaquín, porque los cobros atrasados cuentan en el mes en que se cobran. Egresos **$86.400**, resultado **$790.600**. Disponible **$2.440.600**: Efectivo $613.100, MP $1.674.000, Galicia $123.500, Nación $30.000. «Sin asistencia» **32**. | NO coincide por el desvío de ejecución 8.3. Cobranza real: $1.330.000, 75 Al día/0 En plazo/9 Morosos/17 Deudores. Reportes octubre: ingresos $921.000, egresos $86.400, resultado $834.600, incluyendo agosto/septiembre cobrados ahora. Disponible $2.484.600: EFE $613.100, MP $1.718.000, Galicia $123.500, Nación $30.000. Sin asistencia 32, cajas por validar 0. Diferencias monetarias $44.000 conciliadas con CUOTA-25; esperado original intacto. Evidencia 8.9-*.jpg/txt. | No |

## Preparación

Reloj fijado una sola vez al comenzar en 17/10/2026 09:30, con avance automático: [registro](evidencia-dia-08/reloj-inicio.txt). El salto invalidó la sesión previa: el primer envío del ingreso volvió al formulario y el segundo permitió entrar como Pablo; no se abrió caja hasta confirmar su identidad. Cambios de usuario con al menos 60 segundos desde Salir. Chrome y controles visibles; sin consultar la base de negocio ni reparar diferencias.

## Resultado del día

9 situaciones recorridas: 6 coinciden y 3 no (8.3, 8.5 y 8.9). Caja #7 validada, sin diferencia física; gasto corregido a $5.900. Cierre real: deuda $1.330.000; 75 Al día/0 En plazo/9 Morosos/17 Deudores; ingresos octubre $921.000, egresos $86.400, resultado $834.600, disponible $2.484.600. Sin asistencia 32. Los esperados originales permanecen intactos.

El pago parcial de 8.3 no quedó acreditado por el error de ejecución descrito abajo. La diferencia de Helena se entrega sin reparación ni atribución de causa. T14 sigue en curso y requiere revisión independiente; no se declara conformidad integral del guion.

### Desvío de ejecución en 8.3

El guion pedía $48.000 de agosto, $48.000 de septiembre y $4.000 de octubre. Al intentar reemplazar el importe de octubre, la interfaz mostró «No se pueden colocar más de dos decimales» y conservó $48.000. Codex envió el cobro sin detenerse a verificar ese rechazo. Es un error de esta ejecución: el caso de pago parcial no queda probado y no se atribuye a Wings un fallo de cobro parcial.

CUOTA-25 quedó por $144.000 MP y Joaquín Al día. No se canceló ni se fabricó un reemplazo: se mantiene la diferencia, según la orden recibida. Desde ese punto los controles financieros posteriores deben leerse con el desvío real de $44.000, sin cambiar los esperados originales: deuda $44.000 menor, un Al día más/un Moroso menos e ingresos, resultado y MP $44.000 mayores. Cada cierre se contrasta igualmente en las pantallas reales.

### Diferencia de asistencia en 8.5

Helena Iglesias apareció con 1/1 usado y la pantalla propuso EXTRA al marcarla presente; el guion esperaba que no. Se conservaron las tres propuestas (Helena, Guadalupe y Berenice) y se verificó su persistencia. Olivia no recibió adicional. No se modificó el dato ni se decidió aquí si debe cambiar el esperado o el producto.

## Archivos de evidencia

[Capturas y recibos con enlaces](evidencia-dia-08/LEEME.md) · [Galería](EVIDENCIA-DIAS-07-09.html) · [Control documental](evidencia-dia-08/control-entrega.json).

Esperados y situaciones literales contrastados con el guion y con la copia previa a operar. Imágenes decodificadas, recibos de una página con texto extraído y render revisado; enlaces locales y huellas verificadas. Pausas entre usuarios comprobadas a partir de los eventos guardados. Sin suite, migraciones ni cambios de aplicación por esta entrega documental. La verificación independiente queda pendiente.
