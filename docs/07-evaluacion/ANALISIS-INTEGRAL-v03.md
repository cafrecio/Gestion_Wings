# Análisis integral — Gestión Wings — v03

**Fecha de corte:** 2026-08-04 05:49:00 (America/Buenos_Aires)  
**Repositorio:** `C:\xampp\htdocs\gestion-wings`  
**Rama / commit:** `main` / `c763933ec34bbb44c1f88325af5c0b5b4eb5b566`  
**Modo:** auditoría técnica, funcional y de seguridad; solo lectura sobre código y datos  
**Versión anterior comparada:** `ANALISIS-INTEGRAL-v02.md`  
**Informe comparativo:** `COMPARATIVA-v02-v03.md`

---

## 1. Resumen ejecutivo

El sistema implementa un circuito amplio y reconocible de alumnos, planes, deudas, cobros, caja, cashflow, clases, asistencias, liquidaciones y recibos. Sin embargo, **no está en condiciones de considerarse seguro ni contablemente confiable para exposición en red o trabajo concurrente** sin corregir primero los hallazgos P0.

Esta revisión consolidó **57 hallazgos únicos**: **6 CRÍTICOS, 31 ALTOS, 15 MEDIOS, 4 BAJOS y 1 INFORMATIVO**. De ellos, **48 están confirmados por código/configuración/comandos**, **7 son probables** y **2 son recomendaciones**. La cifra no es comparable de forma mecánica con v02: esa versión declara 41 hallazgos, pero su tabla e IDs internos no concilian; v03 usa IDs consecutivos y conteo mecánico verificable.

Los seis riesgos críticos son:

1. un `PROFESOR` puede invocar por URL operaciones de caja, cobros y alumnos;
2. una cuenta desactivada puede autenticarse y conservar sesiones;
3. existe XSS almacenado en el autocomplete de alumnos;
4. el instalador ejecuta seeders que crean una cuenta operativa de credenciales predecibles y datos financieros de prueba;
5. un sobrepago puede registrarse completo en pago/caja/pivote y acreditarse solo parcialmente en deuda;
6. un movimiento cancelado puede ingresar igualmente al cashflow al validar la caja.

Los problemas se refuerzan entre sí: la autorización permite alcanzar operaciones financieras; esas operaciones carecen de locks, unicidades e invariantes suficientes; el despliegue genera credenciales/datos de prueba; y la suite real no arranca, por lo que no existe una red automática que detecte regresiones.

### Dictamen por área

| Área | Estado | Motivo principal |
|---|---|---|
| Funcionalidad/reglas | Rojo | Flujos parciales, FIFO evadible, scheduler desalineado y reglas históricas mutables |
| Seguridad | Rojo | Escalada de rol, cuentas inactivas, XSS, dump sensible y configuración insegura |
| Roles/permisos | Rojo | El backend no aplica la matriz contractual en el dominio diario |
| Datos/integridad | Rojo | Sobrepagos, cancelaciones, referencias e idempotencia no conciliables |
| Arquitectura/calidad | Ámbar | Casos de uso duplicados y multiescrituras dentro de controladores |
| Pruebas | Rojo | 12/14 fallan antes de ejecutar negocio; los dos pases son scaffold |
| Rendimiento | Ámbar/Rojo | N+1 y cargas completas en asistencias, cobranza, caja y generación mensual |
| Infraestructura | Rojo | Seeder en deploy, debug/HTTP/root DB, scheduler ausente y backup no confiable |
| Documentación | Ámbar | Contratos valiosos, pero varias afirmaciones no coinciden con el código ejecutado |

## 2. Alcance revisado

Se inspeccionaron 399 archivos versionados, incluyendo:

- 15 servicios, 44 controladores, 26 modelos, 4 middleware y 32 Form Requests;
- 124 rutas web efectivas y el archivo de API actualmente no registrado;
- 68 migraciones, 10 seeders/factory y estructura del dump sin reproducir secretos ni datos;
- 75 vistas Blade y JavaScript embebido relevante;
- 4 archivos de tests;
- 56 archivos documentales, contratos, estado, guías de prueba y versiones de evaluación;
- `.env.example`, configuraciones Laravel, scripts de import/export y `deploy-wings.bat`;
- manifiestos y locks de Composer/npm.

No se modificó código fuente, configuración existente ni datos. Las únicas escrituras autorizadas son esta documentación v03.

## 3. Metodología y agentes utilizados

Se ejecutaron en paralelo auditorías especializadas de:

1. funcionalidad y reglas de negocio;
2. seguridad;
3. usuarios, roles y permisos;
4. base de datos e integridad;
5. arquitectura y calidad;
6. pruebas, errores y casos límite;
7. rendimiento y escalabilidad;
8. configuración, infraestructura y dependencias;
9. documentación y consistencia global.

Cada especialista trabajó sobre código real en modo solo lectura y devolvió evidencia con líneas. La consolidación aplicó estas reglas:

- unir duplicados de distintas áreas bajo un ID `AUD-nnn`;
- mantener como `CONFIRMADO` solo lo demostrable por código, esquema, configuración o comando reproducible;
- usar `PROBABLE` para carreras/reachability cuyo impacto requiere ejecución controlada;
- no trasladar a v03 afirmaciones dinámicas de v02 que no se revalidaron;
- separar el aviso de dependencia confirmado de su explotabilidad concreta;
- usar como fuente aplicada el código/routing/configuración efectivos cuando contradicen documentación, sin asumir que esa conducta sea la deseada.

## 4. Limitaciones de la auditoría

- No se consultó ni modificó una base productiva; no se enumeraron usuarios reales ni se validaron saldos actuales.
- No se enviaron requests mutantes ni se ejecutaron migraciones, seeders, importaciones, exports o despliegues.
- Las carreras se verificaron como patrones de lectura-modificación-escritura sin lock/unique; su ocurrencia real requiere MariaDB aislada y concurrencia controlada.
- No se ejecutó `EXPLAIN ANALYZE`, profiling ni pruebas de carga; los N+1/cargas completas sí están confirmados por estructura del código.
- No se verificaron firewall, Apache global, bind de MariaDB, TLS externo, UPS, espacio en disco o backups fuera del repo.
- La API está deshabilitada en `bootstrap/app.php:14-21`; sus defectos son latentes, no explotables por routing actual.
- No se midió cobertura porque la suite aborta en migraciones.
- No se reprodujeron secretos. Las ubicaciones sensibles se citan sin valores.

## 5. Descripción funcional reconstruida

### 5.1 Autenticación y navegación

`POST /login` autentica por email/contraseña y redirige según `User::rol`. Las rutas web principales quedan bajo `auth`; los dominios administrativos usan `ensure.admin.web`. El menú oculta opciones al profesor, pero caja/alumnos/movimientos no tienen un middleware servidor que excluya ese rol.

### 5.2 Alumnos, grupos, planes y vigencias

El alta web crea alumno y una vigencia `AlumnoPlan`. El evento `AlumnoPlan::creating` cierra la vigencia anterior (`app/Models/AlumnoPlan.php:29-42`). La edición de alumno, planes de grupo y el cambio de plan durante un cobro coordinan múltiples escrituras, no siempre dentro de una transacción común.

### 5.3 Deudas y pagos

El comando mensual evalúa alumnos activos con plan, asistencia o pago del período anterior. Crea una deuda única por alumno/período o una revisión. `PagoCuotaService` registra pagos operativos o admin, aplica primer pago/FIFO, imputa deudas y crea el movimiento de caja o cashflow. La deuda transita `PENDIENTE/AJUSTADA → PAGADA` y puede pasar a `CONDONADA`; el pago puede pasar de `COMPLETADO` a `ANULADO`.

### 5.4 Caja y cashflow

La caja sigue `ABIERTA → CERRADA → VALIDADA`; `ABIERTA/CERRADA → RECHAZADA`. Al validar, cada movimiento se refleja en cashflow. La implementación usa la caja como referencia común, mientras el contrato y un seeder usan el movimiento, y no filtra cancelados.

### 5.5 Clases y asistencias

Las clases pueden crearse en serie, editarse, cancelarse/reactivarse, validarse y reasignar profesores. La asistencia se guarda por clase/alumno y calcula excesos semanales. El backend no verifica que cada alumno recibido pertenezca al grupo y la escritura masiva no es atómica.

### 5.6 Liquidaciones y recibos

La liquidación puede ser por horas o comisión y transita `ABIERTA → CERRADA`; el pago separado pasa `PENDIENTE → PAGADA` y genera un egreso. Los recibos PDF se generan después del commit, pero todavía dentro del request; el fallo solo queda en logs.

### 5.7 Cobranza y revisión

`CobranzaEstadoService` clasifica `AL_DIA`, `MOROSO` o `DEUDOR`. Si no existe deuda del mes ni anterior, el alumno queda `AL_DIA`. Las revisiones transitan `PENDIENTE → RESUELTO` con acciones de continuidad, inactivación o reactivación automática; la asistencia web no invoca esa reactivación.

## 6. Evaluación de seguridad

La autenticación usa hashing de Laravel y mensaje genérico; los formularios mutantes revisados usan CSRF. No se encontraron SQLi por concatenación, uploads, `eval`, ejecución de comandos desde requests, SSRF, open redirect o deserialización directa controlada por usuario. Esos resultados negativos no compensan las fallas de autorización y despliegue.

Prioridades de seguridad:

- bloquear profesor y cuentas inactivas en backend (`AUD-001`, `AUD-002`);
- eliminar el XSS de autocomplete (`AUD-003`);
- retirar credenciales/datos de seed del deploy (`AUD-004`);
- tratar el dump como incidente potencial (`AUD-008`);
- endurecer producción y errores (`AUD-009`, `AUD-046`, `AUD-053`);
- mapear y actualizar dependencias en una tarea separada con regresión (`AUD-007`).

