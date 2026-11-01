# Prueba por días: días 48 a 57 (jueves 26/11 al sábado 5/12 de 2026) — tanda 5, la última

Armado por Claude CAB el 01/11/2026, sobre una copia de la base del sitio al 25/11 con el código de `main` del 01/11 (T94 a T102 y T104 a T109). Carlos sumó los días 55, 56 y 57 y decidió: **con estos resultados se despliega en producción.**

**Autorización de Carlos:** va en el [prompt](PROMPT-CODEX-DIAS-48-57.md).

## El objetivo de esta semana

Wings se usa de verdad en pocos días. Esta semana es la última vez que alguien recorre el club entero antes que la gente del club, y tiene que contestar una pregunta: **¿un mes completo se puede operar, cobrar, pagar y entender solo con Wings, sin arreglar nada por atrás?** Por eso no alcanza con que cada pantalla responda: la plata tiene que cerrar al peso y cada persona tiene que poder hacer su trabajo.

| Objetivo | Cómo se cumple | Dónde |
|---|---|---|
| **Pasar por el cambio de mes con todas las salidas del generador** | La generación del 1/12 corre sola, con un alumno preparado para cada camino. Después corre otra vez y no tiene que hacer nada | 53.0 y 55.0 |
| **Probar por fin la Revisión** | Las tandas anteriores nunca tuvieron a quién mandar. Ahora van 8: se resuelven por la operativa y por el dueño, dos veces la misma, con nota corta, por pago | 53.0 y 54.1 a 54.5 |
| **Probar las reparaciones T94 a T109** | Cada una, por pantalla, en un momento en que le pasaría a alguien | Tabla de abajo |
| **Cerrar noviembre de verdad** | Liquidaciones cobradas y pagadas, también la de una profesora que ya se fue; sueldos de las operativas y canchas | 53.5 a 53.9, 54.7, 55.1 a 55.3 |
| **Abrir una actividad nueva de punta a punta** | Hockey: deporte, profesora, grupo, clases, usuario, altas, primera lista | 51.1, 52.3, 55.7, 55.8, 56.4, 57.3 |
| **Dejar la base lista para enero** | Aumento de precios, enero cobrado por adelantado, deudores vivos, clases hasta fin de mes | 56.7, 57.6 |
| **Probar lo que nunca se probó** | Unos 70 casos de la [planilla](https://claude.ai/artifact/8cgmP6kq4upcBKXw9KkJAt). Lo que no entra está al final, con el motivo | Todos los días |
| **Sostener el volumen** | Cobranza de diciembre: entre 8 y 15 cobros por día | Rutinas |

| Reparación | Qué se prueba | Paso |
|---|---|---|
| T94 | Sacar una frecuencia: con alumnos, sin usar y usada | 48.1 |
| T95 | Profesora sin tarifa | 51.1 |
| T96 | Efectivo sin Configurar; ajuste de saldo | 48.5 y 48.6 |
| T97 | Anular una transferencia, de hoy y de un mes anterior | 48.7 y 51.7 |
| T98 | El texto al no poder eliminar un alumno | 55.5 |
| T99 | Dos filas iguales y avisos sin nombres internos | 48.2 y 52.6 |
| T100 | Adelanto con un cero de más | 49.1 |
| T101 | Gasto del dueño en el turno de otro | 49.5 |
| T102 | Inactivar un nivel y pasar un grupo a otro | 48.3 |
| T105 y T109 | Recibo liviano, recibo inexistente y filtros con basura | 49.7 |
| T106 | Tarjetas sin sumar transferencias | 48.9 |
| T107 | Cerrar con la pantalla vieja | 49.8 |
| T108 | Segunda ficha sin inscripción | 49.4 y 55.7 |

T103 y T104 se cerraron sin cambio de código: no hay nada que probar.

### Qué tiene que pasar para desplegar

Claude lo revisa con la entrega y le da a Carlos el resultado por punto.

1. **Plata al peso:** los cierres de los 10 días coinciden con este guion, medio por medio.
2. **Las reparaciones cumplen.** Cada una de la tabla, con lo que dice el guion.
3. **Ningún error del servidor** y ninguna pantalla en inglés en todo el recorrido.
4. **Defectos:** solo los anunciados abajo. Uno nuevo se anota, se sigue, y lo decide Carlos.
5. **Lo que no se pudo probar se dice por su nombre,** en lugar de darlo por bueno.

### Lo que el 1/12 tiene que dar

Comprobado corriendo la generación de las 06:00 sobre la simulación.

**Constancia:** 78 cuotas creadas, 8 a Revisión, 31 ya existían, 0 sin plan, 117 activos. **Cobranza a las 06:00: $3.118.910** (32 Al día, 79 En plazo, 6 Deudores). Cambia contra el ensayo de la tanda 4 (84, 7 y 23) porque en esta semana hay más adelantos y altas.

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
| Segunda ficha reciente, pagada, que vino | Mateo Rodríguez en Patín (49.4) | Cuota $30.000 |
| Ingreso el 1/12 | Renzo Acuña | Sigue la de $25.000, pendiente |
| Adelantados | Los de la tanda 4, más Mateo Castro, Catalina y Melina Castro, Franco Molina, Felipe Sosa, Delfina Giménez, Lorenzo Morales y Benjamín Fernández | No crea otra |
| Con el saldo perdonado | Clara Iglesias | Sigue **Condonada**: no deuda |
| No vino, pero ya pagó | Facundo Ríos | No va a Revisión |
| Baja antes del 1/12 | Lautaro Medina, Candela | Sin cuota |
| Vino y no pagó | Liam Pereyra | Cuota $35.000; Deudor, $73.500 |
| Cambió de grupo | Lara Quiroga | Cuota $43.000 |

**Deudores a las 06:00:** Alma $36.300, Liam $73.500, Pilar $33.000, Thiago (Patín) $12.000, Valentina $38.500 y Emma $30.410. El alumno sin plan (ALU-023) ya no se puede armar: lo impide T56.

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
| 55 | Paga sueldos y canchas, mira Reportes | Sandra | Nora, su primera lista, por teléfono | Alta mal escrita, validar sin tildes, escribir importes raros |
| 56 | Usuarios, mes pasado, aumento de enero | Pablo a la mañana, Rocío a la tarde | Lucía | Pantalla vieja al abrir, rectificar mal, salir sin guardar, sesión vencida, validar una clase futura |
| 57 | Paga el torneo, cierra Caja Torneo, retira | Juan Pablo | Mariela; Nora sin señal | Cobrar lo que ya se cobró, adelantar con el precio viejo |

## Punto de partida

- **El sitio tiene `main` desplegado, con sus migraciones:** `219f5d59`, 01/11. Hasta el día 47 tenía `6214fd4a`.
- Reloj en el **25/11**; **no volver atrás**.
- Deuda **$215.910**; 114 activos: 107 Al día, 2 En plazo (Thiago y Tobías), 5 Morosos. Disponible **$7.441.520,75**: Efectivo $1.772.100, Mercado Pago $3.835.270,75, Galicia $1.776.150, Nación $58.000. Rocío dejó $10.000 de cambio.
- **No vienen en toda la tanda:** Pilar, Valentina, Emma Villalba, Valentino, Joaquina, Bruno y la ficha de Patín de Thiago. **Alma no viene.** **Liam viene y no paga.** **Tobías viene una vez, a probar, el 30/11.**

## Cómo se trabaja

Igual que las tandas anteriores: reloj del día una vez, rutina de la mañana con `--simular` y después real, las tareas **por pantalla** y en orden, rutina de la noche. Lo que no coincide se anota y se sigue; **no se repara nada**. Lo marcado **Solo prueba técnica** se fuerza en la revisión de Claude.

**Teléfono:** en cada paso marcado teléfono, usar `Emulation.setDeviceMetricsOverride` con ancho 375 y `mobile: true`, como `scripts/medir-ancho-movil.mjs:98`, y dejar en la evidencia `window.innerWidth` y `document.documentElement.scrollWidth`. Si `innerWidth` no da 375, el paso queda **no probado**: en la tanda 4 la pantalla siguió en 1905.

**Defectos que este guion espera:** están retratados a propósito. T110 a T112: Carlos los dejó para después de producción (01/11). Se ejecutan igual y se anotan; no frenan.

| Tarea | Paso | Qué pasa |
|---|---|---|
| **T110** | 48.4 | Guardar un grupo con la pantalla vieja se lleva puesta la frecuencia que agregó la otra pestaña, sin aviso. GRU-005 pide rechazarlo |
| **T111** | 53.4 | Mora tiene una ficha por deporte: queda presente en dos clases a las 16:00. ASI-008 pide rechazarlo, y el control mira la ficha, no la persona |
| **T112** | 53.4 | Un presente no saca a Tobías de Revisión. CBZ-016 a pide que salga. Ya anotado el 13/10 en el catálogo, sin tarea |
| **T113** | 56.8 | Wings ofrece Validar en una clase que todavía no ocurrió, y después no ofrece deshacerlo. Esa clase se paga en la próxima liquidación. Sin decisión de Carlos todavía |
| **T114** | 55.4 | Reportes → Sueldos toma a Mariela a $342.000 y se le pagó $361.000; a Hernán, $561.000 y se le pagó $561.100. Dice «Estimado» y ya está pagado. Sin decisión de Carlos todavía |

## Día 48: jueves 26/11, el dueño ordena el club para 2027

Es un día tranquilo y el dueño aprovecha: saca frecuencias que no se venden, prueba armar un grupo de los más chicos y le cambia el nombre al nivel. Después mira la plata: faltan $1.200 en la caja fuerte, se equivoca de cuenta en una transferencia y deposita el efectivo de la semana. Sandra carga a Ciro, que viene desde agosto y nadie había cargado.

Reloj **09:00**. **Rutina** (`dia-48-manana.json`): Sandra abre con $10.000; cinco familias de Fútbol Avanzadas pagan el torneo a $3.000 (cuatro en efectivo, una por MP); remera $18.000; limpieza $3.200, librería $1.500, insumos $2.600, botiquín $1.700, cerradura $4.800; listas de Patín Avanzadas (faltan Valentina y Zoe Pereyra), Federadas (falta Olivia Rossi) y Adultos (faltan Joaquina y Bruno).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 48.1 | Dueño | **T94.** En la ficha de los grupos: elimina Fútbol Infantiles 1 por semana (tiene a Renzo); Infantiles 2 por semana (nunca usada); Federadas 1 por semana (la usó alguien antes). Vuelve a agregar Federadas 1 por semana a **$42.000**. | «1x por semana tiene 1 alumno: cambiales el plan antes.»; «Plan eliminado.»; «Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.»; «Plan agregado.» sobre la misma fila, sin duplicar (GRU-004). |
| 48.2 | Dueño | **T99.** Grupos → Nuevo: Patín, Infantiles, **dos filas de 1 por semana**. Después una sola, a $22.000. Elimina esa única frecuencia. | «Hay dos filas con la misma frecuencia. Dejá una sola.», sin página de error; «Grupo creado correctamente.»; «No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.» (GRU-002, GRU-004). |
| 48.3 | Dueño | **T102.** Inactiva el nivel Infantiles. Crea el nivel **Mini**. En Editar, pasa Patín Infantiles a Principiantes; después a Mini. Inactiva Mini. Desactiva el grupo e inactiva Mini. Activa el grupo. | «No se puede inactivar el nivel porque tiene 2 grupos activos. Pasalos a otro nivel o desactivalos.»; «Nivel creado correctamente.»; «Ya existe un grupo con ese deporte y nivel.»; «Grupo actualizado correctamente.»; «…tiene 1 grupo activo. Pasalo a otro nivel o desactivalo.»; «Nivel inactivado correctamente.»; «No se puede activar el grupo porque el nivel «Mini» está inactivo. Activá el nivel o pasá el grupo a otro.» (CAT-011, CAT-003). |
| 48.4 | Dueño | **Pantalla vieja.** Abre Editar de Patín Adultos en dos pestañas. En una agrega 3 por semana a $52.000. En la otra cambia los precios a $33.000 y $43.000 y guarda. | **DEFECTO (T110):** «Grupo actualizado correctamente.» Los precios cambian y **el 3 por semana desaparece**, sin aviso. Anotarlo y seguir (GRU-005). |
| 48.5 | Dueño | **T96.** Abre Caja, busca Configurar. Escribe `/caja/configuracion`. Tipos de Caja → Efectivo. | No hay Configurar; la dirección vuelve a Caja. Nombre y saldo inicial ($250.000) de solo lectura (CAJ-002). **Solo prueba técnica:** el pedido directo. |
| 48.6 | Dueño | Cashflow → Nuevo: **Ajuste de saldo: salida, $1.200** de Efectivo, «Arqueo de la caja fuerte: faltan $1.200». Sandra busca ese renglón al cargar un gasto. | «Movimiento directo registrado en cashflow.» Disponible baja $1.200; **el resultado de Reportes no cambia** (CAJ-002 c). En Cashflow sí se ve en egresos. A Sandra no se le ofrece. |
| 48.7 | Dueño | **T97.** Transferencia: Mercado Pago a Galicia, **$400.000**, «Era a Nación, no a Galicia». Anular desde la fila de entrada, sin motivo; con «Cuenta de destino equivocada». Otra pestaña anula desde la fila de salida. | «Transferencia registrada…»; «Indicá el motivo de la anulación.»; «…anulada. Los originales y los dos asientos en contra se conservan.»; «La transferencia ya está anulada.» Las cuatro filas solo con Ver; MP y Galicia como estaban (CSH-020, CSH-021, CSH-023). |
| 48.8 | Dueño | Transferencia real: Efectivo a Galicia, **$800.000**, «Depósito del efectivo de la semana». | Efectivo baja y Galicia sube $800.000 (CSH-018). |
| 48.9 | Dueño | **T106.** Cashflow, semana, sin filtro. Con Galicia. Historial del 23 al 26/11. | Sin filtro: ingresos **$991.600**, egresos **$58.400**; no suman las transferencias. Con Galicia: ingresos **$946.000**, con el depósito. Historial: los mismos $991.600 y $58.400. |
| 48.10 | Sandra | **Ciro Paz**: nacido el 11/4/2015, DNI 49111222, tutora Laura Paz (11-4455-6600), Fútbol Principiantes, 1 por semana, ingreso **10/8/2026**. Sin elegir; eligiendo generar la cuota. Paga todo en efectivo. | «Elegí si se genera la cuota de este mes antes de guardar.»; «Alumno creado correctamente.»: noviembre **$28.000** e inscripción $8.000; **Deudor, $36.000**. Cobra $36.000; Al día (ALU-006). |
| 48.11 | Hernán | Por pantalla: Fútbol Avanzadas (falta Simón Silva). | «Asistencias guardadas.» |

**Noche** (`dia-48-noche.json`): Sandra cierra con el esperado, **$62.200**, y deja $10.000; el dueño valida.

**Cierre del día 48:** deuda **$215.910**; 115 activos: 108 Al día, 2 En plazo, 5 Morosos. Ingresos **$4.378.900**; egresos $2.847.680; resultado $1.531.220; disponible **$7.495.520,75**. Efectivo $1.023.100; MP $3.838.270,75; Galicia $2.576.150; Nación $58.000.

## Día 49: viernes 27/11, otra vez el cero de más

Rocío repite el error de la semana pasada, y esta vez Wings lo deja bien. El dueño limpia lo que quedó mal de antes (Clara y Thiago) y Mateo, que juega al fútbol, empieza patín.

Reloj **09:00**. **Rutina** (`dia-49-manana.json`): Rocío abre con $10.000; dos familias de Adultos pagan el torneo a $3.000; remeras $18.000 en efectivo y $18.000 por MP; detergente $2.900, sobres $1.100, conos $3.400, vendas $2.200; lista de Intermedias (faltan Alma, Emma Villalba y Rocío Sosa).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 49.1 | Rocío | **T100.** **Mateo Castro** paga diciembre en efectivo: **$280.000**. Se da cuenta: cancela con «Un cero de más: eran $28.000». Vuelve a Cobrar y escribe **$28.000**. | El esperado sube a $304.400 y vuelve a $24.400. **Diciembre desaparece** de la ficha. El nuevo cobro **no pide motivo** y deja diciembre Pagada, $28.000 (COB-019). |
| 49.2 | Dueño | **El saldo de Clara.** En su ficha, Condonar diciembre: con «Error»; con «Un cero de más del 20/11: la cuota era de $30.000». Vuelve a intentarlo. | «El motivo de la condonación debe tener al menos 10 caracteres.»; «Deuda condonada correctamente.»; queda Condonada, con los $30.000 pagados. Ya no hay Condonar (CBZ-009). |
| 49.3 | Dueño | **La inscripción de Thiago.** En la ficha de Patín, Condonar la inscripción, «Ya es del club: no paga otra inscripción». | «Inscripción condonada.» De **$20.000 a $12.000**; deuda total **$207.910**. |
| 49.4 | Rocío | **T108.** Abre el alta con el DNI de **Mateo Rodríguez** (vino de la primera carga, juega en Fútbol Avanzadas). Segunda ficha: **Patín Principiantes**, 1 por semana, ingreso 27/11. Paga por MP. | El aviso dice «Esta persona ya es del club: no paga inscripción.» Noviembre **$12.000** (40%), sin inscripción. Deuda total $219.910 y, al pagar, $207.910 (ALU-005). |
| 49.5 | Dueño | **T101.** En la tarjeta del turno de Rocío, Agregar: Insumos **$2.500**, «Lamparita del vestuario: la pagó la caja». Rocío mira su caja. | «Movimiento registrado.» **Figura a nombre de Admin Prueba.** Esperado **$49.900** (CAJ-041). |
| 49.6 | Rocío | **Catalina Castro** paga diciembre, $45.000 por MP. | Entra; ingresos de noviembre no suben. |
| 49.7 | Rocío | **T105 y T109.** Abre el recibo de Mateo Castro. Escribe `/recibos/cuota/9999999`. Escribe `/alumnos?estado=basura&page=xx&deporte_id=abc`. | PDF de unos **210 KB**. «Página no disponible». El listado de alumnos, sin error. |
| 49.8 | Rocío | **T107.** Abre el cierre: **$49.900**. En otra pestaña, **Melina Castro** paga diciembre, **$45.000 en efectivo**. Vuelve a la pantalla vieja y cierra con $49.900. Después con **$94.900**, dejando $10.000. Doble clic. | «Cambió el efectivo esperado: ahora es $94,900.00. Entró o se corrigió un movimiento desde que abriste esta pantalla. Revisá lo que contaste y tocá Cerrar de nuevo.»; sigue abierta. «Caja cerrada.», diferencia $0. Segundo clic: «La caja no está en estado editable.» (CAJ-023, CAJ-024). |

**Noche** (`dia-49-noche.json`): el dueño valida.

**Cierre del día 49:** deuda **$207.910**; 116 activos: 109, 2, 5. Ingresos **$4.396.900**; egresos $2.859.780; resultado $1.537.120; disponible **$7.655.420,75**. Efectivo $1.108.000; MP $3.913.270,75; Galicia $2.576.150.

## Día 50: sábado 28/11, sesiones y listas

Sábado de dos personas en el mostrador y en las listas al mismo tiempo. El dueño revisa los usuarios: desactiva a Hernán con la sesión abierta y lo reactiva.

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
| 50.6 | Dueño | **ACC-005, USU-007 y USU-010.** Usuarios: desactiva a Pablo. Desactiva a Hernán, con su sesión abierta en otro navegador y la lista a la vista. Hernán toca Clases y guarda la lista. El dueño lo reactiva. Hernán entra con su clave. | Pablo: «Este usuario tiene una caja abierta. Primero hay que cerrar la caja.» Hernán va al ingreso, «Tu cuenta fue desactivada.»; no se guarda nada. Reactivado, entra; sus 15 marcas siguen. **El catálogo está viejo:** ACC-005 c cuenta que una operativa con caja abierta se puede desactivar; anotarlo. |
| 50.7 | Pablo | Cobra diciembre: **Franco Molina** $38.000 en efectivo; **Felipe Sosa** $38.000 por MP. | Esperado **$61.500**. |
| 50.8 | Pablo | Abre la clase de Adultos del **3/12**: cancela, «Mariela avisó que ese jueves no puede». Busca «Cancelar serie». | «Clase cancelada.» Una sola; no le ofrece la serie (CLA-025). **Solo prueba técnica:** forzarla. |
| 50.9 | Pablo | **ACC-008 y ACC-017.** Entra también desde otro navegador tildando Recordarme: cierra ese navegador y lo abre. Después Salir en uno y Atrás. | Sigue adentro en el segundo. Salir no cierra el otro; Atrás no ejecuta nada. |
| 50.10 | Pablo y Lucía | **ACC-014 y ACC-021.** Escriben `/cajas/1/validar`, `/deudas/1/condonar`, `/alumnos/inscripcion-preview`, `/usuarios/check-email`. | La página en castellano que explica que esa dirección no se abre a mano. Lucía: sin datos de alumnos ni de plata. |

**Noche** (`dia-50-noche.json`): Pablo cierra con el esperado y deja $10.000; el dueño valida.

**Cierre del día 50:** deuda **$207.910**; 116 activos. Ingresos **$4.399.900**; egresos $2.867.280; resultado $1.532.620; disponible **$7.744.920,75**. Efectivo $1.159.500; MP $3.951.270,75; Galicia $2.576.150.

## Día 51: domingo 29/11, el dueño desde su casa

El club suma **Hockey sobre patines** en diciembre, con Nora Vidal: deporte, profesora, grupo y clases se cargan hoy. También prepara el torneo de fin de año del 5/12: abre una caja propia con $50.000 de fondo y contrata el sonido. Y le da usuario a su socia.

Reloj **20:00**. Sin actividad en el club.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 51.1 | Dueño | **T95.** Deportes → Nuevo: **Hockey**, por hora; « hockey ». Profesores → Nuevo: **Nora Vidal**, Hockey, DNI 30111222, nacida el 10/3/1985, Av. Rivadavia 1200, CABA, 11-6655-4433, **sin valor hora**; después a **$14.000**. | «Deporte creado correctamente.»; «Ya existe un deporte con ese nombre.»; «Cargá el valor por hora.»; «Profesor creado correctamente.» (CAT-001, PRO-002). |
| 51.1b | Dueño | Grupos → Nuevo: Hockey, Principiantes, 1 por semana **$30.000** y 2 por semana **$42.000**. Clases → Nueva, serie del **3 al 19/12**: jueves 16:00 a 17:00 y sábados 12:00 a 13:00; primero **sin profesora**, después con Nora. | «Grupo creado correctamente.» Sin profesora no crea nada (CLA-023). Con Nora: **6 clases**: 3, 5, 10, 12, 17 y 19/12 (GRU-001). |
| 51.2 | Dueño | Tipos de Caja → Nuevo: Caja Torneo con abreviatura `TORNEO`; saldo `-1`; nombre «mercado pago»; después bien: **Caja Torneo**, `TOR`, **$50.000**. | «La abreviatura no puede tener más de 5 caracteres.»; «El saldo inicial no puede ser negativo…»; «Ya existe un tipo de caja con ese nombre.»; «Tipo de caja creado correctamente.» Disponible sube $50.000 (CAT-005). |
| 51.3 | Dueño | Reparaciones menores pasa a solo del dueño. | «Subrubro actualizado correctamente.» Sandra deja de verlo el 52 (CAT-008). |
| 51.4 | Dueño | Busca editar o desactivar Cuota Mensual. | No hay; forzando: «No se puede editar: subrubro reservado del sistema.» (CAT-009). |
| 51.5 | Dueño | Interruptor de **Vanina Directora** en Profesores: la apaga y la prende. Interruptor de su propia cuenta. | «Profesora inactiva…»; «Profesora activa. Su usuario conserva el estado anterior…»; su cuenta dice «Tu cuenta» (SIS-014). |
| 51.6 | Dueño | Usuarios → Nuevo: **Vanina Socia**, vanina.socia@example.com, clave `Socia-del-club-2026!`, Administrador. Carga **Eventos $15.000** por Galicia, «Sonido del torneo». Entra Vanina y lo corrige a **$12.000**, «El presupuesto final fue $12.000». | «Usuario creado correctamente.»; «Movimiento actualizado. El historial se conserva.» Cargó uno, corrigió la otra, y los dos a la vista (CSH-011). |
| 51.7 | Dueño | **T97.** Transferencia: Galicia a Nación, $50.000, **20/10**, «Quedó sin cargar en octubre»: sin confirmar y confirmando. La anula: «Ya estaba cargada: me confundí». | Pide confirmar; «…registrada…»; «…anulada…». Octubre sigue **$3.417.850,75 y $341.350** (CSH-022). |
| 51.8 | Dueño | Cashflow: busca Editar y Eliminar en un movimiento de caja validada, en un cobro suyo, en una liquidación pagada y en un anulado. | No ofrece corregir (CSH-009). **Solo prueba técnica:** forzarlo. |

**Cierre del día 51:** deuda **$207.910**. Ingresos $4.399.900; egresos **$2.879.280**; resultado $1.520.620; disponible **$7.782.920,75**. Galicia $2.564.150; Caja Torneo $50.000.

## Día 52: lunes 30/11, el último del mes

Último día para cobrar noviembre y para anotarse al torneo. Entra Nina mientras el dueño toca la inscripción. Hernán y Lucía se olvidan de cargar sus listas: las cargan mañana, y eso se ve al liquidar.

Reloj **09:00**. **Rutina** (`dia-52-manana.json`): Sandra abre con $10.000; remera $18.000; lavandina $3.600; **fotocopias $1.250,50**; cinta $2.800; alcohol $1.400; lista de Patín Principiantes (faltan Pilar, Valentino y Thiago). **Hernán y Lucía se olvidan:** Fútbol Principiantes de las 16 e Intermedias de las 17 quedan sin lista.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 52.1 | Dueño | Configuración: inscripción a **$0**. | «El importe de la inscripción debe ser mayor que cero.» ALU-010 no se alcanza por pantalla; anotarlo. |
| 52.2 | Sandra y dueño | Sandra abre el alta de **Nina Arias**: nacida el 20/2/2018, DNI 57222333, tutor Pablo Arias (11-7788-9900), Patín Principiantes, 1 por semana, ingreso 30/11, con el aviso a la vista. El dueño sube la inscripción a **$9.000**. Sandra guarda. El dueño la vuelve a **$8.000**. Sandra revisa el aviso y guarda. | «El importe de inscripción cambió. Revise el nuevo importe antes de guardar.», sin crear nada. «Alumno creado correctamente.»: noviembre **$12.000**, inscripción $8.000 (ALU-009). |
| 52.3 | Sandra y dueño | Nina paga $20.000 **en efectivo**. Las familias **Torres** y **Suarez** pagan el torneo, $3.000 cada una, **por Caja Torneo** (Torneos → Inscripciones). El dueño abre Editar de Caja Torneo. | «Pago registrado…» El medio nuevo se ofrece; el saldo inicial ya no se puede cambiar (CAT-005 a, c). **Solo prueba técnica:** forzarlo. |
| 52.3b | Dueño | Usuarios → Nuevo: **Nora Vidal**, nora.vidal@example.com, clave `Hockey-del-club-2026!`, Profesor, vinculada a Nora. | «Usuario creado correctamente.» (USU-002). |
| 52.4 | Sandra y dueño | Sandra cobra diciembre: **Delfina Giménez** $35.000 en efectivo; **Lorenzo Morales** $38.000 por MP. El dueño: **Benjamín Fernández**, $52.000 por Galicia. | Entran; noviembre no sube. |
| 52.5 | Dueño | Cashflow → Nuevo: **Luz $9.800** por Galicia, «Edesur, noviembre». | Galicia **$2.606.350**. |
| 52.6 | Sandra | **T99.** Renzo: cambia el ingreso al 5/12, sin motivo. Abre Nuevo gasto. | «El campo motivo de la fecha de ingreso es obligatorio.», sin nombres internos. Reparaciones menores no se ofrece (CAT-008 a). |
| 52.7 | Dueño | **REP-010, SIS-002.** Compara Reportes, Inicio, Cashflow, Historial y la caja. Busca los $1.250,50 en Caja, Historial, Movimientos y Cashflow. | Cuadran. **Ingresos $26.000** de hoy: Nina y las dos familias. Egresos **$19.850,50**. Los centavos se ven. **El catálogo está viejo:** dice punto de miles y coma decimal, y desde T30 es `$1,250.50`; anotarlo. |

**Noche** (`dia-52-noche.json`): Sandra cierra con el esperado, **$73.949,50**, y deja $10.000. **El dueño no valida hoy.**

**Cierre del día 52:** deuda **$207.910**; 117 activos: 110, 2, 5; **1 caja por validar, 2 clases sin lista**. Ingresos **$4.425.900**; egresos $2.898.130,50; resultado $1.527.769,50; disponible **$7.933.070,25**. Efectivo $1.159.500; MP $3.951.270,75; Galicia $2.606.350; Caja Torneo $50.000 más los $6.000 de la caja sin validar.

## Día 53: martes 1/12, cambia el mes

**El día más importante de la prueba.** A las 06:00 Wings genera diciembre sin que nadie toque nada, y a las 08:30 cada uno mira lo que le toca. Es el día en que el club ve por primera vez a quién mandó a Revisión. Después el dueño cierra noviembre con todos los errores que le pueden pasar.

Reloj **05:55**; **esperar las 06:00 y las 06:15**: corren solas la generación y los recargos. A las **08:30** sigue el día.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 53.0 | Dueño | Cobranza, Revisión, Reportes de Alumnos, Inicio. Sandra, Inicio. Lucía escribe `/revision-cobranza`. | La tabla de arriba. 8 pendientes, igual en todas. Lucía: Sin permiso. Deudores, los seis de arriba (CBZ-003, CBZ-013, CBZ-017). **Clara no aparece.** Las dos fichas de Thiago, cada una por su lado. |
| 53.1 | Dueño | Valida la caja del 30/11. Mira noviembre y diciembre. | «Caja validada y reflejada en cashflow.» Noviembre sigue **$4.425.900 y $2.898.130,50**; diciembre **$1.214.000**, sin cambios (REP-013). |
| 53.2 | Dueño | Cancela los feriados: 8/12, «Feriado: Inmaculada Concepción», y 25/12, «Feriado: Navidad». | **4 clases** el 8/12 y **1** el 25/12. |
| 53.3 | Dueño | Reactiva a **Lautaro Medina**. | «Alumno activado correctamente. Se generó la cuota de diciembre: $28,000.00.» En plazo, $28.000; deuda **$3.146.910** (ALU-018). |

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

**Cierre del día 53:** deuda **$2.713.910**; 118 activos: 44 Al día, 68 En plazo, 6 Deudores. Diciembre: ingresos **$1.647.000**; egresos $7.200; resultado $1.639.800; disponible **$8.358.870,25**. Efectivo $1.485.249,50; MP $4.120.270,75; Galicia $2.639.350; Caja Torneo $56.000.

## Día 54: miércoles 2/12, se resuelve la Revisión

Rocío y el dueño llaman y deciden, uno por uno, qué pasa con los 8. Es el trabajo que Wings le pide al club todos los meses, y nunca se recorrió. A la tarde el dueño paga las liquidaciones.

Reloj **09:00**. **Rutina** (`dia-54-manana.json`): Rocío abre con $10.000; quince familias pagan diciembre (diez en efectivo $364.000, cuatro por MP $157.000, una por Galicia $43.000); rejilla $2.700, gasas $1.900, tinta $1.300; lista de Patín Principiantes (faltan Pilar, Valentino, Paulina Silva y Thiago).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 54.1 | Rocío y dueño | **CBZ-014.** **Pilar:** Continúa sin nota; «Sí.»; «La mamá confirma que vuelve en diciembre». **Valentina**, el dueño: Inactivo, «Se mudó: no vuelve». | «La nota es obligatoria.»; «La nota debe tener al menos 5 caracteres.»; «Alumno confirmado como activo. Se generó la deuda del período.»: Pilar Deudor, **$63.000**. «Alumno marcado como inactivo.»: sin cuota de diciembre; sale de Cobranza y sus $38.500 quedan en la ficha. Deuda **$2.141.410**. |
| 54.2 | Dueño y Rocío | **CBZ-015.** Joaquina, abierta en dos equipos. El dueño: Continúa, «Vuelve después del viaje». Rocío, con la pantalla vieja: Inactivo. | «Esta revisión ya fue resuelta.» Sigue activa, con una sola cuota de **$33.000**. |
| 54.3 | Rocío | **CBZ-016 b.** **Emma Villalba** paga noviembre, $30.410 en efectivo. Mira Revisión. Paga diciembre, **$33.000**. | Con noviembre sigue pendiente. Con diciembre sale sola: «Reactivado automáticamente». |
| 54.4 | Rocío | Ficha de Patín de **Thiago**: Inactivo, «Sigue solo en Fútbol». **Alma**, **Tobías** y **Ciro**: Continúa. | Thiago sigue activo en Fútbol, En plazo, $28.000. Revisión **0**. Deudores: Alma $69.300, Liam, Pilar, Tobías $47.200. |
| 54.5 | Dueño | **CBZ-021.** Revisión: Pendientes, Resueltos, Todos, período. Inicio y Reportes de Alumnos. | Quién, cuándo y la nota. Emma: «Reactivado automáticamente». Todos dicen 0. |
| 54.6 | Rocío | **COB-024, CBZ-004.** **Santino Flores** pasa a 2 por semana y paga. **Zoe Pereyra**, que vino ayer, pasa a 1 por semana y paga por MP. **Liam** paga diciembre, $35.000: sin motivo y con «Paga diciembre: noviembre lo trae la semana que viene». | Santino **$35.000**, ya al plan nuevo. Zoe **$45.000**, al plan anterior; rige desde enero. «Quedan 1 meses anteriores impagos por $38,500.00 (noviembre 2026)…»; con motivo entra: Liam sigue Deudor, **$38.500**. |
| 54.7 | Dueño | Paga: **Lucía $264.000** y **Mariela $361.000** por Galicia; **Hernán $561.100** y **Martín $195.000** por MP. Doble clic en la de Lucía. Busca Cancelar, Eliminar, Recalcular y Ajustar en una pagada. | «Liquidación pagada. Movimiento registrado en cashflow.»; el segundo envío no duplica. Una pagada no ofrece nada (LIQ-013). **Solo prueba técnica:** forzarlo. Egresos de diciembre suben **$1.381.100**; noviembre no cambia. |
| 54.8 | Hernán | **Por teléfono (19:00):** Fútbol Principiantes, falta Facundo. Con motivos de Liam, Thiago y Tobías. | «Asistencias guardadas.», 27 alumnos, sin desplazarse de costado (ASI-014, SIS-003). |

**Noche** (`dia-54-noche.json`): Rocío cierra con el esperado, **$501.510**, y deja $10.000; el dueño valida.

**Cierre del día 54:** deuda **$2.113.000**; 116 activos: 60 Al día, 52 En plazo, 4 Deudores; Revisión 0; sin lista 0. Diciembre: ingresos **$2.389.410**; egresos $1.394.200; resultado $995.210; disponible **$7.714.280,25**. Efectivo $1.976.759,50; MP $3.566.170,75; Galicia $2.057.350; Nación $58.000; Caja Torneo $56.000. Octubre sigue $3.417.850,75 y $341.350; noviembre, $4.425.900 y $2.898.130,50.

## Día 55: jueves 3/12, se termina de pagar noviembre

Como el 3/11, se pagan los sueldos y las canchas. Falta Verónica, que se fue en noviembre y dio dos clases: también cobra. Con todo pago, el dueño mira cuánto le costó el mes. A la tarde arranca Hockey: se anotan Benicio y Mia, y Nora toma su primera lista desde la cancha.

Reloj **05:55**; **esperar las 06:00 y las 06:15**. A las **08:30** sigue el día.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 55.0 | Dueño | **CBZ-012.** El generador volvió a correr solo, ahora con la Revisión resuelta. Mira Cobranza y Revisión. | No hizo nada: 116 cuotas de diciembre, la constancia sigue 78 y 8, Revisión **0**, Pilar con una sola cuota. Cifras como las del cierre del 54. |

**Rutina** (`dia-55-manana.json`, 09:00): Sandra abre con $10.000; trece familias pagan diciembre (nueve en efectivo $327.000, tres por MP $116.000, una por Galicia $50.000); remera $18.000; limpiavidrios $2.900, gasas $1.500, biromes $900; listas de Avanzadas (falta Lucía Ramírez), Federadas (falta Olivia Rossi) y Fútbol Avanzadas (falta Simón Silva).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 55.1 | Dueño | **T46.** Nueva liquidación, noviembre: busca a **Verónica Salinas**, inactiva. Genera, cierra y paga por Efectivo. | Se ofrece: «Inactiva, con clases dictadas pendientes», 2 clases. **$30.000**, 2 detalles. «Liquidación pagada. Movimiento registrado en cashflow.» |
| 55.2 | Dueño | **CSH-015.** Cashflow → Nuevo, Sueldos, «Sueldo de noviembre»: **Sandra $320.000** y **Pablo $320.000** en efectivo, **Rocío $280.000** por MP. | «Movimiento directo registrado en cashflow.» tres veces. No ofrece a las profesoras. |
| 55.3 | Dueño | Cashflow → Nuevo, Alquileres, «Alquiler de cancha, noviembre»: **San Carlos $180.000** por MP, **Centenera $125.000** en efectivo. | Con los gastos de la mañana y Verónica, egresos de diciembre **$2.654.500**. Noviembre no cambia. |
| 55.4 | Dueño | **REP-006, B12.** Reportes → Sueldos de noviembre; Alumnos de noviembre; Ingresos y egresos. Compara Sueldos con lo que pagó. | Costo **$1.392.000**; ingreso $3.986.700; 891 asistencias; 34,9%. Operativas **$920.000**. **DEFECTO (T114):** Mariela figura $342.000, se pagó $361.000; Hernán $561.000, se pagó $561.100. Anotarlo y seguir. |
| 55.5 | Dueño | **T98.** Abre la ficha de **Liam** y busca Eliminar. | «No se puede eliminar el alumno: Tiene pagos registrados. Puede pasarlo a Inactivo.», sin decir «dar de baja» (ALU-024). |
| 55.6 | Dueño | **CAT-006, GRU-006.** Desactiva **Efectivo**; **Banco Nacion ahorro**; el grupo **Patín Adultos**. | «No se puede desactivar porque es el medio de pago del cajón de mostrador.» El ahorro se apaga y deja de ofrecerse. «No se puede desactivar el grupo porque tiene 4 alumnos activos y 7 clases futuras.» **El catálogo está viejo:** CAT-006 b dice «primero hay que elegir otro»; desde T96 no hay otro; anotarlo. |
| 55.7 | Sandra y dueño | Sandra anota a **Benicio** mal: apellido **Lunna**, DNI 58123456, nacido el 2/6/2014, tutora Marta Luna (11-6611-2233), Hockey, Principiantes, 1 por semana, ingreso 3/12. Busca Eliminar. El dueño lo elimina. Sandra lo carga bien, **Luna**, y paga todo en efectivo. | «Alumno creado correctamente.» Sandra no tiene Eliminar. «Alumno eliminado correctamente.» Sin cobros no queda rastro que moleste. Diciembre $30.000 e inscripción $8.000: **$38.000** (ALU-024). |
| 55.7b | Sandra | **T108.** Segunda ficha de **Mia Rodríguez**, de Patín Intermedias: Hockey, Principiantes, 1 por semana, ingreso 3/12. Paga por MP. | «Esta persona ya es del club: no paga inscripción.» Diciembre **$30.000**, sin inscripción. |
| 55.8 | Nora | **Por teléfono, 16:30:** su primera lista: Benicio y Mia presentes. Escribe `/alumnos`. | «Asistencias guardadas.» En Clases solo lo suyo y lo del día. «Sin permiso» (USU-002, ASI-014). |
| 55.9 | Sandra | **SIS-001.** En Nuevo gasto, en Cobrar y en la apertura, escribe en el importe `1250.5`, `1250,50`, `1250.505` y `48.000`. **No guardar.** | En todas igual: la coma pasa a punto; el tercer decimal se corta, en rojo, 4 segundos. |

**Noche** (`dia-55-noche.json`): Sandra cierra con el esperado, **$387.700**, y deja $10.000. **El dueño valida por pantalla:**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 55.10 | Dueño | **CAJ-040.** Abre el detalle. Valida sin tildar. Tilda tres, sale y vuelve. Valida. Tilda los 20 y valida. | «Faltan 20 movimientos por verificar antes de validar la caja.»; los tres siguen tildados; «Faltan 17…»; «Caja validada y reflejada en cashflow.» |

**Cierre del día 55:** deuda **$1.620.000**; 118 activos: 75 Al día, 39 En plazo, 4 Deudores. Diciembre: ingresos **$2.950.410**; egresos $2.654.500; resultado $295.910; disponible **$7.032.980,25**. Efectivo $1.559.459,50; MP $3.252.170,75; Galicia $2.107.350; Nación $58.000; Caja Torneo $56.000.

## Día 56: viernes 4/12, dos turnos y los papeles del dueño

Un viernes con cambio de turno: Rocío entra temprano y deja la apertura abierta mientras Pablo atiende la mañana. El dueño corrige el nombre de Pablo, le da a Gonzalo un lugar en el mostrador para el verano y anuncia el aumento de enero. La familia Silva avisa que septiembre nunca se le cobró.

Reloj **09:00**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 56.0 | Rocío | Entra, abre Caja → Abrir y **deja esa pantalla como está**. | Propone **$10.000**. |

**Rutina** (`dia-56-manana.json`, reloj **09:30**): Pablo abre con $10.000; seis familias pagan diciembre (cuatro en efectivo $156.000, dos por MP $95.000); silbatos $2.200. **Mediodía** (`dia-56-mediodia.json`, reloj **13:00**): Pablo cierra con **$163.800** y **deja $12.000**; el dueño valida.

Reloj **14:00**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 56.1 | Rocío | **CAJ-011.** Toca Abrir en la pantalla de la mañana. Recarga y abre. | «Cambió el último cierre. Volvé a abrir la pantalla y confirmá el efectivo.»; no abre. Ahora propone **$12.000**; «Caja abierta con el efectivo declarado.» |
| 56.2 | Pablo y Rocío | **CAJ-014.** Pablo busca Rectificar en la caja de Rocío y en la suya validada. Rocío rectifica la suya con motivo «Ya». | Pablo no lo tiene. «El campo motivo de la rectificación debe tener al menos 3 caracteres.» Ninguna caja cambia. **Solo prueba técnica:** forzar los dos de Pablo. |
| 56.3 | Dueño | **USU-004, USU-008.** A Rocío, con la caja abierta, le cambia el rol a Profesor. A Pablo: **Juan Pablo Ledesma**. A **Gonzalo Ibarra**: rol Operativo. Mira Cashflow y una caja vieja de Pablo. | «Este usuario tiene una caja abierta. Primero hay que cerrar la caja.» «Usuario actualizado correctamente.» dos veces. Rubro **Op-Juan Pablo Ledesma**; sus cajas, con el nombre nuevo. **Los recibos ya emitidos no cambian.** Gonzalo con menú de operativa; sus 4 clases futuras siguen a su nombre y **Wings no lo avisa**: anotarlo. |
| 56.4 | Rocío | **Ámbar Funes**, hija de Graciela de Adultos: DNI 58234567, nacida el 21/9/2012, tutora Graciela Funes (11-4433-2211), Hockey, 1 por semana, ingreso 4/12. Paga en efectivo. | **$38.000**: diciembre $30.000 e inscripción $8.000. |
| 56.5 | Dueño y Rocío | **COB-029.** Rocío abre la ficha de **Paulina Silva** y busca «Meses sin cuota generada». El dueño: la ve, crea **Septiembre 2026**. Rocío cobra septiembre en efectivo. | Rocío no lo ve. «Se creó la cuota de septiembre 2026: $30,000.00.» Paulina pasa a Deudor, **$60.000**; al cobrar, En plazo, **$30.000**. En Reportes el cobro entra en **diciembre**: la parte atrasada es del mes en que se cobra (DU-19). **Solo prueba técnica:** Rocío forzándolo, y un mes anterior al ingreso. |
| 56.6 | Rocío | **Tobías**: la mamá paga todo por MP. | Era Deudor, **$47.200**. Queda Al día. |
| 56.7 | Dueño | **GRU-003.** Aumento de enero: Patín Principiantes a **$33.000** y **$48.000**. Mira la ficha de Martina Castro. | «Grupo actualizado correctamente.» Su diciembre, ya generado, sigue **$30.000**. Enero, para quien lo pague hoy, **$33.000**. |
| 56.8 | Dueño | **CLA-024.** Abre la clase de Hockey del **19/12** y toca Validar por error. Busca cómo deshacerlo. | **DEFECTO (T113):** «Clase validada para liquidación.» y el botón desaparece. Anotarlo y seguir: esa clase queda validada. |
| 56.9 | Rocío | **ALU-011.** Alumnos → Nuevo: escribe nombre y apellido y toca otra opción del menú. Cancela. Vuelve a tocarla y acepta. Abre el alta vacía y sale. | Pregunta antes de perder lo cargado; con Cancelar sigue todo; sin datos, sale sin preguntar. No se guarda nada. |
| 56.10 | Sandra | **ACC-006.** En otro navegador abre Nuevo gasto y escribe. Borra la cookie de sesión del sitio con las herramientas del navegador. Toca Guardar. | Va al ingreso con «Tu sesión expiró. Iniciá sesión nuevamente.» No se guarda nada. |

**Rutina** (`dia-56-tarde.json`, reloj **15:00**): Rocío: cinco familias pagan diciembre (cuatro en efectivo $126.000, una por MP $35.000); bolsas $2.400, sobres $800.

Reloj **17:00**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 56.11 | Lucía | **Alma vuelve.** Lista de Intermedias: vuelve **Alma**, falta Rocío Sosa. Sin motivo; con «Vuelve hoy: la mamá paga el sábado». | «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.»; «Asistencias guardadas.» |

**Noche** (`dia-56-noche.json`): Rocío cierra con el esperado, **$202.800**, y deja $10.000; el dueño valida.

**Cierre del día 56:** deuda **$1.160.800**; 119 activos: 88 Al día, 28 En plazo, 3 Deudores. Diciembre: ingresos **$3.477.610**; egresos $2.659.900; resultado $817.710; disponible **$7.554.780,25**. Efectivo $1.904.059,50; MP $3.429.370,75; Galicia $2.107.350; Nación $58.000; Caja Torneo $56.000.

## Día 57: sábado 5/12, el torneo de fin de año

Último día. Hernán se lleva al equipo al torneo y se cancela su clase. Renzo tiene su primera clase, un día en que Gonzalo ya no toma lista. Último momento para anotarse, por Caja Torneo. El dueño paga al organizador, cierra esa caja y retira. Dos familias adelantan enero, cada una con un precio distinto en la cabeza.

**Rutina** (`dia-57-manana.json`, reloj **09:00**): **Juan Pablo** abre con $10.000; ocho familias pagan diciembre (seis en efectivo $215.000, dos por MP $78.000); limpieza $3.200, cintas de medallas $1.800; listas de Federadas (Mariela; falta Lucía Ramos) y **Fútbol Infantiles** (Juan Pablo, porque Gonzalo ya es operativa; Renzo presente).

Reloj **13:30**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 57.1 | Dueño | Cancela **Fútbol Avanzadas** de las 11, «Torneo de fin de año: Hernán lleva al equipo». | «Clase cancelada.» No suma a Sin asistencia. |
| 57.2 | Juan Pablo | Familias **Herrera, Molina, Romero y Lopez**: torneo, $3.000 cada una, **por Caja Torneo**. | «Movimiento registrado.» cuatro veces. |
| 57.3 | Nora | **ASI-015. Por teléfono, sin señal:** clase de Hockey, 12:00. Benicio falta; Mia y Ámbar presentes. Corta la conexión desde las herramientas del navegador y toca Guardar. Recupera la conexión y toca Guardar. | Avisa el error de conexión y las marcas siguen en pantalla. Después, guardada una sola vez: 3 marcas, 2 presentes. |
| 57.4 | Juan Pablo | **Alma** paga todo en efectivo. | Era Deudor, **$69.300**. Queda Al día. |
| 57.5 | Juan Pablo y dueño | **COB-027, SIS-008.** Los dos abren el cobro de **Carla Morales**, que debe $15.000. Juan Pablo cobra en efectivo. El dueño, con la pantalla vieja, por Galicia. | «Pago registrado…»; «El importe entregado debe ser positivo y no superar la deuda seleccionada más la inscripción.» Un solo cobro: diciembre Pagada. |
| 57.6 | Juan Pablo | **COB-012.** Cobrar de **Valentina López**: elige **Enero 2027**, $48.000 por MP. **Julieta Morales** paga enero con lo que traía: **$30.000** en efectivo. | La lista de meses cruza el año. Enero de Valentina **$48.000**, Pagada. **Wings no avisa que Julieta paga menos que el precio nuevo:** enero queda Pagada por $30.000; anotarlo. Ingresos de diciembre **no suben**. |

**Noche** (`dia-57-noche.json`, 21:00): Juan Pablo cierra con el esperado, **$334.300**, y deja $10.000; el dueño valida.

Reloj **21:30**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 57.7 | Dueño | **CAT-006.** Caja Torneo tiene **$68.000**. Cashflow → Nuevo, Gastos de torneos, **Pago al organizador**, $40.000. Transferencia de los **$28.000** a Galicia, «Cierre de Caja Torneo». Desactiva Caja Torneo. Mira Cobrar y Cashflow. | «Movimiento directo registrado…»; «Transferencia… registrada…»; Caja Torneo en $0 e inactiva. **No se ofrece** al cobrar ni al cargar un gasto, y sus movimientos se siguen viendo. Sin error en la operación. |
| 57.8 | Dueño | **Retiro de dueños** $300.000 por Galicia, «Retiro de diciembre». | Disponible baja $300.000; **el resultado de Reportes no cambia**. En Cashflow sí se ve en egresos. |
| 57.8b | Sandra | **SIS-009.** Se equivoca de clave hasta que Wings la frena. Espera un minuto real y entra. | Límite de intentos con su aviso en castellano; se libera al minuto aunque el reloj esté detenido. |
| 57.9 | Dueño | **REP-001, CBZ-018.** El último cuadre: Inicio, Cobranza, Reportes y Cashflow. Octubre, noviembre y diciembre. Sandra y Rocío, su Inicio. | Dicen lo mismo. Deudores: **Liam $38.500** y **Pilar $63.000**. Revisión 0. |

**Cierre del día 57 y de la prueba:** deuda **$783.500**; 119 activos: 98 Al día, 19 En plazo, 2 Deudores; inactivos $80.000; Revisión 0; sin lista 0. Diciembre: ingresos **$3.866.910**; egresos $2.704.900; resultado **$1.162.010**; disponible **$7.677.080,25**. Efectivo $2.228.359,50; MP $3.555.370,75; Galicia $1.835.350; Nación $58.000; Caja Torneo $0. Octubre sigue $3.417.850,75 y $341.350; noviembre, $4.425.900 y $2.898.130,50.

**Lo que queda listo para enero:** 2 Deudores y 19 En plazo vivos, enero adelantado en dos fichas, Patín Principiantes con precio nuevo, clases hasta fin de diciembre, Hockey con Nora sin liquidar todavía y una clase validada por error.

## Cómo se sabe que da bien

- **Plata:** corrida en la copia del sitio con `main`, y cada día comparado a mano con sus operaciones, medio por medio: **coinciden al peso**, incluidos ingresos, egresos y deuda. Las cuentas se hicieron **después** de correr. [Cierres](simulacion/tanda-5/cierres-48-57.txt) y [salidas](simulacion/tanda-5/LEEME.md).
- **Comisiones de Hernán y Martín:** salieron de Wings; **no las recalculé a mano**. Se comparan en la revisión.
- **Volumen al cierre:** 507 movimientos de caja, 315 cobros, 2.087 marcas de asistencia, unos 570 renglones de Cashflow, 123 fichas de alumnos, 14 liquidaciones y 232 clases.
- **Lo que esta simulación no prueba:** corrió entrando por los controladores, no por el navegador. Doble clic real, teléfono, confirmaciones, sesiones, sin señal y los textos de las pantallas los comprueba Codex.

## Lo que queda sin probar, y por qué

| Casos | Motivo |
|---|---|
| PCA-001 a PCA-008 | **Ya probados fuera de esta prueba:** [verificación de Gemini](../PRU-02/VERIFICACION-P1.md), 13 de 13, y la carga real del sitio el 10/10 (T4). No se repiten: necesitan una base vacía |
| CBZ-005 | Deber solo la inscripción pasa únicamente con la primera carga: hoy el sitio no tiene a nadie así. Verificado en T31 |
| ALU-023 | T56 impide que un activo quede sin plan: no hay cómo llegar |
| CAJ-003, CAJ-042, SIS-011 | Necesitan un sistema nuevo o la base de producción: van con el despliegue |
| ALU-022 | T55 es para después de producción: hoy gana el último que guarda |
| ALU-019, ASI-010, COB-026, MOV-017, CBZ-009 b, CBZ-020, SIS-004 | Forzar o simular: los hace Claude en la prueba técnica, sobre una copia |
| ACC-018, ACC-019, REP-009, SIS-010 | No hay cómo llegar en este relato sin romper el sitio: prueba técnica o quedan sin acreditar, y se dice cuáles |
| COB-010, CSH-016, PEN-002, REP-014, ALU-016 | Su tarea es para después de producción (MF11, T60, POS-06, T72 y T53) |
