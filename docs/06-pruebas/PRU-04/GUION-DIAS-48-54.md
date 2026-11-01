# Prueba por días: días 48 a 54 (jueves 26/11 al miércoles 2/12 de 2026) — tanda 5 de 5

Armado por Claude CAB el 01/11/2026, sobre una copia de la base del sitio al 25/11 con el código de `main` del 01/11 (T94 a T102 y T104 a T109).

**Autorización de Carlos:** se pide al entregar el prompt. Ver [prompt](PROMPT-CODEX-DIAS-48-54.md).

**Todavía no se entrega.** Faltan los controles de T102, T107, T108 y T109, la devolución de T100 y que Cora termine en el sitio. Si un control cambia algo, se vuelve a simular el día que toca.

## Para qué sirve esta semana

| Trabajo | Cómo se cumple |
|---|---|
| **Probar las reparaciones** | T94 a T102, T105 a T109, por pantalla y con el relato de un día normal |
| **Dejar limpio lo que quedó mal cargado** | Clara y Thiago, con las herramientas que tiene el dueño (49.2 y 49.3) |
| **Pasar por el 1/12** | Generación, Revisión con sus salidas y liquidaciones de noviembre, cobradas y pagadas |
| **Probar lo que nunca se probó** | Unos 50 casos de la [planilla](https://claude.ai/artifact/8cgmP6kq4upcBKXw9KkJAt). Lo que no entra está al final, con el motivo |
| **Sostener el volumen** | Cobranza de diciembre: 12 cobros el 1/12 y 15 el 2/12, en las rutinas |

### Lo que el 1/12 tiene que dar

Comprobado corriendo la generación de las 06:00 sobre la simulación.

**Constancia:** 78 cuotas creadas, 8 a Revisión, 31 ya existían, 0 sin plan, 117 activos. **Cobranza a las 06:00: $3.123.910** (32 Al día, 79 En plazo, 6 Deudores). Cambia contra el ensayo de la tanda 4 (84, 7 y 23) porque en esta semana hay más adelantos y altas.

| Camino del generador | Quién | Resultado |
|---|---|---|
| No vino en todo el mes | Pilar Ramos, Valentina Suárez | Revisión |
| Su único presente fue en una clase cancelada | Alma Navarro | Revisión |
| Alta reciente que no pagó | Emma Villalba | Revisión |
| Alta reciente con el pago anulado | Tobías Vera | Revisión |
| Alta un día antes del límite, pagada | Joaquina Luna | Revisión |
| Fecha de ingreso vieja, recién cargada, pagó y no vino | **Ciro Paz** (48.10) | Revisión: su ingreso es de agosto, no reciente |
| Segundo deporte sin venir | Thiago Ruiz | Patín a Revisión; Fútbol, cuota $28.000 |
| Alta justo en el límite, pagada | Bruno Cabrera | Cuota **$33.000**: el precio que dejó 48.4 |
| Alta reciente, pagada, sin venir | Valentino Godoy, Nina Arias (52.2) | Cuota $44.000 y $30.000 |
| Ingreso el 1/12 | Renzo Acuña | Sigue la de $25.000, pendiente |
| Adelantados | Los de la tanda 4, más Mateo Castro, Catalina y Melina Castro, Franco Molina, Felipe Sosa, Delfina Giménez, Lorenzo Morales y Benjamín Fernández | No crea otra |
| Con el saldo perdonado | Clara Iglesias | Sigue **Condonada**: no deuda |
| No vino, pero ya pagó | Facundo Ríos | No va a Revisión |
| Baja antes del 1/12 | Lautaro Medina, Candela | Sin cuota |
| Vino y no pagó | Liam Pereyra | Cuota $35.000; Deudor, $73.500 |
| Cambió de grupo | Lara Quiroga | Cuota $43.000 |

**Deudores a las 06:00:** Alma $36.300, Liam $73.500, Pilar $33.000, Thiago (Patín) $12.000, Valentina $38.500 y Emma $30.410. Sigue sin preparar: alumno sin plan (ALU-023).

### Quién opera

| Día | Dueño | Operativa | Profesora, por pantalla | Errores humanos |
|---|---|---|---|---|
| 48 | Grupos, niveles, Cashflow | Sandra | Hernán carga su lista | Dos filas iguales, pantalla vieja, transferencia equivocada, anular sin motivo |
| 49 | Clara, Thiago, gasto en el turno | Rocío | — | Un cero de más, cerrar con la pantalla vieja, doble clic |
| 50 | Desactiva y reactiva usuarios | Pablo | Mariela y Hernán | Doble clic al abrir, dos personas en la misma lista, salir y volver atrás |
| 51 | Desde su casa; la socia corrige | — | — | Nombres repetidos, abreviatura larga, fecha vieja |
| 52 | Cambia la inscripción con un alta a la vista | Sandra | Lucía y Hernán se olvidan | Guardar con el importe viejo |
| 53 | Valida, liquida, cancela feriados | Pablo | Hernán por teléfono, Lucía | Generar por error, liquidar dos veces, ajustar mal |
| 54 | Resuelve con Rocío, paga | Rocío | Hernán por teléfono | Nota corta, pantalla vieja, cobrar sin motivo |

## Punto de partida

- **El sitio tiene que tener `main` desplegado, con sus migraciones.** Hasta el día 47 tenía `937d99cc`.
- Reloj en el **25/11 a las 09:00**; **no volver atrás**.
- Deuda **$215.910**; 114 activos: 107 Al día, 2 En plazo (Thiago y Tobías), 5 Morosos. Disponible **$7.441.520,75**: Efectivo $1.772.100, Mercado Pago $3.835.270,75, Galicia $1.776.150, Nación $58.000. Rocío dejó $10.000 de cambio.
- **No vienen en toda la tanda:** Pilar, Valentina, Emma Villalba, Valentino, Joaquina, Bruno y la ficha de Patín de Thiago. **Alma no viene.** **Liam viene y no paga.** **Tobías viene una vez, a probar, el 30/11.**

## Cómo se trabaja

Igual que las tandas anteriores: reloj del día una vez, rutina de la mañana con `--simular` y después real, las tareas **por pantalla** y en orden, rutina de la noche. Lo que no coincide se anota y se sigue; **no se repara nada**. Lo marcado **Solo prueba técnica** se fuerza en la revisión de Claude.

**Teléfono:** en cada paso marcado teléfono, usar `Emulation.setDeviceMetricsOverride` con ancho 375 y `mobile: true`, como `scripts/medir-ancho-movil.mjs:98`, y dejar en la evidencia `window.innerWidth` y `document.documentElement.scrollWidth`. Si `innerWidth` no da 375, el paso queda **no probado**: en la tanda 4 la pantalla siguió en 1905.

**Defectos que este guion espera:** son nuevos y están retratados a propósito.

| Tarea | Paso | Qué pasa |
|---|---|---|
| **T110** | 48.4 | Guardar un grupo con la pantalla vieja se lleva puesta la frecuencia que agregó la otra pestaña, sin aviso. GRU-005 pide rechazarlo |
| **T111** | 53.4 | Mora tiene una ficha por deporte: queda presente en dos clases a las 16:00. ASI-008 pide rechazarlo, y el control mira la ficha, no la persona |
| **T112** | 53.4 | Un presente no saca a Tobías de Revisión. CBZ-016 a pide que salga. Ya anotado el 13/10 en el catálogo, sin tarea |

## Día 48: jueves 26/11, llegan las reparaciones

Reloj **09:00**. **Rutina** (`dia-48-manana.json`): Sandra abre con $10.000; cinco familias de Fútbol Avanzadas pagan el torneo a $3.000 (cuatro en efectivo, una por MP); remera $18.000; limpieza $3.200, librería $1.500, insumos $2.600, botiquín $1.700, cerradura $4.800; listas de Patín Avanzadas (faltan Valentina y Zoe Pereyra), Federadas (falta Olivia Rossi) y Adultos (faltan Joaquina y Bruno).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 48.1 | Dueño | **T94.** En la ficha de los grupos: elimina Fútbol Infantiles 1 por semana (tiene a Renzo); Infantiles 2 por semana (nunca usada); Federadas 1 por semana (la usó alguien antes). Vuelve a agregar Federadas 1 por semana a **$42.000**. | «1x por semana tiene 1 alumno: cambiales el plan antes.»; «Plan eliminado.»; «Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.»; «Plan agregado.» sobre la misma fila, sin duplicar (GRU-004). |
| 48.2 | Dueño | **T99.** Grupos → Nuevo: Patín, Infantiles, **dos filas de 1 por semana**. Después una sola, a $22.000. Elimina esa única frecuencia. | «Hay dos filas con la misma frecuencia. Dejá una sola.», sin página de error; «Grupo creado correctamente.»; «No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.» (GRU-002, GRU-004). |
| 48.3 | Dueño | **T102.** Inactiva el nivel Infantiles. Crea el nivel **Mini**. En Editar, pasa Patín Infantiles a Principiantes; después a Mini. Inactiva Mini. Desactiva el grupo e inactiva Mini. Activa el grupo. | «No se puede inactivar el nivel porque tiene 2 grupos activos. Pasalos a otro nivel o desactivalos.»; «Nivel creado correctamente.»; «Ya existe un grupo con ese deporte y nivel.»; «Grupo actualizado correctamente.»; «…tiene 1 grupo activo. Pasalo a otro nivel o desactivalo.»; «Nivel inactivado correctamente.»; «No se puede activar el grupo porque el nivel «Mini» está inactivo. Activá el nivel o pasá el grupo a otro.» (CAT-011, CAT-003). |
| 48.4 | Dueño | **Pantalla vieja.** Abre Editar de Patín Adultos en dos pestañas. En una agrega 3 por semana a $52.000. En la otra cambia los precios a $33.000 y $43.000 y guarda. | **DEFECTO (T110):** «Grupo actualizado correctamente.» Los precios cambian y **el 3 por semana desaparece**, sin aviso. Anotarlo y seguir (GRU-005). |
| 48.5 | Dueño | **T96.** Abre Caja, busca Configurar. Escribe `/caja/configuracion`. Tipos de Caja → Efectivo. | No hay Configurar; la dirección vuelve a Caja. Nombre y saldo inicial ($250.000) de solo lectura (CAJ-002). **Solo prueba técnica:** el pedido directo. |
| 48.6 | Dueño | Cashflow → Nuevo: **Ajuste de saldo: salida, $1.200** de Efectivo, «Arqueo de la caja fuerte: faltan $1.200». Sandra busca ese renglón al cargar un gasto. | «Movimiento directo registrado en cashflow.» Disponible baja $1.200; **el resultado no cambia** (CAJ-002 c). A Sandra no se le ofrece. |
| 48.7 | Dueño | **T97.** Transferencia: Mercado Pago a Galicia, **$400.000**, «Era a Nación, no a Galicia». Anular desde la fila de entrada, sin motivo; con «Cuenta de destino equivocada». Otra pestaña anula desde la fila de salida. | «Transferencia registrada…»; «Indicá el motivo de la anulación.»; «…anulada. Los originales y los dos asientos en contra se conservan.»; «La transferencia ya está anulada.» Las cuatro filas solo con Ver; MP y Galicia como estaban (CSH-020, CSH-021, CSH-023). |
| 48.8 | Dueño | Transferencia real: Efectivo a Galicia, **$800.000**, «Depósito del efectivo de la semana». | Efectivo baja y Galicia sube $800.000 (CSH-018). |
| 48.9 | Dueño | **T106.** Cashflow, semana, sin filtro. Con Galicia. Historial del 23 al 26/11. | Sin filtro: ingresos **$991.600**, egresos **$58.400**; no suman las transferencias. Con Galicia: ingresos **$946.000**, con el depósito. Historial: los mismos $991.600 y $58.400. |
| 48.10 | Sandra | **Ciro Paz**: nacido el 11/4/2015, DNI 49111222, tutora Laura Paz (11-4455-6600), Fútbol Principiantes, 1 por semana, ingreso **10/8/2026**. Sin elegir; eligiendo generar la cuota. Paga todo en efectivo. | «Elegí si se genera la cuota de este mes antes de guardar.»; «Alumno creado correctamente.»: noviembre **$28.000** e inscripción $8.000; **Deudor, $36.000**. Cobra $36.000; Al día (ALU-006). |
| 48.11 | Hernán | Por pantalla: Fútbol Avanzadas (falta Simón Silva). | «Asistencias guardadas.» |

**Noche** (`dia-48-noche.json`): Sandra cierra con el esperado, **$62.200**, y deja $10.000; el dueño valida.

**Cierre del día 48:** deuda **$215.910**; 115 activos: 108 Al día, 2 En plazo, 5 Morosos. Ingresos **$4.378.900**; egresos $2.847.680; resultado $1.531.220; disponible **$7.495.520,75**. Efectivo $1.023.100; MP $3.838.270,75; Galicia $2.576.150; Nación $58.000.

## Día 49: viernes 27/11, otra vez el cero de más

Reloj **09:00**. **Rutina** (`dia-49-manana.json`): Rocío abre con $10.000; dos familias de Adultos pagan el torneo a $3.000; remeras $18.000 en efectivo y $18.000 por MP; detergente $2.900, sobres $1.100, conos $3.400, vendas $2.200; lista de Intermedias (faltan Alma, Emma Villalba y Rocío Sosa).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 49.1 | Rocío | **T100.** **Mateo Castro** paga diciembre en efectivo: **$280.000**. Se da cuenta: cancela con «Un cero de más: eran $28.000». Vuelve a Cobrar y escribe **$28.000**. | El esperado sube a $304.400 y vuelve a $24.400. **Diciembre desaparece** de la ficha. El nuevo cobro **no pide motivo** y deja diciembre Pagada, $28.000 (COB-019). |
| 49.2 | Dueño | **El saldo de Clara.** En su ficha, Condonar diciembre: con «Error»; con «Un cero de más del 20/11: la cuota era de $30.000». Vuelve a intentarlo. | «El motivo de la condonación debe tener al menos 10 caracteres.»; «Deuda condonada correctamente.»; queda Condonada, con los $30.000 pagados. Ya no hay Condonar (CBZ-009). |
| 49.3 | Dueño | **La inscripción de Thiago.** En la ficha de Patín, Condonar la inscripción, «Ya es del club: no paga otra inscripción». | «Inscripción condonada.» De **$20.000 a $12.000**; deuda total **$207.910**. |
| 49.4 | Rocío | **T108.** Abre el alta con el DNI de **Mateo Rodríguez** (vino de la primera carga). Segunda ficha: Patín Avanzadas, 1 por semana, ingreso 27/11. Paga por MP. | El aviso dice «Esta persona ya es del club: no paga inscripción.» Noviembre **$14.000** (40%), sin inscripción. Deuda total $221.910 y, al pagar, $207.910 (ALU-005). |
| 49.5 | Dueño | **T101.** En la tarjeta del turno de Rocío, Agregar: Insumos **$2.500**, «Lamparita del vestuario: la pagó la caja». Rocío mira su caja. | «Movimiento registrado.» **Figura a nombre de Admin Prueba.** Esperado **$49.900** (CAJ-041). |
| 49.6 | Rocío | **Catalina Castro** paga diciembre, $45.000 por MP. | Entra; ingresos de noviembre no suben. |
| 49.7 | Rocío | **T105 y T109.** Abre el recibo de Mateo Castro. Escribe `/recibos/cuota/9999999`. Escribe `/alumnos?estado=basura&page=xx&deporte_id=abc`. | PDF de unos **210 KB**. «Página no disponible». El listado de alumnos, sin error. |
| 49.8 | Rocío | **T107.** Abre el cierre: **$49.900**. En otra pestaña, **Melina Castro** paga diciembre, **$45.000 en efectivo**. Vuelve a la pantalla vieja y cierra con $49.900. Después con **$94.900**, dejando $10.000. Doble clic. | «Cambió el efectivo esperado: ahora es $94,900.00. Entró o se corrigió un movimiento desde que abriste esta pantalla. Revisá lo que contaste y tocá Cerrar de nuevo.»; sigue abierta. «Caja cerrada.», diferencia $0. Segundo clic: «La caja no está en estado editable.» (CAJ-023, CAJ-024). |

**Noche** (`dia-49-noche.json`): el dueño valida.

**Cierre del día 49:** deuda **$207.910**; 116 activos: 109, 2, 5. Ingresos **$4.398.900**; egresos $2.859.780; resultado $1.539.120; disponible **$7.657.420,75**. Efectivo $1.108.000; MP $3.915.270,75; Galicia $2.576.150.

## Día 50: sábado 28/11, sesiones y listas

Reloj **09:30**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 50.1 | Pablo y Sandra | Pablo abre con los $10.000 propuestos, con doble clic. Sandra, desde otro equipo, abre al mismo tiempo. | Una sola caja abierta. El segundo envío: «Hay un turno abierto. Cerralo antes de abrir otro.» (CAJ-010). |
| 50.2 | Dueño | **A las 09:30** abre Clases. Fija el reloj en **10:30** y mira otra vez. | Federadas de las 10: «Por comenzar» y después «En curso». Fútbol Avanzadas de las 11: «Programada» y «Por comenzar» (CLA-022). |

**Rutina** (`dia-50-manana.json`, con el reloj en **10:30**): Pablo carga el torneo de Renzo, $3.000; remera $18.000; bolsas $2.500, marcadores $900, inflador $4.100.

Reloj **12:30**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 50.3 | Dueño | Clases, filtro Finalizada, y «Sin asistencia». | Las dos clases del día: **2** (CLA-022 d, f). |
| 50.4 | Mariela y Pablo | La misma lista de Federadas abierta en dos pestañas. Mariela guarda con faltas de Lucía Ramos y Berenice Rossi. Pablo, con la pantalla vieja, guarda con la falta de Lucía Ramos. | «Asistencias guardadas.» las dos veces. Queda la última: 8 marcas, **7 presentes**, sin duplicar (ASI-012). |
| 50.5 | Hernán | Fútbol Avanzadas (falta Lucas Gómez). | Sin asistencia **0** (CLA-022 e). |
| 50.6 | Dueño | **ACC-005 y USU-010.** Usuarios: desactiva a Pablo. Desactiva a Hernán, con su sesión abierta en otro navegador y la lista a la vista. Hernán toca Clases y guarda la lista. El dueño lo reactiva. Hernán entra con su clave. | Pablo: «Este usuario tiene una caja abierta. Primero hay que cerrar la caja.» Hernán va al ingreso, «Tu cuenta fue desactivada.»; no se guarda nada. Reactivado, entra; sus 15 marcas siguen. **El catálogo está viejo:** ACC-005 c cuenta que una operativa con caja abierta se puede desactivar; anotarlo. |
| 50.7 | Pablo | Cobra diciembre: **Franco Molina** $38.000 en efectivo; **Felipe Sosa** $38.000 por MP. | Esperado **$61.500**. |
| 50.8 | Pablo | Abre la clase de Adultos del **3/12**: cancela, «Mariela avisó que ese jueves no puede». Busca «Cancelar serie». | «Clase cancelada.» Una sola; no le ofrece la serie (CLA-025). **Solo prueba técnica:** forzarla. |
| 50.9 | Pablo | **ACC-008 y ACC-017.** Entra también desde otro navegador tildando Recordarme: cierra ese navegador y lo abre. Después Salir en uno y Atrás. | Sigue adentro en el segundo. Salir no cierra el otro; Atrás no ejecuta nada. |
| 50.10 | Pablo y Lucía | **ACC-014 y ACC-021.** Escriben `/cajas/1/validar`, `/deudas/1/condonar`, `/alumnos/inscripcion-preview`, `/usuarios/check-email`. | La página en castellano que explica que esa dirección no se abre a mano. Lucía: sin datos de alumnos ni de plata. |

**Noche** (`dia-50-noche.json`): Pablo cierra con el esperado y deja $10.000; el dueño valida.

**Cierre del día 50:** deuda **$207.910**; 116 activos. Ingresos **$4.401.900**; egresos $2.867.280; resultado $1.534.620; disponible **$7.746.920,75**. Efectivo $1.159.500; MP $3.953.270,75; Galicia $2.576.150.

## Día 51: domingo 29/11, el dueño desde su casa

Reloj **20:00**. Sin actividad en el club.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 51.1 | Dueño | Deportes → Nuevo: **Natación**, por hora; « natación ». Profesores → Nuevo: **Nora Vidal**, Natación, DNI 30111222, nacida el 10/3/1985, Av. Rivadavia 1200, CABA, 11-6655-4433, **sin valor hora**; después a **$14.000**. | «Deporte creado correctamente.»; «Ya existe un deporte con ese nombre.»; «Cargá el valor por hora.»; «Profesor creado correctamente.» (CAT-001, PRO-002). |
| 51.2 | Dueño | Tipos de Caja → Nuevo: Caja Torneo con abreviatura `TORNEO`; saldo `-1`; nombre «mercado pago»; después bien: **Caja Torneo**, `TOR`, **$50.000**. | «La abreviatura no puede tener más de 5 caracteres.»; «El saldo inicial no puede ser negativo…»; «Ya existe un tipo de caja con ese nombre.»; «Tipo de caja creado correctamente.» Disponible sube $50.000 (CAT-005). |
| 51.3 | Dueño | Reparaciones menores pasa a solo del dueño. | «Subrubro actualizado correctamente.» Sandra deja de verlo el 52 (CAT-008). |
| 51.4 | Dueño | Busca editar o desactivar Cuota Mensual. | No hay; forzando: «No se puede editar: subrubro reservado del sistema.» (CAT-009). |
| 51.5 | Dueño | Interruptor de **Vanina Directora** en Profesores: la apaga y la prende. Interruptor de su propia cuenta. | «Profesora inactiva…»; «Profesora activa. Su usuario conserva el estado anterior…»; su cuenta dice «Tu cuenta» (SIS-014). |
| 51.6 | Dueño | Usuarios → Nuevo: **Vanina Socia**, vanina.socia@example.com, clave `Socia-del-club-2026!`, Administrador. Carga **Eventos $15.000** por Galicia, «Sonido del torneo». Entra Vanina y lo corrige a **$12.000**, «El presupuesto final fue $12.000». | «Usuario creado correctamente.»; «Movimiento actualizado. El historial se conserva.» Cargó uno, corrigió la otra, y los dos a la vista (CSH-011). |
| 51.7 | Dueño | **T97.** Transferencia: Galicia a Nación, $50.000, **20/10**, «Quedó sin cargar en octubre»: sin confirmar y confirmando. La anula: «Ya estaba cargada: me confundí». | Pide confirmar; «…registrada…»; «…anulada…». Octubre sigue **$3.417.850,75 y $341.350** (CSH-022). |
| 51.8 | Dueño | Cashflow: busca Editar y Eliminar en un movimiento de caja validada, en un cobro suyo, en una liquidación pagada y en un anulado. | No ofrece corregir (CSH-009). **Solo prueba técnica:** forzarlo. |

**Cierre del día 51:** deuda **$207.910**. Ingresos $4.401.900; egresos **$2.879.280**; resultado $1.522.620; disponible **$7.784.920,75**. Galicia $2.564.150; Caja Torneo $50.000.

## Día 52: lunes 30/11, el último del mes

Reloj **09:00**. **Rutina** (`dia-52-manana.json`): Sandra abre con $10.000; remera $18.000; lavandina $3.600; **fotocopias $1.250,50**; cinta $2.800; alcohol $1.400; lista de Patín Principiantes (faltan Pilar, Valentino y Thiago). **Hernán y Lucía se olvidan:** Fútbol Principiantes de las 16 e Intermedias de las 17 quedan sin lista.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 52.1 | Dueño | Configuración: inscripción a **$0**. | «El importe de la inscripción debe ser mayor que cero.» ALU-010 no se alcanza por pantalla; anotarlo. |
| 52.2 | Sandra y dueño | Sandra abre el alta de **Nina Arias**: nacida el 20/2/2018, DNI 57222333, tutor Pablo Arias (11-7788-9900), Patín Principiantes, 1 por semana, ingreso 30/11, con el aviso a la vista. El dueño sube la inscripción a **$9.000**. Sandra guarda. El dueño la vuelve a **$8.000**. Sandra revisa el aviso y guarda. | «El importe de inscripción cambió. Revise el nuevo importe antes de guardar.», sin crear nada. «Alumno creado correctamente.»: noviembre **$12.000**, inscripción $8.000 (ALU-009). |
| 52.3 | Sandra | Nina paga $20.000 por **Caja Torneo**. El dueño intenta cambiarle el saldo inicial. | El medio se ofrece; ya no deja cambiar el saldo inicial (CAT-005 a, c). |
| 52.4 | Sandra y dueño | Sandra cobra diciembre: **Delfina Giménez** $35.000 en efectivo; **Lorenzo Morales** $38.000 por MP. El dueño: **Benjamín Fernández**, $52.000 por Galicia. | Entran; noviembre no sube. |
| 52.5 | Dueño | Cashflow → Nuevo: **Luz $9.800** por Galicia, «Edesur, noviembre». | Galicia **$2.606.350**. |
| 52.6 | Sandra | **T99.** Renzo: cambia el ingreso al 5/12, sin motivo. Abre Nuevo gasto. | «El campo motivo de la fecha de ingreso es obligatorio.», sin nombres internos. Reparaciones menores no se ofrece (CAT-008 a). |
| 52.7 | Dueño | **REP-010, SIS-002.** Compara Reportes, Inicio, Cashflow, Historial y la caja. Busca los $1.250,50 en Caja, Historial, Movimientos y Cashflow. | Cuadran. **Ingresos $20.000** de hoy: Nina. Egresos **$19.850,50**. Los centavos se ven. **El catálogo está viejo:** dice punto de miles y coma decimal, y desde T30 es `$1,250.50`; anotarlo. |

**Noche** (`dia-52-noche.json`): Sandra cierra con el esperado, **$53.949,50**, y deja $10.000. **El dueño no valida hoy.**

**Cierre del día 52:** deuda **$207.910**; 117 activos: 110, 2, 5; **1 caja por validar, 2 clases sin lista**. Ingresos **$4.421.900**; egresos $2.898.130,50; resultado $1.523.769,50; disponible **$7.929.070,25**. Efectivo $1.159.500; MP $3.953.270,75; Galicia $2.606.350; Caja Torneo $50.000.

## Día 53: martes 1/12, cambia el mes

Reloj **05:55**; **esperar las 06:00 y las 06:15**: corren solas la generación y los recargos. A las **08:30** sigue el día.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 53.0 | Dueño | Cobranza, Revisión, Reportes de Alumnos, Inicio. Sandra, Inicio. Lucía escribe `/revision-cobranza`. | La tabla de arriba. 8 pendientes, igual en todas. Lucía: Sin permiso. Deudores, los seis de arriba (CBZ-003, CBZ-013, CBZ-017). **Clara no aparece.** Las dos fichas de Thiago, cada una por su lado. |
| 53.1 | Dueño | Valida la caja del 30/11. Mira noviembre y diciembre. | «Caja validada y reflejada en cashflow.» Noviembre sigue **$4.421.900 y $2.898.130,50**; diciembre **$1.214.000**, sin cambios (REP-013). |
| 53.2 | Dueño | Cancela los feriados: 8/12, «Feriado: Inmaculada Concepción», y 25/12, «Feriado: Navidad». | **4 clases** el 8/12 y **1** el 25/12. |
| 53.3 | Dueño | Reactiva a **Lautaro Medina**. | «Alumno activado correctamente. Se generó la cuota de diciembre: $28,000.00.» En plazo, $28.000; deuda **$3.151.910** (ALU-018). |

**Rutina** (`dia-53-manana.json`, 09:00): Pablo abre con $10.000; doce familias pagan diciembre (ocho en efectivo $249.000, tres por MP $131.000, una por Galicia $33.000); limpieza $3.100, planillas $1.700, bombín $2.400; listas de Patín Avanzadas (faltan Valentina y Carla Morales), Federadas (falta Catalina Díaz), Fútbol Avanzadas (falta Simón Silva) y Adultos (falta Joaquina).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 53.4 | Hernán | **Por teléfono:** Fútbol Principiantes del 30/11, marca a todos menos a Facundo. Sin motivos. Con los motivos de Tobías («Vino a probar: la mamá paga el miércoles»), Liam («El papá avisó que paga esta semana») y Thiago («Viene a Fútbol: arregla lo de Patín»). **Mora Benítez presente.** | «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.» Con motivos: «Asistencias guardadas.» **DEFECTO (T111):** Mora queda presente en dos clases a las 16:00. **DEFECTO (T112):** Tobías sigue en Revisión, 8 pendientes (ASI-014, PEN-003, ASI-008, CBZ-016 a). |
| 53.5 | Dueño y Lucía | **LIQ-003, LIQ-007, LIQ-004.** Generar la de **Lucía**, noviembre: mira el aviso. Lucía carga Intermedias del 30/11 (faltan Alma y Emma). Recalcular. Cerrar. Generar otra vez. | Avisa la clase sin asistencia. **$252.000**, 21 clases. «Liquidación recalculada correctamente.»: **$264.000**, 22. «Liquidación cerrada correctamente.» «Ya existe una liquidación para el profesor en 11/2026.» |
| 53.6 | Lucía y dueño | **ASI-006, LIQ-012.** Lucía corrige la lista: también faltó Rocío Sosa. El dueño cancela: sin motivo; «Mal»; «Asistencia mal cargada del 30/11». Lucía corrige, sin motivo y con «Rocío Sosa no vino: la marqué mal». Generar de nuevo y cerrar. | «No se pueden modificar asistencias: la clase integra una liquidación cerrada.»; «Indicá el motivo de la cancelación.»; «El motivo debe tener al menos 5 caracteres.»; «Liquidación cancelada. Ya podés revisar asistencias y regenerarla.»; «Esta clase ya tenía lista tomada: indicá el motivo de la corrección.»; «Asistencias guardadas.» Otra vez **$264.000**: se paga la hora. La cancelada dice cuál la reemplazó. |
| 53.7 | Dueño | **LIQ-014, LIQ-017.** Genera la de **Mariela** por error: $342.000. La elimina. Intenta eliminar la cerrada de Lucía. Edita a Mariela: valor hora **$19.000**. Genera y cierra. Abre la de octubre de Mariela. | «Liquidación eliminada.»; «No se puede eliminar una liquidación cerrada o cancelada.»; noviembre **$361.000** (19 clases a $19.000). Octubre sigue a **$18.000**. |
| 53.8 | Dueño | **Hernán, LIQ-015.** Generar. Aprueba la cuota atrasada de Santino Romero. Ajustar a **$561.100**: sin motivo; `-1`; con otra pestaña que cambió; con «Redondeo acordado con Hernán». Cerrar antes de aprobar y después. | **$549.800** a pagar y $11.200 de la atrasada. «Primero validá o rechazá cada cuota atrasada.»; «Decisión registrada…»: **$561.000**. «Indicá el motivo del ajuste.»; negativo rechazado; «El importe cambió mientras editabas…»; «Monto final actualizado. El cálculo original se conserva.» Cerrada por **$561.100**. |
| 53.9 | Dueño | Genera y cierra la de **Martín**. Genera la de **Gonzalo**: no tuvo nada en noviembre. La elimina. | Martín **$195.000**. Gonzalo $0, sin detalles: **no avisa que está vacía**; anotarlo. |
| 53.10 | Pablo | **COB-032.** **Carla Morales** paga **$20.000** de diciembre con doble clic, «Paga el resto el viernes». | Un solo cobro: saldo $15.000. |

**Noche** (`dia-53-noche.json`): Pablo cierra con el esperado, **$271.800**, y deja $10.000; el dueño valida.

**Cierre del día 53:** deuda **$2.718.910**; 118 activos: 44 Al día, 68 En plazo, 6 Deudores. Diciembre: ingresos **$1.647.000**; egresos $7.200; resultado $1.639.800; disponible **$8.354.870,25**. Efectivo $1.465.249,50; MP $4.122.270,75; Galicia $2.639.350; Caja Torneo $70.000.

## Día 54: miércoles 2/12, se resuelve la Revisión

Reloj **09:00**. **Rutina** (`dia-54-manana.json`): Rocío abre con $10.000; quince familias pagan diciembre (diez en efectivo $364.000, cuatro por MP $157.000, una por Galicia $43.000); rejilla $2.700, gasas $1.900, tinta $1.300; lista de Patín Principiantes (faltan Pilar, Valentino, Paulina Silva y Thiago).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 54.1 | Rocío y dueño | **CBZ-014.** **Pilar:** Continúa sin nota; «Sí.»; «La mamá confirma que vuelve en diciembre». **Valentina**, el dueño: Inactivo, «Se mudó: no vuelve». | «La nota es obligatoria.»; «La nota debe tener al menos 5 caracteres.»; «Alumno confirmado como activo. Se generó la deuda del período.»: Pilar Deudor, **$63.000**. «Alumno marcado como inactivo.»: sin cuota de diciembre; sale de Cobranza y sus $38.500 quedan en la ficha. Deuda **$2.146.410**. |
| 54.2 | Dueño y Rocío | **CBZ-015.** Joaquina, abierta en dos equipos. El dueño: Continúa, «Vuelve después del viaje». Rocío, con la pantalla vieja: Inactivo. | «Esta revisión ya fue resuelta.» Sigue activa, con una sola cuota de **$33.000**. |
| 54.3 | Rocío | **CBZ-016 b.** **Emma Villalba** paga noviembre, $30.410 en efectivo. Mira Revisión. Paga diciembre, **$33.000**. | Con noviembre sigue pendiente. Con diciembre sale sola: «Reactivado automáticamente». |
| 54.4 | Rocío | Ficha de Patín de **Thiago**: Inactivo, «Sigue solo en Fútbol». **Alma**, **Tobías** y **Ciro**: Continúa. | Thiago sigue activo en Fútbol, En plazo, $28.000. Revisión **0**. Deudores: Alma $69.300, Liam, Pilar, Tobías $47.200. |
| 54.5 | Dueño | **CBZ-021.** Revisión: Pendientes, Resueltos, Todos, período. Inicio y Reportes de Alumnos. | Quién, cuándo y la nota. Emma: «Reactivado automáticamente». Todos dicen 0. |
| 54.6 | Rocío | **COB-024, CBZ-004.** **Santino Flores** pasa a 2 por semana y paga. **Zoe Pereyra**, que vino ayer, pasa a 1 por semana y paga por MP. **Liam** paga diciembre, $35.000: sin motivo y con «Paga diciembre: noviembre lo trae la semana que viene». | Santino **$35.000**, ya al plan nuevo. Zoe **$45.000**, al plan anterior; rige desde enero. «Quedan 1 meses anteriores impagos por $38,500.00 (noviembre 2026)…»; con motivo entra: Liam sigue Deudor, **$38.500**. |
| 54.7 | Dueño | Paga: **Lucía $264.000** y **Mariela $361.000** por Galicia; **Hernán $561.100** y **Martín $195.000** por MP. Doble clic en la de Lucía. Busca Cancelar, Eliminar, Recalcular y Ajustar en una pagada. | «Liquidación pagada. Movimiento registrado en cashflow.»; el segundo envío no duplica. Una pagada no ofrece nada (LIQ-013). **Solo prueba técnica:** forzarlo. Egresos de diciembre suben **$1.381.100**; noviembre no cambia. |
| 54.8 | Hernán | **Por teléfono (19:00):** Fútbol Principiantes, falta Facundo. Con motivos de Liam, Thiago y Tobías. | «Asistencias guardadas.», 27 alumnos, sin desplazarse de costado (ASI-014, SIS-003). |

**Noche** (`dia-54-noche.json`): Rocío cierra con el esperado, **$501.510**, y deja $10.000; el dueño valida.

**Cierre del día 54 y de la prueba:** deuda **$2.118.000**; 116 activos: 60 Al día, 52 En plazo, 4 Deudores; Revisión 0; sin lista 0. Diciembre: ingresos **$2.389.410**; egresos $1.394.200; resultado $995.210; disponible **$7.710.280,25**. Efectivo $1.956.759,50; MP $3.568.170,75; Galicia $2.057.350; Nación $58.000; Caja Torneo $70.000. Octubre sigue $3.417.850,75 y $341.350; noviembre, $4.421.900 y $2.898.130,50.

## Cómo se sabe que da bien

- **Plata:** corrida en la copia del sitio con `main`, y cada día comparado a mano con sus operaciones, medio por medio: **coinciden al peso**, incluidos los egresos e ingresos. Las cuentas se hicieron **después** de correr, no antes, a diferencia de la tanda 4. [Cierres](simulacion/tanda-5/cierres-48-54.txt) y [salidas](simulacion/tanda-5/LEEME.md).
- **Comisiones de Hernán y Martín:** salieron de Wings; **no las recalculé a mano**. Se comparan en la revisión.
- **Volumen al cierre:** 447 movimientos de caja, 274 cobros, 2.018 marcas de asistencia, 472 renglones de Cashflow, 120 alumnos y 13 liquidaciones.
- **Lo que esta simulación no prueba:** corrió entrando por los controladores, no por el navegador. Doble clic real, teléfono, confirmaciones, sesiones y los textos de las pantallas (50.2, 50.3, 50.6, 50.9, 50.10, 52.7 y 53.0) los comprueba Codex.

## Lo que queda sin probar, y por qué

| Casos | Motivo |
|---|---|
| PCA-001 a PCA-008 | La primera carga necesita una base nueva: van en el ensayo del despliegue |
| CAJ-003, CAJ-042, SIS-011 | Necesitan un sistema nuevo o la base de producción: ídem |
| CAJ-011, ALU-022, COB-027, SIS-008 | Dos personas al mismo tiempo: los fuerza la prueba técnica |
| ALU-019, ASI-010, COB-026, MOV-017, CBZ-009 b | Pedidos adulterados: prueba técnica |
| ACC-006, ACC-018, ACC-019, ASI-015, CBZ-020, REP-009, SIS-004, SIS-010 | No hay cómo llegar en este relato sin romper el sitio: prueba técnica o quedan sin acreditar, y se dice cuáles |
| CAT-006, CLA-023, COB-010, COB-029, GRU-006, PEN-002, REP-014, USU-004, USU-007, USU-008, ALU-016, CAJ-040 | Figuran «no implementado»: su tarea es después de producción o el catálogo está viejo. Se revisan caso por caso |
