# Prueba por días (PRU-04) — instrucciones para Gemini, desde el día 4

Escrito por Claude CyE el 21/10/2026, a pedido de Carlos. No es un prompt: es lo que necesitás saber
para escribir los cuentos de la prueba sin repetir lo que ya se hizo ni lo que ya salió mal.
Actualizado por Codex CyE el 22/10/2026 según la aclaración expresa de Carlos sobre los tres roles.
Esta asignación reemplaza los pedidos anteriores que mezclaban cuento y auditoría técnica.
El Día 4 anterior queda como antecedente: hay que reescribirlo, sin ejecutar ni borrar los días 1 a 3.

---

## 1. Para qué es esta prueba

Wings es el sistema de gestión del club (alumnos, cuotas, cobros, caja, cashflow, clases, asistencia,
liquidaciones de profesoras y reportes). Carlos quiere **subir a producción la versión actual**. Antes,
hay que comprobar los caminos felices, las validaciones y los errores de uso por pantalla, y
**cerrar un mes completo** (octubre) para ver el fin de mes y el arranque del siguiente.

La prueba se cuenta como un **cuento**: días simulados del club, uno detrás de otro, con personas que
hacen su trabajo por el menú y los botones. Lo que se prueba no se dice en el cuento: está implícito en
los controles (a dónde fue la información, si quedó a la vista de quien corresponde, si apareció un error).

Objetivos de hoy, en orden:

1. Que cada módulo funcione en su camino feliz con la versión desplegada.
2. Que la información llegue a todos los lugares donde tiene que estar (Inicio, Cobranza, Caja,
   Cashflow, Reportes, Sueldos, fichas) y diga lo mismo en todos.
3. Que cada rol vea y haga lo suyo, y no lo ajeno.
4. Acumular datos realistas de octubre (cobros y asistencia todos los días) para que el fin de mes,
   el 1/11 y la liquidación de octubre tengan algo real que calcular.

Los errores humanos también forman parte de los cuentos: escribir mal un nombre y corregirlo,
equivocarse de DNI y pedir eliminar al alumno, omitir un dato obligatorio, intentar una operación
sin permiso o cancelar antes de guardar. El relato describe el pedido; no inventa si Wings debe
permitirlo o rechazarlo. Las expectativas se apoyan en las decisiones y contratos vigentes.
Esto no cambia por sí solo el estado de T76 ni acredita su ejecución.

---

## 2. Quién hace qué

| Quién | Qué hace en esta etapa |
|---|---|
| **Carlos** | Dueño del producto. Decide y aprueba lo visual. Resuelve dudas de negocio que no estén definidas. |
| **Gemini (vos)** | Escribe jornadas realistas orientadas a objetivos funcionales, con errores humanos y comprobaciones por pantalla. Planifica la cobertura y el crecimiento de datos. No ejecuta los días ni prepara la auditoría interna. |
| **Codex — Prueba humana** | Interpreta el cuento, opera por menús y formularios y comprueba por pantalla. Informa qué hizo, qué vio y dónde tuvo dificultades. No necesita tablas, servicios, SQL ni cálculos internos. |
| **Este chat — Casos prueba humana I** | Convierte la misma historia en controles técnicos y los contrasta con el informe del ejecutor. Revisa datos, cálculos, procesos, seguridad, eficiencia y efectos indirectos. La preparación técnica y del entorno queda fuera del relato humano. |
| **Claude** | Casi sin límite semanal hasta el sábado. No cuenten con él para la prueba. |

Regla de siempre: **lo que hace uno lo controla otro** (AGENTS.md §6a).

---

## 3. Lo que hicimos hasta ahora y cómo

### La tabla de pruebas

Entre el 12 y el 16/10 Claude armó con Carlos la **Tabla de pruebas Wings**: unos 330 **casos**
(`ALU-007`, `COB-012`, `CLA-012`…), cada uno con qué se hace, qué tiene que existir y, línea por línea, qué
tiene que pasar en todo el sistema. Las preguntas que surgieron se le hicieron a Carlos como **dudas**
(`DU-01` a `DU-60`). **La respuesta de Carlos a una duda es una decisión de negocio**: no se discute ni se cambia.

