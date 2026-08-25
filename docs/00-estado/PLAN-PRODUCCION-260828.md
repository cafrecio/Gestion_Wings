# Plan de Producción — Wings v1.0

> **Objetivo:** sistema en línea, seguro y contablemente confiable el **viernes 28/08/2026**.
> **Servidor:** AlmaLinux 9 · **Datos:** base limpia + import selectivo · **Arranque:** producción real, todos los roles.
> **Reajustado:** martes 25/08/2026, tras verificar uno por uno los 57 hallazgos de la auditoría v03.
> **Versión visual navegable:** artifact "Wings Go-Live".

> ### ANTES DE EJECUTAR CUALQUIER TAREA DE ESTE PLAN, LEER `AGENTS.md`
> Contiene las reglas que no se negocian, en especial que **el diseño no se toca**.

---

## 0. Qué cambió en este reajuste

El plan anterior listaba 11 bloqueantes. Eran los que estaban verificados; los otros
~46 hallazgos de la auditoría v03 se habían dado por buenos sin comprobar. Se
verificaron todos contra el código actual. Resultado:

**Cuatro hallazgos que se creían abiertos ya estaban cerrados** por el ciclo de Codex:

| # | Hallazgo | Evidencia de cierre |
|---|---|---|
| AUD-014 | Pagos concurrentes pierden imputación | `lockForUpdate` en `PagoCuotaService.php:351,365` |
| AUD-016 | Doble validación duplica cashflow | `validarCaja` envuelve todo en transacción con lock sobre la caja |
| AUD-017 | Cancelar pago conserva imputaciones | `PagoDeudaCuota::...->delete()` y revierte `monto_pagado` |
| AUD-024 | Pago ANULADO cuenta como actividad | Filtra por `ESTADO_COMPLETADO` |

**Un hallazgo nuevo entra como bloqueante: AUD-012.**

**Cuatro hallazgos quedan abiertos pero NO son bloqueantes de go-live**, por un motivo
concreto y verificado en cada caso. Están en la sección 4.

---

## 1. Bloqueantes

| # | Hallazgo | Evidencia |
|---|---|---|
| **B1** | `DatabaseSeeder` crea `test@example.com` con password `password` | `DatabaseSeeder.php:20-36`, `UserFactory.php:29` |
| **B2** | `database/dump.sql` versionado con 27 tablas reales | `git ls-files database/dump.sql` |
| ~~**B3**~~ | ~~Hook `pre-commit` reexporta el dump~~ | **CERRADO 25/08** — renombrado a `.disabled` |
| **B4** | `.env` en local, `APP_DEBUG=true`, HTTP, DB con root sin password | `.env` |
| **B5** | Sin headers de seguridad | No hay middleware |
| **B6** | **FIFO evadible** | `PagoCuotaService.php:442-444` |
| **B7** | Asistencias sin transacción ni validación de pertenencia | `ClaseWebController.php:374-408` |
| **B8** | 44 advisories en 15 paquetes PHP | `composer audit --locked` |
| **B9** | La suite corre en SQLite, no en MariaDB | `phpunit.xml` |
| **B10** | Sin CI, sin backups probados, sin scheduler en servidor | — |
| **B11** | No existe procedimiento de deploy a servidor | `deploy-wings.bat` es un instalador de XAMPP |
| **B12** | **Cambio de plan no atómico con el cobro** (AUD-012) | `CajaWebController::pagar()` — plan en línea 631, pago en 674, sin transacción |

### Por qué B12 entra ahora y no puede esperar

En `pagar()`, el cambio de plan se graba **antes** de llamar al servicio de pago y
fuera de cualquier transacción común. Si el pago falla, el alumno queda con el plan
nuevo y sin cobro registrado.

**La tarea 1.9 empeora este bug.** Arreglar el FIFO hace que muchos más pagos sean
rechazados, y cada rechazo deja un cambio de plan huérfano. **1.9 y 1.9b van juntas
o no van ninguna de las dos.**

---

## 2. Estado verificado del resto

