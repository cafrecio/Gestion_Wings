# Plan de Producción — Wings v1.0

> **Objetivo:** sistema en línea, seguro y contablemente confiable el **viernes 28/08/2026**.
> **Servidor:** AlmaLinux 9 · **Datos:** base limpia + import selectivo · **Arranque:** producción real, todos los roles.
> **Rebasado:** martes 25/08/2026 a los 4 días que quedan.
> **Versión visual navegable:** artifact "Wings Go-Live" — tablero con el detalle exhaustivo de cada tarea.

> ### ANTES DE EJECUTAR CUALQUIER TAREA DE ESTE PLAN, LEER `AGENTS.md`
> Contiene las reglas que no se negocian, en especial que **el diseño no se toca**.

---

## 0. Diagnóstico verificado

### Cerrado y comprobado

| Ítem | Evidencia |
|---|---|
| PROFESOR no alcanza caja, alumnos ni dinero | `reject.profesor.web` — `routes/web.php:36,75,94,128` |
| Usuario desactivado no entra y pierde la sesión | `ensure.active.web` — `routes/web.php:35` |
| XSS almacenado en autocomplete | `alumnos/index.blade.php:263-271` usa `textContent` |
| API REST sin control de rol | Apagada en `bootstrap/app.php:14` |
| **Inyección SQL** | Sin vectores. Los 13 usos de `DB::raw` / `whereRaw` / `selectRaw` son parametrizados |
| Sobrepago sobre el saldo de la deuda | Rechazado en `PagoCuotaService` |
| Movimientos cancelados hacia cashflow | Excluidos |
| Cancelar movimiento de otra caja (IDOR) | Scopeado por caja |
| Vulnerabilidades npm de producción | `npm audit --omit=dev` da 0 |
| **Matriz de permisos** | 268 pruebas GET × 4 roles el 25/08: 0 errores 500, 0 accesos indebidos |
| **Integridad estructural** | 127 rutas cargan · 54 vistas referenciadas existen · 0 includes rotos · 0 errores de sintaxis · 33 tests pasan |

### Bloqueantes abiertos

| # | Hallazgo | Evidencia |
|---|---|---|
| **B1** | `DatabaseSeeder` crea `test@example.com` con password `password` y carga cashflow de prueba | `DatabaseSeeder.php:20-36`, `UserFactory.php:29` |
| **B2** | `database/dump.sql` versionado con 27 tablas reales: alumnos, pagos, tokens, sesiones | `git ls-files database/dump.sql` |
| **B3** | Hook `pre-commit` reexporta el dump en cada commit | `.git/hooks/pre-commit` |
| **B4** | `.env` en local, `APP_DEBUG=true`, HTTP, DB con root sin password | `.env` |
| **B5** | Sin headers de seguridad | No hay middleware |
| **B6** | **FIFO evadible**: retorno anticipado con un solo ítem, nunca consulta deudas viejas | `PagoCuotaService.php:442-444` |
| **B7** | Asistencias sin transacción y sin validar pertenencia al grupo | `ClaseWebController.php:374-408` |
| **B8** | 44 advisories en 15 paquetes PHP | `composer audit --locked` |
| **B9** | La suite corre en SQLite, no en MariaDB | `phpunit.xml` |
| **B10** | Sin CI, sin backups probados, sin scheduler en servidor | — |
| **B11** | No existe procedimiento de deploy a servidor | `deploy-wings.bat` es un instalador de XAMPP |

---

## 1. Por qué este orden

1. **El código antes que el servidor.** Un servidor perfecto con código inseguro sigue siendo inseguro. Y el blindaje no necesita SSH.
2. **La fuga de datos, antes que todo.** 1.1 y 1.2 van primeras porque el hook sigue armado: cada commit sube más datos. Es el único problema que empeora solo.
3. **El seeder antes de sacar el dump.** El dump es hoy el único mecanismo para sincronizar la BD entre máquinas. Primero el reemplazo, después el retiro.
4. **Dependencias el miércoles, no el martes.** Actualizar librerías rompe cosas; con la suite verde como referencia se sabe qué rompió qué.
5. **Los tests después del servidor.** Para probar de verdad hace falta MariaDB. En SQLite darían verde sin significar nada.
6. **La prueba funcional después del primer deploy.** TLS, permisos, SELinux y timezone solo aparecen en el servidor.
7. **El jueves cierra, el viernes ejecuta.** Si el jueves no cerró, el go-live pasa al lunes 31.

