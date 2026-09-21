# Verificación FIN-08 — Revisión de cobranza para OPERATIVO

**Fecha:** 21/09/2026  
**Commit evaluado:** `5dff231c8f5b2ea841cfd6cd0838c13a1c6c374e`  
**Agente verificador:** Gemini (Antigravity)  
**Firma:** LOG GEM CYE  

---

## 1. Resumen ejecutivo y Veredicto

Se realizó una auditoría y verificación exhaustiva de la tarea **FIN-08** ("La revisión de cobranza la resuelve también el operativo") sobre el commit `5dff231`.

- **Veredicto:** **APROBADO CON OBSERVACIÓN MENOR DE UX**.
- La apertura de permisos para el rol OPERATIVO respeta rigurosamente los contratos de negocio (`PERMISOS-ROLES.md` y `Wings-contrato-estadosAlum-cobranza-asistencia-V1.md`).
- Se verificó que resolver una revisión como `INACTIVO` **no condona deudas previas** (ni totales, ni parciales, ni notas), manteniendo la potestad de condonar exclusivamente en manos del rol `ADMIN`.
- Se verificó que el rol `PROFESOR` queda completamente excluido (menú sin enlace, GET 403 y POST 403).
- La suite completa de pruebas del proyecto pasa al 100% (**288 pruebas pasando**, 0 errores, 0 fallos).
- Se detectó un defecto menor de UX en la vista Blade (`revision-cobranza/index.blade.php` no muestra `$errors` de validación), el cual queda documentado en detalle en la Sección 3 sin modificar código (siguiendo la regla *"No arregles nada sin avisar: reportá"*).

---

## 2. Resultado detallado de los 9 puntos de verificación

### Punto 1: OPERATIVO entra a Revisión desde el menú "Día a día" y los filtros funcionan
- **Resultado:** **VERIFICADO (PASS)**.
- **Detalle:**
  - El usuario con rol `OPERATIVO` ve el enlace `"Revisión"` en la barra de navegación dentro del grupo `"Día a día"`.
  - Al ingresar a `/revision-cobranza`, accede correctamente al listado con estado HTTP 200.
  - **Filtro por Estado:** Por defecto filtra por `estado=PENDIENTE`. Al seleccionar `RESUELTO`, muestra las revisiones resueltas con su color y etiqueta correspondiente. Al seleccionar vacío (`Todos`), lista el historial unificado.
  - **Filtro por Período:** El selector `<select name="periodo">` lista dinámicamente los períodos objetivo presentes en la base de datos. Al seleccionar un período (ej. `2026-09`), filtra correctamente los registros.
  - El botón `"Limpiar"` aparece cuando hay filtros activos y restablece la vista al estado por defecto.

### Punto 2: OPERATIVO resuelve "Continúa" y se genera la cuota con precio vigente
- **Resultado:** **VERIFICADO (PASS)**.
- **Detalle:**
  - Al enviar la resolución `CONTINUA` con su nota correspondiente:
    - La revisión pasa a `estado_revision = 'RESUELTO'`, `resolucion = 'CONTINUA'`, registrando el ID del usuario operativo y la fecha/hora en `resuelto_at`.
    - Se crea el registro correspondiente en la tabla `deuda_cuotas` con el período objetivo, `monto_original` igual al `precio_mensual` del plan activo del alumno, `monto_pagado = 0` y `estado = 'PENDIENTE'`.
    - El alumno permanece activo (`activo = 1`).
    - En el módulo de **Cobranza** (`/cobranza`), el alumno pasa a figurar como `Deudor` (o en plazo según corresponda).
    - En la ficha individual del alumno (`/alumnos/{id}`), la nueva deuda aparece reflejada en el bloque *"Estado de cobranza"* con su período y monto correspondiente (ej. `$35.000`).

### Punto 3: OPERATIVO resuelve "Inactivo" en alumno con deuda previa y pago parcial
- **Resultado:** **VERIFICADO (PASS)**.
- **Detalle:**
  - Se probó sobre un alumno que registraba una deuda del período anterior (`2026-08`) por `$35.000` con un pago parcial aplicado de `$15.000` (`saldo_pendiente = $20.000`), estado `PENDIENTE` y observaciones de mostrador.
  - Al resolver `INACTIVO` con la nota `"Tutor informó mudanza"`:
    - El alumno pasa a `activo = 0`.
    - La revisión pasa a `estado_revision = 'RESUELTO'`, `resolucion = 'INACTIVO'`.
    - **No se genera deuda** para el período objetivo de la revisión.
    - **La deuda previa del mes anterior queda 100% INTACTA:** conserva su estado `PENDIENTE`, su `monto_original` de `$35.000`, su `monto_pagado` de `$15.000`, su `saldo_pendiente` de `$20.000` y sus observaciones originales sin alteraciones.
    - **Se confirma que el OPERATIVO NO tiene vía para condonar deudas pasadas.**

