# Flujo de usuario — Sistema Wings

Última revisión: 2026-07-03

Este documento describe qué ve y qué puede hacer cada tipo de usuario en cada pantalla del sistema. Está escrito para ser usado como guía de pruebas manuales, base para manuales de usuario y documentación funcional.

No contiene código ni tecnicismos. Dice exactamente qué pasa en pantalla.

---

## Roles del sistema

El sistema tiene tres tipos de usuarios:

- **Administrador (Admin):** Accede a todo. Ve métricas globales, valida cajas, administra liquidaciones, configuración y usuarios.
- **Operativo:** Gestiona su propia caja diaria, cobra cuotas y registra asistencias.
- **Profesor:** Solo ve las clases en las que está asignado y puede registrar asistencias.

---

## Pantalla de inicio de sesión

**¿Quién la ve?** Todos, antes de entrar.

El usuario llega a una pantalla centrada con el logo de Wings, un campo de email, uno de contraseña, un checkbox "Recordarme" y el botón "Ingresar".

**Si los datos son correctos:** entra al sistema y va al panel principal según su rol.

**Si los datos son incorrectos:** aparece un mensaje de error debajo del logo y puede volver a intentar.

**Límite de intentos:** después de 10 intentos fallidos en un minuto, el sistema bloquea temporalmente el acceso desde esa red. Es una protección automática.

**Lo que no existe:** no hay botón de "olvidé mi contraseña". Si un usuario pierde el acceso, debe pedirle al administrador que la resetee.

---

## Menú lateral

El menú cambia según el rol del usuario. Aparece siempre a la izquierda de la pantalla.

**Administrador ve:**
- Dashboard
- Caja, Cashflow, Liquidaciones
- Alumnos, Clases, Grupos, Profesores
- Deportes, Rubros, Niveles, Tipos de Caja, Configuración, Usuarios

> **Pendiente:** Falta el link "Cobranza" (revisión de alumnos posibles inactivos). La pantalla existe pero no se puede acceder desde el menú. También falta el link "Cashflow" que lleve al historial — el link actual lleva al formulario de carga.

**Operativo ve:**
- Caja, Alumnos, Clases, Grupos

> **Pendiente:** Falta el link "Inicio" que lleve al dashboard propio del operativo. También aparece "Movimientos" que según el diseño no debería estar en el menú principal.

**Profesor ve:**
- Clases (con un número rojo si hay clases con asistencia pendiente)

---

## Dashboard del Administrador

**Quién lo ve:** solo el Admin.
**Cómo llegar:** aparece al entrar al sistema o haciendo clic en "Dashboard".

Muestra estadísticas globales del negocio: ingresos del mes, alumnos activos, cajas pendientes de validar. Accesos rápidos a las secciones principales.

---

## Dashboard del Operativo

**Quién lo ve:** solo el Operativo.
**Cómo llegar:** haciendo clic en "Inicio" del menú (cuando esté disponible). Hoy solo se accede escribiendo `/operativo` en la barra de direcciones.

Muestra:
- La fecha de hoy escrita en texto.
- Tres números resumidos: dinero cobrado hoy, cantidad de cobros registrados, cantidad de cajas.
- Un panel de estado de la caja del día:
  - **Sin caja abierta:** fondo gris, mensaje explicando que la caja se abre sola al hacer el primer cobro. Botón "Cobrar".
  - **Caja abierta:** fondo naranja con la hora en que se abrió. Botones: "Cobrar", "Movimiento" y "Resumen".
  - **Caja cerrada:** fondo verde con la hora de cierre. Botón "Nueva caja".
  - **Alerta de caja vieja sin cerrar:** fondo rojo bloqueando todo. El operativo no puede hacer nada hasta cerrar la caja atrasada.
- Lista de cajas del día con sus totales.

> **Pendiente:** Faltan tres secciones definidas: lista de alumnos con deuda (para cobrar rápido), clases del día (con indicador de si ya se tomó lista) y alerta de cajas rechazadas por el admin.

---

## Alumnos — Lista

**Quién lo ve:** Admin y Operativo.
**Cómo llegar:** clic en "Alumnos" del menú.

Aparece una lista de tarjetas de alumnos. Arriba hay un buscador con sugerencias en tiempo real (escribe nombre, apellido o DNI y aparecen resultados inmediatamente). También hay dos selectores: uno para filtrar por deporte y otro por grupo. Al cambiar cualquiera de los selectores, la lista se actualiza sola sin necesidad de hacer clic en ningún botón.

