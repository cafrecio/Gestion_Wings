# Implementación T54: Buscar alumnos como busca la gente

> **Actualización 28/10/2026:** Carlos aprobó el aspecto: «T54 OK». Con la lógica ya aprobada por Claude el27/10, T54 queda cerrada en el tablero. [Galería revisada](APROBAR-T54.html). El informe de abajo conserva su fecha y resultados originales.

**Fecha:** 2026-10-27  
**Responsable:** Gemini (CAB)  
**Verificador:** Claude  
**Referencias:** DU-13, Caso ALU-020 (`docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/ALU.json`)

---

## 1. Resumen y Alcance

En respuesta a la decisión de Carlos (DU-13) y lo especificado en el catálogo ALU-020, se unificó y potenció el mecanismo de búsqueda de alumnos en todo el sistema para alinearlo con el comportamiento natural de los usuarios:
1. **Autocompletar desde la primera letra:** El desplegable ofrece sugerencias inmediatas con solo tipear 1 carácter.
2. **Insensibilidad a acentos y mayúsculas/minúsculas:** Escribir «Gomez» encuentra «Gómez», «álvarez» encuentra «Álvarez», y «n»/«ñ» son tratados equivalentemente según el cotejamiento de base de datos.
3. **Múltiples palabras en cualquier orden:** Escribir «perez juan» o «juan perez» encuentra a Juan Pérez. Cada término ingresado debe coincidir en cualquier parte del nombre, apellido o DNI.
4. **Criterio único y reutilizable:** Implementado a través del scope `scopeBuscar($query, ?string $termino)` en `app/Models/Alumno.php` y reutilizado en:
   - Listado web de alumnos (`AlumnoWebController::index`)
   - Endpoint de autocompletar (`AlumnoWebController::autocomplete`)
   - Búsqueda para cobro de cuota (`CajaWebController::cobrarCuotaSelect`)
   - Controlador API de Alumnos (`Admin\AlumnoController::index`)

---

## 2. Cambios Realizados

### Backend

1. **`app/Models/Alumno.php`:**
   - Se añadió el query scope `scopeBuscar($query, ?string $termino)`:
     ```php
     public function scopeBuscar($query, ?string $termino)
     {
         $termino = trim((string) $termino);
         if ($termino === '') {
             return $query;
         }

         $tokens = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY);
         if (empty($tokens)) {
             return $query;
         }

         return $query->where(function ($q) use ($tokens) {
             foreach ($tokens as $token) {
                 $escaped = addcslashes($token, '%_\\');
                 $q->where(function ($sub) use ($escaped) {
                     $sub->where('nombre', 'like', "%{$escaped}%")
                         ->orWhere('apellido', 'like', "%{$escaped}%")
                         ->orWhere('dni', 'like', "%{$escaped}%");
                 });
             }
         });
     }
     ```
   - Cada palabra ingresada añade una cláusula `AND` donde dicho token debe coincidir en `nombre`, `apellido` o `DNI`.

2. **`app/Http/Controllers/AlumnoWebController.php`:**
   - En `index()`: Se reemplazó la búsqueda manual por `$query->buscar($request->input('search'))`.
   - En `autocomplete()`: Se actualizó el umbral de longitud a `mb_strlen($q) < 1` (antes `< 2`), y se aplica `$query->buscar($q)` con límite de 8 resultados (`limit(8)`).

3. **`app/Http/Controllers/CajaWebController.php`:**
   - En `cobrarCuotaSelect()`: Cuando `$request->filled('search')`, se aplica `$query->buscar($request->input('search'))`. Si la búsqueda está vacía, se preserva la lógica de T29 (`$query->whereIn('id', $conDeudaIds)`).

4. **`app/Http/Controllers/Admin/AlumnoController.php`:**
   - En `index()`: Se actualizó la búsqueda para usar `$query->buscar($request->input('search'))`. Aunque `routes/api.php` se encuentra deshabilitada desde la auditoría de seguridad S2, se mantuvo la paridad para consistencia arquitectónica.

### Frontend

1. **`resources/js/alumnos-index.js`:**
   - En el listener `input` del campo de búsqueda:
     ```javascript
     const q = searchInput.value.trim();
     if (q.length < 1) {
         cerrar();
         return;
     }
     ```
     (Se cambió `q.length < 2` a `q.length < 1`). Se mantiene el debounce existente para no saturar el servidor al tipear.
   - Compilado con `npm run build` sin errores.

