# PROMPT — T113: Desvalidar una clase — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T113 `en_curso, tiene=Gemini`. **Acá implementás vos;** después controla Claude. Si tenés abierta la segunda vuelta de T12 y T13, cerrala antes y no mezcles commits.

## Qué pidió Carlos

**El dueño tiene que poder desvalidar una clase. Solo el dueño.** Validar es decir «esta clase se dio y se paga, aunque no tenga presentes». Hoy, una vez validada, el botón desaparece y no hay vuelta atrás.

**Fuera de esta tarea:** Validar en una clase futura se sigue ofreciendo. Carlos no pidió sacarlo. Con Desvalidar, ese error ya tiene arreglo.

## Qué hay hoy

- `resources/views/clases/show.blade.php:133`: el botón solo se muestra si la clase no está validada y no tiene presentes. Por eso el «Desvalidar» de la línea 143 nunca aparece.
- **Cuidado:** ese mismo `@if` envuelve el enlace **Editar** (línea 146). Editar tiene que seguir apareciendo exactamente en los mismos casos que hoy.
- `app/Http/Controllers/ClaseWebController.php:785`, `toggleValidada`: da vuelta el dato sin saber qué quería quien tocó el botón. Si la clase integra una liquidación cerrada, ya lo rechaza.
- `routes/web.php:228`: la ruta está dentro de `ensure.admin.web`.
- `app/Services/LiquidacionService.php:280`, `cerrarLiquidacion`: cierra sin recalcular, así que una liquidación abierta conserva las clases que ya contó.

## Qué tiene que pasar

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Dueño, clase validada sin liquidación | Ve **Desvalidar**. Al tocarlo: «Clase desmarcada de validación.», vuelve a ofrecer **Validar** y desaparece la marca «Validada para liquidación» |
| 2 | Clase futura validada por error, como la Hockey del 19/12 de la tanda 5 | Se desvalida igual. Deja de contar para una liquidación generada después |
| 3 | La clase desvalidada no tiene asistencias | Vuelve a contar en «Sin asistencia» si ya pasó, y vuelve a poder **Eliminarse** si cumple lo demás |
| 4 | Clase validada que después tuvo presentes | Se puede desvalidar. Se sigue pagando por los presentes: no cambia el importe |
| 5 | Clase en una liquidación **cerrada o pagada** | No ofrece Desvalidar. Forzado: el rechazo que ya existe. Nada cambia |
| 6 | Clase en una liquidación por hora **abierta** | No ofrece Desvalidar. Forzado: «No se puede desvalidar: la clase está en una liquidación abierta. Eliminá esa liquidación, desvalidá y generala de nuevo.» Nada cambia. Así no se paga una clase desvalidada al cerrar sin recalcular |
| 7 | Clase de una liquidación **cancelada** | Se puede desvalidar |
| 8 | Operativa y profesora | No ven Desvalidar. Forzado: 403 y nada cambia. Sin sesión: al ingreso |
| 9 | **Doble clic y pantalla vieja** | El botón manda lo que quiere hacer, no «dar vuelta». Dos pestañas abiertas: una desvalida y la otra toca Desvalidar con la pantalla vieja; la clase no vuelve a quedar validada. Lo mismo al revés con Validar. Texto: «La clase cambió mientras la mirabas. Recargá la pantalla.» |
| 10 | Validar | Sigue igual en todos los casos que hoy lo ofrecen |

## Pruebas

- Pruebas en `tests/` que fallen sin el arreglo, con cada caso de la tabla, y las de `tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php` al día si cambia el envío. Actualizá el número en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`: lo controla `DocumentacionNoMienteTest`.
- Al menos una mutación por regla: sacar el control de abierta, sacar el de cerrada, volver a «dar vuelta» y mostrarle el botón a la operativa. Cada una tiene que hacer fallar una prueba.
- En navegador real, con una copia local descartable: los casos 1, 2, 6 y 9, con captura. No escribas en el sitio de prueba.

## Reglas

- Carlos autorizó tocar esta vista solo para esto. Respetá `docs/03-diseno-ui/design-system/DESIGN-RULES.md`: mismo botón, mismo tamaño, una palabra. Prohibido JavaScript dentro de una vista (`AGENTS.md` §5-bis).
- Pruebas solo en `wings_testing_gemini`. Corré la suite rápida; la completa la corre Claude al controlar. Sin desplegar.
- Separá lo comprobado de lo leído. Citá `archivo:línea` reales.
- El catálogo y el manual no se tocan. `CLA-024` y `resources/manual/admin/programar-clases.md` quedan para Claude: proponé el cambio en tu informe.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T113-GEMINI.md`, con evidencia en una carpeta propia.
- T113 `a_verificar, tiene=Claude, hizo=Gemini`, con `php scripts/tablero/tablero.php cambiar`.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