Cada tarjeta muestra:
- Nombre y apellido
- DNI
- Grupo al que pertenece
- Edad (y si cumple años este mes, aparece un emoji de torta)
- Nombre y teléfono del tutor

Abajo de cada tarjeta hay cuatro botones:
- **Cobrar:** va directo a la pantalla de cobro de ese alumno.
- **Ver:** abre la ficha completa del alumno.
- **Editar:** abre el formulario de edición.
- **Switch Activo/Inactivo:** cambia el estado del alumno al instante sin recargar la página.

Arriba a la derecha, el botón **"Nuevo"** abre el formulario de creación de alumno (disponible para Admin y Operativo).

> **Pendiente:** El punto de color a la izquierda del nombre de cada tarjeta siempre aparece gris. Debería cambiar de color según si el alumno está al día (verde), moroso (naranja) o deudor (rojo).

---

## Alumnos — Crear

**Quién lo ve:** Admin y Operativo.
**Cómo llegar:** clic en "Nuevo" desde la lista de alumnos.

El formulario tiene estos campos:
- **Nombre y Apellido** (obligatorios)
- **DNI** (obligatorio; no puede repetirse dentro del mismo deporte)
- **Fecha de nacimiento** (obligatoria; no puede ser futura; el año se valida mientras se escribe)
- **Celular** (opcional; hay un checkbox "Mismo que el teléfono del tutor" que copia el valor automáticamente)
- **Email** (opcional; se valida el formato mientras se escribe)
- **Deporte** (obligatorio; una vez creado el alumno no se puede cambiar)
- **Grupo** (obligatorio; la lista se filtra automáticamente según el deporte elegido)
- **Frecuencia semanal** (aparece después de elegir el grupo; muestra las opciones disponibles con precio mensual; es obligatoria)

Si el alumno es menor de edad (calculado automáticamente según la fecha de nacimiento), los campos de nombre y teléfono del tutor pasan a ser obligatorios.

Botones al pie: **"Guardar"** (crea el alumno y vuelve a la lista) y **"Cancelar"** (vuelve a la lista sin guardar).

**Importante:** No se puede guardar un alumno sin asignarle una frecuencia semanal. Si el grupo no tiene planes configurados, el campo de frecuencia no aparece y no se puede crear el alumno.

---

## Alumnos — Ficha (Ver)

**Quién lo ve:** Admin y Operativo.
**Cómo llegar:** clic en "Ver" desde la lista de alumnos.

El encabezado muestra el nombre del alumno y un badge de estado: verde "Activo" o rojo "Inactivo".

Si el alumno está inactivo, aparece el botón **"Reactivar"** que pide confirmación antes de ejecutarse. Siempre está disponible el botón **"Editar"**.

**Columna izquierda (información):**
- DNI, fecha de nacimiento, celular, email
- Deporte, grupo, plan actual (frecuencia y precio mensual), fecha de alta
- Estado de cobranza con badge de color:
  - Verde "Al día": no tiene deudas pendientes
  - Naranja "Moroso": tiene deuda pero dentro del período de gracia
  - Rojo "Deudor": tiene deuda vencida, con el detalle de cada cuota (período y saldo)
- Datos del tutor (si tiene)

**Columna derecha:**
> **Pendiente:** Hay dos bloques que dicen "disponible próximamente": historial de pagos y asistencias del mes. Son las dos cosas que más se necesitan al ver la ficha de un alumno.

---

## Alumnos — Editar

**Quién lo ve:** Admin y Operativo.
**Cómo llegar:** clic en "Editar" desde la lista o la ficha.

Igual que el formulario de creación, pero:
- El deporte aparece bloqueado (no se puede cambiar).
- El grupo y la frecuencia semanal se pueden cambiar. Si se cambia la frecuencia, el historial del plan anterior queda guardado con la fecha en que se cerró; no se borra.
- Si el alumno no tiene un plan válido (el plan anterior fue eliminado), aparece un aviso rojo arriba del formulario y el campo de frecuencia semanal pasa a ser obligatorio. No se puede guardar sin elegir uno.

---

## Caja — Lista

**Quién lo ve:** Admin ve todas las cajas de todos los operativos. Operativo solo ve las suyas.
**Cómo llegar:** clic en "Caja" del menú.

**El Admin** tiene filtros para ver por operativo y por mes.

Cada tarjeta de caja muestra: fecha, operativo, estado y monto total.

