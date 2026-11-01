# PROMPT — T117 y T118: segunda vuelta — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T117 y T118 `en_curso, tiene=Gemini`. Leé `docs/06-pruebas/PRU-04/VERIFICACION-T117-T118-CLAUDE.md`: ahí está lo que encontré y la evidencia. Si tenés otra tarea abierta, no mezcles commits.

## 1. T117: Configuración se traba

En `/configuraciones`, con un campo cambiado y la sesión vencida, Guardar dispara el aviso de salida de `resources/js/configuraciones.js:89`. Si la persona cancela, el campo y el botón quedan deshabilitados para siempre y sin mensaje, porque la promesa de `resources/js/bootstrap.js:18` no termina.

**Qué tiene que pasar:** lo que dice `ACC-006`. Va al ingreso con «Tu sesión expiró. Iniciá sesión nuevamente.», sin preguntar si quiere salir y sin que nada quede trabado. Lo cargado se pierde: el caso lo admite. Salir sin guardar con la sesión viva tiene que seguir preguntando, igual que hoy.

**Comprobá en navegador real**, con un clic de mouse de verdad, porque sin gesto Chrome no abre el aviso. `evidencia-t117-t118/control-claude/control-configuracion.mjs` lo reproduce y mira la red; su `LEEME.md` dice cómo correrlo. Probá también el guardado de las reglas de la misma pantalla, y buscá si hay otra pantalla con aviso de salida que escriba por `fetch`.

## 2. T118: tres pruebas que faltan

Agregalas en `tests/Feature/SesionVencidaYMayusculasT117T118Test.php` y comprobá que cada una falla sin el arreglo:

- `movimientos/index.blade.php:119`: una observación de 36 caracteres con acentos se ve entera, sin «…». Con `strlen` hoy pasaría igual.
- `TipoCajaWebController.php:52`: el alta guarda la abreviatura con el acento en mayúscula.
- `TipoCajaWebController.php:94`: la edición, lo mismo.

Actualizá el número de pruebas en `ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md`.

## 3. El sembrador

`evidencia-t117-t118/seed-test-gemini.php` corre `migrate:fresh` sobre la base que encuentre. Corrido desde la carpeta de trabajo, borra `wings_test`. Que antes de hacer nada compruebe que la base sea una de las descartables y, si no, se niegue.

## 4. El informe

Corregí `IMPLEMENTACION-T117-T118-GEMINI.md` y tu entrada de `LOG-GEMINI.md`:

- **Sección 3.D, cuenta desactivada:** llega al ingreso **sin aviso**; no se ve «Tu cuenta fue desactivada.». Poné el comportamiento comprobado. No lo arregles: eso lo decide Carlos.
- **Los nombres de las pruebas:** 5 de los 7 no existen. Copialos del archivo.
- **`TipoCajaWebController`:** el cambio es sobre la abreviatura, no sobre el nombre. Citá el código real.
- **Navegador:** separá lo ejecutado de lo leído, punto por punto de la tabla del primer prompt. Tu script corrió Cobrar, Guardar lista solo a 375, Cancelar clase y dos reingresos.
- **Base de datos:** el script no consulta la base. Decí de dónde sale cada dato, o sacalo.

## Reglas

- Sin tocar diseño ni vistas: nada de esto las necesita. Prohibido JavaScript dentro de una vista.
- Pruebas solo en `wings_testing_gemini`. Corré la suite rápida; la completa la corre Claude. Sin desplegar.
- Corré `npm run build` para ver el JS nuevo en tu copia descartable. No escribas en el sitio de prueba.
- El catálogo y el manual no se tocan.
- Separá lo comprobado de lo leído. Citá `archivo:línea` reales.

## Cómo entregar

- Sección «Segunda vuelta» al final del informe, con evidencia en tu carpeta.
- T117 y T118 `a_verificar, tiene=Claude, hizo=Gemini`, con `php scripts/tablero/tablero.php cambiar`.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
