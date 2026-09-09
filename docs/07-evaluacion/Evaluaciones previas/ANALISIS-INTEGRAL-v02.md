# Análisis integral — Wings — v02

**Fecha:** 2026-08-03
**Rama:** `main` · **Commit auditado:** `b31f8d7`
**Versión anterior:** `ANALISIS-INTEGRAL.md` + `index.html` (sin sufijo de versión; se los trata como v01 y **no se modificaron**).
**Estado del documento:** en elaboración incremental. Las áreas se van completando a medida que cada auditoría independiente termina; al pie de cada sección se indica su estado.

---

## 1. Resumen ejecutivo

Auditoría técnica, funcional y de seguridad del sistema Wings, realizada **sin modificar código ni datos**. El sistema es una aplicación Laravel 12 de gestión de una academia deportiva: alumnos, cuotas, caja operativa, cashflow, clases, asistencias y liquidaciones a profesores.

El sistema está **funcionalmente operativo y su núcleo de dinero está razonablemente construido** (montos en decimal, uso extendido de transacciones, integración caja→cashflow idempotente, imputación FIFO implementada). Los problemas graves no están en el cálculo sino en **quién puede ejecutarlo**.

### Lo más urgente no es de seguridad

> ⚠️ **CORRECCIÓN POSTERIOR (2026-08-09).** Este apartado afirmaba que *"el sistema lleva tres meses sin emitir deuda"* como si fuera pérdida real de facturación. **Es incorrecto.** La base analizada contiene **datos de prueba sembrados por `DemoSeeder`**, en un entorno XAMPP local donde nunca existió un scheduler. Esos datos van a ser borrados y regenerados.
>
> **Lo que se cae:** que el negocio haya perdido tres meses de ingresos.
> **Lo que sigue en pie sin cambios:** los defectos de código descritos abajo (F-02 y F-03), que romperían la facturación en un servidor real. C-01 pasa de defecto a tarea de instalación del VPS.
>
> El error fue de método: se leyeron datos de demo como si fueran producción, y se extrajo una conclusión de negocio más grande que la evidencia.

**El sistema no genera deuda, y cuando no la genera informa que todos los alumnos están al día.**

Estado de la base analizada: la última deuda es de **2026-05**, los 35 alumnos activos figuran `AL_DIA` y la tabla de revisión de cobranza tiene 0 filas — el proceso mensual nunca corrió en este entorno.

Son tres fallas encadenadas, y ninguna produce un error visible:

- **C-01:** nada ejecuta `schedule:run` en este servidor, así que el comando mensual nunca se dispara (verificado: `schtasks` no tiene ninguna tarea de PHP).
- **F-02:** aunque corriera, **no crearía ninguna deuda** — la ventana de elegibilidad está mal calculada y evalúa el mes en curso, que el día 1 está vacío.
- **F-03:** si no hay deuda emitida, el cálculo de estado cae en el `else` y devuelve `AL_DIA`. La ausencia de facturación se reporta como salud.

A esto se suma que **`/cobranza` está caída** (A-01): devuelve 500 siempre, por un `ORDER BY` sobre una columna que una migración eliminó. Es la pantalla donde se ve quién debe.

### Los tres problemas de control de acceso

1. **El control por rol es, en buena parte, decorativo.** El menú esconde las opciones, pero el servidor no las bloquea. Un usuario PROFESOR puede listar y editar cualquier alumno, ver el historial financiero completo y **registrar cobros reales** escribiendo la URL.
2. **Dar de baja a un usuario no le quita el acceso.** El login nunca consulta `activo`. Un empleado desactivado sigue cobrando y moviendo caja indefinidamente.
3. **Un operativo puede anular el cobro de otro.** El chequeo valida la caja pero no el movimiento, y la anulación revierte deuda y pago reales.

Y **no existe ninguna auditoría de acciones**: ante un descuadre o la explotación de lo anterior, no hay forma de reconstruir quién hizo qué.

### El estado de la red de seguridad

**La suite de tests no cubre nada: 12 de 14 fallan** y los 2 que pasan son los stubs de scaffold. La cobertura efectiva de lógica de negocio es **0%**. Esto explica por qué A-01 —una página que devuelve 500 siempre— pudo pasar desapercibida.

### Verificaciones negativas relevantes

Lo que se revisó y **está bien**, para que una próxima auditoría no lo repita: el virtualhost apunta correctamente a `public/` (`.env`, el dump y el código fuente dan 404 por HTTP); no hay SQL injection ni XSS; CSRF está bien cubierto; las contraseñas usan bcrypt correctamente; los recibos PDF están fuera del webroot; la tabla `users` sí está excluida del dump; los invariantes de dinero **hoy cuadran exactamente** (0 divergencias en 145 pagos y 170 deudas); y las ~60 rutas administrativas están todas protegidas.

---

## 2. Alcance revisado

| Elemento | Cantidad |
|---|---|
| Controllers | 44 (8 de ellos bajo `Admin/`, alcanzables solo por la API deshabilitada) |
| Services | 15 |
| Modelos | 26 |
| Middleware | 4 |
| FormRequests | 32 |
| Migraciones | 68 |
| Seeders | 10 |
| Vistas Blade | 75 |
| Rutas web registradas | 127 |
| Tablas en BD | 35 |
| Archivos de test | 4 |
| Documentos `.md` | 46 |

**Fuera de alcance por decisión explícita:** `routes/api.php` está deshabilitada en `bootstrap/app.php`; sus rutas no responden. Se audita únicamente como **riesgo latente** (qué pasaría si se reactivara), no como superficie activa.

---

## 3. Metodología y agentes utilizados

La auditoría se realizó con **agentes independientes**, uno por área, con instrucción estricta de solo lectura. Ninguno tenía capacidad de escritura sobre el proyecto. El coordinador consolidó los resultados, eliminó duplicados y resolvió contradicciones entre informes.

Cada hallazgo se clasifica con dos ejes:

- **Severidad:** CRÍTICA · ALTA · MEDIA · BAJA · INFORMATIVA
- **Estado de verificación:** CONFIRMADO · PROBABLE · NO VERIFICABLE · RECOMENDACIÓN

Se exigió a cada agente distinguir explícitamente entre lo verificado y lo supuesto, y cerrar con una sección de lo que no pudo comprobar.

### Nota de transparencia sobre la ejecución

Un primer intento lanzó los agentes en segundo plano y **se perdió completo**: la sesión se interrumpió y ninguno devolvió resultados. El intento válido los ejecutó de forma síncrona, garantizando la recepción de cada informe. Este documento se escribe de forma incremental por la misma razón.

---

## 4. Limitaciones de la auditoría

Estas limitaciones acotan el valor de las conclusiones y deben tenerse presentes:

1. **La auditoría es estática.** No se iniciaron sesiones ni se dispararon peticiones autenticadas para explotar los hallazgos. Los marcados CONFIRMADO lo están por lectura completa de la cadena rutas → middleware → controller → service, donde la ausencia del control es inequívoca, pero **no hay traza HTTP de explotación**. Las contraseñas de las cuentas de prueba no estaban disponibles.
2. **No se ejecutó la suite de tests** como red de verificación (ver área de Pruebas).
3. **No se inspeccionó la configuración de Apache/XAMPP** más allá de comprobar por HTTP que los archivos sensibles no son alcanzables.
4. **No se determinó la exposición de red del host** (si Apache escucha en LAN o solo en localhost). La severidad práctica de varios hallazgos de configuración depende de esto.
5. **`composer audit` reporta por versión, no por alcance**: no se trazó si el código ejercita los caminos vulnerables de cada CVE.
6. **No se auditó vista por vista** qué botones quedan visibles para cada rol; la columna "restricción en frontend" de la matriz es aproximada.

---

## 5. Evaluación de seguridad

**Estado del área:** completada.

### 5.1 Hallazgos críticos

#### S-01 — El rol PROFESOR tiene acceso completo a Alumnos y Caja
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

El middleware `ensure.profesor.web` existe y está registrado, pero **no se aplica a ninguna ruta**. Todo el bloque de Alumnos, Caja, Movimientos, Dashboard operativo y Grupos cuelga solo de `auth`, y ninguno de esos controllers verifica `isProfesor()`. El sidebar esconde los enlaces, pero el servidor no valida.

- **Evidencia:** `routes/web.php:89-97` — el comentario dice "accesible para todos los roles autenticados (ADMIN y OPERATIVO)" pero no lo aplica. `AlumnoWebController` (299 líneas) no contiene ningún `isAdmin()`, `isProfesor()` ni `abort(403)`.
- **Archivos:** `routes/web.php:35-97,123,140`; `app/Http/Controllers/AlumnoWebController.php:19-192`; `app/Http/Controllers/CajaWebController.php:35,86,510,539,591,689`; `app/Http/Middleware/EnsureProfesorWeb.php` (sin usos).
- **Consecuencia:** Un profesor puede listar/crear/editar/desactivar cualquier alumno (con DNI, celular, email y datos del tutor), leer 90 días de historial financiero de todos los operativos, y **registrar cobros de cuota reales** que abren caja a su nombre y mutan `deuda_cuotas` y `pagos`.
- **Reproducción:** Login como `rol=PROFESOR` → navegar a `/alumnos` → 200 con listado completo. Luego `/caja/historial` → 200. Luego `GET /caja/cobrar` y `POST /caja/cobrar/{alumnoId}` → el pago se registra.
- **Recomendación:** Crear un middleware que rechace `rol === PROFESOR` y aplicarlo al grupo que contiene `/caja/*`, `/cajas*`, `/alumnos*`, `/movimientos`, `/operativo`, `/grupos*`. Dejar bajo `auth` puro solo `/clases*` y `/recibos/cuota/*` (este último ya filtra profesor internamente).
- **Contradice:** `docs/02-contratos/PERMISOS-ROLES.md` ("PROFESOR: solo clases y asistencias. No participa de plata ni de alumnos").

