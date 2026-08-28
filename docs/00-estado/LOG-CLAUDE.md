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

## 2026-08-28 — Claude CyE

**Objetivo:** revisar una por una las 12 tareas del bloque 1, preguntando de cada
una: qué hace, para qué, si el problema es real, si omitimos algo, y qué aporta a
dejar el sistema funcionando en el servidor.

### Contexto nuevo que cambió decisiones

Carlos informó la arquitectura destino: un dominio `gestionar-te.com.ar` con la web
comercial, y subdominios por cliente. Wings es un caso particular con base propia;
el futuro `gestionclubes` será multi-inquilino sobre base compartida.

**Consecuencia inmediata verificada:** `SESSION_DOMAIN` debe quedar vacío. Si se
pone el dominio padre, la cookie de sesión vale para todos los subdominios y un
cliente de gestionclubes llevaría sesión válida hacia el sistema de Wings. Entra
como chequeo del módulo 1.7.

**Consecuencia 2:** el servidor se arma para varios sitios desde el principio —una
carpeta por sitio, un nginx por subdominio, certificado wildcard— no para uno solo.
Rehacerlo después cuesta; hacerlo bien de entrada, no.

### Cambios al plan

| Qué | Decisión |
|---|---|
| **1.2** sacar el dump | **Movida al go-live** como paso 7.3b. No aporta nada a que el sistema funcione; su valor aparece justo antes de cargar datos reales |
| **1.6b** CSP | **Reformulada y sube a prioritaria.** Medido: 1.054 estilos inline en 66 vistas, 447 valores distintos, y 24 archivos con script incrustado |
| **1.7 + 1.8** | **Fusionadas en un módulo.** Eran la misma lista vista dos veces; separadas se desincronizan |
| **1.10** asistencias | **Separada en dos prioridades** |
| Estilos inline | **A sección 8**, mejoras posteriores al go-live |

### El hallazgo que abarató la CSP

Un estilo inline no ejecuta código, solo pinta. El riesgo real es el JavaScript.
Entonces: `script-src` estricto y `style-src` permisivo. Hay protección contra
inyección **y no se rompe una sola pantalla**. Los 1.054 estilos quedan como están.

Eso baja la tarea de "rehacer el diseño" a "ordenar 24 archivos de JavaScript".

Sacar los estilos inline pasa a ser un problema de **mantenimiento**, no de
seguridad: hoy cambiar un color implica tocar 66 archivos. Va después del go-live y
con Carlos mirando, porque toca vistas y CSS.

### Verificación que corrigió una afirmación mía

Sobre la tarea 1.10 afirmé que se podía marcar asistencia de un alumno ajeno.
Carlos observó que el profesor solo ve a los de su grupo. **Verificado en
`ClaseWebController.php:283`**: la lista se arma con `where('grupo_id', ...)` y solo
alumnos activos. Por pantalla no es alcanzable.

La tarea queda partida: el **guardado a medias es ALTA** —pasa sin que nadie haga
nada raro, y alimenta la liquidación del profesor— y el **alumno ajeno es BAJA**,
porque requiere armar un pedido a mano salteando la interfaz.

### Regla de negocio confirmada

La regla de cuándo aplica un cambio de plan estaba escrita pero sujeta al visto
bueno del cliente. **Vanina lo dio.** Asentado en
`Wings-contrato-estadosAlum-cobranza-asistencia-V1.md`.

Bajar aplica el mes siguiente si ya asistió a alguna clase este mes, y el mes en
curso si no asistió. Subir aplica siempre en el mes en curso.

### Contraseñas de entrega

Se descartó el patrón `V4n1n4*2026`: es el nombre de la persona con sustituciones
típicas más el año, que es el primer patrón que prueban las herramientas, y lo
adivina cualquiera que la conozca.

**Criterio adoptado:** frases de tres palabras del tipo `gimnasia-patin-jueves`.
Más fáciles de recordar y tipear, y más resistentes. Una por persona, sin cambio
periódico obligatorio.

### Estado del bloque 1

Doce tareas revisadas: 3 cerradas, 9 pendientes.

**Bloqueantes:** 1.5 crear-admin, 1.6b CSP, 1.7 configuración, 1.9 FIFO, 1.9b
atomicidad del plan.
**Altas:** 1.6 headers, 1.10 guardado de asistencias, 1.11 login, 1.13 cierre.
**Bajas:** 1.12 tabla muerta.

**Siguiente paso:** decidir si se revisan los bloques 2 a 6 con el mismo método, o
se pasa directo al rediseño del plan por prioridades.

---

## 2026-08-28 — Claude CyE

**Objetivo:** corregir un patrón propio de afirmar hechos sin verificarlos.

**Error 2, detectado por Carlos:** afirmé que el dump exponía datos personales de
36 menores reales. Los alumnos los había creado un seeder anterior: DNI
correlativos desde `30000001`, celulares `11-4500-0001`, emails vacíos, token
llamado `test`. Llegué a la conclusión con un `COUNT` y `SUM(edad<18)`, **sin mirar
una sola fila**. Un `LIMIT 5` lo resolvía; lo corrí recién cuando me corrigió.

**Error 1, mismo patrón:** relaté AUD-021 como crítico heredando la severidad de la
auditoría. Un grep mostraba que solo vive en un controlador de API y la API está
apagada. No era alcanzable.

**Consecuencia real:** decisiones del proyecto discutidas sobre datos falsos, y
tiempo perdido.

**Corrección:** protocolo de verificación agregado a `AGENTS.md` sección 6c, con
los dos casos documentados como ejemplo. Cinco reglas: contar no es mirar;
severidad sin alcanzabilidad es ruido; separar verificado de inferido; reportar en
vez de alarmar; heredar un hallazgo es inferir, no verificar.

**Reclasificación:** **B2 baja de crítico a higiene.** No hay fuga de datos
personales, no hay que limpiar la historia de Git, no hay que rotar nada con
urgencia. La tarea 1.2 sigue valiendo, pero por otros motivos: el día que se carguen
alumnos reales ese archivo empieza a tenerlos, son 500 KB que cambian enteros en cada
commit, y el seeder ya lo reemplaza.

**Siguiente paso:** actualizar el plan con la reclasificación de B2.

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
