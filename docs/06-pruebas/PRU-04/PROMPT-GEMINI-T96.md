# PROMPT — T96: controlar el botón Configurar en Caja — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 31/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T96 `en_curso, tiene=Gemini`.

## Qué pasó

El manual encontró un hueco: una vez configurado el efectivo del mostrador, ninguna pantalla permitía volver a cambiarlo; solo se llegaba escribiendo `/caja/configuracion`. Claude agregó un botón **Configurar** en Caja. Claude no controla su propio trabajo: el control es tuyo, sin dar por cierto nada de lo que dice este prompt.

**El aspecto ya lo aprobó Carlos** (`docs/06-pruebas/PRU-04/APROBAR-T96.html`). No opines sobre diseño ni cambies vistas: controlás funcionamiento, permisos y manual.

## Qué se cambió

- `resources/views/caja/index.blade.php`: la fila «Cajón del mostrador» (antes «Cajón del mostrador: abrile la caja a un operativo»).
- `app/Http/Controllers/CajaWebController.php`: `index` calcula `$hayCajaAbierta`.
- `resources/manual/admin/configurar-mostrador.md`: el manual ya no dice «consultá a soporte».
- `tests/Feature/MedioPagoCajonTest.php`: 4 pruebas nuevas.

## Qué controlar

Ejecutalo, no alcanza con leer. Pantallas por navegador contra una base tuya; las reglas también con pedidos directos.

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Dueño, mostrador configurado, ninguna caja abierta | **Configurar** habilitado y abre «Efectivo del mostrador»; **Abrir** sigue yendo a la apertura |
| 2 | Cambiar la cuenta y guardar | «Medio de efectivo configurado.»; quedan autor y fecha en `caja_mostrador`; la caja siguiente abre con la cuenta nueva |
| 3 | Dueño, con una caja abierta de cualquier operativo | **Configurar** inhabilitado, sin enlace; al pasar el mouse explica el motivo |
| 4 | Dueño, con caja abierta, escribiendo la dirección | Selector inhabilitado, aviso y sin **Guardar** |
| 5 | POST directo con otra cuenta y una caja abierta | Rechaza con «No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.»; la cuenta no cambia |
| 6 | Dueño, mostrador sin configurar | Un solo botón, **Configurar**; **Abrir** no aparece |
| 7 | Operativa en Caja | No ve **Configurar**; por dirección y por POST directo recibe 403 |
| 8 | Profesora | No llega a esta función; anotá qué responde |
| 9 | Se abre una caja mientras el dueño tiene abierta la pantalla de configuración, y después guarda otra cuenta | Rechaza y la cuenta no cambia |

**Prestá atención a esto, que Claude no comprobó:** al cambiar la cuenta, la caja anterior pudo dejar cambio retenido en el cajón y la caja nueva lo hereda con la cuenta nueva. Mirá qué pasa con esa plata en Cashflow, al peso: en qué cuenta queda cada importe al validar la caja anterior y la nueva. Si el saldo de alguna cuenta queda mal, es un hallazgo.

**También controlar:**

- El manual `configurar-mostrador` contra el código, condición por condición (`docs/06-pruebas/PRU-04/REVISOR-MANUAL.md` punto 6), y `php scripts/manual/verificar-manual.php`.
- La ficha `evidencia-t12-t13/fuentes/admin-configurar-mostrador.md`, y que las citas regeneradas de T95 en `admin-administrar-profesores.md` sean ciertas.
- Las 4 pruebas nuevas: tienen que fallar si se saca el botón o se quita la condición de caja abierta. Comprobalo y dejá el código como estaba.
- Suite rápida en `wings_testing_gemini` (`--exclude-group=lento`). Claude la corrió con 1 fallida por un contador de un documento, corregido, y repitió solo esa prueba: la corrida entera en verde todavía no existe.

## Reglas

- Pruebas solo en `wings_testing_gemini`. **No uses la base local de trabajo.** Sin despliegue: Cora trabaja en el sitio de prueba.
- No corregir el producto. Lo que encuentres se informa.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T96-GEMINI.md`: una fila por caso, lo comprobado y lo inferido por separado, cada hallazgo con gravedad y `archivo:línea`.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T96 estado=cerrado verifica=Gemini tiene=nadie`, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`, con el paso que corresponda.
- Entrada en tu bitácora `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