#### S-02 / P-01 — Un usuario desactivado puede iniciar sesión y operar
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

`WebController::login()` ejecuta `Auth::attempt()` sin verificar `activo`. La verificación existe **solo** dentro de `EnsureAdminWeb` y `EnsureProfesorWeb`, aplicados a rutas de admin. Todas las rutas protegidas únicamente por `auth` nunca ejecutan ese chequeo.

- **Evidencia:** `app/Http/Controllers/WebController.php:29-33` — `if (Auth::attempt($credentials, ...)) { $request->session()->regenerate(); return $this->redirectByRole(Auth::user()); }` sin chequear `isActivo()`.
- **Archivos:** `app/Http/Controllers/WebController.php:22-38`; `app/Http/Middleware/EnsureAdminWeb.php:18-21`.
- **Consecuencia:** Dar de baja a un empleado desde `/usuarios` **no le quita el acceso operativo**. Sigue pudiendo cobrar cuotas, mover caja y editar alumnos. Solo se le cierra el panel admin. La baja de usuario es una falsa sensación de revocación.
- **Reproducción:** En `gestion_wings.users` el id 3 es `OPERATIVO` con `activo=0`. Login con esa cuenta → autentica → `/operativo` → acceso completo a caja y cobro.
- **Recomendación:** Rechazar en el login si `!isActivo()` (con `Auth::logout()`), y agregar un middleware `ensure.activo` al grupo `auth` completo para invalidar sesiones ya emitidas.

### 5.2 Hallazgos altos

#### S-03 — `database/dump.sql` versionado incluye sesiones autenticadas y tokens Sanctum
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

El dump se exporta con `--ignore-table` solo para `users`. Las tablas `sessions` y `personal_access_tokens` **sí se exportan con datos**, y el dump está trackeado en git. Con `SESSION_ENCRYPT=false`, los payloads son base64 plano.

- **Evidencia:** 11 payloads de sesión en el dump, **3 con la clave `login_web_*`** (sesión autenticada), además de `_token` CSRF, IP y User-Agent. `personal_access_tokens` trae filas con abilities `["*"]` sin expiración.
- **Archivos:** `database/dump.sql`; `CLAUDE.md` (comando de export con un solo `--ignore-table`); `.env` (`SESSION_ENCRYPT`).
- **Consecuencia:** Cualquiera con acceso al repo obtiene identificadores de sesión — que son el valor de la cookie — y tokens CSRF válidos. Si el dump se reimporta en un entorno vivo, esas sesiones vuelven a existir y una cookie forjada con ese ID queda autenticada mientras no venza el `lifetime`.
- **Recomendación:** Agregar `--ignore-table` para `sessions`, `personal_access_tokens`, `cache`, `cache_locks`, `jobs`, `failed_jobs` y `password_reset_tokens`, tanto en `CLAUDE.md` como en `scripts/db-export.sh`. Purgar las filas ya versionadas, invalidar los tokens existentes y poner `SESSION_ENCRYPT=true`.
- **Nota:** La exclusión de `users` **sí funciona correctamente** (0 INSERT verificados).

#### S-04 — `APP_DEBUG=true` con stack traces devueltos al cliente
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

`.env` tiene `APP_ENV=local`, `APP_DEBUG=true`, `LOG_LEVEL=debug`. Cualquier excepción no capturada renderiza la página de debug con el SQL completo, credenciales de conexión, rutas absolutas y fragmentos de código. Además `ReciboController` propaga `$e->getMessage()` crudo en las respuestas JSON.

- **Evidencia:** `app/Http/Controllers/ReciboController.php:80-85` y `:147-152`. El log muestra el formato que se expondría: `SQLSTATE[HY000]... (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: gestion_wings, SQL: insert into alumnos ...)`.
- **Consecuencia:** Divulgación de estructura de BD, host y puerto de MariaDB, rutas del servidor y variables de entorno al primer error.
- **Recomendación:** `APP_DEBUG=false` y `APP_ENV=production` en cualquier instalación que no sea el equipo de desarrollo; `LOG_LEVEL=warning`. En `ReciboController`, loguear el mensaje y devolver uno genérico.
- **Dependencia:** se potencia con S-11 (disparador trivial de error 500).

#### P-02 — IDOR: un operativo puede anular el cobro de otro operativo
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

`cancelarMovimiento()` valida que la **caja** pertenezca al usuario, pero pasa el `$movId` **sin verificar que ese movimiento pertenezca a esa caja**. `PagoCuotaService::cancelarCobroOperativo()` hace `findOrFail($movimientoId)` y solo comprueba el estado de la caja *del movimiento*, nunca el usuario. La versión GET del mismo flujo **sí** hace el scope correcto: el chequeo existe pero se evade saltando el formulario.

- **Evidencia:** `$this->pagoCuotaService->cancelarCobroOperativo($movId, $request->input('motivo'), Auth::id());`
- **Archivos:** `app/Http/Controllers/CajaWebController.php:440-461` (comparar con `:417-438`); `app/Services/PagoCuotaService.php:603-618`.
- **Consecuencia:** Anulación de cobros ajenos: la deuda del alumno se revierte, el `Pago` pasa a `ANULADO` y el movimiento a `CANCELADO`, imputado a otra caja. Descuadre de arqueo y borrado de cobranza legítima.
- **Reproducción:** Operativo A, con caja propia id=10, hace `POST /caja/10/movimientos/{movId_de_B}/cancelar`. Pasa el `abort(403)` porque la caja 10 es suya, y cancela el cobro de B.
- **Recomendación:** Resolver el movimiento con el mismo scope que el formulario (`MovimientoOperativo::where('caja_operativa_id', $cajaId)->findOrFail($movId)`).

#### P-03 — `updateMovimiento` saltea la validación de `permitido_para`
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

Toda **alta** de movimiento pasa por `CajaService`, que exige `permitido_para === 'OPERATIVO'` y bloquea subrubros reservados. La **edición** no: escribe directo al modelo. Solo valida `exists:subrubros,id` y chequea `es_reservado_sistema` del subrubro **viejo**, nunca del nuevo.

- **Archivos:** `app/Http/Controllers/CajaWebController.php:349-385`; contraste con `app/Services/CajaService.php:290-298`.
- **Consecuencia:** Un operativo reclasifica un movimiento propio a un subrubro `permitido_para = ADMIN` (Sueldos, Servicios, Intereses). Contamina rubros exclusivos del admin y el reflejo en cashflow al validar la caja; además el movimiento queda invisible en `/caja/historial`, que filtra por subrubros `OPERATIVO`.
- **Recomendación:** Enrutar la edición por el service, o replicar las tres validaciones (reservado, `permitido_para`, `afecta_caja`).

#### P-05 — La misma información financiera es pública en un módulo y privada en otro
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

Dos vistas sobre `movimientos_operativos` con criterios opuestos. `CajaWebController::historial()` no tiene **ningún** chequeo de rol ni propiedad: lista movimientos de todos los operativos filtrando solo por rubro. `MovimientoWebController::index()`, sobre los mismos registros, filtra a la caja propia si no es admin y **no** filtra por rubro.

- **Archivos:** `app/Http/Controllers/CajaWebController.php:86-127` vs `app/Http/Controllers/MovimientoWebController.php:26-30`.
- **Consecuencia:** El control es ilusorio: lo que `/movimientos` oculta se ve entero en `/caja/historial`. Y como `historial` no filtra por rol, un PROFESOR accede a 90 días de cobranza con nombres de alumnos y montos.
- **Recomendación:** Unificar el criterio (visibilidad por rubro, según el contrato) y agregar el gate de rol que excluye PROFESOR de ambos.

#### P-04 — Contradicción directa con `PERMISOS-ROLES.md` sobre ver caja ajena
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** medio (decisión de producto)

El contrato dice textualmente que la única restricción de propiedad legítima es **cerrar** la caja de otro, y que *"ver el historial y los movimientos no está restringido por propiedad"*. El código hace lo contrario en 8 puntos.

