# Evaluación integral — Wings — 08/09/2026

> Hecha por Claude CAB sobre `main` en `97fb840`, con cuatro auditores en paralelo:
> sistema, seguridad, promesas al usuario y funcionalidad faltante.
> Cada hallazgo dice **VERIFICADO** (se leyó el cuerpo del código o se consultó la
> base) o **INFERIDO**. Lo que no se comprobó, se dice.
>
> **Esta evaluación no cambió una sola línea de código.**

---

> **Actualizado el 08/09 tras cruzar con la evaluación de Codex.** Lo que él encontró
> y yo no está incorporado abajo, verificado por mi cuenta antes de repetirlo. Lo que
> él afirma y no corresponde está en la sección final, *Dónde no coincidimos*.

---

## Lo que hay que mirar antes que nada

Cuatro cosas mueven mal la plata y **ninguna avisa**. Todas terminan con el cartel
verde de "listo".

### 0. Un cobro parcial con descuento borra deuda

**VERIFICADO. Lo encontró Codex; lo comprobé leyendo el código antes de repetirlo.**

Cuando corresponde el descuento de primera cuota, el sistema ajusta el monto de la
deuda para que quede saldada con el importe rebajado. El problema es que ese ajuste
recorre **todos los períodos del cobro**, no solo el que lleva el descuento, y le pisa
el monto original con lo que se está pagando.

Alumno nuevo que debe agosto y septiembre, $40.000 cada uno. Le corresponde el 70% en
agosto. El operativo cobra agosto ($28.000) y le toma **$10.000 a cuenta** de
septiembre:

```
septiembre valía        $40.000
se cobran               $10.000
queda valiendo          $10.000   ← el ajuste le pisa el monto original
estado                  PAGADA
desaparecen             $30.000   ← sin que nadie los cobre
```

La guarda que existe (`monto_pagado < monto nuevo`) no lo frena, porque el pago todavía
es cero. Y no hay ningún aviso: la deuda simplemente deja de existir.

*Archivos:* `app/Services/PagoCuotaService.php:50-53, 658-668`,
`app/Http/Controllers/CajaWebController.php:745`.

**Para que ocurra hacen falta tres cosas juntas:** que sea el primer pago, que aplique
descuento y que se cobre un parcial de otro mes. Las tres se hacen desde la misma
pantalla.

### 1. Un cobro de $30.000 se puede registrar como $30

**VERIFICADO en el código y en el comportamiento de PHP. Falta la prueba de pantalla.**

El campo del monto se muestra formateado con punto de miles: `30.000`. Un script se
encarga de sacarle el punto antes de enviarlo, pero se engancha al mismo evento
`submit` que el script de la propia pantalla — y ese corre primero, arma el paquete de
datos con el punto todavía puesto y cancela el envío normal.

Al servidor le llega el texto `"30.000"`. Y acá está lo que lo vuelve grave:

```
is_numeric("30.000")   →  true     ← pasa la validación
(float) "30.000"       →  30       ← treinta pesos
is_numeric("1.234.567") → false    ← con dos puntos, la rechaza
```

**Los montos de cuatro a seis dígitos pasan en silencio y se registran mil veces más
chicos.** Las cuotas del club van de $28.000 a $50.000: todas caen en ese rango.

Consecuencia: se registra un pago de $30, la deuda queda en $29.970, el socio se va
con su recibo y el sistema lo sigue mostrando como deudor. La caja del día no cierra
y nadie sabe por qué.

Las pruebas automáticas no lo agarran porque mandan números crudos (`20000`), no el
texto formateado que manda el navegador.

*Archivos:* `resources/views/caja/cobrar.blade.php:126-134, 355-358`,
`resources/js/ds-app.js:145-150`, `app/Http/Controllers/CajaWebController.php:645`.

**Lo que falta para cerrarlo:** abrir la pantalla, cobrar una cuota y mirar qué quedó
en la base. Es un clic.

### 2. Cambiar el plan al cobrar no cambia nada

**VERIFICADO y cerrado.**

En la pantalla de cobro se puede elegir un plan nuevo — de 3 veces por semana a 2, por
ejemplo. El monto se actualiza en pantalla, se cobra, sale "Pago registrado".

