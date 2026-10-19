# Implementación de T34 y T67 — Clases con lista tomada y DNI recortado en lista de asistencia

**Fecha:** 18/10/2026  
**Agente:** Gemini (Antigravity) — LOG GEM CAB  
**Base de pruebas:** `wings_testing_gemini`  

---

## 1. Decisiones de Carlos

- **T34 (DU-33):** *«Con lista tomada sale del contador y queda para que el dueño decida si la paga».*
- **T67 (DU-56):** *«Quitar el DNI de la lista, dejar solo los últimos 3 números del DNI para homónimos».*

---

## 2. Resumen de Cambios

### T34: Clase con lista tomada y nadie presente
1. **Contador del menú y filtro «Sin asistencia» (`finalizada`):**
   - En `app/Providers/AppServiceProvider.php`: Se cambió la condición de conteo de `$badge` de `whereDoesntHave('asistencias', fn($q) => $q->where('presente', true))` a `doesntHave('asistencias')`.
   - En `app/Http/Controllers/ClaseWebController.php` (método `index`, match de `estado`): El valor `'finalizada'` ahora filtra con `->doesntHave('asistencias')`. De esta forma, una clase con la lista tomada donde todos faltaron ya no aparece como «Sin asistencia» ni incrementa el contador del menú.
   - En `app/Services/ReporteMensualService.php` (`avisos()['asistencia']`): Se actualizó a `doesntHave('asistencias')` para mantener absoluta sincronía con el contador y el filtro.
   - En `app/Http/Controllers/LiquidacionWebController.php` (`clases_sin_asistencia`): Se sincronizó a `doesntHave('asistencias')`.
2. **Nuevo filtro para el dueño (`sin_presentes`):**
   - En `app/Http/Controllers/ClaseWebController.php`: Se añadió el estado `'sin_presentes'` al `match ($request->input('estado'))`. Solo aplica si `$esAdmin` es verdadero; en caso contrario devuelve `null` (comportándose como sin filtro de estado). Consulta: clases pasadas, no canceladas, no validadas, que tienen asistencias registradas (`whereHas('asistencias')`) y no tienen presentes (`whereDoesntHave('asistencias', fn($q) => $q->where('presente', true))`).
   - En `resources/views/clases/index.blade.php`: Se agregó la opción `<option value="sin_presentes">Sin presentes</option>` en el `<select name="estado">` envuelta en `@if($esAdmin)` para que sea exclusiva del dueño.
3. **Liquidación:**
   - `LiquidacionService::clasesLiquidablesHora` no sufrió alteraciones. Exige `validada_para_liquidacion = true` o tener al menos un presente. Una clase con lista tomada y nadie presente **no se paga** a menos que el dueño la valide manualmente.
4. **Permisos de validación manual:**
   - Se verificó `routes/web.php`: la ruta `PATCH /clases/{id}/validar` (`toggleValidada`) se encuentra dentro del grupo protegido por `Route::middleware('ensure.admin.web')`. La operativa no puede validar clases para liquidación. En la vista `clases/show.blade.php`, el botón de validación está protegido por `@if($esAdmin && !$clase->validada_para_liquidacion && $cantPresentes === 0)`.

> [!NOTE]
> **Consulta sobre clases que el dueño decida "no pagar":**
> Si el dueño decide que una clase con todos ausentes **no se paga**, hoy no existe un botón o estado explícito "no pagar", por lo que la clase simplemente permanece sin validar (y no entra a la liquidación). En el filtro de "Sin presentes" permanecerá visible a menos que se le asigne un estado o marca específica si Carlos lo considera necesario en el futuro.

---

### T67: DNI en la lista de asistencia
1. **Recorte a los últimos 3 dígitos:**
   - En `resources/views/clases/show.blade.php` (línea 335): Se reemplazó `<span>· DNI {{ $alumno->dni }}</span>` por `<span>· DNI …{{ substr((string) $alumno->dni, -3) }}</span>`.
   - Se preserva el resguardo para alumnos homónimos sin exponer el documento completo.