- **Archivos:** `docs/02-contratos/PERMISOS-ROLES.md:64-68` vs `app/Http/Controllers/CajaWebController.php:219,269,283,301,335,354,392,422,445` y `:66-70`.
- **Fuente que aplica realmente:** el **código**, no el contrato.
- **Consecuencia:** Rompe la operación descrita en el propio contrato (el turno tarde no puede revisar la caja del turno mañana). Riesgo secundario: que alguien "corrija" el contrato para que coincida con el código, consolidando justamente el modelo mental que el documento existe para evitar.
- **Recomendación:** Definir cuál gana. Si gana el contrato, dejar el `abort(403)` solo en escritura y liberar `resumen`/`detalle`/`index`. Si gana el código, corregir el contrato, que hoy es fuente de verdad falsa.

### 5.3 Hallazgos medios

| ID | Título | Estado | Esfuerzo |
|---|---|---|---|
| S-05 | Throttle de login débil (`throttle:10,1` por IP, sin límite por cuenta ni registro de intentos) | CONFIRMADO | bajo |
| S-06 | Política de contraseñas mínima (`Password::min(8)` sin complejidad); sin cambio propio ni recuperación | CONFIRMADO | medio |
| S-07 | Sin auditoría de accesos ni de acciones sobre dinero | CONFIRMADO | medio |
| S-08 | Dependencias con vulnerabilidades conocidas (38 avisos composer, 5 HIGH; 10 npm) | CONFIRMADO | bajo |
| S-09 | PII de alumnos (nombre, DNI, teléfono) en `storage/logs/laravel.log` | CONFIRMADO | bajo |
| P-06 | Regla de oro invertida: al ADMIN se le ofrecen subrubros que el sistema le rechaza | CONFIRMADO | bajo |
| P-07 | El POST de cancelación no replica las precondiciones del GET | CONFIRMADO | bajo |
| P-08 | Tres middlewares registrados: dos muertos y uno que produciría un deadlock si se aplicara | CONFIRMADO | medio |
| P-09 | `users.rol` nullable con default `OPERATIVO`; el control de acceso hace *fail-open* a operativo | CONFIRMADO | medio |

**Detalle de los más relevantes:**

- **S-05:** 10 intentos/minuto por IP son ~14.400 diarios contra un padrón de 5 usuarios. No hay listener de `Failed`/`Lockout`: un ataque en curso es invisible. *(Nota positiva: el login **no** permite enumeración de usuarios; el mensaje de error es genérico.)*
- **S-07:** No existe tabla de auditoría en las 35 tablas, ni listeners de eventos, ni logging de acciones (solo 3 llamadas a `Log::` en toda la app, todas para errores técnicos). Ante un descuadre de caja o la explotación de S-01/S-02, **no hay forma de reconstruir quién hizo qué**. Agravante: `destroyMovimiento` hace borrado físico sin rastro.
- **S-08:** HIGH en `laravel/framework v12.46.0`, `symfony/http-kernel`, `symfony/mime`, `guzzlehttp/guzzle`. Relevante para este proyecto: **4 CVE MEDIA en `dompdf/dompdf v3.1.4`**, motor de los recibos PDF, incluida lectura de archivos locales vía SVG. Hoy no es explotable porque las plantillas usan solo datos estructurados. Las de npm son todas de desarrollo (`vite`, `rollup`), sin impacto en producción.
- **P-09:** El esquema define `rol` sin `NOT NULL` y con default `OPERATIVO`; todo el control es por negación (`!isAdmin()`) y `redirectByRole()` usa `default => operativo`. Un rol NULL o desconocido **no es rechazado en ningún punto**. Además `rol` no está en `$fillable`, así que cualquier `User::create()` desde un seeder o import produce una cuenta silenciosamente operativa. Hoy no hay filas con rol NULL (5 usuarios: 1 ADMIN, 2 OPERATIVO, 2 PROFESOR).

### 5.4 Hallazgos bajos

| ID | Título | Estado |
|---|---|---|
| S-10 | Cookie de sesión sin endurecer (`SESSION_ENCRYPT=false`, sin `Secure`, `same_site=lax`, no expira al cerrar) | CONFIRMADO |
| S-11 | `/caja/historial` acepta fechas sin validar y rompe con 500 (disparador trivial de S-04) | CONFIRMADO |
| S-12 | Middleware `bloqueo.caja.vieja` inoperante: no aplicado y su regex solo reconoce rutas `api/` | CONFIRMADO |
| S-13 | `routes/api.php` sigue en el repo, sin control de rol, a un descomentado de reactivarse | CONFIRMADO (riesgo latente) |
| P-10 | Sin salvaguarda de "último admin"; desactivar un usuario no invalida su sesión activa | CONFIRMADO |

### 5.5 Verificaciones sin hallazgo (resultado negativo documentado)

Se revisaron y **no** presentan problemas. Se documenta para que una revisión futura no repita el trabajo:

- **Exposición por HTTP:** el virtualhost apunta a `public/`. Verificado con peticiones reales: `/.env` → 404, `/database/dump.sql` → 404, `/composer.json` → 404, `/app/Models/User.php` → 404. **Ningún archivo sensible es descargable.**
- **SQL Injection:** las 14 apariciones de `whereRaw`/`selectRaw`/`DB::raw` usan literales estáticos o bindings parametrizados. Los `LIKE` de búsqueda escapan con `addcslashes($input, '%_\\')`.
- **XSS:** las 56 ocurrencias de `{!! !!}` son todas `<svg {!! $iconAttr !!}>` con una cadena estática definida en las propias plantillas. Ningún dato de usuario se emite sin escapar.
- **CSRF:** ningún formulario POST carece de `@csrf`; las 13 llamadas `fetch()` envían `X-CSRF-TOKEN`; no hay exclusiones en `bootstrap/app.php`.
- **Mass assignment:** `User::$fillable` **excluye** `rol` y `activo`. Ningún modelo usa `$guarded`.
- **IDOR en rutas anidadas:** correctamente scopeadas (`Subrubro::where('rubro_id',...)->findOrFail()`), salvo la excepción documentada en P-02.
- **Recibos PDF:** viven en `storage/app/private/recibos/`, fuera del webroot, sin symlink `public/storage`. Rutas construidas desde IDs enteros, sin path traversal.
- **Sin file upload, sin SSRF, sin open redirect, sin `eval`/`unserialize`/`exec`.**
- **Secretos en git:** `.env` está en `.gitignore` y nunca fue commiteado. La tabla `users` está correctamente excluida del dump.
- **Hashing:** `bcrypt` con `BCRYPT_ROUNDS=12` y cast `'password' => 'hashed'`. Correcto.
- **Rutas administrativas:** las ~60 rutas admin están todas cubiertas por `ensure.admin.web` a nivel de grupo. No se encontró ninguna sin protección.

---

## 6. Matriz de usuarios, roles y permisos

**Estado del área:** completada.

Leyenda: ✅ accede · ❌ bloqueado · ⚠️ acceso parcial. "Frontend" = el enlace está oculto por rol en Blade. "Backend" = hay chequeo real en servidor.

| Módulo | Ruta | Middleware efectivo | ADMIN | OPER. | PROF. | Frontend | Backend | Riesgo |
|---|---|---|---|---|---|---|---|---|
| Auth | `POST /login` | `throttle:10,1` | ✅ | ✅ | ✅ | — | No valida `activo` | **CRÍTICO** |
| Caja | `GET /caja` | `auth` | ✅ todas | ⚠️ propias 30d | ✅ | Sí | Rama `isAdmin()`, sin gate de rol | ALTO |
| Caja | `GET/POST /caja/movimiento` | `auth` | ✅ | ✅ | ✅ | Sí | Solo `permitido_para` en service | ALTO |
| Caja | `GET /caja/historial` | `auth` | ✅ | ✅ todos | ✅ | Sí | **Ninguna** | ALTO |
| Caja | `GET/POST /caja/cobrar/{alumnoId}` | `auth` | ✅ | ✅ | ✅ | Sí | **Ninguna** | **CRÍTICO** |
| Caja | `GET /caja/{id}/resumen`·`/detalle` | `auth` | ✅ | ⚠️ propias | ⚠️ propias | Sí | `abort(403)` propiedad | MEDIO (contradice contrato) |
| Caja | `PUT /caja/{c}/movimientos/{m}` | `auth` | ✅ | ⚠️ propias | ⚠️ propias | Sí | Propiedad; **no valida subrubro nuevo** | ALTO |
| Caja | `POST .../movimientos/{m}/cancelar` | `auth` | ✅ | ✅ **cualquier mov** | ✅ | Sí | Propiedad de caja, **no del mov** | ALTO |
| Caja | `POST /cajas/{id}/validar`·`/rechazar` | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — |
| Dashboard | `GET /operativo` | `auth` | ✅ | ✅ | ✅ | Sí | Ninguna | MEDIO |
| Dashboard | `GET /admin/dashboard` | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — |
| Movimientos | `GET /movimientos` | `auth` | ✅ todos | ⚠️ propia caja | ✅ | Sí | Filtro por propiedad | MEDIO |
| Cashflow | `/cashflow*` | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — |
| Cobranza | `/cobranza`, `/revision-cobranza*` | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — |
| Alumnos | `/alumnos*` (lectura y escritura) | `auth` | ✅ | ✅ | ✅ | Sí | **Ninguna** | ALTO |
| Catálogos | `/deportes*`,`/rubros*`,`/niveles*`,`/tipos-caja*` | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — |
| Grupos | `GET /grupos*` | `auth` | ✅ | ✅ | ✅ | Parcial | Ninguna | BAJO |
| Grupos | escritura + planes | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — |
| Clases | `GET /clases`,`/clases/{id}` | `auth` | ✅ | ✅ | ✅ | No | Límite 35d si no admin | — (contrato OK) |
| Clases | `POST /clases/{id}/asistencias` | `auth` | ✅ | ✅ | ✅ | No | Motivo si es corrección | — (contrato OK) |
| Clases | `PATCH /clases/{id}/cancelar` | `auth` | ✅ | ✅ cancelar / ❌ reactivar | ❌ | Sí | `abort(403)` + admin p/reactivar | — |
| Clases | `PATCH /clases/{id}/profesores` | `auth` | ✅ | ✅ solo futuras | ❌ | Sí | `abort(403)` + admin si pasada | — |
| Clases | `create`,`store`,`edit`,`update`,`validar` | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — (excepción documentada) |
| Liquidaciones | `/liquidaciones*` (incl. `pagar`) | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — |
| Usuarios | `/usuarios*` | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí + no auto-degradarse | BAJO |
| Recibos | `GET /recibos/cuota/{pagoId}` | `auth` | ✅ | ✅ cualquiera | ❌ | Sí | `abort(403)` profesor | — (contrato OK) |
| Recibos | `GET /recibos/liquidacion/{id}` | `+ensure.admin.web` | ✅ | ❌ | ❌ | Sí | Sí | — |

