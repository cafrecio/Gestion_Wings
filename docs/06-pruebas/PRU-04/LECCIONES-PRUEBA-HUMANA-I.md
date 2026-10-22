# Prueba Humana I — Lecciones Aprendidas y Preparación del Día 4 (PRU-04)

> **Documento de enlace para Gemini en Oficina CyE.**  
> **Fecha:** 22/10/2026 (sesión iniciada en CAB).  
> **Objetivo:** Registrar las decisiones, el ida y vuelta con Carlos y Codex, y las lecciones aprendidas durante el diseño y ajuste del **Día 4 (martes 13/10/2026)** para continuar el trabajo desde CyE.

---

## 1. Contexto de la sesión y propósito

La sesión **Prueba humana I** tiene como meta estructurar y preparar la prueba funcional manual del sistema Wings simulando el uso cotidiano del club día por día, como si fuera una persona operando en mostrador y en la pista.

- **Filosofía del método:** Las pruebas automatizadas (incluso con suite de 1.000+ tests verdes) no detectan problemas de usabilidad, mensajes confusos en pantalla, botones desbordados o bloqueos por flujos cotidianos de usuarios reales.
- **Roles y responsabilidades acordadas:**
  - **Carlos:** Dueño de producto. Revisa, aprueba y decide sobre reglas de negocio y diseño.
  - **Gemini:** Diseña y estructura los casos, redacta el cuento (`DIA-xx.md`), define los objetivos técnicos de control (`OBJETIVOS-PRUEBA.md`) y documenta el seguimiento. **Gemini nunca ejecuta la prueba.**
  - **Codex:** Ejecuta las pruebas operando exclusivamente desde el navegador Chrome real como usuario humano en el chat *Prueba humana*.
  - **Verificador independiente:** Audita la base y el código tras cada jornada.

---

## 2. Requerimientos definidos para el Día 4 (Martes 13/10/2026)

Continuando el estado acumulado de los Días 1 a 3 (10/10, 11/10 y 12/10 feriado):
1. **Ambiente:** `https://test.gestionar-te.com.ar`, base descartable `wingstest`. Reloj simulado en Wings.
2. **Volumen de operaciones:** Entre 20 y 30 operaciones de negocio como máximo (se fijaron exactamente **18**).
3. **Casos a ejercitar naturalmente en el cuento:**
   - Apertura de caja heredando el cambio retenido del sábado ($10.000).
   - Alta de una alumna nueva al 13/10, con cuota al 100% e inscripción, calculando su plazo exacto de gracia.
   - Cobranzas variadas: cuota completa del mes, deuda anterior, pago parcial bajo regla FIFO, y medios múltiples (Efectivo, Mercado Pago, Banco Galicia).
   - Cobro adelantado de noviembre (`T18` / `T42` / `COB-012`), diferenciando recaudación en caja vs devengamiento económico en reportes.
   - Movimientos de mostrador: ingreso habitual (indumentaria) y egreso operativo permitido (limpieza).
   - Ingreso sin categoría adecuada: el operador no encuentra subrubro, frena sin improvisar, escala al dueño, y el dueño crea el subrubro en Catálogos (`T63`).
   - Asistencia: clase concluida del día y reducción del pasivo de asistencias históricas pendientes (`CLA-018`).
   - Control de permisos y roles por menús visibles.
   - Cierre de caja con diferencia $0 y validación del dueño con tildes obligatorios (`T36`, `T37`, `§8-bis`).
   - Conciliación final integral (Inicio, Cobranza, Cashflow y Reportes).

---

## 3. El ida y vuelta: observaciones de Codex y correcciones aplicadas

Tras la entrega del primer borrador, Codex analizó `DIA-04.md` y `OBJETIVOS-PRUEBA.md`, señalando 5 inconsistencias clave. Todas fueron investigadas y corregidas:

### Lección 1 — Comprobar los saldos iniciales históricos en Cashflow
- **Qué pasó:** El borrador inicial calculó el disponible final sumando solo los movimientos de los días 1 y 4 ($164.000 + $204.500 = $368.500), ignorando los saldos iniciales de los tipos de caja.
- **La corrección:** Se auditó `tipos_caja` en `wingstest`: Efectivo tiene `$250.000` y Mercado Pago `$1.320.000` de saldo inicial (`$1.570.000`). Con los `$164.000` del Día 1, el saldo previo era de `$1.734.000`. Al sumar los `$204.500` netos del Día 4, el disponible real y exacto en Cashflow es **`$1.938.500,00`**.
- **Regla aprendida:** Nunca calcular disponibles acumulados sumando únicamente las operaciones del relato; verificar siempre los saldos basales de `tipos_caja.saldo_inicial` y movimientos previos.

