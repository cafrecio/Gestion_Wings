# Wings — Reglas para agentes

Este archivo es la puerta de entrada para cualquier agente que trabaje en el repo
(Codex, Claude Code u otro). **Leerlo completo antes de tocar un solo archivo.**

`CLAUDE.md` contiene la guía extendida del proyecto y sigue vigente: todo lo que
dice aplica igual. Este archivo agrega las reglas que no se negocian.

---

## 1. EL DISEÑO NO SE TOCA

El sistema visual de Wings es un activo del proyecto. Se construyó a lo largo de
meses y **no se cambia, no se "mejora" y no se reemplaza**.

### Regla dura

**Ningún agente modifica estos archivos sin pedido explícito y por escrito del
dueño del proyecto:**

- `resources/views/**` — todas las vistas Blade
- `resources/css/app.css` — tokens y componentes del design system
- `resources/views/components/ds/**` — componentes del design system

Si una tarea parece requerir tocarlos: **frenar y preguntar.** No improvisar.

### Por qué esta regla existe

Ninguna de las tareas del plan de producción vigente
(`docs/00-estado/PLAN-PRODUCCION.md`) necesita modificar una vista ni el
CSS. Si un cambio aparece ahí, casi siempre significa que se entendió mal la
tarea.

### Si el dueño autoriza tocar una vista

Leer ANTES, sin excepción:

- `docs/03-diseno-ui/wings-design/SKILL.md`
- `docs/03-diseno-ui/design-system/DESIGN-RULES.md`
- `resources/views/alumnos/index.blade.php` — es la vista de referencia canónica

Y respetar:

- Botones con **un solo verbo corto**: `Nuevo`, `Editar`, `Guardar`, `Cobrar`,
  `Registrar`, `Volver`, `Filtrar`, `Limpiar`.
  Nunca `Cobrar cuota`, `Guardar cambios`, `Nueva clase`, `Registrar movimiento`.
- Mantener `ds-btn`, `x-ds.*`, `alumno-card` y la estructura visual existente.
- **Prohibido hardcodear un color hex en Blade.** Todo color va por token
  (`var(--color-brand)`), según `docs/03-diseno-ui/design-system/tokens.md`.
- No introducir Alpine.js ni Livewire. No agregar frameworks de CSS.
- No reordenar ni "limpiar" markup que ya funciona.

### Verificación obligatoria antes de cerrar cualquier tarea

```bash
git diff --stat -- resources/views resources/css
```

Si devuelve algo y la tarea no era explícitamente de diseño: **revertir.**

---

## 2. Trampa conocida: CSP contra estilos inline

La tarea 1.6 del plan agrega headers de seguridad, entre ellos
`Content-Security-Policy`.

**Las vistas de Wings usan `style="..."` inline y `<script>` inline en todos
lados.** Una CSP estricta de manual (`default-src 'self'` sin `'unsafe-inline'`)
**destruye visualmente la aplicación entera**: se cae el layout, se pierden los
colores y los rails de deporte quedan sin pintar.

### La medida concreta del riesgo — verificada el 28/08

| Qué | Cuánto |
|---|---|
| Atributos `style="..."` escritos a mano en las vistas | **1.054** |
| Archivos de vista que los contienen | **66** |
| Archivos con `<script>` incrustado | **24** |

Una CSP de manual bloquea las 1.054 de una sola vez. La aplicación queda sin
colores, sin layout y sin los rieles de color por deporte.

**Esta es la única tarea del plan capaz de destruir el diseño.** Por eso no la
ejecuta ningún agente sin supervisión, y por eso el modo reporte no es opcional.

### Cómo se implementa la CSP en este proyecto

Obligatorio, en este orden:

1. **Primero en modo reporte.** Usar `Content-Security-Policy-Report-Only`, que
   registra lo que bloquearía pero no bloquea nada.
2. **Recorrer la aplicación completa** con la consola del navegador abierta y
   juntar la lista real de violaciones.
3. **Recién ahí** endurecer, con las excepciones ya conocidas y verificadas.
4. Después de activar el modo bloqueante, **revisar visualmente** las pantallas
   principales: alumnos, caja, cobrar, clases, liquidaciones.

