# Índice de tareas desde T94

Pedido de Carlos, 01/11/2026: se resuelven una a una y este índice se mantiene, **sacando las tareas ya cerradas**. Cada vez que se retoma el tema se le muestra de nuevo. El estado que manda es el del [tablero](QUIEN-HACE-QUE.md): antes de mostrarlo, comparar contra `tareas.json`.

| Tarea | De qué se trata | Estado |
|---|---|---|
| **T100** | Un adelanto cobrado con un cero de más y después cancelado deja la cuota con el importe equivocado: $300.000 en vez de $30.000. | Codex comprobó la plata en sus once casos y la devolvió por textos. Claude corrigió el manual y los avisos de éxito. Falta tu OK para dos textos de pantalla y volver a controlar. |
| **T102** | Un nivel con grupos no se puede eliminar, y tampoco inactivar porque esa opción no existe. Regla de Carlos (01/11): con grupos activos no se inactiva; los grupos se pasan a otro nivel. | Hecha por Claude, con tu OK visual. La controla Codex, etapa 3. |
| **T107** | Si alguien cierra la caja con la pantalla desactualizada, Wings no le avisa que el esperado cambió. El dueño ve el faltante, pero quien cierra no se entera. | Hecha por Claude: si el esperado cambió, no cierra y avisa el nuevo. La controla Gemini (`PRU-04/PROMPT-GEMINI-T107-T108-CONTROL.md`). |
| **T108** | Un alumno que vino de la primera carga no tiene inscripción registrada, y al abrirle una segunda ficha Wings le cobra inscripción ($8.000 en el caso de Thiago). | Hecha por Claude: quien ya tiene ficha no vuelve a pagar (decisión de Carlos). La controla Gemini, mismo prompt que T107. |
| **T110** | Guardar un grupo con la pantalla vieja borra sin aviso la frecuencia que agregó otra pestaña. | Nueva, sin empezar. Apareció al simular la tanda 5. |
| **T111** | Quien tiene una ficha por deporte puede quedar presente en dos clases a la misma hora. | Nueva, sin empezar. Apareció al simular la tanda 5. |
| **T112** | Un presente no saca a nadie de Revisión; el catálogo pide que sí. | Nueva: para que decidas si se arregla o se corrige el catálogo. |

**Pendiente suelto de Carlos:** con qué rubro se compensa un cobro de más en una caja ya validada (`corregir-movimiento-caja-validada.md:44`); «Ajuste de saldo» no sirve, porque deja el cobro duplicado en el resultado.
