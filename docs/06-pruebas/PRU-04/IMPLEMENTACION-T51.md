# T51 — Historia de movimientos manuales de caja

28/10/2026 · Codex CyE · implementación local, pendiente de Claude y aspecto de Carlos. Sin despliegue.

Editar conserva antes/después; eliminar conserva qué era. Cada corrección registra autor, fecha/hora y caja en `movimiento_operativo_correcciones`. No tiene FK al movimiento eliminado; conserva nombres de concepto, medio y autor tal como estaban. Se muestra debajo de los movimientos en Detalle, también a la operativa. Los filtros del dominio OPERATIVO se aplican a ambas fotos.

La migración nueva debe ejecutarse al desplegar. El registro empieza con esta implementación; no reconstruye correcciones anteriores sin evidencia.

`CajaCorreccionService` serializa caja/movimiento y guarda corrección + historia en la misma transacción. Reutiliza el control de efectivo y clasificación de `CajaService`; no cambia cálculos. Arqueo y reporte mensual vuelven a leer los movimientos vigentes; eliminar quita el importe y la historia no suma. El método interno de cálculo/actualización de CajaService conserva su interfaz; las dos rutas web pasan por el servicio de corrección con usuario explícito.

ADMIN o titular operativo pueden corregir cajas ABIERTAS/RECHAZADAS. Cobros con alumno/pago, cancelados y conceptos reservados se bloquean en formulario y servidor. La cancelación de cobros con motivo no se modifica. Se tomó de T27 la transacción con fotos y autor; este circuito conserva los formularios existentes, sin agregar JavaScript ni un nuevo circuito de contraasientos.

Verificación:

- 9 pruebas propias / 77 aserciones: editar, eliminar, arqueo, gasto mensual pendiente, historia visible, actor real, operativa ajena, profesor, automático con/sin concepto reservado, caja cerrada, fondos insuficientes y reversión ante fallo de auditoría.
- Capturas de respuestas reales HTTP de Laravel con datos sintéticos en `wings_testing_codex`; Chrome a 1366 y marco real 375, login de calibración. [Galería](APROBAR-T51.html). Sin desborde de documento ni errores JavaScript según `evidencia-t51/medidas-chrome.json`.
- Suite rápida: 1088 aprobadas, 6 omitidas, 8371 aserciones, 318,47 s, salida 0. Completa: 1217 aprobadas, 6 omitidas, 7 fallas HTTP 503 en InicioDeudaCuotasEInscripcionesTest e InicioOperativoTest, 10091 aserciones, 1588,63 s, salida 1. Repetidas aisladas: los dos archivos completos, 17 aprobadas / 152 aserciones / 31,55 s, salida 0. No se atribuye una causa a los 503 ni se presenta la completa como verde.
- Sintaxis PHP de los siete archivos propios y compilación/limpieza Blade correctas. Solo cambia la vista Detalle de caja; CSS intacto. Búsqueda estructural: las dos rutas de corrección llaman CajaCorreccionService. El índice indicó cobertura no disponible y Blade no parseable; se verificaron las fuentes reales.

Catálogo externo, solo propuesta para Claude:

- MOV-007, `MOV.json:281`, comprobación d): actual «No queda registro del valor anterior.»; propuesto «Detalle conserva antes/después, importe anterior, autor y fecha/hora».
- MOV-008, `MOV.json:333`, estado: actual `no_implementado`; propuesto `vigente` después de la verificación independiente. Comprobación c) ya dice «Queda registro de lo eliminado: qué era, quién lo eliminó y cuándo, igual que en Cashflow.»; conservarla. Fuente: quitar «Falta programar: T51» y citar CajaCorreccionService después de verificar.

No se modificó la copia del catálogo ni DU-07. La verificación independiente debe comprobar código y pantalla; esta entrega no cierra T51.

Corte: nueve fuentes propias conservaron su SHA-256 durante la validación. Cambios paralelos T12/T13 posteriores en layout/JavaScript/rutas y sus pruebas quedan fuera de esta entrega y de su certificación. Tablero separado desde HEAD con el mismo generador: solo T51 en el índice; T12/T13 permanecen intactas en el trabajo compartido. [Resultados](evidencia-t51/pruebas.txt) · [Fuentes](evidencia-t51/fuentes-sha256.json).
