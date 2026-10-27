# T86 — Cierre verificado, 27/10/2026

Codex CAB registra dos verificaciones distintas, sin modificar producto ni desplegar:

- Lógica: **Claude**, informe [VERIFICACION-T86-CLAUDE.md](../VERIFICACION-T86-CLAUDE.md), commit `ea07b6ae`. Incluye 32 pruebas de la entrega, seguridad 23/23 y lectura del servicio y la vista. El informe identifica `13c4ee9d`, `f257fd6f` y `fb00e170`.
- Aspecto: **Carlos**, respuesta directa en este chat del 27/10/2026: **«Las imagenes estan OK»**. Aprueba la [galería real 1366/375](../APROBAR-T86-SUELDOS.html). Se aplica AGENTS.md §6a: lo visual aprobado por Carlos no requiere otra IA.

Se comprobaron las nueve huellas de fuentes de la entrega contra el checkout actual: ninguna cambió. No se vuelven a correr pruebas por registrar esta aprobación documental. El cierre ya consta en el commit concurrente `f5306953`; se conserva su contenido. El tablero queda `cerrado`, `hizo=Codex`, `verifica=Claude`, `tiene=nadie`; la aprobación visual de Carlos queda aquí y en la entrega.

La duda no bloqueante sobre reservas antiguas al cambiar de rol sigue para Carlos, sin adoptar una política nueva. El cierre no afirma que T86 esté desplegada: no hubo despliegue en esta actuación.

## Uso

Con usuario ADMIN: Cashflow → Nuevo → Egreso → rubro Sueldos → subrubro de la operativa. Completar fecha, medio de pago, monto positivo y observaciones; pulsar Registrar. El sistema guarda el egreso con su signo correspondiente.

En Reportes → Sueldos, elegir el mes y pulsar Filtrar: «Pagos de operativas» muestra total, importe y cantidad de pagos por persona. Es gasto del club, sin reparto por deporte; una anulación compensa el importe. Las profesoras continúan por Liquidaciones.