### Lectura de la matriz

El patrón es nítido: **todo lo que está bajo `ensure.admin.web` está bien protegido** (~60 rutas, sin excepciones encontradas). El problema está concentrado en el bloque que cuelga de `auth` a secas — caja, alumnos, movimientos, dashboard operativo — donde la separación entre OPERATIVO y PROFESOR simplemente no existe en el servidor.

El módulo de **Clases** es el mejor resuelto: los chequeos están en el controller, son explícitos y coinciden con su contrato.

---

## 7. Evaluación de base de datos e integridad

**Estado del área:** completada.

### 7.1 Lectura general

La base está **bien construida en lo estructural**: montos en `decimal` (nunca float), FKs completas, constraints únicos donde importan (`deuda_cuotas_alumno_periodo_unique`, `unique_alumno_plan_activo`, `pago_deuda_cuota_unique`), y los flujos de dinero principales envueltos en `DB::transaction`.

El problema es de otra naturaleza: **casi todos los hallazgos son bugs latentes que hoy no se ven en los datos porque la función que los dispara todavía no se usó.** Concretamente, no hay ni un movimiento `CANCELADO` ni un pago `ANULADO` en toda la base. El día que alguien use "Cancelar" por primera vez, varios invariantes se rompen de forma permanente.

### 7.2 Hallazgos críticos

#### D-01 — Sobrepago silencioso: se imputa menos de lo que se registra
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO (por código) · **Esfuerzo:** bajo

`aplicarPagoADeudas()` acredita `min($item['monto'], $saldoPendiente)` a la deuda, pero `relacionarPagoConDeudas()` escribe en el pivote el `$item['monto']` **completo**, y el pago guarda `monto_final = SUM(items)`. Si el operador ingresa un monto mayor al saldo, la caja y el cashflow registran el total cobrado y la deuda solo el saldo. **No hay validación de tope**: los FormRequests solo exigen `numeric|min:0.01`.

- **Evidencia:** `PagoCuotaService.php:311` → `$montoAplicar = min($item['monto'], $saldoPendiente);` vs `PagoCuotaService.php:574` → `'monto_aplicado' => $item['monto'],`
- **Archivos:** `app/Services/PagoCuotaService.php:295-320, 566-578`; `app/Http/Requests/StorePagoCuotaOperativoRequest.php:23`
- **Consecuencia:** Rompe el invariante `deuda.monto_pagado = Σ imputaciones`. Dinero cobrado sin contraparte de deuda, y recibo con importe no imputado.
- **Estado en datos:** aún no ocurrió (0 divergencias en las 170 deudas).
- **Recomendación:** Usar `$montoAplicar` como `monto_aplicado`, y rechazar el pago si `item.monto > saldo_pendiente`.

#### D-02 — El reflejo caja→cashflow apunta al movimiento, no a la caja
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO (por datos) · **Esfuerzo:** medio

`CashflowIntegracionCajaService` escribe `referencia_id = $cajaId`, pero los datos reales tienen 145 filas `CAJA_OPERATIVA` con 145 `referencia_id` **distintos**, 65 de ellos mayores al `MAX(cajas_operativas.id) = 80`. El origen es `DemoSeeder.php:527`, que escribe `'referencia_id' => $mov->id`.

- **Evidencia:**
  ```sql
  SELECT COUNT(*) FROM cashflow_movimientos WHERE referencia_tipo='CAJA_OPERATIVA'
    AND referencia_id > (SELECT MAX(id) FROM cajas_operativas);   -- 65
  ```
- **Archivos:** `app/Services/CashflowIntegracionCajaService.php:32-33,72`; `database/seeders/DemoSeeder.php:512-527`
- **Consecuencia:** El guard de idempotencia (`where referencia_id = $cajaId`) **no reconoce esos asientos**. Re-validar cualquiera de esas 65 cajas duplicaría el cashflow. Además la trazabilidad caja→cashflow queda rota para conciliación.
- **Recomendación:** Definir una sola semántica (`referencia_id = caja_id`, más una columna `movimiento_operativo_id` para el 1:1) y remediar los datos seedeados.

### 7.3 Hallazgos altos

#### D-03 — `validarCaja()` sin bloqueo: doble reflejo en cashflow
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

Abre transacción pero lee con `findOrFail()` sin `lockForUpdate`, y la idempotencia es un `exists()` sin lock. Dos requests concurrentes (doble click en Validar) leen `CERRADA`, ambas pasan el `exists()` en falso y ambas insertan. No hay unique que lo impida.

- **Contraste revelador:** `abrirCajaSiNoExiste` **sí** serializa con `lockForUpdate` (`CajaService.php:78-79`). El patrón correcto ya existe en el repo y no se aplicó acá.
- **Archivos:** `app/Services/CajaService.php:206-232`
- **Recomendación:** `CajaOperativa::whereKey($cajaId)->lockForUpdate()->firstOrFail()` al inicio.

#### D-04 — El reflejo a cashflow incluye movimientos CANCELADO
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

`CajaOperativa::movimientos()` es un `hasMany` sin filtro de estado, y el reflejo itera sin filtrar. Un cobro cancelado se sigue reflejando como dinero real al validar la caja. **El scope `activos()` existe y se usa en `CajaWebController.php:110`** — la intención está, falta aplicarla acá.

- **Archivos:** `app/Models/CajaOperativa.php:73-76`; `app/Services/CashflowIntegracionCajaService.php:47`
- **Recomendación:** `$caja->movimientos()->activos()` en el reflejo.

#### D-05 — Cancelar un cobro no borra sus imputaciones
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

`cancelarCobroOperativo` revierte `deuda.monto_pagado`, marca el pago `ANULADO` y el movimiento `CANCELADO`, pero **deja intactas las filas de `pago_deuda_cuota`**. Después de una sola cancelación se rompe permanentemente el invariante `deuda.monto_pagado = Σ imputaciones`.

- **Archivos:** `app/Services/PagoCuotaService.php:603-651`
- **Consecuencia:** Reportes de cobranza y recibos que sumen el pivote cuentan plata anulada. La auditoría de integridad deja de ser utilizable.
- **Recomendación:** Borrar (o marcar) las filas del pivote en la misma transacción, y filtrar `pagos.estado='COMPLETADO'` en toda lectura del pivote.

#### D-06 — Lectura-modificación-escritura de `monto_pagado` sin bloqueo de fila
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

`obtenerOcrearDeuda()` lee sin `lockForUpdate` y `aplicarPagoADeudas()` hace `monto_pagado = leído + montoAplicar; save()`. **No hay un solo `lockForUpdate` en todo `PagoCuotaService`.** Dos cobros simultáneos sobre la misma deuda: ambos leen el mismo valor, el segundo pisa al primero, pero **ambos** pagos, imputaciones y movimientos de caja quedan creados.

- **Archivos:** `app/Services/PagoCuotaService.php:336-345, 313-320`
- **Consecuencia:** Doble cobro real con una sola deuda acreditada.
- **Recomendación:** `lockForUpdate()` sobre la deuda dentro de la transacción; idealmente `UPDATE ... SET monto_pagado = monto_pagado + ?` atómico.

#### D-07 — Cadenas `ON DELETE CASCADE` que borran la contabilidad
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** medio

De 27 FKs en cascada, tres cadenas tocan dinero:

| Cadena | Efecto de borrar una fila |
|---|---|
| `cajas_operativas.usuario_operativo_id → users` + `movimientos_operativos.caja_operativa_id` | 1 usuario → 80 cajas → 147 movimientos |
| `cashflow_movimientos.usuario_admin_id → users` | Borrar al admin borra **157 asientos = $3.534.000** |
| `pagos.alumno_id`, `deuda_cuotas.alumno_id → alumnos` + pivote | 1 alumno → sus pagos, deudas e imputaciones |

