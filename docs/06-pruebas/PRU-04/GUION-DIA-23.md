# Prueba por días: día 23 (domingo 1 de noviembre de 2026), generación de noviembre y liquidaciones de octubre

Armado por Claude el 27/10/2026. Solo el dueño; no hay clases ni caja.

**Cómo se sabe si el generador hace lo correcto:** la lista de lo que tiene que generar se calculó aparte,
con las reglas del contrato y consultas propias a la base, sin usar el código del generador
([`generacion-noviembre/esperado-generacion.php`](generacion-noviembre/esperado-generacion.php), resultado en
[`esperado.json`](generacion-noviembre/esperado.json)). Después de que corra, un comparador marca alumno por
alumno qué coincide, qué sobra, qué falta y qué importe es distinto. Lo mismo con las liquidaciones
([`esperado-liquidaciones.json`](generacion-noviembre/esperado-liquidaciones.json)).

**Punto de partida** (cierre del día 22, controles internos 16/16): deuda $0; 103 activos Al día;
disponible $4.959.900,75 (Efectivo $1.617.300, MP $2.638.550,75, Galicia $576.050, Nación $128.000);
ninguna liquidación. T84 ya está en el sitio (interruptor en la ficha). Respaldo de la base antes de este día:
`/root/respaldos/wingstest-antes-generacion-noviembre-2026-10-27.sql.gz`.

**Autorización de Carlos (27/10/2026):** generar la deuda de noviembre en el sitio de prueba, correrla una
segunda vez, generar y cerrar las liquidaciones de octubre y septiembre **sin pagarlas**.

## Lo que tiene que generar (calculado aparte)

**100 cuotas de noviembre, $3.783.000.** Nadie va a Revisión: todos los activos vinieron en octubre.
3 alumnos ya tienen noviembre pagado por adelantado y no reciben otra cuota: Felipe Sosa, Agustín Acosta y
Julieta Morales. Julieta Álvarez y Santino Romero están inactivos: no reciben nada.

| Grupo | Plan | Cuotas | Importe |
|---|---|---|---|
| Fútbol Avanzadas | 1 vez | 6 | $38.000 |
| Fútbol Avanzadas | 2 veces | 8 | $48.000 |
| Fútbol Principiantes | 1 vez | 14 | $28.000 |
| Fútbol Principiantes | 2 veces | 7 | $35.000 |
| Patín Avanzadas | 1 vez | 4 | $35.000 |
| Patín Avanzadas | 2 veces | 8 | $45.000 |
| Patín Federadas | 2 veces | 8 | $50.000 |
| Patín Intermedias | 1 vez | 9 | $33.000 |
| Patín Intermedias | 2 veces | 9 | $43.000 |
| Patín Principiantes | 1 vez | 17 | $30.000 |
| Patín Principiantes | 2 veces | 10 | **$44.000** (el aumento rige desde noviembre) |

Casos a mirar: **Lucas Gómez** $38.000 (su plan nuevo rige desde noviembre); **Mora Benítez** dos cuotas, Patín
$44.000 y Fútbol $28.000; **Valentín Ortega** $35.000; **Helena Iglesias** $50.000 (2 veces).

## Lo que tienen que dar las liquidaciones (calculado aparte)

| Profesor | Octubre | Septiembre |
|---|---|---|
| Lucía Gaitán, $12.000 la hora | 17 clases, **$204.000** | 3 clases, **$36.000** |
| Verónica Salinas, $15.000 | 7 clases, **$105.000** | 2 clases, **$30.000** |
| Mariela Ocampo, $18.000 | 15 clases, **$270.000** | 3 clases, **$54.000** |
| Hernán Quintana, comisión 40% | **$484.680** sobre $1.211.700 cobrados a 28 alumnos; $128.400 son de 9 cuotas atrasadas | — |
| Martín Acosta, comisión 30% | **$186.600** sobre $622.000 cobrados a 11 alumnos; $46.200 son de 4 cuotas atrasadas | — |

Las de septiembre son las clases que el dueño validó a mano el día 10 y nunca se liquidaron. Vanina no tiene
nada que liquidar.

## Cómo se trabaja

- Reloj **una vez**, a las **05:55**: `/usr/bin/php82 artisan wings:fecha-simulada "2026-11-01 05:55"`.
- La generación **no se corre a mano la primera vez**: la corre sola el programador del sitio a las 06:00, como
  en producción. Se espera hasta las 06:02 del reloj de Wings.
- El comparador se corre en el servidor como `wingstest`, desde la copia del repositorio:
  `php comparar-generacion.php /home/wingstest/app esperado.json`.
