# Verificación T61 — Transferir entre cuentas propias (Claude CAB, 20/10/2026)

Entrega de Codex: `c9b68d2` → `43a7097` (transferencia única) → `f089768` (FIN-09).
Informes: `IMPLEMENTACION-T61.md`, `VERIFICACION-T61-CYE.md`. Visual aprobado por Carlos.

## Veredicto: **cerrada el 21/10** tras la segunda vuelta (ver al final). Primera vuelta: devuelta por H1–H3

La transferencia en sí está bien. El problema es lo que quedó de la primera versión: la
clasificación TRASPASO sigue ofrecida en el formulario de **cualquier** subrubro, y con eso se
puede cargar un gasto o un ingreso de **una sola pata** que no cuenta en el resultado del club.

## Lo verificado y correcto

| Punto | Cómo | Resultado |
|---|---|---|
| Un envío guarda salida y entrada juntas, con cabecera | Control propio 1: $300.000 Efectivo → Banco por la ruta real | 2 movimientos (−300.000 / +300.000), `REF_TRANSFERENCIA`, clasificación TRASPASO |
| Doble envío no duplica | Mismo control, segundo POST con la misma clave | Sigue habiendo 2 movimientos |
| El resultado del club no cambia; los saldos sí | `ReporteMensualService` antes/después | Ingresos, egresos, resultado, gastos, aportes, retiros y disponible iguales; Efectivo −$300.000, Banco +$300.000 |
| Las patas no se corrigen sueltas | `puedeCorregirse()` | Falso en las dos |
| Servicio | Lectura de `TransferenciaCashflowService` | Admin activo, cuentas distintas y activas, candado en orden estable, clave única, transacción |
| Alta manual en Cashflow | Lectura de `CashflowService::registrarMovimientoAdmin` | Rechaza cualquier subrubro TRASPASO («Usá Transferir») |
| Pruebas de Codex | `TraspasosEntreMediosT61Test` + T16 + Catálogos en `wings_testing_claude` | 19 aprobadas, 201 aserciones |

## Hallazgos (reproducidos)

Controles en `evidencia-verificacion-t61-claude/VerificacionT61ClaudeTest.php` (4/4; los tres
«hallazgo» registran lo que pasa hoy).

**H1 — El dueño puede crear un subrubro común clasificado TRASPASO.**
`ClasificacionSubrubros::permitidas()` incluye TRASPASO para ingresos y egresos, y
`SubrubroWebController` (alta y edición) y el formulario la ofrecen en cualquier rubro.
Reproducido: POST a `web.subrubros.store` en «Gastos Operativos» con TRASPASO y permitido
OPERATIVO → se crea, no reservado. Tras el rediseño (transferencia del sistema con catálogo
reservado), esa opción ya no tiene uso legítimo.

**H2 — Con ese subrubro, la operativa carga un gasto que no cuenta.**
`CajaService::validarSubrubroManual` solo rechaza reservados. Reproducido: la operativa
registra en su caja un egreso de $50.000 con ese subrubro; Reportes no cambia egresos ni resultado.
Es una pata sola: la plata sale del cajón y el gasto no aparece.

**H3 — Corregir un gasto real pasándolo a ese subrubro lo hace desaparecer.**
`CashflowCorreccionService::corregir` solo rechaza reservados. Reproducido: un gasto directo de
$40.000 en «Limpieza» corregido al subrubro TRASPASO → se acepta; egresos del mes pasan de
$40.000 a $0 (`correccion-a-traspaso.json`).

**Alcanzable:** sí, desde pantallas reales (Rubros → Subrubro, la Caja y Cashflow → Editar).
Requiere que el dueño elija «Traspaso entre medios (no cuenta en el resultado)» en un subrubro.

**Corrección sugerida (Codex decide cómo):** TRASPASO solo para los dos subrubros reservados de
transferencias. Sacarla de `permitidas()` para formularios de subrubro (alta y edición), y
rechazarla también en la caja operativa y en la corrección de Cashflow, para cubrir datos ya
existentes. Revisar si en alguna base ya hay subrubros no reservados con TRASPASO (la primera
versión de T61 los permitía) y qué hacer con ellos: decisión de Carlos.

## Observaciones (no bloquean)

- La pantalla Cashflow suma las transferencias en sus totales del período (en el recorrido de
  Codex: ingresos $320.000 y egresos $307.000 con $300.000 de transferencia). Es el libro, no el
  resultado, y Carlos aprobó esa pantalla; lo anoto por si confunde.
- FIN-09: el chequeo de «ya existía» para no repetir el aviso al dueño está fuera de la
  transacción; dos envíos simultáneos de la misma transferencia vieja podrían avisar dos veces.
  Inferido leyendo el código, no reproducido.
- `down()` de `crear_transferencias_cashflow` no hace nada: un rollback seguido de migrate
  fallaría porque la tabla ya existe. Es deliberado (no borrar historia); conviene que quede dicho
  en el contrato.

## Qué no verifiqué

- No repetí el recorrido en navegador (aprobado por Carlos con capturas de Codex).
- No probé concurrencia real con dos conexiones.
- No corrí la suite completa: no cambia código mío.

## Segunda vuelta — verificada y cerrada (21/10/2026)

Codex, rama `codex/t61-segunda-vuelta` (`a5d5cc4`, `9c395bb`), integrada a `main` por Claude con
autorización de Carlos («Integrala»). Visual aprobado por Carlos: «La parte visual está OK».

**Qué cambió:** `ClasificacionSubrubros::permitidas()` ya no ofrece TRASPASO; el nuevo
`exigirSinTraspaso()` frena un subrubro (o un movimiento ya registrado) TRASPASO en la caja de la
operativa (alta y edición), la corrección de Cashflow, el alta manual de Cashflow, el reflejo de la
caja validada, el pago de liquidaciones, el cobro de cuotas e inscripción y la anulación de cobros.
El formulario de subrubro no muestra la opción ni la explicación. La transferencia no cambió.

**Cómo lo verifiqué:** mis mismos controles, sin cambiarlos, corridos sobre el código de la rama en
`wings_testing_claude` (sin otra suite activa):

| Control | Primera vuelta | Segunda vuelta |
|---|---|---|
| Camino feliz $300.000 | Pasa | Pasa |
| H1: crear subrubro común TRASPASO | Se creaba | **Rechazado** (el control queda en rojo, como se esperaba) |
| H2: la operativa carga $50.000 con ese subrubro | Se guardaba y no contaba | **Rechazado**, sin movimiento |
| H3: corregir un gasto de $40.000 a ese subrubro | Egresos $40.000 → $0 | **Rechazado** («Usá Transferir…»); egresos siguen en $40.000 (`segunda-vuelta/correccion-a-traspaso.json`) |

Pruebas de Codex: `TraspasosEntreMediosT61Test` 16/16 (incluidas sus tres regresiones H1–H3), más
T16 y Catálogos: 22 en verde.

**Datos que ya existían:** el inventario de Codex fue sobre su base descartable (vacía). Según los
registros de despliegue, ninguna versión de T61 llegó al sitio de prueba ni a producción, así que
no debería haber subrubros comunes TRASPASO fuera de bases locales. Inferido de esos registros, no
comprobado en el servidor. Si apareciera alguno, desde ahora no se puede usar y validar una caja
que lo tenga se rechazaría: qué hacer con él lo decide Carlos.
