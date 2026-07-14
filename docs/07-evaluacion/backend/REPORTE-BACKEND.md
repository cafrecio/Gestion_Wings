# Reporte de auditoría de backend — Wings

Auditoría de rutas (web 137 / API 131), controllers (44), services (15) y requests. **7 hallazgos** (2 altos, 3 medios, 2 bajos). El patrón dominante: existe una API completa construida en paralelo a la web, con lógica más vieja y a veces divergente, que nadie consume. La capa de services y la cobertura de transacciones en los flujos de plata son una fortaleza.

### B1.0 — Dos sistemas de pago en paralelo (API vs web) con lógica divergente
**Severidad:** Alta
**Dónde:** `app/Services/PagoService.php` (usado por `PagoController` API, `routes/api.php:65`) vs `app/Services/PagoCuotaService.php` (usado por el flujo web de cobro, `CajaWebController`).
**Qué pasa:** Hay dos servicios que registran pagos con reglas distintas. `PagoService` (el viejo, API) aplica su propia versión de regla de primer pago y cambio de plan; `PagoCuotaService` (el actual, web) tiene la lógica FIFO, el cambio de plan hacia adelante y las correcciones recientes. Un pago hecho por la API y uno por la web NO pasan por el mismo código: los arreglos que se hicieron en el flujo web (cambio de plan, neteo, etc.) no están en el de la API. Doble mantenimiento y comportamiento inconsistente según por dónde entre el pago.
**Soluciones:**
- SB1.1 ⭐ Unificar en `PagoCuotaService` (el que tiene la lógica correcta y probada). Que `PagoController` de la API lo use, o directamente deshabilitar la API de pagos si no se consume (ver B4). Eliminar `PagoService`.
- SB1.2 Si la API debe seguir viva, hacer que ambos controllers deleguen en un único service y borrar el duplicado.

### B2.0 — Dos implementaciones de "resolver revisión de cobranza" con vocabularios distintos
**Severidad:** Alta
**Dónde:** `CobranzaEstadoService::resolverRevision()` (`app/Services/CobranzaEstadoService.php:180`, usado por `CobranzaController` API con acciones `GENERAR_DEUDA`/`MARCAR_INACTIVO`) vs `RevisionCobranzaService` (usado por el flujo web con acciones `CONTINUA`/`INACTIVO`).
**Qué pasa:** La misma decisión de negocio (¿el alumno posible-inactivo sigue o no?) tiene dos implementaciones con nombres de acción diferentes y comportamiento distinto: la de la API no guarda nota, ni usuario, ni timestamp de la resolución; la web sí. Según por dónde se resuelva, queda distinta traza. Riesgo de datos incoherentes en la misma tabla `alumnos_revision_cobranza`.
**Soluciones:**
- SB2.1 ⭐ Dejar `RevisionCobranzaService` (web, más completo) como único, y que la API —si sigue— delegue en él con el mismo vocabulario. Eliminar `resolverRevision()` de `CobranzaEstadoService`.
- SB2.2 Mínimo: documentar que la vía API es legacy y no debe usarse, hasta poder borrarla.

### B3.0 — API `AlumnoController` a medio construir pero expuesto
**Severidad:** Media
**Dónde:** `app/Http/Controllers/AlumnoController.php:15-17,23-25,74-92` (`index`, `create`, `edit`, y otros métodos con cuerpo `//` vacío) expuestos por `Route::apiResource('alumnos', ...)` en `routes/api.php:57`.
**Qué pasa:** El controller REST de alumnos tiene métodos stub vacíos: `GET /api/alumnos` (index) no devuelve nada útil, `edit` está vacío. La ruta está publicada igual. Es una API "fantasma": responde 200 con contenido vacío o incompleto, y (por S2) sin control de rol. Superficie publicada que no hace lo que promete.
**Soluciones:**
- SB3.1 ⭐ Si la API se conserva, completar o quitar los métodos stub y no exponer via `apiResource` los que no están implementados (declarar rutas explícitas solo de lo que existe).
- SB3.2 Si no se consume, eliminar el controller y su ruta (parte de B4).

### B4.0 — La API entera duplica la web sin consumidor conocido
**Severidad:** Media
**Dónde:** `routes/api.php` completo (131 rutas), en espejo de `routes/web.php`.
**Qué pasa:** B1, B2, B3 y los hallazgos de seguridad S2/S3/S4 son todos síntomas de lo mismo: se construyó una API REST completa que el front Blade no usa, quedó con lógica más vieja, controllers a medias y permisos flojos. Es superficie de mantenimiento, riesgo y confusión sin retorno. (Ver integral I2.0 y seguridad S6.0.)
**Soluciones:**
- SB4.1 ⭐ Decisión de producto: si no hay app móvil planificada, deshabilitar `routes/api.php` (comentar el `require` o el archivo). Cierra B1/B2/B3 y S2/S3/S4 de un golpe. Reactivable cuando haya consumidor real.
- SB4.2 Si va a usarse, congelarla, unificar services con la web (B1/B2), completar controllers (B3) y ponerle permisos y tests.

