# Plan de Producción — Wings v1.0

> **Objetivo:** sistema en línea, seguro y contablemente confiable el **viernes 28/08/2026**.
> **Servidor:** AlmaLinux 9 · **Datos:** base limpia + import selectivo · **Arranque:** producción real, todos los roles.
> **Elaborado:** lunes 24/08/2026, sobre `main` @ `bfc3a87`.

---

## 0. Diagnóstico verificado hoy

No hereda afirmaciones de `ANALISIS-INTEGRAL-v03.md`. Cada línea fue comprobada contra el código actual.

### Ya resuelto (verificado)

| Ítem | Evidencia |
|---|---|
| PROFESOR no puede tocar caja, alumnos ni dinero | `reject.profesor.web` aplicado en `routes/web.php:36,75,94,128` |
| Usuario desactivado no entra y pierde la sesión | `ensure.active.web` en `routes/web.php:35` |
| XSS almacenado en autocomplete | `alumnos/index.blade.php:263-271` usa `textContent` |
| API REST sin control de rol | Apagada en `bootstrap/app.php:14` |
| **Inyección SQL** | Sin vectores. Los 13 usos de `DB::raw/whereRaw/selectRaw` son parametrizados o con columnas literales |
| Sobrepago sobre el saldo de la deuda | Rechazado en `PagoCuotaService` |
| Movimientos cancelados hacia cashflow | Excluidos |
| Cancelar movimiento de otra caja (IDOR) | Scopeado por caja |
| Vulnerabilidades npm de producción | `npm audit --omit=dev` → **0** |

### Bloqueantes abiertos

| # | Hallazgo | Evidencia | Riesgo |
|---|---|---|---|
| **B1** | `DatabaseSeeder` crea `test@example.com` con password `password` y carga cashflow de prueba | `database/seeders/DatabaseSeeder.php:20-36`, `UserFactory.php:29` | Cuenta conocida en producción |
| **B2** | `database/dump.sql` versionado con 27 tablas de datos reales: alumnos, pagos, `personal_access_tokens`, `sessions`, profesores | `git ls-files database/dump.sql` | PII y tokens en el repo y en su historia |
| **B3** | Hook `pre-commit` reexporta el dump en **cada** commit | `.git/hooks/pre-commit` | Mantiene B2 vivo automáticamente |
| **B4** | `.env` en `local`, `APP_DEBUG=true`, `APP_URL` en HTTP, DB con `root` sin password | `.env` | Trazas expuestas, credenciales sin TLS |
| **B5** | Sin headers de seguridad (CSP, HSTS, X-Frame-Options, X-Content-Type-Options) | No hay middleware de headers | Clickjacking, sniffing, sin defensa en profundidad |
| **B6** | **FIFO evadible**: `validarFifo` retorna en el primer `if` cuando hay un solo ítem y nunca consulta deudas anteriores impagas | `PagoCuotaService.php:442-444` | Se cobra marzo con febrero impago |
| **B7** | Asistencias sin transacción y sin validar que el alumno pertenezca al grupo | `ClaseWebController.php:374-408` | Escritura parcial, asistencia de alumno ajeno |
| **B8** | 44 advisories en 15 paquetes PHP (Laravel 12.46, Guzzle 7.10, DOMPDF 3.1.4, CommonMark 2.8) | `composer audit --locked` | Vulnerabilidades conocidas |
| **B9** | Suite corre sobre SQLite, no sobre MariaDB | `phpunit.xml` | Las migraciones y el SQL real de producción no se prueban |
| **B10** | Sin CI, sin backups probados, sin scheduler en servidor | — | Sin red de contención |
| **B11** | `deploy-wings.bat` es un instalador de XAMPP en Windows | — | No existe procedimiento de deploy a servidor |

---

## 1. Plan por días

### D1 — Lunes 24/08 · Blindaje de código

**Objetivo del día:** que el código sea seguro *antes* de tocar el servidor.

