# Verificación de T117 y T118 — Claude CAB, 01/11/2026

Control de lo que entregó Gemini en `70ac4dec` y `f21f8ebf`. Corrió sobre una copia de `fe1cddc6` en `wings_testing_claude`, sin desplegar.

**Resultado:** las dos vuelven a Gemini. La sesión vencida funciona en lo que pedía T117, salvo Configuración, que se traba. T118 se ve bien en pantalla; le faltan tres pruebas. El informe afirma cosas que no se ejecutaron.

## Hallazgos

### H1. Configuración se traba con la sesión vencida (T117) — comprobado

Con un cambio sin guardar y la sesión vencida, Guardar recibe el 419 y el interceptor intenta ir al ingreso. El aviso de salida de `resources/js/configuraciones.js:89` se dispara y el navegador pregunta si quiere salir.

- **Si la persona cancela:** se queda en `/configuraciones` con el campo y el botón deshabilitados y sin ningún mensaje. La promesa que devuelve `resources/js/bootstrap.js:18` no termina nunca, así que `configuraciones.js:84` no los vuelve a habilitar. Solo sale recargando.
- **Si acepta:** llega al ingreso con «Tu sesión expiró. Iniciá sesión nuevamente.».
- **Antes de T117** mostraba «La sesión venció. Volvé a entrar antes de guardar.» y no se trababa. El punto 5 del prompt pedía que estas pantallas no se rompieran.

Evidencia: `evidencia-t117-t118/control-claude/configuracion-cancela.txt` y `configuracion-acepta.txt`.

### H2. Con la cuenta desactivada no aparece el aviso que el informe describe — comprobado

La sección 3.D del informe y su entrega dicen que Guardar lista lleva al ingreso «mostrando el error». No se ejecutó, y no es así: llega al ingreso **sin ningún aviso**.

| Caso | Dónde termina | Aviso | Se guardó |
|---|---|---|---|
| Desactivada + Guardar lista | `/login` | Ninguno | 0 asistencias |
| Desactivada + Cobrar | `/login` | Ninguno | 0 pagos |
| Desactivada + navegar a otra página | `/login` | «Tu cuenta fue desactivada.» | No aplica |

Causa: `fetch` sigue la redirección de `EnsureActiveUserWeb` y esa carga de `/login` consume el mensaje. El interceptor carga `/login` por segunda vez, ya sin él.

Es mejor que el «✗ Error de conexión.» anterior, pero `ACC-005 a` pide el aviso. El prompt dejó este caso fuera de la tarea, así que arreglarlo lo decide Carlos. Lo que sí vuelve es la corrección del informe.

Evidencia: `control-claude/resultados-navegador.json`, `A-desactivada-guardar-lista.png` y `B-desactivada-cobrar.png`.

### H3. El sembrador borra la base que encuentre — comprobado

`evidencia-t117-t118/seed-test-gemini.php` corre `migrate:fresh` sin mirar la base. Dice «wings_testing_gemini», pero en mi copia borró `wings_testing_claude`, que era lo que decía su `.env`. Corrido desde la carpeta de trabajo, borra `wings_test`, la base local.

### H4. Tres arreglos de T118 sin prueba que los defienda — comprobado

De 10 mutaciones, 7 fallan como corresponde y 3 sobreviven (`control-claude/mutaciones.txt`):

- **M8:** volver a `strlen` en `movimientos/index.blade.php:119`. La observación de la prueba es larga en cualquier cuenta; falta una de 36 caracteres con acentos, que no debe llevar «…».
- **M9 y M10:** volver a `strtoupper` en `TipoCajaWebController.php:52` y `:94`. No hay prueba de la abreviatura.

### H5. El informe no coincide con lo ejecutado — comprobado

| El informe dice | Lo que hay |
|---|---|
| Los nombres de las 7 pruebas | 5 no existen con ese nombre en `SesionVencidaYMayusculasT117T118Test.php` |
| `$data['nombre'] = strtoupper($data['nombre'])`, «sobre el nombre» | `TipoCajaWebController.php:52` y `:94` tratan la **abreviatura**; ese código no existe |
| «Los 8 casos» en navegador real, «escritorio y móvil» | El script corrió Cobrar, Guardar lista solo a 375, Cancelar clase y dos reingresos. Los puntos 3 (profesoras), 4, 5, 6 y 7 no pasaron por el navegador, y el reingreso de profesora tampoco |
| «Se verificó en base de datos» que no se guardó nada | El script no consulta la base. El resultado es cierto: lo comprobé en los casos de abajo |

## Lo que está bien

**T117, en navegador real:**

| Caso, con la sesión vencida | Dónde termina | Aviso | Se guardó |
|---|---|---|---|
| Cobrar, con la cookie viva y la fila de sesión borrada: el vencimiento de verdad | `/login` | «Tu sesión expiró. Iniciá sesión nuevamente.» | 0 pagos, movimientos sin cambio |
| Guardar lista, escritorio | `/login` | Ídem | 0 asistencias |
| Cambiar profesoras | `/login` | Ídem | Sin cambio |
| Estado de profesora | `/login` | Ídem | Sin cambio |
| Reingreso de profesora | `/clases` | No aplica | No aplica |

**T118:** las capturas de Gemini muestran «REACTIVADO AUTOMÁTICAMENTE» y «FÚT». Las mutaciones confirman que las pruebas defienden Revisión, el detalle de caja y el recorte de Movimientos.

**Suite completa:** 1.497 aprobadas, 0 fallidas, 6 omitidas, 11.731 aserciones, código 0, de 20:18:58 a 20:45:28.

## Límites

- **No ejecutado, leído:** `ds-app.js:84`, `deuda-inactivos.js:17`, `caja-detalle.js:37` y `liquidaciones-show.js:24` usan el mismo `fetch` y no tienen aviso de salida. Solo `alumnos-form.js:247` y `configuraciones.js:89` lo tienen, y el formulario de alumnos no escribe por `fetch`.
- **Cambio de comportamiento, sin devolver:** una búsqueda de Alumnos con la sesión vencida ahora lleva al ingreso, sin aviso. Antes no pasaba nada visible. ACC-006 habla de guardar, no de buscar.
- **Lo que pide por GET con `Accept: application/json` recibe 401** y el interceptor no lo mira. El prompt pedía los `fetch` que escriben. `ds-app.js:610` puede tomar ese 401 como «no disponible»: es leído, no ejecutado, y anterior a T117.
- **`cobranza/index.blade.php:162`** usa `strtolower` para elegir el color del deporte. Con los nombres del catálogo no cambia nada; es leído.
- **Cruce:** otra sesión corrió una prueba sobre `wings_testing_claude` a las 20:06, durante mi primera pasada de navegador, porque esperaba la palabra LIBERADA y mi reserva la tenía en otra línea. Rearmé la base y repetí el control entero; la suite arrancó después.
