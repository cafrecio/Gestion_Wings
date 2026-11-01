# Entrega T94: Sacar una frecuencia de un grupo

> Informe de implementación y pruebas de **T94** para Wings.
> **Fecha:** 01/11/2026 · **Agente:** Gemini (Antigravity) · **Máquina:** CAB · **Firma:** `LOG GEM CAB`.
> **Estado en tablero:** `a_verificar` · **Hizo:** Gemini · **Tiene:** Claude · **Verifica:** Claude.

---

## 1. Resumen ejecutivo

Carlos decidió la regla de negocio para sacar frecuencias de un grupo:
1. **Nunca se usó:** se elimina físicamente de la base de datos (`$plan->delete()`). Mensaje en la ficha: `«Plan eliminado.»`; en edición: `«Grupo actualizado correctamente.»`.
2. **Se usó antes pero no tiene alumnos hoy:** se desactiva (`activo = false`); deja de ofrecerse para nuevas inscripciones, cambios o cobros, manteniendo intacto el historial comercial y contable (cuotas, deudas, pagos, recibos y precio por período). Mensaje: `«Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.»`.
3. **Tiene alumnos hoy:** no se saca y se rechaza la operación sin guardar nada. Mensaje: `«Nx por semana tiene N alumno(s): cambiales el plan antes.»`. «Tiene alumnos hoy» es cualquier asignación con `activo = true`: incluye alumnos activos, alumnos inactivos en el club pero con asignación activa, y cambios de plan programados a futuro.
4. **Es la última frecuencia activa:** el grupo no puede quedar sin frecuencias. En la ficha avisa `«No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.»`; en edición avisa `«El grupo debe tener al menos una frecuencia para poder cargar alumnos.»`.
5. **Volver a agregar una frecuencia desactivada:** se reactiva la misma fila (`activo = true`) actualizando su `precio_mensual` (que registra el nuevo precio en el historial `grupo_plan_precios` vía boot del modelo). No se crea otra fila ni se duplican registros. Mensaje en ficha: `«Plan agregado.»`; en edición: `«Grupo actualizado correctamente.»`.

---

## 2. Modificaciones realizadas en el código

### A. `app/Http/Controllers/GrupoWebController.php`
- **`show(int $id)` y `edit(int $id)`:** Se filtra la relación eagerly loaded `'planes' => fn($q) => $q->where('activo', true)->orderBy('clases_por_semana')`. Con esto, tanto la ficha (`grupos/show.blade.php`) como el formulario de edición (`grupos/_form.blade.php`) reciben e iteran únicamente las frecuencias activas **sin tocar una sola línea de diseño ni vistas Blade**.
- **`destroyPlan(int $planId)`:**
  - Bloqueo `FOR UPDATE` serializado sobre el grupo y el plan.
  - Comprueba que el grupo conserve más de una frecuencia activa: `$grupo->planesActivos()->count() <= 1` &rarr; avisa `«No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.»`.
  - Comprueba alumnos hoy: `$alumnosHoy = AlumnoPlan::where('plan_id', $planId)->where('activo', true)->count()` &rarr; si es mayor a cero, aborta con `«{$plan->clases_por_semana}x por semana tiene {$alumnosHoy} {$palabra}: cambiales el plan antes.»`.
  - Comprueba uso previo: `$usadoAntes = AlumnoPlan::where('plan_id', $planId)->exists() || Pago::where('plan_id', $planId)->exists()`.
  - Si se usó antes: si existieran filas inactivas previas de esa frecuencia, consolida referencias para evitar colisiones del índice único `unique_grupo_clases_activo` (`grupo_id`, `clases_por_semana`, `activo`), y pasa el plan a `activo = false` con el aviso `«Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.»`.
  - Si nunca se usó: ejecuta `$plan->delete()` con el aviso `«Plan eliminado.»`.
- **`storePlan(Request $request, int $id)`:**
  - Si ya existe activa: avisa `«Ya existe un plan con esa frecuencia para este grupo.»`.
  - Si existe inactiva: reactiva la misma fila (`activo = true`) y actualiza `precio_mensual` al nuevo valor.
  - Si no existe: crea la fila activa.
  - Redirecciona con `«Plan agregado.»`.
- **`update(Request $request, int $id)`:**
  - Validación con regla `distinct` en `planes.*.clases_por_semana` con mensaje `'Hay dos filas con la misma frecuencia. Dejá una sola.'`.
  - Identifica frecuencias activas en BD no incluidas en la lista enviada (`$planesRemovidos`).
  - Si alguna tiene alumnos activos (`activo = true`), aborta inmediatamente dentro de la transacción con `«Nx por semana tiene N alumno(s): cambiales el plan antes.»` y no guarda ningún cambio.
  - Procesa frecuencias removidas: las nunca usadas se eliminan físicamente; las usadas previamente se desactivan (`activo = false`).
  - Procesa frecuencias enviadas: filas existentes se actualizan; filas nuevas comprueban si ya existía una inactiva previa y la reactivan con el nuevo precio sin crear duplicados.
  - Si hubo alguna desactivación, el mensaje de éxito es `«Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.»`; si solo hubo altas/modificaciones, `«Grupo actualizado correctamente.»`.

