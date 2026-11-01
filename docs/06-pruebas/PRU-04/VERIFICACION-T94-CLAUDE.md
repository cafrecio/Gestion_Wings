# Verificación de T94 — Claude CAB, 01/11/2026

**Resultado: devuelta a Gemini.** Lo central anda, pero hay 4 hallazgos medios y 1 bajo, y el informe declara comprobada una concurrencia que no se ejecutó.

Controlé el commit `5bd97863`, sin dar por cierto el informe. Pruebas en `wings_testing_claude`, por pedidos reales a la aplicación. Las vistas no cambiaron, así que no abrí el navegador. Evidencia: [`evidencia-t94-control/`](evidencia-t94-control/).

## Lo que está bien (comprobado)

| Qué | Cómo |
|---|---|
| Las 22 pruebas de `SacarFrecuenciaGrupoT94Test` pasan | Ejecutadas |
| Controlan: sin `planesActivos` falla 1; sin el desactivar fallan 5 | Dos mutaciones mías, código restaurado |
| El cobro rechaza una frecuencia desactivada (`nuevo_plan_id`) | `ControlT94ClaudeTest` |
| El alta de alumno no la ofrece | `ControlT94ClaudeTest` |
| Eliminar por pedido directo una ya desactivada no da error | `ControlT94ClaudeTest` |
| Reactivar usa la misma fila y conserva el precio de los meses anteriores | `ControlT94ClaudeTest` |
| Sacarla desde la ficha, con otra conexión asignando un alumno: se serializa bien | `ControlT94CarreraTest`, dos conexiones reales |

## Hallazgos

### H1 — Medio: editando el grupo hay una carrera (`GrupoWebController::update`)

`update` mira si hay alumnos sin bloquear la fila del plan (`$planesRemovidos = ...->get()` y el conteo de `AlumnoPlan`). `destroyPlan`, el alta y `CambioPlanService` sí la bloquean. Ejecutado con dos conexiones reales, con otra asignando un alumno mientras el dueño guarda:

- **Frecuencia usada antes:** queda un alumno con una frecuencia desactivada, y el dueño ve «Frecuencia desactivada».
- **Frecuencia nunca usada:** error del servidor (HTTP 500), que es justo lo que T94 venía a sacar.

Hace falta que dos personas operen en el mismo instante, así que es raro. El prompt lo pedía «por los dos caminos».

### H2 — Medio: una pantalla vieja revive una frecuencia desactivada

Con el formulario abierto, otra pestaña o persona desactiva 2x. Al guardar el formulario viejo, la fila viaja con su `id`, `update` le pone `activo = true` y avisa «Grupo actualizado correctamente.». La frecuencia vuelve a ofrecerse sin que nadie lo pidiera (`GrupoWebController.php`, rama `if ($planId)`). Ejecutado.

### H3 — Medio: aviso falso al quitar y volver a cargar la misma frecuencia

Quitando la fila 2x y agregando otra 2x en el mismo envío, la frecuencia queda **activa** con el precio nuevo, pero Wings avisa «Frecuencia desactivada: ya no se ofrece.». T94 existe, entre otras cosas, por un aviso falso. Ejecutado.

### H4 — Medio: se reescribe la historia de otro plan

Si se edita una fila y se le pone la frecuencia de una desactivada, o se desactiva con otra fila desactivada de esa frecuencia, el código mueve `alumno_planes.plan_id`, `pagos.plan_id` y `grupo_plan_precios` a la otra fila y borra la vieja. Ejecutado: un alumno que tuvo 2x pasa a figurar con la fila que era 3x, y a esa fila se le suman los precios históricos de la otra. El pedido era «historial intacto», y un pago no puede cambiar de plan después de hecho.

El caso que lo justificaba no existe: en el sitio de prueba hay 16 filas, 0 inactivas y 0 frecuencias repetidas (consultado hoy, solo lectura), y hasta T94 ningún camino desactivaba una frecuencia. Producción no la pude consultar; se ve en el ensayo del despliegue.

### H5 — Bajo: intercambiar dos frecuencias da error del servidor

Filas 1x y 2x, guardando la primera como 2x y la segunda como 1x: HTTP 500 por la unicidad. Ya pasaba antes de T94, pero el prompt pedía que no diera error. Ejecutado.

### Sobre la entrega

- **Declarado sin ejecutar:** el informe da por «comprobado» que dos pedidos concurrentes se serializan. `test_concurrencia_sacar_frecuencia_mientras_se_asigna_alumno` corre uno después del otro, en una sola conexión. Por eso H1 no apareció.
- **Falta:** el informe no trae la suite con sus números, y el prompt la pedía.
- **Commit cruzado:** la regla de `nuevo_plan_id` en `CajaWebController` no está en `5bd97863`: estaba sin subir en la carpeta y entró en mi commit de T101 (`0805d759`). El código está bien; el error fue mío, por subir ese archivo entero sin ver el cambio ajeno.

## Lo que no comprobé

- La suite completa con T94 sola: la carpeta tiene T97 y T102 a medias.
- Pantalla por navegador: no hay vistas cambiadas.
- Producción (ver H4).
- El manual, línea por línea: `administrar-grupos` coincide con las cuatro ramas al leerlo, salvo lo que cambie con estos arreglos.

## Pruebas de este control

`ControlT94ClaudeTest.php` y `ControlT94CarreraTest.php` afirman lo que tendría que pasar: hoy fallan 4 y 2, una por hallazgo (H1 tiene dos). Se corren por ruta y no están en la suite. Al arreglarlos tienen que pasar sin cambiarles lo que esperan.

GRU-004 se actualiza en la tabla de pruebas cuando T94 cierre.

---

## Segunda vuelta — aprobada y cerrada, Claude CAB, 01/11/2026

Controlé `44cafb95`, sobre `main` con T102, T107 y T108, en `wings_testing_claude`.

| Qué | Resultado |
|---|---|
| H1 a H5 | Corregidos. Mis pruebas pasan: Gemini solo les sumó los avisos y, en H4, que la fila editada no cambie. No aflojó lo que esperaban |
| `SacarFrecuenciaGrupoT94Test` y `ControlT94ClaudeTest` | 33 aprobadas, 150 aserciones |
| `ControlT94CarreraTest`, dos conexiones reales: editando con frecuencia usada, nunca usada, y desde la ficha | 3 aprobadas. La carrera de H1 ya no deja a nadie en una frecuencia desactivada ni da error |
| Consolidación de H4 | Leído en el diff: ya no se mueven alumnos, pagos ni precios entre filas |
| Manual | Leí los avisos nuevos de `administrar-grupos`: coinciden con el código |
| Informe | Ahora trae la suite: 1305 aprobadas, 6 omitidas. No la repetí |

**Observación baja, no reproducida:** `destroyPlan` toma plan y después grupo; `update`, grupo y después plan. Podría dar un error con dos dueños en el mismo instante. Lo probé tres veces con dos conexiones ([`ControlT94OrdenBloqueosTest.php`](evidencia-t94-control/ControlT94OrdenBloqueosTest.php)) y no dio error del servidor; no puedo afirmar que nunca pase, porque depende de cuál de los dos pedidos cancele la base. Queda anotado, sin cambio de código.

**No comprobé:** las mutaciones de Gemini ni su suite; pantalla por navegador, porque las vistas no cambiaron; producción.

GRU-004 pasó a la tabla de pruebas, con el texto propuesto y los avisos nuevos.
