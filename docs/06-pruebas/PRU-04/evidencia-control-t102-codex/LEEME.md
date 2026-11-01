# Evidencia de control T102 — Codex CAB, 01/11/2026

[Informe](../VERIFICACION-T102-CODEX.md). Solo wings_testing_codex y copia local aislada; sin producto, catálogo de casos ni despliegue.

- Originales:23/94. Propias:2/57; cuatro cruces concurrentes propios:4/40.
- pantalla-antes.json y pantalla-despues.json: doce tablas conservadas y catorce comparaciones monetarias, negocio/deporte.
- historia-ejecutada.json prueba eventos del nivel original incluso en un delta posterior y conserva septiembre cerrado.
- recibo-conservado.pdf y render: recibo directo cuya identidad, mes e importe se verificaron. Los PDF pantalla-antes/despues reutilizaron una caché del fixture anterior y quedan excluidos: auditoria-identidad-pdf.json.
- monitor-suite.jsonl conserva el falso positivo inicial por seguir solo hijos PHP directos. monitor-arbol.jsonl incluye cmd y todos los descendientes; parentesco-inicial.json comprueba el PID de PHPUnit.
- fuentes-antes-suite.json fija el corte y hashes; verificar-cierre.py comprueba integridad al finalizar.
- Suite completa: una única corrida; resultado final en resultado-suite.json. La aceptación del monitor se explica en revision-final.json, separada de sus datos originales.

Resultado: T102 devuelta por S1/S2 de aislamiento. Completa1428/51fallidas/6omitidas; diez archivos verdes aislados, inscripción diagnosticada y verde con preparación. Ningún defecto de negocio T102 comprobado. Las 878 fuentes quedaron intactas.

TXT normalizados a LF/sin espacios finales para Git; salidas-originales.zip conserva sus bytes anteriores. Los monitores originales no se reescriben para borrar incidentes.