2. **Búsqueda de DNI en rutas y vistas accesibles al profesor:**
   - El profesor solo tiene acceso a `GET /clases`, `GET /clases/{id}`, `POST /clases/{id}/asistencias` y `PATCH /clases/{id}/profesores`.
   - No tiene acceso a `alumnos` (index, show, autocomplete ni forms), ni a `caja` o `cobranza`.
   - En `clases/index.blade.php` y en las cards de clases (`clases/_card.blade.php`) no se renderiza ningún DNI de alumno.
   - En `clases/show.blade.php`, el DNI del alumno solo aparecía en esa línea ahora modificada. El HTML generado no contiene el DNI completo de los alumnos.

---

## 3. Verificado vs. Inferido (Protocolo §6c)

### Verificado
1. **Comportamiento del contador y filtros:**
   - Clase sin lista: cuenta en badge del menú y aparece en filtro «Sin asistencia» (`estado=finalizada`).
   - Clase con lista y todos ausentes: no cuenta en badge, no aparece en «Sin asistencia», y aparece en el nuevo filtro «Sin presentes» (`estado=sin_presentes`).
   - Clase con al menos un presente: no cuenta en badge, no aparece en «Sin asistencia» ni en «Sin presentes».
   - Clase validada a mano: no cuenta en badge ni en «Sin asistencia».
   - Clase cancelada: no cuenta en badge ni en «Sin asistencia».
   - Sincronía del número del menú con el listado comprobada.
2. **Permisos:**
   - Rol ADMIN ve la opción «Sin presentes» en el combo de estados y puede filtrar.
   - Rol OPERATIVO y PROFESOR no ven la opción «Sin presentes» en el HTML y no pueden activar dicho filtro.
   - Solo ADMIN puede ejecutar `PATCH /clases/{id}/validar`.
3. **Liquidación:**
   - Una clase con lista tomada y todos ausentes calcula $0.00 en la liquidación del profesor mientras no esté validada.
   - Una vez validada a mano por el dueño, se incluye automáticamente en la liquidación por hora.
4. **Visual y DNI:**
   - La lista de asistencia renderiza `DNI …456` para `42123456`.
   - Búsqueda de la cadena del DNI completo en el HTML de la vista devuelve falso.
   - Capturas en 1366 px y 360 px (marco con iframe) generadas con el sistema real en `docs/06-pruebas/PRU-04/evidencia-t34-t67/`.
5. **Suite de pruebas:**
   - `tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php` pasa 4/4 (29 aserciones).
   - `tests/Feature/ReporteMensualTest.php` pasa 21/21 (194 aserciones).
   - `tests/Feature/CspSinCodigoIncrustadoTest.php` pasa 2/2 (0 código JS incrustado nuevo).

### Inferido
- Se infiere que si el dueño no desea pagar una clase sin presentes, dejarla sin validar es suficiente para la operativa actual, ya que `LiquidacionService` nunca liquida clases no validadas sin presentes.

---

## 4. Evidencia Visual

Las capturas fueron generadas utilizando Chrome headless sobre el HTML producido por la aplicación:
- **01. Contador del menú (Sin asistencia):**
  - Escritorio (1366px): `docs/06-pruebas/PRU-04/evidencia-t34-t67/01-contador-menu-sin-asistencia-escritorio-1366.png`
  - Móvil (360px): `docs/06-pruebas/PRU-04/evidencia-t34-t67/01-contador-menu-sin-asistencia-marco-360.png`
  - *Detalle:* El botón `Nuevo` está ubicado exclusivamente en la barra de resultados (sección E, debajo de los filtros), sin invadir la barra de título de «Clases de hoy».
- **02. Filtro nuevo «Sin presentes» (solo dueño):**
  - Escritorio (1366px): `docs/06-pruebas/PRU-04/evidencia-t34-t67/02-filtro-sin-presentes-escritorio-1366.png`
  - Móvil (360px): `docs/06-pruebas/PRU-04/evidencia-t34-t67/02-filtro-sin-presentes-marco-360.png`
- **03. Lista de asistencia con DNI recortado y planes activos:**
  - Escritorio (1366px): `docs/06-pruebas/PRU-04/evidencia-t34-t67/03-lista-asistencia-dni-recortado-escritorio-1366.png`
  - Móvil (360px): `docs/06-pruebas/PRU-04/evidencia-t34-t67/03-lista-asistencia-dni-recortado-marco-360.png`
  - *Detalle:* Alumnos con `GrupoPlan` y `AlumnoPlan` activos (`0/2`), DNI recortado (`…543` y `…456`), sin estado `Sin plan`.