- Importes sin punto de miles. Las diferencias se anotan y no se reparan. **Ninguna liquidación se paga.**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 23.1 | — | Reloj a las 05:55. **Antes de las 06:00**, foto del antes: Cobranza ($0) y `generaciones_cuotas` sin noviembre. A las 06:02, mirar `storage/logs/scheduler.log` y la tabla. | La generación corrió sola una vez y noviembre figura generado (CBZ-011). **Si a las 06:02 no corrió:** anotarlo como falla del camino automático, con el log, y correrla a mano una vez (`php artisan cobranza:generar-deudas`) para seguir. |
| 23.2 | — | Correr el comparador. | **Coinciden 100 de 100**, total $3.783.000; sobran 0, faltan 0, importe distinto 0; duplicadas 0; Revisión 0. Copiar la salida entera al informe. |
| 23.3 | — | Correr la generación **otra vez a mano**: `php artisan cobranza:generar-deudas`. Después, el comparador de nuevo. | No crea nada: el mismo resultado que en 23.2, sin duplicadas (CBZ-012). |
| 23.4 | Dueño | Cobranza. | Total adeudado **$3.783.000**; 103 activos: **100 En plazo y 3 Al día**. |
| 23.5 | Dueño | Fichas de **Alma Herrera**, **Lucas Gómez**, **Mora Benítez** y **Felipe Sosa**. | Alma: noviembre $44.000. Lucas: $38.000. Mora: una cuota en cada deporte, $44.000 y $28.000. Felipe: noviembre pagado, sin una segunda cuota. |
| 23.6 | Dueño | Liquidaciones → Nueva → **Lucía, octubre**. Antes de generar, anotar cuántas clases dice la pantalla. Generar. | La pantalla puede decir 20 (defecto T87, ya conocido: suma septiembre); **generada: 17 clases, $204.000** (LIQ-001). |
| 23.7 | Dueño | **Cerrar** la de Lucía de octubre. | Ahora sí cierra: el mes terminó (LIQ-005, LIQ-008). Queda Cerrada y sin pagar. |
| 23.8 | Dueño | Generar y cerrar **Lucía septiembre**, **Verónica** octubre y septiembre, **Mariela** octubre y septiembre. | Los importes de la tabla de arriba, cada una Cerrada y sin pagar. |
| 23.9 | Dueño | **Hernán, octubre**: generar. Revisar las cuotas atrasadas y **validarlas todas** (son alumnos que vinieron a sus clases). Cerrar. | Cálculo **$484.680**; las 9 atrasadas suman $128.400; validadas todas, el confirmado es $484.680. Sin decidir las atrasadas no deja cerrar (LIQ-002, T43). |
| 23.10 | Dueño | **Martín, octubre**: igual que Hernán. | **$186.600**; 4 atrasadas, $46.200 (LIQ-002). |
| 23.11 | Dueño | Listado de Liquidaciones, Inicio y Reportes de octubre. **No pagar nada.** | 8 liquidaciones Cerradas sin pagar. **Por pagar $1.370.280** en el listado, en el Inicio y en Reportes, iguales entre sí (LIQ-018). Disponible sin cambios, $4.959.900,75. |
| 23.12 | — | Si el reloj de Wings pasa las 08:00 mientras se trabaja: ¿llegó el resumen diario por Telegram? | Opcional: anotarlo si se ve. |

**Lo que el Inicio todavía no puede mostrar como deuda:** el sueldo de las operativas (no se puede ni
registrar, T86) y el alquiler de las canchas (solo existe cuando se paga). Queda anotado para Carlos.

**Corrección posterior (Claude, 27/10):** los ingresos de noviembre no son $0 sino **$103.000**, los tres
noviembres pagados por adelantado (T42). Y el precio de $44.000 rige desde el 18/10: noviembre es solo la
primera cuota nueva de esas alumnas. Detalle en la [revisión](ESTADO-PRUEBA.md).

**Cierre del día 23:**
- Deuda de noviembre **$3.783.000**; 103 activos: 100 En plazo, 3 Al día. Revisión vacía.
- Disponible **$4.959.900,75**, igual que el sábado; ingresos de noviembre $0.
- **Por pagar $1.370.280** (8 liquidaciones cerradas).
- Casos nuevos: CBZ-011, CBZ-012, LIQ-001, LIQ-002, LIQ-005, LIQ-008, LIQ-018.

## Entrega

- `DIA-23.md` con la salida del comparador (dos veces), del log del programador y de la segunda generación.
- Evidencia en `evidencia-dia-23/`. Entrada en `LOG-CODEX.md` y tablero T14 con `tiene=Claude`.
- Commit solo de lo tuyo, agregando por nombre, sin push.
