# Wings — Bitácora compartida de Codex

> Memoria operativa entre las computadoras de CyE y CAB.
> No reemplaza `ESTADO-ACTUAL.md` ni el plan vigente: registra qué se hizo y qué sigue.

## Cómo usar esta bitácora

- Leerla antes de comenzar una tarea.
- Agregar una entrada al cerrar cualquier trabajo que produzca cambios.
- Firmar como **Codex CyE** en CyE o **Codex CAB** en la casa de Carlos.
- Registrar hechos verificables y enlazar archivos o commits cuando corresponda.
- No incluir contraseñas, tokens, datos personales ni información sensible.
- Mantener cada entrada corta: objetivo, cambios, decisiones, verificación y siguiente paso.

---

## 2026-08-30 — Codex CAB

**Objetivo:** iniciar el lote D1B por A1 (atomicidad del cambio de plan y el pago),
con la regresión obligatoria de deuda vieja impaga y cobro de solo el período nuevo.

**Contradicción verificada — lote detenido según `AGENTS.md` §6b.** La orden afirma
que el FIFO ya implementado debe rechazar ese pedido. En el código actual,
`PagoCuotaService::validarFifo()` retorna sin validar cuando recibe un solo ítem y
solo examina los períodos incluidos en el pedido; no consulta deudas anteriores que
quedaron fuera. Se ejecutó la regresión indicada con deuda de julio impaga y pedido
solo por agosto: el pago fue aceptado y redirigió a caja, en vez de producir el error
FIFO. La prueba temporal se retiró para no dejar la suite rota.

**Opciones, sin elegir ninguna:** (1) ampliar primero el FIFO para consultar y
rechazar deudas anteriores no incluidas, lo que implica que 1.9 no estaba cerrada;
(2) cambiar la aceptación de A1 para forzar el rollback mediante otro error real del
pago; (3) probar el rollback enviando deuda vieja y período nuevo en el mismo pedido,
que sí activa el FIFO actual pero no cumple el escenario literal de la orden.

**Cambios funcionales:** ninguno. **Pendiente:** definición del dueño antes de
implementar A1 o continuar las otras seis tareas.

---

## 2026-08-27 17:27 — Codex CyE

**Tareas:** corrección conjunta 1.3 + 1.4 — fuente única de catálogos y seeders seguros.

**Cambios:** `DatabaseSeeder` llama únicamente a `CatalogosSeeder`; eliminados los cinco seeders viejos ya sin invocaciones; `test@example.com` pasó a un `TestSeeder` explícito; `DemoSeeder` y `TestSeeder` abortan en producción; agregada una migración nueva que elimina únicamente niveles legacy sin grupos y registra por `Log::warning` los que conserva por tener referencias. La migración de abril no se modificó.

**Aceptación literal:** sobre una base SQLite descartable vacía se ejecutó `php artisan migrate:fresh --seed`, sin indicar un seeder manualmente. La segunda ejecución de `php artisan db:seed` mantuvo los mismos conteos:

- Rubros: **8**.
- Subrubros: **15**.
- Tipos de caja: **5**.
- Deportes: **2**.
- Niveles: **3** (`Principiantes`, `Intermedias`, `Avanzadas`).
- Usuarios: **0**.
- Movimientos de cashflow: **0**.
- `Cuota Mensual`: **1**.
- `Sueldos`: **1**.

**Verificación:** 38 pruebas y 130 aserciones aprobadas; archivos PHP sin errores de sintaxis; vistas compilan; diff de vistas y CSS vacío.

**Siguiente paso:** commit y push de la corrección conjunta; después continúa la tarea 1.2.

---

## 2026-08-26 17:24 — Codex CyE

**Tarea:** 1.3 — primera implementación de `CatalogosSeeder`; quedó incompleta y fue corregida junto con 1.4 en la entrada del 27/08.

**Cambios:** creado `database/seeders/CatalogosSeeder.php` con deportes, niveles, rubros, subrubros, tipos de caja y reglas de primer pago. Incluye los nombres literales obligatorios `Cuota Mensual` y `Sueldos`; no incluye subrubros personales de profesores, que se crean al registrar cada profesor. Agregadas dos pruebas de aceptación.

**Verificación:** base SQLite temporal vacía migrada y sembrada correctamente; segunda ejecución correcta; comparación exacta de filas sin modificaciones; `Cuota Mensual=1`, `Sueldos=1`, `Usuarios=0`; 35 pruebas y 120 aserciones aprobadas; vistas compilan; diff de vistas y CSS vacío.

**Commit:** `feat(seed): crear catalogos base sin datos personales`.

**Siguiente paso:** tarea 1.2, sacar `database/dump.sql` del repositorio después de sincronizar.

---

## 2026-08-26 11:41 — Codex CyE

**Objetivo:** eliminar Redis del PHP local porque Wings no lo utiliza.

**Cambio:** deshabilitada la línea `extension=php_redis.dll` en `C:\xampp\php\php.ini`. No se alteró la configuración opcional estándar de Laravel porque está inactiva y no carga la extensión.

**Verificación:** `php -v` y `php --ini` sin advertencias; `php artisan view:cache` y `view:clear` correctos; 33 pruebas y 107 aserciones aprobadas.

**Siguiente paso:** ninguno para Redis. Apache tomará el cambio en su próximo reinicio.

---

## 2026-08-26 11:19 — Codex CyE

**Objetivo:** crear una memoria compartida para continuar el proyecto entre computadoras.

**Punto de partida verificado:**

- Rama: `main`.
- Commit: `06eb669`.
- Plan vigente: `docs/00-estado/PLAN-PRODUCCION.md`.
- Orden vigente de Codex: `docs/00-estado/ORDEN-CODEX-D1.md`.
- El lote D1 figura pendiente salvo las tareas ya marcadas como hechas en el plan.

**Cambios:**

- Creada esta bitácora.
- Agregadas en `AGENTS.md` las identidades **Codex CyE** y **Codex CAB** y la obligación de mantener el log.
- Agregado el log al índice de `docs/README.md` y al mapa documental de `AGENTS.md`.

**Decisión:** los chats siguen siendo locales; la continuidad verificable queda dentro del repositorio.

**Verificación:** cambio exclusivamente documental; no se modificó lógica, vistas ni CSS. `php artisan test`: 33 pruebas y 107 aserciones aprobadas. `php artisan view:cache` quedó bloqueado sin salida y fue interrumpido; además PHP advierte que no puede cargar `php_redis.dll`. Ambos problemas quedan informados, no investigados en esta tarea documental.

**Siguiente paso:** leer el plan y esta bitácora antes de ejecutar la próxima tarea del proyecto.