---

## D1 · Martes 25/08 — Blindaje de código

Todo local. No hace falta el servidor.

| # | Tarea | Cierra | Est. |
|---|---|---|---|
| 1.1 | Desactivar el hook `pre-commit` que exporta la base | B3 | 5 min |
| 1.2 | Sacar `dump.sql` del repo y rotar tokens | B2 | 30 min |
| 1.3 | `CatalogosSeeder` idempotente que reemplace al dump | B2 | 1.5 h |
| 1.4 | Sanear seeders y blindarlos contra producción | B1 | 40 min |
| 1.5 | Comando `wings:crear-admin` | B1 | 30 min |
| 1.6 | Middleware `SecurityHeaders`: los 5 headers sin riesgo | B5 | 45 min |
| **1.6b** | **CSP en modo reporte — TAREA SUPERVISADA** | B5 | 1 h |
| 1.7 | `.env.production.example` con el perfil de producción | B4 | 25 min |
| 1.8 | Comando `wings:preflight` que aborte el deploy | B4 | 1 h |
| 1.9 | **FIFO fuerte real** | B6 | 1.5 h |
| 1.10 | Asistencias transaccionales y validadas | B7 | 1 h |
| 1.11 | Endurecer el login a `throttle:5,1` | — | 20 min |
| ~~1.11b~~ | ~~Guardar funciones de Blade con `function_exists`~~ — **HECHO 25/08** | — | — |
| 1.12 | Limpiar la tabla `formas_pago` de la base local | — | 10 min |
| 1.13 | Cierre: suite completa y `/security-review` | — | 30 min |

### Detalle de las tareas críticas

**1.1 — Desactivar el hook.** Es el mecanismo que mantiene la fuga viva: cada commit reexporta la base con alumnos, pagos y sesiones y la sube a GitHub.

    mv .git/hooks/pre-commit .git/hooks/pre-commit.disabled

Los hooks no se versionan: hay que repetirlo en cada máquina.
*Verificación:* un commit de prueba no incluye `dump.sql`.

**1.2 — Sacar el dump del repo.**

    git rm --cached database/dump.sql
    echo "database/dump.sql" >> .gitignore

Después invalidar lo expuesto: vaciar `personal_access_tokens` y `sessions`. La historia de Git sigue conteniendo los datos; limpiarla queda diferido a post go-live.
*Verificación:* `git ls-files database/dump.sql` no devuelve nada y el archivo sigue en disco.
*Depende de:* 1.1 y 1.3.

**1.3 — CatalogosSeeder.** Cubre rubros, subrubros, tipos de caja, deportes, niveles y reglas de primer pago. Idempotente con `updateOrCreate` sobre clave natural. Sin un solo dato personal adentro.
*Verificación:* sobre base vacía, `migrate --seed` la deja utilizable; correrlo dos veces no cambia ninguna fila.
*Si no se hace:* se saca el dump y no queda forma de reconstruir la base.

**1.4 — Sanear seeders.** `DatabaseSeeder` pasa a llamar solo a catálogos; la cuenta de prueba se muda a `TestSeeder`; ambos seeders de datos abortan en producción.

    if (app()->environment('production')) {
        throw new \RuntimeException('Este seeder no corre en produccion.');
    }

*Verificación:* con `APP_ENV=production`, `db:seed` no crea ningún usuario.

**1.6 — Headers de seguridad, la parte sin riesgo.** Estos cinco se aplican directo:

    X-Frame-Options            DENY
    X-Content-Type-Options     nosniff
    Referrer-Policy            same-origin
    Permissions-Policy         camera=(), microphone=(), geolocation=()
    Strict-Transport-Security  max-age=31536000    (solo bajo HTTPS)

*Verificación:* `curl -I` muestra los cinco.

**1.6b — CSP. TAREA SUPERVISADA, NO LA TOMA UN AGENTE SOLO.**

Las vistas usan `style="..."` y `<script>` inline en todos lados. Una CSP estricta de manual **destruye visualmente la aplicación**: se cae el layout, se pierden los colores y los rails de deporte quedan sin pintar.

Secuencia obligatoria:

1. Empezar con `Content-Security-Policy-Report-Only`, que registra sin bloquear.
2. Recorrer la aplicación completa con la consola del navegador abierta y juntar la lista real de violaciones.
3. Recién ahí endurecer, con las excepciones ya conocidas y verificadas.
4. Después de activar el modo bloqueante, revisar visualmente alumnos, caja, cobrar, clases y liquidaciones.

