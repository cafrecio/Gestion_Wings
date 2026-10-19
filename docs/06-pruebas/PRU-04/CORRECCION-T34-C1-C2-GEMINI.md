# Corrección de C1 y C2 en T34 — Gemini (CyE)

Fecha: 19/10/2026  
Autor: Gemini (CyE) — `LOG GEM CYE`  
Verificador asignado: Codex CyE  
Documentos de referencia:
- `docs/06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md` (Codex CyE, commit `ba471ce2`)
- `docs/00-estado/ESTADO-ACTUAL.md`
- AGENTS.md §6-ter

---

## 1. Resumen Ejecutivo

En la verificación de T34/T67 realizada por Codex CyE (`ba471ce2`), los criterios **H1** (etiqueta en tres estados en tarjeta de clase) y el **PATCH de H2** (protección de clases que integran liquidación CERRADA) resultaron **aprobados**.
Se registraron dos hallazgos preexistentes en los criterios c/f:
- **C1:** El View Composer global en `AppServiceProvider` recalculaba el `count()` de clases pendientes para cada tarjeta, botón y componente de la vista (33→75 consultas en el listado).
- **C2:** En `LiquidacionWebController::store`, la opción `incluir_hoy` validaba clases de hoy antes de invocar el servicio de liquidación, fuera de transacción y sin validar si hoy correspondía al período solicitado ni si la clase integraba una liquidación cerrada.

Ambos defectos fueron corregidos de raíz respetando estrictamente todas las restricciones del proyecto.

---

## 2. Detalle de las Correcciones

### C1 — Consultas por tarjeta (`app/Providers/AppServiceProvider.php`)

- **Problema:** `View::composer('*', ...)` se dispara por cada vista, layout, componente Blade y subvista renderizada en la respuesta. Al evaluar el `count()` de clases pendientes sin memoización por petición, una página con 16 tarjetas disparaba 56 veces la misma consulta SQL.
- **Solución implementada:**
  - Se utiliza el almacenamiento de atributos del request actual (`$request->attributes->get('wings.badge_clases_pendientes')`).
  - En la primera vista evaluada de la petición HTTP, si no está seteado, se ejecuta la consulta (solo si `Auth::check()`) y se guarda en `$request->attributes`.
  - Las siguientes vistas, componentes y tarjetas dentro de la misma petición reutilizan el entero ya calculado sin tocar la base de datos (0 consultas adicionales).
  - Al cambiar de petición HTTP (siguiente request en navegador o siguiente llamada `$this->get()` en pruebas), Laravel instancia un nuevo `Request` limpio, recalculando el valor de forma transparente sin valores obsoletos.
  - Se preservó intacto el manejo de anónimos (`Auth::check() = false` → 0) y páginas de error sin contexto (`wings.error_sin_contexto` → 0).

### C2 — Validaciones que persisten tras un rechazo (`app/Http/Controllers/LiquidacionWebController.php`)

- **Problema:** Si se intentaba generar una liquidación con `incluir_hoy=1` para un período ya cerrado o inválido (ej. mes=9, cuando hoy es octubre), las clases de hoy se marcaban `validada_para_liquidacion = true` antes de llamar a `generarLiquidacionMensual()`. Al fallar la generación, la clase de hoy quedaba indebidamente validada en la base, y además se modificaban clases de meses futuros ajenos al período solicitado.
- **Solución implementada:**
  - Se envolvió toda la operación en `DB::transaction(...)`. Cualquier fallo o excepción en la validación o generación produce un **rollback automático**, garantizando que un rechazo no deje ninguna clase validada en la base.
  - Se restringió la validación de `incluir_hoy` estrictamente al período solicitado:
    `if ($request->boolean('incluir_hoy') && today()->month === (int) $request->mes && today()->year === (int) $request->anio)`. Si se liquida un mes pasado (ej. septiembre en octubre), `incluir_hoy` no modifica ninguna clase de hoy.
  - Para cada clase candidata de hoy, se verifica `!$clase->integraLiquidacionCerrada()`, garantizando que ninguna clase en liquidación cerrada sea modificada.
  - Generar sin `incluir_hoy` o con `incluir_hoy` en período vigente funciona de manera óptima y atómica.

---

## 3. Pruebas de Regresión Permanentes (`tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php`)

Se incorporaron 5 pruebas de regresión permanente cubriendo todos los aspectos solicitados:

1. `test_c1_consultas_no_crecen_por_tarjeta_en_listado_de_clases`:
   - Con 2 tarjetas vs 16 tarjetas: el COUNT de badge se ejecuta exactamente **1 vez** en ambas peticiones (`$cuentaCountBadgeAntes === 1` y `$cuentaCountBadgeDespues === 1`).
   - El total de consultas no se dispara por tarjeta (`$this->assertSame($antes, $despues)`).
2. `test_c1_contador_se_actualiza_entre_peticiones_sucesivas`:
   - Comprueba que en la petición 1 devuelve 0 y, tras crearse una clase pendiente, la petición 2 en el mismo proceso devuelve 1 (no queda congelado).
3. `test_c2_generacion_rechazada_no_deja_validaciones_persistidas`:
   - POST a generar liquidación de septiembre ya cerrada con `incluir_hoy=1`: falla con error y la clase de hoy permanece `validada_para_liquidacion = false`.
4. `test_c2_incluir_hoy_no_modifica_clases_ajenas_al_periodo_solicitado`:
   - Generación válida de mes=9 con `incluir_hoy=1`: la clase de octubre (hoy) permanece `validada_para_liquidacion = false`.