| # | Tarea | Archivos | Cierra |
|---|---|---|---|
| 1.1 | Desactivar el hook `pre-commit` que exporta el dump | `.git/hooks/pre-commit` | B3 |
| 1.2 | `git rm --cached database/dump.sql` + agregarlo a `.gitignore` + rotar todo `personal_access_token` y sesión que contenga | `.gitignore` | B2 |
| 1.3 | `CatalogosSeeder` idempotente (rubros, subrubros, tipos de caja, deportes, niveles, reglas primer pago). Reemplaza al dump como forma de levantar una instancia | `database/seeders/` | B1, B2 |
| 1.4 | `DatabaseSeeder` solo llama catálogos. `test@example.com` se muda a `TestSeeder`. `DemoSeeder` y `TestSeeder` abortan si `app()->environment('production')` | `DatabaseSeeder.php` | B1 |
| 1.5 | Comando `wings:crear-admin` — crea el primer admin pidiendo password por consola, sin default | `app/Console/Commands/` | B1 |
| 1.6 | Middleware `SecurityHeaders` global: `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`, `Permissions-Policy`, `Strict-Transport-Security` (solo bajo HTTPS) | `app/Http/Middleware/` | B5 |
| 1.7 | `.env.production.example`: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_SAME_SITE=strict`, `LOG_LEVEL=warning`, usuario DB dedicado | raíz | B4 |
| 1.8 | Comando `wings:preflight` — aborta el deploy si: `APP_DEBUG=true`, `APP_ENV≠production`, `APP_KEY` vacía, `DB_USERNAME=root`, `APP_URL` sin `https`, `SESSION_SECURE_COOKIE≠true`, existe `test@example.com`, existe usuario con password débil conocida | `app/Console/Commands/` | B4 |
| 1.9 | **FIFO fuerte real**: quitar el early return. Antes de imputar, consultar todas las `DeudaCuota` del alumno con período anterior al menor ítem enviado y saldo > 0. Si existe alguna, rechazar | `PagoCuotaService.php:440` | B6 |
| 1.10 | Asistencias: envolver en `DB::transaction`, validar por FormRequest que cada `alumno_id` esté en el grupo de la clase y que la estructura sea válida | `ClaseWebController.php` | B7 |
| 1.11 | Endurecer login: `throttle:5,1` por IP + email, y bloqueo temporal tras N fallos | `routes/web.php` | — |

**Cierre del día:** `php artisan test` en verde + `/security-review` sobre el branch.

---

### D2 — Martes 25/08 · Servidor AlmaLinux 9

**Objetivo del día:** infraestructura lista, con TLS, y un deploy repetible.

**2.1 — Base del sistema**
```bash
dnf update -y
dnf install -y epel-release
dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
dnf module reset php -y && dnf module enable php:remi-8.3 -y
dnf install -y php php-fpm php-mysqlnd php-mbstring php-xml php-bcmath php-gd php-zip php-intl php-opcache \
               nginx mariadb-server git unzip policycoreutils-python-utils fail2ban