| Ítem | Evidencia |
|---|---|
| PROFESOR no alcanza caja, alumnos ni dinero | `reject.profesor.web` — `routes/web.php:36,75,94,128` |
| Usuario desactivado no entra y pierde la sesión | `ensure.active.web` — `routes/web.php:35` |
| XSS almacenado en autocomplete | `alumnos/index.blade.php:263-271` usa `textContent` |
| API REST sin control de rol | Apagada en `bootstrap/app.php:14` |
| **Inyección SQL** | Sin vectores. Los 13 usos de `DB::raw` / `whereRaw` son parametrizados |
| Sobrepago sobre el saldo | Rechazado |
| Movimientos cancelados hacia cashflow | Excluidos |
| IDOR al cancelar movimiento | Scopeado por caja |
| npm de producción | 0 vulnerabilidades |
| **Matriz de permisos** | 268 pruebas GET × 4 roles: 0 errores 500, 0 accesos indebidos |
| **Integridad estructural** | 127 rutas · 54 vistas · 0 includes rotos · 0 errores de sintaxis · 33 tests |
| Funciones sueltas de Blade | **CERRADO 25/08** — guardadas con `function_exists` |

---

## 3. Por qué este orden

1. **El código antes que el servidor.** Un servidor perfecto con código inseguro sigue siendo inseguro. Y el blindaje no necesita SSH.
2. **La fuga de datos, antes que todo.** Ya cerrado el hook (1.1). Falta sacar el dump, que depende del seeder.
3. **El seeder antes de sacar el dump.** El dump es hoy el único mecanismo para sincronizar la BD entre máquinas.
4. **FIFO y atomicidad del plan, juntos.** Arreglar uno sin el otro empeora el sistema.
5. **Dependencias el miércoles.** Actualizar librerías rompe cosas; con la suite verde como referencia se sabe qué rompió qué.
6. **Los tests después del servidor.** Para probar de verdad hace falta MariaDB.
7. **La prueba funcional después del primer deploy.** TLS, permisos, SELinux y timezone solo aparecen en el servidor.
8. **El jueves cierra, el viernes ejecuta.** Si el jueves no cerró, el go-live pasa al lunes 31.

---

## D1 · Martes 25/08 — Blindaje de código

Todo local, no hace falta el servidor. La columna **Ejecuta** define quién toma cada
tarea: buena parte corre en paralelo, que es lo que hace que el día entre.

| # | Tarea | Ejecuta | Cierra | Est. |
|---|---|---|---|---|
| ~~1.1~~ | ~~Desactivar el hook `pre-commit`~~ | — | B3 | **HECHO** |
| 1.2 | Sacar `dump.sql` del repo y rotar tokens | Codex | B2 | 30 min |
| 1.3 | `CatalogosSeeder` idempotente | Codex | B2 | 1.5 h |
| 1.4 | Sanear seeders y blindarlos contra producción | Codex | B1 | 40 min |
| 1.5 | Comando `wings:crear-admin` | Codex | B1 | 30 min |
| 1.6 | `SecurityHeaders`: los 5 headers sin riesgo | Codex | B5 | 45 min |
| **1.6b** | **CSP en modo reporte** | **Supervisada** | B5 | 1 h |
| 1.7 | `.env.production.example` | Codex | B4 | 25 min |
| 1.8 | Comando `wings:preflight` | Codex | B4 | 1 h |
| **1.9** | **FIFO fuerte real** | **Supervisada** | B6 | 1.5 h |
| **1.9b** | **Atomicidad del cambio de plan (AUD-012)** | **Supervisada** | B12 | 2 h |
| 1.10 | Asistencias transaccionales y validadas | Codex | B7 | 1 h |
| 1.11 | Endurecer el login a `throttle:5,1` | Codex | — | 20 min |
| ~~1.11b~~ | ~~Funciones de Blade con `function_exists`~~ | — | — | **HECHO** |
| 1.12 | Limpiar `formas_pago` de la base local | Codex | — | 10 min |
| 1.13 | Cierre: suite completa y `/security-review` | Supervisada | — | 30 min |