**Nunca activar la CSP bloqueante en un solo paso.** Ver `AGENTS.md` §2.

**1.9 — FIFO fuerte real.** El problema está en el retorno anticipado de `validarFifo`:

    if (count($items) <= 1) {
        return;      // esta es la puerta
    }

Antes de imputar hay que buscar todas las `DeudaCuota` del alumno con período anterior al menor ítem enviado y saldo mayor a cero. Si existe alguna, rechazar el cobro nombrando el período que falta. **La validación va en el servicio, no en la vista**: hoy la interfaz lo disimula pero el servicio no lo garantiza.
*Verificación:* alumno con febrero impago, cobro de marzo como ítem único. Hoy pasa; después debe rechazar.
*Si no se hace:* se acumulan deudas viejas invisibles. El alumno figura al día, la caja cierra bien, y el descubrimiento llega meses después sin forma de reconstruir qué pasó.

**1.10 — Asistencias.** Envolver el bucle de guardado en `DB::transaction` y agregar un FormRequest que valide la estructura completa y que cada `alumno_id` pertenezca al grupo de esa clase.
*Verificación:* alumno de otro grupo, rechaza sin escribir nada; fallo intermedio, no queda ninguna asistencia guardada.
*Si no se hace:* aparecen asistencias de alumnos que no eran de esa clase, y esas asistencias alimentan la liquidación de los profesores.

**1.12 — `formas_pago`.** La migración `drop_forma_pago` la eliminó, pero sobrevivió en la base local porque al importar el dump solo se ejecuta lo que el dump contiene. Si se exporta desde esa máquina, la tabla vuelve al repo y le deshace la migración a todos.

    DROP TABLE IF EXISTS formas_pago;

---

## D2 · Miércoles 26/08 — Servidor y dependencias

| # | Tarea | Cierra | Est. |
|---|---|---|---|
| 2.1 | Provisión base de AlmaLinux 9 | B11 | 1 h |
| 2.2 | Hardening del host | B10 | 1 h |
| 2.3 | MariaDB con usuarios de privilegio mínimo | B4 | 40 min |
| 2.4 | Layout de directorios y verificación de exposición | B11 | 45 min |
| 2.5 | TLS con Let's Encrypt y HSTS | B4 | 30 min |
| 2.6 | `deploy.sh` atómico con rollback | B11 | 1.5 h |
| 2.7 | Scheduler como systemd timer | B10 | 25 min |
| 2.8 | Backups cifrados con destino externo | B10 | 1 h |
| 2.9 | Actualizar dependencias, 44 advisories | B8 | 2 h |

**2.1 — Base del sistema.** AlmaLinux 9 trae PHP 8.0 de fábrica y `composer.json` exige `^8.2`: el repo Remi es obligatorio, no opcional.

    dnf update -y
    dnf install -y epel-release
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
    dnf module reset php -y && dnf module enable php:remi-8.3 -y
    dnf install -y php php-fpm php-mysqlnd php-mbstring php-xml php-bcmath \
                   php-gd php-zip php-intl php-opcache nginx mariadb-server \
                   git unzip policycoreutils-python-utils fail2ban

*Verificación:* `php -v` devuelve 8.3 y los tres servicios arrancan y quedan habilitados al boot.

**2.2 — Hardening del host.** Firewalld abre solo 22, 80 y 443, con el 3306 cerrado. SSH sin root y sin autenticación por contraseña. Fail2ban sobre `sshd` y sobre el log de nginx. **SELinux en enforcing** — no desactivarlo, es la mitad del valor de haber elegido AlmaLinux:

    semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/wings/shared/storage(/.*)?"
    semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/wings/current/bootstrap/cache(/.*)?"
    restorecon -Rv /var/www/wings
    setsebool -P httpd_can_network_connect_db on

*Verificación:* un escaneo desde afuera solo ve 22, 80 y 443; `sestatus` dice enforcing.

**2.3 — MariaDB.** `mysql_secure_installation`, `bind-address = 127.0.0.1`, `time_zone = '-03:00'`. Dos usuarios distintos, la aplicación nunca entra como root:

    CREATE DATABASE wings CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

    -- el que usa la app en el dia a dia
    CREATE USER 'wings_app'@'localhost' IDENTIFIED BY '<fuerte>';
    GRANT SELECT, INSERT, UPDATE, DELETE ON wings.* TO 'wings_app'@'localhost';

    -- solo durante el deploy, para migrate
    CREATE USER 'wings_migrate'@'localhost' IDENTIFIED BY '<fuerte>';
    GRANT ALL ON wings.* TO 'wings_migrate'@'localhost';