**Nunca activar `Content-Security-Policy` en modo bloqueante en un solo paso.**

Los otros cinco headers (`X-Frame-Options`, `X-Content-Type-Options`,
`Referrer-Policy`, `Permissions-Policy`, `Strict-Transport-Security`) no tienen
este riesgo y se pueden aplicar directo.

---

## 3. Datos y base de datos

### El dump ya no se versiona

`database/dump.sql` contiene datos personales reales de alumnos, pagos, tokens y
sesiones. **No se vuelve a agregar al repositorio.**

- No hacer `git add database/dump.sql`.
- No reinstalar el hook `pre-commit` que lo exportaba.
- Para levantar una base nueva se usa el seeder de catálogos, no el dump.

### Nunca versionar la tabla `users`

Contiene hashes de contraseñas reales. Si por algún motivo hay que exportar la
base, siempre con:

```bash
mysqldump -u root --ignore-table=gestion_wings.users gestion_wings
```

---

## 4. Permisos y roles

**Antes de tocar cualquier control de acceso** (middlewares, `abort(403)`,
`if ($user->isX())`, filtros de listados por usuario) leer
`docs/02-contratos/PERMISOS-ROLES.md`.

El error que se repitió muchas veces: **el OPERATIVO no ve "solo lo suyo".**
El rol define el dominio de trabajo, no la propiedad de los registros. Y el
ADMIN nunca tiene menos poder que el operativo.

Estado verificado el 25/08/2026 sobre 268 pruebas de rutas GET:

| Rol | Acceso |
|---|---|
| ADMIN | 59 rutas |
| OPERATIVO | 19 rutas; las 47 de admin, cerradas |
| PROFESOR | solo `clases` y `clases/{id}` |
| ANÓNIMO | solo `login` |

**Si un cambio altera esta matriz, es un bug hasta que se demuestre lo
contrario.**

---

## 5. Blade: no declarar funciones sueltas

Declarar `function foo()` dentro de un bloque `@php` hace que la vista falle con
error fatal si se renderiza dos veces en el mismo proceso de PHP. No se nota en
producción con PHP-FPM, pero rompe el smoke test y cualquier corrida en CI.

Si hace falta una función en una vista, envolverla:

```php
if (!function_exists('miFuncion')) {
    function miFuncion(...) { ... }
}
```

Ya aplicado en `clases/index.blade.php` y `liquidaciones/index.blade.php`.
Preferible: mover la lógica a un helper o a un componente.

---

## 6. Antes de cerrar cualquier tarea

```bash
php artisan test                                    # 33 tests deben pasar
php -l <cada archivo tocado>                        # sin errores de sintaxis
php artisan view:cache && php artisan view:clear    # las vistas compilan
git diff --stat -- resources/views resources/css    # debe estar vacío
```

Y verificar el criterio de aceptación específico que la tarea declara en
`docs/00-estado/PLAN-PRODUCCION.md`.

---

## 6b. Frenar ante contradicciones — NO improvisar

Si una tarea contradice lo que ves en el código, en los datos o en otro documento:
**pará y preguntá.** No elijas vos la interpretación que parece más razonable.

Esto vale para todo, no solo para diseño:

- El plan pide algo que los datos reales no permiten.
- Dos documentos dicen cosas distintas.
- La tarea parece requerir tocar un archivo prohibido.
- El criterio de aceptación no se puede cumplir como está escrito.
- Encontrás algo que parece un bug pero no está en tu tarea.

Al frenar, reportá tres cosas:

1. Qué dice la tarea.
2. Qué encontraste en el código o en los datos.
3. Las opciones que ves, sin elegir ninguna.

**Por qué existe esta regla:** el proyecto arrastra contradicciones entre
documentación y código. Una decisión improvisada que parece obvia puede deshacer
un arreglo anterior. Ejemplo real: los subrubros `Sueldo - Apellido, Nombre`
parecen datos personales que habría que reemplazar por categorías genéricas, pero
son generados automáticamente por profesor y unificarlos rompería el desglose de
sueldos en el cashflow, reintroduciendo un bug ya corregido.

Frenar nunca es un error. Improvisar sí.