- **Mitigación actual:** no existe ruta web `DELETE` para users ni alumnos, y los catálogos sí validan referencias. **La protección es 100% aplicativa.**
- **Consecuencia:** Un `DELETE FROM users WHERE id=1` manual, un seeder o un futuro CRUD destruye la contabilidad sin error.
- **Recomendación:** Pasar los FKs desde `users`/`alumnos` hacia tablas con dinero a `RESTRICT`; usar desactivación en lugar de borrado.

### 7.4 Hallazgos medios y bajos

| ID | Título | Sev. | Estado |
|---|---|---|---|
| D-08 | "Una sola caja ABIERTA por usuario" no existe como constraint, solo como lock aplicativo | MEDIA | CONFIRMADO |
| D-09 | `condonarDeuda`/`ajustarDeuda`/`ajustarDeudas` reescriben deuda sin transacción ni guardas | MEDIA | CONFIRMADO |
| D-10 | Tipos de monto inconsistentes (`decimal(10,2)` vs `(12,2)`) y sin CHECK de dominio | BAJA | CONFIRMADO |

**D-09 en detalle**, por su impacto en reglas de negocio: (1) `condonarDeuda` y `ajustarDeuda` no verifican `monto_pagado`, así que se puede condonar una deuda con pagos parciales; (2) `ajustarDeuda` permite bajar `monto_original` por debajo de `monto_pagado` y lo tapa marcándola `PAGADA`, con lo que el sobrepago desaparece del balance; (3) **`ajustarDeudas` reescribe `monto_original` de deudas históricas PENDIENTES** al aplicar la regla de primer pago al "alumno inactivo que vuelve", contra la regla documentada de no reescribir deuda anterior. Falla en silencio: solo `Log::warning`.

### 7.5 Verificado sin hallazgo

Estado sano confirmado con consultas sobre datos reales:

- **Convención de signo:** 0 filas con signo contrario a `rubro.tipo` en cashflow; 0 con `monto=0`; 0 con `monto <= 0` en movimientos operativos.
- **Invariantes de dinero:** `pago.monto_final = Σ imputaciones` → 0 divergencias en 145 pagos. `deuda.monto_pagado = Σ imputaciones` → 0 divergencias en 170 deudas. 0 filas con `monto_pagado > monto_original`. 0 estados incoherentes PAGADA/PENDIENTE.
- **Unicidad:** deuda por (alumno, período) y plan activo por alumno, ambas con constraint real y datos que las cumplen. El patrón de `activo` nullable + unique parcial para cerrar planes es correcto.
- **Transacciones:** los flujos principales de dinero están todos dentro de `DB::transaction`. La generación de PDF se difiere con `DB::afterCommit` — correcto.
- **Idempotencia de pago de liquidaciones:** doble guard y egreso forzado a negativo con `-abs()`.
- **Huérfanos:** 0 pagos sin movimiento ni cashflow; 0 movimientos con `alumno_id` sin `pago_id`; 0 períodos con formato inválido.
- **Coherencia de estados de caja:** 0 `VALIDADA` sin admin o sin `validada_at`.

---

## 8. Arquitectura y calidad

**Estado del área:** completada.

#### A-01 — `/cobranza` está caída: devuelve 500 siempre
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO · **Esfuerzo:** 10 minutos

`CobranzaWebController::index()` arma el combo de filtro con `Grupo::where('activo',true)->orderBy('nombre')->get()`. La migración `2026_04_17_040414_refactor_grupos_add_nivel_remove_nombre` **eliminó la columna `grupos.nombre`**; el modelo la repuso solo como accessor PHP, que Eloquent no puede traducir a SQL.

- **Evidencia:** ejecutando la consulta real → `ERROR 1054 (42S22): Unknown column 'nombre' in 'order clause'`. `SHOW COLUMNS FROM grupos` confirma que no existe.
- **Archivo:** `app/Http/Controllers/CobranzaWebController.php:27`
- **Consecuencia:** El módulo de Cobranza entero es inaccesible. Es la pantalla donde se ve quién debe — función de negocio central. Ninguna prueba lo detecta.
- **Recomendación:** `Grupo::with(['deporte','nivel'])->...->get()` ordenando en PHP por `nombre_completo`, o replicar el join que ya usan `ClaseWebController:110-116` y `AlumnoWebController:46-52`.

#### A-02 — El accessor `Grupo::nombre` degrada a `" — "` en silencio
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** 1 h

`getNombreCompletoAttribute()` lee `deporte`/`nivel` solo si `relationLoaded()`; si no, concatena dos vacíos y devuelve `" — "`. Nunca devuelve `null`, así que **todo `?? 'Sin grupo'` río abajo es código muerto**.

- **Consecuencia:** `LiquidacionService:104` **persiste** esa descripción en `liquidacion_detalles.descripcion`, que va al recibo PDF del profesor: queda `"Clase 05/03/2026 -  —  (Con asistencia)"`. Un comprobante de pago con el grupo en blanco, ya escrito en BD e inmutable. Las filas actuales usan un formato anterior, así que **el daño se materializa en la próxima liquidación**.
- **Archivos:** `app/Models/Grupo.php:40-53`; `app/Services/LiquidacionService.php:87,104,431,445`

#### A-03 — Cuatro definiciones divergentes de "deuda impaga"
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** 2 h

| Lugar | Criterio |
|---|---|
| `WebController.php:53-57` (dashboard ADMIN) | `estado = PENDIENTE` **solamente** |
| `AlumnoWebController.php:57-59` | `estado NOT IN (PAGADA, CONDONADA)` **AND** `monto_pagado < monto_original` |
| `OperativoDashboardController.php:57-58` | idéntico al anterior |
| `DeudaCuota::estaPagada():70-74` | `estado === PAGADA` **OR** `monto_pagado >= monto_original` |

- **Consecuencia:** Una deuda AJUSTADA e impaga entra en el criterio del operativo pero **queda fuera** del dashboard del admin. El ADMIN ve **menos** deuda que el OPERATIVO sobre los mismos datos, lo que contradice directamente la regla de oro del proyecto. Hoy no se manifiesta porque solo existen estados PENDIENTE y PAGADA.
- **Recomendación:** un único `DeudaCuota::scopeImpaga()` y reemplazar los cuatro criterios.

#### A-04 — La máquina de estados de cobranza está escrita tres veces
**Severidad:** MEDIA · **Estado:** CONFIRMADO · **Esfuerzo:** 1-2 h

`AL_DIA / MOROSO / DEUDOR` con gracia al día 10 aparece en `CobranzaEstadoService::estadoAlumno()`, en `calcularEstadoDesdeDeudas()` (**duplicado literal dentro de la misma clase**) y en `AlumnoWebController:56-75` inline — este último con el umbral **hardcodeado** (`$diaActual > 10`) en vez de usar `DIA_GRACIA`. Mover la gracia a otro día corregiría dos de tres lugares y dejaría el listado de alumnos mintiendo.

---

## 9. Pruebas

**Estado del área:** completada.

#### T-01 — La suite no cubre nada: 12 de 14 tests fallan
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO · **Esfuerzo:** 1 h para desbloquear

- **Evidencia:** `php artisan test` → **`Tests: 12 failed, 2 passed (3 assertions)`**. Los 2 que pasan son los stubs de scaffold (`assertTrue(true)` y un GET a `/`). Los 12 de `PagoCuotaServiceTest` fallan en `setUp`, **antes de ejercitar una sola línea de negocio**.
- **Consecuencia:** cobertura efectiva de lógica de negocio = **0%**. Esto explica que A-01 —una página que devuelve 500 siempre— llevara tiempo sin detectarse.
- **Contradicción documental:** `CLAUDE.md` afirma *"Los tests base pasan"* y atribuye la falla a un solo archivo. Es literalmente cierto, pero oculta que **ese archivo es toda la suite real**.

#### T-02 — Una migración con `MODIFY` bloquea `RefreshDatabase` entero
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** 2 h (guards) / 4 h (MariaDB de test)

`ALTER TABLE grupos MODIFY nivel_id ...` es sintaxis MySQL; SQLite corta con `General error: 1 near "MODIFY"`. Son 5 de 68 migraciones con `DB::statement`, casi todas para manipular ENUM.

- **Nota positiva:** `phpunit.xml:22-23` usa `sqlite`/`:memory:` correctamente. **No apunta a `gestion_wings`**: no hay riesgo de que los tests toquen datos reales.
- **Recomendación:** envolver los 5 `DB::statement` en `if (DB::getDriverName() === 'mysql')`. Como las columnas ENUM no son representables en SQLite, la alternativa robusta es una conexión MariaDB de test.

---

## 10. Rendimiento

**Estado del área:** completada.

#### R-01 — El View Composer global escanea dos tablas completas en cada render
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** 1 h

`View::composer('*')` ejecuta el conteo de clases pendientes en **toda** vista renderizada, para todo usuario autenticado, sin caché.

