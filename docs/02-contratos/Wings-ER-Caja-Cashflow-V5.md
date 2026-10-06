# ER Caja y Cashflow — V5, enmienda A25

**06/10/2026.v1 · Codex CAB.** Implementado; pendiente control ajeno, sin despliegue.
Complementa el [ER V4 histórico](Wings-ER-Caja-Cashflow-V4.md); no pretende certificar
los otros campos históricos de ese documento. [Reglas V5](Wings-Contrato-Caja-Cashflow-V5.md).

## Configuración del cajón: `caja_mostrador`

- `id`: unsigned tinyint PK, singleton 1. Fila de coordinación, no catálogo ni ingreso.
- `tipo_caja_id`: nullable FK tipos_caja, borrado restringido.
- `configurado_por_id`: nullable FK users, null al eliminar usuario.
- `configurado_at`: timestamp nullable.

El servicio bloquea esa fila en transacción para configurar una vez y para serializar
apertura/cierre. La migración deja el medio pendiente de elección por ADMIN.

## Nuevos campos de `cajas_operativas`

| Campo | Tipo y propósito |
|---|---|
| tipo_caja_efectivo_id | FK nullable tipos_caja, restrict delete; medio físico del turno |
| caja_origen_id | FK nullable a cajas_operativas, restrict delete; cierre heredado |
| efectivo_heredado | DECIMAL(12,2) nullable; importe propuesto del origen |
| efectivo_inicial | DECIMAL(12,2) nullable; lo recibido y confirmado |
| motivo_apertura | TEXT nullable; obligatorio si recibido difiere de heredado |
| efectivo_esperado | DECIMAL(12,2) nullable; resultado al cerrar |
| efectivo_contado | DECIMAL(12,2) nullable; declaración física |
| diferencia_efectivo | DECIMAL(12,2) nullable; contado menos esperado |
| cambio_retenido | DECIMAL(12,2) nullable; efectivo que queda |
| efectivo_retirado | DECIMAL(12,2) nullable; contado menos retenido |
| usuario_apertura_id | FK nullable users, null on delete; autor de la declaración |
| usuario_cierre_id | FK nullable users, null on delete; autor del conteo/cierre |

Nullable preserva desconocidos históricos sin inventar ceros. No se sustituye
`usuario_operativo_id` ni los campos administrativos anteriores.

## Integridad

No negativos para inicial, contado, retenido; retenido ≤ contado. Un solo ABIERTA
para el club, comprobado bajo bloqueo del singleton. Referencia de origen inmutable.
Conteo/entrega originales no se reescriben al corregir rechazadas. Sin nuevos asientos
en movimientos_operativos/cashflow por estas declaraciones.

Migración: `2026_10_06_120000_add_arqueo_to_cajas_operativas.php`; reversión elimina
solo las relaciones/campos añadidos y la tabla singleton. No ejecutada en la base del club.