- La tabla viva es privada de Carlos y Claude. **Vos y Codex solo leen la copia** del repositorio:
  `docs/06-pruebas/PRU-04/tabla-de-pruebas/` (`catalogo/<módulo>.json` y `dudas/DU-xx.json`).
- **No cambian la tabla viva, ni directamente ni pidiéndoselo a una conversación de Claude**
  (AGENTS.md §5-ter). Si un caso tiene que cambiar, lo proponen por escrito.

### Los días 1 a 3 (10, 11 y 12/10)

Los ejecutó Codex en el sitio de prueba y Claude los verificó contra la base. Leé los tres antes de escribir:
[DIA-01.md](DIA-01.md), [DIA-02.md](DIA-02.md), [DIA-03.md](DIA-03.md) y [VERIFICACION-DIA-01.md](VERIFICACION-DIA-01.md).

| Día | Qué pasó |
|---|---|
| 1 — sáb 10/10 | Sandra abrió caja, cobró $164.000, cerró; el dueño validó sin diferencia. Mariela tomó asistencia (6 presentes, 2 ausentes). Inicio no conciliaba; la profesora veía clases ajenas. |
| 2 — dom 11/10 | Sin clases. Inicio, Cobranza y Reportes coinciden: ingresos $164.000, deuda $2.010.000. Venció la gracia: 22 alumnos pasaron a Moroso. |
| 3 — lun 12/10 | Feriado: se cancelaron las 3 clases. La plata quedó intacta. Salió T21: una clase cancelada el mismo día desaparecía de Clases. |

**Formato que funcionó** (respetalo): un archivo por día con un resumen de 3 líneas, las reglas del día y
una tabla `Tarea | Esperado | Lo que pasó | ¿Coincide?`. **Lo esperado se escribe antes de actuar.** Después,
hallazgos clasificados (Frena / Molesta / Detalle) con captura. La verificación de base queda en el
informe técnico separado de este chat, sin instrucciones internas para el ejecutor humano.

**Reglas que se aprendieron en esos días:**
- Todo por menú, botones y formularios, como una persona. Nada de escribir direcciones internas, cargar
  datos por programa, consultar la base durante el recorrido ni arreglar nada.
- El reloj se fija al empezar el día y **no se toca durante el día**.
- El ingreso tiene un límite de **5 intentos por minuto**: al pasar de un usuario a otro, esperar un minuto.
- Las suplencias y abrir caja un feriado están autorizados: no son hallazgos.

### Lo que se hizo después del día 3

La prueba se frenó para resolver lo encontrado y las decisiones de Carlos. **Estas tareas están cerradas y
desplegadas en el sitio de prueba**: los cuentos tienen que pasar por ellas en su camino feliz.

| Área | Tareas (detalle de cada una en el tablero, `docs/00-estado/tareas.json`) |
|---|---|
| Cobros y deuda | T18 cobro adelantado · T29 buscador de Cobrar · T30 centavos · T31 inscripción cuenta como deuda · T33 perdonar y reactivar · T38 reactivar genera la cuota del mes · T39 anular cobro del dueño · T58 crear cuota de un mes pasado · B11 punitorios por mora |
| Alumnos | T50 fecha de ingreso solo hasta fin del mes siguiente · T56 alumno activo siempre con plan · T44 aumento de precio desde el mes siguiente |
| Caja y Cashflow | T23 aviso de apertura · T26 historial de caja · T27 el dueño edita sus movimientos · T28 Cashflow con alumno y quién cobró · T36 validar exige todo tildado · T37 rechazar exige motivo · T57 medio del cajón · T59 el dueño va a Cashflow · T61 pasar plata entre medios sin que cuente en el resultado |
| Clases y asistencia | T21 canceladas visibles · T34 lista tomada sin presentes · T35 no se toma asistencia antes de la clase · T52 la lista es de quienes estaban en el grupo ese día · T66 al menos una profesora · T67 DNI con 3 dígitos |
| Liquidaciones | T41 no se cierra antes de que termine el mes · T42 un mes adelantado es comisión del mes que paga · T43 comisión de cuotas atrasadas: aviso y decisión del dueño |
| Inicio, Reportes, fichas | T16/T17/T19 ingresos y deuda coherentes · T68 fichas de grupo y profesor con datos · T74 Inicio de la operativa con Vencidos y En plazo |
| Configuración | T63 no desactivar con alumnos activos · T64 reglas de primer pago sin huecos |
| Cuotas automáticas | T40 el 1 de cada mes se generan las cuotas; si no corrió, se reintenta y avisa |
| Sistema | T15/T47 pantallas de error en castellano · T73 sin JavaScript dentro de las pantallas · T75 botones en celular |