- **Evidencia — EXPLAIN real:**
  ```
  1 PRIMARY      clases       type=ALL  key=NULL  rows=658
  2 MATERIALIZED asistencias  type=ALL  key=NULL  rows=3517
  ```
  Full scan en ambas. Además `whereDate('fecha', ...)` envuelve la columna en `date()`, lo que **inhabilita el índice** `clases_fecha_horario_index`.
- **Consecuencia:** ~4.200 filas materializadas por request, incluidos redirects, login y PDFs, donde el badge ni se muestra. Con el volumen actual el costo es tolerable; `asistencias` crece ~3.500/año y el escaneo es lineal.
- **Recomendación:** limitar el composer a los layouts que muestran el badge, cachear el conteo unos minutos, y usar `where('fecha','<',today())` para que el índice entre.

#### R-02 — Cobranza carga la tabla de alumnos completa dos veces por request
**Severidad:** MEDIA · **Estado:** CONFIRMADO · **Esfuerzo:** 2 h

`CobranzaWebController::index()` llama `filtrarAlumnosPorEstado()` **y** `resumenDashboard()`; ambas hacen su propio `Alumno::where('activo',true)->with('deudaCuotas')->get()`. El filtrado por estado ocurre en PHP, después de traer todo, y la lista no pagina.

- **Escala:** con 500 alumnos y varios años de deudas serían ~1.000 alumnos y ~24.000 deudas hidratadas como modelos Eloquent por pantallazo.

#### R-03 — Faltan índices en las columnas de filtro de los dashboards
**Severidad:** MEDIA · **Estado:** CONFIRMADO · **Esfuerzo:** 30 min

`deuda_cuotas` tiene PK y único `(alumno_id, periodo)` pero **nada sobre `estado`**, que es el filtro de los tres dashboards. `pagos` solo tiene las FK. `clases` no indexa `cancelada` ni `validada_para_liquidacion`.

---

## 11. Configuración e infraestructura

**Estado del área:** completada (complementa S-03, S-04, S-08, S-09, S-10 del área de seguridad).

#### C-01 — Nada ejecuta `schedule:run`: la generación mensual nunca corre
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** 30 min

`routes/console.php:13` registra `Schedule::command('cobranza:generar-deudas')->monthlyOn(1,'06:00')`, pero en Windows/XAMPP no existe el cron que dispare el scheduler.

- **Evidencia:** `schtasks /query /fo CSV` devuelve **0 entradas** que mencionen `php` o `artisan`. No hay `.bat` de scheduler; el único es `deploy-wings.bat`. Sin `schedule:run` cada minuto, Laravel nunca evalúa la expresión cron.
- **Consecuencia:** Es la causa raíz operativa de F-03. Y como `PagoCuotaService` bloquea cobrar un período pasado sin deuda previa, **un mes sin generar se vuelve incobrable** por la vía normal. No da error: simplemente no pasa nada el día 1.
- **Recomendación:** tarea programada de Windows que corra `php artisan schedule:run` cada minuto, más una verificación mensual de que la ejecución ocurrió.

#### C-02 — `.env.example` no declara ninguna variable de base de datos
**Severidad:** MEDIA · **Estado:** CONFIRMADO · **Esfuerzo:** 5 min

Faltan exactamente las 5 claves de BD (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). En el otro sentido no falta ninguna, así que es un recorte deliberado.

- **Consecuencia:** Un clon nuevo cae a los defaults de `config/database.php` y se conecta a la BD equivocada. **Ya ocurrió un incidente de este tipo** en el historial del proyecto (el `.env` apuntando a una BD vacía).
- **Verificado sano:** `.gitignore` cubre correctamente `.env`, `.env.backup`, `.env.production` y `*.log`.

---

## 12. Contradicciones documentales

**Estado del área:** completada.

| ID | Contradicción | Fuente que aplica realmente |
|---|---|---|
| **P-04** | `PERMISOS-ROLES.md:64-68` dice que *"ver el historial y los movimientos no está restringido por propiedad"*; el código lo restringe en 8 puntos | **El código** |
| **T-01** | `CLAUDE.md` afirma *"Los tests base pasan"*; la realidad es 12 de 14 fallando y 0% de cobertura de negocio | **El código** |
| **DOC-01** | La regla de mora (AL_DIA/MOROSO/DEUDOR, gracia día 10) **no tiene contrato**: `Wings-contrato-cuotas-deudas-pagos-V1.md:7` la excluye explícitamente y ningún otro documento la cubre — pero está implementada tres veces y gobierna el módulo Cobranza | **Solo el código** |
| **S-01** | `PERMISOS-ROLES.md` dice que el PROFESOR *"no participa de plata ni de alumnos"*; el servidor no lo impide | **El código** |

**DOC-01 es la más importante de las cuatro**, porque es causa y no síntoma: el umbral de 10 días, la precedencia DEUDOR > MOROSO y el trato de las deudas condonadas **no están acordados en ningún lado**. Esa ausencia es exactamente lo que produjo A-03 y A-04 — sin contrato, cada implementación eligió su propio criterio.

**Verificado sano:** las 15 rutas que cita `CLAUDE.md` existen todas, sin referencias rotas. `ESTADO-ACTUAL.md` describe con precisión el estado de los tests y del motor de cobranza; su debilidad es la antigüedad (7 semanas) y que su tabla de contradicciones todavía trata `routes/api.php` como activa.

---

## 13. Funcionalidad y reglas de negocio

**Estado del área:** completada.

> **Este apartado contiene el hallazgo más grave de toda la auditoría (F-02 + F-03).** No es una vulnerabilidad ni una deuda técnica: es que **el sistema dejó de facturar hace tres meses y el tablero informa que está todo en orden.**

### 13.1 Estados y transiciones reconstruidos desde el código

**CajaOperativa**
```
(nueva) → ABIERTA ──cerrar(op|admin)──→ CERRADA ──validar(admin)──→ VALIDADA ⛔ terminal
ABIERTA ──validar(admin)──→ auto-cierra → VALIDADA
ABIERTA/CERRADA ──rechazar──→ RECHAZADA ──cerrar──→ CERRADA (recuperable)
ABIERTA y RECHAZADA admiten alta/edición/borrado de movimientos; CERRADA y VALIDADA no.
```

**DeudaCuota**
```
(auto en cobro / comando) → PENDIENTE ──pago total──→ PAGADA ──cancelarCobro──→ PENDIENTE
PENDIENTE ──condonar──→ CONDONADA ⛔      PENDIENTE ──ajustar──⇄ AJUSTADA
AJUSTADA: no cobrable por UI (los selectores filtran PENDIENTE) pero sí cuenta como impaga → callejón sin salida.
```

**Pago / MovimientoOperativo**
```
Pago: COMPLETADO ──cancelarCobroOperativo──→ ANULADO ⛔ (solo con caja ABIERTA|RECHAZADA)
Mov:  ACTIVO ──cancelar──→ CANCELADO ⛔
Tras VALIDADA no existe ninguna transición de reversa para ninguno de los dos.
```

**Liquidación / Clase**
```
Liq: ABIERTA ⇄ recalcular ; ABIERTA ──eliminar──→ (borrada) ; ABIERTA ──cerrar──→ CERRADA ⛔
     CERRADA+PENDIENTE ──pagar──→ CERRADA+PAGADA ⛔ (sin reabrir, sin des-pagar, sin borrar)
Clase: cancelada false⇄true (libre) ; validada_para_liquidacion false⇄true (toggle libre,
     sin verificar si esa clase ya fue liquidada y cerrada)
```

### 13.2 Hallazgos críticos

#### F-02 — El comando de generación de deudas no crea ninguna deuda
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo (1 línea) / alto si hay que decidir la política

El período objetivo es **mes+1**, y la ventana de elegibilidad es "el mes anterior al objetivo" = **el mes en curso**. El schedule lo corre el **día 1 a las 06:00**, cuando el mes en curso no tiene todavía ni una asistencia ni un pago.

- **Evidencia:** `$periodo = $ahora->copy()->addMonth()` (`:42`) + `$periodoAnterior = Carbon::parse($periodo.'-01')->subMonth()` (`:46`). Corriendo el 2026-09-01, evalúa asistencias y pagos entre 2026-09-01 y 2026-09-30.
- **Archivo:** `app/Console/Commands/GenerarDeudasMensualesCommand.php:42,46,86-119`; `routes/console.php:12`
- **Consecuencia:** La condición `$tieneAsistenciasMesAnterior || $tienePagoPeriodoAnterior` es **siempre falsa** → 0 deudas creadas y N alumnos enviados a revisión. La facturación mensual completa pasa a depender de que un admin resuelva a mano una revisión por alumno, todos los meses.
- **Recomendación:** La ventana debe ser el mes **cerrado** anterior al objetivo (`subMonths(2)` respecto del período), o correr el comando el último día del mes. Fijar el criterio en el contrato antes de tocar el código.

#### F-03 — Sin deuda emitida, el sistema informa a todos los alumnos como AL_DIA
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO (verificado contra datos reales) · **Esfuerzo:** medio