**Carga:** ~7 h de Codex en paralelo con ~5 h supervisadas. Las tres supervisadas
(1.6b, 1.9, 1.9b) son las que pueden romper el diseño o la contabilidad.

### Detalle de las tareas críticas

**1.2 — Sacar el dump del repo.**

    git rm --cached database/dump.sql
    echo "database/dump.sql" >> .gitignore

Después invalidar lo expuesto: vaciar `personal_access_tokens` y `sessions`. La
historia de Git sigue conteniendo los datos; limpiarla queda diferido.
*Verificación:* `git ls-files database/dump.sql` no devuelve nada y el archivo sigue en disco.
*Depende de:* 1.3.

**1.3 — CatalogosSeeder.** Rubros, subrubros, tipos de caja, deportes, niveles y
reglas de primer pago. Idempotente con `updateOrCreate` sobre clave natural. Sin un
solo dato personal.
*Verificación:* sobre base vacía, `migrate --seed` la deja utilizable; correrlo dos veces no cambia ninguna fila.

**1.4 — Sanear seeders.**

    if (app()->environment('production')) {
        throw new \RuntimeException('Este seeder no corre en produccion.');
    }

*Verificación:* con `APP_ENV=production`, `db:seed` no crea ningún usuario.

**1.6 — Headers sin riesgo.** Se aplican directo:

    X-Frame-Options            DENY
    X-Content-Type-Options     nosniff
    Referrer-Policy            same-origin
    Permissions-Policy         camera=(), microphone=(), geolocation=()
    Strict-Transport-Security  max-age=31536000    (solo bajo HTTPS)

**1.6b — CSP. SUPERVISADA.** Las vistas usan `style="..."` inline en todos lados; una
CSP estricta de manual destruye visualmente la aplicación.

1. Empezar con `Content-Security-Policy-Report-Only`.
2. Recorrer la app con la consola abierta y juntar las violaciones reales.
3. Recién ahí endurecer, con las excepciones verificadas.
4. Revisar visualmente alumnos, caja, cobrar, clases y liquidaciones.

**Nunca activar la CSP bloqueante en un solo paso.** Ver `AGENTS.md` §2.

**1.9 — FIFO fuerte real. SUPERVISADA.** El problema es el retorno anticipado:

    if (count($items) <= 1) {
        return;      // esta es la puerta
    }

Antes de imputar, buscar todas las `DeudaCuota` del alumno con período anterior al
menor ítem enviado y saldo mayor a cero. Si existe alguna, rechazar nombrando el
período faltante. La validación va en el servicio, no en la vista.
*Verificación:* alumno con febrero impago, cobro de marzo como ítem único → debe rechazar.
*No se hace sin 1.9b.*

**1.9b — Atomicidad del cambio de plan. SUPERVISADA.** En `CajaWebController::pagar()`,
envolver el bloque de cambio de plan y la llamada al servicio de pago en una sola
`DB::transaction`. Hoy el plan se graba en la línea 631 y el pago se ejecuta en la 674
sin transacción común.
*Verificación:* forzar un rechazo de pago después de seleccionar cambio de plan; no debe persistir ni el plan nuevo ni el recálculo de deuda.
*Depende de:* va junto con 1.9.

**1.10 — Asistencias.** Envolver el bucle de guardado en `DB::transaction` y agregar
un FormRequest que valide que cada `alumno_id` pertenece al grupo de esa clase.
*Verificación:* alumno de otro grupo, rechaza sin escribir; fallo intermedio, no queda nada guardado.

**1.12 — `formas_pago`.** La migración la eliminó pero sobrevivió en la base local.

    DROP TABLE IF EXISTS formas_pago;

---

## D2 · Miércoles 26/08 — Servidor y dependencias

