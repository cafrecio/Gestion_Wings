# A10 — primera revisión de Claude (08/10/2026): evidencia negativa, conservada

Resultado de la primera revisión: **A10 devuelto a Codex** porque Limpiar perdía el período en el navegador.
Estos archivos no se regeneran; la segunda revisión escribe en la carpeta de arriba.

- `VERIFICACION-A10-primera-devolucion.md`: el informe tal como quedó al devolver. Sus enlaces eran relativos a
  `docs/06-pruebas/PRU-02/`; lo que citaban está ahora en esta carpeta (`capturas/`, los dos JSON).
- `resultado-navegador.json`: recorrido en Chrome con los cuatro «MAL» de Limpiar.
- `resultado-http.json`: ensayo HTTP propio, 9 pruebas y 6.697 aserciones.
- `capturas/`: 12 capturas; `04-…` es antes de Limpiar y `05-…` después, con el período ya perdido.
- `recorrer-navegador-primera.mjs` y `VerificacionA10ClaudeTest-primera.php.txt`: el recorrido y el ensayo como se corrieron.
- `HUELLAS-SHA256.txt`: huella de cada archivo.