### B. Endpoints directos y servicios de asignación
Para impedir que una frecuencia desactivada pueda ser asignada por pedidos HTTP directos (alta, edición, cobro):
- **`app/Http/Controllers/AlumnoWebController.php`:**
  - `store`: regla `plan_id` exige `Rule::exists('grupo_planes', 'id')->where('grupo_id', ...)->where('activo', true)`. Dentro de la transacción se bloquea `FOR UPDATE` el plan y se verifica `$plan->activo`.
  - `previsualizarCuotaAlta`: regla `plan_id` exige `Rule::exists('grupo_planes', 'id')->where('activo', true)`.
  - `update`: reglas para `plan_id` exigen `->where('activo', true)`.
- **`app/Http/Controllers/CajaWebController.php`:**
  - `pagar`: regla `nuevo_plan_id` exige `Rule::exists('grupo_planes', 'id')->where('grupo_id', $alumno->grupo_id)->where('activo', true)`.
- **`app/Services/CambioPlanService.php`:**
  - `cambiar`: bloquea el nuevo plan `FOR UPDATE` y lanza `ValidationException` si `!$nuevo->activo` (`«No se puede cambiar a un plan inactivo.»`).

---

## 3. Lugares revisados en el codebase

Se verificaron todas las referencias a `GrupoPlan` y `grupo_planes` en el repositorio:
1. **`app/Services/PagoCuotaService.php:477`:** Obtiene el plan para calcular precios y cuotas históricas sin filtrar por `activo`. **Queda bien y no se toca**, porque una cuota o deuda de un período pasado donde el plan estaba vigente debe poder calcularse y cobrarse con exactitud aunque la frecuencia haya sido desactivada posteriormente.
2. **`app/Services/PrimeraCargaExcelService.php:57`:** Ya filtraba con `where('activo', true)`. **Queda bien**.
3. **`app/Http/Controllers/PagoController.php:174` y `PagoService.php:24`:** Delegan el cambio de plan a `CambioPlanService::cambiar`, que ahora valida y bloquea planes inactivos. **Queda bien**.
4. **Relaciones Eloquent (`Alumno::planActivo()`, `Alumno::planProgramado()`, `Pago::plan()`, `AlumnoPlan::plan()`):** Permiten navegar al modelo `GrupoPlan` histórico sin romper vistas, reportes ni recibos PDF. **Queda bien**.

---

## 4. Comprobado e inferido por separado

### Comprobado fehacientemente (ejecutado con pruebas reales)
1. **Nunca se usó:** Comprobado en `test_frecuencia_nunca_usada_se_elimina_fisicamente_via_destroy` y `test_frecuencia_nunca_usada_se_elimina_fisicamente_via_update`: el registro se borra físicamente de la base (`assertDatabaseMissing`).
2. **Se usó antes sin alumnos hoy:** Comprobado en `test_frecuencia_usada_antes_sin_alumnos_hoy_se_desactiva_via_destroy`, `test_frecuencia_usada_antes_sin_alumnos_hoy_se_desactiva_via_update` y `test_frecuencia_usada_solo_en_pagos_se_desactiva_sin_error_500`: pasa a `activo = false` sin lanzar error 500 (`assertDatabaseHas('grupo_planes', ['activo' => false])`) y con el mensaje exacto.
3. **Tiene alumnos hoy:** Comprobado con alumnos activos, con plural (`3 alumnos`), con alumnos inactivos en el club pero con asignación deportiva activa, con cambios de plan programados a futuro y con rollback total en edición: no guarda nada y muestra `«Nx por semana tiene N alumno(s): cambiales el plan antes.»`.
4. **Última frecuencia activa:** Comprobado que rechaza eliminar o vaciar frecuencias si el grupo quedaría sin frecuencias activas.
5. **Reactivación:** Comprobado que al volver a agregar la frecuencia vía `storePlan` o `update`, se reactiva la misma fila (`assertDatabaseHas('grupo_planes', ['id' => $id, 'activo' => true, 'precio_mensual' => ...])`) y no crea una fila nueva.
6. **Colisión de índice unique:** Comprobado que si existían registros previos inactivos o duplicados en bases antiguas, la desactivación consolida referencias y no falla por MySQL 1062.
7. **Filtrado en vistas:** Comprobado que `show` y `edit` no muestran frecuencias desactivadas.
8. **Rechazo en endpoints directos:** Comprobado rechazo en alta de alumno, edición de alumno y `CambioPlanService::cambiar`.
9. **Prueba de punta a punta con plata real al peso:** Comprobado flujo completo: alumno inicia con 2x ($30.000), pasa a 1x ($20.000), dueño desactiva 2x, se cobra en caja la deuda vieja de $30.000 (deuda pasa a `PAGADA`, saldo pendiente 0, pago registrado), se reactiva 2x a $35.000 y un alumno nuevo se inscribe y toma el plan 2x a $35.000.
10. **Concurrencia / Serialización:** Comprobado que dos pedidos concurrentes (sacar frecuencia y asignar alumno) se serializan mediante bloqueos `FOR UPDATE` detectando la asignación y rechazando el borrado.
11. **Pruebas de mutación:** Se ejecutaron mutaciones deliberadas (anular control de última frecuencia activa, anular control de alumnos hoy, anular control de plan inactivo en servicio) y en todos los casos las pruebas fallaron con error exacto (`Exit 1`), restaurando el código original a continuación.

