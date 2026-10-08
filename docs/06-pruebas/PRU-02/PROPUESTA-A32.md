# A32 — Estado de tu propia cuenta

08/10/2026 · Codex CyE. **Antecedente aprobado por Carlos: «A-32 OK». Aplicado y cerrado.** [Entrega y capturas posteriores](IMPLEMENTACION-A32.md).

## Comprobado antes de aplicar

`usuarios/index.blade.php` pasa `checked=true` y `disabled=true` a la cuenta propia activa. La pantalla real muestra la etiqueta Activo y el botón a derecha, pero todo el control tiene opacidad 0,45 por `.ds-toggle--disabled`: esa atenuación provoca la apariencia apagada. No está desmarcado.

Leído el cuerpo actual de `UsuarioWebController::toggleActivo`: rechaza la desactivación propia antes de guardar. La propuesta no modifica ese método ni permisos.

## Propuesta concreta

- Cuenta propia: texto Activo, subtítulo Tu cuenta y candado; sin interruptor ni dirección de activación.
- Otras cuentas: componente x-ds.toggle original, con sus estados reales.
- En celular: Editar alineado con Nuevo; estado debajo, ambos a derecha. En escritorio: disposición y geometría de tarjetas/botones conservadas.

[Visor ANTES/propuesta](evidencia/a6-a10/visor-a32.html). Nueve capturas reales: escritorio y marco 375×667, propia/otra activa/inactiva, login de calibración. Usuarios y correos ficticios en `wings_testing_codex`; no se usa la base del club.

La variante deriva del Blade original y es renderizada por el controlador real en el laboratorio. No es un HTML inventado de la pantalla. [Control](evidencia/a6-a10/control-a32.json): originales de Usuarios, toggle compartido, app.css y controlador idénticos por SHA256. Otros interruptores y geometría de escritorio idénticos; a 375, Editar/Nuevo x=224–320, estado termina en x=320. Capturas miradas por Codex.

Sintaxis PHP/Blade y scripts correctos; controles documentales y enlaces comprobados. Sin build ni suite funcional por ser una propuesta documental. Servidor del laboratorio detenido al entregar.

## Aprobación y entrega

Carlos aprobó las imágenes: «A-32 OK». Variante aplicada exclusivamente en `usuarios/index.blade.php`, capturas finales y suite completa verdes. Tablero cerrado, verifica Carlos (§6a). El ensayo de nueve capturas y sus huellas anteriores se conservan como antecedente; el [control posterior](evidencia/a6-a10/control-a32-aplicado.json) certifica lo aplicado. Sin despliegue.
