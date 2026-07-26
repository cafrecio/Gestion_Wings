# Wings-Contrato-Catalogos-Contables-V1.md

**Caso de uso (Index):** 6) Catálogos contables (Rubros / Subrubros / Tipos de Caja)
**Versión:** V1
**Estado:** CERRADO
**Origen:** `docs/05-pendientes/CUESTIONARIO-I3-CONTRATOS.md` (I3.0) — módulo sin ningún contrato previo. Reglas definidas junto al usuario el 2026-07-26 a partir de auditoría completa del código real.
**Alcance:** Rubro, Subrubro, TipoCaja — ABM, unicidad de nombre, ciclo de vida (alta/baja).
**No incluye:** Deporte, Nivel, Grupo (ABM propio, ver Caso 2), reglas de `permitido_para` en el flujo de caja (ver `Wings-Contrato-Caja-Cashflow-V4.md`).

---

## 6.a Ciclo de vida — nunca borrado físico

**Regla central:** Subrubro y TipoCaja **no se eliminan nunca**, solo se **desactivan** (`activo = false`). Motivo: la FK de `movimientos_operativos`/`cashflow_movimientos` hacia `subrubros`/`tipos_caja` es `onDelete('cascade')` — borrar un subrubro o tipo de caja con historial borraba en cascada movimientos de plata reales, sin aviso.

- `subrubros.activo` (nuevo campo, default `true`) y `tipos_caja.activo` (ya existía, pero nunca se exponía en la UI) controlan el ciclo de vida.
- Las pantallas de ABM reemplazan el botón "Eliminar" por "Activar"/"Pausar" (Subrubro) o "Activar"/"Desactivar" (TipoCaja).
- Los desplegables para **cargar un movimiento nuevo** (`CajaWebController::cargarRubros()`, selector de tipo de caja al pagar liquidación) solo muestran subrubros/tipos de caja **activos**.
- Lo ya cargado en el histórico (movimientos viejos que referencian un subrubro/tipo de caja ahora inactivo) **no se toca ni se oculta** — sigue mostrando su nombre normalmente.
- `Rubro` mantiene su regla previa (no cambia en esta resolución): `RubroWebController::destroy()` bloquea el borrado si tiene subrubros asociados (`subrubros_count > 0`). Es borrado real, no desactivación — se deja así porque un Rubro vacío (sin subrubros) no tiene historial de plata que proteger.

---

## 6.b Subrubros reservados del sistema

- `es_reservado_sistema = true` (ej. "Cuota Mensual") bloquea **editar** y **activar/desactivar** — es un dato estructural del sistema, no un catálogo administrable.
- Esta regla ya existía y no cambia.

---

## 6.c Unicidad de nombre

Se usa `App\Rules\NombreUnico` (normaliza mayúsculas y acentos, ver B5 del reporte de backend) en **los tres catálogos de este contrato**:

- `TipoCaja` — ya la tenía.
- `Nivel` — ya la tenía (catálogo de Caso 2, se menciona por completitud).
- `Subrubro` — **agregado en esta resolución**. Antes la capa web real (`SubrubroWebController`) solo tenía el `unique` nativo de Laravel/DB (case-insensitive por collation, pero NO insensible a acentos) — la protección completa solo existía en la capa API deshabilitada.
- `Rubro` — **agregado en esta resolución**, no la tenía en ninguna capa.
- `Deporte` — **agregado en esta resolución**, no la tenía en ninguna capa (Caso 2, se incluye por ser el mismo patrón y haberse tocado en el mismo trabajo).

---

## 6.d Recibo emitido — sin relación con este contrato

Un subrubro/tipo de caja desactivado **no invalida** recibos ya emitidos que lo mencionen — el nombre queda tal cual estaba en el momento de la operación.

---

## 6.e Reglas Freeze

- Ningún Subrubro ni TipoCaja se borra físicamente desde la web real.
- Ningún nombre duplicado (ignorando mayúsculas/acentos) puede coexistir en Rubro, Subrubro, TipoCaja, Deporte o Nivel.
- Los desplegables de carga nueva nunca ofrecen un Subrubro/TipoCaja inactivo; el histórico nunca se filtra por este criterio.

Cualquier cambio futuro requiere versión V2 explícita.

---

## 6.f Estado

🔒 CONTRATO CERRADO. Implementado y probado contra datos reales (transacción + rollback) el 2026-07-26: 7 aserciones sobre NombreUnico (Subrubro/Rubro/Deporte), toggle de activo (con y sin protección de reservado), y filtrado de desplegables de carga nueva.
