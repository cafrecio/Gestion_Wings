# Contrato de permisos por rol — Wings

Fuente de verdad de qué puede hacer y ver cada rol. **Leer antes de tocar cualquier control de acceso** (middlewares, `abort(403)`, `if ($user->isX())`, filtros de listados por usuario). Este documento existe porque el mismo error de modelo mental se repitió varias veces.

---

## El error mental a evitar

> ❌ "Cada operativo ve/hace solo lo suyo."

**Esto es FALSO en Wings.** No es un sistema donde cada empleado tiene su cajón privado. Es un sistema de gestión de un negocio donde el rol define el **dominio de trabajo**, no la propiedad de los registros.

> ✅ El operativo ve y opera sobre **todo lo del negocio que le corresponde por rubro/función**, sin importar quién lo generó — incluido lo que hizo el admin.

Un operativo que entra al mostrador tiene que poder atender a cualquier alumno, cobrar cualquier cuota, ver el historial completo de cobranza y emitir el recibo de cualquier cobro de cuota — lo haya hecho él, otro operativo o el admin. Restringirlo a "lo suyo" rompe la operación real: si el turno mañana cobró y el turno tarde tiene que reimprimir ese recibo, tiene que poder.

---

## Los tres roles

| Rol | Qué es | Dominio |
|-----|--------|---------|
| **ADMIN** | Dueño/a del negocio | Todo. Ninguna acción del sistema le está vedada. |
| **OPERATIVO** | Recepción / mostrador | Toda la operación diaria: alumnos, cobros de cuota, caja, cobranza, clases y asistencias. Ve lo del admin en lo que comparten rubro. |
| **PROFESOR** | Docente | Solo clases y asistencias. No participa de plata ni de alumnos. |

### Regla de oro entre ADMIN y OPERATIVO

**El ADMIN nunca puede tener menos poder que el OPERATIVO.** Si el operativo puede hacer/ver algo, el admin también — siempre. No puede existir una acción disponible para el operativo y negada al admin. (Esto ya se corrigió una vez en los subrubros: la opción de permiso es "Solo Admin" o "Ambos", nunca "Solo Operativo".)

### Qué comparten y qué no

- Lo que el operativo puede usar, el admin también (regla de oro).
- Lo que es **solo del admin**: cashflow directo, liquidaciones a profesores, validación/rechazo de cajas, configuración del sistema, usuarios, y los subrubros marcados `permitido_para = ADMIN` (sueldos, servicios, intereses, etc.).
- El operativo **no ve** esos dominios exclusivos del admin.

---

## Permisos por rubro (subrubros)

El campo `subrubros.permitido_para` define quién puede **usar y ver** movimientos de ese subrubro:

- `permitido_para = 'OPERATIVO'` significa **"ambos"**: el operativo lo usa en su caja y lo ve en su historial, y el admin también (por la regla de oro). Ej: Cuota Mensual, Gastos Operativos (librería, limpieza), Ingresos Varios.
- `permitido_para = 'ADMIN'` significa **"solo admin"**: el operativo no lo ve ni lo usa. Ej: Sueldos, Servicios, Intereses, Liquidaciones.

**Consecuencia para listados y recibos:** cuando el operativo mira el historial de movimientos, ve **todos** los movimientos de subrubros `OPERATIVO` sin importar quién los registró (otro operativo o el admin). El criterio de visibilidad es el **rubro**, nunca "quién lo hizo".

---

## Casos concretos ya definidos (no volver a discutir)

### Recibo de cuota (`web.recibos.cuota` → `ReciboController::cuota()`)
El recibo es para el **tutor del alumno** y lo emite quien cobra la cuota. Cobran cuota ADMIN y OPERATIVO.
- ADMIN: cualquier recibo. ✅
- OPERATIVO: cualquier recibo de cuota, lo haya cobrado él, otro operativo o el admin. ✅ (el rubro Cuotas es `OPERATIVO` = ambos)
- PROFESOR: ninguno. ❌ (no participa de cobranza)

**No** se restringe al operativo a "los recibos de sus propias cajas" — eso sería el error mental de arriba.

### Recibo de liquidación (`web.recibos.liquidacion`)
La liquidación es un pago a un profesor, dominio exclusivo del admin.
- ADMIN: sí. OPERATIVO y PROFESOR: no (`ensure.admin.web`).

### Historial de movimientos (`web.caja.historial`)
El operativo ve todos los movimientos de subrubros `OPERATIVO` de los últimos 90 días, de cualquier operativo y del admin. No se filtra por "usuario que lo registró".

### Caja
El operativo abre/opera/cierra cajas. La única restricción "de propiedad" legítima que existe es que un operativo no puede **cerrar** la caja abierta de otro operativo (integridad del arqueo, no privacidad). Ver `CajaWebController::cerrar()`. Pero **ver** el historial y los movimientos no está restringido por propiedad.

---

## Cómo aplicar esto al escribir código

Antes de escribir un `if ($user->isOperativo() && $registro->usuario_id === $user->id)`, preguntarse:

1. ¿Este dato pertenece a un **rubro/dominio** que el operativo puede ver? → Si sí, lo ve **todo**, no solo lo suyo.
2. ¿La restricción por "dueño" protege **integridad** (ej. no cerrar la caja de otro) o solo **privacidad** entre operativos? → Si es privacidad entre operativos, **no va**: el negocio es uno solo.
3. ¿El admin queda con al menos el mismo poder que el operativo? → Si no, el diseño está mal.

Si la respuesta no está clara, **preguntar al usuario antes de implementar**, no asumir el modelo "cada uno lo suyo".
