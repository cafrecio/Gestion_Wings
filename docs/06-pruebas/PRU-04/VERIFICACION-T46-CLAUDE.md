# Verificación de T46 (Codex, `c475fa6c`) — Claude CAB, 28/10/2026

**Resultado: aprobada en lógica.** Con el OK visual de Carlos (28/10, en CyE), T46 queda cerrada.

## Lo que se comprobó

- Código leído completo: `ProfesorEstadoService::cambiar` (solo ADMIN activo; bloquea la profesora; si tiene clases no canceladas futuras o en curso, no la desactiva y dice cuántas; desactiva los usuarios vinculados, les cambia el `remember_token` y borra sus sesiones, todo en una transacción), `User::puedeIngresar`, los dos middlewares, el login, `Profesor::clasesPendientesLiquidacion`, los cambios de `LiquidacionService` y `LiquidacionWebController::create`, y `bloquearProfesoresActivos` en alta, edición y reasignación de clases.
- **Todas las rutas con sesión pasan por `ensure.active.web`**: se recorrieron las 175 rutas con `route:list` y ninguna autenticada queda sin ese control. Una profesora desactivada pierde el acceso en la petición siguiente, aunque tenga la sesión abierta.
- La API sigue deshabilitada (`bootstrap/app.php`, línea 17), así que `Admin\ClaseController` y `ClaseService::asignarProfesor`, que no controlan si la profesora está activa, no se pueden alcanzar.
- La edición de la profesora (`ProfesorWebController::update`) no acepta `activo`: no hay otro camino para desactivarla sin pasar por el servicio.
- Pruebas de Codex y del área (35 archivos: liquidaciones, clases, profesoras, usuarios, ingreso, sueldos, T45, T62, T66, T87): **276/276, 1851 aserciones**, en `wings_testing_claude`. [Salida](evidencia-verificacion-t46-claude/area.txt).

## Ataques propios

[Prueba](evidencia-verificacion-t46-claude/VerificacionT46ClaudeTest.php), 4/4. Va en evidencia, fuera del contador de la suite.

1. **Clase futura compartida con otra profesora:** también bloquea la baja, porque sigue a su nombre. Al sacarla de la clase, se desactiva y la clase queda con la otra.
2. **Clase de hoy ya terminada:** no bloquea la baja. Después no se la puede pasar a la tarde con la inactiva adentro: la edición exige profesoras activas, y la clase no cambia.
3. **Dos meses dictados:** se liquida septiembre y la profesora sigue apareciendo por octubre. Repetir septiembre forzando la ruta da error, sin duplicar. Liquidado octubre, desaparece. $5.000 cada mes.
4. **Clase dictada sin lista:** después de la baja no aparece para liquidar. Validada a mano por el dueño, aparece y se liquida.

## Observación (no frena)

La propuesta de Codex para el texto de PRO-004 en la tabla de pruebas (c: «su sesión se corta; reactivarla requiere habilitar el usuario por separado») describe lo que hace el código. Queda para que Carlos la acepte junto con las otras tres propuestas pendientes (ALU-017.b, ALU-025.b, REP-006).
