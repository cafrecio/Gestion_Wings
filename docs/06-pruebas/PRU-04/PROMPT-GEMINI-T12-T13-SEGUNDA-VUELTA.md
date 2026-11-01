# PROMPT — T12 y T13: segunda vuelta del control del manual — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T12 y T13 `en_curso, tiene=Gemini`.

## Qué pasó

Tu hallazgo del camino 1 era correcto: lo reproduje y era error mío. El manual mandaba al dueño a cargar el retiro con **Agregar**, y Wings lo rechaza. Lo corregí para que diga lo que Wings hace hoy, sin cambiar código. Controlá la corrección sin dar por cierto nada de este prompt.

Qué cambió:

- `resources/manual/admin/dinero-del-dueno-retiros-aportes.md`: Canal 2 reescrito y tres preguntas nuevas o corregidas. Se sacó la captura `paso2`, que era del formulario de la operativa.
- `resources/manual/operativo/registrar-gasto-caja.md`: la regla del dinero del dueño, el resumen, la introducción y los ejemplos que nombraban retiros.
- Las fichas de esas dos páginas y las dos secciones de `docs/06-pruebas/PRU-04/APROBAR-T12-T13-T94-T109.html`.
- Tu prueba pasó a `docs/06-pruebas/PRU-04/evidencia-t12-t13/`. En `tests/` dejaba `DocumentacionNoMienteTest` en rojo (1470 declaradas, 1474 reales) y daba por correcto un defecto. **Las pruebas de un control van a la carpeta de evidencia.**

## Qué controlar

**1. Las dos páginas, palabra por palabra, ejecutando cada camino.** Tené en cuenta `docs/06-pruebas/PRU-04/evidencia-t12-t13/RetiroDuenoCajonRealidadTest.php`, pero escribí tu propia prueba en esa misma carpeta. Lo que hay que comprobar:

- Un retiro no entra al turno, ni por el dueño ni por la operativa.
- Por Cashflow baja la cuenta **Efectivo** una sola vez, con faltante si salió del cajón durante el turno y sin faltante si salió después del cierre.
- Validar con ese faltante no vuelve a bajar la cuenta. Reportes lo cuenta como retiro y el resultado no cambia.
- Efectivo puede quedar en negativo hasta que se valida. Mirá también si la frase «el saldo se acomoda al validar» es cierta.
- Un aporte entra con **Agregar** solo si su subrubro está en **Ambos** y en **Afecta caja**.
- Agregar le ofrece al dueño subrubros que después rechaza, con el texto exacto de la página.

**2. Otras páginas.** Buscá en las 59 cualquier otra frase que suponga que un retiro, o algún subrubro **Solo Admin**, entra a un turno. Mirá sobre todo `validar-rechazar-caja`, `cerrar-caja`, `auditar-detalle-caja` y `administrar-rubros`.

**3. El ? en un navegador real.** En la primera vuelta usaste pruebas HTTP y lo dijiste bien, pero el pedido era el navegador. Con cada rol, en las pantallas que cambiaron con T96, T97 y T102: abrí el ? y anotá qué página abre. Dejá la captura en la carpeta de evidencia.

**4. Tres capturas que informaste viejas.** Las revisé y no me dan viejas: comprobalo o rebatime con el motivo concreto.

| Captura | Lo que vi yo |
|---|---|
| `operativo/consultar-movimientos-paso3.png` | Muestra la columna **Registró**. Sí trae un rubro que el catálogo actual no tiene, «Gastos Menores» |
| `operativo/cobrar-cuota-paso2.png` | `caja/cobrar.blade.php` y `caja/cobrar-cuota.blade.php` no cambiaron desde `d797d0b5` |
| `admin/primera-carga-descargar-plantilla-paso2.png` | Las vistas de `primera-carga` no cambiaron desde `d797d0b5` |

**5. La galería.** Que las dos secciones nuevas digan lo mismo que las páginas.

## Reglas

- Es un control: no corrijas páginas, capturas ni fichas. Lo que encuentres se informa con gravedad y `archivo:línea`. Revisá que la línea citada sea la real: en la primera vuelta citaste 30-35 y el texto estaba en 38-43.
- Separá lo comprobado de lo inferido. No declares «comprobado en navegador» lo que fue un pedido HTTP, ni «ejecutado» lo que leíste.
- Pruebas solo en `wings_testing_gemini`. Sin desplegar. No toques el sitio de prueba: Codex está recorriendo ahí los días 48 a 57.
- Comando: `php scripts/manual/verificar-manual.php`, más `tests/Feature/AyudaManualT12T13Test.php`, `tests/Feature/AyudaBotonPorRolTest.php` y `tests/Feature/DocumentacionNoMienteTest.php`.
- T116 es una decisión de Carlos y no se cuenta como hallazgo. Si Carlos cambia el comportamiento, el manual se vuelve a tocar.

## Cómo entregar

- Agregá «Segunda vuelta» a `docs/06-pruebas/PRU-04/VERIFICACION-T12-T13-T94-T109-GEMINI.md`, con lo comprobado y lo inferido por separado.
- Sin graves ni medios: T12 y T13 `a_verificar, tiene=Carlos`, paso «Manual al día con T94 a T109, controlado por Gemini: OK de Carlos con APROBAR-T12-T13-T94-T109.html». Con hallazgos: `devuelto, tiene=Claude`.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
