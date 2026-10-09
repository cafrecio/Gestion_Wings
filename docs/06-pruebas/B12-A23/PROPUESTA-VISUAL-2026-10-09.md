# B12 — propuesta visual V3

09/10/2026 · Codex CyE · propuesta de ensayo, pendiente de Carlos.

Carlos pidió **«Hacelo mas visual y de menos lectura»** y aportó tres referencias:
[tarjetas con tendencias](referencia-visual-1.png),
[panel de gráficos](referencia-visual-2.png),
[tarjetas y composición](referencia-visual-3.png).
«El grafico no esta mal» fue una devolución favorable sobre V2, no aprobación de todo Reportes.

## Qué cambió

- Septiembre vs agosto: cuatro tarjetas con importes, tendencias y variación.
- Evolución mensual: gráfico de líneas conservado; últimos meses cerrados con datos.
- Cambio en resultado: barras firmadas por rubro, incluyendo efecto de ingresos y egresos.
- Octubre aparte: tres importes; origen de ingresos circular, destino de gastos en barras.
- Saldos acumulados en tres tarjetas; filas y explicaciones extensas bajo Detalle.
- Celular: tarjetas 2×2, gráficos en una columna, selects completos y Filtrar a la derecha.

Se conservan layout, tokens, componentes y semántica de Wings; las referencias son
inspiración, no una autorización para reemplazar el diseño compartido.
La relación Egresos/ingresos es del mismo mes cerrado y con fecha real de movimiento;
no representa costo de clases ni rentabilidad salarial. Es indicador propuesto, no regla nueva.

## Imágenes reales

[Escritorio 1440](capturas/finanzas-v3-escritorio.png) ·
[Marco real375](capturas/finanzas-v3-marco-375.png) · [visor](visor.html).

Blade de ensayo renderizado por solicitud autenticada a Laravel sobre
`wings_testing_codex`, con sesión/cache en memoria. HTML de salida sanitizado:
[pantalla](capturas/finanzas-v3.html). No se fabricó HTML para simular la aplicación.
Se conserva el control login375 previo. Las imágenes V2 siguen como antecedente.

Los filtros muestran aspecto; todavía no están conectados. Esta propuesta no agrega
rutas de Reportes ni sustituye vistas habituales. Inicio continúa como fue aprobado;
Ver/cifras de detalle siguen deshabilitados. Alumnos/Sueldos permanecen pendientes.

## Verificación

Revisión independiente de fuente y las dos capturas: gráficos renderizados, números
y porcentajes conciliados, filtros/etiquetas/pie completos, sin recorte en375.
Se corrigieron etiquetas negativas sobre barras; importes parciales y comparación
indisponible quedan identificados. Desglose accesible por HTML oculto solo visualmente.

Septiembre: ingresos750.000, egresos75.000, resultado675.000, egresos/ingresos10%.
Cuotas+150.000 y ventas−25.000 explican resultado+125.000 frente a agosto.
Octubre: ingresos660.000, gastos75.000, resultado585.000; cuotas90,9%/ventas9,1%.
Disponible4.360.000, por cobrar690.000 y por pagar90.000, según escenario ficticio.
Signos de contraasientos conservados. El circular solo representa categorías netas
no negativas; si hay netos negativos se usan barras. Ningún importe nulo se convierte en cero.

PHP y JS sin errores de sintaxis; Blade compila. CSS privado bajo `.reporte-visual`,
sin modificar `app.css`, `sidebar.css`, layouts ni vistas de producción.
Build final33,73s: módulo visual4,88KB (gzip2,14KB), CSS5,45KB (gzip1,31KB),
Chart.js compartido172,47KB (gzip60,30KB). Salida local `storage/app/reportes-v3-build.txt`.
Suite completa en `wings_testing_codex`: **532 aprobadas/2 omitidas, 4286 aserciones,
580,76s**. Salida local `storage/app/reportes-v3-suite.txt`. Escenario ficticio restaurado
después de la suite; no usar su base en paralelo a pruebas.
La revisión independiente no ejecutó suite ni accedió a la base durante la corrida.

El render final encontró una directiva Blade pegada al texto de clasificación,
que causaba500 en la propuesta. Corregida; solicitud real posterior200 y PHP
compilado de esa vista sin errores. El script corta con salida1 ante P1 pendiente
o respuesta distinta de200: no seguir capturando un HTML anterior tras fallar.
Tres controles de render real, con datos modificados solo en memoria: último mes
sin clasificar, anterior sin clasificar y desglose accesible. Todos correctos;
no fabrican comparación ni pierden signos. Salida `storage/app/reportes-v3-casos.txt`.

## Continuidad

Carlos elige V3 antes de aplicar la pantalla final. Luego: rutas/filtros/detalle,
Reporte Alumnos y análisis Sueldos; atribución de cuotas por profesor e historia
requieren definiciones verificables. No cambiar comisiones para construir reportes.
Estado B12/A23 en curso, sin despliegue ni modificaciones a la base del club.