Estados posibles de una caja:
- **Abierta:** naranja. La caja está en uso ese día.
- **Cerrada:** gris. El operativo la cerró y espera validación.
- **Validada:** verde. El admin la revisó y aprobó.
- **Rechazada:** rojo. El admin la rechazó con un motivo.

En cada tarjeta, los botones disponibles dependen del estado:
- Siempre: **"Resumen"** y **"Detalle"**.
- Si la caja está abierta o rechazada y el operativo es el dueño: **"Cobrar"**, **"Agregar"**, y dentro del Resumen aparece **"Cerrar"**.
- Si el admin ve una caja cerrada: dentro del Resumen aparecen **"Validar"** y **"Rechazar"**.

**Si no hay caja del día:** aparece un mensaje explicando que se abre sola al registrar el primer movimiento, con botones "Cobrar" y "Nuevo".

---

## Caja — Resumen

**Cómo llegar:** clic en "Resumen" desde la lista de cajas.

Muestra:
- Datos de encabezado: operativo, fecha, hora de apertura, hora de cierre (si cerró), estado.
- Tabla de totales por medio de pago (efectivo, transferencia, etc.).
- Tabla de totales por rubro.
- Total final: ingresos, egresos y neto.

**Si la caja es del operativo y está abierta:** botones "Cobrar", "Nuevo" (movimiento manual) y "Cerrar".

Al hacer clic en "Cerrar", el sistema pide confirmación. Al confirmar, la caja queda cerrada y el admin puede revisarla.

**Si el admin ve una caja cerrada:** botones "Validar" y "Rechazar".

- **Validar:** confirma la caja con un clic. El dinero pasa automáticamente al historial de cashflow.
- **Rechazar:** abre un campo de texto para escribir el motivo (opcional). El operativo verá el motivo cuando entre a su caja.

---

## Caja — Detalle

**Cómo llegar:** clic en "Detalle" desde la lista de cajas.

Muestra la tabla completa de todos los movimientos de esa caja. Cada fila tiene: fecha, tipo (ingreso/egreso), medio de pago, rubro, descripción o nombre del alumno, y monto.

Los movimientos cancelados aparecen tachados y en opaco.

**Si la caja está abierta y es del operativo, cada movimiento tiene:**
- Para cobros de cuotas: botón "Cancelar". Al hacer clic abre un campo para escribir el motivo (obligatorio). Si confirma, el pago se anula y la deuda del alumno vuelve a quedar pendiente.
- Para movimientos manuales: botones "Editar" y "Eliminar".
- Para cualquier cobro de cuota: botón "Recibo" que abre el PDF del recibo.

**El admin ve checkboxes al lado de cada movimiento:** puede ir tildando los que revisó. Un contador muestra cuántos lleva verificados. Cuando quiere validar, hace clic en "Validar" — lo puede hacer aunque no haya tildado todos los checkboxes, los checkboxes son solo para ayudar al control manual.

---

## Cobrar — Seleccionar alumno

**Cómo llegar:** clic en "Cobrar" del menú lateral, o desde el dashboard.

Muestra solo los alumnos que tienen deuda pendiente. Se puede buscar por nombre o DNI. Cada tarjeta muestra: nombre, saldo total adeudado, cuántas cuotas están pendientes y el grupo.

Botones por tarjeta: **"Cobrar"** (va directo al formulario de cobro) y **"Ver"** (va a la ficha del alumno).

Si ningún alumno tiene deuda, la pantalla muestra "Todos los alumnos están al día".

---

## Cobrar — Formulario de cobro

**Cómo llegar:** clic en "Cobrar" desde la lista de alumnos o desde la pantalla de selección.

**Franja superior:** datos del alumno — DNI, deporte, grupo, y el total pendiente en rojo.

**Si el alumno no tiene un plan activo válido:** en lugar de mostrar el formulario, el sistema redirige a la pantalla de edición del alumno con un mensaje pidiendo que se le asigne un plan antes de poder cobrar.

**Si el grupo tiene más de una frecuencia disponible:** aparece un panel con las opciones de plan como botones de selección (ej: "1 vez/semana — $7.000/mes", "2 veces/semana — $12.000/mes"). El plan actual del alumno aparece pre-seleccionado. Al cambiar la selección, los montos de las cuotas se actualizan automáticamente al precio del nuevo plan.

**Si aplica descuento de primer pago o reingreso:** aparece un aviso amarillo explicando qué porcentaje se aplicará y por qué. El descuento se calcula y aplica automáticamente al confirmar, el operativo no necesita hacer nada.

