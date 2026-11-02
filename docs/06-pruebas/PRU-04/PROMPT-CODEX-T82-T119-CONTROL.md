# PROMPT — Control de T119 y segunda vuelta de T82 — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026, a pedido de Carlos.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado.

**Por etapas:** 1) T119, 2) T82. Cada una termina con su informe, su tablero y su commit antes de empezar la otra. Una devuelta no frena la otra.

Claude hizo las dos y no controla su propio trabajo. No des por cierto nada de este prompt. **Ejecutalo, no alcanza con leer:** lo que no ejecutaste se declara como leído.

# Etapa 1: T119

Marcá T119 `en_curso, tiene=Codex`. Commit `502101f0`, sin desplegar. Tres controles estaban solo en la pantalla: con sesión de operativa y el envío armado a mano se podía cancelar una serie, cobrar por un medio inactivo y cambiarle el deporte a un alumno. Lo encontró la prueba técnica de la tanda 5: `docs/06-pruebas/PRU-04/prueba-tecnica/INFORME.md`.

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Operativa: `PATCH /clases/{id}/cancelar` con `cancelar_serie`, por JSON y por formulario | 403 o error «La serie la cancela el dueño. No se canceló ninguna clase.» Ninguna clase cambia, tampoco la pedida |
| 2 | Operativa cancela una clase sola; dueño cancela la serie | Siguen funcionando |
| 3 | Medio inactivo en Cobrar, Nuevo movimiento, Agregar, Cashflow, transferencia (salida y destino) y pago de liquidación | «Elegí un medio de pago activo.» Nada se escribe, en ningún rol |
| 4 | Medio inexistente | Sigue el mensaje de siempre, sin error del servidor |
| 5 | Corregir un movimiento de caja o de Cashflow cuyo medio se desactivó después | Se conserva ese medio; no se puede pasar a otro inactivo |
| 6 | Editar un alumno mandando otro deporte, operativa y dueño | «No se puede cambiar el deporte de un alumno: cargá otra ficha para el otro deporte.» Deporte, grupo y plan no cambian |
| 7 | Editar un alumno sin cambiar el deporte; segunda ficha en otro deporte por el alta | Siguen funcionando |

**Mirá además, y decí qué encontraste:**

- **¿Quedó otro camino web que escriba un medio inactivo?** Claude buscó `exists:tipos_caja,id`. La API está apagada (`bootstrap/app.php:17`) y no se tocó.
- **¿Rompe algo real?** Por ejemplo, una caja abierta con un movimiento en un medio que se desactiva a mitad del turno: cerrar y validar tienen que seguir funcionando. Una transferencia para vaciar un medio ya inactivo hoy se rechaza: hay que reactivarlo antes. Decí si eso traba a alguien.
- **¿Hay otros controles que estén solo en la pantalla?** Si encontrás uno, se anota con quién llega; no se repara.

Código: `app/Rules/MedioDePagoActivo.php`, `ClaseWebController.php:611`, `AlumnoWebController.php:510`, `CajaWebController.php`, `CashflowWebController.php`, `LiquidacionWebController.php`, `TransferenciaCashflowService.php`, `CashflowCorreccionService.php`. Pruebas: `tests/Feature/ControlesDelServidorT119Test.php`; mutaciones de Claude en `docs/06-pruebas/PRU-04/prueba-tecnica/mutaciones-t119.txt`.

Entrega: `docs/06-pruebas/PRU-04/VERIFICACION-T119-CODEX.md`. Sin hallazgos graves ni medios: `cerrado, verifica=Codex`. Con hallazgos: `devuelto, tiene=Claude`.

# Etapa 2: T82, segunda vuelta

Marcá T82 `en_curso, tiene=Codex`. Devolviste T82 con H1 a H4 ([tu informe](../../04-tecnico/seguridad-vps/2026-10-28-ensayo-restauracion/VERIFICACION-T82-CODEX.md)). Claude corrigió `scripts/servidor/ensayo-restauracion.sh` y [anotó el resultado](../../04-tecnico/seguridad-vps/2026-10-28-ensayo-restauracion/README.md).

**Autorización.** Carlos pidió este control y te pasa este prompt: podés entrar con `ssh vps` y correr lo que dice acá, con los límites de «Reglas». Nada más. Si hace falta otra cosa en el servidor, frená y preguntale.

| # | Control | Qué tiene que pasar |
|---|---|---|
| H1 | `FUENTE=wingstest`, con el script nuevo | Compara `total_calculado` y `liquidacion_detalles` con importes reales. Hoy: $3.026.280. Con una consulta rota, en una copia del script fuera del repositorio: «NO COINCIDE» y código de salida distinto de 0 |
| H2 | Provocá un fallo en cada etapa que puedas: descarga, clonado y `kill` a mitad de camino | Código distinto de 0 y sin carpeta, base, usuario, proceso ni puerto. El script acepta `REPO` para simular GitHub caído |
| H3 | `/root/wings-backup/` | `restaurar.sh` ya no está: Carlos autorizó renombrarlo a `restaurar.sh.viejo-20260830-no-usar`. Nada lo llama: comprobá `respaldar.sh` y los cron. `DESPLIEGUE-PRODUCCION.md` indica el correcto |
| H4 | Producción, desde Drive | La huella y la fecha de `/root/.config/rclone/rclone.conf` no cambian. La copia temporal se borra |
| Nuevo | El resto de la tabla de tu informe | Decí si algo de lo que aprobaste se rompió con estos cambios |

**Respondé también:** ¿con esto se puede cerrar T82, sabiendo que un servidor vacío y el volumen real siguen sin probarse y que el ensayo se repite antes y después de desplegar? Los tres pendientes de Carlos no frenan el cierre, como dijiste.

Entrega: «Segunda vuelta» en tu informe. Sin hallazgos graves ni medios: `cerrado, verifica=Codex`. Con hallazgos: `devuelto, tiene=Claude`.

# Reglas

- Es un control: no corrijas. Hallazgos con gravedad y `archivo:línea` reales. Separá lo comprobado de lo inferido.
- Pruebas solo en `wings_testing_codex`. Corré solo las pruebas del área tocada, sin la suite rápida ni la completa (`AGENTS.md` §6-ter). Sin desplegar.
- **Hay trabajo sin subir de otros en la misma carpeta** (T113, T115, T116): controlá sobre una copia limpia de `main`, no sobre la carpeta.
- En el servidor: no toques las bases `wings` ni `wingstest`, ni `/home/wings`, ni `/home/wingstest`. No corras `restaurar.sh` en modo `EN-SERIO` ni el archivo viejo. Fuera de 03:00–03:30 UTC. Dejá `/root` como lo encontraste.
- Sin personas, credenciales, huellas de claves ni volcados en la evidencia.
- Entrada en `docs/00-estado/LOG-CODEX.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
