# Plan de Producción — Wings v1.0

> **Corregido:** miércoles 26/08/2026, 21:15.
> **Servidor:** AlmaLinux 9 · **Datos:** el cliente carga · **Arranque:** producción real.
> **Versión visual navegable:** artifact "Wings Go-Live".

> ### ANTES DE EJECUTAR CUALQUIER TAREA, LEER `AGENTS.md`
> Y registrar lo hecho en `docs/00-estado/LOG-CODEX.md`.

---

## 0. El viernes 28 no entra. Acá está la cuenta.

No es pesimismo: es la suma de las horas que faltan contra los días que quedan.

### Estado real al miércoles 26/08, 21:15

| Tarea | Estado |
|---|---|
| 1.1 Hook desactivado | Hecha |
| 1.11b Funciones de Blade | Hecha |
| 1.3 `CatalogosSeeder` | Escrita, **sin commitear** |
| 1.2, 1.4 a 1.13 | **Sin empezar** |
| Todo D2 (servidor) | **Sin empezar y bloqueado** — no hay acceso SSH |
| Todo D3 (pruebas y datos) | Sin empezar |

**D1 está al 15%.** Martes y miércoles se consumieron.

### Horas que faltan

| Bloque | Horas |
|---|---|
| Terminar D1 | 11 |
| Servidor y dependencias | 9 |
| **Seeder de prueba** (nunca estuvo en el plan) | 7 |
| Pruebas, datos, deploy y verificación | 13 |
| Gate y go-live | 4 |
| **Total** | **44** |

Con Codex y Claude trabajando en paralelo sobre lo que es separable, son unas
**22 horas de reloj**: tres jornadas completas, y eso asumiendo que nada falle.

Quedan dos días hasta el viernes. **No da.**

### Lo que faltaba en el plan anterior

`docs/06-pruebas/DATASET-SEEDER-V1.md` define el seeder de prueba: 15 alumnos,
5 cajas en los cuatro estados, 22 clases, 3 liquidaciones y una sección de
verificación obligatoria. Son unas 7 horas de trabajo cuidadoso.

**No estaba en el plan.** Y el recorrido funcional sobre el servidor lo necesita:
sin esos datos no hay nada que probar.

---

## 1. Fecha nueva: martes 1/09

Con el sábado 29 y el domingo 30 disponibles, entran dos jornadas más. Eso adelanta
el go-live del 4/09 al **1/09**.

### Sigue dependiendo del acceso al servidor

| Si el SSH llega… | Go-live |
|---|---|
| Jueves 27 | **Martes 1/09** |
| Viernes 28 | Miércoles 2/09 |
| Semana que viene | Se recalcula |

### Cronograma

| Día | Bloque | Horas de trabajo |
|---|---|---|
| **Jue 27** | Blindaje: seguridad y los dos bugs de plata | 11 |
| **Vie 28** | Servidor y dependencias | 9 |
| **Sáb 29** | Código: seeder de prueba | 7 |
| **Dom 30** | Código: tests P0, CI, y primer deploy | 10 |
| **Lun 31** | Verificación técnica, recorrido funcional, correcciones | 7 |
| **Mar 1/09** | Gate y go-live | 4 |

**El gate se firma el lunes 31 a la noche.** Si algo queda en rojo, el go-live se
corre al miércoles 2. No se sube con el gate abierto.

### La deuda del primer mes se carga a mano — sí o sí

Verificado en `GenerarDeudasMensualesCommand.php:84-101`. El proceso mensual genera
la cuota solo si el alumno cumple una de dos condiciones:

- tuvo **asistencias el mes anterior**, o
- se dio de alta hace menos de 15 días **y ya pagó**.

Una base de producción recién cargada no cumple ninguna: no hay asistencias de
agosto porque el sistema no estaba arriba, y los alumnos existentes tienen fecha de
alta vieja.

**Resultado si se deja correr solo: los manda a todos a la cola de revisión y no
genera ni una deuda.**

Por eso la cuota del primer mes se carga junto con los datos iniciales, como parte
del arranque. No es un problema del proceso mensual: es que el primer mes no tiene
mes anterior. A partir de octubre funciona solo.

Esto **elimina la ventaja de salir antes del 1 de septiembre**: la generación
automática de ese día no iba a servir de todos modos.

## 2. Bloqueantes

