# Índice de tareas desde T94

Pedido de Carlos, 01/11/2026: se resuelven una a una y este índice se mantiene, **sacando las tareas ya cerradas**. Cada vez que se retoma el tema se le muestra de nuevo. El estado que manda es el del [tablero](QUIEN-HACE-QUE.md): antes de mostrarlo, comparar contra `tareas.json`.

| Tarea | De qué se trata | Estado |
|---|---|---|
| **T100** | Un adelanto cobrado con un cero de más y después cancelado deja la cuota con el importe equivocado: $300.000 en vez de $30.000. | Hecha por Claude: cancelar borra la cuota nacida de ese cobro (Carlos eligió, 01/11). La controla Codex, etapa 1. |
| **T102** | Un nivel con grupos no se puede eliminar, y tampoco inactivar porque esa opción no existe. Regla de Carlos (01/11): con grupos activos no se inactiva; los grupos se pasan a otro nivel. | Hecha por Claude, con tu OK visual. La controla Codex, etapa 3. |
| **T104** | «Sin descubierto» solo se controla al pagar una liquidación; un gasto o una transferencia pueden dejar la cuenta en negativo igual. | Respondida por Claude: es real y ya lo decidiste el 13/10 (DU-28: avisar y dejar seguir), anotado en T60 para después de producción. Falta tu OK para cerrarla como repetida. |
| **T105** | Cada recibo PDF pesaba 1,7 MB. Con el recorte de fuentes baja a unos 225 KB y se ve igual; los 3 segundos de armarlo no bajan. | Hecha por Claude. La controla Gemini, después de T94. |
| **T106** | Cashflow e Historial cuentan las transferencias entre cuentas como ingreso y egreso: dicen $1.921.200 de ingresos cuando entraron $421.200. Reportes está bien. | Hecha por Claude: sin filtro de cuenta no suman; con filtro sí. La controla Codex, etapa 2. |
| **T107** | Si alguien cierra la caja con la pantalla desactualizada, Wings no le avisa que el esperado cambió. El dueño ve el faltante, pero quien cierra no se entera. | Hecha por Claude: si el esperado cambió, no cierra y avisa el nuevo. La controla Gemini (`PRU-04/PROMPT-GEMINI-T107-T108-CONTROL.md`). |
| **T108** | Un alumno que vino de la primera carga no tiene inscripción registrada, y al abrirle una segunda ficha Wings le cobra inscripción ($8.000 en el caso de Thiago). | Hecha por Claude: quien ya tiene ficha no vuelve a pagar (decisión de Carlos). La controla Gemini, mismo prompt que T107. |
| **T109** | Escribir basura en la dirección de un filtro da error del servidor, y un recibo que no existe devuelve texto técnico. Hace falta sesión iniciada y no expone datos. | Hecha por Claude: el filtro inválido se ignora y el recibo inexistente muestra la pantalla común. La controla Gemini, después de T105. |

**Pendiente suelto de Carlos:** con qué rubro se compensa un cobro de más en una caja ya validada (`corregir-movimiento-caja-validada.md:44`); «Ajuste de saldo» no sirve, porque deja el cobro duplicado en el resultado.