## 7. Matriz de usuarios, roles y permisos

Leyenda: **F** = restricción visible en frontend; **B** = control servidor efectivo.

| Rol | Módulo/pantallas | Permitido por contrato | Prohibido | F | B efectivo | Riesgo |
|---|---|---|---|---|---|---|
| ADMIN | Dashboard admin, caja global, cashflow, alumnos, clases, catálogos, usuarios, profesores, liquidaciones, configuración | Todo; validar/rechazar/cerrar cajas, liquidar/pagar, administrar | — | Sí | `ensure.admin.web` en dominios admin | Alto por integridad concurrente, no por falta general de rol |
| OPERATIVO | Dashboard, alumnos, cobros, caja, cobranza, clases/asistencias, grupos lectura | Operación diaria global del rubro; cerrar caja propia | Validar/rechazar caja, cashflow/config/usuarios/liquidaciones | Sí | Parcial: admin bloqueado; lectura de cajas/movimientos ajenos contradice contrato | Medio/alto (`AUD-010`, `AUD-037`)
| PROFESOR | Clases y asistencias | Ver/tomar asistencia de cualquier clase | Dinero, alumnos, caja, cobranza, grupos/precios | Sí: menú mínimo | **No:** rutas diarias solo `auth` | Crítico (`AUD-001`)
| Cuenta inactiva | Ninguna | No debe iniciar sesión; sesión debe revocarse | Toda operación | No relevante | **No global:** login ignora `activo` | Crítico (`AUD-002`)

Controles de clase efectivos: el profesor no puede crear/editar/validar clases por las rutas admin, ni cancelar/reasignar por checks ad hoc; sí puede registrar asistencia de cualquier clase. No existen Policies/Gates ni una matriz central; `ensure.profesor.web` está registrado pero sin uso.

## 8. Evaluación de base de datos e integridad

Aspectos positivos: montos principales usan `DECIMAL`; existen únicas en deuda `(alumno_id, periodo)`, asistencia `(clase_id, alumno_id)`, pivote pago/deuda, liquidación `(profesor, mes, año)` y plan activo nullable. Apertura de caja serializa sobre `User` con `lockForUpdate`.

Los huecos más peligrosos están en invariantes cruzadas y concurrencia:

- no se garantiza `pago = suma imputaciones = movimiento = incremento deuda`;
- cancelación no invalida el pivote y puede entrar a cashflow;
- referencias polimórficas no tienen FK/unique suficiente;
- validación de caja y pagos de liquidación usan `exists-then-insert` sin lock/unique;
- cascadas pueden borrar historia financiera;
- ajustes/condonaciones permiten estados monetariamente ambiguos.

## 9. Arquitectura y calidad

El problema dominante no es la ausencia de patrones sofisticados, sino la falta de un dueño único por caso de uso. `CajaWebController` (736 líneas) y `ClaseWebController` (605) mezclan adaptación HTTP, consultas, autorización contextual, reglas y escritura. Pago admin/operativo y cálculo/preview de liquidación duplican algoritmos; web y dos APIs mantienen definiciones distintas de alumno. Las correcciones deben extraer primero los casos críticos, acompañadas de pruebas de caracterización, sin reescritura total.

## 10. Pruebas y confiabilidad

Resultado reproducible de `php artisan test`: **12 fallos, 2 pases, 3 assertions**. Las 12 pruebas de negocio fallan antes de llegar al `setUp` por `ALTER TABLE ... MODIFY` incompatible con SQLite. Los dos pases son scaffold (`assertTrue(true)` y redirect raíz/login). La única suite real usa el flujo admin y evita explícitamente la caja operativa.

Por tanto, cobertura efectiva de negocio: **0% cualitativo ejecutable**. No existe matriz HTTP de roles, pruebas de caja/cashflow/liquidaciones/asistencias, rollback por fallos intermedios o concurrencia MariaDB.

## 11. Rendimiento y escalabilidad

Los hotspots confirmados son:

- pantalla/guardado de asistencia: múltiples consultas por alumno;
- generador mensual: 4–5 consultas por alumno más carga completa;
- comisión: una consulta de asistencia por pago;
- cobranza: carga dos veces alumnos y toda deuda, filtra en memoria;
- historial de caja: carga 90 días de dos tablas y pagina en PHP;
- cashflow: filtros `YEAR/MONTH`, agregados repetidos e índices ausentes;
- recibo PDF: DomPDF síncrono post-commit.

Sin datos productivos no se estiman tiempos; sí se confirma que el costo crece con alumnos, meses de deuda, asistencias y movimientos, no con el tamaño visible de página.

## 12. Configuración e infraestructura

El instalador actual no debe usarse como procedimiento productivo: ejecuta seeders generales, usa HTTP, debug/local, root MySQL sin contraseña, no endurece ACL, no instala scheduler y no ofrece rollback. Los scripts de backup sobrescriben un dump versionado y no prueban restore. El host inspeccionado hereda permisos de modificación a usuarios Windows autenticados.

`composer audit` confirmó 38 avisos en 15 paquetes; `npm audit` confirmó 10. No se actualizó ninguna dependencia.

## 13. Contradicciones documentales

| Tema | Documentación | Código/config aplicado | Evaluación |
|---|---|---|---|
| Profesor | Solo clases/asistencias | Caja/alumnos/movimientos bajo `auth` | Contradicción confirmada; aplica código |
| Cuenta inactiva | No entra y se desloguea | Login ignora `activo`; no hay middleware global | Contradicción confirmada; aplica código |
| Caja ajena operativo | Lectura global del dominio | detalle/listado filtran por propietario, historial no | Implementación internamente contradictoria |
| FIFO fuerte | No saltar deuda anterior | un ítem retorna sin validar y no consulta omitidas | Contradicción confirmada |
| Deuda mensual | Guía dice deuda “del mes” el día 1 | comando por defecto genera mes siguiente | Definición de negocio pendiente |
| Caja→cashflow | Una referencia por movimiento | servicio usa ID de caja; seeder usa ID de movimiento | Tres fuentes incompatibles |
| Asistencia y revisión | Asistencia reactiva automáticamente | API inactiva sí; flujo web no | Función documentada no aplicada |
| Pruebas de contratos | Docs citan 19/7 assertions | no existen en `tests/`; suite real no arranca | Evidencia no reproducible |
| API | Setup/deploy anuncia endpoints | routing API deshabilitado | Documentación operativa obsoleta |
| Producción | Guía menciona cache/config | deploy copia plantilla local/debug y no optimiza | Procedimiento no implementa guía |

La matriz definitiva y hallazgos documentales adicionales se incluyen en `AUD-023`, `AUD-037`, `AUD-039`, `AUD-047` y `AUD-050`.

---

## 14. Hallazgos detallados

Formato: cada hallazgo incluye área, severidad, estado, evidencia, consecuencia, escenario, recomendación, prioridad, esfuerzo y dependencias.

### AUD-001 — PROFESOR puede operar dinero y administrar alumnos

- **Área:** Autorización
- **Severidad:** **CRÍTICA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** caja, cobros, alumnos, `/operativo`, `/movimientos` y lectura de grupos dependen solo de `auth`; el sidebar los oculta, pero el servidor no excluye `PROFESOR`.
- **Evidencia:** `routes/web.php:35-65,73-77,89-97,122-155`; `app/Http/Controllers/CajaWebController.php:591-659,689-717`; `app/Http/Controllers/AlumnoWebController.php:19-76,156-191,221-250`; contrato `docs/02-contratos/PERMISOS-ROLES.md:19-35`; menú `resources/views/layouts/ds-app.blade.php:28-50`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** alteración de pagos, caja, deuda, planes y alumnos; acceso a DNI/contacto/tutor e información financiera; cajas atribuidas a profesor.
- **Escenario:** autenticar profesor y enviar directamente POST a `/caja/movimiento`, `/caja/cobrar/{alumnoId}` o `/alumnos`; no se ejecutó por ser mutante, pero la cadena de autorización es inequívoca.
- **Recomendación:** middleware deny-by-default `ADMIN|OPERATIVO` para todo el dominio diario y checks defensivos en operaciones financieras; mantener profesor solo en clases/asistencias.
- **Prioridad:** P0.
- **Esfuerzo:** medio (2–4 días con pruebas).
- **Dependencias:** depende de matriz central y `AUD-028`.

### AUD-002 — Cuentas inactivas pueden autenticarse y conservar acceso

- **Área:** Autenticación/revocación
- **Severidad:** **CRÍTICA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `Auth::attempt()` usa solo email/password (`app/Http/Controllers/WebController.php:22-37`); `UsuarioWebController.php:158-170` cambia `activo` sin revocar; el chequeo existe solo en `EnsureAdminWeb.php:18-21` y `EnsureProfesorWeb.php:19-21`, este último sin rutas. Contradice `docs/06-pruebas/FLUJO-USUARIO.md:455-464`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** un empleado revocado puede iniciar o continuar sesión y operar módulos críticos.
- **Escenario:** en entorno aislado, desactivar operativo, iniciar sesión y abrir `/caja/movimiento` o `/alumnos`.
- **Recomendación:** incluir `activo` en login, middleware global post-auth y revocar sesiones/tokens al desactivar, cambiar rol o contraseña.
- **Prioridad:** P0.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** relacionado con `AUD-001`.

### AUD-003 — XSS almacenado en autocomplete de alumnos

