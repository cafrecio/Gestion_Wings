# PRU-03 · Verificación de Claude sobre el informe de Gemini

10/10/2026 · Claude CyE · contra la base del sitio de prueba (solo lectura) y el commit `7b10b8c`.

## Verificado: lo que se cargó es lo que dice el Excel

Comparé las 90 filas de `PADRON-PRU-03.xlsx` contra la base, dato por dato, no una muestra.

| Qué | Resultado |
|---|---|
| Filas del Excel / alumnos en la base | 90 / 90, ninguno de más ni de menos |
| DNI, apellido, nombre, nacimiento, ingreso, celular, email, tutor, teléfono del tutor, deporte y grupo | 0 diferencias |
| Plan (uno activo por alumno, del grupo correcto, vigente desde la fecha de ingreso) | 0 diferencias |
| Deuda: cada mes y cada importe | 53 cuotas por $1.801.000, 0 diferencias, todas pendientes y sin pago |
| Inscripción | 8 cargos de $5.000, uno por DNI; la alumna de dos deportes tiene uno solo |
| Plata | 0 pagos, 0 movimientos de caja, 0 movimientos de cashflow |

El hallazgo de Gemini es cierto: una fecha de ingreso futura se acepta
(`PrimeraCargaExcelService.php:155` solo controla que sea posterior al nacimiento).

## No verificado por Gemini, aunque el informe lo da por bueno

- **Si es práctico para una persona.** La prueba entera duró unos 20 minutos y la hizo un
  programa que maneja el navegador. El Excel lo generó otro programa. Nadie abrió la
  plantilla, usó las listas desplegables ni corrigió a mano sobre el Excel marcado. Los
  «tiempos y sensaciones» del informe no salen de haberlo hecho: son opinión.
- **Miró el código** para explicar comportamientos (cita `Str::ascii()` y el servicio) sin
  anotarlo como hallazgo, que era la regla 3.
- **Entró a las pantallas escribiendo la dirección, no tocando el menú.** Por eso
  `01b-redireccion-inicio.png` y `02-paso1-catalogos.png` son una página «404 NOT FOUND» y
  el informe describe la primera como si mostrara la redirección.
- **La fila repetida** no se probó (se pidieron 12 errores, se hicieron 11).

## El padrón es realista solo a medias

Edades, grupos y reparto de deuda están bien. Pero 59 de los 90 alumnos tienen de tutor
«Tutor Apellido» y de correo el nombre del propio nene: se nota el relleno. Solo 5 ingresos
son de 2026 y hay alumnos de 2025 marcados como que deben inscripción.

## Capturas para el manual: no alcanzan

Faltan todas las del Excel (plantilla abierta, hoja Guía, lista desplegable, Excel
marcado). Dos son páginas 404. En `capturas/fichas/` hay 178 archivos que son 24 imágenes
repetidas con nombres que no corresponden al alumno.

## Hallazgos propios al revisar

- El aviso dice «Inicio» pero el botón del menú del admin dice «Dashboard».
- La página de dirección inexistente sale en inglés («404 NOT FOUND»).