Reglas de diseño nuevas que el cuento tiene que poder detectar si se rompen:
**DESIGN-RULES §8-bis** (decidir sobre una fila no recarga la página) y **un solo verbo por botón**.

---

## 4. Antecedente del entorno al 21/10 (preparación técnica separada)

Los datos siguientes conservan el corte anterior; este chat debe comprobar el estado vigente.
No son condiciones que deba resolver el lector del cuento. El ejecutor humano recibe el día preparado.

- **Dirección:** https://test.gestionar-te.com.ar · base `wingstest`. **Producción es `wings` y no se toca.**
- **Versión:** `56379295`, desplegada por Claude el 21/10 (respaldo previo en el servidor:
  `/root/respaldos/wingstest-antes-despliegue-2026-10-21.sql.gz`). Es exactamente lo que hay en GitHub.
- **Datos:** los de los días 1 a 3. 100 alumnos activos, todos con plan; 3 reglas de primer pago
  (1–15 al 100%, 16–23 al 70%, 24–31 al 40%).
- **Usuarios:** Admin Prueba (dueño), Sandra Vidal y Pablo Ledesma (operativas), Lucía Gaitán, Verónica
  Salinas, Mariela Ocampo y Hernán Quintana (profesoras). Las claves las tiene Carlos o están en el
  material de los días anteriores; no las escribas en ningún archivo.
- **Configuración:** gracia de cobranza 10 días; cuotas generadas el día 1; inscripción $5.000; plazo de
  alta 7 días; mora desde el día 10, **con porcentaje 0%** (ver §10).
- **Reloj:** está en la hora real. Cada día empieza fijándolo, en el servidor (`ssh vps`):
  `cd /home/wingstest/app && sudo -u wingstest /usr/bin/php82 artisan wings:fecha-simulada "2026-10-13 09:00"`.
  Sin argumento muestra el vigente; `--real` vuelve a la hora real. **El día 4 es el martes 13/10.**
- **Procesos automáticos:** corren cada minuto **con el reloj simulado**: cuotas del mes a las 6:00
  (`--si-falta`), recargos a las 6:15 y resumen diario al dueño a las 8:00. Al cambiar de día, contá con que
  se disparan solo si se dan sus condiciones. Su comprobación corresponde al control técnico;
  cambiar la fecha no demuestra que hayan corrido.

---

## 5. El calendario: del martes 13/10 al lunes 2/11

Carlos aprobó llegar hasta el **2/11**, para cerrar octubre y ver el arranque de noviembre. No todos los
días necesitan un cuento largo:

- **Días cortos** (la mayoría): lo de todos los días del club. Cobros y asistencia, para acumular datos.
- **Días completos:** los que traen algo nuevo. Tienen un cuento con más tareas y controles.

**Lo que tiene que pasar sí o sí en algún día** (pedido de Carlos y caminos felices a probar):

1. **Cobros a alumnos con deuda, todos los días hábiles**, variando: un mes completo, pago parcial, varios
   meses juntos, deuda con inscripción, un **mes adelantado de noviembre** cobrado en octubre (T42), con
   cada medio de pago.
2. **Asistencia todos los días con clases**, y además **tomar asistencias viejas**: las clases pasadas que
   quedaron sin lista (había 33 pendientes el 12/10).
