# Ajuste de liquidación — etiquetas e íconos, 10/10/2026

Codex CyE. Carlos preguntó si el campo era la diferencia, si admitía negativos y pidió íconos.
Se mantiene la regla elegida antes: el administrador escribe el **total a pagar**.

- Campo **Monto ajustado**, con ayuda: «Total a pagar, desde $0. La diferencia se calcula automáticamente».
- Ejemplo: cálculo $20.000, total ingresado $18.000, diferencia −$2.000.
- Total cero o positivo; negativos rechazados por navegador y servidor. La diferencia puede ser negativa.
- Nueve íconos del sistema existente: seis labels de formularios y tres importes. SVG decorativos, ayuda accesible.
- Cálculo original, controles, auditoría, estados, pago y nombres internos de campos conservados.

## Aprobación

Carlos revisó [escritorio](capturas/ajuste-final-abierto-escritorio.png) y
[celular375](capturas/ajuste-final-abierto-marco-375.png), y respondió **«Sí, así»**.
Aspecto del ajuste aprobado; verifica **Carlos** según AGENTS.md §6a.

## Verificaciones y alcance

PHP sin errores; vistas compiladas y limpiadas. Respuestas HTTP200 auténticas de Laravel;
seis PNG renovadas, formulario abierto/cerrado y liquidación pagada; tokens FICTICIO.
Chrome comprobó los seis labels con sus campos, íconos y ayuda; −1 rechazado, 0 y18000 admitidos sin enviar formularios.
Control independiente de fuente y cuatro PNG aprobado; nombres, valores y restricciones conservados.
La fuente muestra diferencia con signo; estas capturas contienen ajustes positivos.

Marco375: formulario plegado scroll375; abierto scroll382 por el selector de caja.
Mismo selector y medida382 en el HTML real anterior del repositorio; no se introdujo por las etiquetas.
Las imágenes aprobadas conservan ese comportamiento previo; no certifican toda la pantalla sin desbordes.

Suite completa en wings_testing_codex: 572 aprobadas, 2 omitidas y 1 falla documental; 4612 aserciones, 733,56s. El contador vio583 métodos mientras arrancó con575, por ocho pruebas agregadas simultáneamente por otro agente. Sin fallas funcionales. El guardián documental repetido sobre esta entrega aislada (575 métodos) aprobó: 1 prueba/7 aserciones,5,61s.
Otro agente está agregando CBU/alias y ocho pruebas en la misma carpeta; sus cambios se excluyen de esta entrega.

## Continúa

B12 sigue en curso: prueba manual y clasificación de antecedentes. A23 conserva cierre de Claude.
No se desplegó este retoque ni se modificó la base del club.
