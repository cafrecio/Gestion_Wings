# Reporte de auditoría de seguridad — Wings

Auditoría sobre app/Http, routes/web.php, routes/api.php, app/Models, resources/views, config/ y archivos versionados. **9 hallazgos** (2 críticos, 3 altos, 2 medios, 2 bajos). El grueso del riesgo estaba en la API REST, que quedó deshabilitada (fix S2, 2026-07-13) por no tener consumidor real. Al final se listan las superficies verificadas como SANAS para no re-auditarlas.

### S1.0 — dump.sql versionado expone hashes de contraseñas reales — ✅ RESUELTO (2026-07-13)
**Severidad:** Crítica
**Resolución:** Contraseñas rotadas (5/5 usuarios, hashes únicos). `dump.sql` re-exportado con `--ignore-table=gestion_wings.users` — la tabla ya no viaja por git. Nuevo `database/seeders/UserSeeder.php` recrea cuentas sin contraseñas versionadas. Pendiente aparte, no incluido: purgar el historial de git donde los hashes viejos ya quedaron (requiere reescribir historia + force-push, acción destructiva que necesita autorización explícita).
**Dónde:** `database/dump.sql` (versionado en git, referenciado en `CLAUDE.md` como práctica obligatoria antes de cada commit)
**Qué pasa:** El dump completo de la BD se commitea al repo, incluyendo la tabla `users` con sus hashes bcrypt reales. Los usuarios admin (id 1) y operativo (id 2) comparten el mismo hash, y la contraseña conocida es `password`. Cualquiera con acceso al repo (o si el repo se filtra/es público) tiene los hashes para crackear offline, y con "password" ni hace falta. Es la llave del sistema de plata, en texto plano de facto.
**Soluciones:**
- SS1.1 ⭐ Sacar la tabla `users` (y cualquier dato sensible) del dump versionado: `mysqldump --ignore-table=gestion_wings.users` o un dump de solo-estructura para users. Rotar YA las contraseñas reales de producción a unas fuertes y únicas por usuario.
- SS1.2 Dejar de versionar `dump.sql` por completo (agregarlo a `.gitignore`) y confiar solo en migraciones + seeders para reconstruir. Purgar el historial de git donde ya está.
- SS1.3 Encriptar el dump antes de commitear (git-crypt). Más fricción, sigue siendo dato sensible en el repo.

### S2.0 — API: alumnos, pagos, liquidaciones y clases sin control de rol — ✅ RESUELTO (2026-07-13)
**Severidad:** Crítica
**Resolución:** Se verificó primero que no hay ningún consumidor real (cero `fetch`/`axios` a `/api/` en `resources/`, sin `config/cors.php`, `config/sanctum.php` sin tocar) — auditar rol por ruta hubiera sido inventar permisos para un caso de uso inexistente. Se aplicó SS2.2: `routes/api.php` deshabilitada en `bootstrap/app.php` (comentada la línea `api:` de `withRouting()`, con la razón y los pasos para reactivarla documentados ahí mismo). Verificado: `/api/*` devuelve 404, la web sigue en 200, `route:list --path=api` vacío. Esto también resuelve S3 (recibo por API) y S4 (login API sin throttle) porque la superficie que explotaban ya no existe.
**Dónde:** `routes/api.php:57` (`apiResource('alumnos')`), `:64-80` (pagos, cambiar-plan), `:104-115` (liquidaciones store/show/destroy/cerrar/recalcular), `:83-87` (clases store), `:90-101` (asistencias). Controller confirmado sin gate: `app/Http/Controllers/AlumnoController.php` (no tiene isAdmin/isProfesor/authorize en ningún método).
**Qué pasa:** Todo eso está bajo `auth:sanctum` a secas, sin `ensure.admin`. Cualquier usuario autenticado — incluido un PROFESOR o un OPERATIVO — con un token válido puede `POST/PUT/DELETE` alumnos, crear o borrar liquidaciones, cambiar planes y disparar pagos vía API, cosas que en la web están bajo `ensure.admin.web`. Es escalada de privilegios directa: el rol se controla en la web pero la API es la puerta de atrás abierta.
**Soluciones:**
- SS2.1 ⭐ Auditar cada grupo de rutas de `api.php` y agregar el middleware de rol correcto (`ensure.admin`, o uno de operativo) igual que en la web. Nada que la web restrinja por rol puede quedar en la API con solo `auth:sanctum`.
- SS2.2 Si la API no se usa (ver análisis integral I2), desactivarla entera hasta que haya un consumidor real y se diseñen sus permisos.

