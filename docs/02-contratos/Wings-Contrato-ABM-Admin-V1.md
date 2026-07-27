# Wings-Contrato-ABM-Admin-V1.md

**Caso de uso (Index):** 9) ABM básicos (Admin)
**Versión:** V1
**Estado:** CERRADO
**Origen:** `docs/05-pendientes/CUESTIONARIO-I3-CONTRATOS.md` (I3.0). Auditoría completa de todos los catálogos administrables el 2026-07-26, confirmando que la capa web real ya converge en un único criterio (fue el resultado, no el punto de partida — Subrubro/TipoCaja se corrigieron en la resolución anterior de este mismo cuestionario).
**Alcance:** Regla general de listar/crear/editar/borrar aplicable a Deporte, Grupo, Nivel, Rubro, Subrubro, TipoCaja, Profesor, Alumno.
**No incluye:** Clase (tiene contrato propio, `Wings-Contrato-Clases-Asistencias-V1.md`, con reglas específicas de fecha pasada/liquidación que no aplican al resto).

---

## 9.a Regla general — nunca borrado duro si hay historial posible

**Por defecto, ningún catálogo se borra físicamente.** Se desactiva (`activo = false`) vía `toggleActivo()`. Motivo verificado con código real: las FK hacia tablas de historial (movimientos, pagos, asistencias, clases, liquidaciones) son `onDelete('cascade')` en la mayoría de los casos — un borrado físico arrastra datos reales sin aviso si no se lo protege explícitamente, y un chequeo de aplicación puede tener huecos (ver 9.c).

Catálogos bajo esta regla, todos ya conformes en la capa web real:

| Catálogo | Acción | Campo |
|---|---|---|
| Deporte | Solo `toggleActivo()` | `deportes.activo` |
| Grupo | Solo `toggleActivo()` | `grupos.activo` |
| Subrubro | Solo `toggleActivo()` | `subrubros.activo` (agregado en esta resolución) |
| TipoCaja | Solo `toggleActivo()` | `tipos_caja.activo` |
| Profesor | Solo `toggleActivo()` | `profesores.activo` |
| Alumno | Solo `toggleActivo()` | `alumnos.activo` |

## 9.b Excepción — borrado real permitido solo si está vacío

**Nivel** y **Rubro** son la única excepción, y queda así deliberadamente (no se unifica): permiten borrado físico, pero **solo si no tienen hijos** (`grupos_count = 0` para Nivel, `subrubros_count = 0` para Rubro). Un Nivel o Rubro vacío no tiene ningún historial de plata ni de operación que proteger — borrarlo no pierde datos. Es una regla distinta a la de 9.a, pero igual de segura, y más simple para estos dos casos puntuales (catálogos de clasificación pura, sin campos de negocio propios).

## 9.c Unicidad de nombre

`App\Rules\NombreUnico` (evita duplicados por mayúsculas/acentos) se aplica en todos los catálogos que tienen un campo `nombre` propio: Deporte, Nivel, Rubro, Subrubro, TipoCaja. **No aplica** a Profesor ni Alumno, que no tienen `nombre` único como identidad — ahí la unicidad real es el **DNI** (`profesores.dni` único global; `alumnos.dni` único compuesto con `deporte_id`, a propósito: la misma persona puede existir como alumno en dos deportes distintos). El DNI no necesita normalización de acentos, así que el `Rule::unique` nativo de Laravel alcanza — no hace falta extender `NombreUnico` a estos casos.

## 9.d La API admin deshabilitada NO sigue esta regla — es deuda conocida, no se toca ahora

`routes/api.php` (deshabilitada desde S2, ver I2.0) todavía tiene controllers `Admin\*Controller::destroy()` con **borrado físico** para Deporte, Grupo, Subrubro, TipoCaja, Profesor y Alumno — contradice 9.a directamente. Esto es código muerto hoy (la ruta no carga), pero si la API se reactiva algún día (decisión ya tomada en I2.0: se congela para una futura app móvil), **hay que corregir estos `destroy()` antes de reactivar**, junto con el gate de rol por endpoint que I2.0 ya dejó pendiente. No se toca en esta resolución porque tocar la API contradice la propia decisión de "congelar, no podar" de I2.0.

Hallazgos menores de esa misma capa muerta, documentados para cuando se retome (sin acción ahora):
- `app/Http/Controllers/AlumnoController.php` (sin namespace `Admin`) es un controller huérfano con métodos vacíos (no-op) salvo `store()`, enruteado en `routes/api.php:57` vía `apiResource`. Es distinto del `Admin\AlumnoController` real.
- `Admin\AlumnoController::destroy()` no chequea `deudaCuotas()` antes de borrar (sí chequea `pagos()` y `asistencias()`) — inconsistencia interna.
- `Admin\ProfesorController`'s `Store/UpdateProfesorRequest` no validan `dni/fecha_nacimiento/direccion/localidad`, campos ya NOT NULL en el esquema real — quedó desactualizada respecto a una migración posterior.

## 9.e Reglas Freeze

- Ningún catálogo de la tabla en 9.a se borra físicamente desde la capa web real.
- Nivel y Rubro solo se borran si no tienen hijos.
- La unicidad de nombre (donde aplica) ignora mayúsculas y acentos.

Cualquier cambio futuro requiere versión V2 explícita.

## 9.f Estado

🔒 CONTRATO CERRADO. No requirió cambios de código — la auditoría confirmó que la capa web real ya cumple la regla (Subrubro/TipoCaja se habían corregido en la resolución anterior de este mismo cuestionario, Deporte/Grupo/Profesor/Alumno ya cumplían de antes). Verificado por lectura completa de los 8 catálogos y sus dos capas (web + API admin) el 2026-07-26.
