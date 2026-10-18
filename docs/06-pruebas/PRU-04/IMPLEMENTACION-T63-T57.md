# Implementación T63 y T57 — Deporte/Grupo en uso y Medio de Pago del Cajón

**Fecha:** 18/10/2026  
**Autor:** Gemini CAB  
**Tareas:** T63 y T57  
**Estado:** IMPLEMENTADO, listo para verificación por Claude y aprobación visual por Carlos. Sin despliegue.

---

## 1. T63: No desactivar deporte o grupo en uso (DU-41)

### Decisión de Carlos
> «Impedirlo mientras tenga alumnos activos o clases futuras».

### Análisis y Hallazgos
- `DeporteWebController::toggleActivo` y `GrupoWebController::toggleActivo` cambiaban el booleano `activo` sin validar dependencias. Al desactivar un grupo o deporte en uso, los alumnos activos quedaban huérfanos de grupo visible en listados y filtros.
- **Definición canónica de «clase futura»:** Se localizó en `ClaseWebController:528` (`where('cancelada', false)->whereDate('fecha', '>=', today())`), respetando la zona horaria de la aplicación (`America/Argentina/Buenos_Aires`).
- **Alumnos activos:**
  - En Grupo: `$grupo->alumnos()->where('activo', true)->count()`.
  - En Deporte: Alumnos activos vinculados directamente o por cualquiera de sus grupos.

### Cambios Implementados
- En `DeporteWebController::toggleActivo`: si el deporte está activo y se intenta desactivar, se contabilizan alumnos activos y clases futuras. Si alguna cuenta es mayor a 0, se frena y se informa:
  - Formato claro y natural (ej. `No se puede desactivar el deporte porque tiene 2 alumnos activos y 2 clases futuras.` o singular según corresponda).
  - Vía JSON (usada por el toggle asíncrono): devuelve `{error: "..."}` con HTTP 422 (compatible con `ds-app.js`).
  - Vía Web clásica: redirección `back()->with('error', $mensaje)`.
  - Reactivación (`activo = false -> true`): siempre permitida sin restricciones.
- En `GrupoWebController::toggleActivo`: idéntica regla y respuesta para grupos individuales.

### Pruebas
- Archivo: `tests/Feature/DesactivarDeporteGrupoEnUsoTest.php` (10 pruebas):
  - Rechazo web y JSON con alumnos activos.
  - Rechazo con clases futuras.
  - Mensaje plural combinado.
  - Desactivación permitida con alumnos inactivos y clases pasadas o canceladas.
  - Reactivación sin freno.

---

## 2. T57: Medio de pago del cajón de mostrador (DU-16, DU-59)

### Decisiones de Carlos
> DU-16: «El medio de pago asignado al cajón no se puede desactivar».  
> DU-59: «El dueño puede cambiar el medio de pago del cajón, pero SOLO cuando no hay ninguna caja ABIERTA».

### Cambios Implementados
1. **Protección contra desactivación del medio del cajón:**
   - En `CajaService`: nuevo método `tipoCajaMostradorId(): ?int`.
   - En `TipoCajaWebController::toggleActivo`: si el tipo de caja es el configurado para el mostrador, se rechaza con `422` en JSON o flash `error` en web:
     `No se puede desactivar porque es el medio de pago del cajón de mostrador.`
   - En `TipoCajaWebController::update`: se bloquea cualquier intento de desmarcar `activo` si coincide con el medio del mostrador.
2. **Cambio de medio permitido condicionado a que no haya caja abierta:**
   - En `CajaService::configurarMostrador(int $tipoCajaId, int $adminId)`:
     - Si ya estaba configurado y se selecciona un medio diferente, se comprueba `CajaOperativa::where('estado', 'ABIERTA')->exists()`.
     - Si existe una caja abierta, arroja `DomainException`:
       `No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.`
     - Registra auditoría completa: `tipo_caja_id`, `configurado_por_id = $adminId`, `configurado_at = now()`.
   - En `CajaWebController`:
     - `configuracionMostrador()` pasa `$hayCajaAbierta` a la vista.
     - `configurarMostrador()` captura `DomainException` y redirige `back()->with('error', ...)`.
