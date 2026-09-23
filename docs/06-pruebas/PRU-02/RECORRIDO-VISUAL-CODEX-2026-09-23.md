# Recorrido visual de Codex — 23/09/2026

Sitio: https://test.gestionar-te.com.ar. Chrome, escritorio 1366×900 y celular 390×844. Cuentas de prueba: Admin Prueba, Sandra Vidal (OPERATIVO), Lucía Gaitán (PROFESOR).

Los siete hallazgos nuevos están en [DEFECTOS.md, A36–A42](DEFECTOS.md#complemento-visual-de-codex--23092026), con una línea y captura por defecto. Se volvió a leer el registro al finalizar para excluir los A17–A35 incorporados durante el recorrido por otro agente.

## Cobertura

Cada resultado de la tabla se observó en escritorio y a ancho de celular. “Restringida” significa que se intentó abrir la dirección conocida desde ADMIN y se observó la denegación o redirección; no significa que se revisó contenido al que ese rol no accede.

| Pantalla | ADMIN | OPERATIVO | PROFESOR |
|---|---|---|---|
| Inicio | Dashboard | Inicio | Inicia en Clases |
| Cobranza | Listado y filtros | Listado y filtros | Acceso denegado |
| Caja | Listado vacío y filtros | Sin caja abierta, accesos Nuevo y Cobrar | Acceso denegado |
| Alumnos: listado | Revisado | Revisado | Acceso denegado |
| Alumnos: ficha | Acosta, Alan | Acosta, Alan | Sin acceso al módulo |
| Alumnos: alta | Formulario abierto | Formulario abierto | Sin acceso al módulo |
| Alumnos: edición | Formulario abierto | Formulario abierto | Sin acceso al módulo |
| Clases: listado | Revisado | Revisado | Revisado |
| Clases: ficha y asistencia | Patín Federadas, 24/09 | Patín Federadas, 24/09 | Patín Principiantes, 28/09 |
| Clases: alta | Formulario abierto | No ofrece Nuevo | No ofrece Nuevo |
| Revisión | Sin revisiones | Sin revisiones | Acceso denegado |
| Movimientos | Sin movimientos, filtros | Sin movimientos; se abre por dirección, falta en menú | Acceso denegado |
| Cashflow | Totales y estado sin movimientos | Redirige a Caja | Redirige a Clases |
| Liquidaciones | Sin liquidaciones, filtros | Redirige a Caja | Redirige a Clases |
| Grupos | Seis grupos | Seis grupos | Acceso denegado |
| Niveles | Cuatro niveles | Redirige a Caja | Redirige a Clases |
| Profesores | Cinco profesores | Redirige a Caja | Redirige a Clases |
| Rubros | Ingresos y egresos | Redirige a Caja | Redirige a Clases |
| Tipos de caja | Cinco tipos | Redirige a Caja | Redirige a Clases |
| Configuración | Campos y reglas de primer cobro | Redirige a Caja | Redirige a Clases |
| Usuarios | Siete usuarios | Redirige a Caja | Redirige a Clases |

También se abrió con OPERATIVO el formulario de cobro de Benegas, Abril: deuda visible de septiembre por $43.000. No se emitió el cobro. En edición de Acosta se reingresó el mismo DNI para observar el aviso de inscripción y se salió con Cancelar, sin guardar.

## Límites del resultado

- Inspección visual de los datos disponibles y formularios, sin leer código, consultar la base ni ejecutar pruebas. El único documento del proyecto consultado para la auditoría fue DEFECTOS.md.
- No se guardaron altas, cambios, asistencias, cobros, anulaciones ni liquidaciones. Por lo tanto, no se afirma que sus validaciones al guardar funcionen ni que permitan una operación solo porque exista un botón.
- Caja, Movimientos, Revisión y Liquidaciones no tenían registros en las vistas recorridas. No se verificaron detalles de cajas cerradas, reversión de cobros, recibos emitidos ni liquidaciones pagadas.
- No se repiten como nuevos los problemas ya registrados, ni se ratifican afirmaciones técnicas de otros autores a partir de estas capturas.
- Se restauró el tamaño habitual del navegador y se dejó la cuenta ADMIN en Alumnos. No se modificaron código, configuración del sitio ni tableros.

Capturas del recorrido: [carpeta de evidencia](evidencia-codex-visual-2026-09-23/). Los archivos cuyo nombre incluye `acceso` documentan el resultado de intentar entrar a una sección con otro rol, no el acceso efectivo a esa sección.