| # | Hallazgo | Evidencia |
|---|---|---|
| **B1** | `DatabaseSeeder` crea `test@example.com` con password `password` | `DatabaseSeeder.php:20-36` |
| **B2** | `database/dump.sql` versionado. **Higiene, no fuga**: los 36 alumnos son inventados, DNI correlativos. Se resuelve en el go-live, antes de cargar datos reales | `git ls-files database/dump.sql` |
| ~~**B3**~~ | ~~Hook `pre-commit` reexporta el dump~~ | **CERRADO** |
| **B4** | `.env` en local, `APP_DEBUG=true`, HTTP, DB con root sin password | `.env` |
| **B5** | Sin headers de seguridad | No hay middleware |
| **B6** | **FIFO evadible** | `PagoCuotaService.php:442-444` |
| **B7** | Asistencias sin transacción ni validación de pertenencia | `ClaseWebController.php:374-408` |
| **B8** | 44 advisories en 15 paquetes PHP | `composer audit --locked` |
| **B9** | La suite corre en SQLite, no en MariaDB | `phpunit.xml` |
| **B10** | Sin CI, sin backups probados, sin scheduler en servidor | — |
| **B11** | No existe procedimiento de deploy a servidor | — |
| **B12** | **Cambio de plan no atómico con el cobro** | `CajaWebController::pagar()` — plan en 631, pago en 674 |
| **B13** | **No existe seeder de prueba** | `DATASET-SEEDER-V1.md` está escrito pero sin implementar |

---

## 3. Los dos seeders, que no son el mismo

Es la corrección conceptual que faltaba. Tienen objetivos opuestos.

### `CatalogosSeeder` — va ahora

Lo que producción necesita para funcionar: deportes, niveles, rubros, subrubros,
tipos de caja y configuraciones.

Desbloquea sacar el `dump.sql` del repo (tarea 1.2).

El **contenido** de esos catálogos lo define el cliente. El seeder los deja
cargados para que el sistema arranque sin explotar; el cliente los ajusta desde
la aplicación.

Dos nombres son obligatorios porque el código los busca literalmente:

- El subrubro **Cuota Mensual** — lo usa `PagoCuotaService`.
- El rubro **Sueldos** — lo usa `ProfesorWebController` para crear el subrubro de
  cada profesor.

### Seeder de prueba — va el lunes 31

Los datos para probar el sistema. Está especificado fila por fila en
`docs/06-pruebas/DATASET-SEEDER-V1.md`.

**Quien lo implemente no decide qué datos cargar.** Si algo no está definido ahí,
se pregunta; no se inventa.

Ese documento dice explícitamente que el seeder se construye **al final, cuando el
sistema ya funcione**, porque hacerlo antes obliga a rehacerlo después de cada
corrección. Por eso va el lunes y no hoy.

**Por qué importa que esté bien hecho:** el `DemoSeeder` anterior cargó una
operación por día, todas iguales, todas en efectivo, todas de cobro de cuota, todas
del mismo usuario, y las clases sin asistencia. Con esos datos no se puede probar
nada. La spec existe justamente para no repetirlo.

---

## 4. Por qué este orden

1. **El código antes que el servidor.** Un servidor perfecto con código inseguro sigue siendo inseguro. Y D1 no necesita SSH.
2. **El seeder de catálogos antes de sacar el dump.** El dump es hoy el único modo de sincronizar la BD entre máquinas.
3. **FIFO y atomicidad del plan, juntos.** Arreglar uno sin el otro empeora el sistema.
4. **Dependencias después del código propio.** Actualizar librerías rompe cosas; con la suite verde como referencia se sabe qué rompió qué.
5. **El seeder de prueba después de las correcciones.** Si se hace antes, hay que rehacerlo.
6. **Los tests después del servidor.** Para probar de verdad hace falta MariaDB.
7. **La prueba funcional después del primer deploy.** TLS, permisos, SELinux y timezone solo aparecen en el servidor.
8. **El último día ejecuta, no programa.**

---

## D1 · Jueves 27/08 — Blindaje de código

Todo local, no necesita servidor.

