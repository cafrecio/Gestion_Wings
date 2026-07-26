# Análisis integral — Wings

Vista transversal del sistema, lo que ningún reporte por-tema captura: completitud real, red de seguridad, deuda de documentación y camino para dejarlo 100% funcional. Escrito por el hilo principal (no un agente).

**Escala del sistema:** 44 controllers · 15 services · 27 modelos · 59 migraciones · 75 vistas · 137 rutas web + 131 rutas API · 4 archivos de test.

---

### I1.0 — Sistema que maneja plata prácticamente sin tests
**Severidad:** Crítica
**Dónde:** `tests/` (solo 4 archivos: 2 son los `ExampleTest` de Laravel, 1 es `PagoCuotaServiceTest` roto).
**Qué pasa:** No hay red de seguridad automática. Cada uno de los bugs de dinero que aparecieron (sumar ingresos+egresos, doble submit, signo de egresos, cambio de plan retroactivo) pasó a producción sin que nada lo detectara. `PagoCuotaServiceTest` ni siquiera corre: usa SQLite en memoria y una migración usa sintaxis `MODIFY` de MySQL que SQLite no soporta.
**Soluciones:**
- SI1.1 ⭐ Definir BD MariaDB de test (`.env.testing` apuntando a `gestion_wings_test`) y escribir tests de los flujos de plata: cobro FIFO, cambio de plan hacia adelante, cierre de caja, neteo de egresos, integración caja→cashflow. Es la única forma de "quedar 100% funcional" y no romperlo en el próximo cambio.
- SI1.2 Arreglar solo la migración incompatible para que la suite corra en SQLite (más rápido, menos fiel al entorno real).
- SI1.3 No hacer tests (status quo). Inaceptable para un sistema de caja.

### I2.0 — La API (131 rutas) es un universo paralelo sin consumidor
**Severidad:** Alta
**Dónde:** `routes/api.php` (131 rutas) vs `routes/web.php` (137). El front (Blade) no consume la API; usa las rutas web.
**Qué pasa:** Existe una API REST casi en espejo de toda la funcionalidad web, pero nadie la usa desde el sistema actual. Cada endpoint es superficie de ataque y mantenimiento. Si no hay una app móvil planificada que la consuma, son 131 puertas que hay que asegurar y mantener sin beneficio.
**Soluciones:**
- SI2.1 ⭐ Decisión de producto: si NO hay app móvil en el horizonte, podar la API a lo mínimo (o quitarla). Menos superficie, menos mantenimiento.
- SI2.2 Si SÍ va a haber app móvil, congelar la API, documentarla (OpenAPI) y cubrirla con la auditoría de seguridad como ciudadano de primera.
- SI2.3 Dejarla como está: acumula deuda y riesgo silenciosamente.

### I3.0 — Documentación de contratos con versiones contradictorias conviviendo
**Severidad:** Alta
**Dónde:** `docs/02-contratos/` — conviven V1, V2, V3 y V4 de los mismos contratos (alumno-grupo-deporte-deuda V2 y V3; caja-cashflow V4; cuotas-deudas-pagos V1 ×2). También `docs/00-estado/` tiene ESTADO-ACTUAL y ESTADO-ANTERIOR.
**Qué pasa:** Al volver al proyecto no se sabe cuál contrato es la verdad. Es exactamente lo que te desorienta. Un documento que se contradice con otro es peor que no tenerlo: induce a decisiones equivocadas.
**Soluciones:**
- SI3.1 ⭐ Dejar UNA sola versión vigente de cada contrato (la última verificada contra el código), mover el resto a `99-archivo/`. Un contrato = un archivo vivo.
- SI3.2 Regenerar los contratos desde el código actual (fuente de verdad real) y archivar todos los viejos.

### I4.0 — El seeder no representa la operación real (causa raíz de bugs invisibles)
**Severidad:** Alta
**Dónde:** `database/seeders/DemoSeeder.php` y el estado actual de `gestion_wings`.
**Qué pasa:** El seeder solo cobra cuotas, siempre en efectivo, siempre el mismo operativo, sin egresos, sin cancelaciones, sin cajas rechazadas, sin posibles inactivos. Con datos así, todos los totales "parecen" correctos aunque la lógica esté mal — por eso los bugs de dinero fueron invisibles hasta que aparecieron a mano. Un seeder irreal es una evaluación que miente.
**Soluciones:**
- SI4.1 ⭐ Rehacer el seeder con datos que ejerciten TODOS los flujos: egresos en varios medios de pago, dos operativos, cobros del admin, cancelaciones, cajas rechazadas, deudores con distintas antigüedades, posibles inactivos, cambios de plan. Guía: `docs/06-pruebas/PLAN-PRUEBAS-FUNCIONALES.md`.
- SI4.2 Mantener el seeder actual pero agregar un segundo seeder "estrés" con los casos borde.

