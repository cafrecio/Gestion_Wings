# PROMPT — T94: sacar una frecuencia de un grupo — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa a Gemini. Lo controla Claude.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T94 `en_curso, tiene=Gemini`.

## El problema

Sacar una frecuencia de un grupo falla de dos maneras, por los dos caminos que hay: editar el grupo y borrar la fila (`GrupoWebController::update`, líneas 180-189) o tocar **Eliminar** en la ficha (`destroyPlan`, línea 315).

- **Si alguna vez se usó:** da error del servidor. El control solo mira `alumno_planes.activo = true`, pero un cambio de plan deja la asignación vieja con `activo = null` (`CambioPlanService.php`), y `alumno_planes.plan_id` y `pagos.plan_id` no dejan borrar. Lo ejecutaste vos el 30/10: HTTP 500 por los dos caminos.
- **Si tiene alumnos hoy, editando el grupo:** Wings no la saca y dice «Grupo actualizado correctamente.». Es un aviso falso.

## Lo que hay que hacer

Carlos eligió **desactivar**. La tabla `grupo_planes` ya tiene `activo`, así que no debería hacer falta tocar la estructura de la base.

| Caso | Qué tiene que pasar | Aviso |
|---|---|---|
| Nunca se usó | Se borra, como hoy | El de hoy |
| Se usó antes, sin alumnos hoy | Se desactiva: no se ofrece más; el historial, los pagos y las deudas quedan intactos | «Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.» |
| Tiene alumnos hoy | No se saca | «2x por semana tiene N alumnos: cambiales el plan antes.» Si eran varias filas o había otros cambios en el formulario, no se guarda nada |
| Es la última frecuencia activa | No se saca | El aviso que ya existe: el grupo quedaría sin frecuencias |
| Volver a agregar una frecuencia desactivada | Se **reactiva esa misma fila** con el precio nuevo. No se crea otra | «Grupo actualizado correctamente.» si de verdad se guardó |

**«Tiene alumnos hoy»** es cualquier asignación con `activo = true`: también un alumno inactivo, y también un cambio de plan programado para más adelante. Si encontrás que eso deja afuera o suma de más a alguien, frená y decilo en el informe.

## Cuidado con esto

- **La base admite una sola fila activa y una inactiva por frecuencia** (`unique` de `grupo_id`, `clases_por_semana` y `activo`, en `create_grupo_planes_table`). Hoy `update` ve la inactiva como «ya existe» y no crea nada, pero dice que guardó (líneas 201-212). `storePlan` solo mira las activas y crea otra fila: desactivar esa después da error. Tiene que quedar una sola fila por frecuencia. Si en una base ya hay dos, no puede dar error del servidor.
- Editar una fila y cambiarle la frecuencia a una que ya existe, activa o inactiva, tampoco puede dar error del servidor.
- **Mostrar solo las activas:** el formulario y la ficha traen todas (`grupos/_form.blade.php:5`, `grupos/show.blade.php:111` y `121`). `destroyPlan:323` cuenta las inactivas para decidir si es la última. Si no, una desactivada volvería a aparecer, y al guardar el formulario se reenviaría.
- **Pedidos directos:** el alta y la edición de alumno aceptan cualquier `plan_id` del grupo (`AlumnoWebController.php:372`, `506`, `508`), y el cobro y el cambio de plan buscan el plan sin mirar `activo` (`CajaWebController.php:970`, `AlumnoWebController.php:542`). Una frecuencia desactivada no se tiene que poder asignar por ningún camino.
- **Lo viejo tiene que seguir funcionando:** cobrar una deuda vieja de ese plan, el precio por período (`precioParaPeriodo`), recibos, reportes y liquidaciones. `PagoCuotaService.php:477` busca el plan sin mirar `activo`: ahí está bien y no hay que cambiarlo.
- Buscá también los demás lugares que usan `planes` o `GrupoPlan` y decí en el informe por qué cada uno queda bien.

## Pantallas

**El diseño no se toca sin el OK de Carlos.** Esto no agrega pantallas ni botones, y las frecuencias activas se pueden filtrar desde el controlador, sin cambiar las vistas. **Eliminar** y su confirmación en la ficha siguen igual. Si creés que hay que cambiar una vista, frená: armá capturas en `docs/06-pruebas/PRU-04/APROBAR-T94.html` sin tocar la vista real y avisale a Carlos que las abra con `Start-Process chrome "file:///C:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/APROBAR-T94.html"`.

## Documentos

Lo que el cambio vuelve falso se corrige en el mismo commit:

- `resources/manual/admin/administrar-grupos.md`, líneas 53 a 56 y lo que corresponda, condición por condición contra el código (`docs/06-pruebas/PRU-04/REVISOR-MANUAL.md` punto 6). Corré `php scripts/manual/verificar-manual.php` y actualizá su ficha en `evidencia-t12-t13/fuentes/`.
- `docs/06-pruebas/PRU-04/IMPLEMENTACION-T12-T13.md` §15, huecos 3 y 6.
- El contrato del área, si dice algo sobre sacar frecuencias: buscalo.
- `docs/00-estado/ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md`, con el número de pruebas (lo controla `DocumentacionNoMienteTest`).
- La tabla de pruebas no se toca (`AGENTS.md` §5-ter): proponé en el informe cómo queda GRU-004 y Claude la pasa.

## Pruebas

- Una prueba por cada fila de la tabla y por cada punto de «Cuidado con esto», por los dos caminos. Tienen que fallar si se saca la regla: probalo con mutaciones y dejá el código como estaba.
- Una de punta a punta: alumno con el plan 2x, cambia a 3x, se desactiva 2x, se cobra una deuda vieja de 2x, se genera el mes siguiente, se reactiva 2x con otro precio y un alumno nuevo la toma. Importes al peso.
- Dos pedidos a la vez: sacar la frecuencia mientras otro le asigna un alumno.
- Suite rápida en `wings_testing_gemini` (`--exclude-group=lento`), y la completa si tocás algo marcado `lento`. Nunca con otra suite corriendo en la misma máquina.

## Reglas

- Pruebas solo en `wings_testing_gemini`. **No uses la base local de trabajo.** Sin despliegue: Cora trabaja en el sitio de prueba.
- Solo T94. Si encontrás otra cosa, se informa y no se arregla.
- **La carpeta es compartida:** Codex está con T97 (Cashflow y transferencias) y T100 (`PagoCuotaService`, cancelar adelantos) puede estar sin subir. No toques esos archivos. Si `DocumentacionNoMienteTest` no coincide por pruebas ajenas sin subir, decilo en el informe en vez de ajustar el número a ellas.
- Si algo de este prompt no coincide con el código, confiá en el código y decilo en el informe. Si cambia una regla que decidió Carlos, frená y consultalo.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T94.md`: qué se hizo, los lugares revisados, lo comprobado y lo inferido por separado, suite con sus números, cómo queda GRU-004.
- Tablero: `php scripts/tablero/tablero.php cambiar T94 estado=a_verificar tiene=Claude`, con un paso que diga el resultado.
- Entrada en tu bitácora `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