- **Área:** XSS
- **Severidad:** **CRÍTICA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** nombre/apellido/DNI aceptan strings (`AlumnoWebController.php:156-169,267-277`), se devuelven en JSON (`:79-103`) y `resources/views/alumnos/index.blade.php:232-253` inserta `r.label`/`r.sub` con `innerHTML`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** JavaScript persistente en navegador de admin/operativo, capaz de acciones same-origin y lectura de DOM/CSRF. `AUD-001` permite además que un profesor siembre el payload.
- **Escenario:** en base de prueba, apellido inocuo `<b>AUD-003</b>` se renderiza como HTML en autocomplete.
- **Recomendación:** crear nodos con `textContent`; CSP solo como defensa adicional.
- **Prioridad:** P0.
- **Esfuerzo:** bajo (<1 día + prueba).
- **Dependencias:** `AUD-001`, `AUD-053`.

### AUD-004 — Deploy crea cuenta predecible y datos financieros de prueba

- **Área:** Despliegue/credenciales
- **Severidad:** **CRÍTICA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `deploy-wings.bat:313-332` ejecuta `db:seed --force`; `DatabaseSeeder.php:20-23` crea `test@example.com`; `UserFactory.php:24-32` fija contraseña conocida; migraciones dan rol `OPERATIVO` y activo por defecto (`2026_02_06_063616...:14-16`, `2026_05_27_085302...:14-16`). `DatabaseSeeder.php:30-36` además carga cashflow de prueba.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** acceso conocido en una instalación y contaminación contable inicial; combinado con `AUD-001`, amplio alcance operativo.
- **Escenario:** instalación nueva aislada mediante el script y verificación de la cuenta/filas creadas.
- **Recomendación:** `DatabaseSeeder` solo para catálogos idempotentes; separar Demo/TestSeeder, abortar fuera de local/testing y definir bootstrap seguro del primer admin.
- **Prioridad:** P0.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** `AUD-036`.

### AUD-005 — Sobrepago registrado completo pero acreditado parcialmente

- **Área:** Integridad financiera
- **Severidad:** **CRÍTICA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** distintos registros del mismo cobro usan el monto solicitado y el monto realmente aplicable.
- **Evidencia:** `app/Services/PagoCuotaService.php:295-323,456-494,566-575`; `app/Http/Requests/StorePagoCuotaAdminRequest.php:16-24`; `app/Http/Requests/StorePagoCuotaOperativoRequest.php:16-24`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** pago, recibo, caja/cashflow y pivote pueden registrar $1.500 mientras deuda solo aumenta $1.000; rompe conciliación.
- **Escenario:** deuda saldo 1.000, request de 1.500. La web limita algunos caminos, pero servicio/API latente no garantiza la invariante.
- **Recomendación:** rechazar monto mayor al saldo o propagar el `montoAplicar` real a todas las entidades; constraint/prueba de conservación.
- **Prioridad:** P0.
- **Esfuerzo:** bajo.
- **Dependencias:** requiere locks de `AUD-014`.

### AUD-006 — Movimientos cancelados ingresan al cashflow

- **Área:** Caja/cashflow
- **Severidad:** **CRÍTICA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** la integración no distingue movimientos activos de cobros ya anulados.
- **Evidencia:** `app/Services/PagoCuotaService.php:603-653`; `app/Services/CashflowIntegracionCajaService.php:24,41-73`; scope no usado en `app/Models/MovimientoOperativo.php:85-88`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** deuda/pago se revierten, pero el ingreso aparece al validar caja; caja y cashflow divergen.
- **Escenario:** cobrar, cancelar con caja abierta/rechazada, cerrar y validar.
- **Recomendación:** integrar solo `ACTIVO` y probar caja mixta; reconciliar datos afectados antes de desplegar la corrección.
- **Prioridad:** P0.
- **Esfuerzo:** bajo.
- **Dependencias:** `AUD-015`, `AUD-017`.

### AUD-007 — Locks contienen 48 avisos de seguridad conocidos

- **Área:** Dependencias
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO** para versiones afectadas; explotabilidad individual **NO VERIFICADA**.
- **Descripción:** los archivos lock fijan versiones alcanzadas por avisos públicos actuales; se confirma exposición de versión, no que cada vector sea alcanzable desde Wings.
- **Evidencia:** `composer audit --locked`: 38 avisos/15 paquetes (4 high, 26 medium, 6 low, 2 sin severidad); `npm audit`: 10 (2 critical, 6 high, 1 moderate, 1 low). Ejemplos directos/transitivos observados: Laravel 12.46.0, DOMPDF 3.1.4, Guzzle 7.10.0, Symfony 7.4.x, Axios/Vite.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** exposición potencial a fallas conocidas; varias npm pertenecen a toolchain de desarrollo, por lo que no se afirma compromiso productivo sin reachability.
- **Escenario:** repetir auditorías con red y mapear advisory→versión→uso/ruta.
- **Recomendación:** priorizar avisos critical/high en rama separada, eliminar paquetes innecesarios y ejecutar regresión/rollback; no actualizar a ciegas.
- **Prioridad:** P0/P1.
- **Esfuerzo:** medio/alto.
- **Dependencias:** `AUD-027`, `AUD-028`.

### AUD-008 — Dump sensible versionado en Git

- **Área:** Datos/repositorio
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `database/dump.sql` está seguido por Git e incluye inserts de alumnos (`:90`), pagos (`:890`), personal access tokens (`:949`), profesores/PII (`:992`) y sesiones/IP/user-agent/payload (`:1080`). No se reproducen valores.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** lectores, clones y backups del repo reciben datos personales/financieros y artefactos de sesión; borrar en un commit futuro no limpia historia.
- **Escenario:** `git ls-files database/dump.sql` y búsqueda solo de encabezados `INSERT INTO`.
- **Recomendación:** fixtures sintéticos, ignorar dumps, evaluar incidente/rotación y limpiar historia coordinadamente si los datos son reales.
- **Prioridad:** P0/P1.
- **Esfuerzo:** medio/alto.
- **Dependencias:** `AUD-033`; origen real de datos no verificable.

### AUD-009 — Perfil de instalación inseguro: debug, HTTP y root DB vacío

- **Área:** Configuración.
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO** para plantilla/instalador/host local; exposición externa no verificable.
- **Descripción:** el procedimiento suministrado configura un entorno de desarrollo como instalación final, sin separación segura de producción.
- **Evidencia:** `.env.example:2-5` local/debug/HTTP; `deploy-wings.bat:268-286` copia plantilla y fija root/password vacía; `:382-389` vhost en 80; `config/session.php:172` deja `Secure` a env.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** trazas, cookies/credenciales sin TLS y privilegio DB máximo.
- **Escenario:** inspección de env generado/config efectiva sin mostrar secretos.
- **Recomendación:** perfiles separados; production, debug false, TLS, secure cookie, usuario DB dedicado y preflight que aborte valores inseguros.
- **Prioridad:** P0 antes de red.
- **Esfuerzo:** 1–3 días.
- **Dependencias:** infraestructura TLS.

### AUD-010 — IDOR permite cancelar cobro de otro operativo

