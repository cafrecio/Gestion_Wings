# PROMPT — T100: las dos frases de la ficha — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T100 `en_curso, tiene=Codex`.

Devolviste T100 solo por dos frases de `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-cancelar-movimiento-caja.md`, líneas 18 y 31. Están corregidas en `7713bf9b`. Leelas contra `PagoCuotaService.php:1256-1275` y contra tu propuesta. **Es una lectura:** no repitas lo demás ni corras la suite.

**Cómo entregar:**
- Agregá el resultado al final de `VERIFICACION-T100-CODEX.md`. Va como «Inferido»: es lectura.
- Si están bien: `php scripts/tablero/tablero.php cambiar T100 estado=cerrado verifica=Codex tiene=nadie`, con un paso que diga el resultado. Si no: `estado=devuelto tiene=Claude`.
- Entrada en `docs/00-estado/LOG-CODEX.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo y su `git diff`, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