### S3.0 — Recibos PDF sin verificación de propiedad (IDOR) — ✅ RESUELTO vía API (2026-07-13) · ⚠️ sigue abierto vía web, ver S9
**Severidad:** Alta
**Resolución parcial:** La ruta API (`/api/recibos/cuota/{pagoId}`) murió junto con toda la API (fix S2). Pero al investigar se encontró que el mismo problema existe en la ruta WEB equivalente, que sí tiene uso real — ver **S9.0**, hallazgo nuevo, no resuelto.
**Dónde:** `routes/api.php:298-299` — `GET /recibos/cuota/{pagoId}` y `/info` solo bajo `auth:sanctum` (a diferencia de los de liquidación en `:302-305`, que sí tienen `ensure.admin`).
**Qué pasa:** El recibo de un pago se sirve por `pagoId` sin comprobar que el usuario tenga derecho a ese pago. Cualquier usuario autenticado puede iterar IDs (`1, 2, 3…`) y bajar los recibos de todos los alumnos: nombres, montos, períodos. Fuga de datos de terceros.
**Soluciones:**
- SS3.1 ⭐ Verificar propiedad/rol antes de generar el PDF: admin ve todos; operativo solo los de sus cajas; profesor ninguno. Abortar 403 si no corresponde.
- SS3.2 Como mínimo, exigir `ensure.admin` en la ruta del recibo de cuota si por ahora solo el admin/operativo debe bajarlos.

### S4.0 — API login sin throttle (fuerza bruta) — ✅ RESUELTO (2026-07-13)
**Severidad:** Alta
**Resolución:** La ruta `/api/auth/login` dejó de existir junto con toda la API (fix S2). Ya no hay endpoint de login sin throttle.
**Dónde:** `routes/api.php:34` — `POST /auth/login` sin middleware `throttle`. (La web sí lo tiene: `routes/web.php` login con `throttle:10,1`.)
**Qué pasa:** El endpoint de login de la API no limita intentos. Un atacante puede probar miles de contraseñas por minuto contra `/api/auth/login` sin bloqueo. Combinado con S1 (contraseñas débiles conocidas), es la vía práctica de entrada.
**Soluciones:**
- SS4.1 ⭐ Agregar `->middleware('throttle:10,1')` (o similar) al login de la API, igual que en la web.
- SS4.2 Throttle global por IP a nivel de servidor/proxy además del de Laravel.

### S5.0 — Sin política de fuerza de contraseña al crear/editar usuarios
**Severidad:** Media
**Dónde:** flujo de creación de usuarios (`UsuarioWebController` / `StoreUserRequest` si existe) y el hecho de que la contraseña real sea `password`.
**Qué pasa:** No hay regla que exija longitud mínima, complejidad ni que rechace contraseñas comunes. Un admin puede crear un usuario con "1234". Esto es lo que permitió que exista "password" como clave de producción.
**Soluciones:**
- SS5.1 ⭐ Aplicar `Password::min(8)->mixedCase()->numbers()` (regla de Laravel) en la validación de alta/edición de usuarios, y forzar cambio de las contraseñas actuales.
- SS5.2 Mínimo: longitud ≥ 8 y rechazo de una lista corta de comunes.

### S6.0 — La API completa no se usa pero queda expuesta — ✅ RESUELTO (2026-07-13)
**Severidad:** Media
**Resolución:** Deshabilitada en `bootstrap/app.php` (mismo cambio que resolvió S2). Reactivable cuando exista un consumidor real, con la lista de deberes (S2/S3/S4) documentada ahí mismo.
**Dónde:** `routes/api.php` (131 rutas) vs el front Blade que consume `routes/web.php`.
**Qué pasa:** Es superficie de ataque viva sin beneficio: cada endpoint es algo que asegurar, versionar y mantener, sin ningún consumidor que lo justifique. S2, S3 y S4 son todos síntomas de que la API se construyó y quedó sin el cuidado de permisos que sí recibió la web. (Ver análisis integral I2.0.)
**Soluciones:**
- SS6.1 ⭐ Decisión de producto: si no hay app móvil planificada, deshabilitar `routes/api.php` (o reducirlo al mínimo) hasta que exista un consumidor real. Elimina S2/S3/S4 de un saque.
- SS6.2 Si va a usarse, tratarla como ciudadano de primera: gate de rol por ruta, throttle, tests y documentación.

### S7.0 — `.env.example` y `config/` — revisar defaults
**Severidad:** Baja (a confirmar)
**Dónde:** `.env.example` versionado (correcto que `.env` NO esté en git — verificado), `config/app.php`.
**Qué pasa:** `.env` real no está versionado (bien). Queda por confirmar que en producción `APP_DEBUG=false` (con `true`, la pantalla de error de Laravel expone stack traces, queries y fragmentos de config — de hecho las capturas de error que se vieron durante el desarrollo muestran `APP_DEBUG=true`). Con debug activo, cualquier excepción es una fuga de información.
**Soluciones:**
- SS7.1 ⭐ Garantizar `APP_DEBUG=false` y `APP_ENV=production` en el `.env` de producción. Documentarlo en el setup.
- SS7.2 Página de error genérica personalizada para 500.

### S8.0 — Sesión: revisar flags de cookie en producción
**Severidad:** Baja (a confirmar)
**Dónde:** `config/session.php`, cookie `wings-session`.
**Qué pasa:** Sobre XAMPP/HTTP local no se puede confirmar, pero en producción la cookie de sesión debe ser `Secure` (solo HTTPS), `HttpOnly` y `SameSite=Lax/Strict`. Si el deploy queda en HTTP o sin estos flags, la sesión es interceptable/robable.
**Soluciones:**
- SS8.1 ⭐ En producción: HTTPS obligatorio, `SESSION_SECURE_COOKIE=true`, `HttpOnly` (default de Laravel) y `SameSite` configurado. Verificar en el deploy real.

