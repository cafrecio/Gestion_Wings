# Resultado de prueba humana V1

**Fecha:** 30/08/2026  
**Ejecutor:** Codex CAB  
**Ambiente:** `http://gestion-wings`, base descartable `wings_test`  
**Estado:** finalizada por bloqueo funcional en B2

## Estado de la ejecución

La cadena se retomó desde A3 sin repetir A1 ni A2. Se opera exclusivamente por
las pantallas de Wings. No se llamó ningún servicio ni se escribió directamente
en la base.

## Bloque A — El administrador arma el club

### A1 · Un deporte más — PASA

- Se ingresó a **Deportes → Nuevo** y se cargó `Hockey` con liquidación **Por hora**.
- El sistema volvió al listado con el mensaje `Deporte creado correctamente.`.
- El contador pasó de 2 a 3 y `Hockey` apareció en el listado.
- Escritura observada: sí, únicamente el deporte esperado.

### A2 · Deporte repetido — PASA

Se probaron por pantalla las tres variantes exigidas:

| Nombre ingresado | Resultado |
|---|---|
| `Patín` | Rechazado con `Ya existe un deporte con ese nombre.` |
| `patín` | Rechazado con `Ya existe un deporte con ese nombre.` |
| `PATIN` | Rechazado con `Ya existe un deporte con ese nombre.` |

- Tipo de respuesta: mensaje de validación dentro del formulario; no hubo pantalla
  de error.
- Escritura pese al rechazo: no. Al volver al listado seguían figurando exactamente
  3 deportes: `Fútbol`, `Hockey` y `Patín`.

### A3 · Profesores — PASA

- Jorge Mitre se creó en Patín con liquidación por hora y valor visible de
  `$12.000`.
- Silvia Roca se creó en Fútbol con liquidación por comisión y valor visible de
  `40.00%`.
- En Rubros → Sueldos aparecieron automáticamente `Patín-Jorge Mitre` y
  `Fútbol-Silvia Roca`.
- No hubo pantalla de error.

Incidencia del ejecutor: antes de la ejecución limpia de A5 se reutilizó por error
el formulario validado de A4, que conservaba la dirección anterior, y se creó el
profesor adicional `Sin Direccion`. No es un resultado de A5 ni un defecto de
Wings; queda registrado porque altera el recuento físico final. No se eliminó.

### A4 · Profesor con DNI repetido — PASA

- El DNI `20111222` fue rechazado con `Ya existe un profesor con ese DNI.`.
- Tipo de respuesta: validación dentro del formulario, no pantalla de error.
- Escritura pese al rechazo: no se creó el profesor duplicado.

### A5 · Profesor sin dirección — PASA

- Se repitió desde un formulario nuevo y se verificó visualmente que Dirección
  estaba vacía antes de guardar.
- La pantalla permaneció en el formulario y mostró `Completa este campo`.
- Tipo de respuesta: validación del formulario en castellano; no hubo pantalla de
  error.
- Escritura pese al rechazo: no se creó el profesor de esta ejecución limpia.

### A6 · Grupos — PASA

Se crearon los tres grupos y el listado mostró exactamente:

- `Patín — Principiantes`.
- `Patín — Avanzadas`.
- `Fútbol — Intermedias`.

### A7 · Grupo repetido — PASA

- El segundo intento de `Patín — Principiantes` fue rechazado con
  `Ya existe un grupo con ese deporte y nivel.`.
- Tipo de respuesta: validación dentro del formulario.
- Escritura pese al rechazo: no; el listado siguió mostrando 3 grupos.

### A8 · Planes — PASA

- `Patín — Principiantes`: 2 veces/semana por `$28.000` y 3 veces/semana por
  `$35.000`.
- `Patín — Avanzadas`: 3 veces/semana por `$42.000`.
- `Fútbol — Intermedias`: 2 veces/semana por `$30.000`.
- Al escribir `28000`, el campo mostró `28.000` antes de guardar.

### A9 · Precio inválido — FALLA

Se probaron las dos variantes en `Patín — Avanzadas`:

- Precio `0`: se esperaba rechazo; la pantalla informó `Grupo actualizado
  correctamente.` y el listado mostró `1x/sem — $0`.
- Precio `-5000`: se esperaba rechazo; mientras se escribía, la pantalla eliminó
  el signo menos y mostró `5.000`. Luego informó `Grupo actualizado
  correctamente.` y el listado mostró `4x/sem — $5.000`.
- Tipo de respuesta: no hubo validación ni pantalla de error; ambos pedidos fueron
  aceptados.
- Escritura pese al resultado inválido: sí en ambos casos. Quedaron dos planes
  adicionales guardados. Los cuatro planes válidos de A8 siguen presentes.

### A10 · Usuarios — PASA

- Se creó `Operativo Prueba` con rol Operativo.
- Al elegir el rol Profesor apareció el campo obligatorio `Profesor vinculado`.
- Antes de vincularlo se intentó guardar y el servidor rechazó el pedido con
  `Debe seleccionar el profesor vinculado.`; no creó la cuenta incompleta.
- Después de elegir `Mitre, Jorge — Patín`, se creó `Jorge Mitre` con rol
  Profesor y el vínculo correcto.
- El listado terminó mostrando los 3 usuarios esperados: administrador inicial,
  operativo y profesor.

### A11 · Email repetido — PASA

- Otro usuario con `operativo@wings.test` fue rechazado con
  `Ya existe un usuario con ese email.`.
- Tipo de respuesta: validación dentro del formulario.
- Escritura pese al rechazo: no; el listado siguió mostrando 3 usuarios.

