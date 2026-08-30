# Orden de trabajo — Codex — D1B (domingo 30/08)

> **Leer `AGENTS.md` completo antes de tocar un archivo.** No se repite acá.
> Reemplaza a `ORDEN-CODEX-D1.md` para lo que quedó pendiente del bloque 1.

Este lote está armado para ejecutarse **sin supervisión**: mientras Codex lo hace,
Carlos y Claude están revisando los bloques 2 a 6. Cada tarea trae su criterio de
aceptación cerrado para que no haga falta preguntar.

**Si algo contradice lo que ves en el código, frená y anotalo en `LOG-CODEX.md`
en vez de decidir por tu cuenta.** Regla §6b de `AGENTS.md`.

---

## Límites de este lote

**No tocar** — ninguna tarea lo necesita, y varios los estamos editando en paralelo:

| Archivo o carpeta | Motivo |
|---|---|
| `resources/views/**` | `AGENTS.md` §1 |
| `resources/css/app.css` | `AGENTS.md` §1 |
| `resources/views/components/ds/**` | `AGENTS.md` §1 |
| `docs/00-estado/PLAN-PRODUCCION.md` | **Lo estamos reescribiendo ahora** |
| `docs/07-evaluacion/**` | Idem |
| `docs/06-pruebas/DATASET-SEEDER-V1.md` | El seeder va al final, no ahora |

**Excepción acotada:** la tarea 1.6 necesita registrar un middleware en
`bootstrap/app.php`. Se permite **agregar el alias y el append al grupo `web`**, nada
más. **La línea de la API sigue comentada.** Reactivarla es un error.

Antes de cerrar cada tarea:

```bash
git diff --stat -- resources/views resources/css   # debe estar vacío
php artisan test                                    # 38 tests deben pasar
php -l <cada archivo tocado>
```

**Un commit por tarea.** El mensaje dice qué riesgo cierra, no qué archivos tocó.

---

## Estado verificado al 30/08

Verificado leyendo código y base, no heredado de un documento:

| # | Tarea | Estado real |
|---|---|---|
| 1.1 | Hook `pre-commit` | **Cerrada.** En CAB solo existe el `.sample` |
| 1.3 | `CatalogosSeeder` | **Cerrada** |
| 1.4 | Sanear seeders | **Cerrada** |
| 1.9 | FIFO fuerte | **CORREGIDO 30/08 — sigue abierto.** Ver más abajo |
| 1.12 | Limpiar `formas_pago` | **Sin trabajo.** La tabla ya no existe en la base |
| 1.11 | Throttle de login | **Parcial.** Hay `throttle:10,1`; el plan pide `5,1` |
| 1.5 | `wings:crear-admin` | **No existe.** Solo está `GenerarDeudasMensualesCommand` |
| 1.6 | Headers de seguridad | **No existe** ningún middleware de headers |
| 1.7 | Configuración de producción | **No existe** `.env.production.example` ni `wings:preflight` |
| 1.10 | Asistencias todo-o-nada | **Pendiente.** El guardado no está en transacción |
| 1.9b | Atomicidad del cambio de plan | **Pendiente y ya alcanzable** — ver abajo |

### Corrección del 30/08 — el FIFO NO está resuelto

En la primera versión de esta orden di 1.9 por cerrada. **Estaba mal.** Verifiqué que
`validarFifo()` existía y que se invocaba, y di por hecho lo que validaba sin leer qué
hacía. Codex lo encontró al intentar escribir el test de A1.

`PagoCuotaService.php:442-444`:

```php
if (count($items) <= 1) {
    return;
}
```

La función **solo controla el orden entre los períodos que se cobran juntos.** Nunca
mira las deudas que quedaron afuera del cobro. Si un alumno debe mayo y junio y se
cobra únicamente junio, llega un solo ítem, la función se va sin validar y el pago se
acepta con mayo impago.

Es exactamente el hallazgo **B6 "FIFO evadible"** del plan, que citaba estas mismas
líneas como evidencia. Sigue abierto y es alcanzable desde la pantalla de cobro, donde
el operativo elige qué períodos paga.