*Verificación:* con `wings_app`, un `DROP TABLE` es rechazado y la aplicación funciona igual.

**2.4 — Layout y exposición.**

    /var/www/wings/
    ├── releases/20260826-1430/     cada deploy
    ├── shared/.env                 600, root:nginx
    ├── shared/storage/
    └── current -> releases/...     symlink atomico

Nginx sirve `current/public` y nada más.
*Verificación obligatoria:* pedir por HTTP `/.env`, `/database/dump.sql`, `/storage/logs/laravel.log` y `/.git/config`. Los cuatro deben devolver **404**.

**2.5 — TLS.** Certbot con Let's Encrypt, redirect 301 de 80 a 443, HSTS a un año, renovación automática verificada.
*Verificación:* SSL Labs con calificación A o superior.

**2.6 — deploy.sh atómico.**

    git fetch && checkout <tag>
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    ln -s shared/.env  y  shared/storage
    php artisan wings:preflight        aborta si algo esta mal
    php artisan migrate --force
    php artisan config:cache route:cache view:cache
    ln -sfn releases/<nuevo> current   el cambio atomico
    systemctl reload php-fpm nginx

Rollback: repuntar el symlink al release anterior y recargar. Segundos, no minutos. Retener los últimos 5 releases.

**2.7 — Scheduler.** Timer de systemd cada minuto ejecutando `schedule:run`. Confirmar que la zona horaria del sistema sea `America/Argentina/Buenos_Aires`.
*Si no se hace:* el 1 de septiembre no se genera la cuota del mes y la cobranza queda parada sin aviso.

**2.8 — Backups.** `mysqldump` diario a las 03:00, cifrado con `age` o `gpg`, guardado fuera del webroot y **copiado a un destino externo**. Retención 14 días. Un backup en el mismo disco que la base no es un backup. El restore se prueba el jueves (3.8), no se asume.

**2.9 — Dependencias.** En rama aparte, nunca directo sobre `main`:

    git checkout -b chore/deps
    composer update
    php artisan test
    composer audit --locked

Los advisories que queden vivos se documentan con el motivo, no se ignoran en silencio.
*Verificación:* sin avisos critical ni high, y la suite completa en verde.

---

## D3 · Jueves 27/08 — Datos, pruebas y verificación

El día más cargado. Es el que decide si el viernes se sube.

| # | Tarea | Est. |
|---|---|---|
| 3.1 | Mover la suite de SQLite a MariaDB | 1 h |
| 3.2 | Escribir los tests P0 | 3 h |
| 3.3 | CI en GitHub Actions | 40 min |
| 3.4 | Script de import selectivo | 1.5 h |
| 3.5 | Primer deploy real al servidor | 1 h |
| 3.6 | Smoke de todas las rutas por rol | 1 h |
| 3.7 | Prueba de concurrencia real sobre MariaDB | 1 h |
| 3.8 | Restore drill del backup | 45 min |
| 3.9 | Verificar el scheduler en vivo | 20 min |
| 3.10 | Recorrido funcional manual sobre el servidor | 2 h |
| 3.11 | Revisión de código completa | 40 min |

**3.1 — Suite sobre MariaDB.** Los 33 tests actuales pasan pero corren sobre SQLite, que es otra base: las migraciones reales, el `CONVERT` de `NombreUnico` y los `lockForUpdate` directamente no se ejecutan ahí. Agregar una conexión `mysql_testing` contra una base `wings_test` dedicada.

**3.2 — Tests P0.** Los que sostienen la plata:

- **Matriz de roles** — cada rol × cada ruta × cada método, con URL directa e IDs ajenos. Y verificar que después de un 403 **no quedó ninguna escritura**.
- **Invariante contable** — `pago = suma de imputaciones = movimiento de caja = incremento de deuda`. Es el test más importante del sistema.
- **FIFO** — deuda vieja impaga más intento de cobrar la nueva como ítem único.
- **Caja** — abrir, mover, cerrar, rechazar, corregir, validar. Con caja mixta y doble validación.
- **Cancelación** — propia, ajena, repetida y concurrente.
- **Asistencias** — alumno ajeno, inexistente, duplicado y fallo intermedio.