### S9.0 — Recibo de cuota por WEB sin control de rol ni de propiedad (IDOR) — ✅ RESUELTO (2026-07-13)
**Severidad:** Alta
**Resolución:** Aplicado SS9.1. `Pago::movimientoOperativo()` (nueva relación `hasOne`) permite saber quién cobró cada pago. En `ReciboController::cuota()`: ADMIN ve cualquier recibo; OPERATIVO solo si `pago->movimientoOperativo->usuario_id === auth()->id()`; cualquier otro caso (incluido PROFESOR) → `abort(403)`. Probado con los 4 casos reales contra un pago existente: admin 200, operativo dueño 200, otro operativo 403, profesor 403.
**Dónde:** `routes/web.php:224` — `GET /recibos/cuota/{pagoId}` registrada dentro de `Route::middleware('auth')` a secas, sin `ensure.admin.web` (a diferencia de `web.recibos.liquidacion` en la línea de al lado, que sí lo tiene). Controller: `app/Http/Controllers/ReciboController.php:35-78`, método `cuota()` — no valida rol ni dueño del pago, solo que el `pagoId` exista.
**Qué pasa:** Encontrado al verificar el impacto real de apagar la API para S2: esta es la ruta que **de verdad usa el producto** — `caja/detalle.blade.php:171,198,211` linkea acá para el botón de descargar/ver recibo. Como solo exige estar logueado (cualquier rol), un **PROFESOR** —que por diseño solo debería ver clases y asistencias— puede entrar a `/recibos/cuota/1`, `/recibos/cuota/2`, etc. e ir bajando el recibo de cualquier alumno: nombre, monto, períodos pagados. Es el mismo IDOR que se documentó en S3 para la API, pero acá sí hay un usuario real expuesto todos los días.
**Soluciones:**
- SS9.1 ⭐ En `cuota()`: cargar el `Pago` con su `alumno`/movimiento asociado y verificar rol — ADMIN ve cualquiera; OPERATIVO solo pagos de sus propias cajas (`MovimientoOperativo::where('usuario_id', auth()->id())` vía el `pago_id`); PROFESOR nunca. Devolver 403 si no corresponde, antes de generar el PDF.
- SS9.2 Mínimo rápido: agregar `ensure.admin.web` a la ruta como ya tiene `web.recibos.liquidacion`, aceptando que el operativo pierda la posibilidad de ver recibos de otros. Menos preciso que SS9.1 pero corta la fuga en una línea.

---

## Superficies verificadas como SANAS (no re-auditar)

- **Inyección SQL:** todos los `whereRaw`/`selectRaw`/`DB::raw` del código usan *bindings* con `?` (ej. `PagoCuotaService:557`, `CajaWebController:625`, `NivelWebController:34`, los `Request` de subrubro) o son comparaciones/agregados de solo-columna sin input de usuario (ej. `AlumnoWebController:59`, `CashflowSaldoService:29`). No hay concatenación de input crudo. **Limpio.**
- **XSS vía `{!! !!}`:** las ~60 ocurrencias son todas `{!! $iconAttr !!}`, una cadena estática de atributos SVG definida en el propio Blade, nunca datos de usuario. El resto del output usa `{{ }}` (escapado). **Limpio.**
- **Mass assignment en User:** `$fillable = [name, email, password, profesor_id]` — `rol` y `activo` están fuera, no se pueden setear por request. **Correcto.**
- **`.env`:** no está versionado (solo `.env.example`). **Correcto.**
- **Ya resueltos en iteraciones previas:** throttle en login web, `isActivo()` en los middlewares web y API admin, IDOR en `cerrar()` de caja, `addcslashes` en búsquedas LIKE.

## Tabla resumen

| ID | Severidad | Título | Recomendación |
|----|-----------|--------|---------------|
| S1 | Crítica | dump.sql versionado expone hashes + password "password" | ✅ Resuelto — sacado del dump + rotadas (SS1.1) |
| S2 | Crítica | API alumnos/pagos/liquidaciones sin gate de rol | ✅ Resuelto — API deshabilitada (SS2.2) |
| S9 | Alta | Recibo de cuota por WEB sin control de rol (IDOR real) | ✅ Resuelto — check de rol/dueño en cuota() (SS9.1) |
| S3 | Alta | Recibos PDF por API sin verificación de propiedad (IDOR) | ✅ Resuelto — API deshabilitada (S2) |
| S4 | Alta | API login sin throttle | ✅ Resuelto — API deshabilitada (S2) |
| S5 | Media | Sin política de fuerza de contraseña | Regla Password::min(8)->mixedCase() (SS5.1) |
| S6 | Media | API expuesta sin consumidor | ✅ Resuelto — deshabilitada (SS6.1) |
| S7 | Baja | Confirmar APP_DEBUG=false en producción | Forzar debug off en prod (SS7.1) |
| S8 | Baja | Confirmar flags Secure/HttpOnly de cookie | HTTPS + SESSION_SECURE_COOKIE (SS8.1) |