El alumno queda con el plan viejo. Los botones de plan están **fuera del formulario**
(línea 54; el formulario abre en la 83), no tienen el atributo que los ataría a él, y
el JavaScript solo los usa para pintar el recuadro y recalcular el número que se
muestra. Nunca los agrega al envío.

El bloque del servidor que crea el plan nuevo y reescribe la cuota del mes existe y
está bien escrito: **nunca se ejecuta**, porque el dato no llega.

Consecuencia: el mes siguiente se le vuelve a generar la cuota cara. Sin error, con
mensaje de éxito.

*Archivos:* `resources/views/caja/cobrar.blade.php:54, 83, 207, 383-400`,
`app/Http/Controllers/CajaWebController.php:693-733`.

### 3. Cancelar un cobro le saca al alumno el descuento de primera cuota

**VERIFICADO.**

El chequeo de "¿este alumno ya pagó alguna vez?" no filtra los pagos anulados.

Alumno se anota el 24, le corresponde el 40% de descuento por entrar a fin de mes. El
operativo le cobra, se equivoca de medio de pago, cancela el movimiento y vuelve a
cobrar. En el segundo intento el sistema ve el pago anulado, concluye que ya pagó
antes, y **le cobra el mes entero**. La pantalla muestra el importe completo como si
correspondiera.

*Archivos:* `app/Services/PagoCuotaService.php:587`,
`app/Http/Controllers/CajaWebController.php:600`.

---

## Sistema

### La base que le entregamos al cliente no se puede reproducir

**VERIFICADO.**

La base del servidor se vació a mano el 07/09 con un script que quedó en una carpeta
temporal de una sola máquina, **sin versionar**. No hay seeder ni comando que produzca
el estado de entrega. Si hay que rehacerla, hay que repetir el borrado de memoria.

Y el camino que `CLAUDE.md` recomienda para una base nueva —`migrate --seed`— produce
otra cosa: 8 rubros, 15 subrubros, 5 tipos de caja, 2 deportes y 3 niveles.

### El seeder desprotege los rubros del sistema

**VERIFICADO. Ya estaba anotado, se confirma que sigue igual.**

`CatalogosSeeder` escribe `es_reservado_sistema = false` en los 8 rubros, incluidos
`Cuotas` y `Sueldos`, en cada corrida. Sobre una base existente, **desmarca** lo que ya
estaba protegido.

Sin ese flag, un admin puede cambiar `Cuotas` de INGRESO a EGRESO — y toda la
recaudación pasa a contarse como gasto, en la caja y en el cashflow — o renombrar
`Sueldos`, que rompe el alta de profesores y de operativos.

`RubroReservadoTest` no lo detecta: pone el flag a mano y prueba el candado, no de
dónde sale la llave.

### El pago de una liquidación se puede duplicar

**VERIFICADO.**

Es el único lugar del dominio de plata que crea un asiento sin bloquear la fila, y el
índice que debería impedir el duplicado no es único. Doble clic, refresh del
formulario o el botón de atrás y quedan **dos egresos por el mismo sueldo**.

*Archivo:* `app/Services/LiquidacionPagoService.php:31, 72-76, 104-113`.

### Las fechas de los movimientos no tienen tope

**VERIFICADO.**

Un movimiento se puede fechar en 2024 o en 2030. Queda dentro de la caja de hoy pero
se imputa al cashflow con esa fecha. Entonces cambia el resultado de un mes ya cerrado,
y desaparece del historial de caja, que solo muestra 90 días. Caja y cashflow dejan de
cerrar entre sí.

*Archivos:* `CajaWebController.php:314, 371, 646`.

### Resolver una revisión como "Inactivo" condona sin motivo

**VERIFICADO.**

El camino normal de condonación exige motivo de 10 a 500 caracteres y es solo del
admin. Este atajo perdona la deuda del mes anterior con un clic en un desplegable, sin
motivo, sin mirar si esa deuda ya tenía un pago parcial imputado, y **pisando** las
observaciones anteriores en vez de agregarse a ellas.

Y resolver como "Continúa" genera la deuda con el **precio de hoy**, no el del período
que se está resolviendo.

*Archivo:* `app/Services/RevisionCobranzaService.php:37-60`.

### Cancelar un cobro borra el rastro de a qué se había imputado

**VERIFICADO.**

