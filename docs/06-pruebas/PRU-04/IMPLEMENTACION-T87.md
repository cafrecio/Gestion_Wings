# Implementación T87 — Nueva liquidación cuenta solo las clases del mes elegido

> **Actualización 28/10/2026:** Carlos aprobó el aspecto: «T87 Visualmente OK, asumo que el funcionamiento logico esta bien». Con la lógica ya aprobada por Claude el27/10, T87 queda cerrada en el tablero. [Galería revisada](APROBAR-T87.html). El informe de abajo conserva su fecha y resultados originales.

**Fecha:** 27/10/2026  
**Autor:** Gemini CAB  
**Estado:** `a_verificar`  
**Verificador asignado:** Claude  

---

## 1. Contexto y problema

En la pantalla **Liquidaciones → Nueva** (`/liquidaciones/crear`), al lado del nombre de cada profesor figuraba el indicador «N clases liquidables». Dicho número sumaba todas las clases pendientes históricas de todos los meses, en lugar de considerar únicamente el mes y año seleccionados en el formulario (`#mes` y `#anio`).

El mismo comportamiento erróneo ocurría con el banner de advertencia para clases pasadas sin asistencia:
> «{Profesor} tiene N clases sin asistencia cargada que no se incluirán en esta liquidación.»

Ese total también acumulaba clases de todos los meses previos.

### El caso concreto en el sitio de prueba (Día 22)
El 01/11/2026, la pantalla mostró para la profesora Lucía Méndez «20 clases liquidables» teniendo seleccionado el mes de octubre. Al generarse la liquidación, el cálculo real (`LiquidacionService`) liquidó 17 clases por \$204.000 (el valor exacto correspondiente a octubre), dado que las 3 clases restantes pertenecían a septiembre.

Aunque el backend de generación liquidaba correctamente el período, la interfaz generaba confusión al prometer 20 clases e incluir clases de meses previos en el conteo y aviso.

---

## 2. Decisión de Carlos (27/10/2026)

1. El número de clases liquidables y el del aviso cuentan **únicamente las clases del mes y año elegidos**, bajo las mismas reglas canónicas:
   - Clases no canceladas.
   - Validadas o con al menos un presente.
   - Que no pertenezcan a una liquidación cerrada.
   - Para el aviso de sin asistencia: clases pasadas, sin validar y sin asistencias registradas.
2. Al cambiar el mes o año en el selector, **ambos números se actualizan reactivamente en el cliente**, sin recargar la página.
3. **No se alteran los profesores habilitados ni deshabilitados:** el radio button permanece deshabilitado únicamente si el profesor tiene 0 clases en todos los meses (`clases_count_total === 0`). Si tiene clases en algún mes pero 0 en el mes seleccionado, permanece seleccionable mostrando «0 clases liquidables».
4. El cálculo de la liquidación generada (`LiquidacionService`), los textos y el diseño visual no se modifican.
5. Prohibido código JavaScript inline en Blade (AGENTS.md §5-bis). Toda la reactividad vive en `resources/js/liquidaciones-create.js`.

---

## 3. Modificaciones realizadas

### A. Controlador (`app/Http/Controllers/LiquidacionWebController.php`)
- En el método `create(Request $request)`:
  - Se obtienen `$mesActual` y `$anioActual` (desde `$request` o por defecto el mes y año corrientes de Wings).
  - Se calcula `$clases_count_total` por profesor (conteo histórico de todas las clases liquidables de cualquier mes) para fijar el atributo `disabled` de la vista.
  - Se calculan `$clases_count` y `$clases_sin_asistencia` filtrando por `anio = $anioActual` y `mes = $mesActual`.
  - Se construye un desglose por período (`periodos: { "YYYY-M": { clases: N, sin_asistencia: M } }`) y totales globales dentro de `$profesoresJson` para su consumo reactivo en JavaScript desde `#liquidacion-create-datos`.

### B. Vista Blade (`resources/views/liquidaciones/create.blade.php`)
- En el radio button:
  `disabled="{{ ($profesor->clases_count_total ?? $count) === 0 ? 'disabled' : '' }}"`
- Se agregaron las clases semánticas `.profesor-clases-count` y `.profesor-sin-asistencia-aviso` a los `<span>` correspondientes de cada fila de profesor para permitir su actualización desde JavaScript.
- Se preservaron íntegramente los tokens de diseño, espaciados y estructura HTML. No se introdujo ningún script inline.
- Autorización de diseño: `Diseno-autorizado: Carlos pidio en T87 actualizar clases liquidables y sin asistencia al cambiar mes o anio`.