---

## 6c. Protocolo de verificación — antes de afirmar un hecho

Aplica a todos los agentes. Existe porque ya produjo dos decisiones tomadas sobre
datos falsos.

**1. Contar no es mirar.** Nunca concluir sobre datos a partir de un agregado.
Antes de afirmar qué *son* los datos, mirar filas concretas con `LIMIT 5`. Un
`COUNT` responde cuántos, nunca qué.

**2. Severidad sin alcanzabilidad es ruido.** Antes de calificar algo de grave,
comprobar que alguien puede llegar: ¿hay ruta? ¿hay botón? ¿está habilitado el
router? Si no es alcanzable, no es urgente, aunque un documento diga CRÍTICO.

**3. Separar verificado de inferido, explícitamente.** Si no se comprobó, decirlo.
Nunca presentar una inferencia con el tono de un hecho.

**4. Reportar, no alarmar.** Dar el hecho y su alcance; la urgencia la decide el
dueño del proyecto.

**5. Heredar un hallazgo de un documento es inferir, no verificar.** Toda auditoría
o reporte previo se revalida contra el código actual antes de repetirse.

### Los cuatro gatillos — agregados el 30/08/2026

Las cinco reglas de arriba son principios, y los principios se leen y se incumplen igual:
esta sección existía desde el 28/08 y el 30/08 se violó otra vez, frenando dos veces a
Codex. Lo que faltaba no era el enunciado sino el paso mecánico. **Estos cuatro son
acciones, no ideas.**

| Cuándo | Qué hacer, sin excepción |
|---|---|
| Antes de escribir que algo **está implementado, hecho o funciona** | Leer **el cuerpo de la función**. No el nombre, no el docblock, no que se invoque desde el lugar correcto |
| Antes de commitear la **corrección de un documento** | `grep` de todas las menciones del tema en ese documento. Nunca editar de memoria dónde se escribió |
| En **cada afirmación** sobre el código, los datos o el estado del sistema | **No se responde nada sin un chequeo real.** No alcanza con avisar que es una suposición: se comprueba antes de escribirlo, o no se escribe |
| Antes de entregar un **prompt o una orden de trabajo** | Releerla contra el estado real de los documentos y del código que referencia |

**El caso que los originó.** Un comando mostró en pantalla el cuerpo de `validarFifo()`,
incluida la línea `if (count($items) <= 1) return;` que la desactiva. Se leyó esa salida
y se escribió igual "ya implementado", porque se verificó que la función **existiera** y
se **invocara**, y se reportó sobre lo que **hacía**. El plan de producción decía la
verdad y se lo pisó.

De ahí salió un criterio de aceptación imposible de cumplir, y al corregirlo se editaron
tres lugares del documento y se olvidó un cuarto, que quedó contradiciendo al contrato.
**Los dos frenos los detectó Codex, ninguno el autor del error.**

### Los dos casos que originaron esta regla

**AUD-021.** La auditoría lo marcaba CRÍTICO: ajustar una deuda deja plata sin
registrar. Se relató como hecho actual. Un grep mostraba que solo existe en
`PagoCuotaController`, que es de API, y la API está apagada en `bootstrap/app.php`.
No es alcanzable.

**Los 36 alumnos del dump.** Un `COUNT` devolvió "36 con DNI, 36 menores" y se
concluyó que el dump exponía datos de 36 chicos reales. Los DNI eran `30000001`,
`30000002`, `30000003`, los celulares `11-4500-0001` y los emails vacíos. Datos
generados por un seeder. Un `SELECT ... LIMIT 5` lo habría resuelto antes de
plantear el problema.

---

## 6d. Definicion de terminado — que documento acabo de dejar mintiendo

Una tarea no termina cuando el codigo funciona. Termina cuando **todo lo que el cambio
volvio falso quedo corregido**.

Antes de cerrar cualquier tarea, preguntarse: **que documento acabo de dejar
mintiendo**, y arreglarlo en el mismo turno.

Los cuatro que se desactualizan siempre:

| Archivo | Que suele quedar viejo |
|---|---|
| `docs/00-estado/ESTADO-ACTUAL.md` | Cantidad de pruebas, estado de cada modulo, bugs ya cerrados |
| `docs/00-estado/PLAN-PRODUCCION.md` | Tareas marcadas pendientes que ya se hicieron |
| `docs/00-estado/CHECKLIST-CARLOS.md` | Cosas que le piden a Carlos algo que ya entrego |
| El contrato del area tocada | Reglas de negocio que el cambio modifico |

**Detectar que un documento miente y solo reportarlo no cierra la tarea.** Si no se
actualiza, no se actualiza solo, y la proxima orden se escribe sobre informacion falsa.

Eso ya paso: `ESTADO-ACTUAL.md` declaraba 14 pruebas cuando habia 76, y describia como
abierto y pendiente de decision un defecto que ya estaba cerrado.

---

## 7. Reglas generales

1. No tocar lógica funcional si el pedido es solo documental u organizativo.
2. No mover archivos de código para "ordenar" sin pedido explícito.
3. Si se detecta una contradicción entre documentación y código, registrarla en
   `docs/00-estado/ESTADO-ACTUAL.md` antes de implementar.
4. No commitear `.claude/settings.local.json` — es configuración local del dueño.
5. Antes de cambios grandes, revisar `routes/web.php`.
6. La API REST está deshabilitada a propósito en `bootstrap/app.php`. **No
   reactivarla** sin cerrar antes el control de rol de cada grupo de rutas.

---

## 8. Mapa documental

| Necesidad | Ruta |
|---|---|
| **Plan de producción vigente** | `docs/00-estado/PLAN-PRODUCCION.md` |
| **Orden de trabajo de Codex (D1)** | `docs/00-estado/ORDEN-CODEX-D1.md` |
| **Bitácora de Codex** | `docs/00-estado/LOG-CODEX.md` |
| **Bitácora de Claude Code** | `docs/00-estado/LOG-CLAUDE.md` |
| Pasos manuales por máquina y pendientes del dueño | `docs/00-estado/CHECKLIST-CARLOS.md` |
| Estado actual del proyecto | `docs/00-estado/ESTADO-ACTUAL.md` |
| Permisos y roles | `docs/02-contratos/PERMISOS-ROLES.md` |
| Design system | `docs/03-diseno-ui/` |
| Contratos de negocio | `docs/02-contratos/` |
| Pruebas funcionales | `docs/06-pruebas/` |
| Evaluación integral | `docs/07-evaluacion/index.html` |
| Guía extendida | `CLAUDE.md` |

---

## 9. Bitácoras compartidas entre computadoras

Los chats son locales y no sirven como memoria compartida. La continuidad del
proyecto se registra en el repositorio.

**Hay dos bitácoras, una por agente:**

| Agente | Archivo |
|---|---|
| Codex | `docs/00-estado/LOG-CODEX.md` |
| Claude Code | `docs/00-estado/LOG-CLAUDE.md` |

Están separadas a propósito: cada agente escribe en la suya y así no se pisan al
trabajar en paralelo.

**Cada agente lee las dos y escribe solo en la suya.**

Identidad obligatoria al firmar cada entrada, según la computadora:

| Computadora | Codex firma | Claude firma |
|---|---|---|
| CyE | **Codex CyE** | **Claude CyE** |
| Casa de Carlos | **Codex CAB** | **Claude CAB** |

Antes de empezar una tarea, leer ambas bitácoras. Antes de cerrar una tarea que
haya producido cambios, agregar una entrada breve con: objetivo, cambios reales,
decisiones, verificaciones y pendiente siguiente.

**Entradas más nuevas arriba.** No registrar contraseñas, tokens, datos personales
ni información sensible.

### Obligación específica de Claude Code

Claude verifica el trabajo que reporta Codex. **Esa verificación se registra en
`LOG-CLAUDE.md` con el resultado real, no con lo que dijo el reporte** — incluyendo
cuando la verificación rechaza una tarea y por qué.

Motivo: ya pasó una vez. La tarea 1.3 se reportó terminada y la verificación
encontró que el criterio se había validado con `--class=CatalogosSeeder` en lugar
del `migrate --seed` literal, y que el defecto seguía presente.