Las filas que decían a qué período fue cada peso se eliminan físicamente. El recibo del
pago anulado sale **sin períodos**, porque los lee de esa tabla ya vacía.

*Archivo:* `app/Services/PagoCuotaService.php:762`.

### Condonar y ajustar deuda corren sin transacción ni bloqueo

**VERIFICADO.**

Todo el resto del servicio bloquea. Estos dos no. Si el admin condona mientras el
operativo cobra esa misma cuota, queda una deuda condonada con monto pagado y un
movimiento de caja cobrado contra ella.

*Archivo:* `app/Services/PagoCuotaService.php:217-277`.

### La prueba de concurrencia no prueba concurrencia

**VERIFICADO.**

`MoneyLockingTest` abre los archivos de los servicios como texto y busca con una
expresión regular que esté escrita la palabra `lockForUpdate()`. No abre una conexión,
no lanza dos transacciones, no prueba nada. Pasaría igual si el bloqueo estuviera
puesto sobre la fila equivocada, o si alguien sacara la transacción que lo envuelve.

### Cinco servicios de plata sin una sola prueba

**VERIFICADO.**

| Servicio | Qué hace | Pruebas |
|---|---|---:|
| `LiquidacionPagoService` | Le paga a los profesores | **0** |
| `ReciboService` | Los comprobantes | **0** |
| `PagoService` | Pago de plan mensual | **0** |
| `CashflowService` | Movimientos de cashflow | **0** |
| `RevisionCobranzaService` | Da de baja alumnos y condona deuda | **0** |

El único test que menciona `LiquidacionService` prueba **el nombre del grupo** en el
detalle, no el cálculo del monto. Dicho de otro modo: **lo que el club le paga a sus
profesores no está cubierto por ninguna prueba.**

### No hay integración continua

**VERIFICADO.** No existe `.github/workflows`. Las 129 pruebas corren solo si alguien
se acuerda de correrlas antes de commitear.

### La deuda mensual solo se genera si alguien cargó asistencias

**VERIFICADO. Es el diseño, no un defecto — pero es el punto de falla más grande del
mes.**

Si en septiembre no se cargan asistencias, el 1 de octubre a las 06:00 la corrida
produce **cero deudas** y una revisión pendiente por alumno. Un socio que paga hace dos
años pero que nadie marcó presente entra igual en esa lista.

Y no hay ninguna alerta: la tarea manda su salida a `/dev/null` y el servidor **no
tiene monitoreo** (verificado por SSH el 08/09: cero servicios corriendo). Si falla, el
club se entera cuando nadie tenga cuota que pagar.

---

## Seguridad

**Titular: no hay nada crítico ni explotable de forma anónima.** Se verificó que la API
está apagada (129 rutas activas, ninguna de `api/`), que no hay XSS ni inyección SQL,
que los recibos no son alcanzables por URL y que el mass assignment está cerrado. Todo
lo que sigue es posterior a tener sesión.

### Las dependencias de JavaScript no están limpias

**VERIFICADO — corrige una afirmación equivocada de mi propia auditoría.**

Mi auditor de seguridad reportó `npm audit: 0 vulnerabilidades`. **Es falso.** Codex lo
marcó y lo volví a correr yo mismo:

```
11 vulnerabilities (1 low, 1 moderate, 7 high, 2 critical)
```

Afectan a `vite`, `axios`, `esbuild`, `postcss`, `rollup`, `shell-quote`,
`follow-redirects`, `form-data`, `nanoid`, `picomatch` y `concurrently`. Varias son
herramientas de desarrollo y no viajan al navegador; `axios` sí. Ninguna está
demostrada como explotable en Wings, pero **la afirmación de que estaba todo limpio era
incorrecta y hay que corregirla.**

`composer audit` sí está limpio: cero avisos en PHP.

### Cambiar la contraseña no expulsa a nadie

**VERIFICADO.** Si una clave se filtró o se compartió con alguien que ya no debe
tenerla, cambiarla **no cierra su sesión**. Y si esa persona tildó "Recordarme", la
cookie lo vuelve a autenticar durante **cinco años**, aunque la contraseña ya no exista.

Desactivar al usuario sí lo echa en el request siguiente, y bajarle el rol también. El
agujero es específico del cambio de contraseña.

*Archivo:* `app/Http/Controllers/UsuarioWebController.php:157-166`.

