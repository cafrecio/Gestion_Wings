# Prueba por días: días 17 y 18 (lunes 26 y martes 27 de octubre de 2026)

Armado por Claude el 26/10/2026. El domingo 25 ya se recorrió como día 16.

**Punto de partida:** el sitio al cierre del día 16, tal como quedó, sin reparar
([revisión](ESTADO-PRUEBA.md)). Los números salen de las pantallas que guardó Codex
(`evidencia-dia-16/16.10-*.txt`):
- Deuda $391.000; 104 activos: 99 Al día, 1 Moroso (Joaquín Vega $44.000) y 4 Deudores: Alma Navarro
  $58.000, Santino Romero $84.000 (agosto a octubre, $28.000 cada una), Berenice Sánchez $129.000
  y Felipe Sosa $76.000.
- Ingresos de octubre $1.824.850,75; egresos $240.850.
- Disponible $3.337.000,75: Efectivo $1.182.900, MP $1.888.550,75, Galicia $137.550, Nación $128.000.
- «Sin asistencia»: 1, la #76 del sábado.
- Julieta Álvarez sigue **inactiva** con $143.000 (13.11 no se hizo).

**Lo que vuelve de la semana pasada, ahora bien planteado:** Julieta (13.11), Rocío con clave conocida
(15.2), Martín **por comisión**, porque Fútbol liquida así (15.3–15.5), la #76 sin lista y Botiquín visto
por la operativa (16.9).

**Autorización de Carlos (25/10/2026):** todas las operaciones de plata de este guion en
test.gestionar-te.com.ar (base `wingstest`, datos ficticios), incluidos cambios de clave de usuarios de
prueba, altas de profesores y usuarios y la corrida de los scripts de rutina. No hace falta volver a preguntar.

## Cómo se trabaja