### C. Cliente JavaScript (`resources/js/liquidaciones-create.js`)
- Se implementó la función `actualizarConteos()` que lee el mes y año elegidos en los campos `#mes` e `#anio`.
- Para cada fila de profesor:
  - Actualiza el texto `N clases liquidables` según el período seleccionado.
  - Actualiza el texto `· N sin asistencia` si existen clases sin asistencia en ese período.
- Para el aviso superior `#aviso-sin-asistencia`:
  - Si el profesor seleccionado tiene clases sin asistencia en el mes/año seleccionado, muestra el aviso con el conteo filtrado del período.
  - Si en ese mes no tiene clases sin asistencia, oculta el banner inmediatamente.
- Escucha los eventos `change` en `#mes` e `input`/`change` en `#anio`.
- Compilado con `npm run build`.

---

## 4. Pruebas técnicas y automatizadas

### 1. Inversión del hallazgo del día 22
- En [`docs/06-pruebas/PRU-04/prueba-tecnica/SeguridadDiasPruebaTest.php`](prueba-tecnica/SeguridadDiasPruebaTest.php), el método `test_hallazgo_dia_22_nueva_liquidacion_cuenta_clases_de_otros_meses` fue actualizado para reflejar la corrección: ahora comprueba que con 1 clase en octubre y 1 en septiembre, al solicitar la pantalla para octubre, se muestra exactamente **1 clase liquidable** (y no 2).
- Se amplió la guarda de entorno de base de datos para permitir ejecuciones bajo `wings_testing_gemini` y `wings_testing_codex`.

### 2. Prueba de integración permanente
- Creada en [`tests/Feature/NuevaLiquidacionConteoMesT87Test.php`](../../../tests/Feature/NuevaLiquidacionConteoMesT87Test.php):
  - `test_pantalla_nueva_liquidacion_cuenta_solo_el_mes_elegido_y_aviso_sin_asistencia`:
    - Recrea el escenario del día 22: Lucía con 17 clases liquidables y 2 sin asistencia en octubre, y 3 clases liquidables y 1 sin asistencia en septiembre.
    - Verifica que para octubre la pantalla entregue 17 clases liquidables y 2 sin asistencia.
    - Verifica que para septiembre la pantalla entregue 3 clases liquidables y 1 sin asistencia.
    - Verifica que el JSON entregado al frontend incluya los períodos discriminados con sus valores exactos.
  - `test_liquidacion_generada_coincide_con_el_numero_mostrado_en_pantalla`:
    - Genera la liquidación para octubre: 17 clases, \$204.000.
    - Genera la liquidación para septiembre: 3 clases, \$36.000.
    - Ambas pasan en verde con 24 aserciones.

---

## 5. Evidencia visual en navegador real (Chrome / CDP)

Las pruebas en navegador real se ejecutaron en Chrome mediante el script [`docs/06-pruebas/PRU-04/evidencia-t87/probar-t87-browser.mjs`](evidencia-t87/probar-t87-browser.mjs).

Se verificó el flujo completo:
1. Carga inicial en Octubre 2026: Lucía seleccionada muestra **17 clases liquidables** y el banner indica **2 clases sin asistencia**.
2. Cambio en vivo de mes a Septiembre (`change` select): reactivamente Lucía pasa a **3 clases liquidables** y el banner a **1 clase sin asistencia** sin recarga de página.
3. Regreso a Octubre: reactivamente vuelve a **17 clases liquidables** y banner con **2 clases sin asistencia**.

### Capturas generadas
- **Octubre 2026 (17 clases liquidables, 2 sin asistencia):**
  - Escritorio (1366 px): [`01-octubre-lucia-1366.png`](evidencia-t87/01-octubre-lucia-1366.png)
  - Celular (375 px marco): [`02-octubre-lucia-375.png`](evidencia-t87/02-octubre-lucia-375.png)
- **Septiembre 2026 (3 clases liquidables, 1 sin asistencia):**
  - Escritorio (1366 px): [`03-septiembre-lucia-1366.png`](evidencia-t87/03-septiembre-lucia-1366.png)
  - Celular (375 px marco): [`04-septiembre-lucia-375.png`](evidencia-t87/04-septiembre-lucia-375.png)
- **Vuelta a Octubre 2026 (reactivo en cliente):**
  - Escritorio (1366 px): [`05-vuelta-octubre-1366.png`](evidencia-t87/05-vuelta-octubre-1366.png)
  - Celular (375 px marco): [`06-vuelta-octubre-375.png`](evidencia-t87/06-vuelta-octubre-375.png)