### El preflight de producción corre después de abrir el sitio

**VERIFICADO.** En `scripts/deploy.sh` el orden es: salir de mantenimiento, y recién
después verificar producción. Si un despliegue sube con el modo depuración encendido,
hay una ventana con el sitio **público mostrando credenciales de base en cada error**.
El control existe y es bueno; está en el lugar equivocado.

*Archivo:* `scripts/deploy.sh:220-221`.

### Un cobro adelantado no tiene tope

**VERIFICADO.** Cuando el período ya tiene deuda, el monto se limita al saldo. Cuando no
—un pago por adelantado— entra crudo, sin techo contra el precio del plan. Y el período
acepta cualquier cosa: `2099-99` pasa la validación.

*Archivo:* `CajaWebController.php:752-763`.

### La contraseña web pide 8 caracteres; la de consola, 12

**VERIFICADO.** `Password::min(8)` pelado, sin exigir mayúsculas, números ni chequeo
contra filtraciones conocidas. Un admin creado desde la pantalla puede tener `12345678`.
La regla más fuerte está en el camino que menos se usa.

### El bloqueo del login es solo por IP

**VERIFICADO.** Cinco intentos por minuto por IP, sin contador por cuenta ni bloqueo. Sin
segundo factor, la contraseña es lo único. Un ataque que rote direcciones sigue siendo
posible, aunque es ruidoso y caro.

### Los recibos filtran el mensaje de error crudo

**VERIFICADO.** El `catch` devuelve el texto de la excepción al navegador. Eso **no lo
tapa** apagar el modo depuración, porque es una respuesta explícita del controlador.

*Archivo:* `app/Http/Controllers/ReciboController.php:83, 150`.

### Los respaldos garantizan menos de lo que anuncian

**Aporte de Codex. VERIFICADO leyendo los scripts.** Ninguno de mis cuatro auditores
miró los respaldos; fue el hueco más grande de mi evaluación.

Tres cosas distintas, que juntas cambian lo que significa "la restauración fue
probada":

- **Restaurar no reconstruye Wings.** El respaldo guarda la configuración y los
  archivos de `storage`, pero el comando de restauración **solo importa la base**. Tras
  perder el servidor, los archivos y la configuración siguen dentro del paquete: hay
  que sacarlos a mano. Nadie los perdió, pero la restauración automática es parcial.
- **El ensayo compara cantidades, no contenido.** Verifica que 15 tablas tengan la misma
  cantidad de filas. Dos bases con los mismos pagos pero **importes distintos** pasan
  ese control. Y omite la tabla de imputaciones. El mensaje final dice que se restauró
  completo.
- **La copia externa puede fallar y el script termina bien.** Si la subida al Drive
  falla, se imprime un aviso y el resultado final sigue siendo éxito. Es deliberado —
  para no perder el respaldo local — pero quien supervisa puede leer "ok" y creer que
  también quedó una copia afuera.

Sumado a que el servidor **no tiene monitoreo** (verificado por SSH hoy: cero
servicios), el resultado es que nadie se entera si la copia externa deja de funcionar.

### Sin rastro de las acciones sensibles del admin

**VERIFICADO.** Condonar una deuda, cambiar el rol de alguien o cambiarle la contraseña a
otro usuario no dejan registro de quién ni cuándo. La plata sí está trazada (los
movimientos guardan usuario y las cancelaciones piden motivo); la administración de
identidades, no.

---

## Lo que le prometemos al usuario y no cumplimos

### La lista de deudores no dice cuánto, ni desde cuándo, ni el teléfono

**VERIFICADO.** Cinco columnas: alumno, deporte, grupo, estado y "Ver". Ni un peso, ni un
contacto. Para armar la ronda de llamadas hay que abrir las fichas de a una.

Peor: el único botón lleva a la ficha del alumno, **y la ficha no tiene botón Cobrar**.
El flujo se corta justo en el paso que importa.

### Un alumno que no debe un peso figura "Deudor"

**VERIFICADO.** El sistema pregunta "¿pagó alguna vez acá?", no "¿debe plata?". Entonces:

- Un alumno recién inscripto aparece en rojo el mismo día.
- Un alumno al que le condonaron su única deuda queda **Deudor para siempre**.
- Todos los que vengan de una carga inicial arrancan en rojo.