### Lección 2 — Mapear las transiciones individuales de estados de cobranza
- **Qué pasó:** El borrador contó los alumnos al día sumando a los que pagaron cuota completa, pero no descontó correctamente las transiciones entre `DEUDOR` y `MOROSO`.
- **La corrección:** En Wings:
  - `DEUDOR` = tiene deuda de meses *anteriores* al mes en curso.
  - `MOROSO` = tiene deuda únicamente del *mes corriente* fuera de la gracia general.
  - Al pagar septiembre, **Emma Fernández** y **Dante Ramos** cancelan su deuda anterior y solo les queda octubre pendiente. Por lo tanto, pasan de `Deudor` a `Moroso` (-2 Deudores, +2 Morosos).
  - **Renata Cabrera** y **Mateo Rodríguez** cancelan octubre y pasan de `Moroso` a `Al día` (-2 Morosos, +2 Al día).
  - **Mora Benítez** entra como alta nueva (`+1 En plazo`).
  - **Contadores finales auditados:** 64 Al día, 1 En plazo, 15 Morosos, 21 Deudores. Total: **101 alumnos activos**.
- **Regla aprendida:** Un pago no solo pasa a «Al día»; cuando cancela deuda histórica manteniendo la del mes, convierte al alumno de Deudor a Moroso. Las transiciones deben trazarse una por una.

### Lección 3 — Vincular a los casos exactos del catálogo JSON
- **Qué pasó:** Se habían citado casos como `ASI-007` (pensando que era asistencia histórica) y `COB-005` (pensando que era FIFO general).
- **La corrección:** Se leyeron los catálogos JSON en `docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/`:
  - `ASI-007` es en realidad: *«Alumno que viene más veces de las que paga»*. El caso exacto para asentar una clase vieja desde el filtro es **`CLA-018.b,c`** (*Cargar la asistencia de una clase muy vieja / pendiente en Sin asistencia*).
  - `COB-005` es: *«Primer cobro de un alumno nuevo: inscripción y cuota»*. Los casos exactos para pago parcial y aplicación a deudas anteriores son **`COB-008.a,b,c`** (*Pago parcial y actualización de saldo*), **`COB-009.a`** (*Motivo obligatorio*) y **`COB-006.a`** / **`COB-007.c`** (*Imputación prioritaria a la deuda más antigua*).
- **Regla aprendida:** Siempre contrastar los códigos de casos contra los títulos y criterios literales de los JSON del catálogo antes de asignarlos en los objetivos de prueba, evitando acreditaciones de cobertura falsas.

### Lección 4 — Método 100% humano (sin URLs internas y con detención estricta)
- **Qué pasó:** El borrador proponía tipear URLs internas como `/cashflow` para probar el error 403 y sugería «continuar» ante cualquier diferencia.
- **La corrección:**
  - En una prueba humana, los roles se evalúan **mirando la barra de navegación**: la operativa comprueba visualmente que no tiene accesos a Cashflow ni Liquidaciones; la profesora comprueba que su menú solo contiene Clases.
  - Condición de detención: Si la plata no cuadra (caja, mostrador o cashflow) o si el camino feliz queda bloqueado, **se detiene la prueba en el acto y se le avisa a Carlos**. No se avanza sobre datos inconsistentes.
- **Regla aprendida:** Un operador real navega por la interfaz gráfica, no por el omnibox de URL. La detención temprana protege la validez de los datos de días futuros.

### Lección 5 — Consistencia de la línea temporal del reloj Wings
- **Qué pasó:** Se proponía arrancar a las 17:00 hs con una clase programada de 17:00 a 18:00 hs, requiriendo tomar lista antes de que finalizara.
- **La corrección:** Se fijó el reloj al inicio en las **18:15 hs** (`2026-10-13 18:15:00`). A esa hora, la clase de 17:00 a 18:00 ya terminó naturalmente. Lucía puede registrar la asistencia de una clase ya ocurrida sin contradicción temporal y sin alterar el reloj.
- **Regla aprendida:** Elegir una hora de inicio posterior a la finalización de la clase evaluada del día para que el flujo sea completamente natural.

---

## 4. Archivos generados y disponibles en el repositorio

1. [docs/06-pruebas/PRU-04/DIA-04.md](DIA-04.md): Cuento del día 4 con las 18 operaciones, personas, datos, esperados completos y columnas de resultados en blanco para Codex.
2. [docs/06-pruebas/PRU-04/OBJETIVOS-PRUEBA.md](OBJETIVOS-PRUEBA.md): Matriz técnica con casos fuente exactos, INTs, tareas de tablero, pantallas, bases de datos y controladores.
3. [docs/06-pruebas/PRU-04/INSTRUCCIONES-EJECUTOR-DIA-04.md](INSTRUCCIONES-EJECUTOR-DIA-04.md): Guía operativa limpia para que Codex ejecute en Chrome con las condiciones de reloj, pausas anti-throttle de 60 segundos y criterio de detención.

---

## 5. Instrucciones para Gemini en CyE

Cuando abras esta tarea en la máquina **CyE**:
1. Leer este documento completo para comprender el estado y las decisiones tomadas.
2. Leer los tres archivos generados en `docs/06-pruebas/PRU-04/`.
3. Revisar el estado de los agentes en el tablero y bitácoras (`LOG-GEMINI.md`, `LOG-CODEX.md`).
4. Firmar las próximas entradas en la bitácora con la identidad obligatoria de oficina: **`LOG GEM CYE`**.
5. Esperar la instrucción de Carlos para coordinar la ejecución con Codex en el chat *Prueba humana*.