| # | Tarea | Ejecuta | Cierra | Est. |
|---|---|---|---|---|
| 2.1 | Provisión base de AlmaLinux 9 | Codex | B11 | 1 h |
| 2.2 | Hardening del host | Codex | B10 | 1 h |
| 2.3 | MariaDB con usuarios de privilegio mínimo | Codex | B4 | 40 min |
| 2.4 | Layout de directorios y verificación de exposición | Codex | B11 | 45 min |
| 2.5 | TLS con Let's Encrypt y HSTS | Codex | B4 | 30 min |
| 2.6 | `deploy.sh` atómico con rollback | Codex | B11 | 1.5 h |
| 2.7 | Scheduler como systemd timer | Codex | B10 | 25 min |
| 2.8 | Backups cifrados con destino externo | Codex | B10 | 1 h |
| 2.9 | Actualizar dependencias, 44 advisories | Supervisada | B8 | 2 h |

**2.1 — Base.** AlmaLinux 9 trae PHP 8.0 y `composer.json` exige `^8.2`: el repo Remi
es obligatorio, no opcional.

    dnf update -y
    dnf install -y epel-release
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
    dnf module reset php -y && dnf module enable php:remi-8.3 -y
    dnf install -y php php-fpm php-mysqlnd php-mbstring php-xml php-bcmath \
                   php-gd php-zip php-intl php-opcache nginx mariadb-server \
                   git unzip policycoreutils-python-utils fail2ban

**2.2 — Hardening.** Firewalld solo 22/80/443, 3306 cerrado. SSH sin root ni password.
Fail2ban sobre `sshd` y nginx. **SELinux en enforcing**, no desactivarlo:

    semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/wings/shared/storage(/.*)?"
    semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/wings/current/bootstrap/cache(/.*)?"
    restorecon -Rv /var/www/wings
    setsebool -P httpd_can_network_connect_db on

**2.3 — MariaDB.** `mysql_secure_installation`, `bind-address = 127.0.0.1`,
`time_zone = '-03:00'`. La aplicación nunca entra como root:

    CREATE DATABASE wings CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

    CREATE USER 'wings_app'@'localhost' IDENTIFIED BY '<fuerte>';
    GRANT SELECT, INSERT, UPDATE, DELETE ON wings.* TO 'wings_app'@'localhost';

    -- solo durante el deploy, para migrate
    CREATE USER 'wings_migrate'@'localhost' IDENTIFIED BY '<fuerte>';
    GRANT ALL ON wings.* TO 'wings_migrate'@'localhost';

**2.4 — Layout y exposición.**

    /var/www/wings/
    ├── releases/20260826-1430/
    ├── shared/.env                 600, root:nginx
    ├── shared/storage/
    └── current -> releases/...     symlink atomico

*Verificación obligatoria:* `/.env`, `/database/dump.sql`, `/storage/logs/laravel.log`
y `/.git/config` deben devolver **404**.

**2.6 — deploy.sh.**

    git fetch && checkout <tag>
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    ln -s shared/.env  y  shared/storage
    php artisan wings:preflight        aborta si algo esta mal
    php artisan migrate --force
    php artisan config:cache route:cache view:cache
    ln -sfn releases/<nuevo> current   el cambio atomico
    systemctl reload php-fpm nginx

Rollback: repuntar el symlink. Retener 5 releases.

**2.7 — Scheduler.** Timer de systemd cada minuto ejecutando `schedule:run`. Confirmar
timezone `America/Argentina/Buenos_Aires`.
*Si no se hace:* el 1 de septiembre no se genera la cuota del mes.

**2.8 — Backups.** `mysqldump` diario 03:00, cifrado con `age` o `gpg`, fuera del
webroot y **copiado a destino externo**. Retención 14 días. El restore se prueba el
jueves (3.8).

**2.9 — Dependencias. SUPERVISADA.** En rama aparte:

    git checkout -b chore/deps && composer update && php artisan test && composer audit --locked

Los advisories que queden vivos se documentan con el motivo.

---

## D3 · Jueves 27/08 — Datos, pruebas y verificación

El día más cargado. Es el que decide si el viernes se sube.

