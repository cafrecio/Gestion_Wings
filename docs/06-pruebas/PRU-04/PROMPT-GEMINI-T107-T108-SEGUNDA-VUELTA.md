# PROMPT — T107 y T108: tres comprobaciones que faltaron — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Codex ya terminó su segunda vuelta: la máquina está libre.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T107, T108 y T100 `en_curso, tiene=Gemini`.

Tu informe (`VERIFICACION-T107-T108-GEMINI.md`) declara comprobadas tres cosas que tu prueba no ejecuta. El resto no se repite. No corrijas el producto: lo que encuentres se informa.

| # | Lo que dice el informe | Lo que ejecuta `VerificacionT107T108GeminiTest.php` | Qué hacer |
|---|---|---|---|
| 1 | **C1:** concurrencia real, dos procesos PHP | Ninguna segunda conexión: no hay `proc_open` ni otra conexión a la base | Dos conexiones reales: un cobro en efectivo y el cierre de esa caja a la vez, en los dos órdenes. Tiene que quedar el cobro dentro del esperado o rechazado, nunca como faltante falso. Mirar `tests/Feature/ControlT94CarreraTest.php` como ejemplo; **dejá la base limpia al terminar** |
| 2 | **Caso 14:** primera carga por Excel | Llama a `InscripcionService::sincronizar`, no pasa por la carga | Subir por la carga real un Excel con «Inscripción: Sí» y con «No», también con dos filas del mismo DNI, y mirar qué queda |
| 3 | **Casos 1 y 9:** «por pantalla» | Pedidos HTTP | Hacerlos en el navegador, sobre `wings_testing_gemini`: el aviso de 1 con la pantalla al día, y el aviso previo de 9 |

**T100, además, una lectura corta.** Codex la devolvió solo por dos frases de `evidencia-t12-t13/fuentes/operativo-cancelar-movimiento-caja.md` (líneas 18 y 31), ya corregidas. Leelas contra `PagoCuotaService.php:1256-1275` y contra `VERIFICACION-T100-CODEX.md`, «Segunda vuelta». Lo demás de T100 ya está controlado: no lo repitas. Si están bien: `php scripts/tablero/tablero.php cambiar T100 estado=cerrado verifica=Gemini tiene=nadie`. Si no: `estado=devuelto tiene=Claude`.

**«Comprobado» es solo lo que ejecutaste.** Lo que leíste o dedujiste va en «Inferido».

## Reglas

- Pruebas solo en `wings_testing_gemini`. No uses la base local de trabajo. Sin despliegue.
- Si encontrás algo para decidir, anotalo y seguí con lo demás.
- La tabla de pruebas no se toca: proponé los cambios en el informe.

## Cómo entregar

- Agregá «Segunda vuelta» al final de `VERIFICACION-T107-T108-GEMINI.md`, con lo de T100.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T107 estado=cerrado verifica=Gemini tiene=nadie`, y lo mismo con T108, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo y su `git diff`, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