---

## 3. Comportamientos y Casos Específicos Verificados

- **Acentos y diacríticos:** Se verificó empíricamente contra MariaDB con collation `utf8mb4_unicode_ci` que `o = ó`, `a = á`, `n = ñ`, etc., son evaluados como idénticos en cláusulas `LIKE`.
- **Filtros combinados:** La búsqueda por texto se combina correctamente con los filtros de deporte, grupo y estado (activo/inactivo).
- **Paginación:** La paginación mantiene el término de búsqueda en los enlaces mediante `withQueryString()`.
- **Homónimos:** Alumnos con nombres idénticos se discriminan mostrando su DNI y grupo tanto en el listado como en el autocompletar.
- **Cobrar cuota (T29):** Sin término de búsqueda, se muestran exclusivamente los alumnos con deuda activa. Al escribir un término de búsqueda, busca sobre todos los alumnos activos.
- **Rendimiento:** Medido con más de 300 alumnos en base de datos. La respuesta del endpoint `autocomplete` promedió 80 ms, muy por debajo del umbral de 500 ms.
- **Seguridad e integridad visual:** Ninguna vista Blade fue modificada. Cero código JavaScript incrustado (`AGENTS.md` §5-bis).

---

## 4. Pruebas Automatizadas

Se creó la suite integral en `tests/Feature/BusquedaAlumnosT54Test.php`:
1. `test_busqueda_insensible_a_acentos_y_mayusculas_en_listado`: Busca «gomez» y encuentra «Gómez», «alvarez» encuentra «Álvarez».
2. `test_autocompletar_funciona_desde_la_primera_letra`: Busca con «g» y retorna resultados con DNI y grupo; con cadena vacía retorna `[]`.
3. `test_busqueda_multiples_palabras_en_cualquier_orden`: «perez juan» y «juan perez» devuelven a Juan Pérez.
4. `test_busqueda_por_dni_y_combinada_nombre_dni`: Búsqueda por DNI parcial o combinación «351 juan».
5. `test_busqueda_se_combina_con_filtros_deporte_grupo_y_estado`: Búsqueda combinada con `deporte_id`, `grupo_id` y `activo`.
6. `test_paginacion_conserva_parametros_de_busqueda`: Enlaces de paginación contienen `search=gomez`.
7. `test_homonimos_se_distinguen_por_dni_y_grupo`: Dos alumnos con mismo nombre muestran DNI y grupo diferentes.
8. `test_busqueda_sin_resultados_muestra_estado_vacio_sin_error`: Búsqueda inexistente muestra mensaje amigable sin errores.
9. `test_cobrar_cuota_select_busqueda_con_y_sin_termino`: Vacío muestra solo deudores; con término busca en todos los activos con coincidencia insensible a acentos.
10. `test_autocompletar_rendimiento_con_mas_de_300_alumnos`: Inserción masiva de 300 alumnos, autocompletar ejecuta en < 500ms y limita a 8 resultados.

Resultado de la suite: **10 pruebas pasadas, 80 aserciones, 0 fallas.**  
Suite rápida ejecutada: **1021 pruebas aprobadas, 6 omitidas.**

---

## 5. Evidencia Visual

Generadas con Google Chrome en entorno real (escritorio 1366 px y móvil 375 px dentro de marco):
- `docs/06-pruebas/PRU-04/evidencia-t54/01-desplegable-una-letra-1366.png` y `375.png`: Desplegable de autocompletar activándose con la letra «g».
- `docs/06-pruebas/PRU-04/evidencia-t54/02-gomez-encuentra-gomez-1366.png` y `375.png`: Búsqueda «gomez» encontrando a «Gómez, Martín».
- `docs/06-pruebas/PRU-04/evidencia-t54/03-perez-juan-1366.png` y `375.png`: Búsqueda «perez juan» encontrando a «Pérez, Juan».

---

## 6. Devolución 27/10: Historial de caja y descarte en autocompletar

Tras la verificación de Claude (`docs/06-pruebas/PRU-04/VERIFICACION-T54-CLAUDE.md`), se atendieron los dos puntos observados:

