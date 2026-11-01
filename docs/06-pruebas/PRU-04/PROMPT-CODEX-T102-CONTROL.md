# PROMPT — Etapa 3: T102, controlar niveles inactivos — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026. Es la etapa 3 de `PROMPT-CODEX-T100-CONTROL.md`: va después de T100 y T106. T105 la controla Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T102 `en_curso, tiene=Codex`.

Claude la hizo y no controla su propio trabajo: el control es tuyo, sin dar por cierto nada de lo que dice este prompt.

## T102: nivel inactivo y grupo que cambia de nivel

**Decisión de Carlos, 01/11/2026:** un grupo no puede quedar sin nivel. Un nivel con grupos activos no se puede inactivar: esos grupos se pasan a otro nivel o se desactivan.

Código: `NivelWebController::toggleActivo`, `GrupoWebController` (`store`, `edit`, `update`, `toggleActivo`, `avisoNivelDestino`), `grupos/_form.blade.php`, `niveles/index.blade.php`, `ds-app.js`, `app.css`. Contrato: `Wings-contrato-alumno-grupo-deporte-deuda-v5.md`, «Nivel del grupo». Manual: `administrar-niveles` y `administrar-grupos`.

| Caso | Qué tiene que pasar |
|---|---|
| Inactivar con grupos activos | No se inactiva; el aviso dice cuántos y qué hacer |
| Inactivar con grupos solo inactivos, o sin grupos | Se inactiva; el grupo inactivo conserva su nivel |
| Crear un grupo o cambiarle el nivel, por pantalla y forzando el pedido | Un nivel inactivo no se ofrece ni se acepta |
| Cambiar de nivel | Conserva alumnos, precios, clases, cuotas y pagos |
| Destino donde ese deporte ya tiene grupo | Se rechaza; no se unen los grupos |
| Editar con otro dato rechazado | Si algo no se guarda, el nivel tampoco |
| Activar un grupo cuyo nivel está inactivo | No se activa; el aviso lo explica |
| Inactivar y asignar al mismo tiempo, con dos conexiones | Ningún grupo activo termina en un nivel inactivo |

Mirá en especial lo que usa el nombre o el nivel del grupo: reportes por deporte y nivel de meses anteriores, liquidaciones, clases, cobranza y recibos. El contrato afirma que los eventos de reportes conservan el `nivel_id` con que se registraron; Claude lo leyó en `HistorialReporteObserver` y no lo ejecutó de punta a punta: **ejecutalo**. Un grupo que cambia de nivel con importes en meses cerrados no puede cambiar ningún importe.

## Reglas

- Pruebas solo en `wings_testing_codex`. **No uses la base local de trabajo.** Sin despliegue: Cora trabaja en el sitio de prueba.
- No corregir el producto. Lo que encuentres se informa.
- La tabla de pruebas no se toca (`AGENTS.md` §5-ter): proponé los cambios en el informe.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T102-CODEX.md`: una fila por caso, lo comprobado y lo inferido por separado, cada hallazgo con gravedad y `archivo:línea`.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T102 estado=cerrado verifica=Codex tiene=nadie`, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`, con el paso que corresponda.
- Entrada en `docs/00-estado/LOG-CODEX.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