| # | Tarea | Ejecuta | Cierra | Est. |
|---|---|---|---|---|
| 1.3 | Cerrar y commitear `CatalogosSeeder` | Codex | B2 | 30 min |
| 1.4 | Sanear seeders y blindarlos contra producción | Codex | B1 | 40 min |
| 1.5 | Comando `wings:crear-admin` | Codex | B1 | 30 min |
| 1.6 | `SecurityHeaders`: los 5 headers sin riesgo | Codex | B5 | 45 min |
| **1.6b** | **CSP — PRIORITARIA, obligatoria antes de subir** | **Supervisada** | B5 | 4 h |
| 1.7 | `.env.production.example` | Codex | B4 | 25 min |
| 1.8 | Comando `wings:preflight` | Codex | B4 | 1 h |
| **1.9** | **FIFO fuerte real** | **Supervisada** | B6 | 1.5 h |
| **1.9b** | **Atomicidad del cambio de plan** | **Supervisada** | B12 | 2 h |
| 1.10 | Asistencias transaccionales y validadas | Codex | B7 | 1 h |
| 1.11 | Endurecer el login a `throttle:5,1` | Codex | — | 20 min |
| 1.12 | Limpiar `formas_pago` de la base local | Codex | — | 10 min |
| 1.13 | Cierre: suite completa y `/security-review` | Supervisada | — | 30 min |

**1.9 y 1.9b van juntas.** Arreglar el FIFO aumenta los pagos rechazados, y cada
rechazo deja un cambio de plan huérfano si 1.9b no está.

### 1.6b — CSP. Obligatoria antes de subir.

**Medido el 28/08:** 1.054 atributos `style` escritos a mano en 66 vistas (447
valores distintos, ya usando los tokens del diseño) y 24 archivos con `<script>`
incrustado.

**Decisión: no hay que sacar los estilos.** Un estilo inline no ejecuta código,
solo pinta. El riesgo real es el JavaScript. Entonces:

- `script-src` **estricto** — ahí está el peligro.
- `style-src` **permisivo** — ahí está el diseño.

Con eso hay protección contra inyección de código y **no se rompe una sola
pantalla**. Los 1.054 estilos quedan como están.

**El trabajo real son los 24 archivos con JavaScript incrustado:** se mueven a
archivos aparte o se les firma con hash. Entre 3 y 5 horas.

**Secuencia obligatoria igual:** arrancar en `Content-Security-Policy-Report-Only`,
recorrer la app juntando violaciones reales, y recién ahí endurecer. Revisar a ojo
alumnos, caja, cobrar, clases y liquidaciones.

**Definir la política por subdominio, no una sola para todo el dominio:** la web
comercial va a tener sus propios estilos y scripts.

---

## D2 · Viernes 28/08 — Servidor y dependencias

**Bloqueado hasta que llegue el acceso SSH.**

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

**2.1 — AlmaLinux 9 trae PHP 8.0 y el proyecto exige 8.2.** El repo Remi es
obligatorio:

    dnf install -y epel-release
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
    dnf module reset php -y && dnf module enable php:remi-8.3 -y
    dnf install -y php php-fpm php-mysqlnd php-mbstring php-xml php-bcmath \
                   php-gd php-zip php-intl php-opcache nginx mariadb-server \
                   git unzip policycoreutils-python-utils fail2ban

**2.2 — SELinux en enforcing.** No desactivarlo:

    semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/wings/shared/storage(/.*)?"
    semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/wings/current/bootstrap/cache(/.*)?"
    restorecon -Rv /var/www/wings
    setsebool -P httpd_can_network_connect_db on

**2.3 — La aplicación nunca entra como root:**

    CREATE USER 'wings_app'@'localhost' IDENTIFIED BY '<fuerte>';
    GRANT SELECT, INSERT, UPDATE, DELETE ON wings.* TO 'wings_app'@'localhost';

    -- solo durante el deploy
    CREATE USER 'wings_migrate'@'localhost' IDENTIFIED BY '<fuerte>';
    GRANT ALL ON wings.* TO 'wings_migrate'@'localhost';

**2.4 — Verificación obligatoria:** `/.env`, `/database/dump.sql`,
`/storage/logs/laravel.log` y `/.git/config` deben devolver **404**.

**2.8 — Backups:** cifrados, fuera del webroot y **copiados a un destino externo**.
Uno en el mismo disco que la base no es un backup. El restore se prueba el miércoles.

---

## D3 · Sábado 29/08 — Seeder de prueba

Un solo bloque, porque es grande y no se puede hacer a medias.

| # | Tarea | Ejecuta | Cierra | Est. |
|---|---|---|---|---|
| 3.0 | Implementar el seeder de prueba según `DATASET-SEEDER-V1.md` | Supervisada | B13 | 7 h |

**Transcribir, no inventar.** El documento define cada fila.

Lo que tiene que quedar cargado:

- 15 alumnos, cada uno para probar una cosa concreta: los cuatro estados de
  cobranza, el corte por cantidad de clases del alumno nuevo, la generación mensual,
  los tres casos de cambio de plan y un alumno dado de baja.
