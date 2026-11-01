# T104: «Sin descubierto» solo se controla al pagar una liquidación — Claude CAB, 01/11/2026

**El problema es real, y ya estaba decidido.** Se cierra T104 como repetida de T60.

| Qué | Cómo se sabe |
|---|---|
| Un gasto de Cashflow deja en −$4.000 una cuenta «Sin descubierto» | Ejecutado: [`ControlT104Test.php`](ControlT104Test.php), en `wings_testing_claude` |
| Una transferencia, lo mismo | Ejecutado: la misma prueba |
| Pagar una liquidación sin saldo se rechaza | Leído: `LiquidacionWebController.php:439`, la única lectura de `permite_descubierto` para frenar un egreso |
| Los contratos no lo definen | Búsqueda en `docs/02-contratos`: «descubierto» no aparece |
| **Carlos ya lo decidió el 13/10** | [DU-28](../tabla-de-pruebas/dudas/DU-28.json): «Avisar y dejar seguir. El tipo de caja dice si admite o no admite negativos.» Caso CSH-016 |
| Está anotado para después de producción | T60, «[DESPUES, Carlos 18/10]» |
| No hay cuentas en negativo en el sitio de prueba | Consulta de solo lectura, 01/11, con el reloj en el 25/11: las cinco en positivo o en cero |

**Por qué no frena producción:** no se pierde plata ni se rompe un dato: el saldo negativo se ve, y el manual `administrar-tipos-caja` ya dice que hoy solo se controla al pagar una liquidación.

**Queda para T60:** el interruptor `permite_descubierto` ya existe, así que no hace falta la migración que anotaba T60. Tampoco se decidió qué pasa con la plata que vuelve sola (anular una transferencia, validar una caja): esos caminos no pueden frenarse sin dejar un error sin corregir.