La rama que marca MOROSO exige que exista `$deudaVigente`. Si no hay deuda generada, ninguna rama dispara y cae al `else` → **AL_DIA**.

- **Evidencia:** `} elseif (!$vigentePagada && $deudaVigente && $diaActual > self::DIA_GRACIA) { … } else { $estado = self::ESTADO_AL_DIA; }`
- **Archivo:** `app/Services/CobranzaEstadoService.php:49-56`, duplicado en `:195-202`
- **Verificación con datos reales:** `MAX(periodo)` en `deuda_cuotas` = **2026-05**. Último pago, mes 5. Hoy es 2026-08. **Junio, julio y agosto no fueron facturados**, y los 35 alumnos activos figuran AL_DIA. La tabla `alumnos_revision_cobranza` tiene **0 filas**: el comando nunca corrió.
- **Consecuencia:** F-02 —o simplemente que el scheduler no se ejecute— **no produce ninguna alarma**. El dashboard de cobranza informa "todos al día" mientras se acumulan meses sin emitir. Es una falla silenciosa de todo el circuito de ingresos.
- **Recomendación:** Distinguir `SIN_DEUDA_GENERADA` de `AL_DIA`, y mostrar un aviso en el dashboard cuando el período vigente no tiene deudas emitidas. Unificar además `estadoAlumno` y `calcularEstadoDesdeDeudas`, hoy duplicados y ya divergibles.

#### F-01 — Los movimientos cancelados entran al cashflow al validar la caja
**Severidad:** CRÍTICA · **Estado:** CONFIRMADO · **Esfuerzo:** 1 línea

Todo el sistema calcula plata sobre `estado='ACTIVO'` — hay hasta un comentario explícito *"Solo movimientos activos: los cancelados no cuentan plata"* (`CajaWebController:224`). El único punto que **no** filtra es justamente el que escribe en el libro contable.

- **Archivo:** `app/Services/CashflowIntegracionCajaService.php:47-73`
- **Consecuencia:** Un cobro cancelado (con deuda ya revertida y pago ANULADO) se asienta en cashflow por su monto completo. Y como el reflejo es idempotente por caja, **una vez validada no hay forma de corregirlo**: no existe des-validar ni borrar asientos.
- **Nota:** coincide con el hallazgo D-04, detectado de forma independiente por la auditoría de base de datos.

### 13.3 Hallazgos altos y medios

#### F-04 — Liquidación cerrada es irreversible y bloquea el período del profesor
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** medio

`cerrarLiquidacion` no valida nada: ni total > 0, ni que el período haya terminado, ni que las clases estén validadas. No existe ruta de reapertura. `recalcular` y `eliminar` rechazan CERRADA, y `validarNoExisteLiquidacion` bloquea cualquier segunda liquidación para ese profesor+mes+año.

- **Consecuencia:** Cerrar de más —o generar un mes futuro, que el controller permite hasta `now()->year+1` con total 0— deja el slot **muerto**: no se recalcula, no se borra, no se reabre, y no se puede generar otra. Las clases de ese profesor en ese mes nunca se pagan.
- **Agravante:** `toggleValidada` y des-cancelar una clase cambian libremente el insumo de una liquidación ya cerrada, sin ningún chequeo.

#### F-05 — Deuda de período pasado, condonación y ajuste no tienen ruta web
**Severidad:** ALTA · **Estado:** CONFIRMADO · **Esfuerzo:** medio-alto

`obtenerOcrearDeuda` rechaza períodos pasados con el mensaje *"Debe crearla un administrador"* — pero **esa capacidad no existe en la aplicación**: `condonarDeuda`, `ajustarDeuda` y `crearDeudaSiNoExiste` solo se invocan desde el controller de la API deshabilitada y desde el comando.

- **Consecuencia:** (a) una cuota vieja no emitida es incobrable, y el error deriva a una función inexistente; (b) no hay forma de perdonar una deuda incobrable, así que el alumno queda DEUDOR permanente; (c) el estado AJUSTADA, si existiera, saldría de todos los selectores de cobro pero seguiría contando como impaga: deuda invisible e incobrable.
- **Relación:** explica por qué F-02 no tiene remedio manual razonable hoy.

#### F-06 — FIFO fuerte no se valida cuando se paga un solo período
**Severidad:** MEDIA · **Estado:** CONFIRMADO · **Esfuerzo:** bajo

El contrato lo declara regla freeze (*"No se permite romper FIFO fuerte"*), pero el validador **sale antes de mirar nada** si hay un único ítem, y solo compara entre los ítems enviados — nunca contra las deudas **no incluidas** en el pago.

- **Evidencia:** `if (count($items) <= 1) { return; }` (`PagoCuotaService.php:412-435`)
- **Consecuencia:** Un alumno con enero, febrero y mayo pendientes puede pagar solo mayo. La antigüedad de la mora se distorsiona.
- **Recomendación:** Comparar los períodos enviados contra **todas** las deudas impagas anteriores del alumno; eliminar el early-return.

#### F-07 — La regla de primer pago descuenta todos los períodos del pago
**Severidad:** MEDIA · **Estado:** PROBABLE (requiere definición de negocio) · **Esfuerzo:** bajo

Detectada la regla, el porcentaje se aplica a la lista completa de ítems y `ajustarDeudas` reescribe el `monto_original` de cada uno.

- **Consecuencia:** Un alumno nuevo con regla del 50% que abona 3 meses juntos paga los 3 al 50%, y la rebaja queda **persistida** en las tres deudas (irreversible sin ajuste manual, que no tiene UI — ver F-05).
- **No se encontró en los contratos** una regla que autorice extender el descuento más allá de la primera cuota. **Requiere confirmación del usuario antes de tocar código.**

#### F-08 — La comisión del profesor se calcula sobre la fecha de cobro, no sobre el período de la cuota
**Severidad:** MEDIA · **Estado:** CONFIRMADO · **Esfuerzo:** medio

`crearPago` graba `mes`/`anio` desde la **fecha de pago**, y la liquidación filtra los pagos por ese campo. Pero un pago puede cubrir N períodos y su `monto_final` es la suma de todos.

- **Consecuencia:** Si un alumno cancela 3 meses de atraso en agosto, el profesor cobra comisión sobre los 3 en agosto y $0 en los meses cubiertos. Como el modelo de cobranza es **anticipado**, el desfase es sistemático, no excepcional.
- **Agravante:** `previsualizarLiquidacionComision` duplica el cálculo palabra por palabra; toda corrección hay que hacerla dos veces o divergen.
- **Recomendación:** Liquidar sobre `pago_deuda_cuota.monto_aplicado` agrupado por `deuda_cuotas.periodo`, no sobre `pagos.mes/anio`.

---

## 14. Riesgos agrupados por severidad

**Total: 41 hallazgos.**

| Severidad | Cantidad | IDs |
|---|---|---|
| **CRÍTICA** | 7 | S-01, S-02/P-01, D-01, D-02, F-01/D-04, F-02, F-03, A-01, T-01 *(S-02 y P-01 son el mismo hallazgo; F-01 y D-04 también)* |
| **ALTA** | 14 | S-03, S-04, P-02, P-03, P-04, P-05, D-03, D-05, D-06, D-07, F-04, F-05, A-02, A-03, R-01, T-02, C-01 |
| **MEDIA** | 14 | S-05, S-06, S-07, S-08, S-09, P-06, P-07, P-08, P-09, D-08, D-09, F-06, F-07, F-08, A-04, R-02, R-03, C-02, DOC-01 |
| **BAJA** | 6 | S-10, S-11, S-12, S-13, P-10, D-10 |
| **INFORMATIVA** | — | Verificaciones sin hallazgo (secciones 5.5 y 7.5) |

Por estado de verificación: **39 CONFIRMADO**, **2 PROBABLE** (F-07 y parte de A-02, ambos dependientes de una definición de negocio).

### El patrón de fondo

Los hallazgos no están distribuidos al azar. Se agrupan en tres familias, y cada una tiene una causa distinta:

1. **Controles que existen pero no se aplican.** `ensure.profesor.web` sin usar, `bloqueo.caja.vieja` sin aplicar, el scope `activos()` que existe y no se llama en el reflejo a cashflow, el `lockForUpdate` que está en un service y falta en el otro. **El código correcto ya está escrito; falta enchufarlo.** Casi todos son de esfuerzo bajo.
2. **Reglas de negocio sin dueño único.** "Deuda impaga" definida de cuatro formas, la máquina de cobranza escrita tres veces, el cálculo de comisión duplicado. La causa está identificada: **DOC-01**, la regla de mora nunca tuvo contrato.
3. **Fallas silenciosas.** Nada de esto tira error: el scheduler que no corre, las deudas que no se generan, el dashboard que dice "todos al día", los movimientos cancelados que entran al cashflow. **El sistema no avisa cuando deja de funcionar.**

---

## 15. Plan de corrección priorizado

### Bloque 0 — Parar la hemorragia (esta semana)

Son fallas activas que hoy están costando plata o bloqueando operación.