### 1. Historial de caja (`CajaWebController::historial`)
- **Diagnóstico:** El filtrado se realizaba en memoria PHP mediante `str_contains(mb_strtolower(...))` sobre filas ya formateadas (`$f->alumno`, `$f->obs`, `$f->subrubro`). Esto fallaba ante acentos («gomez» vs «Gómez») y múltiples palabras desordenadas («perez juan» vs «Pérez, Juan»).
- **Solución implementada:** Se utilizó `Str::ascii()` y `strtolower()` para normalizar tanto los tokens de búsqueda ingresados como el texto consolidado de la fila (`alumno`, `dni`, `obs`, `subrubro`). Se tokeniza el término de búsqueda por espacios y se exige que cada token esté contenido en el texto normalizado de la fila. De esta forma:
  - «gomez» y «GOMEZ» encuentran «Gómez, Martín».
  - «alvarez» y «ÁLVAREZ» encuentran «Álvarez, Pedro».
  - «perez juan» y «juan perez» encuentran «Pérez, Juan».
  - La búsqueda por DNI o combinación de DNI con nombre también funciona.
  - Se mantiene la compatibilidad con los filtros de tipo (`INGRESO`/`EGRESO`), paginación (preservando `search=...` en los enlaces de página) y la visualización de movimientos cancelados para el ADMIN.

### 2. Descarte de respuestas desactualizadas en autocompletar (`resources/js/alumnos-index.js`)
- En el listener de entrada del autocompletar, al resolverse la promesa de `fetch`:
  ```javascript
  .then(data => {
      // Descartar respuestas si el texto del campo ya no coincide con lo pedido
      if (searchInput.value.trim() !== q) {
          return;
      }
      renderResults(data);
  })
  ```
  Si el texto del campo cambió mientras la petición estaba en vuelo, o si el usuario borró el campo, la respuesta demorada se descarta de inmediato evitando que resultados viejos (por ejemplo de «g») pisen o se muestren cuando el usuario ya escribió «go» o vació el campo.

### 3. Relevamiento completo de filtros por nombre de alumno
Se realizó un relevamiento exhaustivo en todo el código fuente mediante búsqueda de expresiones regulares y `codebase-memory`:
- **Puntos alcanzados y unificados por T54:**
  1. `AlumnoWebController::index` (listado web de alumnos, vía `Alumno::scopeBuscar`)
  2. `AlumnoWebController::autocomplete` (desplegable de búsqueda predictiva desde 1 letra, vía `Alumno::scopeBuscar`)
  3. `CajaWebController::cobrarCuotaSelect` (buscador en Cobrar cuota, vía `Alumno::scopeBuscar`, respetando T29)
  4. `Admin\AlumnoController::index` (controlador API para consistencia arquitectónica)
  5. `CajaWebController::historial` (historial de movimientos de caja en PHP mediante normalización `Str::ascii`)
  6. `resources/js/alumnos-index.js` (autocompletar desde 1 letra y protección contra respuestas asíncronas desordenadas)
- **Puntos relevados que NO corresponden a T54:**
  - `ProfesorWebController::index`: Búsqueda de profesores (no alumnos).
  - `CobranzaWebController::index`: Filtrado únicamente por selects desplegables (estado, deporte, grupo); no tiene campo de búsqueda por texto.

### 4. Nuevas pruebas permanentes
Se añadieron 4 pruebas permanentes en `tests/Feature/BusquedaAlumnosT54Test.php`:
- `test_historial_caja_busqueda_con_y_sin_acento_y_mayusculas`: Verificación en rojo previo al arreglo y en verde tras el mismo.
- `test_historial_caja_busqueda_varias_palabras_en_cualquier_orden`: Ambos órdenes de nombres y apellidos.
- `test_historial_caja_busqueda_se_combina_con_filtro_de_tipo_y_paginacion`: Interacción con tipos de movimiento y enlaces de paginación.
- `test_historial_caja_busqueda_incluye_movimientos_cancelados`: Movimientos cancelados visibles para el ADMIN.

**Total suite T54:** 14 pruebas, 110 aserciones, todas aprobadas.  
**Suite rápida:** 1032 pruebas aprobadas, 6 omitidas. Contador actualizado a 1157 en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`.