### Punto 4: Doble resolución simultánea (dos pestañas)
- **Resultado:** **VERIFICADO (PASS)**.
- **Detalle:**
  - Si dos pestañas o usuarios intentan resolver la misma revisión:
    - La primera petición se ejecuta y completa la transacción.
    - La segunda petición es interceptada por la validación en `RevisionCobranzaService`:
      ```php
      if ($revision->estado_revision !== AlumnoRevisionCobranza::ESTADO_PENDIENTE) {
          throw new \Exception('Esta revisión ya fue resuelta.');
      }
      ```
    - El controlador `RevisionCobranzaWebController::resolver` atrapa la excepción y redirige al usuario con un mensaje flash de error: `"Esta revisión ya fue resuelta."`.
    - No se produce duplicación de deuda ni estados inconsistentes.

### Punto 5: Validación de resolución y notas
- **Resultado:** **VERIFICADO (PASS en backend / ALERTA en vista)**.
- **Detalle:**
  - Las reglas del backend en `RevisionCobranzaWebController`:
    ```php
    $request->validate([
        'resolucion'      => 'required|in:CONTINUA,INACTIVO',
        'nota_resolucion' => 'required|string|min:5|max:500',
    ]);
    ```
  - Si falta la nota o tiene menos de 5 caracteres, la validación de Laravel rechaza la solicitud de forma estricta e impide cualquier cambio en la base de datos.
  - En el navegador, el textarea cuenta con los atributos HTML5 `required minlength="5" maxlength="500"`.
  - *(Ver Hallazgo 1 en Sección 3 sobre la falta de renderizado de `$errors` en la plantilla Blade).*

### Punto 6: PROFESOR no ve en menú ni accede por URL/POST (403)
- **Resultado:** **VERIFICADO (PASS)**.
- **Detalle:**
  - En el layout `resources/views/layouts/ds-app.blade.php`, la barra de navegación para el rol `PROFESOR` sólo renderiza el grupo *"Mis clases"*, omitiendo por completo el enlace a Revisión.
  - Si un usuario autenticado como profesor intenta un acceso directo por URL `GET /revision-cobranza`:
    - El middleware `RejectProfesorWeb` (`reject.profesor.web`) ejecuta `abort(403)`.
  - Si intenta emitir un `POST /revision-cobranza/{id}/resolver`:
    - El middleware `RejectProfesorWeb` ejecuta `abort(403)`.

### Punto 7: ADMIN condona; OPERATIVO no puede condonar
- **Resultado:** **VERIFICADO (PASS)**.
- **Detalle:**
  - **ADMIN:** En la ficha del alumno (`/alumnos/{id}`), el botón *"Condonar"* sólo se muestra si `Auth::user()->isAdmin()`. Al presionar condonar y justificar motivo (mínimo 10 caracteres), la ruta `POST /deudas/{id}/condonar` pasa la deuda a estado `CONDONADA` y registra la traza de auditoría.
  - **OPERATIVO:** En la ficha del alumno no tiene el botón ni el modal de condonación en el DOM. Si intenta enviar un `POST` forzado a `/deudas/{id}/condonar`, la ruta está protegida por el middleware `EnsureAdminWeb` (`ensure.admin.web`), el cual lo intercepta y lo redirige (HTTP 302) a `/caja`. La deuda permanece inalterada en estado `PENDIENTE`.

### Punto 8: Menú en ancho móvil (responsive)
- **Resultado:** **VERIFICADO (PASS)**.
- **Detalle:**
  - Se analizó la estructura DOM y estilos CSS de `ds-app.blade.php` y `app.css`.
  - Existe un único elemento `<aside class="ds-sidebar">` en el DOM para desktop y mobile; no hay duplicaciones de navegación.
  - En anchos `< 768px`, el sidebar pasa a ser un cajón oculto (`transform: translateX(-100%)`) activable mediante `#ds-menu-toggle` y backdrop `#ds-sidebar-overlay`.
  - En los 3 roles (ADMIN, OPERATIVO y PROFESOR), las rutas correspondientes aparecen exactamente una vez (o cero en el caso de profesor para revisión), sin duplicados.

### Punto 9: Regresión sobre otros módulos
- **Resultado:** **VERIFICADO (PASS)**.
- **Detalle:**
  - Módulos verificados:
    - **Alumnos** (`/alumnos`): Listados, filtros por deporte/grupo y fichas individuales funcionando normalmente.
    - **Cobranza** (`/cobranza`): Semáforo de estados (Al día, En plazo, Moroso, Deudor), filtros y listado funcionando sin problemas.
    - **Caja** (`/caja`): Apertura, movimientos, historial y cobro de cuotas funcionando correctamente.
    - **Clases** (`/clases`): Listados y toma de asistencia sin alteraciones.
  - Suite de pruebas completa: **288 tests pasando**.