**3.4 — Import selectivo.** Traer catálogos, alumnos reales y sus planes vigentes. **No traer** pagos, cajas, movimientos, asistencias, liquidaciones ni deudas. La parte crítica es lo que no se trae: si entra el historial de prueba, la contabilidad arranca contaminada y no hay forma de separarla después.
*Verificación:* después del import, todo saldo de caja y de cashflow da **cero**.

**3.6 — Smoke por rol.** Ya existe una base funcionando: el script de 268 pruebas GET usado el 25/08. Extenderlo a los métodos mutantes con CSRF.
*Verificación:* ningún 500 en ninguna combinación, y ningún 200 donde correspondía 403.

**3.7 — Concurrencia real.** Es el límite que el ciclo anterior dejó explícitamente abierto: los bloqueos están escritos pero nunca se probaron con dos conexiones simultáneas de verdad.
*Verificación:* una operación gana y la otra falla con error claro; los saldos quedan consistentes.

**3.8 — Restore drill.** Borrar la base del servidor a propósito y restaurarla del backup cifrado del día. Cronometrar: ese número es el tiempo real de recuperación.
*Si no se hace:* creés tener respaldo hasta el día que lo necesitás.

**3.10 — Recorrido funcional.** Usar el plan de `docs/06-pruebas/`, pero sobre el servidor con TLS y no sobre XAMPP. Los dos circuitos completos: alumno → deuda → cobro → caja → validación → cashflow, y clase → asistencia → liquidación → pago. Registrar todo sin corregir en el momento: primero la lista, después los arreglos.

---

## D4 · Viernes 28/08 — Gate y go-live

No se programa. Se ejecutan los pasos y se abre la caja.

| # | Paso |
|---|---|
| 4.1 | Corregir lo que falló el jueves — única ventana del día |
| 4.2 | Tag `v1.0.0` y congelamiento de código |
| 4.3 | `wings:preflight` en verde sobre el servidor |
| 4.4 | Import de los datos reales |
| 4.5 | Crear los usuarios reales y borrar los de prueba |
| 4.6 | Backup manual antes de abrir |
| 4.7 | Activar el monitoreo de errores |
| 4.8 | Firmar el gate de 17 condiciones |
| 4.9 | Apertura de la primera caja real, acompañada |

---

## 2. Gate de go-live

Se firma el jueves a la noche, no el viernes a la mañana. Si alguna está en rojo, **no se sube**.

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
- [ ] Concurrencia real probada sobre MariaDB
- [ ] Backup cifrado con restore probado
- [ ] Scheduler disparando de verdad
- [ ] Suite completa en verde sobre MariaDB
- [ ] Advisories critical y high en cero
- [ ] Monitoreo de errores recibiendo eventos

---

## 3. Riesgo residual y mitigación

Salir el viernes con el sistema como fuente de verdad del dinero desde el día uno es agresivo. Quedan abiertas a propósito: la atomicidad del cambio de plan más cobro, los algoritmos financieros duplicados, el PDF síncrono y varios N+1. No son bloqueantes de seguridad, pero sí de robustez.

1. **Conciliación diaria** las primeras dos semanas: caja física contra sistema al cerrar cada día.
2. **Backup antes de cada cierre de caja**, no solo el automático de las 03:00.
3. **Método actual en paralelo** dos semanas, solo anotando los totales del día para contrastar.
4. **Un solo operativo la primera semana**: menos superficie de concurrencia mientras se estabiliza.
5. **Ventana de corrección**: los primeros 15 días un admin revisa cada cobro cancelado y cada ajuste de deuda.

---

## 4. Trabajo diferido (post go-live)

| Prioridad | Ítem | Ref |
|---|---|---|
| Alta | Transaccionalidad de cambio de plan más cobro | AUD-012 |
| Alta | Recibo PDF a cola con reintento | AUD-045 |
| Media | N+1 en asistencias, cobranza y generador mensual | AUD-029 a AUD-032 |
| Media | Unificar los algoritmos financieros duplicados | AUD-043 |
| Media | Policies en vez de middleware por ruta | AUD-028 |
| Media | Cascadas que pueden borrar historia financiera | AUD-025 |
| Baja | Limpiar la historia de Git del `dump.sql` | AUD-008 |
| Baja | Contrato completo de la API antes de reactivarla | AUD-026 |
