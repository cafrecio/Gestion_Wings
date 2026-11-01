# PROMPT — T94, segunda vuelta: corregir lo que devolvió el control — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. T102 ya está subida.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T94 `en_curso, tiene=Gemini`. T102 ya está subida (`a648ec9e`) y cambió `GrupoWebController` (`store`, `edit`, `update`, `toggleActivo`) y `grupos/_form.blade.php`: partí de ese código. Si `GrupoWebController.php` tiene cambios ajenos sin subir, **frená y avisale a Carlos**.

## Qué pasó

Claude controló `5bd97863` y la devolvió: [`VERIFICACION-T94-CLAUDE.md`](VERIFICACION-T94-CLAUDE.md). Lo central anda y se queda como está. Leé el informe y comprobá cada hallazgo vos, sin darlo por cierto: las pruebas que los muestran están en `docs/06-pruebas/PRU-04/evidencia-t94-control/`.

## Lo que hay que corregir

| # | Problema | Cómo tiene que quedar |
|---|---|---|
| H1 | `update` decide sin bloquear la fila del plan. Con otra conexión asignando un alumno, queda un alumno en una frecuencia desactivada, o da error del servidor | Bloquear las filas de los planes que se sacan, en orden estable, **antes** de contar alumnos. El resultado es el aviso «Nx por semana tiene N alumnos: cambiales el plan antes.» |
| H2 | Un formulario viejo que trae el `id` de una frecuencia desactivada la reactiva | Se rechaza con el aviso que ya existe: «Las frecuencias del grupo cambiaron. Volvé a cargar el formulario.» Reactivar es solo agregar una fila nueva |
| H3 | Quitar la fila y volver a cargar la misma frecuencia avisa «Frecuencia desactivada» | El aviso sale del resultado final: si la frecuencia sigue activa, «Grupo actualizado correctamente.» |
| H4 | Se mueven `alumno_planes`, `pagos` y `grupo_plan_precios` de una fila a otra | **Sacá toda esa consolidación.** Cambiar una fila a la frecuencia de una desactivada se rechaza: «Ya hay una Nx desactivada. Agregala con Nuevo en vez de cambiar esta fila.» Si una base ya tuviera una activa y una inactiva de la misma frecuencia, desactivar se rechaza con un aviso, sin error del servidor y sin tocar nada |
| H5 | Intercambiar dos frecuencias da error del servidor | Se rechaza con un aviso: «Ya hay una fila con esa frecuencia. Cambialas de a una.» |

**Lo que no se cambia:** las cuatro ramas de la tabla original, la reactivación por fila nueva, el filtro de activas en `show` y `edit`, y las reglas de alta, edición, cobro y `CambioPlanService`.

## Pantallas

Las vistas no se tocan. Si creés que hace falta, frená y avisale a Carlos.

## Documentos

- `resources/manual/admin/administrar-grupos.md` y su ficha, con los avisos nuevos.
- `Wings-contrato-alumno-grupo-deporte-deuda-v5.md` §2.c, si cambia alguna regla de las que escribiste.
- `IMPLEMENTACION-T94.md`: corregí el punto 10 de «Comprobado» y el de la colisión del índice. Sumá la suite con sus números, que faltaba.
- `docs/00-estado/ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md`, con el número de pruebas.

## Pruebas

- Pasá las pruebas de `evidencia-t94-control/` a `tests/Feature/`, con los nombres de clase que quieras. **Tienen que pasar sin cambiarles lo que esperan**, salvo la de H4, que además tiene que comprobar el aviso y que la fila editada no cambió.
- `ControlT94CarreraTest` usa dos conexiones reales: dejala en el grupo `lento`, como las demás de concurrencia. Reemplazá tu `test_concurrencia_sacar_frecuencia_mientras_se_asigna_alumno`, que no ejecutaba dos conexiones.
- Una prueba por cada aviso nuevo, y mutaciones sobre cada regla, dejando el código como estaba.
- Suite rápida en `wings_testing_gemini` (`--exclude-group=lento`), y las de `lento` de esta tarea. Nunca con otra suite corriendo en la misma máquina. **Solo esa base:** no uses la base local de trabajo. Sin despliegue: Cora trabaja en el sitio de prueba.

**«Comprobado» es solo lo que ejecutaste.** Lo que leíste o dedujiste va en «Inferido». Lo que no llegaste a hacer se dice.

## Cómo entregar

- Tablero: `php scripts/tablero/tablero.php cambiar T94 estado=a_verificar tiene=Claude`, con un paso que diga el resultado.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, y en cada uno mirar el `git diff` antes de agregarlo. Agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos. La carpeta es compartida con otros agentes.