| # | Tarea | Ejecuta | Est. |
|---|---|---|---|
| 3.1 | Mover la suite de SQLite a MariaDB | Codex | 1 h |
| 3.2 | Escribir los tests P0 | Supervisada | 3 h |
| 3.3 | CI en GitHub Actions | Codex | 40 min |
| 3.4 | Script de import selectivo | Supervisada | 1.5 h |
| 3.5 | Primer deploy real al servidor | Codex | 1 h |
| 3.6 | Smoke de todas las rutas por rol | Codex | 1 h |
| 3.7 | Prueba de concurrencia real sobre MariaDB | Supervisada | 1 h |
| 3.8 | Restore drill del backup | Codex | 45 min |
| 3.9 | Verificar el scheduler en vivo | Codex | 20 min |
| 3.10 | Recorrido funcional manual sobre el servidor | **Carlos** | 2 h |
| 3.11 | Revisión de código completa | Supervisada | 40 min |

**3.2 — Tests P0.** Los que sostienen la plata:

- **Matriz de roles** — cada rol × cada ruta × cada método, con URL directa e IDs
  ajenos. Verificar que tras un 403 **no quedó ninguna escritura**.
- **Invariante contable** — `pago = suma de imputaciones = movimiento de caja =
  incremento de deuda`. El test más importante del sistema.
- **FIFO** — deuda vieja impaga más cobro de la nueva como ítem único.
- **Cambio de plan con fallo posterior** — no debe persistir nada (cubre B12).
- **Caja** — abrir, mover, cerrar, rechazar, corregir, validar. Caja mixta y doble validación.
- **Cancelación** — propia, ajena, repetida y concurrente.
- **Asistencias** — alumno ajeno, inexistente, duplicado y fallo intermedio.

**3.4 — Import selectivo.** Traer catálogos, alumnos reales y planes vigentes. **No
traer** pagos, cajas, movimientos, asistencias, liquidaciones ni deudas.
*Verificación:* después del import, todo saldo de caja y de cashflow da **cero**.

**3.6 — Smoke.** Ya existe base funcionando: el script de 268 pruebas GET del 25/08.
Extenderlo a métodos mutantes con CSRF.

**3.7 — Concurrencia real.** Los `lockForUpdate` existen pero nunca se probaron con
dos conexiones simultáneas.

**3.8 — Restore drill.** Borrar la base del servidor y restaurarla del backup cifrado.
Cronometrar: ese número es el RTO real.

**3.10 — Recorrido funcional.** Sobre el servidor con TLS, no sobre XAMPP. Los dos
circuitos completos. Registrar sin corregir en el momento.

---

## D4 · Viernes 28/08 — Gate y go-live

| # | Paso |
|---|---|
| 4.1 | Corregir lo que falló el jueves — única ventana del día |
| 4.2 | Tag `v1.0.0` y congelamiento |
| 4.3 | `wings:preflight` en verde sobre el servidor |
| 4.4 | Import de los datos reales |
| 4.5 | Crear los usuarios reales y borrar los de prueba |
| 4.6 | Backup manual antes de abrir |
| 4.7 | Activar el monitoreo de errores |
| 4.8 | Firmar el gate de 17 condiciones |
| 4.9 | Apertura de la primera caja real, acompañada |

---

## 4. Riesgos que salen a producción, y por qué se pueden asumir

Cuatro hallazgos funcionales quedan abiertos el viernes. **Ninguno se difiere por
falta de tiempo: se difiere porque se verificó que no es alcanzable o no se usa en
las primeras semanas.**

| # | Hallazgo | Por qué NO bloquea | Fecha límite real |
|---|---|---|---|
| **AUD-021** | Ajustar o condonar deuda deja plata sin registrar | **No es alcanzable.** Solo existe en `PagoCuotaController` (API), y la API está apagada. No hay ruta web ni botón en ninguna vista | Antes de reactivar la API o de construir la UI |
| **AUD-020** | La comisión usa alumno activo y deporte actual, no los del período | Solo afecta al **recalcular** una liquidación. Producción arranca con cero liquidaciones y la primera es a fin de septiembre | **Antes del 25/09** |
| **AUD-018** | Pago concurrente de liquidación puede duplicar el egreso | Mismo módulo, mismo calendario. Además es admin-only y de concurrencia mínima | **Antes del 25/09** |
| **AUD-019** | Editar clase no valida solapamiento ni exige motivo | Admin-only. Impacta liquidaciones vía asistencias, que no se liquidan hasta septiembre | **Semana del 31/08** |
| **AUD-025** | Cascadas pueden borrar historia financiera | **No es alcanzable desde la app**: no hay ruta `DELETE` para alumnos, usuarios, grupos ni deportes. Es una mina solo para limpieza manual por SQL | Antes de agregar cualquier botón de borrar |
| AUD-015 | Referencia de cashflow sin índice único | Mitigado: el lock de `validarCaja` serializa la integración | Semana del 31/08 |