Y el número grande de "Deudores" y el indicador del dashboard **están inflados con gente
que no debe nada**. La dueña decide sobre un número que miente y el operativo llama a
reclamarle plata a quien está al día.

### El "balance" del cashflow suma peras con manzanas

**VERIFICADO.** Se filtra por un mes y el balance mezcla los ingresos y egresos de ese mes
con el **saldo inicial histórico de todos los tipos de caja**, incluidos los
desactivados. No es el saldo de hoy ni el resultado del mes. Es un número sin
significado presentado como balance, en la pantalla del "cuánta plata tengo".

*Archivos:* `cashflow/index.blade.php:10`, `CashflowWebController.php:45-48`.

### El cashflow solo cuenta las cajas validadas, y no lo dice

**VERIFICADO.** Si la dueña no validó las cajas del mes, el total que ve está incompleto y
**nada se lo advierte**. Lo mismo al pagar una liquidación: puede leer "saldo
insuficiente en Efectivo" con el cajón lleno, porque esa plata todavía no está validada.

### Una caja sin validar desaparece el día 1

**VERIFICADO.** El listado de caja filtra por mes y arranca en el mes actual. Una caja de
agosto que nunca se validó **deja de verse el 1 de septiembre**, y no hay ninguna otra
pantalla que la muestre. Plata en el limbo, sin alerta.

### El dashboard de la dueña no contesta ninguna de sus preguntas

**VERIFICADO.** Cuatro contadores de alumnos y deuda, y tres accesos rápidos, dos de los
cuales son catálogos que se tocan una vez al armar el club. No hay saldo, ni cobrado
del mes, ni aviso de cajas esperando validación, ni de liquidaciones abiertas, ni de
revisiones pendientes.

El contraste es incómodo: **el dashboard del operativo sí tiene todo eso**. La
recepcionista tiene un tablero; la dueña tiene cuatro números.

### El recibo no está a mano cuando el socio lo pide

**VERIFICADO.** Después de cobrar se vuelve al listado sin ningún link al comprobante. El
botón existe solo dentro del detalle de la caja del día. Si el papá vuelve la semana que
viene hay que acordarse en qué día pagó: el historial de la ficha muestra los últimos 8
pagos **sin link al recibo**.

Además el recibo resuelve el medio de cobro por **búsqueda difusa** —texto parecido,
monto igual, misma fecha— teniendo el identificador del pago guardado al lado. Con dos
cobros iguales el mismo día puede tomar el equivocado, y si no encuentra nada escribe
"Medio de cobro: N/D" sin explicar por qué.

### Lo que se hace mal no se puede deshacer

**VERIFICADO.**

| Acción | ¿Se deshace? | ¿Avisa antes? |
|---|---|---|
| Movimiento de cashflow | **No.** No hay editar ni borrar | No |
| Validar una caja | **No.** Una vez validada no se rechaza ni se corrige | No |
| Cerrar una liquidación | **No** | Sí |
| Pagar una liquidación | **No** | No |

### Un grupo sin planes deja el alta trabada, sin mensaje

**VERIFICADO.** El mensaje de error del campo "plan" vive dentro de un bloque que se
oculta cuando el grupo no tiene planes. El error existe y el usuario **nunca lo ve**:
el formulario vuelve con los datos cargados y ningún cartel. Y si va a editar el
alumno, el campo también está escondido. Círculo cerrado.

### Si te olvidás la contraseña, quedaste afuera

**VERIFICADO.** No hay recuperación de contraseña: ni ruta, ni vista, ni link. Depende de
que la dueña entre a cambiarla. Si la que se olvida es la dueña, no hay salida por
pantalla.

### Los errores que no son 403 salen en inglés

**VERIFICADO.** Solo existe la pantalla de 403. El 419 —dejar la pestaña abierta toda la
mañana y después guardar, el más frecuente en un mostrador— muestra la pantalla blanca
de Laravel en inglés. El recibo que falla devuelve un JSON crudo con texto técnico.

### Editar una clase saltea el control que sí hace crearla

**VERIFICADO.** Al crear, si el profesor ya tiene otra clase a esa hora, el sistema frena.
Al **editar** la fecha o la hora, no valida nada. Queda el profesor en dos clases
simultáneas, y esas horas después se liquidan.

### Cuando la dueña cobra, se abre una caja a su nombre y nadie se lo dice

