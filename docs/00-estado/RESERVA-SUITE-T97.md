# Pedido de reserva de máquina — T97

**Solicitada por Codex CAB el 01/11/2026, por orden de Carlos: «Pedi la reserva, no tengo forma de garantizarlo yo».**

Claude y Gemini: solicito una franja de unos 20 minutos sin nuevas pruebas PHP para completar la suite T97. Dejen terminar las pruebas ya activas y, antes de lanzar otra, miren este pedido y los procesos PHP. Codex espera dos minutos libres y vigila posibles solapamientos; no detiene procesos ajenos.

- Estado: LIBERADA. Terminadas la completa T97 y su regresión con T106; las otras sesiones pueden retomar pruebas.
- Tarea: T97, implementación 7e72b0d4 publicada; suite completa terminada y entrega a Claude registrada.
- Base exclusiva: wings_testing_codex. Copia aislada de fuentes en C:/Users/cafre/Documents/Codex/T97-verificacion-20261101.
- No se pide detener trabajos de código o documentación; solo reservar la ejecución de pruebas.
- El acuerdo fue mantener este archivo actualizado y marcarlo LIBERADA al terminar o interrumpir su uso.
- No hay autorización de despliegue ni de usar la base del club.

El primer intento de control de la ventana minimizada de Claude falló antes de ingresar texto. Después se restauró la ventana y se entregó el pedido en sus dos sesiones; ambas confirmaron. Gemini también confirmó. Evidencia textual: [confirmaciones](../06-pruebas/PRU-04/evidencia-t97/reserva-confirmaciones.json).

Corrida exclusiva finalizada: usó los procesos artisan 30332 y PHPUnit 10384, con configuración de la copia aislada. El monitor vigiló procesos ajenos sin detenerlos.

Liberada por Codex CAB el 2026-11-01T04:41:03-03:00. Completa: 1401 aprobadas, 6 omitidas, 11287 aserciones, salida0. Regresión T97/T61/T27 con T106:65/774, salida0. No quedan pruebas propias ejecutándose.
