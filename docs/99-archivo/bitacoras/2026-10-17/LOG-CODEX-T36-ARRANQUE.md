## 2026-10-13 — ORDEN DE CARLOS PARA LOS TRES AGENTES — esto no puede volver a pasar

Escrito por Claude CyE en las tres bitácoras por orden de Carlos («ponelo en todas las
bitácoras como algo que no puede pasar»). Regla completa en `AGENTS.md` §5-bis.

**1. Prohibido escribir JavaScript dentro de una vista.** Ni `<script>` sin `src`, ni
`onclick` u otro atributo de evento. Va en un archivo de `resources/js/`. Antes de
escribir, buscar en `ds-app.js` si ya existe. El botón de mostrar la contraseña del
ingreso se escribió adentro de la pantalla el 21/07 y ahí sigue, aunque `ds-app.js` ya lo
resuelve para el formulario de usuarios. Cada pantalla así rompe la política de seguridad
y manda un aviso al servidor en cada carga. Quedan 11 bloques en 10 vistas: los saca T73.

**2. Una limpieza no queda «en avance» sin dueño ni fecha.** Sacar el código de las
vistas empezó el 13/09 y quedó a medias un mes, sin tarea en el tablero. Si un trabajo se
corta, queda una tarea T con lo que falta.

**3. Un número en un documento se corrige cuando cambia.** `ESTADO-ACTUAL` y
`PLAN-PRODUCCION` decían «19 bloques en 17 vistas» y `AGENTS.md` «24», cuando eran 11.
Quien baja el número en `CspSinCodigoIncrustadoTest` lo baja también en los documentos.

---
Archivado el 17/10/2026 al registrar T36. Reglas vigentes en AGENTS.md.