**VERIFICADO.** El aviso de "tenés una caja abierta de un día anterior" está dentro del
bloque que solo ve el operativo. La dueña se entera cuando el sistema la frena, sin
decirle cuál es la caja ni dónde cerrarla.

---

## Lo que un sistema de gestión debería hacer y este no hace

### Duele la primera semana

- **No se puede saber cuánto facturó el club en un mes.** Hay contrato de reportes
  escrito y **cero rutas** implementadas. Lo más cerca es el cashflow, con los dos
  problemas de arriba.
- **No se puede exportar nada.** Ni Excel, ni CSV, ni un listado para imprimir. Cuando el
  contador pida el detalle del mes, no hay camino.
- **No queda registro de que se le reclamó a alguien.** Ni aviso al deudor por mail o
  WhatsApp, ni una nota de "lo llamé el 12". Cuaderno o WhatsApp personal del que cobra:
  si esa persona se va, el historial se va con ella.
- **No hay ficha médica ni contacto de emergencia**, y el club es de menores. Solo el
  tutor y su teléfono.
- **Un chico que hace dos deportes son dos alumnos**, con el mismo DNI cargado dos veces,
  dos cuotas y dos estados de cobranza. Es el caso de Sofía Morales, que en la carga de
  prueba tratamos como borde: es una limitación del modelo.
- **Hermanos y descuento familiar no existen.** Si el club hace precio familiar, hay que
  cobrar distinto de lo que dice la deuda y queda un saldo que nadie va a poder explicar.
- **No hay historial de precios.** El precio se edita pisando la fila: no queda quién ni
  cuándo. Y el aumento se carga grupo por grupo, sin "subir todo un 15%".
- **No queda quién editó o borró qué.** Hay motivos obligatorios en algunos lugares, pero
  no una bitácora. El día que falte plata, la conversación va a ser "¿vos tocaste esto?"
  sin nada que mirar.
- **El profesor no ve cuánto va a cobrar.** Todo pasa por el admin.
- **Al dar de baja a un alumno no se pregunta por qué.** No se puede responder por qué se
  van.

### Puede esperar

- Historial de asistencia de un alumno más allá del mes en curso.
- Aviso al que falta seguido.
- **Cierre de mes**: no existe el concepto. Nada impide cargar hoy un movimiento con
  fecha de marzo o editar deuda de un período ya informado.
- El dueño no puede bajarse sus propios datos sin depender de quien tiene SSH.
- Multi-sede: el modelo está atado a un club.
- Torneos e indumentaria tienen dónde poner la plata pero no gestión: quién se anotó,
  quién debe la camiseta.
- Becados: se resuelve condonando todos los meses.

---

## Documentos que hoy dicen algo falso

**Todos VERIFICADOS contra el código.**

| Documento | Qué dice | Realidad |
|---|---|---|
| `ESTADO-ACTUAL.md` | El operativo no entra a `/cobranza`, pendiente de Carlos | Se arregló el 07/09. **El pendiente ya no existe** |
| `ESTADO-ACTUAL.md` | Commit desplegado `7abf327` | El servidor tiene **`9fdd03d`**. Lo desplegué y no actualicé el documento |
| `ESTADO-ACTUAL.md` | "sin usuarios del club" | La cuenta de la dueña se creó el 07/09 |
| `LOG-CLAUDE.md` (G4) | Falta el ojo para ver la contraseña | **Ya existe en el login.** Falta solo en el formulario de usuarios |
| `AGENTS.md` | 33 pruebas | Son 129 |
| `ESTADO-ACTUAL.md` | Botón Cobrar deshabilitado en el listado de alumnos | Es un enlace funcional |
| `ESTADO-ACTUAL.md` | `AlumnoPlan` puede dejar dos planes activos | La base lo impide con un índice único |
| `ESTADO-ACTUAL.md` | Montos como float, riesgo de precisión | Sobredimensionado: todas las columnas son decimales y los modelos castean |
| `PLAN-MAESTRO.md` | UC-21 a UC-35 sin implementar; UI al 40% | Casi todo existe. Sigue siendo cierto solo lo de exportables |
| `wings-design/SKILL.md` | `ds-content` con tope de 1200px | El CSS no lo implementa. **Esta contradicción sigue abierta** |

