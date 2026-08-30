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

## 2026-08-30 — Codex CAB — dependencias sin avisos de seguridad

**Actualización:** `composer update` renovó 73 paquetes dentro de los rangos ya
declarados. `barryvdh/laravel-dompdf` pasó de 3.1.1 a 3.1.2 y su motor
`dompdf/dompdf` de 3.1.4 a 3.1.6. `laravel/tinker` permaneció en la rama 2
(2.11.0 a 2.11.1). `composer.json` no se modificó; solo cambió el lock.

**Recibo real:** se regeneró el recibo de cuota 145 con datos existentes y se
abrieron sus dos páginas A5 mediante render PNG. Cabecera, datos, período, total,
medio de cobro, observaciones y firma quedaron legibles, sin cortes,
superposiciones, glifos rotos ni imágenes faltantes. Se comparó contra el recibo 1,
cacheado en julio con Dompdf 3.1.4: ya tenía las mismas dos páginas y distribución,
por lo que no hubo regresión de paginado.

**Verificación:** 65 pruebas y 252 aserciones aprobadas; `composer validate
--strict` correcto; `composer audit --locked` informa **0 advertencias**. No se
tocaron archivos PHP, por lo que no hubo archivos aplicables a `php -l`. Vistas
compiladas y diff de vistas/CSS vacío. Los PNG temporales de inspección se
eliminaron; el PDF regenerado permanece en el storage local no versionado.

---

## 2026-08-30 — Codex CAB — cuenta ADMIN protegida

**Decisión contractual:** se mantuvieron los tres roles. `es_superadmin` es una
marca de integridad sobre una cuenta ADMIN, no un rol ni un permiso adicional; no
cambia rutas, middlewares ni la matriz ADMIN/OPERATIVO/PROFESOR. La excepción quedó
documentada en `PERMISOS-ROLES.md`.

**Cambios reales:** migración booleana con valor predeterminado `false`; el listado
solo muestra cuentas protegidas a sí mismas; `edit`, `update` y `toggleActivo`
responden 403 para cualquier otro usuario antes de validar o escribir. El alta y la
edición web ignoran intentos de establecer la marca. `wings:crear-admin
--superadmin` es la única vía de alta protegida. El preflight conserva su consulta
por rol ADMIN activo y se probó expresamente con una cuenta protegida.

**Regresión:** antes del bloqueo fallaban seis escenarios: visibilidad, acceso a la
edición, cambio de datos, contraseña, rol y estado activo. Después del arreglo pasan,
incluido que la cuenta protegida se vea a sí misma y administre a otro ADMIN.

**Verificación:** 65 pruebas y 252 aserciones aprobadas; siete archivos PHP sin
errores de sintaxis; vistas compiladas; diff de vistas y CSS vacío. La migración se
aplicó en CAB y la columna quedó `tinyint(1)`, predeterminado `0`. No se creó ni se
marcó ninguna cuenta real: Carlos debe ejecutar el comando con su email y una
contraseña que no se registre en el repo.

---

## 2026-08-30 — Codex CAB — D1 nombres de grupo en documentos

**Corregido en el origen.** `Grupo::nombre_completo` ahora carga `deporte` y
`nivel` cuando faltan y nunca devuelve un nombre vacío. Las consultas masivas de
liquidaciones y cobranza cargan ambas relaciones para evitar una consulta por fila;
`PagoService` carga la cadena del plan activo. El uso señalado en
`AlumnoWebController::autocomplete()` ya tenía `grupo.deporte` y `grupo.nivel`, por
lo que D1 no necesitó modificarlo.

**Regresión comprobada antes del arreglo:** el accesor devolvía `" — "` y una
liquidación real generaba `"Clase 02/03/2026 -  —  (Validada manual)"`. Después del
arreglo, el detalle contiene `"Patín — Inicial"`; el test también cubre el acceso a
un grupo recuperado sin relaciones precargadas.

