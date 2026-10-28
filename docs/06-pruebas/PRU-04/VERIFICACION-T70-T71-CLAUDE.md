# Verificación de T70 y T71 (Codex, `df7442bc`) — Claude CyE, 28/10/2026

**Resultado: aprobada en lógica.** Con el OK visual de Carlos del mismo día («Vistas, parte visual OK»), T70 y T71 quedan cerradas. Sin despliegue: se suben al sitio de prueba con el resto del alcance.

## Lo que se comprobó

- Código leído: migración (`inactivo_desde`, `alumno_estado_historial`, `monto_perdido`, `deudas_perdidas`), `Alumno` (fecha e historial en cada cambio de `activo`), `DeudaInactivosService`, el controlador y su ruta (dentro de `ensure.admin.web`, y el servicio vuelve a exigir ADMIN activo), `CobranzaEstadoService`, `ReporteMensualService`, `ReporteAlumnosService` y `PagoCuotaService`.
- Todos los caminos que pasan a Inactivo usan Eloquent (interruptor, cobro con reactivación, cola de Revisión), así que todos dejan fecha e historial.
- Cobro, recargos y totales usan `saldo_pendiente`, que ya descuenta lo perdido.
- Pruebas de Codex y del área (cobranza, reportes, T25, T33, T38, T65, T84, Revisión): **237/237** en `wings_testing_claude`.
- Ataques propios, **3/3** ([prueba](evidencia-verificacion-t70-t71-claude/VerificacionT70T71ClaudeTest.php), fuera de la suite permanente):
  1. Una cuota pagada en parte y dada por perdida no admite cobro cuando el alumno vuelve, ni forzando el período. El rechazo es por el saldo, no por otra cosa.
  2. La baja que sale de la cola de Revisión deja fecha, historial y cuenta en el reporte del mes.
  3. Dar por perdida dos veces seguidas: la segunda responde 422 y no escribe nada.

## Observación (no frena)

La cuota perdida conserva el estado `PENDIENTE`, con saldo 0. Todo lo que se revisó usa el saldo, no el estado. Si en el futuro alguien filtra solo por `PENDIENTE`, la vería como deuda: conviene recordarlo al tocar cobranza.