- 22 clases con asistencias **desiguales**: alguna con todos presentes, alguna con
  todos ausentes, el resto mezcladas, y dos con motivo de corrección.
- 5 cajas en los cuatro estados. **Ninguna con un solo movimiento**, cada una con
  al menos dos tipos de caja distintos y al menos un egreso.
- Solo la caja validada se refleja en cashflow, con `referencia_id` apuntando a la
  **caja**, nunca al movimiento.
- 3 liquidaciones, cuyo total tiene que **coincidir con las clases realmente
  cargadas**. Si no cierra, el dataset está mal.

**Sección 7 del documento: la verificación es obligatoria.** El seeder imprime y
compara el estado de cobranza de los 15 alumnos contra el esperado. Si alguno no
coincide, falla. Si falla, no se entrega.

---

## D4 · Domingo 30/08 — Pruebas automatizadas y primer deploy

| # | Tarea | Ejecuta | Cierra | Est. |
|---|---|---|---|---|
| 4.1 | Mover la suite de SQLite a MariaDB | Codex | B9 | 1 h |
| 4.2 | Escribir los tests P0 | Supervisada | B9 | 3 h |
| 4.3 | CI en GitHub Actions | Codex | B10 | 40 min |
| 4.4 | **Primer deploy real al servidor** | Codex | B11 | 1 h |

**4.4 — El primer deploy** es la primera vez que el código corre fuera de una máquina de desarrollo. Todo lo implícito en XAMPP se hace explícito: permisos, rutas, zona horaria, SELinux.

**4.2 — Los tests que sostienen la plata:**

- **Invariante contable** — `pago = suma de imputaciones = movimiento de caja =
  incremento de deuda`. El más importante del sistema.
- **Matriz de roles** — cada rol × cada ruta × cada método, con IDs ajenos. Y que
  tras un 403 no quede ninguna escritura.
- **FIFO** — deuda vieja impaga más cobro de la nueva como ítem único.
- **Cambio de plan con fallo posterior** — no debe persistir nada.
- **Caja** — abrir, mover, cerrar, rechazar, corregir, validar. Caja mixta.
- **Cancelación** — propia, ajena, repetida y concurrente.
- **Asistencias** — alumno ajeno, inexistente, duplicado y fallo intermedio.

---

## D5 · Lunes 31/08 — Verificación técnica y recorrido funcional

| # | Tarea | Ejecuta | Est. |
|---|---|---|---|
| 5.1 | Smoke de todas las rutas por rol | Codex | 1 h |
| 5.2 | Prueba de concurrencia real sobre MariaDB | Supervisada | 1 h |
| 5.3 | Restore drill del backup | Codex | 45 min |
| 5.4 | Verificar el scheduler en vivo | Codex | 20 min |
| 5.5 | **Recorrido funcional completo sobre el servidor** | **Carlos** | 2 h |
| 5.6 | Revisión de código completa | Supervisada | 40 min |
| 5.7 | Corregir lo que aparezca | Supervisada | variable |

**5.1** — Ya existe base: el script de 268 pruebas GET del 25/08. Extenderlo a
métodos mutantes con CSRF.

**5.2** — Los `lockForUpdate` existen pero nunca se probaron con dos conexiones
simultáneas reales.

**5.3** — Borrar la base del servidor y restaurarla del backup cifrado.
Cronometrar: ese número es el tiempo real de recuperación.

---

**5.5 — El recorrido funcional.** Sobre el servidor con TLS, con los datos del
seeder de prueba. Los dos circuitos completos: alumno → deuda → cobro → caja →
validación → cashflow, y clase → asistencia → liquidación → pago.

Registrar todo sin corregir en el momento: primero la lista, después los arreglos.

**5.7 — Es la última ventana de corrección.** Lo que no entre acá no sale el martes.

---

## D6 · Martes 1/09 — Gate y go-live

| # | Paso |
|---|---|
| 7.1 | Firmar el gate de 18 condiciones |
| 7.2 | Tag `v1.0.0` y congelamiento |
| 7.3 | `wings:preflight` en verde sobre el servidor |
| **7.3b** | **Sacar `dump.sql` del repo** — antes de que existan datos reales | 30 min |
| 7.4 | Base limpia y carga de datos reales por el cliente |
| 7.5 | Crear los usuarios reales y borrar los de prueba |
| 7.6 | **Cargar la deuda del primer mes junto con los datos** — ver seccion 1 |
| 7.7 | Backup manual antes de abrir |
| 7.8 | Activar el monitoreo de errores |
| 7.9 | Apertura de la primera caja real, acompañada |