```
> AlmaLinux 9 trae PHP 8.0 de fábrica; `composer.json` exige `^8.2`. El repo Remi es obligatorio, no opcional.

**2.2 — Hardening del host**
- `firewalld`: abrir solo 22, 80, 443. Cerrar 3306.
- SSH: `PermitRootLogin no`, `PasswordAuthentication no`, solo clave pública.
- `fail2ban` activo sobre `sshd` y sobre el log de nginx.
- SELinux en **enforcing** (no lo desactives — es la mitad del valor de AlmaLinux):
  ```bash
  semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/wings/shared/storage(/.*)?"
  semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/wings/current/bootstrap/cache(/.*)?"
  restorecon -Rv /var/www/wings
  setsebool -P httpd_can_network_connect_db on
  ```

**2.3 — MariaDB**
- `mysql_secure_installation`: password de root, sin usuarios anónimos, sin acceso remoto de root.
- `bind-address = 127.0.0.1`.
- Usuario de aplicación con privilegios mínimos, **nunca root**:
  ```sql
  CREATE DATABASE wings CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'wings_app'@'localhost' IDENTIFIED BY '<password fuerte>';
  GRANT SELECT, INSERT, UPDATE, DELETE ON wings.* TO 'wings_app'@'localhost';
  ```
  > Para `migrate` hacen falta ALTER/CREATE/DROP/INDEX/REFERENCES. Opción recomendada: un segundo usuario `wings_migrate` con esos permisos, usado solo durante el deploy.
- `time_zone = '-03:00'` (ya contemplado en `config/database.php`).

**2.4 — Layout y permisos**
```
/var/www/wings/
├── releases/20260825-1430/     ← cada deploy
├── shared/
│   ├── .env                    ← 600, root:nginx
│   └── storage/
└── current -> releases/…       ← symlink atómico
```
- `nginx` sirve `current/public`. **Nada fuera de `public/` debe ser alcanzable.**
- Verificar explícitamente que devuelven 404: `/.env`, `/database/dump.sql`, `/storage/logs/laravel.log`, `/.git/config`.

**2.5 — TLS**
- `certbot --nginx` con Let's Encrypt.
- Redirect 301 de 80 → 443.
- HSTS con `max-age=31536000; includeSubDomains`.
- Objetivo: **A o A+ en SSL Labs**.

**2.6 — Deploy atómico** (`deploy.sh`, idempotente y con rollback)
```
git fetch && checkout <tag>  →  composer install --no-dev --optimize-autoloader
npm ci && npm run build      →  symlink shared/.env y shared/storage
php artisan wings:preflight  →  ABORTA si algo está mal
php artisan migrate --force  →  config:cache · route:cache · view:cache
ln -sfn releases/<nuevo> current  →  systemctl reload php-fpm nginx
```
Rollback = repuntar el symlink al release anterior. Retener 5 releases.

**2.7 — Scheduler** (systemd timer, no cron)
```ini
# /etc/systemd/system/wings-scheduler.timer  → OnCalendar=*:0/1
# /etc/systemd/system/wings-scheduler.service
ExecStart=/usr/bin/php /var/www/wings/current/artisan schedule:run
```
Verificar que `cobranza:generar-deudas` (agendado en `routes/console.php:12` para el día 1 a las 06:00) efectivamente dispara.

**2.8 — Backups**
- `mysqldump` diario 03:00 → cifrado con `age` o `gpg` → fuera del webroot.
- Copia a destino externo (S3/Backblaze/otro host). Un backup en el mismo disco no es un backup.
- Retención 14 días.
- **Restore drill obligatorio el jueves.** Un backup sin restore probado no cuenta.

---

### D3 — Miércoles 26/08 · Dependencias, datos y red de pruebas

| # | Tarea | Cierra |
|---|---|---|
| 3.1 | Rama `chore/deps`: `composer update` dentro de los constraints → suite completa → medir advisories restantes. Los que queden, documentar por qué | B8 |
| 3.2 | Segunda conexión `mysql_testing` en `config/database.php` y `phpunit.xml` apuntando a MariaDB. La suite pasa a correr sobre el motor real | B9 |
| 3.3 | **Tests P0** (detalle en §2) | B9 |
| 3.4 | Script de import selectivo: catálogos + alumnos + planes vigentes. **Sin** pagos, caja, movimientos, asistencias ni liquidaciones. Validación posterior: todo saldo de caja y cashflow en 0 | — |
| 3.5 | CI en GitHub Actions: `php artisan test` + `composer audit` + `php -l` en cada push a `main` | B10 |
| 3.6 | Corregir el `README.md` raíz (hoy es el genérico de Laravel) | — |

---

### D4 — Jueves 27/08 · Prueba funcional sobre el servidor real

Este es el día que decide si el viernes se sube o no.

| # | Prueba | Criterio de aprobación |
|---|---|---|
| 4.1 | Smoke de las 124 rutas × 4 sujetos (ADMIN, OPERATIVO, PROFESOR, anónimo) | Ningún 500. Ningún 200 donde corresponde 403 |
| 4.2 | Recorrido completo de `docs/06-pruebas/PLAN-PRUEBAS-FUNCIONALES.md` sobre el servidor con TLS | Los 2 flujos troncales cierran sin error |
| 4.3 | **Concurrencia real en MariaDB**: dos conexiones pagando la misma deuda a la vez | Una gana, otra falla limpio. Cero saldos corruptos. *(Es el límite que el ciclo anterior dejó explícitamente abierto)* |
| 4.4 | Scheduler: forzar `cobranza:generar-deudas` y reejecutarlo | Idempotente: la segunda corrida no duplica deuda |
| 4.5 | **Restore drill**: borrar la BD del servidor y restaurar del backup cifrado | Restaura completa. Cronometrar RTO |
| 4.6 | Exposición: `/.env`, `/database/dump.sql`, `/storage/logs/`, `/.git/` | 404 en todos |
| 4.7 | `/code-review ultra` sobre el branch de la semana | Sin hallazgos críticos abiertos |
| 4.8 | SSL Labs | A o superior |

**Regla del día:** todo lo que falle se corrige el jueves. Si algo del gate (§3) sigue rojo el jueves a la noche, el viernes no se sube.

---

### D5 — Viernes 28/08 · Go-live

1. Tag `v1.0.0` y congelamiento de código.
2. `wings:preflight` en verde sobre el servidor.
3. Import selectivo de datos reales.
4. Crear los usuarios reales (admin, operativos, profesores) con passwords fuertes. **Borrar toda cuenta de prueba.**
5. Verificar que `test@example.com` no existe.
6. Backup manual antes de abrir.
7. Activar monitoreo de errores.
8. Firmar el gate (§3) punto por punto.
9. Apertura de caja real con vos presente.

---

## 2. Plan de testeo

Cinco capas. Ninguna reemplaza a la otra.

### Capa 1 — Automatizada sobre MariaDB (PHPUnit)

Hoy hay 33 tests sobre SQLite. Faltan los que sostienen el dinero:

| Grupo | Casos |
|---|---|
| **Matriz de roles** | ADMIN / OPERATIVO / PROFESOR / inactivo / anónimo × cada ruta × cada método. Incluye acceso por URL directa y con IDs ajenos. Verificar que tras un 403 **no quedó ninguna escritura** |
| **Invariante contable** | `pago.monto_final == Σ imputaciones == movimiento de caja == incremento de deuda`. Es la prueba más importante del sistema |
| **FIFO** | Deuda vieja impaga + intento de pagar la nueva con un solo ítem → debe rechazar (hoy pasa) |
| **Sobrepago** | Monto > saldo → rechazo, sin escrituras |
| **Cancelación** | Propia, ajena, repetida y concurrente. Pivote y cashflow conciliados |
| **Caja** | `abrir → mover → cerrar → rechazar → corregir → validar`, caja mixta con movimientos cancelados, doble validación |
| **Cambio de plan + fallo** | Si el pago falla después del cambio de plan, no persiste nada |
| **Asistencias** | Alumno ajeno, inexistente, duplicado, fallo intermedio → rollback total |
| **Scheduler** | Día 1 con período explícito, pago anulado, reejecución idempotente |
| **Liquidaciones** | Doble pago concurrente, dos liquidaciones consumiendo el mismo saldo |

### Capa 2 — Smoke de rutas por rol
Script que autentica cada rol y recorre las 124 rutas. Corre en CI. Detecta 500 y permisos invertidos en segundos.

### Capa 3 — E2E de navegador (Playwright)
Los 6 flujos donde una regresión cuesta plata:
1. Login por rol y redirección correcta.
2. Alta de alumno con plan.
3. Cobro de cuota → caja → recibo PDF.
4. Cierre y validación de caja → reflejo en cashflow.
5. Clase → asistencia → liquidación → pago.
6. Cancelación de un cobro y su reversión completa.

### Capa 4 — Manual guiada
`docs/06-pruebas/PLAN-PRUEBAS-FUNCIONALES.md` y `GUIA-PRUEBA-COLABORADOR.html`, ejecutados sobre el servidor real, no sobre XAMPP.

### Capa 5 — Infraestructura
Restore drill · scheduler real · SSL Labs · exposición de archivos · headers · límites de subida · rotación de logs.

---

## 3. Gate de go-live

Checklist binaria. Si alguna línea está en rojo el jueves a la noche, **no se sube el viernes**.

- [ ] `wings:preflight` verde en el servidor
- [ ] `APP_DEBUG=false` y `APP_ENV=production`
- [ ] HTTPS forzado, HSTS activo, SSL Labs ≥ A
- [ ] Headers de seguridad presentes en la respuesta
- [ ] DB con usuario dedicado, no root; puerto 3306 cerrado al exterior
- [ ] `/.env`, `/database/dump.sql`, `/storage/logs/`, `/.git/` devuelven 404
- [ ] Ninguna cuenta de prueba ni password conocida
- [ ] `dump.sql` fuera del repo y tokens rotados
- [ ] Matriz de roles: 0 accesos indebidos
- [ ] Invariante contable: 0 fallos
- [ ] FIFO no evadible
- [ ] Concurrencia real probada sobre MariaDB
- [ ] Backup cifrado **con restore probado**
- [ ] Scheduler disparando de verdad
- [ ] Suite completa en verde sobre MariaDB
- [ ] Advisories critical/high de composer en 0
- [ ] Monitoreo de errores recibiendo eventos

---

## 4. Riesgo residual y mitigación

Salir el viernes a producción real, con el sistema como fuente de verdad del dinero desde el día 1, es **agresivo**. El código viene de una auditoría de 57 hallazgos de la cual se cerró una parte, y varias cosas (atomicidad de cambios de plan, duplicación de algoritmos financieros, PDF síncrono, N+1) quedan abiertas por diseño de este plan: no son bloqueantes de seguridad, pero sí de robustez.

Mitigaciones que recomiendo aplicar igual:

1. **Conciliación diaria las primeras 2 semanas.** Al cerrar cada día: caja física vs. sistema. Una diferencia detectada el mismo día se arregla; una detectada al mes, no.
2. **Backup automático antes de cada cierre de caja**, no solo el diario de las 03:00.
3. **Método actual en paralelo 2 semanas.** No como doble carga completa: solo anotar los totales del día aparte, para poder contrastar.
4. **Un solo operativo la primera semana.** Menos superficie de concurrencia mientras se estabiliza.
5. **Ventana de corrección**: los primeros 15 días, un admin revisa cada cobro cancelado y cada ajuste de deuda.

---

## 5. Trabajo diferido (post go-live)

Ordenado por lo que primero va a doler:

| Prioridad | Ítem | Referencia |
|---|---|---|
| Alta | Transaccionalidad de cambio de plan + cobro | AUD-012 |
| Alta | Recibo PDF a cola con reintento | AUD-045 |
| Media | N+1 en asistencias, cobranza y generador mensual | AUD-029 a AUD-032 |
| Media | Unificar los algoritmos financieros duplicados | AUD-043 |
| Media | Policies en vez de middleware por ruta | AUD-028 |
| Media | Revisar cascadas que pueden borrar historia financiera | AUD-025 |
| Baja | Limpiar la historia de Git del `dump.sql` | AUD-008 |
| Baja | Contrato completo de la API antes de reactivarla | AUD-026 |

---

## 6. Herramientas — evaluación

### 6.1 MCP

| Servidor | Para qué sirve acá | Prioridad |
|---|---|---|
| **Playwright MCP** | Manejo un navegador real: entro con cada rol, lleno el formulario de cobro, verifico que la caja cerró, saco capturas. Es la diferencia entre "los tests pasan" y "la app funciona". Cubre la Capa 3 del plan de testeo y sirve para reproducir bugs que reportes en lenguaje natural | **Alta** |
| **Sentry MCP** | Leer los errores de producción desde acá sin entrar por SSH. Con producción real desde el día 1, esto vale mucho la primera semana | **Alta (post-deploy)** |
| **Filesystem / Git / GitHub** | Redundantes: ya tengo Read/Write/Bash y `gh` CLI | No instalar |
| **MCP de MariaDB** | Redundante: ya consulto con el cliente `mysql` | No instalar |
| **Context7 (docs de librerías)** | Docs actualizadas de Laravel 12. Útil pero marginal: el proyecto usa 4 dependencias directas | Baja |

Instalación (verificar el comando exacto al momento de correrlo):
```bash
claude mcp add playwright -- npx -y @playwright/mcp@latest
```

### 6.2 Skills a crear en `.claude/skills/`

Hoy no hay ninguna. Cada una evita que yo re-derive contexto en cada sesión:

| Skill | Contenido | Por qué |
|---|---|---|
| `wings-deploy` | El runbook del §D2 completo, con los comandos reales del servidor | Deploy repetible sin reconstruirlo cada vez |
| `wings-preflight` | Checklist de seguridad pre-deploy + el gate del §3 | Que nunca se suba algo sin pasar por la lista |
| `wings-db` | Reglas de export/import, hoy enterradas en `CLAUDE.md` | Se invoca cuando hace falta, en vez de ocupar contexto siempre |
| `wings-design` | Mover `docs/03-diseno-ui/wings-design/SKILL.md` a `.claude/skills/wings-design/SKILL.md` | **Ya está escrito como skill pero no está registrado.** Registrado, se carga solo cuando toco Blade |
| `wings-test` | Cómo correr la suite en MariaDB y qué cubre cada grupo | — |

### 6.3 Skills que ya tenés y conviene usar esta semana

| Skill | Cuándo |
|---|---|
| `/security-review` | Al cerrar cada día de código (D1 y D3) |
| `/code-review ultra` | Jueves, como parte del gate |
| `/fewer-permission-prompts` | Cuanto antes: `.claude/settings.json` tiene **170 permisos ad-hoc acumulados**, ilegible e inmantenible |
| `/run` | Levantar la app para verificar cambios |
| `/loop` | Monitoreo post-launch (revisar logs y errores cada X minutos) |

### 6.4 Herramientas de desarrollo

| Herramienta | Para qué | Prioridad |
|---|---|---|
| **Larastan (PHPStan nivel 5+)** | Encuentra bugs de tipo y null sin ejecutar nada. En un dominio con montos como float y arrays sueltos, esto sí encuentra cosas reales | **Alta** |
| **MariaDB de test** | La suite hoy corre en SQLite: las migraciones reales, el `CONVERT(... USING utf8mb4)` de `NombreUnico` y los `lockForUpdate` **no se prueban** | **Alta** |
| **GitHub Actions** | Tests + `composer audit` en cada push | **Alta** |
| **Playwright** (además del MCP) | Los E2E quedan versionados y corren en CI, no solo cuando yo los ejecuto | **Alta** |
| **Sentry** (free tier alcanza) | Errores de producción con stack trace, sin `APP_DEBUG=true` | **Alta** |
| **`age` o `gpg`** | Cifrar los backups | **Alta** |
| **fail2ban** | Brute force sobre SSH y sobre `/login` | **Alta** |
| **PCOV** | Medir cobertura real | Media |
| **Laravel Pint** | Formato consistente | Baja |
| **Laravel Telescope** | Debug local. **Nunca en producción** | Baja |
| **Redis** | Cache, sesiones y cola. Mejora el PDF síncrono | Post-launch |

### 6.5 Hooks recomendados

| Hook | Acción |
|---|---|
| PostToolUse en `Edit`/`Write` de `*.php` | `php -l` — detecta el error de sintaxis en el momento, no en el deploy |
| PreToolUse en `git commit` | Correr la suite. Reemplaza al hook actual que exporta el dump |
| **Eliminar** el `pre-commit` actual | Es el que mantiene la PII entrando al repo |

---

## 7. Qué necesito de vos

| Cuándo | Qué |
|---|---|
| **Hoy** | Acceso SSH al servidor AlmaLinux 9 y el dominio apuntado por DNS |
| **Hoy** | Confirmar qué alumnos y planes son reales (para el import selectivo del miércoles) |
| Martes | Un destino externo para los backups (S3, Backblaze u otro host) |
| Miércoles | La lista de usuarios reales: nombre, email y rol de cada uno |
| Jueves | 2–3 horas tuyas para el recorrido funcional del §4.2 |
