# T81 — Teléfono en Alumnos y en Cobranza en lugar del DNI

**Fecha:** 25/10/2026  
**Autor:** Gemini CAB  
**Pedido de Carlos:**
> «El teléfono tiene que verse en el listado de Alumnos, y también en Cobranza. En Cobranza no hay lugar, así que se saca la columna DNI y en su lugar va el teléfono. Cobranza se usa como lista para llamar (caso CBZ-019). Hoy hay que entrar a cada ficha para ver el número.
> Qué teléfono:
> 1. El que el club usa para llamar: el celular del alumno.
> 2. Si es menor sin celular propio, el del tutor. Desde A26 se guarda el del tutor.
> 3. Si no hay ninguno, la celda queda vacía y no muestra «Sin teléfono».
> La búsqueda por DNI tiene que seguir funcionando aunque la columna no se vea.»

---

## 1. Cambios implementados

### A. Modelo `Alumno` (`app/Models/Alumno.php`)
- Se incorporó el accessor `telefono_contacto` (`getTelefonoContactoAttribute`):
  ```php
  protected function telefonoContacto(): Attribute
  {
      return Attribute::make(
          get: function () {
              if (!blank($this->celular)) {
                  return $this->celular;
              }
              if (!blank($this->telefono_tutor)) {
                  return $this->telefono_tutor;
              }
              return null;
          }
      );
  }
  ```
  - Prioriza `celular` (el que el club usa para llamar).
  - Si no tiene celular pero tiene `telefono_tutor`, toma `telefono_tutor`.
  - Si ninguno existe o están en blanco, devuelve `null`.

### B. Vista `Cobranza` (`resources/views/cobranza/index.blade.php`)
- En la celda del alumno, se reemplazó la visualización del `DNI: ...` por el teléfono de contacto:
  ```blade
  @if($alumno->telefono_contacto)
      <div style="font-size:0.72rem; font-weight:400; color:var(--color-text-muted); margin-top:2px;">
          {{ $alumno->telefono_contacto }}
      </div>
  @endif
  ```
- Si el alumno no tiene teléfono cargado, no se renderiza nada (queda la celda limpia, sin leyendas como "Sin teléfono" o guiones).
- Se preserva el `<colgroup>` intacto y la regla de `table-layout: fixed; min-width: 860px;` (no se agregó columna extra).
- La lógica de negocio y cálculo de cobranza no fue modificada.

### C. Vista `Alumnos` (`resources/views/alumnos/index.blade.php`)
- En las tarjetas del listado de alumnos, la etiqueta `Celular:` se actualizó a `Teléfono:` mostrando `$alumno->telefono_contacto ?? '–'`.
- El campo `DNI` en la tarjeta se mantiene visible para identificar inequívocamente al alumno.
- El buscador de cabecera `placeholder="Buscar por nombre, apellido o DNI..."` y el autocomplete por DNI (`AlumnoWebController::autocomplete`) continúan funcionando perfectamente.

---

## 2. Reglas de diseño y no regresión

- Cero JavaScript incrustado (`<script>` o manejadores inline) en Blade (`AGENTS.md §5-bis`).
- Tokens visuales existentes respetados (`var(--color-text-muted)`).
- Estructura y grilla de diseño conservadas.

---

## 3. Pruebas automatizadas

Se creó el archivo de pruebas [`tests/Feature/TelefonoAlumnosYCobranzaT81Test.php`](../../../tests/Feature/TelefonoAlumnosYCobranzaT81Test.php) con 4 métodos y 28 aserciones:
1. `test_alumno_con_celular_muestra_su_celular_en_alumnos_y_en_cobranza`: verifica que con celular cargado, se muestra en Alumnos (`Teléfono:`) y en Cobranza bajo el nombre sin mostrar `DNI:`.
2. `test_alumno_menor_sin_celular_muestra_el_telefono_del_tutor_en_alumnos_y_en_cobranza`: verifica que para un menor sin celular propio, se muestre el teléfono del tutor en ambas vistas.
3. `test_alumno_sin_telefono_muestra_guion_en_alumnos_y_nada_en_cobranza`: verifica que si no tiene teléfono, en Cobranza no se imprime nada (ni leyenda ni `Sin teléfono`), y en Alumnos muestra guión `–`.
4. `test_busqueda_por_dni_en_alumnos_y_autocomplete_sigue_funcionando`: verifica que la búsqueda por DNI y el autocomplete sigan encontrando al alumno.

### Corrida de suite y guardianes:
- `TelefonoAlumnosYCobranzaT81Test`: **4 aprobadas** (28 aserciones) en `wings_testing_gemini`.
- `DocumentacionNoMienteTest`: **aprobado** (1051 pruebas declaradas en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`).
- `TableroAlDiaTest`: **aprobado**.
- `CspSinCodigoIncrustadoTest`: **aprobado**.
- Suite rápida (`--exclude-group=lento`): **925 aprobadas, 6 omitidas, 0 fallidas** (7.421 aserciones).

---

## 4. Capturas en Chrome real (1366 px y 375 px)

Capturas tomadas con Chrome headless emulando escritorio y móvil sobre el servidor de pruebas local, guardadas en [`docs/06-pruebas/PRU-04/evidencia-t81/`](evidencia-t81/):
- **Alumnos (1366 px):** [`alumnos-index-1366.png`](evidencia-t81/alumnos-index-1366.png)
- **Cobranza (1366 px):** [`cobranza-index-1366.png`](evidencia-t81/cobranza-index-1366.png) (muestra teléfono debajo del nombre del alumno en lugar de DNI)
- **Alumnos (375 px):** [`alumnos-index-375.png`](evidencia-t81/alumnos-index-375.png)
- **Cobranza (375 px):** [`cobranza-index-375.png`](evidencia-t81/cobranza-index-375.png)

---

## 5. Próximo paso

- **Visual:** Aprobado por Carlos el 25/10/2026 («Las imagenes estan OK»).
- **Lógica:** Falta verificación de lógica por otro agente (Codex) conforme a `AGENTS.md §6a`.