3. **Cambiar profesoras** de una clase (titular y suplencia), incluida una clase ya pasada.
4. **Caja todos los días:** abrir, cobrar, cerrar; el dueño valida (todo tildado) o rechaza con motivo.
5. **Movimientos del dueño en Cashflow** y un **pase de plata entre medios** (T61).
6. **Alumnos:** un alta nueva (dentro del límite de fecha), un cambio de grupo, una baja y una reactivación.
7. **Fin de mes (31/10):** todas las cajas validadas; revisar Inicio, Cobranza, Reportes y Sueldos según
   lo que muestra cada uno. Este chat concilia sus distintas dimensiones, sin exigir totales idénticos.
8. **1/11:** se generan las cuotas de noviembre una sola vez; el que pagó noviembre por adelantado no recibe
   otra; el mes queda anotado como generado (T40). El resumen diario lo refleja.
9. **2/11:** liquidación de octubre de cada profesora, por hora y por comisión. Lo adelantado de noviembre no
   entra en octubre (T42); las cuotas atrasadas aparecen con aviso y el dueño decide (T43). Cerrar, pagar y
   sacar el recibo.

Repartí estos puntos en el calendario como haría el club: los días con clases salen del cronograma de clases
del sistema (el domingo 11/10 no hubo), y la gracia de cobranza vence pasado el día 10 de cada mes. Cada día corto puede repetir lo de los días anteriores con otros alumnos.

---

## 6. Cobertura funcional de los cuentos

Usá el catálogo de solo lectura y los recorridos del control integral para organizar los objetivos
que se pueden ejercitar por pantalla. Podés consultar la interfaz para conocer opciones y vocabulario,
sin cargar la jornada anticipadamente. No se exige una auditoría técnica completa antes del nuevo Día 4.

La meta es cubrir el catálogo completo a lo largo de las jornadas, incluidas variantes, roles y errores
de uso. No metas todo en un día ni marques un caso como probado por mencionarlo en el cuento.
Lo técnico que no se puede comprobar por pantalla se deriva a este chat; lo pendiente queda visible.
Si no hay una expectativa respaldada, señalá la duda sin inventarla ni detener el resto de la redacción.

La actividad repetida también sirve: nuevos alumnos, pagos, clases y asistencias se conservan entre
días para dar historia y volumen a los reportes. Eso prepara la evaluación de performance; no la certifica.

---

## 7. Qué tenés que escribir

### Los cuentos — `DIA-04.md`, `DIA-05.md`, …

Uno por día, con el mismo formato de los días 1 a 3. Vos escribís: el resumen esperado del día, las reglas
del día (reloj, quién entra) y la tabla con **Tarea** y **Esperado** completos. Codex completa **Lo que
pasó** y **¿Coincide?** al ejecutar.

- Las tareas cuentan lo que hacen las personas: «Sandra le cobra a Renata Cabrera octubre y noviembre en
  efectivo». No digan «probar T42».
- **Esperado** tiene que poder comprobarse con la pantalla: importes, estados, cantidades, dónde aparece.
- Al final de cada día, un **cierre del dueño**: consulta Inicio, Cobranza, Caja/Cashflow y Reportes,
  busca los movimientos de la jornada y registra lo que ve. Este chat analiza las diferencias entre
  caja, ingresos y deuda; no se exige que todas las pantallas muestren el mismo total.
- Incluí comprobaciones entre roles: «el dueño registra el cobro; Sandra entra como operativa y lo busca».
  Para restricciones, usá el contrato de permisos; ver clases ajenas no demuestra por sí solo un defecto,
  porque las suplencias están permitidas. Una restricción visible no acredita seguridad HTTP.
- Escribí situaciones naturales: «la mamá aclara que anotaron mal el apellido; pide corregirlo».
  Indicá luego la comprobación humana: buscarlo otra vez y revisar su ficha y su grupo.
- Incluí datos ficticios completos para operar, importes y pedidos concretos de las personas.
  No pongas en sus diálogos saldos internos precalculados, tablas, rutas, servicios o reglas SQL.
- No le pidas al ejecutor preparar el servidor ni reconciliar contadores globales históricos como
  condición técnica. Puede consultar y registrar lo que ve; este chat prepara y analiza el estado inicial.