### Regla operativa mientras tanto

**No hacer limpieza de datos por SQL directo en producción** hasta que AUD-025 esté
resuelto. Un `DELETE FROM grupos` borraría los alumnos de ese grupo y, en cascada,
todos sus pagos.

---

## 5. Gate de go-live

Se firma el jueves a la noche. Si alguna está en rojo, **no se sube**.

- [ ] `wings:preflight` en verde en el servidor
- [ ] Debug apagado y entorno en producción
- [ ] HTTPS forzado, HSTS activo, SSL Labs A o superior
- [ ] Headers de seguridad presentes en la respuesta
- [ ] Base con usuario dedicado y 3306 cerrado al exterior
- [ ] `.env`, dump, logs y `.git` devuelven 404
- [ ] Ninguna cuenta de prueba ni password conocida
- [ ] Dump fuera del repo y tokens rotados
- [ ] Matriz de roles: cero accesos indebidos
- [ ] Invariante contable: cero fallos
- [ ] FIFO no evadible
- [ ] **Cambio de plan atómico: un pago rechazado no deja plan huérfano**
- [ ] Concurrencia real probada sobre MariaDB
- [ ] Backup cifrado con restore probado
- [ ] Scheduler disparando de verdad
- [ ] Suite completa en verde sobre MariaDB
- [ ] Advisories critical y high en cero
- [ ] Monitoreo de errores recibiendo eventos

---

## 6. Mitigaciones operativas de las primeras semanas

1. **Conciliación diaria** las primeras dos semanas: caja física contra sistema al cerrar cada día.
2. **Backup antes de cada cierre de caja**, no solo el automático de las 03:00.
3. **Método actual en paralelo** dos semanas, solo anotando totales para contrastar.
4. **Un solo operativo la primera semana**: menos superficie de concurrencia.
5. **Ventana de corrección**: los primeros 15 días un admin revisa cada cobro cancelado.
6. **Nada de SQL manual en producción** hasta resolver AUD-025.
7. **No editar clases pasadas** hasta resolver AUD-019.

---

## 7. Calendario post go-live

Ya no es una lista sin fecha: cada ítem tiene su vencimiento, dado por cuándo se
empieza a usar la funcionalidad que afecta.

### Semana del 31/08

| Ítem | Ref |
|---|---|
| Editar clase: validar solapamiento y exigir motivo retroactivo | AUD-019 |
| Índice único en la referencia de cashflow | AUD-015 |
| Recibo PDF a cola con reintento | AUD-045 |

### Antes del 25/09 — primera liquidación real

| Ítem | Ref |
|---|---|
| Comisión por período histórico, no por datos actuales | AUD-020 |
| Locks en el pago de liquidaciones | AUD-018 |
| Paridad entre preview y generación de liquidación | AUD-041 |

### Antes de reactivar la API o construir UI de ajustes

| Ítem | Ref |
|---|---|
| Ajustar/condonar sin dejar plata sin registrar | AUD-021 |
| Contrato completo de la API | AUD-026 |

### Antes de agregar cualquier botón de borrar

| Ítem | Ref |
|---|---|
| Revisar las cascadas que borran historia financiera | AUD-025 |

### Sin fecha crítica

| Ítem | Ref |
|---|---|
| N+1 en asistencias, cobranza y generador mensual | AUD-029 a AUD-032 |
| Unificar algoritmos financieros duplicados | AUD-043 |
| Policies en vez de middleware por ruta | AUD-028 |
| Limpiar la historia de Git del `dump.sql` | AUD-008 |
