# Verificación de T54 — Claude, 27/10/2026

Entrega de Gemini en `c4094222`. Verificado contra DU-13, ALU-020 y el código.
Base descartable `wings_testing_claude`.

## Resultado: devuelta a Gemini por un lugar que quedó afuera

Lo hecho funciona. Pero el pedido decía «el mismo criterio en todos lados donde se busca un
alumno» y que se listaran los lugares encontrados. Quedó uno afuera y el informe no lo menciona.

## Lo que pasa

| Punto | Cómo | Estado |
|---|---|---|
| `Alumno::scopeBuscar`: cada palabra en nombre, apellido o DNI, en cualquier orden, con comodines escapados | Lectura del cuerpo | Verificado |
| Listado, autocompletar, Cobrar y la API usan el mismo scope | Diff de los cuatro controladores | Verificado |
| Autocompletar desde una letra, en el navegador y en el servidor, con espera de 250 ms entre teclas y como máximo 8 resultados | `alumnos-index.js` y `autocomplete` | Verificado por lectura |
| Acentos y mayúsculas: las columnas `nombre` y `apellido` usan `utf8mb4_unicode_ci` en el sitio de prueba y en producción | Consulta al servidor el 27/10 | Verificado |
| Pruebas de Gemini | `BusquedaAlumnosT54Test`: 10 aprobadas, 80 aserciones, corridas acá | Verificado |
| Sin cambios de vistas ni de CSS | Diff | Verificado |

## Lo que vuelve

1. **El Historial de caja no busca con el mismo criterio.** `CajaWebController::historial`
   (línea 301) filtra en PHP con `str_contains(mb_strtolower(...))` sobre el nombre del
   alumno, la observación y el subrubro. La comparación es exacta: «gomez» no encuentra un
   cobro de «Gómez», y «perez juan» no encuentra a «Pérez, Juan». Es el lugar donde la
   operativa busca el cobro de un alumno en el día. *Verificado por lectura del código;
   `str_contains` compara byte a byte.*
2. **Respuestas que llegan desordenadas** (*inferido, no reproducido*). Con una sola letra,
   el pedido de «g» trae más filas que el de «go». Si el primero llega después, el
   desplegable muestra los resultados de «g» con «go» escrito. `renderResults` no comprueba
   que la respuesta corresponda a lo que hay en el campo. Hay que descartar la respuesta
   si el texto del campo ya cambió.

`ProfesorWebController` busca profesores, no alumnos: queda fuera de T54.

## Aspecto

No cambia el diseño. Las capturas (`evidencia-t54`) muestran el desplegable con una letra;
las mira Carlos.

## Segunda vuelta — 27/10, commit `a9e7e37b`

**Aprobada en lógica.** Falta el OK visual de Carlos.

- Historial de caja: normaliza con `Str::ascii` y minúsculas la búsqueda y el texto de la
  fila (alumno, DNI, observación y subrubro). Cada palabra tiene que aparecer, en cualquier
  orden. «gomez» encuentra a «Gómez» y «perez juan» encuentra a «Pérez, Juan».
  *Verificado por lectura.* Cambio menor: antes, el texto completo tenía que aparecer en uno
  de los tres campos; ahora cada palabra puede estar en cualquiera.
- Autocompletar: descarta la respuesta si el campo ya no tiene el texto que la pidió.
  *Verificado por lectura.*
- Barrido: Gemini lista seis lugares tocados. Profesores y Cobranza no buscan alumnos por
  texto.
- `BusquedaAlumnosT54Test`: 14 aprobadas, 110 aserciones, corridas acá. Las 4 nuevas del
  Historial no se corrieron contra el código anterior; que fallarían con él es una
  inferencia (antes la comparación era exacta).