5. `test_c2_incluir_hoy_en_periodo_vigente_valida_clases_y_genera_correctamente`:
   - Generación de mes=10 (mes en curso) con `incluir_hoy=1`: la clase de hoy se valida (`true`) y se incluye en los detalles de la liquidación generada.

### Evidencia: Rojas antes y Verdes después

**Antes de corregir (código de producción original):**
```text
   FAIL  Tests\Feature\ClaseSinAsistenciaYDniT34T67Test
  ✓ contador menu y filtro sin asistencia y sin presentes                                                        7.95s  
  ✓ permisos filtro sin presentes                                                                                0.29s  
  ✓ liquidacion clase con lista y todos ausentes solo se paga si esta validada                                   0.13s  
  ✓ lista asistencia muestra solo ultimos tres digitos del dni                                                   0.09s  
  ✓ tarjeta muestra etiqueta adecuada segun asistencia                                                           0.37s  
  ✓ clase en liquidacion cerrada no puede desmarcar validacion                                                   0.27s  
  ⨯ c1 consultas no crecen por tarjeta en listado de clases                                                      0.46s  
  ✓ c1 contador se actualiza entre peticiones sucesivas                                                          0.11s  
  ⨯ c2 generacion rechazada no deja validaciones persistidas                                                     0.12s  
  ⨯ c2 incluir hoy no modifica clases ajenas al periodo solicitado                                               0.11s  
  ✓ c2 incluir hoy en periodo vigente valida clases y genera correctamente                                       0.12s  
  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
  FAILED: c1 consultas no crecen por tarjeta en listado de clases
  El COUNT de badge debe ejecutarse como máximo 1 vez con 2 tarjetas.
  Failed asserting that 14 is identical to 1.
  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
  FAILED: c2 generacion rechazada no deja validaciones persistidas
  Una generación rechazada no debe dejar validaciones nuevas.
  Failed asserting that true is false.
  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
  FAILED: c2 incluir hoy no modifica clases ajenas al periodo solicitado
  incluir_hoy no debe tocar clases de meses futuros/ajenos.
  Failed asserting that true is false.

  Tests: 3 failed, 8 passed (76 assertions)
```

**Después de corregir:**
```text
   PASS  Tests\Feature\ClaseSinAsistenciaYDniT34T67Test
  ✓ contador menu y filtro sin asistencia y sin presentes                                                        8.36s  
  ✓ permisos filtro sin presentes                                                                                0.21s  
  ✓ liquidacion clase con lista y todos ausentes solo se paga si esta validada                                   0.17s  
  ✓ lista asistencia muestra solo ultimos tres digitos del dni                                                   0.16s  
  ✓ tarjeta muestra etiqueta adecuada segun asistencia                                                           0.30s  
  ✓ clase en liquidacion cerrada no puede desmarcar validacion                                                   0.27s  
  ✓ c1 consultas no crecen por tarjeta en listado de clases                                                      0.44s  
  ✓ c1 contador se actualiza entre peticiones sucesivas                                                          0.14s  
  ✓ c2 generacion rechazada no deja validaciones persistidas                                                     0.17s  
  ✓ c2 incluir hoy no modifica clases ajenas al periodo solicitado                                               0.16s  
  ✓ c2 incluir hoy en periodo vigente valida clases y genera correctamente                                       0.13s  

  Tests: 11 passed (78 assertions)
  Duration: 10.68s
```

---

## 4. Corridas de Suite en `wings_testing_gemini`

1. **Suite Completa de Regresión:**
   Ejecutada sola en la máquina tras constatar la finalización de otros procesos:
   `$env:DB_DATABASE='wings_testing_gemini'; php artisan test`
   - **Resultado:** 934 pruebas aprobadas, 4 omitidas, 0 fallidas de código (8239 aserciones, 1498.66 s). Salida 0.
   - La única falla observada en la corrida previa fue el guardián documental `DocumentacionNoMienteTest` antes de sincronizar el nuevo contador a 939 pruebas.
2. **Suite Rápida (`--exclude-group=lento`):**
   `$env:DB_DATABASE='wings_testing_gemini'; php artisan test --exclude-group=lento`
   - **Resultado:** 820 aprobadas, 4 omitidas, 0 fallidas (6656 aserciones, 291.24 s).
3. **Guardianes Documentales y de Tablero:**
   `DocumentacionNoMienteTest`: 1 passed (7 assertions) en verde.
   `TableroAlDiaTest`: 6 passed (27 assertions) en verde.
   `CspSinCodigoIncrustadoTest`: 2 passed en verde (sin JS incrustado).
4. **Vistas y Sintaxis:**
   `php -l` sin errores en todos los archivos modificados.
   `view:cache` y `view:clear` limpios.
   `git diff --stat -- resources/views resources/css`: vacío (0 archivos de diseño tocados).

---

## 5. Separación de lo Comprobado y lo Supuesto

- **Comprobado:**
  - Medición exacta de SQL Query Log antes y después.
  - Rollback en base de datos verificado con `assertFalse($hoy->fresh()->validada_para_liquidacion)`.
  - Comprobación de que clases ajenas al mes no son tocadas.
  - Ejecución de suite completa en `wings_testing_gemini` sin errores de código.
- **Supuesto / Fuera de alcance:**
  - No se modificaron ni ejecutaron pruebas en `wings_testing_codex` ni en el VPS.
  - H1 y PATCH H2 permanecen intactos. T67 sigue cerrada.
