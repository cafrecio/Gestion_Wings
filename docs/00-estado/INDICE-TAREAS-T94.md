# Índice de tareas desde T94

Pedido de Carlos, 01/11/2026: se resuelven una a una y este índice se mantiene, **sacando las tareas ya cerradas**. Cada vez que se retoma el tema se le muestra de nuevo. El estado que manda es el del [tablero](QUIEN-HACE-QUE.md): antes de mostrarlo, comparar contra `tareas.json`.

| Tarea | De qué se trata | Estado |
|---|---|---|
| **T94** | Sacarle una frecuencia a un grupo da error si alguna vez se usó, y dice «Grupo actualizado» sin haberlo hecho si tiene alumnos. | Devuelta a Gemini por el control de Claude (`PRU-04/VERIFICACION-T94-CLAUDE.md`). Lo central anda; faltan 5 correcciones. Prompt listo para cuando T102 esté subida, porque tocan el mismo archivo. |
| **T97** | Una transferencia entre cuentas no se puede corregir ni anular. | Codex la está haciendo, con tu OK visual. Después la controla Claude. |
| **T100** | Un adelanto cobrado con un cero de más y después cancelado deja la cuota con el importe equivocado: $300.000 en vez de $30.000. | Hecha por Claude: cancelar borra la cuota nacida de ese cobro (Carlos eligió, 01/11). Falta el control de Codex, después de T97. |
| **T101** | Un gasto que carga el dueño en el turno de una operativa queda a nombre de ella. | Hecha por Claude: ahora figura a nombre de quien lo cargó. La controla Codex junto con T100, después de T97. |
| **T102** | Un nivel con grupos no se puede eliminar, y tampoco inactivar porque esa opción no existe. Regla de Carlos (01/11): con grupos activos no se inactiva; los grupos se pasan a otro nivel. | Hecha por Claude, con tu OK visual. Falta el control de Codex, después de T100. |
| **T104** | «Sin descubierto» solo se controla al pagar una liquidación; un gasto o una transferencia pueden dejar la cuenta en negativo igual. | Sin empezar. Claude lee los contratos y le explica a Carlos si el problema es real. |
| **T105** | Cada recibo PDF pesaba 1,7 MB. Con el recorte de fuentes baja a unos 225 KB y se ve igual; los 3 segundos de armarlo no bajan. | Hecha por Claude. Falta el control de Codex, después de T100. |
| **T106** | Cashflow e Historial cuentan las transferencias entre cuentas como ingreso y egreso: dicen $1.921.200 de ingresos cuando entraron $421.200. Reportes está bien. | Sin empezar. Se ve hoy con Carlos. |
| **T107** | Si alguien cierra la caja con la pantalla desactualizada, Wings no le avisa que el esperado cambió. El dueño ve el faltante, pero quien cierra no se entera. | Sin empezar. Se ve hoy con Carlos. |
| **T108** | Un alumno que vino de la primera carga no tiene inscripción registrada, y al abrirle una segunda ficha Wings le cobra inscripción ($8.000 en el caso de Thiago). | Sin empezar. Se ve hoy con Carlos. |
| **T109** | Escribir basura en la dirección de un filtro da error del servidor, y un recibo que no existe devuelve texto técnico. Hace falta sesión iniciada y no expone datos. | Sin empezar. Se ve hoy con Carlos. |

**Pendiente suelto de Carlos:** con qué rubro se compensa un cobro de más en una caja ya validada (`corregir-movimiento-caja-validada.md:44`); «Ajuste de saldo» no sirve, porque deja el cobro duplicado en el resultado.
