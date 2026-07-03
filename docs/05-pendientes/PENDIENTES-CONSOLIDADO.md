# Pendientes consolidados — Wings

Última revisión: 2026-07-03 (generado por evaluación con agentes + revisión manual)

Este archivo reemplaza la memoria de sesión sobre qué falta. Actualizarlo cada vez que se implementa algo o aparece algo nuevo. Es la fuente de verdad para arrancar después de una compactación de contexto.

---

## Estado general del sistema

El núcleo funciona: alta de alumnos, cobro de cuotas, caja operativa, validación de cajas, cashflow, clases, asistencias y liquidaciones están implementados. Los problemas que quedan son de navegación, completitud de pantallas y detalles de UX.

---

## PRIORIDAD ALTA — Bloquean o esconden funcionalidad implementada

### 1. Sidebar admin no tiene link a Revisión de Cobranza
**Problema:** La pantalla `/revision-cobranza` existe y funciona, pero no hay ningún link en el menú lateral del admin. Solo se puede acceder escribiendo la URL a mano.
**Archivo:** `resources/views/layouts/ds-app.blade.php`
**Fix:** Agregar ítem "Cobranza" apuntando a `route('web.revision-cobranza.index')` en el bloque ADMIN.

### 2. Sidebar operativo no tiene link al Dashboard Operativo
**Problema:** La ruta `/operativo` (dashboard del operativo) existe y funciona, pero el sidebar operativo no tiene ningún link "Inicio" ni "Dashboard" que lleve ahí. El operativo entra y va directo a Caja.
**Archivo:** `resources/views/layouts/ds-app.blade.php`
**Fix:** Agregar ítem "Inicio" apuntando a `route('web.operativo.dashboard')` como primer ítem del bloque OPERATIVO.

### 3. Sidebar admin: "Cashflow" lleva al formulario de carga, no al historial
**Problema:** El ítem "Mov. directo" lleva al formulario de carga de un movimiento. El historial de cashflow (`/cashflow`) es completamente inaccesible desde la navegación. Un admin que quiere ver los movimientos históricos no puede.
**Archivo:** `resources/views/layouts/ds-app.blade.php`
**Fix:** Cambiar el link a `route('web.cashflow.index')` y renombrarlo "Cashflow". El botón "Nuevo" dentro de la vista de historial da acceso al formulario de carga.

### 4. Redirect post-guardado de movimiento cashflow va a Caja en lugar de Cashflow
**Problema:** Después de guardar un movimiento directo, el admin queda en la lista de cajas en lugar de ver el historial de cashflow donde debería poder confirmar lo que acaba de cargar.
**Archivo:** `app/Http/Controllers/CashflowWebController.php` — método `store()`
**Fix:** Cambiar redirect a `route('web.cashflow.index')`.

---

## PRIORIDAD ALTA — Pantallas incompletas con impacto directo en operación

### 5. Dashboard operativo: faltan las tres secciones definidas
**Problema:** El dashboard operativo solo muestra stats de caja del día. Las tres secciones pendientes son:

**a) Alumnos con deuda** — lista clickeable de alumnos activos con deuda pendiente. El servicio `CobranzaEstadoService` ya tiene la lógica. El click debe ir directo a cobrar.

**b) Clases del día** — clases asignadas al operativo con indicador de si ya tiene asistencia cargada o no. El operativo necesita saber qué clases tiene y cuáles le falta tomar lista.

**c) Cajas rechazadas** — si el admin rechazó alguna caja anterior (no solo del día), mostrar alerta prominente. Actualmente no hay ningún aviso visible.

**Archivos:** `app/Http/Controllers/OperativoDashboardController.php`, `resources/views/operativo/dashboard.blade.php`

### 6. Ficha de alumno (show): historial de pagos y asistencias vacíos
**Problema:** La columna derecha de la ficha del alumno muestra dos bloques con el texto "disponible próximamente": historial de pagos y asistencias del mes. Es la parte más visible de la ficha y está explícitamente incompleta.
**Archivo:** `resources/views/alumnos/show.blade.php`
**Fix:** Implementar historial de pagos (últimos N pagos del alumno con fecha, monto y períodos) y resumen de asistencias del mes actual.

---

## PRIORIDAD MEDIA — Mejoras de usabilidad importantes

### 7. Sidebar: sin agrupación visual ni semántica
**Problema:** El sidebar es una lista plana para ambos roles. No hay cabeceras de sección. La estructura definida en `DEFINICIONES-PENDIENTES.md` (sección 4) nunca se implementó.

**ADMIN debería tener:**
```
Dashboard
Cobranza

── Caja / Finanzas ──
Caja · Cashflow · Liquidaciones

── Académico ──
Alumnos · Clases · Grupos · Profesores

── Configuración ──
Deportes · Rubros · Niveles · Tipos de Caja · Configuración · Usuarios
```

**OPERATIVO debería tener:**
```
Inicio

── Caja ──
Caja

── Alumnos ──
Alumnos

── Clases ──
Clases · Grupos (solo lectura)
```

**Archivo:** `resources/views/layouts/ds-app.blade.php`

### 8. Sidebar operativo: "Movimientos" no debería estar ahí
**Problema:** El operativo ve "Movimientos" en su menú. Por definición, ese acceso corresponde a Caja (accesible desde dentro de la pantalla de caja, no como ítem de nav principal).
**Archivo:** `resources/views/layouts/ds-app.blade.php`
**Fix:** Eliminar el ítem "Movimientos" del bloque OPERATIVO.