### Tus objetivos funcionales — `OBJETIVOS-FUNCIONALES.md`

Un solo seguimiento para todas las jornadas, separado del cuento. Para cada objetivo, indicar:

| Campo | Qué poner |
|---|---|
| Situación y objetivo | Qué funcionalidad, validación, error o reporte ejercita |
| Caso de referencia | Código y variante del catálogo cuando corresponda, sin modificarlo |
| Jornada y persona | Cuándo se propone, quién opera y con qué rol |
| Comprobación humana | Qué buscar o revisar por pantalla, incluida claridad y aspecto en computadora/teléfono |
| Continuidad | Qué dato queda para otro día y cuándo se vuelve a observar |
| Cobertura | Planificado, pendiente, observado con evidencia o derivado al control técnico; no confundir planificación con aprobación |

La matriz técnica `OBJETIVOS-PRUEBA.md` es responsabilidad del chat **Casos prueba humana I**.
Su borrador anterior y la revisión del 22/10 son antecedentes para ese chat; no son una orden para
que Gemini audite código o explique el backend a quien ejecuta. Cuando llegue el nuevo cuento,
este chat prepara los controles internos correspondientes.

Rehacé también `INSTRUCCIONES-EJECUTOR-DIA-04.md` con instrucciones exclusivamente humanas.
Las condiciones de servidor y reloj se coordinan aparte. No ejecutes el cuento para completar resultados.

---

## 8. Reglas para escribir y ejecutar

- **Esperado antes de actuar.** Si el sistema hace otra cosa, se anota; no se corrige el esperado después.
- Un rechazo esperado ante un error deliberado es parte del recorrido: se registra y se continúa con
  la corrección prevista en el cuento. Un bloqueo inesperado se informa, sin arreglar el producto.
- **Frena el camino feliz** (no se puede seguir, o la plata queda mal): Codex para y le avisa a Carlos.
  **Molesta o es detalle:** se anota con captura y se sigue.
- No se arregla nada durante la prueba. Los hallazgos van al informe del día; Carlos decide.
- Nada de datos personales ni claves en los archivos. Los alumnos de prueba son ficticios.
- Carpeta compartida con otros agentes: nada de checkout, stash ni reset. Commit y push juntos.
- Cada agente escribe en su bitácora (`LOG-GEMINI.md`, `LOG-CODEX.md`).

---

## 9. Dónde leer más

- Reglas comunes: `AGENTS.md` (§5-bis, §5-ter, §6a, §6-ter) y `docs/00-estado/PROTOCOLO-CONTINUIDAD.md`.
- Diseño: `docs/03-diseno-ui/design-system/DESIGN-RULES.md` (§8-bis y checklist §9).
- Permisos: `docs/02-contratos/PERMISOS-ROLES.md`. Contratos de cada área: `docs/02-contratos/`.
- Tablero: `docs/00-estado/TABLERO.html` (T14 es esta prueba).
- Servidor y sitios: `docs/04-tecnico/SERVIDOR.md`.

---

## 10. Reinicio del Día 4

1. Leé la continuidad y los días 1 a 3; conservá esa historia y las cuentas existentes.
2. Reescribí el martes 13/10 como una jornada con propósito, pagos y clases, errores humanos y
   comprobaciones por pantalla. La selección de situaciones responde a la cobertura funcional.
3. No conserves las 18 filas anteriores como obligación. Mantené una jornada manejable; distribuí
   el resto hasta el 2/11 y dejá visibles los objetivos que aún no tienen día asignado.
4. Entregá cuento, instrucciones humanas y objetivos funcionales. Resultados de ejecución vacíos.
5. Registrá el trabajo en tu bitácora, commit y push de tus cambios documentales. No ejecutes,
   cambies reloj/datos/producto ni despliegues por esta redacción.

La preparación del entorno, conciliación basal, procesos automáticos y mediciones técnicas pertenecen
a este chat. Mora en 0% y avisos reales requieren resolver su preparación cuando corresponda;
no se modifican por escribir el cuento. Preparar o ejecutar una jornada no autoriza producción.