**Carlos ya lo definió el 30/08, y no es lo que proponía el plan.** El cobro de un mes
suelto **no se bloquea**: se avisa, se pide motivo y se notifica al administrador. La
regla quedó escrita en `Wings-contrato-estadosAlum-cobranza-asistencia-V1.md` §9b y se
implementa en la tarea **A2** de esta orden.

**No convertir esto en un bloqueo.** Es una decisión tomada, no un defecto pendiente.

---

# Bloque A — la plata. Va primero.

## A1 · 1.9b — Atomicidad del cambio de plan · 2 h · cierra B12

**Por qué es urgente ahora y no antes.** El plan decía que 1.9 y 1.9b iban juntas,
porque arreglar el FIFO aumenta los pagos rechazados y cada rechazo deja un cambio de
plan huérfano. **1.9 se implementó y 1.9b no.** El sistema quedó justo en el estado
que el plan advertía que había que evitar.

**El defecto, verificado en `app/Http/Controllers/CajaWebController.php`:**

- Línea **631**: se crea el `AlumnoPlan` nuevo.
- Líneas **644-648**: se reescribe `monto_original` de la deuda del mes en curso.
- Líneas **673-687**: recién ahí se intenta el pago, dentro de un `try/catch` que
  devuelve el error **sin revertir nada**.

No hay ninguna transacción en el archivo. Si el pago falla —y el FIFO ahora lo hace
fallar— el operativo ve "no se registró el pago", pero el alumno **ya quedó con el
plan cambiado y la deuda del mes reescrita**.

**Qué hacer:** el cambio de plan, la reescritura de la deuda y el registro del pago
tienen que ser **una sola unidad**. O pasan los tres, o no pasa ninguno.

- Envolver el tramo completo en un único `DB::transaction`.
- La excepción tiene que **propagarse** para que la transacción revierta. Hoy el
  `catch` la absorbe adentro: si el `try/catch` queda dentro de la transacción, no
  revierte nada. El manejo del error va **por fuera**.
- El servicio de pago abre su propia transacción: anidada usa savepoints, no es
  problema.
- No cambiar la regla de negocio del cambio de plan. Bajar con asistencia del mes
  aplica al mes siguiente; sin asistencia, al mes en curso; subir siempre al mes en
  curso. Está en `Wings-contrato-estadosAlum-cobranza-asistencia-V1.md`.

**Aceptación — test de regresión obligatorio.**

> **Corrección del 30/08.** La versión anterior de este criterio pedía disparar el
> fallo con una deuda vieja impaga cobrando solo el período nuevo. **Ese criterio
> estaba mal**: el FIFO actual no valida ese caso, así que el pago se acepta y el
> test nunca llega a probar la atomicidad. Codex lo detectó y frenó el lote, que es
> exactamente lo que corresponde. El defecto del FIFO es real y quedó abierto como
> tarea aparte; **no bloquea esta**.

Lo que hay que probar acá es **una sola cosa: que si el pago falla, no queda nada
escrito.** Da igual por qué falla.

Por eso el disparador del fallo es libre y lo elegís vos. Lo más limpio es forzar
que el servicio de pago lance una excepción —con un doble o un mock de
`PagoCuotaService`— en vez de depender de una regla de negocio concreta. Así el test
prueba la transacción y no una validación que mañana puede cambiar.

Con el pago fallando, verificar que **después del fallo**:

- no se creó ninguna fila nueva en `alumno_planes`;
- `deuda_cuotas.monto_original` del mes en curso quedó **igual que antes**;
- no se creó ningún `pago` ni `movimiento_operativo`.

Si el test pasa contra el código actual, está mal escrito: hoy el cambio de plan
persiste igual.

---

## A2 · Aviso al cobrar dejando deuda anterior · 1.5 h · cierra B6 · **depende de A1**

Regla nueva, definida por Carlos el 30/08 y escrita en
`Wings-contrato-estadosAlum-cobranza-asistencia-V1.md` **§9b**. Leerla antes de
implementar.

