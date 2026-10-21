# Prueba por días (PRU-04) — instrucciones para Gemini, desde el día 4

Escrito por Claude CyE el 21/10/2026, a pedido de Carlos. No es un prompt: es lo que necesitás saber
para escribir los cuentos de la prueba sin repetir lo que ya se hizo ni lo que ya salió mal.
Leelo entero antes de empezar. Si algo no se entiende o falta, preguntale a Carlos antes de actuar.

---

## 1. Para qué es esta prueba

Wings es el sistema de gestión del club (alumnos, cuotas, cobros, caja, cashflow, clases, asistencia,
liquidaciones de profesoras y reportes). Carlos quiere **subir a producción la versión actual**. Antes,
hay que comprobar que **los caminos felices** funcionan de punta a punta, como los va a usar el club, y
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

Los casos raros y los errores de uso quedan para después (T76, plan de mejora).

---

## 2. Quién hace qué

| Quién | Qué hace en esta etapa |
|---|---|
| **Carlos** | Dueño del producto. Decide. Aprueba lo visual. Resuelve con vos los puntos abiertos (§10). |
| **Gemini (vos)** | 1) Recorre el sistema y evalúa los casos de prueba (§6). 2) Escribe los cuentos día por día y el documento de objetivos (§7). **No ejecuta los días.** |
| **Codex** | Ejecuta cada día en el sitio de prueba, siguiendo tu cuento, y anota lo que pasó. |
| **Verificador** | Controla cada día contra la base y el código. A definir por Carlos (puede ser otra cuenta de Codex). |
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
hallazgos clasificados (Frena / Molesta / Detalle) con captura, y al final la verificación contra la base.

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

## 4. El sitio de prueba al empezar

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
  se disparan, y verificá que hicieron lo que debían; no lo supongas.

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
7. **Fin de mes (31/10):** todas las cajas validadas; Inicio, Cobranza, Reportes y Sueldos cuentan lo mismo.
8. **1/11:** se generan las cuotas de noviembre una sola vez; el que pagó noviembre por adelantado no recibe
   otra; el mes queda anotado como generado (T40). El resumen diario lo refleja.
9. **2/11:** liquidación de octubre de cada profesora, por hora y por comisión. Lo adelantado de noviembre no
   entra en octubre (T42); las cuotas atrasadas aparecen con aviso y el dueño decide (T43). Cerrar, pagar y
   sacar el recibo.

Repartí estos puntos en el calendario como haría el club: los días con clases salen del cronograma de clases
del sistema (el domingo 11/10 no hubo), y la gracia de cobranza vence pasado el día 10 de cada mes. Cada día corto puede repetir lo de los días anteriores con otros alumnos.

---

## 6. Antes de los cuentos: recorrido y evaluación de los casos

Pedido de Carlos: antes de escribir los cuentos, recorré todo el sistema y evaluá **todos los casos de la
tabla** contra la versión desplegada. Así los cuentos se apoyan en lo que el sistema hace hoy, no en lo
que hacía el 16/10. **El alcance y la forma los definís con Carlos** (§10).

Lo mínimo que tiene que salir: para cada caso, si **sigue igual**, **cambió**, **ya está hecho** o **quedó
viejo**, con qué tarea cambió; y la lista de casos que los cuentos tienen que cubrir. Lo que haya que
cambiar en la tabla va como propuesta escrita (caso, línea, texto actual, texto propuesto).

---

## 7. Qué tenés que escribir

### Los cuentos — `DIA-04.md`, `DIA-05.md`, …

Uno por día, con el mismo formato de los días 1 a 3. Vos escribís: el resumen esperado del día, las reglas
del día (reloj, quién entra) y la tabla con **Tarea** y **Esperado** completos. Codex completa **Lo que
pasó** y **¿Coincide?** al ejecutar.

- Las tareas cuentan lo que hacen las personas: «Sandra le cobra a Renata Cabrera octubre y noviembre en
  efectivo». No digan «probar T42».
- **Esperado** tiene que poder comprobarse con la pantalla: importes, estados, cantidades, dónde aparece.
- Al final de cada día, un **cierre del dueño**: Inicio, Cobranza, Caja/Cashflow y Reportes dicen lo mismo,
  y coincide con lo que se hizo en el día. Así se ve a dónde fue cada peso.
- Incluí siempre al menos una comprobación de rol: alguien intenta ver o hacer algo que no le corresponde
  (por ejemplo, una profesora y las clases ajenas, una operativa y Cashflow).

### Los objetivos — `OBJETIVOS-PRUEBA.md`

Un solo documento, con una sección por día y una fila por tarea del cuento. Para cada tarea, explícito:

| Campo | Qué poner |
|---|---|
| Módulo | Cobros, Caja, Clases, Liquidaciones… |
| Casos y tareas | Códigos de la tabla (`COB-012`) y tareas del tablero (`T42`) que ejercita |
| Usuario | Qué ve y qué hace la persona; qué no debe poder hacer |
| Visual | Pantallas que intervienen, en computadora y teléfono; §8-bis si decide fila por fila |
| Base de datos | Qué filas se crean o cambian y en qué tablas (por ejemplo `pagos`, `pago_deuda_cuota`, `deuda_cuotas`, `movimientos_operativos`, `cashflow_movimientos`, `asistencias`, `liquidaciones`) |
| Controladores | Qué rutas y controladores atienden la acción |
| Servicios | Qué servicios calculan (`PagoCuotaService`, `CajaService`, `LiquidacionService`…) |
| Seguridad | Roles que pueden y que no; protecciones que tienen que saltar |

Para el verificador, este documento es la lista de control: qué mirar en la base y en el código después de
cada día. Buscá en el código con `codebase-memory-mcp` primero y confirmá en el archivo (AGENTS.md §6e).

---

## 8. Reglas para escribir y ejecutar

- **Esperado antes de actuar.** Si el sistema hace otra cosa, se anota; no se corrige el esperado después.
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

## 10. Lo que Carlos resuelve con vos antes de empezar

1. **Recorrido previo de los casos (§6):** alcance y forma (¿contra el código, con clics, o los dos?).
2. **Registro de resultados que Codex y vos sí pueden escribir**, equivalente a la tabla, para que Claude
   lo pase después a la tabla viva.
3. **Producción:** si se despliega al terminar el camino feliz o después de revisar los hallazgos.
4. **Verificador de cada día** (§2).
5. **Recargo por mora en 0%:** con ese valor los recargos (B11) no se prueban. ¿Se pone un porcentaje
   para la prueba?
6. **Avisos reales:** el sitio de prueba tiene configurados el Telegram y el correo de Carlos. Con días
   simulados, el resumen diario y los avisos de cuotas le pueden llegar de verdad, uno por día simulado.
   ¿Se apagan durante la prueba o los quiere recibir?
