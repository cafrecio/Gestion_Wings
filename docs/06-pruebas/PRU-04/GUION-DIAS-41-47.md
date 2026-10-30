# Prueba por días: días 41 a 47 (jueves 19 al miércoles 25 de noviembre de 2026) — tanda 4 de 5

Armado por Claude CyE el 30/10/2026, sobre la base del sitio al 18/11 (después de la tanda 3 de Codex). Reemplaza la
simulación anterior de esta tanda, que Carlos rechazó con razón: no preparaba todas las salidas del 1/12 y sumaba
muy pocos datos.

**Autorización de Carlos (25/10, 28/10, 29/10 y 30/10/2026):** la misma de las tandas anteriores para todas las
operaciones de plata de este guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios). No hace falta
volver a preguntar.

## Para qué sirve esta semana

Noviembre ya está casi cobrado: esta semana no vale por la cobranza, sino por lo que deja lista. Tiene cinco trabajos:

| Trabajo | Cómo se cumple | Medida |
|---|---|---|
| **Probar lo que nunca se probó** | Cada día recorre casos sin probar de la [planilla](https://claude.ai/artifact/8cgmP6kq4upcBKXw9KkJAt) | **58 casos nuevos**; de 178 a 236 de 334 si cumplen todos |
| **Preparar el 1/12** | Una persona por cada camino del generador de cuotas, no solo «el que no vino» | Tabla de abajo, ensayada: 84 creadas, 7 a Revisión, 23 ya existían |
| **Sumar el volumen de todos los días** | Las familias pagan el torneo una por una, en vez de un total por grupo; más remeras y gastos chicos | **87 movimientos de caja**, 27 cobros, 227 marcas de asistencia, 90 renglones de Cashflow y 78 clases de diciembre. De 8 movimientos por turno a entre 13 y 29 |
| **Pasar por todos los perfiles** | Dueño, las tres operativas y las profesoras **por pantalla**, no solo por rutina | Tabla de abajo |
| **Equivocarse como una persona** | Un error humano por lo menos cada día con actividad | Tabla de abajo |

### Lo que el 1/12 tiene que dar

Calculado con las reglas antes de simular y confirmado corriendo la generación sobre la base del día 47
([`ensayo-1-12.php`](simulacion/tanda-4/ensayo-1-12.php)). La tanda 5 lo repite después de las clases del 26 al 30/11.

| Camino del generador | Quién | Resultado |
|---|---|---|
| No vino en todo el mes | Pilar Ramos, Valentina Suárez | Revisión |
| Su único presente fue en una clase después cancelada (CLA-012 c) | **Alma Navarro** (45.9) | Revisión |
| Alta reciente que no pagó | Emma Villalba | Revisión |
| Alta reciente cuyo pago se anuló (el pago anulado no cuenta) | **Tobías Vera** (47.7) | Revisión |
| Alta un día antes del límite de 15 días, pagada | **Joaquina Luna**, ingreso 15/11 (41.7) | Revisión |
| Alta justo en el límite, pagada | **Bruno Cabrera**, ingreso 16/11 (41.7) | Cuota $32.000 |
| Alta reciente, pagada, sin venir (CBZ-017 a) | Valentino Godoy (45.1) | Cuota $44.000 |
| Segundo deporte sin venir: cada ficha se decide sola | **Thiago Ruiz** (46.4) | Patín a Revisión; Fútbol, cuota $28.000 |
| No vino, pero ya pagó diciembre (no hay nada que generar) | **Facundo Ríos** (47.8) | No va a Revisión; diciembre pagado |
| Ingreso el 1/12 | Renzo Acuña | No crea otra: sigue la de $25.000, pendiente |
| Adelantados | Joaquín Vega, Emma Fernández, Dante Ramos, Clara Iglesias, Morena Rossi, Emilia Medina y los 15 de 47.9 | No crea otra |
| Baja antes del 1/12 | **Lautaro Medina** (47.10), Candela | Sin cuota |
| Vino y no pagó | Liam Pereyra | Cuota $35.000 y pasa a Deudor |
| Cambió de grupo | Lara Quiroga | Cuota $43.000, el precio de Intermedias |

**Totales del ensayo:** 114 activos: 84 cuotas creadas, 7 a Revisión, 23 ya existían, 0 sin plan. Deuda
$3.652.910, en la que **$270.000 son el defecto de Clara** (42.1). Queda sin preparar: **alumno sin plan**
(ALU-023), porque no encontré cómo llegar por pantalla.

### Quién opera y cómo se equivoca

| Día | Dueño | Operativa | Profesora, por pantalla | Errores humanos |
|---|---|---|---|---|
| 41 | Nivel, grupo, profesor | Sandra | **Mariela** toma lista por teléfono; **Lucía** intenta abrir un recibo | Apertura mal escrita, nivel mal escrito, grupo y profesor mal cargados, fecha lejana, doble Guardar |
| 42 | Internet | Rocío | **Lucía** mira una lista ajena | **Un cero de más en un cobro**, cancelar sin motivo, doble Guardar, gasto duplicado, dejar más cambio |
| 43 | Abre la caja de Pablo | Pablo | **Lucía** toma lista; **Lucía** busca cambiar su clave | Profesor de otro deporte, abrir a su propio nombre |
| 44 | Desde su casa | **Sandra** escribe direcciones | **Lucía** escribe direcciones | Fecha de mañana, gasto de octubre que llegó tarde |
| 45 | Cobra sin caja, carga un gasto en el turno | Sandra | **Hernán** se olvida de la lista | Cobrar por el medio equivocado, motivo corto, cambiar el ingreso con pago hecho |
| 46 | Corrige profesora e Internet | Pablo | **Hernán** carga la lista atrasada por teléfono | Cobrar dos veces, **cerrar con la pantalla vieja**, doble clic, otra pestaña |
| 47 | Calendario, usuario | Rocío | **Gonzalo** entra por primera vez | Se arrepienten de un alta, choque de horario, doble clic en la serie, usuario mal cargado |

## Punto de partida

- **El sitio tiene el código de `937d99cc`:** tiene la etapa C de la Ayuda y T98, y **no tiene las reparaciones de T94 a T101**. Hasta
  terminar la tanda no se despliega nada: cambiaría lo que este guion espera.
- Reloj en el **18/11 a las 09:00**; **no volver atrás**. El día 41 se fija a las 09:00 del 19/11.
- Deuda **$205.200**; 108 activos: 102 Al día, 1 En plazo (Emma Villalba), 5 Morosos (Alma, Liam, Pilar, Facundo y
  Valentina). Disponible **$6.358.020,75**: Efectivo $1.253.900, Mercado Pago $3.383.070,75, Galicia $1.663.050,
  Nación $58.000. Rocío dejó $7.000 de cambio.
- **No vienen en toda la tanda:** Pilar, Valentina, Facundo, Emma Villalba, Valentino, Joaquina y Bruno, Tobías y la
  ficha de Patín de Thiago. **Alma viene una sola vez**, el 23/11. **Liam viene y no paga.**

## Cómo se trabaja

Igual que las tandas anteriores: reloj del día una vez, rutina de la mañana con `--simular` y después real, las
tareas de la tabla **por pantalla** y en orden, rutina de la noche. Lo que no coincide se anota y se sigue; **no se
repara nada**. Todo lo marcado «teléfono» se hace con la pantalla en 375 de ancho. Lo marcado **Solo prueba técnica**
no lo hace Codex: se fuerza fuera de la pantalla en la revisión de Claude.

## Día 41: jueves 19/11, se arma Fútbol Infantiles

Reloj **09:00**. **Antes de la rutina:**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 41.0 | Sandra | Abre la caja mal: vacío; con letras; `-7000`; `7000.505`; bien pero sin tildar que contó. | No abre ninguna. Ofrece **$7.000** heredados. Los tres importes mal escritos: la pantalla los frena o «Ingresá un importe válido, con hasta dos decimales.» (CAJ-008). |

**Rutina** (`dia-41-manana.json`): Sandra abre con $7.000; cinco familias de Avanzadas pagan el torneo a $3.000 (cuatro
en efectivo, una por MP); dos remeras a $18.000 (efectivo y MP); limpieza $4.500, librería $1.200, insumos $2.300,
botiquín $1.800; listas de Avanzadas 17 (faltan Valentina y Catalina Castro) y Fútbol Avanzadas 19 (falta Lucas Gómez).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 41.1 | Dueño | Niveles → Nuevo: «Infantil». Lo corrige a «Infantiles». Otro «infantiles ». | «Nivel creado correctamente.»; «Nivel actualizado correctamente.»; el tercero: «Ya existe un nivel con un nombre similar.» (CAT-004). |
| 41.2 | Dueño | Grupos → Nuevo, mal: Patín Principiantes otra vez; Fútbol Infantiles sin planes; plan a $0; **dos filas de 1 por semana**. Después bien: 1 por semana $25.000 y 2 por semana $38.000. | «Ya existe un grupo con ese deporte y nivel.»; «El grupo debe tener al menos una frecuencia para poder cargar alumnos.»; «El precio debe ser mayor a cero.». **Dos filas iguales: página de error (T99)**, sin guardar nada. Bien: «Grupo creado correctamente.» (GRU-002). |
| 41.3 | Dueño | Profesores → Nuevo, mal: DNI de Lucía; comisión 101; mail sin arroba; sin nombre. Después **Gonzalo Ibarra**, Fútbol, DNI 33444555, nacido el 15/6/1990, Av. Rivadavia 4500, CABA, 11-4444-5555, gonzalo.ibarra@example.com, comisión 35%. | «Ya existe un profesor con ese DNI.»; «El porcentaje de comisión no puede superar 100.»; «El email debe ser una dirección válida.»; «El nombre es obligatorio.». Bien: «Profesor creado correctamente.» y su renglón de sueldo (PRO-002). |
| 41.4 | Sandra | Alta de **Renzo Acuña**: nacido el 14/3/2019, DNI 56123456, tutora Marcela Acuña (11-5566-7788), Fútbol Infantiles, 1 por semana. Ingreso **4/1/2027**; después **1/12**. | El primero: «La fecha de ingreso no puede ser posterior al 31/12/2026.». El segundo: «Alumno creado correctamente.»; diciembre pendiente $25.000 e inscripción $5.000; En plazo por $5.000 (ALU-007). |
| 41.5 | Sandra | La mamá paga solo la inscripción, $5.000 en efectivo. | Al día, $0. Diciembre no cuenta como deuda hasta diciembre. |
| 41.6 | Sandra y Lucía | Sandra abre el último recibo de Dante Ramos (lo cobró Rocío) y el detalle de la última caja de Rocío; escribe el recibo 99999 y el **resumen** de esa caja. Lucía escribe la dirección del recibo. | Sandra ve recibo y detalle; 99999 no existe; resumen: Sin permiso. Lucía: «No tenés permiso para ver recibos de cobranza.» (REC-004, ACC-016). |
| 41.7 | Sandra | Dos adultos que se anotaron y recién hoy se cargan. **Joaquina Luna**: DNI 28111222, nacida el 20/5/1991, cel. 11-4455-6677, Patín Adultos, 1 por semana, **ingreso 15/11**. **Bruno Cabrera**: DNI 27333444, nacido el 9/9/1988, igual, **ingreso 16/11**. | Joaquina: 100% (primera quincena), noviembre **$32.000**. Bruno: 70% (segunda quincena), noviembre **$22.400**. Los dos con inscripción $5.000. **Son los dos lados del límite del 1/12.** |
| 41.8 | Sandra | Al guardar a Bruno: doble clic en Guardar. Después atrás, cambia el apellido a «Cabreras» y reenvía. | Un solo Bruno. Lo segundo: «Este formulario ya fue guardado con otros datos. Abra un alta nueva.» (ALU-008). |
| 41.9 | Sandra y Mariela | Joaquina paga **$37.000** en efectivo; Bruno, **$27.400** por MP. **A las 18:00, Mariela, por teléfono,** toma Federadas (falta Helena Iglesias). | Los dos Al día. La lista entra sin desplazarse de costado y Guardar está siempre a mano (ASI-014). |

**Noche** (`dia-41-noche.json`): Sandra cierra con el esperado, **$69.200**, y deja $10.000; el dueño valida.

**Cierre del día 41:** deuda **$205.200**; 111 activos: 105 Al día, 1 En plazo, 5 Morosos. Ingresos **$4.179.300**
(las remeras no suman: son aporte); egresos $2.748.580; resultado $1.430.720; disponible **$6.468.620,75**. Efectivo
$1.316.100; MP $3.431.470,75; Galicia $1.663.050.

## Día 42: viernes 20/11, el cero de más

Reloj **09:00**. **Rutina** (`dia-42-manana.json`): Rocío abre con $10.000; cuatro familias de Fútbol Principiantes
pagan el torneo a $3.000; remera $18.000; **patines usados $85.000 por MP**; fotocopias $2.800, bolsas $2.100, bisagra
$6.000, cinta $1.500; **Intermedias la toma Mariela**, que cubre a Lucía (faltan Alma y Emma Villalba).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 42.0 | Rocío | **Liam** quiere pagar solo diciembre, debiendo noviembre. No sigue. | Avisa: «Quedan 1 meses anteriores impagos por $38,500.00 (noviembre 2026). ¿Cobrás igual…» y pide motivo. Cancela: no se crea nada (COB-013). **Solo prueba técnica:** un mes a más de doce meses. |
| 42.1 | Rocío | **Clara Iglesias** paga diciembre. Primero escribe mal: `44.505`, `0` y letras. Después, **sin darse cuenta, $300.000**. Se da cuenta: cancela sin motivo; después con «Un cero de más: eran $30.000». Vuelve a Cobrar y escribe **$30.000**. Ante el panel, motivo «Corrijo el importe: eran $30.000». | Lo mal escrito no pasa (COB-011). **$300.000 entra sin ningún aviso** (lo que Carlos tiene para decidir de la tanda 3). Cancelar: sin motivo no; con motivo «Cobro cancelado y deuda revertida.», y el efectivo esperado baja $300.000 (COB-019). **DEFECTO (T100):** diciembre queda pendiente **en $300.000**. Los $30.000 piden motivo como «cobro parcial» y **quedan $270.000 de saldo**. Hoy no cuenta como deuda; **el 1/12 sí**. Anotarlo y seguir. |
| 42.2 | Rocío | Resma $6.500 en efectivo, doble clic en Guardar. | Un solo movimiento (MOV-015). |
| 42.3 | Rocío | Corrige la resma a $6.000. Después busca editar un gasto de su caja validada del 18/11 y escribe esa dirección. | La corrección entra. La caja validada no ofrece editar; guardar se rechaza y el importe no cambia (MOV-009). |
| 42.4 | Lucía | Abre la clase de Intermedias que tomó Mariela. Escribe la ficha de Lara Quiroga. | Ve la lista. La ficha: Sin permiso (ASI-016). |
| 42.5 | Dueño | Cashflow → Nuevo: **Internet $22.000** por Galicia, «Telecentro, noviembre». Otra vez desde otra pestaña. Elimina el segundo con motivo «Cargado dos veces». | Quedan los dos hasta eliminar: **no hay freno de duplicado**. Después Galicia baja $22.000 una sola vez y el historial conserva la anulación (CSH-008). |
| 42.6 | Rocío | Cierra: primero deja de cambio más de lo contado; después **$15.000**, aunque recibió $10.000. | Lo primero se rechaza. Lo segundo pasa (CAJ-022). Esperado **$51.600**. |

**Noche** (`dia-42-noche.json`): el dueño valida.

**Cierre del día 42:** deuda **$205.200**; 111 activos: 105, 1, 5. Ingresos **$4.191.300** (diciembre no suma);
egresos $2.788.980; resultado $1.402.320; disponible **$6.573.220,75**. Efectivo $1.357.700; MP $3.516.470,75;
Galicia $1.641.050.

## Día 43: sábado 21/11, llueve y Pablo llega tarde

Reloj **09:30**. Hoy no se cobra nada: es a propósito (CAJ-037). **Sin rutina de mañana:** la única lista va por pantalla (43.4).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 43.1 | Dueño | Pablo llega tarde: el dueño abre la caja. Primero intenta a su nombre; después elige a Pablo, con los $15.000 propuestos. | A su nombre no puede: no se ofrece. A nombre de Pablo: «Caja abierta con el efectivo declarado.»; el turno es de Pablo (CAJ-012, CAJ-022 b). |
| 43.2 | Dueño | Federadas de las 10: suma a Lucía a Mariela. Después intenta poner a Gonzalo. | Las dos quedan. Gonzalo no se ofrece: es de Fútbol (CLA-020, CLA-007). |
| 43.3 | Pablo | Cancela Fútbol Avanzadas de las 11, «Lluvia: la cancha está anegada». | «Clase cancelada.» Sin lista. |
| 43.4 | Lucía | **Por pantalla:** toma Federadas (falta Catalina Díaz). Busca en su menú cómo cambiar la clave. | La lista entra. El menú trae solo Clases y Ayuda; no hay cambio de clave (USU-012, SIS-013). |
| 43.5 | Pablo | Mira Inicio y la última caja de Rocío; escribe el resumen, el cierre y editar uno de sus movimientos. | Detalle sí; las otras tres, Sin permiso (ACC-016). |
| 43.6 | Los tres | Comparan el menú. | Lucía: Clases, Ayuda. Pablo: Inicio, Alumnos, Clases, Cobranza, Caja, Movimientos, Revisión, Grupos, Ayuda. El dueño, todo. **El catálogo está viejo:** dice «Medios de pago» donde dice «Tipos de Caja»; anotarlo (SIS-013). |
| 43.7 | Pablo | 13:30: cierra sin movimientos, $15.000 contados y $15.000 de cambio. | «Caja cerrada.»; diferencia $0 (CAJ-037). **El dueño no valida hoy.** |

**Cierre del día 43:** igual al 42, con **1 caja por validar**: disponible $6.573.220,75.

## Día 44: domingo 22/11, el dueño desde su casa

Reloj **20:00**. Sin actividad en el club.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 44.1 | Dueño | Historial del 16 al 21/11 y Cashflow del mismo período. | Historial: ingresos **$421.200**, egresos **$43.200**. Cashflow: ingresos $421.200, egresos **$65.200**. **La diferencia son los $22.000 de Internet:** Historial solo muestra lo que pasó por las cajas. **Contra el catálogo** (MOV-013/014 esperan otra cosa): anotarlo. |
| 44.2 | Dueño | Valida la caja de Pablo. | «Caja validada y reflejada en cashflow.»; no cambia ni un número (CAJ-037 b). |
| 44.3 | Dueño | Configuración: inscripción a **$8.000**. | La de Emma Villalba sigue en $5.000; la deuda no cambia (CFG-003). |
| 44.4 | Dueño | Fichas de Gonzalo y Mariela. | Datos reales, sin «Próximamente» (PRO-005, SIS-012). |
| 44.5 | Dueño | Días de gracia a **25**; mira Cobranza. Prueba **40**. Vuelve a **10**. | Con 25: **6 En plazo, 0 Morosos**, deuda igual. 40: «Elegí un día del 1 al 28.». Con 10: 1 En plazo, 5 Morosos (CFG-001). **Dejarlo en 10: el recargo corre el martes.** |
| 44.6 | Dueño | Cashflow → Nuevo: **Luz $9.400** por Galicia, «Edesur, octubre: llegó tarde». Fecha de mañana; después 28/10, sin confirmar y confirmando. | Mañana: rechazado. 28/10: pide confirmar que es un mes cerrado; sin confirmar no guarda. Confirmado: guarda. **Egresos de octubre $331.950 → $341.350**; noviembre no cambia; llega el aviso de fecha vieja (CSH-005, REP-005). |
| 44.7 | Sandra y Lucía | Cada una, desde su casa, escribe Cashflow, Reportes, Reportes de Alumnos, Deportes, Niveles, Rubros, Tipos de Caja, Configuración y Usuarios. Sandra abre Grupos; Lucía también. | Todo Sin permiso, en castellano y sin importes. Sandra ve Grupos sin Nuevo ni Editar; Lucía, Sin permiso (REP-008, CSH-012, CAT-010, GRU-007). |

**Cierre del día 44:** deuda **$205.200**; 0 cajas por validar. Ingresos $4.191.300; egresos $2.788.980; disponible
**$6.563.820,75**. Galicia **$1.631.650**.

## Día 45: lunes 23/11, se corta la luz

Reloj **09:00**. **Rutina** (`dia-45-manana.json`): Sandra abre con $15.000; cinco familias de Intermedias pagan el
torneo a $2.800; remera $18.000; medias $12.000 por MP; botiquín $5.200, detergente $1.900, carpeta $900, canilla
$7.500, tiza $2.600; listas de Patín Principiantes (faltan Pilar y Martina López) e **Intermedias de las 17: Alma
viene, la única vez en el mes** (falta Emma Villalba). **Hernán se olvida de la suya: Fútbol Principiantes queda sin lista.**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 45.1 | Sandra | Alta de **Valentino Godoy**: nacido el 2/7/2017, DNI 55444555, tutora Lorena Godoy (11-3344-5566), Patín Principiantes, 2 por semana, ingreso 23/11. Paga todo en efectivo. Avisan que empieza en diciembre. | Noviembre **$30.800** (70%) e inscripción **$8.000**, ya la nueva. Cobra **$38.800**; Al día (CFG-003, COB-025). |
| 45.2 | Dueño | Desde la ficha de **Morena Rossi**, cobra diciembre: **$44.000 por Mercado Pago**. | Sin caja. Directo a Cashflow como «Cobro del dueño». El esperado de Sandra **no cambia** (COB-016). |
| 45.3 | Dueño | Era transferencia al Galicia: Anular con motivo «MP»; después «Era transferencia al Galicia, no Mercado Pago». Cobra de nuevo por Galicia. | «El motivo de la anulación debe tener al menos 10 caracteres.»; «Cobro anulado y deuda restituida.»; Cashflow lleva +$44.000 y −$44.000 con la misma fecha; el nuevo, por Galicia (COB-021). |
| 45.4 | Sandra | Ficha de Morena. | Ve los cobros, el anulado y el recibo. **No tiene Anular** (ALU-021). |
| 45.5 | Sandra | Renzo no empieza el 1/12 sino el 5/12: cambia la fecha, sin motivo y con «La mamá avisa que empieza el sábado 5/12». | Sin motivo: pide motivo, con un nombre interno (T99). Con motivo: «No se puede modificar el ingreso: la inscripción tiene pagos registrados.». Sigue el 1/12 (ALU-015). |
| 45.6 | Dueño | Cashflow de hoy. | Columna «Val.»; Morena y quién la registró (CSH-014). |
| 45.7 | Dueño y Sandra | El dueño busca validar la caja abierta de Sandra. Sandra busca Validar en la suya. | «Contá el efectivo y cerrá la caja antes de validarla.»; Sandra no tiene Validar ni Rechazar (CAJ-026, CAJ-028). **Solo prueba técnica:** forzar el envío. |
| 45.8 | Dueño | Va a Nuevo movimiento de caja. Después, en la tarjeta del turno de Sandra, Agregar: **Insumos $3.000**, «Bombitas de luz: las pagó la caja». Sandra mira su caja. | Nuevo movimiento lleva a Cashflow (MOV-006). Agregar entra en la caja: esperado **$67.700 → $64.700** (CAJ-041). **DEFECTO probable (T101):** el catálogo pide que figure a nombre de quien lo cargó; en el código queda a nombre de Sandra (`CajaService.php:550`). Anotar qué muestra la pantalla. |
| 45.9 | Dueño | 18:30: la clase de Intermedias de las 17 se suspendió a los 15 minutos. Cancelar con «Se cortó la luz a los 15 minutos». Confirmar. | Avisa que tiene la lista tomada y pide confirmar. «Clase cancelada.» Conserva sus **19 marcas** y deja de contar: **Alma queda con 0 presentes válidos en noviembre** (CLA-012 b y c). |
| 45.10 | Dueño y Hernán | Inicio y «Sin asistencia». Hernán abre sus clases. | **1 clase sin lista:** Fútbol Principiantes de hoy. Hernán la ve pendiente. **No la carga hoy.** |

**Noche** (`dia-45-noche.json`): Sandra cierra con el esperado, **$64.700**, y deja $10.000; el dueño valida.

**Cierre del día 45:** deuda **$205.200**; 112 activos: 106, 1, 5; **sin lista 1**. Ingresos **$4.244.100**; egresos
$2.810.080; resultado $1.434.020; disponible **$6.669.520,75**. Efectivo $1.407.400; MP $3.528.470,75; Galicia $1.675.650.

## Día 46: martes 24/11, el recargo de Emma

Reloj **06:13**; **esperar las 06:15**: corre el recargo solo. A las **09:00** sigue el día.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 46.0 | Dueño | Cobranza. | **Emma Villalba Morosa, $30.410** (cuota $23.100 + inscripción $5.000 + recargo **$2.310**). Deuda **$207.510**. 0 En plazo, 6 Morosos (PEN-001). |
| 46.1 | Pablo | Abre con $10.000. Alta de **Milagros Ferreyra**: nacida el 11/2/2014, DNI 52999888, tutora Silvina Ferreyra (11-7788-9900), Patín Avanzadas, 1 por semana, ingreso 24/11. Paga todo por MP. | Noviembre **$14.000** (40%, fin de mes) + inscripción $8.000 = **$22.000**. Al día. |

**Rutina** (`dia-46-manana.json`, 10:00): cinco familias de Federadas pagan el torneo a $3.000; remera $18.000 por MP;
cinta $3.800, lavandina $2.000, resaltadores $1.100, enchufe $5.000, alcohol $1.500; listas de Avanzadas (falta
Valentina; **Milagros presente**), Federadas (falta Lucía Ramos), **Fútbol Avanzadas la toma Martín** (falta Simón
Silva) y Adultos (**faltan Joaquina y Bruno**).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 46.2 | Pablo | Otra pestaña que quedó abierta: cobra otra vez a Milagros. | Se rechaza: «El importe entregado debe ser positivo y no superar la deuda seleccionada más la inscripción.»; un solo pago (COB-023). |
| 46.3 | Hernán | **Por teléfono:** carga la lista del lunes de Fútbol Principiantes (falta Facundo). | «Asistencias guardadas.»; 25 alumnos. Sin lista: **0**. No pide motivo: es la primera carga (ASI-014). |
| 46.4 | Pablo | **Thiago Ruiz** (Fútbol) se anota también en **Patín Principiantes**, 1 por semana, ingreso 24/11, con el mismo DNI. No paga. | Se crea la segunda ficha: noviembre **$12.000** (40%) e **inscripción $8.000**. **Calculé que no cobraría inscripción y me equivoqué:** esa persona vino de la primera carga sin inscripción registrada, así que Wings la crea. En plazo. |
| 46.5 | Dueño | La clase de Intermedias del 20/11 la dio Mariela: cambia la profesora, sin motivo y con «La dio Mariela: Lucía estaba con médico». Pablo intenta lo mismo con una clase del 19/11. | «Esta clase ya tenía profesor asignado: indicá el motivo del cambio.»; «Profesores actualizados.». Pablo: «Esta clase ya pasó: solo un administrador puede cambiar sus profesores.» (CLA-010). |
| 46.6 | Dueño | Internet a **$23.500**, motivo «La factura vino con el aumento: $23.500», doble clic. Otra pestaña con la pantalla vieja: $24.000. Después fecha de mañana. | «Movimiento actualizado. El historial se conserva.», una sola corrección. Pantalla vieja: «El movimiento cambió mientras editabas. Recargá la pantalla antes de guardar.». Mañana: rechazado (CSH-010, CSH-017). |
| 46.7 | Pablo | 21:00: abre el cierre y **deja la pantalla con el arqueo a la vista: $11.600**. En otra pestaña, **Emilia Medina** paga diciembre, **$44.000 en efectivo**. Vuelve a la pantalla vieja y cierra con **$11.600 contados**, dejando $5.000. Doble clic. | **Cierra sin avisar que el esperado cambió:** esperado **$55.600**, contado $11.600, **diferencia −$44.000**. El cobro queda dentro y a la vista como faltante (CAJ-023). **Anotar lo que muestra:** los $44.000 están en el cajón y figuran como faltante. Segundo clic: «La caja no está en estado editable.» (CAJ-024). |

**Noche** (`dia-46-noche.json`): el dueño valida, viendo la diferencia.

**Cierre del día 46:** deuda **$227.510**; **114 activos**: 107 Al día, 1 En plazo (Thiago), 6 Morosos. Ingresos
**$4.281.100**; egresos $2.824.980; resultado $1.456.120; disponible **$6.753.620,75**. Efectivo $1.453.000; MP
$3.568.470,75; Galicia $1.674.150.

## Día 47: miércoles 25/11, el día más cargado

Reloj **09:00**. **Rutina** (`dia-47-manana.json`): Rocío abre con **$5.000**; cinco familias de Patín Principiantes
pagan el torneo a $3.200; remera $18.000; detergente $3.600, hojas $1.400, gasas $2.200, cinta $1.700; listas de Patín
Principiantes (**faltan Pilar, Valentino y la ficha de Thiago**) y Fútbol Principiantes (falta Facundo); el dueño carga
el calendario de diciembre: **64 clases**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 47.1 | Rocío | Ficha de Emma Villalba y Cobranza → Morosos. | Emma $30.410. Seis: Alma $36.300, Liam $38.500, Pilar $33.000, Facundo $30.800, Valentina $38.500 y Emma. |
| 47.2 | Dueño | **Fútbol Infantiles**, sábados de diciembre a las 11, con Gonzalo y Hernán. Después a las 10, solo Gonzalo. **Doble clic** en Guardar. | «la profesora Hernán Quintana ya tiene asignada la clase 'Fútbol — Avanzadas' que se solapa…» (dice «la profesora» de un profesor: anotarlo) y no crea ninguna. A las 10: «4 clase(s) creada(s).»: 5, 12, 19 y 26/12. El segundo envío se rechaza por el choque; siguen 4 (CLA-006, CLA-019). |
| 47.3 | Dueño | **Patín Adultos** de diciembre, martes y jueves 20:30, Mariela. | Aviso: dura 1 hora y ocupa 2 bloques de alquiler. Confirma: **10 clases**. |
| 47.4 | Rocío | Abre diez veces el recibo de Valentino. | Siempre el mismo; no se crea nada (REC-006). |
| 47.5 | Dueño | Usuario de Gonzalo, mal: mail de Hernán; sin arroba; clave de 8; claves distintas; Profesor sin elegir; sin nombre. Después bien: Gonzalo Ibarra, gonzalo.ibarra@example.com, clave `Infantiles-2026!`, Profesor, Gonzalo. Atrás y reenvía. | «Ya existe un usuario con ese email.»; «El email no tiene un formato válido.»; «El campo clave debe tener al menos 12 caracteres.»; «Las contraseñas no coinciden.»; «Debe seleccionar el profesor vinculado.»; «El nombre es obligatorio.». Bien: «Usuario creado correctamente.». Reenvío: otra vez el mail repetido; un solo usuario y un solo renglón de sueldo (USU-002, USU-011). |
| 47.6 | Gonzalo | Entra por primera vez. | Menú: Clases y Ayuda. Ve sus clases de diciembre. |
| 47.7 | Rocío | Alta de **Tobías Vera**: nacido el 30/8/2015, DNI 53777888, tutora Andrea Vera (11-6677-8899), Fútbol Principiantes, 1 por semana, ingreso 25/11. Paga **$19.200** en efectivo ($11.200 al 40% + $8.000). La mamá se arrepiente: Rocío cancela con «La mamá se arrepintió: no hay cupo en su horario». | Se cancelan los dos renglones juntos. El esperado vuelve a **$30.100**. Tobías **En plazo, $19.200**. El recibo sale anulado con el desglose (COB-020). |
| 47.8 | Rocío | **Facundo Ríos** trae noviembre con el recargo y diciembre, **$58.800** por MP, sin haber venido. | Al día; noviembre y diciembre pagados. No va a Revisión. |
| 47.9 | Rocío | **Quince cobros seguidos de diciembre,** cada uno con su recibo. **Efectivo:** Lola Ríos $30.000, Abril Silva $30.000, Zoe Flores $30.000, Sofía Gómez $30.000, Bianca Pereyra $44.000, Mía Pérez $44.000, Paula Ledesma $43.000, Sol Ruiz $43.000. **MP:** Berenice Sánchez $43.000, Paulina Benítez $43.000, Bautista Ferrari $35.000, Agustín Acosta $35.000, Julián Navarro $52.000. **Galicia:** Tomás Giménez $52.000, Helena Iglesias $50.000. **Anotar si alguna pantalla se pone lenta.** | Los quince entran, cada uno al precio propuesto. Efectivo $294.000, MP $208.000, Galicia $102.000. Esperado **$324.100**. Ingresos de noviembre no suben (COB-030). |
| 47.10 | Rocío | Da de baja a **Lautaro Medina** desde el listado. Mira el listado. | «Alumno desactivado correctamente.». Sigue en el listado marcado Inactivo, sin estado de cobranza (ALU-026). |

**Noche** (`dia-47-noche.json`): Rocío cierra con el esperado, **$324.100**, y deja $10.000; el dueño valida.

**Cierre del día 47:** deuda **$215.910**; 114 activos: **107 Al día, 2 En plazo (Thiago y Tobías), 5 Morosos**.
Ingresos **$4.327.900**; egresos $2.833.880; resultado $1.494.020; disponible **$7.441.520,75**. Efectivo $1.772.100;
MP $3.835.270,75; Galicia $1.776.150; Nación $58.000.

## Cómo se sabe que da bien

- **Plata:** los saldos por medio y el disponible de cada día se calcularon a mano **antes** de simular
  ([cuentas](simulacion/tanda-4/esperado-antes-de-simular.txt)) y **coinciden al peso los siete días**. Los ingresos y
  egresos se comprobaron a mano después, contra las operaciones de cada día. En la deuda hubo una sola diferencia,
  los $8.000 de 46.4, explicada ahí.
- **Volumen:** al cierre del 25/11, 363 movimientos de caja, 229 cobros, 1.763 marcas de asistencia, 396 renglones de
  Cashflow y 117 alumnos en la base. Al revisar, Claude mide las mismas pantallas que en las tandas anteriores (la
  última, por debajo de 281 ms) y compara. Es volumen de una semana normal, no de dos años: eso lo sigue midiendo
  `performance-volumen-2-anios`.
- **Lo que esta simulación no prueba:** corrió sobre la copia local y entrando por los controladores, no por el
  navegador. Lo que dependa del navegador (frenos de escritura, doble clic real, teléfono, confirmaciones) lo
  comprueba Codex.

## Lo que queda para la tanda 5

- **Clara debe $270.000 que no debe:** la reparación de T100 no arregla sola lo ya cargado. El guion de la tanda 5 dice qué se
  hace con su saldo.
- **Probar en la tanda 5 lo que se está reparando (Carlos, 30/10).** Los esperados salen de lo que se decida en cada tarea:

| Tarea | Qué se prueba por pantalla | Casos |
|---|---|---|
| T94 | Sacar de un grupo una frecuencia nunca usada, una usada antes y una con alumnos hoy; sin error del servidor ni falso «Grupo actualizado» | GRU-004 |
| T95 | Guardar un profesor sin tarifa; que avise en el momento y no recién al liquidar | PRO-002 |
| T96 | El dueño llega a configurar el mostrador desde Caja, con y sin cajas abiertas | CAJ-003 |
| T97 | Anular una transferencia entre cuentas: se revierte en las dos y queda el motivo | CSH-018 |
| T98 | Ya en el sitio: el aviso al no poder eliminar un alumno dice «Puede pasarlo a Inactivo.» | ALU-024 |
| T99 | Repetir 41.2: dos filas de la misma frecuencia sin página de error; avisos sin nombres internos | GRU-002 |
| T100 | Repetir 42.1 con otro alumno: adelanto con un cero de más, cancelarlo y cobrar bien; sin saldo fantasma | COB-019 |
| T101 | Repetir 45.8: el gasto del dueño en un turno figura a su nombre | CAJ-041 |
- Generación del 1/12 contra la tabla de arriba; Revisión con tres resoluciones (CBZ-014, 015, 016); las fichas de
  Thiago, una con cuota y otra en Revisión.
- Liquidación de noviembre: Intermedias del 20/11 de Mariela; Federadas del 21/11 con dos profesoras; **Intermedias
  del 23/11 cancelada con lista**; Fútbol Avanzadas del 21/11 cancelada; comisión con los adelantos de diciembre,
  que cuentan en diciembre.
- Diciembre tiene clases el **8/12 y el 25/12, feriados**: el dueño las cancela. Reactivar a Lautaro (ALU-018).