**Depende de A1 porque toca el mismo método** (`CajaWebController::pagar()`). No
arrancar hasta tener A1 commiteada.

**Qué hay que hacer.** Al confirmar un cobro, detectar si queda pendiente **algún mes
anterior** al más viejo de los que se están pagando. Si lo hay:

1. **No guardar todavía.** Devolver el aviso con la cantidad de meses, los períodos y
   el monto total pendiente.
2. Si el operativo confirma, **exigir un motivo** y recién ahí registrar el cobro.
3. **Notificar al administrador**, igual que el resto de las excepciones de §8.

**No bloquear el cobro.** Es una decisión tomada: se avisa, no se impide.

**Ya existe un patrón para esto en el repo:** el aviso de liquidación ya pagada, que
responde HTTP 409 con `requiere_confirmacion`. Usar el mismo mecanismo en vez de
inventar otro.

### Límite importante

La parte de **backend entra en este lote**: detección, respuesta de confirmación,
guardado del motivo y notificación al administrador.

**La parte visual NO.** Mostrar el aviso y pedir el motivo en pantalla toca
`resources/views/**`, que está prohibido por `AGENTS.md` §1. Carlos tiene que
autorizarlo por separado.

Dejá el backend cerrado y funcionando, con el 409 comprobado por test, y anotá en
`LOG-CODEX.md` que la vista quedó pendiente de autorización.

*Aceptación:* alumno que debe marzo, mayo y junio. Cobrar solo junio devuelve el aviso
con **2 meses anteriores** y el monto sumado de marzo y mayo, y **no escribe nada**.
Reintentar con motivo registra el cobro y deja el motivo guardado. Cobrar los tres
juntos no dispara ningún aviso.

---

# Bloque B — lo que falta para poder desplegar

## B1 · 1.5 — Comando `wings:crear-admin` · 30 min · cierra B1

No existe. **Y `CHECKLIST-CARLOS.md` sección A3 ya le dice a Carlos que lo corra**,
así que hoy esa instrucción falla.

Comando de consola que crea una cuenta ADMIN pidiendo la contraseña por consola.

- La contraseña **se pide interactivamente y no se muestra**. Nunca por argumento:
  quedaría en el historial del shell.
- Rechazar contraseñas débiles con un mínimo razonable de longitud.
- Si ya existe un usuario con ese email, avisar y no duplicar.
- Sirve para el arranque en un servidor donde no hay ninguna cuenta.

*Aceptación:* sobre una base recién migrada y sembrada con `CatalogosSeeder`
—que deja **cero usuarios**— el comando deja exactamente un ADMIN que puede iniciar
sesión. Correrlo dos veces con el mismo email no crea un segundo usuario.

---

## B2 · 1.7 — Módulo de configuración de producción · 1.5 h · cierra B4

**Van juntas y en un solo commit:** la plantilla `.env.production.example` y el
comando `wings:preflight`. Separadas se desincronizan, y un chequeo que da verde
sobre una configuración incompleta es peor que no tener chequeo.

Los nueve valores y qué pasa si falla cada uno están en la tabla de la sección 1.7
de `PLAN-PRODUCCION.md`. **Leer esa tabla, no reinventarla.**

Dos puntos que se olvidan:

- **La cookie de sesión va limitada al subdominio.** `SESSION_DOMAIN` vacío. Si se
  pone el dominio padre, la sesión de un cliente valdría en el sistema de otro.
- Las dos últimas verificaciones —que no exista ninguna cuenta de prueba y que
  exista al menos un administrador— **no son valores de configuración**: viven solo
  en el comando.

**Regla permanente:** cada valor que se agregue a la plantilla se agrega, en el mismo
commit, al comando que lo verifica.

*Aceptación:* el comando sale con **código distinto de cero** cuando algo está mal,
para que un `deploy.sh` se corte solo. Probarlo con al menos un valor mal puesto y
confirmar el código de salida.

---

## B3 · 1.6 — Los cinco headers sin riesgo · 45 min · cierra B5