### 9. Badge de revisiones pendientes no aparece en el sidebar
**Problema:** `RevisionCobranzaService::contarPendientes()` existe y funciona, pero el resultado nunca se inyecta en las vistas. El admin no tiene ninguna señal visual de que hay alumnos pendientes de revisar.
**Archivos:** `app/Providers/AppServiceProvider.php`, `resources/views/layouts/ds-app.blade.php`
**Fix:** Agregar en el `View::composer` la inyección de `$badgeRevisionesPendientes` y usarla como badge en el link de Cobranza.

### 10. Estado de cobranza invisible en la lista de alumnos
**Problema:** El dot de estado en cada tarjeta del listado de alumnos está hardcodeado a `alumno-dot--neutral` (gris). No muestra si el alumno está al día, moroso o deudor. El `CobranzaEstadoService` existe y lo puede calcular, pero no se llama desde el controller del índice.
**Archivos:** `resources/views/alumnos/index.blade.php`, `app/Http/Controllers/AlumnoWebController.php`
**Fix:** Llamar al servicio para cada alumno de la página o cargar las deudas con eager loading y calcular el estado en la vista. Posible impacto en performance si no se hace bien (N+1).

### 11. Cashflow: no se pueden editar ni eliminar movimientos directos
**Problema:** Una vez cargado un movimiento con error, no hay forma de corregirlo. No existen métodos `edit`, `update` ni `destroy` en el controller ni en las rutas.
**Archivos:** `app/Http/Controllers/CashflowWebController.php`, `resources/views/cashflow/index.blade.php`, `routes/web.php`
**Fix:** Implementar al menos eliminación. Edición es deseable pero más compleja.

---

## PRIORIDAD MEDIA — Deuda técnica y riesgos

### 12. Código muerto: CobranzaEstadoService::resolverRevision()
**Problema:** Existen dos servicios con lógica de resolución de revisiones de cobranza con nombres de acciones distintos (`GENERAR_DEUDA`/`MARCAR_INACTIVO` vs `CONTINUA`/`INACTIVO`). El primero no es llamado desde ningún controller web. Si alguien lo usa, el comportamiento es diferente al que muestra la UI: no guarda nota, ni usuario, ni timestamp.
**Archivos:** `app/Services/CobranzaEstadoService.php` (líneas ~180–223)
**Fix:** Eliminar `resolverRevision()` de `CobranzaEstadoService` o dejarlo con un comentario claro que es código legacy no usado.

### 13. Subrubro de liquidación: dependencia implícita sin UI de verificación
**Problema:** Al pagar una liquidación, el sistema busca un subrubro con nombre exacto `"NombreDeporte-Nombre Apellido"` del profesor. Si ese subrubro no existe (nombre cambiado, creación fallida), el pago falla con error genérico. No hay ningún aviso previo al usuario.
**Archivo:** `app/Http/Controllers/LiquidacionWebController.php`
**Fix:** Verificar la existencia del subrubro antes de mostrar el formulario de pago y mostrar advertencia si no existe, con link a la configuración de rubros.

### 14. Tests rotos (SQLite incompatible con migraciones MySQL)
**Problema:** La suite de tests no corre porque una migración usa sintaxis `MODIFY` de MySQL que SQLite no soporta. No hay red de seguridad automática para detectar regresiones.
**Fix:** Definir una estrategia: o correr tests contra MariaDB de test (más fiel al ambiente real), o refactorizar la migración problemática para ser compatible con ambos motores.

---

## PRIORIDAD BAJA — Detalles y pulido

### 15. No hay pantalla de recuperar contraseña
**Problema:** Si un usuario olvida su contraseña, no hay ningún mecanismo de recuperación. Solo un admin puede resetearla manualmente desde la pantalla de Usuarios.

### 16. No hay forma de cambiar el deporte de un alumno
**Problema:** Al crear un alumno, el deporte queda fijo para siempre. En edición, el deporte aparece deshabilitado. Si un alumno cambia de deporte, habría que crear un nuevo registro.
**Decisión a tomar:** ¿Se permite cambiar de deporte editando al alumno existente? ¿O se crea un nuevo alumno y se inactiva el anterior?

### 17. DEFINICIONES-PENDIENTES.md tiene dos ítems marcados como no implementados cuando sí lo están
**Problema:** Las secciones "Regla de primer pago en PagoCuotaService" y "Reactivación automática" están implementadas en el código pero el documento dice que no.
**Fix:** Marcar ambas secciones como implementadas en `DEFINICIONES-PENDIENTES.md`.

---

## Funcionalidades implementadas (para referencia post-compactación)

- Alta y edición de alumnos con plan obligatorio
- Cobro de cuotas con FIFO, descuento de primer pago/reingreso, y cambio de plan en el cobro
- Reactivación automática al pagar o asistir a clase
- Caja operativa: abrir (auto), registrar movimientos, cobrar cuotas, cerrar
- Validación/rechazo de cajas por admin
- Integración caja validada → cashflow
- Cashflow: historial, movimientos directos
- Revisión de cobranza (posible inactivo): UI completa, resolución CONTINUA/INACTIVO
- Clases: lista, detalle, asistencias bulk con control de exceso y recuperación
- Liquidaciones: generación, cierre, pago, recibo PDF
- Recibo PDF de cobro de cuotas
- Cancelación de cobros operativos (con reversión de deuda)
- Configuración: deportes, rubros/subrubros, niveles, tipos de caja, formas de pago, reglas de primer pago
- Usuarios: CRUD con roles ADMIN/OPERATIVO/PROFESOR
- Middlewares de seguridad: rol, activo, throttle en login, IDOR en caja
- Sidebar diferenciado por rol (sin agrupación aún)
- GenerarDeudasMensualesCommand registrado en scheduler (aunque la lógica de POSIBLE_INACTIVO puede necesitar verificación)