**Lista de cuotas pendientes:** cada período aparece como una fila con un checkbox. Al tildar una cuota, el campo de monto se habilita — por defecto muestra el saldo pendiente pero se puede editar. Solo se puede pagar hasta el saldo pendiente de cada cuota; no se puede poner más.

**Campos del pago:**
- **Tipo de pago:** obligatorio (selector).
- **Forma de pago:** opcional.
- **Fecha del pago:** hoy por defecto, no puede ser futura.
- **Observaciones:** texto libre, opcional.

El botón **"Cobrar"** aparece deshabilitado (gris) hasta que se elige al menos una cuota y se selecciona el tipo de pago. En ese momento se habilita y aparece el total calculado.

Al confirmar: el sistema registra el pago, lo agrega a la caja del día (abriéndola automáticamente si no existe), y redirige con mensaje de éxito.

Si el operativo cambió el plan durante el cobro, ese cambio queda registrado en el historial del alumno.

> **Si el alumno no tiene deudas:** la pantalla muestra "Sin deudas pendientes" y el botón Cobrar permanece deshabilitado. El operativo tiene que salir manualmente.

---

## Revisión de cobranza (alumnos posibles inactivos)

**Quién lo ve:** Admin.
**Cómo llegar:** escribiendo `/revision-cobranza` en la barra de direcciones (no hay link en el menú todavía).

Esta pantalla aparece cuando el sistema detectó alumnos que el 1° del mes no generaron deuda automática, porque no asistieron ni pagaron el mes anterior. Esos alumnos quedan en "posible inactivo" hasta que alguien los revisa.

**Si hay alumnos pendientes:** aparece un banner naranja explicando la situación e indicando que hay que contactar al tutor.

Filtros disponibles: Estado (Pendientes/Resueltos/Todos) y Período.

Cada tarjeta muestra: nombre del alumno, período en revisión, deporte, grupo y motivo del flag (en naranja: "PENDIENTE").

Cada tarjeta tiene dos botones de acción:
- **"Continúa"** (verde): el alumno sigue activo aunque no haya asistido (ej: estaba de vacaciones).
- **"Inactivo"** (rojo): se marca como inactivo definitivamente.

Al hacer clic en cualquiera, los botones se ocultan y aparece un campo de texto para escribir la nota de lo que pasó (mínimo 5 caracteres, obligatorio). Hay un botón "Confirmar" y un "Cancelar" para volver atrás sin hacer nada.

**Qué pasa al confirmar cada opción:**
- **Continúa:** se genera la deuda del mes para ese alumno. La nota queda registrada con la fecha y el nombre de quien la resolvió.
- **Inactivo:** el alumno pasa a estado inactivo. La deuda del mes inmediatamente anterior (si estaba pendiente) se cancela automáticamente. Las deudas más antiguas no se tocan — el admin decide qué hacer con ellas por separado.

Los casos resueltos quedan visibles con el resultado en color, la nota y quién los resolvió.

Los casos marcados como "Reactivado automáticamente" son alumnos que el sistema resolvió solo porque vinieron a una clase o pagaron antes de que el admin los revisara.

---

## Cashflow — Historial

**Quién lo ve:** Admin.
**Cómo llegar:** escribiendo `/cashflow` en la barra de direcciones (el link del menú actualmente lleva al formulario de carga, no al historial).

Filtros: año, mes, tipo de caja, tipo (ingresos/egresos). Al cambiar cualquier selector, la tabla se actualiza sola.

Encima de la tabla: una barra de resumen que muestra ingresos totales (verde), egresos totales (rojo), balance resultante y cantidad de movimientos en el período filtrado.

La tabla muestra: fecha, tipo (I/E), tipo de caja, rubro y subrubro, observaciones, monto y quién lo registró.

Botón **"Nuevo"** para agregar un movimiento directo (gastos o ingresos que no son cobros de cuotas).

> **Pendiente:** No se pueden editar ni eliminar movimientos ya cargados. Si se cargó algo con error, no hay forma de corregirlo.

---

## Cashflow — Movimiento directo

**Cómo llegar:** clic en "Nuevo" desde el historial de cashflow (o desde el link del menú, que actualmente lleva acá directamente).

El admin puede cargar ingresos o egresos que no corresponden a cobros de cuotas — por ejemplo, gastos de limpieza, compra de materiales, etc.

Dos botones grandes al inicio que cambian el modo: **"Ingreso"** (verde) o **"Egreso"** (rojo). Al elegir uno, el borde lateral del formulario cambia de color y el selector de rubros filtra solo los rubros del tipo elegido.

