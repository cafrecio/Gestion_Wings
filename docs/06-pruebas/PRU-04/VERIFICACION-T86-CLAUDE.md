# Verificación de T86 (Codex) — Claude CyE, 27/10/2026

**Lógica: APROBADA.** Falta el visto bueno de Carlos del aspecto de Reportes → Sueldos
([galería](APROBAR-T86-SUELDOS.html)). Entrega de Codex: [`IMPLEMENTACION-T86.md`](IMPLEMENTACION-T86.md),
commits `13c4ee9d`, `f257fd6f` y `fb00e170`.

| Qué | Cómo se comprobó | Resultado |
|---|---|---|
| El sueldo de una operativa nace sin reserva; el de profesoras sigue reservado | Código de `SubrubroSueldoService` (`crearOReutilizar` recibe la reserva) | Bien |
| La migración cambia solo los sueldos de operativas | Misma consulta, en solo lectura, sobre la base del sitio de prueba | Cambiaría exactamente Op-Sandra, Op-Pablo y Op-Rocío (21, 22, 31). Ningún renglón de profesoras |
| El dueño paga por Cashflow; los docentes siguen ocultos y rechazados forzando | Pruebas de Codex corridas en `wings_testing_claude` | 32 de 32 |
| **La operativa no se carga su sueldo desde la caja**, ahora que no está reservado | Prueba propia forzando el envío (`SeguridadDiasPruebaTest`) | Rechazado: la caja exige renglones de operativa que afecten caja; nada cambia. Control: el dueño sí lo paga por Cashflow |
| Hallazgo del día 22.5 | La prueba que lo retrataba se dio vuelta | Ahora Cashflow lo ofrece |
| Reportes → Sueldos | Código del servicio y de la vista | Total y filas por persona, sin reparto por deporte; correcciones compensadas, anulados fuera. Usa clases existentes, sin CSS ni JavaScript nuevos |

Seguridad de la prueba por días: 23 de 23.

**Observación (no bloquea):** el bloque se llama «Pagos de operativas», pero toma a cualquier usuario con renglón
de sueldo vinculado. Si en el futuro un dueño tiene el suyo, aparecería ahí. Codex dejó anotado para Carlos qué
pasa con los cambios de rol; no hay una regla decidida.