3. **Pantalla `caja/configuracion` (Blade):**
   - El selector muestra todos los medios activos.
   - Si `$hayCajaAbierta` es true:
     - El selector se muestra deshabilitado (`@disabled($hayCajaAbierta)`).
     - Se muestra el aviso de advertencia: `No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.`
     - El botón `Guardar` se oculta (solo queda `Volver`).
   - Si no hay caja abierta:
     - El selector está habilitado.
     - Botón `Guardar` visible.
     - Texto auxiliar: `Configurado actualmente. Podés cambiarlo seleccionando otro medio activo.`

---

## 3. Evidencia visual para aprobación de Carlos

Siguiendo AGENTS.md §1 (Chrome headless sobre vistas reales del sistema a 1366 px y marco para ancho 360 px / 375 px):

| Pantalla / Estado | Qué muestra | Captura Escritorio (1366 px) | Captura Celular (Marco 360 px) |
|---|---|---|---|
| **Configuración cajón (Habilitada)** | Selector disponible y botón Guardar visible | [Ver PNG](evidencia-t57/caja-configuracion-habilitada-escritorio-1366.png) | [Ver PNG](evidencia-t57/caja-configuracion-habilitada-marco-360.png) |
| **Configuración cajón (Deshabilitada)** | Selector bloqueado, aviso de caja abierta y sin Guardar | [Ver PNG](evidencia-t57/caja-configuracion-deshabilitada-escritorio-1366.png) | [Ver PNG](evidencia-t57/caja-configuracion-deshabilitada-marco-360.png) |
| **Aviso Medio de Cajón** | Cartel al intentar desactivar el medio del cajón | [Ver PNG](evidencia-t57/tipo-caja-cajon-aviso-escritorio-1366.png) | [Ver PNG](evidencia-t57/tipo-caja-cajon-aviso-marco-360.png) |
| **Aviso Grupo en Uso** | Cartel al intentar desactivar un grupo con alumnos activos | [Ver PNG](evidencia-t57/grupo-en-uso-aviso-escritorio-1366.png) | [Ver PNG](evidencia-t57/grupo-en-uso-aviso-marco-360.png) |

Archivos HTML fuente:
- `docs/06-pruebas/PRU-04/evidencia-t57/caja-configuracion-habilitada.html`
- `docs/06-pruebas/PRU-04/evidencia-t57/caja-configuracion-deshabilitada.html`
- `docs/06-pruebas/PRU-04/evidencia-t57/tipo-caja-cajon-aviso.html`
- `docs/06-pruebas/PRU-04/evidencia-t57/grupo-en-uso-aviso.html`

---

## 4. Pruebas y Verificación

### Pruebas Específicas (25 pruebas nuevas)
- `tests/Feature/DesactivarDeporteGrupoEnUsoTest.php` (12 pruebas) — 100% PASS:
  - Rechazo web y JSON con alumnos activos.
  - Rechazo con clases futuras.
  - Mensaje plural combinado (alumnos activos y clases futuras).
  - Desactivación permitida con alumnos inactivos y clases pasadas o canceladas.
  - Reactivación sin freno.
  - Rechazo de permisos para rol `OPERATIVO` al intentar desactivar deportes o grupos (HTTP 403).
- `tests/Feature/MedioPagoCajonTest.php` (13 pruebas) — 100% PASS:
  - Rechazo de desactivación del medio de mostrador vía toggle (web y JSON) y vía update.
  - Desactivación permitida de otro medio activo.
  - Dueño cambia medio sin caja abierta (actualiza `tipo_caja_id`, `configurado_por_id` y `configurado_at`).
  - Bloqueo en backend (`DomainException`) y web al intentar cambiar con caja abierta.
  - Interfaz de configuración adaptada (selector `disabled` y sin botón Guardar con caja abierta).
  - Verificación de que el nuevo medio configurado se propone y utiliza en la apertura siguiente.
  - Rechazo de permisos para rol `OPERATIVO` al intentar desactivar tipos de caja, ver o cambiar la configuración del cajón (HTTP 403).

### Suite Completa
Ejecutada sobre `wings_testing_gemini`:
- **853 pruebas** registradas (851 ejecutadas, 2 omitidas, 0 fallidas).
- 7.666 aserciones.
- `DocumentacionNoMienteTest` pasa sin discrepancias.