---

## Lo que está bien, para calibrar

No todo es deuda. Verificado y sólido:

- Los estados son `ENUM` en la base, no validación en PHP.
- `deuda_cuotas` tiene índice único por alumno y período: **la deuda duplicada es
  imposible por construcción**.
- El modelo `Pago` bloquea la mutación de montos.
- Validar una caja toma el bloqueo antes de cambiar el estado, y el reflejo en cashflow
  es idempotente.
- El PDF se genera después de confirmar la transacción: si falla el PDF, el cobro no se
  cae.
- Un operativo no puede imputar a Sueldos ni escribir en una caja cerrada, aunque mande
  el identificador a mano.
- No hay subida de archivos, ni SQL dinámico, ni datos sin escapar en las vistas.
- Cloudflare está bien resuelto y el servidor solo acepta tráfico suyo.
- Los respaldos corren, están cifrados y la restauración fue probada.

---

## Dónde no coincidimos con Codex

Cruzamos los dos informes. La mayoría coincide. Estas son las diferencias, con lo que
verifiqué en cada una.

### Lo que él da por bueno y no lo está

**"El cambio de plan y el cobro comparten transacción. No repetir esos defectos
antiguos como abiertos."**

Codex verificó el servidor y tiene razón en lo que miró: el bloque que aplica el plan
nuevo comparte transacción con el cobro y está bien escrito. **Pero nunca se ejecuta**,
porque el dato no llega: los botones de plan están fuera del formulario. Él revisó el
backend; el defecto está en la pantalla.

Verificado por mí: radios en la línea 54, formulario de la 83 a la 207, sin atributo
que los ate, y el JavaScript no los agrega al envío.

### Lo que él no vio

- **El monto con punto de miles que se registra mil veces más chico.** Es el hallazgo
  más grave de los dos informes y no está en el suyo.
- **La base de entrega no se puede reproducir.** El script que la generó quedó fuera del
  repositorio.
- **Cinco servicios de plata sin una sola prueba**, y que la única de liquidaciones
  verifica el nombre del grupo, no el monto.
- **Que la prueba de concurrencia es un `grep` del código fuente.** Él la trata como
  "pendiente de probar"; en realidad la prueba que existe **no prueba nada** y da verde.
- **No hay integración continua.**

Casi todo esto se explica por su propio límite declarado: **no consultó la base, no
ejecutó la suite y no entró al servidor.** Su evaluación es enteramente estática.

### Donde él tiene razón y yo estaba mal

- **`npm audit`**: mi auditor dijo cero avisos. Son **11, dos críticos**. Corregido
  arriba.
- **El ojo de contraseña ya existe en el login.** Mi pendiente G4 lo daba por faltante
  en todos lados.
- **`ESTADO-ACTUAL` quedó atrasado dos veces por mi culpa**: el commit desplegado y la
  frase de que no hay usuarios del club.
- **Los respaldos**: ninguno de mis cuatro auditores los miró.

### Donde él pide verificar algo que ya está verificado

Pide "verificar el servicio real antes de afirmar que existe o no monitoreo". **Ya lo
hice hoy por SSH: cero servicios de monitoreo corriendo.** Y el `schedule:run` existe
en el crontab del usuario de la aplicación, mandando su salida a `/dev/null`.

### Una diferencia de método, no de hallazgos

Su informe evita afirmar y prefiere "faltaría demostrar". Es prudente y en varios casos
tiene razón — ninguno de los dos reprodujo estos casos en una base. Pero conviene no
confundir *"no lo reproduje"* con *"puede que no exista"*: los cuatro casos de plata de
arriba están verificados en el código, y tres de ellos no dan ninguna señal cuando
ocurren.

---

## Cierre

Hay un patrón que atraviesa casi todo lo de arriba: **el sistema es duro con lo que se
puede tocar y blando con lo que avisa.** Una caja validada no se rechaza, un movimiento
de cashflow no se edita, una liquidación cerrada no se reabre — pero casi nada explica
por qué una pantalla está vacía, por qué un número es el que es, ni qué hacer cuando
algo no se puede. La combinación deja al usuario trabado sin saber en qué.

Y lo más caro no son los agujeros: son los **tres cobros que salen mal con el cartel
verde de éxito**. Un error que grita se arregla. Uno que felicita, no.