`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`,
`Permissions-Policy` y `Strict-Transport-Security`.

**Estos cinco no rompen nada y van directo.**

> **La CSP NO entra en este lote.** Es la tarea 1.6b y es la única capaz de destruir
> el diseño: hay 1.054 estilos inline en 66 vistas. Requiere modo reporte, recorrido
> visual y supervisión. Ver `AGENTS.md` §2. **No la ejecutes.**

*Aceptación:* los cinco headers presentes en la respuesta de cualquier ruta web.
HSTS solo tiene efecto bajo HTTPS, así que en local se verifica que esté emitido.

---

# Bloque C — endurecimiento acotado

## C1 · 1.10 — Asistencias todo-o-nada · 1 h · cierra B7

En `ClaseWebController::storeAsistencias()`, la validación previa ya está bien
resuelta: hay una primera pasada que junta errores antes de guardar. **Lo que falta
es que el guardado en sí sea atómico** — el `foreach` de la segunda pasada, a partir
de la línea 374, no está en transacción. Si falla a mitad, quedan asistencias
parciales, y eso alimenta la liquidación del profesor.

- Envolver la segunda pasada en `DB::transaction`.
- **Prioridad ALTA.**

Y la parte de prioridad **BAJA**, en el mismo commit: validar que cada
`alumno_id` recibido pertenezca al grupo de la clase. Por pantalla no es alcanzable
—la lista se arma filtrando por grupo— pero un pedido armado a mano lo saltea.

*Aceptación:* un fallo forzado en el medio del guardado no deja ninguna asistencia
escrita. Un `alumno_id` de otro grupo se rechaza con 422.

---

## C2 · 1.11 — Endurecer el throttle del login · 10 min

Hoy `routes/web.php:31` tiene `throttle:10,1`. El plan pide `throttle:5,1`.

*Aceptación:* al sexto intento fallido en un minuto, respuesta 429.

---

## C3 · Cerrar 1.9 y 1.12 sin trabajo · 20 min

No requieren código. Solo verificar y dejarlo asentado en `LOG-CODEX.md`:

- **1.9 FIFO:** confirmar que `validarFifo()` cubre las dos rutas de pago y que
  rechaza el caso de deuda vieja impaga con cobro del período nuevo.
- **1.12 `formas_pago`:** la tabla ya no existe en la base. Confirmar que tampoco
  quedan referencias en el código.

---

# Fuera de este lote

| # | Tarea | Por qué no va ahora |
|---|---|---|
| 1.6b | CSP | 4 h y es la única que puede destruir el diseño. Necesita modo reporte y recorrido visual acompañado |
| 1.2 | Sacar `dump.sql` | Movida al go-live como paso 7.3b, antes de que existan datos reales |
| 1.13 | Cierre del bloque | Es el cierre: va cuando esté todo lo anterior |
| 3.0 | Seeder de prueba | Se construye al final, con el sistema ya funcionando |

---

# Orden y tiempo

| Orden | Tarea | Est. |
|---|---|---|
| 1 | A1 · 1.9b atomicidad del cambio de plan | 2 h |
| 2 | A2 · aviso al cobrar dejando deuda anterior (solo backend) | 1.5 h |
| 3 | B1 · 1.5 `wings:crear-admin` | 30 min |
| 3 | B2 · 1.7 configuración de producción | 1.5 h |
| 4 | B3 · 1.6 los cinco headers | 45 min |
| 5 | C1 · 1.10 asistencias todo-o-nada | 1 h |
| 6 | C2 · 1.11 throttle | 10 min |
| 7 | C3 · cerrar 1.9 y 1.12 | 20 min |
| | **Total** | **≈ 6 h** |

**A1 va primero** porque es plata y ya es alcanzable. El resto puede reordenarse si
algo se traba, salvo que B2 conviene después de B1: el chequeo de "existe al menos un
administrador" se prueba mejor con el comando que lo crea ya hecho.

Al cerrar el lote, entrada en `LOG-CODEX.md` con lo hecho, lo que quedó afuera y
cualquier contradicción encontrada.