---

## 3. Errores, inconsistencias y hallazgos encontrados

Siguiendo la regla *"No arregles nada sin avisar: reportá"*, se detallan a continuación los hallazgos técnicos:

### Hallazgo 1 (Severidad Media - UX): Falta renderizado de `$errors` en `revision-cobranza/index.blade.php`
- **Ubicación:** `resources/views/revision-cobranza/index.blade.php`, líneas 8-18.
- **Problema:** La vista renderiza mensajes de sesión exitosos o de error manual:
  ```blade
  @if(session('success')) ... @endif
  @if(session('error')) ... @endif
  ```
  Sin embargo, **no incluye ningún bloque para `@if($errors->any())`**.
  Cuando la validación de `RevisionCobranzaWebController::resolver` falla (por ejemplo, `nota_resolucion` vacía o con menos de 5 caracteres enviada por curl, bot o si falla la validación HTML5 del cliente), Laravel redirige con los errores de validación en la sesión (`$errors`). Al no renderizarlos la vista ni el layout general, la pantalla se recarga en blanco sin explicarle al usuario qué campo falló ni por qué.
- **Cómo reproducir:**
  1. Con una cuenta de OPERATIVO, enviar una petición `POST` a `/revision-cobranza/{id}/resolver` con `nota_resolucion = 'abc'`.
  2. La respuesta redirige a la página principal de revisión, pero no se muestra ninguna alerta de error.
- **Solución propuesta (para cuando Carlos autorice diseño):**
  Agregar en la zona de mensajes flash de `index.blade.php`:
  ```blade
  @if($errors->any())
  <div class="filtros-card mb-3" style="border-left:4px solid var(--color-danger);">
      <p style="font-size:0.85rem; color:var(--color-danger);">{{ $errors->first() }}</p>
  </div>
  @endif
  ```

### Hallazgo 2 (Severidad Baja - Maquetado móvil): Grilla inline rígida en filtros de `revision-cobranza/index.blade.php`
- **Ubicación:** `resources/views/revision-cobranza/index.blade.php`, línea 35.
- **Problema:** El formulario de filtros declara:
  ```blade
  <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items:end;">
  ```
  En pantallas angostas (<= 360px), esta grilla no tiene media queries y fuerza a los 3 elementos (Estado, Período y botón Limpiar) a permanecer en una sola fila, apretando el contenido.
- **Solución propuesta:** Utilizar la clase `.filtros-row` del design system (idéntica a la canónica de alumnos) que ya implementa flex-wrap para acomodar los campos automáticamente en celular.

### Hallazgo 3 (Severidad Menor - Código muerto / duplicado): Script inline redundante al pie de `index.blade.php`
- **Ubicación:** `resources/views/revision-cobranza/index.blade.php`, líneas 203-214.
- **Problema:** Al pie de la vista se definen las funciones JavaScript `abrirForm(id, tipo)` y `cerrarForm(id)`. No obstante, `resources/js/ds-app.js` (líneas 419-450) ya implementa la delegación de eventos por atributos `[data-abrir-revision]` y `[data-cerrar-revision]` con fallback completo. Este script inline quedó obsoleto y puede removerse cuando se limpie la vista para la política CSP definitiva.

---

## 4. Casos borde analizados

1. **¿Qué ocurre si el alumno no tiene plan activo al resolver "Continúa"?**
   - El servicio `RevisionCobranzaService` lo valida explícitamente:
     ```php
     $alumnoPlan = $revision->alumno->planActivo;
     if (!$alumnoPlan || !$alumnoPlan->plan) {
         throw new \Exception('El alumno no tiene un plan activo. Asigná un plan antes de resolver como Continúa.');
     }
     ```
   - Lanza una excepción clara, el controlador la atrapa y redirige con `session('error')`, la cual sí se muestra en la vista como alerta roja. No se crea deuda huérfana.
2. **¿Los botones cumplen con la regla de un solo verbo corto del Design System?**
   - Sí: `"Continúa"`, `"Inactivo"`, `"Confirmar"`, `"Cancelar"`, `"Limpiar"`, `"Ver"`. Todos cumplen la directiva.
3. **¿Las fechas se muestran en formato argentino?**
   - Sí: la fecha de resolución se muestra como `DD/MM/AAAA HH:mm` en zona horaria `America/Argentina/Buenos_Aires` (`d/m/Y H:i`).

---

## 5. Conclusión final

La implementación técnica del commit `5dff231` para **FIN-08** cumple cabalmente con todos los criterios de seguridad, integridad de fondos y separación de roles. No hay riesgo de fuga ni de condonaciones indebidas.

Quedan únicamente registrados los 3 hallazgos visuales/UX menores para ser tratados en la etapa correspondiente de pulido de vistas con autorización de Carlos.