### B5.0 — Validación de unicidad case-insensitive copiada en 3+ lugares
**Severidad:** Media
**Dónde:** `NivelWebController:34,61,90`, `TipoCajaWebController:107`, `StoreSubrubroRequest:34`, `UpdateSubrubroRequest:34` — todos repiten el patrón `whereRaw('LOWER(nombre) = ?', [...])` para chequear nombre duplicado ignorando mayúsculas.
**Qué pasa:** La misma lógica de "no permitir nombres que difieran solo en mayúsculas/acentos" está copiada y pegada con variaciones (uno usa `CONVERT(... USING utf8mb4)`, otros no). Cambiar la regla implica tocar 5+ lugares y es fácil que queden inconsistentes.
**Soluciones:**
- SB5.1 ⭐ Extraer a una regla de validación reutilizable (`Rule` custom `UniqueCaseInsensitive`) o un scope de modelo, y usarla en todos. Una sola fuente de verdad.
- SB5.2 Definir collation case-insensitive en las columnas de nombre y usar `unique` normal (menos código, resuelve en BD).

### B6.0 — Lógica de timezone/fecha dispersa
**Severidad:** Baja
**Dónde:** mezcla de `now()`, `today()`, `Carbon::now('America/Argentina/Buenos_Aires')`, `->format('Y-m')` en `CajaWebController`, `PagoCuotaService`, `OperativoDashboardController`, `CobranzaEstadoService`.
**Qué pasa:** Algunos cálculos de "hoy"/"período vigente" usan la TZ Argentina explícita y otros el `now()` del server. Cerca de medianoche, o si el server no está en esa TZ, pueden diferir. (Ver integral I5.0.)
**Soluciones:**
- SB6.1 ⭐ Fijar `app.timezone` a `America/Argentina/Buenos_Aires` en `config/app.php` y unificar en `now()`/`today()`, o un helper `hoyAr()`. Verificar la TZ del server de producción.

### B7.0 — Endpoints de API con nombres/estructura inconsistentes
**Severidad:** Baja
**Dónde:** `routes/api.php` — conviven `apiResource('alumnos')`, prefijos `admin/*`, rutas sueltas (`/user`, `/reglas-primer-pago/dia/{dia}`), y dos controllers de alumnos (`AlumnoController` y `Admin\AlumnoController`) para lo mismo con rutas distintas (`/alumnos` vs `/admin/alumnos`).
**Qué pasa:** No hay un criterio único de diseño REST: recursos, acciones RPC y prefijos de rol mezclados; alumnos accesible por dos caminos con dos controllers. Dificulta entender qué endpoint es la verdad.
**Soluciones:**
- SB7.1 ⭐ Si la API se conserva (B4), rediseñar con un criterio único (recursos REST + gate de rol por middleware, no por controller duplicado) y un solo controller de alumnos.

## Fortalezas verificadas (no tocar)

- **Capa de services:** la lógica de negocio de plata vive en services (`PagoCuotaService`, `CajaService`, `CashflowService`, `LiquidacionService`, etc.), no en los controllers. Arquitectura correcta.
- **Transacciones:** los flujos multi-tabla críticos usan `DB::transaction` (8 archivos: caja, pago cuota, asistencia, liquidación, revisión cobranza). Buena atomicidad.
- **Ya resuelto:** lock en apertura de caja, anti doble-submit, throttle en login web, scheduler de `cobranza:generar-deudas`.

## Tabla resumen

| ID | Severidad | Título | Recomendación |
|----|-----------|--------|---------------|
| B1 | Alta | Dos sistemas de pago (API PagoService vs web PagoCuotaService) | Unificar en PagoCuotaService (SB1.1) |
| B2 | Alta | Dos resoluciones de revisión con vocabularios distintos | Dejar RevisionCobranzaService único (SB2.1) |
| B3 | Media | API AlumnoController a medias pero expuesto | Completar o quitar stubs (SB3.1) |
| B4 | Media | API entera duplica la web sin consumidor | Deshabilitar api.php hasta tener consumidor (SB4.1) |
| B5 | Media | Validación unicidad case-insensitive copiada x5 | Regla/scope reutilizable (SB5.1) |
| B6 | Baja | Timezone/fecha disperso | Fijar TZ en config + uso uniforme (SB6.1) |
| B7 | Baja | Diseño de API inconsistente | Rediseñar con criterio único si se conserva (SB7.1) |