Campos:
- **Fecha** (obligatoria)
- **Tipo de caja** (obligatorio)
- **Rubro** (obligatorio)
- **Subrubro** (aparece al elegir el rubro, obligatorio)
- **Monto** (obligatorio, número positivo)
- **Observaciones** (obligatorio en esta pantalla, a diferencia del cobro de cuotas donde es opcional)

Al guardar, el movimiento aparece en el historial de cashflow.

> **Bug actual:** después de guardar, el sistema lleva al admin a la lista de cajas en lugar de al historial de cashflow.

---

## Clases — Lista

**Quién lo ve:** Admin, Operativo y Profesor (con distintos botones).
**Cómo llegar:** clic en "Clases" del menú.

La pantalla tiene dos secciones:

**Arriba — Clases de hoy:** un panel con scroll que muestra solo las clases del día actual. El sistema resalta automáticamente la clase que está "en curso" o "por comenzar".

**Abajo — Todas las clases:** filtros por deporte, grupo, profesor, estado y fecha. Al cambiar el filtro de deporte, la lista de grupos se actualiza automáticamente. Listado paginado.

Estados posibles de una clase:
- **Programada:** gris (es futura y no hay nada especial)
- **Por comenzar:** naranja (empieza en los próximos 30 minutos)
- **En curso:** verde
- **Finalizada:** naranja sin asistencia (ya pasó pero no se tomó lista)
- **Cerrada:** azul (ya pasó y la asistencia está cargada)
- **Cancelada:** rojo

El Admin ve botones "Ver" y "Editar" en cada clase. El Operativo y el Profesor solo ven "Ver".

---

## Clases — Detalle y asistencia

**Cómo llegar:** clic en "Ver" desde la lista de clases.

El encabezado muestra: fecha, horario, deporte, grupo, profesores asignados. Un badge indica si la clase está activa o cancelada.

Si la clase no está cancelada:
- Hay un botón **"Modificar"** para cambiar los profesores asignados.
- Hay un botón **"Cancelar"** que pide el motivo. Si la clase tiene otras clases en la misma serie, el admin puede cancelar solo esta o todas.

**Lista de alumnos del grupo:** cada alumno aparece con su nombre, cuántas clases lleva en la semana de su plan (ej: "1 de 2"), y un checkbox grande "Presente". Al tildar a un alumno, el fondo de su tarjeta se pone verde suave.

**Si marcar a un alumno como presente haría que exceda su plan semanal:** aparece automáticamente un panel naranja pidiendo que se indique si es una clase extra o una recuperación. Si no se indica nada, no se puede guardar.

El botón **"Guardar"** al final registra todas las asistencias sin recargar la página. Aparece un mensaje de éxito o error en el mismo lugar. La pantalla no redirige a otro lado.

Si la clase está cancelada, todos los alumnos aparecen deshabilitados y no se puede guardar.

---

## Grupos — Lista y detalle

**Quién lo ve:** Admin (puede editar). Operativo (solo lectura — para consultar precios por frecuencia).
**Cómo llegar:** clic en "Grupos" del menú.

Muestra los grupos activos del sistema: nombre, deporte, nivel, capacidad, profesores asignados y los planes de frecuencia con sus precios.

El Admin puede crear, editar y eliminar grupos. El Operativo solo puede consultar la información.

---

## Liquidaciones — Lista

**Quién lo ve:** Admin.
**Cómo llegar:** clic en "Liquidaciones" del menú.

Filtros: profesor, mes, año, estado. Botón **"Nuevo"** para crear una liquidación.

Cada tarjeta muestra: nombre del profesor, período, tipo (por hora o por comisión), total calculado y estado con badge de color (abierta/cerrada/pagada).

Botones: **"Ver"** siempre. **"Eliminar"** solo si la liquidación está abierta.

---

## Liquidaciones — Crear

**Cómo llegar:** clic en "Nuevo" desde la lista de liquidaciones.

**Si es el último día del mes:** aparece un aviso preguntando si incluir las clases de hoy (que técnicamente no cerraron). El admin elige "Incluir" o "No incluir".

Lista de todos los profesores activos. Al seleccionar uno se muestra cuántas clases tiene pendientes de liquidar. Los profesores sin clases aparecen deshabilitados.

**Si el profesor tiene clases sin asistencia cargada:** aparece un aviso naranja con un link directo a las clases de ese profesor. El admin debe ir a cargar las asistencias faltantes antes de liquidar.

