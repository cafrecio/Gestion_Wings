# Incoherencia: activar y desactivar se hace de tres formas

> Detectado por Carlos el 30/08/2026, probando la extracción de scripts.
> **No lo causó ese cambio: es previo.** Verificado archivo por archivo.

## El problema

La misma acción —activar o desactivar un registro— está resuelta de tres maneras
distintas, con dos palabras distintas.

| Mecanismo | Pantallas | Palabra |
|---|---|---|
| **Interruptor** `x-ds.toggle` | Alumnos, Deportes, Grupos, Profesores, Usuarios, Configuración | Activo / Inactivo |
| Botón | Tipos de caja (`tipos-caja/index.blade.php:78`) | **Desactivar** / Activar |
| Botón | Subrubros (`rubros/index.blade.php:84` y `:174`) | **Pausar** / Activar |

**Son 6 pantallas contra 2.** El interruptor es el estándar del sistema; las otras
dos son la excepción.

## Por qué es un olvido y no una decisión

Los formularios de edición de **tipos de caja** (`tipos-caja/_form.blade.php`) y de
**subrubros** (`subrubros/_form.blade.php`) **ya usan el interruptor**.

O sea que el componente está disponible y en uso dentro de esas mismas áreas: lo que
falta es usarlo también en el listado. Si fuera una decisión de diseño, el formulario
tampoco lo usaría.

## Además, dos palabras para lo mismo

"Desactivar" y "Pausar" describen la misma operación sobre el mismo campo (`activo`).
El usuario no tiene forma de saber si son cosas distintas.

## Qué habría que hacer

Reemplazar los botones por el interruptor en los dos listados, y que el estado se
diga siempre igual.

**Diferencia de comportamiento a tener en cuenta:** el interruptor cambia el estado
**sin recargar la página**; los botones envían un formulario y recargan. Al unificar,
esas dos pantallas van a dejar de recargarse, que es el comportamiento correcto y el
que ya tienen las otras seis.

## Estado

**Pendiente, no urgente.** Carlos pidió expresamente **no tocar subrubros por ahora**:
ese listado tiene además otros problemas visuales que quiere revisar aparte.

Toca vistas, así que va con él mirando.