Igual que en los días 13 a 16 ([guion](GUION-DIAS-13-16.md#cómo-se-trabaja)): reloj y rutina por consola
en el servidor, una sola vez cada uno; lo de las tablas por pantalla, como una persona; importes sin
punto de miles; mirar el total antes de confirmar; las diferencias se anotan y no se reparan.

```
/usr/bin/php82 artisan wings:fecha-simulada "2026-10-26 15:00"
/usr/bin/php82 scripts/pruebas/rutina/rutina.php docs/06-pruebas/PRU-04/rutina/dia-17-manana.json
```

**Novedad del script:** las listas ahora se piden por grupo y hora («Patín — Principiantes», 16:00), no
por número. Si dice «hay 0 hoy» o «hay 2 hoy», no guarda nada: se anota y se sigue por pantalla.

**Si la confirmación nativa de Chrome vuelve a fallar** (pasó con Julieta), se reintenta una vez. Si falla
otra vez, se anota «no probado por la herramienta» y **no se hace por consola**.

## Día 17: lunes 26/10, el turno de Rocío

**Script de la mañana** (`dia-17-manana.json`), **después del 17.1**, porque Rocío tiene que poder entrar:
- Rocío abre la caja; hereda los $10.000 que dejó el dueño el domingo.
- Limpieza, $3.800 en efectivo.
- Listas del lunes: Patín Principiantes 16–17 (Lucía; falta Zoe Flores), Fútbol Principiantes 16–17
  (Hernán; falta Santino Romero) y Patín Intermedias 17–18 (Verónica; faltan Alma Navarro y Berenice
  Sánchez).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 17.1 | Dueño | Rocío no recuerda su clave. En **Usuarios**, edita a Rocío Paz y le pone **Wings-Rocio-2026**, dos veces. Antes prueba con «corta». | «corta» no la acepta: pide al menos 12 caracteres. Con la buena guarda. Rocío entra con **Wings-Rocio-2026** y ve el menú de la operativa (USU-005). |
| 17.2 | Rocío | La mamá de **Santino Romero** trae **$56.000** en efectivo: «lo que puedo ahora». Rocío elige agosto y septiembre. | Antes de confirmar, total $56.000. Quedan **octubre $28.000**. Santino pasa de Deudor a **Moroso** (COB-006). |
| 17.3 | Rocío | Compra gasas y alcohol: gasto **Botiquín**, $6.500 en efectivo, «Gasas y alcohol». | **Botiquín** aparece en su lista de gastos, creado por el dueño el domingo, y se guarda (CAT-007). |
| 17.4 | Dueño | Alta de profesor: **Martín Acosta**, Fútbol, **comisión 30%**. | Queda activo. En Rubros → Sueldos aparece «Fútbol-Martín Acosta» (PRO-001). |
| 17.5 | Dueño | Le crea a Martín su usuario de **profesor**, vinculado a Martín Acosta, mail martin.acosta@example.com, clave **Wings-Martin-2026**. | Queda activo con rol Profesor. Martín entra y ve solo Clases (USU-003). |
| 17.6 | Hernán | Carga la lista de la **#76**, del sábado 24/10, que quedó sin tomar: presentes **Lucas Gómez** y **Tomás Giménez**, el resto ausente. | **No pide motivo:** la clase nunca tuvo lista, así que es una primera carga. Guarda. «Sin asistencia» baja. |
| 17.7 | Dueño | **Julieta Álvarez** vuelve: la reactiva desde su ficha y le cobra los **$143.000** por Mercado Pago, sin caja. | Vuelve con su deuda tal cual, sin una cuota de octubre nueva. Con el cobro: recibo de cinco meses y **Al día** (ALU-018). |

**Script de la noche** (`dia-17-noche.json`): Rocío cierra con el esperado, **$55.700**
(10.000 + 56.000 − 3.800 − 6.500), deja $10.000 y el dueño valida.

**Cierre del día 17:**
- Deuda **$335.000**; **105 activos: 100 Al día, 0 En plazo, 2 Morosos (Joaquín y Santino), 3 Deudores**.
- Ingresos de octubre **$2.023.850,75**; egresos **$251.150**; resultado **$1.772.700,75**.
- Disponible **$3.525.700,75**: Efectivo $1.228.600, MP $2.031.550,75, Galicia $137.550, Nación $128.000.
- «Sin asistencia» **0**. Por validar $0.

## Día 18: martes 27/10, el profesor nuevo y el turno de Pablo

**Script de la mañana** (`dia-18-manana.json`):
- Pablo abre la caja; hereda $10.000.
- Listas: Patín Avanzadas 17–18 (Lucía; todas presentes) y Patín Federadas 18–19 (Mariela; falta Helena
  Iglesias).
- Fútbol Avanzadas 19–20 **no** va en el script: la toma Martín en el 18.4.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 18.1 | Rocío | Pasa a mediodía a cubrir y quiere **abrir caja**. | «Hay un turno abierto. Cerralo antes de abrir otro.» Muestra el turno de Pablo. No puede cobrar (CAJ-009). |
| 18.2 | Pablo | **Alma Navarro** paga todo: **$58.000** por Mercado Pago. | Total $58.000 antes de confirmar. Recibo con sus dos cuotas. Pasa a **Al día**. |
| 18.3 | Pablo | **Berenice Sánchez** paga **$43.000** en efectivo, una cuota. | Se imputa a la **más vieja, agosto**. Quedan septiembre y octubre, $86.000: sigue **Deudor**. |
| 18.4 | Dueño | Hernán avisa que no viene: en la clase de hoy **Fútbol Avanzadas 19–20** cambia a Hernán por **Martín Acosta**. | Solo ofrece profesores de Fútbol. **No pide motivo**, porque la clase todavía no pasó. Queda Martín (CLA-009). |
| 18.5 | Martín | Entra y toma lista de esa clase: **15 alumnos**; ausente **Felipe Sosa**. | La ve en sus clases. **14 presentes**. Guarda (ASI-001). |
| 18.6 | Hernán | Entra y busca la clase de Fútbol Avanzadas de hoy. | Ya no figura a su cargo. |

**Script de la noche** (`dia-18-noche.json`): Pablo cierra con el esperado, **$53.000** (10.000 + 43.000),
deja $10.000 y el dueño valida.

**Cierre del día 18:**
- Deuda **$234.000**; **105 activos: 101 Al día, 0 En plazo, 2 Morosos, 2 Deudores (Berenice Sánchez y
  Felipe)**.
- Ingresos de octubre **$2.124.850,75**; egresos **$251.150**; resultado **$1.873.700,75**.
- Disponible **$3.626.700,75**: Efectivo $1.271.600, MP $2.089.550,75, Galicia $137.550, Nación $128.000.
- «Sin asistencia» **0**. Por validar $0.

**Si 17.7 vuelve a fallar:** en los cierres de los dos días, restar $143.000 de ingresos, resultado, MP y
disponible, y una alumna activa Al día. Es la misma diferencia de la semana pasada, no una nueva.

## Entrega

- `DIA-17.md` y `DIA-18.md`, con lo que imprimió cada script.
- Evidencia en `evidencia-dia-17/` y `evidencia-dia-18/`.
- Entrada en `LOG-CODEX.md`.
- **Commit, solo de lo tuyo:**
  - agregar por nombre, nunca `git add -A`;
  - si un archivo tuyo tiene cambios de otro agente, frenar y avisar;
  - sin push.
