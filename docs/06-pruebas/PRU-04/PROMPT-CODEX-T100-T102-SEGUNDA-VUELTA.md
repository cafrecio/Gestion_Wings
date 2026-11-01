# PROMPT — T100 y T102: segunda vuelta, solo lo devuelto — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026. Carlos se lo pasa cuando Gemini termine T107 y T108.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T100 y T102 `en_curso, tiene=Codex`.

Tu primer control ya comprobó la plata y el negocio: **no los repitas**. Controlá solo lo que devolviste, sin dar por cierto nada de lo que dice este prompt. No corrijas el producto: lo que encuentres se informa.

| # | Devuelto | Qué se cambió | Qué comprobar |
|---|---|---|---|
| 1 | **T102, S1** | `ControlT94CarreraTest` deja la base limpia al terminar y marca el club operativo | Tu reproductor: T100 → Carrera T94 → Claude T94 pasa, sin aflojar lo que esperan |
| 2 | **T102, S2** | `InscripcionConcurrenteTest` marca el club operativo después de `migrate:fresh` y exige que la primera alta guarde | Corrida sola, en un proceso nuevo, llega a la concurrencia y pasa; tu diagnóstico ya no se desvía a Primera carga |
| 3 | **T102, H1** | `administrar-grupos`: la lista también muestra el nivel inactivo actual | La frase contra la pantalla |
| 4 | **T100, H1** | `operativo/cancelar-movimiento-caja`, paso 3 y «Qué hago si», más su ficha de fuentes | Condición por condición contra `PagoCuotaService.php:1256-1275`: incluye las cuatro que faltaban |
| 5 | **T100, H2** | Avisos de éxito: «Cobro cancelado.» y «Cobro anulado.». Paneles de `caja/detalle`, `caja/cancelar-movimiento` y `alumnos/show`, con el texto que aprobó Carlos | Por pantalla, los tres paneles. Si hay otra frase igual, informala |
| 6 | **Verificador** | `descargar-recibo-liquidacion`: dice «Página no disponible», como quedó con T109 | `php scripts/manual/verificar-manual.php`, y por pantalla el recibo de una liquidación sin pagar |

**Suite:** la completa de Claude dio 1.479 aprobadas, 0 fallidas y 6 omitidas, antes de los textos de pantalla (punto 5). Corré **una sola completa al final**, en `wings_testing_codex`, sin otra suite en la máquina. El comando `artisan test` sale con código 1 también cuando todo pasa: informá el resumen y el código, y si lo explicás, decilo.

**«Comprobado» es solo lo que ejecutaste.** Lo que leíste o dedujiste va en «Inferido».

## Reglas

- Pruebas solo en `wings_testing_codex`. No uses la base local de trabajo. Sin despliegue.
- La tabla de pruebas no se toca: proponé los cambios en el informe.
- Si encontrás algo para decidir, anotalo y seguí con el resto. Frená solo si no podés seguir con nada.

## Cómo entregar

- Agregá «Segunda vuelta» al final de `VERIFICACION-T100-CODEX.md` y de `VERIFICACION-T102-CODEX.md`.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T100 estado=cerrado verifica=Codex tiene=nadie`, y lo mismo con T102, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`.
- Entrada en `docs/00-estado/LOG-CODEX.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo y su `git diff`, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