### I5.0 — Timezone: lógica de fecha dispersa y potencialmente inconsistente
**Severidad:** Media
**Dónde:** Mezcla de `now()`, `today()`, `Carbon::now('America/Argentina/Buenos_Aires')` y `->format('Y-m')` en controllers y services (ej. `CajaWebController`, `PagoCuotaService`, `CobranzaEstadoService`).
**Qué pasa:** Algunas partes usan la TZ Argentina explícita y otras el `now()` del server. Si el server no está en esa TZ, "hoy" puede diferir según qué función se llamó — riesgo en cierres de caja de fin de día y en "período vigente" de deudas cerca de medianoche.
**Soluciones:**
- SI5.1 ⭐ Fijar `app.timezone` a `America/Argentina/Buenos_Aires` en `config/app.php` y usar `now()`/`today()` de forma uniforme, o centralizar en un helper `hoyAr()`. Verificar que la TZ del server coincida.
- SI5.2 Auditar caso por caso los usos de fecha (más trabajo, mismo resultado).

### I6.0 — Sin control de versión de esquema verificable (dump como fuente de verdad)
**Severidad:** Media
**Dónde:** `database/dump.sql` versionado + 59 migraciones.
**Qué pasa:** Se versiona el dump completo Y las migraciones. No hay garantía de que corriendo las migraciones desde cero se obtenga el mismo esquema que el dump. Si divergen, un colaborador nuevo levanta un esquema distinto al de producción.
**Soluciones:**
- SI6.1 ⭐ Verificar que `migrate:fresh` produce un esquema idéntico al dump; si no, corregir las migraciones. El dump queda solo para datos, las migraciones para esquema.
- SI6.2 Abandonar migraciones y tratar el dump como única fuente (pierde trazabilidad; no recomendado).

### I7.0 — Vista muerta y código legacy sin podar — ✅ RESUELTO (2026-07-26)
**Severidad:** Baja
**Dónde:** `resources/views/caja/show.blade.php` (ninguna ruta la renderiza); `CobranzaEstadoService::resolverRevision()` (no llamado desde ningún controller web); columna `pagos.forma_pago_id` (el campo se quitó del flujo pero la columna quedó).
**Qué pasa:** Código y vistas que ya no se usan pero siguen apareciendo en búsquedas y auditorías, sumando ruido y confundiendo (la vista muerta tiene el patrón viejo de totales mal sumados).
**Resolución:** las tres cosas ya estaban muertas por caminos distintos y se confirmó antes de tocar nada. `caja/show.blade.php` eliminada (la única ruta que mencionaba "show" para cajas, `web.cajas.show`, redirige a `web.caja.detalle` y nunca renderizó esta vista). `CobranzaEstadoService::resolverRevision()` eliminado junto con su único caller (`CobranzaController::resolverRevision()`, API), la ruta API asociada y el FormRequest que solo él usaba (`ResolverRevisionCobranzaRequest`) — todo alcanzable únicamente por la API, ya apagada desde S2. La columna `pagos.forma_pago_id` ya se había eliminado en **D4** (columna, FK y tabla completas). `php -l` y `route:list` verificados sin errores tras el barrido.
**Soluciones:**
- SI7.1 ⭐ Barrido de limpieza: eliminar la vista muerta y el método legacy; decidir si la columna `forma_pago_id` se conserva por histórico o se migra fuera. Aplicado.
- SI7.2 Dejarlo (acumula ruido).

## Tabla resumen

| ID | Severidad | Título | Recomendación |
|----|-----------|--------|---------------|
| I1 | Crítica | Sin tests en sistema de plata | BD de test + tests de flujos de dinero (SI1.1) |
| I2 | Alta | API de 131 rutas sin consumidor | Decidir: podar o congelar+documentar (SI2.1) |
| I3 | Alta | Contratos con versiones contradictorias | Una versión viva por contrato, resto a archivo (SI3.1) |
| I4 | Alta | Seeder irreal esconde bugs | Rehacer con todos los flujos reales (SI4.1) |
| I5 | Media | Timezone disperso | Fijar TZ en config + uso uniforme (SI5.1) |
| I6 | Media | Esquema: dump vs migraciones sin verificar | Verificar migrate:fresh == dump (SI6.1) |
| I7 | Baja | Vista muerta y código legacy | ✅ Resuelto — barrido de limpieza (SI7.1) |

---

## Estado de los reportes de esta evaluación

| Tema | Archivo | Estado |
|------|---------|--------|
| Frontend | `frontend/REPORTE-FRONTEND.md` | ✅ Completo (F1–F15) |
| Integral | `ANALISIS-INTEGRAL.md` | ✅ Completo (I1–I7) |
| Seguridad | `seguridad/REPORTE-SEGURIDAD.md` | ⏳ Pendiente |
| Datos | `datos/REPORTE-DATOS.md` | ⏳ Pendiente |
| Backend | `backend/REPORTE-BACKEND.md` | ⏳ Pendiente |
| Usuario ADMIN | `funcionalidad/REPORTE-ADMIN.md` | ⏳ Pendiente |
| Usuario OPERATIVO | `funcionalidad/REPORTE-OPERATIVO.md` | ⏳ Pendiente |
| Usuario PROFESOR | `funcionalidad/REPORTE-PROFESOR.md` | ⏳ Pendiente |

Los pendientes se completan de a uno, secuencial, guardando cada archivo al terminar. El HTML índice se arma cuando estén los 8.
