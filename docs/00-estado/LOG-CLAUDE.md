# Wings — Bitácora de Claude Code

> Memoria operativa entre las computadoras de CyE y CAB.
> No reemplaza `ESTADO-ACTUAL.md` ni el plan vigente: registra qué se hizo y qué sigue.
> La bitácora de Codex es `LOG-CODEX.md`. Son dos archivos separados a propósito.

## Cómo usar esta bitácora

- Leerla antes de comenzar una tarea.
- Agregar una entrada al cerrar cualquier trabajo que produzca cambios.
- Firmar como **Claude CyE** en CyE o **Claude CAB** en la casa de Carlos.
- Registrar hechos verificables y enlazar archivos o commits cuando corresponda.
- **Registrar también las verificaciones del trabajo de Codex**, con el resultado real,
  no con lo que el reporte dijo.
- No incluir contraseñas, tokens, datos personales ni información sensible.
- Mantener cada entrada corta: objetivo, cambios, decisiones, verificación y siguiente paso.

---

## 2026-08-26 — Claude CyE

**Objetivo:** verificar el cierre de las tareas 1.3 y 1.4 reportado por Codex.

**Verificación:** sobre una base descartable `wings_verif`, con el comando literal
`migrate:fresh --seed`. La base de trabajo no se tocó y la descartable se eliminó al
terminar.

- Conteos 8 / 15 / 5 / 2 / 3 / 0 / 0: coinciden con el reporte.
- Niveles exactamente 3: Principiantes, Intermedias, Avanzadas.
- Solo corre `CatalogosSeeder`.
- Segunda ejecución sin cambios.
- Guarda de producción: `DemoSeeder` aborta con `RuntimeException`.
- Suite 38 tests, 130 aserciones.
- `git diff --stat -- resources/views resources/css` vacío.
- La migración de normalización cuenta los grupos antes de borrar y conserva el nivel
  si tiene alguno. La base de Carlos tiene `Principiantes` con 2 grupos: protegido.

**Resultado:** 1.3 y 1.4 cerradas. **B1 cerrado**: una instalación nueva ya no crea
cuenta con contraseña conocida ni movimientos de cashflow de prueba.

**Siguiente paso:** tarea 1.2, sacar `dump.sql` del repositorio. Estaba esperando que
existiera este seeder.

---

## 2026-08-26 — Claude CyE

**Objetivo:** primera verificación de la tarea 1.3, reportada como terminada.

**Resultado: rechazada.** Tres defectos que el reporte no detectó:

1. `DatabaseSeeder` nunca llamaba a `CatalogosSeeder`. El criterio "base vacía
   correcta" se había validado con `--class=CatalogosSeeder`, no con el
   `migrate --seed` literal.
2. El seeder nuevo no reemplazaba a los viejos, se sumaba: rubros 6 a 9, subrubros
   14 a 21, tipos de caja 3 a 6.
3. Los niveles quedaban en 5. Era la misma contradicción que Codex había reportado
   dos días antes, sin resolver.

**Decisión:** 1.3 y 1.4 se resuelven juntas. El criterio de aceptación nuevo nombra
el comando exacto y prohíbe invocar seeders a mano, porque el error estuvo en el
método de validación, no en el criterio.

**Siguiente paso:** reenviar la corrección a Codex.

---

## 2026-08-26 — Claude CyE

**Objetivo:** corregir el plan de producción, que era irreal.

**Punto de partida verificado:** D1 al 15%. Solo cerradas 1.1 y 1.11b. Faltaban 44
horas de trabajo contra dos días. Sin acceso SSH, D2 bloqueado.

**Hallazgo:** faltaba un bloque entero. `docs/06-pruebas/DATASET-SEEDER-V1.md`
especifica el seeder de prueba (15 alumnos, 5 cajas, 22 clases, 3 liquidaciones,
7 horas) y nunca había estado en el plan. El recorrido funcional lo necesita.