**Verificación:** 53 pruebas y 219 aserciones aprobadas; sintaxis PHP correcta en
los cinco archivos PHP de D1; vistas compiladas. D1 no modificó vistas ni CSS. Al
cerrar apareció trabajo local concurrente, ajeno a este commit, en el formulario y
controlador de alumnos para `fecha_alta`; se preservó sin incluirlo. La incorporación
de `fecha_alta` queda fuera de D1 y requiere su autorización separada.

---

## 2026-08-30 — Codex CAB — cierre C3

**1.9 cerrada por reemplazo contractual, no por ampliar el FIFO.** Se contrastaron
los dos contratos vigentes: `Wings-contrato-cuotas-deudas-pagos-V1.md` §3.e define
FIFO cuando un mismo pago cubre múltiples períodos, y
`Wings-contrato-estadosAlum-cobranza-asistencia-V1.md` §9b define los períodos que
quedan fuera. Son coherentes: `PagoCuotaService::validarFifo()` sigue invocado en los
flujos OPERATIVO y ADMIN y ordena lo incluido en un cobro; A2 avisa antes de guardar,
exige motivo y notifica al administrador si quedan meses anteriores omitidos. B6 deja
de considerarse defecto por decisión del dueño.

**1.12 confirmada.** La consulta a `information_schema.TABLES` sobre la base local
`gestion_wings` devolvió cero filas para `formas_pago`. No quedan referencias de
ejecución en `app/`, `config/`, `routes/` ni `tests/`; las únicas menciones técnicas
están en migraciones históricas que crean y luego eliminan la tabla y su FK.

**Verificación:** 51 pruebas y 211 aserciones aprobadas; vistas compilan; diff de
vistas y CSS vacío. No hubo cambios funcionales en C3.

---

## 2026-08-30 — Codex CAB — lote D1B retomado

**Completado y commiteado:** A1 hizo atómicos el cambio de plan, la reescritura de
deuda y el pago (`fb473ce`); A2 agregó aviso 409, motivo obligatorio, registro en el
pago y notificación por correo a administradores activos cuando se dejan meses
anteriores pendientes (`51b7570`); B1 creó `wings:crear-admin` con contraseña oculta,
control de fuerza y duplicados (`5914cab`); B2 agregó la plantilla de producción y
`wings:preflight` sincronizados (`3a050fa`); B3 agregó los cinco headers sin CSP y sin
reactivar la API (`c6ce1d6`); C1 hizo atómico el guardado de asistencias y rechaza
alumnos ajenos al grupo (`dab369f`); C2 limitó el login a cinco intentos por minuto
(`04a7903`).

**Verificación real:** 51 pruebas y 211 aserciones aprobadas al cerrar C2; sintaxis
PHP correcta; vistas compilan; cada tarea cerró con diff de vistas/CSS vacío salvo
A2, cuyo único archivo visual fue `resources/views/caja/cobrar.blade.php`, autorizado
expresamente. No se agregó CSP, no se tocó `app.css`, el plan ni la evaluación.

**Contradicción verificada — C3 detenido según `AGENTS.md` §6b.** La corrección de
la propia orden y el contrato §9b establecen que cobrar un período dejando otro
anterior impago no se bloquea: se avisa, se exige motivo y se notifica. A2 implementa
y prueba esa regla. Sin embargo C3 todavía exige confirmar que `validarFifo()`
"rechaza el caso de deuda vieja impaga con cobro del período nuevo". Cumplir esa
frase desharía A2 y violaría el contrato.

**Opciones, sin elegir ninguna:** (1) corregir C3 para verificar el FIFO solo entre
los períodos incluidos en un mismo cobro y verificar A2 para los períodos omitidos;
(2) declarar 1.9 reemplazada por §9b y cerrar únicamente contra la regla de aviso;
(3) restaurar el bloqueo fuerte, lo que requiere cambiar el contrato y retirar A2.

**Quedó afuera:** C3 no se cerró y no se afirmó el estado actual de `formas_pago` en
esta retomada. Los archivos locales previos de Carlos permanecieron intactos.

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