| # | Hallazgo | Por qué primero | Esfuerzo |
|---|---|---|---|
| 1 | **F-02 + C-01 + F-03** — no se generan deudas, nadie lo avisa | **Hay 3 meses sin facturar (junio, julio, agosto) y el tablero dice que está todo al día.** Es el problema más caro del sistema | bajo + 30 min |
| 2 | **A-01** — `/cobranza` devuelve 500 | El módulo para ver quién debe está caído. 10 minutos de arreglo | 10 min |
| 3 | **F-05** — no hay forma de emitir deuda de un período pasado | Sin esto, los 3 meses no facturados **no se pueden cobrar** por la vía normal | medio-alto |

### Bloque 1 — Cerrar accesos (esta semana, esfuerzo bajo)

| # | Hallazgo | Esfuerzo |
|---|---|---|
| 4 | **S-02/P-01** — usuario desactivado puede entrar y operar | bajo |
| 5 | **S-01** — PROFESOR accede a alumnos, caja y cobro | bajo |
| 6 | **P-02** — un operativo anula el cobro de otro | bajo |
| 7 | **P-03** — edición de movimiento saltea la validación de rubro | bajo |

### Bloque 2 — Integridad de dinero (próximas dos semanas)

| # | Hallazgo | Esfuerzo |
|---|---|---|
| 8 | **F-01/D-04** — movimientos cancelados entran al cashflow | 1 línea |
| 9 | **D-01** — sobrepago silencioso al imputar | bajo |
| 10 | **D-05** — cancelar un cobro no borra sus imputaciones | bajo |
| 11 | **D-03 + D-06** — locks faltantes (doble validación, doble cobro) | bajo |
| 12 | **D-02** — referencia rota caja→cashflow (65 asientos) | medio |

### Bloque 3 — Prevención (un mes)

| # | Hallazgo | Por qué | Esfuerzo |
|---|---|---|---|
| 13 | **T-01 + T-02** — desbloquear la suite | Hoy la cobertura de negocio es 0%. Sin esto, cada corrección de arriba es un salto de fe | 1-3 h |
| 14 | **DOC-01** — contrato de la regla de mora | Es la causa raíz de A-03 y A-04. Documentar **antes** de unificar | 2 h |
| 15 | **A-03 + A-04** — unificar "deuda impaga" y estados de cobranza | Requiere 14 hecho primero | 3-4 h |
| 16 | **S-07** — auditoría de acciones sobre dinero | Sin esto ningún incidente futuro es investigable | medio |
| 17 | **S-04, S-03, S-11** — debug, dump con sesiones, validación de fechas | Endurecimiento | bajo |
| 18 | **P-04** — decidir la contradicción del contrato de permisos | Decisión de producto, no técnica | medio |

### Decisiones de negocio pendientes (bloquean código)

Estos **no se pueden corregir sin una definición del dueño del producto**:

- **F-07:** ¿la regla de primer pago aplica solo a la primera cuota o a todos los períodos que se paguen juntos?
- **F-08:** ¿la comisión del profesor se calcula sobre la fecha de cobro o sobre el período de la cuota?
- **P-04:** ¿un operativo puede ver la caja de otro operativo?

---

## 16. Pruebas recomendadas

Priorizadas. Ninguna existe hoy.

1. **Smoke test de renderizado por rol** — `actingAs($u)->get($ruta)->assertSuccessful()` sobre las rutas de `web.php`. Habría atrapado **A-01** el día que se introdujo, y atrapa toda futura columna renombrada.
2. **`GenerarDeudasMensualesCommand`** — que genere una deuda por alumno activo con plan, que sea idempotente al reejecutar, que use el plan vigente del período y que derive a revisión solo los casos correctos. Es el motor de **F-02** y hoy no tiene una sola aserción.
3. **Ciclo de vida de caja** — apertura → movimientos → cierre → rechazo → validación → reflejo en cashflow, incluyendo `registrarPagoCuotaOperativo()`, que es la ruta de producción real y que el test actual **evita explícitamente** por su complejidad.
4. **Definición única de deuda impaga** — crear una deuda AJUSTADA impaga y afirmar que los tres dashboards devuelven el mismo conteo. Congela la corrección de **A-03**.
5. **Bordes de la máquina de cobranza** — día 10 vs día 11, deuda condonada, deuda de mes anterior impaga, verificando que listado y detalle coincidan. Congela **A-04**.
6. **Autorización por rol** — que PROFESOR reciba 403 en las rutas de caja y alumnos; que un usuario `activo=0` no pueda autenticarse. Congela **S-01** y **S-02**.

---

## 17. Conclusión

El sistema **no tiene un problema de construcción, tiene un problema de terminación**. El núcleo está bien pensado: los montos son decimales, los flujos de dinero están transaccionados, la imputación FIFO existe, la integración caja→cashflow es idempotente por diseño, y las ~60 rutas administrativas están correctamente protegidas. Nada de eso es casualidad ni es común.

Lo que falla es el borde: el control que se escribió pero no se enchufó, la regla que se implementó tres veces porque nunca se escribió una vez, el proceso automático que nadie verificó que estuviera corriendo.

**El hallazgo que hay que mirar hoy no es de seguridad.** Es que el sistema lleva tres meses sin emitir deuda y el tablero informa que está todo en orden. Eso no lo detectó nadie porque **el sistema está diseñado para no avisar cuando deja de funcionar**: la ausencia de deuda se interpreta como "al día", el scheduler que no corre no deja rastro, y la suite de tests que debería haber avisado no ejecuta una sola línea de negocio.

La buena noticia es que la mayor parte del Bloque 0 y el Bloque 1 —lo que más duele— son correcciones de **esfuerzo bajo**, varias de una sola línea. La mala es que sin resolver antes **F-05** (poder emitir deuda de un período pasado), los tres meses no facturados no son cobrables por la vía normal del sistema.

---

## 18. Anexos

### 18.1 Comandos ejecutados (todos de solo lectura)

```bash
# Inventario de alcance
find app/Http/Controllers -name '*.php' | wc -l
php artisan route:list --json

# Verificación de exposición HTTP (resultado: todo 404, vhost correcto)
curl -s -o /dev/null -w '%{http_code}' http://gestion-wings/.env
curl -s -o /dev/null -w '%{http_code}' http://gestion-wings/database/dump.sql

# Suite de tests (resultado: 12 failed, 2 passed)
php artisan test

# Dependencias
composer audit
npm audit

# Scheduler (resultado: 0 entradas)
schtasks /query /fo CSV

# Integridad de dinero (ejemplos)
SELECT d.id FROM deuda_cuotas d JOIN pago_deuda_cuota pd ON pd.deuda_cuota_id=d.id
  GROUP BY d.id HAVING ABS(d.monto_pagado - SUM(pd.monto_aplicado)) > 0.01;
SELECT COUNT(*) FROM cashflow_movimientos WHERE referencia_tipo='CAJA_OPERATIVA'
  AND referencia_id > (SELECT MAX(id) FROM cajas_operativas);
SELECT MAX(periodo) FROM deuda_cuotas;
EXPLAIN SELECT COUNT(*) FROM clases WHERE ... ;
```

**No se ejecutó** ningún `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP`, migración, rollback ni seeder. No se modificó ningún archivo del sistema.

### 18.2 Áreas principales revisadas

`app/Services/` (15) · `app/Http/Controllers/` (44) · `app/Models/` (26) · `app/Http/Middleware/` (4) · `app/Http/Requests/` (32) · `app/Providers/` · `app/Console/Commands/` · `routes/web.php` · `routes/console.php` · `bootstrap/app.php` · `database/migrations/` (68) · `database/seeders/` (10) · `resources/views/` (75) · `tests/` (4) · `config/` · `docs/02-contratos/` (13) · `docs/00-estado/` · `CLAUDE.md`

### 18.3 Áreas que no pudieron verificarse

| Área | Motivo |
|---|---|
| Explotación en vivo de S-01, S-02, P-02 | Auditoría estática; no se dispararon peticiones autenticadas. Confirmados por lectura completa de la cadena de control, sin traza HTTP |
| Concurrencia real (D-03, D-06) | Requiere ejecución concurrente y escrituras, prohibidas en esta auditoría |
| Origen de los 65 asientos de D-02 | El patrón sugiere `DemoSeeder`, pero sin tabla de auditoría no se puede distinguir de operación real |
| Impacto real de F-01/D-04 y D-05 | 0 movimientos CANCELADO y 0 pagos ANULADO en la BD: son bugs latentes, se activan con el primer uso de "Cancelar" |
| Diff exhaustivo migraciones ↔ esquema | Se verificaron las tablas de dinero y `alumno_planes`, no las 35 tablas una por una |
| Si `schedule:run` corre por otro mecanismo | Se descartaron tareas de Windows y `.bat` del repo; no se puede descartar un servicio externo o ejecución manual |
| Cobertura de código en porcentaje | Requiere Xdebug/PCOV |
| Exposición de red del host | No se determinó si Apache escucha en LAN o solo en localhost |
| Reachability de los CVE de dependencias | `composer audit` reporta por versión, no por alcance |
| N+1 en las 75 vistas | Se revisaron los listados principales; no se barrieron todas |

---

*Fin del informe.*