**Hallazgo 2:** verificado en `GenerarDeudasMensualesCommand.php:84-101`, el proceso
mensual genera la cuota solo si el alumno tuvo asistencias el mes anterior, o se dio
de alta hace menos de 15 días y ya pagó. Una base recién cargada no cumple ninguna:
manda a todos a la cola de revisión y no genera ninguna deuda. **La cuota del primer
mes se carga a mano junto con los datos iniciales.**

**Decisiones:**

- Son dos seeders con objetivos opuestos: el de catálogos va ahora, el de prueba al
  final, como dice su propia especificación.
- Fecha expresada como función del bloqueante real: go-live = acceso SSH + 5 días
  hábiles. Con el fin de semana disponible, martes 1/09.
- Archivo renombrado a `PLAN-PRODUCCION.md`; la fecha en el nombre ya no aplicaba.

**Siguiente paso:** conseguir el acceso SSH, que mueve la fecha.

---

## 2026-08-25 — Claude CyE

**Objetivo:** verificar los 57 hallazgos de la auditoría v03 uno por uno contra el
código actual, en vez de heredar sus severidades.

**Resultado:**

- **Cuatro ya estaban cerrados** por el ciclo de Codex: AUD-014 (locks en
  `PagoCuotaService:351,365`), AUD-016 (`validarCaja` con transacción y lock),
  AUD-017 (borra imputaciones al cancelar), AUD-024 (filtra `ESTADO_COMPLETADO`).
- **Uno nuevo entra como bloqueante B12** (AUD-012): en `CajaWebController::pagar()`
  el cambio de plan se graba en la línea 631 y el pago se ejecuta en la 674, sin
  transacción común. Arreglar el FIFO aumenta los rechazos, o sea que multiplica los
  planes huérfanos. **1.9 y 1.9b van juntas.**
- **Seis quedan abiertos y no bloquean**, cada uno por un motivo verificado. AUD-021
  no es alcanzable: solo vive en `PagoCuotaController`, que es de API, y la API está
  apagada. AUD-020 y AUD-018 son del módulo de liquidaciones, que arranca vacío y no
  se usa hasta fin de septiembre. AUD-025 no tiene ruta `DELETE` que lo alcance.

**Lección registrada:** severidad sin alcanzabilidad es ruido. Relaté las severidades
de la auditoría antes de comprobar si eran alcanzables, y eso generó alarma
injustificada sobre AUD-021.

**Siguiente paso:** reflejar todo en el plan.

---

## 2026-08-25 — Claude CyE

**Objetivo:** responder si había errores funcionales, y crear `AGENTS.md`.

**Verificación ejecutada:**

- 0 errores de sintaxis PHP en `app/`, `database/`, `routes/`, `config/`.
- 127 rutas cargan; las 54 vistas referenciadas existen; 0 `@include` rotos.
- **268 pruebas GET × 4 roles: 0 errores 500, 0 accesos indebidos.** ADMIN 59 rutas,
  OPERATIVO 19 con las 47 de admin cerradas, PROFESOR solo `clases` y `clases/{id}`,
  anónimo solo `login`. Coincide con `PERMISOS-ROLES.md`.

**Bug encontrado y corregido:** `clases/index.blade.php` declaraba cuatro funciones
PHP sueltas y `liquidaciones/index.blade.php` una más. Al renderizarse dos veces en
el mismo proceso, PHP tira error fatal por redeclaración. En producción con PHP-FPM
no rompe, pero bloqueaba el smoke test y cualquier corrida en CI. Corregido con
`function_exists`: 10 líneas de guardas, cero markup, cero CSS.

**Cambios:** creado `AGENTS.md` con el diseño como regla dura. Codex lee `AGENTS.md`,
no `CLAUDE.md`, así que hasta entonces entraba al repo sin ninguna regla.

**Decisión:** desactivado el hook `pre-commit` (tarea 1.1). Renombrado, no borrado.
Reexportaba la base con datos de alumnos en cada commit.

**Siguiente paso:** el hook sigue armado en la máquina de la casa. Los hooks no se
versionan.