- **Área:** Autorización de objeto
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** POST `cancelarMovimiento()` valida propiedad del `$cajaId` pero envía `$movId` sin scope (`CajaWebController.php:440-460`); `PagoCuotaService.php:603-618` resuelve el movimiento global. El GET sí usa `where('caja_operativa_id', $cajaId)` (`CajaWebController.php:417-437`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** operativo A puede anular cobro de B, revirtiendo deuda y alterando otra caja.
- **Escenario:** POST `/caja/{cajaPropia}/movimientos/{movAjeno}/cancelar`.
- **Recomendación:** resolver siempre movimiento por caja autorizada y volver a comprobar ownership/estado dentro del servicio transaccional.
- **Prioridad:** P0.
- **Esfuerzo:** bajo.
- **Dependencias:** `AUD-017`, `AUD-028`.

### AUD-011 — Asistencias aceptan alumnos ajenos y pueden guardarse parcialmente

- **Área:** Clases/asistencias
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** pantalla arma roster por grupo (`ClaseWebController.php:280-289`), pero POST usa cualquier `alumno_id` en `updateOrCreate` sin FormRequest/pertenencia (`:348-408`) y escribe ítem por ítem sin transacción. Un ID inválido con `presente=false` puede fallar después de filas válidas.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** asistencia/cupo/comisión/revisión del alumno incorrecto y respuesta de error con cambios parciales.
- **Escenario:** enviar alumno de otro grupo o lista con primer ítem válido y segundo inexistente.
- **Recomendación:** validar estructura completa, existencia, grupo, actividad, duplicados y vigencia antes de escribir; transacción y upsert por lote.
- **Prioridad:** P0.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** `AUD-029`, `AUD-028`.

### AUD-012 — Cambios de plan/alumno/grupo no son atómicos

- **Área:** Funcionalidad/arquitectura
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** cobro crea plan y recalcula deuda antes de entrar al servicio transaccional (`CajaWebController.php:607-629,651-659`; `PagoCuotaService.php:38-40`); edición de alumno actualiza y luego crea plan (`AlumnoWebController.php:239-251`); planes de grupo se eliminan/actualizan/crean sin transacción común (`GrupoWebController.php:128-165`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** fallo FIFO/caja/constraint deja plan/deuda o edición parcialmente aplicada.
- **Escenario:** seleccionar cambio de plan y provocar rechazo posterior del pago.
- **Recomendación:** casos de uso transaccionales únicos para cambiar plan+cobrar, alumno+vigencia y reemplazo de planes.
- **Prioridad:** P0.
- **Esfuerzo:** medio/alto.
- **Dependencias:** `AUD-013`, `AUD-014`.

### AUD-013 — FIFO fuerte puede omitirse

- **Área:** Deudas/pagos
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `validarFifo()` retorna para un ítem y con varios solo compara enviados (`PagoCuotaService.php:414-433`); nunca consulta deudas anteriores omitidas. Contrato: `docs/02-contratos/Wings-contrato-cuotas-deudas-pagos-V1.md:67-87`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** se paga junio dejando mayo/enero pendiente; cartera y comisión se distorsionan.
- **Escenario:** deuda vieja y reciente, enviar solo la reciente.
- **Recomendación:** bajo lock, consultar toda deuda cobrable anterior y exigir secuencia sin huecos.
- **Prioridad:** P0.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-014`.

### AUD-014 — Pagos concurrentes pierden o duplican imputación

- **Área:** Concurrencia financiera
- **Severidad:** **ALTA**.
- **Estado:** **PROBABLE**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** transacciones existen, pero deuda se lee sin `lockForUpdate` y se hace read-modify-save (`PagoCuotaService.php:295-325,336-340,423-426`). Unique de deuda evita duplicar deuda, no dos cobros.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** quedan dos pagos/pivotes/movimientos y una actualización de deuda pisada, o ambos aprueban el mismo saldo.
- **Escenario:** dos requests simultáneos a una misma deuda; no ejecutado por requerir escrituras.
- **Recomendación:** locks de deudas en orden estable, revalidar saldo/FIFO dentro del lock e idempotency key.
- **Prioridad:** P0.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-005`, `AUD-027`.

### AUD-015 — Caja→cashflow usa referencia incompatible y sin unicidad

- **Área:** Trazabilidad/idempotencia.
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**; impacto en datos actuales no verificable.
- **Descripción:** contrato, esquema, servicio y seeder usan unidades de referencia incompatibles.
- **Evidencia:** `docs/02-contratos/Wings-Contrato-Caja-Cashflow-V4.md:45-55`; `database/migrations/2026_02_01_100006_create_cashflow_movimientos_table.php:21-25`; `app/Services/CashflowIntegracionCajaService.php:31-38,64-73`; `database/seeders/DemoSeeder.php:507-527`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** un asiento existente hace omitir los restantes, demo/servicio pueden duplicar y reconciliación no sabe qué movimiento originó cada fila.
- **Escenario:** caja de tres movimientos con un asiento preexistente; revalidación omite dos.
- **Recomendación:** columnas/FK explícitas, unique por movimiento, backfill conciliado y upsert individual.
- **Prioridad:** P0/P1.
- **Esfuerzo:** medio/alto.
- **Dependencias:** `AUD-016`, `AUD-036`.

### AUD-016 — Doble validación puede duplicar cashflow

- **Área:** Concurrencia/idempotencia
- **Severidad:** **ALTA**.
- **Estado:** **PROBABLE**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `CajaService::validarCaja()` usa `findOrFail` sin lock (`:206-234`); integración ejecuta `exists-then-insert` sin unique (`CashflowIntegracionCajaService.php:31-73`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** dos validaciones paralelas pasan guardas y crean asientos dobles.
- **Escenario:** doble submit/conexiones simultáneas; no ejecutado.
- **Recomendación:** `lockForUpdate` sobre caja, unique por movimiento y upsert; no confiar solo en UI.
- **Prioridad:** P0.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-015`, `AUD-027`.

### AUD-017 — Cancelar pago conserva imputaciones activas

- **Área:** Auditoría financiera
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** la cancelación conserva como activa la relación contable entre pago y deuda.
- **Evidencia:** `app/Services/PagoCuotaService.php:620-652`; `database/migrations/2026_02_02_000002_create_pago_deuda_cuota_table.php:15-23`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** suma de imputaciones deja de conciliar con deuda; recibos/reportes por relación pueden contar pago anulado.
- **Escenario:** pagar deuda, cancelar y sumar pivote.
- **Recomendación:** conservar trazabilidad con estado/anulado_at o filtrar siempre por pago completado; conciliación y test de cancelación repetida.
- **Prioridad:** P0/P1.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-006`, `AUD-010`.

### AUD-018 — Pago concurrente de liquidación puede duplicar egreso

- **Área:** Liquidaciones/concurrencia
- **Severidad:** **ALTA**.
- **Estado:** **PROBABLE**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** saldo se comprueba fuera de transacción (`LiquidacionWebController.php:222-233`); servicio lee liquidación/cashflow sin locks, hace `exists/first`, crea asiento y recién luego marca pagada (`LiquidacionPagoService.php:28-38,71-74,101-122`). No hay unique de referencia.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** dos egresos por liquidación o dos liquidaciones consumiendo el mismo saldo.
- **Escenario:** doble submit o pagos simultáneos; requiere prueba aislada.
- **Recomendación:** lock de liquidación y serialización por tipo de caja, unique de referencia y saldo revalidado dentro de transacción.
- **Prioridad:** P0.
- **Esfuerzo:** medio/alto.
- **Dependencias:** `AUD-015`, `AUD-027`.

### AUD-019 — Edición de clase omite solapamiento y trazabilidad retroactiva

- **Área:** Clases/profesores
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** crear/reasignar valida disponibilidad (`ClaseWebController.php:263-276,572-580`), pero `update()` cambia horario/profesores directamente (`:426-450`) y no exige motivo histórico; contrato `Wings-Contrato-Clases-Asistencias-V1.md:43`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** profesor en dos clases simultáneas y modificación histórica/liquidada sin justificación.
- **Escenario:** editar clase para superponerla con otra del mismo profesor.
- **Recomendación:** reutilizar validación de solapamiento, transacción update+sync y motivo/auditoría en retroactivos; proteger clases liquidadas.
- **Prioridad:** P0.
- **Esfuerzo:** medio.
- **Dependencias:** liquidaciones por hora.

### AUD-020 — Comisión depende de fecha de cobro y datos actuales mutables

- **Área:** Liquidaciones.
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO** técnicamente; regla deseada pendiente.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** liquidación filtra `Pago.mes/anio`, alumno activo y deporte actual (`LiquidacionService.php:142-173,468-500`); pago usa fecha de cobro y guarda `plan_id=null` (`PagoCuotaService.php:475-493`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** pago atrasado multi-mes concentra comisión en cobro; inactivar/mover alumno antes de liquidar elimina o reasigna comisión devengada.
- **Escenario:** alumno paga y asiste, luego cambia deporte/inactivo antes de generar liquidación.
- **Recomendación:** decidir contrato; si corresponde período de cuota, usar pivote+monto aplicado+deuda.periodo y snapshot histórico de plan/deporte.
- **Prioridad:** P0/P1.
- **Esfuerzo:** alto.
- **Dependencias:** decisión contable e histórica.

### AUD-021 — Ajustar/condonar permite estados monetarios contradictorios

- **Área:** Deudas
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** request permite monto desde cero (`AjustarDeudaRequest.php:14-19`); servicio conserva `monto_pagado`, acepta nuevo original menor y solo marca PAGADA (`PagoCuotaService.php:236-257`); condonar una PENDIENTE parcialmente pagada conserva el cobro (`:207-220`); saldo oculta exceso con `max(0)` (`DeudaCuota.php:60-65`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** sobrepago invisible o deuda condonada con dinero previo sin crédito, devolución ni asiento.
- **Escenario:** original 100, pagado 80, ajustar a 50; o condonar deuda parcialmente pagada.
- **Recomendación:** bloquear ajuste inferior o modelar explícitamente quita/reintegro/asiento; ledger de ajustes y constraints de dominio.
- **Prioridad:** P0.
- **Esfuerzo:** medio.
- **Dependencias:** decisión contable.

### AUD-022 — Edición permite subrubro reservado/ADMIN/inactivo

- **Área:** Caja/reglas de catálogo
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `updateMovimiento()` solo protege el subrubro anterior; el nuevo valida `exists` y se asigna (`CajaWebController.php:362-382`). Alta sí valida reservado, activo y `permitido_para` (`CajaService.php:106-117,290-298`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** operativo reclasifica movimiento a Cuota Mensual/ADMIN/inactivo, cambia signo/destino y lo vuelve invisible en ciertos listados.
- **Escenario:** crear movimiento válido y PUT con ID reservado/ADMIN.
- **Recomendación:** centralizar update en servicio y validar todas las reglas sobre el nuevo valor.
- **Prioridad:** P0.
- **Esfuerzo:** bajo.
- **Dependencias:** `AUD-006`, `AUD-028`.

### AUD-023 — Scheduler ausente y período mensual desalineado

- **Área:** Cobranza/infraestructura.
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO** en repo/host; mecanismo externo no visible, no verificable.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** Laravel agenda día 1 06:00 (`routes/console.php:11-12`), pero no hay `schedule:run/work` en deploy/scripts ni tarea Windows observada. Además el comando sin opción usa `now()->addMonth()` (`GenerarDeudasMensualesCommand.php:33-43`) mientras `FLUJO-USUARIO.md:499` dice generar “del mes”.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** automatización no corre; si se instala tal cual, el 1 de agosto genera septiembre y agosto puede quedar sin emisión.
- **Escenario:** `php artisan schedule:list` y Task Scheduler; no se ejecutó el comando de negocio.
- **Recomendación:** decidir cobro anticipado vs corriente, alinear docs/código y crear tarea Windows cada minuto con rutas absolutas, lock, logs, monitor e instrucción manual idempotente.
- **Prioridad:** P0 antes del próximo ciclo.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** credenciales OS.

### AUD-024 — Pago ANULADO cuenta como actividad mensual

- **Área:** Generación de deuda
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** comando busca cualquier pago por alumno/mes/año sin estado (`GenerarDeudasMensualesCommand.php:94-97`); pago define `ANULADO` (`Pago.php:12-14`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** cobro cancelado evita revisión y puede generar deuda futura como si fuera válido.
- **Escenario:** único pago anterior anulado, sin asistencia, ejecutar generador del período siguiente.
- **Recomendación:** filtrar `COMPLETADO` y prueba explícita.
- **Prioridad:** P1.
- **Esfuerzo:** bajo.
- **Dependencias:** `AUD-017`, `AUD-023`.

### AUD-025 — Cascadas pueden borrar historia financiera

- **Área:** Retención/integridad
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** varias entidades maestras eliminan en cascada libros de dinero e historia operativa.
- **Evidencia:** `database/migrations/2026_02_01_100004_create_cajas_operativas_table.php:16`; `database/migrations/2026_02_01_100005_create_movimientos_operativos_table.php:16-22`; `database/migrations/2026_02_01_100006_create_cashflow_movimientos_table.php:17-20`; `database/migrations/2026_01_12_091912_create_pagos_table.php:16`; migraciones de creación de `deuda_cuotas:19` y `asistencias:20-21`; `database/migrations/2026_02_02_000002_create_pago_deuda_cuota_table.php:17-18`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** DELETE manual, seeder o futura API destruye trazabilidad sin aviso.
- **Escenario:** no ejecutado; inspección de FKs suficiente.
- **Recomendación:** RESTRICT/SET NULL según actor, snapshots y desactivación/soft delete; política de retención antes de migrar.
- **Prioridad:** P1.
- **Esfuerzo:** medio/alto.
- **Dependencias:** decisiones legales/contables.

### AUD-026 — API dormida es insegura e incompleta si se reactiva

- **Área:** API/arquitectura.
- **Severidad:** **ALTA**.
- **Estado:** **PROBABLE** y condicional; hoy no está registrada.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `bootstrap/app.php:14-21` deshabilita API. Si se reactiva: login sin activo/throttle (`routes/api.php:33-39`; `AuthController.php:23-55`), alumnos/pagos/clases/liquidaciones solo `auth:sanctum` (`routes/api.php:46-115`), tokens sin expiración (`config/sanctum.php:50`), y `AlumnoController` deja index/show/update/destroy vacíos (`:15-18,66-93`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** escalada, tokens indefinidos y respuestas/CRUD incoherentes; actualmente `/api/*` no es superficie explotable.
- **Escenario:** solo rama aislada; `route:list --path=api` actual no registra endpoints.
- **Recomendación:** banner “congelada”, no reactivar hasta autorización, activo, throttle, expiración, contratos y pruebas; eliminar rutas no soportadas.
- **Prioridad:** P0 si se reactiva/P2 mientras dormida.
- **Esfuerzo:** alto.
- **Dependencias:** `AUD-001`, `AUD-002`, `AUD-028`.

### AUD-027 — La suite de negocio no arranca

- **Área:** Pruebas
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `phpunit.xml:20-30` fuerza SQLite memory; migraciones usan MySQL `MODIFY` (`2026_04_17_040414...:54-63`, `2026_05_27_082557...:8-15`, `2026_06_21_133826...:12-25`, `2026_07_20_060052...:15-26`). Ejecución: 12 fallos/2 pases, todos los reales abortan antes de lógica.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** cobertura ejecutada de negocio nula y regresiones financieras pasan inadvertidas.
- **Escenario:** `php artisan test`.
- **Recomendación:** MariaDB exclusiva de test y `migrate:fresh` en CI; opcionalmente branches portables, pero conservar job MariaDB para enums/locks. Nunca apuntar a `gestion_wings` real.
- **Prioridad:** P0.
- **Esfuerzo:** medio (4–8 h base).
- **Dependencias:** ninguna.

### AUD-028 — Flujos críticos carecen de pruebas reproducibles

- **Área:** Confiabilidad
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** única suite real prueba pago admin y declara evitar caja (`PagoCuotaServiceTest.php:50-51,113-121`); no hay tests HTTP de roles/inactivos/IDOR, caja/cashflow, cancelación, liquidación, asistencia, scheduler, rollback ni concurrencia. Fixtures Grupo usan columna eliminada `nombre` y omiten `nivel_id` (`:75-79,446-450,523-527`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** aun arreglando migración, fixtures fallan o prueban esquema distinto; no hay red sobre hallazgos P0.
- **Escenario:** inventario de 14 métodos: dos scaffold, doce de pago admin.
- **Recomendación:** primero matriz rol×ruta, invariantes de dinero, rollback, cancelación, ciclo caja y concurrencia MariaDB; luego módulos restantes.
- **Prioridad:** P0.
- **Esfuerzo:** grande/incremental.
- **Dependencias:** `AUD-027`.

### AUD-029 — Asistencias generan N+1 severo

- **Área:** Rendimiento
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** show llama conteo por alumno (`ClaseWebController.php:281-297`); cada llamada hace COUNT y consulta plan (`ClaseService.php:219-230`). Guardado hace búsquedas/validaciones/update por ítem y vuelve a contar (`ClaseWebController.php:350-405`; `ClaseService.php:171-186,333-350`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** grupo de 50 agrega al menos ~100 consultas al show y varias por presente al guardar.
- **Escenario:** abrir/guardar asistencia de grupo grande.
- **Recomendación:** conteos/planes/asistencias bulk, validación previa y upsert transaccional.
- **Prioridad:** P0.
- **Esfuerzo:** medio/alto.
- **Dependencias:** `AUD-011`.

### AUD-030 — Generador mensual escala con 4–5 consultas por alumno

- **Área:** Rendimiento
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** carga todos los activos con `get()` y por alumno consulta deuda, plan, asistencia, pago e inserta (`GenerarDeudasMensualesCommand.php:53-119`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** memoria O(alumnos) y decenas de miles de queries con miles de alumnos.
- **Escenario:** 5.000 alumnos activos.
- **Recomendación:** `chunkById`, precarga por lote e inserts/upserts idempotentes preservando vigencias.
- **Prioridad:** P0.
- **Esfuerzo:** alto.
- **Dependencias:** `AUD-023`, reglas de plan.

### AUD-031 — Cobranza e historial cargan tablas completas y paginan en memoria

- **Área:** Rendimiento
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** cobranza carga dos veces alumnos+deudas y filtra estado en colección (`CobranzaWebController.php:22-24`; `CobranzaEstadoService.php:83-105,114-132`). Historial carga 90 días de movimientos/cashflow, fusiona, filtra, ordena y luego `forPage()` (`CajaWebController.php:86-196`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** memoria/tiempo crecen con años de deuda y volumen trimestral, aunque la página muestre 30 filas.
- **Escenario:** miles de alumnos o cientos de operaciones diarias.
- **Recomendación:** estados por agregados SQL reutilizados y paginados; historial con `UNION ALL` normalizado, filtros/orden/paginación en DB.
- **Prioridad:** P0.
- **Esfuerzo:** alto.
- **Dependencias:** definición de cobranza (`AUD-039`).

### AUD-032 — Liquidación por comisión consulta asistencia por cada pago

- **Área:** Rendimiento
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** obtiene pagos y dentro del loop ejecuta `exists()` con joins (`LiquidacionService.php:142-167`); preview duplica patrón (`:468-494`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** N pagos → N subconsultas adicionales y duplicación entre preview/generación.
- **Escenario:** profesor/deporte con cientos de pagos.
- **Recomendación:** set SQL de alumnos con asistencia y cruzar en una consulta/colección; compartir cálculo con preview.
- **Prioridad:** P0.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-020`, `AUD-043`.

### AUD-033 — Backup/restore expone secretos y no garantiza recuperación

- **Área:** Backup/recuperación.
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO** para scripts; eficacia de restore no verificable.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `db-export.sh:19,35-50` sobrescribe `database/dump.sql`, pasa password en argv, excluye solo users e invita a commitear; `db-import.sh:40-49` mezcla sobre DB existente sin checksum, cifrado, retención, drop limpio o drill.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** contraseña visible a procesos, pérdida del backup anterior, PII en Git y restore no determinista; RPO/RTO desconocidos.
- **Escenario:** revisión de scripts; no se importó/exportó.
- **Recomendación:** backup cifrado fuera del repo, credencial protegida, retención 3-2-1, excluir efímeras, checksum y simulacro periódico.
- **Prioridad:** P0/P1.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-008`.

### AUD-034 — ACL Windows permite modificar código, env y datos

- **Área:** Permisos OS.
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO** en host; producción no verificable.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `icacls` mostró `Usuarios autentificados:(M)` heredado en raíz, `.env`, `public/index.php`, dump, storage y cache; deploy `:426-441` no endurece ACL.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** otra cuenta Windows autenticada puede modificar PHP, secretos o datos y obtener ejecución bajo Apache.
- **Escenario:** consulta read-only de ACL.
- **Recomendación:** deployer/admin escribe código/env; Apache RX en código y Modify solo en storage/cache; ACL dedicada a backup y verificación postdeploy.
- **Prioridad:** P0/P1 si multiusuario.
- **Esfuerzo:** bajo.
- **Dependencias:** identidad Apache.

### AUD-035 — Deploy no es atómico ni recuperable

- **Área:** Despliegue
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** migra/seedea antes de build (`deploy-wings.bat:319-357`), npm/build puede quedar como aviso, no usa maintenance/release/backup/rollback; verificación es `route:list`, no smoke (`:444-455`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** esquema/datos cambiados con assets fallidos y versión parcial, sin retorno rápido.
- **Escenario:** fallo de build en clon después de migración; no ejecutado.
- **Recomendación:** build previo, checkpoint/backup, maintenance, migrate, smoke `/up`+login+ruta protegida, switch/release y rollback; fallar cerrado.
- **Prioridad:** P1.
- **Esfuerzo:** medio/alto.
- **Dependencias:** `AUD-033`.

### AUD-036 — Seeders no son seguros ni idempotentes

- **Área:** Datos/entornos
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `DatabaseSeeder.php:20-36` siempre llama cashflow; `CashflowMovimientoSeeder.php:21-29,69-78` usa `first` y `create`, agrega filas en cada ejecución y registra egresos positivos contra convención; `DemoSeeder.php:507-532` copia signo y usa referencia de movimiento incompatible.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** `db:seed` repetido agrega dinero ficticio, eleva saldo y mezcla semánticas de referencia.
- **Escenario:** no ejecutado; la ausencia de `firstOrCreate`/guard de entorno es explícita.
- **Recomendación:** separar demo, abortar fuera de testing/local, idempotencia y reutilizar servicios de signos/referencias.
- **Prioridad:** P1.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** `AUD-004`, `AUD-015`.

### AUD-037 — La fuente de verdad documental está obsoleta y anuncia funciones inaccesibles

- **Área:** Gobernanza/documentación
- **Severidad:** **ALTA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `CLAUDE.md:9-12` y `docs/README.md:5-8` declaran `ESTADO-ACTUAL.md` fuente de verdad, pero está fechado 2026-06-15 (`:3`) y dice dashboard pendiente/API corregida (`:41,63-66`); dashboard existe (`routes/web.php:73-74`) y API está apagada. `PLAN-MAESTRO.md:79-100` marca condonar/ajustar/cierre diario implementados, pero solo existen en `routes/api.php:161-169,203-208`, no cargado.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** onboarding duplica funciones, integradores llaman 404 y un admin queda sin salida web para deuda pasada/ajuste/condonación.
- **Escenario:** seguir jerarquía documental y buscar esas capacidades en runtime.
- **Recomendación:** actualizar fuente vigente con fecha/commit y tabla AS-IS/TO-BE/supersedido; enlazar índice versionado y etiquetar código API como inaccesible.
- **Prioridad:** P0 documental/P1 funcional.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** `AUD-026`, `AUD-050`.

### AUD-038 — Visibilidad de OPERATIVO contradice contrato y varía por pantalla

- **Área:** Permisos funcionales
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** contrato permite lectura global del dominio (`PERMISOS-ROLES.md:9-16,41-46,64-68,80-86`); resumen/detalle aborta caja ajena (`CajaWebController.php:209-221,258-271`) y `/movimientos` filtra propietario (`MovimientoWebController.php:15-30`), mientras `/caja/historial` es global (`CajaWebController.php:81-127`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** traspaso de turno bloqueado y control ilusorio: el renglón se ve en un módulo, no su detalle en otro.
- **Escenario:** operativo B abre detalle de caja A → 403, aunque historial la lista.
- **Recomendación:** separar lectura de mutación; aplicar contrato de dominio compartido o corregirlo explícitamente.
- **Prioridad:** P1.
- **Esfuerzo:** medio.
- **Dependencias:** decisión de producto.

### AUD-039 — Motor de cobranza y configuración no tienen una regla única

- **Área:** Reglas/documentación
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** la definición de mora, la ausencia de deuda y los parámetros editables no tienen una única fuente aplicada.
- **Evidencia:** `app/Services/CobranzaEstadoService.php:12-15,39-60,188-201`; `docs/06-pruebas/FLUJO-USUARIO.md:148-151`; `app/Http/Controllers/ConfiguracionWebController.php:19-38`; `database/migrations/2026_05_29_003356_create_configuraciones_table.php:21-37`; `routes/console.php:11-12`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** colores/estados ambiguos; admin guarda parámetros que no producen efecto; ausencia de emisión se presenta como al día.
- **Escenario:** cambiar gracia a 15: día 11 sigue usando 10; alumno sin deuda queda AL_DIA.
- **Recomendación:** contrato con tabla de verdad (incluida ausencia/AJUSTADA), implementación única y conectar o retirar settings.
- **Prioridad:** P1.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-023`, `AUD-031`.

### AUD-040 — Rollback de alumno_planes puede fallar con historial real

- **Área:** Migraciones/recuperación
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** la migración hacia atrás no puede representar múltiples planes históricos bajo el unique anterior.
- **Evidencia:** `database/migrations/2026_07_19_140000_alumno_planes_activo_nullable.php:19-34`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** rollback queda bloqueado a mitad durante recuperación.
- **Escenario:** alumno con dos planes cerrados y `migrate:rollback` en copia.
- **Recomendación:** precheck/irreversibilidad explícita o retirar/recrear unique durante conversión; probar rollback.
- **Prioridad:** P2.
- **Esfuerzo:** bajo.
- **Dependencias:** `AUD-035`.

### AUD-041 — Detalles de liquidación sin integridad referencial ni lock de recálculo

- **Área:** Liquidaciones/DB
- **Severidad:** **MEDIA**.
- **Estado:** **PROBABLE**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `liquidacion_detalles` usa tipo/id con index, sin FK/unique (`create_liquidacion_detalles...:19-26`); recalcular lee estado, borra y recrea sin lock (`LiquidacionService.php:223-255`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** referencias huérfanas y recálculos paralelos con duplicados/deadlock o pérdida de detalle.
- **Escenario:** dos recalculos simultáneos; requiere MariaDB aislada.
- **Recomendación:** lock liquidación, unique `(liquidacion_id,tipo_referencia,referencia_id)` y decidir snapshot vs FK.
- **Prioridad:** P2.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-018`.

### AUD-042 — Tres superficies CRUD definen alumnos distintos

- **Área:** Arquitectura/consistencia
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** web crea alumno+plan (`AlumnoWebController.php:156-176`); API general/admin solo alumno (`AlumnoController.php:34-46`; `Admin/AlumnoController.php:65-68`). Web exige DNI/plan y permite celular nulo; `StoreAlumnoRequest.php:25-37` omite DNI/plan y exige celular.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** validez y efectos dependen del endpoint; correcciones divergen.
- **Escenario:** mismo payload en web y APIs (si se reactivan).
- **Recomendación:** caso de uso canónico con adapters/FormRequests por transporte; decidir plan obligatorio.
- **Prioridad:** P1.
- **Esfuerzo:** alto.
- **Dependencias:** `AUD-026`.

### AUD-043 — Algoritmos financieros y de liquidación están duplicados

- **Área:** Mantenibilidad
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** pago operativo/admin repite pipeline (`PagoCuotaService.php:38-111,122-194`); liquidación definitiva/preview duplica hora y comisión (`LiquidacionService.php:69-193,417-517`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** una corrección se aplica en un camino y no en otro; preview puede no coincidir con persistencia.
- **Escenario:** comparar bloques paso a paso.
- **Recomendación:** pipeline/conceptos únicos y estrategia pequeña para asiento operativo/admin; preview serializa exactamente conceptos calculados.
- **Prioridad:** P1.
- **Esfuerzo:** medio.
- **Dependencias:** pruebas de caracterización `AUD-028`.

### AUD-044 — View Composer global duplica regla y consulta en cada render

- **Área:** Arquitectura/rendimiento
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `View::composer('*')` calcula clases pendientes (`AppServiceProvider.php:16-38`) y duplica lógica de filtro en `ClaseWebController.php:110-129`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** consulta en vistas autenticadas y riesgo badge/listado contradictorio.
- **Escenario:** query log al renderizar cualquier pantalla.
- **Recomendación:** servicio/query único memoizado y composer solo en layout/componente consumidor.
- **Prioridad:** P1.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** rendimiento.

### AUD-045 — PDF post-commit sigue síncrono y sin estado de fallo

- **Área:** Recibos/operación
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** callbacks `afterCommit` llaman directamente `ReciboService` (`PagoCuotaService.php:101-108,184-191`; `LiquidacionPagoService.php:130-137`); DomPDF/escritura ocurre en `ReciboService.php:70-78,136-144` y errores retornan `false` (`:212-244`). No hay jobs/listeners.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** latencia/memoria dentro del request y recibos faltantes solo visibles en log, sin reintento durable.
- **Escenario:** filesystem no escribible o render lento después de pago confirmado.
- **Recomendación:** job idempotente after-commit, reintentos, estado observable y regeneración controlada.
- **Prioridad:** P1.
- **Esfuerzo:** medio.
- **Dependencias:** worker/monitor de cola (`AUD-049`).

### AUD-046 — Excepciones genéricas y mensajes internos expuestos

- **Área:** Errores/seguridad
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** 123 ocurrencias de `throw/catch Exception`; controladores devuelven `$e->getMessage()` (`ReciboController.php:80-84,147-151`; `CajaWebController.php:313-323,651-665,700-714`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** respuestas/códigos inconsistentes y exposición posible de SQL, rutas, tablas/filesystem.
- **Escenario:** fallo controlado de filesystem/DB en entorno aislado.
- **Recomendación:** pocas excepciones de dominio y renderer central; log interno con correlation ID, mensaje externo genérico.
- **Prioridad:** P1/P2.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-009`.

### AUD-047 — Documentos citan pruebas no reproducibles

- **Área:** Documentación/pruebas
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** contrato Clases afirma 19 assertions (`Wings-Contrato-Clases-Asistencias-V1.md:133`) y Catálogos 7 (`Wings-Contrato-Catalogos-Contables-V1.md:61`), pero no existen en `tests/`. `CLAUDE.md:102-106` dice “tests base pasan” sin aclarar que son dos stubs.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** “contrato cerrado” se toma como regresión automatizada cuando no lo es.
- **Escenario:** inventario de tests y ejecución actual.
- **Recomendación:** versionar tests o etiquetar verificación manual puntual con fecha/entorno; documentar “2 stubs pasan, negocio falla en setup”.
- **Prioridad:** P1.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-027`, `AUD-028`.

### AUD-048 — Filtros crecientes carecen de índices/rangos adecuados

- **Área:** Rendimiento/DB.
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO** para esquema; impacto exacto no verificable.
- **Descripción:** las consultas de mayor crecimiento usan funciones sobre fecha o columnas sin índices alineados.
- **Evidencia:** `app/Http/Controllers/CashflowWebController.php:23-47`; `database/migrations/2026_02_01_100006_create_cashflow_movimientos_table.php:14-25`; `database/migrations/2026_01_12_091912_create_pagos_table.php:14-27`; `database/migrations/2026_02_01_100004_create_cajas_operativas_table.php:14-24`; `database/migrations/2026_02_01_100005_create_movimientos_operativos_table.php:14-23`; `database/migrations/2026_02_12_000001_create_alumnos_revision_cobranza_table.php:11-20`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** scans/sorts crecientes con años de operación.
- **Escenario:** requiere `EXPLAIN ANALYZE` sobre datos representativos.
- **Recomendación:** rangos `[inicio,fin)`, agregación condicional y solo índices confirmados por planes reales.
- **Prioridad:** P1.
- **Esfuerzo:** medio.
- **Dependencias:** volumen real.

### AUD-049 — Logs, filesystem y cola carecen de operación observable

- **Área:** Infraestructura.
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO** para configuración.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** env usa stack→single/debug (`.env.example:18-21`; `logging.php:55-72`); mailer log (`.env.example:57-63`; `mail.php:73-76`); discos `throw/report=false` (`filesystems.php:33-60`); queue database sin worker/monitor, aunque hoy no hay jobs.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** logs crecen, correos pueden quedar allí, fallos de archivos se silencian y futuros jobs no corren.
- **Escenario:** revisión config/log; no se reprodujeron contenidos sensibles.
- **Recomendación:** daily/retención/ACL/sanitización, reportar fallos críticos y desplegar worker solo al introducir jobs.
- **Prioridad:** P1/P2.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** `AUD-045`.

### AUD-050 — Setup y contratos anuncian API/controles que runtime no ofrece

- **Área:** Documentación/despliegue
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** `CLAUDE.md:24-30`, `ESTADO-ACTUAL.md:41`, contratos y `deploy-wings.bat:471-479` anuncian API; runtime no carga `routes/api.php`. Deploy limpia caches pero no optimiza y llama `route:list` “Laravel responde” (`deploy-wings.bat:413-455`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** integraciones 404 y falso positivo de deploy.
- **Escenario:** `route:list --path=api` vacío; route:list no prueba HTTP/DB.
- **Recomendación:** banner API congelada, smoke real `/up`/login/protegida y registrar commit/config hash; alinear cache con perfil productivo.
- **Prioridad:** P1/P2.
- **Esfuerzo:** bajo.
- **Dependencias:** `AUD-026`, `AUD-035`, `AUD-037`.

### AUD-051 — Invariantes en eventos de modelo se omiten en updates masivos

- **Área:** Arquitectura de dominio
- **Severidad:** **MEDIA**.
- **Estado:** **PROBABLE**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** hooks cierran planes/validan precio/inmutabilidad (`AlumnoPlan.php:29-42`; `GrupoPlan.php:28-52`; `Pago.php:44-53`; `Liquidacion.php:62-85`), pero `GrupoPlan::where()->update()` (`GrupoWebController.php:146-149`) no dispara eventos por modelo.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** escrituras masivas presentes/futuras saltean garantías implícitas.
- **Escenario:** update masivo inválido en base aislada.
- **Recomendación:** operaciones de dominio explícitas y constraints DB; no declarar garantizada una regla solo por evento.
- **Prioridad:** P2.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-025`.

### AUD-052 — Motivo de rechazo y ER/manuales divergen del runtime

- **Área:** Consistencia documental
- **Severidad:** **MEDIA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** contrato Caja exige motivo (`Wings-Contrato-Caja-Cashflow-V4.md:16,41,82,90`), manual dice opcional (`FLUJO-USUARIO.md:212`) y web valida nullable (`CajaWebController.php:495-500`). ER de grupos conserva `nombre` eliminado; ER cuotas niega `pago_id` luego agregado; ER caja usa `descripcion` frente a `observaciones`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** QA y desarrolladores usan reglas/esquemas incompatibles; rechazo sin explicación viola contrato.
- **Escenario:** POST web sin motivo; diseño de consulta según ER.
- **Recomendación:** tratar migraciones/runtime como AS-IS, contratos como TO-BE explícito; corregir manual/ER y exigir motivo si el contrato sigue vigente.
- **Prioridad:** P1.
- **Esfuerzo:** bajo.
- **Dependencias:** gobernanza `AUD-037`.

### AUD-053 — Faltan cabeceras defensivas y endurecimiento de sesión

- **Área:** Hardening web
- **Severidad:** **BAJA**.
- **Estado:** **RECOMENDACIÓN**.
- **Descripción:** el servidor depende de defaults del navegador y de una plantilla HTTP, sin una capa documentada de headers defensivos.
- **Evidencia:** `public/.htaccess:1-25` solo rewrite; no CSP, frame-ancestors/X-Frame-Options, nosniff, HSTS o Referrer-Policy. Secure depende de env y la plantilla usa HTTP.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** amplifica XSS/clickjacking/MIME sniffing; HSTS no corresponde hasta TLS completo.
- **Escenario:** inspección de headers en entorno aislado.
- **Recomendación:** cabeceras en Apache/middleware, CSP primero report-only, Secure/HSTS tras TLS.
- **Prioridad:** P2.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** `AUD-003`, `AUD-009`.

### AUD-054 — Sin umbral de cobertura, CI ni análisis estático

- **Área:** Calidad
- **Severidad:** **BAJA**.
- **Estado:** **RECOMENDACIÓN**.
- **Descripción:** el repositorio no tiene una puerta automatizada que impida integrar una suite rota o reduzca errores estáticos.
- **Evidencia:** `phpunit.xml:7-19` sin umbral; no hay workflow CI visible; Composer tiene PHPUnit/Pint, no PHPStan/Larastan. Tests scaffold aportan señal mínima.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** suite puede degradarse silenciosamente y errores de tipos/contratos llegan tarde.
- **Escenario:** inspección de configuración.
- **Recomendación:** CI con migrate fresh MariaDB, suite y tendencia de cobertura sobre servicios críticos; análisis estático gradual después de corregir base.
- **Prioridad:** P2.
- **Esfuerzo:** medio.
- **Dependencias:** `AUD-027`, `AUD-028`.

### AUD-055 — Autocomplete/búsqueda usa comodín inicial

- **Área:** Rendimiento
- **Severidad:** **BAJA**.
- **Estado:** **CONFIRMADO**.
- **Descripción:** el defecto se manifiesta en la divergencia o ausencia de control indicada por el título y produce la consecuencia documentada abajo.
- **Evidencia:** listado y autocomplete usan `LIKE "%texto%"` sobre nombre/apellido/DNI (`AlumnoWebController.php:35-41,87-95`).
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** scans repetidos al escribir; B-tree común no resuelve comodín inicial.
- **Escenario:** decenas de miles de alumnos.
- **Recomendación:** límite/debounce; prefijo para DNI y búsqueda normalizada/full-text solo si volumen lo justifica.
- **Prioridad:** P2.
- **Esfuerzo:** medio.
- **Dependencias:** medición real.

### AUD-056 — User↔Profesor no tiene unicidad DB

- **Área:** Identidad
- **Severidad:** **BAJA**.
- **Estado:** **PROBABLE**.
- **Descripción:** la cardinalidad uno-a-uno declarada solo está defendida por validación previa de aplicación.
- **Evidencia:** `app/Http/Controllers/UsuarioWebController.php:33-67,99-137`; `database/migrations/2026_05_27_154622_add_profesor_id_to_users_table.php:9-17`; `app/Models/Profesor.php:105-108`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** concurrencia/import puede vincular varias cuentas o dejar profesor sin ficha.
- **Escenario:** dos altas simultáneas con mismo profesor; no ejecutado.
- **Recomendación:** unique nullable e invariante rol-profesor; definir eliminación de ficha.
- **Prioridad:** P2.
- **Esfuerzo:** bajo.
- **Dependencias:** ciclo de identidad.

### AUD-057 — Código/API y artefactos no alcanzables requieren decisión

- **Área:** Código muerto/gobernanza.
- **Severidad:** **INFORMATIVA**.
- **Estado:** **CONFIRMADO** para código no registrado/artefactos no referenciados; consumidores externos **NO VERIFICABLES**.
- **Descripción:** el árbol operativo conserva una API congelada y copias no cargadas cuya finalidad actual no está documentada.
- **Evidencia:** `bootstrap/app.php:14-21`; `routes/api.php:1-263`; `app/Http/Controllers.zip`; directorio `VISTAS PARA COPIAR/`; `composer.json:25-31` limita PSR-4 a `App\\` bajo `app/`.
- **Archivo y líneas:** ver las rutas y rangos concretos citados en la evidencia; cuando la evidencia es un comando, ver Anexo 20.1.
- **Consecuencia:** superficie de mantenimiento/confusión; retirar sin telemetría podría afectar un consumidor externo no visible.
- **Escenario:** búsquedas de referencias y route:list.
- **Recomendación:** inventariar consumidores/logs y etiquetar congelado; archivar fuera del árbol operativo solo en tarea autorizada posterior.
- **Prioridad:** P3.
- **Esfuerzo:** bajo/medio.
- **Dependencias:** decisión de producto.

---

## 15. Riesgos agrupados por severidad

| Severidad | Cantidad | IDs |
|---|---:|---|
| **CRÍTICA** | 6 | AUD-001 a AUD-006 |
| **ALTA** | 31 | AUD-007 a AUD-037 |
| **MEDIA** | 15 | AUD-038 a AUD-052 |
| **BAJA** | 4 | AUD-053 a AUD-056 |
| **INFORMATIVA** | 1 | AUD-057 |

Por estado: **48 CONFIRMADO**, **7 PROBABLE** (`AUD-014`, `AUD-016`, `AUD-018`, `AUD-026`, `AUD-041`, `AUD-051`, `AUD-056`) y **2 RECOMENDACIÓN** (`AUD-053`, `AUD-054`). Algunos confirmados contienen una parte de impacto no verificable; se indica dentro de cada ficha.

## 16. Plan de corrección priorizado

### Fase 0 — Contención inmediata (0–3 días)

1. Bloquear `PROFESOR` de caja/alumnos/movimientos y cuentas inactivas globalmente (`AUD-001`, `AUD-002`).
2. Corregir XSS con `textContent` (`AUD-003`).
3. Retirar cuenta/datos demo del deploy y rotar/eliminar instalaciones afectadas (`AUD-004`).
4. Bloquear sobrepago y cancelados hacia cashflow (`AUD-005`, `AUD-006`).
5. Restringir exposición: no usar deploy actual en red; debug off/TLS/DB dedicado (`AUD-009`).
6. Preservar dump como evidencia, determinar origen y coordinar remediación sin reproducirlo (`AUD-008`).

### Fase 1 — Integridad financiera y operacional (primera semana)

- Scope del POST de cancelación y edición de subrubros (`AUD-010`, `AUD-022`).
- Validación/transacción de asistencias y clase (`AUD-011`, `AUD-019`).
- Unificar transacción de plan+pago y aplicar locks/FIFO (`AUD-012` a `AUD-014`).
- Definir referencia cashflow, unique/upsert, locks de caja/liquidación (`AUD-015`, `AUD-016`, `AUD-018`).
- Modelar anulación de pivotes y ajuste/condonación (`AUD-017`, `AUD-021`).
- Instalar scheduler y decidir período (`AUD-023`, `AUD-024`).

### Fase 2 — Red de seguridad y datos (semanas 2–3)

- MariaDB de test y suite P0 (`AUD-027`, `AUD-028`).
- Cambiar cascadas según retención y proteger seeders (`AUD-025`, `AUD-036`).
- Resolver comisión histórica y recálculo de liquidación (`AUD-020`, `AUD-041`).
- Auditar/actualizar dependencias con regresión (`AUD-007`).
- Backup cifrado probado, ACL y deploy recuperable (`AUD-033` a `AUD-035`).

### Fase 3 — Escala, arquitectura y documentación (mes 1–2)

- Bulk/paginación SQL para asistencias, generador, cobranza, historial y comisión (`AUD-029` a `AUD-032`, `AUD-048`).
- Unificar casos de uso y cálculos duplicados (`AUD-042` a `AUD-044`).
- Jobs/observabilidad/errores uniformes (`AUD-045`, `AUD-046`, `AUD-049`).
- Actualizar fuente de verdad, contratos, ER y manuales (`AUD-037`, `AUD-039`, `AUD-047`, `AUD-050`, `AUD-052`).
- Mantener API apagada hasta completar `AUD-026`.

## 17. Pruebas recomendadas

### P0

1. Matriz HTTP `ADMIN/OPERATIVO/PROFESOR/inactivo × método × ruta`, incluyendo URL directa, IDs ajenos y ausencia de mutación tras 403.
2. Invariante exacta: `pago.monto_final = Σ pivote válido = movimiento/cashflow = incremento de deuda`.
3. Sobrepago, FIFO con deuda vieja omitida y dos pagos concurrentes sobre la misma deuda.
4. Cancelación propia/ajena, repetida y concurrente; pivote y cashflow conciliados.
5. Caja `abrir→mover→cerrar→rechazar→corregir→validar`, caja mixta y doble validación.
6. Cambio de plan seguido de fallo: ninguna escritura persiste.
7. Asistencia con alumno ajeno, inexistente, duplicado y fallo intermedio: rollback total.
8. Edición de clase solapada/retroactiva/liquidada.
9. Doble pago de liquidación y dos liquidaciones consumiendo mismo saldo.
10. Instalación aislada: no crea credencial predecible ni cashflow demo.

### P1

- Scheduler día 1 con período explícito, pago anulado, idempotencia y reejecución.
- Comisión tras cambio/inactivación y definición por período de cuota/cobro.
- Ajuste debajo de pagado y condonación parcial.
- Paridad preview/generación de liquidación.
- Cobranza con tabla de verdad y settings editables.
- Recibo fallido después de commit y job/reintento.
- Smoke render de todas las pantallas por rol.
- `migrate:fresh` y rollback en copia MariaDB.

### P2

- Planes de ejecución y carga representativa para queries señaladas.
- Headers/cookies, logs y fallo de filesystem/cola.
- Restore drill con checksum y medición RPO/RTO.
- Contrato API completo antes de cualquier reactivación.

## 18. Comparación con v02

El detalle completo está en `COMPARATIVA-v02-v03.md`. Hechos clave:

- entre el commit que introdujo v02 (`ccc6c74`) y el corte v03 no hubo cambios de código; solo documentación de evaluación, por lo que **ningún hallazgo estático de v02 puede considerarse corregido por cambios del sistema**;
- v03 confirmó los núcleos de permisos, cuentas inactivas, dump, sobrepago, cancelados, locks, cascadas, tests, rendimiento y scheduler;
- v03 detectó omisiones relevantes de v02: XSS almacenado, cuenta predecible/seed financiero del deploy, asistencia ajena/parcial, cambio de plan no atómico, doble pago de liquidación, configuración editable inerte y nuevas contradicciones documentales;
- v02 declaró “sin XSS”; v03 demuestra lo contrario con cadena entrada→JSON→`innerHTML`;
- v02 afirmó `/cobranza` 500 y “el comando no crea deuda” con evidencia dinámica previa. v03 no ejecutó requests/DB y no reproduce esas afirmaciones como actuales: quedan **NO REVALIDADAS**, no “corregidas”. Sí confirma fallas estructurales de cobranza/scheduler.

## 19. Conclusión

Gestión Wings tiene suficiente funcionalidad implementada para reconstruir sus procesos, pero sus garantías actuales dependen demasiado de la UI, del orden normal de clicks y de que no haya concurrencia ni fallos intermedios. Eso no es suficiente para permisos, dinero o datos personales.

La corrección debe empezar por contención y invariantes, no por refactor cosmético: autorización backend, revocación, XSS, deploy limpio, conservación contable, locks/unique y una base de pruebas MariaDB. Recién con esa red resulta seguro optimizar y consolidar arquitectura.

## 20. Anexos

### 20.1 Comandos ejecutados

Todos fueron de solo lectura respecto del sistema y los datos:

```text
git status --short / branch / rev-parse / log / diff --name-status
rg --files / rg de rutas, patrones y hallazgos
php artisan route:list --except-vendor / --json
php artisan --version
php artisan schedule:list
php artisan test
php -l (223 archivos bajo áreas de aplicación; 0 errores)
composer validate --no-check-publish
composer audit --locked
npm.cmd audit --json
Get-FileHash sobre documentación anterior
icacls y consulta de Task Scheduler (solo lectura, por agente de infraestructura)
```

Resultados resumidos:

- Laravel 12.46.0; PHP 8.2.12.
- 124 rutas web; API no registrada.
- Scheduler Laravel definido: día 1 06:00; disparador OS no observado.
- Tests: 12 fallidos, 2 pasados, 3 assertions.
- Sintaxis PHP: 223 archivos verificados, 0 fallos.
- Composer manifest válido.
- Composer audit: 38 avisos/15 paquetes.
- npm audit: 10 vulnerabilidades.

### 20.2 Archivos/áreas principales revisados

`app/Services/` · `app/Http/Controllers/` · `app/Models/` · `app/Http/Middleware/` · `app/Http/Requests/` · `app/Providers/` · `app/Console/Commands/` · `routes/web.php` · `routes/api.php` · `routes/console.php` · `bootstrap/app.php` · `database/migrations/` · `database/seeders/` · `database/dump.sql` (sin reproducir datos) · `resources/views/` · `tests/` · `config/` · `.env.example` · `deploy-wings.bat` · `scripts/` · `docs/` · locks Composer/npm.

### 20.3 Áreas no verificadas

| Área | Motivo / requisito para verificar |
|---|---|
| Usuarios/roles/datos reales | Requiere consulta autorizada de DB; no realizada |
| Concurrencia real | MariaDB aislada, múltiples conexiones y escrituras controladas |
| Saldos/huérfanos/duplicados actuales | Consultas de conciliación sobre copia anonimizada |
| Exposición externa | Firewall, Apache/MySQL global, TLS y red fuera del repo |
| Scheduler externo alternativo | Acceso a toda infraestructura/servicios del host |
| Restore efectivo | Backup aislado y drill de recuperación |
| Reachability de cada CVE | Mapeo advisory→uso y pruebas de regresión |
| Rendimiento cuantitativo | Dataset representativo, `EXPLAIN ANALYZE` y carga |
| Consumidores externos de API | Logs/telemetría e inventario de integraciones |

### 20.4 Evidencia de versionado

Hashes SHA-256 previos registrados antes de escribir v03:

```text
ANALISIS-INTEGRAL-v02.md 66473A4B6E9B6E2E0B0BD3407E1E2E1BF14E960CBFC2D490FF06768E719AFE7E
ANALISIS-INTEGRAL.md     4483ECE7551A788823822E835482B9E9BD4A0A628FEF3ED8657BCE4CBEF34C9A
index-v02.html          17D7E4413BB4B26CA6A50A7F5EAB420F63A287747EB3A59D64800983DF655F35
index.html              44077D0A1246EF2D65C05CDFE3D59C4D5C4B68A451FBE01E9C47436A0A001BCF
```

---

*Fin del informe v03.*