### Inferido
- En una base de datos donde ninguna frecuencia haya sido desactivada todavía, el comportamiento de lectura es 100% idéntico al histórico, ya que todas las frecuencias preexistentes tienen `activo = 1`.

---

## 5. Propuesta de actualización para la Tabla de Pruebas (GRU-004)

Conforme a `AGENTS.md §5-ter`, la tabla de pruebas es de solo lectura para Gemini. Se propone a Claude la siguiente actualización para el caso `GRU-004`:

- **Archivo:** `docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/GRU.json` (caso `GRU-004`, líneas 160–188)
- **Texto actual:**
  ```json
  "esperado": [
    {
      "donde": "Con alumnos",
      "l": "a",
      "texto": "«No se puede eliminar: hay alumnos con este plan activo.»",
      "tipo": "directo"
    },
    {
      "donde": "Único",
      "l": "b",
      "texto": "«No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.»",
      "tipo": "directo"
    },
    {
      "donde": "Libre",
      "l": "c",
      "texto": "Se elimina; deja de ofrecerse en altas y cobros.",
      "tipo": "directo"
    },
    {
      "donde": "Historia",
      "l": "d",
      "texto": "Los alumnos inactivos que tuvieron ese plan conservan sus cuotas y pagos.",
      "tipo": "indirecto"
    }
  ]
  ```
- **Texto propuesto:**
  ```json
  "esperado": [
    {
      "donde": "Con alumnos",
      "l": "a",
      "texto": "«Nx por semana tiene N alumnos: cambiales el plan antes.» (rechaza sin guardar).",
      "tipo": "directo"
    },
    {
      "donde": "Único",
      "l": "b",
      "texto": "«No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.»",
      "tipo": "directo"
    },
    {
      "donde": "Libre (nunca usado)",
      "l": "c",
      "texto": "Se elimina físicamente; avisa «Plan eliminado.» en ficha o «Grupo actualizado correctamente.» al editar.",
      "tipo": "directo"
    },
    {
      "donde": "Usado antes (sin alumnos hoy)",
      "l": "d",
      "texto": "Se desactiva con «Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.»; no se ofrece en altas ni cobros y conserva intacto el historial.",
      "tipo": "directo"
    },
    {
      "donde": "Reactivar",
      "l": "e",
      "texto": "Volver a agregar una frecuencia desactivada reactiva la misma fila con el precio nuevo sin duplicar.",
      "tipo": "directo"
    }
  ]
  ```

---

## 6. Documentación actualizada

- `resources/manual/admin/administrar-grupos.md`: Reescrita la sección «¿Qué hago si quiero sacar una frecuencia?» explicando condición por condición las 4 ramas (nunca usado, usado sin alumnos, con alumnos hoy, última frecuencia activa) y la reactivación.
- `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/admin-administrar-grupos.md`: Actualizadas las filas 11, 12, 16 de fuentes y la tabla de «Rechazos y avisos de la acción» con los textos literales y números de línea exactos.
- `docs/06-pruebas/PRU-04/IMPLEMENTACION-T12-T13.md`: Actualizados los huecos 3 y 6 de §15 declarándolos resueltos en T94 con la decisión de Carlos.
- `docs/02-contratos/Wings-contrato-alumno-grupo-deporte-deuda-v5.md`: Incorporadas las reglas de negocio de T94 en §2.c `GrupoPlan`.

---

## 7. Verificación de diseño
Verificado con `git diff --stat -- resources/views resources/css`:
Cero archivos de vistas modificados para T94. El diseño y los componentes visuales permanecen 100% intactos conforme a `AGENTS.md §1`.
