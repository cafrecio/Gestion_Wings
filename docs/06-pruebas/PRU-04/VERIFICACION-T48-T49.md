# T48 y T49 — Verificación de Claude · 17/10/2026 · APROBADA

Verificado sobre `e4c2424` (Gemini CAB). Falta una sola decisión de Carlos: el choque de nombres.

## Qué leí

Todo el cambio de código: `UsuarioWebController::update` y `toggleActivo`,
`SubrubroSueldoService` (`paraUsuarioOperativo`, `renombrarParaUsuarioOperativo`,
`existeChoqueParaOperativo`), `CajaService::obtenerCajaAbierta` (solo estado `ABIERTA`) y el
agregado en `resources/js/ds-app.js`. `paraProfesor` no cambió.

- **T48:** renombra el mismo subrubro (mismo id) solo si el usuario sigue siendo OPERATIVO, ya
  tiene subrubro y el nombre cambió. Guardado del usuario y renombre en una sola transacción.
- **T49:** desactivar o cambiar de rol con caja `ABIERTA` se frena con el aviso, por JSON y por
  redirección. Una caja `CERRADA` sin validar no frena (Carlos dijo «abierta»).
- **`ds-app.js`** maneja todos los interruptores de Wings, no solo el de usuarios. Busqué qué
  controladores responden `{error}` en JSON: solo `UsuarioWebController` (y `ReglaPrimerPago::destroy`,
  que no es un interruptor). Para alumnos, deportes, grupos, profesores, subrubros y medios de pago
  no cambia nada. El interruptor vuelve a su lugar y aparece el cartel; antes, el de «no podés
  inactivarte a vos mismo» quedaba movido aunque el servidor no lo cambiara.
- Sin JavaScript en vistas, sin vistas tocadas.

## Pruebas

Las 7 de Gemini (`RubroReservadoTest`, `UsuarioActivoWebTest`) pasan en `wings_testing_claude`.
Mis controles, [`evidencia-t48-t49-claude/VerificacionT48T49ClaudeTest.php`](evidencia-t48-t49-claude/VerificacionT48T49ClaudeTest.php),
4 pruebas en verde:

| Control | Resultado |
|---|---|
| Lo que ve el dueño: un sueldo pagado antes del cambio, en Cashflow | Aparece con el nombre nuevo, ya no con el viejo |
| Nombre y rol cambiados a la vez (deja de ser operativa), sin caja | Se guarda; su subrubro conserva el nombre viejo |
| Choque que difiere solo en mayúsculas («sandra vidal» contra «Op-Sandra Vidal») | Se detecta y no se guarda nada |
| Reactivar a una operativa inactiva | No lo frena la regla: solo aplica al desactivar |

Capturas de Gemini 04 y 05 miradas: el cartel aparece arriba y el interruptor queda en Activo.

## Para que decida Carlos

**El choque de nombres.** Si se renombra a una operativa con un nombre cuyo `Op-...` ya existe
(dos personas con el mismo nombre y apellido), hoy el sistema no guarda y dice «Ya existe un
subrubro de sueldo con ese nombre.» Gemini lo dejó así como medida segura y lo pregunta en su
informe (§2). Falta el sí de Carlos o la regla que prefiera.

## Observaciones que no cambian el veredicto

- Después del aviso de cambio de rol, el formulario vuelve con el rol que se intentó poner
  (captura 05: Administrador marcado). El usuario sigue siendo Operativo; el cartel lo dice, pero
  quien mire rápido puede creer que el cambio quedó.
- Quien deja de ser operativa conserva su subrubro con el nombre de ese momento. Es lo que ya
  decía el código («nunca se le quita»).

## Sin verificar

- El interruptor en un navegador: me apoyo en las capturas y en el registro de Gemini.
- Nombres de más de 252 caracteres: `Op-` + nombre puede pasar el largo de la columna. Ya pasaba
  antes de este cambio, al dar de alta.