**7.3b — por qué acá y no antes.** Hoy los 36 alumnos del dump son inventados: DNI
correlativos desde `30000001`. **No hay ninguna fuga.** Lo que sí es real es que el
mecanismo sigue apuntando a la base, y en el paso 7.4 esa base pasa a tener alumnos
de verdad. La tarea vale ahí, no antes: no aporta nada a que el sistema funcione.

Incluye actualizar los 16 documentos y 2 scripts que hoy mencionan el dump, y
desactivar el hook `pre-commit` **en la máquina de casa**, donde sigue armado.

**7.6 no es opcional.** El proceso mensual no puede generar la primera cuota: una
base recién cargada no tiene mes anterior. Ver la sección 1.

---

## 5. Gate de go-live

Se firma el lunes 31 a la noche. Si alguna está en rojo, **no se sube**.

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
- [ ] Cambio de plan atómico: un pago rechazado no deja plan huérfano
- [ ] Concurrencia real probada sobre MariaDB
- [ ] Backup cifrado con restore probado
- [ ] Scheduler disparando de verdad
- [ ] Suite completa en verde sobre MariaDB
- [ ] Advisories critical y high en cero
- [ ] Monitoreo de errores recibiendo eventos

---

## 6. Riesgos que salen a producción con el defecto adentro

Ninguno se difiere por falta de tiempo. Cada uno se verificó y no es alcanzable, o
pertenece a un módulo que no se usa hasta fin de septiembre.

| # | Hallazgo | Por qué no bloquea | Vence |
|---|---|---|---|
| **AUD-021** | Ajustar o condonar deuda deja plata sin registrar | **No es alcanzable.** Solo existe en `PagoCuotaController`, que es de API, y la API está apagada | Antes de reactivar la API |
| **AUD-020** | La comisión usa alumno activo y deporte actual | Solo afecta al recalcular. Producción arranca con cero liquidaciones | Antes del 25/09 |
| **AUD-018** | Pago concurrente de liquidación puede duplicar el egreso | Mismo módulo, mismo calendario, admin-only | Antes del 25/09 |
| **AUD-019** | Editar clase no valida solapamiento ni exige motivo | Admin-only. Mitigación: no editar clases pasadas | Semana del 7/09 |
| **AUD-025** | Cascadas pueden borrar historia financiera | **No es alcanzable desde la app**: no hay ruta `DELETE` para alumnos, usuarios, grupos ni deportes | Antes de agregar un botón de borrar |
| AUD-015 | Referencia de cashflow sin índice único | Mitigado por el lock de `validarCaja` | Semana del 7/09 |

---

## 7. Mitigaciones de las primeras semanas

1. **Conciliación diaria** las primeras dos semanas: caja física contra sistema.
2. **Backup antes de cada cierre de caja**, no solo el automático de las 03:00.
3. **Un solo operativo la primera semana**: menos superficie de concurrencia.
4. **Nada de SQL manual en producción** hasta resolver AUD-025.
5. **No editar clases pasadas** hasta resolver AUD-019.

---

## 8. Mejoras posteriores — no bloquean el go-live

Se pueden hacer con el sistema ya en línea.

### Sacar los estilos escritos a mano de las vistas

**No es un problema de seguridad**: con la CSP resuelta como dice la 1.6b, esos
estilos no son un riesgo. **Es un problema de mantenimiento**: hoy cambiar un color
implica tocar 66 archivos.

Son 1.054 ocurrencias con 447 valores distintos. Los 20 patrones más repetidos
cubren cerca de un tercio del total, así que conviene atacar esos primero y dejar
la cola larga para después.

Toca vistas y CSS, o sea que **va con Carlos mirando**, nunca delegada. Ver
`AGENTS.md` §1.

---

## 9. Lo que bloquea hoy

| # | Necesito | Estado |
|---|---|---|
| 1 | **Acceso SSH al servidor** | **Pendiente — mueve la fecha de go-live** |
| 2 | Dominio apuntado a la IP | Pendiente |
| 3 | Destino externo para los backups | Pendiente |
| 4 | Lista de usuarios reales | Pendiente |
| 5 | 2 horas de Carlos el lunes 31/08 | Pendiente |

Explicación de qué es cada cosa: `docs/00-estado/CHECKLIST-CARLOS.md`.
