# PROMPT — T116: controlar el retiro del dueño en el turno — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T116 `en_curso, tiene=Gemini`.

Claude la hizo y no controla su propio trabajo: el control es tuyo, sin dar por cierto nada de lo que dice este prompt.

**Decisión de Carlos, 01/11/2026, opción A:** «Retiro de dueños» va en **Ambos**. El dueño se lleva plata del cajón, lo carga con **Agregar** en el turno, y la operativa no cierra con un faltante que no es suyo. Aceptó que la operativa también pueda cargar uno. No se programa «solo el dueño».

Cambios: `CatalogosSeeder`, `ClasificacionSubrubros::NUEVOS`, la migración `2026_11_01_150000_retiro_de_duenos_en_ambos`, `RetiroEnTurnoT116Test`, `PERMISOS-ROLES.md` y las páginas `admin/dinero-del-dueno-retiros-aportes` y `operativo/registrar-gasto-caja`.

| # | Caso, con plata | Qué tiene que pasar |
|---|---|---|
| 1 | Abre con $10.000, cobra $90.000 en efectivo, el dueño agrega un retiro de $50.000 | Esperado $50.000, a nombre del dueño. La operativa lo ve en Movimientos y en Detalle |
| 2 | Ella cuenta $50.000, cierra y el dueño valida | Sin faltante. La cuenta Efectivo baja $50.000 una sola vez; en Reportes figura en retiros y el resultado no cambia |
| 3 | La operativa carga un retiro desde **Nuevo** y desde **Agregar** | Queda a su nombre, también en Cashflow al validar |
| 4 | La operativa corrige o elimina el retiro del dueño con el turno abierto | Se puede, el autor no cambia y queda en la historia de correcciones. ¿El dueño se entera antes de validar? Decilo |
| 5 | Retiro mayor a lo que hay en el cajón | Se rechaza y no se registra nada |
| 6 | Otra operativa y la profesora, por pantalla y por pedido directo | 403 |
| 7 | Retiro de un banco por Cashflow, y retiro del cajón por Cashflow con el turno abierto | El primero no toca el turno. El segundo deja el faltante que explica el manual y baja la cuenta una sola vez |
| 8 | Dos veces el mismo retiro, en Agregar y en Cashflow | Baja dos veces: el manual lo advierte. Confirmá que lo dice claro |
| 9 | Base existente con el subrubro en Solo Admin: `migrate`, y después `CatalogosSeeder` | Queda en Ambos. No cambia a ningún otro subrubro ni a ningún movimiento ya guardado |

**Prestá atención a esto:**

- Todo lo que usa `permitido_para` para mostrar: Movimientos, Detalle, Historial y lo que ve la operativa con retiros ya cargados. Buscá otro lugar donde un retiro deje de esconderse y no debería.
- Las 59 páginas del manual: ninguna puede seguir diciendo que un retiro no entra al turno.

## Reglas

- Pruebas solo en `wings_testing_gemini`. **No uses la base local de trabajo.** Sin despliegue.
- No corregir el producto. Lo que encuentres se informa.
- La tabla de pruebas no se toca (`AGENTS.md` §5-ter): proponé los cambios en el informe.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T116-GEMINI.md`: una fila por caso, lo comprobado y lo inferido por separado, cada hallazgo con gravedad y `archivo:línea`.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T116 estado=cerrado verifica=Gemini tiene=nadie`, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`, con el paso que corresponda.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