Selectores de mes y año. Botón **"Generar"** que crea la liquidación y redirige al detalle.

---

## Liquidaciones — Detalle

**Cómo llegar:** clic en "Ver" desde la lista.

El encabezado muestra: nombre del profesor, período, tipo de liquidación, total calculado en grande, estado y (si ya se pagó) fecha y medio de pago.

**Tipo por hora:** cada clase aparece como tarjeta con fecha, grupo, duración y subtotal.
**Tipo por comisión:** cada alumno aparece con grupo, monto que pagó y porcentaje calculado.

**Botones según estado:**
- **Abierta:** "Cerrar" (finaliza el cálculo), "Recalcular" (actualiza si se agregaron clases), "Eliminar".
- **Cerrada, sin pagar:** aparece abajo el formulario de pago con tipo de caja, fecha y observaciones.
- **Pagada:** "Recibo" (genera el PDF) y "Volver".

---

## Configuración

**Quién lo ve:** Admin.

El sistema tiene varias pantallas de configuración, todas con estructura similar: lista con botón "Nuevo", y botones "Editar" y "Eliminar" en cada ítem.

- **Deportes:** los deportes que practica el club (patín, fútbol, etc.).
- **Rubros y Subrubros:** categorías para clasificar los movimientos de caja y cashflow.
- **Niveles:** niveles de habilidad para los grupos (inicial, intermedio, avanzado, etc.).
- **Tipos de Caja:** los tipos de pago (efectivo, tarjeta, transferencia, etc.).
- **Formas de Pago:** similar a tipos de caja, agrupación adicional.
- **Reglas de Primer Pago:** configuración de descuentos proporcionales según el día del mes en que ingresa el alumno (ej: si entra después del día 20, paga el 30% de la cuota mensual).

---

## Usuarios

**Quién lo ve:** Admin.
**Cómo llegar:** clic en "Usuarios" del menú.

Lista de todos los usuarios del sistema con nombre, email y rol. Botones "Editar" y un switch de Activo/Inactivo.

El Admin puede crear nuevos usuarios asignándoles rol (Admin, Operativo o Profesor). También puede desactivarlos — un usuario desactivado no puede entrar al sistema aunque tenga contraseña correcta; si ya estaba dentro, el sistema lo desloguea en la próxima acción.

No hay pantalla de cambio de contraseña propia para el usuario logueado.

---

## Casos especiales y situaciones conocidas

### Alumno inactivo que quiere volver

Si el alumno estaba inactivo y aparece para pagar:
1. El operativo lo busca en la lista de alumnos — aparece en la lista con el switch en "Inactivo".
2. Hace clic en "Cobrar".
3. Si el plan del alumno sigue vigente, aparece el formulario de cobro normalmente. Si aplica descuento de reingreso (según la fecha del mes), aparece el aviso amarillo.
4. Al confirmar el pago, el alumno se reactiva automáticamente — el switch vuelve a "Activo" solo.
5. Si había una revisión pendiente de ese alumno en la pantalla de cobranza, también se cierra automáticamente.

Si el plan del alumno ya no existe (el grupo cambió sus precios o el plan fue eliminado):
1. Al hacer clic en "Cobrar", el sistema redirige a la pantalla de edición con un aviso rojo.
2. El operativo debe asignar un nuevo plan antes de poder cobrar.

### Alumno que quiere cambiar de frecuencia al pagar

Si un alumno quiere pasar de 2 a 1 clase por semana al mismo tiempo que paga:
1. En la pantalla de cobro, si el grupo tiene más de un plan, aparecen los planes como opciones seleccionables.
2. El operativo selecciona el nuevo plan — los montos de las cuotas se actualizan automáticamente al precio del nuevo plan.
3. Al confirmar, el sistema primero registra el cambio de plan (el plan anterior queda guardado en el historial) y luego procesa el pago.

### Caja rechazada por el admin

1. El operativo entra al sistema y ve su caja con estado "Rechazada" en rojo.
2. Puede entrar al detalle de esa caja para ver el motivo que escribió el admin.
3. Puede agregar o cancelar movimientos en una caja rechazada.
4. Cuando está conforme, vuelve a cerrarla para que el admin la revise de nuevo.

### Generación automática de deudas

El 1° de cada mes, el sistema automáticamente genera las deudas del mes para todos los alumnos activos que tienen un plan vigente. Si un alumno no asistió ni pagó el mes anterior, en lugar de generar la deuda automáticamente queda en revisión (posible inactivo) para que el admin decida.