### A12 · Alumnos — PASA

- Se crearon correctamente Lucía Fernández, Mateo Gómez, Valentina Ruiz,
  Benjamín Torres y Emma Castro con sus grupos y frecuencias previstas.
- Los cuatro menores de 18 años quedaron con nombre y teléfono de tutor;
  Benjamín figuró con 18 años y sin tutor.
- La fecha de alta apareció inicialmente como `2026-08-30`.
- Para Valentina se cambió a `2024-03-01`; después de crearla, su formulario de
  edición mostró esa misma fecha y la frecuencia correcta de 3 veces/semana por
  `$42.000`.
- El listado terminó mostrando exactamente 5 alumnos.

### A13 · Alumnos que tienen que fallar — PASA

| Caso | Respuesta observada |
|---|---|
| Sin celular | `El celular es obligatorio.` |
| DNI repetido | `Ya existe un alumno con ese DNI en el mismo deporte.` |
| Sin grupo | `Selecciona un elemento de la lista` |
| Menor sin tutor | `El nombre del tutor es obligatorio para menores de edad.` y `El teléfono del tutor es obligatorio para menores de edad.` |
| Nacimiento futuro | `El año ingresado no es válido.` |

- Todos fueron mensajes de validación; no hubo pantalla de error.
- Escritura pese al rechazo: no en los cinco casos. Después de cada intento el
  listado siguió mostrando exactamente 5 alumnos.

## Bloque B — El operativo trabaja

### B1 · Accesos prohibidos — PASA

Con la sesión de `Operativo Prueba`, se escribieron manualmente las siete rutas
pedidas. Todas redirigieron a `/caja`; ninguna pantalla restringida se abrió:

| Ruta intentada | Resultado visible |
|---|---|
| `/usuarios` | Redirección a Caja |
| `/cashflow` | Redirección a Caja |
| `/configuraciones` | Redirección a Caja |
| `/liquidaciones` | Redirección a Caja |
| `/rubros` | Redirección a Caja |
| `/deportes` | Redirección a Caja |
| `/profesores` | Redirección a Caja |

### B2 · Cobrar la primera cuota — FALLA

- Esperado: cobrar a Lucía Fernández la cuota vigente de `$28.000` en Efectivo.
- Observado en Caja → Cobrar: `Sin deudas pendientes`. Al buscar `Lucía`, la
  pantalla respondió `No se encontraron alumnos con deuda para "Lucía"`.
- Se probó también el botón visible `Cobrar` de Lucía en el listado de alumnos.
  La pantalla individual mostró `Total pendiente $0`, `Sin deudas pendientes` y
  el botón final `Cobrar` deshabilitado.
- Tipo de falla: no hubo mensaje de validación ni pantalla de error; la cuota del
  mes no estaba disponible para seleccionar y la operación era inalcanzable.
- Escritura pese a la falla: no. No se ejecutó ninguna transacción. Al volver a
  Caja seguía figurando `No tenés caja abierta hoy`, `0 cajas` y ningún historial.

Este paso rompió la cadena. De acuerdo con el protocolo, no se salteó ni se
generaron datos por fuera de las pantallas para habilitar los pasos siguientes.

### B3 · Cobro dejando deuda anterior — NO SE PUDO

No se ejecutó porque B2 bloqueó la cadena antes del primer cobro.

### B4 · Cancelar un cobro — NO SE PUDO

No existió el cobro previo necesario: B2 no permitió registrar ninguno.

### B5 · Cerrar y validar la caja — NO SE PUDO

No se abrió una caja porque no se pudo registrar el movimiento de B2.

### B6 · Clases — NO SE PUDO

No se ejecutó para no saltear B2–B5 dentro de la cadena indicada.

## Bloque C — El profesor

### C1 · Lo que ve y lo que no — NO SE PUDO

El usuario profesor existe, pero no se inició este bloque porque B2 rompió la
cadena y B6 no llegó a crear las clases.

### C2 · Tomar asistencia — NO SE PUDO

No hubo clase C1 porque B6 no se ejecutó.

### C3 · Corregir la asistencia — NO SE PUDO

No hubo asistencia inicial que corregir.

### C4 · Alumno de otro grupo — NO SE PUDO

No hubo clase ni lista de asistencia donde verificar el grupo.

## Bloque D — Cierre

### D1 · Liquidación del profesor — NO SE PUDO

No se creó ni tomó asistencia en ninguna clase, por lo que la liquidación pedida
no era verificable dentro de la cadena.

### D2 · Recuento final — NO SE PUDO

El protocolo ordena frenar cuando un eslabón se traba. No se ejecutó el cierre
final después del bloqueo de B2.

## Lo que no se pudo probar y por qué

No se pudieron probar B3–D2 porque B2 no expuso ninguna cuota pendiente para
Lucía ni permitió ejecutar el primer cobro. La cadena se frenó en ese punto, sin
llamar servicios, escribir directamente en la base, corregir código ni fabricar
el estado faltante desde otra pantalla.

Dos incidencias anteriores alteran el estado físico respecto del escenario ideal
y quedan explícitas para cualquier recuento posterior:

- A9 guardó dos planes inválidos adicionales en `Patín — Avanzadas`: uno de `$0`
  y otro de `$5.000` resultante de escribir `-5000`.
- Por un error operativo del ejecutor al reutilizar el formulario de A4, se creó
  un tercer profesor de prueba (`Sin Direccion`). La ejecución limpia de A5 sí
  fue rechazada y no creó otro registro. El profesor accidental no se eliminó.
