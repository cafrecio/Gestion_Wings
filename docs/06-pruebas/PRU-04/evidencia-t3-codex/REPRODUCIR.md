# Evidencia propia de T3 — Codex CyE, 16/10/2026

Base de código examinada: `f4e6436`. Todo cambio de prueba se hizo en una copia
ignorada bajo `storage/app/verificacion-t3-codex-f4e6436`, creada desde `git archive`.
No se copiaron `.env`, sesiones ni vendor; las pruebas documentales cargaron el
autoload del vendor existente y los archivos de test de la copia por ruta absoluta.
No usan Laravel ni base de datos. Los procesos recibieron DB_DATABASE=wings_testing_codex.

Preparación ejecutada desde la raíz: crear un directorio nuevo, `git archive
--format=tar --output=<copia>/fuente.tar f4e6436`, extraer allí, quitar solo ese tar,
`git init`, `git config core.autocrlf false`, `git add --all` y commit local de base.
No se usaron stash, checkout ni reset en la carpeta compartida.

Luego:

```text
node docs/06-pruebas/PRU-04/evidencia-t3-codex/auditar-migracion.mjs
node docs/06-pruebas/PRU-04/evidencia-t3-codex/probar-copia.mjs
node docs/06-pruebas/PRU-04/evidencia-t3-codex/probar-fecha-cierre.mjs
```

Los dos últimos verifican que la ruta sea la copia, guardan y restauran sus cinco
archivos y comparan SHA del tablero compartido. El primero solo lee Git y escribe
la evidencia. No son una corrección del generador ni pruebas añadidas a la suite.

- `migracion.json`: 70 comparaciones por ID; estados, encabezados y cuerpos.
- `ENCABEZADOS.md`: los 56 IDs con diferencias de encabezado, antes y después.
- `pruebas-copia.json`: escrituras, diffs, corrupciones deliberadas, CRLF y restauración.
- `fecha-cierre.json`: fallo semántico de la fecha de cierre y guardián verde.
- `guardianes.txt`: los cuatro guardianes juntos, 9 pruebas/31 aserciones.
- `suite-completa.txt` y `suite-salida.json`: corrida completa propia; no números heredados.

Las salidas de fallos deliberados son evidencia de controles funcionando. No se
confunden con errores de la suite completa. No se cambió la fecha del sistema;
la independencia de la generación respecto del día se comprobó leyendo el código.
